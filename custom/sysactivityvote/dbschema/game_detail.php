<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/10/12
 * Time: 10:00
 * Desc: 参赛详情参数表
 */
return  array(
    'columns'=> array(
		'game_id' => array(
            'type' => 'table:game',
            'required' => true, // 必须
            'comment' => app::get('sysactivityvote')->_('参赛id'),
        ),
		'type_id' => array(
            'type' => 'table:type',
            'required' => true,
            'comment' => app::get('sysactivityvote')->_('类型id（菜品，饭店，人物）'),
            'label' => app::get('sysactivityvote')->_('类型名称'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
		'base_desc' => array(
            'type' => 'text',
            'comment' => app::get('sysactivityvote')->_('人物基本信息|饭店口味|标签'),
        ),
		'work_desc' => array(
            'type' => 'text',
            'comment' => app::get('sysactivityvote')->_('人物从业经历|商家信息|配料'),
        ),
		'base_list_image' => array(
            'type' => 'text',
            'comment' => app::get('sysactivityvote')->_('生活风采|推荐菜单|做法 图片列表'),
        ),
		'work_list_image' => array(
            'type' => 'text',
            'comment' => app::get('sysactivityvote')->_('资格证书|就餐环境|无 图片列表'),
        ),
		'recommend_reason_desc' => array(
            'type' => 'text',
            'comment' => app::get('sysactivityvote')->_('推荐理由|营业许可|营业许可'),
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
            'columns' => ['type_id'],   // 需要建立索引的字段名
        ],
    ),
    'comment' => app::get('sysactivityvote')->_('参赛详情参数表'),
);
