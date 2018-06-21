<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/09/01
 * Time: 17:21
 */
class sysbankmember_mdl_bank extends dbeav_model{

    var $has_many = array(

    );

    public $defaultOrder = array('create_time',' DESC');

    /**
     * 构造方法
     * @param object model相应app的对象
     * @return null
     */
    public function __construct($app){
        parent::__construct($app);
    }

    /**
     * 根据银行ID，获取对应的银行数据
     * @param $bankId
     * @return array 如果存在则返回属性和属性值数据，不存在返回空数组
     */
    public function getbankRow($bankId)
    {
        $bankInfo = $this->getRow('*',array('bank_id'=>$bankId));
        if( empty($bankInfo) ) return array();
        return $bankInfo;
    }

    /**
     * 前台获取银行选项
     * @return mixed
     */
    public function getbankOptions(){
        $where = '';
        $sql = "select bank_id, bank_name from sysbankmember_bank".$where;
        $row = app::get('base')->database()->executeQuery($sql)->fetchall();
        
        return $row;
    }
    
    public function getbankIdBybankName($bankName){
        $bankId = 0;
        $where = " name = '{$bankName}'";
        $sql = "select bank_id from sysbankmember_bank where".$where;
        $row = app::get('base')->database()->executeQuery($sql)->fetch();

        if(!empty($row['bank_id']))
        {
            $bankId = $row['bank_id'];
        }
        
        return $bankId;
    }
}