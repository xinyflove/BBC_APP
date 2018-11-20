<?php
/**
 * 卡券领取的逻辑
 * wanghaichao
 * 2017/11/24
 */

class topwap_ctl_give extends topwap_controller
{
	/* action_name (par1, par2, par3)
	* 卡券领取的页面
	* author by wanghaichao
	* date 2017/11/24
	*/
	public function index(){
		$params['voucher_id']=input::get('voucher_id');
		$params['user_id']=input::get('user_id');
        $pagedata['userInfo'] = app::get('topwap')->rpcCall('user.get.info', array('user_id'=>$params['user_id']), 'buyer');   //赠送者的信息
		$voucher=app::get('systrade')->model('voucher')->getRow('*',$params);
		$voucher['end_time']=date('Y.m.d',$voucher['end_time']);
		$pagedata['userInfo']['mobile']= substr_replace($pagedata['userInfo']['mobile'], '****', 3, 4);
        $item_where = [
            'shop_id'=>$voucher['shop_id'],
            'item_id'=>$voucher['item_id'],
            'fields'=>[
                'rows'=>'image_default_id,title'
            ]
        ];
		if(userAuth::check()){
			$pagedata['is_login']=true;
			$user_id=userAuth::Id();
			$myInfo=app::get('topwap')->rpcCall('user.get.info', array('user_id'=>$user_id), 'buyer');    //当前登录者的信息
			$pagedata['myMobile']=substr_replace($myInfo['mobile'],'****',3,4);
		}else{
			$pagedata['is_login']=false;
		}
        $item_info = app::get('topwap')->rpccall('item.get',$item_where);
		$voucher['item']=$item_info;	
		$pagedata['shop']= app::get('topwap')->rpcCall('shop.get',array('fields'=>'shop_mold,shop_name','shop_id'=>$voucher['shop_id']));
		$pagedata['voucher']=$voucher;
		$pagedata['params']=$params;
		//echo "<pre>";print_r($voucher);die();
        return $this->page('topwap/give/index.html', $pagedata);
	}

	/* action_name (par1, par2, par3)
	* 领取卡券的逻辑
	* author by wanghaichao
	* date 2017/11/242017/11/24 
	*/
	public function getVoucher(){
		//if(!userAuth::check()){
		//	return $this->splash('error', null, '请输入手机号后再领取', true);
		//}
		if(!userAuth::check()){
			$mobile=input::get('mobile');
			$vcode=input::get('vcode');
			if(empty($mobile)){
				return $this->splash('error', null, '请输入手机号后再领取', true);
			}
			if(empty($vcode)){
				return $this->splash('error', null, '请输入验证码', true);
			}
			$sendType='signup';
			$vcodeData=userVcode::verify($vcode,$mobile,$sendType);
			if(!$vcodeData)
			{
				$msg = app::get('topwap')->_('验证码填写错误') ;
				return $this->splash('error', null, $msg, true);
			}
			$check=$this->checkUser($mobile);
			if($check==false){	
				//注册用户
				$userInfo['account']=$mobile;
				$userInfo['password']='123456abc';
				$userInfo['pwd_confirm']='123456abc';
				$userId = userAuth::signUp($userInfo['account'], $userInfo['password'], $userInfo['pwd_confirm']);
				userAuth::login($userId, $userInfo['account']);
			}
			$trust=app::get('sysuser')->model('trustinfo');
			$user_id=userAuth::Id();
			$trustinfo=$trust->getRow('user_id',array('user_id'=>$user_id));
			if(!$trustinfo){
				$open_id=$this->getOpenID();
				if($open_id=='NO_OPENID'){
					return $this->splash('error', null,'请在微信浏览器中打开', true);
				}
				$trustinsert['user_id']=$user_id;
				$trustinsert['user_flag']=md5($open_id);
				$trustinsert['flag']='wapweixin';
				$trust->insert($trustinsert);
			}
			$myMobile=substr_replace($mobile,'****',3,4);
		}
		$voucherObj=app::get('systrade')->model('voucher');
		$user_id=userAuth::id();
		$filter=input::get();
		$voucher=$voucherObj->getRow('*',$filter);
		if($voucher['status']!='GIVING'){
			return $this->splash('error', null, '该卡券已不能领取', true);
		}
		if($voucher['user_id']==$user_id){    //判断是不是自己领自己的代金券
			return $this->splash('error', null, '自己不能领取自己的卡券', true);
		}
		$insert['share_user_id']=$voucher['user_id'];   //分享人
		$insert['user_id']=$user_id;    //领取人
		$insert['give_time']=time();      //领取时间
		$insert['oid']=$voucher['oid'];   //分享人
		$insert['tid']=$voucher['tid'];   //分享人
		$insert['voucher_code']=$voucher['voucher_code'];   //分享人
		$insert['shop_id']=$voucher['shop_id'];   //分享人
		$insert['item_id']=$voucher['item_id'];   //分享人
		$insert['start_time']=$voucher['start_time'];   //分享人
		$insert['end_time']=$voucher['end_time'];   //分享人
		$insert['supplier_id']=$voucher['supplier_id'];   //分享人
		$insert['careated_time']=$voucher['careated_time'];   //分享人
		$insert['code']=$voucher['code'];   //分享人
		$insert['status']='WAIT_WRITE_OFF';   //分享人
		$insert['share_user_id']=$voucher['user_id'];   //分享人
		/*add_2018/10/12_by_wanghaichao_start*/
		//加入套票id
		$insert['ticket_id']=$voucher['ticket_id'];
		/*add_2018/10/12_by_wanghaichao_end*/
		
        $db = app::get('topwap')->database();
        $db->beginTransaction();
        try
        {
			$in_res=$voucherObj->insert($insert);
			//echo "<pre>";print_r($insert);die();
			$up_res=$voucherObj->update(array('status'=>'GIVEN'),$filter);
			if($in_res && $up_res){
				$db->commit();
				if($myMobile){
					return $this->splash('success', null, '恭喜 '.$myMobile, true);
				}else{
					return $this->splash('success', null, '领取成功', true);
				}
			}else{
				$db->rollback();
				return $this->splash('error', null, '领取失败', true);
			}
		}catch(\Exception $e)
       {
			$db->rollback();
            throw $e;
        }
	}

}