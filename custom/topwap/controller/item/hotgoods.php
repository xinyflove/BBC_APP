<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2017/10/27
 * Time: 10:05
 * Desc: 热卖单品控制器
 */
class topwap_ctl_item_hotgoods extends topwap_controller {

    public function __construct()
    {
        $this->objLibSearch = kernel::single('topwap_item_search');
    }

    public function getList()
    {
        $filter = input::get();

        $filter['hot_goods'] = explode(',', $filter['hot_goods_str']);
        $limit = 10;
        $filter['pages'] = 1;
        $filter['page_size'] = $limit;
        $filter['item_id'] = $filter['hot_goods_str'];
        $pagedata = app::get('topwap')->rpcCall('item.handle.list',$filter);

        $pagedata['hot_goods_str'] = $filter['hot_goods_str'];
        //头部图片
        $pagedata['top_pic'] = $filter['top_pic'];

        return $this->page('topwap/item/hotgoods/list.html', $pagedata);
    }

    public function ajaxGetItemList()
    {
        $filter = input::get();
        $filter['hot_goods'] = explode(',', $filter['hot_goods_str']);
        $limit = 10;
        $filter['page_size'] = $limit;
        $filter['item_id'] = $filter['hot_goods_str'];
        $pagedata = app::get('topwap')->rpcCall('item.handle.list',$filter);

        return view::make('topwap/item/buycoupon/item_list.html',$pagedata);
    }
}
