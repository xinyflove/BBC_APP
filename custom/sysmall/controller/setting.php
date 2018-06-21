<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/6/15
 * Time: 10:25
 */
class sysmall_ctl_setting extends desktop_controller{

    /**
     * 商城设置
     * @return mixed
     */
    public function index()
    {
        if( $_POST )
        {
            $this->begin();
            app::get('sysmall')->setConf('sysmall.setting.goods_check',$_POST['goods_check']);
            app::get('sysmall')->setConf('sysmall.setting.modify_price',$_POST['modify_price']);
            $this->end(true, app::get('sysmall')->_('当前配置修改成功！'));
        }
        $pagedata['goods_check'] = app::get('sysmall')->getConf('sysmall.setting.goods_check');
        $pagedata['modify_price'] = app::get('sysmall')->getConf('sysmall.setting.modify_price');

        return $this->page('sysmall/setting/index.html', $pagedata);
    }
}