<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/7/31
 * Time: 15:44
 */

class topwap_ctl__miniprogram_member
{
    public function ticket()
    {
        $userId = userAuth::id();
        try
        {
            $voucher_count=app::get('systrade')->model('voucher')->getList('voucher_id',array('user_id'=>$userId,'status'=>'WAIT_WRITE_OFF','end_time|bthan'=>time()));
            $voucher_count2=app::get('systrade')->model('voucher_history')->getList('voucher_id',array('user_id'=>$userId,'status'=>'WAIT_WRITE_OFF','end_time|bthan'=>time()));
            $voucher_count2=count($voucher_count2);
            $page_data['voucher_count']=count($voucher_count);
            $page_data['voucher_count']=$page_data['voucher_count']+$voucher_count2;
            $page_data['agent_voucher_count'] = app::get('systrade')->model('agent_vocher')->count(['user_id'=>$userId]);

            $return_data['err_no'] = 0;
            $return_data['data'] = $page_data;
            $return_data['message'] = '';
        }
        catch(Exception $e)
        {
            $return_data['err_no'] = 1001;
            $return_data['data'] = [];
            $return_data['message'] = $e->getMessage();
        }
        return response::json($return_data);exit;
    }
}