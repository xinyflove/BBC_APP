<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class topwap_ctl_lottery extends topwap_controller {

    // lottery_joint_limit:send_limit 得奖数量限制
    // lottery_joint_limit:today_take_count  今天已抽奖次数
    // lottery_joint_limit:take_count  共已抽奖次数
    // lottery_joint_limit:prize_count  奖品被抽中次数
    public function index(){
        $params = array(
            'lottery_id'=>input::get('lottery_id'),
            'used_platform' => [0,2],
        );
        $lotteryInfo = app::get('topwap')->rpcCall('promotion.lottery.get',$params);
        if(!$lotteryInfo){
            return kernel::abort(404);
        }
        $pagedata = $lotteryInfo;
        if($lotteryInfo['lottery_rules']){
            foreach ($lotteryInfo['lottery_rules'] as $key => $value) {
                $lotteryInfo['lottery_rules'][$key]['id'] = $key;
                if(isset($lotteryInfo['lottery_rules'][$key]['rate'])){
                    unset($lotteryInfo['lottery_rules'][$key]['rate']);
                }
            }
            $pagedata['bonusInfo'] = json_encode($lotteryInfo['lottery_rules']);
        }
        setcookie('lottery_link',url::action('topwap_ctl_lottery@index',array('lottery_id'=>input::get('lottery_id'))),time() + 24 * 3600);
        $user_id = userAuth::id();
        $take_limit = null;
        $today_take_limit = null;

        if($user_id){
            // $jointNum = redis::scene('syspromotion')->get('lottery_joint_limit_'.$user_id.'-'.$lotteryInfo['lottery_id']);
            $today = date('Y-m-d');
            // 今天已抽奖次数
            $today_take_count = redis::scene('syspromotion')->get('lottery_joint_limit:today_take_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id . ':' . $today);

            // 已抽奖次数s
            $take_count = redis::scene('syspromotion')->get('lottery_joint_limit:take_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id);

            // 总抽奖次数限制
            if($lotteryInfo['lottery_joint_limit'] > 0 ){
                $take_limit = $lotteryInfo['lottery_joint_limit'] - intval($take_count);
            }

            // 今日奖次数限制
            if($lotteryInfo['lottery_day_limit'] > 0 ){
                $today_take_limit = $lotteryInfo['lottery_day_limit'] - intval($today_take_count);
            }

            if(!is_null($take_limit) && !is_null($today_take_limit) && ($today_take_limit > $take_limit)){
                $today_take_limit = $take_limit;
            }

            $pagedata['take_limit'] = $take_limit;
            $pagedata['today_take_limit'] = $today_take_limit;

        }else{
            $pagedata['take_limit'] = $take_limit;
            $pagedata['today_take_limit'] = $today_take_limit;
            // $pagedata['lottery_joint_limit'] = $lotteryInfo['lottery_joint_limit'];
        }
        // return view::make('topwap/promotion/lottery.html', $pagedata);
        // $this->setLayoutFlag('member');
        if(kernel::single('topwap_wechat_wechat')->from_weixin())
        {
            $url = url::action("topwap_ctl_lottery@index",input::get());
            //微信分享
            $pagedata['signPackage'] = kernel::single('topwap_jssdk')->index($url);
            $weixin['descContent'] = $lotteryInfo['share_desc'];
            $weixin['imgUrl'] = base_storager::modifier($lotteryInfo['share_img']);
            $weixin['linelink'] = $url;
            $weixin['shareTitle'] = $lotteryInfo['share_title'];
            $pagedata['weixin'] = $weixin;
        }
        return $this->page("topwap/promotion/lottery.html",$pagedata);
    }

    // 点击抽奖
    public function getPrize(){

        $data = input::get();
        $user_id = userAuth::id();
        if(!$user_id){
            $msg = app::get('topwap')->_('未登录,请登录！');
            $url = url::action('topwap_ctl_passport@goLogin');
            return $this->splash('error', $url, $msg, true);
        }

        $params = array(
            'lottery_id'=>$data['lottery_id'],
        );
        $lotteryInfo = app::get('topwap')->rpcCall('promotion.lottery.get',$params);
        $today = date('Y-m-d');
        try
        {
            if($lotteryInfo['start_time'] > time()){
                throw new Exception(app::get('topwap')->_('活动未开始！'));
            }elseif($lotteryInfo['end_time'] < time()){
                throw new Exception(app::get('topwap')->_('活动已结束！'));
            }else{
                if($lotteryInfo['status'] == 'stop'){
                    throw new Exception(app::get('topwap')->_('活动已暂停！'));
                }
            }
            if($data['last_modified_time'] != $lotteryInfo['modified_time']){
                throw new Exception(app::get('topwap')->_('活动已变更，请刷新页面！'));
            }

            // redis::scene('syspromotion')->setnx('lottery_joint_limit:take_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id, 0);
            redis::scene('syspromotion')->incr('lottery_joint_limit:take_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id);

            // redis::scene('syspromotion')->setnx('lottery_joint_limit:today_take_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id . ':' . $today, 0);
            redis::scene('syspromotion')->incr('lottery_joint_limit:today_take_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id . ':' . $today);

            $take_limit = null;
            $today_take_limit = null;

            // 共已抽奖次数
            $take_count = redis::scene('syspromotion')->get('lottery_joint_limit:take_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id);

            // 总抽奖次数限制
            if($lotteryInfo['lottery_joint_limit'] > 0 ){
                $take_limit = $lotteryInfo['lottery_joint_limit'] - intval($take_count);
            }

            if(!is_null($take_limit) && $take_limit < 0){
                $take_limit = 0;
                redis::scene('syspromotion')->decr('lottery_joint_limit:take_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id);
                redis::scene('syspromotion')->decr('lottery_joint_limit:today_take_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id . ':' . $today);
                throw new Exception(app::get('topwap')->_('抱歉，您的抽奖次数已用完！'));
            }
            // 今天已抽奖次数
            $today_take_count = redis::scene('syspromotion')->get('lottery_joint_limit:today_take_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id . ':' . $today);

            // 今日奖次数限制
            if($lotteryInfo['lottery_day_limit'] > 0 ){
                $today_take_limit = $lotteryInfo['lottery_day_limit'] - intval($today_take_count);
            }

            if(!is_null($today_take_limit) && $today_take_limit < 0){
                $today_take_limit = 0;
                redis::scene('syspromotion')->decr('lottery_joint_limit:take_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id);
                redis::scene('syspromotion')->decr('lottery_joint_limit:today_take_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id . ':' . $today);
                throw new Exception(app::get('topwap')->_('抱歉，您今天的抽奖次数已用完！'));
            }

            if(!is_null($take_limit) && !is_null($today_take_limit) && ($today_take_limit > $take_limit)){
                $today_take_limit = $take_limit;
            }

        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg, true);
        }

        try
        {
            // 无中奖的key
            $none_key = '';
            $arr_rate = [];
            foreach ($lotteryInfo['lottery_rules'] as $key => $value) {
                $lotteryInfo['lottery_rules'][$key]['id'] = $key;
                $arr_rate[$key] = $value['rate'];
                if($value['bonus_type'] == 'none'){
                    $none_key = $key;
                }
            }
            $prizeNum = $this->getRand($arr_rate);
            $prizeInfo = $lotteryInfo['lottery_rules'][$prizeNum];

            if($prizeInfo['send_limit'] == 'on'){
                $send_limit = redis::scene('syspromotion')->get('lottery_joint_limit:send_limit:' . $lotteryInfo['lottery_id'] . ':' . $user_id);
                if(intval($send_limit) > 0){
                    throw new Exception();
                }
            }

            $prizeInfo['take_limit'] = $take_limit;
            $prizeInfo['today_take_limit'] = $today_take_limit;
            //发放奖励
            $apiData['user_id'] = userAuth::id();
            $apiData['lottery_id'] = $data['lottery_id'];
            $apiData['bonus_type'] = $prizeInfo['bonus_type'];
            $apiData['prizeInfo'] = $lotteryInfo['lottery_rules'][$prizeNum];
            $apiData['lottery_name'] = $lotteryInfo['lottery_name'];
            $apiData['prizeInfo']['lottery_id'] = $data['lottery_id'];
            if($prizeInfo['bonus_type'] != 'none'){
                // 发送奖励
                $result = app::get('topwap')->rpcCall('promotion.bonus.issue',$apiData);
                // $prizeInfo['hongbaomoney'] = $result;
                // 中奖限制计数
                if($prizeInfo['send_limit'] == 'on'){
                    // redis::scene('syspromotion')->setnx('lottery_joint_limit:send_limit:' . $apiData['lottery_id'] . ':' . $user_id, 0);
                    redis::scene('syspromotion')->incr('lottery_joint_limit:send_limit:' . $apiData['lottery_id'] . ':' . $user_id);
                }
            }
        }
        catch(Exception $e)
        {
            $prizeInfo = $lotteryInfo['lottery_rules'][$none_key];
            // $prizeInfo['lottery_joint_limit'] = $lottery_joint_limit;
            $prizeInfo['take_limit'] = $take_limit;
            $prizeInfo['today_take_limit'] = $today_take_limit;
            $prizeInfo['id'] = $none_key;
            // $prizeInfo = $lotteryInfo['lottery_rules'][0];
            // $prizeInfo['id'] = '0';
            // $prizeInfo['lottery_joint_limit'] = $lottery_joint_limit;
            // unset($prizeInfo['rate']);
            // $msg = app::get('topwap')->_('很遗憾，未中奖');
            // $msg = $e->getMessage();
            // $url = url::action('topwap_ctl_lottery@index',array('lottery_id'=>$data['lottery_id']));
            // return $this->splash('success', '', $msg, true);
        }
        // $url = url::action('topwap_ctl_lottery@index',array('lottery_id'=>$data['lottery_id']));
        return $this->splash('success', '', $prizeInfo, true);
    }
    /**
     * 获奖计算   未使用
     *
     * @param [type] $arr
     * @param [type] $lotteryInfo
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function getPrizeInfo($arr,$lotteryInfo){
        $prizeNum = $this->getRand($arr);
        $prizeInfo = $lotteryInfo['lottery_rules'][$prizeNum];
        $prizeInfo['id'] = $prizeNum;
        //发放奖励
        $apiData['user_id'] = userAuth::id();
        $apiData['lottery_id'] = $lotteryInfo['lottery_id'];
        $apiData['bonus_type'] = $prizeInfo['bonus_type'];
        $apiData['prizeInfo'] = $lotteryInfo['lottery_rules'][$prizeNum];
        $apiData['lottery_name'] = $lotteryInfo['lottery_name'];
        $apiData['prizeInfo']['lottery_id'] = $lotteryInfo['lottery_id'];
        try
        {
            if($prizeInfo['bonus_type'] != 'none'){
                // 发送奖励
                $result = app::get('topwap')->rpcCall('promotion.bonus.issue',$apiData);
                $prizeInfo['hongbaomoney'] = $result;
            }
            return $prizeInfo;
        }
        catch(Exception $e)
        {
            // wt($e->getMessage());
            unset($arr[$prizeNum]);
            return $this->getPrizeInfo($arr,$lotteryInfo);
        }
    }

    // 积分兑换抽奖次数
    // 未优化
    public function getExchangeNum(){
        $data = input::get();
        try
        {
            $points = app::get('topwap')->rpcCall('user.point.get',['user_id' => userAuth::id()]);
            if(intval($points['point_count']) < 1){
                throw new Exception(app::get('topwap')->_('积分兑换失败！'));
            }
            $pointData = array(
                'user_id' => userAuth::id(),
                'type' => 'consume',
                'num' => $data['pointNum'],
                'behavior' => '积分兑换抽奖次数',
                'remark' => '积分兑换抽奖次数'
            );
            $result = app::get('topwap')->rpcCall('user.updateUserPoint',$pointData);
            if(!$result){
                throw new Exception(app::get('topwap')->_('积分兑换失败！'));
            }

            $user_id = userAuth::id();
            $jointNum = redis::scene('syspromotion')->get('lottery_joint_limit_'.$user_id.'-'.$data['lotteryid']);
            $lottery_joint_limit = $jointNum + 1;
            redis::scene('syspromotion')->set('lottery_joint_limit_'.$user_id.'-'.$data['lotteryid'],$lottery_joint_limit);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',$url,$msg,true);
        }
        return intval($lottery_joint_limit);
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

    //填写获奖信息弹框
    public function lottery_info_dialog()
    {
        return view::make('topc/promotion/lottery_dialog.html');
    }

    // 分享回调函数
    public function onMenuShareCollback(){

        $data = input::get();
        $user_id = userAuth::id();
        if(!$user_id){
            $msg = app::get('topwap')->_('请登录后分享！');
            $url = url::action('topc_ctl_passport@signin');
            return $this->splash('error', $url, $msg, true);
        }

        $params = array(
            'lottery_id'=>$data['lottery_id'],
        );
        $lotteryInfo = app::get('topwap')->rpcCall('promotion.lottery.get',$params);
        $today = date('Y-m-d');

        $share_count = redis::scene('syspromotion')->get('lottery_joint_limit:share_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id);
        if(intval($share_count) > 0){
            $msg = app::get('topwap')->_('你已获得抽奖机会！');
            // $url = url::action('topc_ctl_passport@signin');
            return $this->splash('error', '', $msg, true);
        }
        // redis::scene('syspromotion')->setnx('lottery_joint_limit:share_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id, 0);
        redis::scene('syspromotion')->incr('lottery_joint_limit:share_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id);

        $take_limit = null;
        $today_take_limit = null;

        // redis::scene('syspromotion')->setnx('lottery_joint_limit:take_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id, 0);
        redis::scene('syspromotion')->decr('lottery_joint_limit:take_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id);

        // redis::scene('syspromotion')->setnx('lottery_joint_limit:today_take_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id . ':' . $today, 0);
        redis::scene('syspromotion')->decr('lottery_joint_limit:today_take_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id . ':' . $today);

        // 共已抽奖次数
        $take_count = redis::scene('syspromotion')->get('lottery_joint_limit:take_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id);

        // 总抽奖次数限制
        if($lotteryInfo['lottery_joint_limit'] > 0 ){
            $take_limit = $lotteryInfo['lottery_joint_limit'] - intval($take_count);
        }

        // 今天已抽奖次数
        $today_take_count = redis::scene('syspromotion')->get('lottery_joint_limit:today_take_count:' . $lotteryInfo['lottery_id'] . ':' . $user_id . ':' . $today);

        // 今日奖次数限制
        if($lotteryInfo['lottery_day_limit'] > 0 ){
            $today_take_limit = $lotteryInfo['lottery_day_limit'] - intval($today_take_count);
        }
        $prizeInfo['take_limit'] = $take_limit;
        $prizeInfo['today_take_limit'] = $today_take_limit;
        $prizeInfo['message'] = '恭喜你，获得新的抽奖机会！';

        return $this->splash('success', '', $prizeInfo, true);
    }
}
