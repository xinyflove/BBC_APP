<?php
/**
 * 电视购物页面wap端挂件-店铺logo挂件
 * @auth: xinyufeng
 */
class topshop_tvshopping_widgets_nowLive {

    public function makeParams($setting)
    {
        $nowtime = time();
        $today_start = strtotime(date('Y-m-d'));
        $today_end = strtotime(date('Y-m-d')) + 3600 * 24;
        $liveModel = app::get('sysshop')->model('live');

        // 正在直播
        $filter1['shop_id'] = $setting['shop_id'];
        $filter1['status'] = '1';
        $filter1['live_start_time|sthan'] = $nowtime;
        $filter1['live_end_time|bthan'] = $nowtime;
        $order1 = 'live_start_time asc';
        $data1 = $liveModel->getList('item_id,live_url,image_default_id,live_start_time,live_end_time', $filter1, 0, 1, $order1);
        $data1 = array_bind_key($data1, 'item_id');

        // 已经播出
        $filter2['shop_id'] = $setting['shop_id'];
        $filter2['status'] = '1';
        // 开始时间在今天
        $filter2['live_start_time|bthan'] = $today_start;
        // 结束时间小于现在
        $filter2['live_end_time|sthan'] = $nowtime;
        $order2 = 'live_start_time desc';
        $data2 = $liveModel->getList('item_id,image_default_id,live_start_time,live_end_time', $filter2, 0, 2, $order2);
        $data2 = array_bind_key($data2, 'item_id');

        // 即将播出
        $filter3['shop_id'] = $setting['shop_id'];
        $filter3['status'] = '1';
        $filter3['live_start_time|bthan'] = $nowtime;
        $order3 = 'live_start_time asc';
        $data3 = $liveModel->getList('item_id,image_default_id,live_start_time,live_end_time', $filter3, 0, 2, $order3);
        $data3 = array_bind_key($data3, 'item_id');
        // $data3 = $data3[0];
        $item_id = $item = [];
        if($data1) {
            $item_id = array_merge($item_id, array_keys($data1));
        }
        if($data2) {
            $item_id = array_merge($item_id, array_keys($data2));
        }
        if($data3) {
            $item_id = array_merge($item_id, array_keys($data3));
        }
        if($item_id){
            $itemFilter['item_id'] = implode(',', $item_id);
            // jj($item_id);
            $itemFilter['fields'] = 'item_id,title,price,image_default_id';
            $item = app::get('topshop')->rpcCall('item.list.get', $itemFilter);
        }
        $setting['item'] = $item;
        $setting['data1'] = $data1;
        $setting['data2'] = $data2;
        $setting['data3'] = $data3;
        // jj($setting);
        return $setting;
    }
}