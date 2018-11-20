<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-19
 * Desc: 错误处理
 */
class topmaker_ctl_error extends topmaker_controller {

    public function index()
    {
        
        return $this->page('topmaker/error/error.html', []);
    }

}