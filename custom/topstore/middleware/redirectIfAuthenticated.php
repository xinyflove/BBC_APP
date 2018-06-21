<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/4
 * Time: 15:18
 * Desc: 检测用户是否登录注册成功，在路由中调用此中间件
 */
class topstore_middleware_redirectIfAuthenticated {

    public function __construct()
    {

    }

    public function handle($request, Closure $next)
    {
        //判断用户是否登录注册成功
        pamAccount::setAuthType('sysstore');
        
        if(pamAccount::check()){

            return redirect::route('topstore.home');
        }

        return $next($request);
    }

}