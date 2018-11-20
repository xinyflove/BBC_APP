<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-16
 * Desc: 创客第三方信任登录信息表
 */
return array (
    'columns' => array (
        'id' => array(
            'type' => 'number',
            'required' => true,
            'autoincrement' => true,
            'comment' => app::get('sysmaker')->_('自增主键'),
        ),
        'flag' => array(
            'type' => 'string',
            'length' => 20,
            'order' => 10,
            'label' => app::get('sysmaker')->_('第三方'),
            'comment'=>app::get('sysmaker')->_('对应信任登陆方的第三方标识'),
        ),
        'openid' => array(
            'type' => 'string',
            'required' => false,
            'order' => 20,
            'label' => app::get('sysmaker')->_('openid'),
            'comment'=>app::get('sysmaker')->_('对应信任登陆方的用户唯一标识'),
        ),
        'nickname' => array (
            'type' => 'string',
            'length' => 50,
            'order' => 30,
            'label' => app::get('sysmaker')->_('昵称'),
            'comment' => app::get('sysmaker')->_('昵称'),
        ),
        'figureurl' => array (
            'type' => 'string',
            'length' => 500,
            'order' => 40,
            'label' => app::get('sysmaker')->_('头像URL'),
            'comment' => app::get('sysmaker')->_('头像URL'),
        ),
        'sex' => array (
            'type' =>
                array (
                    0 => app::get('sysmaker')->_('保密'),
                    1 => app::get('sysmaker')->_('男'),
                    2 => app::get('sysmaker')->_('女'),
                ),
            'default' => 0,
            'order' => 50,
            'label' => app::get('sysmaker')->_('性别'),
            'comment' => app::get('sysmaker')->_('性别'),
        ),
        'country' => array (
            'type' => 'string',
            'length' => 55,
            'order' => 60,
            'label' => app::get('sysmaker')->_('国家'),
            'comment' => app::get('sysmaker')->_('国家'),
        ),
        'province' => array (
            'type' => 'string',
            'length' => 55,
            'order' => 70,
            'label' => app::get('sysmaker')->_('省'),
            'comment' => app::get('sysmaker')->_('省'),
        ),
        'city' => array (
            'type' => 'string',
            'length' => 55,
            'order' => 80,
            'label' => app::get('sysmaker')->_('市'),
            'comment' => app::get('sysmaker')->_('市'),
        ),
        'create_time' => array (
            'type' => 'time',
            'order' => 90,
            'label' => app::get('sysmaker')->_('创建时间'),
            'comment' => app::get('sysmaker')->_('创建时间'),
        ),
    ),

    'primary' => 'id',

    'index' => array(
        'ind_flag' => ['columns' => ['flag']],
        'ind_openid' => ['columns' => ['openid']],
    ),

    'comment' => app::get('sysmaker')->_('创客第三方信任登录信息表'),
);
