<?php
class sysactivityvote_api_gameList {

    public $apiDescription = "获取参赛列表";

    public function getParams()
    {
        $return['params'] = array(
            'page_no' => ['type'=>'int','valid'=>'','default'=>'','example'=>'','desc'=>'分页当前页数,默认为1'],
            'page_size' => ['type'=>'int','valid'=>'','default'=>'','example'=>'','desc'=>'每页数据条数,默认10条'],
            'fields' => ['type'=>'field_list','valid'=>'','default'=>'','example'=>'','desc'=>'需要的字段','default'=>'','example'=>''],
            'orderBy' => ['type'=>'string','valid'=>'','default'=>'','example'=>'','desc'=>'排序，默认create_time asc'],
            'shop_id' => ['type'=>'int', 'valid'=>'required', 'default'=>'', 'example'=>'1', 'desc'=>'店铺ID'],
            'active_id' => ['type'=>'int', 'valid'=>'required', 'default'=>'', 'example'=>'1', 'desc'=>'投票活动id'],
			'game_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'desc'=>'参赛id,多个用逗号连接'],
            'game_name' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'desc'=>'参赛名称'],
            'game_number' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'desc'=>'参赛编码'],
            'cat_id' => ['type'=>'int', 'valid'=>'', 'default'=>'', 'example'=>'1', 'desc'=>'投票分类id'],
            'type_id' => ['type'=>'int', 'valid'=>'', 'default'=>'', 'example'=>'1', 'desc'=>'投票类型id'],
            'deleted' => ['type'=>'int', 'valid'=>'', 'default'=>'', 'example'=>'', 'desc'=>'投票分类是否删除,0默认1删除'],
        );

        return $return;
    }

    public function gameList($params)
    {
        $objMdlGame = app::get('sysactivityvote')->model('game');
        if(!$params['fields'])
        {
            $params['fields'] = '*';
        }
		$filter = array();
		$filter['shop_id'] = $params['shop_id'];
		$filter['active_id'] = $params['active_id'];
        if($params['game_id']) $filter['game_id'] = explode(',',$params['game_id']);
        if($params['game_name']) $filter['game_name|has'] = $params['game_name'];
        if($params['game_number']) $filter['game_number|has'] = $params['game_number'];
        if($params['cat_id']) $filter['cat_id'] = $params['cat_id'];
        if($params['type_id']) $filter['type_id'] = $params['type_id'];
        if(isset($params['is_game'])) $filter['is_game'] = $params['is_game'];
        $filter['deleted'] = $params['deleted'] ? $params['deleted'] : 0;

        $gameTotal = $objMdlGame->count($filter);
        $pageTotal = ceil($gameTotal/$params['page_size']);
        $page =  $params['page_no'] ? $params['page_no'] : 1;
        $limit = $params['page_size'] ? $params['page_size'] : 10;
        $currentPage = $pageTotal < $page ? $pageTotal : $page;
        $offset = ($currentPage-1) * $limit;

        $orderBy  = $params['orderBy'] ? $params['orderBy'] : ' create_time ASC';
        $gameData = $objMdlGame->getList($params['fields'], $filter, $offset, $limit, $orderBy);
        $result = array(
            'data' => $gameData,
            'total' => $gameTotal,
        );

        return $result;
    }
}