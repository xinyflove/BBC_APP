<?php
class topwap_ctl_category extends topwap_controller{
    public function index()
    {
        $this->setLayoutFlag('category');

        $virtualcatEnable = app::get('syscategory')->getConf('virtualcat.wapenable');
        if($virtualcatEnable =='true'){
            $apiParams = [
                'fields' => 'virtual_cat_id,virtual_cat_name',
                'platform' => 'h5',
            ];
            $virtualCatList = app::get('topwap')->rpcCall('category.virtualcat.get.list', $apiParams);
            $pagedata['data'] = $virtualCatList;
            return $this->page('topwap/category/virtualcatindex.html',$pagedata);
        }else{
            $catList = app::get('topwap')->rpcCall('category.cat.get.list',array('fields'=>'cat_id,cat_name'));
            $pagedata['data'] = $catList;
            /*add_20171101_by_fanglongji_start*/
            if(input::get('select_id'))$pagedata['select_id'] = input::get('select_id');
            /*add_20171101_by_fanglongji_start*/
            /*add_20171101_by_xinyufeng_start*/
            $pagedata['defaultCatImg'] = kernel::single('image_data_image')->getImageSetting('item');
            /*add_20171101_by_xinyufeng_end*/
            return $this->page('topwap/category/index.html',$pagedata);
        }
    }
}
