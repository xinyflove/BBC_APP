<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-09
 * Desc: 广电优选登录
 */
class topshop_ctl_mall_passport {

    public function login()
    {
        $pagedata = array('error'=>0, 'msg'=>'登录成功', 'url'=>url::action('topshop_ctl_mall_home@index'));

        try
        {
            shopAuth::login(input::get('login_account'), input::get('login_password'));
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
        }

        if( !pamAccount::check() )
        {
            // 登录失败
            $pagedata['error'] = 1;
            $pagedata['msg'] = $msg;
            $pagedata['url'] = $_SERVER['HTTP_REFERER'];
        }

        return view::make('topshop/mall/passport.html', $pagedata);
    }
}