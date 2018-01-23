<?php
class sysactivityvote_api_activeList {

    public $apiDescription = "获取礼品活动列表";

    public function getParams()
    {
        $return['params'] = array(
            'page_no' => ['type'=>'int','valid'=>'','default'=>'','example'=>'','desc'=>'分页当前页数,默认为1'],
            'page_size' => ['type'=>'int','valid'=>'','default'=>'','example'=>'','desc'=>'每页数据条数,默认10条'],
            'fields' => ['type'=>'field_list','valid'=>'','default'=>'','example'=>'','desc'=>'需要的字段','default'=>'','example'=>''],
            'orderBy' => ['type'=>'string','valid'=>'','default'=>'','example'=>'','desc'=>'排序，默认create_time asc'],
            'shop_id' => ['type'=>'int', 'valid'=>'required', 'default'=>'', 'example'=>'1', 'desc'=>'店铺ID'],
            'active_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'desc'=>'礼品活动id,多个用逗号连接'],
            'active_name' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'desc'=>'礼品活动名称'],
            'deleted' => ['type'=>'int', 'valid'=>'', 'default'=>'0', 'example'=>'0', 'desc'=>'礼品活动是否删除,0默认1删除'],
        );

        return $return;
    }

    public function activeList($params)
    {
        $objMdlActive = app::get('sysactivityvote')->model('active');
        if(!$params['fields'])
        {
            $params['fields'] = '*';
        }
        $filter = array();
		$filter['shop_id'] = $params['shop_id'];
		if($params['active_id']) $filter['active_id'] = explode(',',$params['active_id']);
		if($params['active_name']) $filter['active_name|has'] = $params['active_name'];
		$filter['deleted'] = $params['deleted'] ? $params['deleted'] : 0;

        $activeTotal = $objMdlActive->count($filter);
        $pageTotal = ceil($activeTotal/$params['page_size']);
        $page =  $params['page_no'] ? $params['page_no'] : 1;
        $limit = $params['page_size'] ? $params['page_size'] : 10;
        $currentPage = $pageTotal < $page ? $pageTotal : $page;
        $offset = ($currentPage-1) * $limit;

        $orderBy  = $params['orderBy'] ? $params['orderBy'] : ' active_id DESC';
        $activeData = $objMdlActive->getList($params['fields'], $filter, $offset, $limit, $orderBy);
        $result = array(
            'data' => $activeData,
            'total' => $activeTotal,
        );

        return $result;
    }
}