<?php

class topshop_ctl_trade_flow extends topshop_controller{
    /**
     * 变更物流信息
     * @params null
     * @return null
     */
    public function updateLogistic()
    {
      //$requestData = input::get();
      //$requestData['dlytmpl_id'] = input::get('dlytmpl_id');
        $requestData['logi_no'] = input::get('logi_no');
        $requestData['corp_id'] = input::get('corp_id');
        $requestData['delivery_id'] = input::get('delivery_id');
        $requestData['shop_id'] = $this->shopId;
        $tid = input::get('tid');
        try{

            $params['tid'] = $tid;
            $params['fields'] = "tid,status";
            $tradeInfo = app::get('topshop')->rpcCall('trade.get',$params,'seller');
            /*modify_20171106_by_fanglongji_start*/
            /*
            if(!$tradeInfo['status']= 'WAIT_BUYER_CONFIRM_GOODS')
            */
            if($tradeInfo['status'] != 'WAIT_BUYER_CONFIRM_GOODS' && $tradeInfo['status'] != 'PARTIAL_SHIPMENT')
            /*modify_20171106_by_fanglongji_end*/
                throw new Exception(app::get('topshop')->_('只能修改已发货待收货订单的物流信息'));

            app::get('topshop')->rpcCall('delivery.updateLogistic', $requestData);
        }catch(Exception $e){
            return $this->splash('error',null, $e->getMessage(), true);
        }

        $url = url::action('topshop_ctl_trade_detail@index', ['tid'=>$tid]);
        return $this->splash('success',$url, '更新物流信息成功', true);
    }

    /**
     * 发货订单处理
     * @params null
     * @return null
     */
    public function dodelivery()
    {
        $sdf = input::get();
        /*add_20171103_by_fanglongji_start*/
        $sdf['filter_array'] = trim(str_replace('\\','',$sdf['filter_array']),'"');
        $sdf['filter_array'] = json_decode($sdf['filter_array'],true);

        $send_num = array_sum($sdf['filter_array']);
        if($send_num == 0)
        {
            return $this->splash('error',null, '没有要发货的商品', true);
        }
        /*add_20171103_by_fanglongji_end*/
        //当订单为自提订单并且没有物流配送，可以填写字体备注
        if( isset($sdf['isZiti']) && $sdf['isZiti'] == "true" )
        {
            if(!trim($sdf['logi_no']) && !trim($sdf['ziti_memo']))
            {
                return $this->splash('error',null, '订单为自提订单，运单号和备注至少选择一项必填', true);
            }
            if( mb_strlen(trim($sdf['ziti_memo']),'utf8') > 200)
            {
                return $this->splash('error',null, '自提备注过长', true);
            }
            $sdf['ziti_memo'] = trim($sdf['ziti_memo']) ? trim($sdf['ziti_memo']) : "";
        }
        else
        {
            unset($sdf['isZiti'],$sdf['ziti_memo']);
            if(empty($sdf['logi_no']))
            {
                return $this->splash('error',null, '发货单号不能为空', true);
            }
        }

        if(isset($sdf['logi_no']) && trim($sdf['logi_no']) && strlen(trim($sdf['logi_no'])) < 6)
        {
            return $this->splash('error',null, '运单号过短，请认真核对后填写(大于6)正确的编号', true);
        }

        if(strlen(trim($sdf['logi_no'])) > 20 )
        {
            return $this->splash('error',null, '运单号过长，请认真核对后填写(小于20)正确的编号', true);
        }
        $sdf['logi_no'] = trim($sdf['logi_no']) ? trim($sdf['logi_no']) : "0";
        $sdf['seller_id'] = $this->sellerId;
        $sdf['shop_id'] = $this->shopId;

        try
        {
            app::get('topshop')->rpcCall('trade.delivery',$sdf);
        }
        catch (Exception $e)
        {
            return $this->splash('error',null, $e->getMessage(), true);
        }
        $this->sellerlog('订单发货。订单号是:'.$sdf['tid']);

        //发送订单发货通知
        $data = [
            'tid' => $sdf['tid'],
            'shop_id' => $sdf['shop_id'],
            'corp_code' => $sdf['corp_code'],
            'logi_no' => $sdf['logi_no'],
        ];

        /*modify_20171109_by_fanglongji_start*/
        /*
        app::get('topshop')->rpcCall('trade.shop.delivery.notice.send', $data);
        $url = url::action('topshop_ctl_trade_list@index', ['useSessionFilter'=>true]);
        return $this->splash('success',$url, '发货成功', true);
        */
        try
        {
            app::get('topshop')->rpcCall('trade.shop.delivery.notice.send', $data);
        }
        catch (Exception $e)
        {
            return $this->splash('error',null, $e->getMessage(), true);
        }
        finally
        {
            $url = url::action('topshop_ctl_trade_list@index', ['useSessionFilter'=>true]);
            return $this->splash('success',$url, '发货成功', true);
        }
        /*modify_20171109_by_fanglongji_end*/
    }

    /**
     * 产生订单发货页面
     * @params string order id
     * @return string html
     */

    public function goDelivery()
    {
        //面包屑
        $this->runtimePath = array(
            ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],
            ['url'=> url::action('topshop_ctl_trade_list@index'),'title' => app::get('topshop')->_('订单列表')],
            ['title' => app::get('topshop')->_('订单发货')],
        );
        $this->contentHeaderTitle = app::get('topshop')->_('订单发货');

        $tid = input::get('tid');
        if(!$tid)
        {
            header('Content-Type:application/json; charset=utf-8');
            echo '{error:"'.app::get('topshop')->_("订单号传递出错.").'",_:null}';exit;
        }
        $params['tid'] = $tid;
        /*modify_20171106_by_fanglongji_start*/
        /*
        $params['fields'] = "orders.spec_nature_info,tid,receiver_name,receiver_mobile,receiver_state,receiver_district,receiver_address,need_invoice,ziti_addr,invoice_type,invoice_name,invoice_main,orders.price,orders.num,orders.title,orders.item_id,orders.pic_path,total_fee,discount_fee,buyer_rate,adjust_fee,orders.total_fee,orders.adjust_fee,created_time,pay_time,consign_time,end_time,shop_id,need_invoice,invoice_name,invoice_type,invoice_main,orders.bn,cancel_reason,orders.refund_fee,orders.aftersales_status,orders.dlytmpl_id,shipping_type,orders.gift_data";
        */
        $params['fields'] = "logistics.corp_code,logistics.corp_code,logistics.logi_no,logistics.logi_name,logistics.delivery_id,logistics.receiver_name,logistics.t_begin,orders.spec_nature_info,tid,receiver_name,receiver_mobile,receiver_state,receiver_district,receiver_address,need_invoice,ziti_addr,invoice_type,invoice_name,invoice_main,orders.price,orders.num,orders.sendnum,orders.title,orders.item_id,orders.pic_path,total_fee,discount_fee,buyer_rate,adjust_fee,orders.total_fee,orders.adjust_fee,created_time,pay_time,consign_time,end_time,shop_id,need_invoice,invoice_name,invoice_type,invoice_main,orders.bn,cancel_reason,orders.refund_fee,orders.aftersales_status,orders.dlytmpl_id,shipping_type,orders.gift_data,orders.init_item_id,orders.init_shop_id";
        /*modify_20171106_by_fanglongji_end*/
        $tradeInfo = app::get('topshop')->rpcCall('trade.get',$params,'seller');
          //获取默认图片信息
        $pagedata['defaultImageId']= kernel::single('image_data_image')->getImageSetting('item');

        $pagedata['tradeInfo'] = $tradeInfo;

        //获取用户的物流模板
        if($tradeInfo['shipping_type'] == 'ziti')
        {
            $pagedata['ziti'] = 'true';
        }

        $dlycorp = app::get('topshop')->rpcCall('shop.dlycorp.getlist',['shop_id'=>$this->shopId]);
        $pagedata['dlycorp'] = $dlycorp['list'];

        $oid_array = array_column($tradeInfo['orders'],'oid');
        $sum_array = array_column($tradeInfo['orders'],'sendnum');
        $pagedata['filter_array'] = array_combine($oid_array,$sum_array);

        return $this->page('topshop/trade/godelivery.html', $pagedata);
    }

    /**
     * 对指定订单进行推送
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function toHub()
    {
        $shop_id = $this->shopId;
        // $start = 1537286400;
        // $end = 1540742400;
        $start = input::get('start');
        $end = input::get('end');
        $action = input::get('action');
        $data = [];

        if($start && $end){
            $start = strtotime($start);
            $end = strtotime($end) + 3600 * 24;
            if($action == 'everyday'){
                for($i = $start; $i < $end;){
                    $start_time = $i;
                    $end_time = $start_time + 3600 * 24;

                    $listsBuilder = db::connection()->createQueryBuilder();
                    $where = "t.shop_id = {$shop_id} and t.created_time >= {$start_time} and t.created_time < {$end_time} and t.status IN  ('WAIT_SELLER_SEND_GOODS','TRADE_BUYER_SIGNED','WAIT_BUYER_CONFIRM_GOODS','TRADE_FINISHED','WRITE_PARTIAL','PARTIAL_SHIPMENT')";

                    $tradeLists = $listsBuilder->select('t.tid, t.shop_id, t.receiver_mobile, t.user_id')
                    ->from('systrade_trade', 't')
                    // ->leftJoin('t', 'systrade_order', 'o', 't.tid = o.tid')
                    ->where($where)
                    // ->setFirstResult($offset)
                    // ->setMaxResults($limit)
                    // ->orderBy('t.created_time')
                    ->execute()->fetchAll();

                    $date_key = date('Y-m-d', $i);
                    $data[$date_key]['new'] = 0;
                    $data[$date_key]['old'] = 0;

                    foreach ($tradeLists as $value) {
                        $listsBuilder = db::connection()->createQueryBuilder();
                        $where = "t.shop_id = {$shop_id} and t.created_time >= {$start} and t.created_time < {$start_time} and t.user_id = {$value['user_id']}";

                        $old_trade = $listsBuilder->select('t.tid')
                        ->from('systrade_trade', 't')
                        ->where($where)
                        ->execute()->fetch();

                        if($old_trade['tid']){
                            $data[$date_key]['old']++;
                        }else{
                            $data[$date_key]['new']++;
                        }
                    }
                    $i = $end_time;
                }
                var_dump($data);
                exit;
            }elseif($action == 'item'){
                $item_ids = trim(input::get('item_ids'));
                if($item_ids){
                    // $item_ids = explode('-', $item_ids);
                    $item_ids = str_replace('-', ',', $item_ids);
                    $select = "o.title, o.user_id, o.item_id, o.num";
                    $where = "o.shop_id = {$shop_id} and t.created_time >= {$start} and t.created_time < {$end} and o.item_id in ({$item_ids}) and t.status in ('WAIT_SELLER_SEND_GOODS','TRADE_BUYER_SIGNED','WAIT_BUYER_CONFIRM_GOODS','TRADE_FINISHED','WRITE_PARTIAL','PARTIAL_SHIPMENT')";
                    $queryBuilder = db::connection()->createQueryBuilder();
                    $itemLists = $queryBuilder->select($select)
                    ->from('systrade_order', 'o')
                    ->leftJoin('o', 'systrade_trade', 't', 'o.tid = t.tid')
                    ->where($where)
                    ->execute()->fetchAll();
                    // jj($itemLists);
                    foreach ($itemLists as $item) {
                        if(!isset($data[$item['item_id']]['title'])){
                            $data[$item['item_id']]['title'] = $item['title'];
                        }

                        if(!isset($data[$item['item_id']]['old'])){
                            $data[$item['item_id']]['old'] = 0;
                        }

                        if(!isset($data[$item['item_id']]['new'])){
                            $data[$item['item_id']]['new'] = 0;
                        }

                        $listsBuilder = db::connection()->createQueryBuilder();
                        $where = "t.shop_id = {$shop_id} and t.created_time < {$start} and t.user_id = {$item['user_id']}";

                        $old_trade = $listsBuilder->select('t.tid')
                        ->from('systrade_trade', 't')
                        ->where($where)
                        ->execute()->fetch();

                        if($old_trade){
                            $data[$item['item_id']]['old'] += $item['num'];
                        }else{
                            $data[$item['item_id']]['new'] += $item['num'];
                        }
                    }
                }
                foreach ($data as $key => &$value) {
                    $sum = $value['old'] + $value['new'];
                    if($sum > 0){
                        $value['old_sg'] = round((($value['old'] / $sum) * 100), 2);
                        $value['new_sg'] = 100 - $value['old_sg'];
                        $value['old_sg'] = $value['old_sg'] . '%';
                        $value['new_sg'] = $value['new_sg'] . '%';
                    }

                }
                var_dump($data);
                exit;
            }



            // $select = "t.user_id, u.mobile, count(t.tid) AS tid_count, SUM(t.payment) AS payment_total";
            // $where = "t.shop_id = {$shop_id} and t.created_time >= {$start} and t.created_time < {$end}";
            // $queryBuilder = db::connection()->createQueryBuilder();
            // $userLists = $queryBuilder->select($select)
            // ->from('systrade_tradeb', 't')

            // ->leftJoin('t', 'sysuser_account', 'u', 't.user_id = u.user_id')
            // ->where($where)
            // ->groupBy('t.user_id')
            // ->orderBy('tid_count', 'DESC')
            // ->execute()->fetchAll();

            // jj($userLists);
            exit;
        }


        $logistics_plug = kernel::single('syslogistics_tasks_pushLogistics');
        $tid = input::get('tid');
        if(!$tid){
            echo '请输入订单号！';
            exit;
        }
        $logistics_plug->exec($tid);
        exit;
    }
}