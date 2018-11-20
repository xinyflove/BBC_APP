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
            $curDateStart = 1542124801;
            $ongoingData = 1542124800;// date('Y-m-d H:i:s',1511193600)=2017-11-21 00:00:00
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
            $fields = "tid,shop_id,pay_type,created_time,pay_time,receiver_name,receiver_mobile,receiver_address,orders.*";
            $tradeInfo = app::get('sysclearing')->rpcCall('trade.get',array('tid' => '2513744000030080','fields' => $fields));
            $tradeInfo['push_type'] = 'SEND_GOODS';
            $trade_result = kernel::single('sysclearing_kingdeetrade')->generate($tradeInfo);
            exit('金蝶销售出库单生成处理完毕');
            die;
        }elseif ($method == 'kingdeedata') {
            $curDateStart = strtotime(date("Y-m-d", time()));
            //$ongoingData=1509465600;//2017-11-01 00:00:00
            //$ongoingData=1512057600;//2017-12-01 00:00:00
            //$ongoingData=1513785600;//2017-12-01 00:00:00
            $ongoingData = 1539100800;//2018-5-25 00:00:00
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
            $curDateStart = 1542124801;
            $ongoingData = 1533052800;
            while ($ongoingData < $curDateStart) {
                $filter = array(
                    'time_start' => $ongoingData,
                    'time_end' => $ongoingData + 86399,
                    'shop_id' => '38',
                );
                kernel::single('sysclearing_tasks_kingdeedata')->exec($filter);
                $ongoingData += 86400;
            }
            exit('金蝶凭证数据整理完毕');
            die;
        }
        elseif ($method == 'kingdee') {

            $curDateStart = strtotime(date("Y-m-d", time()));
            //$ongoingData = 1509465600;//2017-10-30
            $ongoingData = 1542124800;//2018-11-14
            while ($ongoingData < $curDateStart) {
                $filter['time'] = $ongoingData;
                kernel::single('sysclearing_tasks_kingdee')->exec($filter);
                $ongoingData += 86400;
            }
            exit('金蝶凭证生成处理完毕');
            die;

        } elseif ($method == 'butterfly') {

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
//            $tids = ['2314770000215910','2314771000124571'];
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
    }

}
