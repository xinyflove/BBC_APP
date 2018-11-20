<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class topshop_ctl_exporttrade extends topshop_controller{
    /**
     * 订单导出模态页面
     * @return mixed
     */
    public function view()
    {
        //导出方式 直接导出还是通过队列导出
        $pagedata['check_policy'] = 'download';
        $filetype = array(
            //'csv'=>'.csv',
            'xls'=>'.xls',
        );
        $pagedata['model'] = input::get('model');
        $pagedata['app'] = input::get('app');
        $pagedata['orderBy'] = input::get('orderBy');
        $supportType = input::get('supportType');
        //支持导出类型
        if( $supportType && $filetype[$supportType] )
        {
            $pagedata['export_type'] = array($supportType=>$filetype[$supportType]);
        }
        else
        {
            $pagedata['export_type'] = $filetype;
        }
        return view::make('topshop/exporttrade/export.html', $pagedata);
    }
    /**
     * 子订单导出模态页面（用于发货订单导出）
     * @return mixed
     */
    public function vieworder()
    {
        //导出方式 直接导出还是通过队列导出
        $pagedata['check_policy'] = 'download';
        $filetype = array(
            //'csv'=>'.csv',
            'xls'=>'.xls',
        );
        $pagedata['model'] = input::get('model');
        $pagedata['app'] = input::get('app');
        $pagedata['orderBy'] = input::get('orderBy');
        $supportType = input::get('supportType');
        //支持导出类型
        if( $supportType && $filetype[$supportType] )
        {
            $pagedata['export_type'] = array($supportType=>$filetype[$supportType]);
        }
        else
        {
            $pagedata['export_type'] = $filetype;
        }
        return view::make('topshop/exporttrade/exportorder.html', $pagedata);
    }
    /**
     * 主订单导出
     * 系统原有import/export导出方法
     * @return mixed
     */
    public function export()
    {
        //导出
        if( input::get('filter') )
        {
            $filter = json_decode(input::get('filter'),true,512,JSON_BIGINT_AS_STRING);
        }

        $filter=$this->_checkParams($filter);
        //批量处理其他参数，关键词存在时仅仅处理status和tid
        foreach($filter as $k=>$val)
        {
            if(is_null($val))
            {
                unset($filter[$k]);
                continue;
            }
            if($k == "status" || $k == "tid")
            {
                $filter[$k] = explode(',',$val);
            }
        }

        //关键词查询
        if($keyword = trim($filter['keyword'])){
            // $keyword = trim($filter['keyword']);
            unset($filter['keyword']);
            $listsBuilder=db::connection()->createQueryBuilder();
            $tradeLists = $listsBuilder->select('distinct(a.tid) as tid')
                ->from('systrade_trade', 'a')
                ->join('a', 'systrade_order', 'b', 'b.tid = a.tid')
                ->where('b.title  like "%'.$keyword.'%"')
                ->execute()->fetchAll();
            $filter['tid'] = array_column($tradeLists, 'tid');
        }

        //开始时间点
        if( $filter['created_time_start'] )
        {
            $filter['created_time|bthan'] = $filter['created_time_start'];
            unset($filter['create_timed_start']);
        }
        //结束时间点`
        if( $filter['created_time_end'] )
        {
            $filter['created_time|lthan'] = $filter['created_time_end'];
            unset($filter['created_time_end']);
        }
        //根据用户名获取用户 user_id列表
        if($filter['user_name'])
        {
            $userIds = app::get('systrade')->rpcCall('user.get.account.id',['user_name'=>$filter['user_name']]);
            unset($filter['user_name']);
            if($userIds and is_array($userIds)){
                $filter['user_id'] = $userIds;
            }else{
                $filter['user_id'] = 0;
            }
        }
        //供应商查询
        if(empty($filter['supplier_id']) && $filter['supplier_id']!==0){
            unset($filter['supplier_id']);
        }
        //处理虚拟商品字段
        if(isset($filter['is_virtual'])) {
            switch ($filter['is_virtual']) {
                case '0':
                    unset($filter['is_virtual']);
                    break;//全部
                case '1':
                    $filter['is_virtual'] = 0;
                    break;//实物
                case '2':
                    $filter['is_virtual'] = 1;
                    break;//虚拟
                default:
                    unset($filter['is_virtual']);
            }
        }
        $orderBy = str_replace(';', '', input::get('orderBy'));
        $orderBy = str_replace('\'', '', $orderBy);
        $permission = [
            'systrade' =>['trade','order'],
            'sysclearing' =>['settlement','settlement_detail'],
            'sysstat'=>['trade_statics','item_statics']
        ];
        $app = input::get('app',false);
        $model = input::get('model',false);
        if( input::get('name') && $app && $model && $permission[$app] && in_array($model,$permission[$app]) )
        {
            $this->sellerlog('导出操作。对应导出model '.$model);
            $model = $app.'_mdl_'.$model;
            $filter['shop_id'] = shopAuth::getShopId();
            try {
                kernel::single('importexport_export')->fileDownload(input::get('filetype'), $model, input::get('name'), $filter,$orderBy);
            }
            catch (Exception $e)
            {
                return response::make('导出参数错误', 503);
            }
        }
        else
        {
            return response::make('导出参数错误', 503);
        }
    }
    /**
     * 校验和组装参数，适用model->getList()方法的参数形式
     * @param $filter
     * @return mixed
     */
    private function _checkParams($filter)
    {
        $statusLUT = array(
            '1' => 'WAIT_BUYER_PAY',
            '2' => 'WAIT_SELLER_SEND_GOODS',
            '3' => 'WAIT_BUYER_CONFIRM_GOODS',
            '4' => 'TRADE_FINISHED',
            '5' => array('TRADE_CLOSED','TRADE_CLOSED_BY_SYSTEM','WRITE_FINISHED'),
            '8' => 'WAIT_WRITE_OFF',
            '9' => 'WRITE_PARTIAL',
            '10' => 'WRITE_FINISHED'
        );
        foreach($filter as $key=>$value)
        {
            if(!$value) unset($filter[$key]);
            if($key == 'create_time')
            {
                $times = array_filter(explode('-',$value));
                if($times)
                {
                    $filter['created_time_start'] = strtotime($times['0']);
                    $filter['created_time_end'] = strtotime($times['1'])+86400;
                    unset($filter['create_time']);
                }
            }
            if($key=='status' && $value)
            {
                if(in_array($value,array_keys($statusLUT)))
                {
                    $filter['status'] = $statusLUT[$value];
                }
                else
                {
                    if($value == 6)
                    {
                        $filter['pay_type'] = 'offline';
                    }
                    if($value == 7)
                    {
                        $filter['shipping_type'] = 'ziti';
                    }
                    unset($filter['status']);
                }
            }
        }
        return $filter;
    }
    /**
     * 子订单导出页面，vieworder中form的提交地址
     * @return mixed
     */
    public function exportOrder(){
        $pagedata['progress'] = array(
            '0' => app::get('topshop')->_('待处理'),
            '1' => app::get('topshop')->_('待回寄'),
            '2' => app::get('topshop')->_('待确认收货'),
            '4' => app::get('topshop')->_('商家已处理'),//换货的时候可以直接在商家处理结束
            '3' => app::get('topshop')->_('商家已驳回'),
            '5' => app::get('topshop')->_('商家已收货'),
            '7' => app::get('topshop')->_('已退款'),//退款，退货则需要平台确实退款
            '6' => app::get('topshop')->_('已驳回'),
            '8' => app::get('topshop')->_('待退款'),
            '9' => app::get('topshop')->_('换货中'),
        );
        $tradeStatus = array(
            'WAIT_BUYER_PAY' => '未付款',
            'WAIT_SELLER_SEND_GOODS' => '已付款，请发货',
            'WAIT_BUYER_CONFIRM_GOODS' => '已发货，等待确认',
            'TRADE_FINISHED' => '已完成',
            'TRADE_CLOSED' => '已关闭',
            'TRADE_CLOSED_BY_SYSTEM' => '已关闭',
            'WAIT_WRITE_OFF'=>'待核销',
            'WRITE_PARTIAL'=>'部分核销',
            'WRITE_FINISHED'=>'全部已核销'
        );
        $this->contentHeaderTitle = app::get('topshop')->_('订单查询');
        $postFilter = json_decode(input::get('filter'),true,512,JSON_BIGINT_AS_STRING);
        foreach($postFilter as $k => $v){
            if(!is_array($v)){
                $postFilter[$k]=trim($v);
            }

        }
        $filter = $this->_checkParams($postFilter);
        if($filter['status']=='WAIT_SELLER_SEND_GOODS'){
            $filter['status']=array('WAIT_SELLER_SEND_GOODS','PARTIAL_SHIPMENT');
        }
        $status = $filter['status'];

        if(is_array($filter['status']))
        {
            $status = implode(',',$filter['status']);
        }
        if($postFilter['tid|in']){
            $params['tid']=implode(',',$postFilter['tid|in']);
        }else{
            $params = array(
                'status' => $status,
                'tid' => $filter['tid'],
                'create_time_start' =>$filter['created_time_start'],
                'create_time_end' =>$filter['created_time_end'],
                'receiver_mobile' =>$filter['receiver_mobile'],
                'receiver_phone' =>$filter['receiver_phone'],
                'receiver_name' =>$filter['receiver_name'],
                'user_name' =>$filter['user_name'],
                'pay_type' =>$filter['pay_type'],
                'shipping_type' =>$filter['shipping_type'],
                'order_by' =>'created_time desc',
                'keyword'=>$filter['keyword'],
                'supplier_id'=>$filter['supplier_id'],
                'is_virtual'=>$filter['is_virtual'],
                'fields' =>'order.spec_nature_info,shipping_type,tid,shop_id,user_id,status,payment,points_fee,total_fee,post_fee,payed_fee,receiver_name,trade_memo,created_time,receiver_mobile,discount_fee,adjust_fee,order.title,order.price,order.num,order.pic_path,order.tid,order.oid,order.item_id,need_invoice,invoice_name,invoice_type,invoice_main,pay_type,cancel_status,receiver_idcard,order.is_virtual,order.order_from,logistics.corp_code,logistics.logi_no,logistics.logi_name,logistics.delivery_id,logistics.receiver_name,logistics.t_begin',
            );
        }

        //显示订单售后状态
        $params['is_aftersale'] = true;
        $params['shop_id'] = $this->shopId;
        $orderList = app::get('topshop')->rpcCall('trade.get.orderlist',$params,'seller');
        $exportData=$this->_getExportData($orderList['list']);
        // jj($exportData);
        $order_desc = input::get('name');
        $export_time = date('YmdHis',time());
        $this->_exportOrderDataToExcel($export_time.'子订单（'.$order_desc.'）',$exportData);
    }
    /**
     * 整理导出数据
     * @param $data
     * @return array
     */
    private function _getExportData($data){
        $exportData=array();
        foreach($data as $k => $v){
            $curRow['tid']=$v['tid'];
            $curRow['oid']=$v['oid'];
            $curRow['cat_id']=$this->_getCatName($v['cat_id']);
            $curRow['shop_id']=$this->_getShopName($v['shop_id']);
            $curRow['user_id']=$this->_getUserName($v['user_id']);
            $curRow['title']=$v['title'];
            $curRow['spec_nature_info']=$v['spec_nature_info'];
            $curRow['price']=$v['price'];
            $curRow['num']=$v['num'];
            $curRow['sendnum']=$v['sendnum'];
            $curRow['created_time']=$this->_dateFormat($v['trade']['created_time']);
            $curRow['pay_time']=$this->_dateFormat($v['pay_time']);
            $curRow['consign_time']=$this->_dateFormat($v['consign_time']);
            $curRow['shipping_type']=$v['shipping_type'];
            $curRow['logistics_company']=$v['logistics'] ? implode(',', array_column($v['logistics'], 'logi_name')) : '';
            $curRow['logi_no']=$v['logistics'] ? implode(',', array_column($v['logistics'], 'logi_no')) : '';
            $curRow['total_fee']=$v['total_fee'];
            $curRow['payment']=$v['payment'];
            $curRow['points_fee']=$v['points_fee'];
            $curRow['post_fee']=$v['trade']['post_fee'];
            $curRow['consume_point_fee']=$v['consume_point_fee'];
            $curRow['status']=$this->_getTradeStatus($v);
            $curRow['cancel_status']=$this->_getCancelStatus($v['trade']['cancel_status']);
            $curRow['cancel_reason']=$v['trade']['cancel_reason'];
            $curRow['aftersales_status']=$this->_getAfterSaleStatus($v['aftersales_status']);
            $curRow['share_user_id']=$v['share_user_id'];
            $curRow['dlytmpl_id']=$v['trade']['dlytmpl_id'];
            $curRow['receiver_name']=$v['trade']['receiver_name'];
            $curRow['receiver_state']=$v['trade']['receiver_state'];
            $curRow['receiver_city']=$v['trade']['receiver_city'];
            $curRow['receiver_district']=$v['trade']['receiver_district'];
            $curRow['receiver_address']=$v['trade']['receiver_address'];
            $curRow['receiver_zip']=$v['trade']['receiver_zip'];
            $curRow['receiver_mobile']=$v['trade']['receiver_mobile'];
            // $curRow['receiver_phone']=$v['trade']['receiver_phone'];
            // 座席号
            $curRow['seat']=$v['trade']['seat'];
            $curRow['is_virtual']=$this->__getIsVirtualSrc($v['is_virtual']);
            $curRow['is_cross']=$this->__getIsCross($v['is_cross']);
            $curRow['identity_card_number']=$this->__getID($v['is_cross'],$v['trade']['identity_card_number']);
            $curRow['shop_memo']=$v['trade']['shop_memo'];
            $curRow['trade_memo']=$v['trade']['trade_memo'];
            $curRow['payment_id']=$this->__getPaymentId($v['tid']);
            $curRow['refund_id']=$this->__getRefundId($v['oid'],$v['tid']);
            $curRow['barcode']=$this->__getSkuBarcode($v['sku_id']);
            $curRow['ziti_addr']=$v['trade']['ziti_addr'];
            $curRow['order_from']=$v['order_from'];
            $curRow['lijin_fee']=$v['lijin_fee'];
            $curRow['consume_lijin_fee']=$v['consume_lijin_fee'];
            $curRow['gift_data']=$this->_disposeGift($v);
            $curRow['push_logistics']=$v['push_logistics'];
            $exportData[]=$curRow;
            unset($curRow);
        }
        return $exportData;
    }
    /**
     * 导出子订单的数据到excel
     */
    private function _exportOrderDataToExcel($expTitle,$expTableData){
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $xlsTitle; //文件名称可根据自己情况设定
        $cellTitle=array('子订单编号', '订单编号', '下单时间', '付款时间', '商品条形码', '类目', '商品', '规格', '赠品', '购买数量', '商品价格', '实付金额', '收货人姓名','收货人手机号', '收货人邮编', '收货人所在省份','收货人所在城市','收货人所在地区','收货人详细地址', '订单来源', '运送方式', '快递公司', '快递单号', '发货数量', '发货时间', '运费', '物流状态', '子订单状态','退款状态','退款原因','售后状态', '商家', '买家', '身份证号', '应付金额', '优惠金额', '积分抵扣金额','买家使用积分', '礼金抵扣金额','买家使用礼金', '是否为虚拟商品', '是否为跨境商品', '买家留言', '卖家备注', '支付单号', '座席号', '退款款单号', '自提地址',);
        $objPHPExcel = new PHPExcel();
        $cellNum=count($cellTitle);//多少列
        $dataNum = count($expTableData);//多少行
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ','BA','BB','BC','BD','BE');
        //表格边框样式
        $color='0x00000000';
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => array('argb' => $color),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$cellName[$cellNum-1].($dataNum+1))->applyFromArray($styleArray);
        // $objPHPExcel->getActiveSheet()->getColumnDimension('AR')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(40);
        $objPHPExcel->getActiveSheet()->getColumnDimension('AA')->setWidth(30);

        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $cellTitle[$i]);
            //$objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $cellTitle[$i]);
        }
        $tid_array = [];
        $last_tid = $expTableData[0]['tid'];
        $agrb_key = true;
        for($i=0;$i<$dataNum;$i++){//多少行

            // 设置行颜色
            if($expTableData[$i]['tid'] != $last_tid){
                $last_tid = $expTableData[$i]['tid'];
                $agrb_key = $agrb_key ? false : true;
            }

            $argb = $agrb_key ? '00FFFFFF' : '00ADFF2F';

            $objPHPExcel->getActiveSheet()->getStyle('A'.($i+2) . ':' . 'AZ'.($i+2))->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $objPHPExcel->getActiveSheet()->getStyle('A'.($i+2) . ':' . 'AZ'.($i+2))->getFill()->getStartColor()->setARGB($argb);

            $objPHPExcel->getActiveSheet()->setCellValueExplicit('A'.($i+2),$expTableData[$i]['oid'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('B'.($i+2),$expTableData[$i]['tid'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+2),$expTableData[$i]['created_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+2),$expTableData[$i]['pay_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+2),$expTableData[$i]['barcode']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('F'.($i+2),$expTableData[$i]['cat_id']);

            $objPHPExcel->getActiveSheet(0)->setCellValue('G'.($i+2),$expTableData[$i]['title']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('H'.($i+2),$expTableData[$i]['spec_nature_info']);

            $objPHPExcel->getActiveSheet(0)->getStyle('I'.($i+2))->getAlignment()->setWrapText(true);
            // $objPHPExcel->getActiveSheet(0)->getColumnDimension('AR'.($i+2))->setAutoSize(true);
            $objPHPExcel->getActiveSheet(0)->setCellValue('I'.($i+2),$expTableData[$i]['gift_data']);

            $objPHPExcel->getActiveSheet(0)->setCellValue('J'.($i+2),$expTableData[$i]['num']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('K'.($i+2),$expTableData[$i]['price']);

            $objPHPExcel->getActiveSheet(0)->setCellValue('L'.($i+2),$expTableData[$i]['payment']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('M'.($i+2),$expTableData[$i]['receiver_name']);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('N'.($i+2),$expTableData[$i]['receiver_mobile'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('O'.($i+2),$expTableData[$i]['receiver_zip']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('P'.($i+2),$expTableData[$i]['receiver_state']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('Q'.($i+2),$expTableData[$i]['receiver_city']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('R'.($i+2),$expTableData[$i]['receiver_district']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('S'.($i+2),$expTableData[$i]['receiver_address']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('T'.($i+2),$expTableData[$i]['order_from']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('U'.($i+2),$expTableData[$i]['shipping_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('V'.($i+2),$expTableData[$i]['logistics_company']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('W'.($i+2),$expTableData[$i]['logi_no']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('X'.($i+2),$expTableData[$i]['sendnum']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('Y'.($i+2),$expTableData[$i]['consign_time']);
            if(in_array($expTableData[$i]['tid'], $tid_array))
            {
                $objPHPExcel->getActiveSheet(0)->mergeCells('Z'.($i+1).':Z'.($i+2));
            }
            else
            {
                $objPHPExcel->getActiveSheet()->setCellValueExplicit('Z'.($i+2),$expTableData[$i]['post_fee']);
            }
            $objPHPExcel->getActiveSheet(0)->setCellValue('AA'.($i+2),$expTableData[$i]['push_logistics']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('AB'.($i+2),$expTableData[$i]['status']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('AC'.($i+2),$expTableData[$i]['cancel_status']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('AD'.($i+2),$expTableData[$i]['cancel_reason']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('AE'.($i+2),$expTableData[$i]['aftersales_status']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('AF'.($i+2),$expTableData[$i]['shop_id']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('AG'.($i+2),$expTableData[$i]['user_id']);
            // $objPHPExcel->getActiveSheet(0)->setCellValue('AH'.($i+2),$expTableData[$i]['sku_id']);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('AH'.($i+2),$expTableData[$i]['identity_card_number'],PHPExcel_Cell_DataType::TYPE_STRING);

            $objPHPExcel->getActiveSheet(0)->setCellValue('AI'.($i+2),$expTableData[$i]['total_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('AJ'.($i+2),$expTableData[$i]['part_mjz_discount']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('AK'.($i+2),$expTableData[$i]['points_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('AL'.($i+2),$expTableData[$i]['consume_point_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('AM'.($i+2),$expTableData[$i]['lijin_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('AN'.($i+2),$expTableData[$i]['consume_lijin_fee']);
            // $objPHPExcel->getActiveSheet(0)->setCellValue('AO'.($i+2),$expTableData[$i]['share_user_id']);
            // $objPHPExcel->getActiveSheet(0)->setCellValue('AF'.($i+2),$expTableData[$i]['receiver_phone']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('AO'.($i+2),$expTableData[$i]['is_virtual']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('AP'.($i+2),$expTableData[$i]['is_cross']);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('AQ'.($i+2),$expTableData[$i]['shop_memo'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('AR'.($i+2),$expTableData[$i]['trade_memo'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('AS'.($i+2),$expTableData[$i]['payment_id'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('AT'.($i+2),$expTableData[$i]['seat'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('AU'.($i+2),$expTableData[$i]['refund_id'],PHPExcel_Cell_DataType::TYPE_STRING);

            $objPHPExcel->getActiveSheet(0)->setCellValue('AV'.($i+2),$expTableData[$i]['ziti_addr']);

            $tid_array[] = $expTableData[$i]['tid'];
        }
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    private function _disposeGift($order){
        $str = "";
        if(is_array($order['gift_data'])){
            foreach($order['gift_data'] as $gift){
                $str .= $gift['title'] . "\r\n" . '数量：' . $gift['gift_num'] . "\r\n";
            }
            // $str = substr($str, 0, -4);
        }
        return  $str;
    }
    /**
     * 获取分类名称
     * @param $catId
     * @return mixed|string
     */
    private function _getCatName($catId){
        $re=app::get('syscategory')->model('cat')->getRow('cat_name',array('cat_id'=>$catId));
        if(!empty($re) && is_array($re)){
            return $re['cat_name'];
        }else{
            return '';
        }
    }
    /**
     * @param $shopId
     * @return mixed|string
     */
    private function _getShopName($shopId){
        $re=app::get('sysshop')->model('shop')->getRow('shop_name',array('shop_id'=>$shopId));
        if(!empty($re) && is_array($re)){
            return $re['shop_name'];
        }else{
            return '';
        }
    }
    private function _getUserName($userId){
        $re=app::get('sysuser')->model('account')->getRow('mobile',array('user_id'=>$userId));
        if(!empty($re) && is_array($re)){
            return $re['mobile'];
        }else{
            return '';
        }
    }
    private function _dateFormat($timestamp){
        if(empty($timestamp)){
            return '';
        }else{
            return date('Y-m-d H:i:s',$timestamp);
        }
    }
    private function _getTradeStatus($order){
        switch ($order['status']){
            case 'WAIT_BUYER_PAY':$src='等待买家付款';break;
            case 'WAIT_SELLER_SEND_GOODS':$src='等待卖家发货,买家已付款';break;
            case 'WAIT_BUYER_CONFIRM_GOODS':$src='等待买家确认收货';break;
            case'TRADE_BUYER_SIGNED':$src='买家已签收,货到付款专用';break;
            case 'TRADE_FINISHED':$src='交易成功';break;
            case 'TRADE_CLOSED_AFTER_PAY':$src='付款以后,用户退款成功，交易自动关闭';break;
            case 'TRADE_CLOSED_BEFORE_PAY':$src='卖家或买家主动关闭交易';break;
            case 'WAIT_WRITE_OFF':$src='待核销';break;
            case 'WRITE_PARTIAL':$src='部分已经核销';break;
            case 'WRITE_FINISHED':$src='全部已核销';break;
            default:$src='无记录';
        }

        if($order['trade']['pay_type'] == 'offline'){
            if($order['status'] == 'WAIT_SELLER_SEND_GOODS'){
                $src = '未付款、等待发货（货到付款）';
            }elseif($order['status'] == 'WAIT_BUYER_CONFIRM_GOODS'){
                $src = '已发货、未付款（货到付款）';
            }
        }
        return $src;
    }
    /**
     * @desc 获取售后订单状态的屏显字符串
     * @param $afterSaleStatus
     * @return string
     * @author: wudi tvplaza
     * @date: 201708011859
     */
    private function _getAfterSaleStatus($afterSaleStatus){
        switch ($afterSaleStatus){
            case 'WAIT_SELLER_AGREE':$src='买家已经申请退款，等待卖家同意';
            case 'WAIT_BUYER_RETURN_GOODS':$src='卖家已经同意退款，等待买家退货';break;
            case 'WAIT_SELLER_CONFIRM_GOODS':$src='买家已经退货，等待卖家确认收货';break;
            case 'SUCCESS':$src='退款成功';break;
            case 'CLOSED':$src='退款关闭';break;
            case 'REFUNDING':$src='退款中';break;
            case 'SELLER_REFUSE_BUYER':$src='卖家拒绝退款';break;
            case 'SELLER_SEND_GOODS':$src='卖家已发货';break;
            default:$src='未申请售后';
        }
        return $src;
    }
    /**
     * @desc 获取取消订单的状态屏显信息
     * @param $cancelStatus
     * @return string
     * @author: wudi tvplaza
     * @date: 2017-09-29 21:07
     */
    private function _getCancelStatus($cancelStatus){
        switch ($cancelStatus){
            case 'NO_APPLY_CANCEL';$src='未申请';break;
            case'WAIT_PROCESS';$src='等待审核';break;
            case'REFUND_PROCESS';$src='退款处理';break;
            case'SUCCESS';$src='取消成功';break;
            case'FAILS';$src='取消失败';break;
            default:$src="无退款信息";
        }
        return $src;
    }
    /**
     * @desc 获取是否虚拟商品的屏显字符
     * @param $isVirtual
     * @return string
     * @author: wudi tvplaza
     * @date: 201708011856
     */
    private function __getIsVirtualSrc($isVirtual){
        switch($isVirtual){
            case 0:$src='实物';break;
            case 1:$src='虚拟';break;
            default:$src='-';
        }
        return $src;
    }
    private function __getIsCross($isCross){
        switch($isCross){
            case 0:$src='否';break;
            case 1:$src='是';break;
            default:$src='-';
        }
        return $src;
    }
    private function __getID($isCross,$id){
        switch($isCross){
            case 0:$src='';break;
            case 1:$src=$id;break;
            default:$src='-';
        }
        return $src;
    }

    private function __getPaymentId($tid){
        $payinfo=app::get('ectools')->model('trade_paybill')->getRow('payment_id',array('tid'=>$tid,'status'=>'succ'));
        return $payinfo['payment_id'];
    }

    private function __getRefundId($oid,$tid){
        $oidinfo=app::get('ectools')->model('refunds')->getRow('*',array('oid'=>$oid,'status'=>'succ'));
        if(empty($oidinfo)){
            $tidinfo=app::get('ectools')->model('refunds')->getRow('*',array('tid'=>$tid,'status'=>'succ'));
            return $tidinfo['refund_id'];
        }else{
            return $oidinfo['refund_id'];
        }
    }

    private function __getSkuBarcode($sku_id){
        $skuinfo=app::get('sysitem')->model('sku')->getRow('barcode', ['sku_id'=>$sku_id]);
        if(empty($skuinfo['barcode'])){
            return '';
        }else{
            return $skuinfo['barcode'];
        }
    }
//################################wudi tvpalza 20170801 update channel 101 temp update start###############################
    /**
     * @desc 导出待核销交易商品表
     * @author: wudi tvplaza
     * @date: 201708011910
     */
    public function exportVoucher(){
        $shopId=$this->shopId;
        //处理信息丢失
        if(empty($shopId)){
            $url = url::action('topshop_ctl_trade_list@index');
            echo '<meta charset="utf-8"><script>alert("导出失败：店铺信息丢失");location.href="'.$url.'";</script>';
        }
        $params=input::get();
        $filter=array();
        if($params['params']){
            $filter = json_decode($params['params'],true,512,JSON_BIGINT_AS_STRING);
        }
        $filter=$this->_postParams($filter);
        //组装导出数据
        $voucher=$this->_getVoucherData($filter);
        try {
            //导出数据
            $this->_exportVoucherData($voucher);
        } catch (\Exception $e) {
            echo '<meta charset="utf-8"><script>alert("导出失败：未生成excel文件");location.href="'.$url.'";</script>';
        }
    }

    /**
     * 导出线下订单
     */
    public function exportAgentVoucher(){
        $shopId=$this->shopId;
        //处理信息丢失
        if(empty($shopId)){
            $url = url::action('topshop_ctl_trade_list@index');
            echo '<meta charset="utf-8"><script>alert("导出失败：店铺信息丢失");location.href="'.$url.'";</script>';
        }
        $params=input::get();
        $filter=array();
        if($params['params']){
            $filter = json_decode($params['params'],true,512,JSON_BIGINT_AS_STRING);
        }
        $filter=$this->_postAgentParams($filter);
        if($filter['agent_price'] === 'on')
        {
            $filter['agent_price'] = 1;
        }else{
            $filter['agent_price'] = 0;
        }
        //组装导出数据
        $voucher=$this->_getAgentVoucherData($filter);
        try {
            //导出数据
            $this->_exportAgentVoucherData($voucher);
        } catch (\Exception $e) {
            echo '<meta charset="utf-8"><script>alert("导出失败：未生成excel文件");location.href="'.$url.'";</script>';
        }
    }
    /**
     * @desc 组装卡券表数据
     * @param $filter
     * @author: wudi tvplaza
     * @date: 201708112250
     */
    private function _getVoucherData($filter){
        if(empty($filter['shop_id'])){
            $filter['shop_id']=$this->shopId;
            $sqlWhere[]='A.shop_id="'.$this->shopId.'"';
        }else{
            $sqlWhere[]='A.shop_id="'.$filter['shop_id'].'"';
        }
        if($filter['voucher_code']) $sqlWhere[]='A.voucher_code="'.$filter['voucher_code'].'"';
        if(empty($filter['status'])){
            $sqlWhere[]='A.status in ("WAIT_WRITE_OFF","WRITE_FINISHED","HAS_OVERDUE")';
        }else{
            $statusSrc='';
            switch($filter['status']){
                case 1:$statusSrc='WAIT_WRITE_OFF';break;
                case 2:$statusSrc='WRITE_FINISHED';break;
                case 3:$statusSrc='HAS_OVERDUE';break;
                default:$statusSrc='WAIT_WRITE_OFF","WRITE_FINISHED","HAS_OVERDUE';
            }
            $sqlWhere[]='A.status in ("'.$statusSrc.'")';
        }
        if($filter['supplier']){
            $supplierId=app::get('sysshop')->model('supplier')->getList('supplier_id',array('company_name|has'=>$filter['supplier'],'is_audit'=>'PASS'));
            if(empty($supplierId)){
                $sqlwhere[]='A.supplier_id=0';
            }else{
                $midSid=array();
                foreach($supplierId as $key => $value){
                    $midSid[]=$value['supplier_id'];
                }
                $sqlWhere[]='A.supplier_id in ("'.implode('","',$midSid).'")';
            }
        }
        //关键词，关联商品表title
        if($filter['keyword']){
            $sqlWhere[]='E.title like "%'.$filter['keyword'].'%"';
        }
        if($filter['created_time_start']){
            $sqlWhere[]='A.careated_time >= '.$filter['created_time_start'];
        }
        if($filter['created_time_end']){
            $sqlWhere[]='A.careated_time <= '.$filter['created_time_end'];
        }
        if($filter['write_time_start']){
            $sqlWhere[]='A.write_time >= '.$filter['write_time_start'];
        }
        if($filter['write_time_end']){
            $sqlWhere[]='A.write_time <= '.$filter['write_time_end'];
        }
        if($filter['voucher_id']){
            $sqlWhere[]='A.voucher_id in ('.implode(',',$filter['voucher_id']).')';
        }
        $sqlWhereString=implode(' and ',$sqlWhere);
        //主表-systrade_voucher 关联表-sysshop_shop，systrade_trade，sysshop_supplier
        $voucher=db::connection()->createQueryBuilder()
            ->select('B.shop_name,A.tid,A.oid,A.voucher_id,E.title,A.voucher_code,D.mobile as supplier_mobile,D.company_addr,A.start_time,A.end_time,D.supplier_name,C.receiver_name,C.receiver_mobile,F.mobile,A.careated_time,A.status,A.write_time')
            ->from('systrade_voucher','A')
            ->leftJoin('A','sysshop_shop','B','A.shop_id=B.shop_id')
            ->leftJoin('A','systrade_trade','C','A.tid=C.tid')
            ->leftJoin('A','sysshop_supplier','D','A.supplier_id=D.supplier_id')
            ->leftJoin('A','sysitem_item','E','A.item_id=E.item_id')
            ->leftJoin('A','sysuser_account','F','A.user_id=F.user_id')
            ->orderBy('D.company_name,C.receiver_name','ASC')
            ->where($sqlWhereString)
            ->execute()->fetchAll();
        if($voucher && is_array($voucher)){
            foreach($voucher as $key => $value){
                $voucher[$key]['start_time']=!empty($value['start_time'])?date('Y-m-d H:i:s',$value['start_time']):'无';
                $voucher[$key]['end_time']=!empty($value['end_time'])?date('Y-m-d H:i:s',$value['end_time']):'无';
                $voucher[$key]['careated_time']=!empty($value['careated_time'])?date('Y-m-d H:i:s',$value['careated_time']):'无';
                $voucher[$key]['write_time']=!empty($value['write_time'])?date('Y-m-d H:i:s',$value['write_time']):'';
                $voucher[$key]['status']=$this->_getVoucherStauts($value['status']);
            }
        }
        return $voucher;
    }

    /**
     * @desc 组装优惠引擎表数据
     * @param $filter
     * @author: wudi tvplaza
     * @date: 201708112250
     */
    private function _getAgentVoucherData($filter){
        if(empty($filter['shop_id'])){
            $filter['shop_id']=$this->shopId;
            $sqlWhere[]='A.shop_id="'.$this->shopId.'"';
        }else{
            $sqlWhere[]='A.shop_id="'.$filter['shop_id'].'"';
        }
        if(empty($filter['status'])){
            $sqlWhere[]='A.status in ("WAIT","COMPLETE","EXPIRE")';
        }else{
            $sqlWhere[]='A.status in ("'.$filter['status'].'")';
        }
        if($filter['supplier']){
            $supplierId=app::get('sysshop')->model('supplier')->getList('supplier_id',array('company_name|has'=>$filter['supplier'],'is_audit'=>'PASS'));
            if(empty($supplierId)){
                $sqlwhere[]='A.supplier_id=0';
            }else{
                $midSid=array();
                foreach($supplierId as $key => $value){
                    $midSid[]=$value['supplier_id'];
                }
                $sqlWhere[]='A.supplier_id in ("'.implode('","',$midSid).'")';
            }
        }
        //关键词，关联商品表title
        if($filter['keyword']){
            $sqlWhere[]='E.title like "%'.$filter['keyword'].'%"';
        }
        if($filter['careated_time|bthan']){
            $sqlWhere[]='A.careated_time >= '.$filter['careated_time|bthan'];
        }
        if($filter['careated_time|sthan']){
            $sqlWhere[]='A.careated_time <= '.$filter['careated_time|sthan'];
        }
        if($filter['write_time|bthan']){
            $sqlWhere[]='A.write_time >= '.$filter['write_time|bthan'];
        }
        if($filter['write_time|sthan']){
            $sqlWhere[]='A.write_time <= '.$filter['write_time|sthan'];
        }
        if($filter['voucher_id']){
            $sqlWhere[]='A.vocher_id in ('.implode(',',$filter['voucher_id']).')';
        }
        if(isset($filter['agent_price'])){
            $sqlWhere[] = 'A.agent_price = '.$filter['agent_price'];
        }
        if(isset($filter['agent_type']))
        {
            $sqlWhere[] = "A.agent_type = '".$filter['agent_type']."'   ";
        }
        $sqlWhereString=implode(' and ',$sqlWhere);
        //主表-systrade_voucher 关联表-sysshop_shop，systrade_trade，sysshop_supplier
        $voucher=db::connection()->createQueryBuilder()
            ->select('B.shop_name,A.sys_tid,A.sys_oid,A.vocher_id,E.title,D.mobile as supplier_mobile,D.company_addr,A.start_time,A.end_time,D.supplier_name,C.receiver_name,C.receiver_mobile,F.mobile,A.careated_time,A.status,A.write_time,A.tid,A.agent_price,A.agent_type,G.payment,G.total_fee,G.voucher_fee,H.name,H.addr')
            ->from('systrade_agent_vocher','A')
            ->leftJoin('A','sysshop_shop','B','A.shop_id=B.shop_id')
            ->leftJoin('A','systrade_trade','C','A.sys_tid=C.tid')
            ->leftJoin('A','sysshop_supplier','D','A.supplier_id=D.supplier_id')
            ->leftJoin('A','sysitem_item','E','A.item_id=E.item_id')
            ->leftJoin('A','sysuser_account','F','A.user_id=F.user_id')
            ->leftJoin('A','systrade_offline_trade','G','A.tid=G.tid')
            ->leftJoin('G','syssupplier_agent_shop','H','G.agent_shop_id=H.agent_shop_id')
            ->orderBy('D.company_name,C.receiver_name','ASC')
            ->where($sqlWhereString)
            ->execute()->fetchAll();
        if($voucher && is_array($voucher)){
            foreach($voucher as $key => $value){
                $voucher[$key]['start_time']=!empty($value['start_time'])?date('Y-m-d H:i:s',$value['start_time']):'无';
                $voucher[$key]['end_time']=!empty($value['end_time'])?date('Y-m-d H:i:s',$value['end_time']):'无';
                $voucher[$key]['careated_time']=!empty($value['careated_time'])?date('Y-m-d H:i:s',$value['careated_time']):'无';
                $voucher[$key]['write_time']=!empty($value['write_time'])?date('Y-m-d H:i:s',$value['write_time']):'';
                $voucher[$key]['status']=$this->_getAgentStauts($value['status']);
                $voucher[$key]['agent_price'] = $value['agent_price'] == 1?'有偿劵':'免费卷';
                $voucher[$key]['agent_type'] = $this->_getAgentType($voucher[$key]['agent_type']);
                $voucher[$key]['payment'] = (string)$value['payment'];
                $voucher[$key]['total_fee'] = (string)$value['total_fee'];
                $voucher[$key]['voucher_fee'] = (string)$value['voucher_fee'];
                $voucher[$key]['tid'] = !empty($value['tid'])?$value['tid']:'';
            }
        }
        return $voucher;
    }

    /**
     * @desc 导出数据
     * @param $voucher
     * @author: wudi tvplaza
     * @date: 201708121800
     */
    private function _exportVoucherData($voucher){
        //表格文件名称处理
        $expTitle='虚拟商品核销统计表_'.date('Y-m-d-H-i-s',time());
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);
        $fileName = $xlsTitle;
        //表头
        $cellTitle=array("所属商户","订单编号","子订单编号","卡券编号","卡券名称","卡券核销代码","卡券有效时间-开始","卡券有效时间-结束","供应商名称","供应商电话","收货人姓名","收货人手机","会员手机","卡券购买时间","卡券状态","卡券核销时间");
        $objPHPExcel = new PHPExcel();
        $cellNum=count($cellTitle);//多少列
        $dataNum = count($voucher);//多少行
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ','BA');
        //表格边框样式
        $color='0x00000000';
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => array('argb' => $color),
                ),
            ),
        );
        //应用样式
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$cellName[$cellNum-1].($dataNum+1))->applyFromArray($styleArray);
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $cellTitle[$i]);
        }
        for($i=0;$i<$dataNum;$i++){//多少行
            $objPHPExcel->getActiveSheet(0)->setCellValue('A'.($i+2),$voucher[$i]['shop_name']);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('B'.($i+2),$voucher[$i]['tid'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('C'.($i+2),$voucher[$i]['oid'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+2),$voucher[$i]['voucher_id']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+2),$voucher[$i]['title']);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('F'.($i+2),$voucher[$i]['voucher_code'],PHPExcel_Cell_DataType::TYPE_STRING);
            //$objPHPExcel->getActiveSheet(0)->setCellValue('H'.($i+2),$voucher[$i]['company_addr']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('G'.($i+2),$voucher[$i]['start_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('H'.($i+2),$voucher[$i]['end_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('I'.($i+2),$voucher[$i]['supplier_name']);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('J'.($i+2),$voucher[$i]['supplier_mobile'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('K'.($i+2),$voucher[$i]['receiver_name']);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('L'.($i+2),$voucher[$i]['receiver_mobile'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('M'.($i+2),$voucher[$i]['mobile'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('N'.($i+2),$voucher[$i]['careated_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('O'.($i+2),$voucher[$i]['status']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('P'.($i+2),$voucher[$i]['write_time']);
        }
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * @desc 导出优惠引擎数据
     * @param $voucher
     * @author: wudi tvplaza
     * @date: 201708121800
     */
    private function _exportAgentVoucherData($voucher){
        //表格文件名称处理
        $expTitle='优惠引擎使用统计表_'.date('Y-m-d-H-i-s',time());
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);
        $fileName = $xlsTitle;
        //表头
        $cellTitle=array("所属商户","订单编号","子订单编号","卡券编号","卡券名称","卡券有效时间-开始","卡券有效时间-结束","供应商名称","供应商电话","收货人姓名","收货人手机","会员手机","卡券购买时间","卡券状态","线下消费订单号","卡券使用时间","使用线下店名称","使用线下店地址","原始价格","实付金额","优惠金额");
        $objPHPExcel = new PHPExcel();
        $cellNum=count($cellTitle);//多少列
        $dataNum = count($voucher);//多少行
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ','BA');
        //表格边框样式
        $color='0x00000000';
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => array('argb' => $color),
                ),
            ),
        );
        //应用样式
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$cellName[$cellNum-1].($dataNum+1))->applyFromArray($styleArray);
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $cellTitle[$i]);
        }
        for($i=0;$i<$dataNum;$i++){//多少行
            $objPHPExcel->getActiveSheet(0)->setCellValue('A'.($i+2),$voucher[$i]['shop_name']);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('B'.($i+2),$voucher[$i]['sys_tid'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('C'.($i+2),$voucher[$i]['sys_oid'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+2),$voucher[$i]['vocher_id']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+2),$voucher[$i]['title']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('F'.($i+2),$voucher[$i]['start_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('G'.($i+2),$voucher[$i]['end_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('H'.($i+2),$voucher[$i]['supplier_name']);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('I'.($i+2),$voucher[$i]['supplier_mobile'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('J'.($i+2),$voucher[$i]['receiver_name']);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('K'.($i+2),$voucher[$i]['receiver_mobile'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('L'.($i+2),$voucher[$i]['mobile'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('M'.($i+2),$voucher[$i]['careated_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('N'.($i+2),$voucher[$i]['status']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('O'.($i+2),$voucher[$i]['tid']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('P'.($i+2),$voucher[$i]['write_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('Q'.($i+2),$voucher[$i]['name']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('R'.($i+2),$voucher[$i]['addr']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('S'.($i+2),$voucher[$i]['payment']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('T'.($i+2),$voucher[$i]['voucher_fee']);
        }
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * @desc 转换状态屏显字符串
     * @param $voucherStatus
     * @return string
     * @author: wudi tvplaza
     * @date: 201708110928
     */
    private function _getVoucherStauts($voucherStatus){
        switch ($voucherStatus){
            case 'WAIT_WRITE_OFF';$src='未核销';break;
            case'WRITE_FINISHED';$src='已核销';break;
            case'HAS_OVERDUE';$src='已过期';break;
            default:$src="无记录";
        }
        return $src;
    }

    private function _getAgentStauts($voucherStatus){
        switch ($voucherStatus){
            case 'WAIT';$src='未使用';break;
            case'COMPLETE';$src='已使用';break;
            case'EXPIRE';$src='卡券过期';break;
            default:$src="无记录";
        }
        return $src;
    }

    private function _getAgentType($voucherType){
        switch ($voucherType){
            case 'CASH_VOCHER';$src='代金券';break;
            case'DISCOUNT';$src='满折劵';break;
            case'REDUCE';$src='满减劵';break;
            default:$src="无记录";
        }
        return $src;
    }

    /**
     * @desc 格式化request数据
     * @param $params
     * @author: wudi tvplaza
     * @date: 201708110942
     */
    private function _postParams($params){
        if($params['filter_time'])
        {
            $times = array_filter(explode('-',$params['filter_time']));
            if($times){
                if($params['time_type']=='write'){
                    $params['write_time_start'] = strtotime($times['0']);
                    $params['write_time_end'] = strtotime($times['1'])+86400;
                }else{
                    $params['created_time_start'] = strtotime($times['0']);
                    $params['created_time_end'] = strtotime($times['1'])+86400;
                    $params['status']='WRITE_FINISHED';
                }
                unset($params['create_time']);
            }
        }
        unset($params['time_type']);
        return $params;
    }

    /**
     * @desc 格式化request数据
     * @param $params
     * @author: gurundong tvplaza
     */
    private function _postAgentParams($filter){
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
                    $filter['careated_time|sthan'] = strtotime($times['1']);
                }
                unset($filter['create_time']);
                unset($filter['filter_time']);
            }
        }
        unset($filter['time_type']);
        return $filter;
    }



    //################################wudi tvpalza 20170801 update channel 101 temp update end###############################

    public function exportItemSettle(){
        $params=input::get();
        $filter['shop_id']=$this->shopId;
        if($params['timearea'])
        {
            $pagedata['timearea'] = $params['timearea'];
            $timeArray = explode('-', $params['timearea']);
            $filter['createtime_than']  = strtotime($timeArray[0])-3600*24;
            $filter['createtime_lthan'] = strtotime($timeArray[1]) + 3600*24;
        }
        else
        {
            $filter['createtime_than']  = time()-3600*24*7;
            $filter['createtime_lthan'] = time();
        }
        $result = app::get('topshop')->rpcCall('item.get.settle',$filter);
        $exportData['item_unit_settle'] = $result['item_unit_settle'];
        $exportData['item_price_unit_settle'] = $result['item_price_unit_settle'];
        $exportData['post_fee'] = $result['post_fee'];
        $exportData['sale_fee'] = $result['sale_fee'];
        $exportData['post_fee_detail'] = $result['post_fee_detail'];
        $exportData['timearea']=str_replace('/','-',str_replace('-','_',$params['timearea']));
        if($params['unit']=='item'){
            $this->_exportItemSettleByItem($exportData);
        }elseif ($params['unit']=='price'){
            $this->_exportItemSettleByPrice($exportData);
        }elseif($params['unit']=='postfee') {
            $this->_exportItemSettleByPostfee($exportData);
        }else{
            echo '<script>alert("导出失败,请重试")</script>';
        }
    }

    private function _exportItemSettleByPrice($data){
        //表格文件名称处理
        $fileName='商品出库分析_按价格统计'.$data['timearea'];
        $expTableData=$data['item_price_unit_settle'];
        $cellTitle=array('商品编号','商品名称','销售单价','销售数量','销售金额');
        $objPHPExcel = new PHPExcel();
        $cellNum=count($cellTitle);//多少列
        $dataNum = count($expTableData);//多少行
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ','BA');
        //表格边框样式
        $color='0x00000000';
        $titleStyle = array(
            'font' => array (
                'bold' => true
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            )


        );
        $contentStyle=array(
            'borders' => array(
                'allborders' => array(
                    //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => array('argb' => $color),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($titleStyle);
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$cellName[$cellNum-1].($dataNum+3))->applyFromArray($contentStyle);
        //生成表头
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $fileName);
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:E1');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', '销售收入总计：'.$data['sale_fee'].',运费总计：'.$data['post_fee']);
        $objPHPExcel->getActiveSheet(0)->mergeCells('A2:D2');
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'3', $cellTitle[$i]);
        }

        //生成内容
        for($i=0;$i<$dataNum;$i++){//多少行
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('A'.($i+4),$expTableData[$i]['item_id'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('B'.($i+4),$expTableData[$i]['title'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+4),$expTableData[$i]['price']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+4),$expTableData[$i]['num']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+4),$expTableData[$i]['sale_fee']);
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$fileName.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    private function _exportItemSettleByItem($data){
        //表格文件名称处理
        $fileName='商品出库分析_按商品统计'.$data['timearea'];
        $expTableData=$data['item_unit_settle'];
        $cellTitle=array('商品编号','商品名称','销售数量','销售金额');
        $objPHPExcel = new PHPExcel();
        $cellNum=count($cellTitle);//多少列
        $dataNum = count($expTableData);//多少行
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ','BA');
        //表格边框样式
        $color='0x00000000';
        $titleStyle = array(
            'font' => array (
                'bold' => true
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            )


        );
        $contentStyle=array(
            'borders' => array(
                'allborders' => array(
                    //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => array('argb' => $color),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($titleStyle);
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$cellName[$cellNum-1].($dataNum+3))->applyFromArray($contentStyle);
        //生成表头
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $fileName);
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:D1');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', '销售收入总计：'.$data['sale_fee'].',运费总计：'.$data['post_fee']);
        $objPHPExcel->getActiveSheet(0)->mergeCells('A2:D2');
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'3', $cellTitle[$i]);
        }

        //生成内容
        for($i=0;$i<$dataNum;$i++){//多少行
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('A'.($i+4),$expTableData[$i]['item_id'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('B'.($i+4),$expTableData[$i]['title'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+4),$expTableData[$i]['num']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+4),$expTableData[$i]['sale_fee']);
        }
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$fileName.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    private function _exportItemSettleByPostfee($data){
        //表格文件名称处理
        $fileName='商品快递费用分析_按快递统计'.$data['timearea'];
        $expTableData=$data['post_fee_detail'];
        $cellTitle=array('快递模板编号','快递模板名称','快递单价','快递使用次数');
        $objPHPExcel = new PHPExcel();
        $cellNum=count($cellTitle);//多少列
        $dataNum = count($expTableData);//多少行
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ','BA');
        //表格边框样式
        $color='0x00000000';
        $titleStyle = array(
            'font' => array (
                'bold' => true
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            )


        );
        $contentStyle=array(
            'borders' => array(
                'allborders' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => array('argb' => $color),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($titleStyle);
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$cellName[$cellNum-1].($dataNum+3))->applyFromArray($contentStyle);
        //生成表头
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $fileName);
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:D1');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2','运费总计：'.$data['post_fee']);
        $objPHPExcel->getActiveSheet(0)->mergeCells('A2:D2');
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'3', $cellTitle[$i]);
        }

        //生成内容
        for($i=0;$i<$dataNum;$i++){//多少行
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('A'.($i+4),$expTableData[$i]['dlytmpl_ids'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('B'.($i+4),$expTableData[$i]['dlytmpl_name'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+4),$expTableData[$i]['post_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+4),$expTableData[$i]['count']);
        }
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$fileName.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    public function exportItem($data){
        $fileName='settle_item_'.$data['name'].'_'.time();
        $expTableData=$data['content'];
        $cellTitle=$data['title'];
        $objPHPExcel = new PHPExcel();
        $cellNum=count($cellTitle);//多少列
        $dataNum = count($expTableData);//多少行
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ','BA');
        //表格边框样式
        $color='0x00000000';
        $titleStyle = array(
            'font' => array (
                'bold' => true
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            )


        );
        $contentStyle=array(
            'borders' => array(
                'allborders' => array(
                    //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => array('argb' => $color),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($titleStyle);
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$cellName[$cellNum-1].($dataNum+2))->applyFromArray($contentStyle);
        //生成表头
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '供应商结算明细表('.$data['ctitle'].')');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:R1');
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $cellTitle[$i]);
        }
        //生成内容
        for($i=0;$i<$dataNum;$i++){//多少行
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('A'.($i+3),$expTableData[$i]['item_id'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('B'.($i+3),$expTableData[$i]['title'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+3),$expTableData[$i]['shop_id']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+3),$expTableData[$i]['supplier_id']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+3),$expTableData[$i]['incoming_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('F'.($i+3),$expTableData[$i]['tax_rate']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('G'.($i+3),$expTableData[$i]['price']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('H'.($i+3),$expTableData[$i]['cost_price']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('I'.($i+3),$expTableData[$i]['num']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('J'.($i+3),$expTableData[$i]['item_fee_amount']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('K'.($i+3),$expTableData[$i]['post_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('L'.($i+3),$expTableData[$i]['service_charge']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('M'.($i+3),$expTableData[$i]['refund_num']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('N'.($i+3),$expTableData[$i]['refund_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('O'.($i+3),$expTableData[$i]['refund_post_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('P'.($i+3),$expTableData[$i]['refund_service_charge']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('Q'.($i+3),$expTableData[$i]['settlement_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('R'.($i+3),$expTableData[$i]['plate_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('S'.($i+3),$expTableData[$i]['shop_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('T'.($i+3),$expTableData[$i]['settlement_status']);
        }
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$fileName.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $download_path=PUBLIC_DIR.'/csvs/'.$fileName.'.xls';
        //$objWriter->save($download_path);
        $objWriter->save('php://output');
        //return 'csvs/'.$fileName.'.xls';
    }

    public function getShopName($shopId){
        $shopInfo=app::get('sysshop')->model('shop')->getRow('shop_name',array('shop_id'=>$shopId));
        return $shopInfo['shop_name'];
    }

    public function getSupplierName($shopId,$supplierId){
        if($supplierId==0){
            $shopInfo=app::get('sysshop')->model('shop')->getRow('shop_name',array('shop_id'=>$shopId));
            return $shopInfo['shop_name'].'(店铺自营)';
        }else{
            $supplierInfo=app::get('sysshop')->model('supplier')->getRow('company_name',array('shop_id'=>$shopId,'supplier_id'=>$supplierId,'is_audit'=>'PASS'));
            return $supplierInfo['company_name'];
        }
    }

    public function getIncomingType($incomingType){
        $taxConfig=config::get('tax');
        $curTax=$taxConfig[$incomingType];
        return $curTax['src'];
    }

    /**
     * @name getStatusSrc
     * @desc get status src string
     * @param $status
     * @return string
     * @author: wudi tvplaza
     * @date: 2017-11-13 11:03
     */
    public function getStatusSrc($status){
        if($status==1){
            return '未结算';
        }elseif($status==2){
            return '已结算';
        }else{
            return '结算状态异常';
        }
    }

    public function exportSupplierSettleDetail(){
        $timearea=input::get('timearea');
        if(!empty($timearea)){

            $timeArray = explode('-', $timearea);
            $filter['time_start']  = str_replace('/','-',$timeArray[0]);
            $filter['time_end'] = str_replace('/','-',$timeArray[1]);
        }
        $filter['shop_id']=$this->shopId;


        if (!empty($filter['time_start']) && !empty($filter['time_end'])) {
            $name = $filter['time_start'] . '_to_' . $filter['time_end'];
            $ctitle = $filter['time_start'] . ' 至 ' . $filter['time_end'];
        }
        if (empty($filter['time_start']) && empty($filter['time_end'])) {
            $name = 'all';
            $ctitle = '全部';

        }
        if (!empty($filter['time_start'])) {
            $dbfilter['account_time|than'] = strtotime($filter['time_start']);
        } else {
            $dbfilter['account_time|than'] = 0;
        }
        if (!empty($filter['time_end'])) {
            $dbfilter['account_time|lthan'] = strtotime($filter['time_end']) + 86399;
        } else {
            $dbfilter['account_time|lthan'] = time();
        }

        if (!empty($filter['time_start']) && empty($filter['time_end'])) {
            $name = 'from_' . $filter['time_start'];
            $ctitle = $filter['time_start'] . '至今';
        }
        if (empty($filter['time_start']) && !empty($filter['time_end'])) {
            $name = ' by_' . $filter['time_end'];
            $ctitle = '截至' . $filter['time_end'];
        }

        if (empty($filter['shop_id']) || $filter['shop_id'] == -1) {
            $dbfilter['shop_id|noequal'] = 0;
            $ctitle = $ctitle . '_所有商家';
        } else {
            $dbfilter['shop_id|nequal'] = $filter['shop_id'];
            $ctitle = $ctitle . '_' . $this->getShopName($filter['shop_id']);
        }
        $list = app::get('sysclearing')->model('settlement_supplier_detail')->getList('*', $dbfilter,0,-1,'shop_id,supplier_id');
        foreach ($list as $k => $value) {
            $list[$k]['trade_type'] = $this->getTradeTypeSrc($value['trade_type']);
            $list[$k]['trade_time'] = date('Y-m-d H:i:s', $value['trade_time']);
            $list[$k]['shop_id'] = $this->getShopName($value['shop_id']);
            $list[$k]['shop_type'] = $this->getShopType($value['shop_type']);
            $list[$k]['account_type'] = $this->getAccountType($value['account_type']);
            $list[$k]['accounting_type'] = $this->getAccountingType($value['accounting_type']);
            $list[$k]['supplier_fee_from'] = $this->getSupplierFeeFromSrc($value['supplier_fee_from']);
			if($value['supplier_type']==3){
				$list[$k]['supplier_id'] = $this->getShopName($value['supplier_id']);
			}else{
				$list[$k]['supplier_id'] = $this->getSupplierName($value['shop_id'], $value['supplier_id']);				
			}
            $list[$k]['supplier_type'] = $this->getSupplierTypeSrc($value['supplier_type']);
            $list[$k]['incoming_type'] = $this->getIncomingType($value['incoming_type']);
            $list[$k]['account_time'] = date('Y-m-d H:i:s', $value['account_time']);
        }
        $data['content'] = $list;
        $data['name'] = $name;
        $data['ctitle'] = $ctitle;

        $title = array('子订单编号', '订单编号', '交易类型','交易时间', '交易收款平台名称', '交易商户名称', '店铺', '店铺类型', '收款账户类型', '供应商', '供应商来源', '商品标题', 'sku描述', '收入类型', '税率', '商品价格', '供货成本价', '购买数量', 'SKU的值', '交易金额', '店铺毛利', '平台手续费', '供应商结算费用', '发退货时间');
        $fileName='settle_'.$data['name'].'_'.time();
        $expTableData=$data['content'];
        $cellTitle=$title;
        $objPHPExcel = new PHPExcel();
        $cellNum=count($cellTitle);//多少列
        $dataNum = count($expTableData);//多少行
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ','BA');
        //表格边框样式
        $color='0x00000000';
        $titleStyle = array(
            'font' => array (
                'bold' => true
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            )


        );
        $contentStyle=array(
            'borders' => array(
                'allborders' => array(
                    //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => array('argb' => $color),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($titleStyle);
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$cellName[$cellNum-1].($dataNum+2))->applyFromArray($contentStyle);
        //生成表头
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '店铺供应商结算明细('.$data['ctitle'].')');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:R1');
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $cellTitle[$i]);
        }
        //生成内容

        for($i=0;$i<$dataNum;$i++){//多少行
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('A'.($i+3),$expTableData[$i]['oid'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('B'.($i+3),$expTableData[$i]['tid'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+3),$expTableData[$i]['trade_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+3),$expTableData[$i]['trade_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+3),$expTableData[$i]['charge_platform']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('F'.($i+3),$expTableData[$i]['charge_company']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('G'.($i+3),$expTableData[$i]['shop_id']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('H'.($i+3),$expTableData[$i]['shop_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('I'.($i+3),$expTableData[$i]['account_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('J'.($i+3),$expTableData[$i]['supplier_id']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('K'.($i+3),$expTableData[$i]['supplier_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('L'.($i+3),$expTableData[$i]['title']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('M'.($i+3),$expTableData[$i]['spec_nature_info']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('N'.($i+3),$expTableData[$i]['incoming_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('O'.($i+3),$expTableData[$i]['tax_rate']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('P'.($i+3),$expTableData[$i]['price']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('Q'.($i+3),$expTableData[$i]['cost_price']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('R'.($i+3),$expTableData[$i]['num']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('S'.($i+3),$expTableData[$i]['sku_properties_name']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('T'.($i+3),$expTableData[$i]['payment']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('U'.($i+3),$expTableData[$i]['shop_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('V'.($i+3),$expTableData[$i]['platform_service_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('W'.($i+3),$expTableData[$i]['supplier_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('X'.($i+3),$expTableData[$i]['account_time']);
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$fileName.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $download_path=PUBLIC_DIR.'/csvs/'.$fileName.'.xls';
        $objWriter->save($download_path);
        $objWriter->save('php://output');
    }

    public function getTradeTypeSrc($tradeType){
        $src=array(
            'pay'=>'收款',
            'refunds'=>'退款',
        );
        return $src[$tradeType];
    }

    public function getShopType($shopType){
        $type=array(
            'flag'=>'品牌旗舰店',
            'brand'=>'品牌专卖店',
            'cat'=>'类目专营店',
            'self'=>'运营商自营店铺',
            'store'=>'多品类通用型店铺',
        );
        return $type[$shopType];
    }
    public function getAccountType($accountType){
        $type=array(
            'on'=>'店铺自收款',
            'off'=>'平台收款',
        );
        return $type[$accountType];
    }
    public function getAccountingType($accountingType){
        $type=array(
            'platform' => '自营-平台核算',
            'independent' => '自营-独立核算',
            'thirdparty'=>'第三方-独立核算'
        );
        return $type[$accountingType];
    }

    public function getSupplierFeeFromSrc($from){
        $type=array(
            'shop'=>'店铺',
            'platform'=>'平台'
        );
        return $type[$from];
    }

    public function getSupplierTypeSrc($type){
        if($type==0){
            return '店铺';
        }elseif($type==1){
            return '平台';
        }elseif($type==3){
			return '广电优选';
		}
        return $type[$type];
    }


    public function exportItemSettleDetail(){
        $params=input::get();
        $filter['shop_id']=$this->shopId;
        if($params['timearea'])
        {
            $pagedata['timearea'] = $params['timearea'];
            $timeArray = explode('-', $params['timearea']);
            $filter['account_time|bthan']  = strtotime($timeArray[0]);
            $filter['account_time|sthan'] = strtotime($timeArray[1]);
        }
        else
        {
            $filter['account_time|bthan']  = time()-3600*24*7;
            $filter['account_time|sthan'] = time();
        }
        $mdlItemSettle=app::get('sysstat')->model("item_settle_statics_detail");
        $expTableData =$mdlItemSettle->getList('*',$filter);


        $title = array('订单编号','子订单编号','商品编号','商品名称','销售数量','销售金额','主订单运费','发货或者退款完成时间');
        $fileName='delivery_detail'.time();
        $cellTitle=$title;
        $objPHPExcel = new PHPExcel();
        $cellNum=count($cellTitle);//多少列
        $dataNum = count($expTableData);//多少行
        $cellName = array('A','B','C','D','E','F','G','H');
        //表格边框样式
        $color='0x00000000';
        $titleStyle = array(
            'font' => array (
                'bold' => true
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            )


        );
        $contentStyle=array(
            'borders' => array(
                'allborders' => array(
                    //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => array('argb' => $color),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($titleStyle);
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$cellName[$cellNum-1].($dataNum+2))->applyFromArray($contentStyle);
        //生成表头
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '店铺商品出库明细('.$this->_getShopName($this->shopId).')');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:H1');
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $cellTitle[$i]);
        }
        //生成内容

        for($i=0;$i<$dataNum;$i++){//多少行
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('A'.($i+3),$expTableData[$i]['tid'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('B'.($i+3),$expTableData[$i]['oid'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+3),$expTableData[$i]['item_id']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+3),$expTableData[$i]['title']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+3),$expTableData[$i]['num']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('F'.($i+3),$expTableData[$i]['payment']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('G'.($i+3),$expTableData[$i]['postfee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('H'.($i+3),date('Y-m-d',$expTableData[$i]['account_time']));
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$fileName.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $download_path=PUBLIC_DIR.'/csvs/'.$fileName.'.xls';
        $objWriter->save($download_path);
        $objWriter->save('php://output');




    }

    public function exportBillSettleDetail(){
        $timearea=input::get('timearea');
        if(!empty($timearea)){

            $timeArray = explode('-', $timearea);
            $filter['time_start']  = str_replace('/','-',$timeArray[0]);
            $filter['time_end'] = str_replace('/','-',$timeArray[1]);
        }
        $filter['shop_id']=$this->shopId;


        if (!empty($filter['time_start']) && !empty($filter['time_end'])) {
            $name = $filter['time_start'] . '_to_' . $filter['time_end'];
            $ctitle = $filter['time_start'] . ' 至 ' . $filter['time_end'];
        }
        if (empty($filter['time_start']) && empty($filter['time_end'])) {
            $name = 'all';
            $ctitle = '全部';

        }
        if (!empty($filter['time_start'])) {
            $dbfilter['trade_time|than'] = strtotime($filter['time_start']);
        } else {
            $dbfilter['trade_time|than'] = 0;
        }
        if (!empty($filter['time_end'])) {
            $dbfilter['trade_time|lthan'] = strtotime($filter['time_end']) + 86399;
        } else {
            $dbfilter['trade_time|lthan'] = time();
        }

        if (!empty($filter['time_start']) && empty($filter['time_end'])) {
            $name = 'from_' . $filter['time_start'];
            $ctitle = $filter['time_start'] . '至今';
        }
        if (empty($filter['time_start']) && !empty($filter['time_end'])) {
            $name = ' by_' . $filter['time_end'];
            $ctitle = '截至' . $filter['time_end'];
        }

        if (empty($filter['shop_id']) || $filter['shop_id'] == -1) {
            $dbfilter['shop_id|noequal'] = 0;
            $ctitle = $ctitle . '_所有商家';
        } else {
            $dbfilter['shop_id|nequal'] = $filter['shop_id'];
            $ctitle = $ctitle . '_' . $this->getShopName($filter['shop_id']);
        }
        $list = app::get('sysclearing')->model('settlement_billing_detail')->getList('*', $dbfilter,0,-1,'shop_id,supplier_id');
        foreach ($list as $k => $value) {
            $list[$k]['trade_type'] = $this->getTradeTypeSrc($value['trade_type']);
            $list[$k]['trade_time'] = date('Y-m-d H:i:s', $value['trade_time']);
            $list[$k]['shop_id'] = $this->getShopName($value['shop_id']);
            $list[$k]['shop_type'] = $this->getShopType($value['shop_type']);
            $list[$k]['account_type'] = $this->getAccountType($value['account_type']);
            $list[$k]['accounting_type'] = $this->getAccountingType($value['accounting_type']);
            $list[$k]['supplier_fee_from'] = $this->getSupplierFeeFromSrc($value['supplier_fee_from']);
			
			/*modify_2018/11/7_by_wanghaichao_start*/
			/*
			*$list[$k]['supplier_id'] = $this->getSupplierName($value['shop_id'], $value['supplier_id']);
			*/
			//加入判断,判断供货商类型是否是广电优选的
			if($value['supplier_type']==3){
				$list[$k]['supplier_id'] = $this->getShopName($value['supplier_id']);
			}else{
				$list[$k]['supplier_id'] = $this->getSupplierName($value['shop_id'], $value['supplier_id']);
			}
			/*modify_2018/11/7_by_wanghaichao_end*/
			
            $list[$k]['supplier_type'] = $this->getSupplierTypeSrc($value['supplier_type']);
            $list[$k]['incoming_type'] = $this->getIncomingType($value['incoming_type']);
        }
        $data['content'] = $list;
        $data['name'] = $name;
        $data['ctitle'] = $ctitle;

        $title = array('子订单编号', '订单编号', '交易类型','交易时间', '交易收款平台名称', '交易商户名称', '店铺', '店铺类型', '收款账户类型', '供应商', '供应商来源', '商品标题', 'sku描述', '收入类型', '税率', '选货商城供货成本价', '供货成本价', '购买数量', 'SKU的值', '店铺结算费用', '店铺毛利', '支付手续费',  '平台手续费', '供应商结算费用');
        $fileName='settle_bill_'.$data['name'].'_'.time();
        $expTableData=$data['content'];
        $cellTitle=$title;
        $objPHPExcel = new PHPExcel();
        $cellNum=count($cellTitle);//多少列
        $dataNum = count($expTableData);//多少行
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ','BA');
        //表格边框样式
        $color='0x00000000';
        $titleStyle = array(
            'font' => array (
                'bold' => true
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            )


        );
        $contentStyle=array(
            'borders' => array(
                'allborders' => array(
                    //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => array('argb' => $color),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($titleStyle);
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$cellName[$cellNum-1].($dataNum+2))->applyFromArray($contentStyle);
        //生成表头
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '店铺供应商结算明细('.$data['ctitle'].')');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:R1');
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $cellTitle[$i]);
        }
        //生成内容

        for($i=0;$i<$dataNum;$i++){//多少行
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('A'.($i+3),$expTableData[$i]['oid'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('B'.($i+3),$expTableData[$i]['tid'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+3),$expTableData[$i]['trade_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+3),$expTableData[$i]['trade_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+3),$expTableData[$i]['charge_platform']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('F'.($i+3),$expTableData[$i]['charge_company']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('G'.($i+3),$expTableData[$i]['shop_id']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('H'.($i+3),$expTableData[$i]['shop_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('I'.($i+3),$expTableData[$i]['account_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('J'.($i+3),$expTableData[$i]['supplier_id']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('K'.($i+3),$expTableData[$i]['supplier_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('L'.($i+3),$expTableData[$i]['title']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('M'.($i+3),$expTableData[$i]['spec_nature_info']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('N'.($i+3),$expTableData[$i]['incoming_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('O'.($i+3),$expTableData[$i]['tax_rate']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('P'.($i+3),$expTableData[$i]['price']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('Q'.($i+3),$expTableData[$i]['cost_price']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('R'.($i+3),$expTableData[$i]['num']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('S'.($i+3),$expTableData[$i]['sku_properties_name']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('T'.($i+3),$expTableData[$i]['payment']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('U'.($i+3),$expTableData[$i]['shop_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('V'.($i+3),$expTableData[$i]['service_charge']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('W'.($i+3),$expTableData[$i]['platform_service_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('X'.($i+3),$expTableData[$i]['supplier_fee']);
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$fileName.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $download_path=PUBLIC_DIR.'/csvs/'.$fileName.'.xls';
        $objWriter->save($download_path);
        $objWriter->save('php://output');
    }

	/* action_name (par1, par2, par3)
	* 导出本店铺推送的商品的结算明细
	* author by wanghaichao
	* date 2018/7/6
	*/
	public function exportBillPushDetail(){
        $timearea=input::get('timearea');
        if(!empty($timearea)){

            $timeArray = explode('-', $timearea);
            $filter['time_start']  = str_replace('/','-',$timeArray[0]);
            $filter['time_end'] = str_replace('/','-',$timeArray[1]);
        }
		if(!empty(input::get('shop_id'))){
			$dbfilter['shop_id']=input::get('shop_id');
		}
        $filter['init_shop_id']=$this->shopId;


        if (!empty($filter['time_start']) && !empty($filter['time_end'])) {
            $name = $filter['time_start'] . '_to_' . $filter['time_end'];
            $ctitle = $filter['time_start'] . ' 至 ' . $filter['time_end'];
        }
        if (empty($filter['time_start']) && empty($filter['time_end'])) {
            $name = 'all';
            $ctitle = '全部';

        }
        if (!empty($filter['time_start'])) {
            $dbfilter['trade_time|than'] = strtotime($filter['time_start']);
        } else {
            $dbfilter['trade_time|than'] = 0;
        }
        if (!empty($filter['time_end'])) {
            $dbfilter['trade_time|lthan'] = strtotime($filter['time_end']) + 86399;
        } else {
            $dbfilter['trade_time|lthan'] = time();
        }

        if (!empty($filter['time_start']) && empty($filter['time_end'])) {
            $name = 'from_' . $filter['time_start'];
            $ctitle = $filter['time_start'] . '至今';
        }
        if (empty($filter['time_start']) && !empty($filter['time_end'])) {
            $name = ' by_' . $filter['time_end'];
            $ctitle = '截至' . $filter['time_end'];
        }

        //if (empty($filter['shop_id']) || $filter['shop_id'] == -1) {
        //    $dbfilter['shop_id|noequal'] = 0;
        //    $ctitle = $ctitle . '_所有商家';
        //} else {
        //    $dbfilter['shop_id|nequal'] = $filter['shop_id'];
        //    $ctitle = $ctitle . '_' . $this->getShopName($filter['shop_id']);
        //}
		$dbfilter['init_shop_id']=$this->shopId;
        $list = app::get('sysclearing')->model('seller_billing_detail')->getList('*', $dbfilter,0,-1,'shop_id,supplier_id');
        foreach ($list as $k => $value) {
            $list[$k]['trade_type'] = $this->getTradeTypeSrc($value['trade_type']);
            $list[$k]['trade_time'] = date('Y-m-d H:i:s', $value['trade_time']);
            $list[$k]['shop_id'] = $this->getShopName($value['shop_id']);
            $list[$k]['shop_type'] = $this->getShopType($value['shop_type']);
            $list[$k]['account_type'] = $this->getAccountType($value['account_type']);
            $list[$k]['accounting_type'] = $this->getAccountingType($value['accounting_type']);
            $list[$k]['supplier_fee_from'] = $this->getSupplierFeeFromSrc($value['supplier_fee_from']);
            $list[$k]['supplier_id'] = $this->getSupplierName($value['init_shop_id'], $value['supplier_id']);
            $list[$k]['supplier_type'] = $this->getSupplierTypeSrc($value['supplier_type']);
            $list[$k]['incoming_type'] = $this->getIncomingType($value['incoming_type']);
        }
        $data['content'] = $list;
        $data['name'] = $name;
        $data['ctitle'] = $ctitle;

        $title = array('子订单编号', '订单编号', '交易类型','交易时间', '交易收款平台名称', '交易商户名称', '代卖店铺', '代卖店铺类型', '收款账户类型', '供应商', '供应商来源', '商品标题', 'sku描述', '收入类型', '税率', '商品价格', '供货成本价', '购买数量', 'SKU的值', '交易金额', '店铺毛利', '供应商结算费用');
        $fileName='push_bill_'.$data['name'].'_'.time();
        $expTableData=$data['content'];
        $cellTitle=$title;
        $objPHPExcel = new PHPExcel();
        $cellNum=count($cellTitle);//多少列
        $dataNum = count($expTableData);//多少行
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        //表格边框样式
        $color='0x00000000';
        $titleStyle = array(
            'font' => array (
                'bold' => true
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            )


        );
        $contentStyle=array(
            'borders' => array(
                'allborders' => array(
                    //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => array('argb' => $color),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($titleStyle);
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$cellName[$cellNum-1].($dataNum+2))->applyFromArray($contentStyle);
        //生成表头
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '本店推送商品结算明细('.$data['ctitle'].')');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:R1');
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $cellTitle[$i]);
        }
        //生成内容

        for($i=0;$i<$dataNum;$i++){//多少行
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('A'.($i+3),$expTableData[$i]['oid'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('B'.($i+3),$expTableData[$i]['tid'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+3),$expTableData[$i]['trade_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+3),$expTableData[$i]['trade_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+3),$expTableData[$i]['charge_platform']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('F'.($i+3),$expTableData[$i]['charge_company']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('G'.($i+3),$expTableData[$i]['shop_id']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('H'.($i+3),$expTableData[$i]['shop_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('I'.($i+3),$expTableData[$i]['account_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('J'.($i+3),$expTableData[$i]['supplier_id']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('K'.($i+3),$expTableData[$i]['supplier_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('L'.($i+3),$expTableData[$i]['title']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('M'.($i+3),$expTableData[$i]['spec_nature_info']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('N'.($i+3),$expTableData[$i]['incoming_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('O'.($i+3),$expTableData[$i]['tax_rate']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('P'.($i+3),$expTableData[$i]['agent_cost_price']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('Q'.($i+3),$expTableData[$i]['init_cost_price']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('R'.($i+3),$expTableData[$i]['num']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('S'.($i+3),$expTableData[$i]['sku_properties_name']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('T'.($i+3),$expTableData[$i]['supplier_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('U'.($i+3),$expTableData[$i]['init_shop_fee']);
            //$objPHPExcel->getActiveSheet(0)->setCellValue('V'.($i+3),$expTableData[$i]['platform_service_fee']);   //平台手续费
            $objPHPExcel->getActiveSheet(0)->setCellValue('V'.($i+3),$expTableData[$i]['init_supplier_fee']);
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$fileName.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $download_path=PUBLIC_DIR.'/csvs/'.$fileName.'.xls';
        $objWriter->save($download_path);
        $objWriter->save('php://output');
    }

	/* action_name (par1, par2, par3)
	* 导出本店铺拉取的商品的结算明细
	* author by wanghaichao
	* date 2018/7/6
	*/
	public function exportBillPullDetail(){
        $timearea=input::get('timearea');
        if(!empty($timearea)){

            $timeArray = explode('-', $timearea);
            $filter['time_start']  = str_replace('/','-',$timeArray[0]);
            $filter['time_end'] = str_replace('/','-',$timeArray[1]);
        }
		if(!empty(input::get('init_shop_id'))){
			$dbfilter['init_shop_id']=input::get('init_shop_id');
		}
        if (!empty($filter['time_start']) && !empty($filter['time_end'])) {
            $name = $filter['time_start'] . '_to_' . $filter['time_end'];
            $ctitle = $filter['time_start'] . ' 至 ' . $filter['time_end'];
        }
        if (empty($filter['time_start']) && empty($filter['time_end'])) {
            $name = 'all';
            $ctitle = '全部';

        }
        if (!empty($filter['time_start'])) {
            $dbfilter['trade_time|than'] = strtotime($filter['time_start']);
        } else {
            $dbfilter['trade_time|than'] = 0;
        }
        if (!empty($filter['time_end'])) {
            $dbfilter['trade_time|lthan'] = strtotime($filter['time_end']) + 86399;
        } else {
            $dbfilter['trade_time|lthan'] = time();
        }

        if (!empty($filter['time_start']) && empty($filter['time_end'])) {
            $name = 'from_' . $filter['time_start'];
            $ctitle = $filter['time_start'] . '至今';
        }
        if (empty($filter['time_start']) && !empty($filter['time_end'])) {
            $name = ' by_' . $filter['time_end'];
            $ctitle = '截至' . $filter['time_end'];
        }

        //if (empty($filter['shop_id']) || $filter['shop_id'] == -1) {
        //    $dbfilter['shop_id|noequal'] = 0;
        //    $ctitle = $ctitle . '_所有商家';
        //} else {
        //    $dbfilter['shop_id|nequal'] = $filter['shop_id'];
        //    $ctitle = $ctitle . '_' . $this->getShopName($filter['shop_id']);
        //}
		$dbfilter['shop_id']=$this->shopId;
        $list = app::get('sysclearing')->model('seller_billing_detail')->getList('*', $dbfilter,0,-1,'shop_id,supplier_id');
        foreach ($list as $k => $value) {
            $list[$k]['trade_type'] = $this->getTradeTypeSrc($value['trade_type']);
            $list[$k]['trade_time'] = date('Y-m-d H:i:s', $value['trade_time']);
            $list[$k]['shop_id'] = $this->getShopName($value['shop_id']);
            $list[$k]['shop_type'] = $this->getShopType($value['shop_type']);
            $list[$k]['account_type'] = $this->getAccountType($value['account_type']);
            $list[$k]['accounting_type'] = $this->getAccountingType($value['accounting_type']);
            $list[$k]['supplier_fee_from'] = $this->getSupplierFeeFromSrc($value['supplier_fee_from']);
            $list[$k]['supplier_id'] = $this->getShopName($value['init_shop_id']);
            $list[$k]['supplier_type'] = '广电优选';
            $list[$k]['incoming_type'] = $this->getIncomingType($value['incoming_type']);
        }
        $data['content'] = $list;
        $data['name'] = $name;
        $data['ctitle'] = $ctitle;

        $title = array('子订单编号', '订单编号', '交易类型','交易时间', '交易收款平台名称', '交易商户名称', '店铺', '店铺类型', '收款账户类型', '供应商', '供应商来源', '商品标题', 'sku描述', '收入类型', '税率', '商品价格', '供货成本价', '购买数量', 'SKU的值', '交易金额', '店铺毛利', '平台手续费','支付手续费','供应商结算费用');
        $fileName='pull_bill_'.$data['name'].'_'.time();
        $expTableData=$data['content'];
        $cellTitle=$title;
        $objPHPExcel = new PHPExcel();
        $cellNum=count($cellTitle);//多少列
        $dataNum = count($expTableData);//多少行
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        //表格边框样式
        $color='0x00000000';
        $titleStyle = array(
            'font' => array (
                'bold' => true
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            )


        );
        $contentStyle=array(
            'borders' => array(
                'allborders' => array(
                    //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => array('argb' => $color),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($titleStyle);
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$cellName[$cellNum-1].($dataNum+2))->applyFromArray($contentStyle);
        //生成表头
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '本店拉取商品结算明细('.$data['ctitle'].')');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:R1');
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $cellTitle[$i]);
        }
        //生成内容

        for($i=0;$i<$dataNum;$i++){//多少行
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('A'.($i+3),$expTableData[$i]['oid'],PHPExcel_Cell_DataType::TYPE_STRING);   //子订单号
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('B'.($i+3),$expTableData[$i]['tid'],PHPExcel_Cell_DataType::TYPE_STRING);   //主订单号
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+3),$expTableData[$i]['trade_type']);   //交易类型
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+3),$expTableData[$i]['trade_time']);    //交易时间
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+3),$expTableData[$i]['charge_platform']);   //交易收款平台
            $objPHPExcel->getActiveSheet(0)->setCellValue('F'.($i+3),$expTableData[$i]['charge_company']);   //交易商户名称
            $objPHPExcel->getActiveSheet(0)->setCellValue('G'.($i+3),$expTableData[$i]['shop_id']);                 //店铺有
            $objPHPExcel->getActiveSheet(0)->setCellValue('H'.($i+3),$expTableData[$i]['shop_type']);              //店铺类型
            $objPHPExcel->getActiveSheet(0)->setCellValue('I'.($i+3),$expTableData[$i]['account_type']);       //收款账户类型
            $objPHPExcel->getActiveSheet(0)->setCellValue('J'.($i+3),$expTableData[$i]['supplier_id']);              
            $objPHPExcel->getActiveSheet(0)->setCellValue('K'.($i+3),$expTableData[$i]['supplier_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('L'.($i+3),$expTableData[$i]['title']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('M'.($i+3),$expTableData[$i]['spec_nature_info']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('N'.($i+3),$expTableData[$i]['incoming_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('O'.($i+3),$expTableData[$i]['tax_rate']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('P'.($i+3),$expTableData[$i]['price']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('Q'.($i+3),$expTableData[$i]['cost_price']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('R'.($i+3),$expTableData[$i]['num']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('S'.($i+3),$expTableData[$i]['sku_properties_name']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('T'.($i+3),$expTableData[$i]['payment']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('U'.($i+3),$expTableData[$i]['shop_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('V'.($i+3),$expTableData[$i]['platform_service_fee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('W'.($i+3),$expTableData[$i]['service_charge']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('X'.($i+3),$expTableData[$i]['supplier_fee']);
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$fileName.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $download_path=PUBLIC_DIR.'/csvs/'.$fileName.'.xls';
        $objWriter->save($download_path);
        $objWriter->save('php://output');
    }

	/* action_name (par1, par2, par3)
	* 导出主持人的结算详情
	* author by wanghaichao
	* date 2018/7/6
	*/
	public function exportBillSellerDetail(){
        $timearea=input::get('timearea');
        if(!empty($timearea)){

            $timeArray = explode('-', $timearea);
            $filter['time_start']  = str_replace('/','-',$timeArray[0]);
            $filter['time_end'] = str_replace('/','-',$timeArray[1]);
        }
		$dbfilter['shop_id']=$this->shopId;
		if(input::get('seller_id')){
			$dbfilter['seller_id']=input::get('seller_id');
		}elseif($this->sellerInfo['is_compere']==1) {
			$dbfilter['seller_id']=$this->sellerId;
		}else{
			$dbfilter['seller_id|noequal']=0;
		}
		//if(!empty(input::get('shop_id'))){
		//	$dbfilter['shop_id']=input::get('shop_id');
		//}
        //$filter['init_shop_id']=$this->shopId;


        if (!empty($filter['time_start']) && !empty($filter['time_end'])) {
            $name = $filter['time_start'] . '_to_' . $filter['time_end'];
            $ctitle = $filter['time_start'] . ' 至 ' . $filter['time_end'];
        }
        if (empty($filter['time_start']) && empty($filter['time_end'])) {
            $name = 'all';
            $ctitle = '全部';

        }
        if (!empty($filter['time_start'])) {
            $dbfilter['trade_time|than'] = strtotime($filter['time_start']);
        } else {
            $dbfilter['trade_time|than'] = 0;
        }
        if (!empty($filter['time_end'])) {
            $dbfilter['trade_time|lthan'] = strtotime($filter['time_end']) + 86399;
        } else {
            $dbfilter['trade_time|lthan'] = time();
        }

        if (!empty($filter['time_start']) && empty($filter['time_end'])) {
            $name = 'from_' . $filter['time_start'];
            $ctitle = $filter['time_start'] . '至今';
        }
        if (empty($filter['time_start']) && !empty($filter['time_end'])) {
            $name = ' by_' . $filter['time_end'];
            $ctitle = '截至' . $filter['time_end'];
        }

        //if (empty($filter['shop_id']) || $filter['shop_id'] == -1) {
        //    $dbfilter['shop_id|noequal'] = 0;
        //    $ctitle = $ctitle . '_所有商家';
        //} else {
        //    $dbfilter['shop_id|nequal'] = $filter['shop_id'];
        //    $ctitle = $ctitle . '_' . $this->getShopName($filter['shop_id']);
        //}
		$dbfilter['shop_id']=$this->shopId;
        $list = app::get('sysclearing')->model('seller_billing_detail')->getList('*', $dbfilter,0,-1,'shop_id,supplier_id');
        foreach ($list as $k => $value) {
            $list[$k]['trade_type'] = $this->getTradeTypeSrc($value['trade_type']);
            $list[$k]['trade_time'] = date('Y-m-d H:i:s', $value['trade_time']);
            $list[$k]['shop_id'] = $this->getShopName($value['shop_id']);
            $list[$k]['shop_type'] = $this->getShopType($value['shop_type']);
            $list[$k]['account_type'] = $this->getAccountType($value['account_type']);
            $list[$k]['accounting_type'] = $this->getAccountingType($value['accounting_type']);
            $list[$k]['supplier_fee_from'] = $this->getSupplierFeeFromSrc($value['supplier_fee_from']);
			if($value['init_shop_id']){
				$list[$k]['supplier_id'] = $this->getShopName($value['init_shop_id']);
				$list[$k]['supplier_type']='广电优选';
			}else{
				$list[$k]['supplier_id'] = $this->getSupplierName($value['shop_id'], $value['supplier_id']);
				$list[$k]['supplier_type'] = $this->getSupplierTypeSrc($value['supplier_type']);
			}
            $list[$k]['incoming_type'] = $this->getIncomingType($value['incoming_type']);
        }
        $data['content'] = $list;
        $data['name'] = $name;
        $data['ctitle'] = $ctitle;
		//if($this->sellerInfo['is_compere']==1){
		$title=array('主持人','子订单编号', '订单编号', '交易类型','交易时间', '交易收款平台名称', '交易商户名称', '店铺', '商品标题', 'sku描述',  '商品价格', '购买数量', 'SKU的值', '交易金额','主持人佣金' );
		//}else{
		//	$title = array('子订单编号', '订单编号', '交易类型','交易时间', '交易收款平台名称', '交易商户名称', '店铺', '店铺类型', '收款账户类型', '供应商', '供应商来源', '商品标题', 'sku描述', '收入类型', '税率', '商品价格', '供货成本价', '购买数量', 'SKU的值', '交易金额', '店铺毛利', '平台手续费', '供应商结算费用','主持人佣金');
		//}
        $fileName='maker_bill_'.$data['name'].'_'.time();
        $expTableData=$data['content'];
        $cellTitle=$title;
        $objPHPExcel = new PHPExcel();
        $cellNum=count($cellTitle);//多少列
        $dataNum = count($expTableData);//多少行
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        //表格边框样式
        $color='0x00000000';
        $titleStyle = array(
            'font' => array (
                'bold' => true
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            )


        );
        $contentStyle=array(
            'borders' => array(
                'allborders' => array(
                    //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => array('argb' => $color),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($titleStyle);
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$cellName[$cellNum-1].($dataNum+2))->applyFromArray($contentStyle);
        //生成表头
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '本店推送商品结算明细('.$data['ctitle'].')');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:R1');
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $cellTitle[$i]);
        }
        //生成内容
		//if($this->sellerInfo['is_compere']==1){
			for($i=0;$i<$dataNum;$i++){//多少行
				$objPHPExcel->getActiveSheet(0)->setCellValue('A'.($i+3),$expTableData[$i]['seller_id']);
				$objPHPExcel->getActiveSheet(0)->setCellValueExplicit('B'.($i+3),$expTableData[$i]['oid'],PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet(0)->setCellValueExplicit('C'.($i+3),$expTableData[$i]['tid'],PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+3),$expTableData[$i]['trade_type']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+3),$expTableData[$i]['trade_time']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('F'.($i+3),$expTableData[$i]['charge_platform']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('G'.($i+3),$expTableData[$i]['charge_company']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('H'.($i+3),$expTableData[$i]['shop_id']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('I'.($i+3),$expTableData[$i]['title']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('J'.($i+3),$expTableData[$i]['spec_nature_info']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('K'.($i+3),$expTableData[$i]['price']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('L'.($i+3),$expTableData[$i]['num']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('M'.($i+3),$expTableData[$i]['sku_properties_name']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('N'.($i+3),$expTableData[$i]['payment']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('O'.($i+3),$expTableData[$i]['seller_commission']);
			}
		/*}else{
			for($i=0;$i<$dataNum;$i++){//多少行
				$objPHPExcel->getActiveSheet(0)->setCellValueExplicit('A'.($i+3),$expTableData[$i]['oid'],PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet(0)->setCellValueExplicit('B'.($i+3),$expTableData[$i]['tid'],PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+3),$expTableData[$i]['trade_type']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+3),$expTableData[$i]['trade_time']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+3),$expTableData[$i]['charge_platform']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('F'.($i+3),$expTableData[$i]['charge_company']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('G'.($i+3),$expTableData[$i]['shop_id']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('H'.($i+3),$expTableData[$i]['shop_type']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('I'.($i+3),$expTableData[$i]['account_type']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('J'.($i+3),$expTableData[$i]['supplier_id']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('K'.($i+3),$expTableData[$i]['supplier_type']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('L'.($i+3),$expTableData[$i]['title']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('M'.($i+3),$expTableData[$i]['spec_nature_info']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('N'.($i+3),$expTableData[$i]['incoming_type']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('O'.($i+3),$expTableData[$i]['tax_rate']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('P'.($i+3),$expTableData[$i]['price']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('Q'.($i+3),$expTableData[$i]['cost_price']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('R'.($i+3),$expTableData[$i]['num']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('S'.($i+3),$expTableData[$i]['sku_properties_name']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('T'.($i+3),$expTableData[$i]['payment']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('U'.($i+3),$expTableData[$i]['shop_fee']);
				$objPHPExcel->getActiveSheet(0)->setCellValue('V'.($i+3),$expTableData[$i]['platform_service_fee']);
				if($expTableData[$i]['init_shop_id']){
					$objPHPExcel->getActiveSheet(0)->setCellValue('W'.($i+3),$expTableData[$i]['init_shop_fee']);
				}else{
					$objPHPExcel->getActiveSheet(0)->setCellValue('W'.($i+3),$expTableData[$i]['supplier_fee']);
				}
				$objPHPExcel->getActiveSheet(0)->setCellValue('X'.($i+3),$expTableData[$i]['seller_commission']);
			}
		}*/

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$fileName.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $download_path=PUBLIC_DIR.'/csvs/'.$fileName.'.xls';
        $objWriter->save($download_path);
        $objWriter->save('php://output');
    }

}
//################################wudi tvpalza 20170801 update channel 101 temp update end###############################