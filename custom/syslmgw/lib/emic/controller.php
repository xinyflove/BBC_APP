<?php
/**
 * User: xinyufeng
 * Date: 2018/10/12
 * Time: 18:05
 * Desc: 调用易米接口公共类
 */

class syslmgw_emic_controller
{
    protected $_base_url = 'https://api.emic.com.cn/20171106/';     //基础url
    protected $_account_id = '7d8301679ad9dcaf35c2f38e3f304c54';    //帐号id(主账户)
    protected $_account_token = '0fe95acc557d166560eb7f422b9f98c0'; //帐号token
    protected $_sub_account_id = 'a0b511c7486b7edab9e2a04a0c97ff56';    //子帐号id
    protected $_sub_account_token = '1b7afc4ad19a9dc3c3a707f9d476558a'; //子帐号token
    protected $_app_id = 'b8e67c670f20471a8a255e2bc5c6c10b';        //应用id
    protected $_time;                                               //当前时间
    protected $_Authorization;                                      //报头验证信息
    protected $_SigParameter;                                       //REST API 验证参数

    public function __construct()
    {
        $this->_time = date('YmdHis', strtotime('now'));
		header('Access-Control-Allow-Origin:*');
    }

    /**
     * 获取话单列表
     * @param array $params
     * @return mixed
     *  number 数量
     *  records 记录
     *      type 通话类型：0-双向回拨；1-电话直拨；2-语音 验证码；3-语音通知；4-多方通话；5-电话呼入；
     *      subAccountSid 子账号 ID。本次通话企业属于子账号专用企业时才标识
     *      switchNumber 企业总机号码
     *      callId 呼叫 Id
     *      caller 主叫
     *      called 被叫
     *      userData 用户自定义数据（子账户绑定企业或调用呼叫接口时携带）
     *      billId 话单 Id
     *      createTime 话单创建时间
     *      establishTime 通话建立时间，格式：yyyymmddHHMMSS， 通话失败时，该时间为话单创立时间。
     *      hangupTime 通话挂断时间，格式：yyyymmddHHMMSS，通话失败或者尚未完成时，返回字符串“null”
     *      status 通话状态
     *      duration 通话持续时长（秒）
     *      subDetails 子话单详情，请参考以下说明
     *      subNumber 用户呼入时使用的分机号
     *      useNumber 用户呼入时使用的固话号码
     *      adtel 用户呼入中转虚拟号码
     */
    public function Applications_billList($params=array())
    {
        $ope = 'billList';
        $url = $this->_make_url('Applications', $ope, 'Accounts');
        $base_params = array('appId'=>$this->_app_id);
        $params = array_merge($base_params, $params);
        $params = array($ope => $params);
        $params_json = json_encode($params);

        list($return_code, $return_content) = $this->http_post_data($url, $params_json, true);

        if($return_code != 200)
        {
            throw new \LogicException("调用易米接口失败，错误代码：{$return_code}");
        }

        $return_content = json_decode($return_content, true);
        $resp = $return_content['resp'];
        if($resp['respCode'])
        {
            throw new \LogicException("请求易米接口错误，错误代码：{$resp['respCode']}");
        }
        else
        {
            return $resp[$ope];
        }
    }


    /**
     * 通过curl发送POST方式请求
     * @param $url          //请求链接
     * @param $data_string  //请求数据
     * @param $is_json      //是否json格式
     * @return array
     */
    public function http_post_data($url, $data_string, $is_json)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        if ($is_json)
        {
            $http_head = array(
                'Accept:application/json',
                'Content-Type:application/json;charset=utf-8',
                'Content-Length:'.strlen($data_string),
                'Authorization:'.$this->_Authorization,
            );

            curl_setopt($ch, CURLOPT_HTTPHEADER, $http_head);
        }
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();

        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return array($return_code, $return_content);
    }

    /**
     * 生成url
     * @param $fun
     * @param string $ope
     * @param string $type
     * @return string
     */
    protected function _make_url($fun, $ope='', $type='Accounts')
    {
        if(empty($fun))
        {
            throw new \LogicException(app::get('syslmgw')->_('业务功能参数$fun必填'));
        }

        if(!in_array($type, array('Accounts', 'SubAccounts')))
        {
            throw new \LogicException(app::get('syslmgw')->_('参数$type错误'));
        }

        $_arr = array($fun);
        if(!empty($ope)) $_arr[] = $ope;
        $_str = implode('/', $_arr);

        if($type == 'Accounts')
        {
            $this->_Authorization = base64_encode("{$this->_account_id}:{$this->_time}");
            $this->_SigParameter = strtoupper(md5($this->_account_id.$this->_account_token.$this->_time));
            $url = $this->_base_url."{$type}/{$this->_account_id}/{$_str}?sig={$this->_SigParameter}";
        }
        else
        {
            $this->_Authorization = base64_encode("{$this->_sub_account_id}:{$this->_time}");
            $this->_SigParameter = strtoupper(md5($this->_sub_account_id.$this->_sub_account_token.$this->_time));
            $url = $this->_base_url."{$type}/{$this->_sub_account_id}/{$_str}?sig={$this->_SigParameter}";
        }

        return $url;
    }

    protected function _write_log($params)
    {
        app::get('syslmgw')->model('emiclog')->save($params);
    }

    public function splash($code=200,$data=null,$msg=null){
        echo json_encode(array(
            'code'   => $code,
            'data' => $data,
			'msg'=>$msg
        ));
        exit;

    }

	public function rsplash($code=200,$data=null,$msg=null){
        return json_encode(array(
            'code'   => $code,
            'data' => $data,
			'msg'=>$msg
        ));

    }

    // 请求方法
    public function request_data()
    {
        $method = input::get('method');
        if(!$method){
            $this->splash('400', '', '请求方法不能为空！');
        }
        if(method_exists($this, $method)){
            $this->$method();
        }else{
            $this->splash('400', '', "请求方法'{$method}'未定义！");
        }
    }
}