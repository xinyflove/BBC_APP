<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2016/12/12
 * Time: 18:04
 * 员工添加和更新
 */
class sysinneruser_data_users {
    // 等级model
    public $usersModel = null;

    /**
     * sysinneruser_data_users constructor.
     */
    public function __construct()
    {
        $this->usersModel = app::get('sysinneruser')->model('users');
    }

    /**
     * 等级添加
     * @param $data
     * @param $msg
     * @return bool
     */
    public function add($data, &$msg)
    {
        if( !$this->__check($data,$msg) )  return false;

        $data['create_time'] = time();
        $data['modified_time'] = $data['create_time'];

        $userId = $this->usersModel->insert($data);
        if( !$userId )
        {
            $msg = app::get('sysinneruser')->_('员工添加失败');
            return false;
        }

        $msg = app::get('sysinneruser')->_('员工添加成功');

        return $userId;
    }

    /**
     * 等级更新
     * @param $data
     * @param $msg
     * @return bool
     */
    public function update($data, &$msg)
    {
        if( empty( $data['user_id'] ) )
        {
            $msg = app::get('sysinneruser')->_('参数错误');
            return false;
        }

        if( !$this->__check($data,$msg) ) return false;

        $data['modified_time'] = time();

        //更新属性
        if( !$this->usersModel->update($data, array('user_id'=>$data['user_id'])) )
        {
            $msg = app::get('sysinneruser')->_('员工更新失败');
            return false;
        }
        $msg = app::get('sysinneruser')->_('员工更新成功');

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
        if( empty( $data['phone']) )
        {
            $msg = app::get('sysinneruser')->_('请添加员工手机号');
            return false;
        }

        if( $data['level_id'] != 0 && empty( $data['level_id']) )
        {
            $msg = app::get('sysinneruser')->_('请选择等级');
            return false;
        }
		
		if( empty( $data['valid_begin']) )
        {
            $msg = app::get('sysinneruser')->_('请添加有效期开始日期');
            return false;
        }

        if( empty( $data['valid_over']) )
        {
            $msg = app::get('sysinneruser')->_('请添加有效期结束日期');
            return false;
        }

        return true;
    }
}
