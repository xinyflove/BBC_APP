<?php
/**
 * 处理代售商品更新数据
 * Class sysmall_update
 * @auth xinyufeng
 * @time 2018-07-05
 */
class sysmall_update {

	protected $skuStoreMdl;
	protected $specIndexMdl;
	protected $_itemTicket;

	/**
	 * 代售商品更新
	 * @param $params item_id;shop_id
	 * @return bool
	 * @auth xinyufeng
	 */
	public function update($params){

		$itemInfo = app::get('sysitem')->model('item')->getRow('init_item_id, init_is_change', $params);

		if(empty($itemInfo))
		{
			throw new \LogicException('无商品数据或无权操作非本店铺商品');
		}
		if(!$itemInfo['init_item_id'])
		{
			throw new \LogicException('此商品非代售商品，不能执行此操作');
		}
		if(!$itemInfo['init_is_change'])
		{
			throw new \LogicException('对应的原始商品没有更新数据，无需更新');
		}

		$db = app::get('sysmall')->database();
		$db->beginTransaction();
		try
		{
			// 更新item表
			$res = $this->_updateItem($params['item_id'], $params['shop_id'], $itemInfo['init_item_id'], $msg);
			if(!$res)
			{
				throw new \LogicException($msg);
			}
			// 更新item_store表
			$res = $this->_updateItemStore($params['item_id'], $itemInfo['init_item_id'], $msg);
			if(!$res)
			{
				throw new \LogicException($msg);
			}
			// 更新item_nature_props表
			$res = $this->_updateItemNatureProps($params['item_id'], $itemInfo['init_item_id'], $msg);
			if(!$res)
			{
				throw new \LogicException($msg);
			}
			// 更新item_desc表
			$res = $this->_updateItemDesc($params['item_id'], $itemInfo['init_item_id'], $msg);
			if(!$res)
			{
				throw new \LogicException($msg);
			}
			// 更新sku表
			$res = $this->_updateSku($params['item_id'], $params['shop_id'], $itemInfo['init_item_id'], $msg);
			if(!$res)
			{
				throw new \LogicException($msg);
			}
			// 更新套券信息
			$res = $this->_updateItemTicket($params['item_id'], $params['shop_id'], $itemInfo['init_item_id']);

			$db->commit();
			return true;
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw new \LogicException($e->getMessage());
			return false;
		}
	}

	/**
	 * 更新item表
	 * @param $item_id
	 * @param $shop_id
	 * @param $init_item_id
	 * @param $msg
	 * @return bool
	 * @auth xinyufeng
	 */
	protected function _updateItem($item_id, $shop_id, $init_item_id, &$msg)
	{
		if(empty($item_id) || empty($shop_id) || empty($init_item_id))
		{
			$msg = '参数错误更新失败[code:10000]';
			return false;
		}

		$itemMdl = app::get('sysitem')->model('item');
		// 获取原始商品item信息
		$initItemInfo = $itemMdl->getRow('*', array('item_id'=>$init_item_id));
		$itemInfo = $initItemInfo;
		$itemInfo['init_item_id'] = $initItemInfo['item_id'];
		$itemInfo['init_shop_id'] = $initItemInfo['shop_id'];
		$itemInfo['init_is_change'] = 0;
		$itemInfo['item_id'] = $item_id;
		$itemInfo['shop_id'] = $shop_id;
		// 代售商品的成本价 -> 原始商品的供货价
		$itemInfo['cost_price'] = $itemInfo['supply_price'];
		$itemInfo['supply_price'] = 0;
		unset($itemInfo['shop_cat_id'], $itemInfo['created_time'], $initItemInfo);

		$filter = array(
			'item_id' => $item_id,
			'shop_id' => $shop_id,
		);
		$res = $itemMdl->update($itemInfo, $filter);

		if($res) return true;
		$msg = '更新失败[code:10001]';
		return false;
	}

	/**
	 * 更新item_store表
	 * @param $item_id
	 * @param $init_item_id
	 * @param $msg
	 * @return bool
	 * @auth xinyufeng
	 */
	protected function _updateItemStore($item_id, $init_item_id, &$msg)
	{
		if(empty($item_id) || empty($init_item_id))
		{
			$msg = '参数错误更新失败[code:10010]';
			return false;
		}

		$itemStoreMdl = app::get('sysitem')->model('item_store');
		// 获取原始商品item信息
		$initItemStore = $itemStoreMdl->getRow('*', array('item_id'=>$init_item_id));
		$itemStore = $initItemStore;
		$itemStore['item_id'] = $item_id;
		unset($initItemStore);

		$filter = array(
			'item_id' => $item_id,
		);
		$res = $itemStoreMdl->update($itemStore, $filter);
		if($res) return true;
		$msg = '更新失败[code:10011]';
		return false;
	}

	/**
	 * 更新item_nature_props表
	 * @param $item_id
	 * @param $init_item_id
	 * @param $msg
	 * @return bool
	 * @auth xinyufeng
	 */
	protected function _updateItemNatureProps($item_id, $init_item_id, &$msg)
	{
		if(empty($item_id) || empty($init_item_id))
		{
			$msg = '参数错误更新失败[code:10020]';
			return false;
		}

		$itemNaturePropsMdl = app::get('sysitem')->model('item_nature_props');
		$data = $itemNaturePropsMdl->getList("*", array('item_id'=>$init_item_id));

		if(empty($data))
		{
			return true;
		}
		else
		{
			// 移除代售商品的自然属性
			$itemNaturePropsMdl->delete(array('item_id'=>$item_id));
			$time = time();
			foreach($data as $k=>$save){
				$save['item_id'] = $item_id;
				$save['modified_time'] = $time;
				$res = $itemNaturePropsMdl->insert($save);
				if(!$res)
				{
					$msg = '更新失败[code:10021]';
					return false;
				}
			}
			return true;
		}
	}

	/**
	 * 更新item_desc表
	 * @param $item_id
	 * @param $init_item_id
	 * @param $msg
	 * @return bool
	 * @auth xinyufeng
	 */
	protected function _updateItemDesc($item_id, $init_item_id, &$msg)
	{
		if(empty($item_id) || empty($init_item_id))
		{
			$msg = '参数错误更新失败[code:10030]';
			return false;
		}

		$objItemDescMdl = app::get('sysitem')->model('item_desc');
		$data = $objItemDescMdl->getRow('*',array('item_id'=>$init_item_id));
		$update = $data;
		$update['item_id'] = $item_id;
		$res = $objItemDescMdl->update($update, array('item_id'=>$item_id));

		if($res) return true;
		$msg = '更新失败[code:10031]';
		return false;
	}

	/**
	 * 更新sku表
	 * @param $item_id
	 * @param $shop_id
	 * @param $init_item_id
	 * @param $msg
	 * @return bool
	 * @auth xinyufeng
	 */
	protected function _updateSku($item_id, $shop_id, $init_item_id, &$msg)
	{
		if(empty($item_id) || empty($shop_id) || empty($init_item_id))
		{
			$msg = '参数错误更新失败[code:10040]';
			return false;
		}

		$skuMdl = app::get('sysitem')->model('sku');
		$this->skuStoreMdl = app::get('sysitem')->model('sku_store');
		// 原始商品sku数据列表
		$initSkuList = $skuMdl->getList('*', array('item_id'=>$init_item_id));

		/*处理单规格开始*/
		if(count($initSkuList) == 1)
		{
			// 原始商品sku数据赋值给待售商品sku
			$skuInfo = reset($initSkuList);
			// 代售商品的成本价 -> 原始商品的供货价
			$skuInfo['cost_price'] = $skuInfo['supply_price'];
			$skuInfo['supply_price'] = 0;
			// 移除不需要更新的字段
			unset($skuInfo['sku_id'], $skuInfo['item_id'], $skuInfo['shop_id'], $skuInfo['shop_cat_id'], $skuInfo['init_sku_id']);
			// 更新代售商品sku表
			$res = $skuMdl->update($skuInfo, array('item_id'=>$item_id));
			if(!$res)
			{
				$msg = '更新失败[code:10041]';
				return false;
			}

			// 原始商品sku_store数据
			$initSkuStore = $this->skuStoreMdl->getRow('store, freez', array('item_id'=>$init_item_id));
			// 更新代售商品sku_store表
			$res = $this->skuStoreMdl->update($initSkuStore, array('item_id' => $item_id));
			if(!$res)
			{
				$msg = '更新失败[code:10042]';
				return false;
			}

			return true;
		}
		/*处理单规格结束*/

		/*处理多规格开始*/
		// 代售商品的sku信息列表
		$currSkuList = $skuMdl->getList('sku_id, init_sku_id', array('item_id'=>$item_id));
		// 代售商品的sku的init_sku_id
		$currSkuInitSkuIds = array_column($currSkuList, 'init_sku_id');
		$currSkuAssocInitSkuIds = array_column($currSkuList, 'sku_id', 'init_sku_id');
		// 需要更新的代售商品的sku的init_sku_id
		$currSkuExistInitSkuIds = array();

		foreach($initSkuList as $k => $initSkuV){

			$skuInfo = $initSkuV;	// 原始商品的sku信息赋值给代售商品的sku信息
			// 代售商品的成本价 -> 原始商品的供货价
			$skuInfo['cost_price'] = $initSkuV['supply_price'];
			$skuInfo['supply_price'] = 0;
			if(in_array($initSkuV['sku_id'], $currSkuInitSkuIds))
			{
				// update
				$currSkuExistInitSkuIds[] = $initSkuV['sku_id'];	// 需要更新的代售商品的sku的init_sku_id
				// 移除不需要更新的字段
				unset(
					$skuInfo['sku_id'],
					$skuInfo['item_id'],
					$skuInfo['shop_id'],
					$skuInfo['shop_cat_id'],
					$skuInfo['init_sku_id']
				);
				// 更新数据 sku表
				$res = $skuMdl->update($skuInfo, array('init_sku_id'=>$initSkuV['sku_id']));
				if($res)
				{
					// 更新sku库存信息
					$res = $this->_updateSkuStore(
						$currSkuAssocInitSkuIds[$initSkuV['sku_id']],
						$initSkuV['sku_id'],
						$msg);
					if(!$res)
					{
						$msg = '更新失败[code:10041]';
						return false;
					}

					// 更新spec信息
					$res = $this->_updateSpecIndex(
						$currSkuAssocInitSkuIds[$initSkuV['sku_id']],
						$item_id,
						$initSkuV['sku_id'],
						$msg);
					if(!$res)
					{
						$msg = '更新失败[code:10042]';
						return false;
					}
				}
				else
				{
					$msg = '更新失败[code:10043]';
					return false;
				}
			}
			else
			{
				// insert
				// 重新赋值
				unset($skuInfo['sku_id']);
				$skuInfo['item_id'] = $item_id;
				$skuInfo['shop_id'] = $shop_id;
				$skuInfo['init_sku_id'] = $initSkuV['sku_id'];
				// 添加数据
				$sku_id = $skuMdl->insert($skuInfo);
				if($sku_id)
				{
					// 添加sku库存信息
					$res = $this->_insertSkuStore(
						$sku_id,
						$item_id,
						$initSkuV['sku_id'],
						$msg);
					if(!$res)
					{
						$msg = '更新失败[code:10044]';
						return false;
					}

					// 添加spec信息
					$res = $this->_updateSpecIndex(
						$sku_id,
						$item_id,
						$initSkuV['sku_id'],
						$msg);
					if(!$res)
					{
						$msg = '更新失败[code:10045]';
						return false;
					}
				}
				else
				{
					$msg = '更新失败[code:10046]';
					return false;
				}
			}
		}

		// 移除删除的sku信息
		$removeSkuInitSkuIds = array_diff($currSkuInitSkuIds, $currSkuExistInitSkuIds);
		foreach ($removeSkuInitSkuIds as $rmInitSkuId)
		{
			// 删除sku
			$res = $skuMdl->delete(array('init_sku_id'=>$rmInitSkuId));
			if(!$res)
			{
				$msg = '更新失败[code:10047]';
				return false;
			}
			// 删除sku_store
			$res = $this->skuStoreMdl->delete(array('sku_id'=>$currSkuAssocInitSkuIds[$rmInitSkuId]));
			if(!$res)
			{
				$msg = '更新失败[code:10048]';
				return false;
			}
			// 删除spec
			$res = $this->specIndexMdl->delete(array('sku_id'=>$currSkuAssocInitSkuIds[$rmInitSkuId]));
			if(!$res)
			{
				$msg = '更新失败[code:10049]';
				return false;
			}
		}
		/*处理多规格结束*/
		return true;
	}


	/**
	 * 单条更新sku库存信息
	 * @param $sku_id
	 * @param $init_sku_id
	 * @param $msg
	 * @return bool
	 * @auth xinyufeng
	 */
	protected function _updateSkuStore($sku_id, $init_sku_id, &$msg)
	{
		if(empty($sku_id) || empty($init_sku_id))
		{
			$msg = '参数错误更新失败[code:10050]';
			return false;
		}

		if(empty($this->skuStoreMdl))
		{
			$this->skuStoreMdl = app::get('sysitem')->model('sku_store');
		}

		$initSkuStore = $this->skuStoreMdl->getRow('store, freez', array('sku_id'=>$init_sku_id));   // 原始sku信息

		$filter = array('sku_id' => $sku_id);
		$res = $this->skuStoreMdl->update($initSkuStore, $filter);

		if($res) return true;
		$msg = '更新失败[code:10051]';
		return false;
	}

	/**
	 * 单条添加sku库存信息
	 * @param $sku_id
	 * @param $item_id
	 * @param $init_sku_id
	 * @param $msg
	 * @return bool
	 * @auth xinyufeng
	 */
	protected function _insertSkuStore($sku_id, $item_id, $init_sku_id, &$msg)
	{
		if(empty($sku_id) || empty($item_id) || empty($init_sku_id))
		{
			$msg = '参数错误更新失败[code:10060]';
			return false;
		}

		if(empty($this->skuStoreMdl))
		{
			$this->skuStoreMdl = app::get('sysitem')->model('sku_store');
		}

		$initSkuStore = $this->skuStoreMdl->getRow('store, freez', array('sku_id'=>$init_sku_id));   // 原始sku信息

		$skuStore = $initSkuStore;
		$skuStore['sku_id'] = $sku_id;
		$skuStore['item_id'] = $item_id;

		$res = $this->skuStoreMdl->insert($skuStore);

		if($res) return true;
		$msg = '更新失败[code:10061]';
		return false;
	}

	/**
	 * 更新spec信息
	 * @param $sku_id
	 * @param $init_sku_id
	 * @param $msg
	 * @return bool
	 * @auth xinyufeng
	 */
	protected function _updateSpecIndex($sku_id, $item_id, $init_sku_id, &$msg)
	{
		if(empty($sku_id) || empty($item_id) || empty($init_sku_id))
		{
			$msg = '参数错误更新失败[code:10070]';
			return false;
		}

		if(empty($this->specIndexMdl))
		{
			$this->specIndexMdl = app::get('sysitem')->model('spec_index');
		}

		// 移除代售的spec
		$this->specIndexMdl->delete(array('sku_id'=>$sku_id, 'item_id'=>$item_id));

		// 原始spec信息
		$initSpecIndexList = $this->specIndexMdl->getList('*', array('sku_id'=>$init_sku_id));
		foreach($initSpecIndexList as $initSpec)
		{
			$specIndex = $initSpec;
			$specIndex['sku_id'] = $sku_id;
			$specIndex['item_id'] = $item_id;
			$res = $this->specIndexMdl->insert($specIndex);
			if(!$res)
			{
				$msg = '更新失败[code:10072]';
				return false;
			}
			unset($specIndex);
		}

		if($res) return true;
		$msg = '更新失败[code:10071]';
		return false;
	}

	/**
	 * 更新套券信息
	 * @param $item_id
	 * @param $shop_id
	 * @param $init_item_id
	 * @param $msg
	 * @return bool
	 */
	protected function _updateItemTicket($item_id, $shop_id, $init_item_id, &$msg)
	{
		if(empty($item_id) || empty($init_item_id))
		{
			$msg = '参数错误更新失败[code:10080]';
			return false;
		}
		if(empty($this->_itemTicket))
		{
			$this->_itemTicket = app::get('sysitem')->model('ticket');
		}

		// 删除之前的套券疏数据
		$this->_itemTicket->delete(array('item_id'=>$item_id));

		// 获取原始商品套券数据
		$ticket = $this->_itemTicket->getList('*', array('item_id'=>$init_item_id));
		if($ticket)
		{
			foreach ($ticket as $t)
			{
				unset($t['id']);
				$t['item_id'] = $item_id;
				$t['shop_id'] = $shop_id;
				$t['created_time'] = time();
				$t['modified_time'] = $t['created_time'];
				$res = $this->_itemTicket->insert($t);
				if(!$res)
				{
					$msg = '更新失败[code:10081]';
					return false;
				}
			}
		}

		return true;
	}
}
