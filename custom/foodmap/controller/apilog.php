<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2017/12/28
 * Time: 14:37
 */
class foodmap_ctl_apilog extends desktop_controller{
    
    public function index()
    {
        $actions = array();

        $params = array(
            'title' => app::get('foodmap')->_('调用接口日志列表'),
            'actions'=> $actions,
            'use_buildin_filter' => true,
        );

        return $this->finder('foodmap_mdl_apilog', $params);
    }
}