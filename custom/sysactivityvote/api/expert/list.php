<?php
// promotion.activity.list
class sysactivityvote_api_expert_list{
    public $apiDescription = "获取评审专家列表";
    public function getParams()
    {
        $data['params'] = array(
            'active_id' => ['type'=>'', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'活动id'],

            'shop_id' => ['type'=>'', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'活动id'],

            'expert_id' => ['type'=>'', 'valid'=>'', 'title'=>'专家id',  'example'=>'', 'desc'=>'活动id'],

            'search_keywords' => ['type'=>'string','valid'=>'','description'=>'搜索专家关键字','example'=>'','default'=>''],

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
        if($params['active_id'])
        {
            $filter['active_id'] = explode(',',$params['active_id']);
        }

        if($params['shop_id'])
        {
            $filter['shop_id'] = explode(',',$params['shop_id']);
        }

        if($params['expert_id'])
        {
            $filter['expert_id'] = explode(',',$params['expert_id']);
        }

        if($params['search_keywords'])
        {
            $filter['expert_name|has'] = $params['search_keywords'];
        }

        $filter['deleted'] = empty($params['deleted']) ? 0 : 1;

        $row = "*";

        if($params['fields'])
        {
            $row = $params['fields'];
        }

        $objexpert = kernel::single('sysactivityvote_expert');
        $expertCount = $objexpert->countExpert($filter);
        //分页使用
        $pageTotal = ceil($expertCount/$params['page_size']);
        $page =  $params['page_no'] ? $params['page_no'] : 1;
        $limit = $params['page_size'] ? $params['page_size'] : 10;
        $currentPage = $pageTotal < $page ? $pageTotal : $page;
        $offset = ($currentPage-1) * $limit;

        $orderBy = $params['order_by'];
        if(!$params['order_by'])
        {
            $orderBy = "create_time desc";
        }
        $datalist = $objexpert->getList($row,$filter,$offset,$limit,$orderBy);
        if(!$datalist)
        {
            return array();
        }

        $result = array(
            'data' => $datalist,
            'count' => $expertCount,
        );

        return $result;
    }
}
