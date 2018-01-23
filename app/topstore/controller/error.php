<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/4
 * Time: 18:01
 * Desc: 错误处理
 */
class topstore_ctl_error extends topstore_controller {

    public function index()
    {

        return $this->page('topstore/error/error.html', []);
    }

}