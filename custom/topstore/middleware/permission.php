<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/4
 * Time: 11:24
 * Desc: 登录和操作权限
 */
class topstore_middleware_permission {
    
    public function handle($request, Closure $next)
    {
        $routeAs = route::currentRouteName();//当前访问的路由名称
        pamAccount::setAuthType('sysstore');//设置用户认证类型

        //如果没有登录 && 不是非登陆权限 开始
        if( !pamAccount::check() && !in_array($routeAs,config::get('storepermission.common.nologin')) )
        {
            if( input::get('dataonly') )
            {
                return response::json(array(
                    'error' => true,
                    'message' => app::get('topstore')->_('用户未登录！请重新登录'),
                    'redirect' => url::action('topstore_ctl_passport@signin'),
                ));
            }

            //如果是ajax提交
            if( request::ajax() )
            {
                //返回登录页面弹出框
                return redirect::action('topstore_ctl_passport@simpleSignin');
            }

            //跳转登录页面
            return redirect::action('topstore_ctl_passport@signin');
        }
        //如果没有登录 && 不是非登陆权限 结束

        //当前权限(公共权限+登录用户权限),返回值 false 表示为店主不用判断权限
        $currentPermission = storeAuth::getAccountPermission();

        //当前权限不为空 && 当前访问的路由不在权限中 开始
        if( $currentPermission && !in_array($routeAs, $currentPermission) )
        {
            //如果是ajax提交
            if( request::ajax() )
            {
                return response::json(array(
                    'error' => true,
                    'message'=> '无操作权限',
                ));
            }
            
            return redirect::action('topstore_ctl_index@nopermission');
        }
        //当前权限不为空 && 当前访问的路由不在权限中 结束

        return $next($request);
    }
}