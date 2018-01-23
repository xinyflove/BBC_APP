<?php
class sysactivityvote_api_activeTree {

    public $apiDescription = "获取礼品活动树列表";

    public function getParams()
    {
        $return['params'] = array(
            'page_no' => ['type'=>'int','valid'=>'','default'=>'','example'=>'','desc'=>'分页当前页数,默认为1'],
            'page_size' => ['type'=>'int','valid'=>'','default'=>'','example'=>'','desc'=>'每页数据条数,默认10条'],
            'fields' => ['type'=>'field_list','valid'=>'','default'=>'','example'=>'','desc'=>'需要的字段','default'=>'','example'=>''],
            'orderBy' => ['type'=>'string','valid'=>'','default'=>'','example'=>'','desc'=>'排序，默认create_time asc'],
            'shop_id' => ['type'=>'int', 'valid'=>'', 'default'=>'0', 'example'=>'1', 'desc'=>'店铺ID'],
            'active_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'desc'=>'礼品活动id,多个用逗号连接'],
            'active_name' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'desc'=>'礼品活动名称'],
            'active_type' => ['type'=>'string', 'valid'=>'required|in:vote,blue_eyes', 'default'=>'', 'example'=>'', 'desc'=>'活动类型'],
            'deleted' => ['type'=>'int', 'valid'=>'', 'default'=>'0', 'example'=>'0', 'desc'=>'礼品活动是否删除,0默认1删除'],
        );

        return $return;
    }

    public function activeList($params)
    {
        $objMdlActive = app::get('sysactivityvote')->model('active');
        if(!$params['fields'])
        {
            $params['fields'] = 'active_id,shop_id,active_name,active_type';
        }
        $curr_time = time();
        $filter = array();
        $filter['active_type'] = $params['active_type'];
        $filter['active_start_time|sthan'] = $curr_time;
        $filter['active_end_time|bthan'] = $curr_time;
        if($params['shop_id']) $filter['shop_id'] = $params['shop_id'];
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

        $supplierList = app::get('sysshop')->model('supplier')
            ->getList('supplier_id,supplier_name');
        $supplierList = array_column($supplierList,'supplier_name','supplier_id');

        $giftMdl = app::get('sysactivityvote')->model('gift');
        foreach ($activeData as &$d)
        {
            $giftList = $giftMdl->getList('*', array('active_id'=>$d['active_id'],'deleted'=>0), 0, -1);
            foreach ($giftList as &$g){
                $g['supplier_name'] = $supplierList[$g['supplier_id']];
            }
            unset($g);
            $d['gifts'] = $giftList;
        }
        unset($d);

        $result = array(
            'data' => $activeData,
            //'total' => $activeTotal,
        );

        return $result;
    }
}