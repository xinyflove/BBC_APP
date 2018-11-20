<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-14
 * Desc: 创客登录、注册流程
 */
class sysmaker_passport {
    
    public $sellerId = null;

    public $accountName = null;

    public function __construct()
    {
        $this->app = app::get('sysmaker');
        kernel::single('base_session')->start();

        pamAccount::setAuthType('sysmaker');//设置权限类型
        $this->sellerId = pamAccount::getAccountId();
    }

    /**
     * 获取登录创客的信息
     * @param string $sellerId
     * @return array
     */
    public function getSellerData($sellerId=0)
    {
        if( !$this->sellerData )
        {
            if(!$sellerId)
            {
                $sellerId = pamAccount::getAccountId();//获取当前登录用户id
            }
            
            if(!$sellerId)
            {
                $this->sellerData = NULL;
            }
            else
            {
                $sellerModel = app::get('sysmaker')->model('seller');
                $this->sellerData = $sellerModel->getRow('*', array('seller_id'=>$sellerId));
                $accountModel = app::get('sysmaker')->model('account');
                $this->sellerData['account'] = $accountModel->getRow('*', array('seller_id'=>$sellerId));
            }
        }
        return $this->sellerData;
    }

    /**
     * 创客登录
     * @param $loginAccount
     * @param $loginPassword
     * @return bool
     */
    public function login($loginAccount, $loginPassword)
    {
        $account = $this->apiLogin($loginAccount, $loginPassword);
        pamAccount::setSession($account['sellerId'], $account['loginAccount']);
        $this->accountloginlog();
        return true;
    }

    /**
     * 创客登录api
     * @param $loginAccount
     * @param $loginPassword
     * @return array
     */
    public function apiLogin($loginAccount, $loginPassword)
    {
        //检查数据安全
        $loginAccount = utils::_filter_input($loginAccount);
        $loginPassword = utils::_filter_input($loginPassword);

        $sellerId = $this->__verifyLogin($loginAccount, $loginPassword);

        if( $sellerId )
        {
            // 判断是否有重复数据
            $num = app::get('sysmaker')->model('account')->count(array('seller_id'=>$sellerId));
            if( !$num )
            {
                throw new \LogicException(app::get('sysmaker')->_('数据异常，请联系客服'));
            }
        }

        return ['sellerId'=>$sellerId, 'loginAccount'=>trim($loginAccount)];
    }

    /**
     * 验证登录的用户名和密码是否一致
     * @param $loginName
     * @param $password
     * @return mixed
     */
    private function __verifyLogin($loginName, $password )
    {
        if( empty($loginName) )
        {
            pamAccount::setLoginErrorCount();
            throw new \LogicException(app::get('sysmaker')->_('请输入账号'));
        }

        //输入错误的账号，则直接返回错误
        try
        {
            $this->checkSignupAccount(trim($loginName), false);
        }
        catch( LogicException $e )
        {
            pamAccount::setLoginErrorCount();
            throw new \LogicException(app::get('sysmaker')->_('用户名或密码错误'));
        }

        if( empty($password) )
        {
            pamAccount::setLoginErrorCount();
            throw new \LogicException(app::get('sysmaker')->_('请输入密码'));
        }

        $filter = array('login_account'=>trim($loginName),'disabled'=>'0');
        $account = app::get('sysmaker')->model('account')->getRow('seller_id,login_password',$filter);

        if(!$account || !hash::check($password, $account['login_password']))
        {
            pamAccount::setLoginErrorCount();
            throw new \LogicException(app::get('sysmaker')->_('用户名或密码错误'));
        }

        return $account['seller_id'];
    }

    /**
     * 验证传入账号的合法性
     * @param $loginName
     * @param bool $checkIsExists
     * @return bool
     */
    public function checkSignupAccount($loginName, $checkIsExists=true)
    {
        if( empty($loginName) )
        {
            throw new \LogicException(app::get('sysmaker')->_('请输入用户名'));
        }

        if( mb_strlen(trim($loginName)) < 4 )
        {
            throw new \LogicException(app::get('sysmaker')->_('登录账号最少4个字'));
        }
        else if( mb_strlen(trim($loginName)) > 20 )
        {
            throw new \LogicException(app::get('sysmaker')->_('登录账号过长，请换一个重试'));
        }

        if(!preg_match('/^[^\x00-\x2d^\x2f^\x3a-\x3f]+$/i', trim($loginName)) )
        {
            throw new \LogicException(app::get('sysmaker')->_('该登录账号包含非法字符'));
        }

        //判断账号是否存在
        if( $checkIsExists && $this->isExists($loginName,'mobile') )
        {
            throw new \LogicException(app::get('sysmaker')->_('该账号已经被占用，请换一个重试'));
        }

        return true;
    }

    /**
     * 创客注册
     * @param $data
     * @return mixed
     */
    public function signUp($data)
    {
        //检查数据安全
        $data = utils::_filter_input($data);

        //检查密码合法，是否一致
        $this->checkPassport($data['login_password'],$data['psw_confirm']);

        $objSeller = kernel::single('sysmaker_data_seller');
        $data['login_account'] = $data['mobile'];
        $sellerId = $objSeller->saveSeller($data,$msg);

        pamAccount::setSession($sellerId, $data['login_account']);

        return $sellerId;
    }

    /**
     * 创客密码修改
     * @param $data 商家密码
     * @return bool
     */
    public function modifyPwd($data)
    {
        //检查数据安全
        $data = utils::_filter_input($data);
        $accountModel = app::get('sysmaker')->model('account');
        $filter = array('seller_id'=>pamAccount::getAccountId());
        $account = $accountModel->getRow('seller_id,login_password',$filter);

        if( !$account ) return false;

        //检查密码合法，是否一致
        $this->checkPassport($data['login_password'],$data['psw_confirm']);

        if(!hash::check($data['login_password_old'], $account['login_password']))
        {
            throw new \LogicException(app::get('sysmaker')->_('原密码填写错误，请重新填写!'));
        }

        $pamMakerData['login_password'] = hash::make($data['login_password']);
        $pamMakerData['seller_id'] = $filter['seller_id'];
        $pamMakerData['modified_time'] = time();
        if( !$sellerId = $accountModel->save($pamMakerData) )
        {
            throw new \LogicException(app::get('sysmaker')->_('修改失败'));
        }
        return true;
    }

    /**
     * 检查密码是否合法，密码是否一致(注册，找回密码，修改密码)调用
     * @param $password     密码
     * @param $psw_confirm  确认密码
     * @return bool
     */
    public function checkPassport($password, $psw_confirm){
        $passwdlen = strlen( trim($password) );
        if($passwdlen<6)
        {
            $msg = $this->app->_('密码长度不能小于6位');
            throw new \LogicException($msg);
        }

        if($passwdlen>20)
        {
            $msg = $this->app->_('密码长度不能大于20位');
            throw new \LogicException($msg);
        }

        if(preg_match("/^[a-z]*$/i", trim($password)) )
        {
            $msg = $this->app->_('密码不能为纯字母');
            throw new \LogicException($msg);
        }

        if(preg_match("/^[0-9]*$/i", trim($password)) )
        {
            $msg = $this->app->_('密码不能为纯数字');
            throw new \LogicException($msg);
        }

        if($password != $psw_confirm)
        {
            $msg = $this->app->_('输入的密码不一致');
            throw new \LogicException($msg);
        }

        return true;
    }

    /**
     * @brief 判断注册信息账号，手机号，邮箱是否已近注册
     *
     * @param string $str 验证字符串
     * @param string $type 验证类型 账号，手机号
     *
     * @return bool true已存在 | false不存在
     */
    public function isExists($str, $type='account')
    {
        //检查数据安全
        $str = utils::_filter_input($str);

        if(empty($str)) return false;

        switch($type)
        {
            case 'account':
                $accountShopModel = app::get('sysmaker')->model('account');
                $data = $accountShopModel->getRow('seller_id',array('login_account'=>trim($str)));
                break;
            case 'mobile':
                $sysshopModel = app::get('sysmaker')->model('seller');
                $data = $sysshopModel->getRow('seller_id',array('mobile'=>trim($str)));
                break;
        }
        return $data['seller_id'] ? true : false;
    }

    /**
     * 验证是否手机号
     * @param $string
     * @return bool
     */
    private function __isMobile($string)
    {
        if(preg_match("/^1[34578]{1}\d{9}$/",$string)){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 记录创客登录日志
     * @return bool
     * tip: 暂不记录日志 2018-11-14
     */
    protected final function accountloginlog()
    {
        $queue_params = array(
            'seller_id'   => pamAccount::getAccountId(),
            'login_account' => pamAccount::getLoginName(),
            'login_time'    => time(),
            'ip'              => request::getClientIp(),
        );
        
        //暂不记录日志 xinyufeng
        //return system_queue::instance()->publish('sysmaker_tasks_accountloginlog', 'sysmaker_tasks_accountloginlog', $queue_params);
    }
}