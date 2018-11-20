<?php
/**
 * 每日上新页控制器
 */
class topwap_ctl_item_livehot extends topwap_controller {


    public function __construct()
    {
        $this->objLibSearch = kernel::single('topwap_item_search');
    }

    public function index()
    {
        $filter = input::get();
		/*add_2017/11/10_by_wanghaichao_start*/
		$url= url::action("topwap_ctl_item_livehot@index",$filter);
		/*add_2017/11/10_by_wanghaichao_end*/		
        //首页轮播图
        $pic = unserialize($filter['pic_serialize']);
        foreach ($pic as $pic_v){
            $pagedata['pic'][] = $pic_v;
        }

        $limit = 10;
        $where = [
            'item_id'=>implode(',',$filter['item_select_ids']),
            'limit'=>$limit,
            //保证是上架商品
            'approve_status'=>'onsale'
        ];

        $items_arr = app::get('topwap')->rpcCall('item.handle.list',$where);

        $shop_infos = app::get('topc')->rpcCall('shop.list.all.get');

        foreach($items_arr['items'] as $key => &$items) {
            //    获取店铺logo，店铺类型logo，店铺名称
            if (!empty($shop_infos[$items['shop_id']]['shop_logo'])) {
                $items['shop_logo'] = $shop_infos[$items['shop_id']]['shop_logo'];
            }
        }
        $pagedata['items'] = $items_arr['items'];
        $pagedata['pagers']['total'] = ceil(count($filter['item_select_ids'])/$limit);
        $pagedata['activeFilter']['item_select_ids'] = $filter['item_select_ids'];
        $pagedata['activeFilter']['limit'] = $limit;
        $pagedata['logo_pic'] = $filter['logo_pic'];
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        $pagedata['sell_out_img'] = app::get('sysconf')->getConf('item.sell.out.default.img');
		/*add_2017/11/10_by_wanghaichao_start*/
		$share_img=app::get('sysconf')->getConf('sysconf_setting.wap_logo');   //商城logo
		$weixin['descContent']= app::get('site')->getConf('site.orther_share_describe');
		$weixin['imgUrl']= base_storager::modifier($share_img);
		$weixin['linelink']= $url;
		$weixin['shareTitle']=app::get('site')->getConf('site.orther_share_title');
		$pagedata['weixin']=$weixin;
		/*add_2017/11/10_by_wanghaichao_end*/
        return $this->page('topwap/item/live_hot/index.html', $pagedata);
    }

    public function ajaxGetItemList()
    {
        $filter = input::get();
        $where = [
            'item_id'=>implode(',',$filter['item_select_ids']),
            'pages'=>($filter['pages']-1)*$filter['limit'],
            'page_size'=>$filter['limit'],
            'orderBy'=>$filter['orderBy'],
            //保证是上架商品
            'approve_status'=>'onsale'
        ];
        $items_arr = app::get('topwap')->rpcCall('item.handle.list',$where);
        $shop_infos = app::get('topc')->rpcCall('shop.list.all.get');

        foreach($items_arr['items'] as $key => &$items) {
            //    获取店铺logo，店铺类型logo，店铺名称
            if (!empty($shop_infos[$items['shop_id']]['shop_logo'])) {
                $items['shop_logo'] = $shop_infos[$items['shop_id']]['shop_logo'];
            }
        }

        $pagedata['items'] = $items_arr['items'];
        $pagedata['pagers']['total'] = ceil(count($filter['item_select_ids'])/$filter['limit']);
        $pagedata['sell_out_img'] = app::get('sysconf')->getConf('item.sell.out.default.img');

        if( !$pagedata['pagers']['total'] )
        {
            return view::make('topwap/empty/item.html',$pagedata);
        }

        if($pagedata['items'])
        {
            return view::make('topwap/item/live_hot/item_list.html',$pagedata);
        }
    }
}

