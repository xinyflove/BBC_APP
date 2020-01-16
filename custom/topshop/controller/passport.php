<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topshop_ctl_passport extends topshop_controller
{

    //简单的登录页面
    public function simpleSignin()
    {
        return view::make('topshop/passport/simpleSignin.html');
    }

    /**
     * @brief 显示登录页面
     */
    public function signin()
    {

        //第三方记录session
        kernel::single('base_session')->start();
        kernel::single("base_session")->set_sess_expires(10080);
        $cp_login = $_SESSION['cp']['login'];
        $url = url::action('topshop_ctl_passport@signinCp');
        if($cp_login)
        {
            return redirect::to($url);
        }

        $this->contentHeaderTitle = app::get('topshop')->_('企业账号登录');
        $this->set_tmpl('passport');
        if (pamAccount::isEnableVcode('sysshop')) {
            $pagedata['isShowVcode'] = 'true';
        }

        $pagedata['backgroundImgUrl'] = base_storager::modifier(app::get('sysconf')->getConf('sysconf_setting.shop_login_background'));
        /*modify_20170926_by_fanglongji_start*/
        /*
            $pagedata['backgroundImgUrl'] = $pagedata['backgroundImgUrl'] ?: app::get('topshop')->res_url . '/images/bj_01.jpg';
        */
        $pagedata['backgroundImgUrl'] = $pagedata['backgroundImgUrl'] ?: app::get('topshop')->res_url . '/images/bj_01.gif';
        /*modify_20170926_by_fanglongji_end*/
        $pagedata['openid'] = input::get('openid');
        $pagedata['oauthcode'] = input::get('oauthcode');

        return $this->page('topshop/passport/signin.html', $pagedata);
    }

    /**
     * cpshop
     */
    public function signinCp()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('企业账号登录');
        $this->set_tmpl('passport');
        if (pamAccount::isEnableVcode('sysshop')) {
            $pagedata['isShowVcode'] = 'true';
        }

        $pagedata['backgroundImgUrl'] = base_storager::modifier(app::get('sysconf')->getConf('sysconf_setting.shop_login_background'));
        /*modify_20170926_by_fanglongji_start*/
        /*
            $pagedata['backgroundImgUrl'] = $pagedata['backgroundImgUrl'] ?: app::get('topshop')->res_url . '/images/bj_01.jpg';
        */
        $pagedata['backgroundImgUrl'] = $pagedata['backgroundImgUrl'] ?: app::get('topshop')->res_url . '/images/bj_02.jpg';
        /*modify_20170926_by_fanglongji_end*/
        $pagedata['openid'] = input::get('openid');
        $pagedata['oauthcode'] = input::get('oauthcode');

        return $this->page('topshop/passport/signinCp.html', $pagedata);
    }

    /**
     * @brief 会员登录处理
     *
     * @return
     */
    public function login()
    {
        if( pamAccount::isEnableVcode('sysshop') )
        {
            // 验证图片验证码
            if(!base_vcode::verify(input::get('imagevcodekey'), input::get('imgcode')))
            {
                $msg = app::get('topshop')->_('图片验证码错误') ;
                $url = url::action('topshop_ctl_passport@signin');
                return $this->splash('error',$url,$msg,true);
            }
        }
        try
        {
            shopAuth::login(input::get('login_account'), input::get('login_password'));
        }
        catch(Exception $e)
        {
            $url = url::action('topshop_ctl_passport@signin');
            $msg = $e->getMessage();
        }
        if( pamAccount::check() )
        {
            if( input::get('remember_me') )
            {
                setcookie('LOGINNAME',trim(input::get('login_account')),time()+31536000,kernel::base_url().'/');
            }

            $url = url::action('topshop_ctl_index@index');
            $msg = app::get('topshop')->_('登录成功');
            $this->sellerlog('账号登录。账号名是'.input::get('login_account'));

            /*add_start_gurundong_2017/12/14*/
            //第三方记录session
            kernel::single('base_session')->start();
            kernel::single("base_session")->set_sess_expires(10080);
            if(!$_SESSION['cp']['login']){
                //第三方登录特殊凭证
                $_SESSION['cp']['login'] = false;
            }
            /*add_end_gurundong_2017/12/14*/

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
     * @brief 会员登录处理
     *
     * @return
     */
    public function loginCp()
    {
        if( pamAccount::isEnableVcode('sysshop') )
        {
            // 验证图片验证码
            if(!base_vcode::verify(input::get('imagevcodekey'), input::get('imgcode')))
            {
                $msg = app::get('topshop')->_('图片验证码错误') ;
                $url = url::action('topshop_ctl_passport@signin');
                return $this->splash('error',$url,$msg,true);
            }
        }
        try
        {
            shopAuth::login(input::get('login_account'), input::get('login_password'));
        }
        catch(Exception $e)
        {
            $url = url::action('topshop_ctl_passport@signin');
            $msg = $e->getMessage();
        }
        if( pamAccount::check() )
        {
            if( input::get('remember_me') )
            {
                setcookie('LOGINNAME',trim(input::get('login_account')),time()+31536000,kernel::base_url().'/');
            }

            $url = url::action('topshop_ctl_index@index');
            $msg = app::get('topshop')->_('登录成功');
            $this->sellerlog('账号登录。账号名是'.input::get('login_account'));

            //第三方记录session
            kernel::single('base_session')->start();
            kernel::single("base_session")->set_sess_expires(10080);
            if(!$_SESSION['cp']['login']){
                //第三方登录特殊凭证
                $_SESSION['cp']['login'] = true;
            }


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
     * @brief 显示登录注册
     */
    public function signup()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('企业账号注册');
        $this->set_tmpl('pwdfind');
        $pagedata['license'] = app::get('sysshop')->getConf('sysshop.register.setting_sysshop_license');
        return $this->page('topshop/passport/signup.html', $pagedata);
    }

    public function isExists()
    {
        switch( input::get('type') )
        {
        case 'account':
            $str = input::get('login_account');
            break;
        case 'mobile':
            $str = input::get('mobile');
            break;
        case 'email':
            $str = input::get('email');
            break;
        }
        $flag = shopAuth::isExists($str, input::get('type'));
        $status =  $flag ? 'false' : 'true';
        return $this->isValidMsg($status);
    }

    /**
	 * add
     * @检验该供应商是否已经存在
	 * 2017/9/23
	 * 王海超
     */
    public function supplier_isExists()
    {
        switch( input::get('type') )
        {
        case 'account':
            $str = input::get('login_account');
            break;
        case 'mobile':
            $str = input::get('mobile');
            break;
        case 'email':
            $str = input::get('email');
            break;
        }
        $flag = shopAuth::supplier_isExists($str, input::get('type'));
        $status =  $flag ? 'false' : 'true';
        return $this->isValidMsg($status);
    }

    /**
     * @brief 创建商家会员
     *
     * @return json
     */
    public function create()
    {
        if(input::get('license') != 'on')
        {
            $msg = $this->app->_('同意注册条款后才能注册');
            throw new \LogicException($msg);
        }

        try
        {
            $request = input::get();
            if(!$_SESSION['register']['mobile'])
                throw new LogicException('手机号码错误');
            $request['mobile'] = $_SESSION['register']['mobile'];
            $request['auth_type'] = 'AUTH_MOBILE';
            shopAuth::signupSeller($request);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
        }

        if( pamAccount::check() )
        {
            $url = url::action('topshop_ctl_index@index');
            $msg = app::get('topshop')->_('注册成功');
            return $this->splash('success',$url,$msg,true);
        }
        else
        {
            return $this->splash('error',null,$msg,true);
        }
    }

    public function logout()
    {
        $is_hm_supplier_login = $this->checkHuiminSupplierLogin();
        pamAccount::logout();
        //判断如果是惠民供应商登陆，则跳转至供应商登陆页面
        if($is_hm_supplier_login) {
            unset($_SESSION['huimin_supplier_id']);
            return redirect::action('topshop_ctl_passport@huiminSupplierLoginPage');
        }
        return redirect::action('topshop_ctl_passport@signin');
    }

    public function logoutCp()
    {
        pamAccount::logoutCp();
        return redirect::action('topshop_ctl_passport@signinCp');
    }

    /**
     * @brief 会员密码修改
     */
    public function update()
    {
        return view::make('topshop/passport/update.html');
    }

    public function updatepwd()
    {
        try
        {
            shopAuth::modifyPwd(input::get());
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }

        $this->sellerlog('修改当前账号密码。');
        $url = url::action('topshop_ctl_passport@signin');
        $msg = app::get('topshop')->_('修改成功,请重新登陆');
        pamAccount::logout();

        return $this->splash('success',$url,$msg,true);
    }

    //
    public function huiminSupplierRegisterPage()
    {
        $this->set_tmpl('huiminPage');
        $pagedata=[];
        return $this->page('topshop/huimin/register.html', $pagedata);
    }

    public function huiminSupplierRegisterCreate()
    {
        try {
            $hm_shop_id = app::get('sysshop')->getConf('sysshop.hmshopping.shop_id');

            $params = input::get();
            $params['shop_id'] = $hm_shop_id;
            $params['supplier_name'] = '';
            $params['mobile'] = '';
            $params['lat'] = '';
            $params['lon'] = '';
            $params['sh_phone'] = '';
            $params['sh_address'] = '';
            // $params['is_audit'] = 'PASS';

            $objMdlSupplier = app::get('sysshop')->model('supplier');
            $record = $objMdlSupplier->count(['login_account' => $params['login_account']]);
            if( $record  )
            {
                throw new Exception('已用户名已被占用，请重新填写');
            }
            shopAuth::signupSupplier($params, true);

            $url = url::action('topshop_ctl_passport@huiminSupplierLoginPage');
            return $this->splash('success', $url, '创建成功', true);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg, true);
        }
    }

    public function huiminSupplierLoginPage()
    {
        if($this->checkHuiminSupplierLogin()){
            return redirect::action('topshop_ctl_index@index');
        }
        $this->set_tmpl('huiminPage');
        $pagedata=[];
        return $this->page('topshop/huimin/login.html', $pagedata);
    }

    public function huiminSupplierLogin()
    {
        try {
            $loginName = input::get('login_account');
            $password = input::get('login_password');
            if( empty($loginName) )
            {
                throw new Exception(app::get('syssupplier')->_('请输入账号'));
            }

            if( empty($password) )
            {
                throw new Exception(app::get('syssupplier')->_('请输入密码'));
            }

            $filter = ['login_account'=>trim($loginName), 'disabled'=>'0'];
            $supplier = app::get('sysshop')->model('supplier')->getRow('supplier_id,login_account,login_password', $filter);
            if(!$supplier || !hash::check($password, $supplier['login_password']))
            {
                throw new Exception(app::get('sysuser')->_('用户名或密码错误'));
            }
            $hm_shop_id = app::get('sysshop')->getConf('sysshop.hmshopping.shop_id');
            $seller = app::get('sysshop')->model('seller')->getRow('seller_id', ['shop_id' => $hm_shop_id], 'seller_id asc');
            if(!$seller){
                throw new Exception(app::get('syssupplier')->_('数据异常'));
            }

            $account = app::get('sysshop')->model('account')->getRow('seller_id,login_account', ['seller_id' => $seller['seller_id']]);
            if(!$account){
                throw new Exception(app::get('syssupplier')->_('数据异常'));
            }

            pamAccount::setSession($account['seller_id'], $account['login_account']);
            $_SESSION['huimin_supplier_id'] = $supplier['supplier_id'];
            $this->sellerlog("惠民店铺供货商账号登录。账号id：{$supplier['supplier_id']}，账号名：{$supplier['login_account']}");

            $url = url::action('topshop_ctl_account_supplier@hmEdit',['supplier_id' => $supplier['supplier_id']]);
            return $this->splash('success', $url, '登录成功', true);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg, true);
        }
    }
}

