<?php
/**
 * Auth: 王海超
 * Time: 2018/11/15
 * Desc: 主持人提现表
 */
return  array(
    'columns' => array(
		'id'=>array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true, // 必须
            'comment' => app::get('sysmaker')->_('id'),
            'order' => 0,
		),

        'seller_id' => array(
            'type' => 'table:account',
            'label' => app::get('sysmaker')->_('主持人id'),
            'width' => 110,
            'order' => 10,
            'in_list' => true,
            'default_in_list' => true,
        ),

        'shop_id' => array(
            'type' => 'table:shop@sysshop',
            'label' => app::get('sysmaker')->_('店铺id'),
            'width' => 110,
            'order' => 10,
            'in_list' => true,
            'default_in_list' => true,
        ),
		
        'payment' => array(
            'type' => 'money',
            'comment' => app::get('sysclearing')->_('结算金额'),
            'label' => app::get('sysclearing')->_('结算金额'),
            'in_list' => true,
            'default_in_list'=>true,
        ),

        //'mobile' => array(
        //    'type' => 'string',
        ///    'length' => 30,
         //   'label' => app::get('sysmaker')->_('手机号'),
        ///    'required' => true,
        ///    'order' => 30,
         //   'in_list' => true,
         //   'default_in_list'=>true,
        //),
        /*'shop_name' => array(
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
        ),*/

        'remark'=>array(
            'type' => 'string',
            'label' => app::get('sysmaker')->_('备注'),
            'comment' => app::get('sysmaker')->_('备注'),
            'order' => 70,
        ),
		
        'create_time' => array(
            'type' => 'time',
            'label' => app::get('sysclearing')->_('佣金交易时间'),
            'comment' => app::get('sysclearing')->_('佣金交易时间'),
            'in_list' => true,
            'default_in_list'=>true,
        ),

        'modified_time' => array(
            'type' => 'last_modify',
            'label' => app::get('sysmaker')->_('最后修改时间'),
        ),
    ),
    'primary' => 'id',
    'comment' => app::get('sysmaker')->_('主持人提现表'),
);