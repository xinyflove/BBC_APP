<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2017/9/20
 * Time: 10:26
 */
class sysbankmember_data_account {
    // 会员model
    public $accountModel = null;

    /**
     * sysbankmember_data_account constructor.
     */
    public function __construct()
    {
        $this->accountModel = app::get('sysbankmember')->model('account');
    }

    public function add($data, &$msg)
    {
        if( !$this->__check($data,$msg) )  return false;

        $data['create_time'] = time();
        $data['modified_time'] = $data['create_time'];

        $memberId = $this->accountModel->insert($data);
        if( !$memberId )
        {
            $msg = app::get('sysbankmember')->_('会员添加失败');
            return false;
        }

        $msg = app::get('sysbankmember')->_('会员添加成功');

        return $memberId;
    }

    public function update($data, &$msg)
    {
        if( empty( $data['account_id'] ) )
        {
            $msg = app::get('sysbankmember')->_('参数错误');
            return false;
        }

        if( !$this->__check($data,$msg) ) return false;

        $data['modified_time'] = time();

        //更新属性
        if( !$this->accountModel->update($data, array('account_id'=>$data['account_id'])) )
        {
            $msg = app::get('sysbankmember')->_('更新失败');
            return false;
        }
        $msg = app::get('sysbankmember')->_('更新成功');

        return true;
    }

    /**
     * 绑定
     * @param $data
     * @param $msg
     * @return bool
     */
    public function bindCardNumber($data, &$msg)
    {
        $objAccount = kernel::single('sysbankmember_account');
        if($objAccount->existCardNumber($data)){
            $msg = app::get('sysbankmember')->_('银行卡号已存在');
            return false;
        }

        $info = $this->accountModel->getRow('account_id,deleted', array('user_id'=>$data['user_id'], 'card_number'=>$data['card_number']));
        if(!empty($info)){
            if(!$info['deleted']){
                $msg = app::get('sysbankmember')->_('已绑定成功');
                return true;
            }else{
                $data['account_id'] = $info['account_id'];
                $data['deleted'] = 0;
                if(!$this->update($data, $msg)){
                    return false;
                }
            }
        }else{
            if(!$this->add($data, $msg)){
                return false;
            }
        }

        $msg = app::get('sysbankmember')->_('绑定成功');
        return true;
    }

    /**
     * 验证数据函数
     * @param $data
     * @param $msg
     * @return bool
     */
    private function __check($data, &$msg)
    {
        if( empty( $data['member_id']) )
        {
            $msg = app::get('sysbankmember')->_('请选择基础卡号');
            return false;
        }

        if( empty( $data['user_id']) )
        {
            $msg = app::get('sysbankmember')->_('请填写用户信息');
            return false;
        }

        if( empty( $data['card_number']) )
        {
            $msg = app::get('sysbankmember')->_('请填写银行卡号');
            return false;
        }

        return true;
    }
}