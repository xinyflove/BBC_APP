<?php
/**
 * Auth: jiangyunhan
 * Time: 2018-11-14
 * Desc: 创客后台首页
 */
class topmaker_ctl_center extends topmaker_controller {

    public function index()
    {
        pamAccount::setAuthType('sysmaker');
        $targetId = pamAccount::getAccountId();

        $sellerdata = app::get('sysmaker')->model('seller')->getRow('*', array('seller_id'=>$targetId));
        //创客个人信息
        $pagedata['seller'] = $sellerdata;
        //累计佣金
        $pagedata['seller_commission_count'] = kernel::single('sysmaker_data_commission')->getSellerCommissionCount($targetId);


        //创客页面链接信息
        $pagedata['my_shop_url'] = '';//我的店铺
        $pagedata['my_shop_url'] = '';//分销佣金
        $pagedata['my_shop_url'] = url::action('topmaker_ctl_commission@listData');//佣金明细
        $pagedata['my_shop_url'] = '';//提现明细
        $pagedata['my_shop_qrcode_url'] = '';//推广二维码
        $pagedata['my_shop_goods_url'] = url::action('topmaker_ctl_goods@index');//自选商品
        $pagedata['my_shop_setting_url'] = url::action('topmaker_ctl_setting@indexMaker');//小店设置

        return view::make('topmaker/maker/centerindex.html',$pagedata);
    }

}