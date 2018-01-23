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
class sysactivityvote_api_expert_comment_postExpert{

    /**
     * api接口的名称
     * @var string
     */
    public $apiDescription = "专家评论提交接口";

    /**
     * 定义API传入的应用级参数
     * @desc 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入，并且定义参数的数据类型，参数是否必填，参数的描述
     * @return array 返回传入参数
     */
    public function getParams()
    {
        $data['params'] = array(
            'active_id' => ['type'=>'int','valid'=>'required|integer', 'title'=>'活动id',  'example'=>'', 'desc'=>'活动id'],
            'game_id' => ['type'=>'int','valid'=>'required|integer', 'title'=>'参赛id',  'example'=>'', 'desc'=>'参赛id'],
            'shop_id' => ['type'=>'int','valid'=>'required|integer', 'title'=>'店铺id',  'example'=>'', 'desc'=>'店铺id'],
            'expert_id' => ['type'=>'int','valid'=>'required|integer', 'title'=>'专家id',  'example'=>'', 'desc'=>'专家id'],
            'comment_content' => ['type'=>'string','valid'=>'max:500', 'title'=>'专家评论详情',  'example'=>'', 'desc'=>'专家评论最多为500个字符','msg'=>'专家评论最多为500个字符'],
        );
        return $data;
    }

    /**
     * 增加专家评论
     * @return int insertId
     */
    public function postComment($params)
    {

        $insert = array();
        $insert['active_id'] = $params['active_id'];
        $insert['game_id'] = $params['game_id'];
        $insert['shop_id'] = $params['shop_id'];
        $insert['expert_id'] = $params['expert_id'];
        $insert['comment_content'] = $params['comment_content'];
        $expertInsertObj = kernel::single("sysactivityvote_expert_post");
        $insertId = $expertInsertObj->insertComment($insert);
        return $insertId;
    }
}
