<?php

/**
 * User: 王海超
 * Date: 2018/10/15
 * Desc: 蓝莓购物电商大数据看板
 * shop_id = 9 测试店铺
 */

class syslmgw_ctl_blueberry extends syslmgw_emic_controller
{
    public function websocket()
    {
        // $a = 'GET /a=b&c=d&bb&cc=ee HTTP/1.1asfdasdf';

        // preg_match('#GET\s\/(.*)\sHTTP\/1\.1#', $a, $mc);
        // parse_str($mc[1], $myArray);
        // jj($myArray);
        // $to_uid = "123";
        // 推送的url地址，使用自己的服务器地址
        $push_api_url = "http://127.0.0.1:9501/scene=shop_panel&type=shop_stats&bb&cc=ee";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $push_api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['token' => 'token_asdf']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:"));
        // $this_header = array("content-type: application/x-www-form-urlencoded; charset=UTF-8");
        // 设置 HTTP 头字段的数组。格式： array('Content-type: text/plain', 'Content-length: 100')
        // curl_setopt($ch, CURLOPT_HTTPHEADER,$this_header);
        // 需要获取的 URL 地址，也可以在curl_init() 初始化会话的时候。
        // curl_setopt($ch, CURLOPT_URL,$postUrl);
        // // 启用时会将头文件的信息作为数据流输出
        // curl_setopt($ch, CURLOPT_HEADER, 0);
        // // TRUE 将curl_exec()获取的信息以字符串返回，而不是直接输出
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // // TRUE 时会发送 POST 请求，类型为：application/x-www-form-urlencoded，是 HTML 表单提交时最常见的一种。
        // curl_setopt($ch, CURLOPT_POST, 1);
        // // 提交数据
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        // // 设置超时 秒
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        $return = curl_exec($ch);
        curl_close($ch);
        var_export($return);

        // echo view::make('syslmgw/websocket.html', $pagedata);
        exit;
    }

    public function test()
    {
        echo view::make('syslmgw/websocket.html', $pagedata);
        exit;
    }
    /**
     * 各频道今日与昨日销售统计
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function shop_stats()
    {
        $select = "t.shop_id, SUM(t.payment) AS total";

        $where = "t.status IN  ('WAIT_SELLER_SEND_GOODS','WAIT_WRITE_OFF','WAIT_BUYER_CONFIRM_GOODS','TRADE_FINISHED','WRITE_PARTIAL','PARTIAL_SHIPMENT') AND t.cancel_status IN ('NO_APPLY_CANCEL','FAILS') AND t.shop_id != 9 ";

        $queryBuilder = db::connection()->createQueryBuilder();
        // var_dump(db::connection());exit;
        $shopLists = $queryBuilder->select($select)
            ->from('systrade_trade', 't')
            // ->leftJoin('t', 'sysshop_shop', 's', 't.shop_id = s.shop_id')
            ->where($where)
            ->groupBy('t.shop_id')
            ->orderBy('total', 'DESC')
            ->execute()->fetchAll();

        $shops = app::get('sysshop')->model('shop')->getList('shop_id, shop_name', ['shop_id|noequal' => 9], 0, -1, 'shop_id ASC');

        foreach ($shops as $shop) {
            $is_has = false;
            foreach ($shopLists as &$s) {
                if ($s['shop_id'] == $shop['shop_id']) {
                    $s['shop_name'] = $shop['shop_name'];
                    $is_has = true;
                    break;
                }
            }
            if (!$is_has) {
                $shopLists[] = [
                    'shop_id' => $shop['shop_id'],
                    'shop_name' => $shop['shop_name'],
                    'total' => '0.00',
                ];
            }
        }
        // jj($shopLists);
        $day_time = input::get('day_time');
        if ($day_time) {
            $today_start = strtotime(date("Y-m-d", $day_time));
        } else {
            $today_start = strtotime(date("Y-m-d"));
        }
        // $today_start = strtotime("2018-10-16");
        $today_end = $today_start + 3600 * 24;

        $yester_start = $today_start - 3600 * 24;
        $yester_end = $today_start;

        $select = "t.shop_id, SUM(t.payment) AS total";

        $where = "t.created_time >= {$today_start} AND t.created_time < {$today_end} AND t.status IN  ('WAIT_SELLER_SEND_GOODS','WAIT_WRITE_OFF','WAIT_BUYER_CONFIRM_GOODS','TRADE_FINISHED','WRITE_PARTIAL','PARTIAL_SHIPMENT') AND t.cancel_status IN ('NO_APPLY_CANCEL','FAILS') AND t.shop_id != 9 ";

        $queryBuilder = db::connection()->createQueryBuilder();
        // var_dump(db::connection());exit;
        $todayTradeLists = $queryBuilder->select($select)
            ->from('systrade_trade', 't')
            // ->leftJoin('t', 'sysshop_shop', 's', 't.shop_id = s.shop_id')
            ->where($where)
            // ->setFirstResult($offset)
            // ->setMaxResults($limit)
            ->groupBy('t.shop_id')
            ->orderBy('t.created_time')
            ->execute()->fetchAll();

        $where = "t.created_time >= {$yester_start} AND t.created_time < {$yester_end} AND t.status IN  ('WAIT_SELLER_SEND_GOODS','WAIT_WRITE_OFF','WAIT_BUYER_CONFIRM_GOODS','TRADE_FINISHED','WRITE_PARTIAL','PARTIAL_SHIPMENT') AND t.cancel_status IN ('NO_APPLY_CANCEL','FAILS') AND t.shop_id != 9 ";

        $queryBuilder = db::connection()->createQueryBuilder();
        $yesterdayTradeLists = $queryBuilder->select($select)
            ->from('systrade_trade', 't')
            // ->leftJoin('t', 'sysshop_shop', 's', 't.shop_id = s.shop_id')
            ->where($where)
            // ->setFirstResult($offset)
            // ->setMaxResults($limit)
            ->groupBy('t.shop_id')
            ->orderBy('t.created_time')
            ->execute()->fetchAll();

        $todayTradeLists = array_bind_key($todayTradeLists, 'shop_id');
        $yesterdayTradeLists = array_bind_key($yesterdayTradeLists, 'shop_id');
        // $shopLists = array_bind_key($shopLists, 'shop_id');

        $shops = [];
        foreach ($shopLists as $shop) {
            if (!in_array($shop['shop_id'], [23, 24, 30, 32, 39])) {

                if ($shop['shop_id'] == '36') {
                    $shop['shop_name'] = '海米FM商城';
                }

                $shops[] = $shop;
            }
        }
        unset($shop);
        foreach ($shops as &$shop) {

            $shop['yesterday_total'] = array_key_exists($shop['shop_id'], $yesterdayTradeLists) ? $yesterdayTradeLists[$shop['shop_id']]['total'] : '0.00';
            $shop['today_total'] = array_key_exists($shop['shop_id'], $todayTradeLists) ? $todayTradeLists[$shop['shop_id']]['total'] : '0.00';
        }

        $this->splash('200', $shops, '请求成功');
    }
    /*
     * 交易统计,全部交易额和累计下单量/今天交易额和今天下单量
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function platform_stats()
    {
        $blueberry = kernel::single('syslmgw_blueberry');
        $data = $blueberry->platform_stats();

        $this->splash('200', $data, '请求成功');
    }

    /**
     * 用户注册统计
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function signup_stats()
    {
        $blueberry = kernel::single('syslmgw_blueberry');
        $data = $blueberry->signup_stats();
        $this->splash('200', $data, '请求成功');
    }

    /**
     * 青岛地区订单统计
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function qingdao_stats()
    {
        $blueberry = kernel::single('syslmgw_blueberry');
        $data = $blueberry->qingdao_stats();
        $this->splash('200', $data, '请求成功');
    }

    /**
     * 各一级分类支付占比
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function one_category_stats()
    {
        $select = "c1.cat_id, SUM(o.payment) AS total";

        $where = "o.status IN  ('WAIT_SELLER_SEND_GOODS','WAIT_WRITE_OFF','WAIT_BUYER_CONFIRM_GOODS','TRADE_FINISHED','WRITE_PARTIAL','PARTIAL_SHIPMENT') AND o.shop_id != 9";

        $queryBuilder = db::connection()->createQueryBuilder();
        // var_dump(db::connection());exit;
        $catTrade = $queryBuilder->select($select)
            ->from('systrade_order', 'o')
            ->leftJoin('o', 'syscategory_cat', 'c3', 'c3.cat_id = o.cat_id')
            ->leftJoin('o', 'syscategory_cat', 'c2', 'c2.cat_id = c3.parent_id')
            ->leftJoin('o', 'syscategory_cat', 'c1', 'c1.cat_id = c2.parent_id')
            ->where($where)
            ->groupBy('c1.cat_id')
            // ->orderBy('total', 'DESC')
            ->execute()->fetchAll();
        $catTrade = array_bind_key($catTrade, 'cat_id');
        // jj($catTrade);
        $categorys = app::get('syscategory')->model('cat')->getList('cat_id, cat_name', ['level' => 1, 'disabled' => 0], 0, -1, 'order_sort ASC, cat_id DESC');

        foreach ($categorys as &$cat) {
            $cat['total'] = $catTrade[$cat['cat_id']]['total'] ?: '0.00';
        }

        $select = "SUM(o.payment) AS total";
        $where = "o.status IN  ('WAIT_SELLER_SEND_GOODS','WAIT_WRITE_OFF','WAIT_BUYER_CONFIRM_GOODS','TRADE_FINISHED','WRITE_PARTIAL','PARTIAL_SHIPMENT') AND o.shop_id != 9";
        $queryBuilder = db::connection()->createQueryBuilder();
        $count = $queryBuilder->select($select)
            ->from('systrade_order', 'o')
            ->where($where)
            ->execute()->fetchAll();

        $data = [
            'cat' => $categorys,
            'total' => $count[0]['total'] ?: 0,
        ];

        $this->splash('200', $data, '请求成功');
    }

    // 青岛市
    // 市南区
    // 市北区 四方区
    //
    // 黄岛区 开发区 胶南市
    // 崂山区
    // 李沧区
    // 城阳区
    //
    // 胶州市
    // 即墨市
    // 平度市
    //
    // 莱西市
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
            // $start_time = strtotime("2018-10-15");
            $end_time = $start_time + 3600 * 24;
        }

        $tradeList = app::get('systrade')->model('trade')->getList('tid, receiver_state, receiver_city, receiver_district, receiver_address', ['shop_id|noequal' => 9, 'status' => ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF'], 'cancel_status' => ['NO_APPLY_CANCEL', 'FAILS'], 'receiver_city' => '青岛市', 'created_time|bthan' => $start_time, 'created_time|sthan' => $end_time], 0, -1, 'created_time DESC');

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
        $this->splash('200', $data, '请求成功');
    }
    /**
     * 访问统计
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function access_stats()
    {
        $blueberry = kernel::single('syslmgw_blueberry');
        $data = $blueberry->access_stats();

        $this->splash('200', $data, '请求成功');
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

    /* action_name (par1, par2, par3)
	* 会员数据接口
	* author by wanghaichao
	* date 2018/10/31
	*/
    public function userCount()
    {
        $blueberry = kernel::single('syslmgw_blueberry');
        $data = $blueberry->userCount();
        $this->splash('200', $data, '请求成功');
    }

    public function middleground_all($type='')
    {
        $blueberry = kernel::single('syslmgw_blueberry');
        $platform_stats = $blueberry->platform_stats();

        // 浏览量
        $start_time = 19700101;
        $end_time = date('Ymd');
        $shop_data = $this->get_shops();
        $shop_ids_str = implode(',', array_column($shop_data, 'shop_id'));

        $queryBuilder = db::connection()->createQueryBuilder();
        $where = "t.shop_id in ({$shop_ids_str}) AND t.timesig >= {$start_time} AND t.timesig <= {$end_time}";
        $all_pv = $queryBuilder->select('sum(t.pv_count) as pv')
            ->from('statistic_trend', 't')
            ->where($where)
            ->execute()
            ->fetch();

        // 用户量
        $queryBuilder = db::connection()->createQueryBuilder();
        $all_user_total = $queryBuilder->select('count(id) AS total')
            ->from('sysuser_thirdpartyinfo')
            ->execute()
            ->fetch();

        // 新闻阅读量
        $all_read_num['total'] = 0;
        $all_video_num = ['total' => 0];

        // 请求海米数据
        $httpclient = kernel::single('base_httpclient');
        $response = $httpclient->get('https://api.haimifm.com//mobile/appHaiMiFmDataController/getAllHaiMiHistory');
        $response = json_decode($response, true);
        if (is_array($response) && $response['code'] == '200') {
            $result = $response['result'];
            $all_pv['pv'] += ( $result['allRequestNum'] ?: 0 ) ;
            // $all_payment_total['payment'] += ( $result['allTransactionNum'] ?: 0 ) ;
            $all_user_total['total'] += ( $result['allCustomerNum'] ?: 0 ) ;
            $all_read_num['total'] += ( $result['valueReadNum'] ?: 0 ) ;
            $all_video_num['total'] += ( $result['liveOnline'] ?: 0 ) ;
        }

        $response = [
            'pv' => $all_pv['pv'] * 5.2,
            'payment' => (int)$platform_stats['total']['total'],
            'user' => $all_user_total['total'] * 11.44,
            'Read' => $all_read_num['total'] * 3.76,
            'video' => $all_video_num['total'] / 2.9,
        ];

        $response['pv'] = intval($response['pv']);
        $response['user'] = intval($response['user']);
        $response['Read'] = intval($response['Read']);
        $response['video'] = intval($response['video']);
		if ($type=='this')
		{
			return $response;
		}else{
			$this->splash('200', $response, '请求成功');
		}
    }
    public function middleground_today($type='')
    {
        $blueberry = kernel::single('syslmgw_blueberry');
        $platform_stats = $blueberry->platform_stats();

        // 浏览量
        $shop_data = $this->get_shops();
        $shop_ids_str = implode(',', array_column($shop_data, 'shop_id'));

        $today = date('Ymd');
        $queryBuilder = db::connection()->createQueryBuilder();
        $where = "t.shop_id in ({$shop_ids_str}) AND t.timesig = {$today}";
        $today_pv = $queryBuilder->select('sum(t.pv_count) as pv')
            ->from('statistic_trend', 't')
            ->where($where)
            ->execute()
            ->fetch();

        // 用户量
        $start_time = strtotime(date("Y-m-d"));
        $end_time = $start_time + 24 * 3600;
        $where = "create_time >= {$start_time} AND create_time < {$end_time}";
        $queryBuilder = db::connection()->createQueryBuilder();
        $today_user_total = $queryBuilder->select('count(id) AS total')
            ->from('sysuser_thirdpartyinfo')
            ->where($where)
            ->execute()
            ->fetch();

        // 请求海米数据
        $httpclient = kernel::single('base_httpclient');
        $response = $httpclient->get('https://api.haimifm.com//mobile/appHaiMiFmDataController/getTodayHaiMiData');
        $response = json_decode($response, true);

        if (is_array($response) && $response['code'] == '200') {
            $result = $response['result'];
            $today_pv['pv'] += ( $result['todayRequestNum'] ?: 0 ) ;
            // $today_payment_total['payment'] += ( $result['todayTransactionNum'] ?: 0 ) ;
            $today_user_total['total'] += ( $result['todayCustomerNum'] ?: 0 ) ;
        }

        $response = [
            'pv' => $today_pv['pv'],
            'payment' => (int)$platform_stats['today']['total'],
            'user' => $today_user_total['total'] * 33,
        ];
		if($type=='this'){
			return $today_pv['pv'];
		}else{
			$this->splash('200', $response, '请求成功');
		}
    }

        /**
     * 各频道今日与昨日销售统计
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function middleground_stats()
    {
        $fans = [
            7 => 28752 + 380000,
            12 => 17020 + 149384,
            40 => 10000 + 110000,
            18 => 70554 + 90000,
            36 => 500000 + 2800,
            27 => 20000 + 140000,
            33 => 1936 + 366,
            31 => 40174 + 32066,
            8 => 9489 + 5006,
        ];
        $select = "t.shop_id, SUM(t.payment) AS total_payment, count(t.tid) AS count_tid";

        $where = "t.status IN  ('WAIT_SELLER_SEND_GOODS','WAIT_WRITE_OFF','WAIT_BUYER_CONFIRM_GOODS','TRADE_FINISHED','WRITE_PARTIAL','PARTIAL_SHIPMENT') AND t.cancel_status IN ('NO_APPLY_CANCEL','FAILS') AND t.shop_id != 9 ";

        $queryBuilder = db::connection()->createQueryBuilder();
        $shopLists = $queryBuilder->select($select)
            ->from('systrade_trade', 't')
            ->where($where)
            ->groupBy('t.shop_id')
            ->execute()->fetchAll();

        $shopLists = array_bind_key($shopLists, 'shop_id');

        $shop_data = $this->get_shops();
        // jj($shop_data);
        foreach ($shop_data as &$shop) {
                $shop['total_payment'] = $shopLists[$shop['shop_id']]['total_payment'] ?: 0;
                $shop['count_tid'] = $shopLists[$shop['shop_id']]['count_tid'] ?: 0;
                $shop['fans'] = $fans[$shop['shop_id']] ?: ($shop['shop_id'] * 256);
        }
        array_multisort(array_column($shop_data, 'fans'), SORT_DESC,  $shop_data);

        $this->splash('200', $shop_data, '请求成功');
    }
	
	/**
	* 获取智慧城市顶部数据
	* author by wanghaichao
	* date 2020/1/7
	*/
	public function smartcitydata(){
		$res=$this->middleground_all('this');
        $queryBuilder = db::connection()->createQueryBuilder();
        $all_user_total = $queryBuilder->select('count(id) AS total')
            ->from('sysuser_thirdpartyinfo')
            ->execute()
            ->fetch();
		$ds_user=$all_user_total['total'];
		$res['ds_user']=$ds_user;

        $blueberry = kernel::single('syslmgw_blueberry');
        $data = $blueberry->platform_stats();
		//$res['total_price']=$data['total']['total'];
		$res['total_count']=$data['total']['count'];
		$res['today_pv']=$this->middleground_today('this');
        $this->splash('200', $res, '请求成功');
 
	}


}
