<?php
/**
 * User: xinyufeng
 * Time: 2018-10-26 10:30
 * Desc: 广电优选店铺操作公共类
 */
class topshop_ctl_mall_admin_controller extends topshop_controller {

    /**
     * 商品二维码生成，指向手机端
     * @param $itemId
     * @return string
     */
    protected function _qrCode($itemId,$size = 60)
    {
        $url = url::action("topwap_ctl_item_detail@index",array('item_id'=>$itemId));
        return getQrcodeUri($url, $size, 10);
    }

    /**
     * 获取店铺分类
     * @return array
     */
    protected function _getShopCat()
    {
        $shopCatMdl = app::get('sysshop')->model('shop_cat');
        $list = $shopCatMdl->getList('cat_id,cat_name', array('shop_id'=>$this->shopId));
        $cats = array_column($list, 'cat_name', 'cat_id');
        return $cats;
    }
}