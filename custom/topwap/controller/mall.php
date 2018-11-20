<?php
class topwap_ctl_mall extends topwap_controller{

    public function index()
    {
        $filter = input::get();
		/*add_2017/11/10_by_wanghaichao_start*/
		$url= url::action("topwap_ctl_mall@index",$filter);
		/*add_2017/11/10_by_wanghaichao_end*/		
        $this->setLayoutFlag($filter['page_name']);
		/*add_2017/11/10_by_wanghaichao_start*/
		$share_img=app::get('sysconf')->getConf('sysconf_setting.wap_logo');   //商城logo
		$weixin['descContent']= app::get('site')->getConf('site.orther_share_describe');
		$weixin['imgUrl']= base_storager::modifier($share_img);
		$weixin['linelink']= $url;
		$weixin['shareTitle']=app::get('site')->getConf('site.orther_share_title');
		$pagedata['weixin']=$weixin;
		/*add_2017/11/10_by_wanghaichao_end*/
        return $this->page("topwap/mall/index.html");
    }
}
