<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2018/7/27
 * Time: 9:48
 */

class topwap_ctl_miniprogram_trustlogin extends topwap_controller
{
    /**
     * @var base_session session管理器
     */
    protected $session_obj = null;

    public function __construct()
    {
        $config = config::get('session');
        $sess_key = $config['cookie'] ? $config['cookie']:'s';
        $_COOKIE[$sess_key] = input::get('session_id',false);
        $session_id = input::get('session_id',false);
        $this->session_obj = kernel::single('base_session');
        $this->session_obj->start();
        $this->session_obj->set_sess_expires(60*6);
        //if($session_id){
        //    $this->session_obj->set_sess_id($session_id);
        //}
        parent::__construct();
    }

    /**
     * @throws ErrorException
     */
    public function callback()
    {
        /**
         * state 判断微信回来的是否为自己的请求
         */
        try{
            $params = input::get();
            $code = $params['code'];
            if(!$code)
            {
                throw new \ErrorException('微信code必传');
            }

            /** @var sysuser_plugin_wapmini $wixinTrustObj */
            $wixinTrustObj = Kernel::single(sysuser_plugin_wapmini::class);
            $wixinTrustObj->generateAccessToken($code);
            $open_id = $wixinTrustObj->generateOpenId();
            if(!$open_id)
            {
                throw new \LogicException('open_id错误');
            }
            $userFlag = $wixinTrustObj->generateUserFlag($open_id);
            $trustModel = app::get('sysuser')->model('trustinfo');
            //判断用户是否绑定
            if ($row = $trustModel->getRow('user_id', ['user_flag' => $userFlag,'flag'=>'wapmini']))
            {
                $userId = $row['user_id'];
                userAuth::login($userId,null,'wap','wapweixin');
                kernel::single('sysuser_passport')->loginAfter($userId);
                return response::json([
                    'err_no'=>0,
                    'data'=>[
                        //获取session_id
                        'session_id'=>$this->session_obj->sess_id()
                    ],
                    'message'=>'用户微信登录成功'
                ]);
            }else{
                //新用户注册逻辑
                return response::json([
                    'err_no'=>1002,
                    'data'=>[
                        'session_id'=>$this->session_obj->sess_id()
                    ],
                    'message'=>'用户尚未注册，进行新用户注册'
                ]);
            }
        }catch (\Exception $exception)
        {
            return response::json([
                'err_no'=>1001,
                'data'=>[
                ],
                'message'=>$exception->getMessage()
            ]);
        }
    }

    public function bind()
    {
        try{
            $params = input::get();
            // 手机验证码
            $vcode = $params['vcode'];
            // 手机验证码类型
            $sendType =  'signup';
            // 用户名或手机号
            $loginName = $params['login_name'];

            if(!$loginName)
            {
                $msg = app::get('topwap')->_('用户名必填') ;
                throw new \LogicException($msg);
            }

            // 用户名、手机号、邮箱本来都可以注册，这里只从源头定为只支持手机号注册
            // 如果还需要其他注册方式，则在后台开启支持多方式注册
            // 增加客户手机信息的留存
            if( !app::get('sysconf')->getConf('user.account.register.multipletype') )
            {
                //if(!preg_match("/^1[34578]{1}[0-9]{9}$/", $loginName))
                if(!preg_match("/^((13[0-9])|(14[5,7])|(15[0-3,5-9])|(17[0,3,5-8])|(18[0-9])|166|198|199|(147))\\d{8}$/", $loginName))
                {
                    $msg = app::get('topwap')->_("请输入正确的手机号码");
                    throw new \LogicException($msg);
                }
            }

            $vcodeData=userVcode::verify($vcode,$loginName,$sendType);
            if(!$vcodeData)
            {
                $msg = app::get('topwap')->_('验证码填写错误') ;
                throw new \LogicException($msg);
            }

            $code = $params['code'];
            if(!$code)
            {
                throw new \ErrorException('微信code必传');
            }

            /** @var sysuser_plugin_wapmini $wixinTrustObj */
            $wixinTrustObj = Kernel::single(sysuser_plugin_wapmini::class);
            $wixinTrustObj->generateAccessToken($code);
            $open_id = $wixinTrustObj->generateOpenId();
            if(!$open_id)
            {
                throw new \LogicException('open_id错误');
            }
            //获取userFlag
            $userFlag = $wixinTrustObj->generateUserFlag($open_id);

            $user = userAuth::getAccountInfo($loginName);
            if($user['user_id']){
                //绑定老用户
                userAuth::login($user['user_id'], $loginName,'wap','wapmini');
                kernel::single('sysuser_passport_trust_trust')->bind($user['user_id'], $userFlag, 'wapmini');
                $this->replenishUserInfo();
                //登陆合并离线购物车
                kernel::single('topwap_cart')->mergeCart();
            }else{
                //创建新用户
                // 手机验证码为初始密码
                $password  = $vcode;
                $userId = userAuth::signUp($loginName, $password, $password);
                userAuth::login($userId, $loginName,'wap','wapmini');
                kernel::single('sysuser_passport_trust_trust')->bind($userId, $userFlag, 'wapmini');
                $this->replenishUserInfo();
                //登陆合并离线购物车
                kernel::single('topwap_cart')->mergeCart();
            }
            return response::json([
                'err_no'=>0,
                'data'=>[
                ],
                'message'=>'绑定手机号成功'
            ]);

        }catch (\Exception $exception)
        {
            return response::json([
                'err_no'=>1001,
                'data'=>[
                    'session_id'=>$this->session_obj->sess_id()
                ],
                'message'=>$exception->getMessage()
            ]);
        }

    }

    /**
     * 第三方绑定后，再补充其它信息
     * 暂只用来的绑定头像
     * @Author   王衍生
     * @DateTime 2017-09-27T16:34:02+0800
     * @return   [type]                   [description]
     */
    public function replenishUserInfo()
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

    /**
     * 第三方登录发送验证码
     */
    public function sendVcode()
    {
        try{
            $postdata = utils::_filter_input(input::get());
            $validator = validator::make(
                [$postdata['login_name']],['required|mobile'],['您的手机号不能为空!|请输入正确的手机号码']
            );
            if ($validator->fails())
            {
                $messages = $validator->messagesInfo();
                foreach( $messages as $error )
                {
                    throw new \LogicException($error[0]);
                }
            }
            $passport = kernel::single('topwap_passport');
            $passport->sendVcode($postdata['login_name'],'signup');
        }catch (\Exception $exception)
        {
            return response::json([
                'err_no'=>1001,
                'data'=>[
                ],
                'message'=>$exception->getMessage()
            ]);
        }
        return response::json([
            'err_no'=>0,
            'data'=>[
            ],
            'message'=>'发送验证码成功'
        ]);
    }


}
