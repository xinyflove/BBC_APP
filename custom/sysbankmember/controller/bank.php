<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/09/01
 * Time: 16:27
 */
class sysbankmember_ctl_bank extends desktop_controller{

    /**
     * 银行管理列表
     * @return mixed
     */
    public function index(){
        $actions = array(
            array(
                'label'=>app::get('sysbankmember')->_('银行添加'),
                'href'=>'?app=sysbankmember&ctl=bank&act=edit',
                'target'=>'dialog::{title:\''.app::get('sysbankmember')->_('银行添加').'\',width:400,height:365}'
            ),
        );

        $params = array(
            'title' => app::get('sysbankmember')->_('银行列表'),
            'actions'=> $actions,
            'use_view_tab' => true,
        );

        return $this->finder('sysbankmember_mdl_bank', $params);
    }

    /**
     * 银行添加/修改页面
     * @param $bankId
     */
    public function edit($bankId){
        $pagedata = array();

        if( $bankId )
        {
            $bankInfo = app::get('sysbankmember')->model('bank')->getbankRow($bankId);
            $pagedata['bankInfo'] = $bankInfo;
        }

        return $this->page('sysbankmember/bank/edit.html', $pagedata);
    }

    /**
     * 银行保存
     */
    public function savebank(){
        $this->begin();
        $postdata = input::get('bank');

        $cdres = $this->_checkData($postdata);
        if(!$cdres['s']){
            $this->end($cdres['s'],$cdres['m']);
        }

        $objBank = kernel::single('sysbankmember_bank');
        if($objBank->existBankCode($postdata)){
            $this->end(false,'银行代码已存在');
        }
        if($objBank->existBankName($postdata)){
            $this->end(false,'银行名称已存在');
        }

        if(empty($postdata['bank_id'])){
            $flag = kernel::single('sysbankmember_data_bank')->add($postdata,$msg);
            $this->adminlog("添加银行[银行ID:{$postdata['bank_id']}]", $flag ? 1 : 0);
        }else{
            $flag = kernel::single('sysbankmember_data_bank')->update($postdata,$msg);
            $this->adminlog("更新银行[银行ID:{$postdata['bank_id']}]", $flag ? 1 : 0);
        }
        
        $this->end($flag,$msg);
    }

    /**
     * 银行删除页面
     * @param $bank_id
     */
    public function delPage($bank_id){
        $pagedata['bank_id'] = $bank_id;
        return $this->page('sysbankmember/bank/del.html', $pagedata);
    }

    /**
     * 银行删除
     */
    public function delete(){
        $bank_id = input::get('bank_id');
        $this->begin('?app=syscategory&ctl=admin_props&act=index');
        try{
            $delFlag = kernel::single('sysbankmember_data_bank')->delete($bank_id);
            $this->adminlog("删除银行[银行ID:{$bank_id}]", 1);
        }catch(Exception $e){
            $this->adminlog("删除银行[分类ID:{$bank_id}]", 0);
            $msg = $e->getMessage();
            $this->end(false,$msg);
        }
        $this->end(true,app::get('sysbankmember')->_('删除银行成功'));
    }

    /**
     * 验证提交数据
     * @param $data
     * @return array
     */
    protected function _checkData($data)
    {
        if(empty($data['bank_name'])){
            return array('s' => false, 'm' => '请填写银行名称');
        }

        if(empty($data['bank_code'])){
            return array('s' => false, 'm' => '请填写银行代码');
        }

        return array('s' => true, 'm' => '');
    }
}
