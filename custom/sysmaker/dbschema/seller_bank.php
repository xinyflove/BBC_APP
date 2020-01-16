<?php
/**
* 创客绑定银行卡
* author by wanghaichao
* date 2019/7/31
*/
return  array(
    'columns' => array(
        'id' => array(
            'type' => 'number',
            'label' => app::get('sysmaker')->_('银行id'),
            'comment' => app::get('sysmaker')->_('银行id'),
            'autoincrement' => true,
            'order' => 10,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'seller_id' => array(
            'type' => 'string',
            'length' => 50,
            'label' => app::get('sysmaker')->_('创客id'),
            'comment' => app::get('sysmaker')->_('创客id'),
            'required' => true,
            'order' => 20,
            'in_list' => true,
            'default_in_list'=>true,
        ),
        'name' => array(
            'type' => 'string',
            'length' => 30,
            'label' => app::get('sysmaker')->_('持卡人姓名'),
            'comment' => app::get('sysmaker')->_('持卡人姓名'),
            //'required' => true,
            'order' => 30,
            'in_list' => true,
            'default_in_list'=>true,
        ),
        'bank_name' => array(
            'type' => 'string',
            'length' => 30,
            'label' => app::get('sysmaker')->_('银行名称'),
            'comment' => app::get('sysmaker')->_('银行名称'),
            //'required' => true,
            'order' => 30,
            'in_list' => true,
            'default_in_list'=>true,
        ),
        'account_bank' => array(
            'type' => 'string',
            'length' => 200,
            'label' => app::get('sysmaker')->_('开户行'),
            'comment' => app::get('sysmaker')->_('开户行'),
            'order' => 45,
        ),
        'card_number' => array(
            'type' => 'string',
            'length' => 50,
            'label' => app::get('sysmaker')->_('银行卡号'),
            'comment' => app::get('sysmaker')->_('银行卡号'),
            'order' => 40,
        ),
        'created_time' => array(
            'type' => 'time',
            'label' => app::get('sysmaker')->_('创建时间'),
            'comment' => app::get('sysmaker')->_('创建时间'),
        ),
        'modified_time' => array(
            'type' => 'last_modify',
            'label' => app::get('sysitem')->_('更新时间'),
            'comment' => app::get('sysitem')->_('最后更新时间'),
        ),
    ),
    'primary' => 'id',
    //'index' => array(
    //    'ind_s' => ['columns' => ['mobile']],
   // ),
    'comment' => app::get('sysmaker')->_('商家账号信息'),
);