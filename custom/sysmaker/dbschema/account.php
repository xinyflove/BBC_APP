<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-13
 * Desc: 创客帐号表
 */
return  array(
    'columns'=> array(
        'seller_id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true, // 必须
            'comment' => app::get('sysmaker')->_('创客账户序号ID'),
            'order' => 0,
        ),
        'login_account'=> array(
            'type' => 'string',
            'length' => 100,
            'is_title'=>true,
            'comment' => app::get('sysmaker')->_('登录名'),
            'label' => app::get('sysmaker')->_('登录名'),
            'width' => 150,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 1,
        ),
        'login_password'=> array(
            'type' => 'string',
            'length' => 60,
            'required' => true,
            'comment' => app::get('sysmaker')->_('登录密码'),
            'order' => 2,
        ),
        'status' => array(
            'type' => array(
                'pending' => app::get('sysmaker')->_('待审核'),
                'refuse' => app::get('sysmaker')->_('审核拒绝'),
                'success' => app::get('sysmaker')->_('审核通过'),
            ),
            'default' => 'pending',
            'label' => app::get('sysmaker')->_('审核状态'),
            'comment' => app::get('sysmaker')->_('审核状态'),
            'in_list' => true,
            'default_in_list' => true,
            'order' => 3,
        ),
        'reason'=>array(
            'type' => 'string',
            'length' => 500,
            'in_list'=>true,
            'default_in_list'=>true,
            'label' => app::get('sysmaker')->_('不通过原因'),
            'comment' => app::get('sysmaker')->_('审核不通过原因'),
            'order' => 4,
        ),
        'created_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('sysmaker')->_('创建时间'),
            'comment' => app::get('sysmaker')->_('创建时间'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 5,
        ),
        'modified_time' => array(
            'type' => 'last_modify',
            'required' => false,
            'label' => app::get('sysmaker')->_('修改时间'),
            'comment' => app::get('sysmaker')->_('修改时间'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 6,
        ),
		'disabled' => array(
            'type' => array(
                0 => app::get('sysmaker')->_('正常'),
                1 => app::get('sysmaker')->_('禁用'),
            ),
            'default' => 0,
            'label' => app::get('sysmaker')->_('是否禁用'),
            'comment' => app::get('sysmaker')->_('是否禁用'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 7,
        ),
        'deleted' => array(
            'type' => array(
                0 => app::get('sysmaker')->_('正常'),
                1 => app::get('sysmaker')->_('删除'),
            ),
            'default' => 0,
            'comment' => app::get('sysmaker')->_('是否已删除'),
            'order' => 8,
        )

    ),
    'primary' => 'seller_id',	//主键
    'index' => array(
        'ind_login_account' => ['columns' => ['login_account']],
        'ind_created_time' => ['columns' => ['created_time']],
    ),
    'comment' => app::get('sysmaker')->_('创客帐号表'),
);
