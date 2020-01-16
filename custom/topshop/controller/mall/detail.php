<?php
/**
 * User: xinyufeng
 * Time: 2018-10-25 10:30
 * Desc: 广电优选商品详情
 */
class topshop_ctl_mall_detail extends topshop_mall_controller {

    /**
     * 商品详情页
     * @return html
     */
    public function index()
    {
        $itemId = intval(input::get('item_id'));// 商品id
        if(empty($itemId))
        {
            $url = url::action('topshop_ctl_mall_list@index');
            header("Location:".$url);
            exit;
        }

        $mallItem = app::get('sysmall')->model('item')->getRow('item_id, status, shop_id', array('item_id'=>$itemId));
        if(!$mallItem)
        {
            throw new \InvalidArgumentException('广电优选不存在此商品!');
        }
        // 商品状态
        $pagedata['status'] =  $mallItem['status'];
        // 是否本店铺商品
        $pagedata['isOwn'] = $mallItem['shop_id'] == $this->shopId ? 1 : 0;
        // 是否已拉取
        $pagedata['isHas'] = $this->__isHasMallItem($itemId, $this->shopId);
        
        $params['item_id'] = $itemId;
        $params['fields'] = "*,item_desc.pc_desc,item_count,item_store,item_status,sku,item_nature,spec_index";
        $detailData = app::get('topc')->rpcCall('item.get',$params);
        if(!$detailData)
        {
            throw new \InvalidArgumentException('原始商品不存在!');
        }

        // 毛利率
        $detailData['profit'] = round(($detailData['price']-$detailData['supply_price'])/$detailData['price']*100,1);
        // 规格名称
        $detailData['spec'] = $this->__getSpec($detailData['spec_desc'], $detailData['sku']);
        //相册图片
        if( $detailData['list_image'] )
        {
            $detailData['list_image'] = explode(',',$detailData['list_image']);
        }
        $pagedata['item'] = $detailData;

        // 面包屑数据
        $pagedata['breadcrumb'] = $this->__breadcrumb($detailData);
        // 热销
        $pagedata['hotList'] = $this->__getHotItem();

        $commonPageData = $this->_getCommonPageData();
        $pagedata = array_merge($pagedata, $commonPageData);

        //var_dump($pagedata['item']);die;
        return $this->page('topshop/mall/detail.html', $pagedata);
    }

    /**
     * 面包屑数据
     * @param $detailData
     * @return array
     */
    private function __breadcrumb($detailData)
    {
        $cat = app::get('topc')->rpcCall('category.cat.get.data',array('cat_id'=>intval($detailData['cat_id'])));
        $brand = app::get('topc')->rpcCall('category.brand.get.info',array('brand_id'=>$detailData['brand_id'],'fields'=>'brand_id,brand_name'));

        $breadcrumb = [
            ['url'=>url::action('topc_ctl_topics@index',array('cat_id'=>$cat['lv1']['cat_id'])),'title'=>$cat['lv1']['cat_name']],
            ['url'=>'','title'=>$cat['lv2']['cat_name']],
            ['url'=>url::action('topc_ctl_list@index',array('cat_id'=>$cat['lv3']['cat_id'])),'title'=>$cat['lv3']['cat_name']],
            ['url'=>url::action('topc_ctl_list@index',array('cat_id'=>$cat['lv3']['cat_id'],'brand_id'=>$brand['brand_id'])),'title'=>$brand['brand_name']],
            ['url'=>'','title'=>$detailData['title']],
        ];

        return $breadcrumb;
    }

    /**
     * 判断是否已拉取商品
     * @param $item_id
     * @param $shop_id
     * @return int
     */
    private function __isHasMallItem($item_id, $shop_id)
    {
        $item = app::get('sysitem')->model('item')->getRow('item_id', array('shop_id'=>$shop_id,'init_item_id'=>$item_id));
        if($item) return 1;
        return 0;
    }

    /**
     * 获取销售属性信息
     * @param $spec
     * @return array
     */
    private function __getSpec($spec)
    {
        if( empty($spec) ) return array();
        $keys = array_keys($spec);
        $arr = app::get('syscategory')->model('props')->getList('prop_id,prop_name', array('prop_id|in'=>$keys));
        $data['specName'] = array_column($arr, 'prop_name', 'prop_id');

        return $data;
    }

    /**
     * 店铺热销
     * @return mixed
     */
    private function __getHotItem()
    {
        $objLibMallList = kernel::single('sysmall_data_list');

        $params['filter']['status'] = 'onsale';
        $params['offset'] = 0;
        $params['limit'] = 5;
        $params['orderBy'] = 'paid_quantity_desc';
        $list = $objLibMallList->getMallItemList($params);
        
        return $list;
    }
}