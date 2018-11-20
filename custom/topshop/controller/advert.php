<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/9/15
 * Time: 20:03
 */

class topshop_ctl_advert extends topshop_controller
{
    /**
     * @return html
     * 广告管理
     */
    public function manage()
    {
        $shopAdvertModel = app::get('sysshop')->model('shop_advert');
        $advert_info = $shopAdvertModel->getRow('*', ['shop_id' => $this->shopId]);
        $page_data['advert_info'] = $advert_info;
//        echo "<pre>";print_r($page_data);die;
        return $this->page('topshop/advert/manage.html', $page_data);
    }

    /**
     * @return string
     * 广告配置信息保存
     */
    public function save()
    {
        $post_data = input::get();
        $post_data['shop_id'] = $this->shopId;
        unset($post_data['s']);
        try
        {
            $shopAdvertModel = app::get('sysshop')->model('shop_advert');
            $shopAdvertModel->save($post_data);
            return $this->splash('success',null,'保存成功');
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg);
        }

    }
}