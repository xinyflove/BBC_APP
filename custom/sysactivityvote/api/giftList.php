<?php
class sysactivityvote_api_giftList {

    public $apiDescription = "获取赠品列表";

    public function getParams()
    {
        $return['params'] = array(
            'page_no' => ['type'=>'int','valid'=>'','default'=>'','example'=>'','desc'=>'分页当前页数,默认为1'],
            'page_size' => ['type'=>'int','valid'=>'','default'=>'','example'=>'','desc'=>'每页数据条数,默认10条'],
            'fields' => ['type'=>'field_list','valid'=>'','default'=>'','example'=>'','desc'=>'需要的字段','default'=>'','example'=>''],
            'orderBy' => ['type'=>'string','valid'=>'','default'=>'','example'=>'','desc'=>'排序，默认create_time asc'],
            'shop_id' => ['type'=>'int', 'valid'=>'required', 'default'=>'', 'example'=>'1', 'desc'=>'店铺ID'],
            'active_id' => ['type'=>'int', 'valid'=>'required', 'default'=>'', 'example'=>'1', 'desc'=>'投票活动id'],
			'gift_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'desc'=>'赠品id,多个用逗号连接'],
            'gift_name' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'desc'=>'赠品名称'],
            'supplier_id' => ['type'=>'int', 'valid'=>'', 'default'=>'', 'example'=>'1', 'desc'=>'供应商id'],
            'deleted' => ['type'=>'int', 'valid'=>'', 'default'=>'', 'example'=>'', 'desc'=>'投票分类是否删除,0默认1删除'],
        );

        return $return;
    }

    public function giftList($params)
    {
        $objMdlGift = app::get('sysactivityvote')->model('gift');
        if(!$params['fields'])
        {
            $params['fields'] = '*';
        }
		$filter = array();
		$filter['shop_id'] = $params['shop_id'];
		$filter['active_id'] = $params['active_id'];
        if($params['gift_id']) $filter['gift_id'] = explode(',',$params['gift_id']);
        if($params['gift_name']) $filter['gift_name|has'] = $params['gift_name'];
        if($params['supplier_id']) $filter['supplier_id'] = $params['supplier_id'];
        $filter['deleted'] = $params['deleted'] ? $params['deleted'] : 0;

        $giftTotal = $objMdlGift->count($filter);
        $pageTotal = ceil($giftTotal/$params['page_size']);
        $page =  $params['page_no'] ? $params['page_no'] : 1;
        $limit = $params['page_size'] ? $params['page_size'] : 10;
        $currentPage = $pageTotal < $page ? $pageTotal : $page;
        $offset = ($currentPage-1) * $limit;

        $orderBy  = $params['orderBy'] ? $params['orderBy'] : ' create_time ASC';
        $giftData = $objMdlGift->getList($params['fields'], $filter, $offset, $limit, $orderBy);
        $result = array(
            'data' => $giftData,
            'total' => $giftTotal,
        );

        return $result;
    }
}