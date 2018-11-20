<?php
/**
 * Created by PhpStorm.
 * User: jiangyunhan
 * Date: 2018-05-15
 * Time: 11:20
 */
class topshop_ctl_supplier_decorate extends topshop_controller{
    protected $page_type = array(
        'home' => '首页',
    );
    //线下店挂件列表
    public function index()
    {
        $pagedata = array();

        $pageTpye = input::get('page_type','home');
        $pagedata['page_type'] = $pageTpye;

        //商城挂件列表
        $widgets_res = app::get('syssupplier')->model('widgets')
            ->getList('widgets_title,widgets_name', array('platform'=>'wap','deleted'=>0));
        $widgets = array();
        foreach ($widgets_res as $v)
        {
            $widgets[$v['widgets_name']] = $v['widgets_title'];
        }
        unset($widgets_res);
        $pagedata['widgets'] = $widgets;

        //页面挂件列表
        $data = app::get('syssupplier')->model('widget_instance')
            ->getList('*', array('page_type'=>$pageTpye,'deleted'=>0,'shop_id'=>$this->shopId), 0, -1, 'order_sort ASC');
        $pagedata['widgetList'] = $data;
        $pagedata['shop_id'] = $this->shopId;
        $this->contentHeaderTitle = app::get('topshop')->_('wap端米粒儿网站装修');
        return $this->page('topshop/supplier/decorate/home.html', $pagedata);
    }
    /**
     * 编辑数据
     * @return mixed
     */
    public function edit()
    {
        $input = input::get();
        $pagedata = array();

        if(empty($input['widget_id']))
        {
            if(empty($input['widget_type']) || empty($input['page_type']))
            {
                $url = url::action('topshop_ctl_supplier_decorate@index');
                return $this->splash('error', $url, null, false);
            }

            $pagedata['widget']['widget_type'] = $input['widget_type'];
            $pagedata['widget']['page_type'] = $input['page_type'];
            $pagedata['widget']['order_sort'] = 0;
            $title = '添加';
        }
        else
        {
            $widgetInfo = app::get('syssupplier')->model('widget_instance')
                ->getRow('*', array('widget_id'=>$input['widget_id'], 'deleted'=>0,'shop_id'=>$this->shopId));
            if(!$widgetInfo)
            {
                throw new \InvalidArgumentException("未找到ID为{$input['widget_id']}的信息");
            }
            $pagedata['widget']['widget_id'] = $widgetInfo['widget_id'];
            $pagedata['widget']['widget_name'] = $widgetInfo['widget_name'];
            $pagedata['widget']['widget_type'] = $widgetInfo['widget_type'];
            $pagedata['widget']['page_type'] = $widgetInfo['page_type'];
            $pagedata['widget']['order_sort'] = $widgetInfo['order_sort'];
            $pagedata['widget']['disabled'] = $widgetInfo['disabled'];
            $pagedata['params'] = unserialize($widgetInfo['params']);
            if($pagedata['params']['item_id']){
                $pagedata['item']=app::get('sysitem')->model('item')->getList('item_id,title,price,image_default_id',array('item_id'=>$pagedata['params']['item_id']));
                //$pagedata['notEndItem']=json_encode($pagedata['params']['item_id'],true);
            }
            if($pagedata['params']['agent_id']){
                $pagedata['agents']=app::get('syssupplier')->model('agent_shop')->getList('agent_shop_id,name',array('agent_shop_id'=>$pagedata['params']['agent_id']));
                //$pagedata['notEndItem']=json_encode($pagedata['params']['item_id'],true);
            }
            $title = '修改';
        }

        $pagedata['widget_config_path'] = "syssupplier/widgets/wap/{$pagedata['widget']['widget_type']}/config.html";

        $this->contentHeaderTitle = app::get('topshop')->_($title.$this->page_type[$pagedata['widget']['page_type']].'挂件/wap端商城装修');

        return $this->page('topshop/supplier/decorate/edit.html', $pagedata);
    }

    /**
     * 保存数据
     * @return mixed
     */
    public function save()
    {
        $input = input::get();
        $widget = $input['widget'];

        $data = array();
        $data['widget_name'] = $widget['widget_name'];
        $data['page_type'] = $widget['page_type'];
        $data['widget_type'] = $widget['widget_type'];
        $data['order_sort'] = $widget['order_sort'];
        $data['disabled'] = $widget['disabled'];
        $data['params'] = $this->_getParams($input['params'], $widget['widget_type']);
        $data['modified_time'] = time();
        $data['shop_id'] = $this->shopId;
        $widgetMdl = app::get('syssupplier')->model('widget_instance');
        if(empty($widget['widget_id']))
        {
            //add
            $data['write_time'] = $data['modified_time'];
            $data['template_path'] = "syssupplier/widgets/wap/{$widget['widget_type']}/template.html";
            $widget_num = $widgetMdl->count();
            $data['order_sort'] = $widget_num;
            $widget_id = $widgetMdl->insert($data);
        }
        else
        {
            //update
            $widget_id = $widget['widget_id'];
            $widgetMdl->update($data, array('widget_id'=>$widget_id));
        }

        $url = url::action('topshop_ctl_supplier_decorate@index');
        $msg = app::get('topshop')->_('保存成功');

        if(!$widget_id)
        {
            $msg = app::get('topshop')->_('保存失败');
            return $this->splash('error',$url,$msg,true);
        }
        return $this->splash('success',$url,$msg,true);
    }
    /**删除数据
     * @return mixed
     */
    public function delete()
    {
        $widget_id = input::get('widget_id');
        $url = url::action('topshop_ctl_supplier_decorate@index');
        $widgetMdl = app::get('syssupplier')->model('widget_instance');

        $w_res = $widgetMdl->getRow('order_sort', array('widget_id'=>$widget_id));
        if($w_res)
        {
            $order_sort = $w_res['order_sort'];
            $flag = $widgetMdl->delete(array('widget_id'=>$widget_id));
            if($flag)
            {
                $need_sort_widgets = $widgetMdl->getList('widget_id', array( 'order_sort|than'=>$order_sort));
                if($need_sort_widgets)
                {
                    foreach ($need_sort_widgets as $w)
                    {
                        $widgetMdl->update(array('order_sort'=>$order_sort), array('widget_id'=>$w['widget_id']));
                        $order_sort ++;
                    }
                }
                $msg = '删除数据成功';
                return $this->splash('success', $url, $msg, true);
            }
        }

        $msg = '删除数据失败';
        return $this->splash('error', $url, $msg, true);
    }
    //设置挂件的排序
    public function sortOpt()
    {
        $input = input::get();
        if($input['widget_id'] && $input['opt'])
        {
            $widgetMdl = app::get('syssupplier')->model('widget_instance');
            $currWidgetInfo = $widgetMdl->getRow('widget_id,order_sort', array('widget_id'=>$input['widget_id']));
            if($input['opt'] == 'up')
            {
                $prev_sort = $currWidgetInfo['order_sort'] - 1;
                $prevWidgetInfo = $widgetMdl->getRow('widget_id,order_sort', array('order_sort'=>$prev_sort));
                $widgetMdl->update(array('order_sort'=>$currWidgetInfo['order_sort']), array('widget_id'=>$prevWidgetInfo['widget_id']));
                $widgetMdl->update(array('order_sort'=>$prev_sort), array('widget_id'=>$currWidgetInfo['widget_id']));
            }
            elseif ($input['opt'] == 'down')
            {
                $next_sort = $currWidgetInfo['order_sort'] + 1;
                $nextWidgetInfo = $widgetMdl->getRow('widget_id,order_sort', array('order_sort'=>$next_sort));
                $widgetMdl->update(array('order_sort'=>$currWidgetInfo['order_sort']), array('widget_id'=>$nextWidgetInfo['widget_id']));
                $widgetMdl->update(array('order_sort'=>$next_sort), array('widget_id'=>$currWidgetInfo['widget_id']));
            }

            $msg = '操作成功';
            return $this->splash('success', null, $msg, true);
        }
        $msg = '操作失败';
        return $this->splash('error', null, $msg, true);
    }
    //设置挂件的状态
    public function setStatus()
    {
        $input = input::get();
        if(!empty($input['widget_id']) && isset($input['status']))
        {
            $widgetMdl = app::get('syssupplier')->model('widget_instance');
            $widgetMdl->update(array('disabled'=>$input['status']), array('widget_id'=>$input['widget_id']));
            $msg = '操作成功';
            return $this->splash('success', null, $msg, true);
        }

        $msg = '操作失败';
        return $this->splash('error', null, $msg, true);
    }
    protected function _getParams($params, $widget_type)
    {

        switch ($widget_type)
        {
            case 'cate_nav':
                $cate_nav = array();
                foreach ($params['pic'] as $k => $v)
                {
                    $cate_nav[] = array(
                        'pic' => $v,
                        'url' => $params['url'][$k],
                        'title' => $params['title'][$k],
                    );
                }
                return serialize($cate_nav);
                break;
            case 'hot':
                $hot = array();
                foreach ($params['pic'] as $k => $v)
                {
                    $hot['list'][] = array(
                        'pic' => $v,
                        'url' => $params['url'][$k],
                        'title' => $params['title'][$k],
                    );
                }
                unset($params['pic']);
                $hot['icon_title'] = $params['icon_title'];
                $hot['icon_url'] = $params['icon_url'];
                return serialize($hot);
                break;

            case 'item':
                $hot = array();
                foreach ($params['pic'] as $k => $v)
                {
                    $hot['list'][] = array(
                        'pic' => $v,
                        'url' => $params['url'][$k],
                        'title' => $params['title'][$k],
                    );
                }
                unset($params['pic']);
                $hot['icon_title'] = $params['icon_title'];
                $hot['icon_url'] = $params['icon_url'];
                return serialize($hot);
                break;
            case 'slider_ad':
                $slider_ad = array();
                foreach ($params['pic'] as $k => $v)
                {
                    $slider_ad[] = array(
                        'pic' => $v,
                        'url' => $params['url'][$k],
                    );
                }
                return serialize($slider_ad);
                break;
            default :
                return serialize($params);
        }
    }






}