<?php

/**
 * ShopEx licence
 * -
 * - 用于获取文章的详情
 * @copyright Copyright (c) 2005-2016 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license   http://ecos.shopex.cn/ ShopEx License
 * @link      http://www.shopex.cn/
 * @author    grd 2017-10-17
 */
class sysactivityvote_api_expert_comment_validateExpert{

    /**
     * api接口的名称
     * @var string
     */
    public $apiDescription = "验证专家信息";

    /**
     * 定义API传入的应用级参数
     * @desc 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入，并且定义参数的数据类型，参数是否必填，参数的描述
     * @return array 返回传入参数
     */
    public function getParams()
    {
        $data['params'] = array(
            'active_id' => ['type'=>'int','valid'=>'required|integer', 'title'=>'活动id',  'example'=>'', 'desc'=>'活动id'],
            'shop_id' => ['type'=>'int','valid'=>'required|integer', 'title'=>'店铺id',  'example'=>'', 'desc'=>'店铺id'],
//            'expert_id' => ['type'=>'int','valid'=>'required|integer', 'title'=>'专家id',  'example'=>'', 'desc'=>'专家id'],
            'comment_password' => ['type'=>'string','valid'=>'required|max:4|min:4', 'title'=>'专家评论密码',  'example'=>'', 'desc'=>'专家评论密码必须为4位','msg'=>'专家评论密码必须为4位'],
//            'fields'      => ['type'=>'field_list', 'valid'=>'',                 'title'=>'查询字段', 'example'=>'', 'desc'=>'查询字段'],
        );
        return $data;
    }

    /**
     * 获取专家详情
     * @return array is_valid代表验证结果，true为专家验证成功，false为验证失败。
     */
    public function getValidate($params)
    {
        $filter['active_id'] = $params['active_id'];
        $filter['shop_id'] = $params['shop_id'];
        $filter['comment_password'] = $params['comment_password'];
//        $row = "*";
//        if($params['fields'])
//        {
//            $row = $params['fields'];
//        }
        $row = "expert_id";

        $objexpert = kernel::single('sysactivityvote_expert');
        $dataInfo = $objexpert->getInfo($row,$filter);
        $resData = [];
        if($dataInfo){
            $resData["is_valid"] = true;
            $resData["expert_id"] = $dataInfo["expert_id"];
        }else{
            $resData["is_valid"] = false;
            $resData["expert_id"] = null;
        }
        return $resData;
    }
}
