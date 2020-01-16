<?php

/**
 * Tvplaza
 * 话单表
 * 存储话单信息等
 * @user:小超
 * @email:1013631519@qq.com
 * @desc:此表参照易米系统下载应用话单接口
 */

return array(
    'columns' => array(
        'billId' => array(
            'type' => 'number',
            'default' => 0,
            'required' => true,
            'order' => 0,
            'label' => app::get('syslmgw')->_('话单 Id'),
            'comment' => app::get('syslmgw')->_('话单 Id'),
        ),
        'type' => array(
            'type' =>'number',
            'default' => 0,
            'required' => true,
            'order' => 1,
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
        'switchNumber' => array(
            'type' => 'string',
            'length' => 50,
            'default' => '',
            'order' => 3,
            'required' => true,
            'label' => app::get('syslmgw')->_('企业总机号码'),
            'comment' => app::get('syslmgw')->_('企业总机号码'),
        ),
        'callId' => array(
            'type' => 'string',
            'length' => 50,
            'default' => '',
            'order' => 4,
            'required' => true,
            'label' => app::get('syslmgw')->_('呼叫id'),
            'comment' => app::get('syslmgw')->_('呼叫id'),
        ),
        'caller' => array(
            'type' => 'string',
            'length' => 50,
            'default' => '',
            'order' => 5,
            'required' => true,
            'label' => app::get('syslmgw')->_('主叫'),
            'comment' => app::get('syslmgw')->_('主叫'),
        ),
        'called' => array(
            'type' => 'string',
            'length' => 50,
            'default' => '',
            'order' => 6,
            'required' => true,
            'label' => app::get('syslmgw')->_('被叫'),
            'comment' => app::get('syslmgw')->_('被叫'),
        ),
		/*add__by_wanghaichao_start*/
		'callerMobile' => array(
            'type' => 'string',
            'length' => 50,
            'default' => '0',
            'order' => 5,
            'required' => false,
            'label' => app::get('syslmgw')->_('主叫分机所绑定的号码'),
            'comment' => app::get('syslmgw')->_('主叫分机所绑定的号码'),
        ),
        'calledMobile' => array(
            'type' => 'string',
            'length' => 50,
            'default' => '0',
            'order' => 6,
            'required' => false,
            'label' => app::get('syslmgw')->_('被叫分机所绑定的号码'),
            'comment' => app::get('syslmgw')->_('被叫分机所绑定的号码'),
        ),
		/*add__by_wanghaichao_end*/
		
        'userData' => array(
            'type' => 'string',
            'length' => 255,
            'default' => '',
            'order' => 7,
            'label' => app::get('syslmgw')->_('用户自定义数据'),
            'comment' => app::get('syslmgw')->_('用户自定义数据（子账户绑定企业或调用呼
叫接口时携带）'),
        ),
        'createTime' => array(
            'type' => 'string',
            'length' => 14,
            'default' => '',
            'order' => 8,
            'required' => true,
            'label' => app::get('syslmgw')->_('话单创建时间'),
            'comment' => app::get('syslmgw')->_('话单创建时间'),
        ),
        'establishTime' => array(
            'type' => 'string',
            'length' => 14,
            'default' => '',
            'order' => 9,
            'required' => true,
            'label' => app::get('syslmgw')->_('通话建立时间'),
            'comment' => app::get('syslmgw')->_('通话建立时间，格式：yyyymmddHHMMSS， 通话失败时，该时间为话单创立时间'),
        ),
        'hangupTime' => array(
            'type' => 'string',
            'length' => 14,
            'default' => '',
            'order' => 10,
            'required' => true,
            'label' => app::get('syslmgw')->_('通话挂断时间'),
            'comment' => app::get('syslmgw')->_('通话挂断时间，格式：yyyymmddHHMMSS， 通话失败或者尚未完成时，返回字符串“null'),
        ),
        'status' => array(
            'type' =>'number',
            'default' => 0,
            'required' => true,
            'order' => 11,
            'label' => app::get('syslmgw')->_('通话状态'),
            'comment' => app::get('syslmgw')->_('通话状态：0-呼叫中；1-未接通；2-正在通话； 3-已挂断；100-异常话单 说明： 1) 对双向回拨和多方通话，需要首先接通 主叫，主叫接通后才呼叫被叫，此时主 叫的通话已经开始计费；如果所有被叫 都未接通，则话单总体状态为“未接通”； 2) 对双向回拨和多方通话，需要首先接通 主叫，主叫接通后才呼叫被叫，此时主 叫的通话已经开始计费；如果所有被叫 都未接通，则话单总体状态为“未接通”； 3) 异常话单（100）分为两种情况：一种是 通话请求未成功；二是从云总机服务平 台获得的话单不完整。'),
        ),
        'duration' => array(
            'type' =>'number',
            'default' => 0,
            'required' => true,
            'order' => 12,
            'label' => app::get('syslmgw')->_('通话持续时长(秒)'),
            'comment' => app::get('syslmgw')->_('通话持续时长(秒)'),
        ),
        'subDetails' =>array (
            'type' => 'serialize',
            'default' =>'',
            'order' => 13,
            'comment' => app::get('syslmgw')->_('子话单详情'),
        ),
        'subNumber' => array(
            'type' =>'number',
            'default' => 0,
            'order' => 14,
            'label' => app::get('syslmgw')->_('用户呼入时使用的分机号'),
            'comment' => app::get('syslmgw')->_('用户呼入时使用的分机号'),
        ),
        'useNumber' => array(
            'type' =>'number',
            'default' => 0,
            'order' => 15,
            'label' => app::get('syslmgw')->_('用户呼入时使用的固话号码'),
            'comment' => app::get('syslmgw')->_('用户呼入时使用的固话号码'),
        ),
        'adtel' => array(
            'type' =>'number',
            'default' => 0,
            'order' => 16,
            'label' => app::get('syslmgw')->_('用户呼入时使用的固话号码'),
            'comment' => app::get('syslmgw')->_('用户呼入时使用的固话号码'),
        ),
        'create_time' => array(
            'type' => 'time',
            'default' => 0,
            'required' => false,
            'in_list' => true,
            'default_in_list' => true,
            'filterdefault' => true,
            'order' => 7,
            'label' => app::get('syslmgw')->_('创建时间'),
            'comment' => app::get('syslmgw')->_('创建时间'),
        ),
    ),
    'primary' => 'billId',
    'comment' => app::get('syslmgw')->_('话单表'),
);
