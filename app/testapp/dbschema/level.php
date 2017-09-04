<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/08/31
 * Time: 14:37
 */
return  array(
    'columns'=> array(
        'level_id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true, // 必须
            'comment' => app::get('testapp')->_('序号'),
        ),
        'name' => array (
            'type' => 'string',
            'default' => '',
            'required' => true,
            'comment' => app::get('testapp')->_('等级名称'),
            'label' => app::get('testapp')->_('等级名称'),   // 定义在desktop finder中列的名称
            'width' => 150, // 定义在desktop finder中列的初始宽度
            'in_list' => true,  // 定义在desktop finder配置列表项中是否可以勾选显示, 默认值为false.
            'default_in_list' => true,  // 定义在desktop finder列表中初始安装的情况下, 对应列是否默认显示在列表中, 默认值为false.
        ),
        'discount' => array (
            'type' => 'string',
            'default' => 0,
            'required' => true,
            'comment' => app::get('testapp')->_('打多少折'),
            'label' => app::get('testapp')->_('打N折'),
            'width' => 150,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'create_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('testapp')->_('创建时间'),
            'comment' => app::get('testapp')->_('创建时间'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => false,
        ),
        'modified_time' => array(
            'type' => 'last_modify',
            'required' => true,
            'label' => app::get('testapp')->_('修改时间'),
            'comment' => app::get('testapp')->_('修改时间'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => false,
        ),
        'deleted' => array(
            'type' => array(
                0 => app::get('testapp')->_('正常'),
                1 => app::get('testapp')->_('删除'),
            ),
            'default' => 0,
            'comment' => app::get('testapp')->_('是否已删除'),
        )
    ),
    'primary' => 'level_id',  // 定义主键
    'comment' => app::get('testapp')->_('用户等级表'),
);
