<?php

/**
 * ShopEx licence
 * - promotion.activity.info
 * - 用于获取活动详情
 * @copyright Copyright (c) 2005-2016 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license   http://ecos.shopex.cn/ ShopEx License
 * @link      http://www.shopex.cn/
 * @author    shopex 2016-05-18
 */
class sysactivityvote_api_gift_getInfo{

    /**
     * api接口的名称
     * @var string
     */
    public $apiDescription = "获取赠品详情";

    /**
     * 定义API传入的应用级参数
     * @desc 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入，并且定义参数的数据类型，参数是否必填，参数的描述
     * @return array 返回传入参数
     */
    public function getParams()
    {
        $data['params'] = array(
            'active_id' => ['type'=>'int',        'valid'=>'', 'title'=>'活动id',  'example'=>'', 'desc'=>'活动id'],
            'shop_id' => ['type'=>'int',        'valid'=>'', 'title'=>'店铺id',  'example'=>'', 'desc'=>'店铺id'],
            'gift_id' => ['type'=>'int',        'valid'=>'required', 'title'=>'专家id',  'example'=>'', 'desc'=>'赠品id'],
            'fields'      => ['type'=>'field_list', 'valid'=>'', 'title'=>'查询字段', 'example'=>'', 'desc'=>'查询字段'],
        );
        return $data;
    }

    /**
     * 获取专家详情
     */
    public function getInfo($params)
    {
        if($params['shop_id'])
        {
            $filter['shop_id'] = $params['shop_id'];
        }

        if($params['active_id'])
        {
            $filter['active_id'] = $params['active_id'];
        }

        if($params['gift_id'])
        {
            $filter['gift_id'] = $params['gift_id'];
        }

        $row = "*";
        if($params['fields'])
        {
            $row = $params['fields'];
        }

        $objgift = kernel::single('sysactivityvote_gift');
        $dataInfo = $objgift->getInfo($row,$filter);

        return $dataInfo;
    }
}
