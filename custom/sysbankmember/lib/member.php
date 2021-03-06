<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2017/9/4
 * Time: 9:32
 */
class sysbankmember_member {

    /**
     * 判断是否有重复银行卡号
     * @param $data
     * @return bool 没有重复返回 false
     */
    public function existCardNumber($data)
    {
        $where = " card_number = '{$data['card_number']}'";
        if(!empty($data['member_id'])){
            $where .= " and member_id != '{$data['member_id']}'";
        }
        $sql = "select bank_id from sysbankmember_member where".$where;
        $row = app::get('base')->database()->executeQuery($sql)->fetch();

        if(empty($row)) return false;
        return true;
    }

    /**
     * 判断银行卡号是否符合基础卡号
     * @param $card_number
     * @return bool
     */
    public function isBaseCardNumber($card_number)
    {
        $base_card = substr($card_number, 0, 6) . substr($card_number, -4);
        $memberModel = app::get('sysbankmember')->model('member');
        $info = $memberModel->getRow('member_id', array('card_number'=>$base_card));
        if(!empty($info)) return true;
        return false;
    }
}