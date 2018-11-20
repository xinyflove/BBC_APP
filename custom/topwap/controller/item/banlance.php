<?php
/**
 * 余额专场页控制器
 */
class topwap_ctl_item_banlance extends topwap_controller {


    public function __construct()
    {
        $this->objLibSearch = kernel::single('topwap_item_search');
    }

    public function index()
    {
        $filter = input::get();
		/*add_2017/11/10_by_wanghaichao_start*/
		$url= url::action("topwap_ctl_item_banlance@index",$filter);
		/*add_2017/11/10_by_wanghaichao_end*/		
        $limit = 10;
        $filter['pages'] = 1;
        $filter['page_size'] = $limit;
        $filter['item_id'] = implode(',',$filter['item_select_ids']);
        $pagedata = app::get('topwap')->rpcCall('item.handle.list',$filter);

        $pagedata['activeFilter']['item_select_ids'] = $filter['item_select_ids'];
        $pagedata['activeFilter']['limit'] = $limit;
        $pagedata['detail_pic'] = $filter['detail_pic'];
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
		/*add_2017/11/10_by_wanghaichao_start*/
		$share_img=app::get('sysconf')->getConf('sysconf_setting.wap_logo');   //商城logo
		$weixin['descContent']= app::get('site')->getConf('site.orther_share_describe');
		$weixin['imgUrl']= base_storager::modifier($share_img);
		$weixin['linelink']= $url;
		$weixin['shareTitle']=app::get('site')->getConf('site.orther_share_title');
		$pagedata['weixin']=$weixin;
		/*add_2017/11/10_by_wanghaichao_end*/
        return $this->page('topwap/item/banlance/index.html', $pagedata);
    }

    public function ajaxGetItemList()
    {
        $filter = input::get();
        $filter['page_size'] = $filter['limit'];
        $filter['item_id'] = implode(',',$filter['item_select_ids']);
        $pagedata = app::get('topwap')->rpcCall('item.handle.list',$filter);

        if( !$pagedata['pagers']['total'] )
        {
            return view::make('topwap/empty/item.html',$pagedata);
        }

        if($pagedata['items'])
        {
            return view::make('topwap/item/banlance/item_list.html',$pagedata);
        }
    }
}

