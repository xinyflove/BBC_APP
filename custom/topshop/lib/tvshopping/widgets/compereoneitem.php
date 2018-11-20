<?php
/**
 * 电视购物页面wap端挂件-店铺logo挂件
 * @auth: xinyufeng
 */
class topshop_tvshopping_widgets_compereoneitem {

    public function makeParams($params)
    {
		$filter['shop_id']=$params['shop_id'];
		$filter['page_size']=$params['comperecount'];
		$filter['order']='sort asc';
		$compereList = app::get('topshop')->rpcCall('compere.list', $filter);
		$data=array();
		foreach($compereList['data'] as $k=>$v){
			$data[$k]['compere']=$v;
			$filter['compere_id']=$v['id'];
			$filter['page_size']=1;
			$itemlist = app::get('topshop')->rpcCall('compere.item.list', $filter);
			$data[$k]['item']=$itemlist['data'][0];
		}
		$setting['title_pic']=$params['title_pic'];
		$setting['data']=$data;
		return $setting;
    }
}