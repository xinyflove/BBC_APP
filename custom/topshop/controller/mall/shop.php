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

        $objLibMallList = kernel::single('sysmall_data_list');

        $params = input::get();//var_dump($params);die;
        // 获取的每页显示数量
        $params['page_size'] = intval($params['page_size']);
        $params['page_size'] = $params['page_size'] > 0 ? $params['page_size'] : $this->limit;

        $mallParams['filter'] = $this->__filter($params);
        $count = $objLibMallList->getMallItemCount($mallParams);// 列表总数
        // 列表总数
        $pagedata['count'] = $count;
        // 过滤条件

        $pagedata['filter'] = $params;

        $pagedata['filter_json'] = json_encode($pagedata['filter']);
        // 面包屑数据
        //$pagedata['breadcrumb'] = $this->__breadcrumb($params);
        // 过滤条件
        //$pagedata['screen'] = $this->__screen($params);
        
        $commonPageData = $this->_getCommonPageData();
        $pagedata = array_merge($pagedata, $commonPageData);
		$pagedata['setting']=app::get('sysshop')->model('mall_setting')->getRow("*",array('shop_id'=>$params['shop_id']));
		if($pagedata['setting']['items']){
			$shopParams['filter']['item_id']=$pagedata['setting']['items'];
            $shopParams['fields'] = 'item.title, item.image_default_id, item.price, item.supply_price,item.created_time,item.shop_id, shop.shop_name, ROUND((item.price-item.supply_price)/item.price*100,1) AS profit, (store.store-store.freez) AS real_store,m_item.status';
			$pagedata['tj_item'] = $objLibMallList->getMallItemList($shopParams);
		}
		$pagedata['setting']['banner']=unserialize($pagedata['setting']['banner']);
		$pagedata['setting']['ad_pic']=unserialize($pagedata['setting']['ad_pic']);
		$pagedata['shop_id']=$this->shopId;
		
        // 店铺分类
		//foreach($pagedata['shopcat'] as $shopCatId=>&$row)
        //{
        //    if( $row['children'] )
        //    {
        //        $row['cat_id'] = $row['cat_id'].','.implode(',', array_column($row['children'], 'cat_id'));
       //     }
        //}
		$title=app::get('sysshop')->model('shop')->getRow('shop_name',array('shop_id'=>$params['shop_id']));
		$pagedata['title']=$title['shop_name'];
        return $this->page('topshop/mall/shop.html', $pagedata);
    }

    /**
     * 列表数据
     * @return mixed
     */
    public function listData()
    {
        $objLibMallList = kernel::single('sysmall_data_list');

        $params = input::get();
        // 获取的每页显示数量
        $params['page_size'] = intval($params['page_size']);
        $params['page_size'] = $params['page_size'] > 0 ? $params['page_size'] : $this->limit;
        // 获取当前页面数
        $params['pages'] = intval($params['pages']);
        $params['pages'] = $params['pages'] > 0 ? $params['pages'] : 1;

        $mallParams['filter'] = $this->__filter($params);
        $mallParams['offset'] = ($params['pages'] - 1) * $params['page_size'];
        $mallParams['limit'] = $params['page_size'];
        $mallParams['orderBy'] = $params['orderBy'];
        // 列表数据
        $pagedata['list'] = $objLibMallList->getMallItemList($mallParams);
        //var_dump($pagedata['list']);die;
        $commonPageData = $this->_getCommonPageData();
        $pagedata = array_merge($pagedata, $commonPageData);
		$pagedata['shop_id']=$this->shopId;
        return $this->page('topshop/mall/list_data.html', $pagedata);
    }

    /**
     * 重构条件参数
     * @param $params
     * @return array
     */
    private function __filter($params)
    {
        $filter = array('sale_type' => 0);// 过滤销售类型
        $filter['status'] = 'onsale';
        if(isset($params['title'])) $filter['title'] = $params['title'];
        if($params['cat_id']) $filter['cat_id'] = $params['cat_id'];
        if($params['brand_id']) $filter['brand_id'] = $params['brand_id'];
        if($params['shop_id']) $filter['shop_id'] = $params['shop_id'];

        return $filter;
    }


}