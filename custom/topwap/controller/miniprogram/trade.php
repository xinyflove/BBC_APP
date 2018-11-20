<?php

/**
 * 小程序订单
 */
class topwap_ctl_miniprogram_trade extends topwap_controller
{
    public $tradeStatus = array(
        //0代表全部
        '0'=>   null,                           #全部虚拟商品（包括限量购和米粒有偿劵）
        '1' => 'WAIT_BUYER_PAY',                #已下单等待付款
        '2' => 'TRADE_FINISHED|WAIT_WRITE_OFF|WRITE_PARTIAL'      #已购买
    );

    public $limit = 10;

    private $user_id;

    public function __construct()
    {
        $this->user_id = userAuth::getAccountId();
        parent::__construct();
    }

    public function ajaxTradeList()
    {
        try {
            //s
            //pages
            $postdata = input::get();
            $pagedata = $this->__getTrade($postdata);
            foreach ($pagedata['trades'] as $k_trade => &$v_trade) {
                //order双层嵌套数组变单层
                $v_trade['order'] = $v_trade['order'][0];
                foreach ($v_trade['order'] as $k_order => &$v_order) {
                    $v_order['pic_path'] = base_storager::modifier($v_order['pic_path']);
                }
            }
            foreach ($pagedata['defaultImageId'] as &$v)
            {
                $v['default_image'] = base_storager::modifier($v['default_image']);
            }
            $pagedata['trades'] = array_values($pagedata['trades']);
            return response::json([
                'err_no'=>0,
                'data'=>$pagedata,
                'message'=>'获取订单列表成功'
            ]);
        } catch ( Exception $e )
        {
            return response::json([
                'err_no'=>1001,
                'data'=>[
                ],
                'message'=>$e->getMessage()
            ]);
        }
    }

    /**
     * 订单详情
     */
    public function ajaxTradeDetail()
    {
        try{
            $params['tid'] = input::get('tid');
            $params['user_id'] = $this->user_id;
            $params['fields'] = "logistics.corp_code,logistics.corp_code,logistics.logi_no,logistics.logi_name,logistics.delivery_id,logistics.receiver_name,logistics.t_begin,tid,shipping_type,status,payment,points_fee,cancel_status,hongbao_fee,post_fee,pay_type,payed_fee,pay_time,receiver_state,receiver_city,receiver_district,receiver_address,trade_memo,receiver_name,receiver_mobile,ziti_addr,ziti_memo,orders.price,orders.aftersales_status,orders.num,orders.sendnum,orders.title,orders.item_id,orders.pic_path,total_fee,discount_fee,buyer_rate,adjust_fee,orders.total_fee,orders.adjust_fee,created_time,shop_id,need_invoice,invoice_name,invoice_type,invoice_main,invoice_vat_main,activity,cancel_reason,orders.spec_nature_info,orders.gift_data,orders.is_cross,identity_card_number";
            $trade = app::get('topc')->rpcCall('trade.get',$params,'buyer');
            foreach ($trade['orders'] as &$v)
            {
                $v['pic_path'] = base_storager::modifier($v['pic_path']);
            }
            return response::json([
                'err_no'=>0,
                'data'=>$trade,
                'message'=>'获取订单详情成功'
            ]);
        }catch (\Exception $exception)
        {
            return response::json([
                'err_no'=>1001,
                'data'=>[
                ],
                'message'=>$exception->getMessage()
            ]);
        }
    }

    // 获取订单列表
    private function __getTrade($postdata)
    {
        if (isset($this->tradeStatus [$postdata ['s']]))
        {
            $status = $this->tradeStatus [$postdata ['s']];
        }
        else
        {
            $status = null;
        }

        if(null !== $status)
        {
            if($postdata ['s'] == '2')
            {
                //已购买
                $status_arr = explode('|',$status);
                $params ['status|in'] = $status_arr;
            }else{
                $params ['status'] = $status;
            }
        }
        $params ['is_virtual'] = 1; #虚拟商品
        $params ['user_id'] = $this->user_id;
        $params ['page_no'] = intval($postdata ['pages']) ? intval($postdata ['pages']) : 1;
        $params ['page_size'] = intval($this->limit);
        $params ['order_by'] = 'created_time desc';
        $params ['fields'] = 'tid,shop_id,user_id,status,payment,hongbao_fee,points_fee,total_fee,cancel_status,itemnum,post_fee,payed_fee,receiver_name,created_time,receiver_mobile,discount_fee,need_invoice,adjust_fee,order.title,order.price,order.num,order.pic_path,order.tid,order.oid,order.aftersales_status,buyer_rate,order.complaints_status,order.item_id,order.cat_id,order.shop_id,order.status,activity,pay_type,order.spec_nature_info,order.gift_data';

        $tradelist = app::get('topwap')->rpcCall('trade.get.list', $params);

        $count = $tradelist ['count'];
        $tradelist = $tradelist ['list'];

        foreach ($tradelist as $key => $row)
        {
            $tradelist [$key] ['is_buyer_rate'] = false;

            foreach ($row ['order'] as $orderListData)
            {
                if(isset($orderListData['gift_data']) && $orderListData['gift_data'])
                {
                    $tradelist[$key]['gift_count'] += array_sum(array_column($orderListData['gift_data'],'gift_num'));
                }

                if ($row ['buyer_rate'] == '0' && $row ['status'] == 'TRADE_FINISHED')
                {
                    $tradelist [$key] ['is_buyer_rate'] = true;
                    break;
                }
            }

            unset($tradelist [$key] ['order']);
            $tradelist [$key] ['order'] [0] = $row ['order'];

            if (! $tradelist [$key] ['is_buyer_rate'] && $postdata ['s'] == 4)
            {
                unset($tradelist [$key]);
            }
            $tradelist[$key]['actual_payment'] = ecmath::number_minus(array($row['payment'], $row['hongbao_fee']));
        }

        $pagedata ['trades'] = (array)$tradelist;
        $pagedata ['count'] = $count;
        $pagedata ['status'] = $postdata ['s']; // 状态
        $pagedata['pagers'] = $this->__pages($postdata['pages'],$postdata,$count);
        $pagedata ['defaultImageId'] = kernel::single('image_data_image')->getImageSetting('item');
        return $pagedata;
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

}