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
class sysactivityvote_api_expert_comment_save{

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
            'expert_comment_id' => ['type'=>'int','valid'=>'', 'title'=>'评论id',  'example'=>'', 'desc'=>'评论id'],
            'game_id' => ['type'=>'int','valid'=>'', 'title'=>'参赛id',  'example'=>'', 'desc'=>'参赛id'],
            'expert_id' => ['type'=>'int','valid'=>'', 'title'=>'专家id',  'example'=>'', 'desc'=>'专家id'],
            'comment_content' => ['type'=>'string','valid'=>'', 'title'=>'专家评论详情',  'example'=>'', 'desc'=>'专家评论最多为500个字符','msg'=>'专家评论最多为500个字符'],
        );
        return $data;
    }

    /**
     * 增加专家评论
     * 新增评论require参数:game_id expert_id comment_content
     * 更新评论require参数:expert_comment_id
     * @return int insertId
     */
    public function saveComment($params)
    {
        //保存类型
        $type = "insert";
        if($params["expert_comment_id"]){
            $expertComment = app::get('sysactivityvote')->model('expert_comment')->getRow("expert_comment_id",["expert_comment_id"=>$params["expert_comment_id"]]);
            if($expertComment){
                $type = "update";
            }
        }else{
            if(!$params["game_id"]){
                throw new \LogicException("参赛id不能为空");
            }
            if(!$params["expert_id"]){
                throw new \LogicException("专家id不能为空");
            }
            if(!$params["comment_content"]){
                throw new \LogicException("专家评论不能为空");
            }else{
                if(mb_strlen($params["comment_content"]) > 500){
                    throw new \LogicException("评论字符数不能大于500");
                }
                if(mb_strlen($params["comment_content"]) < 10){
                    throw new \LogicException("评论字符数不能小于10");
                }
            }
            $insert = array();
            // $insert['active_id'] = $params['active_id'];
            $insert['game_id'] = $params['game_id'];
            // $insert['shop_id'] = $params['shop_id'];
            $insert['expert_id'] = $params['expert_id'];
            $insert['comment_content'] = $params['comment_content'];
        }
        if($type == "insert"){
            $expertInsertObj = kernel::single("sysactivityvote_expert_save");
            $insertId = $expertInsertObj->insertComment($insert);
            return $insertId;
        }
        if($type == "update"){
            $expertUpdateObj = kernel::single("sysactivityvote_expert_save");
            $expertUpdateObj->updateComment($params);
            return $params["expert_comment_id"];
        }
    }
}
