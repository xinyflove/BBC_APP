<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-15
 * Desc: 创客信任登录配置model
 */
class sysmaker_mdl_trustlogin_cfg extends dbeav_model {

    public function __construct()
    {
        $this->columns = array(
            'name' => array('label'=>app::get('sysmaker')->_('名称'),'width'=>200),
            'version' => array('label'=>app::get('sysmaker')->_('版本'),'width'=>200),
            'status' =>  array('type' => 'bool' ,'label'=>app::get('sysmaker')->_('状态'),'width'=>200),
            'platform'=>array(
                    'type' =>array (
                        'web' => app::get('sysmaker')->_('标准版'),
                        'wap' => app::get('sysmaker')->_('触屏版'),
                    ),
                    'default' => 'web',
                    'label'=>app::get('ectools')->_('支持平台'),
                    'width'=>120,
                ),
        );

        $this->schema = array(
                'default_in_list'=>['name','version','status'],
                'in_list'=>['name','version','status'],
                'idColumn'=>'app_name',
                'columns'=>$this->columns
            );
    }

     /**
     * suffix of model
     * @params null
     * @return string table name
     */
    public function table_name()
    {
        return 'trustlogin_cfg';
    }

    /**
     * 数据结构
     * @return array
     */
    function get_schema()
    {
        return $this->schema;
    }
    
    /**
     * 返回接口的数量
     * @param string $filter
     * @return int
     */
    function count($filter='')
    {
        return count($this->getList('*',array('platform'=>$filter['platform'])));
    }
    
    /**
     * 取到服务列表 - 1条或者多条
     * @param string $cols      特殊的列名
     * @param array $filter     限制条件
     * @param int $offset       偏移量起始值
     * @param int $limit        偏移位移值
     * @param null $orderby     排序条件
     * @return array
     */
    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=null)
    {
        $trustInfos = [];
        $trustCollection = collect(kernel::single('sysmaker_passport_trust_manager')->getTrusts());
        $trustCollection->each(function($trust) use (&$trustInfos) {
            $trustInfos[] = $trust->getInfo();
        });
        
        //添加判断pc和wap端的
        foreach ($trustInfos as $key => $value)
        {
            if(!in_array($filter['platform'],$value['platform']))
            {
                unset($trustInfos[$key]);
            }
        }

        return $trustInfos;
    }
}
