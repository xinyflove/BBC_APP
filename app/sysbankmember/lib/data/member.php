<?php
/**
 * Created by PhpStorm.
 * member: Caffrey
 * Date: 2017/09/04
 * Time: 09:36
 * 基础卡号添加、更新和删除
 */
class sysbankmember_data_member {
    // 基础卡号model
    public $memberModel = null;

    /**
     * sysbankmember_data_member constructor.
     */
    public function __construct()
    {
        $this->memberModel = app::get('sysbankmember')->model('member');
    }

    /**
     * 基础卡号添加
     * @param $data
     * @param $msg
     * @return bool
     */
    public function add($data, &$msg)
    {
        if( !$this->__check($data,$msg) )  return false;

        $data['create_time'] = time();
        $data['modified_time'] = $data['create_time'];

        $memberId = $this->memberModel->insert($data);
        if( !$memberId )
        {
            $msg = app::get('sysbankmember')->_('基础卡号添加失败');
            return false;
        }

        $msg = app::get('sysbankmember')->_('基础卡号添加成功');

        return $memberId;
    }

    /**
     * 基础卡号更新
     * @param $data
     * @param $msg
     * @return bool
     */
    public function update($data, &$msg)
    {
        if( empty( $data['member_id'] ) )
        {
            $msg = app::get('sysbankmember')->_('参数错误');
            return false;
        }

        if( !$this->__check($data,$msg) ) return false;

        $data['modified_time'] = time();

        //更新属性
        if( !$this->memberModel->update($data, array('member_id'=>$data['member_id'])) )
        {
            $msg = app::get('sysbankmember')->_('基础卡号更新失败');
            return false;
        }
        $msg = app::get('sysbankmember')->_('基础卡号更新成功');

        return true;
    }

    /**
     * 基础卡号删除
     * @param $memberId
     * @return bool
     */
    public function delete($memberId)
    {
        $relAccount = $this->__checkBindingMember($memberId);
        if($relAccount)
        {
            $msg = app::get('sysbankmember')->_('基础卡号已经与会员关联，不可删除');
            throw new \LogicException($msg);
            return false;
        }

        $delete = $this->memberModel->delete(array('member_id'=>$memberId));
        if(!$delete)
        {
            $msg = app::get('sysbankmember')->_('基础卡号删除失败');
            throw new \LogicException($msg);
            return false;
        }
        return true;
    }

    /**
     * 会员绑定银行卡
     * @param $member_id
     * @param $mobile
     * @param $msg
     * @return bool
     */
    public function bindCardNumber($member_id, $mobile, $card_number, $rel_name, &$msg){
        if( empty($member_id) || empty($mobile) || empty($card_number))
        {
            $msg = app::get('sysbankmember')->_('参数错误');
            return false;
        }

        if(strlen($mobile) != 11){
            $msg = app::get('sysbankmember')->_('请输入11位手机号');
            return false;
        }

        if(strlen($card_number) != 16){
            $msg = app::get('sysbankmember')->_('请输入16位银行卡号');
            return false;
        }

        $objMember = kernel::single('sysbankmember_member');
        if(!$objMember->isBaseCardNumber($card_number)){
            $msg = app::get('sysbankmember')->_('银行卡号不匹配基础卡号');
            return false;
        }

        $userInfo = app::get('sysuser')->model('account')->getRow('user_id', array('mobile'=>$mobile));
        if(empty($userInfo)){
            $msg = app::get('sysbankmember')->_('手机号未注册会员');
            return false;
        }

        $data = array();
        $data['member_id'] = $member_id;
        $data['user_id'] = $userInfo['user_id'];
        $data['card_number'] = $card_number;
        $data['rel_name'] = $rel_name;
        $data['bind_time'] = time();

        $flag = kernel::single('sysbankmember_data_account')->bindCardNumber($data, $msg);
        if(!$flag) return false;
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
        if( empty( $data['shop_id']) )
        {
            $msg = app::get('sysbankmember')->_('请选择店铺');
            return false;
        }

        if( empty( $data['bank_id']) )
        {
            $msg = app::get('sysbankmember')->_('请选择银行');
            return false;
        }
		
		if( empty( $data['card_number']) )
        {
            $msg = app::get('sysbankmember')->_('请填写卡号');
            return false;
        }

        return true;
    }

    /**
     * 检查会员是否绑定了银行信息
     * @param $bankId
     * @return mixed
     */
    private function __checkBindingMember($memberId)
    {
        $objMdlAccount = app::get('sysbankmember')->model('account');
        $relprops = $objMdlAccount->getList('account_id',array('member_id'=>$memberId));
        return $relprops;
    }
}
