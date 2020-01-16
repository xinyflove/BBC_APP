<?php
/**
 * [index description]
 * @Author   房隆基
 * @DateTime 2017-09-14
 */
class topshop_ctl_shop_payment extends topshop_controller{
    public $limit = 10;

    public function index()
    {

        $this->contentHeaderTitle = app::get('topshop')->_('支付方式列表');

        $pagedata['payment'] = unserialize($this->shopInfo['payment']);
        /*add_20170924_by_fanglongji_start*/
        $pagedata['mer_collection'] = $this->shopInfo['mer_collection'];
        /*add_20170924_by_fanglongji_end*/
        return $this->page('topshop/shop/payment/index2.html', $pagedata);
    }

    public function savePayment()
    {
        $postData = input::get();
        $payment = $postData['payment'];
        $pay_name = [
                     'wxsjpayjsapi' => ['id' => 'wxsjpayjsapi', 'name' => '微信支付'],
                     'umspay' => ['id' => 'umspay', 'name' => '银联商务扫码支付(wachat)'],
                     'umsalipay' => ['id' => 'umsalipay', 'name' => '银联商务扫码支付(alipay)'],
                     'umspaypub'  => ['id' => 'umspaypub', 'name' => '银联商务公众号支付'],
                     'upacp' => ['id' => 'upacp', 'name' => '银联商务银行卡支付'],
                     'wapupacp' => ['id' => 'wapupacp', 'name' => '银联商务银行卡WAP'],
                     'miniservicepayapi' => ['id' => 'miniservicepayapi', 'name' => '小程序支付'],
                     'wxEnterprisePayment' => ['id' => 'wxEnterprisePayment', 'name' => '微信企业付款到个人钱包'],
                    ];
        foreach ($payment as $key => $pay)
        {
            $sec[$key]['app_id'] = $pay_name[$key]['id'];
            $sec[$key]['app_display_name'] = $pay_name[$key]['name'];
            $sec[$key]['open'] = isset($payment[$key]['open']) ? $payment[$key]['open'] : 0;
            // 公众号AppId
            $sec[$key]['sub_appid'] = isset($payment[$key]['sub_appid']) ? trim($payment[$key]['sub_appid']) : '';
            // 公众号应用密钥
            $sec[$key]['sub_appsecret'] = isset($payment[$key]['sub_appsecret']) ? trim($payment[$key]['sub_appsecret']): '';
            // 商户号(Mch_Id)
            $sec[$key]['sub_mchid'] = isset($payment[$key]['sub_mchid']) ? trim($payment[$key]['sub_mchid']) : '';
            // 终端号（TId）
            $sec[$key]['sub_tid'] = isset($payment[$key]['sub_tid']) ? trim($payment[$key]['sub_tid']) : '';
            //pfx证书密码(PASSWORD)
            $sec[$key]['pfx_pass'] = isset($payment[$key]['pfx_pass']) ? trim($payment[$key]['pfx_pass']) : '';
            // 微信支付密钥(APIKEY)
            $sec[$key]['pfx'] = isset($payment[$key]['pfx']) ? trim($payment[$key]['pfx']) : '';
            $sec[$key]['key'] = isset($payment[$key]['key']) ? trim($payment[$key]['key']) : '';
            $sec[$key]['root'] = isset($payment[$key]['key']) ? trim($payment[$key]['root']) : '';

            if($key == 'wxEnterprisePayment'){
                $sec[$key]['mchid'] = isset($payment[$key]['mchid']) ? trim($payment[$key]['mchid']) : '';

                $sec[$key]['key'] = isset($payment[$key]['key']) ? trim($payment[$key]['key']) : '';

                $sec[$key]['apiclient_cert_file'] = isset($payment[$key]['apiclient_cert_file']) ? trim($payment[$key]['apiclient_cert_file']) : '';

                $sec[$key]['apiclient_key_file'] = isset($payment[$key]['apiclient_key_file']) ? trim($payment[$key]['apiclient_key_file']) : '';
            }
            // if ($_FILES[$key.'_sub_pfx_file']['name'])
            // {
            //     $sec[$key]['pfx'] = $this->upload_cert($key.'_sub_pfx_file',$message);
            // }
            // if ($_FILES[$key.'_sub_key_file']['name'])
            // {
            //     $sec[$key]['key'] = $this->upload_cert($key.'_sub_key_file',$message);
            // }
            // if ($_FILES[$key.'_sub_root_file']['name'])
            // {
            //     $sec[$key]['root'] = $this->upload_cert($key.'_sub_root_file',$message);
            // }
        }

        try
        {
            $shop_info = app::get('topshop')->rpcCall('shop.get', ['shop_id' => $this->shopId]);
            $shop_payment = unserialize($shop_info['payment']);

            $saveData['shop_id'] = $this->shopId;
            $saveData['payment'] = serialize(array_merge((array)$shop_payment,(array)$sec));

            $result = app::get('topshop')->rpcCall('shop.update',$saveData);
            if(!$result)
            {
                $msg = app::get('topshop')->_('设置失败!');
                throw new Exception($msg);
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            $url = url::action('topshop_ctl_shop_payment@index');
            return $this->splash('error', $url, $msg);
        }
        $this->sellerlog('编辑支付配置信息');
        $url = url::action('topshop_ctl_shop_payment@index');
        $msg = '配置成功';
        return $this->splash('success', $url, $msg, true);
        // header("Location:".$url);
    }

    public function upload_cert()
    {
        // $input_name = input::get('name', '');

        // $filename = $_FILES['file']['name'];
        // $tmp_name = $_FILES['file']['tmp_name'];
        // if (!(empty($filename)) && !(empty($tmp_name)))
        // {
        //     $ext = strtolower(substr($filename, strrpos($filename, '.')));
        //     if (!in_array($ext,['.pem', '.p12', '.pfx'])){
        //         echo json_encode([
        //             'error' => true,
        //             'message' => '文件类型不合法！',
        //         ]);
        //         return;
        //     }
        //     $end = strpos($input_name, '_');
        //     $save_file = substr($input_name, 0, $end);
        //     $bankName = "payment_plugin_".$save_file;
        //     $destination = DATA_DIR . '/cert/' . $bankName;
        //     if ( !file_exists( $destination ) ) {
        //         utils::mkdir_p( $destination, 0755 );
        //     }
        //     $destination = DATA_DIR . '/cert/' . $bankName . '/' . $filename;
        //     if(move_uploaded_file( $tmp_name, $destination ))
        //     {
        //         echo json_encode([
        //             'error' => false,
        //             'message' => '上传成功！',
        //             'filename' => $filename,
        //         ]);
        //         return;
        //     }
        // }
        try
        {
            //获取文件名
            $filename = $_FILES['file']['name'];
            //获取文件临时路径
            $temp_name = $_FILES['file']['tmp_name'];
            //获取大小
            $size = $_FILES['file']['size'];
            //获取文件上传码，0代表文件上传成功
            $error = $_FILES['file']['error'];
            //判断文件大小是否超过设置的最大上传限制
            if ($size > 2*1024*1024){
                throw new Exception('文件大小超过2M大小');
            }
            //phpinfo函数会以数组的形式返回关于文件路径的信息
            //[dirname]:目录路径[basename]:文件名[extension]:文件后缀名[filename]:不包含后缀的文件名
            $arr = pathinfo($filename);
            //获取文件的后缀名
            $ext_suffix = $arr['extension'];
            //设置允许上传文件的后缀
            $allow_suffix = ['pem', 'p12', 'pfx'];
            //判断上传的文件是否在允许的范围内（后缀）==>白名单判断
            if(!in_array($ext_suffix, $allow_suffix)){
                throw new Exception('上传的文件类型只能是pem,p12,pfx');
            }
            //检测存放上传文件的路径是否存在，如果不存在则新建目录
            $destination = DATA_DIR . '/cert/';
            if (!file_exists($destination)){
                utils::mkdir_p( $destination, 0755 );
            }
            //为上传的文件新起一个名字，保证更加安全
            $new_filename =  $this->shopId . date('YmdHis',time()) . rand(1000,9999). '.' . $ext_suffix;
            //将文件从临时路径移动到磁盘
            if (move_uploaded_file($temp_name, $destination . $new_filename)){
                return $this->splash('success', '', ['fileName' => $new_filename]);
            }else{
                echo "<script>alert('文件上传失败,错误码：$error');</script>";
                return $this->splash('error', '', '文件上传失败,错误码：' . $error);

            }
        }catch(Exception $e){
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg);
        }
    }
}