<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/09/01
 * Time: 10:07
 */
return  array(
    'columns'=> array(
        'bank_id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true, // 必须
            'comment' => app::get('sysbankmember')->_('序号'),
			'label' => app::get('sysbankmember')->_('序号'),
			'width' => 50,
			'in_list' => true,
			'default_in_list' => true,
        ),
		'bank_name' => array (
			'type' => 'string',
			'default' => '',
			'required' => true,
			'comment' => app::get('sysbankmember')->_('银行名称'),
			'label' => app::get('sysbankmember')->_('银行名称'),
			'width' => 150,
			'in_list' => true,
			'default_in_list' => true,
			'searchtype' => 'has',
		),
        'bank_code' => array (
			'type' => 'string',
			'default' => '',
			'required' => true,
			'comment' => app::get('sysbankmember')->_('银行代码'),
			'label' => app::get('sysbankmember')->_('银行代码'),   // 定义在desktop finder中列的名称
			'width' => 150, // 定义在desktop finder中列的初始宽度
			'in_list' => true,  // 定义在desktop finder配置列表项中是否可以勾选显示, 默认值为false.
			'default_in_list' => true,  // 定义在desktop finder列表中初始安装的情况下, 对应列是否默认显示在列表中, 默认值为false.
			'searchtype' => 'has',
		),

        'bank_logo' => array (
			'type' => 'string',
			'default' => '',
			'required' => true,
			'comment' => app::get('sysbankmember')->_('银行logo'),
			'label' => app::get('sysbankmember')->_('银行logo'),   // 定义在desktop finder中列的名称
			'width' => 150, // 定义在desktop finder中列的初始宽度
            'filtertype' => 'normal',
            'editable' => false,
			'in_list' => false,  // 定义在desktop finder配置列表项中是否可以勾选显示, 默认值为false.
			'default_in_list' => false,  // 定义在desktop finder列表中初始安装的情况下, 对应列是否默认显示在列表中, 默认值为false.
			
		),

        'bank_color' => array (
			'type' => 'string',
			'default' => '',
			'required' => true,
			'comment' => app::get('sysbankmember')->_('银行颜色'),
			'label' => app::get('sysbankmember')->_('银行颜色'),   // 定义在desktop finder中列的名称
			'width' => 150, // 定义在desktop finder中列的初始宽度
			'in_list' => false,  // 定义在desktop finder配置列表项中是否可以勾选显示, 默认值为false.
			'default_in_list' => false,  // 定义在desktop finder列表中初始安装的情况下, 对应列是否默认显示在列表中, 默认值为false.
		),

        'create_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('sysbankmember')->_('创建时间'),
            'comment' => app::get('sysbankmember')->_('创建时间'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'modified_time' => array(
            'type' => 'last_modify',
            'required' => true,
            'label' => app::get('sysbankmember')->_('修改时间'),
            'comment' => app::get('sysbankmember')->_('修改时间'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => false,
        ),
        'deleted' => array(
            'type' => array(
                0 => app::get('sysbankmember')->_('正常'),
                1 => app::get('sysbankmember')->_('删除'),
            ),
            'default' => 0,
            'comment' => app::get('sysbankmember')->_('是否已删除'),
        )
    ),
    'primary' => 'bank_id',  // 定义主键
    'comment' => app::get('sysbankmember')->_('银行表'),
);
