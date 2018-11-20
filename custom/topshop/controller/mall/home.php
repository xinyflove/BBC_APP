<?php
/**
 * User: xinyufeng
 * Time: 2018-10-26 10:30
 * Desc: 广电优选首页
 */
class topshop_ctl_mall_home extends topshop_mall_controller {

    /**
     * 商品列表页
     * @return html
     */
    public function index()
    {
        $pagedata = array();
        
        $commonPageData = $this->_getCommonPageData();
        $pagedata = array_merge($pagedata, $commonPageData);

        $sysmallWidgets = kernel::single('sysmall_data_widgets');
        $pagedata['widget_instance'] = $sysmallWidgets->getWidgets('index');

        return $this->page('topshop/mall/home.html', $pagedata);
    }
    
}