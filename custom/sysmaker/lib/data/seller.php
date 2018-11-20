<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-13
 * Desc: 创客数据处理
 */
class sysmaker_data_seller {

    public $sellerModel = null;
    public $accountModel = null;

    public function __construct()
    {
        $this->sellerModel = app::get('sysmaker')->model('seller');
        $this->accountModel = app::get('sysmaker')->model('account');
    }

    /**
     * 保存创客
     * @param $data
     * @param $msg
     * @return bool
     */
    public function saveSeller($data, &$msg)
    {
        if($this->_checkRepAccount($data))
        {
            $msg = '手机号已存在';
            return false;
        }

        try{
            $db = app::get('sysmaker')->database();
            $db->beginTransaction();//开启事务

            $sellerData = array('mobile' => $data['mobile'], 'name' => $data['name']);
            $data['login_account'] = empty($data['login_account']) ? $data['mobile'] : $data['login_account'];
            $accountData = array('login_account' => $data['login_account']);

            if(!$data['seller_id'])
            {
                // 新增
                $msg = '添加创客成功';
                $time = strtotime('now');

                $accountData['login_password'] = hash::make($data['login_password']);
                $accountData['created_time'] = $time;
                $sellerId = $this->accountModel->insert($accountData);
                if(!$sellerId)
                {
                    throw new \LogicException('添加创客帐号失败');
                }

                $sellerData['seller_id'] = $sellerId;
                $id = $this->sellerModel->insert($sellerData);
                if(!$id)
                {
                    throw new \LogicException('添加创客信息失败');
                }

                if($data['shop_id'])
                {
                    $bindData = array('seller_id'=>$sellerId, 'shop_id'=>$data['shop_id']);
                    $bool = $this->addBindShop($bindData, $msg);
                    if(!$bool)
                    {
                        throw new \LogicException($msg);
                    }
                }

                if($data['third'])
                {
                    // 保存第三方数据
                    $objHhirdpartyinfo = kernel::single('sysmaker_data_thirdpartyinfo');
                    $bool = $objHhirdpartyinfo->saveThirdData($data['third'], $msg);
                    if(!$bool)
                    {
                        throw new \LogicException($msg);
                    }

                    if($data['third']['userflag'])
                    {
                        // 保存信任登录数据
                        $objTrustinfo = kernel::single('sysmaker_data_trustinfo');
                        $trustData = array(
                            'seller_id' => $sellerId,
                            'user_flag' => $data['third']['userflag'],
                            'flag' => $data['third']['flag'],
                        );
                        $trustId = $objTrustinfo->addTrustInfoData($trustData, $msg);
                        if(!$trustId)
                        {
                            throw new \LogicException($msg);
                        }
                    }
                }
            }
            else
            {
                // 更新
                $msg = '更新创客成功';
                $filter = array('seller_id'=>$data['seller_id']);
                $sellerId = $this->accountModel->update($accountData, $filter);
                if(!$sellerId)
                {
                    throw new \LogicException('更新创客帐号失败');
                }

                $id = $this->sellerModel->update($sellerData, $filter);
                if(!$id)
                {
                    throw new \LogicException('更新创客信息失败');
                }
            }

            $db->commit();//提交
        }catch (Exception $e){
            $db->rollback();//回滚
            $msg = $e->getMessage();
            return false;
        }

        return $sellerId;
    }

    /**
     * 保存密码
     * @param $data
     * @param $msg
     * @return bool
     */
    public function savePwd($data, &$msg)
    {
        if(!$data['seller_id'] && $data['login_password'])
        {
            $msg = '参数错误';
            return false;
        }

        $accountData = array('login_password'=>hash::make($data['login_password']));
        $filter = array('seller_id'=>$data['seller_id']);

        $sellerId = $this->accountModel->update($accountData, $filter);
        if(!$sellerId)
        {
            $msg = '密码更新失败';
            return false;
        }

        $msg = '密码更新成功';
        return true;
    }

    /**
     * 保存店铺数据
     * @param $data
     * @param $msg
     * @return bool
     */
    public function saveBindShop($data, &$msg)
    {
        if(!$data['seller_id'] && $data['shop'])
        {
            $msg = '参数错误';
            return false;
        }

        try{
            $db = app::get('sysmaker')->database();
            $db->beginTransaction();//开启事务
            
            $filter = array('seller_id'=>$data['seller_id']);
            // 1.删除旧数据
            $objMdlRelShop = app::get('sysmaker')->model('shop_rel_seller');
            $objMdlRelShop->delete($filter);

            foreach ($data['shop'] as $shop)
            {
                $shop['seller_id'] = $data['seller_id'];
                // 2.新增新数据
                $r = $objMdlRelShop->insert($shop);
                if(!$r)
                {
                    throw new \LogicException('绑定店铺失败');
                }
            }
            $db->commit();//提交
        }catch (Exception $e) {
            $db->rollback();//回滚
            $msg = $e->getMessage();
            return false;
        }

        $msg = '绑定店铺成功';
        return true;
    }

    /**
     * 添加创客绑定店铺
     * @param $data
     * @param $msg
     * @return bool
     */
    public function addBindShop($data, &$msg)
    {
        if(!$data['seller_id'] && $data['shop_id'])
        {
            $msg = '参数错误';
            return false;
        }

        $objMdlRelShop = app::get('sysmaker')->model('shop_rel_seller');
        $data['created_time'] = time();
        $r = $objMdlRelShop->insert($data);

        if(!$r)
        {
            $msg = '绑定店铺失败';
            return false;
        }

        $msg = '绑定店铺成功';
        return true;
    }

    /**
     * 保存审核操作
     * @param $sellerId
     * @param $data
     * @param $msg
     * @return bool
     */
    public function saveCheck($sellerId, $data, &$msg)
    {
        $msg = '操作成功';
        $params = array(
            'status' => $data['status'],
            'reason' => $data['reason'],
        );

        if($params['status'] == 'success') $params['reason'] = '审核通过';

        $r = $this->accountModel->update($params, array('seller_id'=>$sellerId));
        if(!$r)
        {
            $msg = '操作失败';
            return false;
        }

        return true;
    }

    /**
     * 判断帐号(手机号)是否已存在
     * @param $data
     * @return bool true已存在
     */
    protected function _checkRepAccount($data)
    {
        $filter['login_account'] = $data['mobile'];
        if(!empty($data['seller_id']))
        {
            $filter['seller_id|noequal'] = $data['seller_id'];
        }

        $account = $this->accountModel->getRow('seller_id', $filter);

        if($account)
        {
            return true;
        }

        return false;
    }
}