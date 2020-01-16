<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topshop_middleware_permissionLanmei
{
    public function handle($request, Closure $next)
    {
        //获取shopInfo
        $sellerId = pamAccount::getAccountId();
        $shopId = app::get('topshop')->rpcCall('shop.get.loginId',array('seller_id'=>$sellerId),'seller');

        //获取当前用户的路由权限
        $tv_shop_id = app::get('sysshop')->getConf('sysshop.tvshopping.shop_id');

        if( $shopId != $tv_shop_id)
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

