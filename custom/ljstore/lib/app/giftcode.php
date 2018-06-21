<?php

/* action_name (par1, par2, par3)
* 蓝睛app生成礼品码的接口
* author by wanghaichao
* date 2018/1/22
*/
	
use Endroid\QrCode\QrCode;
class ljstore_app_giftcode extends ljstore_app_controller {
	public function code(){
		$input=input::get();
		$input['mobile']=trim(input::get('mobile'));
		$input['open_id']=trim(input::get('open_id'));
		$input['gift_id']=trim(input::get('gift_id'));
		$input['active_id']=trim(input::get('active_id'));
		if(empty($input['mobile']) && empty($input['open_id'])){
			$this->splash('10002','手机号或open_id不能为空!');
		}
		if($input['mobile'] && $input['open_id']){
			$this->splash('10012','手机号和open_id只能有一个');
		}
		if(empty($input['gift_id'])){
			$this->splash('10003','礼品id不能为空!');
		}
		if(empty($input['active_id'])){
			$this->splash('10004','活动id不能为空!');
		}
		//取出礼品信息
		$giftinfo=app::get('sysactivityvote')->model('gift')->getRow('gain_total,gift_id,supplier_id,valid_start_time,valid_end_time,shop_id',array('gift_id'=>$input['gift_id']));
		if(empty($giftinfo)){
			$this->splash('10005','没有该礼品信息!');
		}
		//取出供货商的信息
		$supplier=app::get('sysshop')->model('supplier')->getRow('code',array('supplier_id'=>$giftinfo['supplier_id'],'is_audit'=>'PASS'));
		if(empty($supplier)){
			$this->splash('10006','没有该供货商信息!');
		}
		if($input['mobile']){
			$insert['user_id']=$this->getUserId($input['mobile']);
			$insert['open_id']=0;
		}elseif($input['open_id']){
			$gift_gain=app::get('sysactivityvote')->model('gift_gain')->getRow('gift_gain_id',array('open_id'=>$input['open_id'],'active_id'=>$input['active_id']));
			if($gift_gain){
				$this->splash('10016','该open_id已获得该活动奖品');
			}
			$insert['open_id']=$input['open_id'];
		}
		//生成code;
		$code=$this->createCode($supplier['code']);
		$insert['qr_code']=$this->__qrCode($code['gift_code'],$giftinfo['supplier_id']);
		$insert['gift_code']=$code['gift_code'];
		$insert['start_time']=$giftinfo['valid_start_time'];
		$insert['end_time']=$giftinfo['valid_end_time'];
		$insert['supplier_id']=$giftinfo['supplier_id'];
		$insert['status']='0';
		$insert['create_time']=time();
		$insert['active_id']=$input['active_id'];
		$insert['gift_id']=$giftinfo['gift_id'];
		$insert['shop_id']=$giftinfo['shop_id'];
		$res=app::get('sysactivityvote')->model('gift_gain')->insert($insert);
		if($res){
			$gain_total=($giftinfo['gain_total']+1);
			$update=app::get('sysactivityvote')->model('gift')->update(array('gain_total'=>$gain_total),array('gift_id'=>$giftinfo['gift_id']));
			$this->splash('10001','领取成功!');
		}else{
			$this->splash('10007','领取失败!');
		}
		//echo "<pre>";print_r($input);die();
	}
	
	//根据商品id生成卡券码
	public function createCode($code){
		
		$time=time();
		//$str=substr($time,-4);
		$str=rand(1000,9999);
		//随机数
		$rand=rand(10,99);
		$return=array();
		$return['gift_code']="ZP".$code.$str.$rand;
		
		//数据库排重,递归函数处理
		$gift_gain=app::get('sysactivityvote')->model('gift_gain')->getRow('gift_gain_id',array('gift_code'=>$return['gift_code']));
		if($gift_gain){
			$return=$this->createCode($item_id);
		}

		return $return;
	}
	
    private function __qrCode($code,$suppiler_id)
    {
       
	   
       $result= getQrcodeUri($code, 80, 10);

	   $code_url="../public/images/voucher/{$code}_code.png"; 
		copy($result,$code_url);   //上传至服务器
		return "/images/voucher/{$code}_code.png";
    }

	public function getUserId($mobile){
		
		$user_info=app::get('sysuser')->model('account')->getRow('user_id',array('mobile'=>$mobile));
		if($user_info){
			$userId=$user_info['user_id'];
		}else{
			$userInfo['account']=$mobile;
			$userInfo['password']='123456abc';
			$userInfo['pwd_confirm']='123456abc';
			$userId = userAuth::signUp($userInfo['account'], $userInfo['password'], $userInfo['pwd_confirm']);
		}
		return $userId;        
	}



}


?>