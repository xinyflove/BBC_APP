<?php
/**
 * Created by PhpStorm.
 * @desc:
 * @author: admin
 * @date: 2017-12-01 11:14
 */

class topshop_extra_kingdeedata{

    private $shopId=24;
    private $supplierId=157;
    private $accessKey='4118fd850e4bfdb77993942c1a670168'; //md5(“GOLDE_BUTTERFLY_KINGDEE_DATA”)
    private $paymentMethod=array(0,1,2,3);
    private $dataTpl=array(
        "shop_id"=>24,
        "supplier_id"=>157,
        "incoming"=>994,
        "service_charge"=>6,
        "shop_fee"=>950,
        "platform_service_charge"=>50,
        "create_time"=>1512230400,//2017-12-03
        "payment_method"=>0,//0,1,2,3

    );

    public function upload(){
        $params=input::get();
        //确认授权
        if(!($this->_checkAuth($params))){
            $this->splash('failed','无访问权限');
        }
        $sourceData=$params;
        $this->_checkData($sourceData);


        $tpldata['payment_method']=$sourceData['payment_method'];
        $tpldata['create_time']=$sourceData['create_time'];

        $chkExist=app::get('sysclearing')->model('kingdee_data_gold_butterfly')->getRow('*',$tpldata);
        if(!empty($chkExist['id'])){
            if($chkExist['deal_status']==2){
                $this->splash('failed','日期为'.date('Y-m-d',$sourceData['create_time']).',收款方式为'.$this->getPaymentMethodSrc($sourceData['payment_method']).'的数据已经存在并已推送凭证，请手动处理');
            }else{
                $tpldata['id']=$chkExist['id'];
            }
        }
        $tpldata["shop_id"]=$this->shopId;
        $tpldata["supplier_id"]=$this->supplierId;
        $tpldata["incoming"]=$sourceData['incoming'];
        $tpldata["service_charge"]=$sourceData['service_charge'];
        $tpldata["shop_fee"]=$sourceData['shop_fee'];
        $tpldata["platform_service_charge"]=$sourceData['platform_service_charge'];
        $tpldata['deal_status']=1;



        $objMdl=app::get('sysclearing')->model('kingdee_data_gold_butterfly');
        try{
            $objMdl->insert($tpldata);
        }catch (Exception $e){
            $errMsg=$e->getMessage();
            $this->splash('failed',$errMsg);
        }

        $this->splash('succ','数据传输成功');

    }

    /**
     * @name _checkAuth
     * @desc 校验accessKey
     * @param $data
     * @author: wudi tvplaza
     * @date: 2017-12-04 14:26
     */
    private function _checkAuth($data){
        if($data['auth']!==$this->accessKey){
            return false;
        }else{
            return true;
        }
    }

    /**
     * @name checkParams
     * @desc 检测数据合法性
     * @param $data
     * @author: wudi tvplaza
     * @date: 2017-12-04 14:45
     */
    public function _checkData($data){
        if($data['create_time'] < 1483200000){
            $this->splash('failed','入账时间戳不合法');
        }

        if($data['payment_method']!=='0' && $data['payment_method']!==0){
            if(empty($data['payment_method'])){
                $this->splash('failed','收款方式payment_method不能为空');
            }
        }

        if(!in_array($data['payment_method'],$this->paymentMethod)){
            $this->splash('failed','收款方式payment_method的值不合法');
        }

        //借贷相等
        $msgCalc='incoming和service_charge的总额不等于shop_fee和platform_service_charge的总额。';
        $chkCalc=$data['incoming']+$data['service_charge']-$data['shop_fee']-$data['platform_service_charge'];
        if($chkCalc!=0 ){
            $this->splash('failed',$msgCalc);
        }

        return true;
    }
    /**
     * @name splash
     * @desc 返回json格式的响应结果
     * @param int $status
     * @param null $msg
     * @param null $data
     * @author: wudi tvplaza
     * @date: 2017-12-04 14:25
     */
    public function splash($status=0,$msg=null,$data=null){
        echo json_encode(array(
            'status'   => $status,
            'message' => $msg,
            'data'=>$data,
        ));
        exit;
    }

    /**
     * @name getPaymentMethodSrc
     * @desc 获取收款方式屏显字符串
     * @param $pmTag
     * @return string
     * @author: wudi tvplaza
     * @date:2017-12-13 9:42
     */
    private function getPaymentMethodSrc($pmTag){
        switch ($pmTag){
            case 0:$src='线上-网银';break;
            case 1:$src='线上-微信支付宝';break;
            case 2:$src='线下-网银';break;
            case 3:$src='线下-微信';break;
            default:$src='缺省';
        }
        return $src;
    }
}