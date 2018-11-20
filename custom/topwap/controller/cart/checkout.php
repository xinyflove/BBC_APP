<?php
class topwap_ctl_cart_checkout extends topwap_controller
{

    public $payType = array(
        'online' => '线上支付',
        'offline' => '货到付款',
    );
    //网页下单页面
    public function index()
    {
        header("cache-control: no-store, no-cache, must-revalidate");
        $this->setLayoutFlag('order_index');

        try {
            $pagedata = $this->getCommonData();
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
            if ($error_msg === 'NONE_CART') {
                $resetUrl = url::action('topwap_ctl_default@index');
                return $this->splash('error', $resetUrl);
            } else {
                return $this->splash('error', null, $error_msg);
            }
        }
        return $this->page('topwap/cart/checkout/index.html', $pagedata);
    }

    /**
     * @return mixed
     * 小程序下单页面
     */
    public function miniOrder()
    {
        header("cache-control: no-store, no-cache, must-revalidate");
        try {
            $page_data = $this->getCommonData();
            $return_data['err_no'] = 0;
            $return_data['data'] = $page_data;
            $return_data['message'] = '';
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
            if ($error_msg === 'NONE_CART') {
                url::action('topwap_ctl_miniprogram_goods@goodsList');
                $return_data['err_no'] = 1004;
                $return_data['data'] = [];
                $return_data['message'] = '购物车数据不存在';
            } else {
                $return_data['err_no'] = 1001;
                $return_data['data'] = [];
                $return_data['message'] = $error_msg;
            }
        }

        return response::json($return_data);
        exit;
    }

     //结算页收货地址列表
    public function addrList()
    {
        $pagedata['mode'] = input::get('mode');
        if (intval(input::get('addr_id'))) {
            $pagedata['default_id'] = input::get('addr_id');
        }
        $pagedata['userAddrList'] = $this->__getAddrList();
        return $this->page('topwap/cart/checkout/addr_list.html', $pagedata);
    }

    //结算页支付方式和配送方式列表
    public function deliveryList()
    {
        $postdata = input::get();
        $isDefault = 1;
        if ($postdata['shipping_type'] && $postdata['shipping_type'] != "express") {
            $isDefault = 0;
        }

        $pagedata['dtyList'][] = array('shipping_type' => 'express', 'name' => '快递配送', 'isDefault' => $isDefault);

        $ifOpenZiti = app::get('syslogistics')->getConf('syslogistics.ziti.open');

        if ($ifOpenZiti == 'true' && $postdata['shop_type'] == "self") {
            $params['user_id'] = userAuth::id();
            $params['addr_id'] = $postdata['addr_id'];
            $params['fields'] = "area";
            $addrInfo = app::get('topwap')->rpcCall('user.address.info', $params);
            $area = explode(':', $addrInfo['area']);
            $area = implode(',', explode('/', $area[1]));
            $zitiData = app::get('topwap')->rpcCall('logistics.ziti.list', array('area_id' => $area));

            foreach ($zitiData as $key => $value) {
                if ($postdata['ziti_id'] == $value['ziti_id']) {
                    $zitiData[$key]['isDefault'] = 1;
                } else {
                    $zitiData[$key]['isDefault'] = 0;
                }
            }

            $isDefault = $postdata['shipping_type'] == "ziti" ? 1 : 0;
            if ($zitiData) {
                $pagedata['dtyList'][] = array('shipping_type' => 'ziti', 'name' => '上门自提', 'isDefault' => $isDefault);
                $pagedata['zitiDataList'] = $zitiData;
            }
        }

        return response::json($pagedata);
        exit;;
    }

    /**
     * @brief 获取上门自取的地址列表
     *
     * @return html
     */
    public function getZitiList()
    {
        $postData = input::get();
        return $this->page('topwap/cart/checkout/ziti_list.html', $pagedata);
    }

    public function getCouponList()
    {
        $shopId = intval(input::get('shop_id'));
        $pagedata['couponlist'] = $this->__getCoupons($shopId);
        $pagedata['shop_id'] = $shopId;
        return $this->page('topwap/cart/checkout/coupon_list.html', $pagedata);
    }

    private function __getCoupons($shop_id)
    {
        // 默认取100个优惠券，用作一页显示，一般达不到这个数量一个店铺
        $params = array(
            'page_no' => 0,
            'page_size' => 100,
            'fields' => '*',
            'user_id' => userAuth::id(),
            'shop_id' => intval($shop_id),
            'is_valid' => 1,
            'platform' => 'wap',
        );
        $couponListData = app::get('topwap')->rpcCall('user.coupon.list', $params, 'buyer');
        $couponList = $couponListData['coupons'];
		/*add_2018/5/28_by_wanghaichao_start*/
        // if ($couponList) {
        //     foreach ($couponList as $k => &$v) {
        //         $v['price'] = sprintf("%.2f", $v['price']);
        //         $v['limit_money'] = sprintf("%.2f", $v['limit_money']);
        //     }
        // }
		/*add_2018/5/28_by_wanghaichao_end*/
        return $couponList;
    }

    private function __getAddrList()
    {
        $params['user_id'] = userAuth::id();
        //会员收货地址
        $userAddrList = app::get('topwap')->rpcCall('user.address.list', $params, 'buyer');
        $count = $userAddrList['count'];
        $userAddrList = $userAddrList['list'];

        foreach ($userAddrList as $key => $value) {
            $userAddrList[$key]['area'] = explode(":", $value['area'])[0];
        }
        return $userAddrList;
    }

    /**
     * @brief 计算购物车金额
     *
     * @return
     */

    public function total()
    {
        $postData = input::get();
        if ($postData['current_shop_id']) {
            $current_shop_id = $postData['current_shop_id'];
            unset($postData['current_shop_id']);
        }

        if ($addrId = $postData['addr_id']) {
            $params['user_id'] = userAuth::id();
            $params['addr_id'] = $addrId;
            $params['fields'] = 'area';
            $addr = app::get('topwap')->rpcCall('user.address.info', $params, 'buyer');
            list($regions, $region_id) = explode(':', $addr['area']);
        }

        $cartFilter['mode'] = $postData['mode'] ? $postData['mode'] : 'cart';
        $cartFilter['needInvalid'] = $postData['checkout'] ? false : true;
        $cartFilter['platform'] = 'wap';
        $cartFilter['user_id'] = userAuth::id();
        $cartInfo = app::get('topwap')->rpcCall('trade.cart.getCartInfo', $cartFilter, 'buyer');

        $allPayment = 0;
        $objMath = kernel::single('ectools_math');

        foreach ($cartInfo['resultCartData'] as $shop_id => $tval) {
            $totalParams = array(
                'discount_fee' => $tval['cartCount']['total_discount'],
                'total_fee' => $tval['cartCount']['total_fee'],
                'total_weight' => $tval['cartCount']['total_weight'],
                'shop_id' => $tval['shop_id'],
                'shipping_type' => $postData['shipping'][$tval['shop_id']]['shipping_type'],
                'region_id' => $region_id ? str_replace('/', ',', $region_id) : '0',
                'usedCartPromotionWeight' => $tval['usedCartPromotionWeight'],
                'usedToPostage' => json_encode($tval['cartByDlytmpl']),
            );
            $totalInfo = app::get('topwap')->rpcCall('trade.price.total', $totalParams, 'buyer');
            $trade_data['allPayment'] = $objMath->number_plus(array($trade_data['allPayment'], $totalInfo['payment']));
            $trade_data['allPostfee'] = $objMath->number_plus(array($trade_data['allPostfee'], $totalInfo['post_fee']));
            $trade_data['disCountfee'] = $objMath->number_plus(array($trade_data['disCountfee'], $totalInfo['discount_fee']));
            if ($current_shop_id && $shop_id != $current_shop_id) {
                continue;
            }

            $trade_data['shop'][$shop_id]['payment'] = $totalInfo['payment'];
            $trade_data['shop'][$shop_id]['total_fee'] = $totalInfo['total_fee'];
            $trade_data['shop'][$shop_id]['discount_fee'] = $totalInfo['discount_fee'];
            $trade_data['shop'][$shop_id]['obtain_point_fee'] = $totalInfo['obtain_point_fee'];
            $trade_data['shop'][$shop_id]['post_fee'] = $totalInfo['post_fee'];
            $trade_data['shop'][$shop_id]['totalWeight'] += $tval['cartCount']['total_weight'];
        }
        $trade_data['catItemPrice'] = json_encode($cartInfo['catItemPrice'], true);
        return response::json($trade_data);
        exit;
    }
    /**
     * 提交订单页 用户选择优惠券
     */
    public function useCoupon()
    {
        try {
            $mode = input::get('mode');
            $buyMode = $mode ? $mode : 'cart';
            $apiParams = array(
                'coupon_code' => input::get('coupon_code'),
                'mode' => $buyMode,
                'platform' => 'wap',
            );
            if (app::get('topwap')->rpcCall('promotion.coupon.use', $apiParams, 'buyer')) {
                //优惠券使用后清空购物券
                app::get('topc')->rpcCall('trade.cart.voucher.add', ['voucher_code' => -1, 'user_id' => userAuth::id(), 'platform' => 'wap']);
                $msg = app::get('topc')->_('使用优惠券成功！');
                return $this->splash('success', null, $msg, true);
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
            return $this->splash('error', null, $msg, true);
        }
    }

    public function cancelCoupon()
    {
        try {
            $apiParams = array(
                'coupon_code' => input::get('coupon_code'),
                'shop_id' => input::get('shop_id'),
            );
            if (app::get('topwap')->rpcCall('trade.cart.cartCouponCancel', $apiParams, 'buyer')) {
                $msg = app::get('topwap')->_('取消优惠券成功！');
                return $this->splash('success', null, $msg, true);
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
            return $this->splash('error', null, $msg, true);
        }
    }

    public function ajaxGetUserPoint()
    {
        $totalPrice = input::get('total_price');
        $totalPostFee = input::get('post_fee');
        $totalPrice = $totalPrice - $totalPostFee;
        $userId = userAuth::id();
        //根据会员id获取积分总值
        $points = app::get('topwap')->rpcCall('user.point.get', ['user_id' => $userId]);
        $setting = app::get('topwap')->rpcCall('point.setting.get');
        $pagedata['open_point_deduction'] = $setting['open.point.deduction'];
        $pagedata['point_deduction_rate'] = $setting['point.deduction.rate'];
        $pagedata['point_deduction_max'] = floor($setting['point.deduction.max'] * $totalPrice * $setting['point.deduction.rate']);
        $pagedata['points'] = $points['point_count'] ? $points['point_count'] : 0;
        //print_r($pagedata);exit;
        return response::json($pagedata);
        exit;
    }
    // 王衍生-2018/09/06-start
    public function ajaxGetUserLijin()
    {
        $objMath = kernel::single('ectools_math');
        // 支付总金额 包含运费
        $totalPrice = input::get('total_price');
        // 运费
        $totalPostFee = input::get('post_fee');
        $shop_id = input::get('shop_id');
        $totalPrice = $totalPrice - $totalPostFee;
        $userId = userAuth::id();

        $setting = app::get('topwap')->rpcCall('shop.cash.setting.get', ['shop_id' => $shop_id]);
        if(!$setting['is_open']){
            $pagedata = [];
        }else{
            $lijin = app::get('topwap')->rpcCall('user.lijin.get', ['user_id' => $userId, 'shop_id' => $shop_id]);
            $pagedata['open_lijin_deduction'] = $setting['is_open'];
            $pagedata['lijin_deduction_rate'] = $setting['deduct_rate'];
            $pagedata['lijin_deduction_max'] = $objMath->number_multiple([$setting['max_deduct_price'] / 100, $totalPrice, $setting['deduct_rate']]);
            $pagedata['lijin'] = $objMath->getOperationNumber($lijin['lijin'] ? $lijin['lijin'] : 0);
        }
        //print_r($pagedata);exit;
        return response::json($pagedata);
        exit;
    }
    // 王衍生-2018/09/06-end

    private function __postdata($postData, &$pagedata)
    {
        if ($postData['checkout_info']) {
            $checkout_info = json_decode($postData['checkout_info'], true);
            foreach ($checkout_info as $val) {
                $name = explode('[', $val['name']);
                $count = count($name);
                if ($count == 1) {
                    $postData[$name[0]] = $val['value'];
                } elseif ($count > 1) {
                    $value = $val['value'];
                    $key = array();
                    for ($i = $count - 1; $i >= 0; $i--) {
                        $k = rtrim($name[$i], ']');
                        if ($i == $count - 1) {
                            $key[$k] = $value;
                        } else {
                            $key[$k] = $key;
                        }

                        unset($key[rtrim($name[$i + 1], ']')]);
                    }
                    $pagedata = $this->__mergeArrays($pagedata, $key);
                }
            }

            //支付方式
            if (isset($postData['pay_type'])) {
                $pagedata['payType'] = array('pay_type' => $postData['pay_type'], 'name' => $this->payType[$postData['pay_type']]);
                unset($postData['pay_type']);
            } else {
                $pagedata['payType'] = array('pay_type' => 'online', 'name' => $this->payType['online']);
            }

            //配送方式
            if (isset($postData['shipping'])) {
                foreach ($postData['shipping'] as $k => $val) {
                    $pagedata['shipping'][$k] = $val;
                }
                unset($postData['shipping']);
            }

            //配送方式
            if (isset($postData['ziti'])) {
                foreach ($postData['ziti'] as $k => $val) {
                    $pagedata['ziti'][$k] = $val;
                }
                unset($postData['ziti']);
            }

            if (isset($postData['coupon'])) {
                foreach ($postData['coupon'] as $k => $val) {
                    $pagedata['coupon'][$k] = $val;
                }
                unset($postData['coupon']);
            }
            unset($postData['checkout_info']);
            $postData = array_merge($postData, $pagedata);
        }
    }

    private function __mergeArrays($Arr1, $Arr2)
    {
        foreach ($Arr2 as $key => $Value) {
            if (array_key_exists($key, $Arr1) && is_array($Value)) {
                $Arr1[$key] = $this->__mergeArrays($Arr1[$key], $Arr2[$key]);
            } else {
                $Arr1[$key] = $Value;
            }

        }

        return $Arr1;
    }

    public function getVouchers()
    {
        $pagedata['list'] = array();
        $catItemPrice = json_decode(input::get('catItemPrice'), true);
        $filter['pages'] = 1;
        $pageSize = 100;
        $params = array(
            'page_no' => $filter['pages'],
            'page_size' => $pageSize,
            'fields' => '*',
            'user_id' => userAuth::id(),
            'is_valid' => 1,
            'platform' => 'wap',
        );
        $voucherListData = app::get('topwap')->rpcCall('user.voucher.list.get', $params);
        if (!$voucherListData['list']) {
            return response::json($pagedata);
            exit;
        }
        foreach ($voucherListData['list'] as $k => $voucherInfo) {
            //判断使用条件
            $limitCat = explode(',', $voucherInfo['limit_cat']);//限制类目
            $limitMoney = $voucherInfo['limit_money'];//限制金额
            $deductMoney = 0;
            $voucherTotalPrice = 0;
            foreach ($catItemPrice as $lv1CatId => $row) {
                if (!in_array($lv1CatId, $limitCat)) {
                    continue;
                }

                foreach ($row as $shopId => $shopPriceTotal) {
                    $params = [
                        'shop_id' => $shopId,
                        'voucher_id' => $voucherInfo['voucher_id'],
                        'fields' => 'verify_status,valid_status,cat_id',
                    ];
                    $voucherRegisterInfo = app::get('topwap')->rpcCall('promotion.voucher.register.get', $params);
                    if (!$voucherRegisterInfo || $voucherRegisterInfo['verify_status'] != 'agree' || $voucherRegisterInfo['valid_status'] != 1) {
                        continue;
                    }

                    $shopCatId = explode(',', $voucherRegisterInfo['cat_id']);
                    if (in_array($lv1CatId, $shopCatId)) {
                        //使用购物券的商品金额
                        $voucherTotalPrice = ecmath::number_plus(array($voucherTotalPrice, $shopPriceTotal['price']));
                    }
                }
            }
            $voucherInfo['start_time'] = date('Y-m-d', $voucherInfo['start_time']);
            $voucherInfo['end_time'] = date('Y-m-d', $voucherInfo['end_time']);
            $this->__platform($voucherInfo);
            if (input::get('voucher_code') == $voucherInfo['voucher_code']) {
                $voucherInfo['checked'] = 1;
            }

            if ($voucherTotalPrice >= $limitMoney) {
                //购物券金额
                $deductMoney = $voucherInfo['deduct_money'];
                $pagedata['list'][] = $voucherInfo;
            }
        }
        return response::json($pagedata);
        exit;
    }

    public function useVoucher()
    {
        try {
            $apiParams = array(
                'voucher_code' => input::get('voucher_code'),
                'user_id' => userAuth::id(),
                'platform' => 'wap',
            );
            if (app::get('topwap')->rpcCall('trade.cart.voucher.add', $apiParams)) {
                $msg = app::get('topwap')->_('使用购物券成功');
                return $this->splash('success', null, $msg, true);
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
            return $this->splash('error', null, $msg, true);
        }
    }

    private function __platform(&$data)
    {
        $platform = $data['used_platform'];
        $platArr = array(
            'pc' => '电脑端',
            'wap' => '触屏端',
            'app' => 'APP端',
        );
        $data['available'] = 0;
        foreach (explode(',', $platform) as $value) {
            $result[] = $platArr[$value];
            if ($value == "wap") {
                $data['available'] = 1;
            }
        }
        $data['used_platform'] = implode(',', $result);
    }

    /**
     * @return mixed
     * @throws Exception
     * 获取买单页的数据
     */
    public function getCommonData()
    {
        $postData = input::get();
        //支付方式
        $pagedata['payType'] = array('pay_type' => 'online', 'name' => $this->payType['online']);

        $cartFilter['mode'] = $postData['mode'] ? $postData['mode'] : 'cart';
        $pagedata['nextPage'] = url::action('topwap_ctl_cart@index');
        $pagedata['mode'] = $postData['mode'];

        try {
            /*获取收货地址 start*/
            if (isset($postData['addr_id']) && $postData['addr_id']) {
                $params['user_id'] = userAuth::id();
                $params['addr_id'] = $postData['addr_id'];
                $userDefAddr = app::get('topwap')->rpcCall('user.address.info', $params);
            } else {
                // 获取默认地址
                $params['user_id'] = userAuth::id();
                $params['def_addr'] = 1;
                $userDefAddr = app::get('topwap')->rpcCall('user.address.list', $params);
                $userDefAddr = $userDefAddr['list']['0'];
                if (!$userDefAddr['list']) {
                    $userAddr = app::get('topwap')->rpcCall('user.address.count', array('user_id' => $params['user_id']));
                    $pagedata['nowcount'] = $userAddr['nowcount'];
                }
            }
            $pagedata['def_addr'] = $userDefAddr;
            /*获取收货地址 end*/

            // 商品信息
            $cartFilter['needInvalid'] = false;
            $cartFilter['platform'] = 'wap';
            $cartFilter['user_id'] = userAuth::id();
            $cartInfo = app::get('topwap')->rpcCall('trade.cart.getCartInfo', $cartFilter, 'buyer');
            if (!$cartInfo) {
                throw new Exception('NONE_CART');
            }

            if ($cartInfo['totalCart']['totalCross'] > 0) {
                $userInfo = app::get('sysuser')->model('user')->getRow('identity_card_number', ['user_id' => userAuth::id()]);
                $pagedata['identity_card_number'] = $userInfo['identity_card_number'];
            }

            $isSelfShop = true;

            foreach ($cartInfo['resultCartData'] as $key => &$val) {
                if ($val['shop_type'] != "self") {
                    $isSelfShop = false;
                } else {
                    $isSelfShopArr[] = $val['shop_id'];
                }

                $coupon_list = $this->__getCoupons($val['shop_id']);
                $val['couponlist'] = $coupon_list;

                $optimal_coupon_info = $this->getOptimalCouponCode($coupon_list, $val['object']);
                $optimal_coupon_code = $optimal_coupon_info['code'] ? $optimal_coupon_info['code'] : '';
                $optimal_coupon_name = $optimal_coupon_info['name'] ? $optimal_coupon_info['name'] : '';
                $pagedata['optimal_coupon_code'] = $optimal_coupon_code;
                $pagedata['optimal_coupon_name'] = $optimal_coupon_name;


                $pagedata['shipping'][$val['shop_id']]['shipping_type'] = 'express';

                if ($cartFilter['mode'] == "fastbuy") {
                    $fastBuyItemData = reset($val['object']);
                    $pagedata['nextPage'] = url::action('topwap_ctl_item_detail@index', ['item_id' => $fastBuyItemData['item_id']]);
                }

                if ($cartFilter['mode'] == "mini") {
                    //退款说明
                    $refund_filter['shop_id'] = $val['shop_id'];
                    $noRefundModel = app::get('syssupplier')->model('mini_explain');
                    $pagedata['refunds_desc'] = $noRefundModel->getRow('*', $refund_filter)['refund_desc'];

                    $fastBuyItemData = reset($val['object']);
                    $pagedata['nextPage'] = url::action('topwap_ctl__miniprogram_item@itemDetail', ['goods_id' => $fastBuyItemData['item_id']]);
                }

                // 王衍生-2018/08/03-start
                $val['is_need_delivery'] = false;
                foreach ($val['object'] as $object) {
                    if ($object['dlytmpl_id']) {
                        $val['is_need_delivery'] = true;
                        break;
                    }
                }
                // 王衍生-2018/08/03-end
            }

            $pagedata['ifOpenOffline'] = app::get('ectools')->getConf('ectools.payment.offline.open');
            $pagedata['isSelfShop'] = $isSelfShop;

            $pagedata['cartInfo'] = $cartInfo;

            //用户验证购物车数据是否发生变化
            $md5CartFilter = array('user_id' => userAuth::id(), 'platform' => 'wap', 'mode' => $cartFilter['mode'], 'checked' => 1);
            $md5CartInfo = md5(serialize(utils::array_ksort_recursive(app::get('topwap')->rpcCall('trade.cart.getBasicCartInfo', $md5CartFilter, 'buyer'), SORT_STRING)));
            $pagedata['md5_cart_info'] = $md5CartInfo;

            //获取默认图片信息
            $pagedata['defaultImageId'] = kernel::single('image_data_image')->getImageSetting('item');

            // 刷新结算页则失效前面选则的优惠券
            $shop_ids = array_keys($pagedata['cartInfo']['resultCartData']);
            foreach ($shop_ids as $sid) {
                $apiParams = array(
                    'coupon_code' => '-1',
                    'shop_id' => $sid,
                );
                app::get('topwap')->rpcCall('trade.cart.cartCouponCancel', $apiParams, 'buyer');
            }

            // 刷新结算页则失效前面的购物券
            $apiParams = array(
                'user_id' => userAuth::id(),
                'platform' => 'wap',
            );
            app::get('topwap')->rpcCall('trade.cart.voucher.cancel', $apiParams);

        } catch (Exception $e) {
            $msg = $e->getMessage();
            throw new Exception($msg);
        }
        // 王衍生-2018/09/06-start
        $shop_id = reset($shop_ids);
        $pagedata['shop_id'] = $shop_id;
        $lijin_setting = app::get('topwap')->rpcCall('shop.cash.setting.get', ['shop_id' => $shop_id]);
        $pagedata['open_lijin_deduction'] = $lijin_setting['is_open'];
        // 目前 礼金抵扣与积分抵扣不能并行使用
        if($pagedata['open_lijin_deduction']){
            $pagedata['if_open_point_deduction'] = 0;
        }else{
            $pagedata['if_open_point_deduction'] = app::get('topwap')->rpcCall('point.setting.get', ['field' => 'open.point.deduction']);
        }
        // 王衍生-2018/09/14-end
        $pagedata['invoice'] = json_decode(redis::scene('sysuser')->hget('invoice_info', userAuth::id()), 1);

        $curSymbol = app::get('topwap')->rpcCall('currency.get.symbol', array());
        $pagedata['curSymbol'] = $curSymbol;

        /*add_2018-01-16_by_xinyufeng_start*/
        if (!empty($_SESSION['store_id'])) {
            $pagedata['store_id'] = $_SESSION['store_id'];
        }
        /*add_2018-01-16_by_xinyufeng_end*/

        return $pagedata;
    }

    /**
     * @param $coupon_list
     * @param $object
     * @return array|int
     * 返回最优优惠券信息
     */
    private function getOptimalCouponCode($coupon_list, $object)
    {
        $itemWithTotalPriceArr = array();
        foreach ($object as $k1=>$v1)
        {
            //统计购物车价格，数量，价格等
            if( $v1['valid'] && $v1['is_checked']=='1' )
            {
                $itemWithTotalPriceArr[$k1] = $v1['item_id'].'_'.$v1['price']['total_price'].'_'.$v1['sku_id'].'_'.$v1['title'];
            }
        }

        if( !$itemWithTotalPriceArr ) return 0;
        $tmp_coupon_array = [];
        foreach($coupon_list as $c_list)
        {
            // 应用优惠券
            $apiParams = array(
                'coupon_code' => $c_list['coupon_code'],
                'cartItemsInfo' => implode('|', $itemWithTotalPriceArr),
                'user_id' => userAuth::id(),
            );
            $temp_coupon_info_array[$c_list['coupon_code']] = $c_list['coupon_name'];
            $couponDiscountData = app::get('systrade')->rpcCall('promotion.coupon.apply', $apiParams);
            if($couponDiscountData['deduct_money'])
            {
                $tmp_coupon_array[$c_list['coupon_code']] = $couponDiscountData['deduct_money'];
            }
        }
        if(!empty($tmp_coupon_array))
        {
            arsort($tmp_coupon_array);
            $max_value = reset($tmp_coupon_array);
            $optimal_coupon_code = array_search($max_value,$tmp_coupon_array);
            return ['code' => $optimal_coupon_code,'name' => $temp_coupon_info_array[$optimal_coupon_code]];
        }
        return [];
    }
}
