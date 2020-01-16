<?php
class topwap_ctl_newshop extends topwap_controller{

    public $limit = 10;
    public $maxpages = 100;

    public $orderSort = array(
        'addtime-l' => 'list_time desc',
        'addtime-s' => 'list_time asc',
        'price-l' => 'price desc',
        'price-s' => 'price asc',
        'sell-l' => 'sold_quantity desc',
        'sell-s' => 'sold_quantity asc',
    );

    public function __construct()
    {
        // start add 王衍生 20170924
        kernel::single('base_session')->start();
        // end add 王衍生 20170924
        parent::__construct();
        $this->objLibSearch = kernel::single('topwap_item_search');
        $this->setLayoutFlag('shop');
    }

    public function index()
    {
        $shopId = input::get('shop_id');

        /*add_20171013_by_xinyufeng_start*/
        $shop_setting = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>$shopId));
        $pagedata['shop_setting'] = $shop_setting;
        /*add_20171013_by_xinyufeng_end*/

        /*add_by_xinyufeng_2018-08-07_start*/
        // 如果是电视购物跳转到电视购物首页
        if($shop_setting['shop_live_switch'] == 'on')
        {
            $url = url::action('topwap_ctl_tvshopping@index',array('shop_id'=>$shopId));
            //return redirect::to($url);
            header("Location:".$url);exit;
        }
        /*add_by_xinyufeng_2018-08-07_end*/

        $pagedata = $this->__common($shopId);

        //店铺关闭后跳转至关闭页面
        if($pagedata['shopdata']['status'] == "dead")
        {
            return $this->page('topwap/shop/close_shop.html', $pagedata);
        }

        // start add 王衍生 20170924
        $_SESSION['shop_id'] = $shopId;
        // 商品售磬角标
        $pagedata['sell_out_img'] = app::get('sysconf')->getConf('item.sell.out.default.img');
        // end add 王衍生 20170924

        $pagedata['shopId'] = $shopId;

        // 店铺分类
        $pagedata['shopcat'] = app::get('topwap')->rpcCall('shop.cat.get',array('shop_id'=>$shopId));
        foreach($pagedata['shopcat'] as $shopCatId=>&$row)
        {
            if( $row['children'] )
            {
                $row['cat_id'] = $row['cat_id'].','.implode(',', array_column($row['children'], 'cat_id'));
            }
        }

        $pagedata['collect'] = $this->__CollectInfo($shopId);
		/*add_2017/9/18_by_wanghaichao_start*/		
		$pagedata['now']=time();
		/*add_2017/9/18_by_wanghaichao_end*/

		/*add_2017/9/27_by_wanghaichao_start*/
		$extsetting=$this->__getExtSetting($shopId);
		$pagedata['extsetting']=$extsetting;
		$ad_show=$_COOKIE['ad_show'];
		if(!$ad_show){
			setcookie('ad_show','1',time()+300);
		}
		$pagedata['cartCount']=$this->getCartCount();
		$pagedata['ad_show']=$ad_show;
		//微信分享的
		$weixin['imgUrl']= base_storager::modifier($pagedata['shopdata']['shop_logo']);
		$weixin['linelink']= url::action("topwap_ctl_newshop@index",array('shop_id'=>$shopId));
		$weixin['shareTitle']=$extsetting['params']['share']['shopcenter_title'];
		$weixin['descContent']=$extsetting['params']['share']['shopcenter_describe'];
		$pagedata['weixin']=$weixin;
		$pagedata['wapTitle']=$pagedata['shopdata']['shop_name'];
		if($pagedata['shopdata']['customer'])
        {
            $pagedata['shopdata']['customer'] = $pagedata['shopdata']['customer'].'&partnerId='.userAuth::id();
        }
		/*add_2017/9/27_by_wanghaichao_end*/

		/*add_2018/6/28_by_wanghaichao_start*/
		$baseUrl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$pagedata['signPackage'] = kernel::single('topwap_jssdk')->index($baseUrl);
		/*add_2018/6/28_by_wanghaichao_end*/
        $pagedata['def_image_set'] = app::get('image')->getConf('image.set.item')['L']['default_image'];
        return $this->page('topwap/shop/index/index.html', $pagedata);
    }

    /**
     * 获取店铺详情
     *
     * @param int $shopId 店铺ID
     */
    public function shopInfo()
    {
        $shopId = input::get('shop_id');
        $pagedata['shopinfo'] = app::get('topwap')->rpcCall('shop.get',['shop_id'=>$shopId]);
        $pagedata['shopDsrData'] = $this->__getShopDsr($shopId);
        $pagedata['collect'] = $this->__CollectInfo($shopId);
        $url = url::action("topwap_ctl_shop@index",array('shop_id'=>$shopId));
        $pagedata['qrCodeData'] = getQrcodeUri($url,80,0);
        return $this->page('topwap/shop/shop_info.html',$pagedata);

    }

    public function shopItemList()
    {
        $filter = input::get();
        $itemsList = $this->__getShowItems($filter);

        $pagedata['items'] = $itemsList['list'];
        $activeFilter = $this->objLibSearch->getActiveFilter();
        $pagedata['activeFilter'] = $activeFilter;
        $pagedata['search_keywords'] = $activeFilter['search_keywords'];
        $pagedata['shopId'] = $activeFilter['shop_id'];

        //默认图片
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        //店铺分类
        $pagedata['shopcat'] = app::get('topwap')->rpcCall('shop.cat.get',array('shop_id'=>$activeFilter['shop_id']));
        foreach($pagedata['shopcat'] as $shopCatId=>&$row)
        {
            if( $row['children'] )
            {
                $row['cat_id'] = $row['cat_id'].','.implode(',', array_column($row['children'], 'cat_id'));
            }
        }
        $pagedata['pagers']['total'] = $this->objLibSearch->getMaxPages();

        return $this->page('topwap/shop/list/index.html', $pagedata);
    }

    public function ajaxGetShopItemList(){
        $filter = input::get();
        $itemsList = $this->__getShowItems($filter);
        $pagedata['items'] = $itemsList['list'];

        $activeFilter = $this->objLibSearch->getActiveFilter();
        $pagedata['activeFilter'] = $activeFilter;
        $pagedata['pagers']['total'] = $this->objLibSearch->getMaxPages();
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        return view::make('topwap/shop/list/item_list.html',$pagedata);

    }


    /**
     * 获取店铺模板页面头部共用部分的数据
     *
     * @param int $shopId 店铺ID
     * @return array
     */
    private function __common($shopId)
    {
        $shopId = intval($shopId);
        $apiParams = [
            'shop_id'    => $shopId,
            'page_name'  => 'index',
            'platform'   => 'wap',
        ];

        $data['widgets'] = app::get('topshop')->rpcCall('sysdecorate.widgets.get', $apiParams);

        foreach ($data['widgets'] as $k => $v) {
            if($v['widgets_type'] =='slider'){
                $data['widgets'][$k]['slider_first_image'] = reset($v['params']);
                $data['widgets'][$k]['slider_last_image'] = end($v['params']);
            }

            if($v['widgets_type'] == 'goods') {
				/*add_2017/11/9_by_wanghaichao_start*/
				//增加排序的
				$filter=$v['params'];
				$item_id=array();
				$item_sort=array();
				foreach($filter['item_id'] as $ka=>$v){
					$item_id[]=explode('-',$v);
				}
				foreach($item_id as $ka=>$v){
					$item_sort[$v[0]]=$v[1];
				}
				//$item_sort=array_filter($item_sort);
				$itemId=array_filter(array_keys($item_sort));
				/*add_2017/11/9_by_wanghaichao_end*/
				
                $filter = array(
                    'shop_id' => $v['shop_id'],
					
					/*modify_2017/11/9_by_wanghaichao_start*/
					/*
                    'item_id' => implode(',', $v['params']['item_id']),*/
					//排序
                    'item_id' => implode(',', $itemId),
					/*modify_2017/11/9_by_wanghaichao_end*/
					
                    /*modify_20171009_by_fanglongji_start*/
                    /*
                    'page_size' => 6,
                    */
                    'page_size' => 25,
                    'orderBy'   => $this->orderSort['addtime-l'],
                    /*modify_20171009_by_fanglongji_end*/
                    'pages' => 1,
					'is_onsale'=>'yes',
                );
				
				/*modify_20171013_by_xinyufeng_start*/
				//$data['widgets'][$k]['showitems'] = $this->__getShowItems($filter);
				$_tempItemsData = $this->__getShowItems($filter);	
				/*modify_2017/11/9_by_wanghaichao_start*/
				/*增加排序*/
				$item_sort=array_filter($item_sort);
				if(!empty($item_sort)){
					$itemList=$_tempItemsData['list'];
					foreach($item_sort as $ke=>$v){
						if($itemList[$ke]){
							$itemList[$ke]['sort']=$v;
						}
					}
					$itemList=$this->array_sort($itemList,'sort');
					foreach($itemList as $ke=>$item){
						$itemList[$ke]['real_store']= (int)$item['store']-(int)$item['freez'];
					}
				}else{
					$_tempItemsData_0 = array();//售罄
					$_tempItemsData_1 = array();//有库存的
					if(!empty($_tempItemsData['list']))
					{
						foreach($_tempItemsData['list'] as $_tk => $_tv)
						{
							if(($_tv['store'] - $_tv['freez'])>0)
							{
								$_tempItemsData_1[$_tk] = $_tv;
								$_tempItemsData_1[$_tk]['real_store']= (int)$_tv['store']-(int)$_tv['freez'];
							}else
							{
								$_tempItemsData_0[$_tk] = $_tv;
								$_tempItemsData_0[$_tk]['real_store']= (int)$_tv['store']-(int)$_tv['freez'];
							}
						}
					}
					$itemList=$_tempItemsData_1+$_tempItemsData_0;
				}
				
				/*modify_2017/11/9_by_wanghaichao_end*/
				
			
				

                //$data['widgets'][$k]['showitems'] = array('list'=>$_tempItemsData_1 + $_tempItemsData_0);
				/*modify_20171013_by_xinyufeng_end*/
				/*modify_2017/11/9_by_wanghaichao_start*/
				/*增加排序*/
				$data['widgets'][$k]['showitems']['list'] =$itemList;
				/*modify_2017/11/9_by_wanghaichao_end*/
				
				$count=app::get('sysitem')->model('item')->count(array('shop_id'=>$shopId,'item_id|notin'=>$itemId,'status'=>'onsale'));
				$data['widgets'][$k]['totalPage']=ceil($count/6);
				
				/*modify_2017/11/9_by_wanghaichao_start*/
				/*$data['widgets'][$k]['itemIds'] = implode(',', $v['params']['item_id']);		*/
                $data['widgets'][$k]['itemIds'] = implode(',', $itemId);
				/*modify_2017/11/9_by_wanghaichao_end*/
				
            }
            if($v['widgets_type'] == 'goods_fix') {
                /*add_2017/11/9_by_wanghaichao_start*/
                //增加排序的
                $filter=$v['params'];
				$item_id=array();
				$item_sort=array();
                foreach($filter['item_id'] as $ka=>$v){
                    $item_id[]=explode('-',$v);
                }
                foreach($item_id as $ka=>$v){
                    $item_sort[$v[0]]=$v[1];
                }
                //$item_sort=array_filter($item_sort);
                $itemId=array_filter(array_keys($item_sort));
                /*add_2017/11/9_by_wanghaichao_end*/
                $filter = array(
                    'shop_id' => $v['shop_id'],
                    /*modify_2017/11/9_by_wanghaichao_start*/
                    /*
                     'item_id' => implode(',', $v['params']['item_id']),*/
                    //排序
                    'item_id' => implode(',', $itemId),
                    /*modify_2017/11/9_by_wanghaichao_end*/
                    /*modify_20171009_by_fanglongji_start*/
                    /*
                     'page_size' => 6,
                     */
                    'page_size' => 10000,
                    'orderBy'   => $this->orderSort['addtime-l'],
                    /*modify_20171009_by_fanglongji_end*/
                    'pages' => 1,
					'is_onsale'=>'no',
                );
                /*modify_20171013_by_xinyufeng_start*/
                //$data['widgets'][$k]['showitems'] = $this->__getShowItems($filter);
                $_tempItemsData = $this->__getShowItems($filter);
                /*modify_2017/11/9_by_wanghaichao_start*/
                /*增加排序*/
                $item_sort=array_filter($item_sort);
                if(!empty($item_sort)){
                    $itemList=$_tempItemsData['list'];
                    foreach($item_sort as $ke=>$v){
						if($itemList[$ke]){
							$itemList[$ke]['sort']=$v;
						}
                    }
                    $itemList=$this->array_sort($itemList,'sort');
                    foreach($itemList as $ke=>$item){
                        $itemList[$ke]['real_store']= (int)$item['store']-(int)$item['freez'];
                    }
                }else{
                    $_tempItemsData_0 = array();//售罄
                    $_tempItemsData_1 = array();//有库存的
                    if(!empty($_tempItemsData['list']))
                    {
                        foreach($_tempItemsData['list'] as $_tk => $_tv)
                        {
                            if(($_tv['store'] - $_tv['freez'])>0)
                            {
                                $_tempItemsData_1[$_tk] = $_tv;
                                $_tempItemsData_1[$_tk]['real_store']= (int)$_tv['store']-(int)$_tv['freez'];
                            }else
                            {
                                $_tempItemsData_0[$_tk] = $_tv;
                                $_tempItemsData_0[$_tk]['real_store']= (int)$_tv['store']-(int)$_tv['freez'];
                            }
                        }
                    }
                    $itemList=$_tempItemsData_1+$_tempItemsData_0;
                }
                /*modify_2017/11/9_by_wanghaichao_end*/
                //$data['widgets'][$k]['showitems'] = array('list'=>$_tempItemsData_1 + $_tempItemsData_0);
                /*modify_20171013_by_xinyufeng_end*/
                /*modify_2017/11/9_by_wanghaichao_start*/
                /*增加排序*/
                $data['widgets'][$k]['showitems']['list'] =$itemList;
                /*modify_2017/11/9_by_wanghaichao_end*/
                $count=app::get('sysitem')->model('item')->count(array('shop_id'=>$shopId));
                $data['widgets'][$k]['totalPage']=ceil($count/6);
                /*modify_2017/11/9_by_wanghaichao_start*/
                /*$data['widgets'][$k]['itemIds'] = implode(',', $v['params']['item_id']);		*/
                $data['widgets'][$k]['itemIds'] = implode(',', $itemId);
                /*modify_2017/11/9_by_wanghaichao_end*/
            }
            if($v['widgets_type']=='wap2image'){
                foreach($v['params'] as $key=>$val){
                    if(isset($val['item_id']) && !empty($val['item_id'])){
                        $iteminfo=$this->__getWap3ImageItem($val['item_id'],$shopId);
                        $v['params'][$key]['item']=$iteminfo;
                        unset($val['item_id']);
                    }
                }
                //echo "<pre>";print_r($v);die();
                $data['widgets'][$k]=$v;
            }
            // add start gurundong 20170915
            if($v['widgets_type'] == 'hotsell') {
                $filter = array(
                    'shop_id' => $v['shop_id'],
                    'item_id' => implode(',', $v['params']['item_id']),
                );
                $data['widgets'][$k]['showitems'] = $this->__getShowItems($filter);
                $data['widgets'][$k]['itemIds'] = implode(',', $v['params']['item_id']);
            }
            // end 20170919 gurundong
			/*add_2017/9/18_by_wanghaichao_start*/
			if($v['widgets_type']=='wap3image'){
				foreach($v['params'] as $key=>$val){					
					if(isset($val['item_id']) && !empty($val['item_id'])){
						$iteminfo=$this->__getWap3ImageItem($val['item_id'],$shopId);
						$v['params'][$key]['item']=$iteminfo;
						unset($val['item_id']);
					}
				}
				//echo "<pre>";print_r($v);die();
				$data['widgets'][$k]=$v;
			}
			
			/*add_2017/9/18_by_wanghaichao_end*/
			// add start 王衍生 20170915
            if($v['widgets_type'] == 'tagsgoods') {
                $filter = array(
                    'shop_id' => $v['shop_id'],
                    'item_id' => implode(',', $v['params']['item_id']),
                    'page_size' => 12,
                    'pages' => 1,
                );
                $data['widgets'][$k]['showitems'] = $this->__getShowItems($filter);
                $data['widgets'][$k]['itemIds'] = implode(',', $v['params']['item_id']);
            }

            if($v['widgets_type'] == 'goods3list') {
                $filter = array(
                    'shop_id' => $v['shop_id'],
                    'item_id' => implode(',', $v['params']['item_id']),
                    'page_size' => 60,
                    'pages' => 1,
                );
                $data['widgets'][$k]['showitems'] = $this->__getShowItems($filter);
                $data['widgets'][$k]['itemIds'] = implode(',', $v['params']['item_id']);
            }
            // add end 王衍生 20170915
			/*add_2017/9/21_by_wanghaichao_start*/
			
            if($v['widgets_type'] == 'icon') {
				if($v['params']['article_id']){
					$filter['article_id|in']=$v['params']['article_id'];
					$shop_article_mdl = app::get('syscontent')->model('article_shop');
					$article = $shop_article_mdl->getList('*',$filter,0,2);
				}
                $data['widgets'][$k]['article'] = $article;
			}
			/*add_2017/9/21_by_wanghaichao_end*/

            /*add_20171012_by_xinyufeng_start*/
            if($v['widgets_type'] == 'current_quarter') {
                if(!empty($v['params'])){
                    foreach ($v['params'] as $cq_k =>$cq_params){
                        $item_ids = array_column($cq_params['goods'], 'item_id');
                        $data['widgets'][$k]['params'][$cq_k]['item_ids'] = implode(',', $item_ids);

                        if(!empty($cq_params['goods'])){
                            if(count($cq_params['goods']) == 1){
                                $filter = array(
                                    'shop_id' => $v['shop_id'],
                                    'item_id' => $cq_params['goods'][0]['item_id'],
                                    'page_size' => 60,
                                    'pages' => 1,
                                );
                                $detailDataRes = $this->__getShowItems($filter);
                                if($detailDataRes['list']){
                                    $detailData = $detailDataRes['list'][$filter['item_id']];
                                    if(empty($detailData['freez'])) $detailData['freez'] = 0;
                                    $data['widgets'][$k]['params'][$cq_k]['iteminfo'] = $detailData;
                                }
                            }
                        }
                    }
                }
            }
            /*add_20171012_by_xinyufeng_end*/
        }

        //店铺信息
        $shopdata = app::get('topwap')->rpcCall('shop.get',array('shop_id'=>$shopId));
        $data['shopdata'] = $shopdata;
        return $data;
    }
	
	/* function_name:__getWap3ImageItem (item_id,shop_id)
	*  函数说明:
	*  获取三图中商品信息的
	* 参数:item_id 商品id;shop_id 商铺id
	* 
	* author by wanghaichao
	* date 2017/9/18
	*/
	
	private function __getWap3ImageItem($item_id,$shop_id){
		if(empty($item_id) || empty($shop_id)){
			return ;
		}
		$filter['shop_id']=$shop_id;
		$filter['item_id']=$item_id;
		$filter['page_size']=1;
		$iteminfo=$this->__getShowItems($filter);
		$info=$iteminfo['list'][$item_id];
		return $info;
	}


    //获取商品
    private function __getShowItems($filter)
    {
        $params['shop_id'] = $filter['shop_id'];
        $params['item_id'] = $filter['item_id'];
        $params['page_size'] = $filter['page_size'];
        $params['pages'] = $filter['pages'] ? $filter['pages'] : 1;
        /*add_20171009_by_fanglongji_start*/
        $params['orderBy'] = $filter['orderBy'] ? $filter['orderBy'] : '';
        /*add_20171009_by_fanglongji_end*/
		$params['is_onsale']=$filter['is_onsale']?$filter['is_onsale']:'yes';
        $itemsList = $this->objLibSearch->search($params)
                          ->setItemsActivetyTag()
                          ->setItemsPromotionTag()
                          ->getData();
		
        return $itemsList;
    }

    /**
     * 获取店铺评分
     *
     * @param int $shopId 店铺ID
     */
    private function __getShopDsr($shopId)
    {
        $params['shop_id'] = $shopId;
        $params['catDsrDiff'] = true;
        $dsrData = app::get('topwap')->rpcCall('rate.dsr.get', $params);
        if( !$dsrData )
        {
            $countDsr['tally_dsr'] = sprintf('%.1f',5.0);
            $countDsr['attitude_dsr'] = sprintf('%.1f',5.0);
            $countDsr['delivery_speed_dsr'] = sprintf('%.1f',5.0);
        }
        else
        {
            $countDsr['tally_dsr'] = sprintf('%.1f',$dsrData['tally_dsr']);
            $countDsr['attitude_dsr'] = sprintf('%.1f',$dsrData['attitude_dsr']);
            $countDsr['delivery_speed_dsr'] = sprintf('%.1f',$dsrData['delivery_speed_dsr']);
        }
        $shopDsrData['countDsr'] = $countDsr;
        $shopDsrData['catDsrDiff'] = $dsrData['catDsrDiff'];
        return $shopDsrData;
    }

    //当前商品收藏和店铺收藏的状态
    private function __CollectInfo($shopId)
    {
        $userId = userAuth::id();
        $collect = unserialize($_COOKIE['collect']);

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

	/* action_name (par1, par2, par3)
	* ajax获取店铺首页商品列表
	* author by wanghaichao
	* date 2017/11/3
	*/
	public function ajaxGetGoodsList($filter){
		$pages=input::get('pages');
		$itemids=input::get('item_ids');
        $params['shop_id'] = input::get('shop_id');
        $params['item_id_no'] = $itemids;
        $params['page_size'] = 6;
		$offset=$params['page_size']*($pages-1);
        //$params['approve_status'] = 'onsale'; //搜索必须为上架的商品
		$params['fields']='SI.item_id,SI.shop_id,SI.title,SI.image_default_id,SI.price,SIC.sold_quantity,SI.sell_time,SIC.paid_quantity,SISS.store,SI.right_logo,SISS.freez';
		$objMdlItem = app::get('sysitem')->model('item');
        $pagedata['itemsList']['list'] =$objMdlItem->getItemListOrderByStore($params['fields'],$params,$offset,$params['page_size']);
        //$pagedata['itemsList'] = app::get('topwap')->rpcCall('item.search',$params);
		$now=time();
		foreach($pagedata['itemsList']['list'] as $k=>&$v){
			$activityItem = app::get('topc')->rpcCall('promotion.activity.item.info',array('item_id'=>$v['item_id'],'valid'=>1),'buyer');
			if($activityItem && $activityItem['start_time']<$now && $activityItem['end_time']>$now){
				$v['price']=$activityItem['activity_price'];
				//$v['activity_tag']=$activityItem['activity_tag'];
			}
		}
        $pagedata['sell_out_img'] = app::get('sysconf')->getConf('item.sell.out.default.img');

        /*add_20171013_by_xinyufeng_start*/
        $shop_setting = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>$params['shop_id']),'seller');
        $pagedata['shop_setting'] = $shop_setting;
        /*add_20171013_by_xinyufeng_end*/
        
        return view::make('topwap/shop/list/goods_list.html',$pagedata);
	}

}
