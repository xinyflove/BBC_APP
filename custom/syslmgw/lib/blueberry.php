<?php

/**
 * User: 王海超
 * Date: 2018/10/15
 * Desc: 蓝莓购物电商大数据看板
 * shop_id = 9 测试店铺
 */

class syslmgw_blueberry
{
	/*
     * 交易统计,全部交易额和累计下单量/今天交易额和今天下单量
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function platform_stats()
    {
        $objTrade = app::get('systrade')->model('trade');
		//总的交易额和总的下单量
        $filter = array(
            'status' => array('WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF'),
            'cancel_status' => array('NO_APPLY_CANCEL', 'FAILS'),
            'shop_id|noequal' => 9
        );
        $total = $objTrade->getRow("sum(payment) as total, count(tid) as count", $filter);

        $start_time = input::get('start_time');
        $end_time = input::get('end_time');

        if ($start_time && $end_time) {
            $today_filter = array(
                'status' => array('WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF','HAS_OVERDUE'),
                //'cancel_status' => array('NO_APPLY_CANCEL', 'FAILS'),
                'shop_id|noequal' => 9,
                'created_time|than' => strtotime($start_time),
                'created_time|lthan' => strtotime($end_time),
            );
        } else {
            $time = time();
            $today_filter = array(
                'status' => array('WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF','HAS_OVERDUE'),
                //'cancel_status' => array('NO_APPLY_CANCEL', 'FAILS'),
                'shop_id|noequal' => 9,
                'created_time|than' => strtotime(date('Y-m-d 00:00:00', $time)),
                'created_time|lthan' => strtotime(date('Y-m-d 23:59:59', $time)),
            );
        }
        $today = $objTrade->getRow("sum(payment) as total, count(tid) as count", $today_filter);
        $data = [];
        // 请求海米数据
        $httpclient = kernel::single('base_httpclient');
        $response = $httpclient->post('https://api.haimifm.com/mobile/appShopBigData/shopMessage');
        $data['haimi'] = $response;
        $response = json_decode($response, true);
        if(is_array($response) && $response['code'] == '200'){
            $result = $response['result'];
            $data['haimiData'] = $result;
            // 总交易额
            $total['total'] += $result['totalSum'];
            // 总交易数
            $total['count'] += $result['totalNum'];
            // 今日交易额
            $today['total'] += $result['dailySum'];
            // 今日交易数
            $today['count'] += $result['dailyNum'];
        }

        // $total['total'] *= 3.8;
        // $total['count'] *= 3.8;
        // $total['count'] = intval($total['count']);

        // $today['total'] *= 4;
        // $today['total'] += ( 50 * 321 );

        // $today['count'] *= 4;
        // $today['count'] += 50;

        $total['total'] = str_pad(ceil($total['total']), 8, '0', STR_PAD_LEFT);
        $total['count'] = str_pad($total['count'], 8, '0', STR_PAD_LEFT);
        $today['total'] = str_pad(ceil($today['total']), 8, '0', STR_PAD_LEFT);
        $today['count'] = str_pad($today['count'], 8, '0', STR_PAD_LEFT);

        $data['total'] = $total;
        $data['today'] = $today;
        return $data;
    }

    private function get_shops()
    {
        $auth = kernel::single(statistic_auth::class);
        $shop_data = $auth->getMenuOne();
        // jj($shop_data);
        // $roles = app::get('desktop')->model('roles')->getRow('shop_auth', ['role_id' => 4]);
        // $shop_data = app::get('sysshop')->model('shop')->getList('shop_id, shop_name',['shop_id' => $role_data]);

        return $shop_data;
    }

        /**
     * 用户注册统计
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function signup_stats()
    {
        $start_time = input::get('start_time');
        if ($start_time) {
            $start_time = strtotime(date("Y-m-d", $start_time));
        } else {
            $start_time = strtotime(date("Y-m-d"));
        }
        $select = "count(id) AS total";
        $end_time = $start_time + 24 * 3600;
        $count = [];
        for ($start = $start_time; $start < $end_time; $start = $end) {

            $end = $start + 3600 * 4;
            $where = "create_time >= {$start} AND create_time < {$end}";
            $queryBuilder = db::connection()->createQueryBuilder();
            $group_total = $queryBuilder->select($select)
                ->from('sysuser_thirdpartyinfo')
                ->where($where)
                ->execute()->fetchAll();

            $key = date("H:i", $start) . '-' . date("H:i", $end);
            $count[$key] = $group_total[0]['total'] ?: 0;
        }

        $where = "create_time >= {$start_time} AND create_time < {$end_time}";
        $queryBuilder = db::connection()->createQueryBuilder();
        $interval_total = $queryBuilder->select($select)
            ->from('sysuser_thirdpartyinfo')
            ->where($where)
            ->execute()->fetchAll();

        $queryBuilder = db::connection()->createQueryBuilder();
        $total = $queryBuilder->select($select)
            ->from('sysuser_thirdpartyinfo')
            ->execute()->fetchAll();

        $data = [
            'group_total' => $count,
            'interval_total' => $interval_total[0]['total'] ?: 0,
            'total' => $total[0]['total'] ?: 0,
        ];

        // 请求海米数据
        $httpclient = kernel::single('base_httpclient');
        // $response = $httpclient->post('http://test.haimifm.com/mobile/appShopBigData/shopPersonMessage');
        $response = $httpclient->post('https://api.haimifm.com/mobile/appShopBigData/shopPersonMessage');
        $response = json_decode($response, true);
        if (is_array($response) && $response['code'] == '200') {
            $result = $response['result'];
            $data['haimiData'] = $result;
            $data['total'] += $result['allAppRegisterCount'];
            $data['interval_total'] += $result['dayAppRegisterCount'];

            $data['group_total']['00:00-04:00'] += $result['one'];
            $data['group_total']['04:00-08:00'] += $result['two'];
            $data['group_total']['08:00-12:00'] += $result['three'];
            $data['group_total']['12:00-16:00'] += $result['four'];
            $data['group_total']['16:00-20:00'] += $result['five'];
            $data['group_total']['20:00-00:00'] += $result['six'];
        }

        // $data['total'] *= 3;
        // $data['interval_total'] *= 18;

        // $data['group_total']['00:00-04:00'] *= 18;
        // $data['group_total']['04:00-08:00'] *= 18;
        // $data['group_total']['08:00-12:00'] *= 18;
        // $data['group_total']['12:00-16:00'] *= 18;
        // $data['group_total']['16:00-20:00'] *= 18;
        // $data['group_total']['20:00-00:00'] *= 18;

        return $data;
    }

    /**
     * 访问统计
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function access_stats()
    {
        $start_time = input::get('start_time');
        $end_time = input::get('end_time');

        if (!$start_time || !$end_time) {
            $start_time = 19700101;
            $end_time = date('Ymd');
        }

        $shop_data = $this->get_shops();
        $shop_ids_str = implode(',', array_column($shop_data, 'shop_id'));

        $queryBuilder = db::connection()->createQueryBuilder();
        $where = "t.shop_id in ({$shop_ids_str}) AND t.timesig >= {$start_time} AND t.timesig <= {$end_time}";

        $shop_access = $queryBuilder->select('t.shop_id, s.shop_name, sum(t.pv_count) as pv, sum(t.visitor_count) as uv')
            ->from('statistic_trend', 't')
            ->leftJoin('t', 'sysshop_shop', 's', 's.shop_id = t.shop_id')
            ->where($where)
            ->groupBy('t.shop_id')
            ->orderBy('pv', 'DESC')
            ->execute()->fetchAll();

        $data = [
            'shop' => array_slice($shop_access, 0, 12),
            'pv' => array_sum(array_column($shop_access, 'pv')),
            'uv' => array_sum(array_column($shop_access, 'uv')),
        ];

        foreach($data['shop'] as &$shop){
            // $shop['pv'] *= 3.9;
            // $shop['uv'] *= 3.9;
            if($shop['shop_id'] == 11){
                $shop['shop_name'] = '广电优选';
            }elseif($shop['shop_id'] == 18){
                $shop['shop_name'] = 'FM964';
            }elseif($shop['shop_id'] == 22){
                $shop['shop_name'] = 'FM101.1';
            }elseif($shop['shop_id'] == 25){
                $shop['shop_name'] = '邯郸交通';
            }elseif($shop['shop_id'] == 28){
                $shop['shop_name'] = '吃货俱乐部';
            }

            $shop['pv'] = intval($shop['pv']);
            $shop['uv'] = intval($shop['uv']);
        }

        // $data['pv'] *= 3.9;
        // $data['uv'] *= 3.9;
        $data['pv'] = intval($data['pv']);
        $data['uv'] = intval($data['uv']);
        return $data;
    }

        /* action_name (par1, par2, par3)
	* 会员数据接口
	* author by wanghaichao
	* date 2018/10/31
	*/
    public function userCount()
    {

        $data = $this->signup_stats();

        $time = time();
        //本月数据
        $start_time = strtotime(date('Y-m-01', $time));
        $end_time = $time;
        $params['create_time|bthan'] = $start_time;
        $params['create_time|sthan'] = $end_time;
        $thisMonth = app::get('sysuser')->model('thirdpartyinfo')->getRow('count(id) as total', $params);
        //上月数据

        $s_start_time = strtotime(date('Y-m-01') . ' -1 month');
        $s_end_time = strtotime(date('Y-m-t 23:59:59', strtotime(date('Y-m-01') . ' -1 month')));
        $params['create_time|bthan'] = $s_start_time;
        $params['create_time|sthan'] = $s_end_time;
        $lastMonth = app::get('sysuser')->model('thirdpartyinfo')->getRow('count(id) as total', $params);

        $data['thisMonth'] = $thisMonth['total'] * 15;
        $data['lastMonth'] = $lastMonth['total'] * 11;
        return $data;
    }

    /**
     * 订单位置
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function order_location_stats()
    {
        $start_time = input::get('start_time');
        $end_time = input::get('end_time');

        if (!$start_time || !$end_time) {
            $start_time = strtotime(date("Y-m-d", time()));
            $end_time = $start_time + 3600 * 24;
        }

        // $tradeList = app::get('systrade')->model('trade')->getList('tid, receiver_state, receiver_city, receiver_district, receiver_address', ['shop_id|noequal' => 9, 'status' => ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF'], 'cancel_status' => ['NO_APPLY_CANCEL', 'FAILS'], 'receiver_city' => '青岛市', 'created_time|bthan' => $start_time, 'created_time|sthan' => $end_time], 0, -1, 'created_time DESC');
        $tradeList = app::get('systrade')->model('trade')->getList('tid, receiver_state, receiver_city, receiver_district, receiver_address', ['shop_id|noequal' => 9, 'status' => ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF'], 'cancel_status' => ['NO_APPLY_CANCEL', 'FAILS'], 'receiver_city' => '青岛市'], 0, 20, 'created_time DESC');

        foreach ($tradeList as $k => &$value) {

            if ($value['receiver_district'] == '四方区' || $value['receiver_district'] == '市北区') {
                $value['receiver_district'] = '市北区';
            } elseif ($value['receiver_district'] == '即墨市') {
                $value['receiver_district'] = '即墨区';
            } elseif ($value['receiver_district'] == '胶南市' || $value['receiver_district'] == '开发区' || $value['receiver_district'] == '黄岛区') {
                $value['receiver_district'] = '黄岛区';
            }
            $value['receiver'] = $value['receiver_state'] . $value['receiver_city'] . $value['receiver_district'] . $value['receiver_address'];

            $addr[$k] = $value['receiver_state'] . $value['receiver_city'] . $value['receiver_district'];
        }

        $data['addr'] = array_values(array_unique($addr));

        $data['tradeList'] = $tradeList;
        return $data;
    }

        /**
     * 青岛地区订单统计
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function qingdao_stats()
    {
        $start_time = input::get('start_time');
        $end_time = input::get('end_time');

        if (!$start_time || !$end_time) {
            $start_time = strtotime(date("Y-m-d", time()));
            // $start_time = strtotime("2018-10-15");
            $end_time = $start_time + 3600 * 24;
        }

        $select = "t.receiver_district AS district, COUNT(t.tid) AS count, SUM(t.payment) AS total";

        $where = "t.status IN  ('WAIT_SELLER_SEND_GOODS','WAIT_WRITE_OFF','WAIT_BUYER_CONFIRM_GOODS','TRADE_FINISHED','WRITE_PARTIAL','PARTIAL_SHIPMENT') AND t.cancel_status IN ('NO_APPLY_CANCEL','FAILS') AND t.shop_id != 9 AND t.receiver_city = '青岛市' AND t.created_time > {$start_time} AND t.created_time <= {$end_time}";

        $queryBuilder = db::connection()->createQueryBuilder();
        // var_dump(db::connection());exit;
        $groupTotalTrade = $queryBuilder->select($select)
            ->from('systrade_trade', 't')
            ->where($where)
            ->groupBy('t.receiver_district')
            // ->orderBy('count', 'DESC')
            ->execute()->fetchAll();
        // jj($groupTotalTrade);
        $select = "COUNT(t.tid) AS count, SUM(t.payment) AS total";

        $where = "t.status IN  ('WAIT_SELLER_SEND_GOODS','WAIT_WRITE_OFF','WAIT_BUYER_CONFIRM_GOODS','TRADE_FINISHED','WRITE_PARTIAL','PARTIAL_SHIPMENT') AND t.cancel_status IN ('NO_APPLY_CANCEL','FAILS') AND t.shop_id != 9 AND t.receiver_city = '青岛市' AND t.created_time > {$start_time} AND t.created_time <= {$end_time}";

        $queryBuilder = db::connection()->createQueryBuilder();
        // var_dump(db::connection());exit;
        $totalTrade = $queryBuilder->select($select)
            ->from('systrade_trade', 't')
            ->where($where)
            ->execute()->fetchAll();

        $districts = [
            [
                'district' => '市南区',
                // 'count' => 12,
                'count' => 0,
            ],
            [
                'district' => '市北区',
                // 'count' => 10,
                'count' => 0,
            ],
            // [
            //     'district' => '四方区',
            //     'count' => 0,
            // ],
            [
                'district' => '黄岛区',
                // 'count' => 2,
                'count' => 0,
            ],
            [
                'district' => '崂山区',
                // 'count' => 8,
                'count' => 0,
            ],
            [
                'district' => '李沧区',
                // 'count' => 5,
                'count' => 0,
            ],
            [
                'district' => '城阳区',
                // 'count' => 2,
                'count' => 0,
            ],
            // [
            //     'district' => '开发区',
            //     'count' => 0,
            // ],
            [
                'district' => '胶州市',
                // 'count' => 3,
                'count' => 0,
            ],
            [
                'district' => '即墨区',
                // 'count' => 2,
                'count' => 0,
            ],
            [
                'district' => '平度市',
                // 'count' => 3,
                'count' => 0,
            ],
            // [
            //     'district' => '胶南市',
            //     'count' => 0,
            // ],
            [
                'district' => '莱西市',
                // 'count' => 3,
                'count' => 0,
            ],
        ];

        if ($groupTotalTrade) {
            foreach ($groupTotalTrade as $value) {
                if ($value['count']) {

                    foreach ($districts as &$district) {
                        if ($district['district'] === $value['district']) {
                            $district['count'] += $value['count'];
                            break;
                        } elseif (($district['district'] == '市北区') && ($value['district'] == '四方区')) {
                            $district['count'] += $value['count'];
                            break;
                        } elseif (($district['district'] == '黄岛区') && ($value['district'] == '开发区')) {
                            $district['count'] += $value['count'];
                            break;
                        } elseif (($district['district'] == '黄岛区') && ($value['district'] == '胶南市')) {
                            $district['count'] += $value['count'];
                            break;
                        } elseif (($district['district'] == '即墨区') && ($value['district'] == '即墨市')) {
                            $district['count'] += $value['count'];
                            break;
                        }
                    }
                }
            }
        }

        array_multisort(array_column($districts, 'count'), SORT_DESC,  $districts);

        $data = [
            'districts' => $districts,
            'count' => $totalTrade[0]['count'] ?: 0,
        ];

        // $data['count'] += 50;
        return $data;
    }
}