<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/3
 * Time: 10:03
 * Desc: 商城表
 */
return  array(
    'columns'=> array(
        'store_id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true, // 必须
            'comment' => app::get('sysstore')->_('序号'),
        ),
        'store_name' => array (
            'type' => 'string',
            'default' => '',
            'required' => true,
            'comment' => app::get('sysstore')->_('商城名称'),
            'label' => app::get('sysstore')->_('商城名称'),
            'width' => 150,
            'in_list' => true,
            'default_in_list' => true,
            'searchtype' => 'has',
        ),
        'store_desc'=>array(
            'type'=>'text',
            'default' => '',
            'label' => app::get('sysstore')->_('商城描述'),
            'comment' => app::get('sysstore')->_('商城描述'),
        ),
        'relate_shop_id' => array (
            'type' => 'string',
            'default' => '',
            'required' => true,
            'comment' => app::get('sysstore')->_('关联店铺'),
            'label' => app::get('sysstore')->_('关联店铺'),
        ),
        'status'=>array(
            'type'=>array(
                'active'=>app::get('sysstore')->_('开启'),
                'dead'=>app::get('sysstore')->_('关闭'),
            ),
            'default'=>'active',
            'comment' => app::get('sysstore')->_('店铺状态'),
            'label' => app::get('sysstore')->_('店铺状态'),
            'in_list'=>true,
            'default_in_list'=>true,
            'order' => 8,
        ),
        'created_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('sysstore')->_('创建时间'),
            'comment' => app::get('sysstore')->_('创建时间'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'modified_time' => array(
            'type' => 'last_modify',
            'required' => false,
            'label' => app::get('sysstore')->_('修改时间'),
            'comment' => app::get('sysstore')->_('修改时间'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'deleted' => array(
            'type' => array(
                0 => app::get('sysstore')->_('正常'),
                1 => app::get('sysstore')->_('删除'),
            ),
            'default' => 0,
            'comment' => app::get('sysstore')->_('是否已删除'),
        )
    ),
    'primary' => 'store_id',	//主键
    'index' => array (  //索引
        'id' => [
            'columns' => ['store_id'],   // 需要建立索引的字段名
        ],
    ),
    'comment' => app::get('sysstore')->_('商城表'),
);