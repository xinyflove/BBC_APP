<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2017/09/01
 * Time: 17:04
 */
class sysbankmember_bank {

    /**
     * 判断是否有重复银行名称
     * @param $data
     * @return bool 没有重复返回 false
     */
    public function existBankName($data)
    {
        $where = " bank_name = '{$data['bank_name']}'";
        if(!empty($data['bank_id'])){
            $where .= " and bank_id != '{$data['bank_id']}'";
        }
        $sql = "select bank_id from sysbankmember_bank where".$where;
        $row = app::get('base')->database()->executeQuery($sql)->fetch();

        if(empty($row)) return false;
        return true;
    }

    /**
     * 判断是否有重复银行代码
     * @param $data
     * @return bool 没有重复返回 false
     */
    public function existBankCode($data)
    {
        $where = " bank_code = '{$data['bank_code']}'";
        if(!empty($data['bank_id'])){
            $where .= " and bank_id != '{$data['bank_id']}'";
        }
        $sql = "select bank_id from sysbankmember_bank where".$where;
        $row = app::get('base')->database()->executeQuery($sql)->fetch();

        if(empty($row)) return false;
        return true;
    }
}