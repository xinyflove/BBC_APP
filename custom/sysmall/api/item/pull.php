<?php
/*
 * Date: 2018-6-20 16:31:30
 * Author: wanghaichao
 * authorEmail: 1013631519@qq.com
 * company: 青岛广电电商
 * 从选货商城拉取到本店铺中
 * 
 */
class sysmall_api_item_pull{

    /**
     * 接口作用说明
     */
    public $apiDescription = '从选货商城拉取到本店铺中';

    /**
     * 定义应用级参数，参数的数据类型，参数是否必填，参数的描述
     * 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入
     */
    public function getParams()
    {
        $return['params'] = array(
            'item_id' => ['type'=>'int','valid'=>'required','description'=>'商品id','example'=>'2','default'=>''],
            'shop_id' => ['type'=>'int','valid'=>'required','description'=>'店铺id','example'=>'2','default'=>''],
            'seller_id' => ['type'=>'int','valid'=>'','description'=>'主持人id','example'=>'2','default'=>''],
			'is_compere'=> ['type'=>'int','valid'=>'','description'=>'是否是主持人','example'=>'2','default'=>''],
        );

        // $return['extendsFields'] = ['item_desc','item_count','item_store','item_status','sku','item_nature','spec_index','promotion'];
        return $return;
    }

    public function itemPull($params)
    {
        $objPull = kernel::single('sysmall_pull');
        return  $objPull->pull($params);
    }
}
