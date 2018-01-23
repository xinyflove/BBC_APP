<?php
// promotion.activity.list
class sysactivityvote_api_expert_comment_list{
    public $apiDescription = "获取专家点评列表";
    public function getParams()
    {
        $data['params'] = array(

            'shop_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'活动id'],
            'active_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'活动id'],
            'game_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'参赛对象id'],
            'game_name' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'参赛对象名称关键词'],
            'expert_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'活动id'],

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

        if($params['expert_id'])
        {
            $filter['expert_id'] = explode(',',$params['expert_id']);
        }

        if($params['game_name'])
        {
            $gameIdList = kernel::single('sysactivityvote_game')->getIdListByName($params['game_name']);
            if(empty($gameIdList))
            {
                return array();
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

        $objComment = kernel::single('sysactivityvote_expert_comment');
        $commentCount = $objComment->countComment($filter);
        //分页使用
        $pageTotal = ceil($commentCount/$params['page_size']);
        $page =  $params['page_no'] ? $params['page_no'] : 1;
        $limit = $params['page_size'] ? $params['page_size'] : 10;
        $currentPage = $pageTotal < $page ? $pageTotal : $page;
        $offset = ($currentPage-1) * $limit;

        $orderBy = $params['order_by'];
        if(!$params['order_by'])
        {
            $orderBy = "create_time desc";
        }
        $datalist = $objComment->getList($row,$filter,$offset,$limit,$orderBy);
        if(!$datalist)
        {
            return array();
        }

        foreach ($datalist as $key => &$value) {
            
            $value['shop_info'] = app::get('sysactivityvote')->rpcCall('shop.get',['shop_id' => $value['shop_id']]);

            $value['active_info'] = app::get('sysactivityvote')->rpcCall('sysactivityvote.active.get',['active_id' => $value['active_id']]);
            // jj($value['active_info']);
            $value['expert_info'] = app::get('sysactivityvote')->rpcCall('activity.vote.expert.get',['expert_id' => $value['expert_id']]);

            $value['game_info'] = app::get('sysactivityvote')->rpcCall('sysactivityvote.game.get',['game_id' => $value['game_id']]);
            
            // $user_name = app::get('sysactivityvote')->rpcCall('user.get.account.name', ['user_id' => $value['user_id']]);
            // $value['user_name'] = $user_name[$value['user_id']];
        }

        $result = array(
            'data' => $datalist,
            'count' => $commentCount,
        );

        return $result;
    }
}
