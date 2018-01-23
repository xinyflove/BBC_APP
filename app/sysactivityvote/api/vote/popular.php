<?php
/*
* 大众评审投票逻辑
* author by wanghaichao
* date 2017/10/16
*/
class sysactivityvote_api_vote_popular{
    public $apiDescription = "获取参赛活动列表";
    public function getParams()
    {
        $return['params'] = array(
            'game_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'分类id'],
            'open_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'用户的open_id'],
        );
        return $return;
    }
	//大众投票方法
	public function popularVote($params){
		$voteObj=app::get('sysactivityvote')->model('vote');
		$info=$this->getInfo($params['game_id']);
		$info['open_id']=$params['open_id'];
		//判断今天是否投过票了
		$time=time();
		$today=date('Y-m-d 00:00:00',$time);
		$today_start=strtotime($today);   //今天零点的时间戳
		$today_end=$today_start+24*60*60;   //明天零点的时间戳
		$filter['today_start']=$today_start;   
		$filter['today_end']=$today_end;
		$filter['open_id']=$params['open_id'];
		$filter['active_id']=$info['active_id'];
		$filter['game_id']=$params['game_id'];
		$filter['cat_id']=$info['cat_id'];
		$filter['shop_id']=$info['shop_id'];
		$check_result=$this->checkVote($filter);                 //用户进行投票
		if(is_array($check_result)){
			return $check_result;
		}
		$insert['open_id']=$params['open_id'];
		$insert['cat_id']=$info['cat_id'];
		$insert['game_id']=$info['game_id'];
		$insert['shop_id']=$info['shop_id'];
		$insert['active_id']=$info['active_id'];
		$insert['create_time']=time();
		$insert['ip']=request::getClientIp();
		$result=$voteObj->insert($insert);
		if($result){
			$actual_poll=$info['actual_poll']+1;
			$total_poll=$info['total_poll']+1;
			$res=app::get('sysactivityvote')->model('game')->update(array('total_poll'=>$total_poll,'actual_poll'=>$actual_poll),array('game_id'=>$info['game_id']));
			return "VOTE_SUCCESS";
		}else{
			return "VOTE_FAIL";
		}
	}
	
	/* 获取要插入的字段
	* 根据game_id获取
	* author by wanghaichao
	* date 2017/10/17
	*/
	public function getInfo($game_id){
		if(empty($game_id)) return;
		$game=app::get('sysactivityvote')->model('game')->getRow('cat_id,active_id,actual_poll,total_poll',array('game_id'=>$game_id));
		$cat_id=$game['cat_id'];
		$data['active_id']=$game['active_id'];
		$data['cat_id']=$game['cat_id'];
		$cat=app::get('sysactivityvote')->model('cat')->getRow('shop_id',array('cat_id'=>$cat_id));
		$data['shop_id']=$cat['shop_id'];
		$data['game_id']=$game_id;
		$data['actual_poll']=$game['actual_poll'];
		$data['total_poll']=$game['total_poll'];
		return $data;
	}
    
    /* 
    * 判断用户今天投票次数
    * author by wanghaichao
    * date 
    */
	public function checkVote($info){
		$catObj=app::get('sysactivityvote')->model('cat');
		$voteObj=app::get('sysactivityvote')->model('vote');
		$cat_info=$catObj->getRow('personal_everyday_vote_limit,game_personal_everyday_vote_limit',array('cat_id'=>$info['cat_id']));
		$personal_everyday_vote_limit=$cat_info['personal_everyday_vote_limit']; //每人每天的投票次数
		//判断每人每天投票次数是否超过了
		$personal_everyday_filter['create_time|than']=$info['today_start'];
		$personal_everyday_filter['create_time|lthan']=$info['today_end'];
		$personal_everyday_filter['open_id']=$info['open_id'];
		$personal_everyday_filter['cat_id']=$info['cat_id'];
		$personal_everyday_filter['deleted']=0;
		$personal_everyday_count=$voteObj->count($personal_everyday_filter);
		if($personal_everyday_count>=$personal_everyday_vote_limit && $personal_everyday_vote_limit!=0){    
            return array('type'=>'personal_everyday_vote_limit','limit'=>$personal_everyday_vote_limit);
		}
		
		$game_personal_everyday_vote_limit=$cat_info['game_personal_everyday_vote_limit'];    //每个参赛作品每天投几票

		$game_personal_everyday_filter['create_time|than']=$info['today_start'];
		$game_personal_everyday_filter['create_time|lthan']=$info['today_end'];
		$game_personal_everyday_filter['open_id']=$info['open_id'];
		$game_personal_everyday_filter['game_id']=$info['game_id'];
		$game_personal_everyday_filter['deleted']=0;
		$game_personal_everyday_count=$voteObj->count($game_personal_everyday_filter);
		if($game_personal_everyday_count>=$game_personal_everyday_vote_limit && $game_personal_everyday_vote_limit!=0){		
            return array('type'=>'game_personal_everyday_vote_limit','limit'=>$game_personal_everyday_vote_limit);
		}
		return true;
	}

}