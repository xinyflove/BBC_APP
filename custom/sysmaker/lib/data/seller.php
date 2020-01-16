<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-13
 * Desc: 创客数据处理
 */
class sysmaker_data_seller {

    public $sellerModel = null;
    public $accountModel = null;
    private $__pInfo = array();

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
                if(app::get('sysmall')->getConf('sysmall.setting.hosts_check') == 'true')
                {
                    $accountData['status'] = 'pending';
                }
                else
                {
                    $accountData['status'] = 'success';
                    $accountData['reason'] = '平台自动审核通过';
                }
                $sellerId = $this->accountModel->insert($accountData);
                if(!$sellerId)
                {
                    throw new \LogicException('添加创客帐号失败');
                }

                $sellerData['seller_id'] = $sellerId;
                $sellerData['id_card_no'] = $data['id_card_no'];
                $sellerData['registered'] = $data['registered'];
                $sellerData['pid'] = $data['pid'];
				/*add_2019/8/9_by_wanghaichao_start*/
				//添加身份证正反面
				$sellerData['front_img']=$data['front_img'];
				$sellerData['reverse_img']=$data['reverse_img'];
				$sellerData['cart_number']=$data['cart_number'];
				/*add_2019/8/9_by_wanghaichao_end*/
				
                $id = $this->sellerModel->insert($sellerData);
                if(!$id)
                {
                    throw new \LogicException('添加创客信息失败');
                }

                if($data['shop_id'])
                {
                    $bindData = array('seller_id'=>$sellerId, 'shop_id'=>$data['shop_id'], 'group_id'=>$data['group_id']);
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
                        makerAuth::setTrustId($trustId);
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

            $created_time = time();
            foreach ($data['shop'] as $shop)
            {
                $shop['seller_id'] = $data['seller_id'];
                $shop['created_time'] = $created_time;
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
        $data['status'] = 'pending';
        $data['created_time'] = time();
        empty($data['group_id']) && $data['group_id'] = 0;
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

    /**
     * 获取商家店铺信息
     *
     * @param $sellerId
     * @return mixed
     */
    public function getSellerInfo($sellerId)
    {
        if (empty($sellerId)) {
            throw new LogicException('参数seller_id不能为空');
        }
        $fields = '*';
        $filter['seller_id'] = $sellerId;
        $seller = $this->sellerModel->getRow($fields, $filter);
        return $seller;
    }

    /**
     * 主持人是否存在
     *
     * @param $sellerId
     * @return bool
     * @author zhangshu
     * @date 2018/11/22
     */
    public function isSellerExist($sellerId)
    {
        if (empty($sellerId)) {
            throw new LogicException('参数seller_id不能为空');
        }
        $filter['seller_id'] = $sellerId;
        $count = $this->sellerModel->count($filter);
        return $count > 0 ? true : false;
    }

    /**
     * 删除创客帐号等信息
     * @param $filter
     * @return bool
     */
    public function delete($filter)
    {
        if( empty($filter['seller_id']) )
        {
            throw new \LogicException('参数seller_id不为空');
            return false;
        }
        if( is_object($filter['seller_id']) )
        {
            throw new \LogicException('参数seller_id类型错误');
            return false;
        }
        if( !is_array($filter['seller_id']) )
        {
            $filter['seller_id'] = array($filter['seller_id']);
        }

        $filter = array('seller_id|in' => $filter['seller_id']);
        $res = $this->accountModel->delete($filter);
        if(!$res)
        {
            throw new \LogicException('删除用户帐号失败');
            return false;
        }
        $res = $this->sellerModel->delete($filter);
        if(!$res)
        {
            throw new \LogicException('删除用户帐号信息失败');
            return false;
        }
        $shoprelsellerMdl = app::get('sysmaker')->model('shop_rel_seller');
        $shoprelsellerMdl->delete($filter);
        $selleritemMdl = app::get('sysmaker')->model('seller_item');
        $selleritemMdl->delete($filter);
        
        return true;
    }

    /**
     * 获取推荐人名字
     * @param $pid
     * @return mixed|string
     */
    public function getPName($pid)
    {
        if($pid)
        {
            if(!$this->__pInfo[$pid])
            {
                $pinfo = $this->sellerModel->getRow('name', array('seller_id'=>$pid));
                if(!$pinfo) return '';
                $this->__pInfo[$pid] = $pinfo['name'];
            }
            return $this->__pInfo[$pid];
        }
        return '';
    }

    /**
     * 通过手机号获取推荐人id
     * @param $mobile
     * @return bool
     */
    public function getPIdByMobile($mobile)
    {
        if($mobile)
        {
            $pinfo = $this->sellerModel->getRow('seller_id', array('mobile'=>$mobile));
            if($pinfo)
            {
                return $pinfo['seller_id'];
            }
        }
        
        return false;
    }
}