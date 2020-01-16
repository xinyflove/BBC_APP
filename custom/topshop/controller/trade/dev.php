<?php
class topshop_ctl_trade_dev extends topshop_controller{
    /**
     * @name tools
     * @desc dev tools
     * @author: wudi tvplaza
     * @date: 2017-10-28 9:07
     */
    public function tools()
    {
        set_time_limit(0);

        //authorization
        $pwd = input::get('auth');
        if ($pwd != 'admin') {
            exit('您无权访问该页面');
            die;
        }

        $method = input::get('method');
        //##############################按照支付退款时间出账###############################################
        if ($method == 'billing1') {
            //2017-11-21之前，银联商务支付手续费用为千分之五
            $ongoingData = 1483200000;//2017-09-30 00:00:00 new plate first trade created time
            while ($ongoingData < 1511193600) {// date('Y-m-d H:i:s',1511193600)=2017-11-21 00:00:00
                $filter = array(
                    'time_start' => $ongoingData,
                    'time_end' => $ongoingData + 86399,
                );
                kernel::single('sysclearing_tasks_billing')->exec($filter);
                $ongoingData += 86400;
            }
            exit('收入成本明细整理完毕');
            die;
        } elseif ($method == 'billing2') {
            //2017-11-21当天起，银联商务支付手续费用为千分之6
            $curDateStart = strtotime(date("Y-m-d", time()));
            $ongoingData = 1543420800;// date('Y-m-d H:i:s',1511193600)=2017-11-21 00:00:00
            while ($ongoingData < $curDateStart) {
                $filter = array(
                    'time_start' => $ongoingData,
                    'time_end' => $ongoingData + 86399,
                    'shop_id'  => '38'//指定店铺更新数据
                );
                kernel::single('sysclearing_tasks_billing')->exec($filter);
                $ongoingData += 86400;
            }
            exit('收入成本明细整理完毕');
            die;
        } elseif ($method == 'billing3') {
            //2017-11-21当天起，银联商务支付手续费用为千分之6
            $curDateStart = strtotime(date("Y-m-d", time()));
            $ongoingData = 1539100800;// date('Y-m-d H:i:s',1511193600)=2017-11-21 00:00:00
            while ($ongoingData < $curDateStart) {
                $filter = array(
                    'time_start' => $ongoingData,
                    'time_end' => $ongoingData + 86399,
                );
                kernel::single('sysclearing_tasks_billing')->exec($filter);
                $ongoingData += 86400;
            }
            exit('收入成本明细整理完毕');
            die;
        }elseif ($method == 'single_bill') {
            $ongoingData = 1525795200;// date('Y-m-d H:i:s',1511193600)=2017-11-21 00:00:00
                $filter = array(
                    'time_start' => $ongoingData,
                    'time_end' => $ongoingData + 86399,
                );
                kernel::single('sysclearing_tasks_billing')->exec($filter);
            exit('收入成本明细整理完毕');
            die;
        } elseif ($method == 'billingdaily') {
            $curDateStart = strtotime(date("Y-m-d", time()));
            $ongoingData = 1483200000;//2017-09-30 00:00:00 new plate first trade created time
            while ($ongoingData < $curDateStart) {
                $filter = array(
                    'time_start' => $ongoingData,
                    'time_end' => $ongoingData + 86399,
                );
                kernel::single('sysclearing_tasks_billingdaily')->exec($filter);
                $ongoingData += 86400;
            }
            exit('收入成本日报表整理完毕');
            die;
//######按照确认收货和确认退款时间出账#######################################################################
        } elseif ($method == 'settle') {
            //2017-10-29 19:54 平台尚未产生退款，只有一单实物,
            $settleFilter = array(
                'pay_time|than' => 0,
                'end_time|than' => 0
            );
            $tids = app::get('systrade')->model('trade')->getList('tid', $settleFilter);

            foreach ($tids as $tid) {
                $isClearing = app::get('systrade')->rpcCall('clearing.detail.add', $tid);
            }


            $refunds = app::get('ectools')->model('refunds')->getList('*', array('oid|noequal' => null, 'status' => 'succ'));

            foreach ($refunds as $refund) {

                unset($tradeData);

                $tradeData['orders'][0]['tid'] = $refund['tid'];
                $tradeData['orders'][0]['oid'] = $refund['oid'];

                $afFilter['refunds_id'] = $refund['refunds_id'];
                $afrefundinfo = app::get('sysaftersales')->model('refunds')->getRow('refunds_type', $afFilter);
                if ($afrefundinfo['refunds_type'] == 0) {
                    $re = kernel::single('sysclearing_settle')->generate($tradeData, '3');
                } elseif ($afrefundinfo['refunds_type'] == 2) {
                    $re = kernel::single('sysclearing_settle')->generate($tradeData, '4');
                }
            }
            exit('店铺供应商结算明细整理完毕');
            die;

        } elseif ($method == 'settledaily') {
            $curDateStart = strtotime(date("Y-m-d", time()));
            $ongoingData = 1483200000;//2017-09-30 00:00:00 new plate first trade created time
            while ($ongoingData < $curDateStart) {
                $filter = array(
                    'time_start' => $ongoingData,
                    'time_end' => $ongoingData + 86399,
                );
                kernel::single('sysclearing_tasks_settledaily')->exec($filter);
                $ongoingData += 86400;
            }
            exit('店铺供应商结算日报表整理完毕');
            die;

//######按照发货时间统计商品销售出库明细#####################################################
        } elseif ($method == 'itemsettle') {//以商品和日期为单位统计出库
            $curDateStart = strtotime(date("Y-m-d", time()));
            $ongoingData = 1478448000;
            while ($ongoingData < $curDateStart) {
                $params = array(
                    'time_start' => $ongoingData,
                    'time_end' => $ongoingData + 86399,
                    'time_insert' => $ongoingData,
                );
                kernel::single('sysstat_tasks_statistic_itemdetail')->exec($params);
                $ongoingData += 86400;
            }
            exit('整理统计数据完毕');
            die;
        } elseif ($method == 'itemsettledetail') {//以商品为单位统计出库
            $curDateStart = strtotime(date("Y-m-d", time()));
            $ongoingData = 1478448000;
            while ($ongoingData < $curDateStart) {
                $params = array(
                    'time_start' => $ongoingData,
                    'time_end' => $ongoingData + 86399,
                    'time_insert' => $ongoingData,
                );
                //kernel::single('sysstat_tasks_statistic_itemdetail')->exec($params);
                $ongoingData += 86400;
            }
            exit('整理统计数据完毕');
            die;
//######金蝶凭证处理########################################################################
        } elseif ($method == 'kingdee_trade') {
            $fields = "tid,shop_id,pay_type,trade_from,created_time,pay_time,receiver_name,receiver_mobile,receiver_address,orders.*";
            $tradeInfo = app::get('sysclearing')->rpcCall('trade.get',array('tid' => '2532573000159521','fields' => $fields));
            $tradeInfo['push_type'] = 'SEND_GOODS';
            $trade_result = kernel::single('sysclearing_kingdeetrade')->generate($tradeInfo);
            exit('金蝶销售出库单生成处理完毕');
            die;
        }elseif ($method == 'kingdee_refund') {
            $tid = '2532784000089559';
            $tid = '2537746000030087';
//            $filter['push_status'] = 'SAVE_FALSE';
            if($tid > 0)
            {
                $filter['tid'] = $tid;
            }
            $kingdeeTradeModel = app::get('systrade')->model('kingdee_trade');
            $save_false_info = $kingdeeTradeModel->getList('*',$filter);
            foreach($save_false_info as $info)
            {
                if($info['push_type'] === 'SEND_GOODS' || $info['push_type'] === 'EXCHANGING_SEND_GOODS')
                {
                    continue;
                }

                $tid = $info['tid'];
                $push_type = $info['push_type'];
                $oid = $info['oid'];
                $fields = "tid,shop_id,pay_type,trade_from,created_time,receiver_name,receiver_mobile,receiver_address,orders.*";
                $refund_filter = ['tid' => $tid,'fields' => $fields];
                if($oid) $refund_filter['oid'] = $oid;

                $refund_data = app::get('sysaftersales')->rpcCall('trade.get',$refund_filter);

                if($push_type === 'EXCHANGING_GOODS')
                {
                    $return_type = 'EXCHANGING_GOODS';
                }
                else
                {
                    $return_type = 'REFUND_GOODS';
                }

                $refund_data['refund_time']  = time();
                $refund_data['return_type']  = $return_type;
                $refund_data['push_type']    = $push_type;
                $refund_data['shop_id']      = $info['shop_id'];
                $refund_data['oid']          = $oid;

                kernel::single('sysclearing_kingdeerefund')->generate($refund_data);
            }
            exit('金蝶销售退货单生成处理完毕');
            die;
        } elseif ($method == 'kingdee_repush') {
            $curDateStart = time();
            $ongoingData = 1566835200;
            $filter['created_time|bthan'] = $ongoingData;
            $filter['created_time|lthan'] = $curDateStart;
            $filter['push_status'] = 'SAVE_FALSE';
            $kingdeeTradeModel = app::get('systrade')->model('kingdee_trade');
            $save_false_info = $kingdeeTradeModel->getList('*',$filter);
            foreach($save_false_info as $info)
            {
                if(in_array($info['push_type'],['SEND_GOODS','EXCHANGING_SEND_GOODS']))
                {
                    $fields = "tid,shop_id,pay_type,trade_from,created_time,pay_time,receiver_name,receiver_mobile,receiver_address,orders.*";
                    $tradeInfo = app::get('sysclearing')->rpcCall('trade.get',array('tid' => $info['tid'],'fields' => $fields));
                    $tradeInfo['push_type'] = $info['push_type'];
                    $tradeInfo['primary_id'] = $info['id'];
                    $tradeInfo['repush_time'] = $info['created_time'];
                    kernel::single('sysclearing_kingdeetrade')->generate($tradeInfo);
                }
                else
                {
                    $tid = $info['tid'];
                    $push_type = $info['push_type'];
                    $oid = $info['oid'];
                    $fields = "tid,shop_id,pay_type,trade_from,created_time,receiver_name,receiver_mobile,receiver_address,orders.*";
                    $refund_filter = ['tid' => $tid,'fields' => $fields];
                    if($oid) $refund_filter['oid'] = $oid;

                    $refund_data = app::get('sysaftersales')->rpcCall('trade.get',$refund_filter);

                    if($push_type === 'EXCHANGING_GOODS')
                    {
                        $return_type = 'EXCHANGING_GOODS';
                    }
                    else
                    {
                        $return_type = 'REFUND_GOODS';
                    }

                    $refund_data['refund_time']  = $info['created_time'];
                    $refund_data['return_type']  = $return_type;
                    $refund_data['push_type']    = $push_type;
                    $refund_data['shop_id']      = $info['shop_id'];
                    $refund_data['oid']          = $oid;
                    $refund_data['primary_id']   = $info['id'];

                    kernel::single('sysclearing_kingdeerefund')->generate($refund_data);
                }
            }
            exit('失败的金蝶销售出库及退货单生成处理完毕');
            die;
        } elseif ($method == 'kingdeedata') {
            $curDateStart = strtotime(date("Y-m-d", time()));
            //$ongoingData=1509465600;//2017-11-01 00:00:00
            //$ongoingData=1512057600;//2017-12-01 00:00:00
            //$ongoingData=1513785600;//2017-12-01 00:00:00
            $ongoingData = 1548691200;//2018-5-25 00:00:00
            while ($ongoingData < $curDateStart) {
                $filter = array(
                    'time_start' => $ongoingData,
                    'time_end' => $ongoingData + 86399,
                );
                kernel::single('sysclearing_tasks_kingdeedata')->exec($filter);
                $ongoingData += 86400;
            }
            exit('金蝶凭证数据整理完毕');
            die;
        } elseif ($method == 'kingdeedatabyshopid') {
            $curDateStart = strtotime(date("Y-m-d", time()));
            $ongoingData = 1557158400;
            $source_house = input::get('source_house');

            while ($ongoingData < $curDateStart) {
                $filter = array(
                    'time_start' => $ongoingData,
                    'time_end' => $ongoingData + 86399,
                    'shop_id' => '38',
                );
                if($source_house) {
                    $filter['deal_type'] = 'agency';
                    $filter['source_house'] = $source_house;
                }
                kernel::single('sysclearing_tasks_kingdeedata')->exec($filter);
                $ongoingData += 86400;
            }
            exit('金蝶凭证数据整理完毕');
            die;
        }elseif ($method == 'kingdeedatabysupplier_type') {
            $start_time = input::get('start_time');
            $end_time = input::get('end_time');
            $supplier_type = input::get('supplier_type', 3);
            $supplier_id = input::get('supplier_id', 38);
            while ($start_time < $end_time) {
                $filter = array(
                    'time_start' => $start_time,
                    'time_end' => $start_time + 86399,
                    'supplier_type' => $supplier_type,
                    'supplier_id' => $supplier_id,
                );
                kernel::single('sysclearing_tasks_kingdeedata')->exec($filter);
                $start_time += 86400;
            }
            exit('金蝶凭证数据整理完毕');
            die;
        }
        elseif ($method == 'kingdee') {

            $ongoingData = input::get('start_time');
            $curDateStart = input::get('end_time');
            while ($ongoingData < $curDateStart) {
                $filter['time'] = $ongoingData;
                kernel::single('sysclearing_tasks_kingdee')->exec($filter);
                $ongoingData += 86400;
            }
            exit('金蝶凭证推送处理完毕');
            die;
        } elseif ($method == 'kingdee_invoice') {
            $trade_result = kernel::single('sysclearing_kingdeeinvoice')->invalidInvoice('2532403000080080');
//            $trade_result = kernel::single('sysclearing_kingdeeinvoice')->push();
            die;
        }
        elseif ($method == 'butterfly') {

            kernel::single('sysclearing_kingdee')->pushButterFly();
            exit('金蝴蝶凭证生成处理完毕');
            die;

//######工具########################################################################
        } elseif ($method == 'tradeconfirm') {//执行确认收货

            /*$params['tid'] = '1709291633105851';
            $params['user_id'] = 5851;
            try{
                app::get('topm')->rpcCall('trade.confirm',$params,'buyer');
            }catch(Exception $e){
                $msg = $e->getMessage();
                var_dump($msg);
            }*/
            exit('执行确认收货完毕');
            die;

        } elseif ($method == 'tool') {//测试工具
            $start_time = 1509465600;
            $count = 0;
            $mdl = app::get('sysclearing')->model('withdraw');
            while ($count < 20) {
                unset($data);
                $data['create_time'] = $start_time + 3600 * 24 * 16;
                $data['start_time'] = $start_time;
                $data['end_time'] = $start_time;
                +3600 * 24 * 15;
                $data['shop_id'] = 7;
                $data['comment'] = 'apply for new withdrawing';
                $data['money'] = rand(1, 99) * 2342;
                $data['status'] = 1;
                try {
                    $re = $mdl->save($data);
                } catch (Exception $e) {
                    print_r($data);
                    echo '<hr/>';
                    print_r($e->getMessage());
                    echo '<hr/>';
                    echo '<hr/>';
                    continue;
                }
                $count++;
                $start_time += 3600 * 24 * 16;
            }
            exit('操作执行完毕');
            die;
        }elseif($method=='3rd') {
            //2017-11-21当天起，银联商务支付手续费用为千分之6
            $curDateStart = strtotime(date("Y-m-d", time()));
            $ongoingData = 1514908800;// date('Y-m-d H:i:s',1511193600)=2017-11-21 00:00:00
            while ($ongoingData < (1514908800 + 86399)) {
                $filter = array(
                    'time_start' => $ongoingData,
                    'time_end' => $ongoingData + 86399,
                );
                kernel::single('sysclearing_tasks_billing')->exec($filter);
                $ongoingData += 86400;
            }
            exit('收入成本明细整理完毕');
            die;
        }elseif($method=='vote') {
            kernel::single('sysactivityvote_vote_auto')->autoVote();
            exit();
            die;
        }elseif($method=='offline') {
            kernel::single('sysclearing_tasks_offlinedaily')->exec();
            exit('线下收款分账日报处理完成');
            die;
        }elseif($method=='getsql'){
            $qb = app::get('sysclearing')->database()->createQueryBuilder();
            $billingDaily=$qb->select('shop_id,shop_type,account_type,accounting_type,supplier_fee_from,supplier_id,supplier_type,incoming_type,tax_rate,sum(payment) as payment,sum(service_charge) as service_charge,sum(incoming) as incoming,sum(platform_fee) as platform_fee,sum(shop_fee) as shop_fee,sum(platform_service_fee) as platform_service_fee,sum(supplier_fee) as supplier_fee,sum(real_supplier_fee) as real_supplier_fee')
                ->from('sysclearing_settlement_billing_detail')
                ->where('trade_time >='.time())
                ->andWhere('trade_time <='.time())
                ->groupBy("CONCAT(shop_id,supplier_id,incoming_type,tax_rate)")
                ->getSql();
            echo $billingDaily;
        }elseif($method=='tmpdaily'){
            //kernel::single('sysclearing_tasks_billingdaily')->exec();
            //kernel::single('sysclearing_tasks_settledaily')->exec();
            kernel::single('sysclearing_tasks_kingdeedata')->exec();
        }
        elseif($method == 'tempCreateVoucher')//生成核销券
        {
//            $tids = ['2586354000080827'];
//            $temp_voucher_model = kernel::single('systrade_api_trade_payFinish');
//            foreach($tids as $tid)
//            {
//                $temp_voucher_model->createOfflineVoucher($tid);
//            }
        }
        elseif ($method == 'shopPerDay') {
            $curDateStart = strtotime(date("Y-m-d", time()));
            $ongoingData = 1525795200;// date('Y-m-d H:i:s',1511193600)=2017-11-21 00:00:00
            while ($ongoingData < $curDateStart) {
                $filter = array(
                    'time_start' => $ongoingData,
                    'time_end' => $ongoingData + 86399,
                    'time_insert'=> $ongoingData,
                );
                kernel::single('sysstat_shop_taskdata')->exec($filter);
                $ongoingData += 86400;
            }
            exit('分析更新商家每天数据完成');
            die;
        }
        elseif ($method == 'outStoreDetailPerDay') {
            $curDateStart = strtotime(date("Y-m-d", time()));
            $ongoingData = 1526918400;//
            while ($ongoingData < $curDateStart) {
                $filter = array(
                    'time_start' => $ongoingData,
                    'time_end' => $ongoingData + 86399,
                    'time_insert'=> $ongoingData,
                );
                kernel::single('sysstat_shop_taskitem')->getDeliverDetail($filter);
                $ongoingData += 86400;
            }
            exit('商品出库明细完毕');
            die;
        }
        elseif ($method == 'outStorePerDay') {
            $curDateStart = strtotime(date("Y-m-d", time()));
            $ongoingData = 1526659200;//
            while ($ongoingData < $curDateStart) {
                $filter = array(
                    'time_start' => $ongoingData,
                    'time_end' => $ongoingData + 86399,
                    'time_insert'=> $ongoingData,
                );
                kernel::single('sysstat_shop_taskitem')->exec($filter);
                $ongoingData += 86400;
            }
            exit('商品出库分析完毕');
            die;
        }
        elseif($method == 'trade_event_close')
        {
            return;
            $tids = [];
            $data['push_type'] = 'CANCEL_GOODS_BEFORE_PAY';
            foreach($tids as $tid)
            {
                kernel::single('sysaftersales_events_listeners_kingdeeRefund')->handle($tid,'38',null,$data);
            }
            die;
            $test = ['sdfs'=>'4554644'];
            unset($test['sdfs']);
            print_r($test);die;
        }elseif($method == 'deliverAfter'){
            $shop_id = input::get('shop_id');
            $logi_no = input::get('logi_no');
            $logistics_plug = kernel::single('syslogistics_logistics_logistics', $shop_id);

            $aggregation = app::get('syslogistics')->model('delivery_aggregation')->getRow('status, delivery_type, tids, shop_id, init_shop_id, corp_code', ['logi_no' => $logi_no, 'shop_id' => $shop_id]);
            $params = [
                'memo' => "程序自动处理货到付款订单",
                'op_time' => time(),
                'tids' => $aggregation['tids'],
                'init_shop_id' => $aggregation['init_shop_id'],
            ];

            if($aggregation['delivery_type'] == 'SEND_GOODS'){
                // 确认投递后操作
                $logistics_plug->sendGoodsDeliverAfter($aggregation['corp_code'], $logi_no, 'succ', $params);
                // 售后换货单以送货完成为标准
            }elseif($aggregation['delivery_type'] == 'EXCHANGING_GOODS'){
                // 确认投递后操作
                $logistics_plug->exchangingGoodsDeliverAfter($aggregation['corp_code'], $logi_no, 'succ', $params);
            }elseif($aggregation['delivery_type'] == 'REFUND_GOODS'){
                $logistics_plug->refundGoodsDeliverAfter($aggregation['corp_code'], $logi_no, 'succ', $params);
            }
        } elseif($method === 'get_supplier_sn') {
            $kingdeeSupplierModel = kernel::single('sysclearing_kingdeeSupplier');
            $supplier_params['FUseOrgId'] = 227509;
            $supplier_params['FNumber'] = 'DSGW00035';
            $supplier_query_res = $kingdeeSupplierModel->queryInfo($supplier_params);
            dump($supplier_query_res);die;
        } elseif($method === 'kingdee_test') {
            $kingdeeBaseModel = kernel::single('sysclearing_kingdeebase');
            $document_type = 'BD_STOCK';
            $data['fields']    = 'FName,FNumber,FStockId';//因金蝶返回信息没有对应字段，因此字段顺序不能改变 否则会造成数据出错
            $data['order_by']  = '';
            $data['top_row_count'] = 0;
            $data['start_row']     = 0;
            $data['limit']        = 0;

//            $document_type = 'STK_Inventory';
//            $data['fields']    = 'FMaterialId,FBaseQty,FStockId';//因金蝶返回信息没有对应字段，因此字段顺序不能改变 否则会造成数据出错
//            $data['order_by']  = '';
//            $data['top_row_count'] = 0;
//            $data['start_row']     = 0;
//            $data['limit']        = 0;
//            $data['filter']    = 'FStockId in(252983,248465)';

            $data_model = $kingdeeBaseModel->getDocumentInquiryModel($data, $document_type);
            $return_data = $kingdeeBaseModel->kingdeeQuery($data_model);
            echo "<pre>";
            print_r($return_data);die;
        } elseif($method === '') {
            try {
//                $trade_data = app::get('systrade')->model('trade')->getRow('*', ['tid'=>'2563507000070080']);
//                unset($trade_data['tid']);
//                $order_data = app::get('systrade')->model('order')->getRow('*', ['tid'=>'2563507000070080']);
//                unset($order_data['tid']);
//                unset($order_data['oid']);
//                $tradeNo = kernel::single('systrade_data_trade_create');
//                for($i=1;$i<=467;$i++) {
//                    $insert_id = 0;
//                    $tid = $tradeNo->genId(1);
//                    $trade_data['tid'] = $tid;
//                    $insert_id = app::get('systrade')->model('trade')->insert($trade_data);
//                    $oid = $tradeNo->genId(1, false);
//                    $order_data['tid'] = $insert_id;
//                    $order_data['oid'] = $oid;
//                    app::get('systrade')->model('order')->insert($order_data);
//                }
//                echo '插入数据完成';die;


                $trade_list = app::get('systrade')->model('trade')->getList('tid, shop_id, payment',['created_time' => '1568608861']);
                foreach($trade_list as $list) {
                    if($list['shop_id'] != 14 || $list['payment'] != 0.1) {
                        continue;
                    }
                    $test_tid = $list['tid'];
                    $invoice_data['invoice_type'] = 'normal';
                    $invoice_data['tid'] = $test_tid;
                    $invoice_data['invoice_name'] = '杨鑫';
                    $invoice_data['contact_way'] = '18561863696';
                    $invoice_data['registration_number'] = '370782199511110058';
                    $invoice_result = kernel::single('sysclearing_kingdeeinvoice')->createInvoice($invoice_data);
                    app::get('systrade')->model('trade')->update(['created_time' => 1], ['tid' => $test_tid]);
                }
            } catch(Exception $e) {
                echo $e->getMessage();
            }
            die;
        }
    }

}
