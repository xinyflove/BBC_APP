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
            app::get('sysmall')->setConf('sysmall.setting.cate_permission',$_POST['cate_permission']);


            /*优选商城主持人账号是否需要审核2018-10-29 jiangyunhan start*/
            app::get('sysmall')->setConf('sysmall.setting.hosts_check',$_POST['hosts_check']);
            /*优选商城主持人账号是否需要审核2018-10-29 jiangyunhan end*/
            $this->end(true, app::get('sysmall')->_('当前配置修改成功！'));
        }
        $pagedata['goods_check'] = app::get('sysmall')->getConf('sysmall.setting.goods_check');
        $pagedata['modify_price'] = app::get('sysmall')->getConf('sysmall.setting.modify_price');
        $pagedata['cate_permission'] = app::get('sysmall')->getConf('sysmall.setting.cate_permission');
        /*优选商城主持人账号是否需要审核2018-10-29 jiangyunhan start*/
        $pagedata['hosts_check'] = app::get('sysmall')->getConf('sysmall.setting.hosts_check');
        /*优选商城主持人账号是否需要审核2018-10-29 jiangyunhan end*/
        return $this->page('sysmall/setting/index.html', $pagedata);
    }
}