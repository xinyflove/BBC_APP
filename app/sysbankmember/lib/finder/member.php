<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2016/12/12
 * Time: 14:08
 * 自定义finder
 */
class sysbankmember_finder_member {

    public $column_edit = '编辑';
    public $column_edit_order = 1;

    public function column_edit(&$colList, $list){
        foreach($list as $k=>$row)
        {
            $editUrl = '?app=sysbankmember&ctl=member&act=create&finder_id='.$_GET['_finder']['finder_id'].'&p[0]='.$row['member_id'];
            $editTar = 'target="dialog::{title:\''.app::get('sysbankmember')->_('编辑').'\', width:600, height:250}"';
            $html = '<a href="'.$editUrl.'" '.$editTar.'>'.app::get('sysbankmember')->_('编辑').'</a>';

            $html .= ' | ';

            $delUrl = '?app=sysbankmember&ctl=member&act=delPage&finder_id='.$_GET['_finder']['finder_id'].'&p[0]='.$row['member_id'];
            $target = 'target="dialog::{title:\''.app::get('sysbankmember')->_('提示').'\', width:200, height:120}"';
            $html .= '<a href="'.$delUrl.'" '.$target.'>'.app::get('sysbankmember')->_('删除').'</a>';

            $html .= ' | ';

            $editUrl = '?app=sysbankmember&ctl=member&act=bindPage&finder_id='.$_GET['_finder']['finder_id'].'&p[0]='.$row['member_id'];
            $editTar = 'target="dialog::{title:\''.app::get('sysbankmember')->_('绑定').'\', width:600, height:250}"';
            $html .= '<a href="'.$editUrl.'" '.$editTar.'>'.app::get('sysbankmember')->_('绑定').'</a>';
            
            $colList[$k] = $html;
        }
    }
}

