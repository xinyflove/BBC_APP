<?php
/**
 * Created by PhpStorm.
 * member: Caffrey
 * Date: 2017/09/04
 * Time: 09:36
 * 会员添加、更新和删除
 */
class sysbankmember_data_member {
    // 会员model
    public $memberModel = null;

    /**
     * sysbankmember_data_member constructor.
     */
    public function __construct()
    {
        $this->memberModel = app::get('sysbankmember')->model('member');
    }

    /**
     * 会员添加
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
            $msg = app::get('sysbankmember')->_('会员添加失败');
            return false;
        }

        $msg = app::get('sysbankmember')->_('会员添加成功');

        return $memberId;
    }

    /**
     * 会员更新
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
            $msg = app::get('sysbankmember')->_('会员更新失败');
            return false;
        }
        $msg = app::get('sysbankmember')->_('会员更新成功');

        return true;
    }

    /**
     * 会员删除
     * @param $memberId
     * @return bool
     */
    public function delete($memberId)
    {
        $delete = $this->memberModel->delete(array('member_id'=>$memberId));
        if(!$delete)
        {
            $msg = app::get('sysbankmember')->_('会员删除失败');
            throw new \LogicException($msg);
            return false;
        }
        return true;
    }

    /**
     * 会员绑定银行卡
     * @param $member_id
     * @param $user_id
     * @param $msg
     * @return bool
     */
    public function bindCardNumber($member_id, $user_id, &$msg){
        if( empty($member_id) ||  empty($user_id))
        {
            $msg = app::get('sysbankmember')->_('参数错误');
            return false;
        }

        $data['user_id'] = $user_id;
        //有会员id添加绑定时间
        $data['bind_time'] = time();

        //更新属性
        if( !$this->memberModel->update($data, array('member_id'=>$member_id)) )
        {
            $msg = app::get('sysbankmember')->_('绑定失败');
            return false;
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
}
