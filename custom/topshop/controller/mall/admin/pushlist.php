<?php
/**
 * User: xinyufeng
 * Time: 2018-10-26 10:30
 * Desc: 广电优选推送商品列表页
 */
class topshop_ctl_mall_admin_pushlist extends topshop_ctl_mall_admin_controller {

    public $limit = 10;// 每页显示数量

    public function index()
    {
        $objLibMallList = kernel::single('sysmall_data_list');

        $params = input::get();
        $filter = array('shop_id'=>$this->shopId);

        if($params['title']) $filter['title'] = $params['title'];
        if($params['status']) $filter['status'] = $params['status'];

        $mallParams['filter'] = $this->__filter($params);
        $count = $objLibMallList->getMallItemCount($mallParams);

        // 获取的每页显示数量
        $params['page_size'] = intval($params['page_size']);
        $params['page_size'] = $params['page_size'] > 0 ? $params['page_size'] : $this->limit;
        // 获取当前页面数
        $params['pages'] = intval($params['pages']);
        $params['pages'] = $params['pages'] > 0 ? $params['pages'] : 1;
        $mallParams['offset'] = ($params['pages'] - 1) * $params['page_size'];
        $mallParams['limit'] = $params['page_size'];
        $mallParams['orderBy'] = $params['orderBy'];
        $mallParams['fields'] = 'item.title, item.image_default_id, item.price, item.cost_price, item.supply_price,item.created_time,ROUND((item.price-item.supply_price)/item.price*100,1) AS profit, (store.store-store.freez) AS real_store,item.nospec,store.store,m_item.created_time as push_time,m_item.sale_type,m_item.status';

        // 列表数据
        $pagedata['list'] = $objLibMallList->getMallItemList($mallParams);
        $pagedata['count'] = $count;
        
        // 分页
        $tmpFilter = $params;
        unset($tmpFilter['pages']);
        $pagedata['filter'] = $tmpFilter;
        $pagedata['pagers'] = $this->__pages($params['pages'], $pagedata['count'], $pagedata['filter']);
        $pagedata['return_to_url'] = url::action('topshop_ctl_mall_admin_pushlist@index');
        $pagedata['status'] = $params['status'];

        $this->contentHeaderTitle = app::get('topshop')->_('推送商品列表');
        //var_dump($pagedata);die;
        return $this->page('topshop/mall/admin/push_list.html', $pagedata);
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
            'link'=>url::action('topshop_ctl_mall_admin_pushlist@index', $filter),
            'current'=>$current,
            'use_app' => 'topshop',
            'total'=>$totalPage,
            'token'=>time(),
        );
        return $pagers;
    }

    /**
     * 重构条件参数
     * @param $params
     * @return array
     */
    private function __filter($params)
    {
        $filter = array('shop_id'=>$this->shopId);
        if(isset($params['title'])) $filter['title'] = $params['title'];
        if(!empty($params['status'])) $filter['status'] = $params['status'];
        if($params['cat_id']) $filter['cat_id'] = $params['cat_id'];
        if($params['brand_id']) $filter['brand_id'] = $params['brand_id'];

        return $filter;
    }
}