<?php

/**
 * Tvplaza
 * 话单表
 * 存储话单信息等
 * @user:小超
 * @email:1013631519@qq.com
 */

return array(
    'columns' => array(
        'billId' => array(
            'type' => 'number',
            'required' => true,
            'autoincrement' => true,
            'in_list' => false,
            'label' => app::get('syscart')->_('租赁主表id'),
            'comment' => app::get('syscart')->_('租赁主表id'),
        ),

        'type' => array(
            'type' =>'number',
            'default' => 0,
            'required' => true,
            'order' => 12,
            'label' => app::get('syscart')->_(''),
            'comment' => app::get('syscart')->_('期数'),
        ),

        'subAccountSid' => array(
            'type' => 'string',
            'length' => 255,
            'required' => true,
            'default' => '',
            'order' => 5,
            'label' => app::get('syscart')->_('子账号 ID'),
            'comment' => app::get('syscart')->_('子账号 ID。本次通话企业属于子账号专用企 业时才标识。'),
        ),

        'switchNumber' => array(
            'type' => 'string',
            'lenght' => '100',
            'required' => false,
            'default_in_list' => true,
            'in_list' => true,
            'order' => 6,
            'label' => app::get('syscart')->_('企业总机号码'),
            'comment' => app::get('syscart')->_('企业总机号码'),
        ),

        'callId' => array(
            'type' => 'string',
            'lenght' => '100',
            'required' => false,
            'default_in_list' => true,
            'in_list' => true,
            'order' => 6,
            'label' => app::get('syscart')->_('呼叫id'),
            'comment' => app::get('syscart')->_('呼叫id'),
        ),
	
        'caller' => array(
            'type' => 'string',
            'lenght' => '20',
            'required' => false,
            'default_in_list' => true,
            'in_list' => true,
            'order' => 6,
            'label' => app::get('syscart')->_('主叫'),
            'comment' => app::get('syscart')->_('主叫'),
        ),

        'called' => array(
            'type' => 'string',
            'lenght' => '20',
            'required' => false,
            'default_in_list' => true,
            'in_list' => true,
            'order' => 6,
            'label' => app::get('syscart')->_('被叫'),
            'comment' => app::get('syscart')->_('被叫'),
        ),

        'createTime' => array(
            'type' => 'string',
            'lenght' => '12',
            'required' => false,
            'default_in_list' => true,
            'in_list' => true,
            'order' => 6,
            'label' => app::get('syscart')->_('话单创建时间'),
            'comment' => app::get('syscart')->_('话单创建时间'),
        ),

        'establishTime' => array(
            'type' => 'string',
            'lenght' => '20',
            'required' => false,
            'default_in_list' => true,
            'in_list' => true,
            'order' => 6,
            'label' => app::get('syscart')->_('通话建立时间'),
            'comment' => app::get('syscart')->_('通话建立时间，格式：yyyymmddHHMMSS， 通话失败时，该时间为话单创立时间'),
        ),

        'hangupTime' => array(
            'type' => 'string',
            'lenght' => '20',
            'required' => false,
            'default_in_list' => true,
            'in_list' => true,
            'order' => 6,
            'label' => app::get('syscart')->_(' 通话挂断时间'),
            'comment' => app::get('syscart')->_(' 通话挂断时间，格式：yyyymmddHHMMSS， 通话失败或者尚未完成时，返回字符串“null'),
        ),

		
        'status' => array(
            'type' =>'number',
            'default' => 0,
            'required' => true,
            'order' => 12,
            'label' => app::get('syscart')->_('通话状态'),
            'comment' => app::get('syscart')->_('通话状态：0-呼叫中；1-未接通；2-正在通话； 3-已挂断；100-异常话单 说明： 1) 对双向回拨和多方通话，需要首先接通 主叫，主叫接通后才呼叫被叫，此时主 叫的通话已经开始计费；如果所有被叫 都未接通，则话单总体状态为“未接通”； 2) 对双向回拨和多方通话，需要首先接通 主叫，主叫接通后才呼叫被叫，此时主 叫的通话已经开始计费；如果所有被叫 都未接通，则话单总体状态为“未接通”； 3) 异常话单（100）分为两种情况：一种是 通话请求未成功；二是从云总机服务平 台获得的话单不完整。'),
        ),

        'duration' => array(
            'type' =>'number',
            'default' => 0,
            'required' => true,
            'order' => 12,
            'label' => app::get('syscart')->_('通话持续时长(秒)'),
            'comment' => app::get('syscart')->_('通话持续时长(秒)'),
        ),

        'SubDetails' =>array (
            'type' => 'serialize',
            'editable' => false,
            'comment' => app::get('sysdecorate')->_('子话单详情，请参考以下说明'),
        ),

        'create_time' => array(
            'type' => 'time',
            'default' => 0,
            'required' => false,
            'in_list' => true,
            'default_in_list' => true,
            'filterdefault' => true,
            'order' => 7,
            'label' => app::get('syscart')->_('创建时间'),
            'comment' => app::get('syscart')->_('创建时间'),
        ),

    ),
    'primary' => 'billId',
    //'index' => array(
     //   'ind_created_time' => ['columns' => ['created_time']],
    //),
    'comment' => app::get('syslmgw')->_('话单表'),
);
