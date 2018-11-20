<?php

/**
 * @绑定银行卡
 * 2017/9/1
 * 王海超
 * wanghaichao@tvplaza.cn
 */
class topwap_ctl_member_bindbank extends topwap_controller {
	
	//绑定银行卡页面
    public function index(){	
		$next_page=input::get('next_page');
		$bank=app::get('sysbankmember')->model('bank');
		$bank_info=$bank->getList('bank_id,bank_name,bank_code');
		$pagedata['bank_info']=$bank_info;
		$pagedata['next_page']=$next_page;
		return $this->viewPage('topwap/member/bank/index.html', $pagedata);
	}
	
	//绑定银行卡逻辑
	public function bind(){
		$post=input::get();
		$filter=$this->check($post);
		if(empty($filter['card_number'])){
			return $this->splash('error','','卡号不能为空!',true);
		}
		if(!is_numeric($filter['card_number'])){
			return $this->splash('error','','卡号必须为数字',true);
		}
		if(empty($filter['bank_id'])){
			return $this->splash('error','','请选择银行卡类型!',true);
		}
		$num_len=$filter['rel_card_number'];
		//if(strlen($num_len)!=16){
		//	return $this->splash('error','','银行卡号必须为16位!',false);
		//}
		$bankMember=app::get('sysbankmember')->model('member');
		$bankBindObj=app::get('sysbankmember')->model('account');
		$memberInfo=$bankMember->getRow('member_id',array('card_number'=>$filter['card_number'],'bank_id'=>$filter['bank_id']));

		if(empty($memberInfo)){
			return $this->splash('error','','对不起,您还没有权限绑定银行卡!',true);
		}
		$bind_filter=array(
			'card_number'=>$filter['rel_card_number'],
			);
		$bind_user=$bankBindObj->getRow('account_id,user_id,member_id,deleted,create_time',$bind_filter);
		if($bind_user){
			if($bind_user['deleted']==1){
				$bind_data['bind_time']=time();
				$bind_data['user_id']=userAuth::id();
				$bind_data['modified_time']=time();	
				$bind_data['rel_name']=$filter['name'];
				$bind_data['deleted']=0;
				$bind_data['member_id']=$bind_user['member_id'];
				$bind_data['create_time']=$bind_user['create_time'];
				$bind_data['account_id']=$bind_user['account_id'];
				$res=$bankBindObj->save($bind_data);
				return $this->splash('success','','恭喜,绑定成功!',true);
			}elseif($bind_user['deleted']==0){
				return $this->splash('error','','对不起,该卡号已经被绑定!',true);
			}
		}
		unset($filter['card_number']);
		//$res=$bankMember->update($filter,array('member_id'=>$memberInfo['member_id']));
		$bind_data['user_id']=userAuth::id();
		$bind_data['bind_time']=time();
		$bind_data['create_time']=time();
		$bind_data['card_number']=$filter['rel_card_number'];
		$bind_data['member_id']=$memberInfo['member_id'];
		$bind_data['rel_name']=$filter['name'];
		$res=$bankBindObj->insert($bind_data);
		if($res){
			return $this->splash('success','','恭喜,绑定成功!',true);
		}else{
			return $this->splash('error','','对不起,绑定失败!',true);
		}
	}
	
	//检测数据
	public function check($post){
		$filter=array();
		if($post['card_number']){
			$post['rel_card_number']=$post['card_number'];
			$font=substr($post['card_number'],0,6);
			$last=substr($post['card_number'],-4);
			$post['card_number']=$font.$last;
		}
		unset($post['next_page']);
		return $post;
	}

	//获取这个人的银行卡列表
	public function bankList(){
		$user_id=userAuth::id();
		$bankMember=app::get('sysbankmember')->model('member');
		$bankAccount=app::get('sysbankmember')->model('account');
		$params['user_id']=$user_id;
		$params['deleted|noequal']='1';
		$bankList=$bankAccount->getList('account_id,member_id,card_number',$params);
		
		if($bankList){
			foreach($bankList as $k=>$v){
				$data=$this->getBankName($v['member_id']);
				$v['bank_name']=$data['bank_name'];
				$v['card_grade']=$data['card_grade'];
				$font=substr($v['card_number'],0,6);
				$last=substr($v['card_number'],-4);
				$v['card_number']=$font.'******'.$last;
				$v['bank_color']=$data['bank_color'];
				$v['bank_logo']=$data['bank_logo'];
				$bankList[$k]=$v;
			}
		}
		$pagedata['next_page'] = url::action("topwap_ctl_member_bindbank@bankList");
		$pagedata['banklist']=$bankList;
		return $this->viewPage('topwap/member/bank/banklist.html', $pagedata);		
	}

	public function getBankName($member_id){
		if(empty($member_id)){
			return;
		}
		$params['member_id']=$member_id;
		$params['deleted|noequal']='1';
		$member=app::get('sysbankmember')->model('member')->getRow('bank_id,card_grade',$params);
		if($member){
			$bank_filter['bank_id']=$member['bank_id'];
			$bank_filter['deleted|noequal']='1';
			$bank=app::get('sysbankmember')->model('bank')->getRow('bank_name,bank_color,bank_logo',$bank_filter);
		}
		$data['bank_name']=$bank['bank_name'];
		$data['card_grade']=$member['card_grade'];
		$data['bank_color']=$bank['bank_color'];
		$data['bank_logo']=$bank['bank_logo'];
		return $data;
	}

	//解除绑定逻辑
	public function unlasing(){
		$account_id=input::get('account_id');
		$account=app::get('sysbankmember')->model('account');
		if(empty($account_id)){
			return $this->splash('error','','参数错误!',true);
		}
		$user_id=userAuth::id();
		$params['user_id']=$user_id;
		$params['account_id']=$account_id;
		$params['deleted']=0;
		$info=$account->getRow('account_id',$params);
		if(empty($info)){
			return $this->splash('error','','没有该银行卡信息!',true);
		}
		$data['deleted']=1;
		$data['modified_time']=time();
		$res=$account->update($data,array('account_id'=>$info['account_id']));
		if($res){
			return $this->splash('success','','解绑成功',true);
		}else{
			return $this->splash('error','','解绑失败!',true);			
		}

	}
}
