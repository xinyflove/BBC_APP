<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2017/9/20
 * Time: 10:34
 */
class sysbankmember_account {
    /**
     * 判断是否有重复银行卡号
     * @param $data
     * @return bool 没有重复返回 false
     */
    public function existCardNumber($data)
    {
        $where = " card_number = '{$data['card_number']}'";
        if(!empty($data['user_id'])){
            $where .= " and user_id != '{$data['user_id']}'";
        }
        $sql = "select account_id from sysbankmember_account where".$where;
        $row = app::get('base')->database()->executeQuery($sql)->fetch();

        if(empty($row)) return false;
        return true;
    }
}