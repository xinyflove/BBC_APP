<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/09/01
 * Time: 10:00
 */
return  array(
    'columns'=> array(
        'account_id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true, // 必须
            'comment' => app::get('sysbankmember')->_('序号'),
        ),
		'member_id' => array(
            'type' => 'table:member@sysbankmember',
            'required' => true,
            'comment' => app::get('sysbankmember')->_('基础卡号id'),
            'label' => app::get('sysbankmember')->_('基础卡号'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
		'user_id' => array(
			'type' => 'table:account@sysuser',
			'required' => false,
			'comment' => app::get('sysbankmember')->_('会员id'),
			'label' => app::get('sysbankmember')->_('会员用户名'),
			'width' => 110,
			'in_list' => true,
			'default_in_list' => true,
			//'searchtype' => 'has',
		),
		'rel_name' => array (
			'type' => 'string',
			'default' => '',
			'required' => true,
			'comment' => app::get('sysbankmember')->_('持卡人姓名'),
			'label' => app::get('sysbankmember')->_('持卡人姓名'),
			'width' => 150,
			'in_list' => true,
			'default_in_list' => true,
			'searchtype' => 'has',
		),
		'card_number' => array (
			'type' => 'string',
			'default' => '',
			'required' => true,
			'comment' => app::get('sysbankmember')->_('会员银行卡号'),
			'label' => app::get('sysbankmember')->_('会员银行卡号'),
			'width' => 150,
			'in_list' => true,
			'default_in_list' => true,
			'searchtype' => 'has',
		),
        'create_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('sysbankmember')->_('创建时间'),
            'comment' => app::get('sysbankmember')->_('创建时间'),
            'width' => 110,
            'in_list' => false,
            'default_in_list' => false,
        ),
        'modified_time' => array(
            'type' => 'last_modify',
            'required' => false,
            'label' => app::get('sysbankmember')->_('修改时间'),
            'comment' => app::get('sysbankmember')->_('修改时间'),
            'width' => 110,
            'in_list' => false,
            'default_in_list' => false,
        ),
		'bind_time' => array(
            'type' => 'time',
            'required' => false,
            'label' => app::get('sysbankmember')->_('绑定时间'),
            'comment' => app::get('sysbankmember')->_('绑定时间'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
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
    'primary' => 'account_id',	//主键
    'index' => array (  //索引
        'id' => [
            'columns' => ['account_id','member_id','user_id'],   // 需要建立索引的字段名
        ],
    ),
    'comment' => app::get('sysbankmember')->_('绑定银行卡会员表'),
);
