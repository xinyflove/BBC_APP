<?php
return array(
    'columns'=>array(
        'id' => array (
            'type' => 'number',
            'autoincrement' => true,
            'order' => 0,
            'label' => app::get('syslmgw')->_('日志id'),
            'comment' => app::get('syslmgw')->_('日志id'),
        ),
        'type' => array(
            'type' => array(
                'callin' => app::get('syslmgw')->_('呼入'),
                'queue' => app::get('syslmgw')->_('排队'),
            ),
            'default' => 'callin',
            'label' => app::get('syslmgw')->_('通话类型'),
            'comment' => app::get('syslmgw')->_('通话类型'),
        ),
        'subAccountSid' => array(
            'type' => 'string',
            'length' => 50,
            'default' => '',
            'order' => 2,
            'label' => app::get('syslmgw')->_('子账号 ID'),
            'comment' => app::get('syslmgw')->_('子账号 ID。本次通话企业属于子账号专用企 业时才标识。'),
        ),
        'max_num' => array(
            'type' => 'number',
            'order'=> 1,
            'default' => '0',
            'label' => app::get('syslmgw')->_('当日最高并发数'),
            'comment' => app::get('syslmgw')->_('当日最高并发数'),
        ),
        'curr_num' => array(
            'type' => 'number',
            'order'=> 2,
            'default' => '0',
            'label' => app::get('syslmgw')->_('当日当前最高并发数'),
            'comment' => app::get('syslmgw')->_('当日当前最高并发数'),
        ),
        'date' => array(
            'type'=> 'string',
            'length' => '8',
            'order'=> 3,
            'label' => app::get('syslmgw')->_('日期'),
            'comment' => app::get('syslmgw')->_('日期'),
        ),
        'stat_time' => array(
            'type' => 'time',
            'order'=> 4,
            'label' => app::get('syslmgw')->_('统计开始时间'),
            'comment' => app::get('syslmgw')->_('统计开始时间'),
        ),
        'last_modify' => array(
            'type' => 'last_modify',
            'order'=> 5,
            'label' => app::get('syslmgw')->_('更新时间'),
            'comment' => app::get('syslmgw')->_('更新时间'),
        ),
    ),
    'primary' => 'id',
    'comment' => app::get('syslmgw')->_('并发数统计表'),
);