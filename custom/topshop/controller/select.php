<?php

/* 选货商城相关逻辑
 * author by wanghaichao
 * date 2018/6/19
 */
class topshop_ctl_select extends topshop_controller{

    public $limit = 10;  //分页,每页的数量
    public $exportLimit = 100;
	/* 选货商城商品列表
	* author by wanghaichao
	* date 2018/6/19
	*/
	public function index(){
        $pages =  input::get('pages',1);
        $filter = array(
            // 'shop_id' => $this->shopId,
            //'approve_status' => $status,
			'fields'=>'b.item_id,b.title,b.image_default_id,b.supply_price,b.nospec,c.store,b.shop_id,a.created_time,a.modified_time,d.list_time,d.delist_time,e.shop_name',
            'page_no' =>intval($pages),
            'page_size' => intval($this->limit),
        );
		if(input::get('item_title')){
			$filter['item_title']=input::get('item_title');
			$pagedata['item_title']=input::get('item_title');
		}
		if(input::get('item_no')){
			$filter['item_no']=input::get('item_no');
			$pagedata['item_no']=input::get('item_no');
		}
		if(input::get('min_price')){
			$filter['min_price']=input::get('min_price');
			$pagedata['min_price']=input::get('min_price');
		}
		if(input::get('max_price')){
			$filter['max_price']=input::get('max_price');
			$pagedata['max_price']=input::get('max_price');
		}

		if($this->sellerInfo['is_compere']==1){
			$filter['shop_id']=$this->shopId;
		}
        $itemsList = app::get('topshop')->rpcCall('mall.item.list',$filter);

        foreach ($itemsList['list'] as $k=>$v)
        {
            // 此处可优化为前端成生二维码
            $itemsList['list'][$k]['qr_code'] = $this->__qrCode($v['item_id']);
			$itemsList['list'][$k]['is_pull']=$this->is_pull($v['item_id']);
        }

        $pagersFilter['pages'] = time();
        $totalPage = ceil($itemsList['count']/$this->limit);
        $pagers = array(
            'link'=>url::action('topshop_ctl_select@index',$pagersFilter),
            'current'=>$pages,
            'use_app' => 'topshop',
            'total'=>$totalPage,
            'token'=>time(),
        );

        $pagedata['pagers'] = $pagers;
		$pagedata['shop_id']=$this->shopId;		//店铺id
		$pagedata['is_compere']=$this->sellerInfo['is_compere'];  //判断是不是主持人
        $pagedata['total'] = $itemsList['count'];
        $pagedata['item_list'] = $itemsList['list'];
		$pagedata['exportLimit']=$this->exportLimit;
        //$pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
		return $this->page('topshop/select/index.html', $pagedata);
	}

    /**
     * 商品二维码生成，指向手机端
     * @param $itemId
     * @return string
     */
    private function __qrCode($itemId,$size = 60)
    {
        $url = url::action("topwap_ctl_item_detail@index",array('item_id'=>$itemId));
        return getQrcodeUri($url, $size, 10);
    }

	/* action_name (par1, par2, par3)
	* 判断是否已经拉过某个商品
	* author by wanghaichao
	* date 2018/6/22
	*/

	public function is_pull($item_id){
		$itemInfo=app::get('sysitem')->model('item')->getRow('item_id',array('init_item_id'=>$item_id,'shop_id'=>$this->shopId));
		if($itemInfo){
			if($this->sellerInfo['is_compere']==1){
				$sellerItem=app::get('sysitem')->model('seller_item')->getRow('id',array('item_id'=>$itemInfo['item_id']));
				if($sellerItem){
					return true;
				}else{
					return false;
				}
			}else{
				return true;
			}
		}else{
			$thisShopItemInfo=app::get('sysitem')->model('item')->getRow('item_id',array('item_id'=>$item_id,'shop_id'=>$this->shopId));
			if($thisShopItemInfo){
				if($this->sellerInfo['is_compere']==1){
					$sellerItem=app::get('sysitem')->model('seller_item')->getRow('id',array('item_id'=>$thisShopItemInfo['item_id']));
					if($sellerItem){
						return true;
					}else{
						return false;
					}
				}
			}else{
				return false;
			}
		}
	}
	
}
