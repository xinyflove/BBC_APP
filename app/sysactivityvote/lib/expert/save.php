<?php

/**
 * Class sysactivityvote_expert_save
 * add_gurundong_20171016_start
 */
class sysactivityvote_expert_save{
    /**
     * 新增评论
     * @param $insert
     * @return mixed
     */
    public function insertComment($insert){
        $expertComment = app::get('sysactivityvote')->model('expert_comment');
        $gameInfo = app::get("sysactivityvote")->model("game")->getRow("active_id,shop_id",["game_id"=>$insert["game_id"]]);
        $insert["active_id"] = $gameInfo["active_id"];
        $insert["shop_id"] = $gameInfo["shop_id"];
        $insert["create_time"] = time();
        $insert["modified_time"] = time();
        $insertId = $expertComment->insert($insert);
        return $insertId;
    }

    /**
     * 修改评论
     * @param $data
     */
    public function updateComment($data){
        $expertComment = app::get('sysactivityvote')->model('expert_comment');
        $data["modified_time"] = time();
        $expertComment->update($data,["expert_comment_id"=>$data["expert_comment_id"]]);
    }
}