<?php
/**
 * Created by PhpStorm.
 * User: jiangyunhan
 * Date: 2018-05-30
 * Time: 13:55
 */


class topshop_ctl_selector_agent extends topshop_controller {
    public $limit = 14;

    public function loadSelectAgentsModal()
    {
        if(input::get('limit')){
            $pagedata['limit'] = input::get('limit');
        }

        $agent_cat = app::get('topshop')->rpcCall('supplier.agent.category.list');
        $agent_cat_arr = array();
        $agent_cat_arr['0-0'] = '全部美食';
        foreach($agent_cat['data'] as $cat_v){
            $id = '0-'.$cat_v['agent_category_id'];
            $agent_cat_arr[$id] = $cat_v['agent_category_name'];
        }
        $pagedata['agent_cat_arr'] = $agent_cat_arr;

        return view::make('topshop/selector/agent/index.html', $pagedata);
    }

    public function formatSelectedAgentsRow()
    {

        $agentIds = input::get('item_id');

        $agentIdsChunk = array_chunk($agentIds, 20);

        $agentsList = array();
        foreach( $agentIdsChunk as $value )
        {
            $searchParams = array(
                'agent_shop_id' => $value
            );
            $agentsListData = app::get('syspromotion')->rpcCall('supplier.agent.shop.search',$searchParams);
            $agentsList = array_merge($agentsList,$agentsListData);
        }



        $pagedata['_input']['agentsList'] = $agentsList;

        //$pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');

        return view::make('topshop/selector/agent/input-row.html', $pagedata);
    }





    public function getBrandList()
    {
        $shopId = $this->shopId;
        $catId = input::get('catId');
        $params = array(
            'shop_id'=>$shopId,
            'cat_id'=>$catId,
            'fields'=>'brand_id,brand_name,brand_url'
        );
        $brands = app::get('topshop')->rpcCall('category.get.cat.rel.brand',$params);
        return response::json($brands);
    }

    //获取所有线下店信息
    public function searchAgent($json=true)
    {

        $pages = input::get('pages');
        $limit = input::get('limit') ? input::get('limit') : $this->limit;
        $catId = input::get('catId');

        $catId_arr = explode('-',$catId);
        if(count($catId_arr)==2 && $catId_arr[1]!=0){
            $catId_info = $catId_arr[1];
            $searchParams = array(
                'agent_category_id' => $catId_info,
                'page_no' => intval($pages),
                'page_size' => intval($limit),
            );
        }else{
            //echo $keywords;exit;
            $searchParams = array(
                'page_no' => intval($pages),
                'page_size' => intval($limit),
            );
        }

        $searchParams['fields'] = 'agent_shop_id,name,agent_img_src';
        $itemsList = app::get('topshop')->rpcCall('supplier.agent.shop.list', $searchParams);

        $pagedata['itemsList'] = $itemsList['data'];
        $pagedata['total'] = $itemsList['data_count'];
        $totalPage = ceil($itemsList['data_count']/$limit);
        $filter = input::get();
        $filter['pages'] = time();
        $pagers = array(
            'link' => url::action('topshop_ctl_selector_agent@searchAgent', $filter),
            'current' => $pages,
            'use_app' => 'topshop',
            'total' => $totalPage,
            'token' => time(),
        );
        $pagedata['pagers'] = $pagers;

        return view::make('topshop/selector/agent/list.html', $pagedata);
    }

    public function getSkuByItemId()
    {
        $searchParams['fields'] = 'sku_id,item_id,title,image_default_id,price,brand_id,spec_info,status';
        $searchParams['item_id'] = input::get('itemId');
        $searchParams['shop_id'] = $this->shopId;
        $skusList = app::get('topshop')->rpcCall('sku.search', $searchParams);
        $pagedata['_input']['skusList'] = $skusList['list'];
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        return view::make('topshop/selector/agent/skus.html', $pagedata);
    }

    /**
     * 获取指定商品的sku，并且显示指定的sku
     */
    public function showSkuByitemId()
    {
        $searchParams['fields'] = 'sku_id,item_id,title,image_default_id,price,brand_id,spec_info,status';
        $searchParams['item_id'] = input::get('itemId');
        $searchParams['shop_id'] = $this->shopId;
        $skusList = app::get('topshop')->rpcCall('sku.search', $searchParams);
        $pagedata['_input']['skusList'] = $skusList['list'];
        $pagedata['_input']['sku_ids'] = explode(',',input::get('sku_id'));
        return view::make('topshop/selector/agent/show_skus.html', $pagedata);
    }
}