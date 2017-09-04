<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/09/01
 * Time: 17:49
 * 自定义finder
 */
class sysbankmember_finder_bank {

    public $column_edit = '编辑';
    public $column_edit_order = 1;

    public function column_edit(&$colList, $list){
        foreach($list as $k=>$row)
        {
            $editUrl = '?app=sysbankmember&ctl=bank&act=edit&finder_id='.$_GET['_finder']['finder_id'].'&p[0]='.$row['bank_id'];
            $editTar = 'target="dialog::{title:\''.app::get('sysbankmember')->_('编辑').'\', width:400, height:165}"';
            $html = '<a href="'.$editUrl.'" '.$editTar.'>'.app::get('sysbankmember')->_('编辑').'</a>';

            $html .= ' | ';

            $delUrl = '?app=sysbankmember&ctl=bank&act=delPage&finder_id='.$_GET['_finder']['finder_id'].'&p[0]='.$row['bank_id'];
            $target = 'target="dialog::{title:\''.app::get('sysbankmember')->_('提示').'\', width:200, height:120}"';
            $html .= '<a href="'.$delUrl.'" '.$target.'>'.app::get('sysbankmember')->_('删除').'</a>';

            $colList[$k] = $html;
        }
    }
}

