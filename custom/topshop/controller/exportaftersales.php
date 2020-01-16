<?php

/**
 * 售后订单导出
 *
 * @Author 王衍生 50634235@qq.com
 */
class topshop_ctl_exportaftersales extends topshop_controller
{

    public function view()
    {
        //导出方式 直接导出还是通过队列导出
        $pagedata['check_policy'] = 'download';

        $filetype = array(
            // 'csv'=>'.csv',
            'xls' => '.xls',
        );

        $pagedata['orderBy'] = input::get('orderBy');
        $supportType = input::get('supportType');
        //支持导出类型
        if ($supportType && $filetype[$supportType]) {
            $pagedata['export_type'] = array($supportType => $filetype[$supportType]);
        } else {
            $pagedata['export_type'] = $filetype;
        }

        return view::make('topshop/exportaftersales/export.html', $pagedata);
    }

    public function export()
    {
        //导出
        if (input::get('filter')) {
            $filter = json_decode(input::get('filter'), true, 512, JSON_BIGINT_AS_STRING);

        }
        $where = " a.shop_id = {$this->shopId}";

        if ($filter['created_time']) {
            $times = explode('-', $filter['created_time']);
            $times[0] = strtotime($times[0]);
            $times[1] = strtotime($times[1]) + 86400;
            $where .= " and a.created_time >= {$times[0]} and a.created_time <= {$times[1]}";
        }

        if ($filter['tid']) {
            $where .= " and a.tid = '{$filter['tid']}'";
        }

        if ($filter['aftersales_bn']) {
            $where .= " and a.aftersales_bn = '{$filter['aftersales_bn']}'";
        }

        if ($filter['aftersales_type']) {
            $where .= " and a.aftersales_type = '{$filter['aftersales_type']}'";
        }

        if ($filter['progress']) {
            if($filter['progress'] == '3-4-6-7'){
                $where .= " and a.progress in (3, 4, 6, 7)";
            }elseif($filter['progress'] != 'all'){
                $where .= " and a.progress = '{$filter['progress']}'";
            }
        }

        if ($filter['title']) {
            $where .= " and a.title like '%{$filter['title']}%'";
        }

        $aftersalesLists = db::connection()->createQueryBuilder()->select('a.aftersales_bn, a.tid, a.oid, a.aftersales_type, a.progress, a.status, a.reason, a.description, a.shop_explanation, a.refunds_reason, a.created_time, a.gift_data, a.num, a.sendback_data, a.sendconfirm_data, t.receiver_state, t.receiver_city, t.receiver_district, t.receiver_address, t.receiver_mobile, o.item_id, o.sku_id, o.title, o.bn, o.spec_nature_info, o.price, o.sendnum, o.payment, o.total_weight, r.refund_bn, u.mobile')
            ->from('sysaftersales_aftersales', 'a')
            ->leftJoin('a', 'systrade_trade', 't', 'a.tid = t.tid')
            ->leftJoin('a', 'systrade_order', 'o', 'a.oid = o.oid')
            ->leftJoin('a', 'sysaftersales_refunds', 'r', 'a.aftersales_bn = r.aftersales_bn')
            ->leftJoin('a', 'sysuser_account', 'u', 'a.user_id = u.user_id')
            ->where($where)
        // ->setFirstResult($offset)
        // ->setMaxResults($limit)
            ->orderBy('a.created_time')
            ->execute()->fetchAll();

        $this->_exportOrderDataToExcel(date('YmdHis',time()) . input::get('name'), $aftersalesLists);
    }

    /**
     * 导出子订单的数据到excel
     */
    private function _exportOrderDataToExcel($expTitle, $expTableData)
    {
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $cellTitle = array('售后单号', '子订单编号', '订单编号', '售后类型', '售后进度', '商品', '规格', '赠品', '数量', '商品价格', '实付金额', '退款单号', '用户手机号', '申请原因', '申请原因描述', '商家处理说明', '商家退款备注', '申请时间', '收货人地址', '收货人手机号', '退回商品物流信息', '再发商品物流信息');
        $objPHPExcel = new PHPExcel();
        $cellNum = count($cellTitle);//多少列
        $dataNum = count($expTableData);//多少行
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        $color = '0x00000000';
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => array('argb' => $color),
                ),
            ),
        );
        //表格边框样式
        $objPHPExcel->getActiveSheet()->getStyle('A1:' . $cellName[$cellNum - 1] . ($dataNum + 1))->applyFromArray($styleArray);

        // 设置一些列的宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(50);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(40);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(40);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(40);
        $objPHPExcel->getActiveSheet()->getColumnDimension('T')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('U')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('V')->setWidth(20);

        // 设置列表题
        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '1', $cellTitle[$i]);
        }

        $aftersales_type = [
            'ONLY_REFUND' => app::get('topshop')->_('退款'),
            'REFUND_GOODS' => app::get('topshop')->_('退货退款'),
            'EXCHANGING_GOODS' => app::get('topshop')->_('换货'),
        ];

        $progress = array(
            '0' => app::get('topshop')->_('等待审核'),
            '1' => app::get('topshop')->_('等待买家回寄'),
            '2' => app::get('topshop')->_('待确认收货'),
            '3' => app::get('topshop')->_('商家已驳回'),
            '4' => app::get('topshop')->_('商家已处理'),
            '5' => app::get('topshop')->_('商家已收货'),
            '6' => app::get('topshop')->_('平台已驳回'),
            '7' => app::get('topshop')->_('平台已处理'),
            '8' => app::get('topshop')->_('等待退款'),
            '9' => app::get('topshop')->_('换货中'),
            // '3-4-6-7' => app::get('topshop')->_('已完成'),//换货的时候可以直接在商家处理结束
        );

        foreach ($expTableData as $k => $v) {//多少行
            $i = $k + 2;
            $argb = $i % 2 ? '00FFFFFF' : '00ADFF2F';

            // 不同行，不同背景颜色
            $objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':' . $cellName[$cellNum - 1] . $i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':' . $cellName[$cellNum - 1] . $i)->getFill()->getStartColor()->setARGB($argb);

            $objPHPExcel->getActiveSheet()->setCellValueExplicit('A' . $i, $v['aftersales_bn'], PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('B' . $i, $v['oid'], PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('C' . $i, $v['tid'], PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D' . $i, $aftersales_type[$v['aftersales_type']]);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E' . $i, $progress[$v['progress']]);
            $objPHPExcel->getActiveSheet(0)->setCellValue('F' . $i, $v['title']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('G' . $i, $v['spec_nature_info']);

            $gift_data = $this->_disposeGift($v);
            // 设置可换行
            $objPHPExcel->getActiveSheet(0)->getStyle('H' . $i)->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet(0)->setCellValue('H' . $i, $gift_data);

            $objPHPExcel->getActiveSheet(0)->setCellValue('I' . $i, $v['num']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('J' . $i, $v['price']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('K' . $i, $v['payment']);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('L' . $i, $v['refund_bn'], PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('M' . $i, $v['mobile']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('N' . $i, $v['reason']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('O' . $i, $v['description']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('P' . $i, $v['shop_explanation']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('Q' . $i, $v['refunds_reason']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('R' . $i, date('Y-m-d H:i:s', $v['created_time']));
            $objPHPExcel->getActiveSheet(0)->setCellValue('S' . $i, $v['receiver_state'] . $v['receiver_city'] . $v['receiver_district'] . $v['receiver_address']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('T' . $i, $v['receiver_mobile']);

            $v['sendback_data'] = unserialize(unserialize($v['sendback_data']));
            // 设置可换行
            $objPHPExcel->getActiveSheet(0)->getStyle('U' . $i)->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet(0)->setCellValue('U' . $i, $v['sendback_data']['corp_code'] . $v['sendback_data']['logi_name'] . "\r\n" . $v['sendback_data']['logi_no']);

            $v['sendconfirm_data'] = unserialize(unserialize($v['sendconfirm_data']));
            // 设置可换行
            $objPHPExcel->getActiveSheet(0)->getStyle('V' . $i)->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet(0)->setCellValue('V' . $i, $v['sendconfirm_data']['corp_code'] . $v['sendconfirm_data']['logi_name'] . "\r\n" . $v['sendconfirm_data']['logi_no']);
        }
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$xlsTitle.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    private function _disposeGift($order){
        $str = "";
        $order['gift_data'] = unserialize($order['gift_data']);
        if(is_array($order['gift_data'])){
            foreach($order['gift_data'] as $gift){
                $str .= $gift['title'] . "\r\n" . '数量：' . $gift['gift_num'] . "\r\n";
            }
            $str = substr($str, 0, -2);
        }
        return  $str;
    }
}
