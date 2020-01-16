<?php
/**
 * User: xinyufeng
 * Time: 2018-10-26 10:30
 * Desc: 广电优选商品列表页
 */
class topshop_ctl_mall_admin_list extends topshop_ctl_mall_admin_controller {

    public $limit = 10;// 每页显示数量

    public function index()
    {
        $objLibMallList = kernel::single('sysmall_data_list');

        $params = input::get();
        $filter = array('shop_id'=>$this->shopId);

        if($params['title']) $filter['title'] = $params['title'];
        if($params['status']) $filter['status'] = $params['status'];
        if($params['supply_shop_name']) $filter['supply_shop_name'] = $params['supply_shop_name'];
        $count = $objLibMallList->getMallAgentCount($filter);

        if($params['page_size'])
        {
            // 获取的每页显示数量
            $params['page_size'] = intval($params['page_size']);
            if($params['page_size'] > 0) $this->limit = $params['page_size'];
        }
        $params['pages'] = intval($params['pages']);// 获取当前页面数
        $params['pages'] = $params['pages'] > 0 ? $params['pages'] : 1;
        $offset = ($params['pages'] - 1) * $this->limit;
        $fields = 'item.item_id, item.title, item.shop_id, item.image_default_id, item.price, item.cost_price, shop.shop_name, store.store, item.nospec, status.approve_status, item.created_time, item.modified_time, item.shop_cat_id, item.init_is_change,status.list_time,m_item.deleted';

        // 列表数据
        $pagedata['list'] = $objLibMallList->getMallAgentList($fields,$filter,$offset,$this->limit,$params['orderBy']);
        $pagedata['count'] = $count;

        $shopCat = $this->_getShopCat();
        foreach ($pagedata['list'] as &$item)
        {
            // 此处可优化为前端成生二维码
            $item['qr_code'] = $this->_qrCode($item['item_id']);
            if($item['shop_cat_id'])
            {
                $item['shop_cat_id'] = trim($item['shop_cat_id'], ',');
                $_shop_cat_ids = explode(',', $item['shop_cat_id']);
                $_shop_cat_names = array();
                foreach ($_shop_cat_ids as $cid)
                {
                    $_shop_cat_names[] = $shopCat[$cid];
                }
                $item['shop_cat_name'] = implode('<br>', $_shop_cat_names);
            }
            else
            {
                $item['shop_cat_name'] = '未设置';
            }
        }
        unset($item);

        // 分页
        $tmpFilter = $params;
        unset($tmpFilter['pages']);
        $pagedata['filter'] = $tmpFilter;
        $pagedata['pagers'] = $this->__pages($params['pages'], $pagedata['count'], $pagedata['filter']);
        $pagedata['return_to_url'] = url::action('topshop_ctl_mall_admin_list@index');
        $pagedata['status'] = $params['status'];

        $this->contentHeaderTitle = app::get('topshop')->_('拉取商品列表');
        return $this->page('topshop/mall/admin/list.html', $pagedata);
    }

    /**
     * 分页处理
     * @param $current  当前页
     * @param $total    总页数
     * @param $filter   查询条件
     * @return array
     */
    private function __pages($current, $total, $filter)
    {
        //处理翻页数据
        $current = ($current || $current <= 100 ) ? $current : 1;
        $filter['pages'] = time();

        if( $total > 0 ) $totalPage = ceil($total/$this->limit);
        $pagers = array(
            'link'=>url::action('topshop_ctl_mall_admin_list@index', $filter),
            'current'=>$current,
            'use_app' => 'topshop',
            'total'=>$totalPage,
            'token'=>time(),
        );
        return $pagers;
    }
}