<?php

/**
 * offlinepay.php 线下消费详情
 * @author     fanglongji
 */
class topwap_ctl_member_offlinepay extends topwap_ctl_member {
    public function __construct()
    {

    }
    /*
     * 支付列表及详情
     */
    public function payInfo()
    {
        $this->setLayoutFlag('pay_info');
        $user_id = userAuth::id();
        $filter['user_id'] = $user_id;
        $filter['fields'] = '*';
        $filter['page_no'] = 1;
        $filter['page_size'] = 6;
        try
        {
            $trade_list = $this->getOfflineTradeList($filter);
            $pageData['trade_list'] = $trade_list['list'];
            $pageData['trade_count'] = $trade_list['count'];
            $pageData['total_page'] = ceil($trade_list['count']/$filter['page_size']);

            return $this->page('topwap/member/offline/pay_info.html', $pageData);
        }
        catch(Exception $e)
        {
            throw new Exception($e->getMessage());
        }

    }
    /**
     * 获取线下订单信息
     */
    public function getOfflineTradeList($filter)
    {
        $trade_list = app::get('topwap')->rpcCall('trade.offline.get.list', $filter);
        //获取线下店的名称
        $agent_shop_ids = array_column($trade_list['list'], 'agent_shop_id');
        $MdlAgentShop = app::get('syssupplier')->model('agent_shop');
        $agent_shop_info = $MdlAgentShop->getList('agent_shop_id, name',['agent_shop_id'=>$agent_shop_ids]);
        $agent_shop_info = array_bind_key($agent_shop_info, 'agent_shop_id');
        //获取优惠券的信息
        $tids = array_column($trade_list['list'], 'tid');
        $voucher_filter['tids'] = $tids;
        $voucher_filter['fields'] = '*';
        $voucher_info = app::get('topwap')->rpcCall('supplier.agentvoucher.get.list', $voucher_filter)['list'];
        $voucher_info = $this->array_group_key($voucher_info, 'tid');

        $all_hold_ids = array_column($trade_list['list'], 'all_hold_id');
        $all_hold_ids = array_unique(array_filter($all_hold_ids));
        $all_hold_filter['fields'] = '*';
        $all_hold_filter['disabled'] = 0;
        $all_hold_filter['filter'] = ['all_hold_id' => $all_hold_ids];
        $all_hold_info = app::get('topwap')->rpcCall('supplier.agent.activity.list', $all_hold_filter)['data'];
        $all_hold_info = array_bind_key($all_hold_info, 'agent_activity_id');

        //获取取消和关闭订单信息
        $cancel_filter['tid'] = $tids;
        $cancel_filter['fields'] = 'tid,reason';
        $cancel_info = app::get('topwap')->rpcCall('offline.trade.cancel.list.get',$cancel_filter)['list'];

        foreach($trade_list['list'] as $id => &$trade)
        {
            if($trade['status'] === 'WAIT_BUYER_PAY')
            {
                $trade['explain'] = '等待付款';
            }
            elseif($trade['status'] === 'TRADE_FINISHED')
            {
                $trade['explain'] = '已完成';
            }
            elseif($trade['status'] === 'TRADE_CLOSED_BY_SYSTEM')
            {
                $trade['explain'] = '已关闭';
                $trade['cancel_reason'] = $cancel_info[$id]['reason'];
            }
            $trade['total_fee']    = number_format(($trade['total_fee']),2,".","");
            $trade['payment']      = number_format(($trade['payment']),2,".","");
            $trade['voucher_fee']  = number_format(($trade['voucher_fee']),2,".","");
            $trade['pay_time']     = date('Y-m-d H:i:s', $trade['pay_time']);
            $trade['agent_shop_name'] = $agent_shop_info[$trade['agent_shop_id']]['name'];
            $trade['voucher_list']    = $voucher_info[$id];
            $trade['all_hold_info']   = $all_hold_info[$trade['all_hold_id']];
            $trade['voucher_list'] = (array)$trade['voucher_list'];
            $trade['voucher_list']['list'] = (array)$trade['voucher_list']['list'];
            $trade['all_hold_info'] = (array)$trade['all_hold_info'];
            foreach ($trade['voucher_list']['list'] as &$vv)
            {
                $vv['item_image'] = base_storager::modifier($vv['item_image']);
            }
        }
        return $trade_list;
    }
    /**
     * ajax获取线下订单
     */
    public function ajaxGetOfflineTrade()
    {
        $post_data = input::get();
        $user_id = userAuth::id();
        $filter['user_id'] = $user_id;
        $filter['fields'] = '*';
        $filter['page_no'] = $post_data['page_no'];
        $filter['page_size'] = 6;
        try
        {
            $trade_list = $this->getOfflineTradeList($filter);
            if ($trade_list)
            {
                $pageData['trade_list'] = $trade_list['list'];
                $pageData['trade_count'] = $trade_list['count'];
                $data['html'] = view::make('topwap/member/offline/trade_list.html', $pageData)->render();
                $data['success'] = true;
            }
            return response::json($data);exit;
        }
        catch(Exception $e)
        {
            $data = [];
            return response::json($data);exit;
        }
    }
    /**
     * 取消线下订单页面
     */
    public function cancel()
    {

        $validator = validator::make([
            input::get('tid')
        ], [
            'numeric'
        ]);
        if ($validator->fails())
        {return $this->splash('error', null, '订单格式错误！', true);}

        $pagedata ['tid'] = $params ['tid'] = input::get('tid');
        $pagedata ['reason'] = config::get('tradeCancelReason');
        $pagedata ['title'] = "取消订单"; // 标题
        return $this->page('topwap/offlinepay/cancel/cancel.html', $pagedata);
    }

    /**
     * 线下订单取消逻辑
     */
    public function cancelBuyer()
    {
        $this->setLayoutFlag('cart');
        $reasonSetting = config::get('tradeCancelReason');
        $reasonPost = input::get('cancel_reason');

        if(!$reasonPost)
        {
            $msg = app::get('topwap')->_('取消原因必填');
            return $this->splash('error',null,$msg);
        }

        if($reasonPost == "other")
        {
            $cancelReason = input::get('other_reason');
            $validator = validator::make([trim($cancelReason)],['required|max:50'],['取消原因必须填写!|取消原因最多填写50个字']);
            if ($validator->fails())
            {
                $messages = $validator->messagesInfo();
                foreach( $messages as $error )
                {
                    return $this->splash('error',null,$error[0]);
                }
            }
        }
        else
        {
            $cancelReason = $reasonSetting['user'][$reasonPost];
        }

        $params['tid'] = input::get('tid');
        $params['user_id'] = userAuth::id();
        $params['cancel_reason'] = $cancelReason;
        try
        {
            app::get('topwap')->rpcCall('trade.offline.cancel.create',$params);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }
        $url = url::action('topwap_ctl_member_offlinepay@payInfo');
        $msg = app::get('topwap')->_('订单取消成功');
        return $this->splash('success', $url, $msg, true);
    }



    /**
     * 根据传入的数组和数组中值的键值，将对数组的键进行替换
     *
     * @param array $array
     * @param string $key
     */
    public function array_group_key($array, $key)
    {
        foreach( (array)$array as $value )
        {
            if( !empty($value[$key]) )
            {
                $k = $value[$key];
                $result[$k]['list'][] = $value;
                $result[$k]['count'] += 1;
                $result[$k]['deduct_price'] = number_format(($value['deduct_price']),2,".","");
                $result[$k]['agent_type']   = $value['agent_type'];
                $result[$k]['item_image']   = $value['item_image'];
                $result[$k]['item_id']      = $value['item_id'];
                $result[$k]['title']        = $value['title'];
                $result[$k]['voucher_ids']  = implode(',',array_column($result[$k]['list'],'vocher_id'));
                $result[$k]['agent_use_limit'] = $value['agent_use_limit'];
//                if($value['agent_type'] === 'CASH_VOCHER')
//                {
//                    $result[$k]['title'] = intval($value['deduct_price']).'元代金券';
//                }
//                elseif($value['agent_type'] === 'DISCOUNT')
//                {
//                    $result[$k]['title'] = intval($value['deduct_price']).'折券';
//                }
//                elseif($value['agent_type'] === 'REDUCE')
//                {
//                    $result[$k]['title'] = intval($value['deduct_price']).'满减券';
//                }
            }
        }
        return $result;
    }

}

