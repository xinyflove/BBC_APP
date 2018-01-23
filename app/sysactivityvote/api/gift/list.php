 <?php
/* 
* 获取赠品的列表
* author by wanghaichao
* date 2017/10/18
*/
class sysactivityvote_api_gift_list{
    public $apiDescription = "获取赠品列表";
    public function getParams()
    {
        $data['params'] = array(
            'active_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'活动id'],
            'game_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'作品id'],
			'time'=>['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'当前时间'],
        );
        return $data;
    }
	
	/* 
	* 获取赠品列表
	* author by wanghaichao
	* date 2017/10/18
	*/
	public function getList($params){
		$giftObj=app::get('sysactivityvote')->model('gift');
		if($params['active_id']){
			$filter['active_id']=$params['active_id'];
		}elseif($params['game_id']){
			$filter['active_id']=$this->getActiveId($params['game_id']);
		}
		if($params['time']){
			$filter['valid_start_time|lthan']=$params['start_time'];
			$filter['valid_end_time|than']=$params['start_time'];
		}
		$gift_list=$giftObj->getList('gift_id,gift_name,gift_total,gain_total',$filter);
		foreach($gift_list as $k=>$v){
			if($v['gift_total']>$v['gain_total']){     //总数量和领取数量相同时.
				$data[$v['gift_id']]=$v;
			}
		}
		return $data;
	}
	
	/* 
	* 根据参赛id获取活动id
	* author by wanghaichao
	* date 2017/10/18
	*/
	public function getActiveId($game_id){
		if(empty($game_id)) return;
		$game=app::get('sysactivityvote')->model('game')->getRow('active_id',array('game_id'=>$game_id,'deleted'=>0));
		return $game['active_id'];
	}
}