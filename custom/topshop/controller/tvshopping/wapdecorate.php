<?php
/**
 * 电视购物页面wap端配置挂件文件
 * @auth: xinyufeng
 */
class topshop_ctl_tvshopping_wapdecorate extends topshop_controller{

    public $obj_widgets;        //挂件对象
    public $obj_pages;          //页面对象
    public $widgets;            //挂件信息数组
    public $pages_type;         //页面类型信息数组
    public $page_type;          //当前页面类型
    public $platform = 'wap';   //平台类型

    public function __construct($app)
    {
        parent::__construct($app);
        $this->page_type = input::get('page_type','home');
        $this->obj_pages = kernel::single('topshop_tvshopping_pages');
        $this->pages_type = $this->obj_pages->pages_type[$this->platform];
        if(!array_key_exists($this->page_type, $this->pages_type))
        {
            throw new \LogicException("页面类型[{$this->page_type}]不存在.");
        }
        $this->obj_widgets = kernel::single('topshop_tvshopping_widgets');
        $this->widgets = $this->obj_widgets->widgets[$this->platform];
    }

    /**
     * 挂件列表
     * @return html
     */
    public function index()
    {
        $pagedata['page_type'] = $this->page_type;
        $pagedata['widgets'] = $this->widgets;
        $pagedata['pages_type'] = $this->pages_type;

        // 获取当前页面类型的页面挂件列表
        $list = $this->obj_widgets->getWidgetsList($this->shopId, $this->page_type, $this->platform);
        $pagedata['list'] = $list;

        $this->contentHeaderTitle = app::get('topshop')->_('电视购物wap端装修');
        return $this->page('topshop/tvshopping/wapdecorate/index.html',$pagedata);
    }

    /**
     * 挂件编辑
     * @return html
     */
    public function edit()
    {
        $input = input::get();

        $widget_type = $input['widget_type'];
        if(empty($widget_type) || !array_key_exists($widget_type, $this->widgets))
        {
            throw new \LogicException("挂件信息错误.");
        }

        $pagedata['setting']['page_type'] = $this->page_type;
        $pagedata['setting']['widgets_type'] = $widget_type;
        $title = '添加';

        if($input['widgets_id'])
        {
            $widget = $this->obj_widgets->getWidget('*', $input['widgets_id']);
            $widget_params = $widget['params'];
            unset($widget['params']);
            $pagedata['setting'] = array_merge($widget, $widget_params);
            $pagedata['params'] = $pagedata['setting']['params'];
            $title = '修改';
        }
		if($widget_type=='aditem' || $widget_type=='itemlist' || $widget_type=='itemRecom' || $widget_type=='floor'){
			//echo "<pre>";print_r($pagedata['setting']['item_id']);die();
			$item_id=array();
			foreach($pagedata['setting']['item_id'] as $k=>$v){
				$item_id[]=intval($v);
			}
			$pagedata['notEndItem'] =  json_encode($item_id,false);
			$pagedata['item_sort']=json_encode($pagedata['setting']['sort'],true);
		}
        $pagedata['widget_config'] = 'topshop/tvshopping/widgets/'.$widget_type.'/config.html';
        $pagedata['widgets'] = $this->widgets;
        $this->contentHeaderTitle = app::get('topshop')->_($title.$this->pages_type[$this->page_type].'挂件/电视购物wap端装修');
        return $this->page('topshop/tvshopping/wapdecorate/edit.html',$pagedata);
    }

    /**
     * 保存挂件
     * @return string
     */
    public function save()
    {
        $input = input::get();
        $save_data = $input['setting'];
        unset($input['setting']);
		/*add_2018/8/10_by_wanghaichao_start*/
		if($save_data['widgets_type']=='topcarousel' || $save_data['widgets_type']=='midside'){
			if(empty($input['params']['pic'][0])){
				return $this->splash('error','','至少上传一张图片!',true);
			}
		}
		/*add_2018/8/10_by_wanghaichao_end*/
		
        $save_data['params'] = $input;
        $save_data['shop_id'] = $this->shopId;
        $save_data['platform'] = $this->platform;
        $save_data['template_path'] = 'topshop/tvshopping/widgets/'.$save_data['widgets_type'].'/default.html';
        $url = url::action('topshop_ctl_tvshopping_wapdecorate@index');
        $res = $this->obj_widgets->saveWidget($save_data, $msg);
        if(!$res)
        {
            return $this->splash('error',$url,$msg,true);
        }

        return $this->splash('success',$url,$msg,true);
    }

    /**
     * 删除挂件
     * @return string
     */
    public function delete()
    {
        $widgets_id = input::get('widgets_id');
        $url = url::action('topshop_ctl_tvshopping_wapdecorate@index');

        $res = $this->obj_widgets->delWidget($widgets_id, $msg);
        if($res)
        {
            return $this->splash('success', $url, $msg, true);
        }
        return $this->splash('error', $url, $msg, true);
    }

    /**
     * 排序操作
     * @return string
     */
    public function sortOpt()
    {
        $input = input::get();
        if($input['widgets_id'] && $input['opt'])
        {
            $widgetMdl = app::get('sysshop')->model('widgets_instance');
            // 当前操作挂件信息
            $currWidgetInfo = $widgetMdl->getRow('widgets_id,order_sort', array('widgets_id'=>$input['widgets_id']));
            if($currWidgetInfo)
            {
                if($input['opt'] == 'up')
                {
                    // 上移
                    if($currWidgetInfo['order_sort'] > 1)
                    {
                        // 从第二个才能进行上移操作
                        $prev_sort = $currWidgetInfo['order_sort'] - 1;
                        // 通过上一个挂件的排序获取挂件的信息
                        $prevWidgetInfo = $widgetMdl->getRow('widgets_id,order_sort', array('order_sort'=>$prev_sort,'shop_id'=>$this->shopId, 'page_type' => $this->page_type));
                        if($prevWidgetInfo)
                        {
                            // 把当前挂件的排序给上一个挂件
                            $widgetMdl->update(array('order_sort'=>$currWidgetInfo['order_sort']), array('widgets_id'=>$prevWidgetInfo['widgets_id']));
                            // 把上一个挂件的排序给当前挂件
                            $widgetMdl->update(array('order_sort'=>$prev_sort), array('widgets_id'=>$currWidgetInfo['widgets_id']));
                        }
                    }
                }
                elseif ($input['opt'] == 'down')
                {
                    // 下移
                    $next_sort = $currWidgetInfo['order_sort'] + 1;
                    // 通过下一个挂件的排序获取挂件的信息
                    $nextWidgetInfo = $widgetMdl->getRow('widgets_id,order_sort', array('order_sort'=>$next_sort,'shop_id'=>$this->shopId, 'page_type' => $this->page_type));
                    if($nextWidgetInfo)
                    {
                        // 把当前挂件的排序给下一个挂件
                        $widgetMdl->update(array('order_sort'=>$currWidgetInfo['order_sort']), array('widgets_id'=>$nextWidgetInfo['widgets_id']));
                        // 把下一个挂件的排序给当前挂件
                        $widgetMdl->update(array('order_sort'=>$next_sort), array('widgets_id'=>$currWidgetInfo['widgets_id']));
                    }
                }

                $msg = '操作成功';
                return $this->splash('success', null, $msg, true);
            }
        }
        $msg = '操作失败';
        return $this->splash('error', null, $msg, true);
    }

    /**
     * 状态操作
     * @return string
     */
    public function setStatus()
    {
        $input = input::get();
        if(!empty($input['widgets_id']) && isset($input['status']))
        {
            $widgetMdl = app::get('sysshop')->model('widgets_instance');
            $widgetMdl->update(array('disabled'=>$input['status']), array('widgets_id'=>$input['widgets_id']));
            $msg = '操作成功';
            return $this->splash('success', null, $msg, true);
        }

        $msg = '操作失败';
        return $this->splash('error', null, $msg, true);
    }
}