<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/10/12
 * Time: 10:00
 * Desc: 参赛表
 */
return  array(
    'columns'=> array(
        'game_id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true, // 必须
            'comment' => app::get('sysactivityvote')->_('序号'),
        ),
		'shop_id' => array(
            'type' => 'table:shop@sysshop',
            'required' => true,
            'comment' => app::get('sysactivityvote')->_('店铺id'),
            'label' => app::get('sysactivityvote')->_('店铺名称'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
		'game_number' => array(
            'type' => 'string',
            'length' => 100,
			'default' => '',
            'required' => true,
			'comment' => app::get('sysactivityvote')->_('参赛编号'),
            'label' => app::get('sysactivityvote')->_('参赛编号'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
		'game_name' => array(
            'type' => 'string',
            'length' => 100,
			'default' => '',
            'required' => true,
			'comment' => app::get('sysactivityvote')->_('参赛名称'),
            'is_title' => true,
            'label' => app::get('sysactivityvote')->_('参赛名称'),
            'width' => 110,
            'searchtype' => 'has',
            'in_list' => true,
            'is_title' => true,
            'default_in_list' => true,
        ),
		'game_profile' => array (
			'type' => 'string',
			'length' => 300,
			'default' => '',
			'required' => false,
			'comment' => app::get('sysactivityvote')->_('参赛简介'),
			'label' => app::get('sysactivityvote')->_('参赛简介'),
			'in_list' => false,
			'default_in_list' => false,
            'deny_export' => true,
		),
		'game_poll' => array (
			'type' => 'number',
			'default' => 0,
			'required' => false,
			'comment' => app::get('sysactivityvote')->_('base投票'),
			'label' => app::get('sysactivityvote')->_('票数，预留 base投票'),
			'in_list' => false,
			'default_in_list' => false,
		),
		'actual_poll' => array (
			'type' => 'number',
			'default' => 0,
			'required' => false,
			'comment' => app::get('sysactivityvote')->_('实际投票数'),
			'label' => app::get('sysactivityvote')->_('实际投票数'),
			'in_list' => false,
			'default_in_list' => false,
		),
		'total_poll' => array (
			'type' => 'number',
			'default' => 0,
			'required' => false,
			'comment' => app::get('sysactivityvote')->_('全部投票数'),
			'label' => app::get('sysactivityvote')->_('全部投票数'),
			'in_list' => false,
			'default_in_list' => false,
		),
		'order_sort' => array(
            'type' => 'number',
			'default' => 0,
			'comment' => app::get('sysactivityvote')->_('排序'),
            'label' => app::get('sysactivityvote')->_('排序'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
            'deny_export' => true,
        ),
		'image_default_id' => array(
            'type' => 'string',
            'comment' => app::get('sysactivityvote')->_('默认图'),
            'deny_export' => true,
        ),
		'list_image' => array(
            'type' => 'text',
            'comment' => app::get('sysactivityvote')->_('图片列表'),
            'deny_export' => true,
        ),
		'type_id' => array(
            'type' => 'table:type',
            'required' => true,
            'comment' => app::get('sysactivityvote')->_('类型id'),
            'label' => app::get('sysactivityvote')->_('类型名称'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
		'game_desc' => array(
            'type' => 'text',
            'comment' => app::get('sysactivityvote')->_('参赛详情'),
            'deny_export' => true,
        ),
		'game_wap_desc' => array(
            'type' => 'text',
            'comment' => app::get('sysactivityvote')->_('参赛详情wap端'),
            'deny_export' => true,
        ),
		'is_game' => array(
            'type' => array(
                0 => app::get('sysactivityvote')->_('未参赛'),
                1 => app::get('sysactivityvote')->_('参赛'),
            ),
            'default' => 0,
            'comment' => app::get('sysactivityvote')->_('是否参赛'),
        ),
		'active_id' => array(
            'type' => 'table:active',
            'required' => true,
            'comment' => app::get('sysactivityvote')->_('活动id'),
            'label' => app::get('sysactivityvote')->_('活动名称'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
		'cat_id' => array(
            'type' => 'table:cat',
            'required' => true,
            'comment' => app::get('sysactivityvote')->_('分类id'),
            'label' => app::get('sysactivityvote')->_('分类名称'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'create_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('sysactivityvote')->_('创建时间'),
            'comment' => app::get('sysactivityvote')->_('创建时间'),
            'width' => 110,
            'in_list' => false,
            'default_in_list' => false,
        ),
        'modified_time' => array(
            'type' => 'last_modify',
            'required' => false,
            'label' => app::get('sysactivityvote')->_('修改时间'),
            'comment' => app::get('sysactivityvote')->_('修改时间'),
            'width' => 110,
            'in_list' => false,
            'default_in_list' => false,
        ),
        'deleted' => array(
            'type' => array(
                0 => app::get('sysactivityvote')->_('正常'),
                1 => app::get('sysactivityvote')->_('删除'),
            ),
            'default' => 0,
            'comment' => app::get('sysactivityvote')->_('是否已删除'),
        )

    ),
    'primary' => 'game_id',	//主键
    'index' => array (  //索引
        'id' => [
            'columns' => ['shop_id','type_id','active_id','cat_id'],   // 需要建立索引的字段名
        ],
    ),
    'comment' => app::get('sysactivityvote')->_('参赛表'),
);
