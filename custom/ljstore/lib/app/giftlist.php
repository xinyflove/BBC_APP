<?php
/* 
* 个人中心礼品列表
* author by wanghaichao
* date 2018/1/22
*/

class ljstore_app_giftlist extends ljstore_app_controller {

	/* action_name (par1, par2, par3)
	* 列表
	* author by wanghaichao
	* date 2018/1/22
	*/
	public function index(){
		$params=input::get();
		$mobile=$params['mobile'];
		if($params['status']==2){
			unset($params['status']);
		}
		$params['user_id']=$this->getUserId($mobile);
		if(!$params['user_id']){
            $this->splash('200',array('data'=>array()));
		}

		$objMdlGift=app::get('sysactivityvote')->model('gift_gain');

        $pageNo = $params['page_no'];
        $pageSize = $params['page_size'];
        unset($params['page_no'],$params['page_size'],$params['mobile']);

        $count = $objMdlGift->count($params);
        $page =  $pageNo ? $pageNo : 1;
        $limit = $pageSize ? $pageSize : 40;
        $pageTotal = ceil($count/$limit);
        $currentPage = $pageTotal < $page ? 10000000000 : $page;
        $offset = ($currentPage-1) * $limit;
		$giftObj=app::get('sysactivityvote')->model('gift');
        $giftLists = $objMdlGift->getList('*',$params,$offset,$limit,$orderBy);
		foreach($giftLists as $k=>$v){
			$gift=$giftObj->getRow('gift_name,image_default_id,gift_profile,gift_wap_desc,gift_desc',array('gift_id'=>$v['gift_id']));
			$v['gift_name']=$gift['gift_name'];
			$v['image']=$gift['image_default_id'];
			$v['qr_code']=base_storager::modifier($v['qr_code']);
			$v['image']=base_storager::modifier($v['image']);
			$v['gift_wap_desc']=strip_tags($gift['gift_wap_desc']);
			$giftLists[$k]=$v;
		}
		$this->splash('200',array('data'=>$giftLists));
	}
	
	public function getUserId($mobile){
		
		$user_info=app::get('sysuser')->model('account')->getRow('user_id',array('mobile'=>$mobile));
		if($user_info){
			$userId=$user_info['user_id'];
			return $userId;
		}else{
			return false;
		}
	}
	
	/**
	* 用户核销礼品
	* author by wanghaichao
	* date 2020/1/2
	*/
	public function write(){
		$params['gift_gain_id']=input::get('gift_gain_id');
		$mobile=input::get('mobile');
		if (empty($params['gift_gain_id'])){
			$this->splash('30001','参数错误');
		}
		if(empty($mobile)){
			$this->splash('30002','手机号不能为空');
		}
		$params['user_id']=$this->getUserId($mobile);
		if(!$params['user_id']){
            $this->splash('30003','用户不存在');
		}
		$objMdlGift=app::get('sysactivityvote')->model('gift_gain');
		$row=$objMdlGift->getRow('gift_gain_id,status',$params);
		if (empty($row)){
			$this->splash('30006','没有这条记录');
		}
		if ($row['status']!=0){
            $this->splash('30004','已经使用过');
		}
		$update['status']=1;
		$update['used_time']=time();
		$update['write_type']='SELF';//自己核销
		$res=$objMdlGift->update($update,$params);
		//echo "<pre>";print_R($res);die();
		if ($res){
			$this->splash('200','使用成功');
		}else{
			$this->splash('30005','使用失败,请稍后尝试');
		}
	}



}


?>