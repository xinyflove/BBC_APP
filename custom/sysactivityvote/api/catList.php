<?php
class sysactivityvote_api_catList {

    public $apiDescription = "获取投票分类列表";

    public function getParams()
    {
        $return['params'] = array(
            'page_no' => ['type'=>'int','valid'=>'','default'=>'','example'=>'','desc'=>'分页当前页数,默认为1'],
            'page_size' => ['type'=>'int','valid'=>'','default'=>'','example'=>'','desc'=>'每页数据条数,默认10条'],
            'fields' => ['type'=>'field_list','valid'=>'','default'=>'','example'=>'','desc'=>'需要的字段','default'=>'','example'=>''],
            'orderBy' => ['type'=>'string','valid'=>'','default'=>'','example'=>'','desc'=>'排序，默认create_time asc'],
            'shop_id' => ['type'=>'int', 'valid'=>'required', 'default'=>'', 'example'=>'1', 'desc'=>'店铺ID'],
            'active_id' => ['type'=>'int', 'valid'=>'required', 'default'=>'', 'example'=>'1', 'desc'=>'投票活动id'],
			'cat_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'desc'=>'投票分类id,多个用逗号连接'],
            'cat_name' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'desc'=>'投票分类名称'],
            'deleted' => ['type'=>'int', 'valid'=>'', 'default'=>'', 'example'=>'', 'desc'=>'投票分类是否删除,0默认1删除'],
        );

        return $return;
    }

    public function catList($params)
    {
        $objMdlCat = app::get('sysactivityvote')->model('cat');
        if(!$params['fields'])
        {
            $params['fields'] = '*';
        }
		$filter = array();
		$filter['shop_id'] = $params['shop_id'];
		$filter['active_id'] = $params['active_id'];
        if($params['cat_id']) $filter['cat_id'] = explode(',',$params['cat_id']);
        if($params['cat_name']) $filter['cat_name|has'] = $params['cat_name'];
        $filter['deleted'] = $params['deleted'] ? $params['deleted'] : 0;

        $catTotal = $objMdlCat->count($filter);
        $pageTotal = ceil($catTotal/$params['page_size']);
        $page =  $params['page_no'] ? $params['page_no'] : 1;
        $limit = $params['page_size'] ? $params['page_size'] : 10;
        $currentPage = $pageTotal < $page ? $pageTotal : $page;
        $offset = ($currentPage-1) * $limit;

        $orderBy  = $params['orderBy'] ? $params['orderBy'] : ' create_time ASC';
        $catData = $objMdlCat->getList($params['fields'], $filter, $offset, $limit, $orderBy);
        $result = array(
            'data' => $catData,
            'total' => $catTotal,
        );

        return $result;
    }
}