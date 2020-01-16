<?php
/**
 * 电视购物页面类型定义文件
 * @auth: xinyufeng
 */
class topshop_tvshopping_pages {

    public $pages_type = array(
        'wap' => array(
            'home' => '首页',
            'qtv_live' => 'QTV直播页',
        ),
        'pc' => array(),
    );
	
	/* action_name (par1, par2, par3)
	* 构造函数,初始化$pages_type值
	* author by wanghaichao
	* date 2018/11/21
	*/
	public function __construct($shop_id){
		$filter['shop_id']=$shop_id;
		$filter['deleted']=0;
		$pages=app::get('sysshop')->model('pagetype')->getList('name,page_type,platform',$filter);
		if($pages){
			foreach($pages as $k=>$v){
				$data[$v['platform']][$v['page_type']]=$v['name'];
			}
			foreach($this->pages_type as $k=>&$v){
				$v=array_merge($v,$data[$k]);
			}
		}
	}
}