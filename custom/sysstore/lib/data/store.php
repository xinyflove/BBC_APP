<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/3
 * Time: 14:14
 */
class sysstore_data_store {
    public $storeModel = null;
    
    public function __construct()
    {
        $this->storeModel = app::get('sysstore')->model('store');
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
        if(empty($data['store_id'])){
            //add
            $data['created_time'] = $data['modified_time'];
            $data['login_password'] = md5($data['login_password']);
            $storeId = $this->storeModel->insert($data);
            if( !$storeId )
            {
                $msg = app::get('sysstore')->_('商城添加失败');
                return false;
            }

            $msg = app::get('sysstore')->_('商城添加成功');

            return $storeId;
        }else{
            //update
            if(!empty($data['login_password'])) {
                $data['login_password'] = md5($data['login_password']);
            }

            if( !$this->storeModel->update($data, array('store_id'=>$data['store_id'])) )
            {
                $msg = app::get('sysstore')->_('商城更新失败');
                return false;
            }

            $msg = app::get('sysstore')->_('商城更新成功');

            return $data['store_id'];
        }
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
            'store_name'=>'商城名称已存在',
        );

        $where = '';
        if($data['store_id']){
            $where .= " store_id <> {$data['store_id']} AND";
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
            ->from($this->storeModel->table_name(true))
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
        if( empty( $data['store_name']) )
        {
            $msg = app::get('sysstore')->_('商城名称不能为空');
            return false;
        }

        if( empty( $data['relate_shop_id']) )
        {
            $msg = app::get('sysstore')->_('关联店铺不能为空');
            return false;
        }

        return true;
    }
}