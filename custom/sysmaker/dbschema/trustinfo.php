<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-13
 * Desc: 创客信任登录表
 */
return  array(
    'columns'=>array(
        'trust_id'=>array(
            'type'=>'number',
            'required' => true,
            'autoincrement' => true,
            'comment'=>app::get('sysmaker')->_('信任id'),
        ),
        'seller_id'=>array(
            'type'=>'table:account',
            'label'=>app::get('sysmaker')->_('用户名'),
            'comment'=>app::get('sysmaker')->_('用户名'),
        ),
        'user_flag'=>array(
            'type' => 'string',
            'required' => true,
            'comment'=>app::get('sysmaker')->_('对应信任登陆方的用户唯一标识'),
        ),
        'flag'=>array(
            'type' => 'string',
            'length' => 20,
            'required' => true,
            'comment'=>app::get('sysmaker')->_('对应信任登陆方的标识'),
        ),
    ),
    'primary' => 'trust_id',
    'index' => array(
        'ind_bind_uniq' => ['columns' => ['seller_id', 'user_flag'], 'prefix' => 'unique'],
    ),
    'comment' => '创客信任登录表',
);
