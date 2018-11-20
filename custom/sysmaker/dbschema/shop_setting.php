<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-14
 * Desc: 创客店铺配置表
 */
return array(
    'columns' => array(
        'shop_id' => array(
            'type' => 'table:shop@sysshop',
            'required' => true,
            'comment' => app::get('sysmaker')->_('店铺ID'),
        ),
        'maker_rate' => array(
            'type' => 'money',
            'default' => '0',
            'comment' => app::get('sysmaker')->_('创客分佣比例'),
        ),
        'created_time' => array(
            'type' => 'time',
            'comment' => app::get('sysmaker')->_('创建时间'),
        ),
        'modified_time' => array(
            'type' => 'last_modify',
            'comment' => app::get('sysmaker')->_('最后更新时间'),
        ),
    ),
    'primary' => ['shop_id'],
    'comment' => app::get('sysmaker')->_('创客店铺配置表'),
);
