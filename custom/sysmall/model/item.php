<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/6/15
 * Time: 14:30
 */
class sysmall_mdl_item extends dbeav_model{
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
     * 重写搜索的下拉选项方法
     * @param null
     * @return null
     */
    public function searchOptions(){
        $columns = array();
        foreach($this->_columns() as $k=>$v)
        {
            if(isset($v['searchtype']) && $v['searchtype'])
            {
                $columns[$k] = $v['label'];
            }
        }

        $columns = array_merge(array(
            'title'=>app::get('sysmall')->_('商品名称'),
            'shop_name'=>app::get('sysmall')->_('店铺名称'),
        ),$columns);

        return $columns;
    }

    /**
     * 重写条件过滤
     * @param array $filter
     * @param null $tableAlias
     * @param null $baseWhere
     * @return array
     */
    public function _filter($filter,$tableAlias=null,$baseWhere=null)
    {
        //$filter['deleted'] = 0;//过来未删除的数据
        if( is_array($filter) && $filter['title'] )
        {
            $item_ids = app::get('sysitem')->model('item')->getList('item_id', array('title|has'=>$filter['title']));
            unset($filter['title']);
            if($item_ids)
            {
                $item_ids = array_column($item_ids, 'item_id');
                $filter['item_id'] = $item_ids;
            }
            else
            {
                $filter['item_id'] = -1;
            }
        }

        if( is_array($filter) && $filter['shop_name'] )
        {
            $shop_ids = app::get('sysshop')->model('shop')->getList('shop_id', array('shop_name|has'=>$filter['shop_name']));
            unset($filter['shop_name']);
            if($shop_ids)
            {
                $shop_ids = array_column($shop_ids, 'shop_id');
                $filter['shop_id'] = $shop_ids;
            }
            else
            {
                $filter['shop_id'] = -1;
            }
        }

        return parent::_filter($filter);
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


        foreach($lists as &$list){
            if(isset($list['item_id'])){
                $sql = 'select title from sysitem_item where item_id='.$list['item_id'];
                $result = app::get('base')->database()->executeQuery($sql)->fetch();
                $list['title'] = $result['title'];
            }
        }

        return $lists;
    }
}