<?php

class topshop_ctl_selector_cart extends topshop_controller {

    public $limit = 14;

    public function loadSelectCartModal()
    {
        $isImageModal = true;
        // $pagedata = $this->searchItem(false);
        $pagedata['imageModal'] = true;
        $pagedata['textcol'] = input::get('textcol');
        $pagedata['view'] = input::get('view');
        $pagedata['shopCatList'] = app::get('syscart')->model('cart')->getList('cart_id,cart_name,frame_code,cart_number',array('shop_id'=>$this->shopId,'status'=>2));
		//echo "<pre>";print_r($pagedata);die();
        return view::make('topshop/selector/cart/index.html', $pagedata);
    }

    public function formatSelectedCartRow()
    { 
        $itemIds = input::get('item_id');    //这里是传过来item_id;还不知道怎么设置的,先这样写
        $extendView = input::get('view');
        $fields = 'cart_id,cart_name,frame_code';
        $searchParams['cart_id'] = implode(',', $itemIds);
        $pagedata['cartRow'] = app::get('syscart')->model('cart')->getRow($fields,$searchParams);

        return view::make('topshop/selector/cart/input-row.html', $pagedata);
    }

    //根据商家id和3级分类id获取商家所经营的所有品牌
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

    //根据商家类目id的获取商家所经营类目下的所有商品
    public function searchCart($json=true)
    {
        $shopId = $this->shopId;

        $keywords = input::get('searchname');
        $pages = input::get('pages');
		if(isset($keywords) && !empty($keywords)){
			$searchParams['frame_code|has']=$keywords;
		}
		$searchParams['status']=2;

        $fields = 'cart_id,cart_name,cart_number,frame_code';
		$cartModel=app::get('syscart')->model('cart');
        $count = $cartModel->count($searchParams);

        $pagedata['itemsList'] = $itemsList['list'];
        $pagedata['total'] = $count;
		$limit=$this->limit;
        $totalPage = ceil($count/$this->limit);
        $currentPage = ($totalPage < $page) ? $totalPage : $page; //防止传入错误页面，返回最后一页信息
        $offset = ($currentPage-1) * $limit;
		$cartlist = $cartModel->getList($fields,$searchParams,$offset,$limit);
	
        $pagersFilter['pages'] = time();
        $pagers = array(
            'link' => url::action('topshop_ctl_selector_cart@searchCart', $pagersFilter),
            'current' => $pages,
            'use_app' => 'topshop',
            'total' => $totalPage,
            'token' => time(),
        );
        $pagedata['pagers'] = $pagers;
		$pagedata['cartList']=$cartlist;

        return view::make('topshop/selector/cart/list.html', $pagedata);
    }

}
