<?php
return array(
    'columns'=>array(
        'log_id' => array (
            'type' => 'number',
            'autoincrement' => true,
            'order' => 0,
            'label' => app::get('syslmgw')->_('日志id'),
            'comment' => app::get('syslmgw')->_('日志id'),
        ),
        'api_url' => array(
            'type' => 'string',
            'length' => '500',
            'order'=> 1,
            'default' => '',
            'label' => app::get('syslmgw')->_('调用链接'),
            'comment' => app::get('syslmgw')->_('调用链接'),
        ),
        'call_time' => array(
            'type'=> 'time',
            'order'=> 2,
            'label' => app::get('syslmgw')->_('创建时间'),
            'comment' => app::get('syslmgw')->_('创建时间'),
        ),
        'run_time' => array(
            'type'=> 'string',
            'order'=> 3,
            'default' => '',
            'label' => app::get('syslmgw')->_('运行时间(秒)'),
            'comment' => app::get('syslmgw')->_('运行时间(秒)'),
        ),
        'resp_code'=>array(
            'type'=> 'string',
            'length' => '10',
            'order'=> 4,
            'label' => app::get('syslmgw')->_('状态码'),
            'comment' => app::get('syslmgw')->_('状态码'),
        ),
        'params' => array(
            'type' => 'serialize',
            'label' => app::get('syslmgw')->_('参数'),
            'comment' => app::get('syslmgw')->_('参数'),
        ),
        'result' => array(
            'type' => 'serialize',
            'label' => app::get('syslmgw')->_('返回数据'),
            'comment' => app::get('syslmgw')->_('返回数据'),
        ),
        'last_modify' => array(
            'type' => 'last_modify',
            'label' => app::get('syslmgw')->_('更新时间'),
            'comment' => app::get('syslmgw')->_('更新时间'),
        ),
    ),
    'primary' => 'log_id',
    'comment' => app::get('syslmgw')->_('易米API调用日志'),
);