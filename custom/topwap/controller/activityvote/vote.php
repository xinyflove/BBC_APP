<?php
/* 
 * 投票活动控制器
* author by wanghaichao
* date 2017/10/12
*/
class topwap_ctl_activityvote_vote extends topwap_controller {	
	public $limit=400;
	public function __construct(&$app)
    {
		/*$active_id=input::get('active_id');
		$next_page=url::action('topwap_ctl_activityvote_vote@index',array('active_id'=>$active_id));
        parent::__construct();
        kernel::single('base_session')->start();
        if(!$this->action) $this->action = 'index';
        $this->action_view = $this->action.".html";
        // 检测是否登录
        if( !userAuth::check() )
        {
            if( request::ajax() )
            {
                $url = url::action('topwap_ctl_passport@goLogin',array('next_page'=>$next_page));
                return $this->splash('error', $url, app::get('topwap')->_('请登录'), true);
            }
            redirect::action('topwap_ctl_passport@goLogin',array('next_page'=>$next_page))->send();exit;
        }

        $this->passport = kernel::single('topwap_passport');*/		
        kernel::single('base_session')->start();
        kernel::single("base_session")->set_sess_expires(10080);
        if(!$_SESSION["expert"]){
//            redirect::action('topwap_ctl_activityvote_vote@cat');
        }
	}
	
	/* 
	* 投票活动首页控制器
	* author by wanghaichao
	* date 2017/10/13
	*/
	public function index(){
		unset($_SESSION['active_id']);
		$active_id=input::get('active_id');
		$_SESSION['active_id']=$active_id;
		$active=app::get('sysactivityvote')->model('active')->getRow("*",array('active_id'=>$active_id));
		$active['popular_vote_start_time_date']=date('m月d日',$active['popular_vote_start_time']);
		$active['popular_vote_end_time_date']=date('m月d日',$active['popular_vote_end_time']);
		$active['expert_vote_start_time_date']=date('m月d日',$active['expert_vote_start_time']);
		$active['expert_vote_end_time_date']=date('m月d日',$active['expert_vote_end_time']);
		$pagedata['active']=$active;
		$pagedata['now']=time();
		$pagedata["active_id"] = $active_id;
		//微信分享的
        $shopdata = app::get('topwap')->rpcCall('shop.get',array('shop_id'=>$active['shop_id']));
		$weixin['imgUrl']= base_storager::modifier($shopdata['shop_logo']);
		$weixin['linelink']= url::action("topwap_ctl_activityvote_vote@index",array('active_id'=>$active_id));
		$weixin['shareTitle']=$active['active_name'];//$extsetting['params']['share']['shopcenter_title'];
		$weixin['descContent']=$active['active_name'].'投票活动开始了,快来参加吧';
		$pagedata['weixin']=$weixin;
		//微信分享结束
		/*add_2017/11/22_by_wanghaichao_start*/
		//浏览量
		app::get('topwap')->rpcCall('sysactivityvote.active.view',array('shop_id'=>$active['shop_id'],'active_id'=>$active_id));
		/*add_2017/11/22_by_wanghaichao_end*/
        return $this->page('topwap/activityvote/index.html', $pagedata);
	}

	/* 
	* 投票活动类别控制器
	* author by wanghaichao
	* date 2017/10/13
	*/
	public function cat(){
		$active_id=input::get('active_id');
        /*add_2018/02/26_by_fanglongji_start*/
        //浏览量
        app::get('topwap')->rpcCall('sysactivityvote.active.view',array('active_id'=>$active_id));
        /*add_2017/02/26_by_fanglongji_end*/
		$active=app::get('sysactivityvote')->model('active')->getRow("shop_id,active_name,popular_vote_start_time,popular_vote_end_time,expert_vote_start_time,expert_vote_end_time,active_view",array('active_id'=>$active_id));
		$cat_filter=array('active_id'=>$active_id,'deleted'=>'0','level'=>1);
		$cat=app::get('sysactivityvote')->model('cat')->getList('cat_id,parent_id,level,cat_image,cat_name,order_sort',$cat_filter,0,10,'order_sort asc');
		$pagedata['active']=$active;
		$pagedata['active']['active_id'] = $active_id;
		$pagedata['now']=time();
		$pagedata['cat']=$cat;
		$pagedata['type']=input::get('type');
		$pagedata['popular_time']=date('Y-m-d',$active['popular_vote_start_time']).' — '.date('Y-m-d',$active['popular_vote_end_time']);
		$pagedata['expert_time']=date('Y-m-d',$active['expert_vote_start_time']).' — '.date('Y-m-d',$active['expert_vote_end_time']);
		/*add_2017/11/22_by_wanghaichao_start*/  //取出累计投票和参与选手
		//累计投票
        /*modify_20180222_by_fanglongji_start*/
        /*
		$pagedata['total']=app::get('sysactivityvote')->model('game')->getRow('SUM(total_poll) as total_poll,COUNT(game_id) as total',array('deleted'=>'0','active_id'=>$active_id));
        */
        $pagedata['total']=app::get('sysactivityvote')->model('game')->getRow('SUM(actual_poll+game_poll) as total_poll,COUNT(game_id) as total',array('deleted'=>'0','active_id'=>$active_id));
        /*modify_20180222_by_fanglongji_end*/
		//选出参赛选手
		//print_r($pagedata['total_poll']);die();
		/*add_2017/11/22_by_wanghaichao_end*/
        //微信分享的
        $shopdata = app::get('topwap')->rpcCall('shop.get',array('shop_id'=>$active['shop_id']));
        $weixin['imgUrl']= base_storager::modifier($shopdata['shop_logo']);
        $weixin['linelink']= url::action("topwap_ctl_activityvote_vote@index",array('active_id'=>$active_id));
        $weixin['shareTitle']=$active['active_name'];//$extsetting['params']['share']['shopcenter_title'];
        $weixin['descContent']=$active['active_name'].'投票活动开始了,快来参加吧';
        $pagedata['weixin']=$weixin;
        //微信分享结束
        return $this->page('topwap/activityvote/cat.html', $pagedata);
	}

	/* 列表页方法
	* 菜品或人物等列表页,用于投票的
	* author by wanghaichao
	* date 2017/10/13
	*/
	public function voteList(){
		try{
		$active_id=$_SESSION['active_id'];
            if(empty($active_id))
			{
				$active_id = input::get('active_id');
			}
        $postdata = input::get();
		$parent_id=$postdata['cat_id'];
		$childcat=$this->getChildCat($parent_id);
		$active=app::get('sysactivityvote')->model('active')->getRow("shop_id,active_name,popular_vote_start_time,popular_vote_end_time,expert_vote_start_time,expert_vote_end_time,top_ad_slide",array('active_id'=>$active_id));
		$pagedata=$this->__getVoteList(array('cat_id'=>$childcat[0]['cat_id']));
		$pagedata['childcat']=$childcat;
		$pagedata['login']=userAuth::check()?true:false;
		$url = url::action('topwap_ctl_activityvote_vote@voteList',input::get());  //获取用户的头像信息等
		kernel::single('topwap_passport')->getThirdpartyInfo($url);
		//获取open_id;
		if(kernel::single('topwap_wechat_wechat')->from_weixin()){
			// $payInfo = app::get('topwap')->rpcCall('payment.get.conf', ['app_id' => 'wxpayjsapi']);
			// $wxAppId = $payInfo['setting']['appId'];
			// $wxAppsecret = $payInfo['setting']['Appsecret'];
			$wxAppId = app::get('site')->getConf('site.appId');
			$wxAppsecret = app::get('site')->getConf('site.appSecret');
			if(!input::get('code'))
			{
				$url = url::action('topwap_ctl_activityvote_vote@voteList',$postdata);
				kernel::single('topwap_wechat_wechat')->get_code($wxAppId, $url);
			}
			else
			{
				$code = input::get('code');
				$openid = kernel::single('topwap_wechat_wechat')->get_openid_by_code($wxAppId, $wxAppsecret, $code);
				if($openid == null){
					$this->splash('failed', 'back',  app::get('topwap')->_('获取openid失败'));
				}
				$_SESSION['open_id']=$openid;
			}
		//}else{
		//	$_SESSION['open_id']=1111;
		}
		$pagedata['active']=$active;
		$pagedata['now']=time();
		$pagedata['type']=$postdata['type'];
		/*add_2017/11/22_by_wanghaichao_start*/
		$pagedata['ad_image']=unserialize($active['top_ad_slide']);
		/*add_2017/11/22_by_wanghaichao_end*/

        //微信分享的
        $shopdata = app::get('topwap')->rpcCall('shop.get',array('shop_id'=>$active['shop_id']));
        $weixin['imgUrl']= base_storager::modifier($shopdata['shop_logo']);
        $weixin['linelink']= url::action("topwap_ctl_activityvote_vote@index",array('active_id'=>$active_id));
        $weixin['shareTitle']=$active['active_name'];//$extsetting['params']['share']['shopcenter_title'];
        $weixin['descContent']=$active['active_name'].'投票活动开始了,快来参加吧';
        $pagedata['weixin']=$weixin;
        //微信分享结束
        /*add_2018/02/26_by_fanglongji_start*/
        //浏览量
        app::get('topwap')->rpcCall('sysactivityvote.active.view',array('active_id'=>$active_id));
        /*add_2017/02/26_by_fanglongji_end*/
		return $this->page('topwap/activityvote/vote.html', $pagedata);
		}
		catch (\Exception $exception)
		{
			$pagedata = [];
            return $this->page('topwap/activityvote/vote.html', $pagedata);
		}
	}

	/* __getVoteList()
	* 根据条件获取参赛列表,
	* author by wanghaichao
	* date 2017/10/16
	*/
	private function __getVoteList($postdata){
        $params ['page_no'] = intval($postdata ['pages']) ? intval($postdata ['pages']) : 1;
        $params ['page_size'] = intval($this->limit);
		$params['cat_id']=$postdata['cat_id'];	
		if($postdata['keywords']){
			$params['keywords']=$postdata['keywords'];
		}
		if($postdata['order_by']){
			$params ['order_by'] = $postdata['order_by'];
		}else{
			$params ['order_by'] = 'game_number asc';
		}
        /*modify_20180222_by_fanglongji_start*/
        /*
		$params['fields']='game_id,game_number,game_name,game_profile,game_poll,order_sort,image_default_id,game_desc,is_game,total_poll';
        */
        $params['fields']='game_id,game_number,game_name,game_profile,game_poll,order_sort,image_default_id,game_desc,is_game,total_poll,(actual_poll+game_poll) as show_total_poll';
        /*modify_20180222_by_fanglongji_end*/
        $gamelist = app::get('topwap')->rpcCall('sysactivityvote.get.gamelist', $params);
	
		$pagedata['game']=$gamelist['list'];
		$pagedata['count']=$gamelist['count'];
        $pagedata['pagers'] = $this->__pages($postdata['pages'],$postdata,$count);
		//$pagedata=array();
		return $pagedata;
	}
	
	/* 
	* ajax方式活动参赛投票列表
	* author by wanghaichao
	* date 2017/10/16
	*/
    public function ajaxVoteList()
    {
        try
        {
            $postdata = input::get();
            $pagedata = $this->__getVoteList($postdata);
            $pagedata['type']=$postdata['type'];
			if($pagedata['game']){
                $data ['html'] = view::make('topwap/activityvote/voteList.html', $pagedata)->render();
			}else{
				$data['html'] = view::make('topwap/empty/vote.html', $pagedata)->render();
			}

            $data ['pages'] = $pagedata ['pagers'];
            $data ['s'] = $pagedata ['status'];
            $data ['success'] = true;
        } catch ( Exception $e )
        {
            $msg = $e->getMessage();
            return $this->splash('error', null, $msg, true);
        }
        //var_dump($data['pages']['total']);
        return response::json($data);
        //return view::make('topwap/member/trade/list.html',$pagedata);
    }
	
	/*
	* 根据一级分类获取子分类
	* $parent_cat_id  一级分类id
	* author by wanghaichao
	* date 2017/10/16
	*/
	private function getChildCat($parent_cat_id){
		if(empty($parent_cat_id)) return;
		$cat=app::get('sysactivityvote')->model('cat')->getList('cat_id,cat_name,order_sort',array('parent_id'=>$parent_cat_id,'deleted'=>0),0,10,'order_sort asc');
		return $cat;
	}

    /**
     * 分页处理
     * @param int $current 当前页
     *
     * @return $pagers
     */
    private function __pages($current,$filter,$count)
    {
        //处理翻页数据
        $current = ($current && $current <= 100 ) ? $current : 1;

        if( $count > 0 ) $totalPage = ceil($count/$this->limit);
        $pagers = array(
            'link'=>'',
            'current'=>intval($current),
            'total'=>intval($totalPage),
        );
        return $pagers;
    }
	
	/*
	* 普通大众投票逻辑
	* author by wanghaichao
	* date 2017/10/17
	*/

	public function ajaxGameVote(){
		
		$params=input::get();
		$params['user_id']=userAuth::id();
		$params['open_id']=$_SESSION['open_id'];
		if(empty($params['game_id'])){
			return $this->splash('failed', '',"请选择要支持的参赛作品", true);
		}
		if($params['type']!='popular'){
			return $this->splash('failed', '',"这不是大众投票", true);			
		}
		$result = app::get('topwap')->rpcCall('sysactivityvote.vote.popular', $params);
		if(is_array($result)){
			if($result['type']=='personal_everyday_vote_limit'){
				return $this->splash('failed', '',"您当前分类下投票次数已用完", true);		
			}
			if($result['type']=='game_personal_everyday_vote_limit'){
                /*modify_20180222_by_fanglongji_start*/
                /*
				return $this->splash('failed', '',"该参赛作品每天最多投{$result['limit']}票", true);

                */
                return $this->splash('failed', '',"该参选企业每天最多投{$result['limit']}票", true);
                /*modify_20180222_by_fanglongji_end*/
			}
		}else{
			if($result=='VOTE_SUCCESS'){    //处理获得赠品的逻辑
				$active_id=$_SESSION['active_id'];
				$time=time();
				$today=date('Y-m-d 00:00:00',$time);
				$today_start=strtotime($today);   //今天零点的时间戳
				$today_end=$today_start+24*60*60;   //明天零点的时间戳
				$gift_filter['open_id']=$_SESSION['open_id'];
				$gift_filter['create_time|than']=$today_start;
				$gift_filter['create_time|lthan']=$today_end;
				//$gift_filter['active_id']=$active_id;
				$gift_gain=app::get('sysactivityvote')->model('gift_gain')->getRow('gift_gain_id',$gift_filter);  //一天只能获得一次奖品
				$gift_list= app::get('topwap')->rpcCall('sysactivityvote.gift.list', array('game_id'=>$params['game_id'],'time'=>$time));
				if(empty($gift_list) || !empty($gift_gain)){
					$data=array('status'=>'success','gift'=>'no_gift');
					echo json_encode($data);exit();
				}
				$active=app::get('sysactivityvote')->model('active')->getRow('win_probability',array('active_id'=>$active_id));
				$win_probability=$active['win_probability']?$active['win_probability']:8;   //获得奖品的概率
				$no_win_probability=(100-$win_probability);    //没获得奖品的概率
				$proArr=array(
						'no'=>$no_win_probability,         
						'have'=>$win_probability,
					);
				$pro=$this->getRand($proArr);
				if($pro=='have'){    //此时说明中奖了
					$key=array_rand($gift_list,1);	
					$result=$gift_list[$key];
					if($_SESSION['gift_id']){
						unset($_SESSION['gift_id']);
					}
					$_SESSION['gift_id']=$result['gift_id'];
					$data=array('status'=>'success','gift'=>$result);
					echo json_encode($data);exit();
				}else{
					$data=array('status'=>'success','gift'=>'no_gift');
					echo json_encode($data);exit();
				}
			}else{
				return $this->splash('failed', '',"投票失败,请稍后再试", true);			
			}
		}
	}

    //获取奖项id算法
    public function getRand($proArr){
        $result = "";
        $proSum = array_sum($proArr);
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        return $result;
    }

	
	public function ajaxGetGift(){
		if(!userAuth::check()){
			$mobile=input::get('mobile');
			$vcode=input::get('vcode');
			$sendType='signup';
			$vcodeData=userVcode::verify($vcode,$mobile,$sendType);
			if(!$vcodeData)
			{
				$msg = app::get('topwap')->_('验证码填写错误') ;
				return $this->splash('error', null, $msg, true);
			}
			$check=$this->checkUser($mobile);
			if($check==false){	
				//注册用户
				$userInfo['account']=$mobile;
				$userInfo['password']='123456abc';
				$userInfo['pwd_confirm']='123456abc';
				$userId = userAuth::signUp($userInfo['account'], $userInfo['password'], $userInfo['pwd_confirm']);
				userAuth::login($userId, $userInfo['account']);
			}
			$trust=app::get('sysuser')->model('trustinfo');
			$user_id=userAuth::Id();
			$trustinfo=$trust->getRow('user_id',array('user_id'=>$user_id));
			if(!$trustinfo){
				$trustinsert['user_id']=$user_id;
				$trustinsert['user_flag']=md5($_SESSION['open_id']);
				$trustinsert['flag']='wapweixin';
				$trust->insert($trustinsert);
			}
		}
		$params['user_id']=userAuth::Id();
		$params['open_id']=$_SESSION['open_id'];
		$params['gift_id']=$_SESSION['gift_id'];
	
		unset($_SESSION['gift_id']);
		$result=app::get('topwap')->rpcCall('sysactivityvote.gift.giftGain', $params);
		if($result){
			return $this->splash('success', null, '领取成功!', true);
		}else{
			return $this->splash('error', null, '领取失败!', true);
		}
	}
	//验证手机号是否注册的
	public function checkUser($mobile){
		$user_info=app::get('sysuser')->model('account')->getRow('user_id',array('mobile'=>$mobile));
		if($user_info){	
            userAuth::login($user_info['user_id'], $mobile);
			return true;
		}else{
			return false;
		}
	}

	/*add_2017-11-22_by_xinyufeng_start*/
	/**
	 * @return mixed
	 * @auth xinyufeng
	 * @desc 获取活动规则
	 */
	public function voteRule()
	{
		$input = input::get();
		$active_id = $input['active_id'];
		$rule_res = app::get('sysactivityvote')->model('active')
			->getRow('active_wap_rule', array('active_id'=>$active_id));
		$rule_content = empty($rule_res['active_wap_rule']) ? '' : $rule_res['active_wap_rule'];

		$pagedata['rule_content'] = $rule_content;
		return $this->page('topwap/activityvote/vote_rule.html', $pagedata);
	}

	public function giftRule()
    {
        $input = input::get();
        $active_id = $input['active_id'];
        $gift_res = app::get('sysactivityvote')->model('active')
            ->getRow('gift_wap_rule', array('active_id'=>$active_id));
        $rule_content = empty($gift_res['gift_wap_rule']) ? '' : $gift_res['gift_wap_rule'];
        $pagedata['gift_content'] = $rule_content;
        return $this->page('topwap/activityvote/gift_rule.html', $pagedata);
    }

	/**
     * 投票结果页面
     */
    public function voteResults()
	{
        $input = input::get();
        $active_id = $input['active_id'];
        $shop_id = $input['shop_id'];
        //微信分享的
        $shopdata = app::get('topwap')->rpcCall('shop.get',array('shop_id'=>$shop_id));
        $weixin['imgUrl']= base_storager::modifier($shopdata['shop_logo']);
        $weixin['linelink']= url::action("topwap_ctl_activityvote_vote@voteResults",array('active_id'=>$active_id,'shop_id'=>$shop_id));
        $weixin['shareTitle']="FM91.5首届吃在青岛美食评选奖项名单喜报";//$extsetting['params']['share']['shopcenter_title'];
        $weixin['descContent']= "评选结果出炉,一起来寻找这座城市最美的味道!";
        $pagedata['weixin']=$weixin;
        return $this->page('topwap/activityvote/vote_results.html', $pagedata);
	}
	/**
	 * @auth xinyufeng
	 * @return base_view_object_interface|string
	 * @desc 头部下拉广告详情
	 */
	public function topAdDetail()
	{
		$pagedata = array();
		return $this->page('topwap/activityvote/top_ad_detail.html', $pagedata);
	}

	/**
	 * @auth xinyufeng
	 * @return base_view_object_interface|string
	 * @desc 头部slide广告详情
	 */
	public function topAdSlideDetail()
	{
		$pagedata = array();
		return $this->page('topwap/activityvote/top_ad_slide_detail.html', $pagedata);
	}
	/*add_2017-11-22_by_xinyufeng_end*/
}

?>