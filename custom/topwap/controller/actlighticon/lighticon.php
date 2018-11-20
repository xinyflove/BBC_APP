<?php
/*
* 964点亮图标的控制器
* author by wanghaichao
* date 2017/12/21
*/
class topwap_ctl_actlighticon_lighticon extends topwap_controller {
    public function __construct(&$app)
    {
        kernel::single('base_session')->start();
        kernel::single("base_session")->set_sess_expires(10080);
    }
    /* action_name (par1, par2, par3)
    * 活动首页
    * author by wanghaichao
    * date 2017/12/21
    */
    public function index(){
        $activity_id=input::get('activity_id');
        $url = url::action('topwap_ctl_actlighticon_lighticon@index',input::get());  //获取用户的头像信息等
        kernel::single('topwap_passport')->getThirdpartyInfo($url);
        //获取openid;
//		if(kernel::single('topwap_wechat_wechat')->from_weixin()){
		if(!$_SESSION['openid']){
			$wxAppId = app::get('site')->getConf('site.appId');
			$wxAppsecret = app::get('site')->getConf('site.appSecret');
			if(!input::get('code'))
			{
				kernel::single('topwap_wechat_wechat')->get_code($wxAppId, $url);
			}
			else
			{
				$code = input::get('code');
				$openid = kernel::single('topwap_wechat_wechat')->get_openid_by_code($wxAppId, $wxAppsecret, $code);
				if($openid == null){
					$this->splash('failed', 'back',  app::get('topwap')->_('获取openid失败'));
				}
				$_SESSION['openid']=$openid;
			}
		}
//		}else{
//			$_SESSION['openid']=123456;
//		}
        $openid=$_SESSION['openid'];
        //根据openid
        $params['activity_id']=$activity_id;
        $pagedata['info']=app::get('topwap')->rpcCall('actlighticon.activity.get',$params);
        if(($pagedata['info']['activity_end_time']) < time()) {
            exit('活动已结束！');
        }
        //获取奖品总数和奖品剩余数
        $gift=app::get('actlighticon')->model('gift')->getRow('SUM(`gift_total`) AS gift_total, SUM(`gain_total`) AS gain_total',array('activity_id'=>$activity_id,'status'=>'0'));
        $pagedata['gift_total']=$gift['gift_total']+$gift['gain_total'];
        $pagedata['sy_gift_total']=$gift['gift_total']?$gift['gift_total']:0;
        //判断用护是否 参与活动了
        $filter['openid']=$openid;
        $filter['activity_id']=$activity_id;
        $pagedata['join']=app::get('topwap')->rpcCall('actlighticon.participant.judge',$filter);
        $pagedata['time']=time();
        //浏览量
        app::get('topwap')->rpcCall('actlighticon.activity.view',array('activity_id'=>$params['activity_id']));

        $shopinfo = app::get('topwap')->rpcCall('shop.get',array('shop_id'=>$pagedata['info']['shop_id']));
		//微信分享
        $weixin['imgUrl']= base_storager::modifier($shopinfo['shop_logo']);
        $weixin['linelink']= action('topwap_ctl_actlighticon_lighticon@index',array('activity_id'=>$activity_id));
        $weixin['shareTitle']=$pagedata['info']['activity_name'];//$extsetting['params']['share']['shopcenter_title'];
        $weixin['descContent']=$pagedata['info']['activity_profile'];
		$pagedata['weixin']=$weixin;
        return $this->page('topwap/actlighticon/index.html', $pagedata);
    }

    /* action_name (par1, par2, par3)
    * 填入信息的页面
    * author by wanghaichao
    * date 2017/12/21
    */
    public function info(){
        $pagedata['openid']=$_SESSION['openid'];
        $pagedata['activity_id']=input::get('activity_id');
        return $this->page('topwap/actlighticon/info.html',$pagedata);
    }
    /* action_name (par1, par2, par3)
    * 用于保存参与者信息
    * author by wanghaichao
    * date 2017/12/22
    */
    public function savePart(){
        $params=input::get();
        if(empty($params['username']) || empty($params['mobile'])  || empty($params['addr'])){
            return $this->splash('failed', '',"为确保邮寄准确,请您填入完整信息", true);
        }

        $validator = validator::make ([ 'mobile' => $params['mobile'] ], [ 'mobile' => 'mobile' ], [ 'mobile' => '手机格式不正确' ]);
        if ($validator->fails ())
        {
            return $this->splash('failed', '',"手机格式不正确", true);

        }

        $activity=app::get('actlighticon')->model('activity')->getRow('shop_id',array('activity_id'=>$params['activity_id']));
        $params['shop_id']=$activity['shop_id'];
        $res=app::get('topwap')->rpcCall('actlighticon.participant.save',$params);
        if($res['status']=='HAS_OPENID'){
            return $this->splash('failed', '',"您已参与该活动!", true);
        }
        if($res['status']=='HAS_MOBILE'){
            return $this->splash('failed', '',"该手机号已参与活动!", true);
        }
        if($res['status']=='NO_OPERAND'){
            return $this->splash('failed', '',"还没有主持人,请稍后参与!", true);
        }
        if ($res['status']=='SUCCESS'){
            $url= url::action('topwap_ctl_actlighticon_lighticon@lightUp',array('activity_id'=>$params['activity_id'],'shop_id'=>$activity['shop_id'],'participant_id'=>$res['participant_id']));
            return $this->splash('success',$url,"参加成功!", true);
        }
    }

    /**
     * 点亮头像页面
     * @url /wap/lighticon-light-up.html
     */
    public function lightUp()
    {
        /**
         * @var array $pagedata 页面数据
         * @var array $params actlighticon.operand.list的api参数
         * @var array $params_get actlighticon.lightlog.get的api参数
         * @var array $operandList 所有主持人列表
         * @var array $lightlog 投票记录
         * @var array $tmp_operand_ids 临时数组，代表Log中已投票的主持人
         * @var array $weixin 微信分享
         * @var integer $participant_id 参与人id
         * @var integer $activity_id 活动id
         * @var integer $shop_id 店铺id
         * @var integer $openid 获取当前openid
         * @var topwap_actlighticon_lightUp $lightUp topwap_actlighticon_lightUp实例
         */
        $pagedata = array();
        //获取active_id;shop_id;participant_id;openid
        $participant_id = input::get('participant_id');
        $activity_id = input::get('activity_id');
        $shop_id = input::get('shop_id');
        $lightUp = kernel::single(topwap_actlighticon_lightUp::class);
        // TODO
        //此openid用来判断是不是本人
		app::get('topwap')->rpcCall('actlighticon.activity.view',array('activity_id'=>$activity_id));

        $url = url::action('topwap_ctl_actlighticon_lighticon@lightUp',input::get());  //获取用户的头像信息等
        kernel::single('topwap_passport')->getThirdpartyInfo($url);
        //获取openid;
        $wxAppId = app::get('site')->getConf('site.appId');
        $wxAppsecret = app::get('site')->getConf('site.appSecret');
        if(!input::get('code'))
        {
            kernel::single('topwap_wechat_wechat')->get_code($wxAppId, $url);
        }
        else
        {
            if(!$_SESSION['actlighticon']['openid']){
                $code = input::get('code');
                $openid = kernel::single('topwap_wechat_wechat')->get_openid_by_code($wxAppId, $wxAppsecret, $code);
                if($openid == null){
                    $this->splash('failed', 'back',  app::get('topwap')->_('获取openid失败'));
                }
                $_SESSION['actlighticon']['openid'] = $openid;
            }else{
                $openid = $_SESSION['actlighticon']['openid'];
            }
        }
//        $openid = 123456;

        //判断participant_id在数据库中还是否存在
        $if_participant_id = app::get('actlighticon')->model('participant')->getRow('participant_id',['participant_id'=>$participant_id]);
        if(!$if_participant_id)
        {
            return redirect::action('topwap_ctl_actlighticon_lighticon@index',[
                'activity_id'=>$activity_id,
            ]);
        }


        //主持人列表，并更新状态(是否已经点亮了)
        $params = array(
            'fields'=>'*',
            'activity_id'=>$activity_id,
            'shop_id'=>$shop_id,
            'participant_id'=>input::get('participant_id'),
            'status'=>0,
            'page_size'=>15
        );
        $params_get = array(
            'fields'=>'*',
            'activity_id'=>$activity_id,
            'shop_id'=>$shop_id,
            'participant_id'=>$participant_id
        );
        $operandList = app::get('actlighticon')->rpcCall("actlighticon.operand.list",$params);
        if(!$operandList['data'])
        {
            return $this->page('topwap/actlighticon/light-up.html',$pagedata);
        }
        $lightlog = app::get('actlighticon')->rpcCall('actlighticon.lightlog.get',$params_get);
        $tmp_operand_ids = array_column($lightlog['data'],'operand_id');
        array_walk($operandList['data'],function (&$value,$key) use ($tmp_operand_ids){
            if(in_array($value['operand_id'],$tmp_operand_ids))
            {
                $value['is_light'] = true;
            }else{
                $value['is_light'] = false;
            }
        });


        //判断是否为发起人,并且为自己发起的
        $filter_judge['openid']=$openid;
        $filter_judge['activity_id']=$activity_id;
        $filter_judge['participant_id']=$participant_id;
//        $pagedata['join']=app::get('topwap')->rpcCall('actlighticon.participant.judge',$filter_judge);
        $pagedata['join']=$lightUp->checkJoin($filter_judge);


        //判断是否全部点亮,并且为发起人
        if((int)$operandList['count'] <= (int)$lightlog['total'] && $pagedata['join']['join'] === 'HAS_JOIN')
        {
            return redirect::action('topwap_ctl_actlighticon_lighticon@congratulations',[
                'participant_id'=>$participant_id,
                'activity_id'=>$activity_id,
                'shop_id'=>$shop_id
            ]);
        }


        //微信分享的
        $shopinfo = app::get('topwap')->rpcCall('shop.get',array('shop_id'=>$shop_id));
        $activityInfo = app::get('actlighticon')->rpcCall('actlighticon.activity.get',['activity_id'=>$activity_id]);
        $weixin['imgUrl']= base_storager::modifier($shopinfo['shop_logo']);
        $weixin['linelink']= action('topwap_ctl_actlighticon_lighticon@lightUp',[
            'participant_id'=>$participant_id,
            'activity_id'=>$activity_id,
            'shop_id'=>$shop_id
        ]);
        $weixin['shareTitle']=$activityInfo['activity_name'];
        $weixin['descContent']=$activityInfo['activity_profile'];


        //pagedata 赋值
        $pagedata['operandList'] = $operandList;
        $pagedata['activity_id'] = $activity_id;
        $pagedata['shop_id'] = $shop_id;
        $pagedata['participant_id'] = $participant_id;
        $pagedata['openid'] = $openid;
        $pagedata['weixin']=$weixin;
        return $this->page('topwap/actlighticon/light-up.html',$pagedata);
    }

    /**
     * 点亮头像操作
     */
    public function lightUpDo()
    {
        /**
         * @var integer $operand_id 待点亮主持人id
         * @var integer $activity_id 活动id
         * @var integer $shop_id shop_id
         * @var integer $participant_id 发起人id
         * @var string $time_limit 活动时间限制
         * @var topwap_actlighticon_lightUp $lightUp lightUp服务库
         */
        $operand_id = input::get('operand_id');
        $activity_id = input::get('activity_id');
        $shop_id = input::get('shop_id');
        $participant_id = input::get('participant_id');
        $openid = input::get('openid');
        $lightUp = kernel::single(topwap_actlighticon_lightUp::class);


        //限制活动结束就不能点亮了
        $time_limit = $lightUp->checkActivityEnd($activity_id);
        if($time_limit !== 'TIME_TRUE')
        {
            if($time_limit === 'TIME_LEFT')
            {
                return $this->splash('exception','','活动未开始~');
            }
            if($time_limit === 'TIME_RIGHT')
            {
                return $this->splash('exception','','活动结束啦~');
            }
        }

        //制每个人只能为一个连接点亮一个头像
        try{
            $lightUp->limitPost([
                'activity_id'=>$activity_id,
                'shop_id'=>$shop_id,
                'participant_id'=>$participant_id,
                'openid'=>$openid
            ]);
        }catch (\LogicException $exception)
        {
            return $this->splash('exception','',$exception->getMessage());
        }


        //获取点亮主持人信息
        $operand_data = app::get('actlighticon')->rpcCall('actlighticon.operand.get',['operand_id'=>$operand_id]);

        //点亮操作
        try{
            //投票执行
            $params = array(
                'operand_id'=>$operand_id,
                'activity_id'=>$activity_id,
                'shop_id'=>$shop_id,
                'participant_id'=>$participant_id,
                'openid'=>$openid
            );
            app::get('actlighticon')->rpcCall('actlighticon.lightlog.post',$params);
            //获取奖品操作（若果为最后一张，则获取奖品）
            if($lightUp->is_end($params))
            {
                try{
                    $gift_data = $lightUp->getGift($params);
                }catch (\Exception $exception)
                {
                    return $this->splash('error',null,$exception->getMessage());
                }
            }
        }catch (\Exception $exception){
            return $this->splash('error',null,['msg'=>$exception->getMessage(),'data'=>$operand_data]);
        }
        return $this->splash('success',null,['msg'=>'点亮成功!','data'=>$operand_data]);
    }

    /**
     * 全部点亮后的页面
     */
    public function congratulations()
    {
        //获取active_id;shop_id;participant_id;openid
        $participant_id = input::get('participant_id');
        $activity_id = input::get('activity_id');
        $shop_id = input::get('shop_id');
        // TODO
        //此openid用来判断是不是发起人
        $wxAppId = app::get('site')->getConf('site.appId');
        $wxAppsecret = app::get('site')->getConf('site.appSecret');
        if(!input::get('code'))
        {
            kernel::single('topwap_wechat_wechat')->get_code($wxAppId, action('topwap_ctl_actlighticon_lighticon@congratulations',[
                'participant_id'=>$participant_id,
                'activity_id'=>$activity_id,
                'shop_id'=>$shop_id
            ]));
        }
        else
        {
            if(!$_SESSION['actlighticon']['openid']){
                $code = input::get('code');
                $openid = kernel::single('topwap_wechat_wechat')->get_openid_by_code($wxAppId, $wxAppsecret, $code);
                if($openid == null){
                    $this->splash('failed', 'back',  app::get('topwap')->_('获取openid失败'));
                }
                $_SESSION['actlighticon']['openid'] = $openid;
            }else{
                $openid = $_SESSION['actlighticon']['openid'];
            }
        }
//        $openid = 1234567;


        //获取奖品信息
        $lightUp = kernel::single(topwap_actlighticon_lightUp::class);
        $giftInfo = $lightUp->getGiftInfo(null,$participant_id);

        $pagedata = array();
        $pagedata['activity_id'] = $activity_id;
        $pagedata['giftInfo'] = $giftInfo;
        return $this->page('topwap/actlighticon/congratulations.html',$pagedata);
    }

    /**
     * 获取图片奖品一张
     */
    public function imgGift(){
        $imgUrl = input::get('img');
        $pagedata['img'] = $imgUrl;
        return $this->page('topwap/actlighticon/imgGift.html',$pagedata);
    }

}
