<?php
/**
 * 挂件实例控制器
 * @auth xinyufeng
 * @time 2018-11-05
 */

class sysmall_ctl_widgets extends desktop_controller {

    public function __construct(&$app)
    {
        $this->tmpls = kernel::single('sysmall_module_config')->tmpls;// 页面类型
        $this->widgets = kernel::single('sysmall_module_config')->widgets;// 挂件类型

        parent::__construct($app);
    }

    /**
     * 挂件编辑页面
     * @param $widgetsId
     * @return mixed
     */
    public function edit_widgets($widgetsId)
    {
        $objMdlWidgetsInstance = app::get('sysmall')->model('widgets_instance');
        $winfo = $objMdlWidgetsInstance->getRow('*', array('widgets_id'=>$widgetsId));
        $pagedata['setting'] = $winfo['params'] ;
        $pagedata['_PAGE_'] = 'sysmall/widgets/'.$winfo['widget'].'/_config.html';
        $pagedata['widgets_id'] = $widgetsId;
        $pagedata['widget'] = $winfo['widget'];

        return view::make('sysmall/widgets/edit.html', $pagedata);
    }

    /**
     * 挂件保存
     */
    public function save_widgets()
    {
        $postdata = input::get();
        $widgetsId = input::get('widgets_id');
        $widgetFunc = input::get('widget');
        unset($postdata['app']);
        unset($postdata['ctl']);
        unset($postdata['act']);
        unset($postdata['widgets_id']);
        unset($postdata['widget']);
        $this->begin("?app=sysmall&ctl=page&act=index");
        try
        {
            //挂件配置保存
            $objMdlWidgetsInstance = app::get('sysmall')->model('widgets_instance');
            if(method_exists($this, $widgetFunc))
            {
                $postdata = call_user_func([$this, $widgetFunc], $postdata);
            }
            $flag = $objMdlWidgetsInstance->update( ['params'=>$postdata], ['widgets_id'=>$widgetsId] );
            if(!$flag)
            {
                throw new \LogicException(app::get('sysmall')->_('配置挂件失败'));
            }
        }
        catch(\Exception $e)
        {
            $msg = $e->getMessage();
            $this->end(false, $msg);
        }

        $this->adminlog("配置挂件[widgets_id:{$widgetsId}]", 1);
        $this->end('true');
    }
}
