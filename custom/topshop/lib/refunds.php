<?php
/**
 *  房隆基
 *  处理退款
 */
class topshop_refunds {

    public function __construct()
    {

    }

    // 商家收款账户退款处理
    public function dorefund($refunds)
    {
        try
        {
            if( !in_array($refunds['status'],['3','5','6']) )
            {
                throw new \LogicException(app::get('topshop')->_('当前申请还未审核'));
            };

            $trade_paybill = app::get('ectools')->model('trade_paybill')->getRow('payment_id',array('tid' => $refunds['tid'],'status' => 'succ','user_id' => $refunds['user_id']));

            $payments = $trade_paybill = app::get('ectools')->model('payments')->getRow('*',array('payment_id' => $trade_paybill['payment_id'],'status' => 'succ'));
            if(!$payments)
            {
                throw new \LogicException(app::get('topshop')->_('没有此支付单'));
            }

            $refundsData['refunds_type'] = $refunds['refunds_type'];
            //退款类型 0售后 1取消
            if( $refunds['refunds_type'] != '1' )
            {
                $refundsData['aftersales_bn'] = $refunds['aftersales_bn'];
            }
            $refundsData['money'] = $refunds['total_price'];
            $refundsData['cur_money'] = $refunds['total_price'];
            $refundsData['refund_bank'] = $payments['bank'];
            $refundsData['refund_account'] = $payments['account'];
            // 退款人 先用店铺id代替
            $refundsData['refund_people'] = $refunds['shop_id'];
            $refundsData['receive_bank'] = $payments['bank'];
            $refundsData['receive_account'] = $payments['pay_account'];
            // 收款人 先用用户id代替
            $refundsData['beneficiary'] = $refunds['user_id'];
            $refundsData['currency'] = $payments['currency'];
            $refundsData['rufund_type'] = 'online';
            $refundsData['status'] = 'ready';

            $refundsData['tid'] = $refunds['tid'];
            $refundsData['oid'] = $refunds['oid'];

            $refundsData['op_id'] = pamAccount::getAccountId();
            $refundsData['return_fee'] = $refunds['total_price']; //退款总金额，包含红包，方便退款
            $refundsData['refunds_id'] = $refunds['refunds_id']; //sysaftersales/refunds.php主键，方便退款
            $refundsData['payment_id'] = $payments['payment_id']; //退款对应原支付单号

            //创建退款单
            $refundsId = app::get('sysaftersales')->rpcCall('refund.create',$refundsData);
            if(!$refundsId)
            {
                throw new \LogicException(app::get('topshop')->_('退款单创建失败'));
            }

            $shop['shop_id'] =  $refunds['shop_id'];
            $shop_info = app::get('ectools')->rpcCall('shop.get', $shop);
            $refunds['shop_info'] = $shop_info['shop_id']."-".$shop_info['shop_name'];

            $refunds['refund_id'] = $refundsId;
            $refunds['trade_no'] = $payments['trade_no'];
            $refunds['payment_id'] = $payments['payment_id'];
            /*add_20170928_by_fanglongji_start*/
            $refunds['shop_payment'] = unserialize($shop_info['payment']);
            $refunds['mer_collection'] = $shop_info['mer_collection'];
            /*add_20170928_by_fanglongji_end*/
            //货到付款不在线退款
            if($refundsData['money'] > 0 && $payments['pay_type'] == 'online')
            {
                $apiParams = [
                    'refund_id' => $refundsId,
                    'payment_id' => $refundsData['payment_id'],
                    'shop_info' => $refunds['shop_info'],
                    /*add_20170928_by_fanglongji_start*/
                    'shop_payment' => $refunds['shop_payment'],
                    'mer_collection' => $refunds['mer_collection'],
                    'refunds_type' => $refunds['refunds_type'],
                    /*add_20170928_by_fanglongji_end*/
                    'money' => number_format($refundsData['money'],2,'.',''),
                ];
                /*$res = app::get('sysaftersales')->rpcCall('payment.trade.refundpay', $apiParams);
                if($res['status']=='progress')
                {
                    if($res['submit_html'])
                    {
                        throw new \LogicException(app::get('topshop')->_($res));
                    }
                }
                if($res['status']!='succ')
                {
                    throw new \LogicException(app::get('topshop')->_('支付失败或者信息未返回'));
                }*/
            }

            //更改退款申请单
            $apiParams = ['return_fee' => $refunds['total_price'], 'refunds_id' => $refunds['refunds_id']];
            app::get('sysaftersales')->rpcCall('aftersales.refunds.restore', $apiParams);
        }
        catch(\Exception $e)
        {
            throw $e;
        }
    }
}

