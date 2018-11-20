<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/9/6
 * Time: 19:02
 */

class topshop_ctl_cash_manage extends topshop_controller
{
    /**
     * @return html
     * 礼金配置
     */
    public function config()
    {
        $params['shop_id']=$this->shopId;
        $page_data=app::get('topshop')->rpcCall('shop.cash.setting.get',$params);
        $page_data['is_lm'] = $this->isLm;
        return $this->page('topshop/cash/config.html', $page_data);
    }

    /**
     * @return string
     * 保存礼金设置
     */
    public function save()
    {
        $post_data = input::get();
        $post_data['shop_id'] = $this->shopId;
        try
        {
            $cash_model = app::get('sysshop')->model('cash_setting');
            $cash_model->save($post_data);
            return $this->splash('success',null,'保存成功');
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg);
        }
    }

    /**
     * @return mixed
     * ajax获取变更日志
     */
    public function ajaxCashChangeLogs()
    {
        $page_data = $this->commonLogData();
        return view::make('topshop/cash/cash_log.html', $page_data);
    }
    /**
     * 获取礼金变更记录
     */
    public function cashChangeLogs()
    {
        $page_data = $this->commonLogData();
        return $this->page('topshop/cash/log_cash_index.html', $page_data);
    }

    /**
     * @return mixed
     * 通用日志数据
     */
    private function commonLogData()
    {
        $limit = 10;
        $shop_id = $this->shopId;
        $post_data = input::get();
        $current = $post_data['pages'] ? $post_data['pages'] : 1;
        $offset = ($current-1) * $limit;
        if($post_data['modified_time'])
        {
            $times = array_filter(explode('-',$post_data['modified_time']));
            if($times)
            {
                $post_data['time_start'] = strtotime($times['0']);
                $post_data['time_end'] = strtotime($times['1'])+86400;
            }
        }
        try
        {
            $logModel = app::get('sysuser')->model('user_lijinlog');
            $filter['shop_id'] = $shop_id;
            if($post_data['time_start'])
            {
                $filter ['modified_time|bthan'] = strtotime ($post_data ['time_start']);
                unset($post_data['time_start']);
            }

            if($post_data['time_end'])
            {
                $filter ['modified_time|sthan'] = strtotime ($post_data ['time_end']);
                unset($post_data['time_end']);
            }
            $cashLogList = $logModel->getList('*',$filter, $offset, $limit);
            $user_id_array = array_column($cashLogList,'user_id');
            $user_id_array = array_filter($user_id_array);
            $userModel = app::get('sysuser')->model('account');
            $user_info = $userModel->getList('user_id, mobile',['user_id|in' => $user_id_array]);
            $user_info = array_bind_key($user_info, 'user_id');
            foreach($cashLogList as &$list)
            {
                $list['user_mobile'] = $user_info[$list['user_id']]['mobile'];
            }
            $log_count = $logModel->count($filter);

            //发放总值
            $grant_filter['behavior_type'] = 'obtain';
            $grant_filter['shop_id'] = $shop_id;
            $grant_count = $logModel->getRow('SUM(lijinlog_id) as grant_count', $grant_filter)['grant_count'];
            //领取总值
            $draw_filter['behavior_type'] = 'consume';
            $draw_filter['shop_id'] = $shop_id;
            $draw_count = $logModel->getRow('SUM(lijinlog_id) as draw_count', $draw_filter)['draw_count'];
        }
        catch(Exception $e)
        {
            $log_count = 0;
            $total = 0;
            $cashLogList = [];
        }
        if($log_count>0) $total = ceil($log_count/$limit);
        $filter['pages'] = time();
        $page_data['pagers'] = array(
            'link'=>url::action('topshop_ctl_cash_manage@cashChangeLogs', $filter),
            'current'=>$current,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>$filter['pages'],
        );
        if(!$grant_count)
        {
            $grant_count = 0;
        }
        if(!$draw_count)
        {
            $draw_count = 0;
        }
        $page_data['cashLogList'] = $cashLogList;
        $page_data['grant_count'] = $grant_count;
        $page_data['draw_count'] = $draw_count;
        return $page_data;
    }
}