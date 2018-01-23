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

    /**
     * 重写getList方法，默认按照注册时间排序
     * @param string $cols 设置要取哪些列的数据
     * @param array $filter 过滤条件,默认为array()
     * @param integer $offset 偏移量,从select出的第几条数据开始取
     * @param integer $limit 取几条数据, 默认值为-1, 取所有select出的数据
     * @param string/array $orderby 排序方式, 默认按照注册时间排序
     * @return array 二维数组, 多行数据, 每行数据对应表的以行, 所取列由$cols参数控制
     * */
    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderBy=null)
    {
        if($orderBy == null)
        {
            $orderBy = 'create_time desc';
        }
        return parent::getList($cols, $filter, $offset, $limit, $orderBy);
    }
}