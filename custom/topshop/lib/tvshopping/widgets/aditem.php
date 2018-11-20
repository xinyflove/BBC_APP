<?php
/**
 * 电视购物页面wap端挂件-店铺logo挂件
 * @auth: xinyufeng
 */
class topshop_tvshopping_widgets_aditem {

    public function makeParams($params)
    {
		$item=array();
		$item=app::get('sysitem')->model('item')->getList('title,item_id,image_default_id,price',array('item_id'=>$params['item_id']));
		foreach($item as $k=>$v){
			$item[$k]['sort']=$params['sort'][$v['item_id']];
		}
		$item=$this->array_sort($item,'sort');
		unset($params['item_id'],$params['item_sku'],$params['sort']);
		$params['item']=$item;
		return $params;
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