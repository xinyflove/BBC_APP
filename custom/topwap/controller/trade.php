<?php
class topwap_ctl_trade extends topwap_controller{
	var $noCache = true;

    public function __construct(&$app)
    {
        parent::__construct();
        theme::setNoindex();
        theme::setNoarchive();
        theme::setNofolow();
        theme::prependHeaders('<meta name="robots" content="noindex,noarchive,nofollow" />\n');
        $this->title=app::get('topwap')->_('订单中心');
        // 检测是否登录
        if( !userAuth::check() )
        {
            redirect::action('topwap_ctl_passport@goLogin')->send();exit;
        }
    }


	public function create()
	{
		$postData                = input::get();
        $postData['mode']        = $postData['mode'] ? $postData['mode'] :'cart';
        $postData['source_from'] = 'wap';
        $receive_data = $this->commonCreateData($postData);

        try
        {
            $createFlag = app::get('topwap')->rpcCall('trade.create',$receive_data);
        }
        catch(Exception $e)
        {
            return $this->splash('error',null,$e->getMessage(),true);
        }

        try
        {
            if($receive_data['payment_type'] == "online")
            {
                $params['tid'] = $createFlag;
                $params['user_id'] = userAuth::id();
                $params['user_name'] = userAuth::getLoginName();
                $paymentId = kernel::single('topwap_payment')->getPaymentId($params);
                $redirect_url = url::action('topwap_ctl_paycenter@index',array('payment_id'=>$paymentId,'merge'=>true));
            }
            else
            {
                $redirect_url = url::action('topwap_ctl_paycenter@index',array('tid' => implode(',',$createFlag)));
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            $url = url::action('topwap_ctl_member_trade@tradeList');
            return $this->splash('error',$url,$msg,true);
        }
        return $this->splash('success',$redirect_url,'订单创建成功',true);
    }

    /**
     * @return mixed
     * 创建小程序订单
     */
    public function miniCreate()
    {
        $postData                = input::get();
        $postData['mark'] = json_decode($postData['mark'], true);
        $postData['shipping'] = json_decode($postData['shipping'], true);
        file_put_contents('minitest.txt',var_export($postData,1));
        // $postData['mode'] = 'mini';
        // $postData['identity_card_number'] = '370181198707093858';
        // $postData['addr_id'] = 1;
        // $postData['payment_type'] = 'online';
        // $postData['mark'] = [3 => '测试小程序'];
        // $postData['md5_cart_info'] = '44444';
        // $postData['shipping'] = [3=> ['shipping_type' =>'nonpost']];

        $postData['mode']        = $postData['mode'] ? $postData['mode'] :'mini';
        $postData['source_from'] = 'mini';
        $receive_data = $this->commonCreateData($postData);

        try
        {
            $createFlag = app::get('topwap')->rpcCall('trade.create',$receive_data);
        }
        catch(Exception $e)
        {
            $return_data['err_no'] = 0;
            $return_data['data'] = [];
            $return_data['message'] = $e->getMessage();
            return response::json($return_data);
        }

        try
        {
            $params['tid'] = $createFlag;
            $params['user_id'] = userAuth::id();
            $params['user_name'] = userAuth::getLoginName();
            $paymentId = kernel::single('topwap_payment')->getPaymentId($params);
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

    /**
     * @param $postData
     * @return mixed
     * 处理订单的共同是数据
     */
    public function commonCreateData($postData)
    {
        //配送方式
        foreach( $postData['shipping'] as $shopId=>$shipping )
        {
            $postData['shipping_type'][] = [
                'shop_id' => $shopId,
                'type'    => $shipping['shipping_type'],
                'ziti_id' => ($shipping['shipping_type'] == 'ziti') ? $postData['ziti'][$shopId]['ziti_addr'] : null,
            ];
        }
        unset($postData['shipping']);
        $postData['shipping_type'] = json_encode($postData['shipping_type']);

        //订单备注
        $markData = $postData['mark'];
        unset($postData['mark']);
        if( $markData )
        {
            foreach( $markData as $shopId=>$mark )
            {
                if( $mark )
                {
                    $postData['mark'][] = [
                        'shop_id' =>$shopId,
                        'memo' =>$mark,
                    ];
                }
            }
            $postData['mark'] = json_encode($postData['mark']);
        }

        //发票信息处理
        $postData['invoice_type']    = !$postData['invoice']['need_invoice'] ? 'notuse' : $postData['invoice']['invoice_type'];
        if( $postData['invoice_type'] == 'normal' )
        {
            $postData['invoice_content']['title'] = $postData['invoice']['invoice_title'];
            $postData['invoice_content']['content'] = $postData['invoice']['invoice_content'];
        }
        elseif( $postData['invoice_type'] == 'vat' )
        {
            $postData['invoice_content'] = $postData['invoice']['invoice_vat'];
        }
        $postData['invoice_content'] = json_encode($postData['invoice_content']);
        unset($postData['invoice']);

        $postData['user_id']   = userAuth::id();
        $postData['user_name'] = userAuth::getLoginName();
        return $postData;
    }
}

