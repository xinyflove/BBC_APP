<?php
class topshop_ctl_activity_expert_comment extends topshop_controller {

    public function commentList()
    {
    	$this->contentHeaderTitle = app::get('topwap')->_('专家点评列表');
    	$filter = input::get();
    	if(!$filter['pages'])
    	{
    		$filter['pages'] = 1;
    	}
        // $filter['active_id'] = 1;
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
            'active_id' => $filter['active_id'],
            'game_id' => $filter['game_id'],
            'game_name' => $filter['game_name'],
    	    'expert_id' => $filter['expert_id'],
            'page_no' => intval($filter['pages']),
    	    'page_size' => $pageSize,
    	    'order_by' => 'modified_time desc',
            'fields' => '*',
    	    'deleted' => empty($filter['deleted']) ? 0 : 1,
    	);

        $commentListData = app::get('topshop')->rpcCall('activity.vote.expert.comment.list', $params, 'seller');


        $expertParams = [
                            'active_id' => $filter['active_id'],
                        ];
                        
    	$expertList = app::get('topshop')->rpcCall('activity.vote.expert.list', $expertParams, 'seller');
        $pagedata['expertList'] = $expertList['data'];
        $pagedata['active_id'] = intval($filter['active_id']);
        $pagedata['game_id'] = intval($filter['game_id']);
        $pagedata['expert_id'] = intval($filter['expert_id']);
    	$pagedata['game_name'] = $filter['game_name'];

        $count = $commentListData['count'];
        $pagedata['commentList'] = $commentListData['data'];

        //处理翻页数据
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_activity_expert_comment@commentList', $filter),
            'current'=>$current,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>$filter['pages'],
        );

        $pagedata['now'] = time();
        $pagedata['total'] = $count;
    	// jj($commentListData);
    	return $this->page('topshop/activity/expert/comment/list.html', $pagedata);
    }

    
    public function commentEdit()
    {
    	$this->contentHeaderTitle = app::get('topshop')->_('编辑专家评论');

        // $activityId = intval(input::get('active_id'));
        // $expertCommentId = intval(input::get('expert_comment_id'));
        $gameId = intval(input::get('game_id'));
        // $expertId = intval(input::get('expert_id'));

        if(!$gameId)
        {
            throw new \LogicException(app::get('topshop')->_('异常操作！'));
        }

        $pagedata['game_id'] = $gameId;
        //面包屑
        $this->runtimePath = array(
            ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],

            ['url'=> url::action('topshop_ctl_activity_vote@index'),'title' => app::get('topshop')->_('活动管理')],

            // ['url'=> url::action('topshop_ctl_activity_vote@expertList', input::get()),'title' => app::get('topshop')->_('专家管理')],

            ['title' => app::get('topshop')->_('编辑专家')],
        );

        $gameParams = array(
            'shop_id' => $this->shopId,
            // 'active_id' => $filter['active_id'],
            'game_id' => $gameId,
            'fields' => '*',
        );
        // 取得投票对象
        $game_info = app::get('topshop')->rpcCall('sysactivityvote.game.get', $gameParams, 'seller');

        $expertParams = array(
            'shop_id' => $this->shopId,
            'active_id' => $game_info['active_id'],
            'page_no' => 1,
            'page_size' => 1000,
            'order_by' => 'modified_time desc',
            'fields' => '*',
            'deleted' => 0,
        );
        // 取得专家列表
        $expertList = app::get('topshop')->rpcCall('activity.vote.expert.list', $expertParams, 'seller');
        $pagedata['expertList'] = $expertList['data'];
        return $this->page('topshop/activity/expert/comment/edit.html', $pagedata);
    }

    public function commentSave()
    {
        $params = input::get();
        $apiData = $params['comment'];
        $apiData['shop_id'] = $this->shopId;
        // $apiData['active_id'] = (int) $params['active_id'];
        // $apiData['expert_id'] = (int) $params['expert_id'];

        try
        {
            // 保存专家点评
            $result = app::get('topshop')->rpcCall('sysactivityvote.expert.save', $apiData, 'seller');
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            // $url = url::action('topshop_ctl_activity_vote@expertList', array('active_id'=>$params['active_id']));
            return $this->splash('error',null,$msg,true);
        }
        // $this->sellerlog('申请活动。活动ID是 '.$apiData['activity_id']);
        $url = url::action('topshop_ctl_activity_expert_comment@commentList', array('game_id'=>$params['comment']['game_id']));
        $msg = app::get('topshop')->_('专家点评保存成功');
        return $this->splash('success',$url,$msg,true);

    }

    public function commentDelete()
    {
        $expertCommentId = input::get('expert_comment_id', false);
        if( !$expertCommentId )
        {
            $msg = '删除失败';
            return $this->splash('error','',$msg,true);
        }

        try
        {
            $params['expert_comment_id'] = (int)$expertCommentId;
            $params['shop_id'] = (int)$this->shopId;
            app::get('topshop')->rpcCall('activity.vote.expert.comment.delete',$params);
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }

        $this->sellerlog('删除评论。账号ID是 '.$expertCommentId);
        $msg = '删除成功';
        $url = url::action('topshop_ctl_activity_expert_comment@commentList');
        return $this->splash('success',$url,$msg,true);
    }
}