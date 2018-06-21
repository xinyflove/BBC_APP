<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2017/12/21
 * Time: 17:24
 */
class foodmap_mdl_apilog extends dbeav_model{
    var $has_many = array();

    public $defaultOrder = array('create_time DESC');

    /**
     * 构造方法
     * @param object model相应app的对象
     * @return null
     */
    public function __construct($app){
        parent::__construct($app);
    }
    
    
}