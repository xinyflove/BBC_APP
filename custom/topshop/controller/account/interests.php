<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topshop_ctl_account_interests extends topshop_controller {
    protected $shopInterestsModel;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->shopInterestsModel = app::get('sysshop')->model('shop_interests');
    }


    /**
     * @return html
     * 会员权益管理
     */
    public function manage()
    {

        $postFilter = input::get();
        $this->contentHeaderTitle = app::get('topshop')->_('会员权益管理');

        $grade_model = app::get('sysuser')->model('user_grade');
        $grade_list  = $grade_model->getList('*',['shop_id' => $this->shopId]);

        $shop_interests = app::get('topshop')->rpcCall('shop.get.interests',['shop_id'=>$this->shopId]);
        foreach($shop_interests['interests']['account']['vip']['coupon'] as &$info)
        {
            $info['coupon_id'] = implode(',', $info['coupon_id']);
        }

        $coupon_params = array(
            'page_no' => input::get('pages',1),
            'page_size' => input::get('page_size',10),
            'fields' => 'deduct_money,coupon_name,coupon_id,canuse_start_time,canuse_end_time,cansend_start_time,cansend_end_time',
            'shop_id' => $this->shopId,
            'platform' => input::get('platform','pc'),
            'is_cansend' => 1,
        );

        $page_data['shop_interests'] = $shop_interests;
        $page_data['grade_list'] = $grade_list;
        $page_data['is_lm'] = $this->isLm;

        return $this->page('topshop/account/interests/manage.html', $page_data);
    }

    /**
     * 会员权益配置存储
     */
    public function save()
    {
        $post_data = input::get();
        $post_data['shop_id'] = $this->shopId;
        unset($post_data['s']);

        foreach($post_data['interests']['account']['vip']['coupon'] as &$info)
        {
            $info['coupon_id'] = explode(',', $info['coupon_id']);
        }

        $post_data['interests'] = serialize($post_data['interests']);

        try
        {
            $this->shopInterestsModel->save($post_data);
        }
        catch(Exception $e)
        {
            return $this->splash('error', null, $e->getMessage());
        }
        $url = url::action('topshop_ctl_account_interests@manage');
        return $this->splash('success', $url, app::get('topshop')->_('保存成功'));
    }

}

