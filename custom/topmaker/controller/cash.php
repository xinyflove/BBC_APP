<?php
/**
* 电视塔提现相关
* author by wanghaichao
* date 2019/8/3
*/
class topmaker_ctl_cash extends topmaker_controller {
	
	/**
	* 提现列表
	* author by wanghaichao
	* date 2019/8/3
	*/
    public function index(){
		return $this->page('topmaker/cash/index.html');
	}
	
	/**
	* 提现功能
	* author by wanghaichao
	* date 2019/8/5
	*/
	public function addCash(){
		$seller_id=$this->sellerId;
		$bank=app::get('sysmaker')->model('seller_bank')->getRow('*',array('seller_id'=>$seller_id));
		$bank['card_number']=substr($bank['card_number'],-4,4);
		$pagedata=kernel::single('sysmaker_data_commission')->getTicketSellerCommission($seller_id);
		//echo "<pre>";print_r($seller_id);die();
		$backurl=url::action('topmaker_ctl_cash@addCash');
		$pagedata['bank']=$bank;
		$pagedata['bindurl']=url::action('topmaker_ctl_cash@bindbank',array('backurl'=>$backurl));
		return $this->page('topmaker/cash/addcash.html',$pagedata);
	}
	
	/**
	* 保存创客提现
	* author by wanghaichao
	* date 2019/8/5
	*/
	public function save(){
		$postdata=input::get();
		$seller_id=$this->sellerId;
		$shop=app::get('sysmaker')->model('shop_rel_seller')->getRow('shop_id',array('seller_id'=>$seller_id));
		$shop_id=$shop['shop_id'];
		if($postdata['payment']<=0){
            return $this->splash('error','','提现金额不能小于0',true);			
		}
	
		//创客一共有的佣金
		//$seller_commission=app::get('sysclearing')->model('seller_billing_detail')->getRow('SUM(seller_commission) AS total_commission',array('seller_id'=>$seller_id,'shop_id'=>$shop_id));
		if($shop_id==46){			
			$seller_commission=app::get('sysclearing')->model('seller_settlement_billing_detail')->getRow('SUM(seller_commission) AS total_commission',array('seller_id'=>$seller_id,'shop_id'=>$shop_id));
		}else{
			$seller_commission=app::get('sysclearing')->model('seller_billing_detail')->getRow('SUM(seller_commission) AS total_commission',array('seller_id'=>$seller_id,'shop_id'=>$shop_id));
		}

		//创客已提现过的佣金
		$has_commission=app::get('sysmaker')->model('cash')->getRow('SUM(payment) as has_commission',array('seller_id'=>$seller_id,'shop_id'=>$shop_id,'status|in'=>array('success','pending')));
		//剩余的佣金
		$sy_commission=(float)$seller_commission['total_commission']-(float)$has_commission['has_commission'];
		$payment=(float)$postdata['payment'];
		if ($payment<1)
		{
            return $this->splash('error','','提现金额不能小于1元',true);		
		}
		//$sy_commission=round($sy_commission,2);
		$res=ecmath::number_minus(array($sy_commission,$payment));
		if($res<0){
            return $this->splash('error','','该创客剩余佣金为:'.$sy_commission.'元,提现金额不能大于剩余佣金',true);
		}
		$bankinfo=app::get('sysmaker')->model('seller_bank')->getRow('bank_name,card_number',array('seller_id'=>$seller_id));
		if($bankinfo){
			$data['bank_name']=$bankinfo['bank_name'];
			$data['card_number']=$bankinfo['card_number'];
		}
		$data['payment']=$payment;
		$data['seller_id']=$seller_id;
		$data['shop_id']=$shop_id;
		$data['remark']=$postdata['remark'];
		$data['status']='pending';
		$data['type']='apply';
		$data['create_time']=time();
		$data['openid']=$_SESSION['wx_openid'];
		$res=app::get('sysmaker')->model('cash')->insert($data);
		if($res){
			$backurl=url::action('topmaker_ctl_cash@index');
			return $this->splash('success',$backurl,'申请提现成功,等待平台审核!',true);	
		}else{
			return $this->splash('error','','申请失败');
		}
	}


	/**
	* ajax获取提现列表的
	* author by wanghaichao
	* date 2019/8/5
	*/
	public function ajaxGetCashList()
    {
        $params = input::get();
        // 获取的每页显示数量
        $limit = 10;
		//echo "<pre>";print_R($params);die();
        if($params['page_size']) {
            $limit = intval($params['page_size']) > 0 ? intval($params['page_size']) : $limit;
        }
        // 获取当前页面数
        $offset = 0;
        if ($params['pages']) {
            $params['pages'] = intval($params['pages']) > 0 ? intval($params['pages']) : 1;
            $offset = ($params['pages'] - 1) * $limit;
        }

        $filter['seller_id'] = $this->sellerId;

        $list = kernel::single('sysmaker_data_commission')->cashList($filter, $offset, $limit);
		//echo "<pre>";print_r($list);die();
        echo json_encode($list);
    }
	/**
	* 绑定银行卡
	* author by wanghaichao
	* date 2019/8/3
	*/
	public function bindbank(){
		$seller_id=$this->sellerId;
		$info=app::get('sysmaker')->model('seller_bank')->getRow('*',array('seller_id'=>$seller_id));
		//echo "<pre>";print_r($info);die();
		$pagedata['info']=$info;
		$pagedata['backurl']=input::get('backurl');
		return $this->page('topmaker/cash/bindbank.html',$pagedata);
	}
	
	/**
	* 银行卡绑定功能
	* author by wanghaichao
	* date 2019/8/5
	*/
	public function bind(){
		$seller_id=$this->sellerId;
		$data=input::get();
		if(empty($data['name'])){
            return $this->splash('error', null, '请输入姓名');
		}
		if(empty($data['card_number'])){
			return $this->splash('error',null,'请输入银行卡号');
		}
		if(empty($data['bank_name'])){
			return $this->splash('error',null,'请输入银行名称');
		}
		if(!is_numeric($data['card_number'])){
			return $this->splash('error',null,'请输入正确的银行卡号');
		}
		$model=app::get('sysmaker')->model('seller_bank');
		$info=$model->getRow('id',array('seller_id'=>$seller_id));
		if($info['id']){
			$res=$model->update($data,array('id'=>$info['id']));
		}else{
			$data['seller_id']=$seller_id;
			$res=$model->save($data);
		}
		$backurl=input::get('backurl');
		if($res){
			return $this->splash('success',$backurl,'保存成功');
		}else{
			return $this->splash('error','','保存失败');
		}
		//echo "<pre>";print_r($data);die();
	}

}