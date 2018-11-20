<?php
/**
 * 电视购物页面wap端挂件-店铺logo挂件
 * @auth: xinyufeng
 */
class topshop_tvshopping_widgets_itemlist {
   public function makeParams($params)
    {
		$item_id=implode(',',$params['item_id']);
        $filter['shop_id'] = $params['shop_id'];
        $filter['item_id'] = $item_id;
        //$params['page_size'] = $filter['page_size'];
        $filter['pages'] = $filter['pages'] ? $filter['pages'] : 1;
          $filter['page_size'] = 50;
        /*add_20171009_by_fanglongji_start*/
        $filter['orderBy'] = $filter['orderBy'] ? $filter['orderBy'] : '';
        /*add_20171009_by_fanglongji_end*/
//		$params['is_onsale']=$filter['is_onsale']?$filter['is_onsale']:'yes';
        $itemsList = kernel::single('topwap_item_search')->search($filter)
                     //     ->setItemsActivetyTag()
                          //->setItemsPromotionTag()
                          ->getData();
		$list=$itemsList['list'];
		foreach($list as $k=>$v){
			$list[$k]['sort']=$params['sort'][$v['item_id']];
		}
		$item=$this->array_sort($list,'sort');
		$params['item']=$item;
		unset($params['item_id'],$params['item_sku'],$params['sort']);
		$params['item_id_no']=$item_id;
		//echo "<pre>";print_r($params);die();
        return $params;

		//$item=array();
		//$item=app::get('sysitem')->model('item')->getList('title,item_id,image_default_id,price',array('item_id'=>$params['item_id']));
		//foreach($item as $k=>$v){
		//	$item[$k]['sort']=$params['sort'][$v['item_id']];
		//}
		//$item=$this->array_sort($item,'sort');
		//unset($params['item_id'],$params['item_sku'],$params['sort']);
		//$params['item']=$item;
		//return $params;
    }

	public function array_sort($arr,$keys,$type='asc')
    {
        $keysvalue = $new_array = array();
        foreach ($arr as $k=>$v)
        {
            $keysvalue[$k] = $v[$keys];
        }
        if($type == 'asc')
        {
            asort($keysvalue);
        }
        else
        {
            arsort($keysvalue);
        }
        reset($keysvalue);
        foreach ($keysvalue as $k=>$v)
        {
            $new_array[$k] = $arr[$k];
        }
        return $new_array;
    }
}