<?php
/*
 * Date: 2018-7-5
 * Author: xinyufeng
 * authorEmail: xinyflove@sina.com
 * company: 青岛广电电商
 * 代售商品更新
 */
class sysmall_api_item_update {

    /**
     * 接口作用说明
     */
    public $apiDescription = '代售商品更新';

    /**
     * 定义应用级参数，参数的数据类型，参数是否必填，参数的描述
     * 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入
     */
    public function getParams()
    {
        $return['params'] = array(
            'item_id' => ['type'=>'int','valid'=>'required','description'=>'商品id','example'=>'1','default'=>''],
            'shop_id' => ['type'=>'int','valid'=>'required','description'=>'店铺id','example'=>'2','default'=>''],
        );

        return $return;
    }

    public function itemUpdate($params)
    {
        $filter = array(
            'item_id' => $params['item_id'],
            'shop_id' => $params['shop_id'],
        );
        
        $objUpdate = kernel::single('sysmall_update');
        return $objUpdate->update($filter);
    }
}
