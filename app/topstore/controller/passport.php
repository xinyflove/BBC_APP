<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/4
 * Time: 15:13
 * Desc: 商城帐号登录相关业务控制器
 */
class topstore_ctl_passport extends topstore_controller {

    //登录页面弹出框(dialog)
    public function simpleSignin()
    {
        return view::make('topstore/passport/simpleSignin.html');
    }

    /**
     * 登录页面
     * @return mixed
     */
    public function signin()
    {
        $this->contentHeaderTitle = app::get('topstore')->_('商城账号登录');
        $this->set_tmpl('passport');
        if( pamAccount::isEnableVcode('sysstore') )
        {
            $pagedata['isShowVcode'] = 'true';
        }

        $pagedata['backgroundImgUrl'] = base_storager::modifier(app::get('sysconf')->getConf('sysconf_setting.store_login_background'));
        $pagedata['backgroundImgUrl'] = $pagedata['backgroundImgUrl'] ?: app::get('topstore')->res_url . '/images/bj_01.gif';

        $pagedata['openid'] = input::get('openid');
        $pagedata['oauthcode'] = input::get('oauthcode');

        return $this->page('topstore/passport/signin.html', $pagedata);
    }

    /**
     * 登录处理
     * @return mixed
     */
    public function login()
    {
        if( pamAccount::isEnableVcode('sysstore') )
        {
            // 验证图片验证码
            if(!base_vcode::verify(input::get('imagevcodekey'), input::get('imgcode')))
            {
                $msg = app::get('topstore')->_('图片验证码错误') ;
                $url = url::action('topstore_ctl_passport@signin');
                return $this->splash('error',$url,$msg,true);
            }
        }

        try
        {
            storeAuth::login(input::get('login_account'), input::get('login_password'));
        }
        catch(Exception $e)
        {
            $url = url::action('topstore_ctl_passport@signin');
            $msg = $e->getMessage();
        }

        if( pamAccount::check() )
        {
            //登录成功
            if( input::get('remember_me') )
            {
                setcookie('LOGINNAME',trim(input::get('login_account')),time()+31536000,kernel::base_url().'/');
            }

            $url = url::action('topstore_ctl_index@index');
            $msg = app::get('topstore')->_('登录成功');
            $this->accountlog('账号登录。账号名是'.input::get('login_account'));

            if(request::ajax())
                return $this->splash('success',$url,$msg,true);
            else
                return redirect::to($url);
        }
        else
        {
            return $this->splash('error',$url,$msg,true);
        }
    }

    /**
     * 退出
     * @return mixed
     */
    public function logout()
    {
        pamAccount::logout();
        return redirect::action('topstore_ctl_passport@signin');
    }

    /**
     * 密码修改页面
     * @return mixed
     */
    public function update()
    {
        return view::make('topstore/passport/update.html');
    }

    /**
     * 密码修改处理
     * @return mixed
     */
    public function updatepwd()
    {
        try
        {
            storeAuth::modifyPwd(input::get());
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }

        $this->accountlog('修改当前账号密码。');
        $url = url::action('topstore_ctl_passport@signin');
        $msg = app::get('topstore')->_('修改成功,请重新登陆');
        pamAccount::logout();

        return $this->splash('success',$url,$msg,true);
    }

    /**
     * 免密码登录
     * @return mixed
     */
    public function noLogin()
    {
        $params = input::get();
        $sign = $params['sign'];
        unset($params['sign']);
        if($sign != $this->sign($params))
        {
            return $this->splash('error',null,'签名错误',true);
        }

        $accountMdl = app::get('sysstore')->model('account');
        $account = $accountMdl->getRow('account_id, login_account', array('login_account'=>$params['login_account'],'disabled'=>0,'deleted'=>0));
        if(empty($account))
        {
            return $this->splash('error',null,'登录失败',true);
        }

        pamAccount::setAuthType('sysstore');
        pamAccount::setSession($account['account_id'], $account['login_account']);
        $url = url::action('topstore_ctl_index@index');
        $msg = app::get('topstore')->_('登录成功');
        $this->accountlog('账号登录。账号名是'.input::get('login_account'));
        return redirect::to($url);
    }

    /**
     * 签名算法
     * @param $params
     * @return string
     */
    protected function sign($params)
    {
        $sign = '';
        if(is_array($params)){
            ksort($params, SORT_STRING);
            foreach($params AS $key=>$val){
                if(is_null($val)) continue;
                if(is_bool($val)) $val = $val ? 1 : 0;
                $sign .= $key . $val;
            }
        }
        $sign .= 'AVOIDHTELOGIN';
        return md5($sign);
    }
}