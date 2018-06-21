<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2016/12/12
 * Time: 14:08
 * 自定义finder
 */
class testapp_finder_level {

    public $column_edit = '操作';
    public $column_edit_order = 1;

    public function column_edit(&$colList, $list){
        foreach($list as $k=>$row)
        {
            $editUrl = '?app=testapp&ctl=level&act=create&finder_id='.$_GET['_finder']['finder_id'].'&p[0]='.$row['level_id'];
            $editTar = 'target="dialog::{title:\''.app::get('testapp')->_('编辑').'\', width:640, height:420}"';
            $html = '<a href="'.$editUrl.'" '.$editTar.'>'.app::get('testapp')->_('编辑').'</a>';

            $html .= ' | ';

            $delUrl = '?app=testapp&ctl=level&act=delPage&finder_id='.$_GET['_finder']['finder_id'].'&p[0]='.$row['level_id'];
            $target = 'target="dialog::{title:\''.app::get('testapp')->_('提示').'\', width:200, height:120}"';
            $html .= '<a href="'.$delUrl.'" '.$target.'>'.app::get('testapp')->_('删除').'</a>';

            $colList[$k] = $html;
        }
    }
}

