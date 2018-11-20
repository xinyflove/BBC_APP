<?php

/**
 * detail.php 商品详情
 *
 * @author
 * @copyright  Copyright (c) 2005-2015 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class topwap_ctl_item_detail extends topwap_controller {

    //add_start_gurundong_2018/01/26
    //虚拟卡劵 -> 核销类型 -> 卡劵种类
    public $agent_type = [
        'CASH_VOCHER'=>'代金劵',
        'DISCOUNT'=>'满折',
        'REDUCE'=>'满减'
    ];
    //add_end_gurundong_2018/01/26

    public function index()
    {
        $itemId = intval(input::get('item_id'));
        if( empty($itemId) )
        {
            return redirect::action('topwap_ctl_default@index');
        }

        $url = url::action('topwap_ctl_item_detail@index',input::get());
        kernel::single('topwap_passport')->getThirdpartyInfo($url);

        if( userAuth::check() )
        {
            $pagedata['nologin'] = 1;
        }
        $pagedata['image_default_id'] = $this->__setting();
        $params['item_id'] = $itemId;
        $params['fields'] = "*,item_desc.wap_desc,item_count,item_store,item_status,sku,item_nature,spec_index,video_dir,agent_type,agent_use_limit,agent_shop_id,max_deduct_price,min_consum,deduct_price";
        $detailData = app::get('topwap')->rpcCall('item.get',$params);
        $_SESSION['shop_id'] = $detailData['shop_id'];
        /*add_201711081620_by_wudi_start:客服*/
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
        //判断商品如果属于赠品 则不允许购买
        $is_gift = false;
        $giftCount = app::get('syspromotion')->model('gift_sku')->count(array('item_id'=>$itemId,'end_time|than'=>time()));
        if($giftCount > 0)
        {
            $is_gift = true;
        }
        $pagedata['is_gift'] = $is_gift;
        /*modify_20171109_by_fanglongji_start*/
        /*
        if(empty($customer['customer'])){
            $pagedata['customer']='https://www.sobot.com/chat/h5/index.html?sysNum=0e8105279fe345a396838798c1850cb3';
        }else{
            $pagedata['customer']=$customer['customer'];
        }
        */
        $pagedata['customer'] = $single_shop_info['customer'];
        $pagedata['item_comment'] = $item_comment_switch;
        /*modify_20171109_by_fanglongji_end*/
        /*add_201711081620_by_wudi_end:客服*/


        if(!$detailData)
        {
            $pagedata['error'] = "商品过期不存在";
            return $this->page('topwap/item/detail/error.html', $pagedata);
        }

        if(count($detailData['sku']) == 1)
        {
            $detailData['default_sku_id'] = array_keys($detailData['sku'])[0];
        }

        $detailData['valid'] = $this->__checkItemValid($detailData);
        if($detailData['use_platform'] != 2 && $detailData['use_platform'] != 0)
        {
            $pagedata['error'] = "该商品仅适用于电脑端";
            return $this->page('topwap/item/detail/error.html', $pagedata);
        }
        // start add 王衍生 20170924
        $pagedata['seller_id'] = intval(input::get('seller_id'));

        if( $detailData['video_dir'] )
        {
            $video_url_arr = app::get('topshop')->rpcCall('video.item.list', ['url' => $detailData['video_dir'], 'fields' => 'url']);
            // if 为兼容早期视频没有入视频表的视频
            if(!$video_url_arr){
                $video_url_arr[0]['url'] = $detailData['video_dir'];
            }
            $detailData['video_dir'] = $video_url_arr;
        }
        // 手机端商品视频启动画面
        $pagedata['video_start_img'] = app::get('sysconf')->getConf('video.start.img');
        // end add 王衍生 20170924

        //相册图片
        if( $detailData['list_image'] )
        {
            $detailData['list_image'] = explode(',',$detailData['list_image']);
            $detailData['list_image_first'] = reset($detailData['list_image']);
            $detailData['list_image_last'] = end($detailData['list_image']);
        }

        $dlytmplParams['template_id'] = $detailData['dlytmpl_id'];
        $dlytmplParams['fields'] = 'is_free';
        //获取是否免邮的信息
        $dlytmplInfo = app::get('topwap')->rpcCall('logistics.dlytmpl.get',$dlytmplParams);
        if($dlytmplInfo)
        {
            $pagedata['freeConf'] = $dlytmplInfo['is_free'];
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
                    $pagedata['promotionDetail'][$vp['promotion_id']] = $basicPromotionInfo;
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
                    $pagedata['promotionTag'][$basicPromotionInfo['promotion_type']] = $basicPromotionInfo;
                }
            }
        }
        $pagedata['promotion_count'] = count($pagedata['promotionDetail']);
		/*add_2017/12/19_by_wanghaichao_start*/
		//wap组合促销
        $params['item_id'] = $itemId;
        $packagedata = app::get('topc')->rpcCall('promotion.package.getPackageItemsByItemId', $params);
        if($packagedata['data']){
            $pagedata['package_name'] = $packagedata['data'][array_keys($packagedata['data'])[0]]['package_name'];
        }
		/*add_2017/12/19_by_wanghaichao_end*/

        //获取赠品促销信息
        $giftDetail = app::get('topwap')->rpcCall('promotion.gift.item.info',array('item_id'=>$itemId,'valid'=>1),'buyer')['0'];
        if($giftDetail)
        {
            $giftDetail['sku_ids'] = $giftDetail['sku_ids'] ? explode(',',$giftDetail['sku_ids']) : null;
            $pagedata['giftDetail'] = $giftDetail;
        }

        // 活动促销(如名字叫团购)
        $activityDetail = app::get('topwap')->rpcCall('promotion.activity.item.info',array('item_id'=>$itemId,'valid'=>1),'buyer');
        if($activityDetail)
        {
            $pagedata['activityDetail'] = $activityDetail;
        }

        $detailData['spec'] = $this->__getSpec($detailData['spec_desc'], $detailData['sku']);

		/*start whc_判断用户是否有购买银行卡商品权限_2017/9/7*/
        $userId = userAuth::id();
		if($detailData['is_bank']==1){
			$bank_filter=array('item_id'=>$detailData['item_id'],'shop_id'=>$detailData['shop_id'],'user_id'=>$userId,'is_bank'=>$detailData['is_bank']);
			$is_bank = app::get('topm')->rpcCall('item.bank.check',$bank_filter);
			$bank_ids=$detailData['bank_ids'];
			$sql="SELECT bank_name FROM sysbankmember_bank WHERE deleted!=1 AND bank_id IN (".$bank_ids.")";
			$banks=app::get('base')->database()->executeQuery($sql)->fetchAll();
			foreach($banks as $k=>$v){
				$bank_str.=$v['bank_name'].",";
			}
			$bank_str=substr($bank_str,0,-1);
			$pagedata['bank_name']=$bank_str;
		}else{
			$pagedata['bank_name']='';
			$is_bank="HAS_BANK";
		}
		//is_bank 代表当前用户是否绑定了银行卡
		$pagedata['is_bank']=$is_bank;
		if($detailData['start_time']){
			$detailData['start_time']=date('Y-m-d',$detailData['start_time']);
		}
		if($detailData['end_time']){
			$detailData['end_time']=date('Y-m-d',$detailData['end_time']);
		}
		/*end whc_判断用户是否有购买银行卡商品权限_2017/9/7*/
        $pagedata['item'] = $detailData;

        $pagedata['shop'] = app::get('topwap')->rpcCall('shop.get',array('shop_id'=>$pagedata['item']['shop_id']));
        $pagedata['next_page'] = url::action("topwap_ctl_item_detail@index",input::get());
        //商品收藏和店铺收藏情况
        $pagedata['collect'] = $this->__CollectInfo(input::get('item_id'),$pagedata['shop']['shop_id']);
        // 获取评价
        $pagedata['countRate'] = $this->__getRateResultCount($detailData);
        // 获取当前平台设置的货币符号和精度
        $cur_symbol = app::get('topwap')->rpcCall('currency.get.symbol',array());
        $pagedata['cur_symbol'] = $cur_symbol;

        /*add_20170927_by_xinyufeng_start 评价信息1条*/
        $rateData = $this->__showRate($itemId, 1);
        $pagedata['rate_data'] = $rateData;
        /*add_20170927_by_xinyufeng_end*/

        /*add_20170927_by_xinyufeng_start 商品详情*/
        // $params['item_id'] = $itemId;
        // $params['fields'] = "*,item_desc.wap_desc,item_count,item_store,item_status,sku,item_nature,spec_index";
        // $detailData = app::get('topwap')->rpcCall('item.get',$params);
        // // 商品详情
        // $pagedata['itemPic'] = $detailData;
        /*add_20170927_by_xinyufeng_end*/
        /*add_20170927_by_xinyufeng_start 大家都在看*/
        $parames = array();
        $parames['limit_num'] = 6;
        $parames['fields'] = "SI.item_id, image_default_id, title, price, sold_quantity,SI.shop_id";
        $parames['filter']['shop_id'] = $detailData['shop_id'];
        $parames['filter']['approve_status'] = 'onsale';
        $parames['order_by'] = ['by' => 'sold_quantity', 'sort' => 'desc'];
        $itemList = app::get('topwap')->rpcCall('item.mybelike.list',$parames);
        $shop_infos = app::get('topwap')->rpcCall('shop.list.all.get');

        foreach($itemList as &$items)
        {
            if($shop_infos[$items['shop_id']]['shop_mold'] == 'tv')
            {
                $items['mold_class'] = 'icon_small_tv';
            }
            else if($shop_infos[$items['shop_id']]['shop_mold'] == 'broadcast')
            {
                $items['mold_class'] = 'icon_fm101';
            }
            else
            {
                $items['mold_class'] = 'icon_other_tv';
            }
            $items['shop_mold'] = $shop_infos[$items['shop_id']]['shop_mold'];
            $items['shop_name'] = $shop_infos[$items['shop_id']]['shop_name'];
        }
        $pagedata['recommend'] = $itemList;
        /*add_20170927_by_xinyufeng_end*/

        /*add_20170928_by_xinyufeng_start 购物车数量*/
        $pagedata['cartNum'] = $this->getCartCount();
        if(empty($pagedata['cartNum'])) $pagedata['cartNum'] = 0;
        /*add_20170928_by_xinyufeng_end*/
		/*add_2017/9/28_by_wanghaichao_start*/
		$pagedata['now_time']=time();

		if($detailData['supplier_id']){
			$supplierparams['supplier_id'] = $detailData['supplier_id'];
			$pagedata['supplier'] = app::get('topshop')->rpcCall('supplier.shop.get',$supplierparams);
		}else{
			$pagedata['supplier']='';
		}

		/*add_start_gurundong_2018/01/26*/
		//虚拟劵-》支付核销类型
        if($pagedata['item']['confirm_type'] == 1)
        {
            $pagedata['item']['agent_type_name'] = $this->agent_type[$pagedata['item']['agent_type']];
            $agent_shop_ids = explode(',',$pagedata['item']['agent_shop_id']);
            $agent_shop_ids = array_filter($agent_shop_ids,function($v) {
                return !empty($v);
            });
            $agent_shop_arr = app::get('topwap')->rpcCall('supplier.agent.shop.search',['agent_shop_id'=>$agent_shop_ids]);
            $pagedata['supplier']['agent_shop'] = $agent_shop_arr;
            if($pagedata['item']['agent_type'] === 'DISCOUNT')
            {
                $pagedata['item']['deduct_price'] = (int)$pagedata['item']['deduct_price'];
            }
        }
		/*add_end_gurundong_2018/01/26*/

		//微信分享
		$extsetting=$this->__getExtSetting($pagedata['shop']['shop_id']);
		if($extsetting['params']['share']['goods_describe']){
			$weixin['descContent']=$extsetting['params']['share']['goods_describe'];
		}else{
			$weixin['descContent']= "我在".$pagedata['shop']['shop_name']."发现了一个不错的商品，快来看看吧。";
		}
		$weixin['imgUrl']= base_storager::modifier($detailData['image_default_id']);
		$weixin['linelink']= url::action("topwap_ctl_item_detail@index",array('item_id'=>$itemId));
		$weixin['shareTitle']=$detailData['title'];
		$pagedata['weixin']=$weixin;
		/*add_2017/9/28_by_wanghaichao_end*/
        /*add_2017/9/29_by_xinyufeng_start*/
        $pagedata['sell_out_img'] = app::get('sysconf')->getConf('item.sell.out.default.img');
        /*add_2017/9/29_by_xinyufeng_end*/
        $this->setLayoutFlag('product');
        /*add_20180316_by_fanglongji_start*/
        $pagedata['rush_ticket_url']= url::action("topwap_ctl_offlinepay_rushTickets@rushTichets");
        /*add_20180316_by_fanglongji_end*/
		/*add_2018/6/28_by_wanghaichao_start*/
		//分享修改的
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $baseUrl = $http_type.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$pagedata['signPackage'] = kernel::single('topwap_jssdk')->index($baseUrl);
		/*add_2018/6/28_by_wanghaichao_end*/
        return $this->page('topwap/item/detail/index_01.html', $pagedata);
    }

    //商品描述
    public function itemPic()
    {
        $itemId = intval(input::get('item_id'));
        if( empty($itemId) )
        {
            return redirect::action('topwap_ctl_default@index');
        }

        $pagedata['image_default_id'] = $this->__setting();
        $params['item_id'] = $itemId;
        $params['fields'] = "*,item_desc.wap_desc,item_count,item_store,item_status,sku,item_nature,spec_index";
        $detailData = app::get('topwap')->rpcCall('item.get',$params);
        $pagedata['title'] = "商品描述";
        $pagedata['itemPic'] = $detailData;
        // 商品自然属性
        $pagedata['itemParamshtml'] = view::make('topwap/item/detail/itemparams.html', $detailData)->render();
        // 商品备注
        $pagedata['itemremarkhtml'] = view::make('topwap/item/detail/itemremark.html',$detailData)->render();

        return $this->page('topwap/item/detail/itempic.html', $pagedata);
    }

    // 商品评价
    public function getItemRate()
    {
        $itemId = intval(input::get('item_id'));
        if( empty($itemId) ) return '';

        $pagedata =  $this->__searchRate($itemId);
        $pagedata['item_id'] = $itemId;
        $pagedata['title'] = app::get('topwap')->_('商品评价');

        return $this->page('topwap/item/detail/itemrate.html', $pagedata);
    }

    // 获取评价列表
    public function getItemRateList()
    {
        try {
            $itemId = intval(input::get('item_id'));
            if( empty($itemId) ) return '';
            $pagedata=$this->__searchRate($itemId);
            $data['pages'] = $pagedata['pages'];
            $data['total'] = $pagedata['total']; // 总页数
            $data['rate_type'] = $pagedata['rate']['result'];
            $data['success'] = true;
            $data['html'] = view::make('topwap/item/detail/itemrate_list.html',$pagedata)->render();
            if(intval($pagedata['total']) <=0)
            {
               $data['html'] = view::make('topwap/empty/rate.html')->render();
            }

        } catch (Exception $e) {
            return $this->splash('error', null, $e->getMessage(), true);
        }
        return response::json($data);
    }

    public function viewNotifyItem()
    {
        $pagedata = input::get();
        $pagedata['title'] = app::get('topwap')->_('到货通知');

        return $this->page('topwap/item/detail/shipment.html', $pagedata);
    }
    // 到货通知
    public function userNotifyItem()
    {
        try
        {
            $postdata = $this->__checkdata(input::get());
            $params['shop_id'] = $postdata['shop_id'];
            $params['item_id'] = $postdata['item_id'];
            $params['sku_id'] = $postdata['sku_id'];
            $params['email'] = $postdata['email'];
            $result = app::get('topwap')->rpcCall('user.notifyitem',$params);
        }
        catch (Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',$url,$msg);
        }
        $url = url::action('topwap_ctl_item_detail@index', ['item_id'=>$postdata['item_id']]);

        if( $result['sendstatus'] == 'ready' )
        {
            $msg = app::get('topwap')->_('您已经填过该商品的到货通知');
        }
        else
        {
            $msg = app::get('topwap')->_('预订成功');
        }
        return $this->splash('success', $url, $msg);
    }

    private function __checkdata($data)
    {
        $validator = validator::make(
                ['shop_id' => $data['shop_id'] , 'item_id' => $data['item_id'],'sku_id' => $data['sku_id'],'email' => $data['email']],
                ['shop_id' => 'required'       , 'item_id' => 'required',     'sku_id' => 'required', 'email' => 'required|email'],
                ['shop_id' => '店铺id不能为空！' , 'item_id' => '商品id不能为空！','sku_id' => '货品id不能为空！','email' => '邮件不能为空！|邮件格式不正确!']
        );
        if ($validator->fails())
        {
            $messages = $validator->messagesInfo();
            foreach( $messages as $error )
            {
                throw new Exception( $error[0] );
            }
        }
        return $data;
    }

    // 获取评论百分比
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

    private function __searchRate($itemId)
    {
        $rate_type_arr = ['1'=>'good','2'=>'neutral','3'=>'bad'];
        $current = input::get('pages',1);
        $rate_type = input::get('rate_type');
        $pagedata['rate_type_group'] = $rate_type;
        $limit = 10;
        $params = ['item_id'=>$itemId,'page_no'=>intval($current),'page_size'=>intval($limit),'fields'=>'*,append'];
        if( $rate_type == '4'  )
        {
            $params['is_pic'] = true;
            $pagedata['query_type'] = 'pic';
        }
        else
        {
            $pagedata['query_type'] = 'content';
        }

        if($rate_type)
        {
            $params['result'] = $rate_type_arr[$rate_type];
            $pagedata['rate_type'] = $rate_type_arr[$rate_type];
        }
        $data = app::get('topwap')->rpcCall('rate.list.get', $params);

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

        $pagedata['rate']= $data['trade_rates'];
        if( $userId )
        {
            $pagedata['userName'] = app::get('topwap')->rpcCall('user.get.account.name',array('user_id'=>implode(',',$userId)),'buyer');
            foreach ($pagedata['userName'] as &$v_username)
            {
                if(preg_match('/^0?1(?:[38]\d)|(?:4[579])|(?:[57][0-35-9])\d{8}$/',$v_username))
                {
                    $v_username = substr($v_username,0,3).'****'.substr($v_username,7);
                }
            }
            /*add_start_gurundong_2017/11/1*/
            /*增加用户信息*/
            $userData = app::get('sysuser')->model('user')->getList('user_id,name,headimg_url',array('user_id|in'=>$userId));
            $userDateNew = array();
            foreach ($userData as $ud){
                if(!$ud['headimg_url']){
                    $ud['headimg_url'] = app::get('sysconf')->getConf('user.default.headimg');
                }
                $userDateNew[$ud['user_id']] = $ud;
            }
            unset($userData);
            $pagedata['userInfo'] = $userDateNew;
            /*add_end_gurundong_2017/11/1*/
        }

        //处理翻页数据
        if($data['total_results']>0) $total = ceil($data['total_results']/$limit);
        $current = $total < $current ? $total : $current;

        $pagedata['pages'] = $current;
        $pagedata['total'] = $total;

        return $pagedata;
    }


    private function __setting()
    {
        $setting = kernel::single('image_data_image')->getImageSetting('item');
        return $setting;
    }

    //当前商品收藏和店铺收藏的状态
    private function __CollectInfo($itemId,$shopId)
    {
        $userId = userAuth::id();
        $collect = unserialize($_COOKIE['collect']);
        if(in_array($itemId, $collect['item']))
        {
            $pagedata['itemCollect'] = 1;
        }
        else
        {
            $pagedata['itemCollect'] = 0;
        }
        if(in_array($shopId, $collect['shop']))
        {
            $pagedata['shopCollect'] = 1;
        }
        else
        {
            $pagedata['shopCollect'] = 0;
        }

        return $pagedata;
    }

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
				/*add_2017/12/11_by_wanghaichao_start*/
				$result['specSku'][$key]['bank_price']=$row['bank_price'];
				/*add_2017/12/11_by_wanghaichao_end*/
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

    /*add_20170927_by_xinyufeng_start 获取评价信息*/
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
    /*add_20170927_by_xinyufeng_end*/

	/* action_name (par1, par2, par3)
	* 获取组合促销的产品
	* author by wanghaichao
	* date 2017/12/15
	*/
    public function getPackage()
    {
        $params['item_id'] = intval (input::get('item_id'));
        $pagedata = app::get('topc')->rpcCall('promotion.package.getPackageItemsByItemId', $params);
        $basicPackageTag = [];
        foreach($pagedata['data'] as $k=>&$v)
        {
            if( !in_array($v['used_platform'], ['0','1']) )
            {
                unset($pagedata['data'][$k]); continue;
            }

            $oldTotalPrice = 0;
            $packageTotalPrice = 0;
            foreach($v['items'] as $k1=>$v1)
            {
                $oldTotalPrice += $v1['price'];

				/*modify_2017/12/13_by_wanghaichao_start*/
				/*$packageTotalPrice = ecmath::number_plus(array($v1['package_price'],$packageTotalPrice));*/
                $packageTotalPrice = ecmath::number_plus(array(($v1['package_price']*$v1['count']),$packageTotalPrice));
				/*modify_2017/12/13_by_wanghaichao_end*/
				/*add_2017/12/19_by_wanghaichao_start*/
				//取出单品对应的sku_id
				if(!$v1['sku_ids']){
					$v1['sku_id']=$this->getSkuId($v1['item_id']);
				}else{
					$v1['sku_id']='';
				}
				/*add_2017/12/19_by_wanghaichao_end*/
				$v['items'][$k1]=$v1;
            }
            $v['old_total_price'] = $oldTotalPrice;
            $v['package_total_price'] = $packageTotalPrice;
            $v['cut_total_price'] = ecmath::number_minus(array($v['old_total_price'], $v['package_total_price']));
            $basicPackageTag[] = array('name'=>$v['package_name'], 'package_id'=>$v['package_id']);
        }

		//echo "<pre>";print_r($pagedata);die();
        if(!$pagedata)return;
        $pagedata['package_tags'] = $basicPackageTag;
        return $this->page('topwap/item/package/package.html', $pagedata);
    }


	/* action_name (par1, par2, par3)
	* 根据item_id获取sku_id
	* author by wanghaichao
	* date 2017/12/19
	*/
	public function getSkuId($item_id){
		if(empty($item_id)){
			return;
		}
		$objSku=app::get('sysitem')->model('sku');
		$params['item_id']=$item_id;
		$sku=$objSku->getRow('sku_id',$params);
		return $sku['sku_id'];
	}


	/* action_name (par1, par2, par3)
	* 根据item_id获取规格信息的
	* author by wanghaichao
	* date 2017/12/18
	*/
	public function getItemSpec(){
		$itemId=input::get('item_id');
		$package_id=input::get('package_id');
        $params['item_id'] = $itemId;
        $params['fields'] = "image_default_id,spec_desc,item_desc.wap_desc,sku";
        $detailData = app::get('topwap')->rpcCall('item.get',$params);
		/*add_2017/12/20_by_wanghaichao_start*/
		$item_pack_info=app::get('syspromotion')->model('package_item')->getRow('sku_ids',array('item_id'=>$itemId,'package_id'=>$package_id));
		/*add_2017/12/20_by_wanghaichao_end*/
		//取出sku
        $skuData = null;
		if($item_pack_info['sku_ids']){
			$specDesc=null;
			$pack_sku_ids=(array)explode(',',$item_pack_info['sku_ids']);
			foreach($pack_sku_ids as $skuId){
				$skuData[$skuId] = $detailData['sku'][$skuId];
                $specDescValue = $skuData[$skuId]['spec_desc']['spec_value_id'];
				foreach( $specDescValue as $specId=>$specValueId )
				{
                    $specDesc[$specId][$specValueId] = $detailData['spec_desc'][$specId][$specValueId];
                }
			}

            $detailData['spec_desc'] = $specDesc;
		}else{
			$skuData=$detailData['sku'];
		}

        $detailData['spec'] = $this->__getSpec($detailData['spec_desc'], $skuData);

		//echo "<pre>";print_r($detailData);die();
		$sku=array();
		//处理sku数据
		if($detailData['sku']){
			foreach($detailData['sku'] as $k=>$v){
				$sku_key=$v['spec_desc']['spec_value_id']['1'].'_'.$v['spec_desc']['spec_value_id']['2'];
				$sku_value=$v['sku_id'];
				$sku[$sku_key]=$sku_value;
			}
		}
		$pagedata['sku']=$sku;
		$pagedata['item']=$detailData;
		$data['image']=$detailData['image_default_id'];
        $data ['html'] = view::make('topwap/item/package/itemspec.html', $pagedata)->render();
        return response::json($data);
	}
}
