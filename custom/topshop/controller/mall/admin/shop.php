<?php
/**
 * User: xinyufeng
 * Time: 2018-10-26 10:30
 * Desc: 广电优选店铺页面配置
 */
class topshop_ctl_mall_admin_shop extends topshop_controller {

    public function setting()
    {
        $pagedata = [];
        $this->contentHeaderTitle = app::get('topshop')->_('店铺首页配置');
        return $this->page('topshop/mall/admin/setting.html', $pagedata);
    }
}