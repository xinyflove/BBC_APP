<?php
/**
 * 商品列表页控制器
 */
class topwap_ctl_item_list extends topwap_controller {


    public function __construct()
    {
        $this->objLibSearch = kernel::single('topwap_item_search');
    }

    public function index()
    {
        $filter = input::get();
        if($filter['virtual_cat_id']){
            $catInfo = app::get('topwap')->rpcCall('category.virtualcat.info',array('virtual_cat_id'=>intval($filter['virtual_cat_id']),'platform'=>'h5'));
            if(!$catInfo){
                $pagedata['items'] = [];
                $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
                $pagedata['pagers']['total'] = 0;
                return $this->page('topwap/item/list/index.html', $pagedata);
            }

            $initFilter = unserialize($catInfo['filter']);
            if($initFilter['brand_id']){
                $initFilter['init_brand_id'] = implode(',', $initFilter['brand_id']);
                unset($initFilter['brand_id']);
            }
        }
        if($initFilter && is_array($initFilter)){
            $filter = array_merge($initFilter,$filter);
        }
        if($filter['cat_id']){
            $pagedata['catFlag'] = $filter['cat_id'];
        }

        $itemsList = $this->objLibSearch->search($filter)
                          ->setItemsActivetyTag()
                          ->setItemsPromotionTag()
                          ->getData();
        $pagedata['items'] = $itemsList['list'];
        /*add_start_gurundong_20171013*/
        foreach ($pagedata['items'] as &$item_v){
            $item_v['real_store'] = (int)$item_v['store'] - (int)$item_v['freez'];
        }
        /*add_end_gurundong_20171013*/
        /*add_20171011_by_fanglongji_start*/
        $shop_infos = app::get('topc')->rpcCall('shop.list.all.get');
        foreach($pagedata['items'] as &$items)
        {
            if($shop_infos[$items['shop_id']]['shop_mold'] == 'tv')
            {
                $items['mold_class'] = 'icon_small_tv';
            }
            else if($shop_infos[$items['shop_id']]['shop_mold'] == 'broadcast')
            {
                $items['mold_class'] = 'icon_fm101';
            }
            else
            {
                $items['mold_class'] = 'icon_other_tv';
            }
            $items['shop_mold'] = $shop_infos[$items['shop_id']]['shop_mold'];
        }
        /*add_20171011_by_fanglongji_end*/
        $activeFilter = $this->objLibSearch->getActiveFilter();

        //根据条件搜索出最多商品的分类，进行显示渐进式筛选项
        $filters = app::get('topc')->rpcCall('item.search.filterItems',$filter);
        if($filters['props'])
        {
            foreach ($filters['props'] as $k => $v)
            {
                $filters['props'][$k]['prop_count'] = count($v['prop_value']);
            }
        }

        $pagedata['activeFilter'] = $activeFilter;
        $pagedata['filter'] = $filters;
        $pagedata['search_keywords'] = $activeFilter['search_keywords'];

        //默认图片
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');

        $pagedata['pagers']['total'] = $this->objLibSearch->getMaxPages();
        $pagedata['screen'] = $this->__itemListFilter($filter);
        //add start 谷润东 20170930
        // 商品售磬角标
        $pagedata['sell_out_img'] = app::get('sysconf')->getConf('item.sell.out.default.img');
        //add end 谷润东 20170930
		/*add_2017/11/3_by_wanghaichao_start*/	
		if(!$pagedata['items']){			
			$parames = array();
			$parames['limit_num'] = 10;
			$parames['fields'] = "SI.item_id, image_default_id, title, price,store,freez, sold_quantity,SI.shop_id";
			//$parames['filter']['shop_id'] = $detailData['shop_id'];
			$parames['filter']['approve_status'] = 'onsale';
			$parames['filter']['SIS.store|than'] = '0';
			$parames['order_by'] = ['by' => 'sold_quantity', 'sort' => 'desc'];
			$itemList = app::get('topwap')->rpcCall('item.mybelike.list',$parames);
			foreach($itemList as &$items)
			{
				if($shop_infos[$items['shop_id']]['shop_mold'] == 'tv')
				{
					$items['mold_class'] = 'icon_small_tv';
				}
				else if($shop_infos[$items['shop_id']]['shop_mold'] == 'broadcast')
				{
					$items['mold_class'] = 'icon_fm101';
				}
				else
				{
					$items['mold_class'] = 'icon_other_tv';
				}
				$items['shop_mold'] = $shop_infos[$items['shop_id']]['shop_mold'];
				$items['shop_name'] = $shop_infos[$items['shop_id']]['shop_name'];
			}
			$pagedata['recommend'] = $itemList;
		}
		/*add_2017/11/3_by_wanghaichao_end*/
        return $this->page('topwap/item/list/index.html', $pagedata);
    }

    public function ajaxGetItemList()
    {
        $filter = input::get();
        $itemsList = $this->objLibSearch->search($filter)
                          ->setItemsActivetyTag()
                          ->setItemsPromotionTag()
                          ->getData();

        $pagedata['items'] = $itemsList['list'];
        /*add_start_gurundong_20171013*/
        foreach ($pagedata['items'] as &$item_v){
            $item_v['real_store'] = (int)$item_v['store'] - (int)$item_v['freez'];
        }
        /*add_end_gurundong_20171013*/
        $activeFilter = $this->objLibSearch->getActiveFilter();
        $pagedata['activeFilter'] = $activeFilter;
        $pagedata['pagers']['total'] = $this->objLibSearch->getMaxPages();
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        //add start 谷润东 20170930
        // 商品售磬角标
        $pagedata['sell_out_img'] = app::get('sysconf')->getConf('item.sell.out.default.img');
        //add end 谷润东 20170930
        if( !$pagedata['pagers']['total'] )
        {
			
			/*modify_2017/11/2_by_wanghaichao_start*/
			/*return view::make('topwap/empty/item.html',$pagedata);	*/
            return view::make('topwap/empty/item_new.html',$pagedata);	
			/*modify_2017/11/2_by_wanghaichao_end*/
        }

        if($pagedata['items'])
        {
            return view::make('topwap/item/list/item_list.html',$pagedata);
        }
    }
    
    // 商品搜索
    private function __itemListFilter($postdata)
    {
        $objLibFilter = kernel::single('topwap_item_filter');
        $params = $objLibFilter->decode($postdata);
        $params['use_platform'] = '0,2';
        $filterItems = app::get('topwap')->rpcCall('item.search.filterItems',$params);
        if($filterItems['shopInfo'])
        {
            /*modify_20171013_by_xinyufeng_start*/
            /*$wapslider = shopWidgets::getWapInfo('waplogo',$filterItems['shopInfo']['shop_id']);
            $filterItems['logo_image'] = $wapslider[0]['params'];*/

            $shop_setting = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>$filterItems['shopInfo']['shop_id']),'seller');
            $filterItems['logo_image']['shop_logo'] = $shop_setting['shop_brand_image'];
            /*modify_20171013_by_xinyufeng_end*/
        }

        //渐进式筛选的数据
        return $filterItems;
    }

    /**
     * 首页获取猜你喜欢ajax方法
     */
    public function ajaxGetLikeItemListIndex(){
        $filter = input::get();
        $offset = ((int)$filter['pages']-1)*(int)$filter['limit'];
        $apiParam = [
            'offset'=>$offset,
            'fields'=>'SI.item_id,title,price,image_default_id,cat_id,SI.shop_id',
            'limit_num'=>$filter['limit'],
            'order_by'=>[
                'by'=>'sold_quantity',
                'sort'=>'desc'
            ],
            'filter'=>[
                'approve_status'=>'onsale'
            ]
        ];
        $pagedata['items'] = app::get('topc')->rpcCall('item.mybelike.list',$apiParam);
        $shop_infos = app::get('topc')->rpcCall('shop.list.all.get');
        $goods_like = array();
        $new_arr = array();
        foreach($pagedata['items'] as $k=>$items)
        {
            if($shop_infos[$items['shop_id']]['shop_mold'] == 'tv')
            {
                $items['mold_class'] = 'icon_small_tv';
            }
            else if($shop_infos[$items['shop_id']]['shop_mold'] == 'broadcast')
            {
                $items['mold_class'] = 'icon_fm101';
            }
            else
            {
                $items['mold_class'] = 'icon_other_tv';
            }
            $items['shop_mold'] = $shop_infos[$items['shop_id']]['shop_mold'];
            $items['shop_name'] = $shop_infos[$items['shop_id']]['shop_name'];
            array_push($goods_like,$items['item_id']);
            //复制数组，将item_id作为key
            $new_arr[$items['item_id']] = $items;
        }
        $pagedata['items'] = $new_arr;

        //获取平台活动商品价格
        $activityParams['item_id'] = implode(',',$goods_like);
        $activityParams['status'] = 'agree';
        $activityParams['end_time'] = 'bthan';
        $activityParams['start_time'] = 'sthan';
        $activityParams['fields'] = 'activity_id,item_id,activity_tag,price,activity_price';
        $activityItemList = app::get('topc')->rpcCall('promotion.activity.item.list',$activityParams);
        if($activityItemList['list'])
        {
            foreach($activityItemList['list'] as $key=>$value)
            {
                //如果当前商品中有搞活动的，则附上活动信息
                if(in_array($value['item_id'],$goods_like)){
                    $pagedata['items'][$value['item_id']]['activity'] = $value;
                }
            }
        }

        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        if($pagedata['items'])
        {
            return view::make('topwap/item/list/guess_like_item_list.html',$pagedata);
        }
    }


    public function ajaxGetLikeItemList()
    {
        $filter = input::get();
        $filter['like_item_id'] = explode(',',$filter['like_item_id']);
        $rows = 'item_id,title,price,image_default_id,cat_id,shop_id';
        $objItem = kernel::single('sysitem_item_info');
        $pagedata['items'] = $objItem->getItemList($filter['like_item_id'], $rows, [], ['start'=>($filter['pages']-1)*$filter['limit'], 'limit'=>$filter['limit']]);
        $shop_infos = app::get('topc')->rpcCall('shop.list.all.get');

        foreach($pagedata['items'] as &$items)
        {
            if($shop_infos[$items['shop_id']]['shop_mold'] == 'tv')
            {
                $items['mold_class'] = 'icon_small_tv';
            }
            else
            {
                $items['mold_class'] = 'icon_small_broadcast';
            }
            $items['shop_mold'] = $shop_infos[$items['shop_id']]['shop_mold'];
            $items['shop_name'] = $shop_infos[$items['shop_id']]['shop_name'];
        }

        //获取平台活动商品价格
        $activityParams['item_id'] = implode(',',$filter['like_item_id']);
        $activityParams['status'] = 'agree';
        $activityParams['end_time'] = 'bthan';
        $activityParams['start_time'] = 'sthan';
        $activityParams['fields'] = 'activity_id,item_id,activity_tag,price,activity_price';
        $activityItemList = app::get('topc')->rpcCall('promotion.activity.item.list',$activityParams);
        if($activityItemList['list'])
        {
            foreach($activityItemList['list'] as $key=>$value)
            {
                $pagedata['items'][$value['item_id']]['activity'] = $value;
            }
        }

        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');

        if($pagedata['items'])
        {
            return view::make('topwap/item/list/guess_like_item_list.html',$pagedata);
        }
    }

	/* action_name (par1, par2, par3)
	* 用于列表加入购物车的
	* author by wanghaichao
	* date 2018/5/28
	*/
    public function miniSpec()
    {
        $itemId = intval(input::get('item_id'));
        if( empty($itemId) )
        {
            return redirect::action('topc_ctl_default@index');
        }

        if( userAuth::check() )
        {
            $pagedata['nologin'] = 1;
        }
        $pagedata['user_id'] = userAuth::id();
        $params['item_id'] = $itemId;
        $params['fields'] = "*,item_desc.pc_desc,item_count,item_store,item_status,sku,item_nature,spec_index";
        $detailData = app::get('topc')->rpcCall('item.get',$params);
        if(!$detailData)
        {
            $pagedata['error'] = "很抱歉，您查看的宝贝不存在，可能已下架或者被转移";
            return view::make('topc/list/error.html', $pagedata);
        }

        if(count($detailData['sku']) == 1)
        {
            $detailData['default_sku_id'] = array_keys($detailData['sku'])[0];
        }

        $detailData['valid'] = $this->__checkItemValid($detailData);

        //判断此商品发布的平台，如果是wap端，跳转至wap链接
        //if($detailData['use_platform'] == 2 )
       // {
        //    redirect::action('topwap_ctl_item_detail@index',array('item_id'=>$itemId))->send();exit;
        //}

        $skuData = null;
        //是否有限定SKU促销
        //如果有限定则只显示限定的sku
        if( input::get('sku_id') )
        {
            $specDesc = null;
            foreach( (array)explode(',',input::get('sku_id')) as $skuId )
            {
                $skuData[$skuId] = $detailData['sku'][$skuId];
                $specDescValue = $skuData[$skuId]['spec_desc']['spec_value_id'];
                foreach( $specDescValue as $specId=>$specValueId )
                {
                    $specDesc[$specId][$specValueId] = $detailData['spec_desc'][$specId][$specValueId];
                }
            }
            $detailData['spec_desc'] = $specDesc;
        }
        else
        {
            $skuData = $detailData['sku'];
        }

        $detailData['spec'] = $this->__getSpec($detailData['spec_desc'], $skuData);
        //$detailData['qrCodeData'] = $this->__qrCode($itemId);
        $pagedata['item'] = $detailData;
	//	echo "<pre>";print_r($pagedata['item']['spec']);die();
        return view::make('topwap/item/list/spec_dialog.html', $pagedata);
    }



    private function __checkItemValid($itemsInfo)
    {
        if( empty($itemsInfo) ) return false;

        //违规商品
        if( $itemsInfo['violation'] == 1 ) return false;

        //未启商品
        if( $itemsInfo['disabled'] == 1 ) return false;

        //未上架商品
        if($itemsInfo['approve_status'] != 'onsale') return false;

        return true;
    }

    private function __getSpec($spec, $sku)
    {
        if( empty($spec) ) return array();

        //获取活动设置的商品 及 sku信息
        $itemId = reset(array_unique(array_column($sku,'item_id')));
        $activiryParams = array(
            'item_id' =>$itemId,
            'start_time|lthan' => time(),
            'end_time|than' => time(),
            'verify_status' => 'agree',
        );
        $activityItem = app::get('topc')->rpcCall('promotion.activity.item.info',array('item_id'=>$itemId,'valid'=>1),'buyer');
        $activitySkuPrice = $activityItem['sku_activity_price'];
        foreach( $sku as $row )
        {
            $key = implode('_',$row['spec_desc']['spec_value_id']);
            $activityPice = $activitySkuPrice[$row['sku_id']] ? $activitySkuPrice[$row['sku_id']] :($activityItem['sku_ids'] ? 0 : $activityItem['activity_price']);

            if( $key )
            {
                $result['specSku'][$key]['sku_id'] = $row['sku_id'];
                $result['specSku'][$key]['item_id'] = $row['item_id'];
                $result['specSku'][$key]['price'] = $row['price'];
                $result['specSku'][$key]['mkt_price'] = $row['mkt_price'];
                $result['specSku'][$key]['activity_price'] = $activityPice;
                $result['specSku'][$key]['store'] = $row['realStore'];
                /*add_2017/12/20_by_gurundong_start*/
                $result['specSku'][$key]['bank_price']=$row['bank_price'];
                /*add_2017/12/20_by_gurundong_end*/
                if( $row['status'] == 'delete')
                {
                    $result['specSku'][$key]['valid'] = false;
                }
                else
                {
                    $result['specSku'][$key]['valid'] = true;
                }

                $specIds = array_flip($row['spec_desc']['spec_value_id']);
                $specInfo = explode('、',$row['spec_info']);
                foreach( $specInfo  as $info)
                {
                    $id = each($specIds)['value'];
                    $result['specName'][$id] = explode('：',$info)[0];
                }
            }
        }
        return $result;
    }

    private function __qrCode($itemId)
    {
        $url = url::action("topwap_ctl_item_detail@index",array('item_id'=>$itemId));
        return getQrcodeUri($url, 80, 10);
    }

}

