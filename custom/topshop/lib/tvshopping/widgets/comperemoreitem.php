<?php
/**
 * 电视购物页面wap端挂件-店铺logo挂件
 * @auth: xinyufeng
 */
class topshop_tvshopping_widgets_comperemoreitem {

    public function makeParams($params)
    {
		//echo"<pre>";print_r($params);die();
		$filter['shop_id']=$params['shop_id'];
		$filter['page_size']=$params['comperecount'];
		$filter['order']='sort asc';
		$compereList = app::get('topshop')->rpcCall('compere.list', $filter);
		$data=array();
		foreach($compereList['data'] as $k=>$v){
			$data[$k]['compere']=$v;
			$filter['compere_id']=$v['id'];
			$filter['page_size']=$params['itemcount'];
			$itemlist = app::get('topshop')->rpcCall('compere.item.list', $filter);
			$data[$k]['item']=$itemlist['data'];
		}
		$setting['title_pic']=$params['title_pic'];
		$setting['data']=$data;
		$setting['pageTotal']=$compereList['page_total'];
		$setting['page']=$compereList['current_page'];
		$setting['shop_id']=$params['shop_id'];
		return $setting;
    }
}