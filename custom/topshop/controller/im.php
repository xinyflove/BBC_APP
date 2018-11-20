<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 7/14/2017
 * Time: 2:29 PM
 */

class topshop_ctl_im extends topshop_controller{

    //智齿开放平台信息

    private $_appId;
    private $_appKey;
    private $_tokenUrl;
    private $_ssoLink;
    private $_tokenExpire;
    private $_msg;

    //TODO::后台配置智齿客服公众平台信息

    public function __construct($app)
    {
        parent::__construct($app);
        $imCfg=config::get('sobot');
        $this->_appId=$imCfg['appId'];
        $this->_appKey=$imCfg['appKey'];
        $this->_tokenUrl=$imCfg['getTokenUrl'];
        $this->_ssoLink=$imCfg['getSsoLink'];
        $this->_tokenExpire=$imCfg['expire'];
        $this->_msg=array(
            '1000'=>'连接成功',
            '1001'=>'无法获取客服平台地址，请刷新后再试，如仍无法连接，请联系技术人员。',
            '1002'=>'无法获取客服平台许可,请刷新后再试，如仍无法连接，请联系技术人员。',
            '1003'=>'您的账号未绑定客服邮箱，请联系店铺管理员为您绑定',
            '2000'=>'您是店铺管理员账号，请使用客服账号登录',
        );
    }

    /**
     * 客服工作界面
     * 返回值代码 1000-获取地址成功，10001-获取地址失败，1002-获取token失败 1003-未绑定邮箱 2000-管理员账号无客服权限
     * @return html
     */
    public function index(){

        $params['shop_id']=$this->shopId;
        $params['seller_id']=$this->sellerId;
        $data = app::get('topshop')->rpcCall('account.shop.user.get',$params);

        //if($data['seller_type']===0){
        if(false){
            $pageData['status']=2000;
        }else{
            $serviceEmail=$this->_getServiceEmail();
            if(empty($serviceEmail)){
                $pageData['status']=1003;
            }else{
                $pageData=$this->_getServiceLink();
            }
        }
        $pageData['msg']=$this->_msg[$pageData['status']];

        return $this->page('topshop/im/index.html',$pageData);
    }

    /**
     * curl请求
     * @param $url
     * @param $param
     * @param string $data
     * @param string $method
     * @return bool|mixed|string
     */
    private function _http($url, $param, $data = '', $method = 'GET'){
        $opts = array(
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        );

        //根据请求类型设置特定参数
        if(empty($param)){
            $opts[CURLOPT_URL] = $url;
        }else{
            $opts[CURLOPT_URL] = $url . '?' . http_build_query($param);
        }

        if(strtoupper($method) == 'POST'){
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $data;

            if(is_string($data)){ //发送JSON数据
                $opts[CURLOPT_HTTPHEADER] = array(
                    'Content-Type: application/json; charset=utf-8',
                    'Content-Length: ' . strlen($data),
                );
            }
        }
        //初始化并执行curl请求
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        //发生错误，抛出异常
        if($error) {
            return false;
        }
        return  $data;
    }

    /**
     * 获取access token
     * @return mixed
     */
    private function _getAccessToken(){
        //base_kvstore::instance('im')->store('accesstoken','', $this->_tokenExpire*60);
        if(($accessToken==cache::store('misc')->get('accesstoken')) && $accessToken){

            $token['status']=1000;
            $token['token']=$accessToken;
        }else{
            $createTime=time()*1000;
            $sign=md5($this->_appId.$this->_appKey.$createTime);
            $param=array('appId'=>$this->_appId,'appKey'=>$this->_appKey,'createTime'=>$createTime,'sign'=>$sign,'expire'=>$this->_tokenExpire);
            $response=$this->_http($this->_tokenUrl,$param);
            $response=json_decode($response);
            $token['status']=$response->code;
            if($response->code=='1000'){
                $token['token']=$response->data->access_token;
                cache::store('misc')->add('accesstoken',$token['token'], $this->_tokenExpire*60);
            }
        }
        return $token;
    }

    /**
     * 获取客服中心登录链接
     * @return array(status,url=>'success return url);
     */
    private function _getServiceLink(){
        $tokenInfo=$this->_getAccessToken();
        $accessToken=($tokenInfo['status']==1000)?$tokenInfo['token']:'';
        if(empty($accessToken)){
            return array('status'=>1002);
        }
        $data['action']='console_direct_url';
        $data['access_token']=$accessToken;
        $data['data']['email']=$this->_getServiceEmail();

        $response=$this->_http($this->_ssoLink,'',json_encode($data),'POST');
        $response=json_decode($response);
        if($response->code==1000){
            return array('status'=>1000,'url'=>$response->data->url);

        }else{
            return array('status'=>1001);
        }
    }

    /**
     * 获取客服人员邮箱地址
     * TODO::确定邮箱地址的来源，且保证tvplaza和客服邮箱记录和智齿账号一致
     * @return string
     */
    private function _getServiceEmail(){
        $params['shop_id']=$this->shopId;
        $params['seller_id']=$this->sellerId;
        $serviceEmail=app::get('sysshop')->model('seller')->getRow('service_email',$params);
        if(!empty($serviceEmail)){
            return $serviceEmail['service_email'];
        }else{
            return false;
        }
    }
}