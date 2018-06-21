<?php
class sysactivityvote_expert_post{
    public function insertComment($insert){
        $expertComment = app::get('sysactivityvote')->model('expert_comment');
        $insert["create_time"] = time();
        $insert["modified_time"] = time();
        $insertId = $expertComment->insert($insert);
        return $insertId;
    }
}