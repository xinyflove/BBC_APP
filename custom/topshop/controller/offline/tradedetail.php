<?php
class topshop_ctl_offline_tradedetail extends topshop_controller{
    public function index()
    {
        $tid = input::get('tid');
        //面包屑
        $this->runtimePath = array(
            ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],
            ['url'=> url::action('topshop_ctl_offline_trade@index'),'title' => app::get('topshop')->_('线下收款订单列表')],
            ['title' => app::get('topshop')->_('线下收款订单详情')],
        );
        $tradeInfo=app::get('systrade')->model('offline_trade')->getRow('*',array('tid'=>$tid));
        if(!$tradeInfo){
            redirect::action('topshop_ctl_offline_trade@index')->send();exit;
        }

        $offlineStoreInfo=app::get('syssupplier')->model('agent_shop')->getRow('*',array('agent_shop_id'=>$tradeInfo['agent_shop_id']));
        $supplierInfo=app::get('sysshop')->model('supplier')->getRow('*',array('supplier_id'=>$offlineStoreInfo['supplier_id'],'is_audit'=>'PASS'));
        $userAccountInfo=app::get('sysuser')->model('account')->getRow('*',array('user_id'=>$tradeInfo['user_id']));
        $userInfo=app::get('sysuser')->model('user')->getRow('*',array('use_id'=>$tradeInfo['user_id']));
        $activityInfo=app::get('syssupplier')->model('agent_activity')->getRow('*',array('agent_activity_id'=>$tradeInfo['all_hold_id']));
        $voucherInfo=app::get('systrade')->model('agent_vocher')->getList('*',array('tid'=>$tid));
        $itemInfo=app::get('sysitem')->model('item')->getRow('*',array('item_id'=>$voucherInfo[0]['item_id']));
        $offlineSettleInfo=app::get('sysclearing')->model('offline_payment_detail')->getRow('*',array('offline_tid'=>$tid));

        $this->contentHeaderTitle = app::get('topshop')->_('线下付款订单详情');

        $pagedata['trade']= $tradeInfo;
        $pagedata['store']=$offlineStoreInfo;
        $pagedata['supplier']=$supplierInfo;
        $pagedata['useraccount']=$userAccountInfo;
        $pagedata['userinfo']=$userInfo;
        $pagedata['activityinfo']=$activityInfo;
        $pagedata['voucherinfo']=$voucherInfo;
        $pagedata['iteminfo']=$itemInfo;
        $pagedata['settleinfo']=$offlineSettleInfo;

        return $this->page('topshop/offline/trade/detail.html', $pagedata);
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
                ->where('agent_shop_id='.$offline_trade_data['agent_shop_id']);
            $agent_shop_data = $queryBuilder->execute()->fetch();
            $offline_trade_data['agent_shop_data'] = $agent_shop_data;
            $offline_trade_data['pay_time'] = date('Y-m-d H:i',$offline_trade_data['pay_time']);
            return $this->splash('success',null,['data'=>$offline_trade_data]);
        }else{
            return $this->splash('error');
        }
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
