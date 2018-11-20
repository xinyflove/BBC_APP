<?php

class topwap_ctl_member_benefit extends topwap_ctl_member {

    public $limit = 10;
    public $shop_id;
    function __construct($app=null)
    {
        parent::__construct($app);
        $this->shop_id = app::get('sysshop')->getConf('sysshop.tvshopping.shop_id');
    }

    /**
     * 会员权益列表页
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function benefitList()
    {
        $pagedata['title'] = app::get('topwap')->_('会员专享权益');
        $shop_id = $this->shop_id;
        $pagedata['shop_id'] = $shop_id;
        $shop_data = app::get('topwap')->rpcCall('shop.get', ['shop_id' => $shop_id]);
        $pagedata['shop_data'] = $shop_data;
        // $this->setLayoutFlag('index');
        $user_id = userAuth::id();
        // $pagedata['account'] = userAuth::getLoginName();
        $user_info = userAuth::getUserInfo();
        $user_info['shop_grade'] = app::get('systrade')->rpcCall('user.grade.basicinfo', ['user_id'=>$user_id, 'shop_id' => $shop_id]);
        $pagedata['user_info'] = $user_info;

        $pagedata['interests'] = app::get('sysshop')->rpcCall('shop.get.interests', ['shop_id' => $shop_id, 'fields' => 'member_day_desc,birthday_privilege_desc,vip_package_desc']);

        // $this->setLayout('default-rewrite.html');
        return $this->page('topwap/member/benefit/list.html', $pagedata);
    }

    /**
     * 会员权益规则说明页
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function benefitRule()
    {
        $pagedata['title'] = app::get('topwap')->_('会员权益规则');
        $shop_id = $this->shop_id;
        $pagedata['shop_id'] = $shop_id;
        $pagedata['interests'] = app::get('sysshop')->rpcCall('shop.get.interests', ['shop_id' => $shop_id, 'fields' => 'rule_desc']);

        // $this->setLayout('default-rewrite.html');
        return $this->page('topwap/member/benefit/rule.html', $pagedata);
    }

    /**
     * 会员权益礼包
     * 目前只是领取优惠券
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function ajaxGetBenefit()
    {
        $data = input::get();
        $user_id = userAuth::id();
        if(!$user_id){
            $msg = app::get('topwap')->_('未登录,请先登录！');
            $url = url::action('topwap_ctl_passport@goLogin');
            return $this->splash('error', $url, $msg, true);
        }
        $data['user_id'] = $user_id;
        try
        {
            $result = app::get('sysuser')->rpcCall('promotion.benefit.issue', ['user_id' => $data['user_id'], 'shop_id' => $this->shop_id]);

        }
        catch (Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', null, $msg, true);
        }
        // $result['coupon'] = ['aaaaa', 'bbbbbbb'];
        return $this->splash('success', null, $result, true);
        // return response::json($data);
    }

}

