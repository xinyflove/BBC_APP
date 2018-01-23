<?php
class sysactivityvote_api_activeGet {

    public $apiDescription = "获取单条投票活动数据";

    public function getParams()
    {
        $return['params'] = array(
            'active_id' => ['type'=>'int','valid'=>'','example'=>'','desc'=>'投票活动id'],
        );

        return $return;
    }

    public function activeGet($params)
    {
        $objMdlActive = app::get('sysactivityvote')->model('active');
        $activeInfo = $objMdlActive->getRow('*',array('active_id'=>$params['active_id']));
        
        return $activeInfo;
    }
}