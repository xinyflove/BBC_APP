<?php
/**
 * Created by PhpStorm.
 * User: CaffreyXin
 * Date: 2017/8/31
 * Time: 13:50
 */
class testapp_mdl_level extends dbeav_model{

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
     * 根据等级ID，获取对应的等级数据
     * @param $levelId
     * @return array 如果存在则返回属性和属性值数据，不存在返回空数组
     */
    public function getLevelRow($levelId)
    {
        $levelInfo = $this->getRow('*',array('level_id'=>$levelId));
        if( empty($levelInfo) ) return array();
        return $levelInfo;
    }

    /**
     * 等级删除
     * @param $levelId
     * @return bool
     */
    public function doDelete($levelId)
    {
        #获取当前等级关联的内部员工
        $relUser = $this->_checkBindinglevel($levelId);
        if($relUser)
        {
            $msg = app::get('testapp')->_('等级已经与内部员工关联，不可删除');
            throw new \LogicException($msg);
            return false;
        }
        $delete = $this->delete(array('level_id'=>$levelId));
        if(!$delete)
        {
            $msg = app::get('testapp')->_('等级删除失败');
            throw new \LogicException($msg);
            return false;
        }
        return true;
    }

    /**
     * 检查员工是否绑定了等级信息
     * @param $levelId
     * @return mixed
     */
    private function _checkBindinglevel($levelId)
    {
        $objMdlUsers = app::get('testapp')->model('users');
        $relprops = $objMdlUsers->getList('user_id',array('level_id'=>$levelId));
        return $relprops;
    }

    /**
     * 前台获取等级选项
     * @return mixed
     */
    public function getLevelOptions(){
        //$cur_time = time();
        // 获取有效期开始小于今天和有效期结束大于今天
        //$where = " where valid_begin <= '{$cur_time}' AND valid_over >= '{$cur_time}'";
        $sql = "select level_id, name from testapp_level".$where;
        $row = app::get('base')->database()->executeQuery($sql)->fetchall();
        
        return $row;
    }
    
    public function getLevelIdByLevelName($levelName){
        $levelId = 0;
        $where = " name = '{$levelName}'";
        $sql = "select level_id from testapp_level where".$where;
        $row = app::get('base')->database()->executeQuery($sql)->fetch();

        if(!empty($row['level_id']))
        {
            $levelId = $row['level_id'];
        }
        
        return $levelId;
    }
}