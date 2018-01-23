<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/3
 * Time: 10:03
 * Desc: 帐号表
 */
return  array(
    'columns'=> array(
        'account_id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true, // 必须
            'comment' => app::get('sysstore')->_('序号'),
        ),
        'login_account'=> array(
            'type' => 'string',
            'length' => 100,
            'is_title'=>true,
            'comment' => app::get('sysstore')->_('用户名'),
            'label' => app::get('sysstore')->_('用户名'),
            'width' => 150,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'login_password'=> array(
            'type' => 'string',
            'length' => 60,
            'required' => true,
            'comment' => app::get('sysstore')->_('登录密码'),
        ),
        'store_id' => array(
            'type' => 'table:store@sysstore',
            'required' => true,
            'comment' => app::get('sysstore')->_('商城id'),
            'label' => app::get('sysstore')->_('商城名称'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'created_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('sysstore')->_('创建时间'),
            'comment' => app::get('sysstore')->_('创建时间'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'modified_time' => array(
            'type' => 'last_modify',
            'required' => false,
            'label' => app::get('sysstore')->_('修改时间'),
            'comment' => app::get('sysstore')->_('修改时间'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
		'disabled' => array(
            'type' => array(
                0 => app::get('sysstore')->_('正常'),
                1 => app::get('sysstore')->_('禁用'),
            ),
            'default' => 0,
            'comment' => app::get('sysstore')->_('是否禁用'),
        ),
        'deleted' => array(
            'type' => array(
                0 => app::get('sysstore')->_('正常'),
                1 => app::get('sysstore')->_('删除'),
            ),
            'default' => 0,
            'comment' => app::get('sysstore')->_('是否已删除'),
        )

    ),
    'primary' => 'account_id',	//主键
    'index' => array (  //索引
        'id' => [
            'columns' => ['account_id', 'store_id'],   // 需要建立索引的字段名
        ],
    ),
    'comment' => app::get('sysstore')->_('帐号表'),
);