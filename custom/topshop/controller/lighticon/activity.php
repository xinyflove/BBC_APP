<?php
class topshop_ctl_lighticon_activity extends topshop_controller {
    public $status = [
        // 正常
        0,
        // 删除
        1
    ];
    /**
     * 活动列表
     * @return html
     * @auth xinyufeng
     */
    public function index()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('点亮图标活动管理');

        /*获取数据列表开始*/
        $filter = input::get();
        if(!$filter['pages'])
        {
            $filter['pages'] = 1;
        }
        $pageSize = 10;
        $params = array(
            'page_no' => $filter['pages'],
            'page_size' => intval($pageSize),
            'fields' => 'activity_id,activity_name,activity_start_time,activity_end_time,prizes_surplus,view,participation,create_time,modified_time,status',
            'shop_id'=> $this->shopId,
        );

        $activityListData = app::get('topshop')->rpcCall('actlighticon.activity.list', $params);
        /*获取数据列表结束*/

        /*处理翻页数据开始*/
        $count = $activityListData['total'];
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_lighticon_activity@index', $filter),
            'current'=>$current,
            'use_app'=>'topshop',
            'total'=>$total,
            'token'=>$filter['pages'],
        );

        $pagedata['activityList'] = $activityListData['data'];
        $pagedata['now'] = time();
        $pagedata['total'] = $count;
        /*处理翻页数据结束*/

        return $this->page('topshop/lighticon/activity/list.html', $pagedata);
    }

    /**
     * 添加/编辑活动
     * @return html|string
     * @auth xinyufeng
     */
    public function activity_edit()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('添加/编辑活动');

        $apiData['activity_id'] = input::get('activity_id');

        if($apiData['activity_id'])
        {
            $showData = app::get('topshop')->rpcCall('actlighticon.activity.get', $apiData);

            if($showData['shop_id'] != $this->shopId)
            {
                return $this->splash('error','','您没有权限编辑此投票活动',true);
            }
            // ff($showData);
            $showData['activity_valid_time'] = date('Y/m/d H:i', $showData['activity_start_time']) . '-' . date('Y/m/d H:i', $showData['activity_end_time']);
            $pagedata['activity'] = $showData;
        }
        return $this->page('topshop/lighticon/activity/edit.html', $pagedata);
    }

    /**
     * 保存活动
     * @return string
     * @auth xinyufeng
     */
    public function activity_save()
    {
        $params = input::get();
        $apiData = $params['activity'];
        $apiData['shop_id'] = $this->shopId;

        $activityTimeArray = explode('-', $apiData['activity_valid_time']);//活动有效期
        $apiData['activity_start_time']  = strtotime($activityTimeArray[0]);
        $apiData['activity_end_time'] = strtotime($activityTimeArray[1]);

        try
        {
            $result = app::get('topshop')->rpcCall('actlighticon.activity.save', $apiData);
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', null, $msg, true);
        }

        $this->sellerlog('添加/编辑点亮图标活动。活动名称是 '.$apiData['activity_name']);
        $url = url::action('topshop_ctl_lighticon_activity@index');
        $msg = app::get('topshop')->_('保存活动成功');
        return $this->splash('success',$url,$msg,true);
    }

    /**
     * 删除投票活动
     * @return string
     * @auth xinyufeng
     */
    public function activityStatus()
    {
        $activityId = input::get('activity_id', false);
        $status = input::get('status', 0);
        if( !$activityId || !in_array($status, $this->status))
        {
            $msg = '参数错误';
            return $this->splash('error','',$msg,true);
        }

        try
        {
            $params['activity_id'] = (int)$activityId;
            $params['shop_id'] = (int)$this->shopId;
            $params['status'] = $status;
            app::get('topshop')->rpcCall('actlighticon.activity.status',$params);
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }

        // $this->sellerlog('删除图标。账号ID是 '.$activityId);
        $msg = '删除成功';
        $url = url::action('topshop_ctl_lighticon_activity@index');
        return $this->splash('success',$url,$msg,true);
    }

}