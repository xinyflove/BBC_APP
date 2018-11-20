<?php
/**
 * Created by PhpStorm.
 * User: xinyufeng
 * Date: 2018/10/19
 * Time: 10:25
 * Desc: 定时统计通话并发数
 */
class syslmgw_tasks_concurrenceStat extends base_task_abstract implements base_interface_task{

    private $subAccountSid = 'a0b511c7486b7edab9e2a04a0c97ff56';

    public function exec($params=null)
    {
        $concurrenceMld = app::get('syslmgw')->model('concurrence');
        $today_time = strtotime('now');//当前时间戳
        $today_date = date('Ymd', $today_time);//今天日期

        $filter_c = array(
            'type' => 'callin',
            'subAccountSid' => $this->subAccountSid,
            'date' => $today_date,
        );
        $row_c = $concurrenceMld -> getRow('stat_time,max_num', $filter_c);//获取当天统计数据

        if($row_c)
        {
            $max_num = $row_c['max_num'];
            $_start_minute = date('YmdHi', $row_c['stat_time']) . '00';
            $stat_time = $row_c['stat_time'];
        }
        else
        {
            $max_num = 0;
            $_start_minute = $today_date . '000000';
            $stat_time = strtotime($_start_minute);
        }
        $_end_minute = date('YmdHi', $today_time) . '00';

        //获取当前时间段和上次统计时间之内的通话数据，把createTime截取到分，进行分组查询
        $db = app::get('base')->database();
        $qb = $db->createQueryBuilder();
        $list = $qb->select("COUNT(billId) AS num, billId")
            ->from('syslmgw_bills')
            ->where("subAccountSid = '{$this->subAccountSid}'")
            ->andWhere("createTime >= '{$_start_minute}'")
            ->andWhere("createTime < '{$_end_minute}'")
            ->groupBy('LEFT(createTime, 12)')
            ->orderBy('createTime', 'ASC')
            ->execute()->fetchAll();

        $curr_num = 0;
        if($list)
        {
            $stat_time = $today_time;
            foreach ($list as $lv)
            {
                $curr_num = $lv['num'];
                if($lv['num'] > $max_num)
                {
                    $max_num = $lv['num'];
                }
            }
        }

        $data = array(
            'type' => 'callin',
            'max_num' => $max_num,
            'curr_num' => $curr_num,
            'date' => $today_date,
            'stat_time' => $stat_time,
            'subAccountSid' => $this->subAccountSid,
        );

        if($row_c)
        {
            //修改
            $filter = array('date' => $today_date, 'type' => 'callin');
            $concurrenceMld->update($data, $filter);
        }
        else
        {
            $concurrenceMld->save($data);
        }
    }
}