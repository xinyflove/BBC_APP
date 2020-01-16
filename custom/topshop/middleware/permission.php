<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topshop_middleware_permission
{
    public function handle($request, Closure $next)
    {
        $routeAs = route::currentRouteName();
        //如果没有登录
        pamAccount::setAuthType('sysshop');
        if( !pamAccount::check() && !in_array($routeAs,config::get('permission.common.nologin')) )
        {
            if( input::get('dataonly') )
            {
                return response::json(array(
                    'error' => true,
                    'message' => app::get('topshop')->_('用户未登录！请重新登录'),
                    'redirect' => url::action('topshop_ctl_passport@signin'),
                ));

            }

            if( request::ajax() )
            {
                return redirect::action('topshop_ctl_passport@simpleSignin');
            }

            return redirect::action('topshop_ctl_passport@signin');
        }

        /**
         * 如果是惠民供应商模式登陆，则只能访问允许访问的路由
         */
        if(pamAccount::check() && $_SESSION['huimin_supplier_id']) {
            $hm_permission = config::get('hmpermission');
            $hm_supplier_permission = $hm_permission['supplier'];
            $allow_main_menu = [];
            $allow_menu_route = [];
            foreach($hm_supplier_permission as $key=>$hsp) {
                array_push($allow_main_menu, $key);
                $allow_menu_route = array_merge($allow_menu_route, $hsp['menu']);
            }

            if(!in_array($routeAs, $allow_menu_route)) {
                if( request::ajax() )
                {
                    return response::json(array(
                        'error' => true,
                        'message'=> '无操作权限',
                    ));
                }
                return redirect::action('topshop_ctl_index@nopermission');
            }
        }

        $currentPermission = shopAuth::getSellerPermission();
        //$currentPermission = false 表示为店主不用判断权限

        //获取当前用户的路由权限
        if( $currentPermission && !in_array($routeAs, $currentPermission) )
        {
            if( request::ajax() )
            {
                return response::json(array(
                    'error' => true,
                    'message'=> '无操作权限',
                ));
            }

            return redirect::action('topshop_ctl_index@nopermission');
        }

        return $next($request);
    }

}

