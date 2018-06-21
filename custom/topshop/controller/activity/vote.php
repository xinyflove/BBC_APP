<?php
class topshop_ctl_activity_vote extends topshop_controller {

    /**
     * 投票活动列表
     * @return html
     * @auth xinyufeng
     */
    public function index()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('礼品活动管理');

        /*获取数据列表开始*/
        $filter = input::get();
        $search = $filter['search'];

        if(!$filter['pages'])
        {
            $filter['pages'] = 1;
        }
        $pageSize = 10;
        $params = array(
            'page_no' => $filter['pages'],
            'page_size' => intval($pageSize),
            'fields' =>'*',
            'shop_id'=> $this->shopId,
        );
        if($search['active_name']) $params['active_name'] = $search['active_name'];
        if($search['active_type']) $params['active_type'] = $search['active_type'];

        $activeListData = app::get('topshop')->rpcCall('sysactivityvote.active.list', $params);
        /*获取数据列表结束*/

        /*处理翻页数据开始*/
        $count = $activeListData['total'];
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_activity_vote@index', $filter),
            'current'=>$current,
            'use_app'=>'topshop',
            'total'=>$total,
            'token'=>$filter['pages'],
        );

        $pagedata['activeList'] = $activeListData['data'];
        $pagedata['now'] = time();
        $pagedata['total'] = $count;
        $pagedata['examine_setting'] = app::get('sysconf')->getConf('shop.promotion.examine');
        /*处理翻页数据结束*/

        $pagedata['search'] = $search;

        return $this->page('topshop/activity/vote/list.html', $pagedata);
    }

    /**
     * 添加/编辑投票活动
     * @return html|string
     * @auth xinyufeng
     */
    public function edit_vote()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('添加/编辑礼品活动');

        $apiData['active_id'] = input::get('active_id');

        $showData = array();
        $showData['personal_everyday_get_limit'] = 0;
        $showData['win_probability'] = 0;

        $pagedata = array();
        $pagedata['active_valid_time'] = date('Y/m/d H:i', time()+60) . '-' . date('Y/m/d H:i', time()+120); //活动有效期默认时间
        $pagedata['popular_vote_valid_time'] = date('Y/m/d H:i', time()+60) . '-' . date('Y/m/d H:i', time()+120); //大众投票有效期默认时间
        $pagedata['expert_vote_valid_time'] = date('Y/m/d H:i', time()+60) . '-' . date('Y/m/d H:i', time()+120); //专家投票有效期默认时间

        if($apiData['active_id'])
        {
            $pagedata['active_id'] = $apiData['active_id'];

            $showData = app::get('topshop')->rpcCall('sysactivityvote.active.get', $apiData);
            $pagedata['active_valid_time'] = date('Y/m/d H:i', $showData['active_start_time']) . '-' . date('Y/m/d H:i', $showData['active_end_time']);
            $pagedata['popular_vote_valid_time'] = date('Y/m/d H:i', $showData['popular_vote_start_time']) . '-' . date('Y/m/d H:i', $showData['popular_vote_end_time']);
            $pagedata['expert_vote_valid_time'] = date('Y/m/d H:i', $showData['expert_vote_start_time']) . '-' . date('Y/m/d H:i', $showData['expert_vote_end_time']);
            $pagedata['top_ad_slide'] = unserialize($showData['top_ad_slide']);

            if($showData['shop_id']!= $this->shopId)
            {
                return $this->splash('error','','您没有权限编辑此投票活动',true);
            }
        }

        $pagedata['active'] = $showData;
        $pagedata['ac'] = input::get('ac', '');

        return $this->page('topshop/activity/vote/edit.html', $pagedata);
    }

    /**
     * 保存投票活动
     * @return string
     * @auth xinyufeng
     */
    public function save_vote()
    {
        $params = input::get();
        $apiData = $params['active'];
        $apiData['shop_id'] = $this->shopId;

        $activeTimeArray = explode('-', $params['active_valid_time']);//活动有效期
        $apiData['active_start_time']  = strtotime($activeTimeArray[0]);
        $apiData['active_end_time'] = strtotime($activeTimeArray[1]);

        $popularVoteTimeArray = explode('-', $params['popular_vote_valid_time']);//大众投票有效期
        $apiData['popular_vote_start_time']  = strtotime($popularVoteTimeArray[0]);
        $apiData['popular_vote_end_time'] = strtotime($popularVoteTimeArray[1]);

        $expertVoteTimeArray = explode('-', $params['expert_vote_valid_time']);//专家投票有效期
        $apiData['expert_vote_start_time']  = strtotime($expertVoteTimeArray[0]);
        $apiData['expert_vote_end_time'] = strtotime($expertVoteTimeArray[1]);

        $apiData['top_ad_slide'] = array();
        foreach ($apiData['top_ad_slide_image'] as $k => $v){
            $apiData['top_ad_slide'][] = array('image'=>$v, 'url'=>$apiData['top_ad_slide_url'][$k]);
        }
        unset($apiData['top_ad_slide_image'], $apiData['top_ad_slide_url']);
        $apiData['top_ad_slide'] = serialize($apiData['top_ad_slide']);
        
        try
        {
            if($params['active_id']){
                //更新
                $apiData['active_id'] = $params['active_id'];
                $result = app::get('topshop')->rpcCall('sysactivityvote.active.update', $apiData);

            }else{
                //新增
                $result = app::get('topshop')->rpcCall('sysactivityvote.active.add', $apiData);
            }
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();

            if($params['active_id'])
            {
                $url = url::action('topshop_ctl_activity_vote@edit_vote', array('active_id'=>$params['active_id']));
            }
            else{
                $url = url::action('topshop_ctl_activity_vote@index');
            }
            return $this->splash('error',$url,$msg,true);
        }

        $this->sellerlog('添加/编辑投票活动。投票活动名称是 '.$apiData['active_name']);
        $url = url::action('topshop_ctl_activity_vote@index');
        $msg = app::get('topshop')->_('保存投票活动成功');

        return $this->splash('success',$url,$msg,true);
    }

    /**
     * 查看投票活动
     * @return html
     * @auth xinyufeng
     */
    public function show_vote()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('查看投票活动');
        $apiData['active_id'] = input::get('active_id');
        $showData = array();
        $pagedata = array();

        if($apiData['active_id'])
        {
            $pagedata['active_id'] = $apiData['active_id'];
            $showData = app::get('topshop')->rpcCall('sysactivityvote.active.get', $apiData);

            $pagedata['active_valid_time'] = date('Y/m/d H:i', $showData['active_start_time']) . '-' . date('Y/m/d H:i', $showData['active_end_time']);
            $pagedata['popular_vote_valid_time'] = date('Y/m/d H:i', $showData['popular_vote_start_time']) . '-' . date('Y/m/d H:i', $showData['popular_vote_end_time']);
            $pagedata['expert_vote_valid_time'] = date('Y/m/d H:i', $showData['expert_vote_start_time']) . '-' . date('Y/m/d H:i', $showData['expert_vote_end_time']);
            $pagedata['top_ad_slide'] = unserialize($showData['top_ad_slide']);
        }

        $pagedata['active'] = $showData;
        $pagedata['ac'] = input::get('ac', '');
        return $this->page('topshop/activity/vote/show.html', $pagedata);
    }

    /**
     * 删除投票活动
     * @return string
     * @auth xinyufeng
     */
    public function delete_vote()
    {
        $apiData['active_id'] = input::get('active_id');
        $url = url::action('topshop_ctl_activity_vote@index');
        try
        {
            app::get('topshop')->rpcCall('sysactivityvote.active.delete', $apiData);
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', $url, $msg, true);
        }
        $this->sellerlog('删除投票活动。投票活动ID是 '.$apiData['active_id']);
        $msg = app::get('topshop')->_('删除投票活动成功');
        return $this->splash('success', $url, $msg, true);
    }

    public function expertList()
    {
    	// jj();
    	$this->contentHeaderTitle = app::get('topwap')->_('评审专家列表');
    	$filter = input::get();
    	if(!$filter['pages'])
    	{
    		$filter['pages'] = 1;
    	}
        // $filter['active_id'] = 1;
    	$pageSize = 10;
    	$params = array(
    		'shop_id' => $this->shopId,
    	    'page_no' => intval($filter['pages']),
    	    'active_id' => intval($filter['active_id']),
            'search_keywords' => $filter['expert_name'],
    	    'page_size' => $pageSize,
    	    'order_by' => 'modified_time desc',
            'fields' => '*',
    	    'deleted' => '0',
    	);

    	$expertListData = app::get('topshop')->rpcCall('activity.vote.expert.list', $params, 'seller');
        $pagedata['active_id'] = intval($filter['active_id']);
    	$pagedata['search_keywords'] = $filter['expert_name'];

        $count = $expertListData['count'];
        $pagedata['expertList'] = $expertListData['data'];

        //处理翻页数据
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_activity_vote@expertList', $filter),
            'current'=>$current,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>$filter['pages'],
        );

        $pagedata['now'] = time();
        $pagedata['total'] = $count;
    	// jj($expertListData);
    	return $this->page('topshop/activity/vote/expert/list.html', $pagedata);
    }
    // 
    public function expertEdit()
    {
    	$this->contentHeaderTitle = app::get('topshop')->_('编辑专家');

        $activityId = intval(input::get('active_id'));
        $expertId = intval(input::get('expert_id'));

        if(!$activityId)
        {
            throw new \LogicException(app::get('topshop')->_('异常操作！'));
        }

        //面包屑
        $this->runtimePath = array(
            ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],
            ['url'=> url::action('topshop_ctl_activity_vote@expertList', ['active_id' => $activityId]),'title' => app::get('topshop')->_('专家管理')],
            ['title' => app::get('topshop')->_('编辑专家')],
        );

        if($expertId)
        {
        	// 获取活动规则信息
        	$expertParams = array(
        	    'active_id' => $activityId,
        	    'shop_id'=>$this->shopId,
        	    'expert_id'=>$expertId,
        	    'fields' => '*',
        	);
        	$pagedata['expert_id'] = $expertId;
        	$pagedata['expert'] = app::get('topshop')->rpcCall('activity.vote.expert.get', $expertParams, 'seller');
        }
        $pagedata['active_id'] = $activityId;
        return $this->page('topshop/activity/vote/expert/edit.html', $pagedata);
    }

    public function expertSave()
    {
        $params = input::get();
        $apiData = $params['expert'];
        $apiData['shop_id'] = $this->shopId;
        $apiData['active_id'] = (int) $params['active_id'];
        $apiData['expert_id'] = (int) $params['expert_id'];

        try
        {
            // 活动报名保存
            $result = app::get('topshop')->rpcCall('activity.vote.expert.save', $apiData, 'seller');
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            $url = url::action('topshop_ctl_activity_vote@expertList', array('active_id'=>$params['active_id']));
            return $this->splash('error',$url,$msg,true);
        }
        // $this->sellerlog('申请活动。活动ID是 '.$apiData['activity_id']);
        $url = url::action('topshop_ctl_activity_vote@expertList', array('active_id'=>$params['active_id']));
        $msg = app::get('topshop')->_('专家保存成功');
        return $this->splash('success',$url,$msg,true);

    }

    public function expertDelete()
    {
        $expertId = input::get('expert_id', false);
        if( !$expertId )
        {
            $msg = '删除失败';
            return $this->splash('error','',$msg,true);
        }

        try
        {
            $params['expert_id'] = (int)$expertId;
            $params['shop_id'] = (int)$this->shopId;
            app::get('topshop')->rpcCall('activity.vote.expert.delete',$params);
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }

        $this->sellerlog('删除专家。账号ID是 '.$expertId);
        $msg = '删除成功';
        $url = url::action('topshop_ctl_account_list@index');
        return $this->splash('success',$url,$msg,true);
    }

    public function voteLogList()
    {
        $this->contentHeaderTitle = app::get('topwap')->_('投票日志列表');
        $filter = input::get();

        if(!$filter['pages'])
        {
            $filter['pages'] = 1;
        }

        if(!$filter['active_id'] && !$filter['game_id'])
        {
            throw new \LogicException(app::get('topshop')->_('异常操作！'));
        }

        if(!$filter['active_id'])
        {
            $gameData = app::get('topshop')->rpcCall('sysactivityvote.game.get', ['game_id' => $filter['game_id']], 'seller');
            $filter['active_id'] = $gameData['active_id'];
        }

        $pageSize = 10;
        $params = array(
            'shop_id' => $this->shopId,
            'page_no' => intval($filter['pages']),
            'active_id' => intval($filter['active_id']),
            'game_id' => intval($filter['game_id']),
            'game_name' => $filter['game_name'],
            'start_time' => $filter['start_time'],
            'end_time' => $filter['end_time'],
            'ip' => $filter['ip'],
            'page_size' => $pageSize,
            'order_by' => 'modified_time desc',
            'fields' => '*',
            'deleted' => '0',
        );

        $voteLogListData = app::get('topshop')->rpcCall('activity.vote.log.list', $params, 'seller');

        $count = $voteLogListData['count'];
        $pagedata['voteLogList'] = $voteLogListData['data'];

        $pagedata['game_name'] = $filter['game_name'];
        $pagedata['end_time'] = $filter['end_time'];

        if($filter['start_time'])
        {
            $pagedata['start_time'] = $filter['start_time'];
            $expertParams['create_time|bthan'] = strtotime($filter['start_time']);
        }

        if($filter['end_time'])
        {
            $pagedata['end_time'] = $filter['end_time'];
            $expertParams['create_time|sthan'] = strtotime($filter['end_time']);
        }

        if($filter['game_id'])
        {
            $expertParams['game_id'] = $filter['game_id'];
            $pagedata['game_id'] = $filter['game_id'];
        }

        if($filter['game_name'])
        {
            $pagedata['game_name'] = $filter['game_name'];
            $gameIdList = kernel::single('sysactivityvote_game')->getIdListByName($filter['game_name']);
            if($gameIdList)
            {
                foreach ($gameIdList as $key => $value) {
                    $expertParams['game_id'][] = $value['game_id'];
                }
            }
        }

        if($filter['active_id'])
        {
            $expertParams['active_id'] = $filter['active_id'];
            $pagedata['active_id'] = $filter['active_id'];
        }

        if($filter['ip'])
        {
            $expertParams['ip'] = $filter['ip'];
            $pagedata['ip'] = $filter['ip'];
        }

        $pagedata['expertParams'] = json_encode($expertParams);
        //处理翻页数据
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_activity_vote@voteLogList', $filter),
            'current'=>$current,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>$filter['pages'],
        );

        $pagedata['now'] = time();
        $pagedata['total'] = $count;
        // jj($voteLogListData);
        return $this->page('topshop/activity/vote/log/list.html', $pagedata);
    }
}