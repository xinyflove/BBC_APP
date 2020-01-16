<?php
 /**
 * Auth: xinyufeng
 * Time: 2018-11-14
 * Desc: 创客路由权限定义，权限对应到路由的别名
 */
return array(

    'common' => [
        'nologin' => [	//不需要登录的路由
			'topmaker.signin',	// 登录页面
			'topmaker.signup',	// 注册页面
			'topmaker.postsignin',	// 登录验证
			'topmaker.postsignup',	// 注册验证
			'topmaker.send.vcode',	// 发送手机验证码
			'topmaker.trustlogin.callbacksignin',	// 登录页面-微信回调函数
			'topmaker.trustlogin.callbacksignup',	// 注册页面-微信回调函数
			'topmaker.getgrouplistdata',	// 获取组织列表数据
        ]
    ],
    
);

