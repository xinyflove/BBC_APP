<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2017/9/20
 * Time: 14:01
 */
class sysbankmember_finder_account {
    public $column_edit;
    public $column_edit_order = 1;
    public $column_edit_width = 40;
    public $column_status;
    public $column_status_order = 10;
    public $column_status_width=40;
    public $column_mobile;
    public $column_mobile_order = 11;
    public $column_username;
    public $column_username_order = 12;
    public $column_bank_name;
    public $column_bank_name_order = 13;

    public function __construct()
    {
        $this->column_edit = app::get('sysbankmember')->_('编辑');
        $this->column_status = app::get('sysbankmember')->_('绑定状态');
        $this->column_mobile = app::get('sysbankmember')->_('会员手机号');
        $this->column_username = app::get('sysbankmember')->_('会员姓名');
        $this->column_bank_name = app::get('sysbankmember')->_('银行名称');
    }

    public function column_edit(&$colList, $list){
        foreach($list as $k=>$row)
        {
            $html = '';

            $unbindUrl = '?app=sysbankmember&ctl=account&act=unbindPage&finder_id='.$_GET['_finder']['finder_id'].'&p[0]='.$row['account_id'];
            $target = 'target="dialog::{title:\''.app::get('sysbankmember')->_('提示').'\', width:200, height:120}"';
            $html .= '<a href="'.$unbindUrl.'" '.$target.'>'.app::get('sysbankmember')->_('解绑').'</a>';

            $colList[$k] = $html;
        }
    }

    public function column_status(&$colList, $list){
        $ids = array_column($list, 'account_id');
        if( !$ids ) return $colList;
        
        foreach($list as $k=>$row)
        {
            $colList[$k] = $row['deleted'] == 0 ? '绑定' : '未绑定';
        }
    }

    public function column_mobile(&$colList, $list){
        $ids = array_column($list, 'user_id');
        if( !$ids ) return $colList;

        $userInfoList = app::get('sysuser')->model('account')->getList('mobile,user_id', array('user_id'=>$ids));
        $userInfoList = array_bind_key($userInfoList,'user_id');

        foreach($list as $k=>$row)
        {
            $info = $userInfoList[$row['user_id']];
            $colList[$k] = $info['mobile'];
        }
    }

    public function column_username(&$colList, $list){
        $ids = array_column($list, 'user_id');
        if( !$ids ) return $colList;

        $userInfoList = app::get('sysuser')->model('user')->getList('username,user_id', array('user_id'=>$ids));
        $userInfoList = array_bind_key($userInfoList,'user_id');

        foreach($list as $k=>$row)
        {
            $info = $userInfoList[$row['user_id']];
            $colList[$k] = $info['username'];
        }
    }

    public function column_bank_name(&$colList, $list){
        $ids = array_column($list, 'member_id');
        if( !$ids ) return $colList;

        $memberInfoList = app::get('sysbankmember')->model('member')->getList('bank_id,member_id', array('member_id'=>$ids));
        $bank_ids = array_column($memberInfoList, 'bank_id');
        $memberInfoList = array_bind_key($memberInfoList,'member_id');

        $bankInfoList = app::get('sysbankmember')->model('bank')->getList('bank_name,bank_id', array('bank_id'=>$bank_ids));

        $bankInfoList = array_bind_key($bankInfoList,'bank_id');

        foreach($list as $k=>$row)
        {
            $info = $bankInfoList[$memberInfoList[$row['member_id']]['bank_id']];
            $colList[$k] = $info['bank_name'];
        }
    }
}