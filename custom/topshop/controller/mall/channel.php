<?php
/**
 * User: zhangshu
 * Date: 2018-11-27
 * Desc: 广电优选频道自选
 */
class topshop_ctl_mall_channel extends topshop_mall_controller {

    /**
     * 频道自选
     * @return html
     */
    public function index()
    {
        $pagedata = array();
        
        $commonPageData = $this->_getCommonPageData();
        $pagedata = array_merge($pagedata, $commonPageData);

        $sysmallWidgets = kernel::single('sysmall_data_widgets');
        $pagedata['widget_instance'] = $sysmallWidgets->getWidgets('channel');

        return $this->page('topshop/mall/channel.html', $pagedata);
    }
    
}