<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 7/11/2017
 * Time: 7:33 PM
 * 物流单号存储在 systrade_order 表中，字段 invoice_no - 子订单所在包裹的运单号，字段 logistics_company - 子订单发货的快递公司
 */
class topshop_ctl_import extends topshop_controller{
    /**
     * 导入页面-模态显示
     * @return mixed
     */
    public function view(){
        return view::make('topshop/import/import.html');
    }

    /**
     * 下载订单物流信息导入模板
     */
    public function downLoadCsvTpl(){
        //抓取未发货状态的订单编号
        $postFilter=input::get();
        foreach($postFilter as $k => $v){
            if(!is_array($v)){
                $postFilter[$k]=trim($v);
            }
        }
        $filter = $this->_checkParams($postFilter);
        // if($filter['tids']){
        //     $params['tid|in']=explode(',',$filter['tids']);
        // }else{
        //     $params = array(
        //         'tid' => $filter['tid'],
        //         'create_time|than' =>$filter['created_time_start'],
        //         'create_time|lthan' =>$filter['created_time_end'],
        //         'receiver_mobile' =>$filter['receiver_mobile'],
        //         'receiver_phone' =>$filter['receiver_phone'],
        //         'receiver_name' =>$filter['receiver_name'],
        //         'user_name' =>$filter['user_name'],
        //         'shipping_type' =>$filter['shipping_type'],
        //         'order_by' =>'created_time desc',
        //         'title|has'=>$filter['keyword'],
        //         'supplier_id'=>$filter['supplier_id'],
        //     );
        // }
        // $params['shop_id']=$this->shopId;
        // $params['status|in']=array('WAIT_SELLER_SEND_GOODS','PARTIAL_SHIPMENT');
        // $params['cancel_status']='NO_APPLY_CANCEL';
        // $toSendList=app::get('systrade')->model('order')->getList('*',$params);

        $status = 'WAIT_SELLER_SEND_GOODS,PARTIAL_SHIPMENT';

        if($filter['tids']){
            $params['tid'] = $filter['tids'];
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
            );
        }

        //显示订单售后状态
        // $params['is_aftersale'] = true;
        $params['shop_id'] = $this->shopId;
        $toSendList = app::get('topshop')->rpcCall('trade.get.orderlist',$params,'seller');
        $toSendList = array_merge($toSendList['list']);
        foreach($toSendList as $key => $orderlist){
            $toSendList[$key]['dlyinfo']=$this->_getTradeDlyInfo($orderlist['tid']);
            $toSendList[$key]['sent_num']=$this->_getSentNum($orderlist['oid']);
        }

        //定制excel信息
        //标题
        $expTitle='青岛广电批量发货导入模板';
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $tips1='说明：1.请不要编辑导出表格中的订单号，防止变成科学计数法导致导入失败。';
        $tips2='说明：2.模板填入订单号之前，请将单元格设置为文本格式，也是防止科学计数法导致单号错误。';
        $tips3='说明：3.物流公司名称和代码对应关系，申通快递-STO,顺丰快递-SF,圆通速递-YTO,韵达快递-YD,EMS快递-EMS,百世汇通-HTKY,圆通快递-YTO,天天快递-HHTT,德邦物流-DBL,中通速递-ZTO,';
        $cellTitle=array("订单号","子订单号","商品信息","购买数量","已发货数量","发货数量","物流公司(必填）","物流公司代码(必填）","物流单号(必填）","操作状态","备注",$tips1);
        $cellNum=count($cellTitle);//多少列
        $dataNum = count($toSendList);//多少行
        $objPHPExcel = new PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        //设置标题
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->getActiveSheet(0)->getColumnDimension($cellName[$i])->setAutoSize(true);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $cellTitle[$i]);
        }
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue("L2", $tips2);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue("L3", $tips3);
        $objPHPExcel->getActiveSheet()->getStyle( 'L1')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
        $objPHPExcel->getActiveSheet()->getStyle( 'L2')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
        $objPHPExcel->getActiveSheet()->getStyle( 'L3')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
        //填充数据
        for($i=0;$i<$dataNum;$i++){//行
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('A'.($i+2),$toSendList[$i]['tid'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('B'.($i+2),$toSendList[$i]['oid'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('C'.($i+2),$toSendList[$i]['title'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('D'.($i+2),$toSendList[$i]['num'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('E'.($i+2),$toSendList[$i]['sent_num'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('K'.($i+2), $toSendList[$i]['dlyinfo']);
        }

        $objPHPExcel->getActiveSheet()->setTitle('导入模板');
        $objPHPExcel->setActiveSheetIndex(0);
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$xlsTitle.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit();
    }

    public function downloadLogFile(){

        //$params['shop_id']=$this->shopId;
        //$params['status']='WAIT_SELLER_SEND_GOODS';

        $file=input::get('file');
        $dir = 'csvs/';
        if( !file_exists($dir.$file) ) {    //检查文件是否存在
            echo   "文件找不到";
        } else {
            $file = fopen($dir.$file, "r"); // 打开文件
            // 输入文件标签
            Header("Content-type: application/octet-stream");
            Header("Accept-Ranges: bytes");
            Header("Accept-Length: ".filesize($dir.$file));
            Header("Content-Disposition: attachment; filename=" . $file);
            // 输出文件内容
            echo fread($file,filesize($dir.$file));
            fclose($file);
        }
        exit();
    }

    /**
     * 处理文件上传
     */
    public function uploadCsvFile() {
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
            if($file_type !=='application/vnd.ms-excel'){
                echo json_encode(array('code'=>0,'msg'=>'文件类型错误，请上传填写好的模板文件'));
                return;
            }

            $tmp_name = $_FILES["csv_file"]["tmp_name"];
            $path = 'csvs/'.date('YmdHis').'.csv';
            move_uploaded_file($tmp_name, $path);

            echo json_encode(array('code' => 1, 'msg' => "上传成功!", 'f_name' => $path));
        }
    }

    /**
     * 处理物流订单信息excel文件导入方法
     * @return bool
     */
    public function import(){

        $dlyCorp=$this->_getShopDlyList();
        if(empty($dlyCorp)){
            echo json_encode(array('code' => 0, 'msg' => '当前店铺无快递服务'));
        }
        $params=input::get();
        //获取文件路径
        $filePath=realpath($params['f_name']);
        //读取excel数据
        $excelData=$this->getExcelData($filePath);
        $tradeModel=app::get('systrade')->model('trade');
        //判断数据是否有效
        if($excelData['error']==1){
            //读取sheet0的数据
            $invoiceList=$excelData['data'][0];
            $record=array_slice($invoiceList,1);
            //去除表头
            if(is_array($record) && !empty($record)){
                $length=count($record);
                $filter['shop_id']=$this->shopId;
                $filter['status|in']=array('WAIT_SELLER_SEND_GOODS','PARTIAL_SHIPMENT');
                $log=array();
                $new_record = [];
                foreach($record as $tk => $r){
                    if(empty($r['F']) || empty($r['G']) || empty($r['H']) || empty($r['I'])){
                        $r['J']='导入失败';
                        $r['K']='信息未填写完整';
                        $log[$tk]=$r;
                        $length--;
                        continue;
                    }
                    $chkdlyCorpCode=$this->_checkCorpCode($r['H']);
                    if(empty($chkdlyCorpCode)){
                        $r['J']='导入失败';
                        $r['K']='当前店铺未查询到相关快递服务，请核对快递公司代码';
                        $log[$tk]=$r;
                        $length--;
                        continue;
                    }

                    $r['J']='同上';
                    $r['K']='同上';
                    $log[$tk]=$r;

                    if(!empty($new_record))
                    {
                        foreach($new_record as &$v)
                        {
                            if($v['tid'] == $r['A']  && $v['logi_no'] == $r['I'] && $v['corp_code'] == $r['H'])
                            {
                                $v['filter_array'][$r['B']]=$r['F'];
                                continue 2;
                            }
                        }
                    }
                    $new_record[$tk]['tid']=$r['A'];
                    $new_record[$tk]['corp_code']=$r['H'];
                    $new_record[$tk]['logi_no']=$r['I'];
                    $new_record[$tk]['shop_id']=$this->shopId;
                    $new_record[$tk]['seller_id']=$this->sellerId;
                    $new_record[$tk]['filter_array'][$r['B']]=$r['F'];
                }

                foreach($new_record as $ntk => $nr)
                {
                    $filter['tid']=$nr['tid'];
                    $curTrade=$tradeModel->getRow('*',$filter);

                    if(!empty($curTrade))
                    {
                        try
                        {
                            $re=app::get('topshop')->rpcCall('trade.delivery',$nr);
                        }
                        catch( Exception $e)
                        {
                            $msg = $e->getMessage();
                            $this->handleLogs($nr['filter_array'],$log,$nr['logi_no'],$nr['corp_code'],'导入失败',$msg);
                            $length--;
                            continue;
                        }
                        if($re!=true)
                        {
                            $this->handleLogs($nr['filter_array'],$log,$nr['logi_no'],$nr['corp_code'],'导入失败','更新订单记录失败');
                            $length--;
                        }
                        else
                        {
                            $this->handleLogs($nr['filter_array'],$log,$nr['logi_no'],$nr['corp_code'],'导入成功','订单已发货');
                            //发送订单发货通知
                            $data = [
                                'tid' => $nr['tid'],
                                'shop_id' => $nr['shop_id'],
                                'corp_code' => $nr['corp_code'],
                                'logi_no' => $nr['logi_no'],
                            ];
                            try
                            {
                                app::get('topshop')->rpcCall('trade.shop.delivery.notice.send', $data);
                            }
                            catch( Exception $e)
                            {
                                $msg = $e->getMessage();
                                continue;
                            }
                        }
                    }
                    else
                    {
                        $this->handleLogs($nr['filter_array'],$log,$nr['logi_no'],$nr['corp_code'],'导入失败','未找到未发货状态的订单记录');
                        $length--;
                        continue;
                    }
                }
//echo "<pre>";
//                print_r($log);die;
//                foreach($record as $tk => $r){
//                    if(empty($r['F']) || empty($r['G']) || empty($r['H']) || empty($r['I'])){
//                        $r['J']='导入失败';
//                        $r['K']='信息未填写完整';
//                        $log[]=$r;
//                        $length--;
//                        continue;
//                    }
//                    $chkdlyCorpCode=$this->_checkCorpCode($r['H']);
//                    if(empty($chkdlyCorpCode)){
//                        $r['J']='导入失败';
//                        $r['K']='当前店铺未查询到相关快递服务，请核对快递公司代码';
//                        $log[]=$r;
//                        $length--;
//                        continue;
//                    }
//                    $filter['tid']=$r['A'];
//                    $curTrade=$tradeModel->getRow('*',$filter);
//                    if(!empty($curTrade)){
//                        unset($sdf);
//                        $sdf['tid']=$filter['tid'];
//                        $sdf['corp_code']=$r['H'];
//                        $sdf['logi_no']=$r['I'];
//                        $sdf['shop_id']=$this->shopId;
//                        $sdf['seller_id']=$this->sellerId;
//                        $sdf['filter_array'][$r['B']]=$r['F'];
//                        try{
//                            $re=app::get('topshop')->rpcCall('trade.delivery',$sdf);
//                        } catch( Exception $e)
//                        {
//                            $msg = $e->getMessage();
//                            $r['J']='导入失败';
//                            $r['K']=$msg;
//                            $log[]=$r;
//                            $length--;
//                            continue;
//                        }
//                        if($re!=true){
//                            $r['J']='导入失败';
//                            $r['K']='更新订单记录失败';
//                            $log[]=$r;
//                            $length--;
//                        }else{
//                            $r['J']='导入成功';
//                            $r['K']='订单已发货';
//                            $log[]=$r;
//
//                            //发送订单发货通知
//                            $data = [
//                                'tid' => $sdf['tid'],
//                                'shop_id' => $sdf['shop_id'],
//                                'corp_code' => $sdf['corp_code'],
//                                'logi_no' => $sdf['logi_no'],
//                            ];
//                            try{
//                                app::get('topshop')->rpcCall('trade.shop.delivery.notice.send', $data);
//                            } catch( Exception $e)
//                            {
//                                $msg = $e->getMessage();
//                                continue;
//                            }
//                        }
//                    }else{
//                        $r['J']='导入失败';
//                        $r['K']='未找到未发货状态的订单记录';
//                        $log[]=$r;
//                        $length--;
//                        continue;
//                    }
//                }
                if($length<=0){
                    $data = array('code' => 0, 'msg' => '导入失败:无法更新订单记录');
                }else{
                    $data=array('code'=>1,'msg'=>'导入完成，请查看日志');
                }
                $logFileName=date('Y-m-d-H-i-s');
                $data['log']=$this->exportLog($logFileName,$log);

            }else {
                $data = array('code' => 0, 'msg' => '导入失败:表格信息读取为空');
            }
        }else{
            $data=array('code'=>0,'msg'=>'读取excel文件内容失败');
        }
        echo json_encode($data);
    }

    /* handleLogs(par1, par2, par3, par4, par5, par6)
     * 函数说明：
     * 参数说明：par1:子订单发货数组，par2:错误日志，par3:物流号, par4:物流公司编码，par5:状态，par6:信息
     * authorbyfanglongji
     * 2017-11-10
     */

    public function handleLogs($filter_array,&$log,$logi_no,$corp_code,$state,$msg)
    {
        foreach($filter_array as $oid=>$num)
        {
            foreach($log as $k=>&$l)
            {
                if($oid == $l['B']  && $logi_no == $l['I'] && $corp_code == $l['H'])
                {
                    $l['J'] = $state;
                    $l['K'] = $msg;
                }
            }
        }
    }
    /**
     * 根据文件的realpath来读取excel文件内容
     * @param $filePath
     * @return array
     */
    public function getExcelData($filePath){
        if(!file_exists($filePath)){
            return array("error"=>0,'message'=>'file not found!');
        }
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        if(!$objReader->canRead($filePath)){
            $objReader = PHPExcel_IOFactory::createReader('Excel5');
            if(!$objReader->canRead($filePath)){
                return array("error"=>0,'message'=>'file not found!');
            }
        }
        $objReader->setReadDataOnly(true);
        try{
            $PHPReader = $objReader->load($filePath);
        }catch(Exception $e){}
        if(!isset($objReader)) return array("error"=>0,'message'=>'read error!');

        //获取工作表的数目
        $sheetCount = $PHPReader->getSheetCount();

        if($sheetCount > 0){
            for($i = 0;$i< $sheetCount; $i++){
                $excelData[]=$PHPReader->getSheet($i)->toArray(null, true, true, true);
            }
        }else{
            $excelData[]=$PHPReader->getSheet(0)->toArray(null, true, true, true);
        }
        unset($PHPReader);
        return array("error"=>1,"data"=>$excelData);
    }

    /**
     * 导出excel文件
     * @param $expTitle
     * @param $expTableData
     * @return string
     */
    public function exportLog($expTitle,$expTableData){

        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $xlsTitle; //文件名称可根据自己情况设定
        $cellTitle=array("订单号","子订单号","商品信息","购买数量","已发货数量","发货数量","物流公司(必填）","物流公司代码(必填）","物流单号(必填）","操作状态","备注");
        $cellNum=count($cellTitle);//多少列
        $dataNum = count($expTableData);//多少行
        $objPHPExcel = new PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $cellTitle[$i]);

        }

        for($i=0;$i<$dataNum;$i++){//多少行
            for($j=0;$j<$cellNum;$j++){ //多少列
                //$objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+2), $expTableData[$i][$cellName[$j]]);
                $objPHPExcel->getActiveSheet(0)->setCellValueExplicit($cellName[$j].($i+2),$expTableData[$i][$cellName[$j]],PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }

        //header('pragma:public');
        //header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        //header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        //$objWriter->save('php://output');
        $savePath= 'csvs/';
        //$realSavePath=realpath($savePath).'/'.$xlsTitle.'.xls';
        $realSavePath=$savePath.$xlsTitle.'.xls';
        $objWriter->save($realSavePath);
        return $xlsTitle.'.xls';

    }

    /**
     * 检测快递公司代码是否有效
     * @param $corpCode
     * @return mixed
     */
    private function _checkCorpCode($corpCode){
        $params['shop_id']=$this->shopId;
        $params['corp_code']=$corpCode;
        $dlycorp=app::get('sysshop')->model('shop_rel_dlycorp')->getRow('id',$params);
        return $dlycorp;
    }

    /**
     * 获取店铺提供的快递服务名单
     * @return mixed
     */
    private function _getShopDlyList()
    {
        $params['shop_id'] = $this->shopId;
        $dlyList = app::get('sysshop')->model('shop_rel_dlycorp')->getList('corp_name,corp_code', $params);
        return $dlyList;
    }

    private function _getTradeDlyInfo($tid){
        $tradeFilter['tid']=$tid;
        $dlyInfo=app::get('systrade')->model('trade')->getRow('receiver_name,receiver_state,receiver_city,receiver_district,receiver_address,receiver_mobile',$tradeFilter);
        if(!empty($dlyInfo)){
            $receiverInfo=implode(',',$dlyInfo);
        }else{
            $receiverInfo='无';
        }

        return $receiverInfo;
    }

    private function _getSentNum($oid){
        $filter['oid']=$oid;
        $num=app::get('syslogistics')->model('delivery_detail')->getRow('sum(number) as sent_num',$filter);
        if(empty($num['sent_num'])) $num['sent_num']=0;
        return $num['sent_num'];
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
}