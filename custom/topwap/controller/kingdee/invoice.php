<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/12/5
 * Time: 9:39
 */

class topwap_ctl_kingdee_invoice extends topwap_controller
{
    public function __construct()
    {

    }

    public function invoiceInfo()
    {
        $encrypt_tid = input::get('tid');
        $tid = base64_decode(urldecode($encrypt_tid));
        $page_data['tid'] = $tid;
        return view::make('topwap/kingdee/invoice/info.html', $page_data);
    }

    /**
     * @return base_view_object_interface|string
     * 提交发票信息并开票
     */
    public function invoiceSubmit()
    {
        $post_data = input::get();

        if($post_data['invoice_name_type'])
        {
            $invoice_data['invoice_name'] = $post_data['company_name'];
            $invoice_data['contact_way'] = $post_data['company_phone'];
            $invoice_data['registration_number'] = $post_data['registration_number'];

            $val_field = ['invoice_name'=> trim($post_data['company_name']),'contact_way' => trim($post_data['company_phone']),'trade_mobile' => trim($post_data['trade_mobile']),'registration_number'=> trim($post_data['registration_number'])];
            $val_rule  = ['invoice_name'=>'required','contact_way' => 'required','trade_mobile' => 'required|mobile','registration_number'=>'required'];
            $val_msg   = ['invoice_name'=>'请输入企业名称!','contact_way' => '请填写正确的个人手机号','trade_mobile' => '请填写正确的下单手机号','registration_number'=>'税号必填且为数字'];
        }
        else
        {
            $invoice_data['invoice_name'] = $post_data['person_name'];
            $invoice_data['contact_way'] = $post_data['personal_mobile'];
            $invoice_data['registration_number'] = $post_data['identity_card_number'];

            $val_field = ['invoice_name'=> trim($post_data['person_name']),'contact_way' => trim($post_data['personal_mobile']),'trade_mobile' => trim($post_data['trade_mobile']),'registration_number'=> trim($post_data['identity_card_number'])];
            $val_rule  = ['invoice_name'=>'required','contact_way' => 'required','trade_mobile' => 'required|mobile','registration_number'=>'required'];
            $val_msg   = ['invoice_name'=>'请输入用户名!','contact_way' => '请填写正确的企业手机号','trade_mobile' => '请填写正确的下单手机号','registration_number'=>'身份证号格式不正确'];
        }

        $validator = validator::make($val_field, $val_rule, $val_msg);
        if ($validator->fails())
        {
            $messages = $validator->messagesInfo();
            foreach( $messages as $error )
            {
                return $this->splash('error',null,$error[0]);
            }
        }

        try
        {
            $trade_mobile = trim($post_data['trade_mobile']);
            $userModel = app::get('sysuser')->model('account');
            $user_info = $userModel->getRow('user_id', ['mobile' => $trade_mobile]);
            $tradeModel = app::get('systrade')->model('trade');

            $filter['tid'] = $tid = trim($post_data['tid']);
            $filter['user_id'] = $user_info['user_id'];
            $filter['status'] = 'TRADE_FINISHED';

            $trade_count = $tradeModel->count($filter);
            if($trade_count<=0)
            {
                throw new Exception('没有符合开票要求的订单');
            }

            $tradeInvoiceModel = app::get('systrade')->model('trade_invoice');
            $trade_invoice_info = $tradeInvoiceModel->getRow('tid,push_status,invoice_type',['tid' => $post_data['tid']]);
            //不是开具电子票的
            if(isset($trade_invoice_info['invoice_type']) && $trade_invoice_info['invoice_type'] != 'normal')
            {
                throw new Exception('此订单不能开具电子发票');
            }
            if($trade_invoice_info['push_status'] != '2')
            {
                $invoice_data['invoice_type'] = 'normal';
                $invoice_data['tid'] = $tid;
                $invoice_data['invoice_name_type'] = $post_data['invoice_name_type'];

                $invoice_result = kernel::single('sysclearing_kingdeeinvoice')->createInvoice($invoice_data);
            }
            $redirect_url = url::action('topwap_ctl_kingdee_invoice@invoiceSuccess',['tid'=>$tid]);
            return $this->splash('success', $redirect_url, '成功');
        }
        catch(Exception $e) {
            return $this->splash('error',null,$e->getMessage());
        }
    }

    public function invoiceSuccess()
    {
        $tid = input::get('tid');
        $trade_invoice_info = [];
        if($tid)
        {
            $tradeInvoiceModel = app::get('systrade')->model('trade_invoice');
            $trade_invoice_info = $tradeInvoiceModel->getRow('shop_id,invoice_name,invoice_code,invoice_no,push_time,pdf_url',['tid' => $tid,'push_status' => '2']);
        }
        $page_data['data'] = $trade_invoice_info;
        return view::make('topwap/kingdee/invoice/success.html', $page_data);
    }
}