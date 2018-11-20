<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2018/7/31
 * Time: 10:45
 */
class topwap_middleware_miniAuth
{
    public function handle($request, Closure $next)
    {
        $session_id = input::get('session_id',false);
        $config = config::get('session');
        $sess_key = $config['cookie'] ? $config['cookie']:'s';
        $_COOKIE[$sess_key] = input::get('session_id',false);
        $session_obj = kernel::single('base_session');
        $session_obj->start();
        if(!$session_id){
            return response::json([
                'err_no'=>1003,
                'data'=>[

                ],
                'message'=>'session_id必传，请重新登录'
            ]);
        }
        $session_data = cache::store('session')->get('USER_SESSION:'.$session_id);
        if(!$session_data)
        {
            return response::json([
                'err_no'=>1003,
                'data'=>[

                ],
                'message'=>'session_id失效，请重新登录'
            ]);
        }
        $session_obj->set_sess_expires(60*6);
        return $next($request);
    }
}