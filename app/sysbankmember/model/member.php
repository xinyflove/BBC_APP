<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/09/04
 * Time: 15:26
 */
class sysbankmember_mdl_member extends dbeav_model{
    var $has_many = array();

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
     * 根据会员ID，获取对应的会员数据
     * @param $memberId
     * @return array
     */
    public function getMemberRowById($memberId, $fields = '*')
    {
        $memberInfo = $this->getRow($fields,array('member_id'=>$memberId));
        if( empty($memberInfo) ) return array();
        return $memberInfo;
    }
}