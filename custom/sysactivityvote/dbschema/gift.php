<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/10/12
 * Time: 10:00
 * Desc: 赠品管理表
 */
return  array(
    'columns'=> array(
        'gift_id' => array(
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
		'gift_name' => array(
            'type' => 'string',
            'length' => 100,
			'default' => '',
            'required' => true,
			'comment' => app::get('sysactivityvote')->_('赠品名称'),
            'is_title' => true,
            'label' => app::get('sysactivityvote')->_('赠品名称'),
            'width' => 110,
            'searchtype' => 'has',
            'in_list' => true,
            'default_in_list' => true,
        ),
		'image_default_id' => array(
            'type' => 'string',
            'comment' => app::get('sysactivityvote')->_('赠品主图'),
        ),
		'list_image' => array(
            'type' => 'text',
            'comment' => app::get('sysactivityvote')->_('赠品列表图'),
        ),
		'gift_profile' => array (
			'type' => 'string',
			'length' => 300,
			'default' => '',
			'required' => false,
			'comment' => app::get('sysactivityvote')->_('赠品简介'),
			'label' => app::get('sysactivityvote')->_('赠品简介'),
			'in_list' => false,
			'default_in_list' => false,
		),
		'gift_desc' => array(
            'type' => 'text',
            'comment' => app::get('sysactivityvote')->_('赠品描述'),
        ),
		'gift_wap_desc' => array(
            'type' => 'text',
            'comment' => app::get('sysactivityvote')->_('赠品描述(wap)'),
        ),
		'gift_total' => array(
            'type' => 'number',
			'default' => 0,
			'comment' => app::get('sysactivityvote')->_('赠品数量'),
            'label' => app::get('sysactivityvote')->_('赠品数量'),
        ),
		'valid_start_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('sysactivityvote')->_('奖品有效开始时间'),
            'comment' => app::get('sysactivityvote')->_('奖品有效开始时间'),
            'width' => 110,
            'in_list' => false,
            'default_in_list' => false,
        ),
		'valid_end_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('sysactivityvote')->_('奖品有效结束时间'),
            'comment' => app::get('sysactivityvote')->_('奖品有效结束时间'),
            'width' => 110,
            'in_list' => false,
            'default_in_list' => false,
        ),
		'gain_total' => array(
            'type' => 'number',
			'default' => 0,
			'comment' => app::get('sysactivityvote')->_('已领券量'),
            'label' => app::get('sysactivityvote')->_('已领券量'),
        ),
		'supplier_id' => array(
            'type' => 'number',
            'required' => false,
			'default' => 0,
			'comment' => app::get('sysactivityvote')->_('供应商id'),
            'label' => app::get('sysactivityvote')->_('供应商'),
            'in_list' => true,
            'default_in_list' => true,
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
    'primary' => 'gift_id',	//主键
    'index' => array (  //索引
        'id' => [
            'columns' => ['shop_id','supplier_id','active_id'],   // 需要建立索引的字段名
        ],
    ),
    'comment' => app::get('sysactivityvote')->_('赠品管理表'),
);
