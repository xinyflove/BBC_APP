<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/12/10
 * Time: 16:29
 */

class topshop_ctl_clearing_invoice extends topshop_controller
{
    /**
     * @return html
     * 发票列表
     */
    public function invoiceList()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('发票列表');
        $post_data = input::get();

        if($post_data['create_time_area'])
        {
            $pagedata['create_time_area'] = $post_data['create_time_area'];
            $timeArray = explode('-', $post_data['create_time_area']);
            $filter['created_time|than'] = strtotime($timeArray[0]);//开始前一天
            $filter['created_time|lthan'] = strtotime($timeArray[1]) + 3600*24;//结束后一天
        }
//        else
//        {
//            $filter['created_time|than']  = strtotime(date('Y/m/d',time()))-3600*24;//开始前一天
//            $filter['created_time|lthan'] = strtotime(date('Y/m/d',time()));//结束后一天
//            $pagedata['create_time_area'] = date('Y/m/d', time()-3600*24*7) . '-' . date('Y/m/d', time()-3600*24);
//        }
        if($post_data['push_time_area'])
        {
            $pagedata['push_time_area'] = $post_data['push_time_area'];
            $pushtimeArray = explode('-', $post_data['push_time_area']);
            $filter['push_time|than']  = strtotime($pushtimeArray[0]);//开始前一天
            $filter['push_time|lthan'] = strtotime($pushtimeArray[1]) + 3600*24;//结束后一天
        }


        if($post_data['invoice_type'] != 'all') $filter['invoice_type'] = $pagedata['invoice_type'] = $post_data['invoice_type'];
        if($post_data['is_invalid']) $filter['is_invalid'] = $pagedata['is_invalid'] = $post_data['is_invalid'];
        if($post_data['push_status'] != -1) $filter['push_status'] = $pagedata['push_status'] = $post_data['push_status'];
        $limit = 5;
        $count = app::get('systrade')->model('trade_invoice')->count($filter);
        $totalPage = ceil($count/$limit);
        $page_no = $post_data['pages'] ? $post_data['pages'] : 1;
        $offset = ($page_no-1)*$limit;
        $data = app::get('systrade')->model('trade_invoice')->getList('*',$filter,$offset,$limit,'created_time desc');
        foreach($data as &$v)
        {
            $v['invoice_project'] = unserialize($v['invoice_project']);
        }
        $pagedata['data']   = $data;
        $pagedata['limits'] = $limit;
        $pagedata['pages']  = $page_no;
        $post_data['pages']  = time();
        $pagedata['create_time_area']  = $post_data['create_time_area'];
        $pagedata['push_time_area']    = $post_data['push_time_area'];
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_clearing_invoice@invoiceList',$post_data),
            'current' => $page_no,
            'total'   => $totalPage,
            'use_app' => 'topshop',
            'token'   => $post_data['pages']
        );
        return $this->page('topshop/sysstat/trade_invoice.html', $pagedata);
    }

    /**
     * 发票导出
     */
    public function exportInvoice()
    {
        $post_data = input::get();
        $create_time_area = $post_data['create_time_area'];
        $push_time_area = $post_data['push_time_area'];
        if(!empty($create_time_area))
        {
            $create_time_array = explode('-', $create_time_area);
            $filter['create_time_start']  = str_replace('/','-',$create_time_array[0]);
            $filter['create_time_end'] = str_replace('/','-',$create_time_array[1]);
        }

        if(!empty($push_time_area))
        {
            $push_time_array = explode('-', $push_time_area);
            $filter['push_time_start']  = str_replace('/','-',$push_time_array[0]);
            $filter['push_time_end'] = str_replace('/','-',$push_time_array[1]);
        }
        $filter['shop_id']=$this->shopId;

        if (!empty($filter['create_time_start']))
        {
            $dbfilter['created_time|than'] = strtotime($filter['create_time_start']);
        }
        else
        {
            $dbfilter['created_time|than'] = 0;
        }
        if (!empty($filter['create_time_end']))
        {
            $dbfilter['created_time|lthan'] = strtotime($filter['create_time_end']) + 86399;
        }
        else
        {
            $dbfilter['created_time|lthan'] = time();
        }

        if (!empty($filter['push_time_start']))
        {
            $dbfilter['push_time|than'] = strtotime($filter['push_time_start']);
        }
        if (!empty($filter['push_time_end']))
        {
            $dbfilter['push_time|lthan'] = strtotime($filter['push_time_end']) + 86399;
        }

        if($post_data['invoice_type'] != 'all') $dbfilter['invoice_type'] = $post_data['invoice_type'];
        if($post_data['is_invalid']) $dbfilter['is_invalid']  = $post_data['is_invalid'];
        if($post_data['push_status'] != -1) $dbfilter['push_status']  = $post_data['push_status'];

        $list = app::get('systrade')->model('trade_invoice')->getList('*', $dbfilter,0,-1,'created_time');
        $trade_model = app::get('systrade')->model('trade');
        foreach($list as &$v)
        {
            $v['created_time'] = date('Y-m-d H:i:s',$v['created_time']);
            $v['push_time'] = date('Y-m-d H:i:s',$v['push_time']);
            $v['push_status'] = $this->_getPushStatus($v['push_status']);
            $v['is_invalid'] = ($v['is_invalid'] == 0) ? '否' : '是';
            $v['invoice_type']  = $this->_getInvoiceType($v['invoice_type']);
            $invoice_project  = unserialize($v['invoice_project']);
            $invoice_content = '';
            foreach($invoice_project as $project)
            {
                if($invoice_content != '')
                {
                    $invoice_content .= "\r\n";
                }
                $invoice_content .= '名称:'.$project['goodsName'].';规格：'.$project['specModel'].';数量：'.$project['num'].';含税单价：'.$project['unitPrice'].';税率：'.$project['taxRate'].';税额：'.$project['taxAmount'];
            }
            $v['invoice_project'] = $invoice_content;
            $v['invalid_time'] = date('Y-m-d H:i:s',$v['invalid_time']);
            
            if(!$v['payment'] == 0)
            {
                $kingdee_trade_data = $trade_model->getRow('payment',['tid'=>$v['tid']]);
                $v['payment'] = $kingdee_trade_data['payment'];
            }
        }
        $data['content'] = $list;
        $data['invoice_title'] = '发票列表'.date('YmdHis',time());

        $fileName = $this->shopId.'trade_invoice_'.time();
        $title = array('id', '订单编号', '发票抬头', '发票类型', '发票项目', '纳税人识别号', '联系方式', '地址', '开户行', '银行卡号', '推送状态', '创建时间', '推送时间', '开票流水号', '发票代码', '发票号码', 'pdf地址', '是否冲红', '红票流水号', '红票代码', '红票号码', '红票pdf','冲红时间','总价税合计');
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
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '订单发票明细('.$data['invoice_title'].')');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:R1');
        for($i=0;$i<$cellNum;$i++)
        {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $cellTitle[$i]);
        }
        //生成内容

        for($i=0;$i<$dataNum;$i++)
        {//多少行
            $objPHPExcel->getActiveSheet(0)->setCellValue('A'.($i+3),$expTableData[$i]['id']);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('B'.($i+3),$expTableData[$i]['tid'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+3),$expTableData[$i]['invoice_name']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+3),$expTableData[$i]['invoice_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+3),$expTableData[$i]['invoice_project']);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('F'.($i+3),$expTableData[$i]['registration_number'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('G'.($i+3),$expTableData[$i]['contact_way']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('H'.($i+3),$expTableData[$i]['addr'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('I'.($i+3),$expTableData[$i]['deposit_bank']);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('J'.($i+3),$expTableData[$i]['card_number'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('K'.($i+3),$expTableData[$i]['push_status']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('L'.($i+3),$expTableData[$i]['created_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('M'.($i+3),$expTableData[$i]['push_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('N'.($i+3),$expTableData[$i]['serial_no'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('O'.($i+3),$expTableData[$i]['invoice_code']);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('P'.($i+3),$expTableData[$i]['invoice_no']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('Q'.($i+3),$expTableData[$i]['pdf_url']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('R'.($i+3),$expTableData[$i]['is_invalid']);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('S'.($i+3),$expTableData[$i]['cancel_serial_no'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('T'.($i+3),$expTableData[$i]['cancel_invoice_code']);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('U'.($i+3),$expTableData[$i]['cancel_invoice_no']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('V'.($i+3),$expTableData[$i]['cancel_pdf_url']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('W'.($i+3),$expTableData[$i]['invalid_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('X'.($i+3),$expTableData[$i]['payment']);
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$fileName.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $dir = 'csvs/trade_invoice';
        $del_file = scandir($dir);
        foreach($del_file as $path)
        {
            if(strpos($path,$this->shopId.'trade_invoice') === 0)
            {
                unlink($dir.'/'.$path);
            }
        }

        if(!file_exists($dir))
        {
            mkdir($dir,0777, true);
        }
        $download_path = PUBLIC_DIR.'/csvs/trade_invoice/'.$fileName.'.xls';
        $objWriter->save($download_path);
        $objWriter->save('php://output');
    }

    /**
     * @return mixed
     */
    public function view()
    {
        return view::make('topshop/import/invoice_import.html');
    }

    /**
     * 处理文件上传
     */
    public function uploadCsvFile()
    {
        $dir = 'csvs/trade_invoice';
        $del_file = scandir($dir);
        foreach($del_file as $path)
        {
            if(strpos($path,$this->shopId.'vat_invoice') === 0)
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
            $path = 'csvs/trade_invoice/'.$this->shopId.'vat_invoice'.date('YmdHis').'.csv';
            move_uploaded_file($tmp_name, $path);
            echo json_encode(array('code' => 1, 'msg' => "上传成功!", 'f_name' => $path));
        }
    }

    /**
     * 下载增值税发票信息导入模板
     */
    public function downLoadCsvTpl()
    {
        //定制excel信息
        //标题
        $tradeInvoiceModel = app::get('systrade')->model('trade_invoice');
        $expTableData = $tradeInvoiceModel->getList('*',['push_status' => '0','invoice_type'=>'vat','shop_id'=>$this->shopId]);

        $expTitle = '增值税发票信息导入模板';
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $tips1='说明：1.请不要编辑导出表格中的信息，防止变成科学计数法导致导入失败。';
        $tips2='说明：2.模板填入信息之前，请将单元格设置为文本格式，也是防止科学计数法导致错误。';
        $tips3='说明：3.订单号必填';
        $cellTitle=array("订单号","开票流水号","发票代码","发票号码","红票流水号","红票代码","红票号码",$tips1);
        $cellNum=count($cellTitle);//多少列
        $dataNum=count($expTableData);//多少列
        $objPHPExcel = new PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        //设置标题
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->getActiveSheet(0)->getColumnDimension($cellName[$i])->setAutoSize(true);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $cellTitle[$i]);
        }
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue("H2", $tips2);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue("H3", $tips3);
        $objPHPExcel->getActiveSheet()->getStyle( 'H1')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
        $objPHPExcel->getActiveSheet()->getStyle( 'H2')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
        $objPHPExcel->getActiveSheet()->getStyle( 'H3')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);

        $objPHPExcel->getActiveSheet()->setTitle('导入模板');
        $objPHPExcel->setActiveSheetIndex(0);

        for($i=0;$i<$dataNum;$i++)
        {//多少行
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('A'.($i+2),$expTableData[$i]['tid'],PHPExcel_Cell_DataType::TYPE_STRING);
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$xlsTitle.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit();
    }

    /**
     * 增值税发票信息导入
     * @return bool
     */
    public function invoice_info_import()
    {
        $params = input::get();
        //获取文件路径
        $filePath = realpath($params['f_name']);
        //读取excel数据
        $excelData = $this->getExcelData($filePath);

        //判断数据是否有效
        if($excelData['error'] == 1)
        {
            //读取sheet0的数据
            $invoiceList = $excelData['data'][0];
            $record = array_slice($invoiceList,1);

            //去除表头
            if(is_array($record) && !empty($record))
            {
                $account_data = [];
                foreach($record as $tk => $r)
                {
                    $error_message = '';
                    if(empty($r['A']))
                    {
                        $error_message .= '订单号为空';
                    }
                    if(empty($r['B']) && empty($r['C']) && empty($r['D'])  && empty($r['E'])  && empty($r['F'])  && empty($r['G']))
                    {
                        continue;
                    }

                    $tid = trim($r['A']);
                    $serial_no = trim($r['B']);
                    $invoice_code = trim($r['C']);
                    $invoice_no   = trim($r['D']);
                    $cancel_serial_no    = trim($r['E']);
                    $cancel_invoice_code = trim($r['F']);
                    $cancel_invoice_no   = trim($r['G']);

                    $invoice_data['tid']                  = $tid;
                    $invoice_data['serial_no']            = $serial_no;
                    $invoice_data['invoice_code']         = $invoice_code;
                    $invoice_data['invoice_no']           = $invoice_no;
                    $invoice_data['cancel_serial_no']     = $cancel_serial_no;
                    $invoice_data['cancel_invoice_code']  = $cancel_invoice_code;
                    $invoice_data['cancel_invoice_no']    = $cancel_invoice_no;
                    $invoice_data['error_message']        = $error_message;

                    $account_data[] = $invoice_data;
                }

                $tradeInvoiceModel = app::get('systrade')->model('trade_invoice');
                try
                {
                    $tk = 1;
                    $tmp_data = [];
                    $excel_data = [];
                    $insert_time = time();

                    foreach($account_data as $v)
                    {
                        $trade_invoice_info = $tradeInvoiceModel->getRow('*',['tid'=>$v['tid']]);
                        if(!empty($trade_invoice_info))
                        {
                            $update_data = $v;

                            if(($invoice_data['serial_no'] != $trade_invoice_info['serial_no'])
                                || ($invoice_data['invoice_code'] != $trade_invoice_info['invoice_code'])
                                || ($invoice_data['invoice_no'] != $trade_invoice_info['invoice_no']) )
                            {
                                $update_data['push_time'] = time();
                                $update_data['push_status'] = 2;
                            }

                            if(($invoice_data['cancel_serial_no'] != $trade_invoice_info['cancel_serial_no'])
                                || ($invoice_data['cancel_invoice_code'] != $trade_invoice_info['cancel_invoice_code'])
                                || ($invoice_data['cancel_invoice_no'] != $trade_invoice_info['cancel_invoice_no']) )
                            {
                                $update_data['invalid_time'] = time();
                                $update_data['is_invalid']   = 1;
                            }

                            $tradeInvoiceModel->update($update_data,['id'=>$trade_invoice_info['id']]);
                        }
                        else
                        {
                            $v['error_message'] = '没有对应的发票信息';
                        }
                        if($v['error_message'])
                        {
                            $tmp_data['A'] =  $tk;
                            $tmp_data['B'] =  $v['tid'];
                            $tmp_data['C'] =  $v['error_message'];
                            $excel_data[] = $tmp_data;
                            $tk++;
                        }
                    }

                    $data = array('code' => 1,'msg' => '导入完成，请查看日志');
                    $dataFileName = $this->shopId.'invoice_import_log'.date('Y-m-d-H-i-s');
                    $data['excel_path'] = $this->exportExcel($dataFileName,$excel_data);
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
    public function exportExcel($expTitle, $expTableData)
    {

        $xlsTitle  = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName  = $xlsTitle; //文件名称可根据自己情况设定
        $cellTitle = array("序号","订单号","错误信息");
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
        $savePath= 'csvs/trade_invoice/';
        $realSavePath=$savePath.$xlsTitle.'.xls';
        $objWriter->save($realSavePath);
        return $xlsTitle.'.xls';
    }

    /**
     * @param $delivery_status
     * @return mixed
     * 获取物流状态
     */
    private function _getPushStatus($push_status)
    {
        $push_status_array = [
            '0' => '未推送',
            '1' => '推送中',
            '2' => '推送成功',
            '3' => '推送失败',
        ];
        return $push_status_array[$push_status];
    }

    /**
     * @param $invoice_type
     * @return mixed
     * 获取发票类型
     */
    private function _getInvoiceType($invoice_type)
    {
        $invoice_type_array = [
            'normal' => '电子普票',
            'vat' => '增值税专票',
        ];
        return $invoice_type_array[$invoice_type];
    }
}