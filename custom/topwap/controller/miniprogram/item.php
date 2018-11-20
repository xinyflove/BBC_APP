<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/7/26
 * Time: 17:02
 */

class topwap_ctl__miniprogram_item
{
    public $agent_type = [
        'CASH_VOCHER'=>'代金劵',
        'DISCOUNT'=>'满折',
        'REDUCE'=>'满减'
    ];

    public function __construct()
    {
        $this->objLibSearch = kernel::single('topwap_item_search');
    }

    /**
     * @return mixed
     * 商品详情
     */
    public function itemDetail($item_id = 0)
    {
        if(!$item_id)
        {
            $itemId = intval(input::get('goods_id'));
        }
        try
        {
            if( empty($itemId) )
            {
               throw new Exception('没有此商品的信息');
            }

//            $url = url::action('topwap_ctl_item_detail@index',input::get());
//            kernel::single('topwap_passport')->getThirdpartyInfo($url);

            if( userAuth::check() )
            {
                $page_data['nologin'] = 1;
            }

            $item_data = $this->getItemList($itemId);

            $page_data = $item_data;

            //退款说明
            $refund_filter['shop_id'] = $item_data['shop']['shop_id'];
            $noRefundModel = app::get('syssupplier')->model('mini_explain');
            $page_data['refunds_desc'] = $noRefundModel->getRow('*',$refund_filter)['refund_desc'];
            //微信分享
            $extsetting=$this->__getExtSetting($item_data['shop']['shop_id']);
            if($extsetting['params']['share']['goods_describe'])
            {
                $desc_content = $extsetting['params']['share']['goods_describe'];
            }
            else
            {
                $desc_content = "我在".$item_data['shop']['shop_name']."发现了一个不错的商品，快来看看吧。";
            }
            $link_url = url::action("topwap_ctl__miniprogram_item@itemDetail",array('goods_id'=>$item_data['item_id']));
            $wechat = $this->wechatShare($desc_content, $item_data['image_default_id'],$link_url,$item_data['title']);
            $page_data['weixin'] = $wechat;

            $page_data['sell_out_img'] = app::get('sysconf')->getConf('item.sell.out.default.img');
            $page_data['rush_ticket_url'] = url::action("topwap_ctl_offlinepay_rushTickets@rushTichets");
            //分享修改的
            $baseUrl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            $page_data['signPackage']  = kernel::single('topwap_jssdk')->index($baseUrl);

            $page_data['request_code'] = 'success';
            $page_data['error_message'] = '';

            $return_data['err_no'] = 0;
            $return_data['data'] = $page_data;
            $return_data['message'] = '';
        }
        catch(Exception $e)
        {
            $return_data['err_no'] = 1001;
            $return_data['data'] = [];
            $return_data['message'] = $e->getMessage();
        }
        
        return response::json($return_data);
    }

    /**
     * @return mixed
     * 获取活动的详情
     */
    public function activeDetail()
    {
        $active_id = intval(input::get('goods_id'));
        try
        {
            if( empty($active_id) )
            {
                throw new Exception('没有此活动的信息');
            }

//            $url = url::action('topwap_ctl__miniprogram_item@activeDetail',input::get());
//            kernel::single('topwap_passport')->getThirdpartyInfo($url);

            if( userAuth::check() )
            {
                $page_data['nologin'] = 1;
            }

            $active_data = $this->getActiveList($active_id);
            $page_data = $active_data;

            //退款说明
            $refund_filter['shop_id'] = $active_data['agent_shop_info']['shop_id'];
            $noRefundModel = app::get('syssupplier')->model('mini_explain');
            $page_data['refunds_desc'] = $noRefundModel->getRow('*',$refund_filter)['refund_desc'];
            //微信分享
            $desc_content = "我在".$active_data['agent_shop_info']['name']."正在举办".$active_data['all_hold_info']['activity_name']."，快来看看吧。";

            $link_url = url::action("topwap_ctl__miniprogram_item@activeDetail",array('goods_id'=>$active_id));
            $wechat = $this->wechatShare($desc_content, $active_data['agent_shop_info']['agent_img_src'],$link_url,$active_data['all_hold_info']['activity_name']);
            $page_data['weixin'] = $wechat;

            $page_data['sell_out_img'] = app::get('sysconf')->getConf('item.sell.out.default.img');
            //分享修改的
            $baseUrl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            $page_data['signPackage']  = kernel::single('topwap_jssdk')->index($baseUrl);

            $return_data['err_no'] = 0;
            $return_data['data'] = $page_data;
            $return_data['message'] = '';
        }
        catch(Exception $e)
        {
            $return_data['err_no'] = 1001;
            $return_data['data'] = [];
            $return_data['message'] = $e->getMessage();
        }

        return response::json($return_data);
    }

    /**
     * @brief 商品收藏添加
     */
    public function ajaxAddItemCollect()
    {
        $userId = userAuth::id();
        $params['item_id'] = input::get('item_id');
        $params['objectType'] = input::get('type');
        $params['user_id'] = $userId;

        try
        {
            if(app::get('topwap')->rpcCall('user.itemcollect.add', $params))
            {
                $collectData = app::get('topwap')->rpcCall('user.collect.info',array('user_id'=>$userId));
                setcookie('collect',serialize($collectData));
            }
            else
            {
                throw new Exception(app::get('topwap')->_('商品收藏失败'));
            }
            $return_data['err_no'] = 0;
            $return_data['data'] = [];
            $return_data['message'] = '';
        }
        catch(Exception $e)
        {
            $return_data['err_no'] = 1001;
            $return_data['data'] = [];
            $return_data['message'] = $e->getMessage();
        }
        return response::json($return_data);
    }
    /**
     * @param $share_title
     * @param $item_data
     * @return mixed
     * 获取微信共享的参数
     */
    public function wechatShare($desc_content, $image_default_id, $link_url, $share_title)
    {
        $weixin['descContent'] = $desc_content;
        $weixin['imgUrl'] = base_storager::modifier($image_default_id);
        $weixin['linelink'] = $link_url;
        $weixin['shareTitle'] = $share_title;
        return $weixin;
    }

    /**
     * @param $active_id
     * @return mixed
     * @throws Exception
     * 获取活动的信息
     */
    public function getActiveList($active_id)
    {
        //获取全场打折
        $all_hold_filter['disabled'] = 0;
        $all_hold_filter['agent_activity_id'] = $active_id;
        $all_hold_info = app::get('topwap')->rpcCall('supplier.agent.activity.get', $all_hold_filter);
        $return_data['all_hold_info'] = $all_hold_info;

        if(!$all_hold_info)
        {
            throw new Exception('没有此活动的信息');
        }

        $agent_shop_id = $all_hold_info['agent_shop_id'];
        //此供应商下的所有线下店
        $agent_shop_info = app::get('topwap')->rpcCall('supplier.agent.shop.get',['agent_shop_id'=>$agent_shop_id]);
        $agent_shop_info['agent_img_src'] = base_storager::modifier($agent_shop_info['agent_img_src']);

        $shop_info = app::get('topwap')->rpcCall('shop.get',array('shop_id'=>$agent_shop_info['shop_id']));
        $shop_info['shop_logo'] = base_storager::modifier($shop_info['shop_logo']);
        $return_data['shop_info'] = $shop_info;
        $return_data['agent_shop_info'] = $agent_shop_info;
        return $return_data;
    }

    /**
     * @param $itemId
     * @return mixed
     * @throws Exception
     * 获取商品详情
     */
    public function getItemList($itemId)
    {
        $item_data['image_default_id'] = $this->__setting();

        $params['item_id'] = $itemId;
        $params['fields'] = "*,item_desc.wap_desc,item_count,item_store,item_status,sku,item_nature,spec_index,video_dir,agent_type,agent_use_limit,agent_shop_id,max_deduct_price,min_consum,deduct_price";
        $detailData = app::get('topwap')->rpcCall('item.get',$params);

        $serviceFilter['shop_id'] = $detailData['shop_id'];
        $single_shop_info = app::get('sysshop')->model('shop')->getRow('customer,item_comment',$serviceFilter);
        $comment_admin_switch = app::get('sysconf')->getConf('open.item.comment');
        if($comment_admin_switch == 0)
        {
            $item_comment_switch = 'off';
        }
        else
        {
            $item_comment_switch = $single_shop_info['item_comment'];
        }

        $item_data['customer'] = $single_shop_info['customer'];
        $item_data['item_comment'] = $item_comment_switch;

        if(!$detailData)
        {
            throw new Exception('商品过期不存在');
        }

        if(count($detailData['sku']) == 1)
        {
            $detailData['default_sku_id'] = array_keys($detailData['sku'])[0];
        }

        $detailData['valid'] = $this->__checkItemValid($detailData);
        if($detailData['use_platform'] != 2 && $detailData['use_platform'] != 0)
        {
            throw new Exception('该商品仅适用于电脑端');
        }

        $item_data['seller_id'] = intval(input::get('seller_id'));

        if( $detailData['video_dir'] )
        {
            $video_url_arr = app::get('topshop')->rpcCall('video.item.list', ['url' => $detailData['video_dir'], 'fields' => 'url']);
            if(!$video_url_arr){
                $video_url_arr[0]['url'] = $detailData['video_dir'];
            }
            $detailData['video_dir'] = $video_url_arr;
        }
        // 手机端商品视频启动画面
        $item_data['video_start_img'] = app::get('sysconf')->getConf('video.start.img');

        //相册图片
        if( $detailData['image_default_id'] )
        {
            $detailData['image_default_id'] = base_storager::modifier($detailData['image_default_id']);
        }
        if( $detailData['list_image'] )
        {
            $list_image  = explode(',',$detailData['list_image']);
            if($list_image)
            {
                foreach($list_image as &$image)
                {
                    $image = base_storager::modifier($image);
                }
            }
            $detailData['list_image'] = $list_image;
            $detailData['list_image_first'] = reset($detailData['list_image']);
            $detailData['list_image_last'] = end($detailData['list_image']);
        }

        $dlytmplParams['template_id'] = $detailData['dlytmpl_id'];
        $dlytmplParams['fields'] = 'is_free';
        //获取是否免邮的信息
        $dlytmplInfo = app::get('topwap')->rpcCall('logistics.dlytmpl.get',$dlytmplParams);
        if($dlytmplInfo)
        {
            $item_data['freeConf'] = $dlytmplInfo['is_free'];
        }
        //获取商品的促销信息
        $promotionInfo = app::get('topwap')->rpcCall('item.promotion.get', array('item_id'=>$itemId));
        if($promotionInfo)
        {
            foreach($promotionInfo as $vp)
            {
                $basicPromotionInfo = app::get('topwap')->rpcCall('promotion.promotion.get', array('promotion_id'=>$vp['promotion_id'], 'platform'=>'wap'));
                $basicPromotionInfo['sku_id'] = explode(',', $vp['sku_id']);
                if($basicPromotionInfo['valid']===true)
                {
                    $basicPromotionInfo['sku_id'] = $vp['sku_id'] ? explode(',',$vp['sku_id']) : null;
                    $item_data['promotionDetail'][$vp['promotion_id']] = $basicPromotionInfo;
                    if( !$vp['sku_id'] || (isset($skuId[$basicPromotionInfo['promotion_type']]) && $skuId[$basicPromotionInfo['promotion_type']] === null) )
                    {
                        $skuId[$basicPromotionInfo['promotion_type']] = null;
                    }
                    else
                    {
                        if($skuId[$basicPromotionInfo['promotion_type']])
                        {
                            $skuId[$basicPromotionInfo['promotion_type']] = array_merge($skuId[$basicPromotionInfo['promotion_type']],$basicPromotionInfo['sku_id']);
                        }
                        else
                        {
                            $skuId[$basicPromotionInfo['promotion_type']] = $basicPromotionInfo['sku_id'];
                        }
                    }

                    $basicPromotionInfo['sku_id'] = $skuId[$basicPromotionInfo['promotion_type']];
                    $item_data['promotionTag'][$basicPromotionInfo['promotion_type']] = $basicPromotionInfo;
                }
            }
        }
        $item_data['promotion_count'] = count($item_data['promotionDetail']);
        //wap组合促销
        $params['item_id'] = $itemId;
        $packagedata = app::get('topc')->rpcCall('promotion.package.getPackageItemsByItemId', $params);
        if($packagedata['data'])
        {
            $item_data['package_name'] = $packagedata['data'][array_keys($packagedata['data'])[0]]['package_name'];
        }

        //获取赠品促销信息
        $giftDetail = app::get('topwap')->rpcCall('promotion.gift.item.info',array('item_id'=>$itemId,'valid'=>1),'buyer')['0'];
        if($giftDetail)
        {
            $giftDetail['sku_ids'] = $giftDetail['sku_ids'] ? explode(',',$giftDetail['sku_ids']) : null;
            $item_data['giftDetail'] = $giftDetail;
        }

        // 活动促销(如名字叫团购)
        $activityDetail = app::get('topwap')->rpcCall('promotion.activity.item.info',array('item_id'=>$itemId,'valid'=>1),'buyer');
        if($activityDetail)
        {
            $item_data['activityDetail'] = $activityDetail;
        }

        $detailData['spec'] = $this->__getSpec($detailData['spec_desc'], $detailData['sku']);

        /*判断用户是否有购买银行卡商品权限*/
        $userId = userAuth::id();
        if($detailData['is_bank']==1)
        {
            $bank_filter=array('item_id'=>$detailData['item_id'],'shop_id'=>$detailData['shop_id'],'user_id'=>$userId,'is_bank'=>$detailData['is_bank']);
            $is_bank = app::get('topm')->rpcCall('item.bank.check',$bank_filter);
            $bank_ids=$detailData['bank_ids'];
            $sql="SELECT bank_name FROM sysbankmember_bank WHERE deleted!=1 AND bank_id IN (".$bank_ids.")";
            $banks=app::get('base')->database()->executeQuery($sql)->fetchAll();
            foreach($banks as $k=>$v)
            {
                $bank_str.=$v['bank_name'].",";
            }
            $bank_str=substr($bank_str,0,-1);
            $item_data['bank_name']=$bank_str;
        }
        else
        {
            $item_data['bank_name']='';
            $is_bank="HAS_BANK";
        }
        //is_bank 代表当前用户是否绑定了银行卡
        $item_data['is_bank']=$is_bank;
        if($detailData['start_time'])
        {
            $detailData['start_time']=date('Y-m-d',$detailData['start_time']);
        }
        if($detailData['end_time'])
        {
            $detailData['end_time']=date('Y-m-d',$detailData['end_time']);
        }
        /*判断用户是否有购买银行卡商品权限*/
        $item_data['item'] = $detailData;

        $shop_info = app::get('topwap')->rpcCall('shop.get',array('shop_id'=>$item_data['item']['shop_id']));
        $shop_info['shop_logo'] = base_storager::modifier($shop_info['shop_logo']);
        $item_data['shop'] = $shop_info;
        $item_data['next_page'] = url::action("topwap_ctl_item_detail@index",array('item_id'=>$itemId));
        //商品收藏和店铺收藏情况
        $item_data['collect'] = $this->__CollectInfo($itemId, $item_data['shop']['shop_id']);
        // 获取评价
        $item_data['countRate'] = $this->__getRateResultCount($detailData);
        // 获取当前平台设置的货币符号和精度
        $cur_symbol = app::get('topwap')->rpcCall('currency.get.symbol',array());
        $item_data['cur_symbol'] = $cur_symbol;

        $rateData = $this->__showRate($itemId, 1);
        $item_data['rate_data'] = $rateData;

        $item_data['cartNum'] = $this->getCartCount();
        if(empty($item_data['cartNum'])) $item_data['cartNum'] = 0;

        $item_data['now_time'] = time();

        if($detailData['supplier_id'])
        {
            $supplierparams['supplier_id'] = $detailData['supplier_id'];
            $item_data['supplier'] = app::get('topshop')->rpcCall('supplier.shop.get',$supplierparams);
        }
        else
        {
            $item_data['supplier']='';
        }

        //虚拟劵-》支付核销类型
        if($item_data['item']['confirm_type'] == 1)
        {
            $item_data['item']['agent_type_name'] = $this->agent_type[$item_data['item']['agent_type']];
            $agent_shop_ids = explode(',',$item_data['item']['agent_shop_id']);
            $agent_shop_ids = array_filter($agent_shop_ids,function($v) {
                return !empty($v);
            });
            $agent_shop_arr = app::get('topwap')->rpcCall('supplier.agent.shop.search',['agent_shop_id'=>$agent_shop_ids]);
            $item_data['supplier']['agent_shop'] = $agent_shop_arr;
            if($item_data['item']['agent_type'] === 'DISCOUNT')
            {
                $item_data['item']['deduct_price'] = (int)$item_data['item']['deduct_price'];
            }
        }
        return $item_data;
    }

    /**
     * @param $postdata
     * @return mixed
     * 获取筛选项
     */
    private function __itemListFilter($postdata)
    {
        $objLibFilter = kernel::single('topwap_item_filter');
        $params = $objLibFilter->decode($postdata);
        $params['use_platform'] = '0,2';
        $filterItems = app::get('topwap')->rpcCall('item.search.filterItems',$params);
        if($filterItems['shopInfo'])
        {
            $shop_setting = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>$filterItems['shopInfo']['shop_id']),'seller');
            $filterItems['logo_image']['shop_logo'] = $shop_setting['shop_brand_image'];
        }
        //渐进式筛选的数据
        return $filterItems;
    }

    private function __setting()
    {
        $setting = kernel::single('image_data_image')->getImageSetting('item');
        return $setting;
    }

    private function __checkItemValid($itemsInfo)
    {
        if( empty($itemsInfo) ) return false;

        //违规商品
        if( $itemsInfo['violation'] == 1 ) return false;

        //未启商品
        if( $itemsInfo['disabled'] == 1 ) return false;

        //未上架商品
        if($itemsInfo['approve_status'] != 'onsale') return false;

        //库存小于或者等于0的时候，为无效商品
        //if($itemsInfo['realStore'] <= 0 ) return false;

        return true;
    }

    /* function_name:__getExtSetting (shop_id)
    *  函数说明:
    *  获取店铺其他配置信息的
    * 参数:shop_id 商铺id
    */

    public function __getExtSetting($shop_id){
        if(empty($shop_id)) return;
        $extsetting= app::get('topshop')->rpcCall('shop.extsetting.get',array('shop_id'=>$shop_id,'use_platform'=>'wap'));

        $shopdata = app::get('topwap')->rpcCall('shop.get',array('shop_id'=>$shop_id));
        if(empty($extsetting['params']['share']['shopcenter_title'])){
            $extsetting['params']['share']['shopcenter_title']=$shopdata['shop_name'];
        }
        if(empty($extsetting['params']['share']['shopcenter_describe'])){
            $extsetting['params']['share']['shopcenter_describe']=$shopdata['shop_name']."直营店，青岛广电生活圈，媒体认证，正品保证。";
        }
        return $extsetting;
    }

    /* function_name:getCartCount (shop_id)
    *  函数说明:
    *  获取购物车数量
    */

    public function getCartCount()
    {
        $cartCount='';
        if(userAuth::check())
        {
            $params['user_id'] = userAuth::id();
            $cartData = app::get()->rpcCall('trade.cart.getCount',$params);
            $cartCount=$cartData['number'];
            return  $cartCount;
        }
        else
        {
            $result = kernel::single('topwap_cart')->getCartInfo();
            $data=$result['resultCartData'];
            foreach($data as $key=>$val){
                foreach($val['object'] as $k=>$v){
                    $cartCount+=$v['quantity'];
                }
            }
            return $cartCount;
        }
    }

    private function __showRate($itemId, $showNum=5)
    {
        $params = ['item_id'=>$itemId,'page_no'=>1,'page_size'=>intval($showNum),'fields'=>'*,append'];
        $params['result'] = 'good';
        $data = app::get('topwap')->rpcCall('rate.list.get', $params);

        if(empty($data['trade_rates'])){
            unset($params['result']);
            $data = app::get('topwap')->rpcCall('rate.list.get', $params);
        }

        $userId = array();
        foreach($data['trade_rates'] as $k=>$row )
        {
            if($row['rate_pic'])
            {
                $data['trade_rates'][$k]['rate_pic'] = explode(",",$row['rate_pic']);
            }

            if( $row['append']['append_rate_pic'] )
            {
                $data['trade_rates'][$k]['append']['append_rate_pic'] = explode(',', $row['append']['append_rate_pic']);
            }

            $userId[] = $row['user_id'];
        }

        $datas['rate']= $data['trade_rates'];
        if( $userId )
        {
            $userData = app::get('sysuser')->model('user')->getList('user_id,name,headimg_url',array('user_id|in'=>$userId));

            $userDateNew = array();
            foreach ($userData as $ud){
                if(!$ud['headimg_url']){
                    $ud['headimg_url'] = app::get('sysconf')->getConf('user.default.headimg');
                }
                $userDateNew[$ud['user_id']] = $ud;
            }
            unset($userData);

            $datas['userInfo'] = $userDateNew;
        }

        return $datas;
    }

    /**
     * @param $itemId
     * @param $shopId
     * @return mixed
     */
    private function __CollectInfo($itemId,$shopId)
    {
        $userId = userAuth::id();
//        $collect = unserialize($_COOKIE['collect']);
        $user_fav = app::get('sysuser')->model('user_fav');
        $shop_fav = app::get('sysuser')->model('shop_fav');
        $item_collect = $user_fav->getRow('item_id',['user_id'=>$userId,'item_id'=>$itemId]);
        $shop_collect = $shop_fav->getRow('snotify_id',['user_id'=>$userId,'shop_id'=>$shopId]);
        if($item_collect)
        {
            $page_data['itemCollect'] = 1;
        }
        else
        {
            $page_data['itemCollect'] = 0;
        }
        if($shop_collect)
        {
            $page_data['shopCollect'] = 1;
        }
        else
        {
            $page_data['shopCollect'] = 0;
        }

        return $page_data;
    }

    private function __getRateResultCount($detailData)
    {
        if( !$detailData['rate_count'] )
        {
            $countRate['good']['num'] = 0;
            $countRate['good']['percentage'] = '0%';
            $countRate['neutral']['num'] = 0;
            $countRate['neutral']['percentage'] = '0%';
            $countRate['bad']['num'] = 0;
            $countRate['bad']['percentage'] = '0%';
            return $countRate;
        }
        $countRate['good']['num'] = $detailData['rate_good_count'];
        $countRate['good']['percentage'] = sprintf('%.2f',$detailData['rate_good_count']/$detailData['rate_count'])*100 .'%';
        $countRate['neutral']['num'] = $detailData['rate_neutral_count'];
        $countRate['neutral']['percentage'] = sprintf('%.2f',$detailData['rate_neutral_count']/$detailData['rate_count'])*100 .'%';
        $countRate['bad']['num'] = $detailData['rate_bad_count'];
        $countRate['bad']['percentage'] = sprintf('%.2f',$detailData['rate_bad_count']/$detailData['rate_count'])*100 .'%';
        $countRate['total'] = $detailData['rate_count'];
        return $countRate;
    }

    /**
     * @param $spec
     * @param $sku
     * @return array
     * 获取属性
     */
    private function __getSpec($spec, $sku)
    {
        if( empty($spec) ) return array();
        //获取活动设置的商品 及 sku信息
        $itemId = reset(array_unique(array_column($sku,'item_id')));
        $activiryParams = array(
            'item_id' =>$itemId,
            'start_time|lthan' => time(),
            'end_time|than' => time(),
            'verify_status' => 'agree',
        );
        $activityItem = app::get('topc')->rpcCall('promotion.activity.item.info',array('item_id'=>$itemId,'valid'=>1),'buyer');
        $activitySkuPrice = $activityItem['sku_activity_price'];
        foreach( $sku as $row )
        {
            $key = implode('_',$row['spec_desc']['spec_value_id']);
            $activityPice = $activitySkuPrice[$row['sku_id']] ? $activitySkuPrice[$row['sku_id']] :($activityItem['sku_ids'] ? 0 : $activityItem['activity_price']);

            if( $key )
            {
                $result['specSku'][$key]['sku_id'] = $row['sku_id'];
                $result['specSku'][$key]['item_id'] = $row['item_id'];
                $result['specSku'][$key]['price'] = $row['price'];
                $result['specSku'][$key]['mkt_price'] = $row['mkt_price'];
                $result['specSku'][$key]['activity_price'] = $activityPice;
                $result['specSku'][$key]['store'] = $row['realStore'];
                $result['specSku'][$key]['bank_price']=$row['bank_price'];
                if( $row['status'] == 'delete')
                {
                    $result['specSku'][$key]['valid'] = false;
                }
                else
                {
                    $result['specSku'][$key]['valid'] = true;
                }

                $specIds = array_flip($row['spec_desc']['spec_value_id']);
                $specInfo = explode('、',$row['spec_info']);
                foreach( $specInfo  as $info)
                {
                    $id = each($specIds)['value'];
                    $result['specName'][$id] = explode('：',$info)[0];
                }
            }
        }
        return $result;
    }
}