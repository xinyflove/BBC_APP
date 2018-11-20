<?php
/**
 * 电视购物页面wap端挂件-店铺logo挂件
 * @auth: xinyufeng
 */
class topshop_tvshopping_widgets_todayLive {

    public function makeParams($setting)
    {
        $nowtime = time();
        $liveModel = app::get('sysshop')->model('live');

        $filter1['shop_id'] = $setting['shop_id'];
        $filter1['status'] = '1';
        $filter1['live_start_time|sthan'] = $nowtime;
        $filter1['live_end_time|bthan'] = $nowtime;
        $order1 = 'live_start_time asc';
        // 当前直播
        $data1 = $liveModel->getList('item_id,live_url,image_default_id', $filter1, 0, 1, $order1);
        $setting['current'] = $data1[0];
        // jj($data1);
        // 当日直播排班
        $filter2['shop_id'] = $setting['shop_id'];
        $filter2['status'] = '1';
        $filter2['live_start_time|bthan'] = strtotime(date('Y-m-d'));
        $filter2['live_end_time|sthan'] = strtotime(date('Y-m-d')) + 3600 * 24;
        $order2 = 'live_start_time asc';
        $data2 = $liveModel->getList('item_id,live_start_time,live_end_time', $filter2, 0, 20, $order2);
        // jj($data2);
        $setting['live_index'] = 1;
        if($data2){
            // $data2 = array_bind_key($data2, 'item_id');
            $itemFilter2['item_id'] = implode(',', array_column($data2, 'item_id'));
            $itemFilter2['fields'] = 'item_id,title,price,image_default_id';
            $item2 = app::get('topshop')->rpcCall('item.list.get', $itemFilter2);
            foreach ($data2 as $key => $value) {
                $data2[$key]['url'] = url::action('topwap_ctl_item_detail@index', ['item_id' => $value['item_id']]);
                $data2[$key]['item'] = $item2[$value['item_id']];

                if($value['live_start_time'] > $nowtime){
                    $data2[$key]['status'] = 'future';
                }elseif($value['live_end_time'] < $nowtime){
                    $data2[$key]['status'] = 'past';
                }else{
                    $data2[$key]['status'] = 'now';
                    $setting['live_index'] = $key + 1;
                }

                // data('H:i',$value['live_start_time'])
                $data2[$key]['date'] = date('H:i', $value['live_start_time']) . '~' . date('H:i', $value['live_end_time']);
            }
        }

        $setting['itemList'] = $data2;
        // jj($setting);
        return $setting;
    }
}