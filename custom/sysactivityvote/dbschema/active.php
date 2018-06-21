<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/10/11
 * Time: 16:00
 * Desc: 活动表
 */
return  array(
    'columns'=> array(
        'active_id' => array(
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
		'active_name' => array (
			'type' => 'string',
			'length' => 90,
			'default' => '',
			'required' => true,
			'comment' => app::get('sysactivityvote')->_('活动名称'),
			'is_title' => true,
			'label' => app::get('sysactivityvote')->_('活动名称'),
			'width' => 150,
			'in_list' => true,
            'is_title' => true,
			'default_in_list' => true,
			'searchtype' => 'has',
		),
		'active_profile' => array (
			'type' => 'string',
			'length' => 300,
			'default' => '',
			'required' => false,
			'comment' => app::get('sysactivityvote')->_('活动简介'),
			'label' => app::get('sysactivityvote')->_('活动简介'),
			'in_list' => false,
			'default_in_list' => false,
		),
		'active_start_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('sysactivityvote')->_('活动开始时间'),
            'comment' => app::get('sysactivityvote')->_('活动开始时间'),
            'width' => 110,
            'in_list' => false,
            'default_in_list' => false,
        ),
		'active_end_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('sysactivityvote')->_('活动结束时间'),
            'comment' => app::get('sysactivityvote')->_('活动结束时间'),
            'width' => 110,
            'in_list' => false,
            'default_in_list' => false,
        ),
		'popular_vote_start_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('sysactivityvote')->_('大众投票开始时间'),
            'comment' => app::get('sysactivityvote')->_('大众投票开始时间'),
            'width' => 110,
            'in_list' => false,
            'default_in_list' => false,
        ),
		'popular_vote_end_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('sysactivityvote')->_('大众投票结束时间'),
            'comment' => app::get('sysactivityvote')->_('大众投票结束时间'),
            'width' => 110,
            'in_list' => false,
            'default_in_list' => false,
        ),
		'expert_vote_start_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('sysactivityvote')->_('专家投票开始时间'),
            'comment' => app::get('sysactivityvote')->_('专家投票开始时间'),
            'width' => 110,
            'in_list' => false,
            'default_in_list' => false,
        ),
		'expert_vote_end_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('sysactivityvote')->_('专家投票结束时间'),
            'comment' => app::get('sysactivityvote')->_('专家投票结束时间'),
            'width' => 110,
            'in_list' => false,
            'default_in_list' => false,
        ),
		'active_link' => array (
			'type' => 'string',
			'default' => '',
			'required' => true,
			'comment' => app::get('sysactivityvote')->_('活动链接'),
			'label' => app::get('sysactivityvote')->_('活动链接'),
			'width' => 110,
			'in_list' => false,
			'default_in_list' => false,
		),
		'personal_everyday_get_limit' => array (
			'type' => 'smallint',
			'default' => 0,
			'required' => false,
			'comment' => app::get('sysactivityvote')->_('每人每天获得奖品次数'),
			'label' => app::get('sysactivityvote')->_('每人每天获得奖品次数'),
			'width' => 110,
			'in_list' => false,
			'default_in_list' => false,
		),
		'active_days' => array (
			'type' => 'smallint',
			'default' => 0,
			'required' => true,
			'comment' => app::get('sysactivityvote')->_('奖品发放天数'),
			'label' => app::get('sysactivityvote')->_('奖品发放天数'),
			'width' => 110,
			'in_list' => false,
			'default_in_list' => false,
		),
		'win_probability' => array (
			'type' => 'smallint',
			'default' => 0,
			'required' => false,
			'comment' => app::get('sysactivityvote')->_('获得奖品概率'),
		),
		'boot_ad_image' => array (
			'type' => 'string',
			'comment' => app::get('sysactivityvote')->_('启动页广告图片'),
		),
		'boot_ad_url' => array (
			'type' => 'string',
			'length' => 255,
			'comment' => app::get('sysactivityvote')->_('启动页广告链接'),
		),
		'boot_ad_able' => array (
			'type' => array(
				0 => app::get('sysactivityvote')->_('关闭'),
				1 => app::get('sysactivityvote')->_('启用'),
			),
			'default' => 0,
			'comment' => app::get('sysactivityvote')->_('是否启用启动页广告'),
		),
		'top_ad_image' => array (
			'type' => 'string',
			'comment' => app::get('sysactivityvote')->_('顶部广告图片'),
		),
		'top_ad_url' => array (
			'type' => 'string',
			'length' => 255,
			'comment' => app::get('sysactivityvote')->_('顶部广告链接'),
		),
		'top_ad_slide' => array (
			'type' => 'text',
			'comment' => app::get('sysactivityvote')->_('顶部广告轮播图'),
		),
		'active_rule' => array (
			'type' => 'text',
			'comment' => app::get('sysactivityvote')->_('活动规则'),
		),
		'active_wap_rule' => array (
			'type' => 'text',
			'comment' => app::get('sysactivityvote')->_('活动规则(wap)'),
		),
        'gift_wap_rule' => array (
            'type' => 'text',
            'comment' => app::get('sysactivityvote')->_('奖品说明(wap)'),
        ),
		'active_view' => array (
			'type' => 'number',
			'default' => 0,
			'comment' => app::get('sysactivityvote')->_('活动浏览量'),
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
		'active_type' => array(
			'type' => array(
				'vote' => app::get('sysactivityvote')->_('投票活动'),
				'blue_eyes' => app::get('sysactivityvote')->_('蓝睛活动'),
			),
			'required' => true,
			'default' => '',
			'comment' => app::get('sysactivityvote')->_('活动类型'),
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
    'primary' => 'active_id',	//主键
    'index' => array (  //索引
        'id' => [
            'columns' => ['shop_id'],   // 需要建立索引的字段名
        ],
    ),
    'comment' => app::get('sysactivityvote')->_('活动表'),
);
