<?php
/**
 * Created by PhpStorm.
 * member: Caffrey
 * Date: 2017/09/01
 * Time: 16:24
 */
class sysbankmember_ctl_member extends desktop_controller{

    /**
     * 基础卡号管理列表
     * @return mixed
     */
    public function index(){
        $actions = array(
            array(
                'label'=>app::get('sysbankmember')->_('卡号添加'),
                'href'=>'?app=sysbankmember&ctl=member&act=create',
                'target'=>'dialog::{title:\''.app::get('sysbankmember')->_('卡号添加').'\',width:600,height:250}'
            ),
            array(
                'label'=>app::get('sysbankmember')->_('卡号导入'),
                'href'=>'?app=sysbankmember&ctl=member&act=upload',
                'target'=>'dialog::{title:\''.app::get('sysbankmember')->_('卡号导入').'\',width:600,height:250}'
            ),
            array(
                'label'=>app::get('sysbankmember')->_('卡号导入模版'),
                'href'=>'/app/sysbankmember/template/member.xlsx?',
                'target'=>'_blank'
            ),
        );

        $params = array(
            'title' => app::get('sysbankmember')->_('卡号列表'),
            'actions'=> $actions,
            'use_buildin_delete' => true,
            //'use_buildin_filter' => true,
        );

        return $this->finder('sysbankmember_mdl_member', $params);
    }

    /**
     * 卡号添加/修改页面
     * @param $memberId
     */
    public function create($memberId){
        $pagedata = array();

        if( $memberId )
        {
            $memberInfo = app::get('sysbankmember')->model('member')->getMemberRowById($memberId);
            $pagedata['memberInfo'] = $memberInfo;
        }

        $bankList = app::get('sysbankmember')->model('bank')->getbankOptions();
        $pagedata['bankList'] = $bankList;
        $shopList = app::get('sysshop')->model('shop')->getList('shop_id,shop_name');
        $pagedata['shopList'] = $shopList;

        return $this->page('sysbankmember/member/edit.html', $pagedata);
    }

    /**
     * 卡号保存
     */
    public function savemember(){
        $this->begin();
        $postdata = input::get('member');

        $cdres = $this->_checkData($postdata);
        if(!$cdres['s']){
            $this->end($cdres['s'],$cdres['m']);
        }

        $objMember = kernel::single('sysbankmember_member');
        if($objMember->existCardNumber($postdata)){
            $this->end(false,'银行卡号已存在');
        }

        if(empty($postdata['member_id'])){
            $flag = kernel::single('sysbankmember_data_member')->add($postdata,$msg);
            $this->adminlog("添加卡号[卡号ID:{$flag}]", $flag ? 1 : 0);
        }else{
            $flag = kernel::single('sysbankmember_data_member')->update($postdata,$msg);
            $this->adminlog("更新卡号[卡号ID:{$postdata['member_id']}]", $flag ? 1 : 0);
        }

        $this->end($flag,$msg);
    }

    /**
     * 卡号删除页面
     * @param $member_id
     */
    public function delPage($member_id){
        $pagedata['member_id'] = $member_id;
        return $this->page('sysbankmember/member/del.html', $pagedata);
    }

    /**
     * 卡号删除
     */
    public function delete(){
        $member_id = input::get('member_id');
        $this->begin();
        try{
            $delFlag = kernel::single('sysbankmember_data_member')->delete($member_id);
            $this->adminlog("删除卡号[卡号ID:{$member_id}]", 1);
        }catch(Exception $e){
            $this->adminlog("删除卡号[卡号ID:{$member_id}]", 0);
            $msg = $e->getMessage();
            $this->end(false,$msg);
        }
        $this->end(true,app::get('sysbankmember')->_('删除卡号成功'));
    }

    /**
     * 上传文件页面
     */
    public function upload(){
        return $this->page('sysbankmember/upload.html');
    }

    /**
     * 上传文件处理
     * @return string|void
     */
    public function doUpload(){
        $this->begin();

        if ($_FILES["file"]["error"] > 0)
        {
            $this->end(false,app::get('sysbankmember')->_('上传文件失败'));
            return;
        }
        else
        {
            if(empty($_FILES["file"]["name"])){
                $this->end(false,app::get('sysbankmember')->_('请选择上传文件'));
                return;
            }

            $tmp_name = $_FILES["file"]["tmp_name"];
            $file_name = $_FILES["file"]["name"];

            $file_name_arr = explode('.',$file_name);
            $ext = end($file_name_arr);
            
            if($ext != 'xlsx' ){
                $this->end(false,app::get('sysbankmember')->_('请选择xlsx类型文件'));
                return;
            }

            $path = 'app/sysbankmember/upload/'.$file_name;
            $res = move_uploaded_file($tmp_name, $path);
            if($res){
                //$this->end(false,app::get('sysbankmember')->_('上传文件成功'));
                $result = array(
                    'success' => '成功：上传文件成功',
                    'redirect' => '',
                    'splash' => true,
                    'data' => $file_name,
                );
                return json_encode($result);
            }else{
                $this->end(false,app::get('sysbankmember')->_('上传文件失败'));
            }
        }
    }

    /**
     * 导入操作
     */
    public function doImport(){
        $this->begin();

        if(empty($_POST['file_name'])){
            $this->end(false,app::get('sysbankmember')->_('导入文件失败'));
        }

        $file_name = $_POST['file_name'];
        $path = 'app/sysbankmember/upload/'.$file_name;
        //读取excel数据
        $excelData=$this->_getExcelData($path);
        if(!$excelData['error']){
            $this->end(false,app::get('sysbankmember')->_('读取文件失败'));
        }
        //去掉表头数据
        $record=array_slice($excelData['data'][0], 1);
        $res = $this->_checkImportData($record);
        if(!$res['s']){
            $this->end(false,app::get('sysbankmember')->_($res['m']));
        }

        foreach ($record as $r){
            $add_data = array(
                'shop_id' => $r['A'],
                'bank_id' => $r['B'],
                'card_number' => $r['C'],
                'card_grade' => $r['D'],
            );
            $flag = kernel::single('sysbankmember_data_member')->add($add_data,$msg);
            $this->adminlog("添加卡号[卡号ID:{$flag}]", $flag ? 1 : 0);
            if(!$flag){
                $this->end($flag,$msg);
            }
        }

        unlink($path);
        $this->end(true, '导入成功');
    }

    /**
     * 会员绑定页面
     * @param $member_id
     */
    public function bindPage($member_id)
    {
        $memberInfo = app::get('sysbankmember')->model('member')->getMemberRowById($member_id);
        $shop_info = app::get('sysshop')->model('shop')
            ->getRow('shop_name', array('shop_id'=>$memberInfo['shop_id']));
        $bank_info = app::get('sysbankmember')->model('bank')
            ->getRow('bank_name', array('bank_id'=>$memberInfo['bank_id']));
        $memberInfo['shop_name'] = $shop_info['shop_name'];
        $memberInfo['bank_name'] = $bank_info['bank_name'];
        $pagedata['memberInfo'] = $memberInfo;
        return $this->page('sysbankmember/member/bind.html', $pagedata);
    }

    public function saveBind(){
        $this->begin();
        $postdata = input::get('account');

        $flag = kernel::single('sysbankmember_data_member')->bindCardNumber($postdata['member_id'], $postdata['mobile'], $postdata['card_number'], $postdata['rel_name'], $msg);
        $this->adminlog("绑定卡号[卡号ID:{$postdata['member_id']}]", $flag ? 1 : 0);

        $this->end($flag,$msg);
    }

    /**
     * 验证提交数据
     * @param $data
     * @return array
     */
    protected function _checkData($data)
    {
        if(empty($data['shop_id'])){
            return array('s' => false, 'm' => '请选择店铺');
        }

        if(empty($data['bank_id'])){
            return array('s' => false, 'm' => '请选择银行');
        }

        if(empty($data['card_number'])){
            return array('s' => false, 'm' => '请填写卡号');
        }

        return array('s' => true, 'm' => '');
    }

    /**
     * 根据文件的realpath来读取excel文件内容
     * @param $filePath
     * @return array
     */
    protected function _getExcelData($filePath){
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
     * 检验导入数据
     * @param $data
     * @return array
     */
    protected function _checkImportData($data)
    {
        $card_numbers = array();
        $repeat_num = array();
        $exist_num = array();
        $shop_ids = array();
        $bank_ids = array();
        foreach ($data as $d){
            if(!in_array($d['C'], $card_numbers)){
                $card_numbers[] = $d['C'];
            }else{
                $repeat_num[] = $d['C'];
            }

            $memberInfo = app::get('sysbankmember')->model('member')->getRow('member_id',array('card_number'=>$d['C']));
            if(!empty($memberInfo)){
                $exist_num[] = $d['C'];
            }

            if(!in_array($d['A'], $shop_ids)){
                $shop_ids[] = $d['A'];
            }

            if(!in_array($d['B'], $bank_ids)){
                $bank_ids[] = $d['B'];
            }
        }

        $shop_id_res = app::get('sysshop')->model('shop')->getList('shop_id');
        $shop_id_res = array_column($shop_id_res, 'shop_id');
        $err_shop_ids = array();
        foreach ($shop_ids as $id){
            if(!in_array($id, $shop_id_res)){
                $err_shop_ids[] = $id;
            }
        }

        $bank_id_res = app::get('sysbankmember')->model('bank')->getList('bank_id');
        $bank_id_res = array_column($bank_id_res, 'bank_id');
        $err_bank_ids = array();
        foreach ($bank_ids as $id){
            if(!in_array($id, $bank_id_res)){
                $err_bank_ids[] = $id;
            }
        }

        if(!empty($repeat_num) || !empty($exist_num) || !empty($err_shop_ids) || !empty($err_bank_ids)){
            $msg = '';
            if(!empty($repeat_num)){
                $msg .= '卡号重复('.implode(',', $repeat_num).');';
            }
            if(!empty($exist_num)){
                $msg .= '卡号存在('.implode(',', $exist_num).');';
            }
            if(!empty($err_shop_ids)){
                $msg .= '店铺ID不存在('.implode(',', $err_shop_ids).');';
            }
            if(!empty($err_bank_ids)){
                $msg .= '银行ID不存在('.implode(',', $err_bank_ids).');';
            }
            return array('s' => false, 'm' => $msg);
        }

        return array('s' => true, 'm' => '');
    }
}