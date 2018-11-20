<?php
class topwap_ctl_passport extends topwap_controller{

    public function __construct()
    {
        parent::__construct();
        kernel::single('base_session')->start();
        $this->passport = kernel::single('topwap_passport');
    }

    /**
     * @brief 进入登录/注册页面
     *
     * @return
     */
    public function goLogin()
    {
        // $next_page = $this->__getFromUrl();
        $_SESSION['next_page'] = $this->__getFromUrl();
        /*add_20180521_by_fanglongji_start*/
        // $_SESSION['account']['member']['before_register_url'] = $next_page;
        /*add_20180521_by_fanglongji_end*/
        // add start 王衍生 20170923
        $this->weixinsignin();
        // add end 王衍生 20170923
        // 第三方登录是否开启
        if (kernel::single('pam_trust_user')->enabled())
        {
            $trustInfoList = kernel::single('pam_trust_user')->getTrustInfoList('wap', 'topwap_ctl_trustlogin@callback');
        }

        // $isShowVcode = userAuth::isShowVcode('login');
        $wap_logo = app::get('sysconf')->getConf('sysconf_setting.wap_logo');
        $wap_name = app::get('sysconf')->getConf('sysconf_setting.wap_name');
        $pagedata = compact('trustInfoList', 'isShowVcode', 'wap_logo', 'wap_name');
        return $this->page('topwap/passport/login/new_index.html',$pagedata);
    }

    /**
     * 微信自动登录
     * @Author   王衍生
     * @DateTime 2017-09-23T16:37:53+0800
     * @return   [type]                   [description]
     */
    public function weixinsignin()
    {
        // if( userAuth::check() ){
        //     return redirect::action('topm_ctl_default@index');
        // }

        if(kernel::single('topwap_wechat_wechat')->from_weixin())
        {
            // $_SESSION['next_page'] = $this->__getFromUrl();
            $weixintrustInfo = kernel::single('pam_trust_user')->getTrustInfoRow('wap', 'topwap_ctl_trustlogin@callback','wapweixin');

            if($weixintrustInfo)
            {
                echo "<script>location.href = '{$weixintrustInfo['url']}';</script>";
                exit;
            }
        }
    }

    /**
     * @brief 完成登录流程
     *
     * @return
     */
    public function doLogin()
    {
        // if(userAuth::isShowVcode('login') )
        // {
        //     $url = url::action('topwap_ctl_default@index');
        //     $verifycode = input::get('verifycode');
        //     if( !input::get('key') || empty($verifycode) || !base_vcode::verify(input::get('key'), $verifycode))
        //     {
        //         $msg = app::get('topwap')->_('图片验证码填写错误') ;
        //         return $this->splash('error',null,$msg);
        //     }
        // }
        // 登录类型 password:密码登录  mcode:手机验证码登录
        $login_type = input::get('login_type', 'password');
        $postdata = utils::_filter_input(input::get());

        if($login_type == 'password') {
            $validator = validator::make(
                [
                    'loginAccount'=>$postdata['account'],
                    'password' => $postdata['password'],
                ],
                [
                    'loginAccount'=>'required',
                    'password' => 'required',
                ],
                [
                    'loginAccount'=>'请输入你的帐号',
                    'password' => '请输入你的密码',
                ]
            );
        }else{
            $validator = validator::make(
                [
                    'loginAccount'=>$postdata['account'],
                    'password' => $postdata['mcode'],
                ],
                [
                    'loginAccount'=>'required|mobile',
                    'password' => 'required',
                ],
                [
                    'loginAccount'=>'请输入你的手机号|请输入正确的手机号码',
                    'password' => '请输入手机验证码',
                ]
            );
        }


        if ($validator->fails())
        {
            $messages = $validator->messagesInfo();
            foreach( $messages as $error )
            {
                return $this->splash('error', '', $error[0]);
            }
        }

        try
        {

            if($login_type == 'password') {
                // 密码错误会抛出错误
                userAuth::attempt(input::get('account'), input::get('password'));
            }else{
                $vcode = $postdata['mcode'];
                $loginName = $postdata['account'];
                $sendType = $postdata['mcode_type'];

                $user = userAuth::getAccountInfo($loginName);
                // if (userAuth::attempt($loginName, $password))
                if($user['user_id'])
                {
                    $vcodeData = userVcode::verify($vcode, $loginName, $sendType);
                    if(!$vcodeData)
                    {
                        throw new \LogicException('手机验证码填写错误');
                    }
                    userAuth::login($user['user_id'], $loginName,'wap');
                }else{
                    throw new \LogicException('此手机号还未注册，先去注册吧');
                }
            }
            //记住密码功能暂无
            //userAuth::setAttemptRemember(input::get('remember',null));

            //商品收藏店铺收藏加入cookie
            $userId = userAuth::id();
            // 已在attempt中的事件中执行，此处是否多余？ 王衍生问
            $collectData = app::get('topwap')->rpcCall('user.collect.info',array('user_id'=>$userId));
            setcookie('collect',serialize($collectData));

            if($_SESSION['next_page']){
                $url = $_SESSION['next_page'];
                unset($_SESSION['next_page']);
            }else{
                $url = url::action('topwap_ctl_default@index');
            }
            kernel::single('topwap_cart')->mergeCart();
            return $this->splash('success',$url,$msg);
        }
        catch(Exception $e)
        {
            // userAuth::setAttemptNumber();
            // if( userAuth::isShowVcode('login') )
            // {
            //     $url = url::action('topwap_ctl_passport@goLogin');
            // }
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg);
        }
    }


    /**
     * @brief 进入注册页面
     * 已弃用
     * @return
     */
    public function goRegister()
    {
        if( userAuth::check() ) $this->logout();
        return $this->page('topwap/passport/register/index.html');
    }

    /**
     * @brief 注册时验证用户名是否有效
     * 已弃用
     * @return
     */
    public function checkUname()
    {
        $data = utils::_filter_input(input::get());

        $uname = $data['uname'];

        // 用户名、手机号、邮箱本来都可以注册，这里只从源头定为只支持手机号注册
        // 如果还需要其他注册方式，则在后台开启支持多方式注册
        // 增加客户手机信息的留存
        if( !app::get('sysconf')->getConf('user.account.register.multipletype') )
        {
            if(!preg_match("/^1[34578]{1}[0-9]{9}$/", $uname))
            {
                $msg = app::get('topwap')->_("请输入正确的手机号码");
                return $this->splash('error','',$msg);
            }
        }

        $userData = userAuth::getAccountInfo($uname);
        if($userData)
        {
            $msg = app::get('topwap')->_("该用户名或手机号已经使用");
            return $this->splash('error','',$msg);
        }

        $accountType = app::get('topwap')->rpcCall('user.get.account.type',array('user_name'=>$uname));
        try
        {
            kernel::single('sysuser_passport')->checkSignupAccount($uname, $accountType);
        }
        catch( \LogicException $e )
        {
            return $this->splash('error','',$e->getMessage());
        }

        //检测注册协议是否被阅读选中
        if(!input::get('license'))
        {
            $msg = app::get('topwap')->_('请阅读并接受会员注册协议');
            return $this->splash('error','',$msg);
        }

        $verifycode = $data['verifycode'];
        if( !input::get('key') || empty($verifycode) || !base_vcode::verify(input::get('key'), $verifycode))
        {
            $msg = app::get('topwap')->_('验证码填写错误') ;
            return $this->splash('error',null,$msg);
        }

        if($accountType == "mobile")
        {
            $pagedata['data']['mobile'] = $uname;
            $pagedata['data']['type'] = 'signup';
            $randomId = str_random(32);
            $_SESSION['topapi'.$uname] = $randomId;
            return view::make('topwap/passport/verify_vcode.html',$pagedata);
        }
        else
        {
            return view::make('topwap/passport/register/set_pwd.html',$data);
        }
    }

    /**
     *
     * @brief 完成注册流程
     * 王衍生
     * @return
     */
    public function doRegister()
    {
        $data = utils::_filter_input(input::get());
        $userInfo = $data['pam_account'];
        // $validator = validator::make(
        //     ['loginAccount'=>$userInfo['login_name'],'password' => $userInfo['login_password'], 'password_confirmation' => $userInfo['psw_confirm']],
        //     ['loginAccount'=>'required|mobile','password' => 'min:6|max:20|confirmed','password_confirmation'=>'required'],
        //     ['loginAccount'=>'请输入你的手机号!|请输入正确的手机号码','password' => '密码长度不能小于6位!|密码长度不能大于20位!|输入的密码不一致!','password_confirmation'=>'确认密码不能为空!']
        // );
        try
        {
            //检测注册协议是否被阅读选中
            // if(!$data['license'])
            // {
            //     $msg = app::get('topwap')->_('请阅读并接受会员注册协议');
            //     throw new \LogicException($msg);
            // }

            // 无需输入确认密码
            $validator = validator::make(
                [
                    'loginAccount'=>$userInfo['login_name'],
                    'password' => $userInfo['login_password'],
                ],
                [
                    'loginAccount'=>'required|mobile',
                    'password' => 'min:6|max:20',
                ],
                [
                    'loginAccount'=>'请输入你的手机号!|请输入正确的手机号码',
                    'password' => '密码长度不能小于6位!|密码长度不能大于20位!',
                ]
            );

            if ($validator->fails())
            {
                $messages = $validator->messagesInfo();
                foreach( $messages as $error )
                {
                    throw new \LogicException($error[0]);
                }
            }

            $userData = userAuth::getAccountInfo($userInfo['login_name']);
            if($userData)
            {
                throw new \LogicException("该手机号已经被使用");
            }

            $vcodeData = userVcode::verify($data['mcode'], $userInfo['login_name'], $data['mcode_type']);
            if(!$vcodeData)
            {
                throw new \LogicException('手机验证码填写错误');
            }

            $userId = userAuth::signUp($userInfo['login_name'], $userInfo['login_password'], $userInfo['login_password']);
            userAuth::login($userId, $userInfo['login_name'],'wap');

            $collectData = app::get('topwap')->rpcCall('user.collect.info',array('user_id'=>$userId));
            setcookie('collect',serialize($collectData));
            kernel::single('topwap_cart')->mergeCart();
        }
        catch(Exception $e)
        {
            //$url = url::action('topwap_ctl_passport@goRegister');
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg);
        }

        // $url = url::action('topwap_ctl_passport@registerSucc');
        if($_SESSION['next_page']){
            $url = $_SESSION['next_page'];
            unset($_SESSION['next_page']);
        }else{
            $url = url::action('topwap_ctl_default@index');
        }
        return $this->splash('success', $url, $msg);
    }

    /**
     * 已弃用
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function registerSucc()
    {
        $pagedata['site_name'] = app::get('site')->getConf('site.name');
        $pagedata['site_logo'] = app::get('site')->getConf('site.logo');
        $pagedata['sendPointNum'] = app::get('sysconf')->getConf('sendPoint.num');
        $pagedata['open_sendpoint'] = app::get('sysconf')->getConf('open.sendPoint');
        /*add_20180521_by_fanglongji_start*/
        if($_SESSION['account']['member']['before_register_url'])
        {
            $pagedata['before_register_url'] = $_SESSION['account']['member']['before_register_url'];
        }
        else
        {
            $pagedata['before_register_url'] =  url::action('topwap_ctl_default@index');
        }
        /*add_20180521_by_fanglongji_end*/
        return $this->page('topwap/passport/register/succ.html',$pagedata);
    }

    /**
     * @brief 获取用户注册协议
     *
     * @return
     */
    public function registerLicense()
    {
        $pagedata['title'] = "用户注册协议";
        $pagedata['license'] = app::get('sysconf')->getConf('sysconf_setting.wap_license');

        return $this->page('topwap/passport/register/license.html', $pagedata);
    }



    /**
     * @brief 找回密码第一步，进入找回密码页面
     *
     * @return  html
     */
    public function goFindPwd()
    {
        return $this->page('topwap/passport/forgotten/verify-uname.html');
    }

    /**
     * @brief 找回密码第二步，验证用户名/手机号
     *
     * @return
     */
    public function verifyUsername()
    {
        $postdata = utils::_filter_input(input::get());

        //验证图片验证码
        $valid = validator::make(
            [$postdata['verifycode']],['required']
        );
        if($valid->fails())
        {
            return $this->splash('error',null,"图片验证码不能为空!");
        }
        if(!base_vcode::verify($postdata['verifycodekey'],$postdata['verifycode']))
        {
            return $this->splash('error',null,"图片验证码错误!");
        }

        //验证用户名
        if($postdata['username'])
        {
            $loginName = $postdata['username'];
            $data = userAuth::getAccountInfo($loginName);
            if($data)
            {
                $randomId = str_random(32);
                $_SESSION['topapi'.$postdata['username']] = $randomId;
                $data['type'] = "forgot";
                $pagedata['data'] = $data;
                return view::make('topwap/passport/verify_vcode.html',$pagedata);
            }
        }

        $url = url::action('topwap_ctl_passport@goFindPwd');
        $msg = app::get('topwap')->_('账户不存在');
        return $this->splash('error',$url,$msg);
    }

    /**
     * 使用此方法的场景 手机验证码登录、找回密码
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function sendVcode()
    {
        $postdata = utils::_filter_input(input::get());
        $validator = validator::make(
            [$postdata['uname']],['required|mobile'],['您的手机号不能为空!|请输入正确的手机号码']
        );

        $url = '';

        if($postdata['type'] == 'login'){

        }elseif($postdata['type'] == 'signup'){

        }else{
            $url = url::action('topwap_ctl_passport@goFindPwd');

            if( ! $_SESSION['topapi'.$postdata['uname']] )
            {
                return $this->splash('error',$url,'页面已过期，请重新验证手机号');
            }
        }

        if ($validator->fails())
        {
            $messages = $validator->messagesInfo();
            foreach( $messages as $error )
            {
                return $this->splash('error',$url,$error[0]);
            }
        }

        try {
            $this->passport->sendVcode($postdata['uname'],$postdata['type']);
        } catch(Exception $e) {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg);
        }
        return $this->splash('success',null,"验证码发送成功");
    }

    /**
     * @brief 找回密码第三步 验证手机验证码
     *
     * @return
     */
    public function verifyVcode()
    {
        $postdata = utils::_filter_input(input::get());
        $vcode = $postdata['vcode'];
        $loginName = $postdata['uname'];
        $sendType = $postdata['type'];
        try
        {
            $vcodeData=userVcode::verify($vcode,$loginName,$sendType);
            if(!$vcodeData)
            {
                throw new \LogicException('验证码输入错误');
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg);
        }

        $userInfo = userAuth::getAccountInfo($loginName);
        $key = userVcode::getVcodeKey($loginName ,$sendType);
        $userInfo['key'] = md5($vcodeData['vcode'].$key.$userInfo['user_id']);

        unset($_SESSION['topapi'.$loginName]);
        if($sendType == "forgot")
        {
            $pagedata['data'] = $userInfo;
            $pagedata['account'] = $loginName;
            return view::make('topwap/passport/forgotten/setting_passport.html', $pagedata);
        }
        else
        {
            $pagedata['uname'] = $loginName;
            return view::make('topwap/passport/register/set_pwd.html',$pagedata);
        }
    }

    /**
     * @brief 找回密码第四部 设置新密码
     *
     * @return
     */
    public function settingPwd()
    {
        $postdata = utils::_filter_input(input::get());
        $userId = $postdata['userid'];
        $account = $postdata['account'];

        $vcodeData = userVcode::getVcode($account,'forgot');
        $key = userVcode::getVcodeKey($account,'forgot');

        if($account !=$vcodeData['account']  || $postdata['key'] != md5($vcodeData['vcode'].$key.$userId) )
        {
            $msg = app::get('topwap')->_('页面已过期,请重新找回密码');
            return $this->splash('failed',null,$msg,true);
        }

        $data['type'] = 'reset';
        $data['new_pwd'] = $postdata['password'];
        $data['user_id'] = $postdata['userid'];
        $data['confirm_pwd'] = $postdata['confirmpwd'];
        try
        {
            app::get('topwap')->rpcCall('user.pwd.update',$data,'buyer');

        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }

        $msg = "修改成功";
        $url = url::action('topwap_ctl_passport@goLogin');
        return $this->splash('success',$url,$msg,true);
    }

    public function logout()
    {
        userAuth::logout();
        return redirect::action('topwap_ctl_default@index');
    }


    private function __getFromUrl()
    {


        $url = utils::_filter_input(input::get('next_page', request::server('HTTP_REFERER')));
        // if(!$url && input::get('shop_id')){
        //     return url::action('topwap_ctl_newshop@index', input::get());
        // }
        // to_index 跳转到店铺首页标识
        if(input::get('shop_id') && input::get('to_index')){
            return url::action('topwap_ctl_newshop@index', input::get());
        }

        $validator = validator::make([$url],['url'],['数据格式错误！']);
        if ($validator->fails())
        {
            return url::action('topwap_ctl_default@index');
        }
        if( !is_null($url) )
        {
            if( strpos($url, 'passport') )
            {
                return url::action('topwap_ctl_default@index');
            }
            return $url;
        }else{
            return url::action('topwap_ctl_default@index');
        }
    }
}
