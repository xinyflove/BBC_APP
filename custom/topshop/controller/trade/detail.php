<?php
class topshop_ctl_trade_detail extends topshop_controller{
    public function index()
    {
        $tids = input::get('tid');
        //面包屑
        $this->runtimePath = array(
            ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],
            ['url'=> url::action('topshop_ctl_trade_list@index'),'title' => app::get('topshop')->_('订单列表')],
            ['title' => app::get('topshop')->_('订单详情')],
        );

        $params['tid'] = $tids;
        /*modify_20171106_by_fanglongji_start*/
        /*
            $params['fields'] = "shipping_type,orders.spec_nature_info,user_id,tid,status,payment,points_fee,ziti_addr,ziti_memo,post_fee,pay_type,payed_fee,receiver_state,receiver_city,receiver_district,receiver_address,receiver_zip,trade_memo,shop_memo,receiver_name,receiver_mobile,orders.price,orders.num,orders.title,orders.item_id,orders.pic_path,total_fee,discount_fee,buyer_rate,adjust_fee,orders.total_fee,orders.adjust_fee,created_time,pay_time,consign_time,end_time,shop_id,need_invoice,invoice_name,invoice_type,invoice_main,invoice_vat_main,orders.bn,cancel_reason,orders.refund_fee,orders.aftersales_status,orders.gift_data,is_virtual,orders.is_virtual";
        */
        $params['fields'] = "logistics.corp_code,logistics.corp_code,logistics.logi_no,logistics.logi_name,logistics.delivery_id,logistics.receiver_name,logistics.t_begin,shipping_type,cancel_status,orders.spec_nature_info,user_id,tid,status,payment,points_fee,ziti_addr,ziti_memo,post_fee,pay_type,payed_fee,receiver_state,receiver_city,receiver_district,receiver_address,receiver_zip,trade_memo,shop_memo,receiver_name,receiver_mobile,orders.price,orders.num,orders.title,orders.item_id,orders.pic_path,total_fee,discount_fee,buyer_rate,adjust_fee,orders.total_fee,orders.adjust_fee,created_time,pay_time,consign_time,end_time,shop_id,need_invoice,invoice_name,invoice_type,invoice_main,invoice_vat_main,orders.bn,cancel_reason,orders.refund_fee,orders.aftersales_status,orders.gift_data,is_virtual,is_cross,identity_card_number,seat,orders.is_virtual,orders.confirm_type,orders.init_shop_id,orders.source_house";
        /*modify_20171106_by_fanglongji_end*/
        /*add_2017-11-14_by_xinyufeng_start*/
        $params['fields'] .= ',orders.allow_refund,orders.user_id';
        /*add_2017-11-14_by_xinyufeng_end*/
        $tradeInfo = app::get('topshop')->rpcCall('trade.get',$params,'seller');
        if($tradeInfo['shipping_type'] == 'ziti')
        {
            $pagedata['ziti'] = "true";
        }

        if(!$tradeInfo)
        {
            redirect::action('topshop_ctl_trade_list@index')->send();exit;
        }
        $userInfo = app::get('topshop')->rpcCall('user.get.account.name', ['user_id' => $tradeInfo['user_id']], 'seller');
        $tradeInfo['login_account'] = $userInfo[$tradeInfo['user_id']];

        //获取默认图片信息
        $pagedata['defaultImageId']= kernel::single('image_data_image')->getImageSetting('item');

        $pagedata['trade']= $tradeInfo;

        $paymentInfo=app::get('ectools')->model('trade_paybill')->getRow('*',array('status'=>'succ','tid'=>$tradeInfo['tid']));
        $pagedata['payinfo']=$paymentInfo;

        if($tradeInfo['cancel_status']=='SUCCESS'){
            $pagedata['cancelinfo']=app::get('ectools')->model('refunds')->getRow('*',array('tid'=>$tradeInfo['tid'],'status'=>'succ','refunds_type'=>1));
        }

        $pagedata['logi'] = app::get('topshop')->rpcCall('delivery.get',array('tid'=>$params['tid']));
        $pagedata['tracking'] = app::get('syslogistics')->getConf('syslogistics.order.tracking');
        $this->contentHeaderTitle = app::get('topshop')->_('订单详情');

        $dlycorp = app::get('topshop')->rpcCall('shop.dlycorp.getlist',['shop_id'=>$this->shopId]);
        $pagedata['dlycorp'] = $dlycorp['list'];

        /*add_2017-11-17_by_xinyufeng_start*/
        $roleFilter['seller_id']=$this->sellerId;
        $sellerRole=app::get('sysshop')->model('seller')->getRow('seller_type',$roleFilter);
        $pagedata['seller_role']=$sellerRole['seller_type'];
        /*add_2017-11-17_by_xinyufeng_end*/
		/*add_2018/6/25_by_wanghaichao_start*/
		$pagedata['is_compere']=$this->sellerInfo['is_compere'];
        /*add_2018/6/25_by_wanghaichao_end*/

        $tv_shop_id = app::get('sysshop')->getConf('sysshop.tvshopping.shop_id');
        // jj($tradeInfo);
        if(($tradeInfo['shop_id'] == $tv_shop_id) && in_array('CALL_CENTER_HOUSE', array_column($tradeInfo['orders'], 'source_house')) && ($tradeInfo['receiver_city'] == '青岛市' && ($tradeInfo['shipping_type'] == 'express'))){
            $aggregation_filter = [
                'tid' => $params['tid'],
            ];
            $aggregation = app::get('sysaftersales')->rpcCall('logistics.push.get.current',$aggregation_filter);
            $pagedata['push_logistics'] = $aggregation;
        }
        return $this->page('topshop/trade/detail.html', $pagedata);
    }

    /**
     * 无偿劵查看消费详情
     */
    public function ajaxGetAgentPrice0()
    {
        $tid = input::get('tid');
        $offline_trade_data = app::get('systrade')->model('offline_trade')->getRow('*',['tid'=>$tid]);
        if(!empty($offline_trade_data))
        {
            //线下已消费，查找消费详细信息
            /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
            $queryBuilder = app::get('systrade')->database()->createQueryBuilder();
            $queryBuilder->select('a.name as agent_shop_name,a.addr as agent_shop_addr,s.supplier_name')->from('syssupplier_agent_shop','a')
                ->leftJoin('a','sysshop_supplier','s','a.supplier_id=s.supplier_id')
                ->where('agent_shop_id='.$offline_trade_data['agent_shop_id'])
                ->andWhere("s.is_audit ='PASS'");
            $agent_shop_data = $queryBuilder->execute()->fetch();
            $offline_trade_data['agent_shop_data'] = $agent_shop_data;
            $offline_trade_data['pay_time'] = date('Y-m-d H:i',$offline_trade_data['pay_time']);
            return $this->splash('success',null,['data'=>$offline_trade_data]);
        }else{
            return $this->splash('error');
        }
    }

    public function getAgentPrice0()
    {
        $tid = input::get('tid');
        $offline_trade_data = app::get('systrade')->model('offline_trade')->getRow('*',['tid'=>$tid]);
        $tradeData = app::get('systrade')->model('agent_vocher')->getRow('*',['tid'=>$tid]);
        $sys_tid = $tradeData['sys_tid'];
        //线下已消费，查找消费详细信息
        /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
        $queryBuilder = app::get('systrade')->database()->createQueryBuilder();
        $queryBuilder->select('a.name as agent_shop_name,a.addr as agent_shop_addr,s.supplier_name')->from('syssupplier_agent_shop','a')
            ->leftJoin('a','sysshop_supplier','s','a.supplier_id=s.supplier_id')
            ->where('agent_shop_id='.$offline_trade_data['agent_shop_id'])
            ->andWhere("s.is_audit='PASS'");
        $agent_shop_data = $queryBuilder->execute()->fetch();
        $offline_trade_data['agent_shop_data'] = $agent_shop_data;
        $offline_trade_data['pay_time'] = date('Y-m-d H:i',$offline_trade_data['pay_time']);
        $pagedata = $offline_trade_data;
        return $this->page('topshop/trade/detail_agentprice0.html',$pagedata);
    }

    public function ajaxGetTrack()
    {
        $postData = input::get();
        $pagedata['track'] = app::get('topshop')->rpcCall('logistics.tracking.get.hqepay',$postData);
        return view::make('topshop/trade/trade_logistics.html',$pagedata);
    }

    public function setTradeMemo()
    {
        $params['tid'] = input::get('tid');
        $params['shop_id'] = $this->shopId;

        if( !is_numeric($params['tid']) )
        {
            $msg = app::get('topshop')->_('参数错误');
            return $this->splash('error','',$msg,true);
        }

        try
        {
            $params['shop_memo'] = input::get('shop_memo');
            $result = app::get('topshop')->rpcCall('trade.add.memo',$params);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }
        $this->sellerlog('编辑订单备注。订单号是'.$params['tid']);
        $msg = app::get('topshop')->_('备注添加成功');
        $url = url::action('topshop_ctl_trade_detail@index',array('tid'=>$params['tid']));
        return $this->splash('success',$url,$msg,true);
    }

    /* modifyLogisticInfo()
     * 函数说明：更改物流信息
     * authorbyfanglongji
     * 2017-11-06
     */
    public function modifyLogisticInfo()
    {
        $logi_no = input::get('logi_no');
        $corp_code = input::get('corp_code');
        $delivery_id = input::get('delivery_id');
        $dlycorp = app::get('topshop')->rpcCall('shop.dlycorp.getlist',['shop_id'=>$this->shopId]);
        $pagedata['dlycorp'] = $dlycorp['list'];
        $pagedata['logi_no'] = $logi_no;
        $pagedata['delivery_id'] = $delivery_id;
        $pagedata['corp_code'] = $corp_code;

        return view::make('topshop/shop/logistics.html', $pagedata);
    }
}
