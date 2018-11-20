<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-14
 * Desc: 创客微信端登录回调
 */
class topmaker_ctl_trustlogin extends topmaker_controller {

    /**
     * 注册回调函数
     * @return mixed
     */
    public function callbackSignUp()
    {
        $thirdData = $this->_getThirdData($msg);
        if(!$thirdData)
        {
            throw new \LogicException($msg);
            exit;
        }
        
        return redirect::action('topmaker_ctl_passport@signup', $thirdData);
    }

    /**
     * 登录回调函数
     * @return mixed
     */
    public function callbackSignIn()
    {
        $thirdData = $this->_getThirdData($msg);
        if(!$thirdData)
        {
            throw new \LogicException($msg);
            exit;
        }

        $objTrustinfo = kernel::single('sysmaker_data_trustinfo');
        $filter = array(
            'user_flag' => $thirdData['userflag'],
            'flag' => $thirdData['flag'],
        );
        // 信任登录信息
        $trustInfo = $objTrustinfo->getTrustInfoData('seller_id', $filter);
        if($trustInfo)// 允许信任登录
        {
            // 登录用户帐号信息
            $sellerInfo = app::get('sysmaker')->model('account')
                ->getRow('login_account', array('seller_id'=>$trustInfo['seller_id']));
            if($sellerInfo)
            {
                pamAccount::setSession($trustInfo['seller_id'], $sellerInfo['login_account']);
                return redirect::action('topmaker_ctl_passport@signin');
            }

        }
        
        return redirect::action('topmaker_ctl_passport@signin', $thirdData);
    }

    /**
     * 获取第三方数据
     * @return array
     */
    protected function _getThirdData(&$msg)
    {
        $params = input::get();//微信跳转回来的参数

        $makerTrust = kernel::single('pam_trust_maker');
        $trustManager = kernel::single('sysmaker_passport_trust_manager');
        $trust = $trustManager->getTrustObjectByFlag($params['flag']);

        // 信任登陆callback认证
        $statue = $makerTrust->getStateCode();
        $userFlag = $trust->setView('wap')->authorize($statue, $params);
        if(!$userFlag)
        {
            $msg = '网页授权失败！';
            return false;
        }

        // 获取微信用户信息
        $userInfo = $trust->getUserInfo();
        if(!$userInfo)
        {
            $msg = '获取微信用户信息失败！';
            return false;
        }

        $thirdData = array(
            'userflag' => $userFlag,
            'flag' => $params['flag'],
            'openid' => $userInfo['openid'],
            'nickname' => $userInfo['nickname'],
            'sex' => $userInfo['sex'],
            'figureurl' => $userInfo['figureurl'],
            'country' => $userInfo['country'],
            'province' => $userInfo['province'],
            'city' => $userInfo['city'],
        );
        
        return $thirdData;
    }
}
