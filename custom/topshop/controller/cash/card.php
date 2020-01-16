<?php

/**
 * Undocumented class
 *
 * @Author 王衍生 50634235@qq.com
 */
class topshop_ctl_cash_card extends topshop_controller
{
    public function card()
    {
        $limit = 20;
        $shop_id = $this->shopId;
        $post_data = input::get();
        $current = $post_data['pages'] ? : 1;
        $offset = ($current - 1) * $limit;

        try {

            $cardsData = $this->getCardsData('*', $post_data, $offset, $limit);
            $card_count = $cardsData['count'];

        } catch (Exception $e) {
            $card_count = 0;
            $total = 0;
            $cardList = [];
        }

        if ($card_count > 0) $total = ceil($card_count / $limit);
        $filter['pages'] = time();
        $page_data['pagers'] = array(
            'link' => url::action('topshop_ctl_cash_card@card', $filter),
            'current' => $current,
            'use_app' => 'topshop',
            'total' => $total,
            'token' => $filter['pages'],
        );
        $page_data['filter'] = $post_data;
        $page_data['cardList'] = $cardsData['list'];
        $page_data['card_count'] = $card_count;
        return $this->page('topshop/cash/card_index.html', $page_data);
    }
    public function createCard()
    {
        return $this->page('topshop/cash/create_card.html', $page_data);
    }

    public function cardSave()
    {
        $post_data = input::get();
        try {
            $client_code = $post_data['client_code'];
            if (!preg_match('/^\d{4}$/', $client_code)) {
                throw new LogicException('客户编号应为4位数字！');
            }
            $value = $post_data['value'];
            if (!preg_match('/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/', $value)) {
                throw new LogicException('请输入正确的礼金面额！');
            }
            $amount = $post_data['amount'];

            $cash_card_model = app::get('sysshop')->model('cash_card');
            $save_data = [
                'shop_id' => $this->shopId,
                'client_code' => $client_code,
                'value' => $value,
                'create_time' => time(),
            ];

            $shop_id = str_pad($this->shopId, 3, "0", STR_PAD_LEFT);
            $quantity = 0;
            for ($i = 0; $i < $amount; $i++) {
                $id = $this->getId($client_code);
                $card_id = '6' . $shop_id . $client_code . '01' . $id;
                $exchange_password = $this->createPasswordStr();
                $save_data['card_id'] = $card_id;
                $save_data['exchange_password'] = $exchange_password;

                if ($cash_card_model->insert($save_data)) {
                    $quantity++;
                }
            }

            return $this->splash('success', null, "成功创建{$quantity}张礼金卡。");
        } catch (Exception $e) {
            $msg = $e->getMessage();
            return $this->splash('success', null, $msg);
        }
    }

    /**
     * 卡号id,相当于一个计数器
     *
     * @param [type] $tid
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    private function getId($client_code)
    {
        $id = redis::scene('syspromotion')->incr("cash_card_id:{$this->shopId}:{$client_code}");
        // redis::scene('syslogistics')->expire('delivery_aggregation_id:' . $date, 3600 * 25);
        // 用0补位
        $id = str_pad($id, 7, "0", STR_PAD_LEFT);

        return $id;
    }
    /**
     * 创建卡密
     *
     * @param integer $length
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    private function createPasswordStr($length = 8)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private function getCardsData($cols='*', $post_data=array(), $offset=0, $limit=-1, $orderBy=null)
    {
        if(trim($post_data['card_id'])){
            $filter['card_id'] = trim($post_data['card_id']);
        }

        if(trim($post_data['client_code'])){
            $filter['client_code'] = trim($post_data['client_code']);
        }

        if(trim($post_data['value'])){
            $filter['value'] = trim($post_data['value']);
        }

        if(trim($post_data['mobile'])){
            $filter['mobile'] = trim($post_data['mobile']);
            $user_id = app::get('systrade')->rpcCall('user.get.account.id', ['user_name' => $filter['mobile']]);
            $user_id = $user_id[$filter['mobile']];

            if($user_id){
                $filter['user_id'] = $user_id;
            }
        }

        $cash_card_model = app::get('sysshop')->model('cash_card');
        $cardList = $cash_card_model->getList($cols, $filter, $offset, $limit, $orderBy);
        $user_id_array = array_column($cardList, 'user_id');
        $user_id_array = array_filter($user_id_array);
        if($user_id_array){
            $userModel = app::get('sysuser')->model('account');
            $user_info = $userModel->getList('user_id, mobile', ['user_id' => $user_id_array]);
            $user_info = array_bind_key($user_info, 'user_id');
            foreach ($cardList as &$list) {
                $list['user_mobile'] = $user_info[$list['user_id']]['mobile'];
            }
        }
        $CardsData = [
            'list' => $cardList,
            'count' => $cash_card_model->count($filter),
        ];
        return $CardsData;
    }

    public function exportView()
    {
        //导出方式 直接导出还是通过队列导出
        $pagedata['check_policy'] = 'download';

        $filetype = array(
            // 'csv'=>'.csv',
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

        return view::make('topshop/cash/card_export.html', $pagedata);
    }

    public function export()
    {
        //导出
        if (input::get('filter')) {
            $filter = json_decode(input::get('filter'), true, 512, JSON_BIGINT_AS_STRING);

        }
        $cardsData = $this->getCardsData('*', $filter, 0, -1);

        $this->_exportDataToExcel(date('YmdHis',time()) . input::get('name'), $cardsData['list']);
    }

    /**
     * 导出子订单的数据到excel
     */
    private function _exportDataToExcel($expTitle, $expTableData)
    {
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $cellTitle = array('卡号', '兑换密码', '客户编码', '礼金价值', '创建时间', '兑换用户','兑换时间');
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
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);

        // 设置列表题
        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '1', $cellTitle[$i]);
        }

        foreach ($expTableData as $k => $v) {//多少行
            $i = $k + 2;
            $argb = $i % 2 ? '00FFFFFF' : '00ADFF2F';

            // 不同行，不同背景颜色
            $objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':' . $cellName[$cellNum - 1] . $i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':' . $cellName[$cellNum - 1] . $i)->getFill()->getStartColor()->setARGB($argb);

            $objPHPExcel->getActiveSheet()->setCellValueExplicit('A' . $i, $v['card_id'], PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('B' . $i, $v['exchange_password']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C' . $i, $v['client_code']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D' . $i, $v['value']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E' . $i, date('Y-m-d H:i:s', $v['create_time']));
            $objPHPExcel->getActiveSheet(0)->setCellValue('F' . $i, $v['user_mobile']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('G' . $i, date('Y-m-d H:i:s', $v['exchange_time']));
        }
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$xlsTitle.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }
}