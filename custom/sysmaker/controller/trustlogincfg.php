<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-15
 * Desc: 创客信任登录配置
 */
class sysmaker_ctl_trustlogincfg extends desktop_controller{


    public function __construct($app)
    {
        parent::__construct($app);
        header("cache-control: no-store, no-cache, must-revalidate");
    }

    /**
     * 信任登陆finder页
     * @return string
     */
    function index()
    {
        return $this->finder('sysmaker_mdl_trustlogin_cfg', array(
            'title'=>app::get('sysmaker')->_('信任登录配置'),
            'use_buildin_recycle'=>false,
            'use_buildin_filter'=> true,
            'use_view_tab'=>true,
            'use_buildin_delete'=>false,
            'actions'=>array(
                array(
                    'label'=>app::get('sysmaker')->_('开启配置'),
                    'target'=>'dialog::{ title:\''.app::get('sysmaker')->_('信任登陆全局配置').'\', width:400, height:150}',
                    'href'=>'?app=sysmaker&ctl=trustlogincfg&act=config',
                ),
            )));
    }

    /**
     * 生成tab标签选项
     * @return array
     */
    public function _views()
    {
        $pc_filter = array('platform'=>'web');
        $wap_filter = array('platform'=>'wap');
        $show_menu = array(
            1=>array('label'=>app::get('sysmaker')->_('标准版'),'optional'=>false,'filter'=>$pc_filter),
            2=>array('label'=>app::get('sysmaker')->_('触屏版'),'optional'=>false,'filter'=>$wap_filter)
        );
        
        return $show_menu;
    }


    /**
     * 信任登陆开启配置
     * @param string $flag
     * @return string
     */
    public function config()
    {
        $config = app::get('sysmaker')->getConf('trustlogin_rule');
        $pagedata['config'] = $config;
        return $this->page('sysmaker/trust/config.html', $pagedata);
    }

    /**
     * 保存信任登陆开启配置
     *
     * @param string $flag
     * @return string
     */
    public function saveConfig()
    {
        $post = input::get();
        $config = $post['config'];
        $this->begin();
        app::get('sysmaker')->setConf('trustlogin_rule', $config);
        $this->adminlog("信任登录全局状态设置", 1);
        $this->end(true, app::get('sysmaker')->_("设置成功！"));
    }

    /**
     * 信任登陆单个配置
     *
     * @param string $flag
     * @return string
     */
    public function setting($flag)
    {
        // 获取指定信任登录配置信息
        $trust = kernel::single('sysmaker_passport_trust_manager')->getTrustObjectByFlag($flag);
        $setting = $trust->getSetting();
        $pagedata = [
            'setting' => $setting,
            'flag' => $flag];
        return $this->page('sysmaker/trust/setting.html', $pagedata);
    }

    /**
     * 保存信任登陆单个配置
     *
     * @return null
     */
    public function saveSetting()
    {
        $this->begin();
        $post = input::get();
        $setting = $post['setting'];
        $flag = $post['flag'];
        $trust = kernel::single('sysmaker_passport_trust_manager')->getTrustObjectByFlag($flag);

        $trust->setSetting($setting);

        $this->adminlog("信任登录设置[{$flag}]", 1);
        $this->end(true, app::get('sysmaker')->_("设置成功！"));
    }

}