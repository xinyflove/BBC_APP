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

        // return $shops;
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
        $blueberry = kernel::single('syslmgw_blueberry');
        $data = $blueberry->platform_stats();
        // return $data;
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
        $blueberry = kernel::single('syslmgw_blueberry');
        $data = $blueberry->signup_stats();
        // return $data;
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
        $blueberry = kernel::single('syslmgw_blueberry');
        $data = $blueberry->qingdao_stats();
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

        // return $data;
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
        $blueberry = kernel::single('syslmgw_blueberry');
        $data = $blueberry->order_location_stats();
        // return $data;
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
        $blueberry = kernel::single('syslmgw_blueberry');
        $data = $blueberry->access_stats();
        // return $data;
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
        $blueberry = kernel::single('syslmgw_blueberry');
        $data = $blueberry->userCount();
        // return $data;
        return $this->rsplash('200', $data, '请求成功');
	}

}