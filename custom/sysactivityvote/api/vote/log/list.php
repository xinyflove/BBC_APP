<?php
// promotion.activity.list
class sysactivityvote_api_vote_log_list{
    public $apiDescription = "获取投票日志列表";
    public function getParams()
    {
        $data['params'] = array(
            'shop_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'活动id'],

            'active_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'活动id'],

            'game_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'对象id'],
            'game_name' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'参赛对象名称关键词'],

            'start_time' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'投票开始时间'],
            
            'end_time' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'投票结束时间'],

            'ip' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'投票ip'],

            'deleted' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'是否删除了 0否 1是'],

            'page_no' => ['type'=>'int','valid'=>'|int','description'=>'分页当前页码,1<=no<=499','example'=>'','default'=>'1'],
            'page_size' =>['type'=>'int','valid'=>'|int','description'=>'分页每页条数(1<=size<=200)','example'=>'','default'=>'40'],
            'order_by' => ['type'=>'int','valid'=>'|string','description'=>'排序方式','example'=>'','default'=>'created_time desc'],
            'fields' => ['type'=>'field_list', 'valid'=>'', 'default'=>'activity_name', 'example'=>'', 'description'=>'查询字段'],
        );
        return $data;
    }
    public function getList($params)
    {
        if($params['shop_id'])
        {
            $filter['shop_id'] = explode(',',$params['shop_id']);
        }

        if($params['active_id'])
        {
            $filter['active_id'] = explode(',',$params['active_id']);
        }

        if($params['game_id'])
        {
            $filter['game_id'] = explode(',',$params['game_id']);
        }

        if($params['ip'])
        {
            $filter['ip'] = $params['ip'];
        }

        if($params['start_time'])
        {
            $filter['create_time|bthan'] = strtotime($params['start_time']);
        }

        if($params['end_time'])
        {
            $filter['create_time|sthan'] = strtotime($params['end_time']);
        }

        if($params['game_name'])
        {
            $gameIdList = kernel::single('sysactivityvote_game')->getIdListByName($params['game_name']);
            if(empty($gameIdList))
            {
                return [];
            }

            foreach ($gameIdList as $key => $value) {
                $filter['game_id'][] = $value['game_id'];
            }
        }

        $filter['deleted'] = empty($params['deleted']) ? 0 : 1;

        $row = "*";
        
        if($params['fields'])
        {
            $row = $params['fields'];
        }

        $objVoteLog = kernel::single('sysactivityvote_vote_log');
        $voteLogCount = $objVoteLog->countVoteLog($filter);
        //分页使用
        $pageTotal = ceil($voteLogCount/$params['page_size']);
        $page =  $params['page_no'] ? $params['page_no'] : 1;
        $limit = $params['page_size'] ? $params['page_size'] : 10;
        $currentPage = $pageTotal < $page ? $pageTotal : $page;
        $offset = ($currentPage-1) * $limit;

        $orderBy = $params['order_by'];
        if(!$params['order_by'])
        {
            $orderBy = "create_time desc";
        }
        $datalist = $objVoteLog->getList($row,$filter,$offset,$limit,$orderBy);
        if(!$datalist)
        {
            return array();
        }

        foreach ($datalist as $key => &$value) {
            
            $value['active_info'] = app::get('sysactivityvote')->rpcCall('sysactivityvote.active.get',['active_id' => $value['active_id']]);

            // $value['expert_info'] = app::get('sysactivityvote')->rpcCall('activity.vote.expert.get',['expert_id' => $value['expert_id']]);

            $value['game_info'] = app::get('sysactivityvote')->rpcCall('sysactivityvote.game.get',['game_id' => $value['game_id']]);

            // $user_name = app::get('sysactivityvote')->rpcCall('user.get.account.name', ['user_id' => $value['user_id']]);

            // $value['user_name'] = $user_name[$value['user_id']];
        }

        $result = array(
            'data' => $datalist,
            'count' => $voteLogCount,
        );

        return $result;
    }
}
