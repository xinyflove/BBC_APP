<?php

/**
 * Undocumented class
 *
 * @Author 王衍生 50634235@qq.com
 */
class topshop_ctl_promotion_exchangecode extends topshop_controller
{
    /**
     * 方案列表
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function project_list()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('兑换方案管理');

        $limit = 20;
        $shop_id = $this->shopId;
        $post_data = input::get();
        $current = $post_data['pages'] ? : 1;
        $offset = ($current - 1) * $limit;

        try {
            $shopping_exchange_model = app::get('syspromotion')->model('shopping_exchange');
            $condition['shop_id'] = $shop_id;

            $projectCount = $shopping_exchange_model->count($condition);
            $projectList = $shopping_exchange_model->getList('*', $condition, $offset, $limit, 'create_time desc');

        } catch (Exception $e) {
            $card_count = 0;
            $total = 0;
            $cardList = [];
        }

        if ($projectCount > 0) $total = ceil($projectCount / $limit);
        $filter['pages'] = time();
        $page_data['pagers'] = array(
            'link' => url::action('topshop_ctl_cash_card@card', $filter),
            'current' => $current,
            'use_app' => 'topshop',
            'total' => $total,
            'token' => $filter['pages'],
        );
        $page_data['filter'] = $post_data;
        $page_data['projectList'] = $projectList;
        $page_data['projectCount'] = $projectCount;
        return $this->page('topshop/promotion/exchangecode/project_list.html', $page_data);
    }
    /**
     * 查看方案
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function project_show()
    {
        $post_data = input::get();

        $this->contentHeaderTitle = app::get('topshop')->_('查看兑换方案');
        $shopping_exchange_model = app::get('syspromotion')->model('shopping_exchange');
        $shopping_exchange_code_model = app::get('syspromotion')->model('shopping_exchange_code');

        $page_data['project_data'] = $shopping_exchange_model->getRow('*', ['shop_id' => $this->shopId, 'id' => $post_data['project_id']]);

        $condition['exchange_id'] = $post_data['project_id'];
        $condition['shop_id'] = $this->shopId;
        $page_data['card_data'] = $this->getListData('*', $condition, 0, -1);

        return $this->page('topshop/promotion/exchangecode/show.html', $page_data);

    }
    /**
     * 编辑方案
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function project_edit()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('编辑兑换方案');

        return $this->page('topshop/promotion/exchangecode/project_edit.html', $page_data);
    }
    /**
     *保存方案
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function project_save()
    {
        try
        {
            $params = input::get();
            $projectItem = null;
            foreach( $params['sku_id'] as $sku_id )
            {
                $item = [];
                $item['sku_id'] = $sku_id;
                $item['title'] = $params['sku_title'][$sku_id];
                if (empty($item['title'])) {
                    throw new LogicException('商品标题不能为空！');
                }
                $item['spec_info'] = $params['spec_info'][$sku_id];
                $item['quantity'] = $params['sku_quantity'][$sku_id];
                if (!is_numeric($item['quantity']) || $item['quantity'] < 0) {
                    throw new LogicException('商品数量为数字且不能小于0！');
                }
                $item['weight'] = $params['sku_weight'][$sku_id];
                if (!is_numeric($item['weight']) || $item['weight'] < 0) {
                    throw new LogicException('商品重量为数字且不能小于0！');
                }
                $projectItem[] = $item;
            }

            foreach( $params['no_sku_title'] as $key => $value )
            {
                $item = [];
                $item['sku_id'] = 0;
                $item['title'] = $params['no_sku_title'][$key];
                if (empty($item['title'])) {
                    throw new LogicException('商品标题不能为空！');
                }
                $item['spec_info'] = $params['no_spec_info'][$key];
                $item['quantity'] = $params['no_sku_quantity'][$key];
                if (!is_numeric($item['quantity']) || $item['quantity'] < 0) {
                    throw new LogicException('商品数量为数字且不能小于0！');
                }
                $item['weight'] = $params['no_sku_weight'][$key];
                if (!is_numeric($item['weight']) || $item['weight'] < 0) {
                    throw new LogicException('商品重量为数字且不能小于0！');
                }
                $projectItem[] = $item;
            }

            $apiData = [];
            $apiData['project_name'] = $params['project_name'];
            if (empty($apiData['project_name'])) {
                throw new LogicException('方案标题不能为空！');
            }
            $apiData['total_num'] = $params['total_num'];
            if (!is_numeric($apiData['total_num']) || $apiData['total_num'] < 1) {
                throw new LogicException('生成卡数量为数字且不能小于1！');
            }
            $apiData['item_info'] = $projectItem;
            $apiData['create_time'] = time();
            $apiData['shop_id'] = $this->shopId;

            $shopping_exchange_model = app::get('syspromotion')->model('shopping_exchange');
            $project_id = $shopping_exchange_model->insert($apiData);
            if (!$project_id) {
                throw new LogicException('保存失败！');
            }
            $total_num = $this->cardSave($project_id, $params['total_num']);
            $url = url::action('topshop_ctl_promotion_exchangecode@project_list');
            $msg = app::get('topshop')->_("创建成功，生成{$total_num}张卡!");
            return $this->splash('success', $url, $msg, true);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', null, $msg, true);
        }
    }

    private function cardSave($project_id, $total_num)
    {
            $shopping_exchange_code_model = app::get('syspromotion')->model('shopping_exchange_code');

            $max_code = $shopping_exchange_code_model->getRow('exchange_code', ['shop_id' => $this->shopId], 'exchange_code desc');
            $init_code = $max_code['exchange_code'] ? ($max_code['exchange_code'] + 1) : 81000000;

            $quantity = 0;
            for ($i = 0; $i < $total_num; $i++) {
                $save_data = [];
                $matches = [];
                if (preg_match('/4(\d*)$/', $init_code, $matches)) {
                    $new_str = str_pad('5', strlen($matches[1]) + 1, '0', STR_PAD_RIGHT);
                    $init_code = preg_replace("/{$matches[0]}$/", $new_str, $init_code);
                }

                $save_data['exchange_code'] = $init_code;
                $save_data['exchange_password'] = $this->createPasswordStr();
                $save_data['shop_id'] = $this->shopId;
                $save_data['exchange_id'] = $project_id;
                $save_data['create_time'] = time();
                if ($shopping_exchange_code_model->insert($save_data)) {
                    $quantity++;
                }
                $init_code = (int) $init_code + 1;
            }

            return $quantity;
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
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
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

        return view::make('topshop/promotion/exchangecode/export.html', $pagedata);
    }

    public function export()
    {
        //导出
        if (input::get('filter')) {
            $filter = json_decode(input::get('filter'), true);
        }
        $condition['exchange_id'] = $filter['project_id'];
        $condition['shop_id'] = $this->shopId;
        $data = $this->getListData('*', $condition, 0, -1);

        $this->_exportDataToExcel(date('YmdHis',time()) . input::get('name'), $data['list']);
    }

    private function getListData($cols='*', $filter=array(), $offset=0, $limit=-1, $orderBy=null)
    {
        $shopping_exchange_code_model = app::get('syspromotion')->model('shopping_exchange_code');
        $dataList = $shopping_exchange_code_model->getList($cols, $filter, $offset, $limit, $orderBy);
        $user_id_array = array_column($dataList, 'user_id');
        $user_id_array = array_filter($user_id_array);
        if($user_id_array){
            $userModel = app::get('sysuser')->model('account');
            $user_info = $userModel->getList('user_id, mobile', ['user_id' => $user_id_array]);
            $user_info = array_bind_key($user_info, 'user_id');
            foreach ($dataList as &$list) {
                $list['user_mobile'] = $user_info[$list['user_id']]['mobile'];
            }
        }
        $data = [
            'list' => $dataList,
            'count' => $shopping_exchange_code_model->count($filter),
        ];
        return $data;
    }
    /**
     * 导出子订单的数据到excel
     */
    private function _exportDataToExcel($expTitle, $expTableData)
    {
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $cellTitle = array('卡号', '兑换密码', '创建时间', '兑换用户','兑换时间');
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
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);

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

            $objPHPExcel->getActiveSheet()->setCellValueExplicit('A' . $i, $v['exchange_code'], PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('B' . $i, $v['exchange_password']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C' . $i, date('Y-m-d H:i:s', $v['create_time']));
            $objPHPExcel->getActiveSheet(0)->setCellValue('D' . $i, $v['user_mobile']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E' . $i, $v['exchange_time'] ? date('Y-m-d H:i:s', $v['exchange_time']) : '');
        }
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$xlsTitle.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }
}