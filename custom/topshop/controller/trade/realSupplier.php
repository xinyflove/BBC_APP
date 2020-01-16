<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2019/12/27
 * Time: 14:40
 */

class topshop_ctl_trade_realSupplier extends topshop_controller
{
    public function logList()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('真实供应商录入记录');
        $postSend = input::get();
        if($postSend['timearea'])
        {
            $pagedata['timearea'] = $postSend['timearea'];
            $timeArray = explode('-', $postSend['timearea']);
            $filter['created_time|than']  = strtotime($timeArray[0]);//开始前一天
            $filter['created_time|lthan'] = strtotime($timeArray[1]) + 3600*24;//结束后一天
        }
        else
        {
            $filter['created_time|than']  = strtotime(date('Y/m/d',time()))-3600*24;//开始前一天
            $filter['created_time|lthan'] = strtotime(date('Y/m/d',time()));//结束后一天
            $pagedata['timearea'] = date('Y/m/d', time()-3600*24*7) . '-' . date('Y/m/d', time()-3600*24);
            $postSend['timearea'] = $pagedata['timearea'];
        }

        if($postSend['oid']) $filter['oid'] = $pagedata['oid'] = $postSend['oid'];

        $filter['shop_id'] = $this->shopId;
        $limit = 20;
        $count = app::get('systrade')->model('trade_real_supplier_log')->count($filter);
        $totalPage = ceil($count/$limit);
        $page_no = $postSend['pages'] ? $postSend['pages'] : 1;
        $offset = ($page_no-1)*$limit;
        $data = app::get('systrade')->model('trade_real_supplier_log')->getList('*',$filter,$offset,$limit,'created_time desc');
        $pagedata['data']   = $data;
        $pagedata['limits'] = $limit;
        $pagedata['pages']  = $page_no;
        $postSend['pages']  = time();
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_trade_realSupplier@logList',$postSend),
            'current' => $page_no,
            'total'   => $totalPage,
            'use_app' => 'topshop',
            'token'   => $postSend['pages']
        );
        return $this->page('topshop/trade/real_supplier_log.html', $pagedata);
    }

    /**
     * 导入页面-模态显示
     * @return mixed
     */
    public function view()
    {
        return view::make('topshop/import/trade_real_supplier_import.html');
    }

    /**
     * 处理文件上传
     */
    public function uploadCsvFile()
    {
        $dir = 'csvs/trade_real_supplier';
        $del_file = scandir($dir);
        foreach($del_file as $path)
        {
            if(strpos($path,$this->shopId.'trade_real_supplier') === 0)
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
            $path = 'csvs/trade_real_supplier/'.$this->shopId.'trade_real_supplier'.date('YmdHis').'.csv';
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
        $orderModel = app::get('systrade')->model('order');
        //判断数据是否有效
        if($excelData['error'] == 1)
        {
            //读取sheet0的数据
            $import_data = $excelData['data'][0];
            $record = array_slice($import_data,1);
            //去除表头
            if(is_array($record) && !empty($record))
            {
                //限定每次导入不能大于100条
                if(count($record) > 1000) {
                    $data = array('code' => 0,'msg' => "每次最多导入100条数据");
                    echo json_encode($data);die;
                }
                $oids = array_column($record, 'B');
                $oids = array_filter($oids);

                try
                {
                    $orderData = $orderModel->getList('*', ['oid|in' => $oids]);
                    $orderData = array_bind_key($orderData, 'oid');
                    $error_log = [];
                    $real_log_data = [];
                    foreach($record as $tk => $r)
                    {
                        $tpm_error_data = $tmp_log_data = [];
                        $tpm_error_data['A'] =  ($tk+2);
                        $tpm_error_data['B'] = $tmp_log_data['tid'] = $r['A'];
                        $tpm_error_data['C'] = $tmp_log_data['oid'] = $r['B'];
                        $tpm_error_data['D'] = $tmp_log_data['real_supplier_id'] = $r['C'];
                        $tpm_error_data['E'] = $tmp_log_data['real_supplier_name'] = $r['D'];
                        $tpm_error_data['F'] = $tmp_log_data['original_supplier_name'] = $r['E'];

                        if(empty($r['A']) || empty($r['B']) || empty($r['C']) || empty($r['D']) || empty($r['E']))
                        {
                            $tmp_log_data['import_status'] = 'FAILED';
                            $tpm_error_data['G'] = $tmp_log_data['remark'] = '一项或多项信息缺失';
                            $error_log[] = $tpm_error_data;
                            $real_log_data[] = $tmp_log_data;
                            continue;
                        }
                        $checkRow = $orderData[$r['B']];
                        if(!$checkRow)
                        {
                            $tmp_log_data['import_status'] = 'FAILED';
                            $tpm_error_data['G'] = $tmp_log_data['remark'] = '数据库中没有相应的订单数据';
                            $error_log[] = $tpm_error_data;
                            $real_log_data[] = $tmp_log_data;
                            continue;
                        }
                        if($orderData[$r['B']]['tid'] != trim($r['A'])) {
                            $tmp_log_data['import_status'] = 'FAILED';
                            $tpm_error_data['G'] = $tmp_log_data['remark'] = '订单号和子订单号信息不匹配';
                            $error_log[] = $tpm_error_data;
                            $real_log_data[] = $tmp_log_data;
                            continue;
                        }
                        $update_data['real_supplier_id'] = $r['C'];
                        $update_data['real_supplier_name'] = $r['D'];
                        $orderModel->update($update_data, ['oid' => $r['B']]);

                        $tmp_log_data['import_status'] = 'SUCCESS';
                        $real_log_data[] = $tmp_log_data;
                    }

                    $log_sql = "INSERT INTO systrade_trade_real_supplier_log (account_id, shop_id, tid, oid, real_supplier_id, "
                        . "real_supplier_name, original_supplier_name, import_status, remark, created_time, modified_time) "
                        . " values ";
                    $log_create_time = $log_modified_time = time();
                    $temp_sql = '';
                    foreach ($real_log_data as $rld) {
                        if(!empty($temp_sql)) $temp_sql .= ",";
                        $temp_sql .= "('".$this->sellerId ."','".$this->shopId."','".$rld['tid']."','".$rld['oid']."','".$rld['real_supplier_id']
                            ."','".$rld['real_supplier_name']."','".$rld['original_supplier_name']."','".$rld['import_status']
                            ."','".$rld['remark']."','".$log_create_time."','".$log_modified_time."')";
                    }

                    $log_sql .= $temp_sql;
                    $db = app::get('base')->database();
                    $log_insert_result = $db->executeQuery($log_sql);
                    $data = array('code' => 1,'msg' => '导入完成，请查看日志');
                    $logFileName = $this->shopId.'trade_real_supplier'.date('Y-m-d-H-i-s');
                    $data['log'] = $this->exportLog($logFileName, $error_log);
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
        echo json_encode($data);die;
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
        $cellTitle = array("序号","订单号","子订单号","真实供应商id","真实供应商名称","原始供应商名称", "备注");
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
        $savePath= 'csvs/trade_real_supplier/';
        $realSavePath=$savePath.$xlsTitle.'.xls';
        $objWriter->save($realSavePath);
        return $xlsTitle.'.xls';
    }

    /**
     * 下载信息导入模板
     */
    public function downLoadCsvTpl()
    {
        //定制excel信息
        //标题
        $expTitle='订单真实供应商信息导入模板';
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $tips1='说明：1.请不要编辑导出表格中的信息，防止变成科学计数法导致导入失败。';
        $tips2='说明：2.模板填入信息之前，请将单元格设置为文本格式，也是防止科学计数法导致错误。';
        $tips3='说明：3.一次性导入数据不允许超过1000个';
        $cellTitle=array("订单号","子订单号","真实供应商id","真实供应商名称","原始供应商名称",$tips1);
        $cellNum=count($cellTitle);//多少列
        $objPHPExcel = new PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        //设置标题
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->getActiveSheet(0)->getColumnDimension($cellName[$i])->setAutoSize(true);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $cellTitle[$i]);
        }
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue("F2", $tips2);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue("F3", $tips3);
        $objPHPExcel->getActiveSheet()->getStyle( 'F1')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
        $objPHPExcel->getActiveSheet()->getStyle( 'F2')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
        $objPHPExcel->getActiveSheet()->getStyle( 'F3')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);

        $objPHPExcel->getActiveSheet()->setTitle('导入模板');
        $objPHPExcel->setActiveSheetIndex(0);
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$xlsTitle.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit();
    }
}