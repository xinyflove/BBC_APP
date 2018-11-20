<?php
/**
  *  租车前端控制器
  *   用于手机号登录
  *
 **/

class topwap_ctl_lease_login extends topwap_controller{

	
	
	public function __construct(&$app)
    {
		kernel::single('base_session')->start();
        kernel::single("base_session")->set_sess_expires(10080);
	}

	public function login(){
		if(input::get('mobile')){
			$mobile=input::get('mobile');
		}
	
		$pagedata['test']=1;
        return view::make('topwap/lease/login.html', $pagedata);	
	}
	
	//创建用户
	//
	public function createUser(){
		$params['number']=input::get('number');
		$params['name']=input::get('name');
		$ownerInfo=app::get('syscart')->model('owner')->getRow('*',$params);
		if(empty($ownerInfo)){
			$status = 'error';
			$msg = app::get('topshop')->_('您还没有在本系统租车');
            return $this->splash($status,'',$msg,true);
		}
		$mobile=$ownerInfo['mobile'];
		//$check=$this->checkUser($mobile);
		//if($check==false){	
			//注册用户
		//	$userInfo['account']=$mobile;
		//	$userInfo['password']='123456abc';
		//	$userInfo['pwd_confirm']='123456abc';
		//	$userId = userAuth::signUp($userInfo['account'], $userInfo['password'], $userInfo['pwd_confirm']);
        //    userAuth::login($userId, $userInfo['account']);
		//}
		$checkLease=$this->checkLease($ownerInfo['owner_id']);
		if($checkLease==true){
			$_SESSION['owner_id']=$ownerInfo['owner_id'];
			$status = 'success';
			$msg = app::get('topshop')->_('查询成功!');
			$url = url::action('topwap_ctl_lease_default@index');
            return $this->splash($status,$url,$msg,true);
		}else{
			$status = 'error';
			$msg = app::get('topshop')->_('您还没有在本系统租车');
            return $this->splash($status,'',$msg,true);
		}
	}
	//验证手机号是否注册的
	public function checkUser($mobile){
		$user_info=app::get('sysuser')->model('account')->getRow('user_id',array('mobile'=>$mobile));

		if($user_info){	
            userAuth::login($user_info['user_id'], $mobile);
			return true;
		}else{
			return false;
		}
	}
	
	//验证手机号是否有租车
	public function checkLease($owner_id){
		$filter['cart_number|noequal']='';
		$filter['owner_id']=$owner_id;
		$lease_info=app::get('syscart')->model('lease_cart')->getRow('lease_id',$filter);
		if($lease_info){
			return true;
		}else{
			return false;
		}
	}

	

}

?>