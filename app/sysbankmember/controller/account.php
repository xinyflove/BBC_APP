<?php
/**
 * Created by PhpStorm.
 * member: Caffrey
 * Date: 2017/09/01
 * Time: 16:24
 */
class sysbankmember_ctl_account extends desktop_controller{

    /**
     * 会员卡号管理列表
     * @return mixed
     */
    public function index(){
        $actions = array();

        $params = array(
            'title' => app::get('sysbankmember')->_('会员卡号列表'),
            'actions'=> $actions,
            'use_view_tab' => true,
            'use_buildin_delete' => false,
        );

        return $this->finder('sysbankmember_mdl_account', $params);
    }

    /**
     * 解绑页面
     * @param $member_id
     */
    public function unbindPage($account_id){
        $pagedata['account_id'] = $account_id;
        return $this->page('sysbankmember/account/unbind.html', $pagedata);
    }

    /**
     * 解绑操作
     */
    public function unbind(){
        $account_id = input::get('account_id');
        $this->begin();
        try{
            $update = array();
            $update['deleted'] = 1;
            $res = app::get('sysbankmember')->model('account')->update($update, array('account_id'=>$account_id));
        }catch(Exception $e){
            $msg = $e->getMessage();
            $this->end(false,$msg);
        }
        $this->end(true,app::get('sysbankmember')->_('解绑成功'));
    }
}