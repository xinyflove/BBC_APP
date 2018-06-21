<?php

/**
 * 投票详情控制器
 * add_start_gurundong_2017_10_13
 * Class topwap_ctl_activityvote_votedetail
 */
class topwap_ctl_activityvote_votedetail extends topwap_ctl_activityvote_vote{

    /**
     * @var int 默认分页数
     */
    protected $page_size = 10;

    public function detail(){
		/*add_2017/12/11_by_wanghaichao_start*/
		$url = url::action('topwap_ctl_activityvote_votedetail@detail',input::get());  //获取用户的头像信息等
		kernel::single('topwap_passport')->getThirdpartyInfo($url);
		if(kernel::single('topwap_wechat_wechat')->from_weixin() && !$_SESSION['open_id']){
			// $payInfo = app::get('topwap')->rpcCall('payment.get.conf', ['app_id' => 'wxpayjsapi']);
			// $wxAppId = $payInfo['setting']['appId'];
			// $wxAppsecret = $payInfo['setting']['Appsecret'];
			$wxAppId = app::get('site')->getConf('site.appId');
			$wxAppsecret = app::get('site')->getConf('site.appSecret');
			if(!input::get('code'))
			{
				$url = url::action('topwap_ctl_activityvote_votedetail@detail',$postdata);
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
		/*add_2017/12/11_by_wanghaichao_end*/
        $game_id = input::get("game_id");
        $type = input::get("type");
        $page = input::get("page");
        $page = 1;
        $page_size = 10;
        $where = [
            "deleted"=>0,
            "game_id"=>$game_id
        ];
        /*modify_20180222_by_fanglongji_start*/
        /*
        $game_info = app::get("sysactivityvote")->model("game")->getRow("shop_id,active_id,type_id,game_name,game_profile,total_poll,game_number,image_default_id,game_poll,list_image",$where);
        */
        $game_info = app::get("sysactivityvote")->model("game")->getRow("shop_id,active_id,type_id,game_name,game_profile,total_poll,game_number,image_default_id,game_poll,list_image,(actual_poll+game_poll) as show_total_poll",$where);
        /*modify_20180222_by_fanglongji_end*/
        $active_info = app::get('sysactivityvote')->model('active')->getRow('popular_vote_start_time,popular_vote_end_time,expert_vote_start_time,expert_vote_end_time',['active_id'=>$game_info['active_id']]);
//        $game_list_data = app::get("sysactivityvote")->model("game")->getRow("game_number,game_name,game_profile,game_poll,image_default_id,list_image",$where);
        $game_data = app::get("sysactivityvote")->model("game_detail")->getRow("game_id,type_id,base_desc,work_desc,base_list_image,work_list_image,recommend_reason_desc",$where);
        $view_path = $this->detailCategory($game_info["type_id"]);
        //获取参赛详情表数据
        $pagedata["game_id"] = $game_id;
        if($game_data){
            $pagedata = $this->unseri($game_data);
        }
        if(!empty($game_info['list_image'])){
            $game_info['list_image'] = explode(",",$game_info["list_image"]);
        }
        //获取参赛列表中的基础数据
        $pagedata['list_data'] = $game_info;
        //获取当前类型，专家或者评论者
        $pagedata['type'] = $type;
        //获取参赛名称
        $pagedata['game_name'] = $game_info['game_name'];
        //获取票数
        /*modify_20180222_by_fanglongji_start*/
        /*
        $pagedata['vote_count'] = $game_info['total_poll'];
        */
        $pagedata['vote_count'] = $game_info['show_total_poll'];
        /*modify_20180222_by_fanglongji_end*/
        //获取参赛编号
        $pagedata['vote_number'] = (string)$game_info['game_number'];
        //获取专家评论列表
        $data = [
            "active_id"=>$game_info["active_id"],
            "shop_id"=>$game_info["shop_id"],
            "game_id"=>$game_id,
            "page"=>[
                "page_no"=>$page,
                "page_size"=>$this->page_size
            ]
        ];
        $pagedata['login']=userAuth::check()?true:false;
        $comment_list = $this->getCommentList($data);
        $pagedata["comment_list"] = $comment_list;
        $pagedata["comment_page_count"] = (int)ceil((int)$comment_list["count"]/(int)$this->page_size);
        $pagedata["comment_page_size"] = $this->page_size;
        //微信分享数据
        $weixin['imgUrl']= base_storager::modifier($game_info['image_default_id']);
        $weixin['linelink']= url::action("topwap_ctl_activityvote_votedetail@detail",array('type'=>$type,'game_id'=>$game_id));
        $weixin['shareTitle']=$game_info['game_name'];//$extsetting['params']['share']['shopcenter_title'];
        $weixin['descContent']=$game_info['game_profile'];
        $pagedata['weixin']=$weixin;
        //专家情况下的特殊数据
        if($type == "expert"){
            //获取是否已经评论
            $confirm = $this->postLimit(["game_id"=>$game_id,"expert_id"=>$_SESSION["expert"]["expert_id"]]);
            $pagedata["confirm"] = $confirm;
            //是否在专家评审时间内
            if(((time()-$active_info['expert_vote_start_time'])>0) && (($active_info['expert_vote_end_time']-time())>0)){
                $pagedata['active_expire']['expert_active'] = true;
            }else{
                $pagedata['active_expire']['expert_active'] = false;
            }
        }
        //大众情况下的特殊数据
        if($type == 'popular'){
            //是否在大众评论时间内
            if(((time()-$active_info['popular_vote_start_time'])>0) && (($active_info['popular_vote_end_time']-time()>0))){
                $pagedata['active_expire']['popular_active'] = true;
            }else{
                $pagedata['active_expire']['popular_active'] = false;
            }
        }
        /*add_2018/02/26_by_fanglongji_start*/
        //浏览量
        app::get('topwap')->rpcCall('sysactivityvote.active.view',array('active_id'=>$game_info['active_id']));
        /*add_2017/02/26_by_fanglongji_end*/
        return $this->page($view_path,$pagedata);
    }

    /**
     * 解序列化
     * 数据库取出的待解序列化数据
     * add_gurundong_20171016
     * @param $data 返回解序列化之后的数据
     */
    private function unseri($data){
        if(array_key_exists("base_desc",$data)){
            if(!empty($data["base_desc"])){
                $data["base_desc"] = unserialize($data["base_desc"]);
            }
            if(!empty($data["work_desc"])){
                $data["work_desc"] = unserialize($data["work_desc"]);
            }
            if(!empty($data["base_list_image"])){
                $data["base_list_image"] = unserialize($data["base_list_image"]);
            }
            if(!empty($data["work_list_image"])){
                $data["work_list_image"] = unserialize($data["work_list_image"]);
            }
            if(!empty($data["recommend_reason_desc"])){
                $data["recommend_reason_desc"] = unserialize($data["recommend_reason_desc"]);
            }
        }
        return $data;
    }

    /**
     * 投票详细类型控制器
     * 不同类型的详情略有不同
     * add_start_gurundong_20171016
     * @param $type_id  类型id
     * @param $active_id  活动id
     * @return $view_path 返回视图路径
     */
    public function detailCategory($type_id){
        switch ($type_id){
            case 1:
                $view_path = "topwap/activityvote/foodClass_details.html";
                break;
            case 2:
                $view_path = "topwap/activityvote/restaurantClass_details.html";
                break;
            case 3:
                $view_path = "topwap/activityvote/personClass_details.html";
                break;
            default:
                $view_path = "topwap/activityvote/restaurantClass_details.html";
        }
        return $view_path;
    }

    /**
     * 验证活动专家投票是否开启
     */
    public function validateTime(){
        $active_id = input::get("active_id");
        $active_info = app::get("sysactivityvote")->model("active")->getRow("expert_vote_start_time,expert_vote_end_time",["active_id"=>$active_id]);
        $now = time();
        if(($now>=(int)$active_info["expert_vote_start_time"])&&($now<=(int)$active_info["expert_vote_end_time"])){
            return $this->splash("success",null,null,true);
        }else{
            if($now<(int)$active_info['expert_vote_start_time'])
            {
                return $this->splash("error_not_start",null,null,true);
            }elseif($now>(int)$active_info['expert_vote_end_time'])
            {
                return $this->splash("error_end",null,null,true);
            }else{
            return $this->splash("error",null,null,true);
            }
        }
    }

    /**
     * 验证专家密码投票
     * 需传入 comment_password    active_id
     * 返回ajax,json数据
     */
    public function validateExpert(){
        $comment_password = input::get("comment_password");
        $active_id = input::get("active_id");
        $active_info = app::get("sysactivityvote")->model("active")->getRow("shop_id,expert_vote_start_time,expert_vote_end_time",["active_id"=>$active_id]);
        $params = [
            "active_id"=>$active_id,
            "shop_id"=>$active_info['shop_id'],
            "comment_password"=>$comment_password
        ];
        try{
            $res = app::get("topwap")->rpccall("sysactivityvote.expert.validate",$params);
        }catch (Exception $e){
            $msg = $e->getMessage();
            return $this->splash("error",null,"密码必须为4位",true);
        }
        if($res['is_valid'] === true){
            $params["expert_id"] = $res["expert_id"];
            $_SESSION["expert"] = $params;
            $now = time();
            $message = [
                "msg"=>"密码验证成功",
            ];
            if(($now>=(int)$active_info["expert_vote_start_time"])&&($now<=(int)$active_info["expert_vote_end_time"])){
                $message["active_is"] = true;
            }else{
                $message["active_is"] = false;
            }
            return $this->splash("success",null,$message,true);
        }else{
            return $this->splash("error",null,"密码验证失败",true);
        }
    }

    /**
     * 专家评论提交
     * 需传入 game_id comment_password comment_content
     * 返回ajax,json数据
     */
    public function postComment(){
        $game_id = input::get("game_id");
        $password = $_SESSION["expert"]["comment_password"];
        $content = input::get("comment_content");
        $params = array();
        $params["game_id"] = $game_id;
        $params["comment_content"] = $content;

        $params_ps = [
            "active_id"=>$_SESSION["expert"]["active_id"],
            "shop_id"=>$_SESSION["expert"]["shop_id"],
            "comment_password"=>$password
        ];
        $expert_id = $_SESSION["expert"]["expert_id"];
        $params["expert_id"] = $expert_id;
        try{
            //添加评论接口
            $insert_id = app::get("topwap")->rpccall("sysactivityvote.expert.save",$params);
            $comment_detail = app::get("sysactivityvote")->model("expert_comment")->getRow("comment_content,modified_time",["expert_comment_id"=>$insert_id]);
            $expert_detail = app::get("topwap")->rpccall("activity.vote.expert.get",[
                "active_id"=>$_SESSION["expert"]["active_id"],
                "shop_id"=>$_SESSION["expert"]["shop_id"],
                "expert_id"=>$expert_id
            ]);
            $return = array();
            $return["comment"] = [
                "expert_name"=>$expert_detail["expert_name"],
                "expert_avatar"=>$expert_detail["expert_avatar"],
                "comment_content"=>$comment_detail["comment_content"],
                "modified_time"=>date("Y-m-d",$comment_detail["modified_time"])
            ];
        }catch (Exception $e){
            $return = array();
            $msg = $e->getMessage();
            $return["msg"] = $msg;
            return $this->splash("error",null,$return,true);
        }
        $return["msg"] = "评论成功";
        return $this->splash("success",null,$return,true);
    }

    /**
     * 专家评论限制，用来判断该参赛id是否已经评论过
     * @param data array 需包含game_id,expert_id,
     */
    public function postLimit($data){
        $comment_confirm = app::get("sysactivityvote")->model("expert_comment")->getRow("expert_comment_id",["game_id"=>$data["game_id"],"expert_id"=>$data["expert_id"]]);
        if($comment_confirm){
            return true;
        }
        return false;
    }

    /**
     * 获取专家评论列表
     */
    public function getCommentList($data){
        try{
            $params = [];
            //活动id
            if(isset($data["active_id"])){
                $params["active_id"] = $data["active_id"];
            }
            //商户id
            if(isset($data["shop_id"])){
                $params["shop_id"] = $data["shop_id"];
            }
            //参赛id
            if(isset($data["game_id"])){
                $params["game_id"] = $data["game_id"];
            }
            //分页-当前页
            if(isset($data["page"]["page_no"])){
                $params["page_no"] = $data["page"]["page_no"];
            }
            //分页-limit
            if(isset($data["page"]["page_size"])){
                $params["page_size"] = $data["page"]["page_size"];
            }
            $list = app::get("topwap")->rpccall("activity.vote.expert.comment.list",$params);
        }catch (Exception $e){
            $msg = $e->getMessage();
            return false;
        }
        return $list;
    }

    public function ajaxCommentList(){
        $page = input::get("page");
        $game_id = input::get("game_id");
        $game_info = app::get("sysactivityvote")->model("game")->getRow("shop_id,active_id",["game_id"=>$game_id]);
        $data = [
            "active_id"=>$game_info["active_id"],
            "shop_id"=>$game_info["shop_id"],
            "game_id"=>$game_id,
            "page"=>[
                "page_no"=>$page,
                "page_size"=>$this->page_size
            ]
        ];
        $comment_list = $this->getCommentList($data);
        if($comment_list["data"]){
            foreach ($comment_list["data"] as &$v){
                $v["modified_time"] = date("Y-m-d",$v["modified_time"]);
            }
        }
        return $this->splash("success",null,$comment_list,true);
    }
}

