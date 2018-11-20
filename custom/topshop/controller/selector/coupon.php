<?php

class topshop_ctl_selector_coupon extends topshop_controller {
    public $limit = 10;

    public function loadSelectModal()
    {
        if(input::get('limit')){
            $pagedata['limit'] = input::get('limit');
        }

        // $isImageModal = true;
        $pagedata['imageModal'] = true;
        $pagedata['textcol'] = input::get('textcol');
        $pagedata['view'] = input::get('view');
        // $pagedata['shopCatList'] = app::get('topshop')->rpcCall('shop.authorize.cat',array('shop_id'=>$this->shopId));
        return view::make('topshop/selector/coupon/index.html', $pagedata);
    }

    public function loadGetCouponRow()
    {
        // ff(input::get('coupon_id'));
        // 优惠券id 以数组方式传递的，可以传多个
        $coupon_ids = input::get('coupon_id');
        $apiData = ['coupon_id' => $coupon_ids[0]];
        try{
            $coupon = app::get('topshop')->rpcCall('promotion.coupon.get', $apiData);

        }catch(Exception $e){
            $meg = $e->getMessage();
            return $this->splash('error', '', $msg, true);
        }

        return $this->splash('success', '', $coupon,true);
    }

    //根据商家类目id的获取商家所经营类目下的所有商品
    public function searchCoupon($json=true)
    {
        $coupon_name = input::get('searchname');
        $pages = input::get('pages');
        $limit = input::get('limit') ? input::get('limit') : $this->limit;
        $limit = 3;
        $params = array(
            'page_no' => intval($pages),
            'page_size' => intval($limit),
            'fields' =>'*',
            'shop_id'=> $this->shopId,
            'coupon_name'=> $coupon_name,
        );
        $couponListData = app::get('topshop')->rpcCall('promotion.coupon.list', $params,'seller');

        $pagedata['coupons_list'] = $couponListData['coupons'];
        $pagedata['total'] = $couponListData['count'];
        $totalPage = ceil($couponListData['count']/$limit);
        $filter = input::get();
        $filter['pages'] = time();
        $pagers = array(
            'link' => url::action('topshop_ctl_selector_coupon@searchCoupon', $filter),
            'current' => $pages,
            'use_app' => 'topshop',
            'total' => $totalPage,
            'token' => time(),
        );
        $pagedata['pagers'] = $pagers;
        $pagedata['now'] = time();

        return view::make('topshop/selector/coupon/list.html', $pagedata);
    }

}
