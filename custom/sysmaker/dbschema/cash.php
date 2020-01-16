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

		'status'=>array(
            'type' => array(
                'pending'=>'审核中',
				'success'=>'审核通过',
				'refuse'=>'拒绝',
            ),
            'label' => app::get('sysmaker')->_('申请状态'),
            'comment' => app::get('sysmaker')->_('申请状态'),
            'order' => 70,
        ),

        'settlement_type'=>array(
            'type' => 'string',
            // 'default' => '0',
            'comment' => app::get('sysmaker')->_('结算类型 1:线上付款到微信钱包结算 2:线下结算'),
		),

        'settlement_status' => array(
            'type' => 'string',
            'default' => '1',
            'comment' => app::get('sysmaker')->_('结算状态 1:未结算 2:结算成功 3结算失败'),
        ),

        'bank_name' => array(
            'type' => 'string',
            'length' => 30,
            'label' => app::get('sysmaker')->_('银行名称'),
            //'required' => true,
            'order' => 30,
            'in_list' => true,
            'default_in_list'=>true,
        ),
		'card_number' => array(
            'type' => 'string',
            'length' => 50,
            'label' => app::get('sysmaker')->_('银行卡号'),
            'order' => 40,
        ),

		'type'=>array(
            'type' => array(
                'sys'=>'后台提现',
				'apply'=>'自己申请',
            ),
            'label' => app::get('sysmaker')->_('类型'),
            'comment' => app::get('sysmaker')->_('类型'),
            'order' => 70,
		),

        'remark'=>array(
            'type' => 'string',
            'label' => app::get('sysmaker')->_('备注'),
            'comment' => app::get('sysmaker')->_('备注'),
            'order' => 70,
        ),

        'openid' => array(
            'type' => 'string',
            'required' => false,
            'order' => 20,
            'label' => app::get('sysmaker')->_('openid'),
            'comment'=>app::get('sysmaker')->_('对应信任登陆方的用户唯一标识'),
        ),

        'create_time' => array(
            'type' => 'time',
            'label' => app::get('sysclearing')->_('申请时间'),
            'comment' => app::get('sysclearing')->_('申请时间'),
            'in_list' => true,
            'default_in_list'=>true,
        ),

        'pay_time' => array(
            'type' => 'time',
            'comment' => app::get('sysmaker')->_('支付时间'),
        ),

        'check_time' => array(
            'type' => 'time',
            'comment' => app::get('sysmaker')->_('审核时间'),
        ),

        'modified_time' => array(
            'type' => 'last_modify',
            'label' => app::get('sysmaker')->_('最后修改时间'),
        ),
    ),
    'primary' => 'id',
    'comment' => app::get('sysmaker')->_('主持人提现表'),
);