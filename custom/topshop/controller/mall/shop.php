<?php
/**
 * User: xinyufeng
 * Time: 2018-10-26 10:30
 * Desc: 广电优选店铺页面
 */
class topshop_ctl_mall_shop extends topshop_mall_controller {

    public $limit = 40;// 每页显示数量

    /**
     * 店铺首页商品列表页
     * @return html
     */
    public function index()
    {
        $objLibMallList = kernel::single('sysmall_data_list');

        $params = input::get();
        if(!$params['shop_id'])
        {
            $url = url::action('topshop_ctl_mall_list@index');
            header("Location:".$url);exit;
        }
        $shop_id = $this->shopId;
        $pagedata['initItemsId'] = $objLibMallList->getInitItemsId($shop_id);//店铺代售商品id数组
        $filter = array();

        $filter['shop_id'] = $params['shop_id'];
        if($params['title']) $filter['title'] = $params['title'];
        $count = $objLibMallList->getMallItemCount($filter);// 列表总数

        if($params['page_size'])
        {
            // 获取的每页显示数量
            $params['page_size'] = intval($params['page_size']);
            if($params['page_size'] > 0) $this->limit = $params['page_size'];
        }
        $params['pages'] = intval($params['pages']);// 获取当前页面数
        $params['pages'] = $params['pages'] > 0 ? $params['pages'] : 1;
        $offset = ($params['pages'] - 1) * $this->limit;
        $fields = 'item.item_id, item.title, item.image_default_id, item.price, item.cost_price, item.mkt_price, item.supply_price';
        
        $pagedata['list'] = $objLibMallList->getMallItemList($fields,$filter,$offset,$this->limit,$params['orderBy']);
        $pagedata['count'] = $count;

        //分页
        $tmpFilter = $params;
        unset($tmpFilter['pages']);
        $pagedata['pagers'] = $this->__pages($params['pages'], $pagedata['count'], $tmpFilter);

        return $this->page('topshop/mall/shop.html', $pagedata);
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
            'link'=>url::action('topshop_ctl_mall_shop@index', $filter),
            'current'=>$current,
            'total'=>$totalPage,
            'token'=>time(),
        );
        return $pagers;
    }
    
}