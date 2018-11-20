<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topshop_ctl_shop_newDecorate extends topshop_controller {

    public function index()
    {
        $pagedata['debugStatus'] = config::get('app.debug');
        return $this->page('topshop/shop/new_decorate.html', $pagedata);
    }

    public function edit()
    {
        $platform = input::get('type','wap');
        $pageName = input::get('page','index');

        $shopdata = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>shopAuth::getShopId()));
        $pagedata['shop'] = $shopdata;

        $apiParams = [
            'shop_id'    => $this->shopId,
            'page_name'  => $pageName,
            'platform'   => $platform,
        ];

        $data = app::get('topshop')->rpcCall('sysdecorate.widgets.get', $apiParams);
        if( $data )
        {
            $decorate = $this->getDecorateInfo($data);
            $pagedata['decorate'] = json_encode($decorate);
        }
        return view::make('topshop/shop/decorate/'.$platform.'/'.$pageName.'.html', $pagedata);
    }

    public function save()
    {
		$post=input::get();
        $data = json_decode(input::get('decorate'), true);

        $orderSort = 0;
        $pageName = input::get('page','index');
        $platform = input::get('type', 'wap');
        if($data){
            $widgetsIds = array_column($data, 'widgets_id');
            if( $widgetsIds )
            {
                //清除已经删除的widgetsId
                app::get('topshop')->rpcCall('sysdecorate.widgets.delete', [
                    'shop_id'    => $this->shopId,
                    'page_name'  => $pageName,
                    'platform'   => $platform,
                    'exclude_widgetsIds' => implode(',',$widgetsIds),
                ]);
            }else{
                app::get('topshop')->rpcCall('sysdecorate.widgets.clean', [
                    'shop_id'    => $this->shopId,
                    'page_name'  => $pageName,
                    'platform'   => $platform,
                ]);
            }

            foreach( $data as $widgetsName=>$row )
            {
                $apiParams = [];
                $apiParams = [
                    'shop_id'    => $this->shopId,
                    'page_name'  => $pageName,
                    'platform'   => $platform,
                    'order_sort' => $orderSort,
                ];

                if( $row['widgets_id'] )
                {
                    $apiParams['widgets_id'] = $row['widgets_id'];
                }

                switch( $row['widget_name'] )
                {
                case 'shopsign':
                    $apiParams['imgurl'] = $row['imgurl'];
                    try
                    {
                        app::get('topshop')->rpcCall('sysdecorate.shopsign.add', $apiParams);
                    }
                    catch(Exception $e)
                    {
                        return $this->splash('error', '', $e->getMessage(),true);
                    }
                    break;
                case 'nav':
                    if( $row['list'] )
                    {
                        $list = [];
                        foreach( $row['list'] as $key=>$list )
                        {
                            if( $list['name'] )
                            {
                                $row['list'][$key]['item_ids'] = implode(',', $list['item_ids']);
                            }
                        }
                        $apiParams['list'] = json_encode($row['list']);
                    }
                    try
                    {
                        app::get('topshop')->rpcCall('sysdecorate.nav.add', $apiParams);
                    }
                    catch(Exception $e)
                    {
                        return $this->splash('error', '', $e->getMessage(),true);
                    }
                    break;
                case 'oneimg':
                    $apiParams['imgurl']   = $row['imgurl'];
                    $apiParams['imglink']  = $row['imglink'];
                    try
                    {
                        app::get('topshop')->rpcCall('sysdecorate.oneimg.add', $apiParams);
                    }
                    catch(Exception $e)
                    {
                        return $this->splash('error', '', $e->getMessage(),true);
                    }
                    break;
                case 'goods':
                    $apiParams['title']   = $row['title'];
					/*modify_2017/11/9_by_wanghaichao_start*/
					/*$itemIds = array_column($row['list'], 'item_id');
					
                    $apiParams['item_id'] = implode(',' ,$itemIds);*/				
					//循环数组,拼接item_id和排序
					$item_Idss='';
					foreach($row['list'] as $k=>$v){
						$item_Idss.=(int)$v['item_id'].'-'.(int)$v['sort'].',';
					}
                    $apiParams['item_id'] = $item_Idss;
					/*modify_2017/11/9_by_wanghaichao_end*/

                    try
                    {
                        app::get('topshop')->rpcCall('sysdecorate.goods.add', $apiParams);
                    }
                    catch(Exception $e)
                    {
                        return $this->splash('error', '', $e->getMessage(),true);
                    }
                    break;
                    
                case 'goods_fix':
                    $apiParams['title']   = $row['title'];
                    /*modify_2017/11/9_by_wanghaichao_start*/
                    /*$itemIds = array_column($row['list'], 'item_id');
                    
                    $apiParams['item_id'] = implode(',' ,$itemIds);*/
                    //循环数组,拼接item_id和排序
                    $item_Idss='';
                    foreach($row['list'] as $k=>$v){
                        $item_Idss.=(int)$v['item_id'].'-'.(int)$v['sort'].',';
                    }
                    $apiParams['item_id'] = $item_Idss;
                    /*modify_2017/11/9_by_wanghaichao_end*/
                    
                    try
                    {
                        app::get('topshop')->rpcCall('sysdecorate.goodsfix.add', $apiParams);
                    }
                    catch(Exception $e)
                    {
                        return $this->splash('error', '', $e->getMessage(),true);
                    }
                    break;

                // add_2017/9/12_by_王衍生_start  标签商品、商品列表
                case 'tagsgoods':
                    $apiParams['title']   = $row['title'];
                    $itemIds = array_column($row['list'], 'item_id');
                    $apiParams['item_id'] = implode(',' ,$itemIds);
					/*add_2017/9/22_by_wanghaichao_start*/
					if(!empty($row['iconimgurl'])){
						$apiParams['iconimgurl']=$row['iconimgurl'];
					}else{
						$apiParams['iconimgurl']='';
					}
					/*add_2017/9/22_by_wanghaichao_end*/
                    try
                    {
                        app::get('topshop')->rpcCall('sysdecorate.tagsgoods.add', $apiParams);
                    }
                    catch(Exception $e)
                    {
                        return $this->splash('error', '', $e->getMessage(),true);
                    }
                    break;
                case 'goods3list':
                    // $apiParams['title']   = $row['title'];
                    $itemIds = array_column($row['list'], 'item_id');
                    $apiParams['item_id'] = implode(',' ,$itemIds);
                    try
                    {
                        app::get('topshop')->rpcCall('sysdecorate.goods3list.add', $apiParams);
                    }
                    catch(Exception $e)
                    {
                        return $this->splash('error', '', $e->getMessage(),true);
                    }
                    break;
                // add__end_by_王衍生_end
                case 'slider':
                    $list = [];
                    foreach($row['list'] as $row)
                    {
                        $val['imgurl']  = $row['imgurl'];
                        $val['imglink'] = $row['imglink'];
                        $list[] = $val;
                    }
                    $apiParams['list'] = json_encode($list);
                    try
                    {
                        app::get('topshop')->rpcCall('sysdecorate.slider.add', $apiParams);
                    }
                    catch(Exception $e)
                    {
                        return $this->splash('error', '', $e->getMessage(),true);
                    }
                    break;
                /*add_20170914_by_xinyufeng_start 季度推荐*/
                case 'quarter':
                    $list = [];
                    foreach($row['list'] as $row)
                    {
                        $val = [];
                        $val['imgurl']  = $row['imgurl'];
                        $val['imglink'] = $row['imglink'];
                        $list[] = $val;
                    }
                    $apiParams['list'] = json_encode($list);
                    try
                    {
                        app::get('topshop')->rpcCall('sysdecorate.quarter.add', $apiParams);
                    }
                    catch(Exception $e)
                    {
                        return $this->splash('error', '', $e->getMessage(),true);
                    }
                    break;
                /*add_20170914_by_xinyufeng_end*/
                /*add_20170915_by_xinyufeng_start 当季推荐*/
                case 'current_quarter':
                    $list = [];
                    foreach($row['list'] as $row)
                    {
                        $val = [];
                        $val['imgurl']  = $row['imgurl'];
                        $val['goods']  = $row['goods'];
                        //$val['item_ids'] = implode(',', $row['item_ids']);
                        $list[] = $val;
                    }
                    $apiParams['list'] = json_encode($list);
                    try
                    {
                        app::get('topshop')->rpcCall('sysdecorate.current_quarter.add', $apiParams);
                    }
                    catch(Exception $e)
                    {
                        return $this->splash('error', '', $e->getMessage(),true);
                    }
                    break;
                /*add_20170915_by_xinyufeng_end*/
                /*add_20170915_by_xinyufeng_start 中部轮播广告*/
                case 'wapslidermid':
                    $list = [];
                    foreach($row['list'] as $row)
                    {
                        $val = [];
                        $val['imgurl']  = $row['imgurl'];
                        $val['imglink'] = $row['imglink'];
                        $list[] = $val;
                    }
                    $apiParams['list'] = json_encode($list);
                    try
                    {
                        app::get('topshop')->rpcCall('sysdecorate.wapslidermid.add', $apiParams);
                    }
                    catch(Exception $e)
                    {
                        return $this->splash('error', '', $e->getMessage(),true);
                    }
                    break;
                /*add_20170915_by_xinyufeng_end*/

				/*add_2017/9/14_by_wanghaichao_start*/
                case 'category':
                    $list = [];
                    foreach($row['list'] as $row)
                    {
                        $val['imgurl']  = $row['imgurl'];
                        $val['imglink'] = $row['imglink'];
                        $val['imgtitle'] = $row['imgtitle'];
                        $list[] = $val;
                    }
                    $apiParams['list'] = json_encode($list);
                    try
                    {
                        app::get('topshop')->rpcCall('sysdecorate.category.add', $apiParams);
                    }
                    catch(Exception $e)
                    {
                        return $this->splash('error', '', $e->getMessage(),true);
                    }
                    break;
				/*add_2017/9/14_by_wanghaichao_end*/

				/*add_2017/9/15_by_wanghaichao_start icon 文章*/
                case 'icon':
                    $apiParams['imgurl']   = $row['imgurl'];
                    $apiParams['imglink']  = $row['imglink'];
                    $articleIds = array_column($row['list'], 'article_id');
                    $apiParams['article_id'] = implode(',' ,$articleIds);
                    try
                    {
                        app::get('topshop')->rpcCall('sysdecorate.icon.add', $apiParams);
                    }
                    catch(Exception $e)
                    {
                        return $this->splash('error', '', $e->getMessage(),true);
                    }
                    break;
				/*add_2017/9/15_by_wanghaichao_start*/				
                case 'wap3image':
					//echo "<pre>";print_r($row['list']);die();
                    $list = [];
                    foreach($row['list'] as $v)
                    {
                        $val['imgurl']  = $v['imgurl'];
                        $val['imglink'] = $v['imglink'];
						if($v['goods'] && !empty($v['goods'])){
							$val['item_id']=$v['goods'][0]['item_id'];
						}else{
							$val['item_id']='';
						}
                        $list[] = $val;
                    }
					//echo "<pre>";print_r($list);die();
					//if(isset($row['goodlist']) && !empty($row['goodlist'])){
					//	$apiParams['item_id']=$row['goodlist'][0]['item_id'];
					//}
                    $apiParams['list'] = json_encode($list);
                    try
                    {
                        app::get('topshop')->rpcCall('sysdecorate.wap3image.add', $apiParams);
                    }
                    catch(Exception $e)
                    {
                        return $this->splash('error', '', $e->getMessage(),true);
                    }
                    break;			
                case 'shopsigns':
                    $apiParams['imgurl']   = $row['imgurl'];
                    try
                    {
                        app::get('topshop')->rpcCall('sysdecorate.shopsigns.add', $apiParams);
                    }
                    catch(Exception $e)
                    {
                        return $this->splash('error', '', $e->getMessage(),true);
                    }
                    break;
				/*add_2017/9/15_by_wanghaichao_end*/
				/*add_2017/9/14_by_gurundong_start*/
                case 'wap2image':
                    $list = [];
                    foreach($row['list'] as $v)
                    {
                        $val['imgurl']  = base_storager::modifier($v['imgurl']);
                        $val['imglink'] = $v['imglink'];
                        if($v['goods']){
                            $val['item_id']=$v['goods'][0]['item_id'];
                        }else{
                            $val['item_id']=null;
                        }
                        $list[] = $val;
                    }
                    $apiParams['list'] = json_encode($list);
                    try
                    {
                        app::get('topshop')->rpcCall('sysdecorate.wap2image.add', $apiParams);
                    }
                    catch(Exception $e)
                    {
                        return $this->splash('error', '', $e->getMessage(),true);
                    }
                    break;
                /*add_2017/9/14_by_gurundong_end*/

                /*add_2017/9/14_by_gurundong_start*/
                case 'hotsell':
                    $apiParams['imgurl']   = $row['imgurl'];
                    $apiParams['imglink']  = $row['imglink'];
                    $itemIds = array_column($row['list'], 'item_id');
                    $apiParams['item_id'] = implode(',' ,$itemIds);
                    try
                    {
                        app::get('topshop')->rpcCall('sysdecorate.hotsell.add', $apiParams);
                    }
                    catch(Exception $e)
                    {
                        return $this->splash('error', '', $e->getMessage(),true);
                    }
                    break;
                /*add_2017/9/18_by_gurundong_end*/
				
                default:
                    $apiParams['list'] = json_encode($row['list']);
                    try
                    {
                        $apiName = 'sysdecorate.'.$row['widget_name'].'.add';
                        app::get('topshop')->rpcCall($apiName, $apiParams);
                    }
                    catch(Exception $e)
                    {
                        return $this->splash('error', '', $e->getMessage(),true);
                    }
                    break;
                }
                $orderSort++;
            }

        }else{
            app::get('topshop')->rpcCall('sysdecorate.widgets.clean', [
                'shop_id'    => $this->shopId,
                'page_name'  => $pageName,
                'platform'   => $platform,
            ]);
        }

        //返回前端数据重新填充页面数据
        $apiParams = [
            'shop_id'    => $this->shopId,
            'page_name'  => $pageName,
            'platform'   => $platform,
        ];
        $data = app::get('topshop')->rpcCall('sysdecorate.widgets.get', $apiParams);
        $decorate = $this->getDecorateInfo($data);
        $wData= json_encode($decorate);
        // $url = url::action('topshop_ctl_shop_newDecorate@edit', array('page'=>$apiParams['page_name'], 'type'=>$apiParams['platform']));

        return $this->splash('success', $wData, $msg,true);
    }

    //格式化decorateData
    private function getDecorateInfo($data){
        $decorateKey = 0;
        $decorate = [];
        foreach( $data as $row )
        {
            $wid = $row['widgets_type'].'_'.$row['widgets_id'];
            $decorate[$decorateKey] = [
                'wid'         => $wid,//前端遍历json使用
                'widget_name' => $row['widgets_type'],
                'widgets_id'  => $row['widgets_id'],
            ];
            switch( $row['widgets_type'] )
            {
            case 'nav':
                foreach( $row['params'] as $k =>$v ){
                   if($v['type'] == 'goods'){
                        $row['params'][$k]['item_ids'] = explode(',', $v['item_ids']);
                   }
                }
                $decorate[$decorateKey]['list'] = $row['params'];
                break;
            case 'shopsign':
                unset($decorate[$decorateKey]);
                $wid = $row['widgets_type'];
                $decorate[$decorateKey]['wid']        = $wid;
                $decorate[$decorateKey]['widgets_id'] = $row['widgets_id'];
                $decorate[$decorateKey]['widget_name'] = $row['widgets_type'];
                $decorate[$decorateKey]['imgurl']     = $row['params']['imgurl'];
                $decorate[$decorateKey]['imgsrc']     = base_storager::modifier($row['params']['imgurl']);
                break;
            case 'oneimg':
                $decorate[$decorateKey]['imgurl'] = $row['params']['imgurl'];
                $decorate[$decorateKey]['imgsrc'] = base_storager::modifier($row['params']['imgurl']);
                $decorate[$decorateKey]['imglink'] = $row['params']['imglink'];
                break;
            /*add_20170914_by_gurundong_start 二图配置*/
            case 'wap2image':
                foreach( $row['params'] as $key=>$val )
                {
                    $row['params'][$key]['imgsrc'] = base_storager::modifier($val['imgurl']);
                    if(!empty($val['item_id'])) {
                        $itemRow = app::get('sysitem')->model('item')->getRow('item_id,image_default_id,title,price', array('item_id' => $val['item_id']));
                        $goodlist = [
                            'item_id' => $itemRow['item_id'],
                            'goodstitle' => str_replace("'",'',$itemRow['title']),
                            'goodslink' => url::action('topwap_ctl_item_detail@index', ['item_id' => $itemRow['item_id']]),
                            'imgurl' => base_storager::modifier($itemRow['image_default_id'], 's'),
                            'price' => $itemRow['price'],
                        ];
                        $row['params'][$key]['goods'][] = $goodlist;
                    }else{
                        $row['params'][$key]['goods'][] = '';
                    }
                }
                $decorate[$decorateKey]['list'] = $row['params'];
                break;
            /*add_20170918_by_gurundong_end*/
            /*add_20170914_by_gurundong_start 热卖*/
            case 'hotsell':
                $itemData = app::get('topshop')->rpcCall('sysdecorate.hotsell.data.get', ['shop_id'=>$this->shopId, 'widgets_id'=>$row['widgets_id'], 'showItemNum'=>8]);
                $list = [];
                foreach($itemData as $itemRow )
                {
                    $list[] = [
                        'item_id'    => $itemRow['item_id'],
                        'goodstitle' => str_replace("'",'',$itemRow['title']),
                        'goodslink'  => url::action('topwap_ctl_item_detail@index', ['item_id'=>$itemRow['item_id']]),
                        'imgurl'     => base_storager::modifier($itemRow['image_default_id'],'s'),
                        'price'      => $itemRow['price'],
                    ];
                }
                $decorate[$decorateKey]['imgurl'] = $row['params']['imgurl'];
                $decorate[$decorateKey]['imgsrc'] = base_storager::modifier($row['params']['imgurl']);
                $decorate[$decorateKey]['imglink'] = $row['params']['imglink'];
                $decorate[$decorateKey]['title'] = addslashes($row['params']['title']);
                $decorate[$decorateKey]['list']  = $list;
                break;
            /*add_20170918_by_gurundong_end*/
            case 'slider':
                foreach( $row['params'] as $key=>$val )
                {
                    $row['params'][$key]['imgsrc'] = base_storager::modifier($val['imgurl']);
                }
                $decorate[$decorateKey]['list'] = $row['params'];
                break;
            case 'goods':
                $itemData = app::get('topshop')->rpcCall('sysdecorate.goods.data.get', ['shop_id'=>$this->shopId, 'widgets_id'=>$row['widgets_id'], 'showItemNum'=>20]);
                $list = [];
                foreach($itemData as $itemRow )
                {
                    $list[] = [
                        'item_id'    => $itemRow['item_id'],
                        'goodstitle' => str_replace("'",'',$itemRow['title']),
                        'goodslink'  => url::action('topwap_ctl_item_detail@index', ['item_id'=>$itemRow['item_id']]),
                        'imgurl'     => base_storager::modifier($itemRow['image_default_id'],'s'),
                        'price'      => $itemRow['price'],
						/*add_2017/11/9_by_wanghaichao_start*/
						//增加排序
						'sort'=>$itemRow['sort'],
						/*add_2017/11/9_by_wanghaichao_end*/
                        'soldnum'    => 'x',
                    ];
                }
                $decorate[$decorateKey]['title'] = addslashes($row['params']['title']);
                $decorate[$decorateKey]['list']  = $list;
                break;
            case 'goods_fix':
                $itemData = app::get('topshop')->rpcCall('sysdecorate.goodsfix.data.get', ['shop_id'=>$this->shopId, 'widgets_id'=>$row['widgets_id'], 'showItemNum'=>20]);
                $list = [];
                foreach($itemData as $itemRow )
                {
                    $list[] = [
                        'item_id'    => $itemRow['item_id'],
                        'goodstitle' => str_replace("'",'',$itemRow['title']),
                        'goodslink'  => url::action('topwap_ctl_item_detail@index', ['item_id'=>$itemRow['item_id']]),
                        'imgurl'     => base_storager::modifier($itemRow['image_default_id'],'s'),
                        'price'      => $itemRow['price'],
                        /*add_2017/11/9_by_wanghaichao_start*/
                        //增加排序
                        'sort'=>$itemRow['sort'],
                        /*add_2017/11/9_by_wanghaichao_end*/
                        'soldnum'    => 'x',
                    ];
                }
                $decorate[$decorateKey]['title'] = addslashes($row['params']['title']);
                $decorate[$decorateKey]['list']  = $list;
                break;
            /*add_20170914_by_xinyufeng_start 季度推荐*/
            case 'quarter':
                foreach( $row['params'] as $key=>$val )
                {
                    $row['params'][$key]['imgsrc'] = base_storager::modifier($val['imgurl']);
                }
                $decorate[$decorateKey]['list'] = $row['params'];
                break;
            /*add_20170914_by_xinyufeng_end*/
            /*add_20170915_by_xinyufeng_start 当季推荐*/
            case 'current_quarter':
                foreach( $row['params'] as $key=>$val )
                {
                    $row['params'][$key]['imgsrc'] = base_storager::modifier($val['imgurl']);
                    //$row['params'][$key]['item_ids'] = explode(',', $val['item_ids']);
                }
                $decorate[$decorateKey]['list'] = $row['params'];
                break;
            /*add_20170915_by_xinyufeng_end*/
            /*add_20170915_by_xinyufeng_start 中部轮播广告*/
            case 'wapslidermid':
                foreach( $row['params'] as $key=>$val )
                {
                    $row['params'][$key]['imgsrc'] = base_storager::modifier($val['imgurl']);
                }
                $decorate[$decorateKey]['list'] = $row['params'];
                break;
            /*add_20170915_by_xinyufeng_end*/
            /*add_20170914_by_wanghaichao_start 季度推荐*/
            case 'category':
                foreach( $row['params'] as $key=>$val )
                {
                    $row['params'][$key]['imgsrc'] = base_storager::modifier($val['imgurl']);
                }
                $decorate[$decorateKey]['list'] = $row['params'];
                break;
            /*add_20170914_by_wanghaichao_end*/

			/*add_2017/9/15_by_wanghaichao_start*/
            case 'icon':
                $decorate[$decorateKey]['imgurl'] = $row['params']['imgurl'];
                $decorate[$decorateKey]['imgsrc'] = base_storager::modifier($row['params']['imgurl']);
                $decorate[$decorateKey]['imglink'] = $row['params']['imglink'];

                $articleData = app::get('topshop')->rpcCall('sysdecorate.icon.data.get', ['shop_id'=>$this->shopId, 'widgets_id'=>$row['widgets_id'], 'showNum'=>12]);
                $list = [];
                foreach($articleData['list'] as $article )
                {
                    $list[] = [
                        'article_id'    => $article['article_id'],
                        'title' => str_replace("'",'',$article['title']),
						'label'=>$article['label']
                        // 'goodslink'  => url::action('topwap_ctl_item_detail@index', ['item_id'=>$article['item_id']]),
                        // 'imgurl'     => base_storager::modifier($article['image_default_id'],'s'),
                        // 'price'      => $article['price'],
                        // 'soldnum'    => 'x',
                    ];
                }
                $decorate[$decorateKey]['list'] = $list;
                break;
			/*add_2017/9/15_by_wanghaichao_end*/
			
			/*add_2017/9/18_by_wanghaichao_start*/
            case 'wap3image':
                foreach( $row['params'] as $key=>$val )
                {
                    $row['params'][$key]['imgsrc'] = base_storager::modifier($val['imgurl']);
					if($val['item_id']){
						$itemRow=app::get('sysitem')->model('item')->getRow('item_id,image_default_id,title,price',array('item_id'=>$val['item_id']));
						$goodlist = [
							'item_id'    => $itemRow['item_id'],
							'goodstitle' => str_replace("'",'',$itemRow['title']),
							'goodslink'  => url::action('topwap_ctl_item_detail@index', ['item_id'=>$itemRow['item_id']]),
							'imgurl'     => base_storager::modifier($itemRow['image_default_id'],'s'),
							'price'      => $itemRow['price'],
						];
						$row['params'][$key]['goods'][]=$goodlist;
					}else{
						$row['params'][$key]['goods'][]='';
					}
                }
                $decorate[$decorateKey]['list'] = $row['params'];
                break;
			/*add_2017/9/18_by_wanghaichao_end*/
			 // add start 2017/09/15 王衍生 标签商品挂件
            case 'tagsgoods':
                $itemData = app::get('topshop')->rpcCall('sysdecorate.tagsgoods.data.get', ['shop_id'=>$this->shopId, 'widgets_id'=>$row['widgets_id'], 'showItemNum'=>12]);
                $list = [];
                foreach($itemData as $itemRow )
                {
                    $list[] = [
                        'item_id'    => $itemRow['item_id'],
                        'goodstitle' => str_replace("'",'',$itemRow['title']),
                        'goodslink'  => url::action('topwap_ctl_item_detail@index', ['item_id'=>$itemRow['item_id']]),
                        'imgurl'     => base_storager::modifier($itemRow['image_default_id'],'s'),
                        'price'      => $itemRow['price'],
                        'soldnum'    => 'x',
                    ];
                }
                $decorate[$decorateKey]['title'] = $row['params']['title'];
				/*add_2017/9/22_by_wanghaichao_start*/				
                $decorate[$decorateKey]['iconimgurl'] = $row['params']['iconimgurl'];
				/*add_2017/9/22_by_wanghaichao_end*/
                $decorate[$decorateKey]['list']  = $list;
                break;
            case 'goods3list':
                $itemData = app::get('topshop')->rpcCall('sysdecorate.goods3list.data.get', ['shop_id'=>$this->shopId, 'widgets_id'=>$row['widgets_id'], 'showItemNum'=>60]);
                $list = [];
                foreach($itemData as $itemRow )
                {
                    $list[] = [
                        'item_id'    => $itemRow['item_id'],
                        'goodstitle' => str_replace("'",'',$itemRow['title']),
                        'goodslink'  => url::action('topwap_ctl_item_detail@index', ['item_id'=>$itemRow['item_id']]),
                        'imgurl'     => base_storager::modifier($itemRow['image_default_id'],'s'),
                        'price'      => $itemRow['price'],
                        'soldnum'    => 'x',
                    ];
                }
                // $decorate[$decorateKey]['title'] = $row['params']['title'];
                $decorate[$decorateKey]['list']  = $list;
                break;
            // add end 王衍生
			/*add_2017/9/22_by_wanghaichao_start*/
            case 'shopsigns':
                $decorate[$decorateKey]['imgurl'] = $row['params']['imgurl'];
                $decorate[$decorateKey]['imgsrc'] = base_storager::modifier($row['params']['imgurl']);
                break;
			/*add_2017/9/22_by_wanghaichao_end*/
			
            default:
                $decorate[$decorateKey]['list'] = $row['params'];
                break;
            }
            $decorateKey++;
        }

        return $decorate;
    }
}

