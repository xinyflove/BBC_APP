<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/10/24
 * Time: 16:14
 */

class topshop_ctl_import_ctpostfee extends topshop_controller
{
    /**
     * 导入页面-模态显示
     * @return mixed
     */
    public function view()
    {
        return view::make('topshop/import/ct_post_fee_import.html');
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
            if(strpos($path,$this->shopId.'ctpostfee') === 0)
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
            $path = 'csvs/ctbill/'.$this->shopId.'ctpostfee'.date('YmdHis').'.csv';
            move_uploaded_file($tmp_name, $path);
            echo json_encode(array('code' => 1, 'msg' => "上传成功!", 'f_name' => $path));
        }
    }

    /**
     * 处理物流运费信息excel文件导入方法
     * @return bool
     */
    public function import()
    {

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

                    if(empty($r['G']) || empty($r['M']) || empty($r['N']))
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
                    $estimate_freight = $checkRow['estimate_freight'];
                    $real_freight = ecmath::number_plus(array((float)$r['M'],(float)$r['N']));

                    $payment_update_data['real_freight'] = $real_freight;
                    $aggreModel->update($payment_update_data, ['logi_no' => $r['G']]);
                    //判断订单预估配送费和实收配送费是否相等
                    if($real_freight != $estimate_freight)
                    {
                        $tpm_data['C'] =  '预估配送费和实收配送费不一致';
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
                        $update_data['post_fee_compare_status'] = 'CHECKED_SUCC';
                        $aggreModel->update($update_data, ['logi_no|in' => $succ_logi_nos]);
                    }
                    if($fail_logi_nos)
                    {
                        $update_data['post_fee_compare_status'] = 'CHECKED_FAIL';
                        $aggreModel->update($update_data, ['logi_no|in' => $fail_logi_nos]);
                    }
                    $data = array('code' => 1,'msg' => '导入完成，请查看日志');
                    $logFileName = $this->shopId.'ctpostfee'.date('Y-m-d-H-i-s');
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

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $savePath= 'csvs/ctbill/';
        $realSavePath=$savePath.$xlsTitle.'.xls';
        $objWriter->save($realSavePath);
        return $xlsTitle.'.xls';
    }
}