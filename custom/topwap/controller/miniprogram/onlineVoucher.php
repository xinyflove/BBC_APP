<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/7/27
 * Time: 11:18
 */

class topwap_ctl_miniprogram_onlineVoucher
{
    public $voucherStatus = array(
        '0' => 'WAIT_WRITE_OFF',    //待核销
        '1' => 'WRITE_FINISHED',    //已核销
        '2' => 'GIVEN',      		//已赠送
        '3' => 'SUCCESS',      		//已退款
        '4'=> 'HAS_OVERDUE',        //已过期
    );

    protected $limit = 10;

    /**
     * @return mixed
     * 获取限量购卡券列表
     */
    public function voucherList()
    {
        $filter = input::get();
        $filter['voucher_status']='0';
        try
        {
            $page_data = $this->__getVoucher($filter);
            if($filter['keyword'])
            {
                $page_data['keyword']=$filter['keyword'];
            }
            $page_data['voucher_status']=0;
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

    /* action_name (par1, par2, par3)
	* 新的获取卡券的逻辑
	*/
    public function __getVoucher($postdata)
    {
        if(isset($this->voucherStatus[$postdata['voucher_status']]))
        {
            $status=$this->voucherStatus[$postdata['voucher_status']];
        }
        else
        {
            $status="";
        }
        $params['status'] = $status;
        $params['user_id'] = userAuth::id();
        $params['page_no'] = intval($postdata['pages']) ? intval($postdata['pages']) : 1;
        $params['page_size'] = intval($this->limit);
        $params['order_by'] = 'careated_time desc';
        $params['fields'] = '*';
        $voucher_list = app::get('topwap')->rpcCall('voucher.get.list',$params);
        $count = $voucher_list['count'];
        $voucher_list = $voucher_list['list'];
        foreach( $voucher_list as $key=>$row)
        {
            //获取商品图片
            $item_where = [
                'shop_id'=>$row['shop_id'],
                'item_id'=>$row['item_id'],
                'fields'=>[
                    'rows'=>'image_default_id,title'
                ]
            ];
            $item_info = app::get('topwap')->rpccall('item.get',$item_where);
            $voucher_list[$key]['pic_path'] = base_storager::modifier($item_info['image_default_id']);
            $voucher_list[$key]['item_title']=$item_info['title'];
            $shop= app::get('topwap')->rpcCall('shop.get',array('fields'=>'shop_mold,shop_name','shop_id'=>$row['shop_id']));
            $voucher_list[$key]['shop_name']=$shop['shop_name'];
            $voucher_list[$key]['end_time']=date('Y.m.d',$row['end_time']);
            $orderInfo=app::get('systrade')->model('order')->getRow('spec_nature_info',array('oid'=>$row['oid']));
            $voucher_list[$key]['spec_nature_info']=$orderInfo['spec_nature_info'];
        }
        $page_data['vouchers'] = $voucher_list;
        $page_data['count'] = $count;
        $page_data['title'] = "卡券";  //标题
        $page_data['voucher_status'] =$postdata['voucher_status'];  //状态
        $page_data['signPackage']=$this->getWxINfo();
        return $page_data;
    }

    /**
     * @return mixed
     * 获取限量购的券
     */
    public function ajaxVoucherList()
    {
        $postdata = input::get();
        try
        {
            $page_data = $this->__getVoucher($postdata);

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

    /**
     * @return mixed
     * 赠送卡券
     */
    public function giveVoucher()
    {
        $voucher_id = input::get('voucher_id');
        $filter['voucher_id'] = $voucher_id;
        $filter['user_id'] = userAuth::id();


        try
        {
            $voucherObj = app::get('systrade')->model('voucher');
            $voucher=$voucherObj->getRow('status',$filter);
            if(!$voucher || $voucher['status']!='WAIT_WRITE_OFF')
            {
                throw new Exception('该卡券不能赠送给别人');
            }
            if($voucher_id)
            {
                $res=$voucherObj->update(array('status'=>'GIVING'),$filter); //赠送中
            }
            if(!$res)
            {
                throw new Exception('赠送失败');
            }

            $return_data['err_no'] = 0;
            $return_data['data'] = [];
            $return_data['message'] = '赠送成功';
        }
        catch(Exception $e)
        {
            $return_data['err_no'] = 1001;
            $return_data['data'] = [];
            $return_data['message'] = $e->getMessage();
        }
        return response::json($return_data);
    }

    /**
     * @return mixed
     * 撤销赠送
     */
    public function revokeVoucher()
    {
        $voucher_id = input::get('voucher_id');
        $filter['voucher_id'] = $voucher_id;
        $filter['user_id'] = userAuth::id();
        try
        {
            $voucherObj = app::get('systrade')->model('voucher');
            $voucher=$voucherObj->getRow('status',$filter);
            if(!$voucher || $voucher['status']!='GIVING')
            {
                throw new Exception('该卡券不能被撤销');
            }
            if($voucher_id)
            {
                $res=$voucherObj->update(array('status'=>'WAIT_WRITE_OFF'),$filter); //赠送中
            }
            if(!$res)
            {
                throw new Exception('赠送失败');
            }

            $return_data['err_no'] = 0;
            $return_data['data'] = [];
            $return_data['message'] = '撤销赠送成功';
        }
        catch(Exception $e)
        {
            $return_data['err_no'] = 1001;
            $return_data['data'] = [];
            $return_data['message'] = $e->getMessage();
        }
        return response::json($return_data);
    }

    /**
     * @return array
     * 获取微信分享的参数信息
     */
    public function getWxINfo()
    {
        $appId = app::get('site')->getConf('site.appId');
        $appsecret = app::get('site')->getConf('site.appSecret');
        $timestamp = time();
        $jsapi_ticket = $this->make_ticket($appId,$appsecret);
        $nonceStr = $this->make_nonceStr();
        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $signature = $this->make_signature($nonceStr,$timestamp,$jsapi_ticket,$url);

        $signPackage = array(
            "appId"     => $appId,
            "appsecret" => $appsecret,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "signature" => $signature,
        );

        return $signPackage;

    }

    /**
     * @return string
     * 微信随机字符串
     */
    public function make_nonceStr()
    {
        $codeSet = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($i = 0; $i<16; $i++) {
            $codes[$i] = $codeSet[mt_rand(0, strlen($codeSet)-1)];
        }
        $nonceStr = implode($codes);
        return $nonceStr;
    }

    /**
     * @param $nonceStr
     * @param $timestamp
     * @param $jsapi_ticket
     * @param $url
     * @return string
     */
    public function make_signature($nonceStr,$timestamp,$jsapi_ticket,$url)
    {
        $tmpArr = array(
            'noncestr' => $nonceStr,
            'timestamp' => $timestamp,
            'jsapi_ticket' => $jsapi_ticket,
            'url' => $url
        );
        ksort($tmpArr, SORT_STRING);
        $string1 = http_build_query( $tmpArr );
        $string1 = urldecode( $string1 );
        $signature = sha1( $string1 );
        return $signature;
    }

    /**
     * @param $appId
     * @param $appsecret
     * @return mixed
     */
    public function make_ticket($appId,$appsecret)
    {
        $data = json_decode(file_get_contents(DATA_DIR."/wxshare/access_token.json"));
        if (!is_dir(DATA_DIR.'/wxshare'))
        {
            mkdir(DATA_DIR.'/wxshare', 0755, true);
        }
        if ($data->expire_time < time())
        {
            $TOKEN_URL="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appId."&secret=".$appsecret;
            $json = file_get_contents($TOKEN_URL);
            $result = json_decode($json,true);
            $access_token = $result['access_token'];
            if ($access_token)
            {
                $data->expire_time = time() + 7000;
                $data->access_token = $access_token;
                $fp = fopen(DATA_DIR."/wxshare/access_token.json", "w");
                fwrite($fp, json_encode($data));
                fclose($fp);
            }
        }
        else
        {
            $access_token = $data->access_token;
        }

        // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
        $data = json_decode(file_get_contents(DATA_DIR."/wxshare/jsapi_ticket.json"));
        if ($data->expire_time < time())
        {
            $ticket_URL="https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$access_token."&type=jsapi";
            $json = file_get_contents($ticket_URL);
            $result = json_decode($json,true);
            $ticket = $result['ticket'];
            if ($ticket)
            {
                $data->expire_time = time() + 7000;
                $data->jsapi_ticket = $ticket;
                $fp = fopen(DATA_DIR."/wxshare/jsapi_ticket.json", "w");
                fwrite($fp, json_encode($data));
                fclose($fp);
            }
        }
        else
        {
            $ticket = $data->jsapi_ticket;
        }
        return $ticket;
    }
}