<?php

/**
 * User: 王海超
 * Date: 2018/10/15
 * Desc: 蓝莓购物电商大数据看板
 * shop_id = 9 测试店铺
 */

class syslmgw_emic_blueberry extends syslmgw_emic_controller
{
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
                if($s['shop_id'] == $shop['shop_id']){
                    $s['shop_name'] = $shop['shop_name'];
                    $is_has = true;
                    break;
                }
            }
            if(!$is_has){
                $shopLists[] = [
                    'shop_id' => $shop['shop_id'],
                    'shop_name' => $shop['shop_name'],
                    'total' => '0.00',
                ];
            }
        }
        // jj($shopLists);
        $day_time = input::get('day_time');
        if($day_time){
            $today_start = strtotime(date("Y-m-d", $day_time));
        }else{
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
            if(!in_array($shop['shop_id'], [23, 24, 30, 32, 39])){
                $shops[] = $shop;
            }
        }
        unset($shop);
        foreach ($shops as &$shop) {

                $shop['yesterday_total'] = array_key_exists($shop['shop_id'], $yesterdayTradeLists) ? $yesterdayTradeLists[$shop['shop_id']]['total'] : '0.00';
                $shop['today_total'] = array_key_exists($shop['shop_id'], $todayTradeLists) ? $todayTradeLists[$shop['shop_id']]['total'] : '0.00';
        }

        return $this->rsplash('200', $shops, '请求成功');
    }
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

        //
        $total['total'] = str_pad(ceil($total['total']), 8, '0', STR_PAD_LEFT);
        $total['count'] = str_pad($total['count'], 8, '0', STR_PAD_LEFT);
        $today['total'] = str_pad(ceil($today['total']), 8, '0', STR_PAD_LEFT);
        $today['count'] = str_pad($today['count'], 8, '0', STR_PAD_LEFT);

        $data = [
            'total' => $total,
            'today' => $today
        ];
        return $this->rsplash('200', $data, '请求成功');
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
        if($start_time){
            $start_time = strtotime(date("Y-m-d", $start_time));
        }else{
            $start_time = strtotime(date("Y-m-d"));
        }
        $select = "count(id) AS total";
        $end_time = $start_time + 24 * 3600;
        $count = [];
        for($start = $start_time; $start < $end_time; $start = $end){
            // if($start > time()){
            //     break;
            // }
            $end = $start + 3600 * 4;
            $where = "create_time >= {$start} AND create_time < {$end}";
            $queryBuilder = db::connection()->createQueryBuilder();
            $group_total = $queryBuilder->select($select)
            ->from('sysuser_thirdpartyinfo')
            ->where($where)
            ->execute()->fetchAll();

            $key = date("H:i", $start) . '-' . date("H:i", $end);
            $count[$key] = $group_total[0]['total'] ? : 0;
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
            'interval_total' => $interval_total[0]['total'] ? : 0,
            'total' => $total[0]['total'] ? : 0,
        ];
        return $this->rsplash('200', $data, '请求成功');
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

        if(!$start_time || !$end_time){
            $start_time = strtotime(date("Y-m-d",time()));
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
        // ->setFirstResult($offset)
        // ->setMaxResults($limit)
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
                'count' => 0,
            ],
            [
                'district' => '市北区',
                'count' => 0,
            ],
            // [
            //     'district' => '四方区',
            //     'count' => 0,
            // ],
            [
                'district' => '黄岛区',
                'count' => 0,
            ],
            [
                'district' => '崂山区',
                'count' => 0,
            ],
            [
                'district' => '李沧区',
                'count' => 0,
            ],
            [
                'district' => '城阳区',
                'count' => 0,
            ],
            // [
            //     'district' => '开发区',
            //     'count' => 0,
            // ],
            [
                'district' => '胶州市',
                'count' => 0,
            ],
            [
                'district' => '即墨区',
                'count' => 0,
            ],
            [
                'district' => '平度市',
                'count' => 0,
            ],
            // [
            //     'district' => '胶南市',
            //     'count' => 0,
            // ],
            [
                'district' => '莱西市',
                'count' => 0,
            ],
        ];

        if($groupTotalTrade){
            foreach ($groupTotalTrade as $value) {
                if($value['count']){

                    foreach ($districts as &$district) {
                        if($district['district'] === $value['district']){
                            $district['count'] += $value['count'];
                            break;
                        }elseif(($district['district'] == '市北区') && ($value['district'] == '四方区')){
                            $district['count'] += $value['count'];
                            break;
                        }elseif(($district['district'] == '黄岛区') && ($value['district'] == '开发区')){
                            $district['count'] += $value['count'];
                            break;
                        }elseif(($district['district'] == '黄岛区') && ($value['district'] == '胶南市')){
                            $district['count'] += $value['count'];
                            break;
                        }elseif(($district['district'] == '即墨区') && ($value['district'] == '即墨市')){
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
            'count' =>$totalTrade[0]['count'] ? : 0,
        ];

        return $this->rsplash('200', $data, '请求成功');
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
            $cat['total'] = $catTrade[$cat['cat_id']]['total'] ? : '0.00';
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
            'total' =>$count[0]['total'] ? : 0,
        ];

        return $this->rsplash('200', $data, '请求成功');
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

        if(!$start_time || !$end_time){
            $start_time = strtotime(date("Y-m-d",time()));
            $end_time = $start_time + 3600 * 24;
        }

        $tradeList = app::get('systrade')->model('trade')->getList('tid, receiver_state, receiver_city, receiver_district, receiver_address', ['shop_id|noequal' => 9, 'status' => ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF'], 'cancel_status' => ['NO_APPLY_CANCEL', 'FAILS'], 'receiver_city' => '青岛市', 'created_time|bthan' => $start_time, 'created_time|sthan' => $end_time], 0, -1, 'created_time DESC');

        foreach ($tradeList as $k=>&$value) {

			if($value['receiver_district']=='四方区' || $value['receiver_district']=='市北区'){
				$value['receiver_district']='市北区';
			}elseif($value['receiver_district']=='即墨市'){
				$value['receiver_district']='即墨区';
			}elseif($value['receiver_district']=='胶南市' || $value['receiver_district']=='开发区' || $value['receiver_district']=='黄岛区'){
				$value['receiver_district']='黄岛区';
			}
			$value['receiver'] = $value['receiver_state'] . $value['receiver_city'] . $value['receiver_district'] . $value['receiver_address'];

			$addr[$k]=$value['receiver_state'] . $value['receiver_city'] . $value['receiver_district'];
		}

		$data['addr']=array_values(array_unique($addr));
		$data['tradeList']=$tradeList;
        return $this->rsplash('200', $data, '请求成功');
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

        if(!$start_time || !$end_time){
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
        return $this->rsplash('200', $data, '请求成功');
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
	public function userCount(){
		$time=time();
		$total=app::get('sysuser')->model('thirdpartyinfo')->getRow('count(id) as total');

		//本月数据
		$start_time=strtotime(date('Y-m-01',$time));
		$end_time=$time;
		$params['create_time|bthan']=$start_time;
		$params['create_time|sthan']=$end_time;
		$thisMonth=app::get('sysuser')->model('thirdpartyinfo')->getRow('count(id) as total',$params);
		//上月数据

		$s_start_time= strtotime(date('Y-m-01') . ' -1 month');
		$s_end_time=strtotime(date('Y-m-t 23:59:59', strtotime(date('Y-m-01') . ' -1 month')));
		$params['create_time|bthan']=$s_start_time;
		$params['create_time|sthan']=$s_end_time;
		$lastMonth=app::get('sysuser')->model('thirdpartyinfo')->getRow('count(id) as total',$params);
		$data['total']=$total['total'];
		$data['thisMonth']=$thisMonth['total'];
		$data['lastMonth']=$lastMonth['total'];
        return $this->rsplash('200', $data, '请求成功');
	}

}