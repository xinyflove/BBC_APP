<?php
/**
 * User: xinyufeng
 * Time: 2018-10-25 11:02
 * Desc: 广电优选主控制器
 */
class topshop_mall_controller extends topshop_controller {

    /**
     * 展示模版
     * @param string $view
     * @param array $pagedata
     * @return mixed
     */
    public function page($view, $pagedata = array())
    {
        return view::make($view, $pagedata);
    }

    /**
     * 商品分类
     * @return mixed
     */
    protected function _getItemsCat()
    {
        $itemsCat = app::get('topc')->rpcCall('category.cat.get.list',array('fields'=>'cat_id,cat_name,cat_logo'));

        return $itemsCat;
    }

    /**
     * 公共页面数据
     * @return mixed
     */
    protected function _getCommonPageData()
    {
        // 登录店铺信息
        $pagedata['login_shop'] = $this->shopInfo;

        $objLibMallList = kernel::single('sysmall_data_list');
        // 店铺代售商品id数组
        $pagedata['initItemsId'] = $objLibMallList->getInitItemsId($this->shopId);
        // 店铺代售商品个数
        $pagedata['initItemsNum'] = count($pagedata['initItemsId']);
        // 商品分类列表
        $pagedata['items_cat'] = $this->_getItemsCat();

        return $pagedata;
    }
}