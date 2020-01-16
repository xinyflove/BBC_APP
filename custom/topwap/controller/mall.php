
<?php
class topwap_ctl_mall extends topwap_controller{

    public function index()
    {
        $filter = input::get();
		/*add_2017/11/10_by_wanghaichao_start*/
		$url= url::action("topwap_ctl_mall@index",$filter);
		/*add_2017/11/10_by_wanghaichao_end*/		
        $this->setLayoutFlag($filter['page_name']);
		/*add_2017/11/10_by_wanghaichao_start*/
		$share_img=app::get('sysconf')->getConf('sysconf_setting.wap_logo');   //商城logo
		$weixin['descContent']= app::get('site')->getConf('site.orther_share_describe');
		$weixin['imgUrl']= base_storager::modifier($share_img);
		$weixin['linelink']= $url;
		$weixin['shareTitle']=app::get('site')->getConf('site.orther_share_title');
		$pagedata['weixin']=$weixin;
		/*add_2017/11/10_by_wanghaichao_end*/
        return $this->page("topwap/mall/index.html");
    }
	
	/**
	* 消息回调
	* author by wanghaichao
	* date 2019/8/1
	*/
	public function notify(){
		$data=input::get();
        $wxAppId = app::get('site')->getConf('site.appId');
        $wxAppsecret = app::get('site')->getConf('site.appSecret');
		$objWechat = new topwap_wechat_jssdk($wxAppId,$wxAppsecret);
       $access_token=$objWechat->getAccessToken();
		$url='https://api.weixin.qq.com/cgi-bin/message/template/subscribe?access_token='.$access_token;
		$postdata['touser']=$data['openid'];
		$postdata['template_id']=$data['template_id'];
		$postdata['url']=url::action('topwap_ctl_coupon_voucher@index');
		$postdata['appid']=$wxAppId;
		$postdata['scene']=$data['scene'];
		$postdata['title']='订单支付成功通知';
		$value=$this->getValue($data);
		$postdata['data']['content']['value']=$value;
		$post=json_encode($postdata);
		$httpclient = kernel::single('base_httpclient');
		$response = $this->http_post_data($url, $post);
		$url = url::action('topwap_ctl_coupon_voucher@index');
		//return redirect::to($url);
		header("Location:".$url);exit;
		//echo "<pre>";print_r($response);die();
	}
	
	/**
	* 组装消息内容的
	* author by wanghaichao
	* date 2019/8/2
	*/
	public function getValue($data){
		$value="订单号：".$data['tid']."\n";
		if(!empty($data['voucher_code'])){
			$voucher_code=explode(',',$data['voucher_code']);
			if(count($voucher_code)==1){
				$value.="卡券号：".$data['voucher_code']."\n";
			}else{
				$value.="卡券号：";
				foreach($voucher_code as $k=>$v){
					$value.=$v."\n";
				}
			}
		}
		$value.='支付金额：'.$data['payment']."\n";
		$value.='支付时间：'.date("Y-m-d H:i:s",$data['pay_time'])."\n";
		$value.='欢迎再次购买';
		return $value;
	}
	
	/**
	* post提交的
	* author by wanghaichao
	* date 2019/8/2
	*/
    public function http_post_data($url, $data_string, $is_json)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        /*if ($is_json)
        {
            $http_head = array(
                'Accept:application/json',
                'Content-Type:application/json;charset=utf-8',
                'Content-Length:'.strlen($data_string),
                'Authorization:'.$this->_Authorization,
            );

            curl_setopt($ch, CURLOPT_HTTPHEADER, $http_head);
        }*/
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();

        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return array($return_code, $return_content);
    }

}
