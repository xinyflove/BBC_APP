<?php
// promotion.activity.list
class sysactivityvote_api_gift_gain_list{
    public $apiDescription = "获取赠品获得记录列表";
    public function getParams()
    {
        $data['params'] = array(
            'active_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'活动id'],

            'shop_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'店铺id'],

            'gift_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'店铺id'],

            'user_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'用户id'],

            'user_name' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'用户名称、手机号'],

            'status' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'状态'],

            'deleted' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'是否删除了 0否 1是'],
            'start_time' => ['type'=>'integer', 'valid'=>'', 'description'=>'开始时间'],
            'end_time' => ['type'=>'integer', 'valid'=>'' , 'description'=>'结束时间'],

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

        if(isset($params['start_time|lthan'])){
            $filter['start_time|lthan'] = $params['start_time|lthan'];
        }

        if(isset($params['end_time|than'])){
            $filter['end_time|than'] = $params['end_time|than'];
        }

        if(isset($params['end_time|lthan'])){
            $filter['end_time|lthan'] = $params['end_time|lthan'];
        }

        if(isset($params['gift_id']))
        {
            $filter['gift_id'] = explode(',',$params['gift_id']);
        }

        if(isset($params['user_id']))
        {
            $filter['user_id'] = explode(',',$params['user_id']);
        }

        if($params['user_name'])
        {
            $userId = app::get('topshop')->rpcCall('user.get.account.id', ['user_name' => $params['user_name']], 'seller');
            if(empty($userId))
            {
                return [];
            }
            
            $filter['user_id'] = array_shift($userId);

        }

        if(isset($params['status']) && $params['status'] != '')
        {
            $filter['status'] = $params['status'];
        }

        $filter['deleted'] = empty($params['deleted']) ? 0 : 1;

        $row = "*";
        
        if($params['fields'])
        {
            $row = $params['fields'];
        }
        $objGiftGain = kernel::single('sysactivityvote_gift_gain');
        $giftGainCount = $objGiftGain->countGiftGain($filter);
        //分页使用
        $pageTotal = ceil($giftGainCount/$params['page_size']);
        $page =  $params['page_no'] ? $params['page_no'] : 1;
        $limit = $params['page_size'] ? $params['page_size'] : 10;
        $currentPage = $pageTotal < $page ? $pageTotal : $page;
        $offset = ($currentPage-1) * $limit;

        $orderBy = $params['order_by'];
        if(!$params['order_by'])
        {
            $orderBy = "create_time desc";
        }
        $datalist = $objGiftGain->getList($row,$filter,$offset,$limit,$orderBy);
        if(!$datalist)
        {
            return array();
        }

        foreach ($datalist as $key => &$value) {
            
            $supplier_info = app::get('sysactivityvote')->rpcCall('supplier.shop.get',['supplier_id' => $value['supplier_id']]);
            $value['supplier_name'] = $supplier_info['supplier_name'];

            $gift_info = app::get('sysactivityvote')->rpcCall('sysactivityvote.gift.get', ['gift_id' => $value['gift_id']]);
            $value['gift_name'] = $gift_info['gift_name'];

            $user_name = app::get('sysactivityvote')->rpcCall('user.get.account.name', ['user_id' => $value['user_id']]);
            $value['user_name'] = $user_name[$value['user_id']];
        }

        $result = array(
            'data' => $datalist,
            'count' => $giftGainCount,
        );

        return $result;
    }
}
