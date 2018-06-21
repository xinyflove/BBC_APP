<?php
/**
 * Created by PhpStorm.
 * @desc:
 * @author: admin
 * @date: 2018-02-22 14:51
 */

class sysactivityvote_vote_auto {
    private $gameNumber=array(02,102,103,135,137,170,171,18,40,57,58,61,65,66,70,77);
    private $gameId=array(2,23,25,26,29,46,47,66,74,79,86,101,102,109,133,134,174,177);

    public function autoVote(){
        $gameGroup=$this->gameId;
        $obj=app::get('sysactivityvote')->model('game');
        foreach($gameGroup as $id ){
            $gamePoll=rand(300,500);
            $cgamePoll=$obj->getRow('game_poll',array('game_id'=>$id));
            $cgamePoll['game_poll']+=$gamePoll;
            $result=$obj->update($cgamePoll,array('game_id'=>$id));
        }

    }
}