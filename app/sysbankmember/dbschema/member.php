<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/09/01
 * Time: 10:00
 */
return  array(
    'columns'=> array(
        'member_id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true, // 必须
            'comment' => app::get('sysbankmember')->_('序号'),
        ),
		'shop_id' => array(
            'type' => 'table:shop@sysshop',
            'required' => true,
            'comment' => app::get('sysbankmember')->_('店铺id'),
            'label' => app::get('sysbankmember')->_('店铺名称'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
		'bank_id' => array(
            'type' => 'table:bank@sysbankmember',
            'required' => true,
            'comment' => app::get('sysbankmember')->_('银行id'),
            'label' => app::get('sysbankmember')->_('银行名称'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
			//'searchtype' => 'has',
        ),
		'card_number' => array (
			'type' => 'string',
			'default' => '',
			'required' => true,
			'comment' => app::get('sysbankmember')->_('银行卡号'),
			'label' => app::get('sysbankmember')->_('银行卡号'),
			'width' => 150,
			'in_list' => true,
			'default_in_list' => true,
			'searchtype' => 'has',
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
    'primary' => 'member_id',	//主键
    'index' => array (  //索引
        'id' => [
            'columns' => ['shop_id','bank_id','user_id'],   // 需要建立索引的字段名
        ],
    ),
    'comment' => app::get('sysbankmember')->_('绑定银行卡会员表'),
);
