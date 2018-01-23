<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/3
 * Time: 10:25
 */
class sysstore_ctl_account extends desktop_controller{

    /**
     * 用户列表
     * @return mixed
     */
    public function lists()
    {
        $actions = array(
            array(
                'label'=>app::get('sysstore')->_('添加用户'),
                'href'=>'?app=sysstore&ctl=account&act=edit',
                'target'=>'dialog::{title:\''.app::get('sysstore')->_('添加用户').'\',width:500,height:360}'
            ),
        );

        $params = array(
            'title' => app::get('sysstore')->_('用户列表'),
            'actions'=> $actions,
        );

        return $this->finder('sysstore_mdl_account', $params);
    }

    /**
     * 编辑用户数据
     * @param $accountId
     */
    public function edit($accountId)
    {
        $pagedata = array();

        if( $accountId )
        {
            $accountInfo = app::get('sysstore')->model('account')->getRow('*', array('account_id' => $accountId));
            $pagedata['accountInfo'] = $accountInfo;
        }

        $storeList = app::get('sysstore')->model('store')->getList('store_id,store_name', array('status'=>'active', 'deleted'=>0));
        $pagedata['storeList'] = $storeList;

        return $this->page('sysstore/account/edit.html', $pagedata);
    }

    /**
     * 保存用户数据
     */
    public function saveAccount()
    {
        $inputs = input::get();
        $account = $inputs['account'];
        $cdres = $this->__checkData($account);
        if(!$cdres['s']){
            $this->splash('error',null,$cdres['m']);
        }

        $account['relate_shop_id'] = implode(',', $account['shop_id']);
        $objAccount = kernel::single('sysstore_data_account');
        try{
            $flag = $objAccount->saveAccount($account,$msg);
            if(!$flag){
                throw new \LogicException($msg);
            }
            $this->adminlog("{$msg}[{$account['login_account']}]", 1);
        }catch (Exception $e){
            $msg = $e->getMessage();
            $this->adminlog("{$msg}[{$account['login_account']}]", 0);
            return $this->splash('error',null,$msg);
        }
        
        return $this->splash('success',null ,$msg);
    }

    /**
     * 验证提交数据
     * @param $data
     * @return array
     */
    private function __checkData($data)
    {
        if(empty($data['login_account'])){
            return array('s' => false, 'm' => '请填写用户登录名');
        }
        
        if(empty($data['account_id']) || !empty($data['login_password']) || !empty($data['psw_confirm'])){
            if(empty($data['login_password'])){
                return array('s' => false, 'm' => '请填写用户登录密码');
            }

            if(empty($data['psw_confirm'])){
                return array('s' => false, 'm' => '请填写确认密码');
            }
        }

        if(empty($data['store_id'])){
            return array('s' => false, 'm' => '请选择商城');
        }

        if($data['login_password'] != $data['psw_confirm']){
            return array('s' => false, 'm' => '用户登录密码和确认密码不一致');
        }

        return array('s' => true, 'm' => '');
    }
}