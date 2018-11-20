<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2018/8/1
 * Time: 16:51
 */
class topwap_ctl_miniprogram_offline extends topwap_controller
{
    /**
     * 券包，买单券
     * @return mixed
     */
    public function ajaxVoucherShow()
    {
        try {
            //传参
            //s 状态
            //pages
            $postdata = input::get();
            /** @var topwap_ctl_member_agentvocher $voucher_ctl */
            $voucher_ctl = Kernel::single(topwap_ctl_member_agentvocher::class);
            $pagedata = $voucher_ctl->__getVoucher($postdata);
            return response::json([
                'err_no' => 0,
                'data' => $pagedata,
                'message' => '获取列表数据成功'
            ]);
        } catch (\Exception $exception) {
            return response::json([
                'err_no' => 1001,
                'data' => [
                ],
                'message' => $exception->getMessage()
            ]);
        }
    }

    /**
     * 我的-我的买单
     */
    public function ajaxOfflinePay()
    {
        try {
            //page_no
            $post_data = input::get();
            $user_id = userAuth::id();
            $filter['user_id'] = $user_id;
            $filter['fields'] = '*';
            $filter['page_no'] = $post_data['page_no'];
            $filter['page_size'] = 10;
            $offlinePay = kernel::single(topwap_ctl_member_offlinepay::class);
            $trade_list = $offlinePay->getOfflineTradeList($filter);
            $trade_list['list'] = array_values($trade_list['list']);
            foreach ($trade_list['list'] as $k=>$v) {
                //判断是消费的劵还是全场打折活动
                if (empty($v['voucher_list']['list'])) {
                    $trade_list['list'][$k]['is_voucher'] = false;
                } else {
                    $trade_list['list'][$k]['is_voucher'] = true;
                }
                //判断是否显示取消原因
                if ($v['status'] == 'TRADE_CLOSED' || $v['status'] == 'TRADE_CLOSED_BY_SYSTEM')
                {
                    $trade_list['list'][$k]['is_cancel_reason'] = true;
                }else{
                    $trade_list['list'][$k]['is_cancel_reason'] = false;
                }
            }
            return response::json([
                'err_no' => 0,
                'data' => (array)$trade_list,
                'message' => '获取列表数据成功'
            ]);
        } catch (\Exception $exception) {
            return response::json([
                'err_no' => 1001,
                'data' => [
                ],
                'message' => $exception->getMessage()
            ]);
        }
    }

    /**
     * 创建支付，去付款
     */
    public function createPay()
    {
        try
        {
            $post_data = input::get();

            $trade_filter['user_id'] = userAuth::id();
            $trade_filter['tid'] = $post_data['tid'];
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
            $session_id = input::get('session_id');
            $redirect_url = url::route('wap.mini.pay_index',[
                'payment_id' => $paymentId,
                'company_name' => $agent_shop_info['name'],
                'session_id'=>$session_id
            ]);
            return response::json([
                'err_no' => 0,
                'data' => [
                    'redirect_url'=>$redirect_url
                ],
                'message' => '创建支付成功'
            ]);
        }
        catch ( Exception $e )
        {
            return response::json([
                'err_no' => 1001,
                'data' => [
                ],
                'message' => $e->getMessage()
            ]);
        }
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
