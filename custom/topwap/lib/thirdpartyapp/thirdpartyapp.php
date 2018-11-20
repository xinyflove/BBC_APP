<?php
/**
 * 第三方app内嵌商城
 *
 * @Author 王衍生 50634235@qq.com
 */
class topwap_thirdpartyapp_thirdpartyapp
{
    /**
     * 返回第三方app标识
     * 未在第三方app 则返回false
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function from_thirdparty_app() {
        preg_match("/tvplaza_thirdparty_app_(.+?)_app/i", $_SERVER['HTTP_USER_AGENT'], $user_agent);
        if(empty($user_agent[1])) {
            // 为兼容 蓝睛app 的获取方式
            return $this->from_lanjing();
            // return false;
        }

        $app_info = config::get('thirdpartyapp.' . $user_agent[1]);
        if(empty($app_info) || $app_info['enabled'] !== true) {
            return false;
        }

        return $user_agent[1];
    }

    /**
     * 为兼容早期蓝睛app
     * 判断是否来自蓝睛APP
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function from_lanjing() {
        // $_SERVER['HTTP_USER_AGENT'] = 'abcdlanjing{"uid":"10"}';
        $p = strpos($_SERVER['HTTP_USER_AGENT'], 'lanjing');
        if ( $p !== false ) {
            $json_info = json_decode(substr($_SERVER['HTTP_USER_AGENT'], $p + 7), true);
            if($json_info['from'] == 'lanjing') {
                return 'lanjing';
            }
        }
        return false;
    }

    public function from_system()
    {
        if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'iphone') || strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'ipad')){
            return 'ios';
        }else if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'android')){
            return 'android';
        }else{
            return '';
        }
    }
}