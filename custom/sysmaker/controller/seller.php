<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-13
 * Desc: 创客帐号列表
 */
class sysmaker_ctl_seller extends desktop_controller{

    /**
     * 用户列表
     * @return mixed
     */
    public function lists()
    {
        $actions = array(
            array(
                'label'=>app::get('sysmaker')->_('添加用户'),
                'href'=>'?app=sysmaker&ctl=seller&act=editPage',
                'target'=>'dialog::{title:\''.app::get('sysmaker')->_('添加用户').'\',width:500,height:380}'
            ),
            array(
                'label'=>app::get('sysmaker')->_('删除'),
                'icon' => 'download.gif',
                'submit' => '?app=sysmaker&ctl=seller&act=doDelete',
                'confirm' => app::get('sysmaker')->_('确定要删除选中用户？'),
            ),
        );

        $params = array(
            'title' => app::get('sysmaker')->_('用户列表'),
            'actions'=> $actions,
            'allow_detail_popup' => false,// 不允许新窗口查看
            'use_buildin_delete' => false,
            'base_filter' =>array( //对列表数据进行过滤筛选
                'deleted' => 0,
            ),
        );
        
        return $this->finder('sysmaker_mdl_account', $params);
    }

    /**
     * 列表tab
     * @return array
     */
    public function _views()
    {
        $subMenu = array(
            0 => array(
                'label'=>app::get('sysmaker')->_('全部'),
                'optional'=>false,
            ),
            1 => array(
                'label'=>app::get('sysmaker')->_('待审核'),
                'optional'=>false,
                'filter'=>array(
                    'status'=>'pending',
                ),
            ),
            2 => array(
                'label'=>app::get('sysmaker')->_('审核通过'),
                'optional'=>false,
                'filter'=>array(
                    'status'=>'success',
                ),

            ),
        );
        return $subMenu;
    }

    /**
     * 编辑用户数据
     * @param $sellerId
     */
    public function editPage($sellerId)
    {
        $pagedata = array();

        if( $sellerId )
        {
            $sellerInfo = app::get('sysmaker')->model('seller')->getRow('*', array('seller_id' => $sellerId));
            $pagedata['sellerInfo'] = $sellerInfo;
        }

        return $this->page('sysmaker/seller/edit.html', $pagedata);
    }

    /**
     * 保存用户数据
     */
    public function saveSeller()
    {
        $inputs = input::get();
        $seller = $inputs['seller'];
        $cdres = $this->__checkData($seller);
        if(!$cdres['s']){
            $this->splash('error',null,$cdres['m']);
        }
        
        $objSeller = kernel::single('sysmaker_data_seller');
        $seller['status'] = 'success';
        $seller['reason'] = '平台添加帐号，审核通过';
        $flag = $objSeller->saveSeller($seller,$msg);
        if(!$flag){
            $this->adminlog("{$msg}[{$seller['mobile']}]", 0);
            return $this->splash('error',null,$msg);
        }
        
        $this->adminlog("{$msg}[{$seller['mobile']}]", 1);
        return $this->splash('success',null ,$msg);
    }

    /**
     * 保存密码
     */
    public function savePwd()
    {
        $inputs = input::get();
        $seller = $inputs['seller'];

        if(!$seller['seller_id'])
        {
            throw new \LogicException('参数错误');
        }

        if($seller['login_password'] != $seller['psw_confirm'])
        {
            return $this->splash('error',null,'用户登录密码和确认密码不一致');
        }

        $objSeller = kernel::single('sysmaker_data_seller');
        $flag = $objSeller->savePwd($seller,$msg);
        if(!$flag){
            $this->adminlog("{$msg}[{$seller['seller_id']}]", 0);
            return $this->splash('error',null,$msg);
        }
        
        $this->adminlog("{$msg}[{$seller['seller_id']}]", 1);
        
        return $this->splash('success',null ,$msg);
    }

    /**
     * 绑定店铺页面
     * @param $sellerId
     */
    public function bindShopPage($sellerId)
    {
        if(!$sellerId)
        {
            throw new \LogicException('参数错误');
        }

        $filter = array('seller_id' => $sellerId);
        $sellerInfo = app::get('sysmaker')->model('seller')->getRow('seller_id, mobile', $filter);
        if(!$sellerInfo)
        {
            throw new \LogicException('创客帐号不存在');
        }

        $pagedata['sellerInfo'] = $sellerInfo;

        $objMdlRelShop = app::get('sysmaker')->model('shop_rel_seller');
        $shop_ids = $objMdlRelShop->getList('shop_id', $filter);
        $pagedata['sellerInfo']['shop_ids'] = array_column($shop_ids, 'shop_id');
        
        // 获取店铺列表
        $objShop = kernel::single('sysshop_data_shop');
        $shopList = $objShop->fetchListShopInfo('shop_id,shop_name');
        $pagedata['shopList'] = $shopList;

        return $this->page('sysmaker/seller/bind_shop.html', $pagedata);
    }

    /**
     * 保存店铺数据
     */
    public function saveBindShop()
    {
        $inputs = input::get();
        $seller = $inputs['seller'];

        if(!$seller['seller_id'])
        {
            throw new \LogicException('参数错误');
        }

        if(!$seller['shop_info'])
        {
            return $this->splash('error',null,'请选择店铺');
        }

        foreach ($seller['shop_info'] as $shop)
        {
            list($shop_id, $shop_name) = explode('|', $shop);
            $seller['shop'][] = array(
                'shop_id' => $shop_id,
                'shop_name' => $shop_name,
            );
        }

        $objSeller = kernel::single('sysmaker_data_seller');
        $flag = $objSeller->saveBindShop($seller,$msg);
        if(!$flag){
            $this->adminlog("{$msg}[{$seller['seller_id']}]", 0);
            return $this->splash('error',null,$msg);
        }

        $this->adminlog("{$msg}[{$seller['seller_id']}]", 1);

        return $this->splash('success',null ,$msg);
    }
    
    /**
     * 审核页面
     * @param $sellerId
     */
    public function checkPage($sellerId)
    {
        $pagedata = array();

        if( $sellerId )
        {
            $accountInfo = app::get('sysmaker')->model('account')
                ->getRow('seller_id, status, reason', array('seller_id' => $sellerId));
            $pagedata['accountInfo'] = $accountInfo;
        }
        
        return $this->page('sysmaker/seller/check.html', $pagedata);
    }

    /**
     * 保存审核
     */
    public function checkSave()
    {
        $seller = input::get('seller');
        if(!$seller['seller_id'])
        {
            throw new \LogicException('参数错误');
        }

        $objSeller = kernel::single('sysmaker_data_seller');
        $flag = $objSeller->saveCheck($seller['seller_id'], $seller, $msg);
        if(!$flag){
            $this->adminlog("{$msg}[{$seller['seller_id']}]", 0);
            return $this->splash('error',null,$msg);
        }

        $this->adminlog("{$msg}[{$seller['seller_id']}]", 1);
        return $this->splash('success',null ,$msg);
    }

    /**
     * 删除创客等信息
     */
    public function doDelete()
    {
        $seller_id = input::get('seller_id');
        // 开启事务
        $this->begin('?app=sysmaker&ctl=seller&act=lists');

        if(empty($seller_id))
        {
            $msg = "请选择要操作的数据项";
            $this->end(false,$msg);
        }
        
        try{
            $filter = array('seller_id' => $seller_id);
            kernel::single('sysmaker_data_seller')->delete($filter);
        }catch(Exception $e){
            $msg = $e->getMessage();
            $this->end(false,$msg);
        }

        $this->end(true,app::get('sysmaker')->_('删除数据成功'));
    }

    /**
     * 验证提交数据
     * @param $data
     * @return array
     */
    private function __checkData($data)
    {
        if(empty($data['mobile'])){
            return array('s' => false, 'm' => '请填写手机号');
        }

        if(empty($data['name'])){
            return array('s' => false, 'm' => '请填写姓名');
        }
        
        if(empty($data['seller_id']) || !empty($data['login_password']) || !empty($data['psw_confirm'])){
            if(empty($data['login_password'])){
                return array('s' => false, 'm' => '请填写登录密码');
            }

            if(empty($data['psw_confirm'])){
                return array('s' => false, 'm' => '请填写确认密码');
            }
        }

        if($data['login_password'] != $data['psw_confirm']){
            return array('s' => false, 'm' => '用户登录密码和确认密码不一致');
        }

        return array('s' => true, 'm' => '');
    }
}