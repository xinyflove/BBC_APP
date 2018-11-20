<?php
class topshop_ctl_lighticon_lightlog extends topshop_controller {
    public function lightlogList()
    {
    	$this->contentHeaderTitle = app::get('topshop')->_('点亮日志列表');
    	$filter = input::get();
    	if(!$filter['pages'])
    	{
    		$filter['pages'] = 1;
    	}
        // $filter['activity_id'] = 1;
    	$pageSize = 10;
    	$params = array(
    		'shop_id' => $this->shopId,
    	    'page_no' => intval($filter['pages']),
    	    'activity_id' => intval($filter['activity_id']),
    	    'participant_id' => intval($filter['participant_id']),
            // 'search_keywords' => $filter['search_keywords'],
    	    'page_size' => $pageSize,
    	    // 'order_by' => 'modified_time desc',
            'fields' => '*',
    	    'status' => '0',
    	);

    	$lightlogListData = app::get('topshop')->rpcCall('actlighticon.lightlog.list', $params, 'seller');
        // $pagedata['activity_id'] = intval($filter['activity_id']);
    	// $pagedata['search_keywords'] = $filter['search_keywords'];

        $count = $lightlogListData['count'];
        $pagedata['lightlogList'] = $lightlogListData['data'];

        //处理翻页数据
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_lighticon_lightlog@lightlogList', $filter),
            'current'=>$current,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>$filter['pages'],
        );

        $pagedata['now'] = time();
        $pagedata['total'] = $count;
    	// jj($lightlogListData);
    	return $this->page('topshop/lighticon/lightlog/list.html', $pagedata);
    }
    //
    public function lightlogEdit()
    {
    	$this->contentHeaderTitle = app::get('topshop')->_('编辑图标');

        $activityId = intval(input::get('activity_id'));
        $lightlogId = intval(input::get('lightlog_id'));

        if(!$activityId)
        {
            throw new \LogicException(app::get('topshop')->_('异常操作！'));
        }

        //面包屑
        $this->runtimePath = array(
            ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],
            ['url'=> url::action('topshop_ctl_lighticon_lightlog@lightlogList', ['activity_id' => $activityId]),'title' => app::get('topshop')->_('图标管理')],
            ['title' => app::get('topshop')->_('编辑图标')],
        );

        if($lightlogId)
        {
        	// 获取活动规则信息
        	$lightlogParams = array(
        	    'activity_id' => $activityId,
        	    'shop_id'=>$this->shopId,
        	    'lightlog_id'=>$lightlogId,
        	    'fields' => '*',
        	);
        	$pagedata['lightlog_id'] = $lightlogId;
        	$pagedata['lightlog'] = app::get('topshop')->rpcCall('actlighticon.lightlog.get', $lightlogParams, 'seller');
        }
        $pagedata['activity_id'] = $activityId;
        return $this->page('topshop/lighticon/lightlog/edit.html', $pagedata);
    }

    public function lightlogSave()
    {
        $params = input::get();
        $apiData = $params['lightlog'];
        $apiData['shop_id'] = $this->shopId;
        $apiData['activity_id'] = (int) $params['activity_id'];
        $apiData['lightlog_id'] = (int) $params['lightlog_id'];

        try
        {
            $result = app::get('topshop')->rpcCall('actlighticon.lightlog.save', $apiData, 'seller');
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            $url = url::action('topshop_ctl_lighticon_lightlog@lightlogList', array('activity_id'=>$params['activity_id']));
            return $this->splash('error',$url,$msg,true);
        }
        // $this->sellerlog('申请活动。活动ID是 '.$apiData['activity_id']);
        $url = url::action('topshop_ctl_lighticon_lightlog@lightlogList', array('activity_id'=>$params['activity_id']));
        $msg = app::get('topshop')->_('图标保存成功');
        return $this->splash('success',$url,$msg,true);

    }

    public function lightlogStatus()
    {
        $lightlogId = input::get('lightlog_id', false);
        if( !$lightlogId )
        {
            $msg = '删除失败';
            return $this->splash('error','',$msg,true);
        }

        try
        {
            $params['lightlog_id'] = (int)$lightlogId;
            $params['shop_id'] = (int)$this->shopId;
            $params['status'] = 1;//删除
            app::get('topshop')->rpcCall('actlighticon.lightlog.changestatus',$params);
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }

        // $this->sellerlog('删除图标。账号ID是 '.$lightlogId);
        $msg = '删除成功';
        $url = url::action('topshop_ctl_lighticon_lightlog@lightlogList');
        return $this->splash('success',$url,$msg,true);
    }
}