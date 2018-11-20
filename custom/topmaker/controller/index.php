<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-14
 * Desc: 创客基础业务控制器
 */
class topmaker_ctl_index extends topmaker_controller {

    public function index()
    {
        $sellerId = $this->sellerId;
        $pagedata = array();
        $this->contentHeaderTitle = app::get('topmaker')->_('首页');
        
        return $this->page('topmaker/index.html', $pagedata);
    }
}