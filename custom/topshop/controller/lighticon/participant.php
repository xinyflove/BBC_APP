<?php
class topshop_ctl_lighticon_participant extends topshop_controller {
    public $status = [
        // 正常
        0,
        // 删除
        1
    ];
    public function participantList()
    {
    	$this->contentHeaderTitle = app::get('topshop')->_('参与会员列表');
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
            'gift_id' => $filter['gift_id'],
    	    'page_size' => $pageSize,
    	    'order_by' => 'modified_time desc',
            'fields' => '*',
    	    'status' => '0',
    	);

        $activity_data = app::get('topshop')->rpcCall('actlighticon.activity.get', ['activity_id' => intval($filter['activity_id'])], 'seller');

    	$participantListData = app::get('topshop')->rpcCall('actlighticon.participant.list', $params, 'seller');
        $pagedata['activity_id'] = intval($filter['activity_id']);
    	$pagedata['search_keywords'] = $filter['search_keywords'];
    	$pagedata['gift_id'] = $filter['gift_id'];

        foreach ($participantListData['data'] as &$value) {
            $gift_data = [];
            if($value['gift_id']) {
                $gift_data = app::get('topshop')->rpcCall('actlighticon.gift.getinfo', ['gift_id' => intval($value['gift_id'])], 'seller');
            }

            if( $gift_data['gift_id'] ) {
                $value['gift'] = $gift_data;
                if($gift_data['need_deliver']) {
                    if($value['logistics_number']) {
                        $value['logistics_info'] = $value['logistics_company'] . '<br>发货号:' . $value['logistics_number'] . '<br>发货时间:' . date('Y-m-d H:i', $value['logistics_time']);
                    } else {
                        $value['logistics_info'] = '待发货';

                    }
                } else {
                    $value['logistics_info'] = '免发货';
                }
            } else {
                $value['gift']['gift_name'] = '未中奖';
                $value['logistics_info'] = '未中奖';
            }
        }

        $count = $participantListData['count'];
        $pagedata['participantList'] = $participantListData['data'];

        //处理翻页数据
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_lighticon_participant@participantList', $filter),
            'current'=>$current,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>$filter['pages'],
        );

        $pagedata['now'] = time();
        $pagedata['total'] = $count;
        // $pagedata['exportFilter'] = json_encode([
        //     'activity_id' => intval($filter['activity_id']),
        //     'status' => 0,
        //     ]);
        // $pagedata['exportGiftFilter'] = json_encode([
        //     'activity_id' => intval($filter['activity_id']),
        //     'gift_id|than' => 0,
        //     'status' => 0,
        //     ]);

        // 奖品
        $gift_params = array(
            'shop_id' => $this->shopId,
            'page_no' => 1,
            'activity_id' => intval($filter['activity_id']),
            'page_size' => 1000,
            'order_by' => 'modified_time desc',
            'fields' => 'gift_id,gift_name',
            'status' => '0',
        );
        $giftListData = app::get('topshop')->rpcCall('actlighticon.gift.list', $gift_params, 'seller');
        $giftListData['data'] = array_merge([
            [ 'gift_id' => -3, 'gift_name' => '全部',],
            [ 'gift_id' => -2, 'gift_name' => '未获奖',],
            [ 'gift_id' => -1, 'gift_name' => '已获奖',],
        ] ,  $giftListData['data']);
        // ff($giftListData);
        $pagedata['giftList'] = $giftListData['data'];

    	return $this->page('topshop/lighticon/participant/list.html', $pagedata);
    }
    //
    public function participantEdit()
    {
    	$this->contentHeaderTitle = app::get('topshop')->_('编辑图标');

        $activityId = intval(input::get('activity_id'));
        $participantId = intval(input::get('participant_id'));

        if(!$activityId)
        {
            throw new \LogicException(app::get('topshop')->_('异常操作！'));
        }

        //面包屑
        $this->runtimePath = array(
            ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],
            ['url'=> url::action('topshop_ctl_lighticon_participant@participantList', ['activity_id' => $activityId]),'title' => app::get('topshop')->_('图标管理')],
            ['title' => app::get('topshop')->_('编辑图标')],
        );

        if($participantId)
        {
        	// 获取活动规则信息
        	$participantParams = array(
        	    'activity_id' => $activityId,
        	    'shop_id'=>$this->shopId,
        	    'participant_id'=>$participantId,
        	    'fields' => '*',
        	);
        	$pagedata['participant_id'] = $participantId;
        	$pagedata['participant'] = app::get('topshop')->rpcCall('actlighticon.participant.get', $participantParams, 'seller');
        }
        $pagedata['activity_id'] = $activityId;
        return $this->page('topshop/lighticon/participant/edit.html', $pagedata);
    }

    public function participantSave()
    {
        $params = input::get();
        $apiData = $params['participant'];
        $apiData['shop_id'] = $this->shopId;
        $apiData['activity_id'] = (int) $params['activity_id'];
        $apiData['participant_id'] = (int) $params['participant_id'];

        try
        {
            $result = app::get('topshop')->rpcCall('actlighticon.participant.save', $apiData, 'seller');
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            $url = url::action('topshop_ctl_lighticon_participant@participantList', array('activity_id'=>$params['activity_id']));
            return $this->splash('error',$url,$msg,true);
        }
        // $this->sellerlog('申请活动。活动ID是 '.$apiData['activity_id']);
        $url = url::action('topshop_ctl_lighticon_participant@participantList', array('activity_id'=>$params['activity_id']));
        $msg = app::get('topshop')->_('图标保存成功');
        return $this->splash('success',$url,$msg,true);

    }
    public function shippingEdit()
    {
    	$this->contentHeaderTitle = app::get('topshop')->_('奖品发货');

        $activityId = intval(input::get('activity_id'));
        $participantId = intval(input::get('participant_id'));

        if(!$activityId || !$participantId)
        {
            throw new \LogicException(app::get('topshop')->_('异常操作！'));
        }
        $pagedata['activity_id'] = $activityId;
        $pagedata['participant_id'] = $participantId;
        return $this->page('topshop/lighticon/participant/shipping.html', $pagedata);
    }

    public function shippingSave()
    {
        $params = input::get();
        $apiData = $params['participant'];
        $apiData['shop_id'] = $this->shopId;
        $apiData['activity_id'] = (int) $params['activity_id'];
        $apiData['participant_id'] = (int) $params['participant_id'];
        $apiData['logistics_company'] = $params['logistics_company'];
        $apiData['logistics_number'] = $params['logistics_number'];

        try
        {
            $result = app::get('topshop')->rpcCall('actlighticon.shipping.save', $apiData, 'seller');
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }
        // $this->sellerlog('申请活动。活动ID是 '.$apiData['activity_id']);
        $url = url::action('topshop_ctl_lighticon_participant@participantList', array('activity_id'=>$params['activity_id']));
        $msg = app::get('topshop')->_('发货信息保存成功');
        return $this->splash('success',$url,$msg,true);

    }

    public function participantStatus()
    {
        $participantId = input::get('participant_id', false);
        $status = input::get('status', 0);
        if( !$participantId || !in_array($status, $this->status))
        {
            $msg = '参数错误';
            return $this->splash('error','',$msg,true);
        }

        try
        {
            $params['participant_id'] = (int)$participantId;
            $params['shop_id'] = (int)$this->shopId;
            $params['status'] = $status;
            app::get('topshop')->rpcCall('actlighticon.participant.changestatus',$params);
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }

        // $this->sellerlog('删除图标。账号ID是 '.$participantId);
        $msg = '删除成功';
        $url = url::action('topshop_ctl_lighticon_participant@participantList');
        return $this->splash('success',$url,$msg,true);
    }
}