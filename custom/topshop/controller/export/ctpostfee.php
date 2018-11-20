<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/10/24
 * Time: 16:39
 */

class topshop_ctl_export_ctpostfee
{
    /**
     * 导出对账单
     */
    public function exportPostFee()
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

        if($post_data['post_fee_compare_status']) $dbfilter['post_fee_compare_status'] = $post_data['post_fee_compare_status'];

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
            $v['post_fee_compare_status'] = $this->__getPostFeeCompareStatus($v['status']);
        }

        $data['content'] = $list;
        $data['name'] = $name;
        $data['ctitle'] = $ctitle;

        $title = array('聚合id', '物流单号', '预估运费', '物流实收运费');
        $fileName = $this->shopId.'ct_post_fee_'.$data['name'].'_'.time();
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
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '城通运费对比明细('.$data['ctitle'].')');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:E1');
        for($i=0;$i<$cellNum;$i++)
        {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $cellTitle[$i]);
        }
        //生成内容

        for($i=0;$i<$dataNum;$i++)
        {//多少行
            $objPHPExcel->getActiveSheet(0)->setCellValue('A'.($i+3),$expTableData[$i]['aggregation_id']);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit('B'.($i+3),$expTableData[$i]['logi_no'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+3),$expTableData[$i]['estimate_freight']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+3),$expTableData[$i]['post_fee_compare_status']);
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$fileName.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $dir = 'csvs/ctbill';
        $del_file = scandir($dir);
        foreach($del_file as $path)
        {
            if(strpos($path,$this->shopId.'ctpostfee') === 0)
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
     * @param $bill_status
     * @return mixed
     * 获取对账状态
     */
    private function __getPostFeeCompareStatus($bill_status)
    {
        $bill_status_array = [
            'UN_CHECKED' => '未对比',
            'CHECKED_SUCC' => '对比成功',
            'CHECKED_FAIL' => '对比失败',
        ];
        return $bill_status_array[$bill_status];
    }
}