<?php
/**
 * User: xinyufeng
 * Date: 2018/10/17
 * Time: 18:05
 * Desc: 来电处理信息统计
 */
class syslmgw_emic_todaycallin extends syslmgw_emic_controller {

    /**
     * 统计并发
     */
    public function concurrence()
    {
        $data = array(
            'curr_num' => 0,
            'max_num' => 0,
            'history_num' => 0
        );

        $today_time = strtotime('now');//当前时间戳
        $today_date = date('Ymd', $today_time);//今天日期
        $concurrenceMld = app::get('syslmgw')->model('concurrence');
        $filter = array('date' => $today_date, 'type' => 'callin');
        $res = $concurrenceMld -> getRow('*', $filter);

        if($res)
        {
            $data['curr_num'] = $res['curr_num'];
            $data['max_num'] = $res['max_num'];
        }

        $filter_his = array('type' => 'callin');
        $orderBy_his = 'max_num DESC';
        $res_his = $concurrenceMld -> getRow('max_num', $filter_his, $orderBy_his);
        if($res_his)
        {
            $data['history_num'] = $res_his['max_num'];
        }

        $this->splash('200',$data,'请求成功!');
    }
}