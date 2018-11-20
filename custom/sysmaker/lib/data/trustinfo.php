<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-16
 * Desc: 信任登录数据处理
 */
class sysmaker_data_trustinfo {

    public $trustinfoModel = null;

    public function __construct()
    {
        $this->trustinfoModel = app::get('sysmaker')->model('trustinfo');
    }

    /**
     * 添加信任登录数据
     * @param $data
     * @param $msg
     * @return bool
     */
    public function addTrustInfoData($data, &$msg)
    {
        $msg = '添加信任登录数据成功';
        $trustData = array(
            'seller_id' => $data['seller_id'],
            'user_flag' => $data['user_flag'],
            'flag' => $data['flag'],
        );

        $trustId = $this->trustinfoModel->insert($trustData);

        if(!$trustId)
        {
            $msg = '添加信任登录数据失败';
            return false;
        }

        return true;
    }

    /**
     * 获取信任登录信息
     * @param $fields
     * @param $params
     * @return array
     */
    public function getTrustInfoData($fields, $params)
    {
        $trust = array();
        $filter = array();
        if($params['trust_id'])
        {
            $filter['trust_id'] = $params['trust_id'];
        }
        if($params['seller_id'])
        {
            $filter['seller_id'] = $params['seller_id'];
        }
        if($params['user_flag'])
        {
            $filter['user_flag'] = $params['user_flag'];
        }
        if($params['flag'])
        {
            $filter['flag'] = $params['flag'];
        }
        
        if(empty($filter)) return $trust;
        
        if(empty($fields)) $fields = '*';
        $trust = $this->trustinfoModel->getRow($fields, $filter);

        return $trust; 
    }

    /**
     * 删除信任登录数据
     * @param $sellerId
     * @param $msg
     * @return bool
     */
    public function delTrustInfoData($sellerId, &$msg)
    {
        if(!$sellerId)
        {
            $msg = '参数错误';
            return false;
        }

        $msg = '删除信任登录数据成功';
        $filter = array('seller_id' => $sellerId);
        $this->trustinfoModel->delete($filter);

        return true;
    }
}