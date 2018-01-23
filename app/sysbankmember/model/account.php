<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2017/9/20
 * Time: 14:30
 */
class sysbankmember_mdl_account extends dbeav_model{
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

    public function _filter($filter,$tableAlias=null,$baseWhere=null)
    {
        if( is_array($filter) && $filter['username'] )
        {
            $user_ids = app::get('sysuser')->model('user')
                ->getList('user_id', array('username'=>$filter['username']));
            $user_ids = array_column($user_ids, 'user_id');
            $tmpfilter['user_id|in'] = $user_ids;
            unset($filter['username']);
        }

        if( is_array($filter) &&  $filter['mobile'] )
        {
            $user_ids = app::get('sysuser')->model('account')
                ->getList('user_id', array('mobile'=>$filter['mobile']));
            $user_ids = array_column($user_ids, 'user_id');
            $tmpfilter['user_id|in'] = $user_ids;
            unset($filter['mobile']);
        }

        if( is_array($filter) &&  $tmpfilter )
        {
            $aData = app::get('sysbankmember')->model('account')->getList('user_id',$tmpfilter);
            if($aData)
            {
                foreach($aData as $key=>$val)
                {
                    $user[$key] = $val['user_id'];
                }
                $filter['user_id'] = $user;
            }
            else
            {
                $filter['user_id'] = '-1';
            }
        }
        $filter = parent::_filter($filter);
        return $filter;
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
            'username'=>app::get('sysbankmember')->_('会员姓名'),
            'mobile'=>app::get('sysbankmember')->_('手机号'),
        ),$columns);

        return $columns;
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