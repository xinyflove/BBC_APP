<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-14
 * Desc: 创客帐号登录相关业务控制器
 */
class topmaker_ctl_passport extends topmaker_controller {

    public $passport;

    public function __construct()
    {
        parent::__construct();
        kernel::single('base_session')->start();
        $this->passport = kernel::single('topwap_passport');
    }

    /**
     * 登录页面
     * @return mixed
     */
    public function signin()
    {
        $inputs = input::get();
        if(empty($inputs['userflag'])) $this->_wechatLogin('topmaker_ctl_trustlogin@callbackSignIn');//处理微信端访问

        $this->contentHeaderTitle = app::get('topmaker')->_('创客登录');

        if( pamAccount::isEnableVcode('sysmaker') )
        {
            // 开启验证码
            $pagedata['isShowVcode'] = 'true';
        }

        $pagedata['thirdData'] = json_encode($inputs);

        return $this->page('topmaker/passport/signin.html', $pagedata);
    }

    /**
     * 登录处理
     * @return mixed
     */
    public function login()
    {
        if( pamAccount::isEnableVcode('sysmaker') )
        {
            // 验证图片验证码
            if(!base_vcode::verify(input::get('imagevcodekey'), input::get('imgcode')))
            {
                $msg = app::get('topmaker')->_('图片验证码错误') ;
                $url = url::action('topmaker_ctl_passport@signin');
                return $this->splash('error',$url,$msg,true);
            }
        }

        try
        {
            $validator = validator::make(
                [
                    'loginAccount'=>input::get('login_account'),
                    'password' => input::get('login_password'),
                ],
                [
                    'loginAccount'=>'required|mobile',
                    'password' => 'required|min:6|max:20',
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

            // 登陆验证
            makerAuth::login(input::get('login_account'), input::get('login_password'));
        }
        catch(Exception $e)
        {
            $url = url::action('topmaker_ctl_passport@signin');
            $msg = $e->getMessage();
        }

        if( pamAccount::check() )   // 验证是否已登录
        {
            //登录成功
            if( input::get('remember_me') )
            {
                // 记录用户名
                setcookie('MAKERNAME',trim(input::get('login_account')),time()+31536000,kernel::base_url().'/');
            }

            $third = input::get('third');
            if($third)
            {
                // 添加信任登录数据
                $objHhirdpartyinfo = kernel::single('sysmaker_data_thirdpartyinfo');
                $bool = $objHhirdpartyinfo->saveThirdData($third, $msg);
                if($bool)
                {
                    // 保存信任登录数据
                    $objTrustinfo = kernel::single('sysmaker_data_trustinfo');
                    $trustData = array(
                        'seller_id' => pamAccount::getAccountId(),
                        'user_flag' => $third['userflag'],
                        'flag' => $third['flag'],
                    );
                    $trustId = $objTrustinfo->addTrustInfoData($trustData, $msg);
                }
            }
            
            $url = url::action('topmaker_ctl_index@index');
            $msg = app::get('topmaker')->_('登录成功');
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
        return redirect::action('topmaker_ctl_passport@signin');
    }

    /**
     * 注册页面
     * @return mixed
     */
    public function signup()
    {
        //如果已登录则退出登录
        if( pamAccount::check() ) pamAccount::logout();

        $inputs = input::get();
        if(empty($inputs['userflag'])) $this->_wechatLogin('topmaker_ctl_trustlogin@callbackSignUp');//处理微信端访问

        $this->contentHeaderTitle = app::get('topmaker')->_('创客注册');

        // 获取店铺列表
        $objShop = kernel::single('sysshop_data_shop');
        $shopList = $objShop->fetchListShopInfo('shop_id,shop_name');
        $pagedata['shopList'] = $shopList;

        $pagedata['thirdData'] = json_encode($inputs);

        return $this->page('topmaker/passport/signup.html', $pagedata);
    }

    /**
     * 注册处理
     * @return mixed
     */
    public function create()
    {
        $pagedata = utils::_filter_input(input::get());

        try
        {
            // 无需输入确认密码
            $validator = validator::make(
                [
                    'loginAccount'=>$pagedata['login_name'],
                    'password' => $pagedata['login_password'],
                    'mcode' => $pagedata['mcode'],
                    'name' => $pagedata['name'],
                    'shop_id' => $pagedata['shop_id'],
                ],
                [
                    'loginAccount'=>'required|mobile',
                    'password' => 'required|min:6|max:20',
                    'mcode' => 'required',
                    'name' => 'required',
                    'shop_id' => 'required',
                ],
                [
                    'loginAccount'=>'请输入你的手机号!|请输入正确的手机号码',
                    'password' => '密码长度不能小于6位!|密码长度不能大于20位!',
                    'mcode' => '请输入验证码!',
                    'name' => '请输姓名!',
                    'shop_id' => '请输选择店铺!',
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

            $sellerBool = makerAuth::isExists($pagedata['login_name'], $type='mobile');
            if($sellerBool)
            {
                throw new \LogicException("该手机号已经被使用");
            }

            $vcodeData = userVcode::verify($pagedata['mcode'], $pagedata['login_name'], $pagedata['mcode_type']);
            if(!$vcodeData)
            {
                throw new \LogicException('手机验证码填写错误');
            }

            $signUpData = array(
                'mobile' => $pagedata['login_name'],
                'login_password' => $pagedata['login_password'],
                'psw_confirm' => $pagedata['login_password'],
                'name' => $pagedata['name'],
                'shop_id' => $pagedata['shop_id'],
                'third' => $pagedata['third'],
            );
            
            $sellerId = makerAuth::signUp($signUpData);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', null, $msg);
        }

        $url = url::action('topmaker_ctl_passport@makerCheck');
        $msg = '注册成功';
        return $this->splash('success', $url, $msg);
    }

    /**
     * 验证状态页面
     * @return mixed
     */
    public function makerCheck()
    {
        if($this->sellerInfo['account']['status'] == 'success' && $this->bindShop['status'] == 'success')
        {
            return redirect::action('topmaker_ctl_index@index');
        }

        $this->contentHeaderTitle = app::get('topmaker')->_('申请进度');

        $pagedata['account_status'] = $this->sellerInfo['account']['status'];
        $pagedata['shop_status'] = $this->bindShop['status'];

        return $this->page('topmaker/passport/maker_check.html', $pagedata);
    }

    /**
     * 使用此方法的场景 手机验证码登录、找回密码
     * @return mixed
     */
    public function sendVcode()
    {
        $postdata = utils::_filter_input(input::get());

        $validator = validator::make(
            [$postdata['mobile']],['required|mobile'],['您的手机号不能为空!|请输入正确的手机号码']
        );

        $url = url::action('topmaker_ctl_passport@signup');

        if ($validator->fails())
        {
            $messages = $validator->messagesInfo();
            foreach( $messages as $error )
            {
                return $this->splash('error',$url,$error[0]);
            }
        }

        try {
            $this->passport->sendVcode($postdata['mobile'],$postdata['type']);
        } catch(Exception $e) {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg);
        }

        return $this->splash('success',null,"验证码发送成功");
    }

    /**
     * 微信信任登录
     * @param $redirectAction
     */
    protected function _wechatLogin($redirectAction)
    {
        // 判断是否来自微信浏览器
        if(kernel::single('sysmaker_wechat')->from_weixin())
        {
            $makerTrust = kernel::single('pam_trust_maker');
            // 如果开启了信任登录
            if($makerTrust->enabled())
            {
                $flag = 'wapweixin';
                // 获取指定的TRUST信息
                $trustInfo = $makerTrust->getTrustInfo('wap', $redirectAction, $flag);
                
                if($trustInfo)
                {
                    echo "<script>location.href = '{$trustInfo['url']}';</script>";
                    exit;
                }
            }
        }
    }
}