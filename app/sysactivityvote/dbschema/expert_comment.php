<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/10/12
 * Time: 10:00
 * Desc: 专家评价参数表
 */
return  array(
    'columns'=> array(
        'expert_comment_id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true, // 必须
            'comment' => app::get('sysactivityvote')->_('序号'),
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
        'expert_id' => array(
            'type' => 'table:expert',
            'required' => true,
            'comment' => app::get('sysactivityvote')->_('专家id'),
            'label' => app::get('sysactivityvote')->_('专家名称'),
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
		'comment_content' => array(
            'type' => 'string',
            'length' => 500,
			'default' => '',
            'required' => true,
			'comment' => app::get('sysactivityvote')->_('评论内容'),
            'label' => app::get('sysactivityvote')->_('评论内容'),
            'in_list' => false,
            'default_in_list' => false,
        ),
//		'comment_avatar' => array(
//            'type' => 'string',
//            'comment' => app::get('sysactivityvote')->_('评价人头像'),
//        ),
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
    'primary' => 'expert_comment_id',	//主键
    'index' => array (  //索引
        'id' => [
            'columns' => ['game_id','active_id','expert_id','shop_id'],   // 需要建立索引的字段名
        ],
    ),
    'comment' => app::get('sysactivityvote')->_('专家评价参数表'),
);
