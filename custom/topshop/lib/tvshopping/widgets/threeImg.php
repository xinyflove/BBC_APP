<?php

/**
 * 电视购物页面wap端挂件-店铺logo挂件
 * @auth: xinyufeng
 */
class topshop_tvshopping_widgets_threeImg
{

    public function makeParams($params)
    {
        // $itemFilter2['item_id'] = implode(',', $params['item_id']);
        // $itemFilter2['fields'] = 'item_id,title,price,mkt_price,image_default_id';
        // $list = app::get('topshop')->rpcCall('item.list.get', $itemFilter2);

        // foreach ($list as $k => $v) {
        //     $list[$k]['sort'] = $params['sort'][$k];
        // }

        // $item = $this->array_sort($list, 'sort');
        // $params['item'] = $item;
        // unset($params['item_id'], $params['item_sku'], $params['sort']);
        // // jj($item);
        return $params;
    }
}