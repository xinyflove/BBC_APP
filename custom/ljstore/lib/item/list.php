<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2018/6/1
 * Time: 16:20
 */
class ljstore_item_list extends ljstore_app_controller {

    /**
     * 参数
     * store_id:商城id
     * limit:每页数量 默认10
     * pages:当前页数 默认1
     * sign:签名
     */
    public function getList()
    {
        $params = input::get();
        $storeId = intval($params['store_id']);

        //商城信息
        $storeInfo = app::get('sysstore')->model('store')->getRow('*', array('store_id'=>$storeId));
        if(empty($storeInfo))
        {
            $this->splash('10001', '商城信息不存在!');
        }
        $shopId = $storeInfo['relate_shop_id'];

        $pages = $params['pages'];
        $limit = $params['limit'] ? $params['limit'] : 10;
        $searchParams = array(
            'shop_id' => $shopId,
            'brand_id' => NULL,
            'search_keywords' => '',
            'bn' => '',
            'page_no' => intval($pages),
            'page_size' => intval($limit),
        );
        $searchParams['fields'] = 'item_id,title,sub_title,image_default_id,price,created_time,modified_time';
        $itemsList = app::get('topshop')->rpcCall('item.search', $searchParams);
        if(!empty($itemsList['list']))
        {
            foreach ($itemsList['list'] as &$item)
            {
                $item['image_default_id'] = base_storager::modifier($item['image_default_id'], 't');
                $item['link'] = url::action('topwap_ctl_item_detail@index', array('item_id'=>$item['item_id']));;
            }
            unset($item);
        }

        $this->splash('0', $itemsList);
    }
}