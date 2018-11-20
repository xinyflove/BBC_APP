<?php /**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */


/*
|--------------------------------------------------------------------------
| 店铺子域名路由
|--------------------------------------------------------------------------
*/
try{
    if(config::get('app.subdomain_enabled')){
        $domain_prefix = ['domain' => '{subdomain:(?!www)[\w\d-]+}.'.config::get('app.subdomain_basic')];
    }else{
        $domain_prefix = ['prefix' => 'shopcenter'];
    }
}catch (Exception $e){
    $domain_prefix = ['prefix' => 'shopcenter'];
}
route::group($domain_prefix, function() {
    # 店铺首页
    route::get('', [ 'as'=>'topc.shopcenter', 'uses' => 'topc_ctl_shopcenter@index' ]);
    # 店铺搜索
    route::post('search.html', [ 'uses' => 'topc_ctl_shopcenter@search' ]);
    route::get('search.html', [ 'uses' => 'topc_ctl_shopcenter@search' ]);
});

//店铺申请证书，回调验证地址
// TODO 不支持闭包缓存
//route::match(array('GET','POST'),'/shophandshake',function(){
//    if($value = redis::scene('system')->get('net.handshake')){
//        echo $value;
//    }else{
//        $code = md5(microtime());
//        redis::scene('system')->set('net.handshake',$code);
//        echo $code;
//    }
//});

//客服工作台-历史订单页面
route::group(array(),function(){
    route::get('im-history.html',['as'=>'topshop.im.history','uses'=>'topc_ctl_im@imOrderHistory']);
    route::post('im-post-history.html',['as'=>'topshop.im.post.history','uses'=>'topc_ctl_im@imOrderHistory']);
    route::get('im-post-history.html',['as'=>'topshop.im.post.history','uses'=>'topc_ctl_im@imOrderHistory']);
    route::match(['GET','POST'],'call_center_order_history.html', [ 'as' => 'topshop.call.center.order.history', 'uses' => 'topc_ctl_callCenter@callCenterOrderHistory' ]);
});

/*
|--------------------------------------------------------------------------
| api
|--------------------------------------------------------------------------
 */
route::group(array('prefix' => 'api'), function()
{
    route::match(array('GET','POST'),'/api.json',['as'=>'api/api.json','uses'=>'system_ctl_getApiJson@index']);
    route::match(array('GET','POST'),'/', ['uses'=>'base_rpc_server@process', 'middleware' => ['system_middleware_checkApiSystemParams']]);
});

route::group(array('prefix' => 'topapi'), function()
{
    route::match(array('GET','POST'),'/', ['uses'=>'topapi_server@process']);
});

route::group(array('prefix' => 'shop/topapi'), function()
{
    route::match(array('GET','POST'),'/', ['uses'=>'topmanageapi_server@process']);
});

route::group(array('prefix' => 'shop/api'), function() {
    route::match(array('GET','POST'),'/', ['uses'=>'topshopapi_server@process']);
});

route::group(array('prefix' => 'matrix/'), function() {
    route::match(array('GET','POST'),'api', ['uses'=>'sysopen_shopex_server@process']);
});

// start add 王衍生 20170928
route::group(array('prefix' => 'supplier/topapi'), function()
{
    route::match(array('GET','POST'),'/', ['uses'=>'topsupplierapi_server@process']);
});
// end add 王衍生 20170928
/*
|--------------------------------------------------------------------------
| api
|--------------------------------------------------------------------------
 */
route::group(array('prefix' => 'license/'), function() {
    route::match(array('GET','POST'),'callback', ['uses'=>'base_license_active@callBack']);
//  route::match(array('GET','POST'),'checkhardware', ['uses'=>'base_machine_hook@getEncodeCode']);
});


/*
route::get('/kkk/{id}', ['as' => 'iiii', function($id) {
        echo $id;
    }]);
*/
/*
|--------------------------------------------------------------------------
| PC端消费者平台
|--------------------------------------------------------------------------
*/

route::group(array('middleware' => ['theme_middleware_preview', 'base_middleware_machine_check', 'base_middleware_machine_hook']), function() {
    /*
    |--------------------------------------------------------------------------
    | 会员登录注册相关
    |--------------------------------------------------------------------------
    */
    route::group(array(), function() {
        # 登陆页
        route::get('passport-signin.html', [ 'middleware' => 'topc_middleware_redirectIfAuthenticated','as' => 'what',
                                             'uses' => 'topc_ctl_passport@signin' ]);
        # 登陆
        route::post('passport-signin.html', [
                                                'middleware' => ['topc_middleware_redirectIfAuthenticated'],
                                              'uses' => 'topc_ctl_passport@login' ]);
        # 注册页
        route::get('passport-signup.html', ['middleware' => 'topc_middleware_redirectIfAuthenticated',
                                            'uses' => 'topc_ctl_passport@signup' ]);
        # 注册成功页
        route::get('passport-signup-success.html', ['middleware' => 'topc_middleware_authenticate',
                                                    'uses' => 'topc_ctl_passport@signupSuccess']);
        # 注册
        route::post('passport-signup.html', ['uses' => 'topc_ctl_passport@create' ]);
        route::post('passport-sendVcode.html', [ 'uses' => 'topc_ctl_passport@sendVcode' ]);
        # 注册验证
        route::post('passport-signupcheck.html', [ 'uses' => 'topc_ctl_passport@checkLoginAccount' ]);
        # 找回密码1
        route::get('passport-findpwd.html', [ 'uses' => 'topc_ctl_passport@findPwd' ]);
        # 找回密码2
        route::post('passport-findpwdtwo.html', [ 'uses' => 'topc_ctl_passport@findPwdTwo' ]);
        route::get('passport-findpwdtwo.html', [ 'uses' => 'topc_ctl_passport@findPwdTwo' ]);
        # 找回密码3
        route::match(array('GET', 'POST'), 'passport-findpwdthree.html', ['uses' => 'topc_ctl_passport@findPwdThree']);
        # #找回密码短信验证码发送
        route::post('passport-findpwdfour.html', [ 'uses' => 'topc_ctl_passport@findPwdFour' ]);
        # 找回密码短信验证码发送
        route::post('passport-sendVcode.html', [ 'uses' => 'topc_ctl_passport@sendVcode' ]);
        # 信任登陆
        # callback
        /*
        route::get('trustlogin-bind.html', [ 'uses' => 'topc_ctl_trustlogin@callBack' ]);
        # 设置新的账号
        route::post('trustlogin.html', [ 'uses' => 'topc_ctl_trustlogin@setLogin' ]);
        # 绑定已有账户
        route::post('trustlogin-binds.html', [ 'uses' => 'topc_ctl_trustlogin@checkLogin' ]);
        # 模拟登陆
        route::get('trustlogin-postlogin.html', [ 'uses' => 'topc_ctl_trustlogin@postLogin' ]);
        */
        route::get('trustlogin-bind.html', [ 'middleware' => 'topc_middleware_redirectIfAuthenticated',
                                             'uses' => 'topc_ctl_trustlogin@callBack' ]);
        route::post('bindDefaultCreateUser.html', [ 'middleware' => 'topc_middleware_redirectIfAuthenticated',
                                                    'uses' => 'topc_ctl_trustlogin@bindDefaultCreateUser' ]);
        route::post('bindExistsUser.html', [  'middleware' => 'topc_middleware_redirectIfAuthenticated',
                                              'uses' => 'topc_ctl_trustlogin@bindExistsUser' ]);
        route::post('bindSignupUser.html', [ 'middleware' => 'topc_middleware_redirectIfAuthenticated',
                                             'uses' => 'topc_ctl_trustlogin@bindSignupUser' ]);

        # 退出
        route::get('passport-logout.html', [ 'middleware' => 'topc_middleware_authenticate',
                                             'uses' => 'topc_ctl_passport@logout' ]);
    });

    /*
    |--------------------------------------------------------------------------
    | 文章相关
    |--------------------------------------------------------------------------
    */
    route::group(array(), function() {

        route::get('content-index.html', [ 'uses' => 'topc_ctl_content@index' ]);
        route::get('content-info.html', [ 'uses' => 'topc_ctl_content@getContentInfo', 'as' => 'topc.content.detail' ]);
    });

    /*
    |--------------------------------------------------------------------------
    | 商品首页详细页相关
    |--------------------------------------------------------------------------
    */

    route::group(array(), function() {
        # 首页
        route::get('/', [ 'as' => 'topc', 'uses' => 'topc_ctl_default@index']);
        //这里做了一个跳转口，用于登陆等需要跳转的地方，可以跳转post等方式
        route::get('/redirect.html', [ 'as' => 'topc.redirect', 'uses' => 'topc_ctl_default@redirect']);
        //----------
        # 商品收藏
        route::post('member_fav.html', [ 'middleware' => 'topc_middleware_authenticate',
                                         'uses' => 'topc_ctl_collect@ajaxFav' ]);
        route::post('member-collectdel.html', [ 'middleware' => 'topc_middleware_authenticate',
                                                'uses' => 'topc_ctl_collect@ajaxFavDel' ]);
        # 店铺收藏
        route::post('member_favshop.html', [ 'middleware' => 'topc_middleware_authenticate',
                                             'uses' => 'topc_ctl_collect@ajaxFavshop' ]);
        route::post('member-collectshopdel.html', [ 'middleware' => 'topc_middleware_authenticate',
                                                    'uses' => 'topc_ctl_collect@ajaxFavshopDel' ]);
        # 商品列表
        route::get('list.html', [ 'uses' => 'topc_ctl_list@index' ]);

        # 商品列表页加入购物车
        route::get('mini_spec.html', [ 'uses' => 'topc_ctl_item@miniSpec' ]);
        # 线下无偿券领取
        route::get('mini_rush.html', [ 'uses' => 'topc_ctl_item@miniRush' ]);
        # 商城一级类目页
        route::get('topics.html', [ 'as' => 'topc.topics', 'uses' => 'topc_ctl_topics@index' ]);
        # 品牌列表
        route::get('brand.html', [ 'uses' => 'topc_ctl_brand@index' ]);

        # 商品详情
        route::get('item.html', ['as' =>'topc.item.detail', 'uses' => 'topc_ctl_item@index' ]);
        // 获取商品关联的组合促销
        route::get('item-package.html', ['as' =>'topc.item.package', 'uses' => 'topc_ctl_item@getPackage' ]);
        // 异步获取商品的规格信息
        route::get('item-getSpecSku.html', ['as' =>'topc.item.getSpecSku', 'uses' => 'topc_ctl_item@getSpecSku' ]);
        # 根据组合促销id获取对应组合商品的规格信息
        route::get('item-packageItemSpec.html', ['as' =>'topc.item.packageItemSpec', 'uses' => 'topc_ctl_item@getPackageItemSpec' ]);
        #商品详情页，评价列表
        route::get('item-rate.html', [ 'uses' => 'topc_ctl_item@getItemRate' ]);
        route::get('item-rate-list.html', [ 'uses' => 'topc_ctl_item@getItemRateList' ]);

        //商品详情页存储浏览商品纪录
        route::get('item/browserHistory.html', [ 'uses' => 'topc_ctl_item@setBrowserHistory' ]);

        #商品详情页，促销
        route::get('promotion-item.html', [ 'uses' => 'topc_ctl_promotion@getPromotionItem' ]);
        // 促销专题页
        route::get('promotion-page/{page_id}.html', [ 'uses' => 'topc_ctl_promotion@ProjectPage' ]);
        route::get('lottery.html', ['uses' => 'topc_ctl_lottery@index' ]);
        route::post('ajax/lottery-prize.html',['middleware' => 'topc_middleware_authenticate', 'uses' => 'topc_ctl_lottery@getPrize' ]);
        route::post('lottery-exchangenum.html',['middleware' => 'topc_middleware_authenticate', 'uses' => 'topc_ctl_lottery@getExchangeNum' ]);
        route::post('lottery-infoDialog.html', ['middleware' => 'topc_middleware_authenticate', 'uses' => 'topc_ctl_lottery@lottery_info_dialog']); #收货地址弹框
        # 优惠券关联商品列表页
        route::get('promotion-coupon-item.html', [ 'uses' => 'topc_ctl_promotion@getCouponItem' ]);
        #商品详情页,到货通知
        route::post('user-item.html', [ 'uses' => 'topc_ctl_memberItem@userNotifyItem' ]);

        #商品详情页，咨询
        route::get('item-gack.html', [ 'uses' => 'topc_ctl_item@getItemConsultation' ]);
        route::get('item-gack-list.html', [ 'uses' => 'topc_ctl_item@getItemConsultationList' ]);
        #提交资讯信息
        route::post('item-gack-add.html', [ 'uses' => 'topc_ctl_item@commitConsultation' ]);
        #所有活动列表页
        route::get('activity/index.html', [ 'uses' => 'topc_ctl_activity@index' ]);
        route::get('activity/item_list.html', [ 'uses' => 'topc_ctl_activity@activity_item_list' ]);
        route::post('activity/remind.html', [ 'uses' => 'topc_ctl_activity@saleRemind' ]);
        route::get('activity/itemlist.html', [ 'uses' => 'topc_ctl_activity@itemlist' ]);
        route::get('activity/detail.html', [ 'uses' => 'topc_ctl_activity@detail' ]);
        route::post('activity/toremind.html',['uses' => 'topc_ctl_activity@toSaleRemind']);
        #微信扫码
        route::post('checkpayment.html', [ 'uses' => 'topc_ctl_paycenter@checkPayments' ]);
        route::match(array('GET', 'POST','PUT','DELETE'),'wxqrpay.html',[ 'uses' => 'topc_ctl_wechat@wxqrpay']);
        /*add_20171205_by_fanglongji_start*/
        //首页电视、广播购物
        route::get('mall.html', [ 'uses' => 'topc_ctl_mall@index' ]);
        route::match(['GET','POST'],'mall_goods.html', [ 'as' => 'mall.goods.list', 'uses' => 'topc_ctl_mall@detail' ]);
        route::match(['GET','POST'],'mall_goods_ajax.html', [ 'as' => 'mall.goods.ajax.list', 'uses' => 'topc_ctl_item_mallgoods@ajaxGetItemList' ]);

        //每日上新
        route::get('day_new.html', [ 'uses' => 'topc_ctl_daynew@index' ]);
        //购券专场
        route::get('buy_coupon.html', [ 'uses' => 'topc_ctl_buycoupon@index' ]);
        //热卖单品
        route::get('hot_goods.html', [ 'uses' => 'topc_ctl_hotgoods@index' ]);
        //余额专场
        route::get('banlance_goods.html', [ 'uses' => 'topc_ctl_banlancegoods@index' ]);
        //买遍全球
        route::get('all_global.html', [ 'uses' => 'topc_ctl_allglobal@index' ]);
        /*add_20171205_by_fanglongji_end*/
        /* add_start_gurundong_2017/12/7 */
        //首页挂件详情路由区
        route::get('widghts-live-hot-detail.html', 'topc_ctl_widghts_liveHot@detail');     #直播热售
        route::get('widghts-goods-time-detail.html', 'topc_ctl_widghts_goodsTime@detail');     #限时闪购
        /* add_end_gurundong_2017/12/7 */

        /*add_by_xinyufeng_2018-07-20_start*/
        // QTV直播页
        route::get('qtvlive/index.html', [ 'uses' => 'topc_ctl_qtvlive@index' ]);

        route::get('tv_schedule/ajaxgetdata.html', [ 'uses' => 'topc_ctl_qtvlive@tv_scheduleAjaxGetData' ]);
        /*add_by_xinyufeng_2018-07-20_end*/
    });

    /*
    |--------------------------------------------------------------------------
    | 店铺相关
    |--------------------------------------------------------------------------
    */
    route::group(array(), function() {
        # 店铺首页
        // route::get('shopcenter.html', [ 'as'=>'topc.shopcenter', 'uses' => 'topc_ctl_shopcenter@index' ]);
        # 店铺前台优惠券列表页
        route::get('shopCouponList.html', [ 'uses' => 'topc_ctl_shopcenter@shopCouponList' ]);
        # 领取优惠券
        route::post('getCoupon.html', [ 'middleware' => 'topc_middleware_authenticate',
                                        'uses' => 'topc_ctl_shopcenter@getCouponCode' ]);
        # 领取优惠券成功页
        route::get('getCouponResult.html', [ 'uses' => 'topc_ctl_shopcenter@getCouponResult' ]);
        // route::get('search.html', [ 'uses' => 'topc_ctl_shopcenter@search' ]);

        #搜索
        route::post('search.html', [ 'uses' => 'topc_ctl_search@index' ]);

        // article
        route::get('shop-article.html', [ 'uses' => 'topc_ctl_shopcenter@shopArticle' ]);

        #sitemap
        # 列表
        //route::get('sitemaps.html', [ 'uses' => 'site_ctl_sitemaps@catalog' ]);
        # 目录明细
        //route::get('sitemaps/{id}.html', [ 'uses' => 'site_ctl_sitemaps@index' ]);

    });

    /*
    |--------------------------------------------------------------------------
    | 会员中心
    |--------------------------------------------------------------------------
    */
    route::group(array('middleware' => 'topc_middleware_authenticate'), function() {
        # 会员中心首页
        route::get('member-index.html', [ 'as' => 'topc.member.index', 'uses' => 'topc_ctl_member@index' ]);
        route::post('member-tradelist.html', [ 'as' => 'topc.member.tradelist', 'uses' => 'topc_ctl_member@tradeStatusList' ]);
        # 会员中心个人资料
        route::get('member-seinfoset.html', [ 'uses' => 'topc_ctl_member@seInfoSet' ]);
        # 会员中心个人资料
        route::post('member-saveinfoset.html', [ 'uses' => 'topc_ctl_member@saveInfoSet' ]);
        # 会员中心信任登陆密码修改
        route::get('member-pwdset.html', [ 'uses' => 'topc_ctl_member@pwdSet' ]);
        # 会员中心信任登陆密码修改
        route::post('member-savepwdset.html', [ 'uses' => 'topc_ctl_member@savePwdSet' ]);
        # 会员中心安全中心
        route::get('member-security.html', [ 'uses' => 'topc_ctl_member@security' ]);
        # 会员中心安全中心密码修改
        route::get('member-modifypwd.html', [ 'uses' => 'topc_ctl_member@modifyPwd' ]);
        # 会员中心安全中心密码修改
        route::post('member-savemodifypwd.html', [ 'uses' => 'topc_ctl_member@saveModifyPwd' ]);
        # 会员中心手机/邮箱绑定
        route::get('member-setuserinfo.html', [ 'uses' => 'topc_ctl_member@verify' ]);
        # 会员中心登录检测
        route::post('member-checkUserLogin.html', [ 'uses' => 'topc_ctl_member@CheckSetInfo' ]);
        route::get('member-setinfoone.html', [ 'uses' => 'topc_ctl_member@setUserInfoOne' ]);# 会员中心手机
        # 会员中心短信验证码发送
        route::post('member-sendVcode.html', [ 'uses' => 'topc_ctl_member@sendVcode' ]);
        # 会员中心个人信息最后保存
        route::post('member-bindMobile.html', [ 'uses' => 'topc_ctl_member@bindMobile' ]);
        route::get('member-bindEmail.html', [ 'uses' => 'topc_ctl_member@bindEmail' ]);
        # 会员中心个人信息最后保存后的跳转
        route::get('member-setinfolast.html', [ 'uses' => 'topc_ctl_member@setUserInfoLast' ]);
        # 会员中心解绑手机邮箱
        route::get('member-unverify.html', [ 'uses' => 'topc_ctl_member@unVerifyOne' ]);
        route::post('member-checkvcode.html', [ 'uses' => 'topc_ctl_member@checkVcode' ]);
        route::get('member-unverifytwo.html', [ 'uses' => 'topc_ctl_member@unVerifyTwo' ]);
        // route::post('member-unverifymobile.html', [ 'uses' => 'topc_ctl_member@unVerifyMobile' ]);
        route::get('member-unverifyemail.html', [ 'uses' => 'topc_ctl_member@unVerifyEmail' ]);
        route::get('member-unverifylast.html', [ 'uses' => 'topc_ctl_member@unVerifyLast' ]);
        # 会员中心收货地址管理
        route::get('member-address.html', [ 'uses' => 'topc_ctl_member@address' ]);
        # 会员中心收货地址修改
        route::post('member-updateaddr.html', [ 'uses' => 'topc_ctl_member@ajaxAddrUpdate' ]);
        # 会员中心默认收货地址
        route::post('member-addrdef.html', [ 'uses' => 'topc_ctl_member@ajaxAddrDef' ]);
        # 删除会员中心收货地址
        route::post('member-deladdr.html', [ 'uses' => 'topc_ctl_member@ajaxDelAddr' ]);
        #会员中心收货地址添加
        route::post('member-address.html', [ 'uses' => 'topc_ctl_member@saveAddress','middleware'=>'topc_middleware_formDuplication' ]);
        # 会员中心店铺收藏
        route::get('member-collectshops.html', [ 'uses' => 'topc_ctl_member@shopsCollect' ]);
        # 会员中心商品收藏
        route::get('member-collectitems.html', [ 'uses' => 'topc_ctl_member@itemsCollect' ]);
        # 会员中心优惠券列表
        route::get('member-coupon.html', [ 'uses' => 'topc_ctl_member_coupon@couponList' ]);
        # 会员中心奖品列表
        route::get('member-lottery.html', [ 'uses' => 'topc_ctl_member_lottery@prizeList' ]);
        # 会员中心奖品收货地址添加
        route::post('ajax/prizeAddr.html', [ 'uses' => 'topc_ctl_member_lottery@saveAddr' ]);
        #会员签到
        route::post('member-checkin.html', [ 'uses' => 'topc_ctl_member@checkin' ]);

        #会员中心售后申请
        route::match(array('GET', 'POST'), 'member-aftersales-apply.html', [ 'uses' => 'topc_ctl_member_aftersales@aftersalesApply' ]);
        route::post('member-aftersales-commit.html', ['uses' => 'topc_ctl_member_aftersales@commitAftersalesApply' ]);
        route::get('member-aftersales-list.html', [ 'uses' => 'topc_ctl_member_aftersales@aftersalesList' ]);
        route::get('member-aftersales-detail.html', [ 'uses' => 'topc_ctl_member_aftersales@aftersalesDetail' ]);
        route::get('member-aftersales-godetail.html', [ 'uses' => 'topc_ctl_member_aftersales@goAftersalesDetail' ]);
        route::post('member-aftersales-sendback.html', [ 'uses' => 'topc_ctl_member_aftersales@sendback' ]);
        route::get('trade-aftersales-logistics.html', [ 'uses' => 'topc_ctl_member_aftersales@ajaxLogistics' ]);

        #订单投诉
        route::get('complaints-view.html', [ 'uses' => 'topc_ctl_member_complaints@complaintsView']);
        route::post('complaints-ci.html', [ 'uses' => 'topc_ctl_member_complaints@complaintsCi']);
        route::get('complaints-detail.html', [ 'uses' => 'topc_ctl_member_complaints@detail']);
        route::get('complaints-list.html', [ 'uses' => 'topc_ctl_member_complaints@complaintsList']);
        route::post('complaints-close.html', [ 'uses' => 'topc_ctl_member_complaints@closeComplaints']);

        #会员中心评价
        route::get('member-rate-add.html',  [ 'uses' => 'topc_ctl_member_rate@createRate' ]);
        route::post('member-rate-add.html', [ 'uses' => 'topc_ctl_member_rate@doCreateRate' ]);
        route::get('member-rate-index.html', [ 'uses' => 'topc_ctl_member_rate@index' ]);
        route::get('member-rate-list.html', [ 'uses' => 'topc_ctl_member_rate@ratelist' ]);
        route::get('member-rate-setAnony.html', [ 'uses' => 'topc_ctl_member_rate@setAnony' ]);
        route::get('member-rate-del.html',  [ 'uses' => 'topc_ctl_member_rate@doDelete' ]);
        route::post('member-rate-update.html', [ 'uses' => 'topc_ctl_member_rate@update' ]);
        route::get('member-rate-edit.html', [ 'uses' => 'topc_ctl_member_rate@edit' ]);
        route::get('member-rate-append.html', [ 'uses' => 'topc_ctl_member_rate@showAppendRateView' ]);
        route::post('member-rate-append.html', [ 'uses' => 'topc_ctl_member_rate@appendRate' ]);

        #会员中心咨询
        route::get('member-gack-index.html', [ 'uses' => 'topc_ctl_member_consultation@index' ]);
        route::post('member-gack-del.html', [ 'uses' => 'topc_ctl_member_consultation@doDelete' ]);

        # 会员中心成长值及极分
        route::get('member-myexp.html', [ 'uses' => 'topc_ctl_member_experience@experience' ]);
        route::get('member-mypoint.html', [ 'uses' => 'topc_ctl_member_point@point' ]);
        route::post('member-getpoint.html', [ 'uses' => 'topc_ctl_member_point@ajaxGetUserPoint' ]);
        route::get('member-mygrade.html', [ 'uses' => 'topc_ctl_member@grade' ]);

        //会员中心红包使用
        route::get('member-hongbao.html', [ 'uses' => 'topc_ctl_member_hongbao@index' ]);
        route::get('member-get.html', [ 'uses' => 'topc_ctl_member_hongbao@getHongbao' ]);

        # 会员中心我的订单
        route::get('trade-list.html', [ 'uses' => 'topc_ctl_member_trade@tradeList' ]);
        route::post('logi.html', [ 'uses' => 'topc_ctl_member_trade@ajaxGetTrack' ]);
        route::post('hint.html', [ 'uses' => 'topc_ctl_member_trade@ajaxHint' ]);
        route::get('canceled-trade-list.html', [ 'uses' => 'topc_ctl_member_trade@canceledTradeList' ]);

        /*add_start_gurundong_2017/12/8*/
        //银行卡管理
        route::get('bank-list.html', [ 'uses' => 'topc_ctl_member_bank@show' ]);
        route::get('bank-add.html', [ 'uses' => 'topc_ctl_member_bank@add' ]);
        route::post('bank-add-post.html', [ 'uses' => 'topc_ctl_member_bank@post' ]);
        route::post('bank-add-delete.html', [ 'uses' => 'topc_ctl_member_bank@delete' ]);
        //代金券管理
        route::get('cash-list.html',['uses'=>'topc_ctl_member_cash@index']);
        /*add_end_gurundong_2017/12/8*/

        # 会员中心订单详情
        route::get('trade-detail.html', [ 'uses' => 'topc_ctl_member_trade@tradeDetail' ]);
        route::get('trade-cancel.html', [ 'uses' => 'topc_ctl_member_trade@ajaxCancelTrade' ]);
        route::get('trade-confirm.html', [ 'uses' => 'topc_ctl_member_trade@ajaxConfirmTrade' ]);
        route::match(array('GET', 'POST'), 'confirm-buyer.html', ['uses' => 'topc_ctl_member_trade@confirmReceipt']);
        route::match(array('GET', 'POST'), 'cancel-buyer.html', ['uses' => 'topc_ctl_member_trade@cancelOrderBuyer']);

        #会员中心线下支付部分
        route::get('offline-trade-list.html', [ 'uses' => 'topc_ctl_member_offlinetrade@tradeList' ]);
        route::get('offline-canceled-trade-list.html', [ 'uses' => 'topc_ctl_member_offlinetrade@canceledTradeList' ]);
        route::get('offline-trade-detail.html', [ 'uses' => 'topc_ctl_member_offlinetrade@tradeDetail' ]);
        route::get('offline-trade-cancel.html', [ 'uses' => 'topc_ctl_member_offlinetrade@ajaxCancelTrade' ]);
        route::get('offline-canceled-trade-detail.html', [ 'uses' => 'topc_ctl_member_offlinetrade@canceledTradeDetail' ]);
        route::get('agent-voucher.html', [ 'uses' => 'topc_ctl_member_agentvoucher@index']);
        route::match(array('GET', 'POST'), 'offline-cancel-buyer.html', ['uses' => 'topc_ctl_member_offlinetrade@cancelOrderBuyer']);


        route::match(array('GET', 'POST'), 'member-deposit-modifyPassword.html', ['uses' => 'topc_ctl_member_deposit@modifyPassword']);
        route::match(array('GET', 'POST'), 'member-deposit-modifyPasswordCheckLoginPassword.html', ['uses' => 'topc_ctl_member_deposit@modifyPasswordCheckLoginPassword']);
        route::match(array('GET', 'POST'), 'member-deposit-doModifyPassword.html', ['uses' => 'topc_ctl_member_deposit@doModifyPassword']);
        route::match(array('GET', 'POST'), 'member-deposit-doModifyPasswordCheckLoginPassword.html', ['uses' => 'topc_ctl_member_deposit@doModifyPasswordCheckLoginPassword']);
        route::match(array('GET', 'POST'), 'member-deposit-forgetPassword.html', ['uses' => 'topc_ctl_member_deposit@forgetPassword']);
        route::match(array('GET', 'POST'), 'member-deposit-forgetPasswordSetPassword.html', ['uses' => 'topc_ctl_member_deposit@forgetPasswordSetPassword']);
        route::match(array('GET', 'POST'), 'member-deposit-forgetPasswordFinished.html', ['uses' => 'topc_ctl_member_deposit@forgetPasswordFinished']);
        route::match(array('GET', 'POST'), 'member-deposit-forgetPasswordSendVcode.html', ['uses' => 'topc_ctl_member_deposit@forgetPasswordSendVcode']);
        route::get('canceled-trade-detail.html', [ 'uses' => 'topc_ctl_member_trade@canceledTradeDetail' ]);

        route::get('voucher/list.html', [ 'uses' => 'topc_ctl_member_voucher@index']);
        route::get('voucher/getvouchercode.html', [ 'uses' => 'topc_ctl_member_voucher@getVoucher']);
    });

    /*
    |--------------------------------------------------------------------------
    | 交易
    |--------------------------------------------------------------------------
    */

    // 购物车
    route::get('cart.html', [ 'uses' => 'topc_ctl_cart@index' ]);
    route::post('cart-add.html', [ 'uses' => 'topc_ctl_cart@add' ]); #加入购物车
    route::post('cart-update.html', [ 'uses' => 'topc_ctl_cart@updateCart' ]); #更新购物车
    route::post('cart-remove.html', [ 'uses' => 'topc_ctl_cart@removeCart' ]); #删除
    route::post('cart.html', [ 'uses' => 'topc_ctl_cart@ajaxBasicCart' ]); #购物车页

    route::group(array('middleware' => 'topc_middleware_authenticate'), function() {
        #显示购物车
        route::post('trade-create.html', [ 'uses' => 'topc_ctl_trade@create' ]); #生成订单
        route::post('cart-total.html', [ 'uses' => 'topc_ctl_cart@total' ]); #统计总金额

        // 订单确认页
        route::get('cart-checkout.html', [ 'uses' => 'topc_ctl_cart@checkout' ]); #立即购买
        route::post('cart-checkout.html', [ 'uses' => 'topc_ctl_cart@checkout' ]); #购物信息结算页
        route::post('cart-saveAddress.html', [ 'uses' => 'topc_ctl_cart@saveAddress' ]); #购物信息结算页
        route::post('cart-addrDialog.html', [ 'uses' => 'topc_ctl_cart@addr_dialog' ]); #收货地址弹框
        route::post('cart-getCoupons.html', [ 'uses' => 'topc_ctl_cart@getCoupons' ]); #获取用户的优惠券
        route::post('cart-useCoupon.html', [ 'uses' => 'topc_ctl_cart@useCoupon' ]); #使用优惠券
        route::post('cart-cancelCoupon.html', [ 'uses' => 'topc_ctl_cart@cancelCoupon' ]); #取消优惠券
        route::post('cart-getDtyList.html', [ 'uses' => 'topc_ctl_cart@getDtyList' ]); #获取指定店铺的配送方式列表

        route::post('cart-getVoucher.html', [ 'uses' => 'topc_ctl_cart@getVouchers' ]); #获取用户的购物券
        route::post('cart-useVoucher.html', [ 'uses' => 'topc_ctl_cart@useVoucher' ]); #获取用户的购物券

        //获取自提列表
        route::post('trade-ziti.html', [ 'uses' => 'topc_ctl_cart@getZitiList' ]); #生成订单
    });

    /*
    |--------------------------------------------------------------------------
    | 支付中心
    |--------------------------------------------------------------------------
    */
    route::group(array('middleware' => 'topc_middleware_authenticate'), function() {
        #支付中心
        route::get('payment.html', [ 'uses' => 'topc_ctl_paycenter@index' ]);
        route::get('errorpay.html', [ 'uses' => 'topc_ctl_paycenter@errorPay' ]);
        route::match(array('GET', 'POST'), 'create.html', ['uses' => 'topc_ctl_paycenter@createPay']);
        route::post('dopayment.html', ['uses' => 'topc_ctl_paycenter@dopayment' ]);

        route::match(array('GET', 'POST'),'finish.html', [ 'uses' => 'topc_ctl_paycenter@finish' ]);
        route::match(array('GET', 'POST'),'freeTicketsFinish.html', [ 'uses' => 'topc_ctl_paycenter@freeTicketsFinish' ]);
        route::match(array('GET', 'POST'),'rushTickets.html', [ 'uses' => 'topc_ctl_offlinepay_rushTickets@rushTichets' ]);  //线下的无偿券领取
    });
 });
/*
|--------------------------------------------------------------------------
| 呼叫中心平台
|--------------------------------------------------------------------------
*/
route::group(array('prefix' => 'call_center','middleware' => ['topc_middleware_callLimit']), function() {
    //用户相关
    route::group(array(), function() {
        route::post('call_point_get.html', [ 'uses' => 'topc_ctl_callCenter_index@ajaxGetUserPoint' ]);
        route::post('call_cash_get.html', [ 'uses' => 'topc_ctl_callCenter_index@ajaxGetUserCash' ]);
        route::post('call_coupon_get.html', [ 'uses' => 'topc_ctl_callCenter_index@ajaxGetUserCoupon' ]);
        route::post('call_coupon_use.html', [ 'uses' => 'topc_ctl_callCenter_index@useCoupon']);
        route::post('call_coupon_cancel.html', [ 'uses' => 'topc_ctl_callCenter_index@cancelCoupon']);
        route::get('call_index.html',[ 'as' => 'call.index', 'uses' => 'topc_ctl_callCenter_index@index']);
        route::get('call_user.html',[ 'as' => 'call.index', 'uses' => 'topc_ctl_callCenter_user@ajaxGetUserInfo']);
        route::get('call_search_user.html',[ 'as' => 'call.search.user', 'uses' => 'topc_ctl_callCenter_user@searchUser' ]);
        route::match(['GET', 'POST'], 'call_create_user.html',[ 'as' => 'call.create.user', 'uses' => 'topc_ctl_callCenter_user@createUser' ]);
        route::match(['GET', 'POST'], 'call_update_user.html',[ 'as' => 'call.update.user', 'uses' => 'topc_ctl_callCenter_user@updateAccountInfo' ]);
    });
    //订单相关
    route::group(array(), function() {
        route::match(['GET','POST'], 'call_trade.html', ['as' => 'call.trade', 'uses' => 'topc_ctl_callCenter_trade@ajaxTradeList']);
        route::match(['GET','POST'], 'call_after_trade.html', ['as' => 'call.after.trade', 'uses' => 'topc_ctl_callCenter_afterTrade@ajaxAfterTradeList']);
        route::get('call_trade_detail.html',[ 'as' => 'call.trade.detail', 'uses' => 'topc_ctl_callCenter_trade@tradeDetail']);
        route::get('call_after_trade_detail.html',[ 'as' => 'call.after.trade.detail', 'uses' => 'topc_ctl_callCenter_afterTrade@afterTradeDetail']);
        route::match(['GET','POST'], 'call_place_order.html',[ 'as' => 'call.place.order', 'uses' => 'topc_ctl_callCenter_placeOrder@index']);
        route::match(['GET','POST'], 'call_total_fee.html', ['as' => 'call.total.fee', 'uses' => 'topc_ctl_callCenter_placeOrder@ajaxComputeTotalFee']);
        route::post('call_trade_create.html', ['as' => 'call.trade.create', 'uses' => 'topc_ctl_callCenter_placeOrder@createCallTrade']);
        route::get('call_trade_cancel.html', ['as' => 'call.trade.cancel', 'uses' => 'topc_ctl_callCenter_trade@tradeCancel']);
        route::post('call_trade_close.html', ['as' => 'call.trade.close', 'uses' => 'topc_ctl_callCenter_trade@closeTrade']);
        route::get('call_trade_refund_apply.html', ['as' => 'call.trade.refund.apply', 'uses' => 'topc_ctl_callCenter_trade@tradeRefundApply']);
        route::post('call_trade_refund_apply_commit.html', ['as' => 'call.trade.refund.apply.commit', 'uses' => 'topc_ctl_callCenter_trade@commitAftersalesApply']);
        route::post('call_trade_edit_receive_address.html', ['as' => 'call.trade.edit.receive.address', 'uses' => 'topc_ctl_callCenter_placeOrder@editReceiveAddress']);
    });
    //测试相关
//    route::group(array(), function() {
//        route::match(['GET','POST'], 'dev_redis.html', ['as' => 'call.trade.redis', 'uses' => 'topshop_ctl_trade_redisTest@testRedis']);
//    });

    //地址相关
    route::group(array(), function() {
        route::match(['GET','POST'], 'call_get_address.html', ['as' => 'call.address.get', 'uses' => 'topc_ctl_callCenter_address@getAddr']);
        route::post('call_add_address.html', ['as' => 'call.address.add', 'uses' => 'topc_ctl_callCenter_address@saveAddr']);
    });
    //商品相关
    route::group(array(), function() {
        route::match(['GET','POST'],'call_item_add.html', ['as' => 'call.item.add', 'uses' => 'topc_ctl_callCenter_item@ajaxItemList']);
        route::match(['GET','POST'],'call_item_checked.html', ['as' => 'call.item.checked', 'uses' => 'topc_ctl_callCenter_item@getCheckedSku']);
    });
});

/*
|--------------------------------------------------------------------------
| WAP端消费者平台
|--------------------------------------------------------------------------
 */
route::group(array('prefix' => 'oldwap', 'middleware' => ['theme_middleware_preview']), function() {
    /*
    |--------------------------------------------------------------------------
    | 会员登录注册相关
    |--------------------------------------------------------------------------
    */
    route::group(array(), function() {
        # 登录
        route::get('passport-signin.html', [ 'middleware' => 'topm_middleware_redirectIfAuthenticated',
                                             'uses' => 'topm_ctl_passport@signin' ]);
        route::post('passport-signin.html', [ 'middleware' => 'topm_middleware_redirectIfAuthenticated',
                                              'uses' => 'topm_ctl_passport@login' ]);
        route::post('passport-saveuname.html', [ 'uses' => 'topm_ctl_passport@saveUname' ]); # 保存登陆设置用户名
        # 退出
        route::get('passport-logout.html', [ 'uses' => 'topm_ctl_passport@logout' ]);
        # 注册
        route::get('passport-signup.html', [ 'middleware' => 'topm_middleware_redirectIfAuthenticated',
                                             'uses' => 'topm_ctl_passport@signup' ]);
        route::get('passport-license.html', [ 'uses' => 'topm_ctl_passport@license' ]);
        route::post('passport-signup.html', [ 'uses' => 'topm_ctl_passport@create' ]);
        route::post('passport-signupcheck.html', [ 'uses' => 'topm_ctl_passport@checkLoginAccount' ]);# 注册验证
        # 找回密码
        route::get('passport-findpwd.html', [ 'uses' => 'topm_ctl_passport@findPwd' ]);#找回密码1
        route::post('passport-findpwdtwo.html', [ 'uses' => 'topm_ctl_passport@findPwdTwo' ]);#找回密码2
        route::get('passport-findpwdtwo.html', [ 'uses' => 'topm_ctl_passport@findPwdTwo' ]);
        route::post('passport-findpwdthree.html', [ 'uses' => 'topm_ctl_passport@findPwdThree' ]);#找回密码3
        route::get('passport-findpwdthree.html', [ 'uses' => 'topm_ctl_passport@findPwdThree' ]);#找回密码3
        route::post('passport-sendVcode.html', [ 'uses' => 'topm_ctl_passport@sendVcode' ]);#找回密码短信验证码发送
        route::post('passport-findpwdfour.html', [ 'uses' => 'topm_ctl_passport@findPwdFour' ]);#找回密码4

        # 信任登陆
        route::get('trustlogin-bind.html', [ 'uses' => 'topm_ctl_trustlogin@callBack' ]);
        route::post('bindDefaultCreateUser.html', [ 'uses' => 'topm_ctl_trustlogin@bindDefaultCreateUser' ]);
        route::post('bindExistsUser.html', [ 'uses' => 'topm_ctl_trustlogin@bindExistsUser' ]);
        route::post('bindSignupUser.html', [ 'uses' => 'topm_ctl_trustlogin@bindSignupUser' ]);


        /*
        route::get('trustwaplogin-bind.html', [ 'uses' => 'topm_ctl_trustlogin@callBack' ]);
        route::post('trustwaplogin.html', [ 'uses' => 'topm_ctl_trustlogin@setLogin' ]);
        route::post('trustwaplogin-binds.html', [ 'uses' => 'topm_ctl_trustlogin@checkLogin' ]);
        route::get('trustwaplogin-postlogin.html', [ 'uses' => 'topm_ctl_trustlogin@postLogin' ]);
        */
    });

    /*
    |--------------------------------------------------------------------------
    | 文章相关
    |--------------------------------------------------------------------------
    */
    route::group(array(), function() {

        route::get('node-list.html', [ 'uses' => 'topm_ctl_content@nodeList' ]);
        route::get('content-list.html', [ 'uses' => 'topm_ctl_content@contentList' ]);
        route::get('content-info.html', [ 'uses' => 'topm_ctl_content@getContentInfo', 'as' => 'topm.content.detail' ]);
    });

    /*
    |--------------------------------------------------------------------------
    | 首页,商品详细页,类目相关
    |--------------------------------------------------------------------------
    */
    route::group(array(), function() {
        # 系统分类
        route::get('/', [ 'as' => 'topm', 'uses' => 'topm_ctl_default@index' ]);
        # 切换到手机端
        route::get('to-pc.html', [ 'as' => 'topm.siwtchToPc', 'uses' => 'topm_ctl_default@switchToPc']);
        #商品搜索
        route::post('search.html', [ 'uses' => 'topm_ctl_search@index' ]);
        route::get('search.html', [ 'uses' => 'topm_ctl_shopcenter@search' ]);
        #商品搜索结果页
        route::get('list.html', [ 'uses' => 'topm_ctl_list@index' ]);
        route::get('ajaxitemshow.html', [ 'uses' => 'topm_ctl_list@ajaxItemShow' ]);

        # 一级类目页
        route::get('categroy.html', [ 'uses' => 'topm_ctl_category@index' ]);
        route::get('catlistinfo.html', [ 'uses' => 'topm_ctl_category@catList' ]);#一级下面类目页

        # 商品详情
        route::get('item.html', ['as' => 'topm.item.detail', 'uses' => 'topm_ctl_item@index' ]);
        route::post('item-collect.html', [ 'uses' => 'topm_ctl_collect@ajaxFav' ]);#收藏
        route::post('shop-collect.html', [ 'uses' => 'topm_ctl_collect@ajaxFavshop' ]);#收藏
        route::get('itempic.html', [ 'uses' => 'topm_ctl_item@itemPic' ]);#图文详情
        route::get('itemparams.html', [ 'uses' => 'topm_ctl_item@itemParams' ]);#图文详情
        #商品详情页，评价列表
        route::get('item-rate.html', [ 'uses' => 'topm_ctl_item@getItemRate' ]);
        route::get('item-rate-list.html', [ 'uses' => 'topm_ctl_item@getItemRateList' ]);

        # 店铺收藏删除,商品收藏删除
        route::post('member-collectdel.html', [ 'uses' => 'topm_ctl_collect@ajaxFavDel' ]);
        route::post('member-collectshopdel.html', [ 'uses' => 'topm_ctl_collect@ajaxFavshopDel' ]);
        # 商品列表

        #商品详情页，促销
        route::get('promotion-item.html', [ 'uses' => 'topm_ctl_promotion@getPromotionItem' ]);
        route::get('promotion-itemlist.html', [ 'uses' => 'topm_ctl_promotion@ajaxPromotionItemShow' ]);

        #商品详情页,到货通知
        route::post('user-item.html', [ 'uses' => 'topm_ctl_memberItem@userNotifyItem' ]);
        #所有活动列表页
        route::get('activity-index.html', [ 'uses' => 'topm_ctl_activity@index' ]);
        route::get('activity-list.html', [ 'uses' => 'topm_ctl_activity@activity_list' ]);
        route::get('activity-toremind.html',['uses' => 'topm_ctl_activity@toRemind']);
        route::post('activity-doremind.html',['uses' => 'topm_ctl_activity@saleRemind']);

        #单个活动信息及对应商品页
        route::get('activity-itemlist.html', [ 'uses' => 'topm_ctl_activity@activity_item_list' ]);
        route::get('activity-search.html', [ 'uses' => 'topm_ctl_activity@search' ]);
        route::get('activity-data.html', [ 'uses' => 'topm_ctl_activity@itemlist' ]);
        route::get('activity-ajax.html', [ 'uses' => 'topm_ctl_activity@ajaxItemShow' ]);
        route::get('activity-detail.html', [ 'uses' => 'topm_ctl_activity@detail' ]);
        route::get('activity-lv3.html', [ 'uses' => 'topm_ctl_activity@getCatLv3' ]);
    });

    /*
    |--------------------------------------------------------------------------
    | 会员中心
    |--------------------------------------------------------------------------
    */
    route::group(array('middleware' => 'topm_middleware_authenticate'), function() {
        # 会员中心
        route::get('member-index.html', [ 'as' => 'topm.member.home', 'uses' => 'topm_ctl_member@index' ]);
        route::get('member-infoset.html', [ 'uses' => 'topm_ctl_member@userinfoSet' ]);# 会员中心个人资料
        route::post('member-saveinfoset.html', [ 'uses' => 'topm_ctl_member@saveInfoSet' ]);# 会员中心个人资料
        route::get('member-collectshops.html', [ 'uses' => 'topm_ctl_member@shopsCollect' ]);# 会员中心商品收藏
        route::get('member-ajaxcollectshops.html', [ 'uses' => 'topm_ctl_member@ajaxshopsCollect' ]);# 会员中心商品收藏
        route::get('member-collectitems.html', [ 'uses' => 'topm_ctl_member@itemsCollect' ]);# 会员中心店铺收藏
        route::get('member-ajaxcollectitems.html', [ 'uses' => 'topm_ctl_member@ajaxitemsCollect' ]);# 会员中心店铺收藏
        route::get('ajaxtradeshow.html', [ 'uses' => 'topm_ctl_member_trade@ajaxTradeShow' ]);

        # 会员中心安全中心
        route::get('member-security.html', [ 'uses' => 'topm_ctl_member@security' ]);
        route::get('member-modifypwd.html', [ 'uses' => 'topm_ctl_member@modifyPwd' ]);# 会员中心安全中心密码修改
        route::post('member-savemodifypwd.html', [ 'uses' => 'topm_ctl_member@saveModifyPwd' ]);# 会员中心安全中心密码修改

        route::get('member-setuserinfo.html', [ 'uses' => 'topm_ctl_member@verify' ]); # 会员中心手机/邮箱绑定
        route::post('member-checkUserLogin.html', [ 'uses' => 'topm_ctl_member@CheckSetInfo' ]);# 会员中心登录检测
        route::get('member-setinfoone.html', [ 'uses' => 'topm_ctl_member@setUserInfoOne' ]);# 会员中心手机
        route::post('member-sendVcode.html', [ 'uses' => 'topm_ctl_member@sendVcode' ]);# 会员中心短信验证码发送
        route::post('member-saveSetInfo.html', [ 'uses' => 'topm_ctl_member@saveSetInfo' ]); # 会员中心个人信息最后保存
        route::get('member-setinfotwo.html', [ 'uses' => 'topm_ctl_member@setUserInfoTwo' ]);# 会员中心个人信息最后保存后的跳转
        route::get('member-bindEmail.html', [ 'uses' => 'topm_ctl_member@bindEmail' ]);# 邮箱认证
        route::get('member-verifyRoute.html', [ 'uses' => 'topm_ctl_member@verifyRoute' ]);# 安全中心绑定手机页面路由
         # 会员中心解绑手机邮箱
        route::get('member-unverify.html', [ 'uses' => 'topm_ctl_member@unVerifyOne' ]);
        route::post('member-checkvcode.html', [ 'uses' => 'topm_ctl_member@checkVcode' ]);
        route::get('member-unverifytwo.html', [ 'uses' => 'topm_ctl_member@unVerifyTwo' ]);
        route::post('member-unverifymobile.html', [ 'uses' => 'topm_ctl_member@unVerifyMobile' ]);
        route::get('member-unverifyemail.html', [ 'uses' => 'topm_ctl_member@unVerifyEmail' ]);
        route::get('member-unverifylast.html', [ 'uses' => 'topm_ctl_member@unVerifyLast' ]);
        # 户名设置
        route::post('member-updateaccount.html', [ 'uses' => 'topm_ctl_member@saveUserAccount' ]);
        # 会员中心收货地址管理
        route::get('member-address.html', [ 'uses' => 'topm_ctl_member@address' ]);
        route::get('member-updateaddr.html', [ 'uses' => 'topm_ctl_member@addrUpdate' ]);# 会员中心收货地址修改
        route::post('member-addrdef.html', [ 'uses' => 'topm_ctl_member@ajaxAddrDef' ]);# 会员中心默认收货地址
        route::post('member-deladdr.html', [ 'uses' => 'topm_ctl_member@ajaxDelAddr' ]);# 删除会员中心收货地址
        route::post('member-address.html', [ 'uses' => 'topm_ctl_member@saveAddress' ]);#会员中心收货地址添加

        #会员中心评价
        route::get('member-rate-add.html',  [ 'uses' => 'topm_ctl_member_rate@createRate' ]);
        route::post('member-rate-add.html', [ 'uses' => 'topm_ctl_member_rate@doCreateRate' ]);

        # 会员中心优惠券列表
        route::get('member-couponList.html', [ 'uses' => 'topm_ctl_member_coupon@couponList' ]);
        route::get('member-ajaxCouponData.html', [ 'uses' => 'topm_ctl_member_coupon@ajaxCouponData' ]);
        #删除优惠券
        route::post('member-deleteCoupon.html', [ 'uses' => 'topm_ctl_member_coupon@deleteCoupon' ]);
        // 会员中心查看优惠券详情
        route::get('member-couponDetail.html', [ 'uses' => 'topm_ctl_member_coupon@couponDetail' ]);

        #会员中心售后申请
        route::get('member-aftersales-exchange.html', [ 'uses' => 'topm_ctl_member_aftersales@exchange' ]);
        route::get('member-aftersales-apply.html', [ 'uses' => 'topm_ctl_member_aftersales@aftersalesApply' ]);
        route::post('member-aftersales-commit.html', [ 'uses' => 'topm_ctl_member_aftersales@commitAftersalesApply' ]);
        //route::get('member-aftersales-list.html', [ 'uses' => 'topm_ctl_member_aftersales@aftersalesList' ]);
        route::get('member-aftersales-detail.html', [ 'uses' => 'topm_ctl_member_aftersales@aftersalesDetail' ]);
        route::get('member-aftersales-godetail.html', [ 'uses' => 'topm_ctl_member_aftersales@goAftersalesDetail' ]);
        route::post('member-aftersales-sendback.html', [ 'uses' => 'topm_ctl_member_aftersales@sendback' ]);
        route::post('member-aftersales-getTrack.html', [ 'uses' => 'topm_ctl_member_aftersales@ajaxGetTrack' ]);
        route::get('member-aftersales-list.html', [ 'uses' => 'topm_ctl_member_aftersales@aftersalesList' ]);
        route::get('member-aftersales-listpage.html', [ 'uses' => 'topm_ctl_member_aftersales@ajaxAftersalesList' ]);

        route::match(['POST', 'GET'], 'member-deposit-modifyPasswordCheckLoginPassword.html',[ 'uses' => 'topm_ctl_member_deposit@modifyPasswordCheckLoginPassword' ]);
        route::match(['POST', 'GET'], 'member-deposit-doModifyPasswordCheckLoginPassword.html',[ 'uses' => 'topm_ctl_member_deposit@doModifyPasswordCheckLoginPassword' ]);
        route::match(['POST', 'GET'], 'member-deposit-modifyPassword.html',[ 'uses' => 'topm_ctl_member_deposit@modifyPassword' ]);
        route::match(['POST', 'GET'], 'member-deposit-doModifyPassword.html',[ 'uses' => 'topm_ctl_member_deposit@doModifyPassword' ]);

        route::match(array('GET', 'POST'), 'member-deposit-forgetPassword.html', ['uses' => 'topm_ctl_member_deposit@forgetPassword']);
        route::match(array('GET', 'POST'), 'member-deposit-forgetPasswordSetPassword.html', ['uses' => 'topm_ctl_member_deposit@forgetPasswordSetPassword']);
        route::match(array('GET', 'POST'), 'member-deposit-forgetPasswordFinished.html', ['uses' => 'topm_ctl_member_deposit@forgetPasswordFinished']);
        route::match(array('GET', 'POST'), 'member-deposit-forgetPasswordSendVcode.html', ['uses' => 'topm_ctl_member_deposit@forgetPasswordSendVcode']);

        #订单投诉
        route::get('complaints-view.html', [ 'uses' => 'topm_ctl_member_complaints@complaintsView']);
        route::post('complaints-ci.html', [ 'uses' => 'topm_ctl_member_complaints@complaintsCi']);
        route::get('complaints-detail.html', [ 'uses' => 'topm_ctl_member_complaints@detail']);
        route::post('complaints-close.html', [ 'uses' => 'topm_ctl_member_complaints@closeComplaints']);
        route::get('complaints-close.html', [ 'uses' => 'topm_ctl_member_complaints@closeView']);


        #会员积分成长值
        route::get('myexp.html', [ 'uses' => 'topm_ctl_member_experience@experience' ]);
        route::get('myexpAjaxShow.html', [ 'uses' => 'topm_ctl_member_experience@ajaxExperienceShow' ]);
        route::get('mypoint.html', [ 'uses' => 'topm_ctl_member_point@point' ]);
        route::post('getpoint.html', [ 'uses' => 'topm_ctl_member_point@ajaxGetUserPoint' ]);
        route::get('exp-detail.html', [ 'uses' => 'topm_ctl_member_experience@grade' ]);
        route::get('pointAjaxShow.html',['uses' => 'topm_ctl_member_point@ajaxPointShow']);
        route::get('experienceAjaxShow.html',['uses' => 'topm_ctl_member_experience@ajaxExperienceShow']);

        # 会员中心我的订单  订单详情
        route::get('tradelist.html', [ 'uses' => 'topm_ctl_member_trade@index']);  #会员中心订单列表）
        route::get('trade-list.html', [ 'uses' => 'topm_ctl_member_trade@tradeList']);  #会员中心订单列表(tab)
        route::get('trade-detail.html', [ 'uses' => 'topm_ctl_member_trade@detail' ]);
        route::get('cancel.html', [ 'uses' => 'topm_ctl_member_trade@cancel' ]);
        route::get('confirm.html', [ 'uses' => 'topm_ctl_member_trade@confirm' ]);
        route::get('canceled.html', [ 'uses' => 'topm_ctl_member_trade@canceledTradeList' ]);
        route::get('canceled_detail.html', [ 'uses' => 'topm_ctl_member_trade@canceledTradeDetail' ]);
        route::post('logim.html', [ 'uses' => 'topm_ctl_member_trade@ajaxGetTrack' ]);
        route::match(array('GET', 'POST'), 'confirm-buyer.html', ['uses' => 'topm_ctl_member_trade@confirmReceipt']);
        route::match(array('GET', 'POST'), 'cancel-buyer.html', ['uses' => 'topm_ctl_member_trade@cancelBuyer']);
    });

    /*
    |--------------------------------------------------------------------------
    | 店铺相关
    |--------------------------------------------------------------------------
    */
    route::group(array(), function() {
        # 店铺首页
        route::get('shopcenter.html', [ 'uses' => 'topm_ctl_shopcenter@index' ]);
        /*route::get('getTagsInfo.html', [ 'uses' => 'topm_ctl_shopcenter@getTagsInfo' ]);
          route::get('ajaxTagsShow.html', [ 'uses' => 'topm_ctl_shopcenter@ajaxTagsShow' ]);*/
        # 店铺前台优惠券列表页
        route::get('shopCouponList.html', [ 'uses' => 'topm_ctl_shopcenter@shopCouponList' ]);
        # 领取优惠券
        route::post('getCoupon.html', [ 'uses' => 'topm_ctl_shopcenter@getCouponCode' ]);
        # 领取优惠券成功页
        route::get('getCouponResult.html', [ 'uses' => 'topm_ctl_shopcenter@getCouponResult' ]);
        route::get('ajaxshopitemshow.html', [ 'uses' => 'topm_ctl_shopcenter@ajaxItemShow' ]);
        route::get('vcode.html', [ 'as' => 'toputil.vcode', 'uses' => 'toputil_ctl_vcode@gen_vcode' ]);
    });

    /*
    |--------------------------------------------------------------------------
    | 交易
    |--------------------------------------------------------------------------
    */
    route::post('cart-add.html', [ 'uses' => 'topm_ctl_cart@add' ]); #加入购物车
    route::get('cart.html',['uses' => 'topm_ctl_cart@index']);   #购物车页
    route::post('cart-update.html',['uses' => 'topm_ctl_cart@updateCart']);  #更新购物车
    route::post('cart.html',['uses' => 'topm_ctl_cart@ajaxBasicCart']);    #购物车页
    route::post('cart-remove.html', [ 'uses' => 'topm_ctl_cart@removeCart' ]); #删除
    route::match(array('GET', 'POST', 'PUT', 'DELETE'), 'wxpayjsapi.html', ['uses' => 'topm_ctl_wechat@wxpayjsapi']);#微信的数据做转发。。。

    route::group(array('middleware' => 'topm_middleware_authenticate'), function() {
        //购物车
        route::post('cart-total.html', [ 'uses' => 'topm_ctl_cart@total' ]); #统计总金额

        // 订单确认页
        route::post('cart-checkout.html',['uses' => 'topm_ctl_cart@checkout']);  #立即购买
        route::get('cart-checkout.html',['uses' => 'topm_ctl_cart@checkout']);  #购物信息结算页
        route::post('cart-saveAddress.html', [ 'uses' => 'topm_ctl_cart@saveAddress' ]); #购物信息结算页
        route::get('cart-editaddr.html', [ 'uses' => 'topm_ctl_cart@editAddr' ]); #收货地址弹框
        route::get('cart-addrList.html', [ 'uses' => 'topm_ctl_cart@getAddrList' ]); #收货地址弹框
        route::get('cart-payTypeList.html', [ 'uses' => 'topm_ctl_cart@getPayTypeList' ]); #收货地址弹框
        route::get('deladdr.html', [ 'uses' => 'topm_ctl_cart@delAddr' ]);# 删除会员中心收货地址
        route::post('cart-useCoupon.html', [ 'uses' => 'topm_ctl_cart@useCoupon' ]); #使用优惠券
        route::post('cart-cancelCoupon.html', [ 'uses' => 'topm_ctl_cart@cancelCoupon' ]); #取消优惠券

        //订单处理
        route::post('trade-create.html', [ 'uses' => 'topm_ctl_trade@create' ]); #生成订单

        #支付中心
        route::get('payment.html', [ 'uses' => 'topm_ctl_paycenter@index' ]);
        route::match(array('GET', 'POST'), 'create.html', ['uses' => 'topm_ctl_paycenter@createPay']);
        route::post('dopayment.html', [ 'uses' => 'topm_ctl_paycenter@dopayment' ]);
        route::get('finish.html', [ 'uses' => 'topm_ctl_paycenter@finish' ]);


    });
});

route::group(array('prefix' => 'wap', 'middleware' => ['theme_middleware_preview', 'base_middleware_machine_check', 'base_middleware_machine_hook']), function() {

    route::get('/', [ 'as' => 'topwap', 'uses' => 'topwap_ctl_default@index']);

    route::get('app.html', [ 'as' => 'topwap.app.index', 'uses' => 'topwap_ctl_app@index']);
    route::get('wx/app.html', [ 'as' => 'topwap.app.wx.boot', 'uses' => 'topwap_ctl_app@wxDownloadBoot']);

    route::get('configContent.html', ['as'=>'topwap.configContent', 'uses'=>'topwap_ctl_util@configContent']);

    /*add_20171018_by_fanglongji_start*/
    // 首页猜你喜欢
    route::get('ajaxguesslikeindex.html', [ 'uses' => 'topwap_ctl_item_list@ajaxGetLikeItemListIndex' ]);
    route::get('ajaxguesslike.html', [ 'uses' => 'topwap_ctl_item_list@ajaxGetLikeItemList' ]);
    //首页每日上新
    route::match(['GET','POST'],'day-new.html', [ 'as' => 'day.new.list', 'uses' => 'topwap_ctl_item_daynew@index' ]);
    route::match(['GET','POST'],'day-new-ajax.html', [ 'as' => 'day.new.ajax.list', 'uses' => 'topwap_ctl_item_daynew@ajaxGetItemList' ]);
    //首页余额专场
    route::match(['GET','POST'],'banlance.html', [ 'as' => 'banlance.list', 'uses' => 'topwap_ctl_item_banlance@index' ]);
    route::match(['GET','POST'],'banlance-ajax.html', [ 'as' => 'banlance.ajax.list', 'uses' => 'topwap_ctl_item_banlance@ajaxGetItemList' ]);
    //首页直播热销
    route::match(['GET','POST'],'live-hot.html', [ 'as' => 'live.hot.list', 'uses' => 'topwap_ctl_item_livehot@index' ]);
    route::match(['GET','POST'],'live-hot-ajax.html', [ 'as' => 'live.hot.ajax.list', 'uses' => 'topwap_ctl_item_livehot@ajaxGetItemList' ]);
    //首页电视购物
    route::get('mall.html', [ 'uses' => 'topwap_ctl_mall@index' ]);
    route::match(['GET','POST'],'mall-goods.html', [ 'as' => 'mall.goods.list', 'uses' => 'topwap_ctl_item_mallgoods@index' ]);
    route::match(['GET','POST'],'mall-goods-ajax.html', [ 'as' => 'mall.goods.ajax.list', 'uses' => 'topwap_ctl_item_mallgoods@ajaxGetItemList' ]);
    /*add_20171018_by_fanglongji_end*/
    /*add_20171026_by_xinyufeng_start*/
    //购券专场
    route::get('buy-coupon-list.html', [ 'as'=>'buy.coupon.list', 'uses'=>'topwap_ctl_item_buycoupon@getList' ]);
    route::get('buy-coupon-list-ajax.html', [ 'as'=>'buy.coupon.list.ajax', 'uses'=>'topwap_ctl_item_buycoupon@ajaxGetItemList' ]);
    //热卖单品
    route::get('hot-goods-list.html', [ 'as'=>'hot.goods.list', 'uses'=>'topwap_ctl_item_hotgoods@getList' ]);
    route::get('hot-goods-list-ajax.html', [ 'as'=>'hot.goods.list.ajax', 'uses'=>'topwap_ctl_item_hotgoods@ajaxGetItemList' ]);
    /*add_20171026_by_xinyufeng_end*/
	/*add_2017/11/3_by_wanghaichao_start*/
	//平台商品列表
    route::match(['GET','POST'],'shop-index-getlist.html', [ 'as'=>'shop.index.getlist', 'uses'=>'topwap_ctl_newshop@ajaxGetGoodsList' ]);
	/*add_2017/11/3_by_wanghaichao_end*/
	/*add_2018/8/6_by_wanghaichao_start*/
	//蓝莓店铺商品列表
    route::match(['GET','POST'],'tvshop-index-getlist.html', [ 'as'=>'tvshop.index.getlist', 'uses'=>'topwap_ctl_tvshopping@ajaxGetGoodsList' ]);
	//获取主持人的逻辑
    route::match(['GET','POST'],'tvshop-index-getcompere.html', [ 'as'=>'tvshop.index.getcompere', 'uses'=>'topwap_ctl_tvshopping@ajaxGetCompere' ]);
	/*add_2018/8/6_by_wanghaichao_end*/


	/*add_start_gurundong_2017/11/8*/
    //统计驾驶舱相关
    route::group(['middleware'=>'statistic_middleware_weixinAuth'],function (){
        route::get('statistic-house-login.html',['as'=>'statistic.order.login','uses'=>'statistic_ctl_house@login']);
    });
    route::group(['middleware'=>'statistic_middleware_configLogin'],function (){
        route::get('statistic-house-weforms.html',['as'=>'statistic.order.weforms','uses'=>'statistic_ctl_house@weforms']);
        route::get('statistic-house-ajaxGetOrder.html',['as'=>'statistic.order.weforms','uses'=>'statistic_ctl_house@ajaxGetOrder']);   #页面首次获取信息
        route::get('statistic-house-ajaxSearchData.html',['as'=>'statistic.order.weforms','uses'=>'statistic_ctl_house@ajaxSearchData']);   #页面筛选信息
        route::get('statistic-house-ajaxGetOrderTrend.html',['as'=>'statistic.order.ajaxGetOrderTrend','uses'=>'statistic_ctl_house@ajaxGetOrderTrend']);   #统计折线图信息
        route::get('statistic-house-getShopTree.html',['as'=>'statistic.order.getShopTree','uses'=>'statistic_ctl_house@getShopTree']);   #统计折线图信息
        route::get('statistic-house-brokenline.html',['as'=>'statistic.order.brokenline','uses'=>'statistic_ctl_house@brokenline']);   #折线图信息
        route::get('statistic-house-logout.html',['as'=>'statistic.order.logout','uses'=>'statistic_ctl_house@logout']);
    });
    route::post('statistic-house-dologin.html',['as'=>'statistic.order.dologin','uses'=>'statistic_ctl_house@dologin']);
    route::get('statistic-house-ceshi.html',['as'=>'statistic.order.ceshi','uses'=>'statistic_ctl_house@ceshi']);
    /* add_end_gurundong_2017/11/8 */

    //店铺模块相关
    route::group(array('middleware' => 'topwap_middleware_checkShop'), function() {
        route::get('shop-index.html', [ 'as' => 'shop.index', 'uses' => 'topwap_ctl_shop@index' ]);
        route::get('shopindex.html', [ 'as' => 'newshop.index', 'uses' => 'topwap_ctl_newshop@index' ]);
        route::get('shop-info.html', [ 'as' => 'shop.info', 'uses' => 'topwap_ctl_shop@shopInfo' ]);
        route::get('shop-coupon.html', [ 'as' => 'shop.coupon', 'uses' => 'topwap_ctl_shop_coupon@index' ]);
        route::post('receive-coupon.html', [ 'as' => 'receive.coupon', 'uses' => 'topwap_ctl_shop_coupon@receiveConpon' ]);
        route::match(['GET','POST'], 'shop-list-index.html', [ 'as' => 'shop.list.index', 'uses' => 'topwap_ctl_shop_list@index' ]);
        route::match(['GET','POST'], 'shop-list-ajax.html', [ 'as' => 'shop.list.ajax', 'uses' => 'topwap_ctl_shop_list@ajaxGetItemList' ]);
        //新店铺装修商家商品展示页面
        route::match(['GET','POST'], 'shopList.html', [ 'as' => 'shop.list', 'uses' => 'topwap_ctl_newshop@shopItemList' ]);
        route::match(['GET','POST'], 'ajaxShopList.html', [ 'as' => 'ajax.shop.list', 'uses' => 'topwap_ctl_newshop@ajaxGetShopItemList' ]);

    });

	/*add_by_xinyufeng_2018-08-01_start*/
	/*
    |--------------------------------------------------------------------------
    | 电视购物相关
    |--------------------------------------------------------------------------
    */
    route::group(array(), function() {
		//电视购物首页
		route::get('tvshopping.html', [ 'as' => 'tvshopping.index', 'uses' => 'topwap_ctl_tvshopping@index' ]);
		//电视购物直播列表页
		route::get('tvshopping/live-list.html', [ 'as' => 'tvshopping.liveList', 'uses' => 'topwap_ctl_tvshopping@liveList' ]);
		//获取电视购物直播列表数据
		route::match(['GET','POST'], 'tvshopping/live-list-data.html', [ 'as' => 'ajax.tvshopping.liveListData', 'uses' => 'topwap_ctl_tvshopping@ajaxLiveListData' ]);

    });
	/*add_by_xinyufeng_2018-08-01_end*/


	/*add_2018/1/3_by_wanghaichao_start*/
	/*
	|-----------------------------------------------------------------------
	|  车租赁中心
	|------------------------------------------------------------------------
	*/
	//租车登录页
	route::get('lease-login.html', ['uses' => 'topwap_ctl_lease_login@login']);
	route::post('lease-createuser.html', ['uses' => 'topwap_ctl_lease_login@createUser']);
	//租车首页
	route::get('lease-index.html', ['uses' => 'topwap_ctl_lease_default@index']);
	//缴费页面
	route::get('lease-paymentscurrent.html', ['uses' => 'topwap_ctl_lease_default@paymentscurrent']);
	//支付页面
	route::get('lease-rechargepage.html', ['uses' => 'topwap_ctl_lease_default@rechargepage']);
	//车辆详情
	route::get('lease-details.html', ['uses' => 'topwap_ctl_lease_default@details']);
	//还款计划
	route::get('lease-repaymentplan.html', ['uses' => 'topwap_ctl_lease_default@repaymentplan']);
	//详情
	route::get('lease-rechargelist.html', ['uses' => 'topwap_ctl_lease_default@rechargelist']);
	//详情
	route::get('lease-paymentsdetails.html', ['uses' => 'topwap_ctl_lease_default@paymentsdetails']);
	//车辆租赁还款详情
	route::get('lease-paymentsdetails.html', ['uses' => 'topwap_ctl_lease_default@paymentsdetails']);

	/*
	|--------------------------------------------------------------------------
	| 支付中心
	|--------------------------------------------------------------------------
	*/
	#支付中心
	route::get('lease-payment.html', [ 'uses' => 'topwap_ctl_lease_paycenter@index' ]);
	route::match(array('GET', 'POST'), 'lease-create.html', ['uses' => 'topwap_ctl_lease_paycenter@createPay']);
	route::match(array('GET', 'POST'),'lease-dopayment.html', ['uses' => 'topwap_ctl_lease_paycenter@dopayment' ]);

	route::match(array('GET', 'POST'),'lease-finish.html', [ 'uses' => 'topwap_ctl_lease_paycenter@finish' ]);

	/*add_2018/1/3_by_wanghaichao_end*/


    //会员模块相关
    route::group(array('middleware' => 'topwap_middleware_authenticate'), function() {
        route::get('member.html', ['uses' => 'topwap_ctl_member@index' ]);
        route::get('member-setting.html', [ 'uses' => 'topwap_ctl_member@setting' ]);
        route::get('member-detail.html', [ 'uses' => 'topwap_ctl_member@detail' ]);

        #会员签到
        route::post('member-checkin.html', [ 'uses' => 'topwap_ctl_member@checkin' ]);

        //会员基本信息设置
        route::get('set-name.html', [ 'uses' => 'topwap_ctl_member@goSetName' ]);
        route::get('set-username.html', [ 'uses' => 'topwap_ctl_member@goSetUsername' ]);
        route::get('set-sex.html', [  'uses' => 'topwap_ctl_member@goSetSex' ]);
        route::get('set-birthday.html', ['uses' => 'topwap_ctl_member@goSetBirthday' ]);
        route::get('set-loginaccount.html', [ 'uses' => 'topwap_ctl_member@goSetLoginAccount']);
        route::get('set-IdentityCardNumber.html', [ 'uses' => 'topwap_ctl_member@goSetIdentityCardNumber']);
        /*add_start_gurundong_2017/11/2*/
        route::get('set-userimg.html', [ 'uses' => 'topwap_ctl_member@goSetHeadImg' ]);
        route::post('save-userimg.html', [ 'uses' => 'topwap_ctl_member@saveHeadImg' ]);
        /*add_end_gurundong_2017/11/2*/

        //会员基本信息保存
        route::post('save-userinfo.html', ['uses' => 'topwap_ctl_member@saveUserInfo' ]);
        route::post('save-loginaccount.html', ['uses' => 'topwap_ctl_member@saveLoginAccount' ]);
        route::post('save-IdentityCardNumber.html', ['uses' => 'topwap_ctl_member@saveIdentityCardNumber' ]);

        //会员收货地址
        route::get('addr-list.html', [ 'as' => 'member.addr.list', 'uses' => 'topwap_ctl_member_address@addrList' ]);
        route::get('addr-add.html', [ 'as' => 'member.addr.add', 'uses' => 'topwap_ctl_member_address@newAddress' ]);
        route::post('addr-save.html', [ 'as' => 'member.addr.save', 'uses' => 'topwap_ctl_member_address@saveAddress' ]);
        route::get('addr-update.html', [ 'as' => 'member.addr.update', 'uses' => 'topwap_ctl_member_address@updateAddr' ]);
        route::post('addr-setDefault.html', [ 'as' => 'member.addr.set.default', 'uses' => 'topwap_ctl_member_address@setDefault' ]);
        route::post('addr-remove.html', [ 'as' => 'member.addr.remove', 'uses' => 'topwap_ctl_member_address@removeAddr' ]);

        //订单相关
        route::get('trade-list.html', [ 'uses' => 'topwap_ctl_member_trade@tradeList' ]);
        route::get('trade-ajaxlist.html', [ 'uses' => 'topwap_ctl_member_trade@ajaxTradeList' ]);
        route::get('trade-detail.html', [ 'uses' => 'topwap_ctl_member_trade@detail' ]);
        route::get('trade-logistics.html', [ 'uses' => 'topwap_ctl_member_trade@logistics' ]);
        route::match(array('GET', 'POST'), 'confirm-buyer.html', ['uses' => 'topwap_ctl_member_trade@confirmReceipt']);

        // cancel
        route::get('cancel.html', [ 'uses' => 'topwap_ctl_member_trade@cancel' ]);
        route::get('canceled.html', [ 'uses' => 'topwap_ctl_member_trade@canceledTradeList' ]);
        route::get('ajaxcanceled.html', [ 'uses' => 'topwap_ctl_member_trade@ajaxCanceledTradeList' ]);
        route::get('canceled_detail.html', [ 'uses' => 'topwap_ctl_member_trade@canceledTradeDetail' ]);
        route::get('goto_canceled_detail.html', [ 'uses' => 'topwap_ctl_member_trade@gotoCanceledTradeDetail' ]);
        route::match(array('GET', 'POST'), 'cancel-buyer.html', ['uses' => 'topwap_ctl_member_trade@cancelBuyer']);
        // 会员中心评价
        route::get('member-rate-add.html',  [ 'uses' => 'topwap_ctl_member_rate@createRate' ]);
        route::post('member-rate-add.html',  [ 'uses' => 'topwap_ctl_member_rate@doCreateRate' ]);
        route::get('member-rate-index.html',  [ 'uses' => 'topwap_ctl_member_rate@index' ]);
        route::get('member-rate-list.html',  [ 'uses' => 'topwap_ctl_member_rate@ratelist' ]);

        // 会员收藏
        route::get('member-collectitems.html', [ 'uses' => 'topwap_ctl_member_favorite@index' ]);
        route::get('member-ajaxcollectitems.html', [ 'uses' => 'topwap_ctl_member_favorite@ajaxitems' ]);
        route::get('member-ajaxcollectshops.html', [ 'uses' => 'topwap_ctl_member_favorite@ajaxshops' ]);

        route::post('collect-item.html', [ 'uses' => 'topwap_ctl_member_favorite@ajaxAddItemCollect' ]);#收藏商品
        route::post('collect-shop.html', [ 'uses' => 'topwap_ctl_member_favorite@ajaxAddShopCollect' ]);#收藏店铺
        # 店铺收藏删除,商品收藏删除
        route::post('collect-item-del.html', [ 'uses' => 'topwap_ctl_member_favorite@ajaxDelItemCollect' ]);
        route::post('collect-shop-del.html', [ 'uses' => 'topwap_ctl_member_favorite@ajaxDelShopCollect' ]);

        // 会员优惠券
        route::get('member-couponList.html', [ 'uses' => 'topwap_ctl_member_coupon@index' ]);
        route::get('member-ajaxcouponList.html', [ 'uses' => 'topwap_ctl_member_coupon@ajaxCouponList' ]);

        // 会员红包
        route::get('memeber-hongbaoList.html', [ 'uses' => 'topwap_ctl_member_hongbao@index' ]);
        route::post('memeber-ajaxhongbaoList.html', [ 'uses' => 'topwap_ctl_member_hongbao@ajaxHongbaoList' ]);

        // 会员中心售后申请
        route::get('member-aftersales-list.html', [ 'uses' => 'topwap_ctl_member_aftersales@aftersalesList' ]);
        route::get('ajax-member-aftersales-list.html', [ 'uses' => 'topwap_ctl_member_aftersales@ajaxAftersalesList' ]);
        route::get('member-aftersales-detail.html', [ 'uses' => 'topwap_ctl_member_aftersales@aftersalesDetail' ]);
        route::match(['POST', 'GET'], 'member-create-aftersales-logistics.html',[ 'uses' => 'topwap_ctl_member_aftersales@createAfterlogistics' ]);
        route::post('member-add-aftersales-logistics.html',[ 'uses' => 'topwap_ctl_member_aftersales@ajaxcreateAfterlogistics' ]);

        route::post('member-aftersales-sendback.html',[ 'uses' => 'topwap_ctl_member_aftersales@sendback' ]);
        route::post('member-aftersales-commit-apply.html',[ 'uses' => 'topwap_ctl_member_aftersales@commitAftersalesApply' ]);
        route::get('member-see-aftersales-logistics.html', [ 'uses' => 'topwap_ctl_member_aftersales@seeAfterlogistics' ]);
        route::get('member-aftersales-apply.html', [ 'uses' => 'topwap_ctl_member_aftersales@aftersalesApply' ]);
		/*add_2017/11/15_by_wanghaichao_start*/
		//虚拟商品退换货界面
        route::get('member-virtualaftersales-apply.html', [ 'uses' => 'topwap_ctl_member_aftersales@virtualAftersalesApply' ]);
        route::get('member-offlinevirtualaftersales-apply.html', [ 'uses' => 'topwap_ctl_member_aftersales@offlineVirtualAftersalesApply' ]);
        route::post('member-virtualaftersales-commit-apply.html',[ 'uses' => 'topwap_ctl_member_aftersales@commitVirtualAftersalesApply' ]);
		/*add_2017/11/15_by_wanghaichao_end*/

        route::get('member-aftersales-godetail.html', [ 'uses' => 'topwap_ctl_member_aftersales@goAftersalesDetail' ]);
        // 会员积分成长值
        route::get('mypoint.html', [ 'uses' => 'topwap_ctl_member_point@point' ]);
        route::get('ajax-mypoint.html', [ 'uses' => 'topwap_ctl_member_point@ajaxPonint' ]);
        // 会员中心安全中心
        route::get('member-security.html', [ 'uses' => 'topwap_ctl_member@security' ]);
        route::get('member-modifypwd.html', [ 'uses' => 'topwap_ctl_member_safe@setUserPwd' ]);# 会员中心安全中心密码修改
        route::post('member-savemodifypwd.html', [ 'uses' => 'topwap_ctl_member_safe@saveModifyPwd' ]);# 会员中心安全中心密码修改

        route::get('member-setuserinfo.html', [ 'uses' => 'topwap_ctl_member_safe@verify' ]); # 会员中心手机/邮箱绑定
        route::post('member-checkUserLogin.html', [ 'uses' => 'topwap_ctl_member_safe@CheckSetInfo' ]);# 会员中心登录检测
        route::get('member-setinfomobile.html', [ 'uses' => 'topwap_ctl_member_safe@setUserMobile' ]);# 会员中心手机
        route::match(['POST', 'GET'], 'member-sendVcode-mobile.html',[ 'uses' => 'topwap_ctl_member_safe@dosetmobile' ]);
        route::post('member-safe-sendVcode.html', [ 'uses' => 'topwap_ctl_member_safe@sendVcode' ]);# 会员中心短信验证码发送
        route::get('member-safe-viewmobile.html', [ 'uses' => 'topwap_ctl_member_safe@viewSetmobile' ]);# 会员中心短信验证码发送
        route::post('member-save-mobile.html', [ 'uses' => 'topwap_ctl_member_safe@saveMobile' ]); # 会员中心个人信息最后保存
        // 解绑手机
        route::get('member-safe-update-mobile.html', [ 'uses' => 'topwap_ctl_member_safe@viewUserMobile' ]);
        // route::get('member-safe-unbind-mobile.html', [ 'uses' => 'topwap_ctl_member_safe@unbindMobile' ]);
        // route::post('member-safe-unbind-mobile.html', [ 'uses' => 'topwap_ctl_member_safe@doUnbindMobile' ]);

        //会员中心投诉相关内容
        route::get('complaints-list.html', [ 'uses' => 'topwap_ctl_member_complaints@complaintsList']);
        route::get('complaints-view.html', [ 'uses' => 'topwap_ctl_member_complaints@complaintsView']);
        route::get('complaints-form.html', [ 'uses' => 'topwap_ctl_member_complaints@complaintsShopFormView']);
        route::post('complaints-post.html', [ 'uses' => 'topwap_ctl_member_complaints@complaintsPostData']);
        route::post('complaints-close.html', [ 'uses' => 'topwap_ctl_member_complaints@closeComplaints']);
        route::get('complaints-close.html', [ 'uses' => 'topwap_ctl_member_complaints@complaintsCloseFormView']);

        // 预存款密码相关
        route::get('member-deposit-passwd.html',[ 'uses' => 'topwap_ctl_member_deposit@depositPwd' ]);
        route::get('member-deposit-check-login-passwd.html',[ 'uses' => 'topwap_ctl_member_deposit@checkLoginpwd' ]);
        route::post('member-deposit-docheck-login-passwd.html',[ 'uses' => 'topwap_ctl_member_deposit@doCheckLoginPwd' ]);
        route::get('member-deposit-check-oldpay-passwd.html',[ 'uses' => 'topwap_ctl_member_deposit@checkOldpayPwd' ]);
        route::post('member-deposit-docheck-oldpay-passwd.html',[ 'uses' => 'topwap_ctl_member_deposit@doCheckOldpayPwd' ]);
        route::match(array('GET', 'POST'), 'member-deposit-modifyPassword.html', ['uses' => 'topwap_ctl_member_deposit@modifyPassword']);
        route::match(array('GET', 'POST'), 'member-deposit-doModifyPassword.html', ['uses' => 'topwap_ctl_member_deposit@doModifyPassword']);
        route::match(array('GET', 'POST'), 'member-deposit-doModifyPasswordCheckLoginPassword.html', ['uses' => 'topwap_ctl_member_deposit@doModifyPasswordCheckLoginPassword']);
        // find password
        route::match(array('GET', 'POST'), 'member-deposit-forgetPassword.html', ['uses' => 'topwap_ctl_member_deposit@forgetPassword']);
        route::match(array('GET', 'POST'), 'member-deposit-forgetPasswordSetPassword.html', ['uses' => 'topwap_ctl_member_deposit@forgetPasswordSetPassword']);
        route::match(array('GET', 'POST'), 'member-deposit-forgetPasswordSendVcode.html', ['uses' => 'topwap_ctl_member_deposit@forgetPasswordSendVcode']);

        route::get('member-voucher-list.html', [ 'uses' => 'topwap_ctl_member_voucher@index']);
        route::get('member-voucher-ajax.html',['uses'=>'topwap_ctl_member_voucher@ajaxVoucherList']);
        route::get('member-voucher-getvouchercode.html', [ 'uses' => 'topwap_ctl_member_voucher@getVoucher']);

        /*add_gurundong_20171019_start*/
        route::get('member-votegift-list.html', [ 'uses' => 'topwap_ctl_member_votegift@index']);   #奖品中心列表
        route::get('member-votegift-ajaxist.html', [ 'uses' => 'topwap_ctl_member_votegift@ajaxGiftList']);  #ajax奖品中心列表
        /*add_gurundong_20171010_end*/

		# add whc会员中心 银行卡相关 2017/9/7
        route::get('bank.html', [ 'uses' => 'topwap_ctl_member_bindbank@index']);  #绑定银行卡页面）
        route::get('bankList.html', [ 'uses' => 'topwap_ctl_member_bindbank@bankList']);  #个人银行卡列表页面）
        route::get('member-ticket.html', [ 'uses' => 'topwap_ctl_member@ticket']);  #个人银行卡列表页面）
        route::match(array('GET', 'POST'), 'bindbank.html', ['uses' => 'topwap_ctl_member_bindbank@bind']);
        route::match(array('GET', 'POST'), 'unlasing.html', ['uses' => 'topwap_ctl_member_bindbank@unlasing']);
		#银行卡相关结束 2017/9/7

        #会员中心  卡券相关
        route::get('coupon-voucherlist.html', [ 'uses' => 'topwap_ctl_coupon_voucher@index']);  #会员中心卡券列表）
        route::get('coupon-voucher-list.html', [ 'uses' => 'topwap_ctl_coupon_voucher@voucherList']);  #会员中心卡券列表(tab)
        route::get('coupon-voucher-detail.html', [ 'uses' => 'topwap_ctl_coupon_voucher@detail' ]);    //卡券详情
        route::match(array('GET', 'POST'),'voucher-getewm.html', [ 'uses' => 'topwap_ctl_coupon_voucher@getewm' ]);    //卡券详情
        route::get('ajaxvouchershow.html', [ 'uses' => 'topwap_ctl_coupon_voucher@ajaxVoucherShow' ]);
        route::match(array('GET', 'POST'),'giveVoucher.html', [ 'uses' => 'topwap_ctl_coupon_voucher@giveVoucher' ]);              //赠送卡券的逻辑
		route::match(array('GET', 'POST'),'revokeVoucher.html', [ 'uses' => 'topwap_ctl_coupon_voucher@revokeVoucher' ]);    //撤销赠送的逻辑

        /*add_2018/01/15_by_fanglongji_start*/
        //获取
        route::get('offline-voucher-detail.html', [ 'uses' => 'topwap_ctl_member_agentvocher@detail' ]);    //线下卡券详情
        route::get('agentVoucherList.html', [ 'uses' => 'topwap_ctl_member_agentvocher@index']);  #会员中心线下店消费卡券）
        route::get('ajaxAgentVoucherShow.html', [ 'uses' => 'topwap_ctl_member_agentvocher@ajaxVoucherShow' ]);
        route::match(array('GET', 'POST'),'ajaxComputeOfflinePayMoney.html', [ 'uses' => 'topwap_ctl_offlinepay_pay@ajaxGetNeedPayPrice' ]);  //ajax计算线下支付的费用
        route::match(array('GET', 'POST'),'ajaxGetAllHoldPrice.html', [ 'uses' => 'topwap_ctl_offlinepay_pay@ajaxGetAllHoldPrice' ]);  //ajax计算线下全场活动的费用
        route::match(array('GET', 'POST'),'getOfflinePayCoupon.html', [ 'uses' => 'topwap_ctl_offlinepay_pay@getOfflinePayCoupon' ]);  //获取该线下店卡券列表
        route::match(array('GET', 'POST'),'ajaxGetOfflineCoupon.html', [ 'uses' => 'topwap_ctl_offlinepay_pay@ajaxGetOfflineCoupon' ]);  //ajax获取该线下店卡券列表
        route::match(array('GET', 'POST'),'ajaxGetOfflineTrade.html', [ 'uses' => 'topwap_ctl_member_offlinepay@ajaxGetOfflineTrade' ]);  //ajax获取该线下订单列表
        /*add_2018/01/15_by_fanglongji_end*/

        # 店铺会员权益 相关
        // 王衍生-2018/09/14-start
        // 会员权益说明页
        route::match(array('GET', 'POST'),'member-benefit-rule.html', [ 'uses' => 'topwap_ctl_member_benefit@benefitRule' ]);
        // 会员权益列表页
        route::match(array('GET', 'POST'),'member-benefit-list.html', [ 'uses' => 'topwap_ctl_member_benefit@benefitList' ]);
        // 会员领取权益礼包
        route::post('member-benefit-get.html', [ 'uses' => 'topwap_ctl_member_benefit@ajaxGetBenefit' ]);

        route::match(array('GET', 'POST'),'member-lijin-index.html', [ 'uses' => 'topwap_ctl_member_lijin@index' ]);
        route::match(array('GET', 'POST'),'member-lijin-log.html', [ 'uses' => 'topwap_ctl_member_lijin@ajaxLijin' ]);

    });
    /*add_2018/03/05_by_fanglongji_start*/
    route::group(array('middleware' => 'topwap_middleware_authAndBack'), function() {
        //获取
        route::match(array('GET', 'POST'),'offlinePayInfo.html', [ 'uses' => 'topwap_ctl_offlinepay_pay@qrcodeRedict' ]);    //撤销赠送的逻辑
        route::match(array('GET', 'POST'),'createOfflineTrade.html', [ 'uses' => 'topwap_ctl_offlinepay_pay@createOfflineTrade' ]);  //线下扫码支付界面
        route::match(array('GET', 'POST'),'OfflineDoPay.html', [ 'uses' => 'topwap_ctl_offlinepay_pay@dopay' ]);  //线下扫码选择支付界面
        route::match(array('GET', 'POST'),'ApiOfflineDoPay.html', [ 'uses' => 'ectools_api_payment_offlinepay@doPay' ]);  //线下扫码选择支付界面
        route::match(array('GET', 'POST'),'OfflineIndex.html', [ 'uses' => 'topwap_ctl_offlinepay_pay@index' ]);  //订单创建完成跳转路由
        route::match(array('GET', 'POST'),'OfflinePayFinish.html', [ 'uses' => 'topwap_ctl_offlinepay_pay@payFinish' ]);  //订单创建完成跳转路由
        route::match(array('GET', 'POST'),'offlinePayDetail.html', [ 'uses' => 'topwap_ctl_member_offlinepay@payInfo' ]);  //我的线下订单
        route::match(array('GET', 'POST'),'rushTickets.html', [ 'uses' => 'topwap_ctl_offlinepay_rushTickets@rushTichets' ]);  //线下的无偿券领取
        route::match(array('GET', 'POST'),'freeTicketsFinish.html', [ 'uses' => 'topwap_ctl_paycenter@freeTicketsFinish' ]);  //线下的无偿券领取成功页面
        route::match(array('GET', 'POST'),'offlineTradeCancel.html', [ 'uses' => 'topwap_ctl_member_offlinepay@cancel' ]);  //线下订单取消原因界面
        route::match(array('GET', 'POST'),'offlineTradeBuyerCancel.html', [ 'uses' => 'topwap_ctl_member_offlinepay@cancelBuyer' ]);  //线下订单取消逻辑
        route::match(array('GET', 'POST'),'offlineTradeRePay.html', [ 'uses' => 'topwap_ctl_offlinepay_pay@createPay' ]);  //线下订单重新支付
    });
    /*add_2018/03/05_by_fanglongji_end*/
    /*add_2017/11/24_by_wanghaichao_start*/
	route::get('give.html', [ 'uses' => 'topwap_ctl_give@index' ]);  //卡券领取页面
	route::match(array('GET', 'POST'),'getVoucher.html', [ 'uses' => 'topwap_ctl_give@getVoucher' ]);  //卡券领取逻辑
	/*add_2017/11/24_by_wanghaichao_end*/


    // 促销商品列表页
    route::get('promotion-item.html', [ 'as' => 'promotion.item', 'uses' => 'topwap_ctl_promotion@getPromotionItem' ]);
    // 促销专题页
    route::get('promotion-page/{page_id}.html', [ 'uses' => 'topwap_ctl_promotion@ProjectPage' ]);
    // ajax获取促销关联的商品列表
    route::get('ajax-promotion-item.html', [ 'as' => 'ajax-promotion.item', 'uses' => 'topwap_ctl_promotion@ajaxGetPromotionItem' ]);

	/*add_2018/1/31_by_wanghaichao_start*/
	route::get('receive-coupon.html', [ 'uses' => 'topwap_ctl_promotion@getCoupon' ]);
	/*add_2018/1/31_by_wanghaichao_end*/

    // 优惠券关联商品列表页
    route::get('promotion-coupon-item.html', [ 'uses' => 'topwap_ctl_promotion@getCouponItem' ]);


    //刮刮卡页面
    route::get('promotion-scratchcard.html', [ 'uses' => 'topwap_ctl_promotion_scratchcard@index' ]);
    route::post('promotion-scratchcard-receive.html', [ 'uses' => 'topwap_ctl_promotion_scratchcard@receive', 'middleware' => 'topwap_middleware_authenticate' ]);
    route::post('promotion-scratchcard-exchange.html', [ 'uses' => 'topwap_ctl_promotion_scratchcard@exchange' ]);

    route::get('item-detail.html', [ 'as' => 'item.detail', 'uses' => 'topwap_ctl_item_detail@index' ]);
	/*add_2017/12/15_by_wanghaichao_start*/
    // 获取商品关联的组合促销
    route::get('item-package.html', ['as' =>'item.package', 'uses' => 'topwap_ctl_item_detail@getPackage' ]);
    // 异步获取商品的规格信息
    route::match(['GET','POST'],'item-getItemSpec.html', ['as' =>'item.getItemSpec', 'uses' => 'topwap_ctl_item_detail@getItemSpec' ]);
	/*add_2017/12/15_by_wanghaichao_end*/

    route::get('item-itempic.html', [ 'uses' => 'topwap_ctl_item_detail@itemPic' ]);
    route::match(['GET','POST'],'item-list.html', [ 'as' => 'item.list', 'uses' => 'topwap_ctl_item_list@index' ]);
    route::match(['GET','POST'],'item-list-ajax.html', [ 'as' => 'item.ajax.list', 'uses' => 'topwap_ctl_item_list@ajaxGetItemList' ]);
	/*add_2018/5/28_by_wanghaichao_start 用于列表页加入购物车的*/
    route::match(['GET','POST'],'wapitem-miniSpec.html', [ 'as' => 'item.miniSpec', 'uses' => 'topwap_ctl_item_list@miniSpec' ]);

	/*add_2018/5/28_by_wanghaichao_end*/

    //微信自定义分享
    route::get('wxshare.html', [ 'uses' => 'topwap_ctl_jssdk@index' ]);

    // 商品详情页，评价列表
    route::get('item-rate.html', [ 'uses' => 'topwap_ctl_item_detail@getItemRate' ]);
    route::get('item-rate-list.html', [ 'uses' => 'topwap_ctl_item_detail@getItemRateList' ]);
    // 商品详情页,到货通知
    route::get('notify-item.html', [ 'uses' => 'topwap_ctl_item_detail@viewNotifyItem' ]);
    route::post('user-item.html', [ 'uses' => 'topwap_ctl_item_detail@userNotifyItem' ]);

    //登录相关
    route::get('passport-gologin.html', [ 'as' => 'go.login', 'uses' => 'topwap_ctl_passport@goLogin' ]);
    route::post('passport-dologin.html', [ 'as' => 'do.login', 'uses' => 'topwap_ctl_passport@doLogin' ]);

    //注册相关
    route::match(array('GET', 'POST'),'passport-goregister.html', [ 'as' => 'go.register', 'uses' => 'topwap_ctl_passport@goRegister' ]);
    route::post('passport-checkuname.html', [ 'as' => 'checkuname', 'uses' => 'topwap_ctl_passport@checkUname' ]);
    route::post('passport-doregister.html', [ 'as' => 'do.register', 'uses' => 'topwap_ctl_passport@doRegister' ]);
    route::get('passport-register-succ.html', [ 'as' => 'register.succ', 'uses' => 'topwap_ctl_passport@registerSucc' ]);
    route::get('passport-register-license.html', [ 'as' => 'register.license', 'uses' => 'topwap_ctl_passport@registerLicense' ]);

    //会员退出
    route::get('passport-logout.html', [ 'as' => 'logout', 'uses' => 'topwap_ctl_passport@logout' ]);

    //找回密码相关
    //去找密码
    route::get('passport-gofindpwd.html', [ 'as' => 'gofindpwd', 'uses' => 'topwap_ctl_passport@goFindPwd' ]);
    //验证用户名
    route::post('passport-verify-uname.html', [ 'as' => 'verify.uname', 'uses' => 'topwap_ctl_passport@verifyUsername']);
    route::post('passport-sendvcode.html', [ 'as' => 'send.vcode', 'uses' => 'topwap_ctl_passport@sendVcode']);
    //验证手机验证码
    route::post('passport-verify-vcode.html', [ 'as' => 'verify.uname', 'uses' => 'topwap_ctl_passport@verifyVcode']);
    //设置新密码
    route::post('passport-setting-pwd.html', [ 'as' => 'setting-pwd', 'uses' => 'topwap_ctl_passport@settingPwd']);

    route::get('trustlogin-bind.html', [ 'uses' => 'topwap_ctl_trustlogin@callback' ]);
    route::post('trustlogin-exists.html', [ 'uses' => 'topwap_ctl_trustlogin@bindExistsUser' ]);
    route::post('trustlogin-signup.html', [ 'uses' => 'topwap_ctl_trustlogin@bindSignupUser' ]);
    // start add 王衍生 20170924
    route::post('trustlogin-sendVcode.html', [ 'uses' => 'topwap_ctl_trustlogin@sendVcode' ]);
    route::get('thirdparty-info.html', [ 'uses' => 'topwap_ctl_trustlogin@thirdpartyCallback' ]);
    // end add 王衍生 20170924
    // 会员订单

    // 支付中心
    route::get('select-hongbao.html', [ 'uses' => 'topwap_ctl_paycenter@selectHongbao' ]);
    route::post('save-hongbao.html', [ 'uses' => 'topwap_ctl_paycenter@saveHongbao' ]);
    route::get('payment.html', [ 'uses' => 'topwap_ctl_paycenter@index' ]);
    route::match(array('GET', 'POST'), 'create.html', ['uses' => 'topwap_ctl_paycenter@createPay']);
    route::post('do-payment.html', [ 'uses' => 'topwap_ctl_paycenter@dopayment' ]);
    // 此路由微信H5支付后get刷新支付请求 特殊处理 其它此路由post请求请看上面的设置
    // route::get('do-payment.html', [ 'uses' => 'topwap_ctl_paycenter@finish' ]);

    route::get('finish.html', [ 'uses' => 'topwap_ctl_paycenter@finish' ]);
    //临时测试路由
    route::get('createTestVoucher.html', [ 'uses' => 'topwap_ctl_paycenter@testCreateVoucher' ]);
    //刮刮卡
    route::post('ajax/getprize.html', [ 'uses' => 'topwap_ctl_paycenter@getPrize' ]);
    route::post('ajax/issueprize.html', [ 'uses' => 'topwap_ctl_paycenter@issue' ]);


    // 微信的数据做转发
    route::match(array('GET', 'POST', 'PUT', 'DELETE'), 'wxpayjsapi.html', ['uses' => 'topwap_ctl_wechat@wxpayjsapi']);
    // 微信的数据做转发（线下支付）
    route::match(array('GET', 'POST', 'PUT', 'DELETE'), 'wxofflinepayjsapi', ['uses' => 'topwap_ctl_wechat@wxofflinepayjsapi']);
	/*add_2018/1/23_by_wanghaichao_start*/
	//动力传媒支付
    route::match(array('GET', 'POST', 'PUT', 'DELETE'), 'lease_wxpayjsapi.html', ['uses' => 'topwap_ctl_wechat@lease_wxpayjsapi']);
	/*add_2018/1/23_by_wanghaichao_end*/


    //平台类目列表
    route::get('category.html',['uses'=>'topwap_ctl_category@index']);

    //购物车首页
    route::get('cart.html',['uses'=>'topwap_ctl_cart@index']);
    route::post('cart-add.html', [ 'uses' => 'topwap_ctl_cart@addCart' ]); #加入购物车



    route::post('cart-update.html', [ 'uses' => 'topwap_ctl_cart@updateCart' ]); #更新购物车
    route::post('cart-remove.html', [ 'uses' => 'topwap_ctl_cart@removeCart' ]); #更新购物车
    route::post('cart-ajaxbasecart.html', [ 'uses' => 'topwap_ctl_cart@ajaxBasicCart' ]); #更新购物车

    route::post('cart-ajaxItemgetpromotion.html', [ 'uses' => 'topwap_ctl_cart@ajaxGetItemPromotion' ]); #购物车请求商品促销

    //会员模块相关
    route::group(array('middleware' => 'topwap_middleware_authenticate'), function() {

        route::match(array('GET','POST'),'cart-checkout.html',['uses' => 'topwap_ctl_cart_checkout@index']);  #立即购买
        route::get('cart-addrlist.html', [ 'uses' => 'topwap_ctl_cart_checkout@addrList' ]); #结算页获取收货地址列表
        route::post('cart-delivery.html', [ 'uses' => 'topwap_ctl_cart_checkout@deliveryList' ]); #结算页支付方式和配送方式列表
        route::get('trade-ziti.html', [ 'uses' => 'topwap_ctl_cart_checkout@getZitiList' ]); #生成订单

        route::get('cart-get-coupon.html', [ 'uses' => 'topwap_ctl_cart_checkout@getCouponList' ]); #结算页获取收货地址列表
        route::post('cart-total.html', [ 'uses' => 'topwap_ctl_cart_checkout@total' ]); #结算页获取收货地址列表
        route::post('cart-user-coupon.html', [ 'uses' => 'topwap_ctl_cart_checkout@useCoupon' ]); #结算页获取收货地址列表
        route::post('cart-cancel-coupon.html', [ 'uses' => 'topwap_ctl_cart_checkout@cancelCoupon' ]); #结算页获取收货地址列表
        route::post('cart-get-userpoint.html', [ 'uses' => 'topwap_ctl_cart_checkout@ajaxGetUserPoint' ]); #购物车获取会员积分
        route::post('cart-get-userlijin.html', [ 'uses' => 'topwap_ctl_cart_checkout@ajaxGetUserLijin' ]); #购物车获取会员积分

        route::post('cart-getVoucher.html', [ 'uses' => 'topwap_ctl_cart_checkout@getVouchers' ]); #获取用户的购物券
        route::post('cart-useVoucher.html', [ 'uses' => 'topwap_ctl_cart_checkout@useVoucher' ]); #获取用户的购物券

        route::post('trade-create.html', [ 'uses' => 'topwap_ctl_trade@create' ]); #结算页创建订单
    });

    /*
    |--------------------------------------------------------------------------
    | 文章相关
    |--------------------------------------------------------------------------
    */
    route::group(array(), function() {
        route::get('content-node-index.html', [ 'uses' => 'topwap_ctl_content@index' ]);
        route::get('content-node-child-list.html', [ 'uses' => 'topwap_ctl_content@childNodeList' ]);
        route::get('content-list.html', [ 'uses' => 'topwap_ctl_content@contentList' ]);
        route::get('content-info.html', [ 'uses' => 'topwap_ctl_content@getContentInfo']);
        route::post('ajax-content-list.html', [ 'uses' => 'topwap_ctl_content@ajaxContentList']);
        route::get('shop-article.html', [ 'uses' => 'topwap_ctl_content@shopArticle' ]);
    });

    # 进行中的活动首页
    route::get('activity-index.html', [ 'as' => 'activity.list.index', 'uses' => 'topwap_ctl_activity@active_list' ]);
    # 即将开始的活动
    route::get('activity-comming.html', [ 'as' => 'activity.list.comming', 'uses' => 'topwap_ctl_activity@comming_list' ]);
    # 活动详情
    route::get('activity-detail.html', [ 'as' => 'activity.list.detail', 'uses' => 'topwap_ctl_activity@detail' ]);
    # 活动的商品列表
    route::get('activity-itemlist.html', [ 'as' => 'activity.list.itemlist', 'uses' => 'topwap_ctl_activity@itemlist' ]);
    # 活动的商品详情
    route::get('activity-itemdetail.html', [ 'as' => 'activity.list.itemdetail', 'uses' => 'topwap_ctl_activity@itemdetail' ]);
    # 活动开售提醒
    route::get('activity-remind.html', [ 'as' => 'activity.list.remind', 'uses' => 'topwap_ctl_activity@remind' ]);
    # 保存订阅活动开售提醒信息
    route::post('activity-saveRemind.html', [ 'as' => 'activity.list.saveRemind', 'uses' => 'topwap_ctl_activity@saveRemind' ]);

    route::get('voucher-list.html',['as' => 'voucher.list','uses' => 'topwap_ctl_voucher@voucherList']);
    route::get('voucher-ajax.html',['as' => 'voucher.ajax','uses' => 'topwap_ctl_voucher@ajaxGetVoucherItem']);
	/*add_2017/9/26_by_wanghaichao_start*/
	#商品分享图片
	route::get('share.html', ['uses' => 'topwap_ctl_share@share']);
	/*add_2017/9/26_by_wanghaichao_end*/
	/*add_2017/10/12_by_wanghaichao_start 投票活动相关*/
	route::get('vote.html', [ 'uses' => 'topwap_ctl_activityvote_vote@index']);  #投票活动首页
	route::get('votecat.html', [ 'uses' => 'topwap_ctl_activityvote_vote@cat']);  #投票活动类别页
	route::get('votelist.html', [ 'uses' => 'topwap_ctl_activityvote_vote@voteList']);  #投票活动类别页
	route::get('ajaxVoteList.html', [ 'uses' => 'topwap_ctl_activityvote_vote@ajaxVoteList']);  #ajax获取列表
	route::match(array('GET','POST'),'ajaxGameVote.html', [ 'uses' => 'topwap_ctl_activityvote_vote@ajaxGameVote']);  #进行投票
	route::match(array('GET','POST'),'ajaxGetGift.html', [ 'uses' => 'topwap_ctl_activityvote_vote@ajaxGetGift']);  #获得赠品
	/*add__by_wanghaichao_end*/
	/*add_2017-11-22_by_xinyufeng_start*/
	route::get('voterule.html', [ 'uses' => 'topwap_ctl_activityvote_vote@voteRule']);  #投票活动规则
	route::get('giftrule.html', [ 'uses' => 'topwap_ctl_activityvote_vote@giftRule']);  #投票活动规则
	route::get('top-ad-detail.html', [ 'uses' => 'topwap_ctl_activityvote_vote@topAdDetail']);  #头部下拉广告详情
	route::get('top-ad-slide-detail.html', [ 'uses' => 'topwap_ctl_activityvote_vote@topAdSlideDetail']);  #头部slide广告详情
	/*add_2017-11-22_by_xinyufeng_end*/
    /*add_2017/10/17_by_gurundong_start*/
    route::get('activityvote-detail.html', [ 'uses' => 'topwap_ctl_activityvote_votedetail@detail']);  #投票详情页
    route::post('activityvote-expertValid.html', [ 'uses' => 'topwap_ctl_activityvote_votedetail@validateExpert']);  #专家投票验证
    route::post('activityvote-timeValid.html', [ 'uses' => 'topwap_ctl_activityvote_votedetail@validateTime']);  #专家投票验证
    route::post('activityvote-postComment.html', [ 'uses' => 'topwap_ctl_activityvote_votedetail@postComment']);  #专家投票提交
    route::get('activityvote-ajaxCommentList.html', [ 'uses' => 'topwap_ctl_activityvote_votedetail@ajaxCommentList']);  #ajax评审列表
    route::get('activityvote-expertList.html', [ 'uses' => 'topwap_ctl_activityvote_expert@expertList']);  #专家评审列表
    route::get('activityvote-expertDetail.html', [ 'uses' => 'topwap_ctl_activityvote_expert@expertDetail']);  #专家评审详情
    route::get('vote-results.html', [ 'uses' => 'topwap_ctl_activityvote_vote@voteResults']);  #投票活动规则
    /*add_2017/10/17_by_gurundong_end*/

	/*add_2017/12/21_by_wanghaichao_start*/
	//964福袋活动
    // route::get('lighticon.html', [ 'uses' => 'topwap_ctl_actlighticon_lighticon@index']); #活动首页
    // route::get('lighticon-light-up.html', [ 'uses' => 'topwap_ctl_actlighticon_lighticon@lightUp']); #活动点亮页面
    // route::post('lighticon-light-post.html', [ 'uses' => 'topwap_ctl_actlighticon_lighticon@lightUpDo']); #活动点亮操作
    // route::get('lighticon-info.html', [ 'uses' => 'topwap_ctl_actlighticon_lighticon@info']); #填入个人信息页面
	// route::post('lighticon-savePart.html', [ 'uses' => 'topwap_ctl_actlighticon_lighticon@savePart']);
	// route::get('lighticon-congratulations.html', [ 'uses' => 'topwap_ctl_actlighticon_lighticon@congratulations']);  #全部点亮页面
	// route::get('lighticon-gift-image.html', [ 'uses' => 'topwap_ctl_actlighticon_lighticon@imgGift']);  #在线图片奖品
	/*add_2017/12/21_by_wanghaichao_end*/



    /*add_201711071603_by_wudi_start:促销活动单页*/
    route::get('sales.html', [ 'uses' => 'topwap_ctl_sales@index']);  #活动单页
    /*add_201711071603_by_wudi_end:促销活动单页*/
	/*add_2017-12-11_by_xinyufeng_start*/
	route::get('foodmap.html', [ 'uses' => 'topwap_ctl_foodmap_index@home']);  #美食地图首页
	route::get('foodmap-supplier-data.html', [ 'uses' => 'topwap_ctl_foodmap_index@supplier_data']);  #供应商数据
	route::get('foodmap-nav.html', [ 'uses' => 'topwap_ctl_foodmap_index@map_nav']);  #地图导航
	route::get('foodmap-red-envelope-data.html', [ 'uses' => 'topwap_ctl_foodmap_index@red_envelope_list']);  #地图红包数据
	/*add_2017-12-11_by_xinyufeng_end*/
	/*add_2018-01-08_by_xinyufeng_start*/
	route::get('store.html', [ 'uses' => 'topwap_ctl_store_index@home']);  #多店铺商城首页
	route::get('store-home-data.html', [ 'uses' => 'topwap_ctl_store_index@getAjaxHomeData']);  #多店铺商城首页数据
	/*add_2018-01-08_by_xinyufeng_end*/
	/*add_2018-11-14_by_xinyufeng_start*/
	route::get('maker.html', [ 'uses' => 'topwap_ctl_maker_index@home']);  #创客店铺首页
	/*add_2018-11-14_by_xinyufeng_end*/
    /*add_2018-11-16_by_zhangshu_start*/
    route::get('maker-item-list.html', ['uses' => 'topwap_ctl_maker_index@ajaxGetSellerItemList']); #创客店铺首页ajax获取商品
    /*add_2018-11-16_by_zhangshu_end*/
    
	//所有米粒前台路由
	route::group(['prefix'=>'rice'],function (){
        /*add_2018-05-15_by_jiangyunhan_start*/
        route::get('supplier.html', ['as' => 'wap.rice.supplier', 'uses' => 'topwap_ctl_supplier_index@home']);  #米粒儿线下店网站首页
        route::get('supplier-home-data.html', [ 'uses' => 'topwap_ctl_supplier_index@getAjaxHomeData']);  #米粒儿线下店网站首页数据
        route::get('supplier-home-tag-data.html', [ 'uses' => 'topwap_ctl_supplier_index@getAjaxHomeTagData']);  #米粒儿线下店网站首页搜索分类标签
        route::get('supplier-home-search.html', [ 'uses' => 'topwap_ctl_supplier_index@search']);#米粒儿线下店网站首页搜索框
        route::get('supplier-home-search-data.html', [ 'uses' => 'topwap_ctl_supplier_index@getAjaxSearchData']);  #米粒儿线下店网站搜索框根据关键字获取的线下店数据
        route::get('supplier-category-list.html', [ 'uses' => 'topwap_ctl_supplier_index@agentCatList']);  #米粒儿分类线下店列表
        route::get('supplier-category-list-data.html', [ 'uses' => 'topwap_ctl_supplier_index@getAjaxAgentCatData']);  #米粒儿分类线下店列表数据
        route::get('supplier-home-user-gps.html', [ 'uses' => 'topwap_ctl_supplier_index@setUserGPS']);  #米粒儿首页获取用户经纬度并存入session
        route::get('supplier-pubaccount.html', [ 'uses' => 'topwap_ctl_supplier_pubAccount@index']);  #米粒儿订阅号列表
        route::get('supplier-pubaccount-data.html', [ 'uses' => 'topwap_ctl_supplier_pubAccount@getAjaxData']);  #米粒儿订阅号列表数据
        /*add_2018-05-15_by_jiangyunhan_end*/
        /*add_2018-05-18_by_fanglongji_start*/
        route::get('agent_shop_detail.html', ['as' => 'wap.rice.agent_shop_detail', 'uses' => 'topwap_ctl_supplier_agentShopInfo@agentShopDetail']);  #米粒儿线下店详情页
        route::get('agent_shop/expert-list.html', [ 'uses' => 'topwap_ctl_supplier_expert@expertCommentList']);  #米粒儿线下店专家评审页
        route::get('agent_shop/expert-ajax-list.html', [ 'uses' => 'topwap_ctl_supplier_expert@ajaxCommentList']);  #米粒儿线下店ajax专家评审页
        route::get('agent_shop/food-list.html', ['as' => 'wap.rice.food_list', 'uses' => 'topwap_ctl_supplier_food@foodList']);  #米粒儿线下店推荐菜品页
        route::get('agent_shop/food-ajax-list.html', [ 'uses' => 'topwap_ctl_supplier_food@ajaxFoodList']);  #米粒儿线下店ajax推荐菜品页
        route::post('agent_shop/food-ajax-click.html', [ 'uses' => 'topwap_ctl_supplier_food@ajaxClick']);  #米粒儿线下店ajax点赞菜品
        route::get('agent_shop/person-list.html', [ 'uses' => 'topwap_ctl_supplier_person@personList']);  #米粒儿线下店人员页
        route::get('agent_shop/person-ajax-list.html', [ 'uses' => 'topwap_ctl_supplier_person@ajaxPersonList']);  #米粒儿线下店ajax人员页
        route::get('agent_video_index.html', [ 'uses' => 'topwap_ctl_supplier_agentVideoList@agentVideoList']);  #米粒儿视频首页
        route::get('agent_video_list.html', [ 'uses' => 'topwap_ctl_supplier_agentVideoList@ajaxGetVideoList']);  #ajax获取米粒儿视频
        route::get('buyer_index.html', [ 'uses' => 'topwap_ctl_supplier_buyer@buyerIndex']);  #买手首页
        route::get('buyer_detail.html', [ 'uses' => 'topwap_ctl_supplier_buyer@buyerDetail']);  #买手首页
        route::get('buyer_list.html', [ 'uses' => 'topwap_ctl_supplier_buyer@ajaxGetBuyerList']);  #买手列表
        route::get('buyer_laud.html', [ 'uses' => 'topwap_ctl_supplier_buyer@laud']);  #买手点赞
    });
	//所有米粒小程序路由
    route::group(['prefix'=>'miniprogram'],function(){
        route::get('trustlogin/callback',['uses'=>'topwap_ctl_miniprogram_trustlogin@callback']);
        route::get('trustlogin/sendVcode',['uses'=>'topwap_ctl_miniprogram_trustlogin@sendVcode']);
        route::get('trustlogin/bind',['uses'=>'topwap_ctl_miniprogram_trustlogin@bind']);
        //活动详情
        route::get('active_detail.html',['uses'=>'topwap_ctl__miniprogram_item@activeDetail']);
        //小程序首页商品列表
        route::get('goods/list',['uses'=>'topwap_ctl_miniprogram_goods@goodsList']);
        //小程序首页banner图片列表
        route::get('goods/banner_list',['uses'=>'topwap_ctl_miniprogram_goods@bannerList']);
        //小程序推荐商品列表
        route::get('goods/recommendlist',['uses'=>'topwap_ctl_miniprogram_goods@recommendList']);

        route::group(['middleware'=>['topwap_middleware_miniAuth']],function(){
            //商品详情
            route::get('item_detail.html',['uses'=>'topwap_ctl__miniprogram_item@itemDetail']);
            //小程序设置用户的经纬度
            route::get('setUserGPS',['uses'=>'topwap_ctl_miniprogram_goods@setUserGPS']);
            route::get('trade/ajaxTradeList',['uses'=>'topwap_ctl_miniprogram_trade@ajaxTradeList']); #我的-订单列表
            route::get('trade/ajaxTradeDetail',['uses'=>'topwap_ctl_miniprogram_trade@ajaxTradeDetail']); #我的-订单详情
            route::get('favorite/ajaxitems',['uses'=>'topwap_ctl_miniprogram_favorite@ajaxitems']);
			route::get('favorite/ajaxDelItem',['uses'=>'topwap_ctl_miniprogram_favorite@ajaxDelItem']);
            //券包入口页
            route::get('voucher/index.html',['uses'=>'topwap_ctl__miniprogram_member@ticket']);
            //限量购
            route::get('voucher/online_voucher_list.html',['uses'=>'topwap_ctl_miniprogram_onlineVoucher@voucherList']);
            route::get('voucher/ajax_online_voucher_list.html',['uses'=>'topwap_ctl_miniprogram_onlineVoucher@ajaxVoucherList']);
            route::get('voucher/online_voucher_give.html',['uses'=>'topwap_ctl_miniprogram_onlineVoucher@giveVoucher']);
            route::get('voucher/online_voucher_revoke.html',['uses'=>'topwap_ctl_miniprogram_onlineVoucher@revokeVoucher']);
            //买单劵
            route::get('offline/ajaxVoucherShow',['uses'=>'topwap_ctl_miniprogram_offline@ajaxVoucherShow']);   #券包-买单券
            route::get('offline/ajaxOfflinePay',['uses'=>'topwap_ctl_miniprogram_offline@ajaxOfflinePay']); #我的-我的买单
            route::get('offline/createPay',['uses'=>'topwap_ctl_miniprogram_offline@createPay']);   #我的-我的买单-去付款
            route::get('offline/pay_index',['uses'=>'topwap_ctl_offlinepay_pay@indexMini','as'=>'wap.mini.pay_index']);   #我的-我的买单-去付款
            //下单页
            route::post('mini_cart_add.html', [ 'uses' => 'topwap_ctl_cart@addMiniCart' ]); #加入购物车
            route::post('mini_trade_create.html', [ 'uses' => 'topwap_ctl_trade@miniCreate' ]); #结算页创建订单
            route::match(array('GET','POST'),'mini_cart_checkout.html',['uses' => 'topwap_ctl_cart_checkout@miniOrder']);
            //支付页面
            route::get('mini_payment.html',['uses' => 'topwap_ctl_paycenter@miniPay']);
            route::get('mini_do_payment.html',['uses' => 'topwap_ctl_paycenter@miniDoPayment']);
            route::get('mini_pay_finish.html',['uses' => 'topwap_ctl_paycenter@miniFinish']);
            route::get('mini_pay_create.html',['uses' => 'topwap_ctl_paycenter@createMiniPay']);
            //抢购无偿券
            route::match(array('GET','POST'),'rushTickets.html',['uses' => 'topwap_ctl_offlinepay_rushTickets@miniRushTichets']);
            //收藏
            route::match(array('GET','POST'),'mini_item_collect.html',['uses' => 'topwap_ctl__miniprogram_item@ajaxAddItemCollect']);

        });
    });
    /*add_2018-05-18_by_fanglongji_end*/
	/*add_2018/1/12_by_wanghaichao_start*/
	//防止和之前的冲突,store的列表
	route::match(['GET','POST'], 'store-list-index.html', [ 'as' => 'shop.list.index', 'uses' => 'topwap_ctl_shop_list@store_list' ]);
    /*add_2018/1/12_by_wanghaichao_end*/

    route::get('lottery.html', ['uses' => 'topwap_ctl_lottery@index' ]);
    route::post('ajax/lottery-prize.html',['middleware' => 'topwap_middleware_authenticate', 'uses' => 'topwap_ctl_lottery@getPrize' ]);
    route::post('lottery-exchangenum.html',['middleware' => 'topwap_middleware_authenticate', 'uses' => 'topwap_ctl_lottery@getExchangeNum' ]);
    route::post('lottery-share-collback.html',['middleware' => 'topwap_middleware_authenticate', 'uses' => 'topwap_ctl_lottery@onMenuShareCollback' ]);
    // route::post('lottery-infoDialog.html', ['middleware' => 'topwap_middleware_authenticate', 'uses' => 'topwap_ctl_lottery@lottery_info_dialog']); #收货地址弹框
    // 会员中心转盘抽奖奖品列表页
    route::get('member-lottery.html', ['middleware' => 'topwap_middleware_authenticate', 'uses' => 'topwap_ctl_member_lottery@prizeList']);
    route::get('member-lottery-list.html', ['middleware' => 'topwap_middleware_authenticate', 'uses' => 'topwap_ctl_member_lottery@ajaxGetprizeList']);

    route::get('member-lottery-addrList.html', ['middleware' => 'topwap_middleware_authenticate', 'uses' => 'topwap_ctl_member_lottery@addrList']);

});
/*
|--------------------------------------------------------------------------
| 商家管理中心
|--------------------------------------------------------------------------
*/

//商家入驻
route::group(array('prefix' => 'shop/register'), function() {
    //注册检查手机号
    route::get('signCheckPhone.html',  [ 'as' => 'topshop.register.signCheckPhonePage',              'uses' => 'topshop_ctl_register@signCheckPhonePage' ]);
    route::post('signCheckPhone.html', [ 'as' => 'topshop.register.signCheckPhoneActioin',           'uses' => 'topshop_ctl_register@signCheckPhoneAction' ]);
    //发送注册的短信验证码
    route::post('sendSms.html',        [ 'as' => 'topshop.register.sendSms',                         'uses' => 'topshop_ctl_register@sendSms' ]);
    //注册页面
    route::get('sign.html',            [ 'as' => 'topshop.register.signPage',                        'uses' => 'topshop_ctl_register@signPage' ]);
    route::post('sign.html',           [ 'as' => 'topshop.register.signAction',                      'uses' => 'topshop_ctl_register@signAction' ]);
    route::group(['middleware'=>['topshop_middleware_permission', 'topshop_middleware_register']], function(){

        //入驻协议
        route::get('agrement.html',        [ 'as' => 'topshop.register.enterAgreementPage',              'uses' => 'topshop_ctl_register@enterAgreementPage' ]);
        //入主流程-公司基本信息
        route::get('companyInfo.html',     [ 'as' => 'topshop.register.enterProcessCompanyInfoPage',     'uses' => 'topshop_ctl_register@enterProcessCompanyInfo' ]);
        route::post('companyInfo.html',    [ 'as' => 'topshop.register.enterProcessCompanyInfoAction',   'uses' => 'topshop_ctl_register@enterProcessCompanyInfoAction' ]);
        //入主流程-银行及税务信息
        route::get('economicInfo.html',    [ 'as' => 'topshop.register.enterProcessEconomicInfoPage',    'uses' => 'topshop_ctl_register@enterProcessEconomicInfo' ]);
        route::post('economicInfo.html',   [ 'as' => 'topshop.register.enterProcessEconomicInfoAction',  'uses' => 'topshop_ctl_register@enterProcessEconomicInfoAction' ]);
        //入驻流程-店铺信息
        route::get('shopInfo.html',        [ 'as' => 'topshop.register.enterProcessShopInfoPage',        'uses' => 'topshop_ctl_register@enterProcessShopInfo' ]);
        route::post('shopInfo.html',       [ 'as' => 'topshop.register.enterProcessShopInfoAction',      'uses' => 'topshop_ctl_register@enterProcessShopInfoAction' ]);
        //入驻流程-提交完成，等待审核
        route::get('waiteExamine.html',    [ 'as' => 'topshop.register.enterProcessWaiteExaminePage',    'uses' => 'topshop_ctl_register@enterProcessWaiteExamine' ]);
        //入驻流程-审核通过，等待签约
        route::get('waiteAward.html',      [ 'as' => 'topshop.register.enterProcessWaiteAwardPage',      'uses' => 'topshop_ctl_register@enterProcessWaiteAward' ]);
        //入驻流程-审核未通过的情况下，重新修改内容
        route::get('updateApply.html',     [ 'as' => 'topshop.register.enterProcessUpdate',              'uses' => 'topshop_ctl_register@enterProcessUpdateApply' ]);

    });
});

/*add_start_gurundong_2017/12/14*/
route::group(array('prefix'=>'cpshop','middleware'=>'topshop_middleware_permission'),function(){
    route::get('passport/signin.html', [ 'as' => 'topshop.signinCp', 'uses' => 'topshop_ctl_passport@signinCp', 'middleware' => 'topshop_middleware_redirectIfAuthenticated' ]);
    route::get('passport/logout.html', [ 'as' => 'topshop.logoutCp', 'uses' => 'topshop_ctl_passport@logoutCp' ]);
    route::post('passport/signin.html', [ 'as' => 'topshop.postsignincp', 'uses' => 'topshop_ctl_passport@loginCp' ]);
});
/*add_end_gurundong_2017/12/14*/

route::group(array('prefix' => 'shop','middleware' => 'topshop_middleware_permission'), function() {
    # 首页
    route::get('/', [ 'as' => 'topshop.home', 'uses' => 'topshop_ctl_index@index' ]);

    route::get('nopermission.html', [ 'as' => 'topshop.nopermission', 'uses' => 'topshop_ctl_index@nopermission' ]);
    route::get('onlySelfManagement.html', [ 'as' => 'topshop.onlySelfManagement', 'uses' => 'topshop_ctl_index@onlySelfManagement' ]);

    # 登录
    route::get('passport/signin-s.html', [ 'as' => 'topshop.simpleSignin', 'uses' => 'topshop_ctl_passport@simpleSignin' ]);
    route::get('passport/signin.html', [ 'as' => 'topshop.signin', 'uses' => 'topshop_ctl_passport@signin', 'middleware' => 'topshop_middleware_redirectIfAuthenticated' ]);
    route::post('passport/signin.html', [ 'as' => 'topshop.postsignin', 'uses' => 'topshop_ctl_passport@login' ]);
    # 注册
  //route::get('passport/signup.html', [ 'as' => 'topshop.signup', 'uses' => 'topshop_ctl_passport@signup', 'middleware' => 'topshop_middleware_redirectIfAuthenticated'  ]);
    route::post('passport/signup.html', [ 'as' => 'topshop.postsignup', 'uses' => 'topshop_ctl_passport@create' ]);
    # 退出
    route::get('passport/logout.html', [ 'as' => 'topshop.logout', 'uses' => 'topshop_ctl_passport@logout' ]);
    # 账户是否存在
    route::get('passport/isexists.html', [ 'as' => 'topshop.userexists', 'uses' => 'topshop_ctl_passport@isExists' ]);
	/*add_2017/9/23_by_wanghaichao_start*/
	#供应商账户检验是否存在
	route::get('passport/supplier_isexists.html', [ 'as' => 'topshop.userexists', 'uses' => 'topshop_ctl_passport@supplier_isExists' ]);
	/*add_2017/9/23_by_wanghaichao_end*/

    # 商家修改密码
    route::get('passport/update.html', [ 'as' => 'topshop.update', 'uses' => 'topshop_ctl_passport@update' ]);
    route::post('passport/update.html', [ 'as' => 'topshop.postupdatepwd', 'uses' => 'topshop_ctl_passport@updatepwd' ]);
    #促销管理
    #满减
    route::post('promotion/fullminusbrand.html', [ 'as' => 'topshop.promotion.fullminus', 'uses' => 'topshop_ctl_promotion_fullminus@getBrandList' ]);
    #组合促销
    route::post('promotion/packagebrand.html', [ 'as' => 'topshop.promotion.package', 'uses' => 'topshop_ctl_promotion_package@getBrandList' ]);
    #满折
    route::post('promotion/fulldiscountbrand.html', [ 'as' => 'topshop.promotion.fulldiscount', 'uses' => 'topshop_ctl_promotion_fulldiscount@getBrandList' ]);
    #优惠券
    route::post('promotion/couponbrand.html', [ 'as' => 'topshop.promotion.coupon', 'uses' => 'topshop_ctl_promotion_coupon@getBrandList' ]);
    #x件y折
    route::post('promotion/xydiscountbrand.html', [ 'as' => 'topshop.promotion.xydiscount', 'uses' => 'topshop_ctl_promotion_xydiscount@getBrandList' ]);
    # 不可报名活动详情
    route::get('activity/noregistered_detail.html', [ 'as' => 'topshop.activity.noregistered_detail', 'uses' => 'topshop_ctl_promotion_activity@noregistered_detail' ]);
    # 活动报名表单
    route::get('activity/canregistered_apply.html', [ 'as' => 'topshop.activity.canregistered_apply', 'uses' => 'topshop_ctl_promotion_activity@canregistered_apply' ]);
    route::get('activity/canregistered_detail.html', [ 'as' => 'topshop.activity.canregistered_detail', 'uses' => 'topshop_ctl_promotion_activity@canregistered_detail' ]);
    # 保存活动报名表单
    route::post('activity/canregistered_apply_save.html', [ 'as' => 'topshop.activity.canregistered_apply_save', 'uses' => 'topshop_ctl_promotion_activity@canregistered_apply_save' ]);
    route::post('activity/getSku.html', [ 'as' => 'topshop.activity.getSku', 'uses' => 'topshop_ctl_promotion_activity@getSkuData']);

    route::get('coupon/ajax/list.html', [ 'as' => 'topshop.coupon.ajaxCouponList', 'uses' => 'topshop_ctl_promotion_coupon@ajaxCouponList']);
    route::get('coupon/ajax/promotionsList.html', [ 'as' => 'topshop.coupon.ajaxPromotionList', 'uses' => 'topshop_ctl_promotion_promotions@ajaxPromotionList']);

    route::group(array('middleware' => 'topshop_middleware_enterapply'), function() {
        # 入驻申请-ajax请求类目下的品牌
        route::match(array('GET', 'POST'),'ajax/cat/brand.html', [ 'as' => 'topshop.ajax.cat.brand', 'uses' => 'topshop_ctl_enterapply@ajaxCatBrand' ]);
    });

    # 获取自然属性页面
    route::post('natureprops.html', [ 'as' => 'toputil.syscat.nature', 'uses' => 'topshop_ctl_sku@getNatureProps' ]);
    # 获取详细参数页面
    route::post('params.html', [ 'as' => 'toputil.syscat.params', 'uses' => 'topshop_ctl_sku@getParams' ]);
    # 获取销售属性页面
    route::post('spec/props.html', [ 'as' => 'toputil.syscat.spec.props', 'uses' => 'topshop_ctl_sku@getSpecProps' ]);
    # 获取销售属性选择信息
    route::post('spec/selectprops.html', [ 'as' => 'toputil.syscat.spec.selectprops', 'uses' => 'topshop_ctl_sku@set_spec_index' ]);
    # 商家后台报表
    route::post('sysstat/sysstat.html', [ 'as' => 'topshop.sysstat.sysstat', 'uses' => 'topshop_ctl_sysstat_sysstat@ajaxTrade' ]);
    route::post('sysstat/stattrade.html', [ 'as' => 'topshop.sysstat.stattrade', 'uses' => 'topshop_ctl_sysstat_stattrade@ajaxTrade' ]);
    route::post('sysstat/itemtrade.html', [ 'as' => 'topshop.sysstat.itemtrade', 'uses' => 'topshop_ctl_sysstat_itemtrade@ajaxTrade' ]);
    route::post('sysstat/stataftersales.html', [ 'as' => 'topshop.sysstat.stataftersales', 'uses' => 'topshop_ctl_sysstat_stataftersales@ajaxTrade' ]);
    route::post('sysstat/stattraffic.html', [ 'as' => 'topshop.sysstat.stattraffic', 'uses' => 'topshop_ctl_sysstat_systraffic@ajaxTrade' ]);
    route::post('sysstat/suppliertrade.html', [ 'as' => 'topshop.sysstat.suppliertrade', 'uses' => 'topshop_ctl_sysstat_suppliertrade@ajaxTrade' ]);
    route::post('sysstat/supplieritemtop.html', [ 'as' => 'topshop.sysstat.supplieritemtop', 'uses' => 'topshop_ctl_sysstat_suppliertrade@getItemTopTen' ]);
    route::get('sysstat/exportview.html', [ 'as' => 'topshop.exportstat.view', 'uses' => 'topshop_ctl_exportstat@view' ]);
    route::post('sysstat/exportstatdo.html', [ 'as' => 'topshop.exportstat.do', 'uses' => 'topshop_ctl_exportstat@export' ]);
    # 商家发货
    route::group(array(), function() {
        //route::get('trade/godelivery.html', [ 'as' => 'topshop.trade.godelivery', 'uses' => 'topshop_ctl_trade_flow@godelivery', 'middleware'=>['topshop_middleware_developerMode']]);
        route::post('trade/dodelivery.html', [ 'as' => 'topshop.trade.dodelivery', 'uses' => 'topshop_ctl_trade_flow@dodelivery', 'middleware'=>['topshop_middleware_developerMode']]);
        route::post('trade/updateLogistic.html', [ 'as' => 'topshop.trade.updateLogistic', 'uses' => 'topshop_ctl_trade_flow@updateLogistic' ]);
        // 王衍生-2018/07/04-start
        route::post('trade/muumi/dodelivery.html', [ 'as' => 'topshop.trade.muumi.dodelivery', 'uses' => 'topshop_ctl_trade_muumi_flow@dodelivery', 'middleware'=>['topshop_middleware_developerMode']]);

        route::post('trade/muumi/updateLogistic.html', [ 'as' => 'topshop.trade.muumi.updateLogistic', 'uses' => 'topshop_ctl_trade_muumi_flow@updateLogistic' ]);

        route::get('trade/toHub.html', [ 'as' => 'topshop.trade.toHub', 'uses' => 'topshop_ctl_trade_flow@toHub']);

        // 王衍生-2018/07/04-end
    });

    //wap配置
    route::post('wap/searchItem.html', [ 'as' => 'topshop.mobile.decorate.searchItem', 'uses' => 'topshop_ctl_wap_decorate@searchItem' ]);
    route::post('wap/getBrandList.html', [ 'as' => 'topshop.mobile.decorate.getBrandList', 'uses' => 'topshop_ctl_wap_decorate@getBrandList' ]);
    #意见反馈
    route::post('feedback.html', [ 'as' => 'topshop.feedback', 'uses' => 'topshop_ctl_index@feedback' ]);
    route::post('newdecorateSetting.html', [ 'as' => 'topshop.wap.newdecorate.setting', 'uses' => 'topshop_ctl_wap_decorate@ajaxSaveNewDecorate' ]);

    #编辑常用菜单
    route::post('common/user/menu.html', [ 'as' => 'topshop.commonUserMenu', 'uses' => 'topshop_ctl_menu@index' ]);

    route::get('export.html', [ 'as' => 'toputil.export.view', 'uses' => 'topshop_ctl_export@view' ]);
    route::post('export.html', [ 'as' => 'toputil.export.do', 'uses' => 'topshop_ctl_export@export' ]);
    /*add_20170923_by_wudi_start*/
    #后台导出订单
    route::get('exporttrade.html', [ 'as' => 'toputil.exporttrade.view', 'uses' => 'topshop_ctl_exporttrade@view' ]);
    route::get('exporttradeorder.html', [ 'as' => 'toputil.exporttrade.vieworder', 'uses' => 'topshop_ctl_exporttrade@vieworder' ]);
    route::post('exporttrade.html', [ 'as' => 'toputil.exporttrade.do', 'uses' => 'topshop_ctl_exporttrade@export' ]);
    route::post('exportorder.html', [ 'as' => 'toputil.exportorder.do', 'uses' => 'topshop_ctl_exporttrade@exportOrder' ]);
    route::get('exportvoucher.html', [ 'as' => 'toputil.exportvoucher', 'uses' => 'topshop_ctl_exporttrade@exportVoucher' ]);
    route::get('exportagentvoucher.html', [ 'as' => 'toputil.exportagentvoucher', 'uses' => 'topshop_ctl_exporttrade@exportAgentVoucher' ]);
    route::get('exportitemsettle.html', [ 'as' => 'toputil.exportitemsettle', 'uses' => 'topshop_ctl_exporttrade@exportItemSettle' ]);
    route::get('exportitemsettledetail.html', [ 'as' => 'toputil.exportitemsettledetail', 'uses' => 'topshop_ctl_exporttrade@exportItemSettleDetail' ]);
    route::get('exportsuppliersettle.html', [ 'as' => 'toputil.exportsuppliersettledetail', 'uses' => 'topshop_ctl_exporttrade@exportSupplierSettleDetail' ]);
    route::get('exportbillsettle.html', [ 'as' => 'toputil.exportbillsettledetail', 'uses' => 'topshop_ctl_exporttrade@exportBillSettleDetail' ]);
    #导入物流单号
    route::get('import.html', [ 'as' => 'toputil.import.view', 'uses' => 'topshop_ctl_import@view' ]);
    route::post('import.html', [ 'as' => 'toputil.import.do', 'uses' => 'topshop_ctl_import@import' ]);
    #下载导入模板
    route::get('downloadTpl.html',['as'=>'toputil.downloadtopl','uses'=>'topshop_ctl_import@downLoadCsvTpl']);
    #下载导出日志
    route::get('downloadlog.html',['as'=>'toputil.downloadlog','uses'=>'topshop_ctl_import@downloadLogFile']);
    #导入模板操作
    route::post('uploadCsvFile.html',['as'=>'toputil.uploadcsvfile','uses'=>'topshop_ctl_import@uploadCsvFile']);
    route::get('virtual-trade-list.html',['as'=>'topshop.vtrade.list','uses'=>'topshop_ctl_trade_voucher@search']);
    route::post('virtual-trade-list.html',['as'=>'topshop.vtrade.list','uses'=>'topshop_ctl_trade_voucher@search']);
    /*add_20170923_by_wudi_end*/
	/*add_2018/7/6_by_wanghaichao_start*/
	//集采商城的明细,主持人的明细导出等
	route::get('exportbillpushdetail.html', [ 'as' => 'toputil.exportbillpushdetail', 'uses' => 'topshop_ctl_exporttrade@exportBillPushDetail' ]);
	route::get('exportbillpulldetail.html', [ 'as' => 'toputil.exportbillpulldetail', 'uses' => 'topshop_ctl_exporttrade@exportBillPullDetail' ]);
	route::get('exportbillsellerdetail.html', [ 'as' => 'toputil.exportbillsellerdetail', 'uses' => 'topshop_ctl_exporttrade@exportBillSellerDetail' ]);
	/*add_2018/7/6_by_wanghaichao_end*/
    // 王衍生-2018/07/03-start
    route::match(array('GET', 'POST'),'/muumi/trade/search.html', [ 'uses' => 'topshop_ctl_trade_muumi_list@search']);
    // 王衍生-2018/07/03-end
    # 选择商品组件
    route::get('select-goods.html', [ 'as' => 'topshop.goods.select', 'uses' => 'topshop_ctl_selector_item@loadSelectGoodsModal' ]);
    route::post('format-selected-goods.html', [ 'as' => 'topshop.goods.selected.format', 'uses' => 'topshop_ctl_selector_item@formatSelectedGoodsRow' ]);
    /*add_2018/05/30_by_jiangyunhan_start*/
    route::post('format-selected-goods-for-widget.html', [ 'as' => 'topshop.goods.selected.format.widget', 'uses' => 'topshop_ctl_selector_item@formatSelectedGoodsRowForWidget' ]);
    /*add_2018/05/30_by_jiangyunhan_end*/
    route::post('select-brandList.html', [ 'as' => 'topshop.goods.brandList', 'uses' => 'topshop_ctl_selector_item@getBrandList' ]);
    route::post('select-getItem.html', [ 'as' => 'topshop.goods.getItem', 'uses' => 'topshop_ctl_selector_item@searchItem' ]);
    route::get('select-item.getsku.html', [ 'as' => 'topshop.item.goods.getsku', 'uses' => 'topshop_ctl_selector_item@getSkuByItemId' ]);
    route::get('select-showsku.html', [ 'as' => 'topshop.goods.showsku', 'uses' => 'topshop_ctl_selector_item@showSkuByitemId' ]);
    # 选择用户组件
    route::get('select-users.html', [ 'as' => 'topshop.users.select', 'uses' => 'topshop_ctl_selector_users@loadSelectUsersModal' ]);
    route::post('format-selected-users.html', [ 'as' => 'topshop.users.selected.format', 'uses' => 'topshop_ctl_selector_users@formatSelectedUsersRow' ]);
    route::post('select-getUser.html', [ 'as' => 'topshop.users.getUser', 'uses' => 'topshop_ctl_selector_users@searchUser' ]);
    # 选择线下店组件
    /*add_2018/05/30_by_jiangyunhan_start*/
    route::get('select-agents.html', [ 'as' => 'topshop.agents.select', 'uses' => 'topshop_ctl_selector_agent@loadSelectAgentsModal' ]);
    route::post('format-selected-agents.html', [ 'as' => 'topshop.agents.selected.format', 'uses' => 'topshop_ctl_selector_agent@formatSelectedAgentsRow' ]);
    route::post('select-getAgent.html', [ 'as' => 'topshop.agents.getAgent', 'uses' => 'topshop_ctl_selector_agent@searchAgent' ]);
    /*add_2018/05/30_by_jiangyunhan_end*/

    /*add_2018/07/30_by_jiangyunhan_start*/
    route::get('select-activity.html', [ 'as' => 'topshop.activity.select', 'uses' => 'topshop_ctl_selector_activity@loadSelectActivityModal' ]);
    route::post('format-selected-activity.html', [ 'as' => 'topshop.activity.selected.format', 'uses' => 'topshop_ctl_selector_activity@formatSelectedActivityRow' ]);
    route::post('select-getActivity.html', [ 'as' => 'topshop.activity.getAgent', 'uses' => 'topshop_ctl_selector_activity@searchActivity' ]);
    /*add_2018/07/30_by_jiangyunhan_end*/

    # 选择地图坐标
    route::get('select-map.html', [ 'as' => 'topshop.map.select', 'uses' => 'topshop_ctl_selector_map@loadMapModal' ]);
	/*add_2017/12/29_by_wanghaichao_start*/
	#选择车辆组件
    route::get('select-cart.html', [ 'as' => 'topshop.cart.select', 'uses' => 'topshop_ctl_selector_cart@loadSelectCartModal' ]);
    route::post('format-selected-cart.html', [ 'as' => 'topshop.cart.selected.format', 'uses' => 'topshop_ctl_selector_cart@formatSelectedCartRow' ]);
    route::post('select-brandList.html', [ 'as' => 'topshop.goods.brandList', 'uses' => 'topshop_ctl_selector_cart@getBrandList' ]);
    route::post('select-getCart.html', [ 'as' => 'topshop.cart.getCart', 'uses' => 'topshop_ctl_selector_cart@searchCart' ]);

	/*add_2017/12/29_by_wanghaichao_end*/



    // add start 王衍生 20170921
    // 文章选择组件
    route::get('article/ajax/list.html', [ 'as' => 'topshop.article.ajaxArticleList', 'uses' => 'topshop_ctl_shop_article@ajaxArticleList']);
    // 优惠券选择器
    route::get('coupon/modal/modal.html', [ 'uses' => 'topshop_ctl_selector_coupon@loadSelectModal']);
    route::match(array('GET', 'POST'),'coupon/modal/list.html', [ 'uses' => 'topshop_ctl_selector_coupon@searchCoupon']);
    route::post('coupon/modal/row.html', [ 'uses' => 'topshop_ctl_selector_coupon@loadGetCouponRow']);
    // add end 王衍生 20170921

    // 商家错误页
    route::get('error_404.html', ['uses' => 'topshop_ctl_error@index' ]);
    route::get('select-sku.html', [ 'as' => 'topshop.sku.select', 'uses' => 'topshop_ctl_selector_sku@loadSelectSkuModal' ]);
    route::post('format-selected-sku.html', [ 'as' => 'topshop.sku.selected.format', 'uses' => 'topshop_ctl_selector_sku@formatSelectedSkusRow' ]);
    route::post('select-getSku.html', [ 'as' => 'topshop.sku.getSku', 'uses' => 'topshop_ctl_selector_sku@searchSku' ]);

    // 选货商城(广电优选)开始
	// 选货商城首页
	route::get('mall/home.html', ['uses' => 'topshop_ctl_mall_home@index']);
	// 商品列表页
	route::get('mall/list.html', ['uses' => 'topshop_ctl_mall_list@index']);
	// 商品列表数据
	route::post('mall/ajax/list_data.html', ['uses' => 'topshop_ctl_mall_list@listData']);
	// 商品详情页
	route::get('mall/detail.html', ['uses' => 'topshop_ctl_mall_detail@index']);
	// 店铺首页
	route::get('mall/shop.html', ['uses' => 'topshop_ctl_mall_shop@index']);
    // 推送按钮链接
    route::post('mall/ajax/push_item.html', ['uses' => 'topshop_ctl_mall_item@pushItem']);
    // 回撤按钮链接
    route::post('mall/ajax/delete_item.html', ['uses' => 'topshop_ctl_mall_item@deleteItem']);
	// 拉取按钮链接
    route::post('mall/ajax/pull_item.html', ['uses' => 'topshop_ctl_mall_item@pullItem']);
	// 更新代售商品按钮链接
    route::post('mall/ajax/update_item.html', ['uses' => 'topshop_ctl_mall_item@updateItem']);
	// 选货商城(广电优选)结束

    $menus = config::get('shop');
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
// 广电优选登录验证
route::post('mall/passport/signin.html', [ 'as' => 'topshop.mall.postsignin', 'uses' => 'topshop_ctl_mall_passport@login' ]);

# 忘记密码
route::group(array('prefix' => 'shop', 'middleware' => 'topshop_middleware_redirectIfAuthenticated'), function() {
    route::get('find/index.html', [ 'as' => 'topshop.find', 'uses' => 'topshop_ctl_find@index']);
    route::get('find/firststep.html', [ 'as' => 'topshop.find', 'uses' => 'topshop_ctl_find@firstStep']);
    route::get('find/isauth.html', [ 'as' => 'topshop.findisauth', 'uses' => 'topshop_ctl_find@isAuth' ]);
    //验证
    route::post('find/checkinfo.html', ['as'=>'topshop.find.check','uses'=>'topshop_ctl_find@checkFindInfo']);
    //找回密码第二步
    route::get('find/secondstep.html', [ 'as' => 'topshop.find.second', 'uses' => 'topshop_ctl_find@secondStep']);
    // 修改密码
    route::post('find/resetpwd.html', [ 'as' => 'topshop.find.resetpwd', 'uses' => 'topshop_ctl_find@resetPassword']);
    // 发送验证码
    route::post('find/sendcode.html', ['as'=>'topshop.auth.send.code','uses'=>'topshop_ctl_find@send']);
});
/*
|--------------------------------------------------------------------------
| 店铺通用显示数据处理
|--------------------------------------------------------------------------
 */
route::group(array('prefix' => 'utils'), function() {
    # 系统分类
    route::post('syscat.html', [ 'as' => 'toputil.syscat', 'uses' => 'toputil_ctl_syscat@getChildrenCatList' ]);
    route::get('vcode.html', [ 'as' => 'toputil.vcode', 'uses' => 'toputil_ctl_vcode@gen_vcode' ]);
    route::post('util/upload_images.html', [ 'as' => 'toputil.uploadImages', 'uses' => 'toputil_ctl_image@uploadImages' ]);
    route::get('util/item_pic.html', [ 'as' => 'toputil.getDefaultItemPic', 'uses' => 'toputil_ctl_image@getItemPic' ]);
    route::post('ajax/articleList.html', [ 'as' => 'toputil.getContentNodeArticleList', 'uses' => 'toputil_ctl_themesAjax@getContentNodeArticleList' ]);
    route::post('ajax/catList.html', [ 'as' => 'toputil.catList', 'uses' => 'toputil_ctl_themesAjax@getChildrenCatList' ]);
    route::post('ajax/virtualCatList.html', [ 'as' => 'toputil.virtualCatList', 'uses' => 'toputil_ctl_themesAjax@getVirtualCatChildrenList' ]);
    route::post('trafficstat.html', [ 'as' => 'toputil.trafficStatic', 'uses' => 'toputil_ctl_trafficStatic@stat' ]);
    # 仅用于demo
    route::post('passport/demologin.html', [ 'as' => 'toputil.demologin', 'uses' => 'toputil_ctl_passportdemo@login' ]);
    // add start 王衍生 20170925
    route::post('util/upload_file.html', [ 'as' => 'toputil.upload.file', 'uses' => 'toputil_ctl_uploadfile@index' ]); // 已弃用

    route::post('util/upload_video.html', [ 'as' => 'toputil.uploadVideo', 'uses' => 'toputil_ctl_video@uploadVideo' ]);

    // end start 王衍生 20170925
});

route::match(array('GET', 'POST', 'PUT', 'DELETE'), 'wxpayApp.html', ['uses' => 'topapi_ctl_wechat@wxpayApp']);

/*add_20171213_by_fanglongji_start*/
route::match(array('GET', 'POST', 'PUT', 'DELETE'), 'zxwxpay.html', ['uses' => 'topapi_ctl_wechat@zxwxpay']);
// 商家微信子商户借用支付 支付结果通知
route::match(array('GET', 'POST', 'PUT', 'DELETE'), 'wap/wxservicepayapi.html', ['uses' => 'topapi_ctl_wechat@wxservicepayapi']);
// 商家小程序子商户借用支付 支付结果通知
route::match(array('GET', 'POST', 'PUT', 'DELETE'), 'wap/miniservicepayapi.html', ['uses' => 'topapi_ctl_wechat@miniservicepayapi']);
// 线下店商家微信子商户借用支付 支付结果通知
route::match(array('GET', 'POST', 'PUT', 'DELETE'), 'wap/wxofflineservicepayapi.html', ['uses' => 'topapi_ctl_wechat@wxofflineservicepayapi']);
// 微信服务商异步退款通知
// route::match(array('GET', 'POST', 'PUT', 'DELETE'), 'wap/refund-wxsjrefund.html', ['uses' => 'topapi_ctl_wechat@wxserviceback']);
route::match(array('GET', 'POST', 'PUT', 'DELETE'), 'wxservicerefund.html', ['uses' => 'topapi_ctl_wechat@wxservicerefund']);
// 微信非服务商异步退款通知
route::match(array('GET', 'POST', 'PUT', 'DELETE'), 'wxrefund.html', ['uses' => 'topapi_ctl_wechat@wxrefund']);

route::match(array('GET', 'POST', 'PUT', 'DELETE'), 'wap/getWxOpenId.html', ['uses' => 'ectools_api_payment_pay@dopay']);
/*add_20171213_by_fanglongji_end*/
/*add_2018/1/23_by_wanghaichao_start*/
route::match(array('GET', 'POST', 'PUT', 'DELETE'), 'wap/getWxLeaseOpenId.html', ['uses' => 'ectools_api_payment_leasepay@dopay']);
/*add_2018/1/23_by_wanghaichao_end*/
route::match(array('GET', 'POST', 'PUT', 'DELETE'), 'wap/lease-wxservicepayapi.html', ['uses' => 'topapi_ctl_wechat@lease_wxservicepayapi']);

// 微信服务商H5支付异步通知
route::match(array('GET', 'POST'), 'wxserviceh5pay.html', ['uses' => 'topwap_ctl_wechat@wxserviceh5pay']);
// 微信非服务商H5支付异步通知
route::match(array('GET', 'POST'), 'wxh5pay.html', ['uses' => 'topwap_ctl_wechat@wxh5pay']);

//电商大数据与呼叫中心数据面板
route::group(array('prefix' => 'shop/panel_data'), function() {
    route::match(['GET','POST'],'blueberry.html',  [ 'as' => 'shop.panel.data.blueberry', 'uses' => 'syslmgw_ctl_blueberry@request_data' ]);
    route::match(['GET','POST'],'callcenter.html',  [ 'as' => 'shop.panel.data.callcenter', 'uses' => 'syslmgw_ctl_callcenter@request_data' ]);
    route::match(['GET','POST'],'rice.html',  [ 'as' => 'shop.panel.data.rice', 'uses' => 'syslmgw_ctl_rice@request_data' ]);
});
/*
|--------------------------------------------------------------------------
| 后台通用route
|--------------------------------------------------------------------------
 */
route::group(array('prefix' => 'shopadmin'), function() {

    # 系统分类
    route::match(array('GET', 'POST'), '/', array('as' => 'shopadmin', 'uses' => 'desktop_router@dispatch'));
});


route::group(array('prefix' => 'dev'), function() {
    route::get('/', [ 'as' => 'topdev.index', 'uses' => 'topdev_ctl_index@index' ]);
    route::get('apis/list.html', [ 'as' => 'topdev.apis.list', 'uses' => 'topdev_ctl_apis@group']);
    route::get('apis/info.html', [ 'as' => 'topdev.apis.info', 'uses' => 'topdev_ctl_apis@info']);
    route::get('apis/test.html', [ 'as' => 'topdev.apis.test', 'uses' => 'topdev_ctl_apis@testView']);
    route::post('apis/test.html', [ 'as' => 'topdev.apis.use', 'uses' => 'topdev_ctl_apis@testApi']);
    route::get('apis/search.html', [ 'as' => 'topdev.apis.search', 'uses' => 'topdev_ctl_apis@search']);
    route::get('apis/topapi/export.html', [ 'as' => 'topdev.apis.topapi.export', 'uses' => 'topdev_ctl_apis@topapiExport']);
});


/*
|--------------------------------------------------------------------------
| setup
|--------------------------------------------------------------------------
 */
route::group(array('prefix' => 'setup'), function() {
    # 安装首页
    route::match(array('GET', 'POST'), '/', ['as' => 'setup', 'uses' => 'setup_ctl_default@index']);
    # 安装页
    route::match(array('GET', 'POST'), '/default/process', ['uses' => 'setup_ctl_default@process']);
    # 命令行安装
    route::match(array('GET', 'POST'), '/default/install_app', ['uses' => 'setup_ctl_default@install_app']);
    # console
    route::match(array('GET', 'POST'), '/default/console', ['uses' => 'setup_ctl_default@console']);
    # 激活
    route::match(array('GET', 'POST'), '/default/active', ['uses' => 'setup_ctl_default@active']);
    # 激活成功
    route::match(array('GET', 'POST'), '/default/success', ['uses' => 'setup_ctl_default@success']);
    # 环境初始化
    route::match(array('GET', 'POST'), '/default/initenv', ['uses' => 'setup_ctl_default@initenv']);
    # 女装初始化数据
    route::match(array('GET', 'POST'), '/default/install_demodata', ['uses' => 'setup_ctl_default@install_demodata']);
    route::match(array('GET', 'POST'), '/default/setuptools', ['uses' => 'setup_ctl_default@setuptools']);

});

/*add_2018-01-08_by_xinyufeng_start*/
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

	/*add_2018/1/11_by_wanghaichao_start*/

    # 选择商品组件
	# 针对蓝鲸商城,  要选择多个店铺的商品
    route::get('store-select-goods.html', [ 'as' => 'topshop.goods.select', 'uses' => 'topstore_ctl_selector_item@loadSelectGoodsModal' ]);
    route::post('store-format-selected-goods.html', [ 'as' => 'topshop.goods.selected.format', 'uses' => 'topstore_ctl_selector_item@formatSelectedGoodsRow' ]);
    route::post('store-select-brandList.html', [ 'as' => 'topshop.goods.brandList', 'uses' => 'topstore_ctl_selector_item@getBrandList' ]);
    route::post('store-select-getItem.html', [ 'as' => 'topshop.goods.getItem', 'uses' => 'topstore_ctl_selector_item@searchItem' ]);
    route::get('store-select-item.getsku.html', [ 'as' => 'topshop.item.goods.getsku', 'uses' => 'topstore_ctl_selector_item@getSkuByItemId' ]);
    route::get('store-select-showsku.html', [ 'as' => 'topshop.goods.showsku', 'uses' => 'topstore_ctl_selector_item@showSkuByitemId' ]);
	/*add_2018/1/11_by_wanghaichao_end*/


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
/*add_2018-01-08_by_xinyufeng_end*/

/*add_2018-11-14_by_xinyufeng_start*/
/*
|--------------------------------------------------------------------------
| 创客路由
|--------------------------------------------------------------------------
*/
route::group(array('prefix' => 'maker','middleware' => 'topmaker_middleware_permission'), function() {
    # 首页(创客中心)
    route::get('/', ['as' => 'topmaker.home', 'uses' => 'topmaker_ctl_index@index']);
	# 登录页面
	route::get('passport/signin.html', ['as' => 'topmaker.signin', 'uses' => 'topmaker_ctl_passport@signin', 'middleware' => 'topmaker_middleware_redirectIfAuthenticated']);
	# 登录验证
	route::post('passport/postsignin.html', ['as' => 'topmaker.postsignin', 'uses' => 'topmaker_ctl_passport@login']);
	# 退出登录
    route::get('passport/logout.html', ['as' => 'topmaker.logout', 'uses' => 'topmaker_ctl_passport@logout']);
	# 注册页面
	route::get('passport/signup.html', ['as' => 'topmaker.signup', 'uses' => 'topmaker_ctl_passport@signup']);
	# 注册验证
	route::post('passport/postsignup.html', ['as' => 'topmaker.postsignup', 'uses' => 'topmaker_ctl_passport@create']);
	# 发送手机验证码
	route::post('passport/sendvcode.html', [ 'as' => 'topmaker.send.vcode', 'uses' => 'topmaker_ctl_passport@sendVcode']);
	# 审核页面
	route::get('passport/makercheck.html', ['as' => 'topmaker.maker.check', 'uses' => 'topmaker_ctl_passport@makerCheck']);
    # 登录页面-微信回调函数
	route::get('trustlogin/callbacksignin.html', ['as' => 'topmaker.trustlogin.callbacksignin', 'uses' => 'topmaker_ctl_trustlogin@callbackSignIn']);
	# 注册页面-微信回调函数
	route::get('trustlogin/callbacksignup.html', ['as' => 'topmaker.trustlogin.callbacksignup', 'uses' => 'topmaker_ctl_trustlogin@callbackSignUp']);

    # 佣金明细列表
    route::get('commission/list.html', ['as' => 'topmaker.commission.list', 'uses' => 'topmaker_ctl_commission@listData']);
    route::get('commission/list-ajax.html', ['as' => 'topmaker.commission.list.ajax', 'uses' => 'topmaker_ctl_commission@ajaxGetListData']);
    # 佣金详情
    route::get('commission/detail.html', ['as' => 'topmaker.commission.detail', 'uses' => 'topmaker_ctl_commission@detail']);
	/*add_2018/11/19_by_wanghaichao_start*/
	#分销佣金
	route::get('commission/statistic.html', ['as' => 'topmaker.commission.statistic', 'uses' => 'topmaker_ctl_commission@statistic']);
	/*add_2018/11/19_by_wanghaichao_end*/
	



    /*add_2018-11-14_by_jiangyunhan_start*/
    route::get('set-maker.html', [ 'uses' => 'topmaker_ctl_setting@indexMaker']);  #创客店铺设置页面
    route::post('save-setmaker.html', [ 'uses' => 'topmaker_ctl_setting@saveMaker' ]);#创客店铺设置页面保存逻辑

    route::get('maker-home.html', [ 'uses' => 'topmaker_ctl_center@index']);  #创客中心页面

    route::get('maker-goods.html', [ 'uses' => 'topmaker_ctl_goods@index']);  #创客自选商品列表页面

    route::get('maker-goods-ajax.html', [ 'uses' => 'topmaker_ctl_goods@indexAjax']);  #创客自选商品列表ajax获取数据


    route::post('maker-goods-save.html', [ 'uses' => 'topmaker_ctl_goods@saveGoods']);  #创客自选商品保存逻辑


    route::get('maker-goods-delete.html', [ 'uses' => 'topmaker_ctl_goods@delGoods']);  #创客自选商品删除逻辑
    /*add_2018-11-14_by_jiangyunhan_end*/
});
/*add_2018-11-14_by_xinyufeng_end*/