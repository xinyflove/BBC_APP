<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/22
 * Time: 14:06
 */
class ljstore_active_list extends ljstore_app_controller {

    /**
     * 获取活动列表
     * 参数:没有必须参数
     */
    public function getList()
    {
        $params = input::get();
        
        $objMdlActive = app::get('sysactivityvote')->model('active');
        if(!$params['fields'])
        {
            $params['fields'] = 'active_id,shop_id,active_name,active_type,active_start_time,active_end_time';
        }
        $curr_time = time();
        $filter = array();
        $filter['active_type'] = $params['active_type'] ? $params['active_type'] : 'blue_eyes';
        //$filter['active_start_time|sthan'] = $curr_time;
        $filter['active_end_time|bthan'] = $curr_time;
        $filter['deleted'] = $params['deleted'] ? $params['deleted'] : 0;
        if($params['shop_id']) $filter['shop_id'] = $params['shop_id'];
        if($params['active_id']) $filter['active_id'] = explode(',',$params['active_id']);
        if($params['active_name']) $filter['active_name|has'] = $params['active_name'];

        $limit = $params['page_size'] ? $params['page_size'] : 30;
        $activeTotal = $objMdlActive->count($filter);
        $pageTotal = ceil($activeTotal/$limit);
        $page =  $params['page_no'] ? $params['page_no'] : 1;
        $currentPage = $pageTotal < $page ? $pageTotal : $page;
        $offset = ($currentPage-1) * $limit;

        $orderBy  = $params['orderBy'] ? $params['orderBy'] : ' active_id DESC';
        $activeData = $objMdlActive->getList($params['fields'], $filter, $offset, $limit, $orderBy);

        $result = array(
            'data' => $activeData,
            'total' => $activeTotal,
        );

        $this->splash('0',$result);
    }
}