<?php
/**
 * User: xinyufeng
 * Date: 2018/10/12
 * Time: 18:05
 * Desc: 接口测试
 */
class syslmgw_emic_test extends syslmgw_emic_controller {
    
    public function index()
    {
        $params = array(
            'subAccountSid' => 'a0b511c7486b7edab9e2a04a0c97ff56',
            //'startTime' => '20180902000000',
            //'endTime' => '20180915000000',
            //'startTime' => date('Ymd', strtotime('now')) . '000000',
            //'endTime' => date('Ymd', strtotime('+1 day')) . '000000',
            'maxNumber' => 500,
        );
        $res = $this->Applications_billList($params);
        var_dump($res);die;
        if($res['number'])  //有记录
        {
            foreach ($res['records'] as $v)
            {

                if($v['SubDetails'])
                {
                    foreach ($v['SubDetails'] as $v_sub)
                    {
                        var_dump($v_sub);
                    }
                    unset($v['SubDetails']);
                }
                else
                {
                    var_dump($v);
                }

            }
        }

    }
    
}