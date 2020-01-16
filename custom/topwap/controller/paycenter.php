<?php

/**
 * paycenter.php 支付
 *
 * @author     Xiaodc
 * @copyright  Copyright (c) 2005-2015 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class topwap_ctl_paycenter extends topwap_controller {

    protected $payment_icon = array(
            'wapupacp' => 'bbc-icon-unipay pay-style-unipay',
            'deposit' => 'bbc-icon-qianbao pay-style-qianbao',
            'malipay' => 'bbc-icon-zhifubao pay-style-zhifubao',
            'wxpayjsapi' => 'bbc-icon-weixin pay-style-weixin',
             /*add_20170925_by_fanglongji_start*/
            'umspaypub' => 'bbc-icon-weixin pay-style-weixin',
            'wxservicepayapi' => 'bbc-icon-weixin pay-style-weixin',
            'miniservicepayapi' => 'bbc-icon-weixin pay-style-weixin'
            /*add_20170925_by_fanglongji_end*/
    );

    public function __construct($app)
    {
        parent::__construct($app);
        // 检测是否登录
        if( !userAuth::check() )
        {
            if( request::ajax() )
            {
                $url = url::action('topwap_ctl_passport@goLogin');
                return $this->splash('error', $url, app::get('topwap')->_('请登录'), true);
            }
            redirect::action('topwap_ctl_passport@goLogin')->send();exit;
        }

        $this->marketingSetting = unserialize(app::get('syspromotion')->getConf('trailingmarketing'));
        if($this->marketingSetting['status'] && $this->marketingSetting['platform'] != '2' && $this->marketingSetting['scratchcard_id'])
        {
            $this->scratchcardInfo = app::get('topwap')->rpcCall('promotion.scratchcard.get',['scratchcard_id'=>$this->marketingSetting['scratchcard_id']]);
        }
    }

    public function selectHongbao()
    {
        $total = input::get('total');
        $apiParams = [
            'user_id' => userAuth::id(),
            'is_valid'=> 'active',
            'fields'=>'hongbao_id,name,money,id,end_time',
            'page_size'=>'100',
            'used_platform' => "wap",
        ];
        $hongbaoData = app::get('topwap')->rpcCall('user.hongbao.list.get', $apiParams);
        $pagedata['hongbao_list'] = $hongbaoData['list'];

        // 获取当前平台设置的货币符号和精度
        $cur_symbol = app::get('topapi')->rpcCall('currency.get.symbol',array());
        $pagedata['cur_symbol'] = $cur_symbol;
        $pagedata['total'] = $total;
        $pagedata['rediret'] = request::server('HTTP_REFERER');
        $pagedata['active_hongbao_id'] = $_SESSION['pay_user_hongbao_id'];

        return $this->page('topwap/payment/redpacketlist.html', $pagedata);
    }

    public function saveHongbao()
    {
        if( input::get('hongbao_id') )
        {
            $_SESSION['pay_user_hongbao_id'] = explode(',',input::get('hongbao_id'));
        }
        else
        {
            unset($_SESSION['pay_user_hongbao_id']);
        }

        return $this->splash('success', null, null, true);
    }

    public function index($filter=null)
    {
        $this->setLayoutFlag('order_detail');
        if(!$filter)
        {
            $filter = input::get();
        }

        // 线下支付
        if(isset($filter['tid']) && $filter['tid'])
        {
            $pagedata['payment_type'] = "offline";
            $ordersMoney = app::get('topwap')->rpcCall('trade.money.get',array('tid'=>$filter['tid']),'buyer');

            if($ordersMoney)
            {
                foreach($ordersMoney as $key=>$val)
                {
                    $newOrders[$val['tid']] = $val['payment'];
                    $newMoney += $val['payment'];
                }
                $paymentBill['money'] = $newMoney;
                $paymentBill['cur_money'] = $newMoney;
            }
            $pagedata['trades'] = $paymentBill;
            $pagedata['title'] = app::get('topwap')->_('订单状态');
            return $this->page('topwap/payment/offline.html', $pagedata);
        }
        $back_url = url::action('topwap_ctl_member_trade@tradeList');
        $plat_form = 'iswap';
        $keep_way = null;
        // 如果来自小程序
        if( kernel::single('topwap_wechat_wechat')->from_mini() ){
            $keep_way = 'miniservicepayapi';
            $plat_form = 'is_mini';
        }

        try
        {
            $pagedata = $this->__commonPay($filter, $plat_form, $keep_way);
            $pagedata['back_url'] = $back_url;
            $pagedata['sess_id'] = kernel::single('base_session')->sess_id();

            return $this->page('topwap/payment/index.html', $pagedata);
        }
        catch(Exception $e)
        {
            return $this->splash('error',$back_url,$e->getMessage(),true);
        }
    }

    /**
     * @param null $filter
     * @return mixed
     * 小程序支付页面
     */
    public function miniPay($filter=null)
    {
        if(!$filter)
        {
            $filter = input::get();
        }
        $back_url = url::action('topwap_ctl_miniprogram_trade@ajaxTradeList',['s'=>0]);

        $keep_way = 'miniservicepayapi';
        $plat_form = 'is_mini';
        try
        {
            $common_data = $this->__commonPay($filter, $plat_form, $keep_way);
            $common_data['back_url'] = $back_url;
            $return_data['err_no'] = 0;
            $return_data['data'] = $common_data;
            $return_data['message'] = '';
        }
        catch(Exception $e)
        {
            $return_data['err_no'] = 1001;
            $return_data['data'] = [];
            $return_data['message'] = $e->getMessage();
        }
        return response::json($return_data);
    }

    /**
     * @param $filter
     * @param $plat_form
     * @param $keep_way
     * @return mixed
     * @throws Exception
     * 获取支付的共同参数
     */
    private function __commonPay($filter, $plat_form, $keep_way = null)
    {
        if($filter['newtrade'])
        {
            $newtrade = $filter['newtrade'];
            unset($filter['newtrade']);
        }

        $filter['fields'] = "*";
        $paymentBill = app::get('topwap')->rpcCall('payment.bill.get',$filter,'buyer');

        //检测订单中的金额是否和支付金额一致 及更新支付金额
        $trade = $paymentBill['trade'];
        $tids['tid'] = implode(',',array_keys($trade));
        $ordersMoney = app::get('topwap')->rpcCall('trade.money.get',$tids,'buyer');

        if($ordersMoney)
        {
            foreach($ordersMoney as $key=>$val)
            {
                $newMoney = 0;
                foreach($ordersMoney as $key=>$val)
                {
                    $newOrders[$val['tid']] = $val['payment'];
                    $newMoney = ecmath::number_plus(array($newMoney, ecmath::number_minus(array($val['payment'], $val['hongbao_fee']))));
                }
            }

            $result = array(
                'trade_own_money' => json_encode($newOrders),
                'money' => $newMoney,
                'cur_money' => $newMoney,
                'payment_id' => $filter['payment_id'],
            );

            if($newMoney != $paymentBill['cur_money'])
            {
                try{
                    app::get('topwap')->rpcCall('payment.money.update',$result);
                }
                catch(Exception $e)
                {
                    $msg = $e->getMessage();
                    throw new Exception($msg);
                }
                $paymentBill['money'] = $newMoney;
                $paymentBill['cur_money'] = $newMoney;
            }
        }
        /*add_20170925_by_fanglongji_start*/
        $sql="SELECT so.oid, so.item_id, so.sku_id, so.title, so.spec_nature_info, so.price, so.num, so.pic_path, st.created_time, so.shop_id FROM ".
            " systrade_trade AS st LEFT JOIN systrade_order AS so ON st.tid = so.tid WHERE ".
            " st.tid  IN (".$tids['tid'].")";
        $order_list = app::get('base')->database()->executeQuery($sql)->fetchAll();
        /*add_20170925_by_fanglongji_end*/
        /*add_20170930_by_fanglongji_start*/
        $payments = $this->__getPaymentWay($order_list[0]['shop_id'], $plat_form, $keep_way);
        /*add_20170930_by_fanglongji_end*/
        // 微信支付
        // ff($payments);
        $apiParams = [
            'user_id' => userAuth::id(),
            'is_valid'=> 'active',
            'fields'=>'hongbao_id,name,money,id,end_time',
            'page_size'=>'100',
            'used_platform' => "wap",
        ];
        $hongbaoData = app::get('topwap')->rpcCall('user.hongbao.list.get', $apiParams);
        if( !$hongbaoData )
        {
            $pagedata['is_empty_hongbao'] = true;
        }
        else
        {
            if( $_SESSION['pay_user_hongbao_id'] )
            {
                $userHongbaoIds = $_SESSION['pay_user_hongbao_id'];
                $selectHongbaoMoney = 0;
                foreach($hongbaoData['list'] as $row)
                {
                    if(in_array($row['id'], $userHongbaoIds))
                    {
                        $selectHongbao[] = $row;
                        $selectHongbaoMoney = ecmath::number_plus(array($selectHongbaoMoney, $row['money']));
                    }
                }

                if( $selectHongbaoMoney > $newMoney )
                {
                    unset($_SESSION['pay_user_hongbao_id']);
                }
                else
                {
                    $pagedata['select_hongbao_list'] = $selectHongbao;
                    $pagedata['select_hongbao_money'] = $selectHongbaoMoney;
                }
            }
        }
        /*add_20170924_by_fanglongji_end*/
        $intervalTime = app::get('sysconf')->getConf('trade.cancel.spacing.time');
        $intervalTime = $intervalTime*3600;
        $pagedata['surplus_time'] = ($order_list[0]['created_time']+$intervalTime)-time();
        $pagedata['order_list'] = $order_list;
        /*add_20170924_by_fanglongji_end*/
        $pagedata['tids'] = $tids['tid'];
        $pagedata['trades'] = $paymentBill;
        $pagedata['payments'] = $payments;
        $pagedata['newtrade'] = $newtrade;
        /*modify_20170924_by_fanglongji_start*/
        /*
            $pagedata['title'] = app::get('topwap')->_('订单支付');
        */
        $pagedata['title'] = app::get('topwap')->_('支付订单');
        /*modify_20170924_by_fanglongji_end*/
        $pagedata['payment_icon'] = $this->payment_icon;
        $pagedata['hasDepositPassword'] = app::get('topwap')->rpcCall('user.deposit.password.has', ['user_id'=>userAuth::id()]);

        return $pagedata;
    }

    // 创建支付
    public function createPay()
    {

        $filter = input::get();
        $filter ['user_id'] = userAuth::id();
        $filter ['user_name'] = userAuth::getLoginName();
        $ifmerge = $filter['merge'];
        try
        {
            $paymentId = kernel::single('topwap_payment')->getPaymentId($filter);
        } catch ( Exception $e )
        {
            $msg = $e->getMessage();
            $url = url::action('topwap_ctl_member_trade@tradeList');

            return $this->splash('error', $url, $msg);
        }
        $url = url::action('topwap_ctl_paycenter@index', array(
                'payment_id' => $paymentId,
                'merge' => $ifmerge
        ));
        return $this->splash('success', $url, $msg, true);
    }

    /**
     * @return mixed
     * 订单列表去付款
     */
    public function createMiniPay()
    {
        try
        {
            $filter = input::get();
            $filter ['user_id'] = userAuth::id();
            $filter ['user_name'] = userAuth::getLoginName();

            $paymentId = kernel::single('topwap_payment')->getPaymentId($filter);
            $redirect_url = url::action('topwap_ctl_paycenter@miniPay',array('payment_id'=>$paymentId,'merge'=>true, 'mode'=>'mini'));
            $return_data['err_no'] = 0;
            $return_data['data'] = ['redirect_url' => $redirect_url];
            $return_data['message'] = [];
        }
        catch(Exception $e)
        {
            $redirect_url = url::action('topwap_ctl_miniprogram_trade@ajaxTradeList',['s'=>0]);
            $msg = $e->getMessage();
            $return_data['err_no'] = 0;
            $return_data['data'] = ['redirect_url' => $redirect_url];
            $return_data['message'] = $msg;
        }
        return response::json($return_data);
    }

    // 开始支付
    public function dopayment()
    {
        $postdata = input::get();
        $payment = $postdata['payment'];
        $payment['deposit_password'] = $postdata['deposit_password'];
        $payment['user_id'] = userAuth::id();
        $payment['platform'] = "wap";
        $payment['hongbao_ids'] = implode(',',$_SESSION['pay_user_hongbao_id']);
        unset($_SESSION['pay_user_hongbao_id']);
        try
        {
            app::get('topwap')->rpcCall('payment.trade.pay',$payment);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            $url = url::action('topwap_ctl_paycenter@index',array('payment_id'=>$payment['payment_id']));
            echo '<meta charset="utf-8"><script>alert("'.$msg.'");location.href="'.$url.'";</script>';
            exit();
        }
        $url = url::action('topwap_ctl_paycenter@finish',array('payment_id'=>$payment['payment_id']));
        return $this->splash('success', $url, $msg, true);
    }

    /**
     * @return base_view_object_interface|string
     * 小程序支付
     */
    public function miniDoPayment()
    {
        $postdata = input::get();
        $payment = json_decode($postdata['payment'],1);
        // 来自哪里的小程序 platform：服务商的小程序
        $payment['from_mini'] = $postdata['from_mini'];
        $payment['deposit_password'] = $postdata['deposit_password'];
        $payment['user_id'] = userAuth::id();
        $payment['platform'] = "wap";
        $payment['hongbao_ids'] = implode(',',$_SESSION['pay_user_hongbao_id']);
        unset($_SESSION['pay_user_hongbao_id']);
        try
        {
            $sign_array = app::get('topwap')->rpcCall('payment.trade.pay',$payment);
            $return_data['err_no'] = 0;
            $return_data['data'] = $sign_array;
            $return_data['message'] = '';
        }
        catch(Exception $e)
        {
            $return_data['err_no'] = 1001;
            $return_data['data'] = [];
            $return_data['message'] = $e->getMessage();
        }
        return response::json($return_data);
    }

    /**
     * @return mixed
     * 小程序支付完成
     */
    public function miniFinish()
    {
        $postdata = input::get();
        $pagedata['back_url'] = url::action('topwap_ctl_miniprogram_trade@ajaxTradeList',['s'=>0]);
        try
        {
            $params['payment_id'] = $postdata['payment_id'];
            $params['fields'] = 'payment_id,status,pay_app_id,pay_name,money,cur_money,payed_time,created_time,return_url';
            $result = app::get('topwap')->rpcCall('payment.bill.get',$params);


            $apiParams['user_id'] = userAuth::id();
            $apiParams['tid'] = implode(",",array_column($result['trade'], 'tid'));
            $apiParams['fields'] = "tid,payment,payed_fee,hongbao_fee,status,pay_type,shop_id";
            $trades = app::get('topc')->rpcCall('trade.get.list',$apiParams);

            $hongbaoMoney = 0;
            $tradeTotalPayment = 0;
            foreach( $trades['list'] as $row )
            {
                $hongbaoMoney = ecmath::number_plus(array($hongbaoMoney, $row['hongbao_fee']));
                $tradeTotalPayment = ecmath::number_plus(array($tradeTotalPayment, $row['payment']));
            }

            $pagedata['hongbao_fee'] = $hongbaoMoney;
            if( $tradeTotalPayment ==  $hongbaoMoney )
            {
                $result['status'] = 'succ';
            }

            $trades = $result['trade_own_money'];
            $result['num'] = count($trades);
            $pagedata['payment'] = $result;
            $pagedata['marketingSetting'] = $this->marketingSetting;
            $pagedata['scratchcardInfo'] = $this->scratchcardInfo['scratchcard'];

            $return_data['err_no'] = 0;
            $return_data['data'] = $pagedata;
            $return_data['message'] = '';
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            $return_data['err_no'] = 1001;
            $return_data['data'] = $pagedata;
            $return_data['message'] = $msg;
        }
        $return_data['create_pay_url'] = $redirect_url = url::action('topwap_ctl_paycenter@miniPay',array('payment_id'=>$postdata['payment_id'],'merge'=>true, 'mode'=>'mini'));
        return response::json($return_data);
    }

    // 支付完成
    public function finish()
    {
        $this->setLayoutFlag('order_detail');
        $postdata = input::get();
        try
        {
            // 微信H5支付 刷新页面get请求 特别处理
            // if(isset($postdata['payment']['payment_id']) && !empty($postdata['payment']['payment_id'])) {
            //     $postdata['payment_id'] = $postdata['payment']['payment_id'];
            // }

            $params['payment_id'] = $postdata['payment_id'];
            $params['fields'] = 'payment_id,status,pay_app_id,pay_name,money,cur_money,payed_time,created_time,return_url';
            $result = app::get('topwap')->rpcCall('payment.bill.get',$params);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
        }

        $apiParams['user_id'] = userAuth::id();
        $apiParams['tid'] = implode(",",array_column($result['trade'], 'tid'));
        /*modify_20170926_by_fanglongji_start*/
        /*
        $apiParams['fields'] = "tid,payment,payed_fee,hongbao_fee,status,pay_type";
        */
        $apiParams['fields'] = "tid,payment,payed_fee,hongbao_fee,status,pay_type,shop_id,pay_time";
        /*modify_20170926_by_fanglongji_end*/
        $trades = app::get('topc')->rpcCall('trade.get.list',$apiParams);

        $hongbaoMoney = 0;
        $tradeTotalPayment = 0;
        foreach( $trades['list'] as $row )
        {
            $hongbaoMoney = ecmath::number_plus(array($hongbaoMoney, $row['hongbao_fee']));
            $tradeTotalPayment = ecmath::number_plus(array($tradeTotalPayment, $row['payment']));
            $shop_id = $row['shop_id'];
			/*add_2019/8/2_by_wanghaichao_start*/			
			$this_shop_id=$row['shop_id'];       //本订单的店铺id
			$tid=$row['tid'];							  //根据订
			$pay_time=$row['pay_time'];
			/*add_2019/8/2_by_wanghaichao_end*/
        }
        //取出广告相关设置和信息
        $auth_shop_id          = app::get('syspromotion')->getConf('advert.auth.shop.id');
        //如果总后台开启了广告授权的店铺，则选取授权店铺的信息
        if($auth_shop_id)
        {
            $shop_id = $auth_shop_id;
        }
        /*add_20170926_by_fanglongji_start*/
        $parames['limit_num'] = 10;
        $parames['fields'] = "SI.item_id, image_default_id, title, price, sold_quantity,SI.shop_id";
        $parames['filter']['shop_id'] = $shop_id;
        $parames['filter']['approve_status'] = 'onsale';
        $parames['order_by'] = ['by' => 'sold_quantity', 'sort' => 'desc'];
        $itemList = app::get('topwap')->rpcCall('item.mybelike.list',$parames);
        $shop_infos = app::get('topwap')->rpcCall('shop.list.all.get');

        foreach($itemList as &$items)
        {
            if($shop_infos[$items['shop_id']]['shop_mold'] == 'tv')
            {
                $items['mold_class'] = 'icon_small_tv';
            }
            else if($shop_infos[$items['shop_id']]['shop_mold'] == 'broadcast')
            {
                $items['mold_class'] = 'icon_fm101';
            }
            else
            {
                $items['mold_class'] = 'icon_other_tv';
            }
            $items['shop_mold'] = $shop_infos[$items['shop_id']]['shop_mold'];
            $items['shop_name'] = $shop_infos[$items['shop_id']]['shop_name'];
        }
        /*add_20170926_by_fanglongji_end*/
        $pagedata['hongbao_fee'] = $hongbaoMoney;
        if( $tradeTotalPayment ==  $hongbaoMoney )
        {
            $result['status'] = 'succ';
        }

        $trades = $result['trade_own_money'];
        $result['num'] = count($trades);
        $pagedata['msg'] = $msg;
        $pagedata['payment'] = $result;
        /*add_20170926_by_fanglongji_start*/
        $pagedata['item_list'] = $itemList;
        $pagedata['back_url'] = url::action('topwap_ctl_member_trade@tradeList');
        $pagedata['image_default_id'] = $this->__setting();
        /*add_20170926_by_fanglongji_end*/
        $pagedata['marketingSetting'] = $this->marketingSetting;
        $pagedata['scratchcardInfo'] = $this->scratchcardInfo['scratchcard'];

        //总后台的广告设置（营销=》广告管理）
        $pagedata['pay_finish_banner']     = app::get('syspromotion')->getConf('advert.payfinish.banner');
        $pagedata['pay_finish_banner_url'] = app::get('syspromotion')->getConf('advert.payfinish.banner.url');
        $advertModel = app::get('sysshop')->model('shop_advert');
        $advert_info = $advertModel->getRow('*',['shop_id' => $auth_shop_id]);
        $pagedata['auth_shop_id'] = $auth_shop_id;
        if($advert_info['is_open'] == 'on')
        {
            $pagedata['pay_finish_banner'] = $advert_info['pay_finish_banner'];
            $pagedata['pay_finish_banner_url'] = $advert_info['pay_finish_banner_url'];
        }
		/*add_2019/8/2_by_wanghaichao_start*/
		$wx_grant_url=$this->getNotifyUrl($tid,$tradeTotalPayment,$pay_time);
		$pagedata['this_shop_id']=$this_shop_id;
		$pagedata['wx_grant_url']=$wx_grant_url;
		//print_r($wx_grant_url);die();
		/*add_2019/8/2_by_wanghaichao_end*/
        return $this->page('topwap/payment/finish.html', $pagedata);
    }
	
	/**
	* 获取一次性订阅消息点击链接
	* author by wanghaichao
	* date 2019/8/2
	*/
	public function getNotifyUrl($tid,$tradeTotalPayment,$pay_time){
		//$tid=2475624000020090;
		//$pay_time='1564731052';
		//$tradeTotalPayment='20.32';
		$voucher=app::get('systrade')->model('voucher')->getList('voucher_code',array('tid'=>$tid));
		if($voucher){
			foreach($voucher as $k=>$v){
				$voucher_code.=$v['voucher_code'].',';
			}
			$voucher_code=substr($voucher_code,0,-1);
		}else{
			$voucher_code='';
		}
		//echo "<pre>";print_r(substr($voucher_code,0,-1));die();
		$data['voucher_code']=$voucher_code;
		$data['tid']=$tid;
		$data['payment']=$tradeTotalPayment;
		$data['pay_time']=$pay_time;
		$appId = app::get('site')->getConf('site.appId');
		$redirect_url=urlencode(url::action('topwap_ctl_mall@notify',$data));
		$scene=rand(1,10000);
		//$wx_grant_url="https://mp.weixin.qq.com/mp/subscribemsg?action=get_confirm&appid={$appId}&scene={$scene}&template_id=EhKzn7g7zFuc1MYaDxDo5jDNNV1OKlDyGLjzNnxKpeE&redirect_url={$redirect_url}#wechat_redirect";
		$wx_grant_url="https://mp.weixin.qq.com/mp/subscribemsg?action=get_confirm&appid={$appId}&scene={$scene}&template_id=RI7ykqWbQh9523sX1IobfXz7RfVJx358V15b-VeIDd4&redirect_url={$redirect_url}#wechat_redirect";
		return $wx_grant_url;
	}

    /*
     * 支付核销的无偿券抢购完成界面
     * 2018-02-23
     * by fanglongji
     */
    public function freeTicketsFinish()
    {
        $this->setLayoutFlag('order_detail');
        $postdata = input::get();
        $itemList = [];
        try
        {
            $parames['limit_num'] = 10;
            $parames['fields'] = "SI.item_id, image_default_id, title, price, sold_quantity,SI.shop_id";
            $parames['filter']['shop_id'] = $postdata['shop_id'];
            $parames['filter']['approve_status'] = 'onsale';
            $parames['order_by'] = ['by' => 'sold_quantity', 'sort' => 'desc'];
            $itemList = app::get('topwap')->rpcCall('item.mybelike.list',$parames);
            $shop_infos = app::get('topwap')->rpcCall('shop.list.all.get');

            foreach($itemList as &$items)
            {
                if($shop_infos[$items['shop_id']]['shop_mold'] == 'tv')
                {
                    $items['mold_class'] = 'icon_small_tv';
                }
                else if($shop_infos[$items['shop_id']]['shop_mold'] == 'broadcast')
                {
                    $items['mold_class'] = 'icon_fm101';
                }
                else
                {
                    $items['mold_class'] = 'icon_other_tv';
                }
                $items['shop_mold'] = $shop_infos[$items['shop_id']]['shop_mold'];
                $items['shop_name'] = $shop_infos[$items['shop_id']]['shop_name'];
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
        }

        $pagedata['msg'] = $msg;
        $pagedata['item_list'] = $itemList;
        $pagedata['back_url'] = url::action('topwap_ctl_member_agentvocher@index');
        $pagedata['image_default_id'] = $this->__setting();

        return $this->page('topwap/payment/freeTicketsFinish.html', $pagedata);
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

    //生成刮刮卡奖品
    public function getPrize(){
        $postdata = input::get();
        $params = [
            'payment_id' => $postdata['paymentid'],
            'fields' => 'payment_id,payed_time',
        ];
        $paymentInfo = app::get('topwap')->rpcCall('payment.bill.get',$params,'buyer');
        try
        {
            if(!$paymentInfo)
            {
                throw new \LogicException(app::get('topwap')->_('支付单不存在!'));
            }

            $apiParams = [
                'user_id' => userAuth::id(),
                'scratchcard_id' => $postdata['scratchcard_id'],
                'rel_paymentid' => $paymentInfo['payment_id'],
            ];
            $data = app::get('topwap')->rpcCall('promotion.scratchcard.receive',$apiParams);
            $prizeInfo = $data['scratchcard_result'];
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return response::json(['error'=>true, 'msg'=>$msg]);
        }

        return response::json(['success'=>true, 'prizeInfo'=>$prizeInfo]);
    }

    //发放刮刮卡奖品
    public function issue(){
        $scratchcard_result_id = input::get('resultid');
        try
        {
            app::get('topwap')->rpcCall('promotion.scratchcard.exchange',['scratchcard_result_id'=>$scratchcard_result_id]);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }

        return $this->splash('success',null,$msg ,true);
    }

    private function __setting()
    {
        $setting = kernel::single('image_data_image')->getImageSetting('item');
        return $setting;
    }

    /**
     * @param $shop_id
     * @param $keep_way 仅保留支付方式字段
     * @return mixed
     * 获取可用支付方式
     */
    private function __getPaymentWay($shop_id, $platform, $keep_way = null)
    {
        //获取可用的支付方式列表
        $payType['platform'] = $platform;
        $payments = app::get('topwap')->rpcCall('payment.get.list',$payType,'buyer');
        $payments = $this->paymentsSort($payments,'app_order_by');

        $shop['shop_id'] = $shop_id;
        $shop_info = app::get('ectools')->rpcCall('shop.get', $shop);
        $shop_payment = unserialize($shop_info['payment']);

        foreach($payments as $paymentKey => $payment)
        {
            // 平台是否授权and店铺是否开启
            if($shop_info['mer_collection'] == 'on' && !$shop_payment[$payment['app_id']]['open'])
            {
                unset($payments[$paymentKey]);
                continue;
            }

            //如果传入了仅保留支付方式，则其余方式全部删除
            if(!is_null($keep_way)  && !in_array($payment['app_id'], [$keep_way]))
            {
                unset($payments[$paymentKey]);
                continue;
            }

            if($payment['app_id'] == 'umspaypub')
            {
                unset($payments[$paymentKey]);
                continue;
            }

            if(in_array($payment['app_id'], ['wxservicepayapi','wxpayjsapi','wxserviceh5pay','wxh5pay']))
            {
                if($shop_info['mer_collection'] != 'on')
                {
                    if(in_array($payment['app_id'], ['wxservicepayapi', 'wxserviceh5pay']))
                    {
                        unset($payments[$paymentKey]);
                        continue;
                    }
                }
                //判断如果是微信浏览器，则删除wxserviceh5pay，wxh5pay
                if(kernel::single('topwap_wechat_wechat')->from_weixin())
                {
                    if(in_array($payment['app_id'], ['wxserviceh5pay', 'wxh5pay']))
                    {
                        unset($payments[$paymentKey]);
                        continue;
                    }
                }
                else
                {
                    if(in_array($payment['app_id'], ['wxservicepayapi', 'wxpayjsapi']))
                    {
                        unset($payments[$paymentKey]);
                        continue;
                    }
                }
            }
        }
        return $payments;
    }
}

