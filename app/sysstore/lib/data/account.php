<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/3
 * Time: 14:14
 */
class sysstore_data_account {
    public $accountModel = null;
    
    public function __construct()
    {
        $this->accountModel = app::get('sysstore')->model('account');
    }

    /**
     * 保存
     * @param $data
     * @param $msg
     * @return bool
     */
    public function saveAccount($data, &$msg)
    {
        if( !$this->__check($data,$msg) )  return false;
        if( !$this->checkRepeatData($data,$msg) )  return false;

        $data['modified_time'] = time();
        if(!empty($data['login_password'])) {
            $data['login_password'] = hash::make($data['login_password']);
        }

        if(empty($data['account_id'])){
            //add
            $data['created_time'] = $data['modified_time'];
            $accountId = $this->accountModel->insert($data);
            if( !$accountId )
            {
                $msg = app::get('sysstore')->_('用户添加失败');
                return false;
            }

            $msg = app::get('sysstore')->_('用户添加成功');

            return $accountId;
        }else{
            //update
            if( !$this->accountModel->update($data, array('account_id'=>$data['account_id'])) )
            {
                $msg = app::get('sysstore')->_('用户更新失败');
                return false;
            }

            $msg = app::get('sysstore')->_('用户更新成功');

            return $data['account_id'];
        }
    }

    /**
     * 根据帐号id获取商城id
     * @param $accountId
     * @return int
     */
    public function getStoreId($accountId)
    {
        $storeId = 0;
        if(empty($accountId)) return $storeId;

        $accountInfo = $this->accountModel->getRow('account_id', array('account_id' => $accountId));
        if(!empty($accountInfo['account_id']))
        {
            $storeId = $accountInfo['account_id'];
        }
        return $storeId;
    }

    /**
     * 验证重复数据
     * @param $data
     * @param $msg
     * @return bool
     */
    public function checkRepeatData($data, &$msg)
    {
        $repeatFields = array(
            'login_account'=>'用户名已存在',
        );

        $where = '';
        if($data['account_id']){
            $where .= " account_id <> {$data['account_id']} AND";
        }

        $or = array();
        $filters = array();
        foreach ($repeatFields as $k => $rf){
            $or[] = "{$k} = '{$data[$k]}'";
            $filters[] = $k;
        }
        $where .= " (".implode(' OR ', $or).")";
        $filter = implode(',', $filters);

        $db = app::get('sysstore')->database();
        $queryBuider = $db->createQueryBuilder();
        $builder = $queryBuider->select($filter)
            ->from($this->accountModel->table_name(true))
            ->where($where);
        $row = $builder->execute()->fetch();
        if(empty($row)){
            return true;
        }

        foreach ($row as $k => $r){
            if($r == $data[$k]){
                $msg = $repeatFields[$k];
                return false;
            }
        }
    }

    /**
     * 验证数据函数
     * @param $data
     * @param $msg
     * @return bool
     */
    private function __check($data, &$msg)
    {
        if( empty( $data['login_account']) )
        {
            $msg = app::get('sysstore')->_('用户登录名不能为空');
            return false;
        }

        if(empty($data['account_id'])){
            if( empty( $data['login_password']) )
            {
                $msg = app::get('sysstore')->_('用户登录密码不能为空');
                return false;
            }
        }

        if( empty( $data['store_id']) )
        {
            $msg = app::get('sysstore')->_('商城id不能为空');
            return false;
        }

        return true;
    }
}