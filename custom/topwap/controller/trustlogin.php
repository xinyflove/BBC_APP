<?php
class topwap_ctl_trustlogin extends topwap_controller{

    public function __construct()
    {
        parent::__construct();
        kernel::single('base_session')->start();
        $this->passport = kernel::single('topwap_passport');
    }

	/**
	 * callback返回页, 同时是bind页面
	 *
	 * @return base_http_response
	 */
    public function callback()
    {
        $params = input::get();
        $flag = $params['flag'];
        unset($params['flag']);

        // 信任登陆校验
        $userTrust = kernel::single('pam_trust_user');
        $res = $userTrust->authorize($flag, 'web', 'topwap_ctl_trustlogin@callback', $params);

        $binded = $res['binded'];
        $userinfo = $res['user_info'];
        $realname = $userinfo['nickname'];
        $avatar = $userinfo['figureurl'];

        if ($binded)
        {
            $userId = $res['user_id'];
            userAuth::login($userId,null,'wap',$flag);
            // add start 王衍生 20170923
            kernel::single('sysuser_passport')->loginAfter($userId);
            kernel::single('topwap_cart')->mergeCart();

            if($_SESSION['next_page']){
               $url = $_SESSION['next_page'];
               unset($_SESSION['next_page']);
               header('Location: '.$url);
               exit;
            }
            // add end 王衍生 20170923

            return redirect::action('topwap_ctl_default@index');
        }
        else
        {
            $pagedata['realname'] =  $realname;
            $pagedata['avatar'] = $avatar;
            $pagedata['flag'] = $flag;

            // 如果要默认注册，就开启下面一行
            // $this->bindDefaultCreateUser();

            // if($_SESSION['next_page']){
            //    $url = $_SESSION['next_page'];
            //    unset($_SESSION['next_page']);
            // }else{
            //    $url = url::action('topwap_ctl_default@index');
            // }
            // $pagedata['back_url'] = $url;

            return $this->page('topwap/trustlogin/bind.html', $pagedata);
        }
    }

    // public function bindDefaultCreateUser()
    // {
    //     $params = input::get();
    //     $flag = $params['flag'];
    //     try
    //     {
    //         $userId = kernel::single('pam_trust_user')->bindDefaultCreateUser($flag);
    //         userAuth::login($userId, $loginName);
    //         //redirect::action('topwap_ctl_default@index')->send();exit;
    //         $url = url::action('topwap_ctl_default@index');
    //         return $this->splash('success', $url, $msg, true);

    //     }
    //     catch (\Exception $e)
    //     {
    //         $msg = $e->getMessage();
    //         return $this->splash('error',null,$msg,true);
    //     }
    // }
    /**
     * 绑定老用户
     * @Author   王衍生
     * @DateTime 2017-09-24T16:25:34+0800
     * @return   [type]                   [description]
     */
    public function bindExistsUser()
    {
        $params = input::get();
        // $verifyCode = $params['verifycode'];
        // $verifyKey = $params['vcodekey'];
        $loginName = $params['uname'];
        // $password = $params['password'];

        // 手机验证码
        $vcode = $params['vcode'];
        // 手机验证码类型
        $sendType =  $params['type'];

         // if(!$loginName || !$password )
         // {
         //    $msg = app::get('topwap')->_('用户名或密码必填') ;
         //    return $this->splash('error', $url, $msg, true);
         // }

        if( !app::get('sysconf')->getConf('user.account.register.multipletype') )
        {
            if(!preg_match("/^1[34578]{1}[0-9]{9}$/", $loginName))
            {
                $msg = app::get('topwap')->_("请输入正确的手机号码");
                return $this->splash('error', null, $msg, true);
            }
        }

        // if(userAuth::isShowVcode('login')){
        //     if( (!$verifyKey) || $b=empty($verifyCode) || $c=!base_vcode::verify($verifyKey, $verifyCode))
        //     {
        //         $msg = app::get('topwap')->_('验证码填写错误') ;
        //         $url = 'vcode_is_show';
        //         return $this->splash('error', $url, $msg, true);
        //     }
        // }

        $vcodeData=userVcode::verify($vcode,$loginName,$sendType);
        if(!$vcodeData)
        {
            $msg = app::get('topwap')->_('验证码填写错误') ;
            return $this->splash('error', null, $msg, true);
        }

        try
        {
            $user = userAuth::getAccountInfo($loginName);
            // if (userAuth::attempt($loginName, $password))
            if($user['user_id'])
            {
                userAuth::login($user['user_id'], $loginName,'wap',$params['flag']);
                kernel::single('pam_trust_user')->bind(userAuth::id(),$params['flag']);

                $this->replenishUserInfo();
                //登陆合并离线购物车
                kernel::single('topwap_cart')->mergeCart();
                // $url = url::action('topwap_ctl_default@index');
                if($_SESSION['next_page']){
                   $url = $_SESSION['next_page'];
                   unset($_SESSION['next_page']);
                }else{
                   $url = url::action('topwap_ctl_default@index');
                }

                $msg = app::get('topwap')->_('绑定手机号成功！') ;
                // $url = url::action('topwap_ctl_default@index');
                return $this->splash('success', $url, $msg, true);
            }else{
                $msg = app::get('topwap')->_('此手机号未注册！') ;
                return $this->splash('error', null, $msg, true);
            }
        }
        catch (Exception $e)
        {
            userAuth::setAttemptNumber();
            if( userAuth::isShowVcode('login') )
            {
                $url = 'vcode_is_show';
            }
            $msg = $e->getMessage();
            return $this->splash('error',$url,$msg,true);
        }
    }
    /**
     * 绑定新用户
     * @Author   王衍生
     * @DateTime 2017-09-24T16:25:58+0800
     * @return   [type]                   [description]
     */
    public function bindSignupUser()
    {
        $params = input::get();
        // 图片验证码
        // $verifyCode = $params['verifycode'];
        // 图片验证码类型
        // $verifyKey =  $params['vcodekey'];
        // 手机验证码
        $vcode = $params['vcode'];
        // 手机验证码类型
        $sendType =  $params['type'];
        // 用户名或手机号
        $loginName = $params['pam_account']['login_name'];
        // 密码
        // $password = $params['pam_account']['login_password'];
        // 确认密码
        // $confirmedPassword = $params['pam_account']['psw_confirm'];

        // 用户名、手机号、邮箱本来都可以注册，这里只从源头定为只支持手机号注册
        // 如果还需要其他注册方式，则在后台开启支持多方式注册
        // 增加客户手机信息的留存
        if( !app::get('sysconf')->getConf('user.account.register.multipletype') )
        {
            if(!preg_match("/^1[34578]{1}[0-9]{9}$/", $loginName))
            {
                $msg = app::get('topwap')->_("请输入正确的手机号码");
                return $this->splash('error', null, $msg, true);
            }
        }

        if(!$loginName)
         {
            $msg = app::get('topwap')->_('用户名必填') ;
            return $this->splash('error', $url, $msg, true);
         }

         // if(!$password)
         // {
         //    $msg = app::get('topwap')->_('密码必填') ;
         //    return $this->splash('error', $url, $msg, true);
         // }

         // if(!$confirmedPassword)
         // {
         //    $msg = app::get('topwap')->_('确认密码必填') ;
         //    return $this->splash('error', $url, $msg, true);
         // }


        // if( !$verifyKey || empty($verifyCode) || !base_vcode::verify($verifyKey, $verifyCode))
        // {
        //     $msg = app::get('topwap')->_('验证码填写错误') ;
        //     return $this->splash('error', null, $msg, true);
        // }

        $vcodeData=userVcode::verify($vcode,$loginName,$sendType);
        if(!$vcodeData)
        {
            $msg = app::get('topwap')->_('验证码填写错误') ;
            return $this->splash('error', null, $msg, true);
        }

        try
        {
            // 是否有注册锁
            $lock_status = redis::scene('sysuser')->get("signup_lock:{$params['flag']}:{$loginName}");
            if($lock_status){
                return $this->splash('error',null,'系统繁忙，请稍后再试！',true);
            }
            // 加上注册锁
            redis::scene('sysuser')->set("signup_lock:{$params['flag']}:{$loginName}", '1');

            // 手机验证码为初始密码
            $password = $confirmedPassword = $vcode;
            $userId = userAuth::signUp($loginName, $password, $confirmedPassword);
            userAuth::login($userId, $loginName,'wap',$params['flag']);
            kernel::single('pam_trust_user')->bind(userAuth::id(),$params['flag']);

            $this->replenishUserInfo();
            //登陆合并离线购物车
            kernel::single('topwap_cart')->mergeCart();
            // $url = url::action('topwap_ctl_default@index');

            if($_SESSION['next_page']){
               $url = $_SESSION['next_page'];
               unset($_SESSION['next_page']);
            }else{
               $url = url::action('topwap_ctl_default@index');
            }
            $msg = app::get('topwap')->_('创建账号成功！');
            // 删除注册锁
            redis::scene('sysuser')->setex("signup_lock:{$params['flag']}:{$loginName}", 5, 1);
            return $this->splash('success', $url, $msg, true);
        }
        catch (\Exception $e)
        {
            // 删除注册锁
            redis::scene('sysuser')->setex("signup_lock:{$params['flag']}:{$loginName}", 5, 1);
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }
    }
    /**
     * 第三方登录发送验证码
     * @Author   王衍生
     * @DateTime 2017-09-24T10:09:20+0800
     * @return   [type]                   [description]
     */
    public function sendVcode()
    {
        $postdata = utils::_filter_input(input::get());
        $validator = validator::make(
            [$postdata['uname']],['required|mobile'],['您的手机号不能为空!|请输入正确的手机号码']
        );

        if ($validator->fails())
        {
            $messages = $validator->messagesInfo();
            // $url = url::action('topwap_ctl_passport@goFindPwd');
            foreach( $messages as $error )
            {
                return $this->splash('error',null,$error[0]);
            }
        }

        // if( ! $_SESSION['topapi'.$postdata['uname']] )
        // {
        //     return $this->splash('error',$url,'页面已过期，请重新验证手机号');
        // }

        try {
            $this->passport->sendVcode($postdata['uname'],$postdata['type']);
        } catch(Exception $e) {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg);
        }
        return $this->splash('success',null,"验证码发送成功");
    }

    /**
     * 第三方绑定后，再补充其它信息
     * 暂只用来的绑定头像
     * @Author   王衍生
     * @DateTime 2017-09-27T16:34:02+0800
     * @return   [type]                   [description]
     */
    private function replenishUserInfo()
    {
        // 是否登录
        if(userAuth::check())
        {
            $userInfo = userAuth::getUserInfo();
            $default_headimg = app::get('sysconf')->getConf('user.default.headimg');
            // 头像为空或为默认头像
            if(!$userInfo['headimg_url'] || $userInfo['headimg_url'] == $default_headimg)
            {
                $userTrustInfo = kernel::single('pam_trust_user')->storage->all();

                $postdata = [
                    'headimg_url' => $userTrustInfo['user_info']['figureurl']
                ];
                $data = array('user_id'=>userAuth::id(),'data'=>json_encode($postdata));
                app::get('topwap')->rpcCall('user.basics.update',$data);
            }

        }
    }

    public function thirdpartyCallback()
    {
        $params = input::get();
        $flag = $params['flag'];
        unset($params['flag']);

        $userTrust = kernel::single('pam_trust_user');
        $res = $userTrust->thirdpartyInfo($flag, 'wap', 'topwap_ctl_trustlogin@thirdpartyCallback', $params);
        if($res) {
            $_SESSION['thirdparty_validated'] = true;
        }

        if($_SESSION['next_page']){
            $url = $_SESSION['next_page'];
            unset($_SESSION['next_page']);
            header('Location: '.$url);
            exit;
        }

        return redirect::action('topwap_ctl_default@index');
    }
}
