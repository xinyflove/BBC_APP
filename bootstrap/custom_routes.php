<?php

#追加的路由

/*
|--------------------------------------------------------------------------
| 商城路由
|--------------------------------------------------------------------------
*/
route::group(array('prefix' => 'store','middleware' => 'topstore_middleware_permission'), function() {
    # 首页
    route::get('/', [ 'as' => 'topstore.home', 'uses' => 'topstore_ctl_index@index' ]);
	//没有权限提示页面
	route::get('nopermission.html', [ 'as' => 'topstore.nopermission', 'uses' => 'topstore_ctl_index@nopermission' ]);

    # 登录
	route::get('passport/signin-s.html', [ 'as' => 'topstore.simpleSignin', 'uses' => 'topstore_ctl_passport@simpleSignin' ]);
    route::get('passport/signin.html', [ 'as' => 'topstore.signin', 'uses' => 'topstore_ctl_passport@signin', 'middleware' => 'topstore_middleware_redirectIfAuthenticated' ]);
    route::post('passport/postsignin.html', [ 'as' => 'topstore.postsignin', 'uses' => 'topstore_ctl_passport@login' ]);
	//免密码登录
    route::post('passport/postnosignin.html', [ 'as' => 'topstore.postnosignin', 'uses' => 'topstore_ctl_passport@noLogin' ]);

	# 商城修改密码
    route::get('passport/update.html', [ 'as' => 'topstore.update', 'uses' => 'topstore_ctl_passport@update' ]);
	route::post('passport/update-wd.html', [ 'as' => 'topstore.postupdatepwd', 'uses' => 'topstore_ctl_passport@updatepwd' ]);

    # 选择商品组件
	# 针对蓝鲸商城,  要选择多个店铺的商品
    route::get('store-select-goods.html', [ 'as' => 'topshop.goods.select', 'uses' => 'topstore_ctl_selector_item@loadSelectGoodsModal' ]);
    route::post('store-format-selected-goods.html', [ 'as' => 'topshop.goods.selected.format', 'uses' => 'topstore_ctl_selector_item@formatSelectedGoodsRow' ]);
    route::post('store-select-brandList.html', [ 'as' => 'topshop.goods.brandList', 'uses' => 'topstore_ctl_selector_item@getBrandList' ]);
    route::post('store-select-getItem.html', [ 'as' => 'topshop.goods.getItem', 'uses' => 'topstore_ctl_selector_item@searchItem' ]);
    route::get('store-select-item.getsku.html', [ 'as' => 'topshop.item.goods.getsku', 'uses' => 'topstore_ctl_selector_item@getSkuByItemId' ]);
    route::get('store-select-showsku.html', [ 'as' => 'topshop.goods.showsku', 'uses' => 'topstore_ctl_selector_item@showSkuByitemId' ]);

	# 退出
    route::get('passport/logout.html', [ 'as' => 'topstore.logout', 'uses' => 'topstore_ctl_passport@logout' ]);

    $menus = config::get('store');
    foreach($menus as $subMenus)
    {
        foreach($subMenus['menu'] as $menu)
        {
            $parameters = array($menu['url'], ['as' => $menu['as'], 'uses' => $menu['action'], 'middleware'=>$menu['middleware']]);
            if(array_key_exists('method', $menu))
            {
                $method = $menu['method'];

                if(is_array($menu['method']))
                {
                    $method = 'match';
                    $parameters = array(['GET','POST'],$menu['url'], ['as' => $menu['as'], 'uses' => $menu['action'], 'middleware'=>$menu['middleware']]);
                }
            }
            forward_static_call_array(array('route', $method), $parameters);
        }
    }
});

