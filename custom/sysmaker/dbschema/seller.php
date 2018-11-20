<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-13
 * Desc: 创客账号信息表
 */
return  array(
    'columns' => array(
        'seller_id' => array(
            'type' => 'table:account',
            'label' => app::get('sysmaker')->_('用户名'),
            'width' => 110,
            'order' => 10,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'name' => array(
            'type' => 'string',
            'length' => 50,
            'label' => app::get('sysmaker')->_('姓名'),
            'required' => true,
            'order' => 20,
            'in_list' => true,
            'default_in_list'=>true,
        ),
        'mobile' => array(
            'type' => 'string',
            'length' => 30,
            'label' => app::get('sysmaker')->_('手机号'),
            'required' => true,
            'order' => 30,
            'in_list' => true,
            'default_in_list'=>true,
        ),
        'shop_name' => array(
            'type' => 'string',
            'length' => 50,
            'label' => app::get('sysmaker')->_('店铺名称'),
            'order' => 40,
        ),
        'avatar'=>array(
            'type' => 'string',
            'label' => app::get('sysmaker')->_('头像'),
            'comment' => app::get('sysmaker')->_('头像'),
            'order' => 50,
        ),
        'shop_brand'=>array(
            'type' => 'string',
            'label' => app::get('sysmaker')->_('店招图片'),
            'comment' => app::get('sysmaker')->_('店招图片'),
            'order' => 60,
        ),
        'qr_code'=>array(
            'type' => 'string',
            'label' => app::get('sysmaker')->_('店招二维码'),
            'comment' => app::get('sysmaker')->_('店招二维码'),
            'order' => 70,
        ),
        'modified_time' => array(
            'type' => 'last_modify',
            'label' => app::get('sysmaker')->_('最后修改时间'),
        ),
    ),
    'primary' => 'seller_id',
    'index' => array(
        'ind_mobile' => ['columns' => ['mobile']],
    ),
    'comment' => app::get('sysmaker')->_('商家账号信息'),
);