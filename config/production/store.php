<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/3
 * Time: 18:10
 * Desc: 商城管理中心菜单定义
 */
return array(
    /*
    |--------------------------------------------------------------------------
    | 商城管理中心之首页
    |--------------------------------------------------------------------------
     */
    'index' => array(
        'label' => '首页',
        'display' => true,
        'action' => 'topstore_ctl_index@index',
        'icon' => 'fa fa-home',
        'menu' => array(
            array(
                'label'=>'首页',
                'display'=>false,
                'as'=>'topstore.index',
                'action'=>'topstore_ctl_index@index',
                'url'=>'/',
                'method'=>'get'
            ),
			array(
                'label'=>'浏览器检测',
                'display'=>false,
                'as'=>'topstore.browserTip',
                'action'=>'topstore_ctl_index@browserTip',
                'url'=>'browserTip.html',
                'method'=>'get'
            ),
        )
    ),
	
	/*
    |--------------------------------------------------------------------------
    | 商城管理中心之商城管理
    |--------------------------------------------------------------------------
     */
    'store' => array(
        'label' => '商城',
        'display' => true,
        'action' => 'topstore_ctl_store_setting@index',
        'icon' => 'fa fa-building',
        'menu' => array(
            //商城配置
            array('label'=>'商城基本配置','display'=>true,'as'=>'topstore.storesetting.index','action'=>'topstore_ctl_store_setting@index','url'=>'setting.html','method'=>'get'),
            array('label'=>'wap端商城装修','display'=>true,'as'=>'topstore.wap.decorate.index','action'=>'topstore_ctl_store_wapdecorate@index','url'=>'wap_decorate/index.html','method'=>'get'),
			array('label'=>'wap端商城装修-编辑','display'=>false,'as'=>'topstore.wap.decorate.edit','action'=>'topstore_ctl_store_wapdecorate@edit','url'=>'wap_decorate/edit.html','method'=>'get'),
			array('label'=>'wap端商城装修-保存','display'=>false,'as'=>'topstore.wap.decorate.save','action'=>'topstore_ctl_store_wapdecorate@save','url'=>'wap_decorate/save.html','method'=>'post'),
			array('label'=>'wap端商城装修-删除','display'=>false,'as'=>'topstore.wap.decorate.delete','action'=>'topstore_ctl_store_wapdecorate@delete','url'=>'wap_decorate/delete.html','method'=>'post'),
			array('label'=>'wap端商城装修-排序','display'=>false,'as'=>'topstore.wap.decorate.sortOpt','action'=>'topstore_ctl_store_wapdecorate@sortOpt','url'=>'wap_decorate/sortOpt.html','method'=>'post'),
			array('label'=>'wap端商城装修-状态','display'=>false,'as'=>'topstore.wap.decorate.setStatus','action'=>'topstore_ctl_store_wapdecorate@setStatus','url'=>'wap_decorate/setStatus.html','method'=>'post'),
        )
    ),
	
	/*
    |--------------------------------------------------------------------------
    | 商城管理中心之商城商品管理
    |--------------------------------------------------------------------------
     */
    'item' => array(
        'label' => '商品',
        'display' => false,
        'action'=> '',
        'icon' => 'fa fa-cube',
        'menu' => array(
            
			array('label'=>'根据条件搜索图片,tab切换','as'=>'topstore.image.search','display'=>false,'action'=>'topstore_ctl_store_image@search','url'=>'image/search.html','method'=>'post'),
            array('label'=>'商城使用图片加载modal','display'=>false,'as'=>'topstore.image.loadModal','action'=>'topstore_ctl_store_image@loadImageModal','url'=>'image/loadimagemodal.html','method'=>'get'),
            
        ),
    ),
);