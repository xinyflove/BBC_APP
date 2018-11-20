<?php
/**
 * Tvplaza
 *	@模态框一些信息
 * @user  小超
 * @email  1013631519@qq.com
 */
class topshop_ctl_syscart_modal_stages extends topshop_controller
{

	//用来显示交每期的金额
	public function ajaxPayment(){
        $pagedata['stages_id'] = input::get('stages_id');
		$stage=app::get('syscart')->model('stages')->getRow('lease_id,deductible_amount,due_amount,owner_name,mobile,repay_time',array('stages_id'=>$pagedata['stages_id']));
		$pagedata['payment']=$stage['due_amount']-$stage['deductible_amount'];
		$pagedata['stage']=$stage;
		$lease=app::get('syscart')->model('lease_cart')->getRow('cart_number',array('lease_id'=>$stage['lease_id']));
		$pagedata['cart_number']=$lease['cart_number'];
        return view::make('topshop/syscart/modal/stage.html', $pagedata);
	}
	//支付期款
	public function payment(){
		$stagesModel=app::get('syscart')->model('stages');
		$postSend=input::get();
		//$stagesInfo=$stagesModel->getRow('deductible_amount,due_amount,lease_id',array('stages_id'=>$postSend['stages_id']));
		$params['payment']='后台还款';																						//还款方式
		$params['serial_number']=$postSend['serial_number'];												//流水账号 
		$params['repayment_amount']=$postSend['repayment_amount'];								//实际还款金额
		//$updateArr['repayment_date']=time();																				//真正还款日期
		$params['status']=$postSend['status'];																			//是否逾期1正常2逾期
		$params['lease_fines']=$postSend['lease_fines']?$postSend['lease_fines']:'0';	//滞纳金
		$params['payment_status']=1;																							//还款状态,已还款
		$params['stages_id']=$postSend['stages_id'];															//期数id

		//$stagesInfo['lease_fines']=$params['updataArr'];							//到时候判断,如果有滞纳金的话,流水表还要插一条滞纳金的记录   
		//插入期数表
		//$restult=$stagesModel->save($updataArr);
		//$this->leaseUpdate($stagesInfo);
		$res=app::get('syscart')->rpcCall('stages.update',$params);
		if($res){
			$status = 'success';
			$msg = app::get('topshop')->_('缴费成功');
            return $this->splash($status,'',$msg,true);
		}
	
	}

	//停止租车
	public function ajaxStopLease(){
		$lease_id=input::get('lease_id');
		$pagedata['lease_id']=$lease_id;
		$pagedata['owner']=app::get('syscart')->model('lease_cart')->getRow('owner_name,mobile',array('lease_id'=>$lease_id));
        return view::make('topshop/syscart/modal/stoplease.html', $pagedata);
	}
	
	//处理停止租车的逻辑
	public function stopLease(){
		$params=input::get();
		$leaseModel=app::get('syscart')->model('lease_cart');
		$stagesModel=app::get('syscart')->model('stages');
		$leaseinfo=$leaseModel->getRow('remarks,owner_id,owner_name',array('lease_id'=>$params['lease_id']));
		$date=date("Y-m-d",time());
		//要更新主表的数据
		$leaseparams['status']=3;    //停租
		$leaseparams['remarks']=$leaseinfo['remarks']."车主:".$leaseinfo['owner_name']."在".$date."停止租车,违约金为:".$params['breach'];
		$leaseparams['breach']=$params['breach'];


		$flow['serial_number']=$params['serial_number'];
		$flow['repayment_amount']=$params['breach'];
		$flow['owner_name']=$params['owner_name'];
		$flow['date']=$date;
		$flow['owner_id']=$leaseinfo['owner_id'];
		$db = app::get('syscart')->database();
		$db->beginTransaction();
		try
		{ 
			$flowInsertSql=$this->flowInsertSql($flow);
			$leaseModel->update($leaseparams,array('lease_id'=>$params['lease_id']));
			$stagesModel->update(array('payment_status'=>4),array('lease_id'=>$params['lease_id']));
			//处理		
			app::get('base')->database()->executeUpdate($flowInsertSql);		//插入流水表数据
			$db->commit();
            return $this->splash('success','','停租成功!',true);
		}  
		catch(Exception $e)
		{
			$db->rollback();
			$msg = app::get('sysitem')->_('停租失败!');
            return $this->splash('error','',$msg,true);
			return false;
		}


	}
	
//返回插入流水表的sql
	public function flowInsertSql($params){
		$flow_type='4';																			//说明是违约金
		$payment_date=time();																//支付日期
		$shop_id=$this->shopId;													//店铺id
		//$owner_id=$params['owner_id'];												//车主id
		$created_time=time();								
		$payment_type='后台支付';										//支付方式:后台还款,支付宝,微信
		$serial_number=$params['serial_number'];								//流水号

		$amount=$params['repayment_amount'];
		$ownerInfo=app::get('syscart')->model('owner')->getRow('owner_id,owner_name,company_name,mobile,company_id',array('owner_id'=>$params['owner_id']));
		//print_r($ownerInfo);die();
		$remarks=$ownerInfo['owner_name']."在".$params['date']."停止租车,违约金为:{$amount}元";
		$owner_id=$ownerInfo['owner_id'];
		$owner_name=$ownerInfo['owner_name'];
		$mobile=$ownerInfo['mobile'];
		$company_id=$ownerInfo['company_id'];
		$company_name=$ownerInfo['company_name'];
		$insertSql="INSERT INTO `syscart_flow` (`flow_type`,`payment_date`,`shop_id`,`owner_id`,`owner_name`,`mobile`,`company_id`,`created_time`,`payment_type`,`serial_number`,`amount`,`company_name`,`remarks`) VALUES ('{$flow_type}','{$payment_date}','{$shop_id}','{$owner_id}','{$owner_name}','{$mobile}','{$company_id}','{$created_time}','{$payment_type}','{$serial_number}','{$amount}','{$company_name}','{$remarks}')";
		return $insertSql;
	}
	

}
?>