<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topwap_ctl_default extends topwap_controller
{
    public function index()
    {
        $redirect_url = url::action('topwap_ctl_tvshopping@index');
        header("Location: ".$redirect_url);
        exit();
        $GLOBALS['runtime']['path'][] = array('title'=>app::get('topwap')->_('首页〉'),'link'=>kernel::base_url(1));
        /*add_start_gurundong_20171026*/
        /*猜你喜欢额外js所需的html结构加载*/
        $widgets_instance = app::get('site')->model('widgets_instance')->getList('core_id',['theme'=>'mobilemall']);
        foreach ($widgets_instance as $v){
            if($v['core_id'] == 'wap_guess_like'){
                $GLOBALS['is_wap'] = true;
            }
        }
        /*add_end_gurundong_20171026*/
		/*add_2017/11/10_by_wanghaichao_start*/
		$share_img=app::get('sysconf')->getConf('sysconf_setting.wap_logo');   //商城logo
		$weixin['descContent']= app::get('site')->getConf('site.share_describe');
		$weixin['imgUrl']= base_storager::modifier($share_img);
		$weixin['linelink']= url::action("topwap_ctl_default@index");
		$weixin['shareTitle']=app::get('site')->getConf('site.share_title');
		$pagedata['weixin']=$weixin;
		/*add_2017/11/10_by_wanghaichao_end*/
		/*add_2018/6/28_by_wanghaichao_start*/
		$baseUrl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$pagedata['signPackage'] = kernel::single('topwap_jssdk')->index($baseUrl);
		/*add_2018/6/28_by_wanghaichao_end*/
        $GLOBALS['def_image_set'] = app::get('image')->getConf('image.set.item')['L']['default_image'];
        $this->setLayoutFlag('index');
        return $this->page("topwap/index.html",$pagedata);
    }

    public function switchToPc()
    {
        setcookie('browse', 'pc');
        return redirect::route('topc');
    }
}
