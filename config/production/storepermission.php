<?php
 /**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/8
 * Time: 09:50
 * Desc: 商城中心路由权限定义，权限对应到路由的别名
 */
return array(

    'common' => [
        'permission' =>[  //公共权限的路由
			'topstore.signin',
			'topstore.simpleSignin',
			'topstore.postsignin',
			'topstore.nopermission',
			'topstore.postnosignin',
        ],
        'nologin' => [	//不需要登录的路由
			'topstore.signin',
			'topstore.simpleSignin',
			'topstore.postsignin',
			'topstore.nopermission',
			'topstore.postnosignin',
        ]
    ],
    
);

