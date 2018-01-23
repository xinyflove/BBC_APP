<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/4
 * Time: 15:43
 * Desc: 商城管理员 登录、注册流程
 */
class sysstore_passport {
    
    public $accountId = null;

    public $accountName = null;

    public function __construct()
    {
        $this->app = app::get('sysstore');
        kernel::single('base_session')->start();

        pamAccount::setAuthType('sysstore');//设置权限类型
        $this->accountId = pamAccount::getAccountId();
    }

    /**
     * 获取登录会员的信息
     * @param string $accountId
     * @return array
     */
    public function getAccountData($accountId=0)
    {
        if( !$this->accountData )
        {
            if(!$accountId)
            {
                $accountId = pamAccount::getAccountId();//获取当前登录用户id
            }

            if(!$accountId)
            {
                $this->accountData = NULL;
            }
            else
            {
                $accountModel = app::get('sysstore')->model('account');
                $this->accountData = $accountModel->getRow('*',array('account_id'=>$accountId));
            }
        }
        return $this->accountData;
    }

    /**
     * 获取当前用户的路由权限
     * @return array|bool
     */
    public function getAccountPermission()
    {
        $permissionData = [];//用户权限
        if( $this->accountId )
        {
            //处理登录用户权限代码
            return false;//店主不需要判断权限，有所有权限
        }

        $commonPermissionData = config::get('storepermission.common.permission');//公共权限
        $permissionData = array_merge($permissionData, $commonPermissionData);//合并权限
        
        return $permissionData;
    }

    /**
     * 商城登录
     * @param $loginAccount
     * @param $loginPassword
     * @return bool
     */
    public function login($loginAccount, $loginPassword)
    {
        $account = $this->apiLogin($loginAccount, $loginPassword);
        pamAccount::setSession($account['accountId'], $account['loginAccount']);
        $this->accountloginlog();
        return true;
    }

    /**
     * 商城登录
     * @param $loginAccount
     * @param $loginPassword
     * @return array
     */
    public function apiLogin($loginAccount, $loginPassword)
    {
        //检查数据安全
        $loginAccount = utils::_filter_input($loginAccount);
        $loginPassword = utils::_filter_input($loginPassword);

        $accountId = $this->__verifyLogin($loginAccount, $loginPassword);
        if( $accountId )
        {
            $num = app::get('sysstore')->model('account')->count(array('account_id'=>$accountId));
            if( !$num )
            {
                throw new \LogicException(app::get('sysstore')->_('数据异常，请联系客服'));
            }
        }

        return ['accountId'=>$accountId, 'loginAccount'=>trim($loginAccount)];
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
            throw new \LogicException(app::get('sysstore')->_('请输入账号'));
        }

        //输入错误的账号，则直接返回错误
        try
        {
            $this->checkSignupAccount(trim($loginName), false);
        }
        catch( LogicException $e )
        {
            pamAccount::setLoginErrorCount();
            throw new \LogicException(app::get('sysstore')->_('用户名或密码错误'));
        }

        if( empty($password) )
        {
            pamAccount::setLoginErrorCount();
            throw new \LogicException(app::get('sysstore')->_('请输入密码'));
        }

        $filter = array('login_account'=>trim($loginName),'disabled'=>'0');
        $account = app::get('sysstore')->model('account')->getRow('account_id,login_password',$filter);

        if(!$account || !hash::check($password, $account['login_password']))
        {
            pamAccount::setLoginErrorCount();
            throw new \LogicException(app::get('sysstore')->_('用户名或密码错误'));
        }

        return $account['account_id'];
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
     * 验证传入账号的合法性
     * @param $loginName
     * @param bool $checkIsExists
     * @return bool
     */
    public function checkSignupAccount($loginName, $checkIsExists=true)
    {
        if( empty($loginName) )
        {
            throw new \LogicException(app::get('sysstore')->_('请输入用户名'));
        }

        if( mb_strlen(trim($loginName)) < 4 )
        {
            throw new \LogicException(app::get('sysstore')->_('登录账号最少4个字'));
        }
        else if( mb_strlen(trim($loginName)) > 20 )
        {
            throw new \LogicException(app::get('sysstore')->_('登录账号过长，请换一个重试'));
        }

        if( is_numeric($loginName) )
        {
            throw new \LogicException(app::get('sysstore')->_('登录账号不能全为数字'));
        }

        if(!preg_match('/^[^\x00-\x2d^\x2f^\x3a-\x3f]+$/i', trim($loginName)) )
        {
            throw new \LogicException(app::get('sysstore')->_('该登录账号包含非法字符'));
        }

        //判断账号是否存在
        if( $checkIsExists && $this->isExists($loginName,'account') )
        {
            throw new \LogicException(app::get('sysstore')->_('该账号已经被占用，请换一个重试'));
        }

        return true;
    }

    /**
     * 商家密码修改
     * @param $data 商家密码
     * @return bool
     */
    public function modifyPwd($data)
    {
        //检查数据安全
        $data = utils::_filter_input($data);
        $accountModel = app::get('sysstore')->model('account');
        $filter = array('account_id'=>pamAccount::getAccountId());
        $account = $accountModel->getRow('account_id,login_password',$filter);

        if( !$account ) return false;

        //检查密码合法，是否一致
        $this->checkPassport($data['login_password'],$data['psw_confirm']);

        if(!hash::check($data['login_password_old'], $account['login_password']))
        {
            throw new \LogicException(app::get('sysstore')->_('原密码填写错误，请重新填写!'));
        }

        $pamStoreData['login_password'] = hash::make($data['login_password']);
        $pamStoreData['account_id'] = $filter['account_id'];
        $pamStoreData['modified_time'] = time();
        if( !$accountId = $accountModel->save($pamStoreData) )
        {
            throw new \LogicException(app::get('sysstore')->_('修改失败'));
        }
        return true;
    }
    
    /**
     * 记录商家登录日志
     * @return bool
     * tip: 暂不记录日志 2018-01-06
     */
    protected final function accountloginlog()
    {

        if(!$this->storeId)
        {
            $account = app::get('sysstore')->model('account')
                ->getRow('store_id', array('account_id'=>pamAccount::getAccountId()));
            $storeId = $account['store_id'] ? $account['store_id'] : 0;
        }
        else
        {
            $storeId = $this->storeId;
        }
        $queue_params = array(
            'account_id'   => pamAccount::getAccountId(),
            'login_account' => pamAccount::getLoginName(),
            'store_id'         => $storeId,
            'login_time'    => time(),
            'ip'              => request::getClientIp(),
        );
        
        //暂不记录日志 xinyufeng
        //return system_queue::instance()->publish('sysstore_tasks_accountloginlog', 'sysstore_tasks_accountloginlog', $queue_params);
    }
}