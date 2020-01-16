<?php
/**
 * Created by PhpStorm.
 * @desc:
 * @author: admin
 * @date: 2017-11-07 16:01
 */
class topwap_ctl_sales extends topwap_controller{
    public function index(){

        $params=input::get();
        if(empty($params['sales_id'])) $params['sales_id']=0;
        $shopId=$params['shop_id'];
        $pagedata['shopId']=$shopId;
        $salesInfo=app::get('syspromotion')->model('sales')->getRow('*',$params);
        $overseaId=unserialize($salesInfo['item']);
        $pagedata['tagName']=$salesInfo['title'];
        $pagedata['desc']=$salesInfo['desc'];
        $pagedata['banner']=$salesInfo['banner'];
        $pagedata['pager']=$params['page_no']?$params['page_no']:0;
        $pagedata['sell_out_img'] = app::get('sysconf')->getConf('item.sell.out.default.img');
        if($shopId)
        {
            $pagedata['shopcat'] = app::get('topwap')->rpcCall('shop.cat.get',array('shop_id'=>$shopId));
        }

        $start=$pagedata['pager']*10;
		/*add_2018/6/28_by_wanghaichao_start*/
		$baseUrl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$pagedata['signPackage'] = kernel::single('topwap_jssdk')->index($baseUrl);
		/*add_2018/6/28_by_wanghaichao_end*/
        $sysitemMdlObj=app::get('sysitem')->model('item');
        $data=$sysitemMdlObj->getList('item_id,title,price,image_default_id',array('item_id|in'=>$overseaId,'order'=>''),$start,100);
		//echo "<pre>";print_r($data);die();
		//微信分享的
		$weixin['imgUrl']= base_storager::modifier($salesInfo['sharepic']);
		$weixin['linelink']= url::action("topwap_ctl_sales@index",array('shop_id'=>$shopId,'sales_id'=>$salesInfo['sales_id']));
		$weixin['shareTitle']=$salesInfo['sharetitle'];
		$weixin['descContent']=$salesInfo['sharedesc'];
		$pagedata['weixin']=$weixin;
		/*add_2018/8/9_by_wanghaichao_start*/
		$sort=unserialize($salesInfo['sort']);
		//echo "<pre>";print_r($sort);die();
		/*add_2018/8/9_by_wanghaichao_end*/
		
        if($data)
        {
            $itemsList['list'] = array_bind_key($data,'item_id');
            $itemIds = array_keys($itemsList['list']);
            $activityParams['item_id'] = implode(',',$itemIds);
            $activityParams['status'] = 'agree';
            $activityParams['end_time'] = 'bthan';
            $activityParams['start_time'] = 'sthan';
            $activityParams['fields'] = 'activity_id,item_id,activity_tag,price,activity_price';
            $activityItemList = app::get('topc')->rpcCall('promotion.activity.item.list',$activityParams);
            if($activityItemList['list'])
            {
                foreach($activityItemList['list'] as $key=>$value)
                {
                    $itemsList['list'][$value['item_id']]['activity'] = $value;
                    $itemsList['list'][$value['item_id']]['price'] = $value['activity_price'];
                    $itemsList['list'][$value['item_id']]['mkt_price'] = $value['price'];
                }
            }

            $data=array_values($itemsList['list']);
        }
        if(is_array($data) && $data){
            foreach($data as $ik => $iv){
//                $saleStatus=app::get('sysitem')->model('item_status')->getRow('approve_status',array('item_id'=>$iv['item_id']));
//
//                if($saleStatus['approve_status']=='instock'){
//                    unset($data[$ik]);
//                    continue;
//                }
                $storeInfo=$this->_getItemStore($iv['item_id']);
                $iv=array_merge($iv,$storeInfo);
                $data[$ik]=$iv;
				$data[$ik]['sort']=$sort[$iv['item_id']];
                unset($storeInfo);
            }
        }
		
        foreach($overseaId as $kk => $vv){
            foreach($data as $dd){
                if($vv==$dd['item_id']){
                    $sortData[]=$dd;
                }
            }
        }
        if($salesInfo['enabled']==1){
            $pagedata['data']=$this->array_sort($sortData,'sort');
        }

        $shopdata = app::get('topwap')->rpcCall('shop.get',array('shop_id'=>$shopId));
		$pagedata['shopdata']=$shopdata;
        if($params['request_type']=='ajax'){
            if(empty($pagedata['data'])){
                $data['html']=false;
                $data['pagers'] = $pagedata['pagers'];
                $data['success'] = false;
            }else{
                //临时：失效上推加载
                $data['html']=false;
                $data['pagers'] = $pagedata['pagers'];
                $data['success'] = false;
            }
            return response::json($data);exit;
        }else{
            return $this->page('topwap/sales/index.html',$pagedata);
        }

    }

    /**
     * 获取商品基本统计信息，对应键值为itemId
     *
     * @param $itemId 商品ID
     */
    private function _getItemStore($itemId)
    {
        $objMdlItemStore = app::get('sysitem')->model('item_store');
        $tmpItemInfoStore = $objMdlItemStore->getRow('*', array('item_id'=>$itemId));
        if( $tmpItemInfoStore )
        {
            $itemInfoStore['store'] = $tmpItemInfoStore['store'];
            $itemInfoStore['freez'] = $tmpItemInfoStore['freez'];
            $itemInfoStore['realStore'] = $tmpItemInfoStore['store']-$tmpItemInfoStore['freez'];
            $itemCount=app::get('sysitem')->model('item_count')->getRow('paid_quantity',array('item_id'=>$itemId));
            $itemInfoStore['paid_quantity']=$itemCount['paid_quantity'];
        }
        return $itemInfoStore;
    }
}