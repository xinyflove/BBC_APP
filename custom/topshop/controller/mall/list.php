<?php
/**
 * User: xinyufeng
 * Time: 2018-10-25 10:30
 * Desc: 广电优选商品列表
 */
class topshop_ctl_mall_list extends topshop_mall_controller {

    public $limit = 40;// 每页显示数量

    /**
     * 商品列表页
     * @return html
     */
    public function index()
    {
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
        $pagedata['breadcrumb'] = $this->__breadcrumb($params);
        // 过滤条件
        $pagedata['screen'] = $this->__screen($params);
        
        $commonPageData = $this->_getCommonPageData();
        $pagedata = array_merge($pagedata, $commonPageData);

        return $this->page('topshop/mall/list.html', $pagedata);
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
        $filter = array();
        if(isset($params['title'])) $filter['title'] = $params['title'];
        if($params['cat_id']) $filter['cat_id'] = $params['cat_id'];
        if($params['brand_id']) $filter['brand_id'] = $params['brand_id'];

        return $filter;
    }

    /**
     * 面包屑
     * @param $params
     * @return array
     */
    private function __breadcrumb($params)
    {
        //面包屑数据
        $breadcrumb = array(['url'=>'','title'=>'全部商品']);

        if($params['cat_id'])   // 如果有分类id
        {
            $cat = app::get('topc')->rpcCall('category.cat.get.data',array('cat_id'=>intval($params['cat_id'])));
            $breadcrumb = array(
                ['url'=>url::action('topshop_ctl_mall_list@index',array('cat_id'=>$cat['lv1']['cat_id'])),'title'=>$cat['lv1']['cat_name']],
                ['url'=>url::action('topshop_ctl_mall_list@index',array('cat_id'=>$cat['lv2']['cat_id'])),'title'=>$cat['lv2']['cat_name']],
                ['url'=>url::action('topshop_ctl_mall_list@index',array('cat_id'=>$cat['lv3']['cat_id'])),'title'=>$cat['lv3']['cat_name']],
            );

            if($params['brand_id']) // 如果有品牌id
            {
                $brands = app::get('topc')->rpcCall('category.brand.get.list',array('brand_id'=>$params['brand_id'],'fields'=>'brand_id,brand_name'));
                $title = (count($brands) >1) ? "品牌：" : '';
                foreach($brands as $brand)
                {
                    $title .= $brand['brand_name']."、";
                }
                $title = rtrim($title,"、");
                $breadcrumb[] = ['url'=>'','title'=>$title];
            }
        }

        if($params['title'])
        {
            $breadcrumb = array(
                ['url'=>'','title'=>'全部商品'],
                ['url'=>'','title'=>$params['title']],
            );
        }

        return $breadcrumb;
    }

    private function __screen($params)
    {
        if($params['title'])
        {
            $params['search_keywords'] = $params['title'];
            unset($params['title']);
        }
        $filterItems = app::get('topc')->rpcCall('item.search.filterItems',$params);
        //var_dump($filterItems);die;
        return $filterItems;
    }
    
}