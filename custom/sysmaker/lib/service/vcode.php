<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-14
 * Desc: 创客帐号登录验证码服务配置
 */
class sysmaker_service_vcode {

    public function status()
    {
        pamAccount::setAuthType('sysmaker');

        $errorCount = pamAccount::getLoginErrorCount();
        //没开启验证码必填的情况下，错误三次及其以上则需要验证码
        return ($errorCount >= 300) ?  true : false;
    }
}
