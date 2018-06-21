<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2017/8/31
 * Time: 15:50
 */
class testapp_level {
    /**
     * 根据等级NAME ID，判断是否有重复数据
     * @param $levelName
     * @param int $levelId
     * @return bool 没有重复返回 false
     */
    public function isNameRepeat($levelName, $levelId = 0)
    {
        $where = " name = '{$levelName}'";
        if(!empty($levelId)){
            $where .= " and level_id != '{$levelId}'";
        }
        $sql = "select level_id from testapp_level where".$where;
        $row = app::get('base')->database()->executeQuery($sql)->fetch();

        if(empty($row)) return false;
        return true;
    }
}