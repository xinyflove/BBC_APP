<?php

/**
 * Class topshop_ctl_trade_agentVoucher
 * 线下店卡卷使用状态
 * 等待商家核销,即:买家已付款 -> WAIT
 * 已经核销完成 -> COMPLETE
 * 卡券过期 -> EXPIRE
 */
class topshop_ctl_trade_agentVoucher extends topshop_controller{
    public $limit = 20;

    public function __construct($app)
    {
        parent::__construct();
        if($this->shopInfo['offline'] === 'off')
        {
            echo '虚拟卡卷线下支付功能未开启！';die;
        }
    }

    /**
     * @desc 商家后台交易列表
     * @return html
     * @author: shopEx
     * @updater: gurundong tvpalza
     * @date: 20180131,增加虚拟商品核销状态
     *
     */
    public function index()
    {
        //订单屏显状态
        $orderStatusList = array(
            '0' => '全部',
            '1' => '未使用',
            '2' => '已使用',
            '3' => '已过期',
        );

        $status = (int)input::get('status');
        $status = in_array($status, array_keys($orderStatusList)) ? $status : 0;

        $pagedata['status'] = $orderStatusList;
        $pagedata['filter']['status'] = $status;
        $pagedata['shop_type'] = $this->shopInfo['shop_type'];
        $this->contentHeaderTitle = app::get('topshop')->_('线下支付核销');
        return $this->page('topshop/trade/agentVoucher/list.html', $pagedata);
    }

    /**
     * @desc 获取订单列表
     * @return mixed
     * @author: wudi tvplaza
     * @date: 201707311530
     */
    public function search()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('虚拟订单查询');
        $postFilter = input::get();
//        $pagedata['status'] = $postFilter['status'];
//        dump($postFilter);die;
        $postFilter['shop_id']=$this->shopId;
        $filter = $this->_checkParams($postFilter);
        $objMdlVoucher=app::get('systrade')->model('agent_vocher');
        $count=$objMdlVoucher->count($filter);
        $page =  $filter['pages'] ? $filter['pages'] : 1;
        $limit = $this->limit;
        $pageTotal = ceil($count/$limit);
        $currentPage = ($pageTotal < $page) ? $pageTotal : $page; //防止传入错误页面，返回最后一页信息
        $offset = ($currentPage-1) * $limit;
        if(isset($filter['agent_price'])){
            $filter['agent_price'] = $filter['agent_price'] === 'on'?1:0;
        }
//        var_dump($filter);die;
        $result=$objMdlVoucher->batch_dump($filter,'*','default',$offset,$limit,null);
//        var_dump($result);die;
        $orderMdl = app::get('systrade')->model('order');
        $tradeMdl = app::get('systrade')->model('trade');
        $userMdl = app::get('sysuser')->model('user');
        $userAccount = app::get('sysuser')->model('account');
        foreach ($result as $key => $value) {
            $orderData = $orderMdl->getRow('price,title,oid',['oid'=>$value['sys_oid']]);
            $tradeData = $tradeMdl->getRow('tid,receiver_name,receiver_mobile,receiver_phone',['tid'=>$value['sys_tid']]);
            //name 用户昵称  username 用户名
            $userData = $userMdl->getRow('username,name',['user_id'=>$value['user_id']]);
            // login_account 登录账号  mobile 手机号
            $accountData = $userAccount->getRow('login_account,mobile',['user_id'=>$value['user_id']]);
            $u_data = array_merge($userData,$accountData);
            $result[$key]['order'][$value['oid']] = $orderData;
            $result[$key]['trade'][$value['tid']] = $tradeData;
            $result[$key]['user'] = $u_data;
        }
        /** @var systrade_agentVocher $agentVocher */
        $agentVocher = Kernel::single(systrade_agentVocher::class);
        $result = $agentVocher->_format_result($result);
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        $pagedata['count']=$count;
        $pagedata['list']=$result;
        $pagedata['pagers']=$this->__pager($filter,$currentPage,$count);
        return view::make('topshop/trade/agentVoucher/item.html', $pagedata);
    }

    /**
     * 修改卡卷日期
     */
    public function ajaxChangeTime()
    {
        $id = input::get('voucher_id');
        $endTime = input::get('end_time');
        if(!$id)
        {
            return json_encode(['status'=>false,'message'=>'id不能为空']);
        }
        if(!$endTime)
        {
            return json_encode(['status'=>false,'message'=>'时间不能为空']);
        }
        $endTime = strtotime($endTime);
        /** @var base_db_model $objMdlVoucher */
        try{
            $objMdlVoucher=app::get('systrade')->model('agent_vocher');
            $res = $objMdlVoucher->update(['end_time'=>$endTime],['vocher_id'=>$id]);
            if(is_int($res) && $res>0)
            {
                return json_encode(['status'=>true,'message'=>'修改成功']);
            }
        }catch (Exception $exception)
        {
            return json_encode(['status'=>false,'message'=>$exception->getMessage()]);
        }
    }

    /**
     * @desc 格式化分页数据
     * @param $postFilter
     * @param $page
     * @param $count
     * @return array
     * @author: gurundong tvplaza
     * @date: 20190131
     */
    private function __pager($postFilter,$page,$count)
    {
        $postFilter['pages'] = time();
        $total = ceil($count/$this->limit);
        $pagers = array(
            'link'=>url::action('topshop_ctl_trade_agentVoucher@search',$postFilter),
            'current'=>$page,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>time(),
        );
        return $pagers;

    }

    /**
     * @desc 格式化传参
     * @param $filter
     * @return mixed
     * @author: gurundong tvplaza
     * @date: 201801231
     */
    private function _checkParams($filter)
    {
        $statusLUT = array(
            '1' => 'WAIT',
            '2' => 'COMPLETE',
            '3' => 'EXPIRE',
        );
        foreach($filter as $key=>$value)
        {
            if(!$value) unset($filter[$key]);
            if($key=='status' && $value)
            {
                if(in_array($value,array_keys($statusLUT)))
                {
                    if($value == 1){
                        $filter['status|in'] = $statusLUT[$value];
                        $filter['end_time|bthan']=time();
                        unset($filter['status']);
                    }elseif($value==3){
                        //添加对已过期的判断
                        $filter['end_time|sthan']=time();
                        unset($filter['status']);
                    }else{
                        $filter['status'] = $statusLUT[$value];
                    }
                }
            }
        }

        if($filter['filter_time'])
        {
            $times = explode('-',$filter['filter_time']);
            if($times){
                if($filter['time_type']=='write'){
                    $filter['write_time|bthan'] = strtotime($times['0']);
                    $filter['write_time|sthan'] = strtotime($times['1']);
                }else{
                    $filter['careated_time|bthan'] = strtotime($times['0']);
                    $filter['careated_time|sthan'] = strtotime($times['1']+1);
                }
                unset($filter['create_time']);
                unset($filter['filter_time']);
            }
        }
        unset($filter['time_type']);
        return $filter;
    }
}

