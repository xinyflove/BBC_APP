<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/6
 * Time: 17:29
 */
class topstore_ctl_store_setting extends topstore_controller {

    public function index()
    {
        $storedata = app::get('sysstore')->model('store')->getRow('*', array('store_id'=>$this->storeId));
        $pagedata['store'] = $storedata;
        $this->contentHeaderTitle = app::get('topstore')->_('商城基本配置');
        //echo '<pre>';var_dump($pagedata);die;
        return $this->page('topstore/store/setting.html', $pagedata);
    }
}