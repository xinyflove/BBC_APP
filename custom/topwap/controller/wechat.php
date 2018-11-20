<?php
class topwap_ctl_wechat extends topwap_controller{

    public function wxpayjsapi()
    {

        // 新微信支付回调地址
        $postData = array();
        $httpclient = kernel::single('base_httpclient');
        $callback_url = kernel::openapi_url('openapi.ectools_payment/parse/ectools_payment_plugin_wxpayjsapi', 'callback');

        $postStr = file_get_contents("php://input");//$GLOBALS["HTTP_RAW_POST_DATA"];
        logger::info('wxpayjsapi data, xml to array :'.var_export($postStr, 1));
        $postArray = kernel::single('site_utility_xml')->xml2array($postStr);
        $postData['weixin_postdata']  = $postArray['xml'];
        $nodify_data = array_merge(input::get(), $postData);
        $response = $httpclient->post($callback_url, $nodify_data);
        echo $response;exit;
        // if($notify->checkSign() == FALSE){
        //     $arr = array('return_code'=>'FAIL','return_msg'=>'签名失败')；
        // }else{
        //     $arr = array('return_code'=>'SUCCESS');
        // }
        // $returnXml = $notify->returnXml();
        // echo $returnXml;exit;

    }

    public function wxofflinepayjsapi()
    {
        // 新微信支付回调地址
        $postData = array();
        $httpclient = kernel::single('base_httpclient');
        $callback_url = kernel::openapi_url('openapi.ectools_payment_offline/parse/ectools_payment_plugin_wxpayjsapi', 'callback');

        $postStr = file_get_contents("php://input");//$GLOBALS["HTTP_RAW_POST_DATA"];
        logger::info('wxpayjsapi data, xml to array :'.var_export($postStr, 1));
        $postArray = kernel::single('site_utility_xml')->xml2array($postStr);
        $postData['weixin_postdata']  = $postArray['xml'];
        $nodify_data = array_merge(input::get(), $postData);
        $response = $httpclient->post($callback_url, $nodify_data);
        echo $response;exit;
    }
	/* action_name (par1, par2, par3)
	* 
	* author by wanghaichao
	* date 2018/1/23
	*/


    public function lease_wxpayjsapi()
    {

        // 新微信支付回调地址
        $postData = array();
        $httpclient = kernel::single('base_httpclient');
        $callback_url = kernel::openapi_url('openapi.ectools_payment/parse/ectools_payment_plugin_wxpayjsapi', 'lease_callback');

        $postStr = file_get_contents("php://input");//$GLOBALS["HTTP_RAW_POST_DATA"];
        logger::info('wxpayjsapi data, xml to array :'.var_export($postStr, 1));
        $postArray = kernel::single('site_utility_xml')->xml2array($postStr);
        $postData['weixin_postdata']  = $postArray['xml'];
        $nodify_data = array_merge(input::get(), $postData);
        $response = $httpclient->post($callback_url, $nodify_data);
        echo $response;exit;
        // if($notify->checkSign() == FALSE){
        //     $arr = array('return_code'=>'FAIL','return_msg'=>'签名失败')；
        // }else{
        //     $arr = array('return_code'=>'SUCCESS');
        // }
        // $returnXml = $notify->returnXml();
        // echo $returnXml;exit;

    }

    public function wxpayApp()
    {

        // 新微信支付回调地址
        $postData = array();
        $httpclient = kernel::single('base_httpclient');
        $callback_url = kernel::openapi_url('openapi.ectools_payment/parse/ectools_payment_plugin_wxpayApp', 'callback');

        $postStr = file_get_contents("php://input");//$GLOBALS["HTTP_RAW_POST_DATA"];
        logger::info('wxpayjsapi data, xml to array :'.var_export($postStr, 1));
        $postArray = kernel::single('site_utility_xml')->xml2array($postStr);
        $postData['weixin_postdata']  = $postArray['xml'];
        $nodify_data = array_merge(input::get(), $postData);
        $response = $httpclient->post($callback_url, $nodify_data);
        // if($notify->checkSign() == FALSE){
        //     $arr = array('return_code'=>'FAIL','return_msg'=>'签名失败')；
        // }else{
        //     $arr = array('return_code'=>'SUCCESS');
        // }
        // $returnXml = $notify->returnXml();
        // echo $returnXml;exit;

    }

        /*action_name()
     * 函数说明：微信服务商H5支付异部通知
     * 参数说明：
     * authorbyfanglongji
     * 2017-12-15
     */
    public function wxserviceh5pay()
    {
        // 新微信支付回调地址
        $postData = array();
        $httpclient = kernel::single('base_httpclient');
        $callback_url = kernel::openapi_url('openapi.ectools_payment/parse/ectools_payment_plugin_wxserviceh5pay', 'callback');

        $postStr = file_get_contents("php://input");
        logger::info('原始微信服务商H5支付回调的信息(topapi_ctl_wechat)'.var_export($postStr, 1));
        $postArray = kernel::single('site_utility_xml')->xml2array($postStr);
        $postData['weixin_postdata']  = $postArray['xml'];
        $nodify_data = array_merge(input::get(), $postData);
        $response = $httpclient->post($callback_url, $nodify_data);
        echo $response;
        exit;
    }
        /*action_name()
     * 函数说明：将原始微信支付的xml参数转化为url参数
     * 参数说明：
     * authorbyfanglongji
     * 2017-12-15
     */
    public function wxh5pay()
    {
        // 新微信支付回调地址
        $postData = array();
        $httpclient = kernel::single('base_httpclient');
        $callback_url = kernel::openapi_url('openapi.ectools_payment/parse/ectools_payment_plugin_wxh5pay', 'callback');

        $postStr = file_get_contents("php://input");
        logger::info('原始微信H5支付回调的信息(topapi_ctl_wechat)'.var_export($postStr, 1));
        $postArray = kernel::single('site_utility_xml')->xml2array($postStr);
        $postData['weixin_postdata']  = $postArray['xml'];
        $nodify_data = array_merge(input::get(), $postData);
        $response = $httpclient->post($callback_url, $nodify_data);
        echo $response;
        exit;
    }
}
