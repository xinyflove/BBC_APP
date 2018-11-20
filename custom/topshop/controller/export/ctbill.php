<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/10/22
 * Time: 16:14
 */

class topshop_ctl_export_ctbill extends topshop_controller
{
    /**
     * 导入页面-模态显示
     * @return mixed
     */
    public function view(){
        return view::make('topshop/import/ct_bill_import.html');
    }

    /**
     * 处理文件上传
     */
    public function uploadCsvFile()
    {
        $dir = 'csvs/ctbill';
        $del_file = scandir($dir);
        foreach($del_file as $path)
        {
            if(strpos($path,$this->shopId.'ctbill') === 0)
            {
                unlink($dir.'/'.$path);
            }
        }

        if ($_FILES["csv_file"]["error"] > 0)
        {
            echo json_encode(array('code' => 0, 'msg' => "上传失败!"));
        }
        else
        {
            $file_name = $_FILES["csv_file"]["name"];
            $file_type = $_FILES["csv_file"]["type"];
            $file_size = $_FILES["csv_file"]["size"];   // /1024 Kb

            if( empty($file_name) || empty($file_size) ){
                echo json_encode(array('code' => 0, 'msg' => "请选择csv文件!"));
                return;
            }
            $tmp_name = $_FILES["csv_file"]["tmp_name"];
            if(!file_exists($dir))
            {
                mkdir($dir,'0777');
            }
            $path = 'csvs/ctbill/'.$this->shopId.'ctbill'.date('YmdHis').'.csv';
            move_uploaded_file($tmp_name, $path);
            echo json_encode(array('code' => 1, 'msg' => "上传成功!", 'f_name' => $path));
        }
    }

    /**
     * 处理物流订单信息excel文件导入方法
     * @return bool
     */
    public function import(){

        $params = input::get();
        //获取文件路径
        $filePath = realpath($params['f_name']);
        //读取excel数据
        $excelData = $this->getExcelData($filePath);
        $aggreModel = app::get('syslogistics')->model('delivery_aggregation');
        $tradeModel = app::get('systrade')->model('trade');
        //判断数据是否有效
        if($excelData['error'] == 1)
        {
            //读取sheet0的数据
            $invoiceList = $excelData['data'][0];
            $record = array_slice($invoiceList,1);
            //去除表头
            if(is_array($record) && !empty($record))
            {
                $logi_nos = array_column($record, 'G');
                $logi_nos = array_filter($logi_nos);
                $aggreData = $aggreModel->getList('*', ['logi_no|in' => $logi_nos]);
                $aggreData = array_bind_key($aggreData, 'logi_no');
                $log = [];
                $succ_logi_nos = [];
                foreach($record as $tk => $r)
                {
                    if(empty($r['A']))
                    {
                        continue;
                    }
                    $tpm_data = [];
                    $tpm_data['A'] =  ($tk+2);
                    $tpm_data['B'] =  $r['G'];

                    if(empty($r['G']) || (empty($r['J']) && $r['J'] !=0) || ((empty($r['K']) && $r['K'] !=0) && (empty($r['L']) && $r['L'] !=0)))
                    {
                        $tpm_data['C'] =  '面单号、应收货款、实收货款、pos金额一项或多项信息缺失';
                        $log[] = $tpm_data;
                        continue;
                    }
                    $checkRow = $aggreData[$r['G']];
                    if(!$checkRow)
                    {
                        $tpm_data['C'] =  '数据库中没有相应的数据';
                        $log[] = $tpm_data;
                        continue;
                    }
                    if(!$checkRow['trade_payment'])
                    {
                        $tids = explode(',', $checkRow['tids']);
                        $tradeInfo = $tradeModel->getRow('SUM(payment) as total_payment,pay_type',['tid|in' => $tids]);
                        //此订单的应支付总金额
                        $total_payment = $tradeInfo['total_payment'];
                    }
                    else
                    {
                        $total_payment = $checkRow['trade_payment'];
                    }
                    $amount_real = ecmath::number_plus(array((float)$r['K'],(float)$r['L']));
                    $payment_update_data['trade_payment'] = $total_payment;
                    $payment_update_data['amount_payable'] = (float)$r['J'];
                    $payment_update_data['amount_real'] = $amount_real;
                    $aggreModel->update($payment_update_data, ['logi_no' => $r['G']]);
                    //判断订单实付金额、物流实付金额以及（物流应付金额+pos金额）是否相等
                    if($total_payment != (float)$r['J'])
                    {
                        $tpm_data['C'] =  '订单实付金额与物流实付金额不一致';
                        $log[] = $tpm_data;
                        continue;
                    }
                    if($total_payment != $amount_real)
                    {
                        $tpm_data['C'] =  '应付金额与实付金额不一致';
                        $log[] = $tpm_data;
                        continue;
                    }
                    $succ_logi_nos[] = $r['G'];
                }

                $fail_logi_nos = array_diff($logi_nos, $succ_logi_nos);
                try
                {
                    if($succ_logi_nos)
                    {
                        $update_data['bill_status'] = 'CHECKED_SUCC';
                        $aggreModel->update($update_data, ['logi_no|in' => $succ_logi_nos]);
                    }
                    if($fail_logi_nos)
                    {
                        $update_data['bill_status'] = 'CHECKED_FAIL';
                        $aggreModel->update($update_data, ['logi_no|in' => $fail_logi_nos]);
                    }
                    $data = array('code' => 1,'msg' => '导入完成，请查看日志');
                    $logFileName = $this->shopId.'ctbill'.date('Y-m-d-H-i-s');
                    $data['log'] = $this->exportLog($logFileName,$log);
                }
                catch(Exception $e)
                {
                    $data = array('code' => 0,'msg' => $e->getMessage());
                }
            }
            else
            {
                $data = array('code' => 0, 'msg' => '导入失败:表格信息读取为空');
            }
        }
        else
        {
            $data = array('code' => 0,'msg' => '读取excel文件内容失败');
        }
        echo json_encode($data);
    }

    /**
     * 根据文件的realpath来读取excel文件内容
     * @param $filePath
     * @return array
     */
    public function getExcelData($filePath)
    {
        if(!file_exists($filePath))
        {
            return array("error"=>0,'message'=>'file not found!');
        }
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        if(!$objReader->canRead($filePath))
        {
            $objReader = PHPExcel_IOFactory::createReader('Excel5');
            if(!$objReader->canRead($filePath))
            {
                return array("error" => 0,'message' => 'file not found!');
            }
        }
        $objReader->setReadDataOnly(true);
        try
        {
            $PHPReader = $objReader->load($filePath);
        }
        catch(Exception $e)
        {

        }
        if(!isset($objReader)) return array("error"=>0,'message'=>'read error!');

        //获取工作表的数目
        $sheetCount = $PHPReader->getSheetCount();

        if($sheetCount > 0)
        {
            for($i = 0;$i< $sheetCount; $i++)
            {
                $excelData[]=$PHPReader->getSheet($i)->toArray(null, true, true, true);
            }
        }
        else
        {
            $excelData[]=$PHPReader->getSheet(0)->toArray(null, true, true, true);
        }
        unset($PHPReader);
        return array("error" => 1,"data" => $excelData);
    }

    /**
     * 导出excel文件
     * @param $expTitle
     * @param $expTableData
     * @return string
     */
    public function exportLog($expTitle, $expTableData)
    {

        $xlsTitle  = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName  = $xlsTitle; //文件名称可根据自己情况设定
        $cellTitle = array("序号","物流单号","备注");
        $cellNum   = count($cellTitle);//多少列
        $dataNum   = count($expTableData);//多少行
        $objPHPExcel = new PHPExcel();
        $cellName    = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

        for($i=0;$i<$cellNum;$i++)
        {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $cellTitle[$i]);
        }

        for($i=0;$i<$dataNum;$i++)
        {//多少行
            for($j=0;$j<$cellNum;$j++)
            { //多少列
                $objPHPExcel->getActiveSheet(0)->setCellValueExplicit($cellName[$j].($i+2),$expTableData[$i][$cellName[$j]],PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }

        //header('pragma:public');
        //header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        //header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        //$objWriter->save('php://output');
        $savePath= 'csvs/ctbill/';
        //$realSavePath=realpath($savePath).'/'.$xlsTitle.'.xls';
        $realSavePath=$savePath.$xlsTitle.'.xls';
        $objWriter->save($realSavePath);
        return $xlsTitle.'.xls';
    }

    /**
     * 导出对账单
     */
    public function exportCtBill()
    {
        $post_data = input::get();
        $timearea = $post_data['timearea'];
        if(!empty($timearea))
        {
            $timeArray = explode('-', $timearea);
            $filter['time_start']  = str_replace('/','-',$timeArray[0]);
            $filter['time_end'] = str_replace('/','-',$timeArray[1]);
        }
        $filter['shop_id']=$this->shopId;


        if (!empty($filter['time_start']) && !empty($filter['time_end']))
        {
            $name = $filter['time_start'] . '_to_' . $filter['time_end'];
            $ctitle = $filter['time_start'] . ' 至 ' . $filter['time_end'];
        }

        if (empty($filter['time_start']) && empty($filter['time_end']))
        {
            $name = 'all';
            $ctitle = '全部';
        }
        if (!empty($filter['time_start']))
        {
            $dbfilter['create_time|than'] = strtotime($filter['time_start']);
        }
        else
        {
            $dbfilter['create_time|than'] = 0;
        }
        if (!empty($filter['time_end']))
        {
            $dbfilter['create_time|lthan'] = strtotime($filter['time_end']) + 86399;
        }
        else
        {
            $dbfilter['create_time|lthan'] = time();
        }

        if($post_data['delivery_type']) $dbfilter['delivery_type'] = $post_data['delivery_type'];
        if($post_data['bill_status']) $dbfilter['bill_status'] = $post_data['bill_status'];

        if (!empty($filter['time_start']) && empty($filter['time_end']))
        {
            $name = 'from_' . $filter['time_start'];
            $ctitle = $filter['time_start'] . '至今';
        }
        if (empty($filter['time_start']) && !empty($filter['time_end']))
        {
            $name = ' by_' . $filter['time_end'];
            $ctitle = '截至' . $filter['time_end'];
        }

        $list = app::get('syslogistics')->model('delivery_aggregation')->getList('*', $dbfilter,0,-1,'create_time');
        foreach($list as &$v)
        {
            $v['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
            $v['status'] = $this->__getDeliveryStatus($v['status']);
            $v['bill_status'] = $this->__getBillStatus($v['bill_status']);
            $v['delivery_type']  = $this->__getDeliveryType($v['delivery_type']);
        }
        $data['content'] = $list;
        $data['name'] = $name;
        $data['ctitle'] = $ctitle;

        $title = array('聚合id', '物流单号', '订单编号', '订单金额', '应付金额', '实付金额', '配送类型', '对账状态', '收货人', '收货人所在省', '收货人所在市', '收货人所在地区', '收货人详细地址', '收货人手机号', '收货人电话', '创建时间', '物流状态', '消息');
        $fileName = $this->shopId.'ctbill_settle_bill_'.$data['name'].'_'.time();
        $expTableData = $data['content'];
        $cellTitle = $title;
        $objPHPExcel = new PHPExcel();
        $cellNum  = count($cellTitle);//多少列
        $dataNum  = count($expTableData);//多少行
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
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '城通对账明细('.$data['ctitle'].')');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:R1');
        for($i=0;$i<$cellNum;$i++)
        {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $cellTitle[$i]);
        }
        //生成内容

        for($i=0;$i<$dataNum;$i++)
        {//多少行
            $objPHPExcel->getActiveSheet(0)->setCellValue('A'.($i+3),$expTableData[$i]['aggregation_id']);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('B'.($i+3),$expTableData[$i]['logi_no'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('C'.($i+3),$expTableData[$i]['tids'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+3),$expTableData[$i]['trade_payment']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+3),$expTableData[$i]['amount_payable']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('F'.($i+3),$expTableData[$i]['amount_real']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('G'.($i+3),$expTableData[$i]['delivery_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('H'.($i+3),$expTableData[$i]['bill_status']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('I'.($i+3),$expTableData[$i]['receiver_name']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('J'.($i+3),$expTableData[$i]['receiver_state']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('K'.($i+3),$expTableData[$i]['receiver_city']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('L'.($i+3),$expTableData[$i]['receiver_district']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('M'.($i+3),$expTableData[$i]['receiver_address']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('N'.($i+3),$expTableData[$i]['receiver_mobile']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('O'.($i+3),$expTableData[$i]['receiver_phone']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('P'.($i+3),$expTableData[$i]['create_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('Q'.($i+3),$expTableData[$i]['status']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('R'.($i+3),$expTableData[$i]['message']);
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$fileName.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $dir = 'csvs/ctbill';
        $del_file = scandir($dir);
        foreach($del_file as $path)
        {
            if(strpos($path,$this->shopId.'ctbill') === 0)
            {
                unlink($dir.'/'.$path);
            }
        }

        if(!file_exists($dir))
        {
            mkdir($dir,0777, true);
        }
        $download_path = PUBLIC_DIR.'/csvs/ctbill/'.$fileName.'.xls';
        $objWriter->save($download_path);
        $objWriter->save('php://output');
    }

    /**
     * @param $delivery_type
     * @return mixed
     * 获取配送类型
     */
    private function __getDeliveryType($delivery_type)
    {
        $delivery_type_array = [
            'SEND_GOODS' => '订单配送',
            'REFUND_GOODS' => '退货退款配送',
            'EXCHANGING_GOODS' => '换货配送',
        ];
        return $delivery_type_array[$delivery_type];
    }

    /**
     * @param $bill_status
     * @return mixed
     * 获取对账状态
     */
    private function __getBillStatus($bill_status)
    {
        $bill_status_array = [
            'UN_CHECKED' => '未对账',
            'CHECKED_SUCC' => '对账成功',
            'CHECKED_FAIL' => '对账失败',
        ];
        return $bill_status_array[$bill_status];
    }

    /**
     * @param $delivery_status
     * @return mixed
     * 获取物流状态
     */
    private function __getDeliveryStatus($delivery_status)
    {
        $delivery_status_array = [
            'succ' => '成功到达',
            'failed' => '发货失败',
            'cancel' => '已取消',
            'lost' => '货物丢失',
            'progress' => '运送中',
            'timeout' => '超时',
            'ready' => '准备发货',
            'rppm' => '客户拒收',
            'push_failed' => '推送失败',
        ];
        return $delivery_status_array[$delivery_status];
    }
}