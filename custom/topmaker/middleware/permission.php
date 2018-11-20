<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-14
 * Desc: 登录和操作权限中间件
 */
class topmaker_middleware_permission {
    
    public function handle($request, Closure $next)
    {
        $routeAs = route::currentRouteName();//当前访问的路由名称
        pamAccount::setAuthType('sysmaker');//设置用户认证类型

        //如果没有登录 && 不是非登陆权限 开始
        if( !pamAccount::check() && !in_array($routeAs,config::get('makerpermission.common.nologin')) )
        {
            //如果是ajax提交
            if( request::ajax() )
            {
                return response::json(array(
                    'error' => true,
                    'message' => app::get('topmaker')->_('用户未登录！请重新登录'),
                    'redirect' => url::action('topmaker_ctl_passport@signin'),
                ));
            }

            //跳转登录页面
            return redirect::action('topmaker_ctl_passport@signin');
        }
        //如果没有登录 && 不是非登陆权限 结束
        
        return $next($request);
    }
}