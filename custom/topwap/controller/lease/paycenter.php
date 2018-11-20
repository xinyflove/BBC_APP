<?php
class topwap_ctl_lease_paycenter extends topwap_controller{

    public function __construct($app)
    {
        parent::__construct();
        //$this->setLayoutFlag('paycenter');
        $this->setLayoutFlag('cart');
        // 检测是否登录
		
		kernel::single('base_session')->start();
        kernel::single("base_session")->set_sess_expires(10080);
        //if( !userAuth::check() )
        //{
        //    redirect::action('topwap_ctl_passport@signin')->send();exit;
        //}
    }
    // 支付第二步 选择支付方式
    public function index($filter=null)
    {
		$mobile=$_SESSION['mobile'];    
        //$payments_shop = app::get('topwap')->rpcCall('payment.get.shop.list',$shop_payType);
		$stages_id=input::get('stages_id');

		$pagedata['payment_total']=app::get('topwap')->rpcCall('get.money',array('stages_id'=>$stages_id));

		$filter['stages_id']=$stages_id;
        // 获取商家支付方式
        $shopInfo = app::get('sysshop')->rpcCall('shop.get',array('shop_id'=>32));
		$payments_shop=unserialize($shopInfo['payment']);
        $shop_pay = false;
        if(is_array($payments_shop))
        {
            foreach ($payments_shop as $key => $value) 
            {
                if(in_array($key, ['wxsj']) && $value['open'])
                {
                    if(kernel::single('topwap_wechat_wechat')->from_weixin())
                    {
                        $shop_pay = true;
                        $wxAppId = app::get('sysconf')->getConf('wechar.pay.server.appid');
                        $wxAppsecret = app::get('sysconf')->getConf('wechar.pay.server.appsecret');
                        if(!input::get('code'))
                        {
                            $url = url::action('topwap_ctl_lease_paycenter@index',$filter);
                            kernel::single('topwap_wechat_wechat')->get_code($wxAppId, $url);
                        }
                        else
                        {
                            $code = input::get('code');
                            $openid = kernel::single('topwap_wechat_wechat')->get_openid_by_code($wxAppId, $wxAppsecret, $code);
                            if($openid == null)
                                $this->splash('failed', 'back',  app::get('topwap')->_('获取openid失败'));
                            $pagedata['openid'] = $openid;
                        }
                    }
                }
            }
        }
		
        $payType['platform'] = 'iswap';
        $payments = app::get('topwap')->rpcCall('payment.get.list',$payType,'buyer');
        $payments = $this->paymentsSort($payments,'app_order_by');
        $shop['shop_id'] = '32';
        $shop_info = app::get('ectools')->rpcCall('shop.get', $shop);
        $shop_payment = unserialize($shop_info['payment']);


        foreach($payments as $paymentKey => $payment)
        {

            /*add_20170930_by_fanglongji_start*/
            if($payment['app_id'] == 'umspaypub')
            {
                unset($payments[$paymentKey]);
                continue;
            }

            // 平台是否授权and店铺是否开启
            if($shop_info['mer_collection'] == 'on' && !$shop_payment[$payment['app_id']]['open'])
            {
                unset($payments[$paymentKey]);
                continue;
            }

            // 微信h5支付
            if(in_array($payment['app_id'], ['wxserviceh5pay', 'wxh5pay']))
            {
                // 测试店铺使用
                if(!in_array($shop['shop_id'], [9]))
                {
                    unset($payments[$paymentKey]);
                    continue;
                }
                // 在微信浏览器中不启用
                if(kernel::single('topwap_wechat_wechat')->from_weixin())
                {
                    unset($payments[$paymentKey]);
                    continue;
                }
            }

            /*add_20170930_by_fanglongji_end*/
            if(in_array($payment['app_id'], ['wxservicepayapi','wxpayjsapi']))
            {
                if(!kernel::single('topwap_wechat_wechat')->from_weixin())
                {
                    unset($payments[$paymentKey]);
                    continue;
                }
                /*add_20171212_by_fanglongji_start*/
                if($shop_info['mer_collection'] == 'on' && $shop_payment[$payment['app_id']]['open'])
                {
                    if(in_array($payment['app_id'], ['wxpayjsapi']))
                    {
                        unset($payments[$paymentKey]);
                        continue;
                    }
                }
                else
                {
                    if(in_array($payment['app_id'], ['wxservicepayapi']))
                    {
                        unset($payments[$paymentKey]);
                        continue;
                    }
                }
            }
        }




		$pagedata['cart_number']=$this->getCartNumber($stages_id);
        $pagedata['tids'] = $tids['tid'];
        $pagedata['trades'] = $paymentBill;
        $pagedata['payments'] = $payments;
        $pagedata['payments_shop'] = $payments_shop;
		$pagedata['stages_id']=$stages_id;            //期数表的id
        //$pagedata['newtrade'] = $newtrade;
        return view::make('topwap/lease/paycenter/index.html', $pagedata);
    }
    // 支付第一步 创建支付单
    /*public function createPay()
    {
        $filter = input::get();
        $filter['user_id'] = userAuth::id();
        $filter['user_name'] = userAuth::getLoginName();

        // 一次支付只能支付一个订单
        // start
        if($filter['tid'] && is_array($filter['tid']) && (count($filter['tid']) >1) )
        {
            // $filter['tid'] = array_shift($filter['tid']);
            return $this->splash('success','','一次只能支付一个商家的订单',true);
        }

        if( !is_array($filter['tid']) && (strpos($filter['tid'],',') !== false)){
            // $filter['tid'] = substr( $filter['tid'], 0, strpos($filter['tid'],',') );
            return $this->splash('success','','一次只能支付一个商家的订单',true);
        }
        // end
        
        try
        {
            $paymentId = kernel::single('topwap_payment')->getPaymentId($filter);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            $url = url::action('topwap_ctl_member_trade@index');
            echo '<meta charset="utf-8"><script>alert("'.$msg.'");location.href="'.$url.'";</script>';
            exit;
        }
        $url = url::action('topwap_ctl_paycenter@index',array('payment_id'=>$paymentId,'merge'=>$ifmerge));
        return $this->splash('success',$url,$msg,true);
    }*/
    // 支付第三步 进行支付
    public function dopayment()
    {
        $postdata = input::get();
		$payment['money']=$postdata['money'];
		$payment['tids']=$postdata['tids'];
		$payment['payment_id']=$postdata['payment_id'];   //期数id  可能是多个id  用-隔开
		$payment['pay_app_id']=$postdata['pay_app_id'];
		$total_money=app::get('topwap')->rpcCall('get.money',array('stages_id'=>$payment['payment_id']));
		if($total_money!=$payment['money']){
			$msg="金额不对,请确认有再提交!";
            $url = url::action('topwap_ctl_lease_paycenter@index',array('stages_id'=>$payment['payment_id'],'app'=>$app));
            echo '<meta charset="utf-8"><script>alert("'.$msg.'");location.href="'.$url.'";</script>';
            exit();
		}
        // 预存款密码
		$app=input::get('app');
		$payment['app']=$app;
        if(!$payment['pay_app_id'])
        {
            echo '<meta charset="utf-8"><script>alert("请选择支付方式"); window.close();</script>';
            exit;
        }
        $payment['user_id'] = userAuth::id();
        $payment['platform'] = "wap";
		try
        {
            if($payment['pay_app_id'] == 'deposit' && $postdata['deposit_password'] == '')
            {
                throw new LogicException('请输入预存款支付密码！');
            }

           app::get('topwap')->rpcCall('payment.trade.leasepay',$payment);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            $url = url::action('topwap_ctl_lease_paycenter@index',array('stages_id'=>$payment['payment_id'],'app'=>$app));
            echo '<meta charset="utf-8"><script>alert("'.$msg.'");location.href="'.$url.'";</script>';
            exit();
        }
        $url = url::action('topwap_ctl_lease_paycenter@finish',array('stages_id'=>$payment['payment_id'],'app'=>$app));
        return $this->splash('success',$url,null,true);
    }

    public function finish()
    {
        $postdata = input::get();
		$app=input::get('app');
        try
        {
			$stages=app::get('syscart')->model('stages');
            $stagesId = $postdata['payment_id'];
			$stagesIdGp=explode('-',$stagesId);

			$filter['stages_id|in']=$stagesIdGp;
			$payment_status=$stages->getList('payment_status',$filter);
			$payment=array();
			foreach($payment_status as $k=>$status){
				$payment[]=$status['payment_status'];
			}
			if(in_array(2,$payment) || in_array(3,$payment)){
				$status='fail';
			}else{
				$status='succ';
			}
           // $params['fields'] = 'payment_id,status,pay_app_id,pay_name,money,cur_money,payed_time,created_time';
            
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
        }
        $pagedata['status'] = $status;
		$pagedata['app']=$app;
        return view::make('topwap/lease/paycenter/finish_new.html', $pagedata);
    }

	public function getCartNumber($stages_id){
		$stages=app::get('syscart')->model('stages');
        $stagesId=$stages_id;
        $stagesIdGp=explode('-',$stagesId);
        $filter['stages_id|in']=$stagesIdGp;
		$lease_id=$stages->getList('lease_id',$filter);
		$leaseId=array();
		foreach($lease_id as $k=>$v){
			$leaseId[]=$v['lease_id'];
		}
		$lease_filter['lease_id|in']=$leaseId;
		$cart_number=app::get('syscart')->model('lease_cart')->getList('cart_number',$lease_filter);
		return $cart_number;
	}
	

    //支付方式排序
    public function paymentsSort($payments,$orderBy,$sort_order=SORT_ASC)
    {
        if(is_array($payments)){
            foreach ($payments as $value) {
                if(is_array($value)){
                    $paymentList[] = $value[$orderBy];
                }
            }
        }
        array_multisort($paymentList,$sort_order,$payments);
        return $payments;
    }

}
