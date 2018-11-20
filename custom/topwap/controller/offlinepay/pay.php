<?php
/**
 * Created by PhpStorm. 线下收款控制器
 * User: fanglongji
 * Date: 2018/1/12
 * Time: 10:06
 */

class topwap_ctl_offlinepay_pay extends topwap_controller
{
    protected $payment_icon = array(
        'zxalipay' => 'bbc-icon-zhifubao pay-style-zhifubao',
        'zxwxpubpay' => 'bbc-icon-weixin pay-style-weixin',
        'wapupacp' => 'bbc-icon-unipay pay-style-unipay',
        'malipay' => 'bbc-icon-zhifubao pay-style-zhifubao',
        'wxpayjsapi' => 'bbc-icon-weixin pay-style-weixin',
        'umspaypub' => 'bbc-icon-weixin pay-style-weixin',
        'wxservicepayapi' => 'bbc-icon-weixin pay-style-weixin'
    );

    public function __construct($app)
    {
        parent::__construct($app);
        // 检测是否登录
    }

    /**
     * 线下扫码跳转处理
    */
    public function qrcodeRedict()
    {
        $params = input::get();
        $agent_shop_id = $params['agent_shop_id'];

        //二维码的url示例：http://qtv.tvplaza.cn/wap/qrcode_handle.html?agent_shop_id=1
        $qrcode_url = url::to(request::server('REQUEST_URI'));
        $_SESSION['account']['member']['qrcode_url'] = $qrcode_url.'&from_voucher=1';
        $from_voucher = $params['from_voucher'] ? 1 : 0;
        $this->setLayoutFlag('default');
        try
        {
            $agent_shop_info = app::get('syssupplier')->rpcCall('supplier.agent.shop.get', ['agent_shop_id' => $agent_shop_id, 'fields' => '*']);
            if(!$agent_shop_info)
            {
                $pagedata['error'] = "店铺信息不存在或者不可用";
                return $this->page('topwap/offlinepay/error.html', $pagedata);
            }
            $this->offline_payment_validate($agent_shop_info['shop_id'],$agent_shop_info['supplier_id']);
            $shop_filter['fields'] = 'shop_logo';
            $shop_filter['shop_id'] = $agent_shop_info['shop_id'];
            $shop_info = app::get('topwap')->rpcCall('shop.get',$shop_filter);
            $pagedata['shop_info'] = $shop_info;

            $pagedata['agent_name'] = $agent_shop_info['name'];

            $agent_voucher_info = $this->getVoucherList($agent_shop_id);
            $voucher_list = $agent_voucher_info['list'];
            $voucher_list = $this->array_group_key($voucher_list, 'item_id');

            $filter['confirm_type']  = 1;
            $filter['approve_status']  = 'onsale';
            $filter['orderBy']       = 'created_time DESC';
            $filter['use_platform']  = '0,2';
            $filter['disabled']      = 0;
            $filter['agent_shop_id'] = ','.$agent_shop_id.',';
            $filter['start_time']    = time();
            $filter['end_time']      = time();
            $filter['fields']    = "item_id";
            $activity_count = app::get('topwap')->rpcCall('item.search',$filter)['total_found'];
            $pagedata['voucher_list']   = $voucher_list;
            $pagedata['voucher_count']  = $agent_voucher_info['count'];
            $pagedata['activity_count'] = $activity_count;
            $pagedata['agent_shop_id']  = $agent_shop_id;
            $pagedata['shop_id']        = $agent_shop_info['shop_id'];

            $all_hold_filter['disabled'] = 0;
            $all_hold_filter['agent_shop_id'] = $agent_shop_id;
            $all_hold_info = app::get('topwap')->rpcCall('supplier.agent.activity.list', $all_hold_filter)['data'][0];
            if($all_hold_info['disabled'] == 1)
            {
                $all_hold_info = [];
            }
            $pagedata['all_hold_info'] = $all_hold_info;
            $pagedata['from_voucher']  = $from_voucher;
        }
        catch(Exception $e)
        {
            $pagedata['error'] = $e->getMessage();
            return $this->page('topwap/offlinepay/error.html', $pagedata);
        }

        return $this->page('topwap/offlinepay/offline_pay.html', $pagedata);
    }
    /**
     * 获取全场优惠后的金额
     */
    public function ajaxGetAllHoldPrice()
    {
        $params = input::get();
        $original_pay_price = floatval($params['original_pay_price']);
        $active_id = $params['active_id'];
        try
        {
            $result = $this->ajaxComputeAllHoldPrice($original_pay_price, $active_id);
            if($result['error_msg'])
            {
                return $this->splash('error',null, $result['error_msg'], true);
            }
            $result['success'] = true;
            return response::json($result);exit;
        }
        catch(Exception $e)
        {
            return $this->splash('error',null, $e->getMessage(), true);
        }
    }
    /**
     *
     * 计算全场优惠后的金额
     */
    public function ajaxComputeAllHoldPrice($original_pay_price, $active_id, $agent_shop_id=null)
    {
        $discount_amount = 0.00;

        $all_hold_filter['agent_activity_id'] = $active_id;
        $all_hold_filter['end_time|bthan'] = time();
        $all_hold_filter['start_time|lthan'] = time();
        $activity_info = app::get('topwap')->rpcCall('supplier.agent.activity.get', $all_hold_filter);
        if(!$activity_info || $activity_info['deleted'] == 1 || $activity_info['disabled'] == 1)
        {
            $data['need_pay_price'] = $original_pay_price;
            $data['discount_amount'] = 0.00;
            $result['message'] = '该活动已失效';
            return ['data' => $data, 'error_msg' => '该活动已失效'];
        }
        if(!is_null($agent_shop_id) && $activity_info['agent_shop_id'] !== intval($agent_shop_id))
        {
            $data['need_pay_price'] = $original_pay_price;
            $data['discount_amount'] = 0.00;
            return ['data' => $data, 'error_msg' => '全场优惠活动不属于该线下店'.intval($agent_shop_id)];
        }
        if(in_array($activity_info['activity_type'], ['ALL_DISCOUNT']))
        {
            if((int)$activity_info['value_min'])
            {
                if($original_pay_price <  floatval($activity_info['value_min']))
                {
                    $data['need_pay_price'] = $original_pay_price;
                    $data['discount_amount'] = 0.00;
                    $result['message'] = '未达到最低消费金额';
                    return ['data' => $data, 'error_msg' => ''];
                }
            }
            $discount_amount = $original_pay_price * (10-floatval($activity_info['activity_value']))/10;
            $discount_amount = round($discount_amount,2);
            if((int)$activity_info['value_max'] && $discount_amount > $activity_info['value_max'])
            {
                $discount_amount = $activity_info['value_max'];
            }
        }
        $need_pay_price = bcsub($original_pay_price,$discount_amount,2);
        $data['need_pay_price'] = $need_pay_price;
        $data['discount_amount'] = $discount_amount;
        return ['data' => $data, 'error_msg' => ''];
    }

    /*
     * 得到该用户可用的优惠券
     */
    public function getVoucherList($agent_shop_id)
    {
        $user_id = userAuth::id();
        $agent_shop_id = ','.$agent_shop_id.',';
        $voucher_list = app::get('topwap')->rpcCall('supplier.agentvoucher.get.list', ['agent_shop_id' => $agent_shop_id, 'user_id' => $user_id, 'status' => 'WAIT', 'start_time' => time(), 'end_time' => time()]);
        return $voucher_list;
    }
    /*
     * 得到优惠后的最终价格
     */
    public function ajaxGetNeedPayPrice()
    {
        $voucher_ids = input::get('voucher_ids');
        $use_count   = input::get('use_count');
        $user_id     = userAuth::id();
        $original_pay_price   = input::get('original_pay_price');
        if(!$original_pay_price)
        {
            return $this->splash('error',null, '支付金额不能为0', true);
        }
        if(!$voucher_ids || !$use_count)
        {
            $result['success'] = true;
            $result['data']['need_pay_price'] = $original_pay_price;
            $result['data']['discount_amount'] = 0.00;
            return response::json($result);exit;
        }
        try
        {
            $result = $this->ajaxComputerVoucherPrice($voucher_ids, $original_pay_price, $use_count, $user_id);

            if($result['error_msg'])
            {
                return $this->splash('error',null, $result['error_msg'], true);
            }
            $result['success'] = true;
            return response::json($result);exit;
        }
        catch(Exception $e)
        {
            return $this->splash('error',null, $e->getMessage(), true);
        }
    }
    /*
     * 支付页面
     */
    public function index($filter=null)
    {
        $this->setLayoutFlag('order_detail');
        if(!$filter)
        {
            $filter = input::get();
        }

        try
        {
            $filter['fields'] = "*";
            $paymentBill = app::get('topwap')->rpcCall('payment.offline.bill.get',$filter);

            $agent_shop_info = app::get('syssupplier')->rpcCall('supplier.agent.shop.get', ['agent_shop_id' => $paymentBill['agent_shop_id'], 'fields' => '*']);
            $bank_payment = $agent_shop_info['payment'];

            $tid = $paymentBill['tid'];
            $objMdlOfflineTrade = app::get('systrade')->model('offline_trade');
            $offline_trades = $objMdlOfflineTrade->getList('tid,payment,status,created_time',['tid' => $tid]);

            $newMoney = 0;
            foreach($offline_trades as $key=>$val)
            {
                $newMoney = ecmath::number_plus(array($newMoney, $val['payment']));
            }
            $result = array(
                'money' => $newMoney,
                'cur_money' => $newMoney,
                'payment_id' => $filter['payment_id'],
            );

            if($newMoney != $paymentBill['cur_money'])
            {
                app::get('topwap')->rpcCall('payment.offline.money.update',$result);
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            $url = url::action('topwap_ctl_member_offlinepay@payInfo');
            return $this->splash('error',$url, $msg,true);
        }
        //获取可用的支付方式列表
        $payType['platform'] = 'is_offline';
        $payments = app::get('topwap')->rpcCall('payment.get.list',$payType,'buyer');
        $payments = $this->paymentsSort($payments,'app_order_by');

        foreach($bank_payment as $bank=>$b_p)
        {
            if($b_p['is_open'] === 'on')
            {
                $open_bank = $bank;
            }
        }
        $bank_payment_array = [
            'ns_bank' => ['is_wechat'=>['app_id' => 'nswxpay'],'is_alipay'=>['app_id' => 'nsmalipay']],
            'qd_bank' => ['is_wechat'=>['app_display_name' => '微信支付','app_id' => 'qdbankpay','msg_type' => 'WXPay.jsPay'],'is_alipay'=>['app_display_name' => '支付宝支付','app_id' => 'qdbankpay','msg_type' => 'trade.jsPay']],
            'pa_bank' => ['is_wechat'=>['app_id' => 'pawxpay'],'is_alipay'=>['app_id' => 'pamalipay']]
        ];
        if(!$open_bank)
        {
            $show_payment_array = [];
        }
        else
        {
            $show_payment_array = $bank_payment_array[$open_bank];
        }
        $is_alipay = kernel::single('topwap_alipay_alipay')->from_alipay();
        $is_wechat = kernel::single('topwap_wechat_wechat')->from_weixin();
        if($is_alipay)
        {
            unset($show_payment_array['is_wechat']);
            $show_payment_array = $show_payment_array['is_alipay'];
        }
        if($is_wechat)
        {
            unset($show_payment_array['is_alipay']);
            $show_payment_array = $show_payment_array['is_wechat'];
        }
        foreach($payments as $paymentKey => &$payment)
        {
            if($payment['app_id'] != $show_payment_array['app_id'])
            {
                unset($payments[$paymentKey]);
            }
            if($show_payment_array['app_display_name'])
            {
                $payment['app_display_name'] = $show_payment_array['app_display_name'];
            }
            if($show_payment_array['msg_type'])
            {
                $payment['msg_type'] = $show_payment_array['msg_type'];
            }
            $payment['open_bank'] = $open_bank;
        }
        $intervalTime = app::get('sysconf')->getConf('trade.offline.cancel.spacing.time');
        $intervalTime = $intervalTime*3600;
        $pagedata['surplus_time'] = ($offline_trades[0]['created_time']+$intervalTime)-time();
        $pagedata['tids']         = $tid;
        $pagedata['payment_id']   = $filter['payment_id'];
        $pagedata['payments']     = $payments;
        $pagedata['money']        = $newMoney;
        $pagedata['company_name'] = $filter['company_name'];
        $pagedata['title']        = app::get('topwap')->_('支付订单');
        $pagedata['back_url']     = url::action('topwap_ctl_member_offlinepay@payInfo');
        $pagedata['payment_icon'] = $this->payment_icon;

        return $this->page('topwap/offlinepay/offline_index.html', $pagedata);
    }

    /**
     * 小程序-去支付
     * @param null $filter
     * @return base_view_object_interface|string
     */
    public function indexMini($filter = null)
    {
        $this->setLayoutFlag('order_detail');
        if(!$filter)
        {
            $filter = input::get();
        }

        try
        {
            $filter['fields'] = "*";
            $paymentBill = app::get('topwap')->rpcCall('payment.offline.bill.get',$filter);

            $agent_shop_info = app::get('syssupplier')->rpcCall('supplier.agent.shop.get', ['agent_shop_id' => $paymentBill['agent_shop_id'], 'fields' => '*']);
            $bank_payment = $agent_shop_info['payment'];

            $tid = $paymentBill['tid'];
            $objMdlOfflineTrade = app::get('systrade')->model('offline_trade');
            $offline_trades = $objMdlOfflineTrade->getList('tid,payment,status,created_time',['tid' => $tid]);

            $newMoney = 0;
            foreach($offline_trades as $key=>$val)
            {
                $newMoney = ecmath::number_plus(array($newMoney, $val['payment']));
            }
            $result = array(
                'money' => $newMoney,
                'cur_money' => $newMoney,
                'payment_id' => $filter['payment_id'],
            );

            if($newMoney != $paymentBill['cur_money'])
            {
                app::get('topwap')->rpcCall('payment.offline.money.update',$result);
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            $url = url::action('topwap_ctl_member_offlinepay@payInfo');
            return $this->splash('error',$url, $msg,true);
        }
        //获取可用的支付方式列表
        $payType['platform'] = 'is_offline';
        $payments = app::get('topwap')->rpcCall('payment.get.list',$payType,'buyer');
        $payments = $this->paymentsSort($payments,'app_order_by');

        foreach($bank_payment as $bank=>$b_p)
        {
            if($b_p['is_open'] === 'on')
            {
                $open_bank = $bank;
            }
        }
        $bank_payment_array = [
            'ns_bank' => ['is_wechat'=>'nswxpay','is_alipay'=>'nsmalipay','wapupacp'],
            'js_bank' => ['is_wechat'=>'jswxpay','is_alipay'=>'jsmalipay','wapupacp'],
            'pa_bank' => ['is_wechat'=>'pawxpay','is_alipay'=>'pamalipay','wapupacp']
        ];
        if(!$open_bank)
        {
            $show_payment_array = ['wapupacp'];
        }
        else
        {
            $show_payment_array = $bank_payment_array[$open_bank];
        }

        $is_alipay = kernel::single('topwap_alipay_alipay')->from_alipay();
        $is_wechat = kernel::single('topwap_wechat_wechat')->from_weixin();
        if($is_alipay)
        {
            unset($show_payment_array['is_wechat']);
        }
        if($is_wechat)
        {
            unset($show_payment_array['is_alipay']);
        }

        foreach($payments as $paymentKey => $payment)
        {
            if(!in_array($payment['app_id'],$show_payment_array))
            {
                unset($payments[$paymentKey]);
            }
        }
        $intervalTime = app::get('sysconf')->getConf('trade.offline.cancel.spacing.time');
        $intervalTime = $intervalTime*3600;
        $pagedata['surplus_time'] = ($offline_trades[0]['created_time']+$intervalTime)-time();
        $pagedata['tids']         = $tid;
        $pagedata['payment_id']   = $filter['payment_id'];
        $pagedata['payments']     = $payments;
        $pagedata['money']        = $newMoney;
        $pagedata['company_name'] = $filter['company_name'];
        $pagedata['title']        = app::get('topwap')->_('支付订单');
        $pagedata['back_url']     = url::action('topwap_ctl_member_offlinepay@payInfo');
        $pagedata['payment_icon'] = $this->payment_icon;
        return $this->page('topwap/offlinepay/offline_index.html', $pagedata);
    }
    /*
     * 创建订单
     */
    public function createOfflineTrade()
    {
        $post_data          = input::get();
        $user_id            = userAuth::id();
        $shop_id            = input::get('shop_id');
        $agent_shop_id      = input::get('agent_shop_id');
        $company_name       = input::get('company_name');
        $active_id          = input::get('active_id');
        $voucher_ids        = !empty($post_data['voucher_ids']) ? explode(',', $post_data['voucher_ids']) : [];
        $use_count          = intval($post_data['use_count']);
        $need_pay_price     = floatval($post_data['need_pay_price']);
        $original_pay_price = floatval($post_data['original_pay_price']);
        if(!$original_pay_price)
        {
            return $this->splash('error',null, '您输入的消费金额有误!', true);
        }
        try
        {
            if($active_id && !$use_count && empty($voucher_ids))
            {
                $result = $this->ajaxComputeAllHoldPrice($original_pay_price, $active_id, $agent_shop_id);
            }
            else
            {
                $result = $this->ajaxComputerVoucherPrice($voucher_ids, $original_pay_price, $use_count, $user_id, $agent_shop_id);
                $active_id = 0;
            }

            if($result['error_msg'])
            {
                return $this->splash('error',null, $result['error_msg'], true);
            }
            if($result['data']['need_pay_price'] != round($need_pay_price,2))
            {
                throw new Exception('计算支付金额与实际支付金额不匹配');
            }
            $agent_shop_info = app::get('syssupplier')->rpcCall('supplier.agent.shop.get', ['agent_shop_id' => $agent_shop_id, 'fields' => '*']);
            $this->offline_payment_validate($shop_id, $agent_shop_info['supplier_id']);
            $create_filter['user_id']        = $user_id;
            $create_filter['shop_id']        = $shop_id;
            $create_filter['supplier_id']    = $agent_shop_info['supplier_id'];
            $create_filter['agent_shop_id']  = $agent_shop_id;
            $create_filter['voucher_ids']    = $voucher_ids;
            $create_filter['all_hold_id']    = $active_id;
            $create_filter['payment']        = $need_pay_price;
            $create_filter['total_fee']      = $original_pay_price;
            $create_filter['voucher_fee']    = $result['data']['discount_amount'];
            $create_filter['user_name']      = userAuth::getLoginName();
            $createFlag = app::get('topwap')->rpcCall('trade.offline.create',$create_filter);
            if($need_pay_price == 0)
            {
                app::get('ectools')->rpcCall('trade.offline.pay.finish', array('tid' => $createFlag, 'payment' => 0));
                $free_single_url =  url::action('topwap_ctl_offlinepay_pay@payFinish',array('shop_id'=>$shop_id,'payment_type'=>'free_single'));
                return $this->splash('success',$free_single_url,'操作完成',true);
            }
            $params['tids']    = $createFlag;
            $params['user_id'] = $user_id;
            $params['shop_id'] = $shop_id;
            $params['money']   = $need_pay_price;
            $params['company_name']  = $company_name;
            $params['agent_shop_id'] = $agent_shop_id;
            $params['user_name'] = userAuth::getLoginName();
            $paymentId = kernel::single('topwap_offlinepayment')->getPaymentId($params);

            $objLibMath = kernel::single('ectools_math');

            $sub_price      = $objLibMath->number_multiple([$need_pay_price, (6/1000)]);
            $need_sub_price = $objLibMath->number_minus([$need_pay_price, $sub_price]);
            $shop_fee       = $objLibMath->number_multiple([$need_sub_price, ($agent_shop_info['sub_ratio']/100)]);
            //生成清算单
            $settle_info['shop_id']                    =   $shop_id;
            $settle_info['supplier_id']                =   $agent_shop_info['supplier_id'];
            $settle_info['store_id']                   =   $agent_shop_id;
            $settle_info['offline_tid']                =   $createFlag;
            $settle_info['payment_account']            =   0;
            $settle_info['total_fee']                  =   $original_pay_price;
            $settle_info['payment']                    =   $need_pay_price;
            $settle_info['discount_fee']               =   ($original_pay_price-$need_pay_price);
            $settle_info['commission_rate']            =   $agent_shop_info['sub_ratio'];
            $settle_info['shop_fee']                   =   $shop_fee;
            $settle_info['supplier_fee']               =   ($need_sub_price - $shop_fee);
            $settle_info['service_charge']             =   0;
            $settle_info['platform_service_charge']    =   0;
            $settle_info['cross_service_charge']       =   0;
            $settle_info['deal_status']                =   1;
            $settle_info['settle_status']              =   'SETTLING';
            $offline_settle_id = kernel::single('sysclearing_offlinesettlement')->generate($settle_info);

            $redirect_url = url::action('topwap_ctl_offlinepay_pay@index',['payment_id' => $paymentId, 'company_name' => $company_name]);
            return $this->splash('success',$redirect_url,'线下订单创建成功',true);
        }
        catch(Exception $e)
        {
            return $this->splash('error',null, $e->getMessage(), true);
        }
    }
    /*
     * 分账系统的支付
     */
    public function doPay()
    {
        $postdata = input::get();
        $payment  = $postdata['payment'];
        $user_id  = userAuth::id();
        try
        {
            $objMdlPayments = app::get('ectools')->model('offline_payments');
            $paymentBill = $objMdlPayments->getRow('agent_shop_id',array('payment_id'=>$payment['payment_id']));
            $agent_shop_info = app::get('topshop')->rpcCall('supplier.agent.shop.get', ['agent_shop_id' => $paymentBill['agent_shop_id']]);
            $this->offline_payment_validate($agent_shop_info['shop_id'],$agent_shop_info['supplier_id']);
            $payment['user_id'] = $user_id;
            $payment['platform'] = "wap";
            app::get('topwap')->rpcCall('payment.offline.trade.pay',$payment);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            $url = url::action('topwap_ctl_member_offlinepay@payInfo');
            echo '<meta charset="utf-8"><script>alert("'.$msg.'");location.href="'.$url.'";</script>';
            exit();
        }

        $url = url::action('topwap_ctl_offlinepay_pay@payFinish',array('payment_id'=>$payment['payment_id']));
        return $this->splash('success', $url, $msg, true);
    }

    // 创建支付
    public function createPay()
    {
        $post_data = input::get();

        $trade_filter['user_id'] = userAuth::id();
        $trade_filter['tid'] = $post_data['tid'];
        try
        {
            $tradeModel = app::get('systrade')->model('offline_trade');
            $trade_info = $tradeModel->getRow('*', $trade_filter);
            $agent_shop_info = app::get('syssupplier')->rpcCall('supplier.agent.shop.get', ['agent_shop_id' => $trade_info['agent_shop_id'], 'fields' => '*']);
            $this->offline_payment_validate($agent_shop_info['shop_id'],$agent_shop_info['supplier_id']);
            $filter['user_id']       = userAuth::id();
            $filter['user_name']     = userAuth::getLoginName();
            $filter['tids']          = $post_data['tid'];
            $filter['shop_id']       = $trade_info['shop_id'];
            $filter['money']         = $trade_info['payment'];
            $filter['agent_name']    = $agent_shop_info['name'];
            $filter['agent_shop_id'] = $trade_info['agent_shop_id'];

            $paymentId = kernel::single('topwap_offlinepayment')->getPaymentId($filter);
//            $objLibMath = kernel::single('ectools_math');
//            $sub_price      = $objLibMath->number_multiple([$trade_info['payment'], (6/1000)]);
//            $need_sub_price = $objLibMath->number_minus([$trade_info['payment'], $sub_price]);
//            $shop_fee   = $objLibMath->number_multiple([$need_sub_price, ($agent_shop_info['sub_ratio']/100)]);

//            //生成清算单
//            $settle_info['shop_id']                    =   $trade_info['shop_id'];
//            $settle_info['supplier_id']                =   $agent_shop_info['supplier_id'];
//            $settle_info['store_id']                   =   $trade_info['agent_shop_id'];
//            $settle_info['offline_tid']                =   $post_data['tid'];
//            $settle_info['payment_account']            =   0;
//            $settle_info['total_fee']                  =   $trade_info['total_fee'];
//            $settle_info['payment']                    =   $trade_info['payment'];
//            $settle_info['discount_fee']               =   $trade_info['voucher_fee'];
//            $settle_info['commission_rate']            =   $agent_shop_info['sub_ratio'];
//            $settle_info['shop_fee']                   =   $shop_fee;
//            $settle_info['supplier_fee']               =   ($need_sub_price - $shop_fee);
//            $settle_info['service_charge']             =   0;
//            $settle_info['platform_service_charge']    =   0;
//            $settle_info['cross_service_charge']       =   0;
//            $settle_info['deal_status']                =   1;
//            $settle_info['settle_status']              =   'SETTLING';
//            $offline_settle_id = kernel::single('sysclearing_offlinesettlement')->generate($settle_info);
        }
        catch ( Exception $e )
        {
            $msg = $e->getMessage();
            return $this->splash('error', null, $msg);
        }
        $redirect_url = url::action('topwap_ctl_offlinepay_pay@index',['payment_id' => $paymentId, 'company_name' => $agent_shop_info['name']]);
        return $this->splash('success', $redirect_url, $msg, true);
    }
    // 支付完成
    public function payFinish()
    {

        $this->setLayoutFlag('order_detail');
        $postdata = input::get();

        try
        {
            if(!$postdata['payment_type'])
            {
                $params['payment_id'] = $postdata['payment_id'];
                $params['fields'] = 'payment_id,tid,status,pay_app_id,pay_name,money,cur_money,payed_time,created_time,shop_id';
                $paymentBill = app::get('topwap')->rpcCall('payment.offline.bill.get',$params);
                $shop_id = $paymentBill['shop_id'];
            }
            elseif($postdata['payment_type'] && $postdata['payment_type'] == 'free_single')
            {
                $paymentBill['status'] = 'succ';
                $shop_id = $postdata['shop_id'];
            }
            //猜你喜欢
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
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
        }
        $pagedata['msg'] = $msg;
        $pagedata['payment'] = $paymentBill;
        $pagedata['back_url'] = url::action('topwap_ctl_member_offlinepay@payInfo');
        $pagedata['image_default_id'] = $this->__setting();
        $pagedata['item_list'] = $itemList;

        return $this->page('topwap/payment/offlinefinish.html', $pagedata);
    }
    /*
     * 计算优惠后的价格
     */
    protected function ajaxComputerVoucherPrice($voucher_ids, $original_pay_price, $use_count, $user_id, $agent_shop_id=null)
    {
        if(!$voucher_ids || !$use_count)
        {
            $data['need_pay_price'] = round($original_pay_price,2);
            $data['discount_amount'] = 0.00;
            return ['data' => $data, 'error_msg' => ''];
        }
        //券的类型：代金券（CASH_VOCHER)；满减券（REDUCE）；满折券（DISCOUNT）；
        $voucher_model = app::get('systrade')->model('agent_vocher');
        $voucher_info  = $voucher_model->getList('*', ['vocher_id|in' => $voucher_ids, 'user_id' => $user_id]);

        if(!$voucher_info)
        {
            $error_msg = '没有此优惠券的信息';
        }
        $voucher_count = count($voucher_info);
        $type_array = array_unique(array_column($voucher_info, 'agent_type'));
        if(!$error_msg && count($type_array) > 1)
        {
            $error_msg = '只能选择一种类别的优惠券';
        }
        if(!$error_msg && $type_array[0] === 'CASH_VOCHER')
        {
            $cate_array = array_unique(array_column($voucher_info, 'item_id'));
            if(count($cate_array) > 1)
            {
                $error_msg = '只能选择一种类别的代金券';
            }
        }

        if(!$error_msg)
        {
            $discount_amount = 0.00;
            foreach($voucher_info as $key=>$val)
            {
                $val_id = explode(',', trim($val['agent_shop_id'],','));
                if(!is_null($agent_shop_id) && !in_array($agent_shop_id, $val_id))
                {
                    $error_msg = '存在非该店的优惠券！';
                    break;
                }
                if($val['status'] !== 'WAIT')
                {
                    $error_msg = '包含已使用过的优惠券，请重新选择';
                    break;
                }
                if($val['start_time'] > time() || $val['end_time'] < time())
                {
                    $error_msg = '优惠券不在使用期限内';
                    break;
                }
                if($use_count > $voucher_count)
                {
                    $error_msg = '您只拥有'.$voucher_count.'张优惠券';
                    break;
                }
                if((int)$val['agent_use_limit']  && $use_count > $val['agent_use_limit'])
                {
                    $error_msg = '使用数量超过限制';
                    break;
                }
                if((int)$val['min_consum'] && $original_pay_price < $val['min_consum'])
                {
                    $error_msg = '未达到消费下限';
                    break;
                }
                if(in_array($val['agent_type'], ['DISCOUNT']))
                {
                    $discount_amount = $original_pay_price * (10-$val['deduct_price'])/10;
                }
                if(in_array($val['agent_type'],['REDUCE']))
                {
                    $discount_amount += $val['deduct_price'];
                }
                if(in_array($val['agent_type'],['CASH_VOCHER']))
                {
                    $discount_amount += $val['deduct_price'];
                }
                if((int)$val['max_deduct_price']  && $discount_amount > $val['max_deduct_price'])
                {
                    $discount_amount = $val['max_deduct_price'];
                }
            }
            $discount_amount = round($discount_amount,2);
            $need_pay_price = bcsub($original_pay_price,$discount_amount,2);
            if($need_pay_price <= 0)
            {
                $need_pay_price = 0;
                $discount_amount = $original_pay_price;
            }
            $data['need_pay_price'] = $need_pay_price;
            $data['discount_amount'] = $discount_amount;
            return ['data' => $data, 'error_msg' => $error_msg];
        }
    }

    //支付方式排序
    protected function paymentsSort($payments,$orderBy,$sort_order=SORT_ASC)
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
    /*
     * 获取商家正在发放的线下消费卡券
     */
    public function getOfflinePayCoupon()
    {
         $this->setLayoutFlag('offline_pay');
         $pagedata['agent_shop_id'] = $params['agent_shop_id'] = input::get('agent_shop_id');
         $params['page_no']   = 1;
         $return_data = $this->getThisAgentShopCoupon($params);
         $pagedata['item_info'] = $return_data['list'];
         $pagedata['count']     = $return_data['count'];

         $pagedata['total_page']= ceil($return_data['count']/5);

         return $this->page('topwap/offlinepay/offline_coupon.html', $pagedata);
    }
    /**
     * 获取该线下店的券
     */
    public function getThisAgentShopCoupon($params)
    {
        $filter['confirm_type']  = 1;
        $filter['approve_status']  = 'onsale';
        $filter['orderBy']       = 'created_time DESC';
        $filter['use_platform']  = '0,2';
        $filter['disabled']      = 0;
        $filter['agent_shop_id'] = ','.$params['agent_shop_id'].',';
        $filter['page_size']     = 5;
        $filter['start_time']    = time();
        $filter['end_time']      = time();
        $filter['page_no']       = $params['page_no'];
        $filter['fields']    = "item_id,title,agent_type,min_consum,max_deduct_price,deduct_price,agent_price,image_default_id,store.store,store.freez";

        $item_info = app::get('topwap')->rpcCall('item.search',$filter);

        $show_item_array = [];
        $can_be_get = [];
        $cannot_be_get = [];
        foreach($item_info['list'] as $item)
        {
            if($item['agent_type'] === 'CASH_VOCHER')
            {
                $item['show_title'] = floatval($item['deduct_price']).'元';
            }
            elseif($item['agent_type'] === 'DISCOUNT')
            {
                $item['show_title'] = floatval($item['deduct_price']).'折';
            }
            elseif($item['agent_type'] === 'REDUCE')
            {
                $item['show_title'] = floatval($item['deduct_price']).'元';
            }
            if((int)$item['min_consum'])
            {
                $item['min_desc']  = '最低消费：'.floatval($item['min_consum']).'元';
            }
            else
            {
                $item['min_desc'] = '最低消费：无限制';
            }
            if((int)$item['max_deduct_price'])
            {
                $item['max_desc'] = '最大抵扣：'.floatval($item['max_deduct_price']).'元';
            }
            else
            {
                $item['max_desc'] = '最大抵扣：无限制';
            }
            $real_store = $item['store'] - $item['freez'];
            if($real_store <= 10)
            {
                $item['re_percent'] = '仅剩余'.$real_store.'件';
            }
            else
            {
                $item['re_percent'] = '剩余'.round(($real_store/$item['store'])*100).'%';
            }
            $item['real_store'] = $real_store;
            if($real_store === 0)
            {
                $cannot_be_get[] = $item;
            }
            else
            {
                $can_be_get[] = $item;
            }
        }
        $show_item_array = array_merge($can_be_get,$cannot_be_get);
        $return_data['list'] = $show_item_array;
        $return_data['count'] = $item_info['total_found'];
        return $return_data;
    }
    /**
     * ajax获取领券中心的券
     */
    public function ajaxGetOfflineCoupon()
    {
        $postdata = input::get();
        $params['agent_shop_id'] = $postdata['agent_shop_id'];
        $params['page_size'] = 5;
        $params['page_no']   = $postdata['page_no'];

        $return_data = $this->getThisAgentShopCoupon($params);
        if($return_data)
        {
            $pagedata['item_info'] = $return_data['list'];
            $data['html'] = view::make('topwap/offlinepay/offline_coupon_item.html',$pagedata)->render();
            $data['success'] = true;
        }
        else
        {
            $data['html'] = view::make('topwap/offlinepay/offline_coupon_item.html',$pagedata)->render();
            $data['success'] = false;
        }
        return response::json($data);exit;
    }
    /**
     * 根据传入的数组和数组中值的键值，将对数组的键进行替换
     *
     * @param array $array
     * @param string $key
     */
    public function array_group_key($array, $key )
    {
        foreach( (array)$array as $value )
        {
            if( !empty($value[$key]) )
            {
                $k = $value[$key];
                if((int)$value['min_consum'])
                {
                    $value['min_desc']  = '最低消费：'.floatval($value['min_consum']).'元';
                }
                else
                {
                    $value['min_desc'] = '最低消费：无限制';
                }
                if((int)$value['max_deduct_price'])
                {
                    $value['max_desc'] = '最大优惠：'.floatval($value['max_deduct_price']).'元';
                }
                else
                {
                    $value['max_desc'] = '最大优惠：无限制';
                }
                $result[$k]['list'][] = $value;
                $result[$k]['count'] += 1;
                $result[$k]['deduct_price'] = $value['deduct_price'];
                $result[$k]['agent_type']   = $value['agent_type'];
                $result[$k]['item_image']   = $value['item_image'];
                $result[$k]['end_time']     = $value['end_time'];
                $result[$k]['min_desc']     = $value['min_desc'];
                $result[$k]['max_desc']     = $value['max_desc'];
                $result[$k]['title']        = $value['title'];
                $result[$k]['voucher_ids']  = implode(',',array_column($result[$k]['list'],'vocher_id'));
                $result[$k]['agent_use_limit'] = $value['agent_use_limit'];

                if($value['agent_type'] === 'CASH_VOCHER')
                {
                    $result[$k]['show_title'] = $value['title'].'（'.$result[$k]['count'].'张）';
                }
                elseif($value['agent_type'] === 'DISCOUNT')
                {
                    $result[$k]['show_title'] = $value['title'];
                }
                elseif($value['agent_type'] === 'REDUCE')
                {
                    $result[$k]['show_title'] = $value['title'];
                }

            }
        }
        return $result;
    }

    private function __setting()
    {
        $setting = kernel::single('image_data_image')->getImageSetting('item');
        return $setting;
    }

    protected function offline_payment_validate($shop_id,$supplier_id)
    {
        $shop_info = app::get('ectools')->rpcCall('shop.get', ['shop_id'=>$shop_id]);
        if($shop_info['offline'] == 'off')
        {
            throw new Exception('该店铺收款功能已关闭!');
        }
        $supplier_info = app::get('ectools')->rpcCall('supplier.shop.get', ['supplier_id'=>$supplier_id]);
        if($supplier_info['agent_sign'] == 0)
        {
            throw new Exception('该线下店供应商收款功能已关闭');
        }
    }
}