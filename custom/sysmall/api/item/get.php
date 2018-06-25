<?php
/*
 * Date: 2018-6-15 16:31:30
 * Author: 王衍生
 * authorEmail: 50634235@qq.com
 * company: 青岛广电电商
 * 获取商品在选货商城的详细信息
 * item.get
 */
class sysmall_api_item_get{

    /**
     * 接口作用说明
     */
    public $apiDescription = '获取商品在选货商城的详细信息';

    /**
     * 定义应用级参数，参数的数据类型，参数是否必填，参数的描述
     * 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入
     */
    public function getParams()
    {
        $return['params'] = array(
            'mall_item_id' => ['type'=>'int','valid'=>'','description'=>'选货商品id','example'=>'1','default'=>''],
            'item_id' => ['type'=>'int','valid'=>'','description'=>'商品id','example'=>'2','default'=>''],
            'fields' => ['type'=>'field_list','valid'=>'','description'=>'要获取的商品字段集','example'=>'title,item_store.store,item_status.approve_status','default'=>'*'],
        );

        // $return['extendsFields'] = ['item_desc','item_count','item_store','item_status','sku','item_nature','spec_index','promotion'];
        return $return;
    }

    public function get($params)
    {
        if(is_null($params['mall_item_id']) && is_null($params['item_id']))
        {
            throw new \LogicException('获取数据标识有误[mall_item_id或item_id不为空]');
        }

        $where = array();
        if($params['mall_item_id'])
        {
            $where['mall_item_id'] = $params['mall_item_id'];
        }
        if($params['item_id'])
        {
            $where['item_id'] = $params['item_id'];
        }

        $iteminfo = app::get('sysmall')->model('item')->getRow($params['fields'], $where);

        return $iteminfo;
    }
}
