<?php
/**
 * Created by PhpStorm.
 * User: jiangyunhan
 * Date: 2018-05-17
 * Time: 15:13
 */
class topshop_ctl_supplier_category extends topshop_controller{
    //线下店分类列表
    public function index(){
        /*$flag = 'a:2:{s:3:"cat";a:4:{i:45;a:2:{s:8:"cat_name";s:12:"中餐美食";s:10:"order_sort";s:1:"0";}i:46;a:2:{s:8:"cat_name";s:12:"西餐美食";s:10:"order_sort";s:1:"1";}i:47;a:2:{s:8:"cat_name";s:12:"火锅烧烤";s:10:"order_sort";s:1:"2";}i:48;a:2:{s:8:"cat_name";s:12:"休闲娱乐";s:10:"order_sort";s:1:"3";}}s:3:"new";a:1:{i:46;a:3:{s:6:"cat_id";s:2:"46";s:8:"cat_name";s:6:"测试";s:10:"order_sort";s:1:"4";}}}';
        $res = unserialize($flag);
        //var_dump($res['del']);exit;
        foreach( $res['cat'] as  $key=>$val )
        {
            foreach($val as $k=>$cat){
                var_dump($key);exit;
            }

        }
        exit;*/
        $flag = app::get('topshop')->rpcCall('supplier.agent.category.list');
        $pagedata['cat'] = $flag;
        $this->contentHeaderTitle = app::get('topshop')->_('线下店分类列表');
        return $this->page('topshop/supplier/category.html', $pagedata);
    }

    /**
     * @brief 保存线下店分类数据
     *
     * @return json
     */
    public function saveCat()
    {
        $params['catlist'] = serialize(input::get());
        $params['shop_id'] = $this->shopId;
        $url = url::action('topshop_ctl_supplier_category@index');
        //a:2:{s:3:"cat";a:1:{i:1;a:2:{s:8:"cat_name";s:12:"美食烧烤";s:10:"order_sort";s:1:"0";}}s:3:"new";a:1:{i:2;a:3:{s:6:"cat_id";s:1:"2";s:8:"cat_name";s:6:"测试";s:10:"order_sort";s:1:"1";}}}
        //return $this->splash('error',$url,$params['catlist'],true);
        try
        {
            $flag = app::get('topshop')->rpcCall('supplier.agent.save.category',$params);
            if( $flag )
            {
                $status = 'success';
                $msg = app::get('topshop')->_('保存成功');
            }
            else
            {
                $status = 'error';
                $msg = app::get('topshop')->_('保存失败');
            }
            return $this->splash($status,$url,$msg,true);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',$url,$msg,true);
        }
    }

    public function removeCat()
    {
        return $this->splash('success',null,$msg,true);
    }

    //设置线下店分类的排序
    public function sortOpt()
    {

        $input = input::get();

        if($input['agent_category_id'] && $input['opt'])
        {
            $widgetMdl = app::get('syssupplier')->model('agent_cat');
            $currWidgetInfo = $widgetMdl->getRow('agent_category_id,order_sort', array('agent_category_id'=>$input['agent_category_id']));

            if($input['opt'] == 'up')
            {
                $prev_sort = $currWidgetInfo['order_sort'] - 1;
                $prevWidgetInfo = $widgetMdl->getRow('agent_category_id,order_sort', array('order_sort'=>$prev_sort));

                $widgetMdl->update(array('order_sort'=>$currWidgetInfo['order_sort']), array('agent_category_id'=>$prevWidgetInfo['agent_category_id']));
                $widgetMdl->update(array('order_sort'=>$prev_sort), array('agent_category_id'=>$currWidgetInfo['agent_category_id']));
            }
            elseif ($input['opt'] == 'down')
            {
                $next_sort = $currWidgetInfo['order_sort'] + 1;
                $nextWidgetInfo = $widgetMdl->getRow('agent_category_id,order_sort', array('order_sort'=>$next_sort));
                $widgetMdl->update(array('order_sort'=>$currWidgetInfo['order_sort']), array('agent_category_id'=>$nextWidgetInfo['agent_category_id']));
                $widgetMdl->update(array('order_sort'=>$next_sort), array('agent_category_id'=>$currWidgetInfo['agent_category_id']));
            }

            $msg = '操作成功';
            return $this->splash('success', null, $msg, true);
        }
        $msg = '操作失败';
        return $this->splash('error', null, $msg, true);
    }
}