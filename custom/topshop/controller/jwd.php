<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2019/1/7
 * Time: 11:06
 */

class topshop_ctl_jwd extends topshop_controller
{
    public function accountBalance()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('万达对账');
        $postSend = input::get();

        if($postSend['inner_code']) $filter['inner_code'] = $pagedata['inner_code'] = trim($postSend['inner_code']);

        $limit = 20;
        $count = app::get('sysclearing')->model('jwd_billing')->count($filter);
        $totalPage = ceil($count/$limit);
        $page_no = $postSend['pages'] ? $postSend['pages'] : 1;
        $offset = ($page_no-1)*$limit;
        $data = app::get('sysclearing')->model('jwd_billing')->getList('*',$filter,$offset,$limit,'created_time desc');

        $pagedata['data']   = $data;
        $pagedata['limits'] = $limit;
        $pagedata['pages']  = $page_no;
        $postSend['pages']  = time();
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_jwd@accountBalance',$postSend),
            'current' => $page_no,
            'total'   => $totalPage,
            'use_app' => 'topshop',
            'token'   => $postSend['pages']
        );
        return $this->page('topshop/jinwanda/account_balance.html', $pagedata);
    }

    public function view()
    {
        return view::make('topshop/import/jwd_import.html');
    }

    /**
     * 处理文件上传
     */
    public function uploadCsvFile()
    {
        $dir = 'csvs/jwd';
        $del_file = scandir($dir);
        foreach($del_file as $path)
        {
            if(strpos($path,$this->shopId.'jwd_sell') === 0)
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
            $path = 'csvs/jwd/'.$this->shopId.'jwd_sell'.date('YmdHis').'.csv';
            move_uploaded_file($tmp_name, $path);
            echo json_encode(array('code' => 1, 'msg' => "上传成功!", 'f_name' => $path));
        }
    }

    /**
     * 下载订单物流信息导入模板
     */
    public function downLoadCsvTpl()
    {
        //定制excel信息
        //标题
        $expTitle='金万达对账信息导入模板';
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $tips1='说明：1.请不要编辑导出表格中的信息，防止变成科学计数法导致导入失败。';
        $tips2='说明：2.模板填入信息之前，请将单元格设置为文本格式，也是防止科学计数法导致错误。';
        $tips3='说明：3.条形码必填，如果是套装，且套装内仅有一种商品，则必须填写内码';
        $cellTitle=array("条形码","内码","商品名称","购买数量","备注",$tips1);
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

    public function export()
    {

    }

    /**
     * 处理金万达excel文件导入方法
     * @return bool
     */
    public function bill_account()
    {
        $params = input::get();
        //获取文件路径
        $filePath = realpath($params['f_name']);
        //读取excel数据
        $excelData = $this->getExcelData($filePath);

        $billModel = app::get('sysclearing')->model('jwd_billing');
        $noequal_number = $billModel->count(['ori_surplus_number|noequal' => 'surplus_number']);
        if($noequal_number > 0)
        {
            $data = array('code' => 0,'msg' => '请先同步上次的原始剩余数量和剩余数量，防止核算失败');
            echo json_encode($data);
        }
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
                    if(empty($r['A']) && empty($r['B']) && empty($r['C']) && empty($r['D']))
                    {
                        continue;
                    }

                    if(empty($r['A']) && empty($r['B']))
                    {
                        $error_message .=  '条形码和内码不能都缺失';
                    }
                    if(empty($r['D']))
                    {
                        $error_message .=  '售卖数量不能为空';
                    }
                    if(!is_numeric($r['D']))
                    {
                        $error_message .=  '售卖数量不是数字';
                    }
                    //如果内码不为空
                    $bar_code = trim($r['A']);
                    $inner_code = trim($r['B']);
                    $item_title = trim($r['C']);
                    $sale_num   = trim($r['D']);
                    if(!empty($inner_code) && $error_message == '')
                    {
                        $account_data[$inner_code]['inner_code']      = $inner_code;
                        $account_data[$inner_code]['bar_code']        = $bar_code;
                        $account_data[$inner_code]['item_title']      = $item_title;
                        $account_data[$inner_code]['total_sale_num']  += (int)$sale_num;
                    }
                    else
                    {
                        $bar_data['inner_code']      = $inner_code;
                        $bar_data['bar_code']        = $bar_code;
                        $bar_data['item_title']      = $item_title;
                        $bar_data['total_sale_num']  = (int)$sale_num;
                        $bar_data['error_message']   = $error_message;
                        $account_data[] = $bar_data;
                    }
                }

                $sku_model = app::get('sysitem')->model('sku');
                try
                {
                    $tk = 1;
                    $tmp_data = [];
                    $excel_data = [];
                    $insert_time = time();
                    foreach($account_data as $code=>$v)
                    {
                        if($code)
                        {
                            $sku_info = $sku_model->getRow('inner_num',['inner_code'=>$code]);
                            if(!empty($sku_info))
                            {
                                $bill_info = $billModel->getRow('id,surplus_number,ori_surplus_number',['inner_code'=>$code]);
                                $total_sale_num = $v['total_sale_num'];
                                if(!empty($bill_info))
                                {
                                    $total_sale_num += $bill_info['ori_surplus_number'];
                                }

                                $inner_num = $sku_info['inner_num'];
                                $cycle_num = floor($total_sale_num/$inner_num);
                                $v['total_sale_num'] = $cycle_num;
                                $surplus_num = $v['total_sale_num']-$cycle_num;
                                $v['surplus_num'] = $surplus_num;

                                $surplus_data['surplus_number'] = $surplus_num;
                                $surplus_data['barcode']    = $v['bar_code'];
                                $surplus_data['inner_code'] = $v['inner_code'];
                                $surplus_data['item_title'] = $v['item_title'];
                                $surplus_data['modified_time'] = $insert_time;

                                if(!empty($bill_info))
                                {
                                    $billModel->update($surplus_data,['id'=>$bill_info['id']]);
                                }
                                else
                                {
                                    $surplus_data['ori_surplus_number']  = $surplus_num;
                                    $surplus_data['created_time']  = $insert_time;
                                    $billModel->insert($surplus_data);
                                }
                            }
                            else
                            {
                                $v['error_message'] = '没有对应的内码';
                            }
                        }

                        $tmp_data['A'] =  $tk;
                        $tmp_data['B'] =  $v['bar_code'];
                        $tmp_data['C'] =  $v['inner_code'];
                        $tmp_data['D'] =  $v['item_title'];
                        $tmp_data['E'] =  $v['total_sale_num'];
                        $tmp_data['F'] =  $v['surplus_num'];
                        $tmp_data['G'] =  $v['error_message'];

                        $excel_data[] = $tmp_data;
                        $tk++;
                    }

                    $data = array('code' => 1,'msg' => '导入完成，请查看日志');
                    $dataFileName = $this->shopId.'jwd_account'.date('Y-m-d-H-i-s');
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

    public function syncSuplusNumber()
    {
        try
        {
            $qb = app::get('sysclearing')->database()->createQueryBuilder();
            $qb->update('sysclearing_jwd_billing', 'jwd')->set('jwd.ori_surplus_number', 'jwd.surplus_number')->execute();
            return $this->splash('success',null,'同步成功');
        }
        catch(Exception $e) {
            return $this->splash('error',null,$e->getMessage());
        }
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
        $cellTitle = array("序号","条形码","内码","商品名称","售卖套数","剩余数量","错误信息");
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
        $savePath= 'csvs/jwd/';
        $realSavePath=$savePath.$xlsTitle.'.xls';
        $objWriter->save($realSavePath);
        return $xlsTitle.'.xls';
    }
}