<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/22
 * Time: 15:08
 */
class ljstore_active_gift_gain extends ljstore_app_controller {

    public function getNum()
    {
        $data = array();
        $total = 0;
        $params = input::get();

        $mobile = trim($params['mobile']);
        if(empty($mobile))
        {
            $this->splash('1','参数错误,mobile不为空');
        }

        $accountMdl = app::get('sysuser')->model('account');
        $account = $accountMdl->getRow('user_id,mobile', array('mobile'=>$mobile));
        if(!empty($account))
        {
            $user_id = $account['user_id'];
            $currYear = date('Y', time());
            if(empty($params['start']))
            {
                $params['start'] = strtotime($currYear.'-01-01');
            }
            if(empty($params['end']))
            {
                $params['end'] = strtotime($currYear.'-12-31 23:59:59');
            }

            $filter = array();
            $filter['active_type'] = $params['active_type'] ? $params['active_type'] : 'blue_eyes';
            $filter['active_start_time'] = $params['start'];
            $filter['active_end_time'] = $params['end'];
            $filter['deleted'] = $params['deleted'] ? $params['deleted'] : 0;

            $offset = 0;
            $limit = 1000;
            $fields = 'sgg.active_id, COUNT(sgg.gift_gain_id) AS num';
            $db = app::get('base')->database();
            $sql = "SELECT {$fields} FROM sysactivityvote_gift_gain AS sgg LEFT JOIN sysactivityvote_active AS sa ON sgg.active_id = sa.active_id WHERE sgg.user_id = '{$user_id}' AND sa.active_start_time >= '{$filter['active_start_time']}' AND sa.active_end_time <= '{$filter['active_end_time']}' AND sa.active_type = '{$filter['active_type']}' AND sa.deleted = '{$filter['deleted']}' GROUP BY sgg.active_id";
            $result = $db->executeQuery($sql)->fetchAll();//获取多行数据

            foreach ($result as $r)
            {
                $data[$r['active_id']] = $r;
                $total += $r['num'];
            }
        }

        $result = array(
            'data' => $data,
            'total' => $total,
        );

        $this->splash('0',$result);
    }
}