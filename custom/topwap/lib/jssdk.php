<?php
class topwap_jssdk{

    public function __construct(){
        $this->appId = app::get('site')->getConf('site.appId');
        $this->appsecret = app::get('site')->getConf('site.appSecret');
    }

    public function index($url)
    {
        $timestamp = time();
        $appId = $this->appId;
        $appsecret = $this->appsecret;
        $jsapi_ticket = $this->make_ticket($appId,$appsecret);
        $nonceStr = $this->make_nonceStr();
        //if (strstr($_GET['url'],'activity-itemdetail')) {
       //     $url = $_GET['url'].'&g='.$_GET['g'];
        //}else{
            //$url = $_GET['url'];

          //  $params=$_GET;
           //// foreach($params as $k=>$v){
            //    $url.='&'.$k.'='.$v;
           // }
        //    $url=substr($url,5);
       // }
        //$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $signature = $this->make_signature($nonceStr,$timestamp,$jsapi_ticket,$url);

        $signPackage = array(
            "appId"     => $this->appId,
            "appsecret" => $this->appsecret,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "signature" => $signature,
        );
        return $signPackage;

    }

    public function make_nonceStr()
    {
        $codeSet = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($i = 0; $i<16; $i++) {
            $codes[$i] = $codeSet[mt_rand(0, strlen($codeSet)-1)];
        }
        $nonceStr = implode($codes);
        return $nonceStr;
    }

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

    public function make_ticket($appId,$appsecret)
    {
		
		$redis= redis::scene('accessToken');
	

        // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
        //$data = json_decode(file_get_contents(DATA_DIR."/wxshare/access_token.json"));
        //if (!is_dir(DATA_DIR.'/wxshare')) {
        //    mkdir(DATA_DIR.'/wxshare', 0755, true);
        //}
        if (!$redis->get('access_token')) {
            $TOKEN_URL="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appId."&secret=".$appsecret;
            $json = file_get_contents($TOKEN_URL);
            $result = json_decode($json,true);
            $access_token = $result['access_token'];
			//$access_token=123123;
            if ($access_token) {
				$redis->set('access_token',$access_token);
				$redis->expire('access_token',7000);
            }
        }else{
            $access_token = $redis->get('access_token');
        }
        // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
        $data = json_decode(file_get_contents(DATA_DIR."/wxshare/jsapi_ticket.json"));
        if (!$redis->get('jsapi_ticket')) {
            $ticket_URL="https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$access_token."&type=jsapi";
            $json = file_get_contents($ticket_URL);
            $result = json_decode($json,true);
            $ticket = $result['ticket'];
            if ($ticket) {
				$redis->set('jsapi_ticket',$ticket);
				$redis->expire('jsapi_ticket',7000);
            }
        }else{
            $ticket = $redis->get('jsapi_ticket');
        }
		//echo "<pre>";print_r($ticket);die();
        return $ticket;
    }
}


