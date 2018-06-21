<?php
class sysactivityvote_api_activeDelete {

    public $apiDescription = "删除单条投票活动数据";

    public $use_strict_filter = true;

    public function getParams()
    {
        $return['params'] = array(
            'active_id' => ['type'=>'int','valid'=>'','example'=>'','desc'=>'投票活动id'],
        );

        return $return;
    }

    public function delete($params)
    {
        $relateData = $this->__getActiveRelateData($params['active_id']);

        if($relateData)
        {
            throw new \LogicException('投票活动有关联数据，无法删除');
        }

        $objMdlActive = app::get('sysactivityvote')->model('active');
        $delete = $objMdlActive->delete(array('active_id'=>$params['active_id']));
        
        return $delete;
    }

    private function __getActiveRelateData($active_id)
    {
        //分类表
        $objMdlCat = app::get('sysactivityvote')->model('cat');
        $catInfo = $objMdlCat->getRow('cat_id',array('active_id'=>$active_id));
        if($catInfo) return true;

        //参赛表
        $objMdlGame = app::get('sysactivityvote')->model('game');
        $gameInfo = $objMdlGame->getRow('game_id',array('active_id'=>$active_id));
        if($gameInfo) return true;

        //专家表
        $objMdlExpert = app::get('sysactivityvote')->model('expert');
        $expertInfo = $objMdlExpert->getRow('expert_id',array('active_id'=>$active_id));
        if($expertInfo) return true;

        //赠品管理表
        $objMdlGift = app::get('sysactivityvote')->model('gift');
        $giftInfo = $objMdlGift->getRow('gift_id',array('active_id'=>$active_id));
        if($giftInfo) return true;

        //投票表
        $objMdlVote = app::get('sysactivityvote')->model('vote');
        $voteInfo = $objMdlVote->getRow('vote_id',array('active_id'=>$active_id));
        if($voteInfo) return true;

        //专家评价参数表
        $objMdlExpertComment = app::get('sysactivityvote')->model('expert_comment');
        $expertCommentInfo = $objMdlExpertComment->getRow('expert_comment_id',array('active_id'=>$active_id));
        if($expertCommentInfo) return true;

        return false;
    }
}