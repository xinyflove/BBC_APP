<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-14
 * Desc: 检测用户是否登录注册成功，在路由中调用此中间件
 */
class topmaker_middleware_redirectIfAuthenticated {

    public function __construct()
    {

    }

    public function handle($request, Closure $next)
    {
        //判断用户是否登录注册成功
        pamAccount::setAuthType('sysmaker');
        
        if(pamAccount::check())
        {
            // 如果已经登陆，跳转到首页
            return redirect::route('topmaker.home');
        }

        return $next($request);
    }

}