<?php
class topwap_ctl_cart extends topwap_controller{

    public function addCart()
    {
        $mode = input::get('mode');
        $obj_type = input::get('obj_type');
        // 王衍生-2018/06/21-start
        // 售卖者id
        $params['seller_id'] = input::get('seller_id', 0);
        // 王衍生-2018/06/21-end
        $params['obj_type'] = $obj_type ? $obj_type : 'item';
        $params['mode'] = $mode ? $mode : 'cart';
        $params['user_id'] = userAuth::id();
        if( $params['obj_type']=='package' )
        {
            $package_id = input::get('package_id');
            $params['package_id'] = intval($package_id);
            $skuids = input::get('package_item');
            /*modify_2017/12/19_by_wanghaichao_start*/
            /*$tmpskuids = array_column($skuids, 'sku_id');
            $params['package_sku_ids'] = implode(',', $tmpskuids);*/
            //直接传过来用逗号拼接好
            $params['package_sku_ids'] = input::get('package_sku_ids');
            /*modify_2017/12/19_by_wanghaichao_end*/
            $params['quantity'] = input::get('package-item.quantity',1);
        }
        if( $params['obj_type']=='item')
        {
            $quantity = input::get('item.quantity');
            $params['quantity'] = $quantity ? $quantity : 1; //购买数量，如果已有购买则累加
            $params['sku_id'] = intval(input::get('item.sku_id'));
        }
		/*add_2018/12/5_by_wanghaichao_start*/
		//判断是不是黑名单用户
		if(userAuth::check()){
			$user_id=userAuth::id();
			$shop_id=$this->getShopId($params['sku_id']);
			$black = app::get('topwap')->rpcCall('blacklist.user',array('shop_id'=>$shop_id,'user_id'=>$user_id));
			if($black=='YES'){
                //$msg = app::get('topwap')->_('您的账户已被限制购买,如有问题请咨询卖家!');
				//2018/12/6 915提出修改
				$msg = app::get('topwap')->_('很抱歉，您的账号存在异常');
                return $this->splash('error',null,$msg,true);
			}
		}
		/*add_2018/12/5_by_wanghaichao_end*/
        try
        {
            $data = kernel::single('topwap_cart')->addCart($params);
            if( $data === false )
            {
                $msg = app::get('topwap')->_('加入购物车失败!');
                return $this->splash('error',null,$msg,true);
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }

        $msg = app::get('topwap')->_('成功加入购物车');
        $url = "";
        if( $params['mode'] == 'fastbuy' )
        {
            $url = url::action('topwap_ctl_cart_checkout@index',array('mode'=>'fastbuy') );
            $msg = "";
        }
        return $this->splash('success',$url,$msg,true);
    }

    /**
     * @return mixed
     * 小程序加入购物车
     */
    public function addMiniCart()
    {
        $request_data = input::get();
        $request_data['item'] = json_decode($request_data['item'], true);

        $mode = $request_data['mode'];
        $obj_type = $request_data['obj_type'];

        $params['obj_type'] = $obj_type ? $obj_type : 'item';
        $params['mode'] = $mode ? $mode : 'cart';
        $params['user_id'] = userAuth::id();

        if( $params['obj_type']=='item')
        {
            $quantity = $request_data['item']['quantity'];
            $params['quantity'] = $quantity ? $quantity : 1; //购买数量，如果已有购买则累加
            $params['sku_id'] = intval($request_data['item']['sku_id']);
        }
        try
        {
            $data = kernel::single('topwap_cart')->addCart($params);
            if( $data === false )
            {
                $msg = app::get('topwap')->_('加入购物车失败!');
                throw new Exception($msg);
            }
            $url = url::action('topwap_ctl_cart_checkout@miniOrder',array('mode'=>'mini') );
            $return_data['err_no'] = 0;
            $return_data['data'] = ['redirect_url'=>$url];
            $return_data['message'] = '';
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            $return_data['err_no'] = 1001;
            $return_data['data'] = [];
            $return_data['message'] = $msg;
        }
        return response::json($return_data);exit;

    }

    public function index()
    {
        $this->setLayoutFlag('cart');

        $pagedata['nologin'] = userAuth::check() ? false : true;
        $pagedata['defaultImageId'] = kernel::single('image_data_image')->getImageSetting('item');

        $cartData = kernel::single('topwap_cart')->getCartInfo();
        $pagedata['aCart'] = $cartData['resultCartData'];
        $pagedata['totalCart'] = $cartData['totalCart'];

        // 店铺可领取优惠券
        foreach ($pagedata['aCart'] as &$v) {
            $params = array(
                'page_no' => 0,
                'page_size' => 1,
                'fields' => '*',
                'shop_id' => $v['shop_id'],
                'platform' => 'wap',
                'is_cansend' => 1,
            );
            $couponListData = app::get('topwap')->rpcCall('promotion.coupon.list', $params, 'buyer');
            if($couponListData['count']>0)
            {
                $v['hasCoupon'] = 1;
            }
        }
		/*add_2017/9/28_by_wanghaichao_start*/
		if(isset($_SESSION['shop_id']) && !empty($_SESSION['shop_id'])){
			$pagedata['shop_id']=$_SESSION['shop_id'];
		}
		/*add_2017/9/28_by_wanghaichao_end*/
		/*add_2018/1/12_by_wanghaichao_start*/
		$thirdparty_app=$this->from_thirdparty_app();
		if($thirdparty_app){
			$_SESSION['thirdparty_app']='1';
		}
		/*add_2018/1/12_by_wanghaichao_end*/
        return $this->page('topwap/cart/index.html',$pagedata);
    }

    public function updateCart()
    {
        $postCartId = input::get('cart_id');
        $postCartNum = input::get('cart_num');
        $postPromotionId = input::get('promotionid');
        $params = array();
        foreach ($postCartId as $cartId => $v)
        {
            $data['mode'] = $mode;
            $data['obj_type'] = $obj_type;
            $data['cart_id'] = intval($cartId);
            $data['totalQuantity'] = intval($postCartNum[$cartId]);
            $data['selected_promotion'] = intval($postPromotionId[$cartId]);
            $data['user_id'] = userAuth::id();

            if($v=='1')
            {
                $data['is_checked'] = '1';
            }
            if($v=='0')
            {
                $data['is_checked'] = '0';
            }
            $params[] = $data;
        }

        try
        {
            foreach($params as $updateParams)
            {
                //$data = app::get('topwap')->rpcCall('trade.cart.update',$updateParams);
                $data = kernel::single('topwap_cart')->updateCart($updateParams);
                if( $data === false )
                {
                    $msg = app::get('topwap')->_('更新失败');
                    return $this->splash('error',null,$msg,true);
                }
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }

        $cartData = kernel::single('topwap_cart')->getCartInfo();
        $pagedata['aCart'] = $cartData['resultCartData'];

        // 临时统计购物车页总价数量等信息
        $totalWeight = 0;
        $totalNumber = 0;
        $totalPrice = 0;
        $totalDiscount = 0;
        foreach($cartData['resultCartData'] as $v)
        {
            $totalWeight += $v['cartCount']['total_weight'];
            $totalNumber += $v['cartCount']['itemnum'];
            $totalPrice += $v['cartCount']['total_fee'];
            $totalDiscount += $v['cartCount']['total_discount'];
        }
        $totalCart['totalWeight'] = $totalWeight;
        $totalCart['number'] = $totalNumber;
        $totalCart['totalPrice'] = $totalPrice;
        $totalCart['totalAfterDiscount'] = ecmath::number_minus(array($totalPrice, $totalDiscount));
        $totalCart['totalDiscount'] = $totalDiscount;
        $pagedata['totalCart'] = $totalCart;

        $pagedata['defaultImageId'] = kernel::single('image_data_image')->getImageSetting('item');

        foreach(input::get('cart_shop') as $shopId => $cartShopChecked)
        {
            $pagedata['selectShop'][$shopId] = $cartShopChecked=='on' ? true : false;
        }
        $pagedata['selectAll'] = input::get('cart_all')=='on' ? true : false;

        // 店铺可领取优惠券
        foreach ($pagedata['aCart'] as &$v) {
            $params = array(
                'page_no' => 0,
                'page_size' => 1,
                'fields' => '*',
                'shop_id' => $v['shop_id'],
                'platform' => 'wap',
                'is_cansend' => 1,
            );
            $couponListData = app::get('topwap')->rpcCall('promotion.coupon.list', $params, 'buyer');
            if($couponListData['count']>0)
            {
                $v['hasCoupon'] = 1;
            }
        }
        $pagedata['nologin'] = userAuth::check() ? false : true;
        $msg = view::make('topwap/cart/cart_main.html', $pagedata)->render();
        return $this->splash('success',null,$msg,true);
    }

    public function ajaxGetItemPromotion()
    {
        $itemId = intval(input::get('item_id'));
        $skuId = intval(input::get('sku_id'));
        $platform='wap';

        $itemPromotionTagInfo = app::get('systrade')->rpcCall('item.promotion.get', array('item_id'=>$itemId),'buyer');
        if(!$itemPromotionTagInfo)
        {
            return false;
        }
        $allPromotion = array();
        foreach($itemPromotionTagInfo as $v)
        {
            $basicPromotionInfo = app::get('systrade')->rpcCall('promotion.promotion.get', array('promotion_id'=>$v['promotion_id'], 'platform'=>$platform), 'buyer');
            if( $v['sku_id'] && !in_array($skuId,explode(',',$v['sku_id'])) )
            {
                continue;
            }

            if($basicPromotionInfo['valid']===true)
            {
                $allPromotion[$v['promotion_id']] = $basicPromotionInfo;
            }
        }
        // 倒序排序，购物车的默认促销规则选择最新添加的促销适用
        ksort($allPromotion);
        $pagedata['promotions'] = $allPromotion;
        return view::make('topwap/cart/item_promotion.html',$pagedata);
    }

    /**
     * @brief 删除购物车中数据
     *
     * @return
     */
    public function removeCart()
    {
        $postCartIdsData = input::get('cart_id');
        $tmpCartIds['cart_id'] = array_filter(explode(',',$postCartIdsData));
        $params['cart_id'] = implode(',',$tmpCartIds['cart_id']);
        if(!$params['cart_id'])
        {
            return $this->splash('error',null,'请选择需要删除的商品！',true);
        }
        $params['user_id'] = userAuth::id();

        try
        {
            $res = kernel::single('topwap_cart')->deleteCart($params);
            if( $res === false )
            {
                throw new Exception(app::get('topwap')->_('删除失败'));
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }
        return $this->splash('success',null,'删除成功',true);
    }

    public function ajaxBasicCart()
    {
        $cartData = kernel::single('topwap_cart')->getCartInfo();
        $pagedata['aCart'] = $cartData['resultCartData'];

        $pagedata['totalCart'] = $cartData['totalCart'];

        $pagedata['defaultImageId'] = kernel::single('image_data_image')->getImageSetting('item');

        foreach(input::get('cart_shop') as $shopId => $cartShopChecked)
        {
            $pagedata['selectShop'][$shopId] = $cartShopChecked=='on' ? true : false;
        }
        $pagedata['selectAll'] = input::get('cart_all')=='on' ? true : false;

        $msg = view::make('topwap/cart/cart_main.html', $pagedata)->render();
        return $this->splash('success',null,$msg,true);
    }
	
	/* action_name (par1, par2, par3)
	* 根据sku_id获取shop_id
	* author by wanghaichao
	* date 2018/12/5
	*/
	public function getShopId($sku_id){
		if(empty($sku_id)){
			return $_SESSION['shop_id'];
		}
		$sku=app::get('sysitem')->model('sku')->getRow('shop_id',array('sku_id'=>$sku_id));
		return $sku['shop_id'];
	}

}
