<?php
/**
 * Tvplaza
 *	@  租车主控制器
 * @user  小超
 * @email  1013631519@qq.com
 */

class topshop_ctl_syscart_syscart extends topshop_controller
{
    public $limit = 10;
	
	//获取租赁列表
	//
	public function index(){
		$pagedata='';
		return $this->page('topshop/syscart/index.html',$pagedata);
	}
	
	public function leaselist(){
        $postSend = input::get();
        $page = $postSend['page'] ? $postSend['page'] : 1;
		$params['shop_id']=$this->shopId;
		$params['page_no']=$page;
		$params['page_size']=$this->limit;
		if(isset($postSend['status']) && !empty($postSend['status'])){
			$params['status']=$postSend['status'];
		}
		//根据手机号查询
		if(isset($postSend['mobile']) && !empty($postSend['mobile'])){
			$params['mobile']=$postSend['mobile'];
		}
		//根据车主姓名查询
		if(isset($postSend['owner_name']) && !empty($postSend['owner_name'])){
			$params['owner_name']=$postSend['owner_name'];
		}
		//根据公司名称查询
		if(isset($postSend['company_name']) && !empty($postSend['company_name'])){
			$params['company_name']=$postSend['company_name'];
		}
		//根据车牌号查询
		if(isset($postSend['cart_number']) && !empty($postSend['cart_number'])){
			$params['cart_number']=$postSend['cart_number'];
		}
		//根据销售经理查询
		if(isset($postSend['sale_manager']) && !empty($postSend['sale_manager'])){
			$params['sale_manager']=$postSend['sale_manager'];
		}
		//取出列表
		$leaseList=app::get('syscart')->rpcCall('lease.list',$params);
        $count = $leaseList['count'];
        $pagedata['count'] = $count;
		$pagedata['total']=$leaseList['total'];
		unset($leaseList['count']);
		unset($leaseList['total']);
		$pagedata['leaseList']=$leaseList;
		//$leaseModel=app::get('syscart')->model('lease_cart');
		//求和计算总数
		//$pagedata['total']=$leaseModel->getRow('SUM(`lease_total`) as lease_total,SUM(`lease_deposit`) as lease_deposit_total,SUM(`front_money`) as front_money_total',$params);
        //处理翻页数据
        $pagedata['limits'] = $limit = $this->limit;
        $postSend['page'] = time();
        $link = url::action('topshop_ctl_syscart_syscart@leaselist',$postSend);
        $pagedata['pagers'] = $this->__pagers($pagedata['count'],$page,$limit,$link);
		$pagedata['status']=$params['status'];
		
		return $this->page('topshop/syscart/leaselist.html', $pagedata);
	}
	
	//每一期的详细信息
	public function stageslist(){
        $postSend = input::get();
		$params['lease_id']=$postSend['lease_id'];
		$params['page_no']=$postSend['page'];
		$stagesModel = app::get('syscart')->model('stages');
		
		if(isset($postSend['status']) && !empty($postSend['status'])){
			$params['status']=$postSend['status'];
		}
        $count = $stagesModel->count($params);
		$pagedata['count']=$count;
        $page =  $params['page_no'] ? $params['page_no'] : 1;
        $limit = $this->limit;
        $pageTotal = ceil($count/$limit);
        $currentPage = ($pageTotal < $page) ? $pageTotal : $page; //防止传入错误页面，返回最后一页信息
        $offset = ($currentPage-1) * $limit;
        $postSend['page'] = time();
        $link = url::action('topshop_ctl_syscart_syscart@stageslist',$postSend);
        $pagedata['pagers'] = $this->__pagers($pagedata['count'],$page,$limit,$link);
		$stagesList=$stagesModel->getList('*',$params,$offset,$limit,$orderBy);
		//取出押金和定金
		$pagedata['lease']=app::get('syscart')->model('lease_cart')->getRow('front_money,lease_deposit',array('lease_id'=>$postSend['lease_id']));
		//取出总和和已经还款金额
		$pagedata['total']=$stagesModel->getRow('SUM(`due_amount`) as due_total,SUM(`repayment_amount`) as repayment_total',$params);
		//逻辑改为总还款额减去定金 然后再平均分给每期
		$pagedata['total']['due_total']=$pagedata['total']['due_total']+$pagedata['lease']['front_money'];
		$pagedata['total']['repayment_total']=$pagedata['total']['repayment_total']+$pagedata['lease']['front_money'];
		/*add_2018/1/3_by_wanghaichao_end*/
		$pagedata['surplus_total']=$pagedata['total']['due_total']-$pagedata['total']['repayment_total'];   //剩余未还款金额
		/*add_2018/1/3_by_wanghaichao_start*/
		
		$pagedata['stagesList']=$stagesList;
		//echo "<pre>";print_r($pagedata);die();
		return $this->page('topshop/syscart/stageslist.html', $pagedata);
	}
	
	
	//增加租赁信息的
	public function leaseAdd(){
		//取出公司
		//选择期数
		$deposit_id=input::get('deposit_id');
		$dataArr=array(5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25);
		$companyModel = app::get('syscart')->model('company');
		$companylist=$companyModel->getList('company_id,company_name',array('shop_id'=>$this->shopId));
		$pagedata['companylist']=$companylist;
		$pagedata['dataArr']=$dataArr;
		$leaseModel = app::get('syscart')->model('lease_cart');
		$leaseInfo=$leaseModel->getRow('sale_manager,sale_id,lease_start_time,repayment_date,lease_type,cart_id',array('deposit_id'=>$deposit_id));
		$pagedata['deposit_info']=app::get('syscart')->model('deposit')->getRow('remarks',array('deposit_id'=>$deposit_id));
		$pagedata['cart_info']=$this->getCartInfo($leaseInfo['cart_id']);
		//取出销售经理的信息
		$pagedata['sale']=app::get('syscart')->model('sale')->getList('sale_id,sale_manager');
		if($leaseInfo['lease_start_time']){
			$leaseInfo['lease_start_time']=date('Y-m-d',$leaseInfo['lease_start_time']);
		}else{
			$leaseInfo['lease_start_time']='';
		}
		$pagedata['leaseInfo']=$leaseInfo;
		
		//$pagedata['']
		return $this->page('topshop/syscart/leaseadd.html', $pagedata); 
	}

	//交押金
	public function depositAdd(){
		$params=input::get();
		//echo "<pre>";print_r($params);die();
		if($params['submit']=='true'){
			
			if(empty($params['owner_id'])){
				return $this->splash('error','','请选择车主!',true);
			}

			if(empty($params['cart_id'])){
				return $this->splash('error','','请选择车辆!',true);
			}

			if(!$this->checkLease(array('cart_id'=>$params['cart_id'],'owner_id'=>$params['owner_id']))){
				return $this->splash('error','','该车主已经租过这辆车!',true);
			}
			$cart_info=$this->getCartInfo($params['cart_id']);
			$insertLease=$params['lease'];
			$insertLease['lease_total']=($insertLease['lease_each']*$insertLease['lease_stages'])+$insertLease['front_money'];                     //还款总额
			$insertLease['lease_balance']=($insertLease['lease_each']*$insertLease['lease_stages']-$insertLease['lease_deposit']);                          //剩余还款额
			//插入租赁主表
			$ownerinfo=app::get('syscart')->model('owner')->getRow('*',array('owner_id'=>$params['owner_id']));
			$insertLease['owner_name']=$ownerinfo['owner_name'];
			$insertLease['company_id']=$ownerinfo['company_id'];
			$insertLease['company_name']=$ownerinfo['company_name'];
			$insertLease['shop_id']=$this->shopId;
			$insertLease['owner_id']=$params['owner_id'];
			$insertLease['created_time']=time();							
			$insertLease['status']=1;                                               //状态
			$insertLease['cart_name']=$cart_info['cart_name'];         //汽车品牌
			$insertLease['cart_total']=$cart_info['price'];          // 汽车总价
			$insertLease['mobile']=$ownerinfo['mobile'];				//手机号
			//插入押金表的
			$depositArr['owner_id']=$params['owner_id'];                                  //车主id
			$depositArr['owner_name']=$ownerinfo['owner_name'];				//车主姓名
			$depositArr['lease_deposit']=$insertLease['lease_deposit'];			//押金
			$depositArr['front_money']=$insertLease['front_money'];			//定金
			$depositArr['mobile']=$ownerinfo['mobile'];									//手机号
			$depositArr['status']=1;																			//状态默认是 1正常2逾期
			$depositArr['created_time']=time();
			$depositArr['remarks']=$params['remarks'];              //备注
			$depositArr['shop_id']=$this->shopId;

			$depositModel= app::get('syscart')->model('deposit');
			$leaseModel = app::get('syscart')->model('lease_cart');
			//事物
			$db = app::get('syscart')->database();
			$db->beginTransaction();
			$deposit_id=$depositModel->insert($depositArr);
			$insertLease['deposit_id']=$deposit_id;
			$insertLease['cart_id']=$params['cart_id'];
			try
			{ 
				$leaseModel->save($insertLease);
				//处理
				app::get('syscart')->model('cart')->update(array('status'=>1),array('cart_id'=>$params['cart_id']));    //说明这辆车已经被租用了
				$sql=$this->createFlow($insertLease);   //处理流水表
				app::get('base')->database()->executeUpdate($sql);
				$db->commit();
				$url = url::action('topshop_ctl_syscart_syscart@ownerlist');
				return $this->splash('success',$url,'保证金缴费成功!',true);
			}  
			catch(Exception $e)
			{
				throw new \LogicException($e);
				$db->rollback();
				return $this->splash('error','','保证金缴费失败了!',true);
			}
			
		}
		$cartParams['shop_id']=$this->shopId;
		$cartParams['status']=2;
		$pagedata['cartlist']=app::get('syscart')->model('cart')->getList('*',$cartParams);                     //缴纳押金的时候取出车辆信息
		//结束
		$ownerModel=app::get('syscart')->model('owner');
		$ownerRow=$ownerModel->getRow('*',array('owner_id'=>$params['owner_id']));
		$pagedata['ownerRow']=$ownerRow;
		$pagedata['ower_id']=$params['ower_id'];
		return $this->page('topshop/syscart/depositadd.html', $pagedata); 
		
	}

	//根据cart_id和owner_id查询
	//两者是唯一的
	public function checkLease($info){
		$leaseModel=app::get('syscart')->model('lease_cart');
		if($leaseModel->getRow('lease_id',$info)){
			return false;
		}else{
			return true;
		}
	}

	//根据cart_id 获取车辆的信息的
	public function getCartInfo($cart_id){
		$cart=app::get('syscart')->model('cart');
		$cart_info=$cart->getRow('*',array('cart_id'=>$cart_id));
		return $cart_info;
	}



	//押金管理
	public function depositlist(){
        $postSend = input::get();
		$params['owner_id']=$postSend['owner_id'];
		$params['page_no']=$postSend['page'];
		$depositModel = app::get('syscart')->model('deposit');
		
		if(isset($postSend['mobile']) && !empty($postSend['mobile'])){
			$params['mobile']=$postSend['mobile'];
		}
		if(isset($postSend['owner_name']) && !empty($postSend['owner_name'])){
			$params['owner_name|has']=$postSend['owner_name'];

		}
        $count = $depositModel->count($params);
		$pagedata['count']=$count;
        $page =  $params['page_no'] ? $params['page_no'] : 1;
        $limit = $this->limit;
        $pageTotal = ceil($count/$limit);
        $currentPage = ($pageTotal < $page) ? $pageTotal : $page; //防止传入错误页面，返回最后一页信息
        $offset = ($currentPage-1) * $limit;
        $postSend['page'] = time();
        $link = url::action('topshop_ctl_syscart_syscart@depositlist',$postSend);
        $pagedata['pagers'] = $this->__pagers($pagedata['count'],$page,$limit,$link);
		$depositlist=$depositModel->getList('*',$params,$offset,$limit,'created_time desc');
		$pagedata['depositlist']=$depositlist;
		//echo "<pre>";print_r($depositlist);die();
		return $this->page('topshop/syscart/depositlist.html', $pagedata); 
	}


	//流水列表
	public function flowlist(){
        $postSend = input::get();
		$params['owner_id']=$postSend['owner_id'];
		$params['page_no']=$postSend['page'];
		$flowModel = app::get('syscart')->model('flow');
		
		if(isset($postSend['mobile']) && !empty($postSend['mobile'])){
			$params['mobile']=$postSend['mobile'];
		}
		if(isset($postSend['owner_name']) && !empty($postSend['owner_name'])){
			$params['owner_name|has']=$postSend['owner_name'];
		}
		if(isset($postSend['company_name']) && !empty($postSend['company_name'])){
			$params['company_name|has']=$postSend['company_name'];
		}
		if(isset($postSend['flow_type']) && $postSend['flow_type']!=''){
			$params['flow_type']=$postSend['flow_type'];
		}
        $count = $flowModel->count($params);
		$pagedata['count']=$count;
        $page =  $params['page_no'] ? $params['page_no'] : 1;
        $limit = $this->limit;
        $pageTotal = ceil($count/$limit);
        $currentPage = ($pageTotal < $page) ? $pageTotal : $page; //防止传入错误页面，返回最后一页信息
        $offset = ($currentPage-1) * $limit;
        $postSend['page'] = time();
        $link = url::action('topshop_ctl_syscart_syscart@flowlist',$postSend);
        $pagedata['pagers'] = $this->__pagers($pagedata['count'],$page,$limit,$link);
		$flowlist=$flowModel->getList('*',$params,$offset,$limit,'flow_id asc');
		$pagedata['total']=$flowModel->getRow('SUM(`amount`) as amount_total',$params);
		$pagedata['flowlist']=$flowlist;
		$pagedata['flow_type']=$postSend['flow_type'];
		//echo "<pre>";print_r($depositlist);die();
		return $this->page('topshop/syscart/flowlist.html', $pagedata); 
	}


	//保存租赁数据的
	public function leaseSave(){
		if($_POST){
			$is_again=input::get('is_again');
			$insertArr=array();
			/*if($_POST['owner_id']!=''){
				$insertArr['owner_id']=$_POST['owner_id'];
				$ownerModel=app::get('syscart')->model('owner');
				$ownerRow=$ownerModel->getRow('*',array('owner_id'=>$_POST['owner_id']));				
				$insertArr['mobile']=$ownerRow['mobile'];
				$insertArr['company_id']=$ownerRow['company_id'];
				$insertArr['company_name']=$ownerRow['company_name'];
				$insertArr['user_name']=$ownerRow['owner_name'];
			}
			/*else{
				if(isset($_POST['item']['company_id'])){
					$companyModel = app::get('syscart')->model('company');
					$companyRow=$companyModel->getRow('*',array('company_id'=>$_POST['company_id']));
					$insertArr['user_name']=$companyRow['company_contact'];
					$insertArr['mobile']=$companyRow['mobile'];
					$insertArr['company_id']=$companyRow['company_id'];
					$insertArr['company_name']=$companyRow['company_name'];
				}else{
					$insertArr['company_id']=0;
					$insertArr['user_name']=$_POST['item']['user_name'];
					$insertArr['mobile']=$_POST['item']['mobile'];
				}
			}*/
			if($_POST['sale_id']!=''){
				$insertArr['sale_id']=$_POST['sale_id'];
				$sale=app::get('syscart')->model('sale')->getRow('sale_manager',array('sale_id'=>$_POST['sale_id']));
				$insertArr['sale_manager']=$sale['sale_manager'];
				//print_r($insertArr);die();
			}

			$insertArr['cart_name']=$_POST['item']['cart_name'];                            //汽车品牌
			$insertArr['cart_number']=$_POST['item']['cart_number'];                    //车牌号
			//$insertArr['lease_deposit']=$_POST['item']['lease_deposit'];					//押金
			//$insertArr['front_money']=$_POST['item']['front_money'];						//定金
			//$insertArr['lease_each']=$_POST['item']['lease_each'];                           //每期的还款金额
			//$insertArr['lease_total']=$_POST['item']['lease_total'];							//还款总额
			$insertArr['lease_start_time']=strtotime($_POST['item']['lease_start_time']);//租购起始日期
			 //租购结束日期
			//$insertArr['created_time']=time();																	//创建时间
			//$insertArr['lease_stages']=$_POST['item']['lease_stages'];						//期数
			$insertArr['repayment_date']=$_POST['item']['repayment_date'];         //每月还款日
			$insertArr['shop_id']=$this->shopId;																//店铺id
			$insertArr['lease_type']=$_POST['item']['lease_type'];								//租用形式
			$leaseModel = app::get('syscart')->model('lease_cart');	
			$lease_stages=$leaseModel->getRow('owner_name,cart_id,lease_stages,repayment_date',array('deposit_id'=>$_POST['deposit_id']));    //取出一共多少期
			$insertArr['lease_end_time']=strtotime(date('Y-m-'.$_POST['item']['repayment_date'],strtotime("{$_POST['item']['lease_start_time']} +{$lease_stages['lease_stages']} month")));      //计算租购结束日期 
			$cart_id=$lease_stages['cart_id'];
			$cart_info=app::get('syscart')->model('cart')->getRow('cart_number',array('cart_id'=>$cart_id));
		
			if($cart_info['cart_number']==''){
				app::get('syscart')->model('cart')->update(array('cart_number'=>$insertArr['cart_number']),array('cart_id'=>$cart_id));
			}
			//echo "<pre>";print_r($insertArr);die();					

			$res=$leaseModel->update($insertArr,array('deposit_id'=>$_POST['deposit_id']));
			if($res){
				//$db = app::get('syscart')->database();
				//$db->beginTransaction();									
				$leaseRow=$leaseModel->getRow('*',array('deposit_id'=>$_POST['deposit_id']));
				//echo "<pre>";print_r($leaseRow);die();
				/*add_2018/1/17_by_wanghaichao_start*/
				$stages=app::get('syscart')->model('stages')->getList('stages_id',array('lease_id'=>$leaseRow['lease_id']));
				$stagesSql=$this->insertStages($leaseRow);
				if($is_again){
					//先删除stages中数据,再进行插入
					app::get('syscart')->model('stages')->delete(array('lease_id'=>$leaseRow['lease_id']));
					//生成期数表
					app::get('base')->database()->executeUpdate($stagesSql['insertSql']);   //用sql 插入期数表中
					$updateArr['repaid_stages']=$stagesSql['stages'];
				}else{
					if($insertArr['repayment_date']!=$lease_stages['repayment_date']){
						$repayment_date_new=strtotime(date('Y-m-'.$insertArr['repayment_date'],time()));  //新的还款日
						$repayment_date_old=strtotime(date('Y-m-'.$lease_stages['repayment_date'],time()));  //老的还款日
						$difference=$repayment_date_new-$repayment_date_old;   //两个时间戳的差值
						$update_stages_sql="UPDATE  `syscart_stages` SET `repay_time`=(`repay_time`+".$difference.") WHERE lease_id=".$leaseRow['lease_id'];
						app::get('base')->database()->executeUpdate($update_stages_sql);
					}
					//echo "<pre>";print_r($lease_stages);die();
				}
				/*add_2018/1/17_by_wanghaichao_end*/
				
				$leaseModel->update($updateArr,array('deposit_id'=>$_POST['deposit_id']));
				$depositModel= app::get('syscart')->model('deposit');
				$depositModel->update(array('status'=>2,'remarks'=>$_POST['remarks']),array('deposit_id'=>$_POST['deposit_id']));   //更新押金表状态
				//$db->commit();   //事物提交
				$this->sellerlog("{$lease_stages['owner_name']}租车,车牌号为:{$insertArr['cart_number']}");
				$url = url::action('topshop_ctl_syscart_syscart@leaselist');
				$msg = app::get('topshop')->_('保存成功');
				return $this->splash('success',$url,$msg,true);
			}else{
				//$db->rollback();   //事物回滚
				return $this->splash('error','','保存失败了!',true);
			}
		}
	}

	//生成每一期交款明细
	//传入期数
	//总额
	//定金
	//押金
	//车主
	//每月几号还款
	//循环插入表中
	//生成每期还款明细
	//生成返回的sql
	private function insertStages($params){
		if(empty($params)){
			return false;
		}
		//echo "<pre>";print_r($params);die();
		$lease_total=$params['lease_total'];          //总的应该还款的金额;
		$front_money=$params['front_money'];      //定金;可以为0;别忘了0 的判断
		$lease_deposit=$params['lease_deposit'];     //押金
		$lease_stages=$params['lease_stages'];			//期数
		$lease_each=$params['lease_each'];				//每期还款金额
		$insertSql="INSERT INTO syscart_stages (`shop_id`,`lease_id`,`status`,`created_time`,`mobile`,`owner_id`,`owner_name`,`due_amount`,`lease_fines`,`repayment_amount`,`remarks`,`serial_number`,`repayment_date`,`payment`,`deductible_amount`,`deductible_type`,`repay_time`,`payment_status`) VALUES ";
		$shop_id=$this->shopId;
		//插入数据表中公用的字段
		//$insertArr['shop_id']=$this->shopId;										//对应的店铺id
		$lease_id=$params['lease_id'];							//租赁主表id
		$status=1;																//
		$created_time=time();										//创建时间
		$mobile=$params['mobile'];
		$owner_id=$params['owner_id'];						//车主id
		$owner_name=$params['owner_name'];			//车主姓名
		$due_amount=$lease_each;								//每一期应该还款金额
		$lease_fines=0;														//滞纳金
		$each_repayment_date=$params['repayment_date'];      //每月还款日
		$lease_start_time=date('Y-m-1',$params['lease_start_time']);    //租购起始日期
		$lease_start_time_day = date('d',$params['lease_start_time']);   //起始租购日 
			//print_r($each_repayment_date);die();

		if($lease_start_time_day>=$each_repayment_date){
			$m=1;            //用来做还款日期递增的;
		}else{
			$m=0;			 //用来做还款日期递增的;
		}
		$stages=0;    //已经还款的期数
		//向上取整ceil
		//先判断定金的逻辑
		//if($front_money>0){
		//	$front_stages=ceil($front_money/$lease_each);      //前几期的判断;除去定金
		//	$surplus_front_money=$front_money%$lease_each;    //不能够整除,剩余下的钱
		//	for($i=1;$i<=$front_stages;$i++){
		//		$stagesModel=app::get('syscart')->model('stages');       //实例化期数表
		//		if($i==$front_stages && $surplus_front_money!=0){
		//			$repayment_amount=$surplus_front_money;	//这里用实际还款金额还是应还款金额?
		//			$remarks='本期款项由定金抵扣'.$surplus_front_money.'元,剩余'.($lease_each-$surplus_front_money).'元需要车主按时还款';
					//sql拼接
		//			$serial_number='';
		//			$repayment_date='';
		//			$payment='';
		//			$deductible_amount=$surplus_front_money;
		//			$deductible_type='1';														//1是定金
		//			$payment_status=3;                                                      //部分还款
		//		}else{
		//			$repayment_amount=$lease_each;									//已经用定金抵扣了;
		//			$remarks='本期款项由定金抵扣,车主无需还款';				//备注本期由定金抵扣
		//			$serial_number='定金抵扣';												//流水也是有定金抵扣
		//			$repayment_date=time();													//还款日期,
		//			$payment='定金抵扣';															//支付方式
		//			$deductible_amount=$repayment_amount;
		//			$deductible_type='1';														//1是定金
		//			$payment_status=1;															//全部还款
		//			$stages++;
		//		}
		//		//计算每月应该还款的日期
		//		$repay_time=strtotime(date('Y-m-'.$each_repayment_date,strtotime("$lease_start_time +".$m." month")));
		//		$insertSql.="('{$shop_id}','{$lease_id}','{$status}','{$created_time}','{$mobile}','{$owner_id}','{$owner_name}','{$due_amount}','{$lease_fines}','{$repayment_amount}','{$remarks}','{$serial_number}','{$repayment_date}','{$payment}','{$deductible_amount}','{$deductible_type}','{$repay_time}','{$payment_status}'),";
		//		$m++;
				//$res=$stagesModel->save($insertArr);
		//	}
		//}else{
			$front_stages=0;
		//}
		//押金的处理逻辑
		if($lease_deposit>0){
			$deposit_stages=ceil($lease_deposit/$lease_each);      //后面几期有押金抵扣
			$surplus_lease_deposit=$lease_deposit%$lease_each;    //不能够整除,剩余下的钱
		}else{
			$deposit_stages=0;
		}
		
		//用总期数减去押金抵扣的期数和定金抵扣的期数;剩余的就是平常该还的期数和钱数
		$surplus_lease_stages=($lease_stages-$front_stages-$deposit_stages);     //总期数减去押金抵扣的期数和定金抵扣的期数
		for($i=1;$i<=$surplus_lease_stages;$i++){
			$stagesModel=app::get('syscart')->model('stages');       //实例化期数表
			$repayment_amount=0;				//每期实际还款金额为0;
			$remarks='';
			$serial_number='';
			$repayment_date='';
			$payment='';
			$deductible_amount='';
			$deductible_type='';
			$payment_status=2;															//未还款
			//计算每月应该还款的日期
			$repay_time=strtotime(date('Y-m-'.$each_repayment_date,strtotime("$lease_start_time +".$m." month")));
			$insertSql.="('{$shop_id}','{$lease_id}','{$status}','{$created_time}','{$mobile}','{$owner_id}','{$owner_name}','{$due_amount}','{$lease_fines}','{$repayment_amount}','{$remarks}','{$serial_number}','{$repayment_date}','{$payment}','{$deductible_amount}','{$deductible_type}','{$repay_time}','{$payment_status}'),";
			$m++;
			//$stagesModel->save($insertArr);				//插入表中
		}
		
		//处理押金抵扣最后几期的逻辑
		if($deposit_stages>0){
			for($i=$deposit_stages;$i>=1;$i--){
			$stagesModel=app::get('syscart')->model('stages');       //实例化期数表
				if($i==$deposit_stages && $surplus_lease_deposit!=0){
					$repayment_amount=$surplus_lease_deposit;	//这里用实际还款金额还是应还款金额?
					$remarks='本期款项由押金抵扣'.$surplus_lease_deposit.'元,剩余'.($lease_each-$surplus_lease_deposit).'元需要车主按时还款';
					$serial_number='';
					$repayment_date='';
					$payment='';
					$deductible_amount=$surplus_lease_deposit;	
					$deductible_type='2';														//2是押金
					$payment_status=3;															//部分还款
				}else{
					$repayment_amount=$lease_each;							//已经用定金抵扣了;
					$remarks='本期款项由押金抵扣,车主无需还款';	//备注本期由定金抵扣
					$serial_number='押金抵扣';										//流水也是有定金抵扣
					$repayment_date=time();											//还款日期,
					$payment='押金抵扣';												//支付方式
					$deductible_amount=$repayment_amount;
					$deductible_type='2';													//2是押金
					$payment_status=1;															//全部还款
					$stages++;
				}
				//计算每月应该还款的日期
				$repay_time=strtotime(date('Y-m-'.$each_repayment_date,strtotime("$lease_start_time +".$m." month")));  
				$insertSql.="('{$shop_id}','{$lease_id}','{$status}','{$created_time}','{$mobile}','{$owner_id}','{$owner_name}','{$due_amount}','{$lease_fines}','{$repayment_amount}','{$remarks}','{$serial_number}','{$repayment_date}','{$payment}','{$deductible_amount}','{$deductible_type}','{$repay_time}','{$payment_status}'),";
				$m++;
				//$stagesModel->save($insertArr);				//插入表中
			}
		}
		$insertSql=substr($insertSql,0,-1);
		return array('insertSql'=>$insertSql,'stages'=>$stages);
	}

	//添加车主信息
	public function ownerAdd(){
		$owner_id=input::get('owner_id');
		$ownerModel=app::get('syscart')->model('owner');
		if($_POST){
			$mobile=trim($_POST['mobile']);
			//if(isset($_POST['company_id']) && $_POST['company_id']!=0){
			//	$companyModel = app::get('syscart')->model('company');
			//	$companyRow=$companyModel->getRow('company_name',array('company_id'=>$_POST['company_id']));
			//}else{
			//$companyRow['company_name']='';
			//}
			if($owner_id==''){
				$data=$ownerModel->getRow('owner_id',array('mobile'=>$mobile));
				if($data){
					return $this->splash('error','','该手机号已被注册成车主!',true);
				}
				
				$numberdata=$ownerModel->getRow('owner_id',array('number'=>$_POST['number']));

				if($numberdata){
					return $this->splash('error','','该身份证号已被注册成车主!',true);
				}
			}
			$insertArr=array();
			//$insertArr['company_id']=$_POST['company_id'];
			//$insertArr['company_name']='';
			$insertArr['number']=trim($_POST['number']);
			$insertArr['remarks']=trim($_POST['remarks']);
			$insertArr['mobile']=trim($_POST['mobile']);
			$insertArr['owner_name']=trim($_POST['owner_name']);
			$insertArr['address']=$_POST['address'];
			$insertArr['shop_id']=$this->shopId;
			$insertArr['status']='deposit';
			if($owner_id){
				$res=$ownerModel->update($insertArr,array('owner_id'=>$owner_id));
				$this->updateOwner($owner_id);
			}else{
				$insertArr['created_time']=time();
				$res=$ownerModel->insert($insertArr);
			}

			if($res){
				$url = url::action('topshop_ctl_syscart_syscart@ownerlist');
				$msg = app::get('topshop')->_('保存成功');
				return $this->splash('success',$url,$msg,true);
			}else{
				
				return $this->splash('error','','保存失败了!',true);
			}
		}
		if($owner_id){
			$pagedata['owner']=$ownerModel->getRow('*',array('owner_id'=>$owner_id));
		}
		return $this->page('topshop/syscart/owneradd.html', $pagedata); 
	}

	//公司列表
	public function companylist(){
		
        $postSend = input::get();
		$params['shop_id']=$this->shopId;
		$params['page_no']=$postSend['page'];
		if(isset($postSend['company_name']) && $postSend['company_name']!=''){
			$params['company_name|has']=$postSend['company_name'];		
		}
		if(isset($postSend['company_contact']) && $postSend['company_contact']!=''){
			$params['company_contact|has']=$postSend['company_contact'];		
		}
		$companyModel = app::get('syscart')->model('company');
        $count = $companyModel->count($params);
		$pagedata['count']=$count;
        $page =  $params['page_no'] ? $params['page_no'] : 1;
        $limit = $this->limit;
        $pageTotal = ceil($count/$limit);
        $currentPage = ($pageTotal < $page) ? $pageTotal : $page; //防止传入错误页面，返回最后一页信息
        $offset = ($currentPage-1) * $limit;
        $postSend['page'] = time();
        $link = url::action('topshop_ctl_syscart_syscart@companylist',$postSend);
        $pagedata['pagers'] = $this->__pagers($pagedata['count'],$page,$limit,$link);
		$companylist=$companyModel->getList('*',$params,$offset,$limit,$orderBy);
		$pagedata['companylist']=$companylist;
		return $this->page('topshop/syscart/companylist.html', $pagedata); 
	}
	

	//新增公司
	public function companyAdd(){
		$companyModel = app::get('syscart')->model('company');
		$company_id=input::get('company_id');
		if($_POST){
			$insertArr['company_name']=trim($_POST['company_name']);
			$insertArr['company_contact']=trim($_POST['company_contact']);
			$insertArr['mobile']=trim($_POST['mobile']);
			$insertArr['company_address']=trim($_POST['company_address']);
			$insertArr['shop_id']=$this->shopId;
			$insertArr['credit_code']=trim($_POST['credit_code']);
			$insertArr['remarks']=$_POST['remarks'];
			$insertArr['number']=trim($_POST['number']);
			if($company_id){
				$res=$companyModel->update($insertArr,array('company_id'=>$company_id));
				$this->updateCompany($company_id);         //更新公司名 操作的;
			}else{
				//print_r(111);die();
				//插入的操作
				$insertArr['created_time']=time();
				$res=$companyModel->insert($insertArr);
			}
			if($res){
				if($company_id){
					$insertArr['company_id']=$company_id;  //存车主表,company_id
				}else{
					$insertArr['company_id']=$res;  //存车主表,company_id
				}
				$result=$this->createOwner($insertArr);
				if($result==='error'){
					$companyModel->delete(array('company_id'=>$insertArr['company_id']));
					return $this->splash('success','','该车主已被注册',true);
				}else{
					$url = url::action('topshop_ctl_syscart_syscart@companylist');
					$msg = app::get('topshop')->_('保存成功');
					return $this->splash('success',$url,$msg,true);
				}
			}else{
				return $this->splash('error','','保存失败了!',true);
			}
		}
		if($company_id){
			$pagedata['company']=$companyModel->getRow('*',array('company_id'=>$company_id));
			$pagedata['company_id']=$company_id;
		}
		return $this->page('topshop/syscart/companyadd.html', $pagedata); 
	}

	//通过添加公司直接生成一条车主信息
	public function createOwner($params){
		$ownerModel=app::get('syscart')->model('owner');
		$is_data=$ownerModel->getRow('owner_id',array('company_id'=>$params['company_id']));
		if(!$is_data){
			$data=$ownerModel->getRow('owner_id',array('mobile'=>$params['mobile']));
			if($data){
				return 'error';
			}

			$numberdata=$ownerModel->getRow('owner_id',array('number'=>$params['number']));

			if($numberdata){
				return 'error';
			}
		}

		$owner['company_name']=$params['company_name'];				//公司名称
		$owner['owner_name']=$params['company_contact'];				//联系人,也就是车主名
		$owner['mobile']=$params['mobile'];										//手机号
		$owner['address']=$params['company_address'];	                //地址
		$owner['shop_id']=$this->shopId;
		$owner['company_id']=$params['company_id'];
		$owner['remarks']=$params['remarks'];
		$owner['status']='deposit';
		$owner['number']=$params['number'];
		if($is_data){
			$ownerModel->update($owner,array('owner_id'=>$is_data['owner_id']));
			$this->updateOwner($is_data['owner_id']);
			return true;
		}else{
			$owner['created_time']=time();
			return $ownerModel->insert($owner);
		}
	}

	//更新各个的公司名
	public function updateCompany($company_id){
		$company=app::get('syscart')->model('company')->getRow('company_id,company_name',array('company_id'=>$company_id));
		//进行更新的操作
		$params['company_name']=$company['company_name'];
		app::get('syscart')->model('lease_cart')->update($params,array('company_id'=>$company_id));
		app::get('syscart')->model('flow')->update($params,array('company_id'=>$company_id));
		return true;
		
	}

	public function updateOwner($owner_id){
		$owner=app::get('syscart')->model('owner')->getRow('owner_id,owner_name',array('owner_id'=>$owner_id));
		//进行更新的操作
		$params['owner_name']=$owner['owner_name'];
		app::get('syscart')->model('lease_cart')->update($params,array('owner_id'=>$owner_id));
		app::get('syscart')->model('flow')->update($params,array('owner_id'=>$owner_id));
		app::get('syscart')->model('stages')->update($params,array('owner_id'=>$owner_id));
		app::get('syscart')->model('deposit')->update($params,array('owner_id'=>$owner_id));
		return true;
		
	}


	//车主列表

	public function ownerlist(){
        $postSend = input::get();
		$params['shop_id']=$this->shopId;
		$params['page_no']=$postSend['page'];
		if(isset($postSend['company_id']) && $postSend['company_id']!=''){
			$params['company_id']=$postSend['company_id'];
		}
		if(isset($postSend['mobile']) && $postSend['mobile']!=''){
			$params['mobile']=$postSend['mobile'];
		}
		if(isset($postSend['owner_name']) && $postSend['owner_name']!=''){
			$params['owner_name|has']=$postSend['owner_name'];
		}
		if(isset($postSend['number']) && $postSend['number']!=''){
			$params['number']=$postSend['number'];
		}
		if(isset($postSend['company_name']) && $postSend['company_name']!=''){
			$params['company_name|has']=$postSend['company_name'];
		}
		$ownerModel = app::get('syscart')->model('owner');
        $count = $ownerModel->count($params);
		$pagedata['count']=$count;
        $page =  $params['page_no'] ? $params['page_no'] : 1;
        $limit = $this->limit;
        $pageTotal = ceil($count/$limit);
        $currentPage = ($pageTotal < $page) ? $pageTotal : $page; //防止传入错误页面，返回最后一页信息
        $offset = ($currentPage-1) * $limit;
        $postSend['page'] = time();
        $link = url::action('topshop_ctl_syscart_syscart@ownerlist',$postSend);
        $pagedata['pagers'] = $this->__pagers($pagedata['count'],$page,$limit,$link);
		$ownerlist=$ownerModel->getList('*',$params,$offset,$limit,'owner_id asc');
		$pagedata['ownerlist']=$ownerlist;
		//echo "<pre>";print_r($ownerlist);die();
		return $this->page('topshop/syscart/ownerlist.html', $pagedata); 
	}


    public function companyDel(){
    	$company_id = input::get('company_id');
		//print_r($company_id);die();
    	$companyModel = app::get('syscart')->model('company');
		//判断该公司下车主是否有租车,如果有的话就不能删除
		$owner_info=app::get('syscart')->model('owner')->getRow('owner_id',array('company_id'=>$company_id));
		
		$result=$this->ownerDel($owner_info['owner_id']);
		if($result=='succ'){
			$res = $companyModel->delete(array('company_id' => $company_id));
			if($res){
				$postSend['page'] = time();
				$url = url::action('topshop_ctl_syscart_syscart@companylist',$postSend);
				$msg = app::get('topshop')->_('删除成功');
				return $this->splash('success',$url,$msg,true);
			}else{
				return $this->splash('error','','删除失败!',true);
			}
		}else{
			return $this->splash('error','','删除失败,该车主下有已还款的信息!',true);
		}
    }


	//删除车主的
	public function deleteOwner(){
		$owner_id=input::get('owner_id');
		$owner=app::get('syscart')->model('owner')->getRow('*',array('owner_id'=>$owner_id));
		$result=$this->ownerDel($owner_id);
		if($result=='succ'){
			if($owner['company_id']){	
				app::get('syscart')->model('company')->delete(array('company_id'=>$owner['company_id']));
				return $this->splash('success','','删除成功!',true);
			}else{
				return $this->splash('success','','删除成功!',true);
			}
		}else{
			return $this->splash('error','','删除失败,该车主已经还款!',true);
		}
	}
	


	//根据owner_id删除车主相关信息
	public function ownerDel($owner_id){
		if(empty($owner_id)){
			return 'succ';
		}
		$stages=app::get('syscart')->model('stages');
		$params['payment_status']=1;
		$params['deductible_type']='';
		$params['owner_id']=$owner_id;
		$stages_info=$stages->getRow('stages_id',$params);
		if($stages_info){   //此时说明不能删除
			//print_r(222);die();
    		$result='error';
		}else{
			//print_r(111);die();
			$stages->delete(array('owner_id'=>$owner_id));   //删除期数表数据
			app::get('syscart')->model('lease_cart')->delete(array('owner_id'=>$owner_id));     //删除主表数据
			app::get('syscart')->model('owner')->delete(array('owner_id'=>$owner_id));   //删除车主表数据
			app::get('syscart')->model('deposit')->delete(array('owner_id'=>$owner_id));   //删除押金表数据
			app::get('syscart')->model('flow')->delete(array('owner_id'=>$owner_id));   //删除流水表数据
			$result='succ';
		}
		return $result;
	}


	

	//车辆管理
	public function cart(){
		
        $postSend = input::get();
        $params['shop_id']=$this->shopId;
        $params['page_no']=$postSend['page'];
        if(!empty($postSend['frame_code'])){
            $params['frame_code|has']=$postSend['frame_code'];
            $pagedata['keyword']['frame_code']=$postSend['frame_code'];
        }

        if(!empty($postSend['cart_number'])){
            $params['cart_number|has']=$postSend['cart_number'];
            $pagedata['keyword']['cart_number']=$postSend['cart_number'];
        }
        /*if(isset($postSend['company_id']) && $postSend['company_id']!=''){
            $params['company_id']=$postSend['company_id'];
        }
        if(isset($postSend['mobile']) && $postSend['mobile']!=''){
            $params['mobile']=$postSend['mobile'];
        }
        if(isset($postSend['owner_name']) && $postSend['owner_name']!=''){
            $params['owner_name|has']=$postSend['owner_name'];
        }
        if(isset($postSend['number']) && $postSend['number']!=''){
            $params['number']=$postSend['number'];
        }*/
        $cartModel = app::get('syscart')->model('cart');
        $count = $cartModel->count($params);
		$pagedata['count']=$count;
        $page =  $params['page_no'] ? $params['page_no'] : 1;
        $limit = $this->limit;
        $pageTotal = ceil($count/$limit);
        $currentPage = ($pageTotal < $page) ? $pageTotal : $page; //防止传入错误页面，返回最后一页信息
        $offset = ($currentPage-1) * $limit;
        $postSend['page'] = time();
        $link = url::action('topshop_ctl_syscart_syscart@cart',$postSend);
        $pagedata['pagers'] = $this->__pagers($pagedata['count'],$page,$limit,$link);
		$cartlist=$cartModel->getList('*',$params,$offset,$limit,'cart_id asc');
		$pagedata['cartlist']=$cartlist;
		//echo "<pre>";print_r($ownerlist);die();
		return $this->page('topshop/syscart/cartlist.html', $pagedata); 
	}
	
	//添加车辆信息
	//
	public function cartAdd(){
		$submit=input::get('submit');
		if($_POST && $submit=='true'){
			if($_POST['cart_id']){
				$insertArr['cart_id']=$_POST['cart_id'];
				//$lease=app::get('syscart')->model('lease_cart')->getRow('lease_id',array('cart_id'=>$_POST['cart_id']));
				//if($lease){
				//	return $this->splash('error','','该车辆已被租用,不能修改!',true);
				//}
			}else{
				$insertArr['created_time']=time();
				$insertArr['status']=2;                                                                  //租用状态,还没有被租用,这张表要跟租赁表关联
			}
			$insertArr['cart_name']=trim($_POST['cart_name']);					 //汽车品牌
			$insertArr['type']=trim($_POST['type']);										  //厂牌型号
			$insertArr['frame_code']=trim($_POST['frame_code']);				  //车辆识别码车架号
			$insertArr['cart_number']=trim($_POST['cart_number']);            //车牌号
			$insertArr['price']=trim($_POST['price']);										  //汽车总价
			$insertArr['color']=$_POST['color'];										  //汽车颜色
			$insertArr['shop_id']=$this->shopId;
			$insertArr['remarks']=$_POST['remarks'];
			$cartModel = app::get('syscart')->model('cart');
			if(!$_POST['cart_id'] && $_POST['cart_number']!=''){
				$info=$cartModel->getRow('cart_id',array('cart_number'=>$insertArr['cart_number']));
				if($info){
					return $this->splash('error','','保存失败,该车牌号已经被录入!',true);
				}
			}elseif($_POST['cart_id']){
				$info=$cartModel->getRow('cart_id',array('cart_number'=>$insertArr['cart_number']));
				if(!empty($info) && $info['cart_id']!=trim($_POST['cart_id'])){
					return $this->splash('error','','保存失败,该车牌号已经被录入!',true);
				}
			}
			$res=$cartModel->save($insertArr);
			if($res){
				if($_POST['cart_id']){
					$lease['cart_name']=$insertArr['cart_name'];
					$lease['cart_number']=$insertArr['cart_number'];
					app::get('syscart')->model('lease_cart')->update($lease,array('cart_id'=>$_POST['cart_id']));     //更新主表车辆信息
					$this->sellerlog("修改车辆信息,车牌号为:{$insertArr['cart_number']}");
				}else{
					$this->sellerlog("添加车辆信息,车牌号为:{$insertArr['cart_number']}");
				}
				$url = url::action('topshop_ctl_syscart_syscart@cart');
				$msg = app::get('topshop')->_('保存成功');
				return $this->splash('success',$url,$msg,true);
			}else{
				return $this->splash('error','','保存失败了!',true);
			}
		}
		$cart_id=input::get('cart_id');
		if($cart_id){
			$pagedata['cart']=app::get('syscart')->model('cart')->getRow('*',array('cart_id'=>$cart_id));
			$pagedata['cart_id']=$cart_id;
		}
		return $this->page('topshop/syscart/cartadd.html', $pagedata); 
	}

	//删除车辆的
	public function cartDel(){
		$cart_id=input::get('cart_id');
		$lease=app::get('syscart')->model('lease_cart')->getRow('lease_id',array('cart_id'=>$cart_id));
		if($lease){
			return $this->splash('error','','删除失败,该辆车已被租用!',true);
		}
		$res=app::get('syscart')->model('cart')->delete(array('cart_id'=>$cart_id));
		if($res){
			return $this->splash('success','','删除成功!',true);
		}else{
			return $this->splash('error','','删除失败!',true);
		}
		
	}


	//销售经理
	public function sale(){
        $postSend = input::get();
		$params['shop_id']=$this->shopId;
		$params['page_no']=$postSend['page'];
		if(isset($postSend['sale_manager']) && $postSend['sale_manager']!=''){
			$params['sale_manager|has']=$postSend['sale_manager'];		
		}
		if(isset($postSend['sale_mobile']) && $postSend['sale_mobile']!=''){
			$params['sale_mobile']=$postSend['sale_mobile'];		
		}
		$saleModel = app::get('syscart')->model('sale');
        $count = $saleModel->count($params);
		$pagedata['count']=$count;
        $page =  $params['page_no'] ? $params['page_no'] : 1;
        $limit = $this->limit;
        $pageTotal = ceil($count/$limit);
        $currentPage = ($pageTotal < $page) ? $pageTotal : $page; //防止传入错误页面，返回最后一页信息
        $offset = ($currentPage-1) * $limit;
        $postSend['page'] = time();
        $link = url::action('topshop_ctl_syscart_syscart@sale',$postSend);
        $pagedata['pagers'] = $this->__pagers($pagedata['count'],$page,$limit,$link);
		$salelist=$saleModel->getList('*',$params,$offset,$limit,$orderBy);
		$pagedata['salelist']=$salelist;
		return $this->page('topshop/syscart/salelist.html', $pagedata); 
	}

	//新增销售经理
	public function saleAdd(){
		if($_POST){
			if($_POST['sale_id']){
				$insertArr['sale_id']=$_POST['sale_id'];
			}else{
				$insertArr['created_time']=time();
			}
			$insertArr['sale_manager']=trim($_POST['sale_manager']);
			$insertArr['sale_mobile']=trim($_POST['sale_mobile']);
			$insertArr['remarks']=$_POST['remarks'];
			$insertArr['shop_id']=$this->shopId;
			$saleModel = app::get('syscart')->model('sale');
			$res=$saleModel->save($insertArr);
			if($res){
				if($_POST['sale_id']){
					$lease['sale_manager']=$insertArr['sale_manager'];
					app::get('syscart')->model('lease_cart')->update($lease,array('sale_id'=>$_POST['sale_id']));     //更新主表车辆信息
				}
				$url = url::action('topshop_ctl_syscart_syscart@sale');
				$msg = app::get('topshop')->_('保存成功');
				return $this->splash('success',$url,$msg,true);
			}else{
				return $this->splash('error','','保存失败了!',true);
			}
		}

		//if($company_id = input::get('company_id')){
		//	$companyModel = app::get('syscart')->model('company');
		//	$company = $companyModel->getRow('*',array('company_id' => $company_id));

		//	$pagedata['company_id'] = $company_id;
		//	$pagedata['company'] = $company;
		//}
		$sale_id=input::get('sale_id');
		if($sale_id){
			$pagedata['sale']=app::get('syscart')->model('sale')->getRow('*',array('sale_id'=>$sale_id));
	
			$pagedata['sale_id']=$sale_id;
		}

		return $this->page('topshop/syscart/saleadd.html', $pagedata); 
	}

	//删除销售经理
	public function saleDel(){
		$sale_id=input::get('sale_id');
		$lease=app::get('syscart')->model('lease_cart')->getRow('lease_id',array('sale_id'=>$sale_id));
		if($lease){
			return $this->splash('error','','删除失败,该销售经理已经销售车辆!',true);
		}
		$res=app::get('syscart')->model('sale')->delete(array('sale_id'=>$sale_id));
		if($res){
			return $this->splash('success','','删除成功!',true);
		}else{
			return $this->splash('error','','删除失败!',true);
		}
	}

	//交定金和押金的时候生成定金和押金的流水
	private function createFlow($params){
		//echo "<pre>";print_r($params);die();
		$insertSql="INSERT INTO syscart_flow (`shop_id`,`company_name`,`owner_name`,`created_time`,`mobile`,`owner_id`,`payment_date`,`payment_type`,`company_id`,`serial_number`,`flow_type`,`amount`,`remarks`) VALUES ";
		$company_name=$params['company_name'];			//公司名称
		$owner_name=$params['owner_name'];						//车主姓名
		$mobile=$params['mobile'];											//手机号
		$shop_id=$params['shop_id'];										//店铺id
		$payment_date=time();													//支付时间
		$owner_id=$params['owner_id'];								//车主id
		$created_time=time();													//创建时间
		$payment_type='后台录入';											//支付方式
		$company_id=$params['company_id'];						//公司id
		$serial_number='';															//流水号;后台是没有的
		//生成押金的流水
		if($params['lease_deposit']>0){
			$flow_type='1';   //1是押金
			$amount=$params['lease_deposit'];    //金额
			$remarks='后台添加押金';
			$insertSql.="('{$shop_id}','{$company_name}','{$owner_name}','{$created_time}','{$mobile}','{$owner_id}','{$payment_date}','{$payment_type}','{$company_id}','{$serial_number}','{$flow_type}','{$amount}','{$remarks}'),";
		}
		//生成定金的流水
		if($params['front_money']>0){
			$flow_type='2';   //2是定金
			$amount=$params['front_money'];    //定金		
			$remarks='后台添加定金';
			$insertSql.="('{$shop_id}','{$company_name}','{$owner_name}','{$created_time}','{$mobile}','{$owner_id}','{$payment_date}','{$payment_type}','{$company_id}','{$serial_number}','{$flow_type}','{$amount}','{$remarks}'),";
		}
		$insertSql=substr($insertSql,0,-1);
		return $insertSql;
	}
	//增加分页功能
	public function __pagers($count,$page,$limit,$link){
        if($count>0)
        {
            $total = ceil($count/$limit);
        }
        $pagers = array(
            'link'=>$link,
            'current'=>$page,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>time(),
        );
        return $pagers;
    }

    /**
     * 导出当月汽车租赁明细
     */
    public function exportToExcel(){

        $leaseData=$this->_generateLeaseData();
        $filename=date('汽车租赁信息-'.date('Y年m月d日',time()));
        $this->exportLeaseExcel($filename,$leaseData);
    }

    /**
     * 组装当月车辆租赁数据
     */
    private function _generateLeaseData(){
        $baseList=app::get('syscart')->model('lease_cart')->getList('*');
        $cellData=array();
        if(!empty($baseList) && is_array($baseList)){

            foreach($baseList as $key => $value){
                $ownerInfo=app::get('syscart')->model('owner')->getRow('*',array('owner_id'=>$value['owner_id']));
                $companyInfo=app::get('syscart')->model('company')->getRow('*',array('company_id'=>$value['company_id']));
                $cartInfo=app::get('syscart')->model('cart')->getRow('*',array('cart_id',array('cart_id'=>$value['cart_id'])));
                $transfer['number']=$key+1;

                $transfer['is_person']=$value['is_person']=1?'个人':'公司';
                $transfer['owner_name']=$value['owner_name'];
                if($value['company_id']){
                    //$transfer['owner_name']=$value['company_name'];
                    $transfer['id_code']=$ownerInfo['number'].'/'.$companyInfo['credit_code'];
                    $transfer['comment']=$companyInfo['comment'];

                }else{
                    //$transfer['owner_name']=$ownerInfo['owner_name'];
                    $transfer['id_code']=$ownerInfo['number'];
                }
                $transfer['mobile']=$value['mobile'];
                $transfer['type']=$cartInfo['type'];
                $transfer['cart_number']=$value['cart_number']?$value['cart_number']:$cartInfo['cart_number'];
                $transfer['frame_code']=$cartInfo['frame_code'];
                $transfer['lease_type']=$value['lease_type'];
                $transfer['lease_stages']=$value['lease_stages'];
                $transfer['lease_start_time']=date('Y-m-d',$value['lease_start_time']);
                $transfer['lease_end_time']=date('Y-m-d',$value['lease_end_time']);
                $transfer['lease_total']=$value['lease_total'];
                $transfer['lease_deposit']=$value['lease_deposit'];
                $transfer['front_money']=$value['front_money'];
                $transfer['lease_each']=$value['lease_each'];
                $transfer['lease_payed']=$value['lease_total']-$value['lease_balance'];
                $transfer['lease_balance']=$value['lease_balance'];
                $transfer['cur_stage_repayment_status']=$this->_getCurStageStatus($value['lease_id'],$value['repayment_date']);
                $transfer['sale_manager']=$value['sale_manager'];
                $transfer['lease_balance']=$value['lease_balance'];

                $cellData[$key]=$transfer;

                unset($transfer);
                unset($ownerInfo);
                unset($companyInfo);
                unset($cartInfo);
                unset($indexTransfer);
            }
        }
        return $cellData;
    }

    /**
     * 报表淡出-获取当月账单还款状态屏显文字
     * @param $lease_id
     * @param $repayment_date
     * @return string
     */
    public function _getCurStageStatus($lease_id,$repayment_date){
        $filter['lease_id']=$lease_id;

        $now=time();
        $firstday=strtotime(date('Y-m-01',$now));
        $lastday=strtotime(date('Y-m-30',$now));

        $filter['repay_time|between']=[$firstday,$lastday];
        $stageInfo=app::get('syscart')->model('stages')->getRow('*',$filter);
        if(!empty($stageInfo) && is_array($stageInfo)){
            if($stageInfo['payment_status']==1){
                return '已还款';
            }else{
                return '未还款';
            }
        }else{
            return '待确认';
        }

    }

    /**
     * 导出excel文件
     * @param $expTitle
     * @param $expTableData
     * @return string
     */
    public function exportLeaseExcel($expTitle,$expTableData){

        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $xlsTitle; //文件名称可根据自己情况设定
        $cellTitle=array('序号','个人/企业','客户名称','身份证号码/企业统一社会信用代码','联系方式','厂牌型号','车牌','车辆识别代号（车架号）','租用形式','租期','起始日期','结束日期','车款总额','押金','定金','月还款额','已还款额','未还款额','当期是否缴款','销售经理','备注');
        $objPHPExcel = new PHPExcel();
        $cellNum=count($cellTitle);//多少列
        $dataNum = count($expTableData);//多少行
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

        //表格边框样式
        $color='0x00000000';
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => array('argb' => $color),
                ),
            ),
        );

        $objPHPExcel->getActiveSheet()->getStyle('A1:'.'U'.($dataNum+2))->applyFromArray($styleArray);
        //表头合并
        $objPHPExcel->getActiveSheet()->mergeCells('B1:E1');
        $objPHPExcel->getActiveSheet()->mergeCells('F1:H1');
        $objPHPExcel->getActiveSheet()->mergeCells('I1:L1');
        $objPHPExcel->getActiveSheet()->mergeCells('M1:S1');
        //表头居中
        $objPHPExcel->getActiveSheet()->getStyle('A1:U1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A2:U2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A1:U1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('A2:U2')->getFont()->setBold(true);
        // 表头
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('B1', '客户信息')
            ->setCellValue('F1', '车辆信息')
            ->setCellValue('I1', '租用信息')
            ->setCellValue('M1', '还款信息');

        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $cellTitle[$i]);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $cellTitle[$i]);
        }

        for($i=0;$i<$dataNum;$i++){//多少行
            $objPHPExcel->getActiveSheet(0)->setCellValue('A'.($i+3), $expTableData[$i]['number']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('B'.($i+3), $expTableData[$i]['is_person']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+3), $expTableData[$i]['owner_name']);
            //$objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+3), $expTableData[$i]['id_code']);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('D'.($i+3),$expTableData[$i]['id_code'],PHPExcel_Cell_DataType::TYPE_STRING);
            //$objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+3), $expTableData[$i]['mobile']);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('E'.($i+3),$expTableData[$i]['mobile'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('F'.($i+3), $expTableData[$i]['type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('G'.($i+3), $expTableData[$i]['cart_number']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('H'.($i+3), $expTableData[$i]['frame_code']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('I'.($i+3), $expTableData[$i]['lease_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('J'.($i+3), $expTableData[$i]['lease_stages']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('K'.($i+3), $expTableData[$i]['lease_start_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('L'.($i+3), $expTableData[$i]['lease_end_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('M'.($i+3), $expTableData[$i]['lease_total']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('N'.($i+3), $expTableData[$i]['lease_deposit']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('O'.($i+3), $expTableData[$i]['front_money']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('P'.($i+3), $expTableData[$i]['lease_each']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('Q'.($i+3), $expTableData[$i]['lease_payed']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('R'.($i+3), $expTableData[$i]['lease_balance']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('S'.($i+3), $expTableData[$i]['cur_stage_repayment_status']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('T'.($i+3), $expTableData[$i]['sale_manager']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('U'.($i+3), $expTableData[$i]['comment']);
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

	
	/* action_name (par1, par2, par3)
	* 导出流水查询里面的所有数据
	* author by wanghaichao
	* date 2018/1/19
	*/
	public function exportFlowExcel(){
		$flowModel=app::get('syscart')->model('flow');
		$flowlist=$flowModel->getList('*');
		foreach($flowlist as $k=>&$flow){
			$flow['flow_type']=$flow['flow_type']==1?'押金':($flow['flow_type']==2?'定金':'分期款');
			$flow['company_name']=$flow['company_name']?$flow['company_name']:'个人';
			$flow['number']=$k+1;
			$flow['payment_date']=date("Y-m-d",$flow['payment_date']);
		}
		//echo "<pre>";print_r($flowlist);die();

        $expTitle=date('流水信息-'.date('Y年m月d日',time()));
		$xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $xlsTitle; //文件名称可根据自己情况设定
        $cellTitle=array('序号','客户名称','手机号','类型','金额','支付方式','支付日期','公司名称','流水账号','备注');
        $objPHPExcel = new PHPExcel();
        $cellNum=count($cellTitle);//多少列
        $dataNum = count($flowlist);//多少行
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

        //表格边框样式
        $color='0x00000000';
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => array('argb' => $color),
                ),
            ),
        );

        $objPHPExcel->getActiveSheet()->getStyle('A1:'.'U'.($dataNum+2))->applyFromArray($styleArray);
        //表头合并
        $objPHPExcel->getActiveSheet()->mergeCells('B1:E1');
        $objPHPExcel->getActiveSheet()->mergeCells('F1:H1');
        $objPHPExcel->getActiveSheet()->mergeCells('I1:L1');
        $objPHPExcel->getActiveSheet()->mergeCells('M1:S1');
        //表头居中
        $objPHPExcel->getActiveSheet()->getStyle('A1:U1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A2:U2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A1:U1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('A2:U2')->getFont()->setBold(true);
        // 表头
        //$objPHPExcel->setActiveSheetIndex(0)
        //    ->setCellValue('B1', '客户信息')
        //    ->setCellValue('F1', '车辆信息')
        //   ->setCellValue('I1', '租用信息')
        //    ->setCellValue('M1', '还款信息');

        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $cellTitle[$i]);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $cellTitle[$i]);
        }

        for($i=0;$i<$dataNum;$i++){//多少行
            $objPHPExcel->getActiveSheet(0)->setCellValue('A'.($i+3), $flowlist[$i]['number']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('B'.($i+3), $flowlist[$i]['owner_name']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+3), $flowlist[$i]['mobile'],PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+3), $flowlist[$i]['flow_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+3), $flowlist[$i]['amount']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('F'.($i+3), $flowlist[$i]['payment_type']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('G'.($i+3), $flowlist[$i]['payment_date']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('H'.($i+3), $flowlist[$i]['company_name']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('I'.($i+3), $flowlist[$i]['serial_number']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('J'.($i+3), $flowlist[$i]['remarks']);
        //    $objPHPExcel->getActiveSheet(0)->setCellValue('M'.($i+3), $flowlist[$i]['lease_total']);
        //    $objPHPExcel->getActiveSheet(0)->setCellValue('N'.($i+3), $flowlist[$i]['lease_deposit']);
        //    $objPHPExcel->getActiveSheet(0)->setCellValue('O'.($i+3), $flowlist[$i]['front_money']);
        //    $objPHPExcel->getActiveSheet(0)->setCellValue('P'.($i+3), $flowlist[$i]['lease_each']);
        //    $objPHPExcel->getActiveSheet(0)->setCellValue('Q'.($i+3), $flowlist[$i]['lease_payed']);
        //    $objPHPExcel->getActiveSheet(0)->setCellValue('R'.($i+3), $flowlist[$i]['lease_balance']);
        //    $objPHPExcel->getActiveSheet(0)->setCellValue('S'.($i+3), $flowlist[$i]['cur_stage_repayment_status']);
        //    $objPHPExcel->getActiveSheet(0)->setCellValue('T'.($i+3), $flowlist[$i]['sale_manager']);
        //    $objPHPExcel->getActiveSheet(0)->setCellValue('U'.($i+3), $flowlist[$i]['comment']);
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
	


	}



}