<?php
/**
 * 电视和广播购物页控制器
 */
class topwap_ctl_item_mallgoods extends topwap_controller {


    public function __construct()
    {

    }

    public function index()
    {
        $filter = input::get();
        $limit = 10;
        $filter['pages'] = 1;
        $filter['page_size'] = $limit;
        $pagedata = app::get('topwap')->rpcCall('item.handle.list',$filter);

        $pagedata['activeFilter']['limit'] = $limit;
        $pagedata['activeFilter']['shop_mold'] = $filter['shop_mold'];
        $pagedata['detail_pic'] = $filter['detail_pic'];
        $pagedata['headline'] = $filter['headline'];

        return $this->page('topwap/item/mall_goods/index.html', $pagedata);
    }

    public function ajaxGetItemList()
    {
        $filter = input::get();
        $filter['page_size'] = $filter['limit'];
        $pagedata = app::get('topwap')->rpcCall('item.handle.list',$filter);

        if( !$pagedata['pagers']['total'] )
        {
            return view::make('topwap/empty/item.html',$pagedata);
        }

        if($pagedata['items'])
        {
            return view::make('topwap/item/mall_goods/item_list.html',$pagedata);
        }
    }
}

