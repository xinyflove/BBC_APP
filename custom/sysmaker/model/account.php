<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-13
 * Desc: 创客帐号表model
 */
class sysmaker_mdl_account extends dbeav_model {
    var $has_many = array();

    public $defaultOrder = array('created_time', 'DESC');

    /**
     * 构造方法
     * @param object model相应app的对象
     * @return null
     */
    public function __construct($app){
        parent::__construct($app);
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
            $orderBy = $this->defaultOrder;
        }

        $lists = parent::getList($cols, $filter, $offset, $limit, $orderBy);

        return $lists;
    }
}