<?php

return  array(
    'columns' => array(
		'id'=>array(
            'type' => 'bigint',
            'unsigned' => true,
            'required' => true,
            'comment' => app::get('systrade')->_('订单号'),
		),

        'cash_id' => array(
            'type' => 'table:cash',
            'comment' => app::get('sysmaker')->_('申请提现id'),
        ),

        'seller_id' => array(
            'type' => 'table:account',
            'comment' => app::get('sysmaker')->_('主持人id'),
        ),

        'seller_name' => array(
            'type' => 'string',
            'length' => 50,
            'comment' => app::get('sysmaker')->_('主持人姓名'),
            'required' => true,
        ),

        'shop_id' => array(
            'type' => 'table:shop@sysshop',
            'comment' => app::get('sysmaker')->_('店铺id'),
        ),

        'payment' => array(
            'type' => 'money',
            'comment' => app::get('sysclearing')->_('结算金额'),
        ),

		'status'=>array(
            'type' => 'string',
            'comment' => app::get('sysmaker')->_('状态 fall:打款失败 success:打款成功'),
		),

        'remark'=>array(
            'type' => 'string',
            'label' => app::get('sysmaker')->_('备注'),
            'comment' => app::get('sysmaker')->_('备注'),
        ),

        'openid' => array(
            'type' => 'string',
            'required' => false,
            'comment'=>app::get('sysmaker')->_('对应信任登陆方的用户唯一标识'),
        ),

        'thirdparty_payment_no' => array(
            'type' => 'string',
            'length' => 64,
            'comment' => app::get('sysitem')->_('第三方支付单号'),
        ),

        'create_time' => array(
            'type' => 'time',
            'comment' => app::get('sysclearing')->_('创建时间'),
        ),

        'payment_time' => array(
            'type' => 'time',
            'comment' => app::get('sysmaker')->_('支付时间'),
        ),
    ),
    'primary' => 'id',
    'comment' => app::get('sysmaker')->_('主持人提现打款表'),
);