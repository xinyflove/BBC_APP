<?php
class topwap_offlinepayment{
    /*
     *检测要支付的订单数据有效性
     *创建支付单
     *返回支付单编号
     */
    public function getPaymentId($filter)
    {
        try
        {
            $paymentId = app::get('topwap')->rpcCall('payment.offline.bill.create',$filter);
        }
        catch(Exception $e)
        {
            throw $e;
        }
        return $paymentId;
    }
}
