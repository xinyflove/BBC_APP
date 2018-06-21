<?php
/**
 * Created by PhpStorm.
 * User: CaffreyXin
 * Date: 2017/8/31
 * Time: 10:19
 */
class testapp_service_view_menu{
    function function_menu(){
        $html[] = "<a href='http://www.taobao.com' target='_blank'>".app::get('testapp')->_('淘宝')."</a>";
        return $html;
    }
}