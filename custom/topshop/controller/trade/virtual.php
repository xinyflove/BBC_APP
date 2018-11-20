<?php
/**
 * Created by PhpStorm.
 * User: xinyufeng
 * Date: 2017/11/16
 * Time: 9:16
 */
class topshop_ctl_trade_virtual extends topshop_controller{
    public function refundSwitch()
    {
        $params = input::get();
        $data = array(
            'shop_id' => $this->shopId,
            'oid' => $params['oid'],
            'allow_refund' => $params['allow_refund'],
        );

        $url = url::action('topshop_ctl_trade_detail@index');

        try
        {
            app::get('topshop')->rpcCall('trade.virtual.refund',$data);
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', $url, $msg, true);
        }

        $msg = app::get('topshop')->_('操作成功');
        return $this->splash('success', $url, $msg, true);
    }
}