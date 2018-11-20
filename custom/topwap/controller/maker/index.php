<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-14
 * Desc: 创客店铺首页
 */
class topwap_ctl_maker_index extends topwap_controller {

    public function home()
    {
        $input = input::get();

        $pagedata = array();
        
        return view::make('topwap/maker/home.html',$pagedata);
    }

}