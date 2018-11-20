<?php
/**
 * Created by PhpStorm.
 * User: jiangyunhan
 * Date: 2018-05-30
 * Time: 13:55
 */


class topshop_ctl_selector_activity extends topshop_controller {
    public $limit = 14;

    public function loadSelectActivityModal()
    {
        if(input::get('limit')){
            $pagedata['limit'] = input::get('limit');
        }

        /*$agent_cat = app::get('topshop')->rpcCall('supplier.agent.category.list');
        $agent_cat_arr = array();
        $agent_cat_arr['0-0'] = '全部美食';
        foreach($agent_cat['data'] as $cat_v){
            $id = '0-'.$cat_v['agent_category_id'];
            $agent_cat_arr[$id] = $cat_v['agent_category_name'];
        }
        $pagedata['agent_cat_arr'] = $agent_cat_arr;*/

        return view::make('topshop/selector/activity/index.html', $pagedata);
    }

    public function formatSelectedActivityRow()
    {

        $activityIds = input::get('item_id');

        $activityIdsChunk = array_chunk($activityIds, 20);

        $activiyList = array();
        foreach( $activityIdsChunk as $value )
        {
            $searchParams = array(
                'agent_activity_id' => $value
            );
            $activityListData = app::get('syspromotion')->rpcCall('supplier.agent.shop.activity.search',$searchParams);

            foreach($activityListData as &$value){
                $agent_name_info = app::get('topshop')->rpcCall('supplier.agent.shop.get',['agent_shop_id'=>$value['agent_shop_id']]);
                $value['agent_shop_name'] = $agent_name_info['name'];
            }

            $activityList = array_merge($activiyList,$activityListData);
        }



        $pagedata['_input']['activitysList'] = $activityList;

        return view::make('topshop/selector/activity/input-row.html', $pagedata);
    }







    //获取所有线下店活动信息
    public function searchActivity($json=true)
    {

        $pages = input::get('pages');
        $limit = input::get('limit') ? input::get('limit') : $this->limit;
        $activity_name = input::get('searchname')?input::get('searchname'):'';
        $agent_shop_name = input::get('searchbrand')?input::get('searchbrand'):'';
        $searchParams = array(
            'page_no' => intval($pages),
            'page_size' => intval($limit),
        );
        if($agent_shop_name)
        {
            $agent_shop = app::get('syssupplier')->model('agent_shop')->getList('agent_shop_id',['name|has'=>$agent_shop_name]);
            $agent_shop_ids = array_column($agent_shop,'agent_shop_id');
            $searchParams['filter']['agent_shop_id|in'] = !empty($agent_shop_ids)?$agent_shop_ids:[-1];
        }
        if($activity_name)
        {
            $searchParams['filter']['activity_name|has'] = $activity_name;
        }

        $searchParams['fields'] = 'agent_activity_id,agent_shop_id,activity_name,activity_type,activity_value,value_max,value_min';
        $itemsList = app::get('topshop')->rpcCall('supplier.shop.agent.activity.list', $searchParams);



        foreach ($itemsList['data'] as &$value){
            $agent_name_info = app::get('topshop')->rpcCall('supplier.agent.shop.get',['agent_shop_id'=>$value['agent_shop_id']]);
            $value['agent_shop_name'] = $agent_name_info['name'];
        }

        $pagedata['itemsList'] = $itemsList['data'];
        $pagedata['total'] = $itemsList['data_count'];
        $totalPage = ceil($itemsList['data_count']/$limit);
        $filter = input::get();
        $filter['pages'] = time();
        $pagers = array(
            'link' => url::action('topshop_ctl_selector_activity@searchActivity', $filter),
            'current' => $pages,
            'use_app' => 'topshop',
            'total' => $totalPage,
            'token' => time(),
        );
        $pagedata['pagers'] = $pagers;

        return view::make('topshop/selector/activity/list.html', $pagedata);
    }




}