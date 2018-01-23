<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/09/01
 * Time: 17:40
 * 银行添加、更新和删除
 */
class sysbankmember_data_bank {
    // 银行model
    public $bankModel = null;

    /**
     * sysbankmember_data_bank constructor.
     */
    public function __construct()
    {
        $this->bankModel = app::get('sysbankmember')->model('bank');
    }

    /**
     * 银行添加
     * @param $data
     * @param $msg
     * @return bool
     */
    public function add($data, &$msg)
    {
        if( !$this->__check($data,$msg) )  return false;

        $data['create_time'] = time();
        $data['modified_time'] = $data['create_time'];
        
        $bankId = $this->bankModel->insert($data);
        if( !$bankId )
        {
            $msg = app::get('sysbankmember')->_('银行添加失败');
            return false;
        }

        $msg = app::get('sysbankmember')->_('银行添加成功');

        return $bankId;
    }

    /**
     * 银行更新
     * @param $data
     * @param $msg
     * @return bool
     */
    public function update($data, &$msg)
    {
        if( empty( $data['bank_id'] ) )
        {
            $msg = app::get('sysbankmember')->_('参数错误');
            return false;
        }

        if( !$this->__check($data,$msg) ) return false;

        $data['modified_time'] = time();

        //更新属性
        if( !$this->bankModel->update($data, array('bank_id'=>$data['bank_id'])) )
        {
            $msg = app::get('sysbankmember')->_('银行更新失败');
            return false;
        }
        $msg = app::get('sysbankmember')->_('银行更新成功');

        return true;
    }

    /**
     * 银行删除
     * @param $bankId
     * @return bool
     */
    public function delete($bankId)
    {
        //获取当前银行关联的会员
        $relMember = $this->__checkBindingBank($bankId);
        if($relMember)
        {
            $msg = app::get('sysbankmember')->_('银行已经与基础卡号关联，不可删除');
            throw new \LogicException($msg);
            return false;
        }
        $delete = $this->bankModel->delete(array('bank_id'=>$bankId));
        if(!$delete)
        {
            $msg = app::get('sysbankmember')->_('银行删除失败');
            throw new \LogicException($msg);
            return false;
        }
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
        if( empty( $data['bank_code']) )
        {
            $msg = app::get('sysbankmember')->_('请添加银行代码');
            return false;
        }

        if( empty( $data['bank_name']) )
        {
            $msg = app::get('sysbankmember')->_('请添加银行名称');
            return false;
        }

        return true;
    }

    /**
     * 检查会员是否绑定了银行信息
     * @param $bankId
     * @return mixed
     */
    private function __checkBindingBank($bankId)
    {
        $objMdlMember = app::get('sysbankmember')->model('member');
        $relprops = $objMdlMember->getList('member_id',array('bank_id'=>$bankId));
        return $relprops;
    }
}
