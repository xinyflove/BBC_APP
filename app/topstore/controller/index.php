<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/3
 * Time: 18:17
 */
class topstore_ctl_index extends topstore_controller {

    public function index()
    {
        $storeId = $this->storeId;
        //获取店铺数据
        $fields = 'store_id,store_name';
        $filter = array('store_id' => $storeId,);
        $storeInfo = app::get('sysstore')->model('store')->getRow($fields, $filter);

        $pagedata['store'] = $storeInfo;
        $this->contentHeaderTitle = app::get('topstore')->_('首页');
        
        return $this->page('topstore/index.html', $pagedata);
    }

    /**
     * 判断浏览器
     * @param null
     * @return null
     */
    public function browserTip()
    {
        return $this->page('topstore/common/browser_tip.html');
    }

    /**
     * 没有权限提示页面
     * @return mixed
     */
    public function nopermission()
    {
        $pagedata['url'] = specialutils::filterCrlf(input::get('next_page', request::server('HTTP_REFERER')));
        return view::make('topstore/permission.html',$pagedata);
    }
}