<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2016/12/9
 * Time: 15:26
 */
class sysinneruser_mdl_users extends dbeav_model{
    var $has_many = array(

    );

    public $defaultOrder = array('create_time',' DESC');

    /**
     * 构造方法
     * @param object model相应app的对象
     * @return null
     */
    public function __construct($app){
        parent::__construct($app);
    }

    /**
     * 根据员工ID，获取对应的员工数据
     * @param $userId
     * @return array
     */
    public function getUserRow($userId)
    {
        $userInfo = $this->getRow('*',array('user_id'=>$userId));
        if( empty($userInfo) ) return array();
        return $userInfo;
    }

    /**
     * 根据员工PHONE ID，判断是否有重复数据
     * @param $userPhone
     * @param int $userId
     * @return bool
     */
    public function isPhoneRepeat($userPhone, $userId = 0)
    {
        $where = " phone = '{$userPhone}'";
        if(!empty($userId)){
            $where .= " and user_id != '{$userId}'";
        }
        $sql = "select user_id from sysinneruser_users where".$where;
        $row = app::get('base')->database()->executeQuery($sql)->fetch();

        if(empty($row)) return false;
        return true;
    }

    /**
     * 员工删除
     * @param $userId
     * @return bool
     */
    public function doDelete($userId)
    {
        $delete = $this->delete(array('user_id'=>$userId));
        if(!$delete)
        {
            $msg = app::get('sysinneruser')->_('员工删除失败');
            throw new \LogicException($msg);
            return false;
        }
        return true;
    }

    public function getUserRowByPhone($userPhone)
    {
        $userInfo = $this->getRow('*',array('phone'=>$userPhone));
        if( empty($userInfo) ) return array();
        return $userInfo;
    }
}