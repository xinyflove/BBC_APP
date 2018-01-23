<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/6
 * Time: 14:26
 * Desc: 安装、更新、卸载操作
 */
class sysstore_task {

    /**
     * 安装
     */
    public function post_install()
    {
        kernel::single('base_initial', 'sysstore')->init();
        pamAccount::registerAuthType('sysstore','store',app::get('sysstore')->_('商城系统'));
    }

    /**
     * 更新
     */
    public function post_uninstall()
    {
        pamAccount::unregisterAuthType('sysstore');
    }

    /**
     * 卸载
     * @param $dbver
     */
    public function post_update($dbver)
    {
        
    }
}