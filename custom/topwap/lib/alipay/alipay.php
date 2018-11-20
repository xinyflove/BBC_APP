<?php
class topwap_alipay_alipay
{
    // 判断是否来自支付宝浏览器
    public function from_alipay()
    {
        if ( strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false )
        {
            return true;
        }
        return false;
    }
}
