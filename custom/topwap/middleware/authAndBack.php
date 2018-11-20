<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topwap_middleware_authAndBack
{
    public function handle($request, Closure $next)
    {
        if( !userAuth::check() )
        {
            $next_page = url::to(request::server('REQUEST_URI'));
            if( request::ajax() )
            {
                $url = url::action('topwap_ctl_passport@goLogin', ['next_page' => $next_page]);
                $data['error'] = true;
                $data['redirect'] = $url;
                $data['message'] = app::get('topwap')->_('请登录');
                return response::json($data);exit;
            }
            redirect::action('topwap_ctl_passport@goLogin', ['next_page' => $next_page])->send();exit;
        }
        return $next($request);
    }
}
