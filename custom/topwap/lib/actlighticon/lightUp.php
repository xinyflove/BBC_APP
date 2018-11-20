<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2017/12/22
 * Time: 15:56
 */

/**
 * 点亮图像相关service
 * Class topwap_actlighticon_lightUp
 */
class topwap_actlighticon_lightUp
{
    /**
     * @param $activity_id
     */
    public function checkActivityEnd($activity_id)
    {
        $activityInfo = app::get('actlighticon')->model('activity')->getRow('*',['activity_id'=>$activity_id]);
        $time = time();
        if($activityInfo['activity_start_time'] > $time)
        {
            return 'TIME_LEFT';
        }elseif($time > $activityInfo['activity_end_time'])
        {
            return 'TIME_RIGHT';
        }else
        {
            return 'TIME_TRUE';
        }
//        if($activityInfo['activity_start_time']<= $time && $time <=$activityInfo['activity_end_time'])
//        {
//            return true;
//        }else{
//            return false;
//        }
    }
    /**
     * 判断是发起人，并且是这个分享的总发起人
     * @param $params
     * @return array
     */
    public function checkJoin($params)
    {
        $filter['openid']=$params['openid'];
        $filter['activity_id']=$params['activity_id'];
        $filter['participant_id']=$params['participant_id'];
        $filter['status']=0;
        $res=app::get("actlighticon")->model('participant')->getRow('participant_id',$filter);
        if($res){
            return array('join'=>"HAS_JOIN",'participant_id'=>$res['participant_id']);
        }else{
            return array('join'=>"NO_JOIN");
        }
    }
    /**
     * 限制每个用户只能给同一发起者点亮一次头像
     * @param array $params 传入参数
     */
    public function limitPost($params)
    {
        $lightlog = app::get('actlighticon')->model('lightlog');
        $row = $lightlog->getRow('*',$params);
        if($row)
        {
            throw new \LogicException('您已点亮过头像啦~');
        }
    }

    /**
     * 获取奖品
     * @param array $params 传入参数
     */
    public function getGift($params)
    {
        /**
         * @var array $filter 接口过滤条件
         * @var array $giftList 奖品列表
         * @var array $data 奖品待抽奖数据
         * @var array $gift_data 奖品信息确认
         * @var array $gift_data_true 用户获取的真实奖品
         * @var integer $gift_id 初次随即奖品id
         * @var integer $gift_id_two 大礼包另完后，图标奖品id
         * @var integer $gift_id_true 真正领取的礼品id
         * @var array $params_updata 礼品数据更新接口筛选条件
         * @var array $participant_update 更新用户信息数组
         * @var Doctrine\DBAL\Connection $db 数据库抽象层
         * @var dbeav_model $mdlGift 礼物表模型
         * @var dbeav_model $mdlParticipant 发起人表模型
         */
        $filter = array(
            'shop_id'=>$params['shop_id'],
            'activity_id'=>$params['activity_id'],
            'status'=>0
        );
        $giftList = app::get('actlighticon')->rpcCall('actlighticon.gift.list',$filter);
        //如果尚未添加奖品，返回null
        if(!$giftList['data'])
        {
            return null;
        }

        //获取大礼包逻辑
        $data = array();
        foreach ($giftList['data'] as $value)
        {
            $data[$value['gift_id']] = $value['percentage'];
        }
        //随即获取奖品
        $gift_id_true = null;
        $gift_id = $this->getRand($data);
        $gift_id_true = $gift_id;
        $gift_data = app::get('actlighticon')->rpcCall('actlighticon.gift.getinfo',['gift_id'=>$gift_id]);
        //如果为大礼包
        if($gift_data['need_deliver'] == 1)
        {
            //如果大礼包还有库存，发放大礼包奖品
            if($gift_data['gift_total'] > 0)
            {
                $gift_data_true = $gift_data;
            }
            //发放图片奖品
            else{
                unset($data[$gift_id]);
                $gift_id_two = $this->getRand($data);
                $gift_id_true = $gift_id_two;
                $gift_data_true = app::get('actlighticon')->rpcCall('actlighticon.gift.getinfo',['gift_id'=>$gift_id_two]);;
            }
        }
        //发放图片奖品
        else{
            $gift_data_true = $gift_data;
        }
        //用户大礼包数据写入，大礼包减库存操作
        $gift_data_true['gift_total']--;
        $gift_data_true['gain_total']++;
        $db = app::get('topwap')->database();
        $db->beginTransaction();
        try{
            $mdlGift = app::get('actlighticon')->model('gift');
            $mdlParticipant = app::get('actlighticon')->model('participant');
            //减礼品库存操作(用最终的礼物信息，与最终的礼物id)
            $mdlGift->update($gift_data_true,['gift_id'=>$gift_id_true]);
            //更新用户礼物信息操作
            $participant_update = array(
                'gift_id'=>$gift_id_true
            );
            $mdlParticipant->update($participant_update,['participant_id'=>$params['participant_id']]);
            $db->commit();
        }catch (\Exception $exception)
        {
            $db->rollBack();
            throw $exception;
        }

        return $gift_data_true;
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

    /**
     * 获取奖品详情
     * @param integer $gift_id 根据奖品id获取
     * @param integer $participant_id 根据参与人id获取
     */
    public function getGiftInfo($gift_id = null,$participant_id = null)
    {
        //根据gift_id获取
        if($gift_id!==null && $participant_id===null)
        {
            $giftInfo = app::get('actlighticon')->rpcCall('actlighticon.gift.getinfo',['gift_id'=>$gift_id]);
        }
        //根据participant_id获取
        elseif ($gift_id===null&&$participant_id!==null)
        {
            $participantInfo = app::get('actlighticon')->model('participant')->getRow('*',['participant_id'=>$participant_id]);
            $giftInfo = app::get('actlighticon')->rpcCall('actlighticon.gift.getinfo',['gift_id'=>$participantInfo['gift_id']]);
        }elseif($gift_id!==null || $participant_id!==null)
        {
            $giftInfo = app::get('actlighticon')->rpcCall('actlighticon.gift.getinfo',['gift_id'=>$gift_id]);
        }else{
            $giftInfo = null;
        }
        return $giftInfo;
    }

    /**
     * @param integer $gift_id 奖品id
     */
    public function is_have_gift($gift_id)
    {
        $gift_data = app::get('actlighticon')->model('gift')->getRow(['gift_id'=>$gift_id]);
        if($gift_data['need_deliver'] == 1)
        {
            if($gift_data['gift_total'] < 1)
            {
                throw new \LogicException('大礼包已经领空');
            }
        }
    }

    /**
     * 判断是否是点亮了最后一张头像
     * @param array $params 传入参数
     * @return bool
     */
    public function is_end($params)
    {
        /**
         * @var array $filter_operand operand筛选条件
         * @var array $filter_lightlog lightlog筛选条件
         */
        $filter_operand = array(
            'activity_id'=>$params['activity_id'],
            'shop_id'=>$params['shop_id'],
            //必须为0  1为删除 2为禁止
            'status'=>0
        );
        $filter_lightlog = array(
            'activity_id'=>$params['activity_id'],
            'shop_id'=>$params['shop_id'],
            'participant_id'=>$params['participant_id']
        );
        $count_operand = app::get('actlighticon')->model('operand')->count($filter_operand);
        $count_lightlog = app::get('actlighticon')->model('lightlog')->count($filter_lightlog);
        if($count_operand == $count_lightlog)
        {
            return true;
        }else{
            return false;
        }
    }
}










