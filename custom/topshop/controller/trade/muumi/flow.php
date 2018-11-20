<?php
/*
 * 原始店铺为被代卖的商品发货
 * Date: 2018-7-3 14:44:41
 * Author: 王衍生
 * authorEmail: 50634235@qq.com
 * company: 青岛广电电商
 */
class topshop_ctl_trade_muumi_flow extends topshop_controller{
    /**
     * 变更物流信息
     * @params null
     * @return null
     */
    public function updateLogistic()
    {
      //$requestData = input::get();
      //$requestData['dlytmpl_id'] = input::get('dlytmpl_id');
        $requestData['logi_no'] = input::get('logi_no');
        $requestData['corp_id'] = input::get('corp_id');
        $requestData['delivery_id'] = input::get('delivery_id');
        $requestData['shop_id'] = $this->shopId;
        $tid = input::get('tid');
        try{

            $params['tid'] = $tid;
            $params['fields'] = "tid,status";
            $tradeInfo = app::get('topshop')->rpcCall('trade.get',$params);
            /*modify_20171106_by_fanglongji_start*/
            /*
            if(!$tradeInfo['status']= 'WAIT_BUYER_CONFIRM_GOODS')
            */
            // jj($tradeInfo);
            if($tradeInfo['status'] != 'WAIT_BUYER_CONFIRM_GOODS' && $tradeInfo['status'] != 'PARTIAL_SHIPMENT')
            /*modify_20171106_by_fanglongji_end*/
                throw new Exception(app::get('topshop')->_('只能修改已发货待收货订单的物流信息'));

            app::get('topshop')->rpcCall('delivery.updateLogistic', $requestData);
        }catch(Exception $e){
            return $this->splash('error',null, $e->getMessage(), true);
        }

        $url = url::action('topshop_ctl_trade_muumi_detail@index', ['tid'=>$tid]);
        return $this->splash('success',$url, '更新物流信息成功', true);
    }

    /**
     * 发货订单处理
     * @params null
     * @return null
     */
    public function dodelivery()
    {
        $sdf = input::get();
        /*add_20171103_by_fanglongji_start*/
        $sdf['filter_array'] = trim(str_replace('\\','',$sdf['filter_array']),'"');
        $sdf['filter_array'] = json_decode($sdf['filter_array'],true);

        $send_num = array_sum($sdf['filter_array']);
        if($send_num == 0)
        {
            return $this->splash('error',null, '没有要发货的商品', true);
        }
        // ff(input::get());
        /*add_20171103_by_fanglongji_end*/
        //当订单为自提订单并且没有物流配送，可以填写字体备注
        if( isset($sdf['isZiti']) && $sdf['isZiti'] == "true" )
        {
            if(!trim($sdf['logi_no']) && !trim($sdf['ziti_memo']))
            {
                return $this->splash('error',null, '订单为自提订单，运单号和备注至少选择一项必填', true);
            }
            if( mb_strlen(trim($sdf['ziti_memo']),'utf8') > 200)
            {
                return $this->splash('error',null, '自提备注过长', true);
            }
            $sdf['ziti_memo'] = trim($sdf['ziti_memo']) ? trim($sdf['ziti_memo']) : "";
        }
        else
        {
            unset($sdf['isZiti'],$sdf['ziti_memo']);
            if(empty($sdf['logi_no']))
            {
                return $this->splash('error',null, '发货单号不能为空', true);
            }
        }

        if(isset($sdf['logi_no']) && trim($sdf['logi_no']) && strlen(trim($sdf['logi_no'])) < 6)
        {
            return $this->splash('error',null, '运单号过短，请认真核对后填写(大于6)正确的编号', true);
        }

        if(strlen(trim($sdf['logi_no'])) > 20 )
        {
            return $this->splash('error',null, '运单号过长，请认真核对后填写(小于20)正确的编号', true);
        }
        $sdf['logi_no'] = trim($sdf['logi_no']) ? trim($sdf['logi_no']) : "0";
        $sdf['seller_id'] = $this->sellerId;
        $sdf['shop_id'] = $this->shopId;
        // 标识代卖订单发货
        // $sdf['muumi_delivery'] = 1;

        try
        {
            app::get('topshop')->rpcCall('trade.delivery',$sdf);
        }
        catch (Exception $e)
        {
            return $this->splash('error',null, $e->getMessage(), true);
        }
        $this->sellerlog('订单发货。订单号是:'.$sdf['tid']);

        //发送订单发货通知
        $data = [
            'tid' => $sdf['tid'],
            'shop_id' => $sdf['shop_id'],
            'corp_code' => $sdf['corp_code'],
            'logi_no' => $sdf['logi_no'],
        ];

        try
        {
            app::get('topshop')->rpcCall('trade.shop.delivery.notice.send', $data);
        }
        catch (Exception $e)
        {
            return $this->splash('error',null, $e->getMessage(), true);
        }
        finally
        {
            $url = url::action('topshop_ctl_trade_muumi_list@index', ['useSessionFilter'=>true]);
            return $this->splash('success',$url, '发货成功', true);
        }
        /*modify_20171109_by_fanglongji_end*/
    }

    /**
     * 产生订单发货页面
     * @params string order id
     * @return string html
     */

    public function goDelivery()
    {
        //面包屑
        $this->runtimePath = array(
            ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],
            ['url'=> url::action('topshop_ctl_trade_list@index'),'title' => app::get('topshop')->_('代卖订单列表')],
            ['title' => app::get('topshop')->_('代卖订单发货')],
        );
        $this->contentHeaderTitle = app::get('topshop')->_('代卖订单发货');

        $tid = input::get('tid');
        if(!$tid)
        {
            header('Content-Type:application/json; charset=utf-8');
            echo '{error:"'.app::get('topshop')->_("订单号传递出错.").'",_:null}';exit;
        }
        $params['tid'] = $tid;

        $params['fields'] = "logistics.corp_code,logistics.corp_code,logistics.logi_no,logistics.logi_name,logistics.delivery_id,logistics.receiver_name,logistics.t_begin,orders.spec_nature_info,tid,receiver_name,receiver_mobile,receiver_state,receiver_district,receiver_address,need_invoice,ziti_addr,invoice_type,invoice_name,invoice_main,orders.price,orders.num,orders.sendnum,orders.title,orders.item_id,orders.pic_path,total_fee,discount_fee,buyer_rate,adjust_fee,orders.total_fee,orders.adjust_fee,created_time,pay_time,consign_time,end_time,shop_id,need_invoice,invoice_name,invoice_type,invoice_main,orders.bn,cancel_reason,orders.refund_fee,orders.aftersales_status,orders.dlytmpl_id,shipping_type,orders.gift_data,orders.init_item_id,orders.init_shop_id,orders.cost_price";
        /*modify_20171106_by_fanglongji_end*/
        // $tradeInfo = app::get('topshop')->rpcCall('trade.get',$params,'seller');
        $tradeInfo = app::get('topshop')->rpcCall('trade.get',$params);
        // jj($tradeInfo);
        //获取默认图片信息
        $pagedata['defaultImageId']= kernel::single('image_data_image')->getImageSetting('item');

        $pagedata['tradeInfo'] = $tradeInfo;
        // jj($tradeInfo);
        //获取用户的物流模板
        if($tradeInfo['shipping_type'] == 'ziti')
        {
            $pagedata['ziti'] = 'true';
        }

        $dlycorp = app::get('topshop')->rpcCall('shop.dlycorp.getlist',['shop_id'=>$this->shopId]);
        $pagedata['dlycorp'] = $dlycorp['list'];
        // 子订单号
        $oid_array = array_column($tradeInfo['orders'],'oid');
        // 已发货数量
        // $sum_array = array_column($tradeInfo['orders'],'sendnum');
        $sum_array = array_fill(0,count($oid_array),0);
        $pagedata['filter_array'] = array_combine($oid_array,$sum_array);
        // jj($pagedata['filter_array']);

        return $this->page('topshop/trade/muumi/godelivery.html', $pagedata);
    }
}

