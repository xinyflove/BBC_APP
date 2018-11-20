<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-15
 * Desc: 微信辅助功能
 */
class sysmaker_wechat {

    /**
     * 判断是否来自微信浏览器
     * @return bool
     */
    public function from_weixin()
    {
        if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false )
        {
            return true;
        }
        
        return false;
    }
    
}
