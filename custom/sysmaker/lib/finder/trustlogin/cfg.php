<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-15
 * Desc: 创客信任登录配置finder
 */
class sysmaker_finder_trustlogin_cfg{

    /**
     * @var string 操作列名称
     */
    var $column_control = '配置';

    /**
     * 配置列显示的html
     * @param array 该行的数据
     * @return string html
     */
    function column_control(&$colList, $list)
    {
        foreach($list as $k=>$row)
        {
            $url = '?app=sysmaker&ctl=trustlogincfg&act=setting&p[0]='.$row['flag'].'&finder_id='. $_GET['_finder']['finder_id'];
            $target = 'dialog::  {title:\''.app::get('sysmaker')->_('信任登陆信息编辑').'\', width:500, height:400}';
            $title = app::get('sysmaker')->_('配置');

            $colList[$k] = '<a href="' . $url . '" target="' . $target . '">' . $title . '</a>';
        }
    }

}
