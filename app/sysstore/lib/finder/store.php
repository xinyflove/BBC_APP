<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/3
 * Time: 15:52
 * Desc: 自定义finder
 */
class sysstore_finder_store {
    public $column_edit;
    public $column_edit_order = 1;
    public $column_edit_width = 40;

    public function __construct()
    {
        $this->column_edit = app::get('sysstore')->_('操作');
    }

    /**
     * 操作
     * @param $colList
     * @param $list
     */
    public function column_edit(&$colList, $list){
        foreach($list as $k=>$row)
        {
            $html = '';

            $unbindUrl = '?app=sysstore&ctl=store&act=edit&finder_id='.$_GET['_finder']['finder_id'].'&p[0]='.$row['store_id'];
            $target = 'target="dialog::{title:\''.app::get('sysstore')->_('编辑商城').'\', width:500, height:450}"';
            $html .= '<a href="'.$unbindUrl.'" '.$target.'>'.app::get('sysstore')->_('编辑').'</a>';

            $colList[$k] = $html;
        }
    }
}