<?php

class sysmall_finder_widgets_instance {

    public $column_edit = '操作';
    public $column_edit_order = 1;
    public $column_edit_width = 60;
    public function column_edit(&$colList, $list)
    {
        foreach($list as $k=>$row)
        {
            $urltmpl = '?app=sysmall&ctl=page&act=edit&finder_id='.$_GET['_finder']['finder_id'].'&p[0]='.$row['widgets_id'];
            $target = 'dialog::{title:\''.app::get('sysmall')->_('编辑挂件').'\', width:400, height:300}';
            $title = app::get('sysmall')->_('编辑');
            $html = '<a href="' . $urltmpl . '" target="' . $target . '">' . $title . '</a>';

            $url = '?app=sysmall&ctl=widgets&act=edit_widgets&finder_id='.$_GET['_finder']['finder_id'].'&p[0]='.$row['widgets_id'];
            $target = 'dialog::{title:\''.app::get('sysmall')->_('编辑挂件').'\', width:900, height:500}';
            $title = app::get('sysmall')->_('配置挂件');
            $html .= '  |  <a href="' . $url . '" target="' . $target . '">' . $title . '</a>';
            $colList[$k] = $html;
        }
    }
 
}
