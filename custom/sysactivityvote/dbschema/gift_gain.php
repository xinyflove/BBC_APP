<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/10/12
 * Time: 10:00
 * Desc: 赠品获得表
 */
return  array(
    'columns'=> array(
        'gift_gain_id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true, // 必须
            'comment' => app::get('sysactivityvote')->_('序号'),
        ),
		'gift_code' => array(
            'type' => 'string',
            'length' => 100,
			'default' => '',
            'required' => true,
			'comment' => app::get('sysactivityvote')->_('赠品code'),
            'label' => app::get('sysactivityvote')->_('赠品code'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
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
        'active_id' => array(
            'type' => 'table:active',
            'required' => true,
            'comment' => app::get('sysactivityvote')->_('活动id'),
            'label' => app::get('sysactivityvote')->_('活动名称'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
		'user_id' => array(
			// 'type' => 'table:account@sysuser',
            'type' => 'number',
			'required' => false,
			'comment' => app::get('sysactivityvote')->_('会员id'),
			'label' => app::get('sysactivityvote')->_('会员用户名'),
			'width' => 110,
			'in_list' => true,
			'default_in_list' => true,
		),
		'gift_id' => array(
			'type' => 'table:gift',
			'required' => false,
			'comment' => app::get('sysactivityvote')->_('赠品id'),
			'label' => app::get('sysactivityvote')->_('赠品名称'),
			'width' => 110,
			'in_list' => true,
			'default_in_list' => true,
		),
		'supplier_id' => array(
            'type' => 'table:supplier@sysshop',
            'required' => false,
			'default' => 0,
			'comment' => app::get('sysactivityvote')->_('供应商id'),
            'label' => app::get('sysactivityvote')->_('供应商'),
            'in_list' => true,
            'default_in_list' => true,
        ),
		'qr_code' => array(
            'type' => 'string',
            'comment' => app::get('sysactivityvote')->_('code 二维码'),
            'deny_export' => true,
        ),
		'status' => array(
            'type' => array(
                0 => app::get('sysactivityvote')->_('未核销'),
                1 => app::get('sysactivityvote')->_('核销'),
            ),
            'default' => 0,
            'comment' => app::get('sysactivityvote')->_('是否核销'),
        ),
		'used_time' => array(
            'type' => 'time',
            'required' => false,
            'label' => app::get('sysactivityvote')->_('核销时间'),
            'comment' => app::get('sysactivityvote')->_('核销时间'),
            'width' => 110,
            'in_list' => false,
            'default_in_list' => false,
        ),
		'start_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('sysactivityvote')->_('开始时间'),
            'comment' => app::get('sysactivityvote')->_('开始时间'),
            'width' => 110,
            'in_list' => false,
            'default_in_list' => false,
        ),
		'end_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('sysactivityvote')->_('结束时间'),
            'comment' => app::get('sysactivityvote')->_('结束时间'),
            'width' => 110,
            'in_list' => false,
            'default_in_list' => false,
        ),
        /* add_start_gurundong */
        'write_type' => array(
            'type' => array(
                'SHOP' => '商家核销',
                'SUPPLIER' => '供应商核销',
                'AGENTSHOP' => '线下店核销',
            ),
            'comment' => app::get('systrade')->_('核销类型'),
            'label' => app::get('systrade')->_('核销类型'),
            'in_list' => true,
            'default_in_list' => true,
            'default' => '',
        ),
        'write_shop_id' => array(
            'type' => 'number',
            'required' => true,
            'label' => app::get('sysitem')->_('核销店铺id'),
            'comment' => app::get('sysitem')->_('核销店铺id'),
            'in_list' => true,
            'default_in_list' => true,
            'default' => 0,
        ),
        'write_supplier_id' => array(
            'type' => 'number',
            'required' => true,
            'label' => app::get('sysitem')->_('核销供应商'),
            'comment' => app::get('sysitem')->_('核销供应商id'),
            'in_list' => true,
            'default_in_list' => true,
            'default' => 0,
        ),
        'write_agent_shop_id' => array(
            'type' => 'number',
            'required' => true,
            'label' => app::get('sysitem')->_('核销供应商'),
            'comment' => app::get('sysitem')->_('核销供应商id'),
            'in_list' => true,
            'default_in_list' => true,
            'default' => 0,
        ),
        /* add_end_start */
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
            'deny_export' => true,
        ),
		'open_id' => array(
			'type' => 'string',
			'required' => true,
            'length' => 255,
			'comment' => app::get('sysactivityvote')->_('open_id'),
			'label' => app::get('sysactivityvote')->_('open_id'),
            'deny_export' => true,

		),
        'deleted' => array(
            'type' => array(
                0 => app::get('sysactivityvote')->_('正常'),
                1 => app::get('sysactivityvote')->_('删除'),
            ),
            'default' => 0,
            'comment' => app::get('sysactivityvote')->_('状态'),
        )

    ),
    'primary' => 'gift_gain_id',	//主键
    'index' => array (  //索引
        'id' => [
            'columns' => ['shop_id','user_id','gift_id','supplier_id','active_id'],   // 需要建立索引的字段名
        ],
    ),
    'comment' => app::get('sysactivityvote')->_('赠品获得表'),
);
