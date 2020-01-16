<?php
class topshop_ctl_trade_cancel extends topshop_controller {

    public $limit = 10;

    public function index()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('订单取消列表');
        $apiParams['shop_id'] = $this->shopId;
        if( input::get('tid') )
        {
            $apiParams['tid'] = input::get('tid');
        }
        $apiParams['page_no']  = intval(input::get('pages',1));
        $apiParams['page_size'] = intval($this->limit);
        if($this->loginSupplierId){
            $apiParams['supplier_id'] = $this->loginSupplierId;
        }
		/*add_2018/6/25_by_wanghaichao_start*/
		$pagedata['is_compere']=$this->sellerInfo['is_compere'];
		//把主持人的查询放进去
		if($pagedata['is_compere']==1){
			$apiParams['fields'] = 'a.*';
			$apiParams['is_compere']==1;
			$apiParams['seller_id']=$this->sellerId;
			$data = app::get('topshop')->rpcCall('trade.cancel.cancellist.get', $apiParams);        //这个接口是通过leftjoin order表来查询的
		}else{
			$apiParams['fields'] = '*';
			$data = app::get('topshop')->rpcCall('trade.cancel.list.get', $apiParams);           //这个接口是按常规处理的,如果有必要可以按照上面的接口查询,暂时先这样
		}

        if( $data['total'] )
        {
            $pagedata['list'] = $data['list'];
            $tids=array_column($pagedata['list'],'tid')?array_column($pagedata['list'],'tid'):array(0);
            $paymentinfo=app::get('ectools')->model('trade_paybill')->getList('*',array('status'=>'succ','tid|in'=>$tids));
            $payinfo=array_bind_key($paymentinfo,'tid');
            $cancelinfo=app::get('ectools')->model('refunds')->getList('*',array('status'=>'succ','refunds_type'=>1,'tid|in'=>$tids));
            $cancel=array_bind_key($cancelinfo,'tid');
            foreach($pagedata['list'] as $key => $value){
                $pagedata['list'][$key]['cancelinfo']=$cancel[$key];
                $pagedata['list'][$key]['payinfo']=$payinfo[$key];
            }

            $pagedata['count'] = $data['total'];
            $pagedata['pagers'] = $this->__pager($data['total'], input::get('pages',1));
        }
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        return $this->page('topshop/trade/cancel/list.html', $pagedata);
    }

    public function ajaxSearch()
    {
        switch( input::get('progress') )
        {
            case '0':
                $apiParams['refunds_status'] = 'WAIT_CHECK';
                break;
            case '1':
                $apiParams['refunds_status'] = 'WAIT_REFUND';
                break;
            case '2':
                $apiParams['refunds_status'] = 'SUCCESS';
                break;
            case '3':
                $apiParams['refunds_status'] = 'SHOP_CHECK_FAILS';
                break;
        }
        if( input::get('tid') )
        {
            $apiParams['tid'] = input::get('tid');
        }
        if( input::get('created_time') )
        {
            $times = array_filter(explode('-',input::get('created_time')));
            if($times)
            {
                $apiParams['created_time_start'] = strtotime($times['0']);
                $apiParams['created_time_end'] = strtotime($times['1'])+86400;
            }
        }
        if($this->loginSupplierId){
            $apiParams['supplier_id'] = $this->loginSupplierId;
        }
        $apiParams['shop_id'] = $this->shopId;
        $apiParams['page_no']  = intval(input::get('pages',1));
        $apiParams['page_size'] = intval($this->limit);
        try
        {
			/*add_2018/6/25_by_wanghaichao_start*/
			$pagedata['is_compere']=$this->sellerInfo['is_compere'];
			//把主持人的查询放进去
			if($pagedata['is_compere']==1){
				$apiParams['fields'] = 'a.*';
				$apiParams['is_compere']==1;
				$apiParams['seller_id']=$this->sellerId;
				$data = app::get('topshop')->rpcCall('trade.cancel.cancellist.get', $apiParams);        //这个接口是通过leftjoin order表来查询的
			}else{
				$apiParams['fields'] = '*';
				$data = app::get('topshop')->rpcCall('trade.cancel.list.get', $apiParams);
			}
			/*add_2018/6/25_by_wanghaichao_end*/
        }
        catch( Exception $e)
        {
        }

        if( $data['total'] )
        {
            $pagedata['list'] = $data['list'];
            $tids=array_column($pagedata['list'],'tid')?array_column($pagedata['list'],'tid'):array(0);
            $paymentinfo=app::get('ectools')->model('trade_paybill')->getList('*',array('status'=>'succ','tid|in'=>$tids));
            $payinfo=array_bind_key($paymentinfo,'tid');
            $cancelinfo=app::get('ectools')->model('refunds')->getList('*',array('status'=>'succ','refunds_type'=>1,'tid|in'=>$tids));
            $cancel=array_bind_key($cancelinfo,'tid');
            foreach($pagedata['list'] as $key => $value){
                $pagedata['list'][$key]['cancelinfo']=$cancel[$key];
                $pagedata['list'][$key]['payinfo']=$payinfo[$key];
            }
            $pagedata['count'] = $data['total'];
            $pagedata['pagers'] = $this->__pager($data['total'], input::get('pages',1));
        }
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');

        return view::make('topshop/trade/cancel/item.html', $pagedata);
    }

    /**
     * 分页处理
     *
     * @param $total  总条数
     * @param $current 当前页
     */
    private function __pager($total, $current)
    {
        $filter = input::get();

        //处理翻页数据
        $current = $current ? $current : 1;

        $filter['pages'] = time();

        if( $total > 0 ) $totalPage = ceil($total/$this->limit);
        $current = $totalPage < $current ? $totalPage : $current;

        $pagers = array(
            'link'=>url::action('topshop_ctl_trade_cancel@ajaxSearch',$filter),
            'current'=>$current,
            'total'=>$totalPage,
            'use_app'=>'topshop',
            'token'=>time(),
        );

        return $pagers;
    }

    public function detail()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('订单取消详情');
        $pagedata['tracking'] = app::get('syslogistics')->getConf('syslogistics.order.tracking');
        /*
         * start 退款功能 房隆基 2017.9.8
         */
        $roleFilter['seller_id']=$this->sellerId;
        $sellerRole=app::get('sysshop')->model('seller')->getRow('seller_type',$roleFilter);
        $pagedata['seller_role']=$sellerRole['seller_type'];
        /*
         * end
         */
        //面包屑
        $this->runtimePath = array(
            ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],
            ['url'=> url::action('topshop_ctl_trade_cancel@index'),'title' => app::get('topshop')->_('订单取消列表')],
            ['title' => app::get('topshop')->_('订单取消详情')],
        );

        $cancelId = input::get('cancel_id');
        try{
            $data = app::get('topc')->rpcCall('trade.cancel.get',['shop_id'=>$this->shopId,'cancel_id'=>$cancelId]);
        }catch(Exception $e){
    	    return $this->page('topshop/trade/cancel/detail.html',$pagedata);
        }

        $pagedata['data'] = $data;

        //获取取消订单的订单数据
        $tid = $data['tid'];
        $params['tid'] = $tid;
        $params['fields'] = "user_id,tid,status,payment,points_fee,ziti_addr,ziti_memo,shipping_type,post_fee,pay_type,payed_fee,receiver_state,receiver_city,receiver_district,receiver_address,receiver_zip,trade_memo,shop_memo,receiver_name,receiver_mobile,cancel_status,orders.price,orders.num,orders.title,orders.item_id,orders.pic_path,total_fee,discount_fee,buyer_rate,adjust_fee,orders.total_fee,orders.adjust_fee,created_time,pay_time,consign_time,end_time,shop_id,orders.bn,cancel_reason,orders.refund_fee,orders.gift_data";
        $tradeInfo = app::get('topshop')->rpcCall('trade.get',$params,'seller');
        $pagedata['trade'] = $tradeInfo;


        $paymentInfo=app::get('ectools')->model('trade_paybill')->getRow('*',array('status'=>'succ','tid'=>$tradeInfo['tid']));
        $pagedata['payinfo']=$paymentInfo;

        if($tradeInfo['cancel_status']=='SUCCESS'){
            $pagedata['cancelinfo']=app::get('ectools')->model('refunds')->getRow('*',array('tid'=>$tradeInfo['tid'],'status'=>'succ','refunds_type'=>1));
        }

        $userName = app::get('topshop')->rpcCall('user.get.account.name', ['user_id' => $tradeInfo['user_id']], 'seller');
        $pagedata['userName'] = $userName[$tradeInfo['user_id']];

        if($tradeInfo['shipping_type'] == 'ziti' )
        {
            $pagedata['ziti'] = "true";
        }

        if( $tradeInfo['status'] == 'WAIT_BUYER_CONFIRM_GOODS' || $tradeInfo['status'] == 'TRADE_FINISHED' )
        {
            $pagedata['logi'] = app::get('topshop')->rpcCall('delivery.get',array('tid'=>$tradeInfo['tid']));
        }
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
		/*add_2018/7/4_by_wanghaichao_start*/
		//判断是否是主持人的
		$pagedata['is_compere']=$this->sellerInfo['is_compere'];
		/*add_2018/7/4_by_wanghaichao_end*/
    	return $this->page('topshop/trade/cancel/detail.html',$pagedata);
    }

    //商家审核是否同意取消订单
    public function shopCheckCancel()
    {
        $params['cancel_id'] = input::get('cancel_id');
        $params['shop_id'] = $this->shopId;

        if( input::get('check_result','false') == 'false' )
        {
             $validator = validator::make(
                [ trim(input::get('shop_reject_reason'))],
                [ 'required|max:50'],
                ['拒绝理由必填|拒绝理由最多为50个字符!']
            );
            if ($validator->fails())
            {
                $messages = $validator->messagesInfo();

                foreach( $messages as $error )
                {
                    return $this->splash('error',null,$error[0]);
                }
            }
            $params['status'] = 'reject';
            $params['reason'] = input::get('shop_reject_reason');
        }
        else
        {
            $params['status'] = 'agree';
        }

        try{
            app::get('topshop')->rpcCall('trade.cancel.shop.check',$params);
        }
        catch( LogicException $e ){
            return $this->splash('error',null, $e->getMessage(), true);
        }
        $this->sellerlog('处理订单取消申请。申请ID是'.$params['cancel_id']);
        $url = url::action('topshop_ctl_trade_cancel@detail',['cancel_id'=>$params['cancel_id']]);

        return $this->splash('success',$url, '审核提交成功', true);
    }

    /**
     * 取消订单的退款
     * @Author   房隆基
     * @DateTime 2017-09-08
     * @return   [type]                   [description]
     */
    public function cancel_refund()
    {
        $db = app::get('topshop')->database();
        $db->beginTransaction();
        $cancelId = input::get('cancel_id');
        try
        {
            $data = app::get('topc')->rpcCall('trade.cancel.get',['shop_id'=>$this->shopId,'cancel_id'=>$cancelId]);

            $filter['shop_id'] = $this->shopId;
            $filter['status|in'] = array('3','5','6');
            $filter['tid'] = $data['tid'];
            $objMdlRefunds = app::get('sysaftersales')->model('refunds');
            $refunds = $objMdlRefunds->getRow('refunds_id,refund_bn,status,aftersales_bn,refund_fee,total_price,refunds_type,user_id,shop_id,tid,oid',$filter);
            kernel::single('topshop_refunds')->dorefund($refunds);
            $db->commit();
            $this->sellerlog('处理取消订单退款申请。申请ID是'.$cancelId);
        }
        catch(LogicException $e)
        {
            $db->rollback();
            return $this->splash('error',null, $e->getMessage(), true);
        }


        $url = url::action('topshop_ctl_trade_cancel@detail',['cancel_id' => $cancelId]);
        return $this->splash('success', $url, '退款成功', true);
    }

	/* action_name (par1, par2, par3)
	* 推送商品订单取消管理
	* author by wanghaichao
	* date 2018/7/4
	*/
	public function pushItemCancelTrade(){

		/*$params='';
        if(!$params)
        {
			$mintime=strtotime(date('Y-m-d 00:00:00', time()));
            $maxtime=strtotime(date('Y-m-d 23:59:59', time()));

			$filter="b.pay_time > '{$mintime}' and b.pay_time < '{$maxtime}' and (a.seller_id !='' or a.init_shop_id!=0 or a.init_shop_id!='')";
			//$refundfilter ="b.finish_time >'{$mintime}' and b.finish_time<'{$maxtime}' and (a.seller_id !='' or a.init_shop_id!=0 or a.init_shop_id!='') and b.status='succ'";

            $refundfilter = array(
                'finish_time|than'=>strtotime(date('Y-m-d 00:00:00', time())),
				'finish_time|lthan'=>strtotime(date('Y-m-d 23:59:59', time())),
            );
        }else{
            //$filter['pay_time|than']=$params['time_start'];
            //$filter['pay_time|lthan']=$params['time_end'];

			$filter="b.pay_time > '{$params['time_start']}' and b.pay_time < '{$params['time_end']}' and (a.seller_id !='' or a.seller_id!=0 or a.init_shop_id!=0 or a.init_shop_id!='')";

			//$refundfilter ="b.finish_time >'{$params['time_start']}' and b.finish_time<'{$params['time_end']}' and (a.seller_id !='' or a.init_shop_id!=0 or a.init_shop_id!='') and b.status='succ'";

            $refundfilter['finish_time|than'] = $params['time_start'];
            $refundfilter['finish_time|lthan'] = $params['time_end'];
        }
		//$filter['seller_id|noequal']='';
        //pay_time > 0 eq payed
        //$payTradeIds=app::get('systrade')->model('order')->getList('tid',$filter);

		$listsBuilder=db::connection()->createQueryBuilder();
        $payTradeIds = $listsBuilder->select('a.tid')
            ->from('systrade_order', 'a')
            ->where($filter)
            ->leftjoin('a', 'systrade_trade', 'b', 'b.tid = a.tid')
			->groupBy('a.tid')
            ->execute()->fetchAll();
        if(!empty($payTradeIds)){
           // foreach($payTradeIds as $tid){
            //    $payClearing = app::get('systrade')->rpcCall('clearing.sellerbilling.add',$tid);
		//	}
        }

        $refundTradeInfo=app::get('ectools')->model('refunds')->getList('tid,oid,finish_time as trade_time',array_merge(array('status'=>'succ'),$refundfilter));
		//echo "<pre>";print_r($refundTradeInfo);die();

        if(!empty($refundTradeInfo)){
            foreach($refundTradeInfo as $refund){
                $refund['billing_type']=2;
                $refundCleaing = app::get('systrade')->rpcCall('clearing.sellerbilling.add',$refund);
            }
        }*/


		$this->contentHeaderTitle = app::get('topshop')->_('推送商品订单取消列表');
        $apiParams['init_shop_id'] = $this->shopId;
        if( input::get('tid') )
        {
            $apiParams['tid'] = input::get('tid');
        }
        $apiParams['page_no']  = intval(input::get('pages',1));
        $apiParams['page_size'] = intval($this->limit);
		$apiParams['fields'] = 'a.*';
		$data = app::get('topshop')->rpcCall('trade.cancel.cancellist.get', $apiParams);        //这个接口是通过leftjoin order表来查询的

        if( $data['total'] )
        {
            $pagedata['list'] = $data['list'];
            $tids=array_column($pagedata['list'],'tid')?array_column($pagedata['list'],'tid'):array(0);
            $paymentinfo=app::get('ectools')->model('trade_paybill')->getList('*',array('status'=>'succ','tid|in'=>$tids));
            $payinfo=array_bind_key($paymentinfo,'tid');
            $cancelinfo=app::get('ectools')->model('refunds')->getList('*',array('status'=>'succ','refunds_type'=>1,'tid|in'=>$tids));
            $cancel=array_bind_key($cancelinfo,'tid');
            foreach($pagedata['list'] as $key => $value){
                $pagedata['list'][$key]['cancelinfo']=$cancel[$key];
                $pagedata['list'][$key]['payinfo']=$payinfo[$key];
            }

            $pagedata['count'] = $data['total'];
            $pagedata['pagers'] = $this->__pager($data['total'], input::get('pages',1));
        }
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
		$pagedata['push']=1;
        return $this->page('topshop/trade/cancel/pushlist.html', $pagedata);
	}

	/*
	* 推送商品取消订单
	* author by wanghaichao
	* date 2018/7/4
	*/
    public function ajaxPushSearch()
    {
        switch( input::get('progress') )
        {
            case '0':
                $apiParams['refunds_status'] = 'WAIT_CHECK';
                break;
            case '1':
                $apiParams['refunds_status'] = 'WAIT_REFUND';
                break;
            case '2':
                $apiParams['refunds_status'] = 'SUCCESS';
                break;
            case '3':
                $apiParams['refunds_status'] = 'SHOP_CHECK_FAILS';
                break;
        }
        if( input::get('tid') )
        {
            $apiParams['tid'] = input::get('tid');
        }
        if( input::get('created_time') )
        {
            $times = array_filter(explode('-',input::get('created_time')));
            if($times)
            {
                $apiParams['created_time_start'] = strtotime($times['0']);
                $apiParams['created_time_end'] = strtotime($times['1'])+86400;
            }
        }

        $apiParams['init_shop_id'] = $this->shopId;
        $apiParams['page_no']  = intval(input::get('pages',1));
        $apiParams['page_size'] = intval($this->limit);
        try
        {
			$apiParams['fields'] = 'a.*';
			$data = app::get('topshop')->rpcCall('trade.cancel.cancellist.get', $apiParams);        //这个接口是通过leftjoin order表来查询的
        }
        catch( Exception $e)
        {
        }

        if( $data['total'] )
        {
            $pagedata['list'] = $data['list'];
            $tids=array_column($pagedata['list'],'tid')?array_column($pagedata['list'],'tid'):array(0);
            $paymentinfo=app::get('ectools')->model('trade_paybill')->getList('*',array('status'=>'succ','tid|in'=>$tids));
            $payinfo=array_bind_key($paymentinfo,'tid');
            $cancelinfo=app::get('ectools')->model('refunds')->getList('*',array('status'=>'succ','refunds_type'=>1,'tid|in'=>$tids));
            $cancel=array_bind_key($cancelinfo,'tid');
            foreach($pagedata['list'] as $key => $value){
                $pagedata['list'][$key]['cancelinfo']=$cancel[$key];
                $pagedata['list'][$key]['payinfo']=$payinfo[$key];
            }
            $pagedata['count'] = $data['total'];
            $pagedata['pagers'] = $this->__pager($data['total'], input::get('pages',1));
        }
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
		$pagedata['push']=1;
        return view::make('topshop/trade/cancel/item.html', $pagedata);
    }

	/* action_name (par1, par2, par3)
	* 推送商品的详情
	* author by wanghaichao
	* date 2018/7/4
	*/
	public function pushDetail()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('推送商品订单取消详情');
        $pagedata['tracking'] = app::get('syslogistics')->getConf('syslogistics.order.tracking');
        /*
         * start 退款功能 房隆基 2017.9.8
         */
        $roleFilter['seller_id']=$this->sellerId;
        $sellerRole=app::get('sysshop')->model('seller')->getRow('seller_type',$roleFilter);
        $pagedata['seller_role']=$sellerRole['seller_type'];
        /*
         * end
         */
        //面包屑
        $this->runtimePath = array(
            ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],
            ['url'=> url::action('topshop_ctl_trade_cancel@index'),'title' => app::get('topshop')->_('订单取消列表')],
            ['title' => app::get('topshop')->_('订单取消详情')],
        );

        $cancelId = input::get('cancel_id');
        try{
            $data = app::get('topc')->rpcCall('trade.cancel.get',['cancel_id'=>$cancelId]);
        }catch(Exception $e){
    	    return $this->page('topshop/trade/cancel/detail.html',$pagedata);
        }

        $pagedata['data'] = $data;

        //获取取消订单的订单数据
        $tid = $data['tid'];
        $params['tid'] = $tid;
		$params['init_shop_id']=$this->shopId;
        $params['fields'] = "user_id,tid,status,payment,points_fee,ziti_addr,ziti_memo,shipping_type,post_fee,pay_type,payed_fee,receiver_state,receiver_city,receiver_district,receiver_address,receiver_zip,trade_memo,shop_memo,receiver_name,receiver_mobile,cancel_status,orders.price,orders.num,orders.title,orders.item_id,orders.pic_path,total_fee,discount_fee,buyer_rate,adjust_fee,orders.total_fee,orders.adjust_fee,created_time,pay_time,consign_time,end_time,shop_id,orders.bn,cancel_reason,orders.refund_fee,orders.gift_data,orders.init_shop_id,orders.cost_price";

        $tradeInfo = app::get('topshop')->rpcCall('trade.get',$params,'seller');
		if(empty($tradeInfo['orders'])){
			$pagedata['data']='';
			return $this->page('topshop/trade/cancel/detail.html',$pagedata);
		}

		//这里不能给原始店铺展示售价,只能给他展示他的供货价
		$t_total_fee=''; //订单的总金额;
		foreach($tradeInfo['orders'] as $k=>$v){
			$tradeInfo['orders'][$k]['price']=$v['cost_price'];
			$tradeInfo['orders'][$k]['total_fee']=$v['cost_price']*$v['num'];
			$t_total_fee+=$v['cost_price']*$v['num'];
		}
		$tradeInfo['total_fee']=$t_total_fee;
		$tradeInfo['payment']=$t_total_fee;

        $pagedata['trade'] = $tradeInfo;

       $paymentInfo=app::get('ectools')->model('trade_paybill')->getRow('*',array('status'=>'succ','tid'=>$tradeInfo['tid']));
        $pagedata['payinfo']=$paymentInfo;

        if($tradeInfo['cancel_status']=='SUCCESS'){
            $pagedata['cancelinfo']=app::get('ectools')->model('refunds')->getRow('*',array('tid'=>$tradeInfo['tid'],'status'=>'succ','refunds_type'=>1));
        }

        $userName = app::get('topshop')->rpcCall('user.get.account.name', ['user_id' => $tradeInfo['user_id']], 'seller');
        $pagedata['userName'] = $userName[$tradeInfo['user_id']];

        if($tradeInfo['shipping_type'] == 'ziti' )
        {
            $pagedata['ziti'] = "true";
        }

        if( $tradeInfo['status'] == 'WAIT_BUYER_CONFIRM_GOODS' || $tradeInfo['status'] == 'TRADE_FINISHED' )
        {
            $pagedata['logi'] = app::get('topshop')->rpcCall('delivery.get',array('tid'=>$tradeInfo['tid']));
        }
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
		$pagedata['push']=1;  //根据这个判断详情是不是推送商品
		$pagedata['is_compere']=$this->sellerInfo['is_compere'];
    	return $this->page('topshop/trade/cancel/detail.html',$pagedata);
    }
}

