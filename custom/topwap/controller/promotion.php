<?php
class topwap_ctl_promotion extends topwap_controller{

    public $limit = 10;

    /**
     * 通用的获取促销关联的商品，主要是购物车促销，如满减，满折，xy折
     * @return mixed 返回促销关联的商品信息，分页信息等
     */
    public function getPromotionItem()
    {
        $filter = input::get();
        $filter['action'] = 'topwap_ctl_promotion@getPromotionItem';
        $promotionInfo = app::get('topwap')->rpcCall('promotion.promotion.get', array('promotion_id'=>$filter['promotion_id']));
        if($promotionInfo['valid'])
        {
            $pagedata = $this->__commonPromotionItemList($filter, $promotionInfo);
            $pagedata['filter'] = $filter;
            $pagedata['shopinfo'] = app::get('topwap')->rpcCall('shop.get',['shop_id'=>$promotionInfo['shop_id']]);
            return $this->page("topwap/promotion/index.html", $pagedata);
        }
        else
        {
            return kernel::abort(404);
        }
    }

    /**
     * 返回优惠券关联的商品列表
     * @return mixed 返回优惠券关联商品的信息，分页信息等
     */
    public function getCouponItem()
    {
        $filter = input::get();
        $filter['action'] = 'topwap_ctl_promotion@getCouponItem';
        $promotionInfo = ['promotion_type'=>'coupon', 'rel_promotion_id'=>$filter['coupon_id']];
        $pagedata = $this->__commonPromotionItemList($filter, $promotionInfo);
        // 判断优惠券是否过期
        if(time() > $pagedata['promotionInfo']['canuse_end_time'])
        {
            kernel::abort(404);
            return;
        }
        $pagedata['filter'] = $filter;

        $couponInfo = app::get('syspromotion')->model('coupon')
            ->getRow('shop_id', array('coupon_id'=>$filter['coupon_id']));

        $pagedata['shopinfo'] = app::get('topwap')->rpcCall('shop.get',['shop_id'=>$couponInfo['shop_id']]);
        
        return $this->page("topwap/promotion/index.html", $pagedata);
    }

    /**
     * 返回促销关联的商品页面
     * @param  array $filter 获取促销关联商品所需的，分页
     * @param  array $promotionInfo 对应促销的促销id，促销类型
     * @return mixed 返回促销关联商品列表等信息
     */
    public function __commonPromotionItemList($filter, $promotionInfo)
    {
        if(!$filter['pages'])
        {
            $filter['pages'] = 1;
        }
        $pageSize = $this->limit;
        $params = array(
            'page_no' => intval($filter['pages']),
            'page_size' => intval($pageSize),
            'orderBy' => $filter['order_by'],
            'fields' =>'item_id,shop_id,title,image_default_id,price',
        );
        //获取促销商品列表
        $promotionItem = $this->__promotionItemList($promotionInfo, $params);
        $count = $promotionItem['total_found'];
        $promotionItemList = $promotionItem['list'];
        if( userAuth::check())
        {
            $pagedata['nologin'] = 1;
        }
        //处理翻页数据
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link' => url::action($filter['action'], $filter),
            'current' => $current,
            'total' => $total,
            'token' => $filter['pages'],
        );
        $pagedata['title'] = $promotionItem['promotionInfo']['promotion_tag'];
        $pagedata['promotionItemList']= $promotionItemList;
        $pagedata['count'] = $count;
        $pagedata['promotionInfo'] = $promotionItem['promotionInfo'];
        $pagedata['promotiontype'] = $promotionInfo['promotion_type'];
        $pagedata ['defaultImageId'] = kernel::single('image_data_image')->getImageSetting('item');
		/*add_2018/1/31_by_wanghaichao_start*/
		$shop_id=$pagedata['promotionInfo']['shop_id'];
        $pagedata['shopinfo'] = app::get('topwap')->rpcCall('shop.get',['shop_id'=>$shop_id]);

		if($pagedata['shopinfo']['shop_mold'] == 'tv')
		{
			$pagedata['shopinfo']['mold_class'] = 'icon_small_tv';
		}
		else if($pagedata['shopinfo']['shop_mold'] == 'broadcast')
		{
			$pagedata['shopinfo']['mold_class'] = 'icon_fm101';
		}
		else
		{
			$pagedata['shopinfo']['mold_class'] = 'icon_other_tv';
		}
		/*add_2018/1/31_by_wanghaichao_end*/
        return $pagedata;
    }



    //获取促销的类型以及商品数据
    private function __promotionItemList($promotionInfo,$params)
    {
        switch ($promotionInfo['promotion_type'])
        {
            case 'fullminus':
                $params['fullminus_id'] = $promotionInfo['rel_promotion_id'];
                $promotionItem = app::get('topwap')->rpcCall('promotion.fullminusitem.list', $params);
                break;
            case 'coupon':
                $params['coupon_id'] = $promotionInfo['rel_promotion_id'];
                $promotionItem = app::get('topwap')->rpcCall('promotion.couponitem.list', $params);
                break;
            case 'fulldiscount':
                $params['fulldiscount_id'] = $promotionInfo['rel_promotion_id'];
                $promotionItem = app::get('topwap')->rpcCall('promotion.fulldiscountitem.list', $params);
                break;
            case 'xydiscount':
                $params['xydiscount_id'] = $promotionInfo['rel_promotion_id'];
                $promotionItem = app::get('topwap')->rpcCall('promotion.xydiscountitem.list', $params);
                break;
        }

        return $promotionItem;
    }


    // ajax分页获取促销对应的商品列表
    public function ajaxGetPromotionItem()
    {
        $filter = input::get();
        if($filter['type']=='coupon')
        {
            $filter['action'] = 'topwap_ctl_promotion@getCouponItem';
            $promotionInfo = ['promotion_type'=>'coupon', 'rel_promotion_id'=>$filter['coupon_id']];
        }
        else
        {
            $filter['action'] = 'topwap_ctl_promotion@getPromotionItem';
            $promotionInfo = app::get('topwap')->rpcCall('promotion.promotion.get', array('promotion_id'=>$filter['promotion_id']));
        }
        $pagedata = $this->__commonPromotionItemList($filter, $promotionInfo);
        $pagedata['filter'] = $filter;
        // start add 王衍生 20170928
        $pagedata['shopinfo'] = app::get('topwap')->rpcCall('shop.get',['shop_id'=>$pagedata['promotionInfo']['shop_id']]);
		/*add_2018/1/31_by_wanghaichao_start*/
		if($pagedata['shopinfo']['shop_mold'] == 'tv')
		{
			$pagedata['shopinfo']['mold_class'] = 'icon_small_tv';
		}
		else if($pagedata['shopinfo']['shop_mold'] == 'broadcast')
		{
			$pagedata['shopinfo']['mold_class'] = 'icon_fm101';
		}
		else
		{
			$pagedata['shopinfo']['mold_class'] = 'icon_other_tv';
		}
		/*add_2018/1/31_by_wanghaichao_end*/
        // end add 王衍生 20170928
        return view::make('topwap/promotion/itemlist.html', $pagedata);
    }

    // 促销专题页
    public function ProjectPage($pageId)
    {
        $preview = input::get('preview', 0);
        // 获取促销页详情
        $data = app::get('topwap')->rpcCall('promotion.get.page.info', ['page_id'=> $pageId]);
        if(!$data || $data['used_platform'] !='wap')
        {
            kernel::abort(404);
            return false;
        }

        if($preview !=1)
        {
            // 判断活动是否已开始
            if($data['is_display'] == 0 || time() < $data['display_time'])
            {
                kernel::abort(404);
                return false;
            }
        }

        // 设置seo
        if($data['page_name'])
        {
            theme::setTitle($param['page_name']);
        }
        if($data['page_desc'])
        {
            theme::setDescription($data['page_desc']);
        }

        // 显示页面
        $this->setlayoutflag('promotion');
        $this->setLayout($data['page_tmpl']);

        return $this->page();
    }

	
	/* action_name (par1, par2, par3)
	* 优惠券领取分享的
	* author by wanghaichao
	* date 2018/1/31
	*/
	
    public function getCoupon()
    {
        $filter = input::get();
        $filter['action'] = 'topwap_ctl_promotion@getCoupon';
        $promotionInfo = ['promotion_type'=>'coupon', 'rel_promotion_id'=>$filter['coupon_id']];
        $pagedata = $this->__commonPromotionItemList($filter, $promotionInfo);
        $userId = userAuth::id();
        // 判断优惠券是否过期
       // if(time() > $pagedata['promotionInfo']['canuse_end_time'])
        //{
         //   kernel::abort(404);
        //    return;
        //}
        $pagedata['filter'] = $filter;
		$pagedata['promotionInfo']['canuse_start_time']=date('Y.m.d',$pagedata['promotionInfo']['canuse_start_time']);
		$pagedata['promotionInfo']['canuse_end_time']=date('Y.m.d',$pagedata['promotionInfo']['canuse_end_time']);
		//echo "<pre>";print_r($pagedata['promotionInfo']);die();
		if($userId){
			$row=app::get('sysuser')->model('user_coupon')->getRow('SUM(`price`) AS total',array('user_id'=>$userId,'coupon_id'=>$filter['coupon_id']));
			$pagedata['total']=$row['total'];
		}else{
			$pagedata['total']=0;
		}
        return $this->page("topwap/promotion/getcoupon.html", $pagedata);
    }
}