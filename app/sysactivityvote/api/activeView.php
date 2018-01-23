<?php
/**
 * Created by PhpStorm.
 * User: xinyufeng
 * Date: 2017/11/21
 * Time: 15:44
 */
class sysactivityvote_api_activeView {
    public $apiDescription = "增加投票活动浏览量";

    public function getParams()
    {
        $return['params'] = array(
            'shop_id' => ['type'=>'int','valid'=>'','desc'=>'店铺id','default'=>'','example'=>'1'],
            'active_id' => ['type'=>'int','valid'=>'required','example'=>'1','desc'=>'投票活动id', 'msg'=>'活动id是必填字段'],
            'view_number' => ['type'=>'int','valid'=>'','default'=>'1','example'=>'1','desc'=>'浏览次数'],
        );

        return $return;
    }

    public function addView($params)
    {
        $params['view_number'] = empty($params['view_number']) ? 1 : $params['view_number'];
        $db = app::get('sysactivityvote')->database();
        $sql = "UPDATE sysactivityvote_active SET active_view = active_view + {$params['view_number']} WHERE active_id = '{$params['active_id']}'";
        if($params['shop_id']){
            $sql .= " AND shop_id = '{$params['shop_id']}'";
        }

        $res = $db->executeUpdate($sql);
        //成功返回1 失败返回0
        return $res;
    }
}