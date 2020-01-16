<?php
/**
* 协会表
* author by wanghaichao
* date 2019/9/2
*/
return  array(
    'columns' => array(
        'group_id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true, // 必须
            'comment' => app::get('sysmaker')->_('协会ID'),
            'order' => 0,
        ),
        'shop_id' => array(
            'type' => 'table:shop@sysshop',
            'required' => true,
            'comment' => app::get('sysmaker')->_('关联店铺id'),
        ),
        'name' => array(
            'type' => 'string',
            'length' => 50,
            'label' => app::get('sysmaker')->_('协会名称'),
            'required' => true,
            'order' => 20,
            'in_list' => true,
            'default_in_list'=>true,
        ),
        'contact' => array(
            'type' => 'string',
            'length' => 200,
            'label' => app::get('sysmaker')->_('联系人'),
            'required' => true,
            'order' => 30,
            'in_list' => true,
            'default_in_list'=>true,
        ),
        'mobile' => array(
            'type' => 'string',
            'length' => 30,
            'label' => app::get('sysmaker')->_('协会电话号'),
            'required' => true,
            'order' => 30,
            'in_list' => true,
            'default_in_list'=>true,
        ),
        'created_time' => array(
            'type' => 'time',
            'in_list' => true,
            'default_in_list' => true,
            'comment' => app::get('sysitem')->_('创建时间'),
            'label' => app::get('sysitem')->_('创建时间'),
            'order' => 14,
        ),
        'modified_time' => array(
            'type' => 'last_modify',
            'label' => app::get('sysmaker')->_('最后修改时间'),
        ),
    ),
    'primary' => 'group_id',
    'index' => array(
        'ind_mobile' => ['columns' => ['mobile']],
    ),
    'comment' => app::get('sysmaker')->_('商家账号信息'),
);