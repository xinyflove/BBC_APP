<?php

/**
 * 电视购物页面wap端挂件-店铺logo挂件
 * @auth: xinyufeng
 */
class topshop_tvshopping_widgets_itemRecom
{

    public function makeParams($params)
    {
        $itemFilter2['item_id'] = implode(',', $params['item_id']);
        $itemFilter2['fields'] = 'item_id,title,price,mkt_price,image_default_id';
        $list = app::get('topshop')->rpcCall('item.list.get', $itemFilter2);

        foreach ($list as $k => $v) {
            $list[$k]['sort'] = $params['sort'][$k];
        }

        $item = $this->array_sort($list, 'sort');
        $params['item'] = $item;
        unset($params['item_id'], $params['item_sku'], $params['sort']);
        // $params['item_id_no'] = $item_id;
        // jj($item);
        return $params;
    }

    public function array_sort($arr, $keys, $type = 'asc')
    {
        $keysvalue = $new_array = array();
        foreach ($arr as $k => $v) {
            $keysvalue[$k] = $v[$keys];
        }
        if ($type == 'asc') {
            asort($keysvalue);
        } else {
            arsort($keysvalue);
        }
        reset($keysvalue);
        foreach ($keysvalue as $k => $v) {
            $new_array[$k] = $arr[$k];
        }
        return $new_array;
    }
}