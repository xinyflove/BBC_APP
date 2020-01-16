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
        /*add_2019/8/7_by_wanghaichao_start*/
        //加入登录判断
		$type=input::get('type');
        /*add_2019/8/7_by_wanghaichao_end*/
        
        if(pamAccount::check())
        {
            // 如果已经登陆，跳转到首页
            if(isset($type) && $type=='ticket'){
				return redirect::route('topmaker.ticket.home');
			}else{
				return redirect::route('topmaker.home');
			}
        }
        return $next($request);
    }

}