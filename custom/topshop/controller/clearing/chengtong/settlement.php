<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topshop_ctl_clearing_chengtong_settlement extends topshop_controller
{
    public function billDetail()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('城通物流结算');
        $postSend = input::get();
        $filter['status'] = 'succ';
        if($postSend['timearea'])
        {
            $pagedata['timearea'] = $postSend['timearea'];
            $timeArray = explode('-', $postSend['timearea']);
            $filter['create_time|than']  = strtotime($timeArray[0]);//开始前一天
            $filter['create_time|lthan'] = strtotime($timeArray[1]) + 3600*24;//结束后一天
        }
        else
        {
            $filter['create_time|than']  = strtotime(date('Y/m/d',time()))-3600*24;//开始前一天
            $filter['create_time|lthan'] = strtotime(date('Y/m/d',time()));//结束后一天
            $pagedata['timearea'] = date('Y/m/d', time()-3600*24*7) . '-' . date('Y/m/d', time()-3600*24);
            $postSend['timearea'] = $pagedata['timearea'];
        }

        if($postSend['delivery_type']) $filter['delivery_type'] = $pagedata['delivery_type'] = $postSend['delivery_type'];
        if($postSend['bill_status']) $filter['bill_status'] = $pagedata['bill_status'] = $postSend['bill_status'];

        $limit = 20;
        $count = app::get('syslogistics')->model('delivery_aggregation')->count($filter);
        $totalPage = ceil($count/$limit);
        $page_no = $postSend['pages'] ? $postSend['pages'] : 1;
        $offset = ($page_no-1)*$limit;
        $data = app::get('syslogistics')->model('delivery_aggregation')->getList('*',$filter,$offset,$limit,'create_time desc');
        foreach($data as &$v)
        {
            $v['tid_array'] = explode(',', $v['tids']);
        }
        $pagedata['data']   = $data;
        $pagedata['limits'] = $limit;
        $pagedata['pages']  = $page_no;
        $postSend['pages']  = time();
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_clearing_chengtong_settlement@billDetail',$postSend),
            'current' => $page_no,
            'total'   => $totalPage,
            'use_app' => 'topshop',
            'token'   => $postSend['pages']
        );
        return $this->page('topshop/sysstat/chengtong/confirm_bill.html', $pagedata);
    }

    /**
     * @return html
     * 城通物流运费列表
     */
    public function postFeeDetail()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('城通运费结算');
        $postSend = input::get();
        //只有成功到达和拒收状态的才有运费核对和显示
        $filter['status|in'] = ['succ','rppm'];
        if($postSend['timearea'])
        {
            $pagedata['timearea'] = $postSend['timearea'];
            $timeArray = explode('-', $postSend['timearea']);
            $filter['create_time|than']  = strtotime($timeArray[0]);//开始前一天
            $filter['create_time|lthan'] = strtotime($timeArray[1]) + 3600*24;//结束后一天
        }
        else
        {
            $filter['create_time|than']  = strtotime(date('Y/m/d',time()))-3600*24;//开始前一天
            $filter['create_time|lthan'] = strtotime(date('Y/m/d',time()));//结束后一天
            $pagedata['timearea'] = date('Y/m/d', time()-3600*24*7) . '-' . date('Y/m/d', time()-3600*24);
            $postSend['timearea'] = $pagedata['timearea'];
        }

        if($postSend['post_fee_compare_status']) $filter['post_fee_compare_status'] = $pagedata['post_fee_compare_status'] = $postSend['post_fee_compare_status'];

        $limit = 20;
        $count = app::get('syslogistics')->model('delivery_aggregation')->count($filter);
        $totalPage = ceil($count/$limit);
        $page_no = $postSend['pages'] ? $postSend['pages'] : 1;
        $offset = ($page_no-1)*$limit;
        $data = app::get('syslogistics')->model('delivery_aggregation')->getList('*',$filter,$offset,$limit,'create_time desc');
        $pagedata['data']   = $data;
        $pagedata['limits'] = $limit;
        $pagedata['pages']  = $page_no;
        $postSend['pages']  = time();
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_clearing_chengtong_settlement@postFeeDetail',$postSend),
            'current' => $page_no,
            'total'   => $totalPage,
            'use_app' => 'topshop',
            'token'   => $postSend['pages']
        );
        return $this->page('topshop/sysstat/chengtong/confirm_post_fee.html', $pagedata);
    }


    /**
     * 城通物流货款对账
     * @return bool
     */
    public function balanceAccount()
    {
        try
        {
            $aggreModel = app::get('syslogistics')->model('delivery_aggregation');
            $bill_status = ['UN_CHECKED','CHECKED_FAIL'];
            $aggreData = $aggreModel->getList('*', ['bill_status|in' => $bill_status, 'status|in' => ['succ','rppm']]);
            $logi_nos = array_column($aggreData, 'logi_no');
            $logi_nos = array_filter($logi_nos);
            $log = [];
            $succ_logi_nos = [];
            foreach($aggreData as $ak => $av)
            {
                if($av['receivable_payment'] != $av['amount_real'])
                {
                    continue;
                }
                $succ_logi_nos[] = $av['logi_no'];
            }

            $fail_logi_nos = array_diff($logi_nos, $succ_logi_nos);
            if($succ_logi_nos)
            {
                $update_data['bill_status'] = 'CHECKED_SUCC';
                $aggreModel->update($update_data, ['logi_no|in' => $succ_logi_nos]);
            }
            if($fail_logi_nos)
            {
                $update_data['bill_status'] = 'CHECKED_FAIL';
                $aggreModel->update($update_data, ['logi_no|in' => $fail_logi_nos]);
            }
            return $this->splash('success',"","货款对账成功",true);
        }
        catch(Exception $e)
        {
            return $this->splash('error',"","货款对账失败",true);
        }
    }
}
