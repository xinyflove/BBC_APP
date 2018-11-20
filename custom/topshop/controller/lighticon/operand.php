<?php
class topshop_ctl_lighticon_operand extends topshop_controller {
    public function operandList()
    {
    	$this->contentHeaderTitle = app::get('topshop')->_('图标列表');
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
            'search_keywords' => $filter['search_keywords'],
    	    'page_size' => $pageSize,
    	    'order_by' => 'order_by desc',
            'fields' => '*',
    	    'status' => [0,2],
    	);

    	$operandListData = app::get('topshop')->rpcCall('actlighticon.operand.list', $params, 'seller');
        $pagedata['activity_id'] = intval($filter['activity_id']);
    	$pagedata['search_keywords'] = $filter['search_keywords'];

        $count = $operandListData['count'];
        $pagedata['operandList'] = $operandListData['data'];

        //处理翻页数据
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_lighticon_operand@operandList', $filter),
            'current'=>$current,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>$filter['pages'],
        );

        $pagedata['now'] = time();
        $pagedata['total'] = $count;
    	// jj($operandListData);
    	return $this->page('topshop/lighticon/operand/list.html', $pagedata);
    }
    //
    public function operandEdit()
    {
    	$this->contentHeaderTitle = app::get('topshop')->_('编辑图标');

        $activityId = intval(input::get('activity_id'));
        $operandId = intval(input::get('operand_id'));

        if(!$activityId)
        {
            throw new \LogicException(app::get('topshop')->_('异常操作！'));
        }

        //面包屑
        $this->runtimePath = array(
            ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],
            ['url'=> url::action('topshop_ctl_lighticon_operand@operandList', ['activity_id' => $activityId]),'title' => app::get('topshop')->_('图标管理')],
            ['title' => app::get('topshop')->_('编辑图标')],
        );

        if($operandId)
        {
        	// 获取活动规则信息
        	$operandParams = array(
        	    'activity_id' => $activityId,
        	    'shop_id'=>$this->shopId,
        	    'operand_id'=>$operandId,
        	    'fields' => '*',
        	);
        	$pagedata['operand_id'] = $operandId;
        	$pagedata['operand'] = app::get('topshop')->rpcCall('actlighticon.operand.get', $operandParams, 'seller');
        }
        $pagedata['activity_id'] = $activityId;
        return $this->page('topshop/lighticon/operand/edit.html', $pagedata);
    }

    public function operandSave()
    {
        $params = input::get();
        $apiData = $params['operand'];
        $apiData['shop_id'] = $this->shopId;
        $apiData['activity_id'] = (int) $params['activity_id'];
        $apiData['operand_id'] = (int) $params['operand_id'];

        try
        {
            $result = app::get('topshop')->rpcCall('actlighticon.operand.save', $apiData, 'seller');
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            $url = url::action('topshop_ctl_lighticon_operand@operandList', array('activity_id'=>$params['activity_id']));
            return $this->splash('error',$url,$msg,true);
        }
        // $this->sellerlog('申请活动。活动ID是 '.$apiData['activity_id']);
        $url = url::action('topshop_ctl_lighticon_operand@operandList', array('activity_id'=>$params['activity_id']));
        $msg = app::get('topshop')->_('图标保存成功');
        return $this->splash('success',$url,$msg,true);

    }

    public function operandStatus()
    {
        $operandId = input::get('operand_id', false);
        if( !$operandId )
        {
            $msg = '删除失败';
            return $this->splash('error','',$msg,true);
        }

        try
        {
            $params['operand_id'] = (int)$operandId;
            $params['shop_id'] = (int)$this->shopId;
            $params['status'] = 1;//删除
            app::get('topshop')->rpcCall('actlighticon.operand.changestatus',$params);
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }

        // $this->sellerlog('删除图标。账号ID是 '.$operandId);
        $msg = '删除成功';
        $url = url::action('topshop_ctl_lighticon_operand@operandList');
        return $this->splash('success',$url,$msg,true);
    }
}