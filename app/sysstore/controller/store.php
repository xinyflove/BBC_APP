<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/3
 * Time: 10:25
 */
class sysstore_ctl_store extends desktop_controller{

    /**
     * 商城列表
     * @return mixed
     */
    public function lists()
    {
        $actions = array(
            array(
                'label'=>app::get('sysstore')->_('添加商城'),
                'href'=>'?app=sysstore&ctl=store&act=edit',
                'target'=>'dialog::{title:\''.app::get('sysstore')->_('添加商城').'\',width:500,height:450}'
            ),
        );

        $params = array(
            'title' => app::get('sysstore')->_('商城列表'),
            'actions'=> $actions,
        );

        return $this->finder('sysstore_mdl_store', $params);
    }

    /**
     * 编辑商城数据
     * @param $storeId
     */
    public function edit($storeId)
    {
        $pagedata = array();

        if( $storeId )
        {
            $storeInfo = app::get('sysstore')->model('store')->getRow('*', array('store_id' => $storeId));
            $storeInfo['shop_id'] = explode(',', $storeInfo['relate_shop_id']);
            $pagedata['storeInfo'] = $storeInfo;
        }

        $shopList = app::get('sysshop')->model('shop')->getList('shop_id,shop_name', array('status'=>'active'));
        $pagedata['shopList'] = $shopList;

        return $this->page('sysstore/store/edit.html', $pagedata);
    }

    /**
     * 保存商城数据
     */
    public function saveStore()
    {
        $inputs = input::get();
        $store = $inputs['store'];
        $cdres = $this->__checkData($store);
        if(!$cdres['s']){
            $this->splash('error',null,$cdres['m']);
        }

        $store['relate_shop_id'] = implode(',', $store['shop_id']);
        $objAccount = kernel::single('sysstore_data_store');
        try{
            $store_id = $objAccount->saveAccount($store,$msg);
            if(!$store_id){
                throw new \LogicException($msg);
            }
            $this->adminlog("{$msg}[{$store['store_name']} ID:{$store_id}]", 1);
        }catch (Exception $e){
            $msg = $e->getMessage();
            $this->adminlog("{$msg}[{$store['store_name']}]", 0);
            return $this->splash('error',null,$msg);
        }
        
        return $this->splash('success',null ,$msg);
    }

    /**
     * 验证提交数据
     * @param $data
     * @return array
     */
    private function __checkData($data)
    {
        if(empty($data['store_name'])){
            return array('s' => false, 'm' => '请填写商城名称');
        }

        if(empty($data['shop_id'])){
            return array('s' => false, 'm' => '请选择至少一个店铺');
        }

        return array('s' => true, 'm' => '');
    }
}