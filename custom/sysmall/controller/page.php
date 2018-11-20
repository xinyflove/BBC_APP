<?php
/**
 * Date: 2018/11/5
 * Time: 8:56
 */
class sysmall_ctl_page extends desktop_controller{

    public function __construct(&$app)
    {
        $this->tmpls = kernel::single('sysmall_module_config')->tmpls;
        $this->widgets = kernel::single('sysmall_module_config')->widgets;

        parent::__construct($app);
    }
    
    public function index()
    {
        $view = input::get('view','0');
        $views = ['0'=>'index','1'=>'activityindex'];
        return $this->finder(
            'sysmall_mdl_widgets_instance',
            array(
                'title' => app::get('sysmall')->_('挂件列表'),
                'actions' => array(
                    array(
                        'label'=>app::get('sysmall')->_('添加挂件'),
                        'href'=>'?app=sysmall&ctl=page&act=edit&tmpl='.$views[$view],'target'=>'dialog::{title:\''.app::get('sysmall')->_('添加挂件').'\',width:400,height:300}'
                    ),
                ),
                'use_view_tab' => true,
                'use_buildin_filter' => true,
            )
        );
    }

    public function _views()
    {
        $objMdlTmpl = app::get('sysmall')->model('widgets_instance');
        // 修改此顺序或者key的话要同步修改本页面index方法内的$views参数值
        $sub_menu = array(
            '0'=>array('label'=>app::get('sysmall')->_('首页'),'optional'=>false,'filter'=>array('tmpl'=>'index')),
            // '1'=>array('label'=>app::get('sysmall')->_('活动首页'),'optional'=>false,'filter'=>array('tmpl'=>'activityindex')),
        );

        if(isset($_GET['optional_view'])) $sub_menu[$_GET['optional_view']]['optional'] = false;

        foreach($sub_menu as $k=>$v){
            if($v['optional']==false){
                $show_menu[$k] = $v;
                if(is_array($v['filter'])){
                    $v['filter'] = array_merge(array(),$v['filter']);
                }else{
                    $v['filter'] = array();
                }
                $show_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
                if($k==$_GET['tmpl']){
                    $show_menu[$k]['newcount'] = true;
                    $show_menu[$k]['addon'] = $objMdlTmpl->count($v['filter']);
                }
                $show_menu[$k]['href'] = '?app=sysmall&ctl=page&act=index&view='.($k).(isset($_GET['optional_view'])?'&optional_view='.$_GET['optional_view'].'&view_from=dashboard':'');
            }elseif(($_GET['view_from']=='dashboard')&&$k==$_GET['tmpl']){
                $show_menu[$k] = $v;
            }
        }

        return $show_menu;
    }

    /**
     * 添加挂件页面
     *
     * @param int $widgetsId 实例ID
     */
    public function edit($widgetsId)
    {
        $tmpl = input::get('tmpl','index');
        $pagedata['data']['tmpl']=$tmpl;//传过来的页面
        if( $widgetsId )
        {
            $pagedata['data'] = app::get('sysmall')->model('widgets_instance')->getRow('*', ['widgets_id'=>$widgetsId]);
        }
        $pagedata['tmpls'] = $this->tmpls;//模板页列表
        $pagedata['widgets'] = $this->widgets;//挂件挂件列表
        return view::make('sysmall/page/tmpl.html', $pagedata);
    }

    /**
     * 保存挂件页面
     */
    public function saveTmpl()
    {
        $this->begin();

        $post = input::get();
        if( !in_array($post['tmpl'], array_keys($this->tmpls) ) )
        {
            $this->end(false, app::get('sysmall')->_('页面不在范围内'));
        }

        if( !in_array($post['widget'], array_keys($this->widgets) ) )
        {
            $this->end(false, app::get('sysmall')->_('挂件不在范围内'));
        }
        if( !is_numeric($post['order_sort']) || $post['order_sort']<0 )
        {
            $this->end(false, app::get('sysmall')->_('排序必须为数字'));
        }
        if($post['widgets_id'])
        {
            $data['widgets_id'] = intval($post['widgets_id']);
        }
        $data['order_sort'] = intval($post['order_sort']);
        $data['tmpl'] = $post['tmpl'];
        $data['widget'] = $post['widget'];

        $objMdlWidgetsInstance = app::get('sysmall')->model('widgets_instance');

        $count = $objMdlWidgetsInstance->count(['tmpl'=>$data['tmpl']]);
        if( $count>=10 ){
            $this->end(false, app::get('sysmall')->_('每个页面最多添加10个挂件'));
        }
        $flag = $objMdlWidgetsInstance->save($data);
        $msg = $flag ? app::get('sysmall')->_('添加挂件成功') :app::get('sysmall')->_('保存挂件失败');
        $this->adminlog("添加挂件[{$data['tmpl']}:{$data['widget']}]", $flag ? 1 : 0);

        $this->end($msg);
    }
}