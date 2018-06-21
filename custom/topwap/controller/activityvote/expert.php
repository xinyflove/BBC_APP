<?php

/**
 * 评审专家控制器
 * add_start_gurundong_2017_10_18
 * Class topwap_ctl_activityvote_expert
 */
class topwap_ctl_activityvote_expert extends topwap_ctl_activityvote_vote{

    /**
     * 评审专家列表
     */
    public function expertList(){
        $active_id = input::get("active_id");
        $page = input::get("page");
        $page = 1;
        $params = array();
        $activeInfo = app::get("sysactivityvote")->model("active")->getRow("shop_id",["active_id"=>$active_id]);
        $params["active_id"] = $active_id;
        $params["shop_id"] = $activeInfo["shop_id"];
        $params["page_no"] = $page;
        $params["page_size"] = 8;
        $list = app::get("topwap")->rpccall("activity.vote.expert.list",$params);
        $pagedata = array();
        $pagedata["expert_list"] = $list["data"];
        return $this->page("topwap/activityvote/review_List.html",$pagedata);
    }

    /**
     * 专家评审详情
     */
    public function expertDetail(){
        $expert_id = input::get("expert_id");
        $expertInfo = app::get("topwap")->rpccall("activity.vote.expert.get",["expert_id"=>$expert_id]);
        /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
        $queryBuilder = app::get('sysactivityvote')->database()->createQueryBuilder();
        $comment_query = $queryBuilder->select('a.comment_content,b.game_name,b.game_number')->from('sysactivityvote_expert_comment','a')
        ->leftJoin('a','sysactivityvote_game','b','a.game_id=b.game_id')
        ->where('a.expert_id='.$expert_id);
        $comment_data = $comment_query->execute()->fetchAll();
        $pagedata = array();
        $pagedata= $expertInfo;
        $pagedata['comment'] = $comment_data;
//        dump($pagedata);die;
        return $this->page("topwap/activityvote/review_details.html",$pagedata);
    }
}