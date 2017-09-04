<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/08/31
 * Time: 14:37
 */
return  array(
    'columns'=> array(
        'user_id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true, // 必须
            'comment' => app::get('testapp')->_('序号'),//备注
        ),
        'mobile' => array (
            'type' => 'string',
            'default' => '',
            'required' => true,
            'comment' => app::get('testapp')->_('用户手机号'),
            'label' => app::get('testapp')->_('手机号'),
            'width' => 150,
            'in_list' => true,
            'default_in_list' => true,
            'searchtype' => 'has',
        ),
        'level_id' => array(
            'type' => 'table:level@testapp',
            'required' => true,
            'comment' => app::get('testapp')->_('等级id'),
            'label' => app::get('testapp')->_('等级'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'create_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('testapp')->_('创建时间'),
            'comment' => app::get('testapp')->_('创建时间'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => false,
        ),
        'modified_time' => array(
            'type' => 'last_modify',
            'required' => true,
            'label' => app::get('testapp')->_('修改时间'),
            'comment' => app::get('testapp')->_('修改时间'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => false,
        ),
        'valid_begin' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('testapp')->_('有效期开始'),
            'comment' => app::get('testapp')->_('有效期开始'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'valid_over' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('testapp')->_('有效期结束'),
            'comment' => app::get('testapp')->_('有效期结束'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'deleted' => array(
            'type' => array(
                0 => app::get('testapp')->_('正常'),
                1 => app::get('testapp')->_('删除'),
            ),
            'default' => 0,
            'comment' => app::get('testapp')->_('是否已删除'),
        )

    ),
    'primary' => 'user_id',
    'index' => array (  //索引名称
        'mobile' => [
            'columns' => ['mobile'],   // 需要建立索引的字段名
        ],
    ),
    'comment' => app::get('testapp')->_('用户表'),
);
