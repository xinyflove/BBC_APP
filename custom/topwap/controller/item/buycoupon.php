<?php
/**
 * Created by PhpStorm.
 * User: xinyufeng
 * Date: 2017/10/26
 * Time: 9:15
 * Desc: 购券专场控制器
 */
class topwap_ctl_item_buycoupon extends topwap_controller {
    
    public function __construct()
    {
        $this->objLibSearch = kernel::single('topwap_item_search');
    }
    
    public function getList()
    {
        $filter = input::get();
        
        $virtualList = $this->__getVirtualList($filter);
        $pagedata['items'] = $virtualList['data'];

        $pagedata['pagers']['total'] = $virtualList['pagers_total'];

        //商品默认图片
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        //商品售磬角标
        $pagedata['sell_out_img'] = app::get('sysconf')->getConf('item.sell.out.default.img');
        //头部图片
        $pagedata['top_pic'] = $filter['top_pic'];
        
        return $this->page('topwap/item/buycoupon/list.html', $pagedata);
    }
    
    public function ajaxGetItemList()
    {
        $filter = input::get();
        
        $virtualList = $this->__getVirtualList($filter);
        $pagedata['items'] = $virtualList['data'];

        //商品默认图片
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        //商品售磬角标
        $pagedata['sell_out_img'] = app::get('sysconf')->getConf('item.sell.out.default.img');

        return view::make('topwap/item/buycoupon/item_list.html',$pagedata);
    }

    private function __getVirtualList($filter)
    {
        $itemsList = $this->objLibSearch->searchVirtual($filter)
            ->setItemsActivetyTag()
            ->setItemsPromotionTag()
            ->getData();
        $data['data'] = $itemsList['list'];

        foreach ($data['data'] as &$item_v){
            $item_v['real_store'] = (int)$item_v['store'] - (int)$item_v['freez'];
        }

        $shop_infos = app::get('topc')->rpcCall('shop.list.all.get');
        foreach($data['data'] as &$items)
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
        }

        $data['pagers_total'] = $this->objLibSearch->getMaxPages();

        return $data;
    }
}