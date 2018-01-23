<?php
class sysactivityvote_api_activeAdd {

    public $apiDescription = "创建投票活动数据";

    public function getParams()
    {
        $return['params'] = array(
            'shop_id' => ['type'=>'int','valid'=>'required','desc'=>'店铺id','default'=>'','example'=>'1'],
            'active_name' => ['type'=>'string','valid'=>'required','example'=>'投票活动','desc'=>'投票活动名称', 'msg'=>'请填写投票活动名称'],
            'active_profile' => ['type'=>'string','valid'=>'max:300','example'=>'','desc'=>'活动简介','msg'=>'活动简介不能超过300个字'],
            'active_start_time' => ['type'=>'string','valid'=>'required','example'=>'1483150679','desc'=>'活动开始时间'],
            'active_end_time' => ['type'=>'string','valid'=>'required','example'=>'1483150680','desc'=>'活动结束时间'],
            'popular_vote_start_time' => ['type'=>'string','valid'=>'required','example'=>'1483150679','desc'=>'大众投票开始时间'],
            'popular_vote_end_time' => ['type'=>'string','valid'=>'required','example'=>'1483150680','desc'=>'大众投票结束时间'],
            'expert_vote_start_time' => ['type'=>'string','valid'=>'required','example'=>'1483150679','desc'=>'专家投票开始时间'],
            'expert_vote_end_time' => ['type'=>'string','valid'=>'required','example'=>'1483150680','desc'=>'专家投票结束时间'],
            'active_link' => ['type'=>'string','valid'=>'','default'=>'','example'=>'http://abc.com','desc'=>'活动链接'],
            'personal_everyday_get_limit' => ['type'=>'string','valid'=>'','default'=>'0','example'=>'0','desc'=>'每人每天获得奖品次数'],
            'active_days' => ['type'=>'string','valid'=>'','default'=>'0','example'=>'0','desc'=>'奖品发放天数'],
            'win_probability' => ['type'=>'string','valid'=>'','default'=>'0','example'=>'0','desc'=>'获得奖品概率'],
            'boot_ad_image' => ['type'=>'string','valid'=>'','default'=>'0','example'=>'0','desc'=>'启动页广告图片'],
            'boot_ad_url' => ['type'=>'string','valid'=>'','default'=>'0','example'=>'0','desc'=>'启动页广告链接'],
            'boot_ad_able' => ['type'=>'string','valid'=>'','default'=>'0','example'=>'0','desc'=>'是否启用启动页广告'],
            'top_ad_image' => ['type'=>'string','valid'=>'','default'=>'0','example'=>'0','desc'=>'顶部广告图片'],
            'top_ad_url' => ['type'=>'string','valid'=>'','default'=>'0','example'=>'0','desc'=>'顶部广告链接'],
            'top_ad_slide' => ['type'=>'string','valid'=>'','default'=>'0','example'=>'0','desc'=>'顶部广告轮播图'],
            'active_rule' => ['type'=>'string','valid'=>'','default'=>'0','example'=>'0','desc'=>'活动规则'],
            'active_wap_rule' => ['type'=>'string','valid'=>'','default'=>'0','example'=>'0','desc'=>'活动规则(wap)'],
            'active_type' => ['type'=>'string','valid'=>'','default'=>'','example'=>'vote','desc'=>'活动类型'],
        );

        return $return;
    }

    public function save($params)
    {
        $this->__checkData($params);

        $current_time = time();
        $days = $this->__diffBetweenTwoDays($params['active_start_time'], $params['active_end_time']);

        $data = array(
            'shop_id' => $params['shop_id'],
            'active_name' => $params['active_name'],
            'active_profile' => $params['active_profile'],
            'active_start_time' => $params['active_start_time'],
            'active_end_time' => $params['active_end_time'],
            'popular_vote_start_time' => $params['popular_vote_start_time'],
            'popular_vote_end_time' => $params['popular_vote_end_time'],
            'expert_vote_start_time' => $params['expert_vote_start_time'],
            'expert_vote_end_time' => $params['expert_vote_end_time'],
            'active_link' => $params['active_link'],
            'personal_everyday_get_limit' => $params['personal_everyday_get_limit'],
            'active_days' => $days+1,
            'win_probability' => $params['win_probability'],
            'boot_ad_image' => $params['boot_ad_image'],
            'boot_ad_url' => $params['boot_ad_url'],
            'boot_ad_able' => $params['boot_ad_able'],
            'top_ad_image' => $params['top_ad_image'],
            'top_ad_url' => $params['top_ad_url'],
            'top_ad_slide' => $params['top_ad_slide'],
            'active_rule' => $params['active_rule'],
            'active_wap_rule' => $params['active_wap_rule'],
            'active_type' => $params['active_type'],
            'create_time' => $current_time,
            'modified_time' => $current_time,
            'deleted' => 0,
        );

        $objMdlActive = app::get('sysactivityvote')->model('active');
        $active_id = $objMdlActive->insert($data);

        if(!$active_id)
        {
            throw new \LogicException('添加投票活动失败');
        }

        $active_link = url::action('topwap_ctl_activityvote_vote@index', array('active_id'=>$active_id));
        $objMdlActive->update(array('active_link'=>$active_link), array('active_id'=>$active_id));

        return true;
    }

    /**
     * 计算两个日期相隔天数
     * @param $t1
     * @param $t2
     * @return float|int
     */
    private function __diffBetweenTwoDays($t1, $t2)
    {
        $d1 = date('Y-m-d', $t1);
        $d2 = date('Y-m-d', $t2);

        if($d1 == $d2){
            return 0;
        }else{
            $second1 = strtotime($d1);
            $second2 = strtotime($d2);
            if ($second1 > $second2) {
                $tmp = $second2;
                $second2 = $second1;
                $second1 = $tmp;
            }
            return ($second2 - $second1) / 86400;
        }
    }

    /**
     * 校验数据
     * @param $data
     */
    private function __checkData($data)
    {
        //结束时间大于等于开始时间
        if($data['active_start_time'] > $data['active_end_time'])
        {
            throw new \LogicException('活动开始时间应大于等于活动结束时间');
        }
        if(in_array($data['active_type'], array('vote')))
        {
            if($data['popular_vote_start_time'] > $data['popular_vote_end_time'])
            {
                throw new \LogicException('大众投票开始时间应大于等于大众投票结束时间');
            }
            if($data['expert_vote_start_time'] > $data['expert_vote_end_time'])
            {
                throw new \LogicException('专家投票开始时间应大于等于专家投票结束时间');
            }

            //投票时间在活动时间内
            if(!($data['popular_vote_start_time'] >= $data['active_start_time'] &&
                $data['popular_vote_start_time'] <= $data['active_end_time'] &&
                $data['popular_vote_end_time'] >= $data['active_start_time'] &&
                $data['popular_vote_end_time'] <= $data['active_end_time']))
            {
                throw new \LogicException('大众投票有效期应在活动有效期内');
            }
            if(!($data['expert_vote_start_time'] >= $data['active_start_time'] &&
                $data['expert_vote_start_time'] <= $data['active_end_time'] &&
                $data['expert_vote_end_time'] >= $data['active_start_time'] &&
                $data['expert_vote_end_time'] <= $data['active_end_time']))
            {
                throw new \LogicException('专家投票有效期应在活动有效期内');
            }
        }
    }
}