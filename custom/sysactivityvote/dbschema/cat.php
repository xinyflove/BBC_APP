<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/10/11
 * Time: 17:00
 * Desc: 分类表
 */
return  array(
    'columns'=> array(
        'cat_id' => array(
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
		'parent_id' => array(
            'type' => 'number',
			'comment' => app::get('sysactivityvote')->_('分类父级ID'),
            'label' => app::get('sysactivityvote')->_('分类父级ID'),
        ),
		'cat_path' => array(
            'type' => 'string',
            'length' => 100,
            'default' => ',',
            'comment' => app::get('sysactivityvote')->_('分类路径(从根至本结点的路径,逗号分隔,首部有逗号)'),
        ),
		'level' => array(
            'type' => array(
                '1' => app::get('sysactivityvote')->_('一级分类'),
                '2' => app::get('sysactivityvote')->_('二级分类'),
                '3' => app::get('sysactivityvote')->_('三级分类'),
            ),
            'default' => '1',
			'comment' => app::get('sysactivityvote')->_('分类层级'),
            'label' => app::get('sysactivityvote')->_('分类层级'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => false,
        ),
		'cat_name' => array(
            'type' => 'string',
            'length' => 100,
			'default' => '',
            'required' => true,
			'comment' => app::get('sysactivityvote')->_('分类名称'),
            'is_title' => true,
            'label' => app::get('sysactivityvote')->_('分类名称'),
            'width' => 110,
            'searchtype' => 'has',
            'in_list' => true,
            'default_in_list' => true,
        ),
		'cat_image' => array(
            'type' => 'string',
            'comment' => app::get('sysactivityvote')->_('分类图片'),
        ),
		'order_sort' => array(
            'type' => 'number',
			'comment' => app::get('sysactivityvote')->_('排序'),
            'label' => app::get('sysactivityvote')->_('排序'),
            'width' => 110,
            'default' => 0,
            'in_list' => true,
            'default_in_list' => true,
        ),
		'personal_everyday_vote_limit' => array (
			'type' => 'smallint',
			'default' => 0,
			'required' => false,
			'comment' => app::get('sysactivityvote')->_('每人每天投票次数'),
			'label' => app::get('sysactivityvote')->_('每人每天投票次数'),
			'width' => 110,
			'in_list' => false,
			'default_in_list' => false,
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
		'game_personal_everyday_vote_limit' => array (
			'type' => 'smallint',
			'default' => 0,
			'required' => false,
			'comment' => app::get('sysactivityvote')->_('一个参赛id 每人一天可投票次数'),
			'label' => app::get('sysactivityvote')->_('一个参赛id 每人一天可投票次数'),
			'width' => 110,
			'in_list' => false,
			'default_in_list' => false,
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
    'primary' => 'cat_id',	//主键
    'index' => array (  //索引
        'id' => [
            'columns' => ['shop_id','active_id'],   // 需要建立索引的字段名
        ],
    ),
    'comment' => app::get('sysactivityvote')->_('分类表'),
);
