<?php
/**
 * Auth: 王海超
 * Time: 2018/11/20
 * Desc: 
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

        'ip'=>array(
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

    ),
    'primary' => 'id',
    'comment' => app::get('sysmaker')->_('主持人店铺访问表'),
);