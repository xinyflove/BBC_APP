<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topshop_ctl_item_cat extends topshop_controller {

    public function index()
    {
        $data = app::get('topshop')->rpcCall('shop.cat.get',array('shop_id'=>$this->shopId));
		/*add_2017/9/29_by_wanghaichao_start*/
		if($data){
			foreach($data as $k=>$v){
				if($v['children']){
					$cat_ids='';
					foreach($v['children'] as $key=>$val){
						$val['url']=url::action('topwap_ctl_shop_list@index',array('shop_id'=>$this->shopId,'sc'=>$val['cat_id']));
						$v['children'][$key]=$val;
						$cat_ids.=$val['cat_id'].',';
					}
				}else{
					$cat_ids='';
				}
				$cat_ids=$cat_ids.$v['cat_id'];
				$v['url']=url::action('topwap_ctl_shop_list@index',array('shop_id'=>$this->shopId,'sc'=>$cat_ids));
				$data[$k]=$v;
			}
		}
		/*add_2017/9/29_by_wanghaichao_end*/
        $pagedata['cat'] = $data;
        $pagedata['nowtime'] = time();
		
        $this->contentHeaderTitle = app::get('topshop')->_('店铺分类列表');
        return $this->page('topshop/item/category.html', $pagedata);
    }

    /**
     * @brief 保存店铺分类数据
     *
     * @return json
     */
    public function storeCat()
    {
        //$shopId = $this->shopId;
        //$data = input::get();
        $params['shop_id'] = $this->shopId;
        $params['catlist'] = serialize(input::get());
        //echo '<pre>';print_r($data);exit();
        $url = url::action('topshop_ctl_item_cat@index');
        try
        {
            $flag = app::get('topshop')->rpcCall('shop.save.cat',$params);
            //$flag = kernel::single('sysshop_data_cat')->storeCat($data,$shopId);
            if( $flag )
            {
                $status = 'success';
                $msg = app::get('topshop')->_('保存成功');
            }
            else
            {
                $status = 'error';
                $msg = app::get('topshop')->_('保存失败');
            }
            $this->sellerlog('保存店铺分类');
            return $this->splash($status,$url,$msg,true);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',$url,$msg,true);
        }
    }

    public function removeCat()
    {
        return $this->splash('success',null,$msg,true);
    }
}

