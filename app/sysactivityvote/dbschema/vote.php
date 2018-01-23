<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/10/12
 * Time: 10:00
 * Desc: 投票表
 */
return  array(
    'columns'=> array(
        'vote_id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true, // 必须
            'comment' => app::get('sysactivityvote')->_('序号'),
            'label' => app::get('sysactivityvote')->_('序号'),
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
		'open_id' => array(
			'type' => 'string',
			'required' => true,
            'length' => 255,
			'comment' => app::get('sysactivityvote')->_('open_id'),
			'label' => app::get('sysactivityvote')->_('open_id'),
		),
		'game_id' => array(
			'type' => 'table:game',
			'required' => false,
			'comment' => app::get('sysactivityvote')->_('参赛id'),
			'label' => app::get('sysactivityvote')->_('参赛名称'),
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
		'cat_id' => array(
			'type' => 'table:cat',
			'required' => false,
			'comment' => app::get('sysactivityvote')->_('分类id'),
			'label' => app::get('sysactivityvote')->_('分类名称'),
			'width' => 110,
		),
		'vote_poll' => array(
            'type' => 'number',
			'required' => false,
			'default' => 1,
			'comment' => app::get('sysactivityvote')->_('票数'),
            'label' => app::get('sysactivityvote')->_('票数'),
            'in_list' => false,
            'default_in_list' => false,
        ),
        'ip' => array (
            'type' => 'ipaddr',
            'comment' => app::get('ectools')->_('投票IP'),
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
    'primary' => 'vote_id',	//主键
    'index' => array (  //索引
        'id' => [
            'columns' => ['shop_id','open_id','game_id','active_id'],   // 需要建立索引的字段名
        ],
    ),
    'comment' => app::get('sysactivityvote')->_('投票表'),
);
