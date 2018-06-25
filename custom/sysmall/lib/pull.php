<?php
/* 
* 处理拉取到选货商城的逻辑
* author by wanghaichao
* date 2018/6/20
*/
class sysmall_pull
{
	public $is_compere;
	
	public function pull($params){
		$itemInfo=app::get('sysitem')->model('item')->getRow('item_id',array('item_id'=>$params['item_id']));
		if(empty($itemInfo)){			
			throw new \LogicException(app::get('sysmall')->_('商品信息有误,请重新选择'));
		}
		//判断商品是否在选货商城中
		$mallItemInfo=app::get('sysmall')->model('item')->getRow('item_id',array('item_id'=>$params['item_id']));
		if(empty($mallItemInfo) && $params['is_compere']!=1){   //说明是店铺拉取商品不是主持人拉取商品
			throw new \LogicException(app::get('sysmall')->_('拉取商品有误,请重新选择'));
		}

		//判断商品是不是本店铺的商品
		$shopItemInfo=app::get('sysitem')->model('item')->getRow('item_id,title',array('item_id'=>$params['item_id'],'shop_id'=>$params['shop_id']));
		//如果是本店铺的商品则不能拉取
		if($shopItemInfo){
			if($params['is_compere']==1){
				return $this->insertSellerItem($params['seller_id'],$shopItemInfo['item_id']);
			}else{
				throw new \LogicException(app::get('sysmall')->_('不能拉取本店铺的商品'.$shopItemInfo['title'].',请重新选择'));
			}
		}
		return $this->getShopItemId($params);
	}
	/* action_name (par1, par2, par3)
	* 判断店铺是否已经拉取过这个商品,如果拉过这个商品则返回商品id,
	* author by wanghaichao
	* date 2018/6/20
	*/
	public function getShopItemId($params){
		$objItem=app::get('sysitem')->model('item');

		$selectItem=$objItem->getRow('item_id,title',array('init_item_id'=>$params['item_id'],'shop_id'=>$params['shop_id']));
		if(!empty($selectItem)){
			if($params['is_compere']==1){   //如果是主持人,插入主持人商品关联表中
				return $this->insertSellerItem($params['seller_id'],$selectItem['item_id']);
			}else{    //不是主持人警告商品已经被选择
				throw new \LogicException(app::get('sysmall')->_('商品'.$selectItem['title'].'已经拉取到本店铺中,请重新选择'));
			}
		}else{    //此时插入商品表中,
			$itemInfo=$objItem->getRow('*',array('item_id'=>$params['item_id']));
			unset($itemInfo['item_id'],$itemInfo['seller_id']);   //去掉item_id;
			$insertItem=$itemInfo;
			$insertItem['shop_id']=$params['shop_id'];
			$insertItem['init_shop_id']=$itemInfo['shop_id'];
			$insertItem['init_item_id']=$params['item_id'];
			//获取店铺信息
			$shopInfo = app::get('sysmall')->rpcCall('shop.get',array('shop_id'=>$params['shop_id']));
			$insertItem['shop_name']=$shopInfo['shop_name'];
			$insertItem['shop_descript']=$shopInfo['shop_descript'];
			$insertItem['shop_type']=$shopInfo['shop_type'];
			$insertItem['shop_area']=$shopInfo['shop_area'];
			$insertItem['shop_addr']=$shopInfo['shop_addr'];
			$insertItem['created_time']=time();
			$insertItem['modified_time']=time();
			$item_id=$objItem->insert($insertItem);   //新item_id
			$init_item_id=$params['item_id'];   //原item_id
			//处理商品的库存
			$this->insertItemStore($item_id,$init_item_id);
			//处理商品的状态(上下架)
			$this->insertItemStatus($item_id,$init_item_id,$params['shop_id']);
			//处理商品销售数量等
			$this->insertItemCount($item_id);
			//处理商品自然属性等的
			$this->insertItemNatureProps($item_id,$init_item_id);
			//处理sku信息
			$this->insertSku($item_id,$init_item_id,$params['shop_id']);
			//处理商品详情信
			$this->insertItemDesc($item_id,$init_item_id);

			if($params['is_compere']==1){   //如果是主持人,插入主持人商品关联表中
				return $this->insertSellerItem($params['seller_id'],$item_id);
			}else{
				return true;
			}
			//echo "<pre>";print_r($insertItem);die();
		}
		
	}
	/* action_name (par1, par2, par3)
	* 处理商品库存
	* author by wanghaichao
	* date 2018/6/21
	*/
	public function insertItemStore($item_id,$init_item_id){
		$objItemStore=app::get('sysitem')->model('item_store');
		//取出商品的库存信息
		$initItemStore=$objItemStore->getRow('*',array('item_id'=>$init_item_id));   //获取原来的商品库存
		unset($initItemStore['item_id']);
		$insertData=$initItemStore;
		$insertData['item_id']=$item_id;

		return $objItemStore->save($insertData);
	}
	
	/* action_name (par1, par2, par3)
	* 处理商品上下架
	* author by wanghaichao
	* date 2018/6/21
	*/
	public function insertItemStatus($item_id,$init_item_id,$shop_id){
		$objItemStatus=app::get('sysitem')->model('item_status');
		//取出商品的库存信息
		$insertData=$initItemStatus;
		$insertData['item_id']=$item_id;
		$insertData['shop_id']=$shop_id;
		$insertData['approve_status']='instock';    //拉过来的时候都是下架的状态
		$insertData['delist_time']=time();
		return $objItemStatus->save($insertData);
	}
	
	/* action_name (par1, par2, par3)
	* 处理商品各种数量的
	* author by wanghaichao
	* date 2018/6/21
	*/
	public function insertItemCount($item_id){
		$insert=array('item_id'=>$item_id);
		return app::get('sysitem')->model('item_count')->insert($insert);
	}
	
	/* action_name (par1, par2, par3)
	* 处理商品的自然属性(如果有的话)
	* author by wanghaichao
	* date 2018/6/21
	*/
	public function insertItemNatureProps($item_id,$init_item_id){
		$objItemNatureProps=app::get('sysitem')->model('item_nature_props');
		$data=$objItemNatureProps->getList("*",array('item_id'=>$init_item_id));
		if(empty($data)){
			return true;
		}else{
			foreach($data as $k=>&$insert){
				$insert['item_id']=$item_id;
				$objItemNatureProps->save($insert);
			}
			return true;
		}
	}
	
	/* action_name (par1, par2, par3)
	* 处理商品详情
	* author by wanghaichao
	* date 2018/6/21
	*/
	public function insertItemDesc($item_id,$init_item_id){
		$objItemDesc=app::get('sysitem')->model('item_desc');
		$data=$objItemDesc->getRow('*',array('item_id'=>$init_item_id));
		$insert=$data;
		$insert['item_id']=$item_id;
		return $objItemDesc->insert($insert);
	}
	
	/* action_name (par1, par2, par3)
	* 处理商品的sku信息
	* author by wanghaichao
	* date 2018/6/21
	*/
	public function insertSku($item_id,$init_item_id,$shop_id){
		$objSku=app::get('sysitem')->model('sku');
		//先取出原来的商品的sku信息
		$initSku=$objSku->getList('*',array('item_id'=>$init_item_id));
		if($initSku){
			foreach($initSku as $k=>&$data){
				$insert='';
				$insert=$data;
				unset($insert['sku_id'],$insert['item_id']);
				$insert['init_sku_id']=$data['sku_id'];
				$insert['item_id']=$item_id;
				$insert['shop_id']=$shop_id;
				$new_sku_id=$objSku->insert($insert);
				//处理sku的库存
				$this->insertSkuStore($item_id,$new_sku_id,$data['sku_id']);
				//处理商品sku规格的
				$this->insertSpecIndex($item_id,$new_sku_id,$data['sku_id']);

			}
		}else{
			return true;
		}
	}
	
	/* action_name (par1, par2, par3)
	* 处理sku库存的
	* author by wanghaichao
	* date 2018/6/21
	*/
	public function insertSkuStore($item_id,$new_sku_id,$init_sku_id){
		$objSkuStore=app::get('sysitem')->model('sku_store');
		$data=$objSkuStore->getRow("*",array('sku_id'=>$init_sku_id));   //取出原来的sku库存信息
		$insert=$data;
		$insert['item_id']=$item_id;
		$insert['sku_id']=$new_sku_id;
		return $objSkuStore->save($insert);
	}
	
	/* action_name (par1, par2, par3)
	* 处理商品sku规格
	* author by wanghaichao
	* date 2018/6/21
	*/
	public function insertSpecIndex($item_id,$new_sku_id,$init_sku_id){
		$objSpecIndex=app::get('sysitem')->model('spec_index');
		$data=$objSpecIndex->getList("*",array('sku_id'=>$init_sku_id));
		if(empty($data)){
			return true;
		}
		foreach($data as $k=>$v){	
			$v['item_id']=$item_id;
			$v['sku_id']=$new_sku_id;
			$objSpecIndex->insert($v);
		}
		return true;
	}
	/* 
	* 插入主持人关联表中
	* author by wanghaichao
	* date  2018/6/21
	*/
	public function insertSellerItem($seller_id,$item_id){
		$insert=array();
		$objSellerItem=app::get('sysitem')->model('seller_item');
		$data=$objSellerItem->getRow('id',array('item_id'=>$item_id,'seller_id'=>$seller_id));
		if($data){
			throw new \LogicException(app::get('sysmall')->_('您已经选过该商品,请重新选择'));
		}
		$insert['item_id']=$item_id;
		$insert['seller_id']=$seller_id;
		$insert['created_time']=time();
		$insert['deleted']=0;
		return $objSellerItem->insert($insert);
	}

}

?>