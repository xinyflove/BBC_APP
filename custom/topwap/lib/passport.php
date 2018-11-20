<?php
class topwap_passport{

    //前端注册验证码的发送
    public function sendVcode($account,$sendType)
    {
        if(!$account)
        {
            throw new \LogicException(app::get('topwap')->_('请填写正确的手机号码'));
        }

        if(!$sendType)
        {
            throw new \LogicException(app::get('topwap')->_('参数错误'));
        }
        $type = app::get('topwap')->rpcCall('user.get.account.type',array('user_name'=>$account),'buyer');
        if($type=='mobile')
        {
            $noticeType = '手机号码';
        }
        else
        {
            throw new \LogicException(app::get('topwap')->_('请填写正确的手机号码'));
        }
        $data = userAuth::getAccountInfo($account);
        $userId = userAuth::id();

        switch($type)
        {
        case "mobile":
            if( !userVcode::send_sms($sendType,$account) )
            {
                throw new \LogicException(app::get('topwap')->_('验证码发送失败'));
            }
            break;
        default:
            throw new \LogicException(app::get('topwap')->_('数据格式错误!'));
            break;
        }
        return true;
    }


    public function sendEmailVcode($sendType,$account)
    {
        $userId = userAuth::id();
        $resetUrl = url::action("topwap_ctl_member@bindEmail",array('uname'=>$account,'type'=>$sendType,'verify'=>md5($userId)));
        $unresetUrl = url::action("topwap_ctl_member@unVerifyEmail",array('uname'=>$account,'type'=>$sendType,'verify'=>md5($userId)));
        switch($sendType)
        {
        case 'activation':
        case 'reset':
            $content = url::action("topwap_ctl_member@bindEmail",array('uname'=>$account,'type'=>$sendType,'verify'=>md5($userId),'next_page'=>$resetUrl));
            break;
        case 'unreset':
            $content = url::action("topwap_ctl_member@unVerifyEmail",array('uname'=>$account,'type'=>$sendType,'verify'=>md5($userId),'next_page'=>$unresetUrl));
        break;
        case 'forgot':
        case 'signup': //手机注册
            $content = url::action("topwap_ctl_passport@findPwdThree",array('uname'=>$account,'type'=>$sendType));
            break;
        case 'depost_forgot'://支付密码忘记密码
            $content = url::action("topwap_ctl_member_deposit@forgetPasswordSetPassword",array('uname'=>$account,'type'=>$sendType));
            break;
        }

        if(!userVcode::send_email($sendType,$account,$content))
        {
            return false;
        }
        return true;
    }

    /**
     * 单纯获得第三方用户信息功能使用
     * @Author   王衍生
     * @DateTime 2017-09-23T16:37:53+0800
     * @return   [type]                   [description]
     */
    public function getThirdpartyInfo($callback_url)
    {
        kernel::single('base_session')->start();

        if ($_SESSION['thirdparty_validated'] == true)
        {
            return true;
        }

        if(kernel::single('topwap_wechat_wechat')->from_weixin())
        {
            $_SESSION['next_page'] = $callback_url;

            $weixintrustInfo = kernel::single('pam_trust_user')->getTrustInfoRow('wap', 'topwap_ctl_trustlogin@thirdpartyCallback','wapweixin');

            if($weixintrustInfo)
            {
                echo "<script>location.href = '{$weixintrustInfo['url']}';</script>";
                exit;
            }
        }
    }
}
