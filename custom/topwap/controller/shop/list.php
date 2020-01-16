<?php
/**
 * 店铺搜索控制器
 */
class topwap_ctl_shop_list extends topwap_ctl_shop {

    public function __construct()
    {
        parent::__construct();
        $this->objLibSearch = kernel::single('topwap_item_search');
    }

    public function index()
    {
        $filter = input::get();

         //标签获取
        if($filter['widgets_id']&&$filter['widgets_type'])
        {
            $tagInfo = shopWidgets::getWapInfo($filter['widgets_type'],$filter['shop_id'],$filter['widgets_id']);
            foreach ($tagInfo[0]['params']['item_id'] as $key => $value)
            {
                $item_id .= $value.',';
            }
            $filter['item_id'] = rtrim($item_id, ",");
            unset($filter['widgets_id'],$filter['widgets_type']);
        }

		$filter['is_onsale']='yes';

        $itemsList = $this->objLibSearch->search($filter)
                          ->setItemsActivetyTag()
                          ->setItemsPromotionTag()
                          ->getData();

        $pagedata['items'] = $itemsList['list'];

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

            if($items['init_item_id'])
            {
                // 代售商品 库存信息重新赋值 @auth:xinyufeng
                $_initItemId = $items['init_item_id'];
                $initItemStore = kernel::single('sysitem_item_info')->getItemStore($_initItemId);
                $items['store'] = $initItemStore[$_initItemId]['store'];
                $items['freez'] = $initItemStore[$_initItemId]['freez'];
            }
        }
        /*add_20171011_by_fanglongji_end*/

        /*add_start_gurundong_20171013*/
        foreach ($pagedata['items'] as &$item_v){
            $item_v['real_store'] = (int)$item_v['store'] - (int)$item_v['freez'];
        }
        /*add_end_gurundong_20171013*/
        $activeFilter = $this->objLibSearch->getActiveFilter();
        $pagedata['activeFilter'] = $activeFilter;
        $pagedata['search_keywords'] = $activeFilter['search_keywords'];
        $pagedata['shopId'] = $activeFilter['shop_id'];

        //默认图片
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        //店铺分类
        $pagedata['shopcat'] = app::get('topwap')->rpcCall('shop.cat.get',array('shop_id'=>$activeFilter['shop_id']));
        foreach($pagedata['shopcat'] as $shopCatId=>&$row)
        {
            if( $row['children'] )
            {
                $row['cat_id'] = $row['cat_id'].','.implode(',', array_column($row['children'], 'cat_id'));
            }
        }

        $pagedata['pagers']['total'] = $this->objLibSearch->getMaxPages();
        //add start gurundong 20170930
        // 商品售磬角标
        $pagedata['sell_out_img'] = app::get('sysconf')->getConf('item.sell.out.default.img');
        //add end gurundong 20170930

        /*add_20171013_by_xinyufeng_start*/
        $shop_setting = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>$filter['shop_id']),'seller');
        $pagedata['shop_setting'] = $shop_setting;
        /*add_20171013_by_xinyufeng_end*/
        return $this->page('topwap/shop/list/index.html', $pagedata);
    }


	/* action_name (par1, par2, par3)
	* store列表,重写防止冲突
	* author by wanghaichao
	* date 2018/1/11
	*/
	public function store_list(){
        $filter = input::get();
		/*add_2018/1/12_by_wanghaichao_start*/
		//把store_id换成shop_id
		if($filter['store_id'] && !$filter['shop_id']){
			$pagedata['store_id']=$filter['store_id'];
			$shopids=app::get('sysstore')->model('store')->getRow('relate_shop_id',array('store_id'=>$filter['store_id']));
			$filter['shop_id']=$shopids['relate_shop_id'];
			$cat_shop_id=$shopids['relate_shop_id'];
			unset($filter['store_id']);
		}elseif($filter['store_id'] && $filter['shop_id']){
			$pagedata['store_id']=$filter['store_id'];
			$shopids=app::get('sysstore')->model('store')->getRow('relate_shop_id',array('store_id'=>$filter['store_id']));
			$cat_shop_id=$shopids['relate_shop_id'];
		}
		/*add_2018/1/12_by_wanghaichao_end*/

         //标签获取
        if($filter['widgets_id']&&$filter['widgets_type'])
        {
            $tagInfo = shopWidgets::getWapInfo($filter['widgets_type'],$filter['shop_id'],$filter['widgets_id']);
            foreach ($tagInfo[0]['params']['item_id'] as $key => $value)
            {
                $item_id .= $value.',';
            }
            $filter['item_id'] = rtrim($item_id, ",");
            unset($filter['widgets_id'],$filter['widgets_type']);
        }

		$filter['is_onsale']='yes';
        $itemsList = $this->objLibSearch->search($filter)
                          ->setItemsActivetyTag()
                          ->setItemsPromotionTag()
                          ->getData();

        $pagedata['items'] = $itemsList['list'];

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

            if($items['init_item_id'])
            {
                // 代售商品 库存信息重新赋值 @auth:xinyufeng
                $_initItemId = $items['init_item_id'];
                $initItemStore = kernel::single('sysitem_item_info')->getItemStore($_initItemId);
                $items['store'] = $initItemStore[$_initItemId]['store'];
                $items['freez'] = $initItemStore[$_initItemId]['freez'];
            }
        }
        /*add_20171011_by_fanglongji_end*/

        /*add_start_gurundong_20171013*/
        foreach ($pagedata['items'] as &$item_v){
            $item_v['real_store'] = (int)$item_v['store'] - (int)$item_v['freez'];
        }
        /*add_end_gurundong_20171013*/
        $activeFilter = $this->objLibSearch->getActiveFilter();
        $pagedata['activeFilter'] = $activeFilter;
        $pagedata['search_keywords'] = $activeFilter['search_keywords'];
        $pagedata['shopId'] = $activeFilter['shop_id'];

        //默认图片
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        //店铺分类
        $pagedata['shopcat'] = app::get('topwap')->rpcCall('shop.cat.get',array('shop_id'=>$cat_shop_id));
        foreach($pagedata['shopcat'] as $shopCatId=>&$row)
        {
            if( $row['children'] )
            {
                $row['cat_id'] = $row['cat_id'].','.implode(',', array_column($row['children'], 'cat_id'));
            }
        }
		/*add_2018/1/12_by_wanghaichao_start*/
		//兼容多个店铺
		$shopcats=array();
		foreach($pagedata['shopcat'] as $k=>$v){
			$shopcats[$v['shop_id']]['shop_name']=str_replace('（自营店铺）','',$shop_infos[$v['shop_id']]['shop_name']);
			$shopcats[$v['shop_id']]['shop_id']=$v['shop_id'];
			$shopcats[$v['shop_id']]['cat'][]=$v;
		}
		$pagedata['shopcats']=$shopcats;
		//echo "<pre>";print_r($shopcats);die();

		/*add_2018/1/12_by_wanghaichao_end*/
        $pagedata['pagers']['total'] = $this->objLibSearch->getMaxPages();
        //add start gurundong 20170930
        // 商品售磬角标
        $pagedata['sell_out_img'] = app::get('sysconf')->getConf('item.sell.out.default.img');
        //add end gurundong 20170930

        /*add_20171013_by_xinyufeng_start*/
        $shop_setting = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>$filter['shop_id']),'seller');
        $pagedata['shop_setting'] = $shop_setting;
        /*add_20171013_by_xinyufeng_end*/
        return $this->page('topwap/store/list/index.html', $pagedata);
	}

    public function ajaxGetItemList()
    {
        $filter = input::get();
		$filter['is_onsale']='yes';
        $itemsList = $this->objLibSearch->search($filter)
                          ->setItemsActivetyTag()
                          ->setItemsPromotionTag()
                          ->getData();

        $pagedata['items'] = $itemsList['list'];

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

            if($items['init_item_id'])
            {
                // 代售商品 库存信息重新赋值 @auth:xinyufeng
                $_initItemId = $items['init_item_id'];
                $initItemStore = kernel::single('sysitem_item_info')->getItemStore($_initItemId);
                $items['store'] = $initItemStore[$_initItemId]['store'];
                $items['freez'] = $initItemStore[$_initItemId]['freez'];
            }
        }
        /*add_20171011_by_fanglongji_end*/

        /*add_start_gurundong_20171013*/
        foreach ($pagedata['items'] as &$item_v){
            $item_v['real_store'] = (int)$item_v['store'] - (int)$item_v['freez'];
        }
        /*add_end_gurundong_20171013*/

        $activeFilter = $this->objLibSearch->getActiveFilter();
        $pagedata['activeFilter'] = $activeFilter;
        $pagedata['pagers']['total'] = $this->objLibSearch->getMaxPages();
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        //add start gurundong 20170930
        // 商品售磬角标
        $pagedata['sell_out_img'] = app::get('sysconf')->getConf('item.sell.out.default.img');
        //add end gurundong 20170930

        /*add_20171013_by_xinyufeng_start*/
        $shop_setting = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>$filter['shop_id']),'seller');
        $pagedata['shop_setting'] = $shop_setting;
        /*add_20171013_by_xinyufeng_end*/
        return view::make('topwap/shop/list/item_list.html',$pagedata);
    }

    public function apiGetItemList()
    {
        // $AllowOrigin = ['http://127.0.0.1:5551', 'http://wxh5pay.dev.tvplaza.cn', '*'];

        // $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        // $origin = '*';
        // if (in_array($origin, $AllowOrigin)) {

            // 允许 $originarr 数组内的 域名跨域访问
            // header('Access-Control-Allow-Origin:' . $origin);
            header('Access-Control-Allow-Origin: *');
            // 响应类型
            header('Access-Control-Allow-Methods:POST,GET');
            // 带 cookie 的跨域访问
            header('Access-Control-Allow-Credentials: true');
            // 响应头设置
            header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        // }

        $limit =  input::get('limit', 20);
        $limit = intval($limit);
        $page =  input::get('page', 1);
        $shop_id =  input::get('shop_id', 49);
        $filter = array(
            'shop_id' => $shop_id,
            'approve_status' => 'onsale',
            'page_no' => intval($page),
            'page_size' => $limit,
        );

        $filter['fields'] = 'item_id,title,image_default_id,price,approve_status';
        $filter['orderBy'] = 'modified_time desc';

        $itemsList = app::get('topshop')->rpcCall('item.search', $filter);
        foreach($itemsList['list'] as &$items){
            $items['h5_url'] = url::action("topwap_ctl_item_detail@index",array('item_id' => $items['item_id']));
        }
        $pagedata['item_list'] = $itemsList['list'];
        $pagedata['totalItem'] = $itemsList['total_found'];

        $pagedata['totalPage'] = ceil($itemsList['total_found']/$limit);
        return response::json($pagedata);
    }
}

