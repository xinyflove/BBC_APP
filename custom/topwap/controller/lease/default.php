<?php
/**
 *  租车前端控制器
 *
 *
 **/

class topwap_ctl_lease_default extends topwap_controller{

    public $owner_id;//当前用户手机号
    public $ownerInfo;//当前用户信息

    public function __construct()
    {
		
		kernel::single('base_session')->start();
        kernel::single("base_session")->set_sess_expires(10080);
        if($_SESSION['owner_id']!=''){
            $this->owner_id=$_SESSION['owner_id'];
            $this->ownerInfo=app::get('syscart')->model('owner')->getRow('company_name,mobile,owner_name',array('owner_id'=>$this->owner_id));
        }else{

			//$action_name="topwap_ctl_lease_login@login";
			$url=url::action("topwap_ctl_lease_login@login");
			echo "<script>window.location.href='{$url}'</script>";
            //重定向查询页面
        }
    }

    /**
     * 还款首页
     * @return mixed
     */
    public function index(){
        $pagedata=$this->getLeaseInfo();
        $pagedata['ownerInfo']=app::get('syscart')->model('owner')->getRow('company_name,mobile,owner_name',array('mobile'=>$this->mobile));
        return view::make('topwap/lease/index.html', $pagedata);
    }

    /**
     * 还款信息详情列表页
     * @return mixed
     */
    public function paymentsdetails(){
        $owner_id=$this->owner_id;
        $params['owner_id']=$owner_id;
        $params['status']=3;
        $leaseList=app::get('syscart')->rpcCall('lease.list',$params);
        $pagedata['count']=$leaseList['count'];
        $pagedata['total']=$leaseList['total'];
        unset($leaseList['count']);
        unset($leaseList['total']);
        $pagedata['leaseList']=$leaseList;
        $pagedata['ownerInfo']=app::get('syscart')->model('owner')->getRow('company_name,mobile,owner_name',array('owner_id'=>$this->owner_id));
        return view::make('topwap/lease/paymentsdetails.html', $pagedata);
    }


    /**
     * 租赁详情 lease-detail.html
     * @return mixed
     */
    public function details(){
        $lease_id=input::get('lease_id');
        $leaseModel=app::get('syscart')->model('lease_cart');
        $leaseRow=$leaseModel->getRow('*',array('lease_id'=>$lease_id));
        $pagedata['leaseRow']=$leaseRow;
        return view::make('topwap/lease/details.html', $pagedata);
    }

    /**
     * 还款计划列表
     * @return mixed
     */
    public function repaymentplan(){
        $params['lease_id']=input::get('lease_id');
       // $params['mobile']=$this->mobile;
        $params['orderBy']='stages_id asc';
        $pagedata['stages']=$this->getRepayPlan($params);
        return view::make('topwap/lease/repaymentplan.html', $pagedata);
    }


    /**
     * 缴费页面
     * @return mixed
     */
    public function paymentscurrent(){
		//redis::scene('topwap')->hset('1234', '234', '设置存储');
		//$stages_id='1443';				//获取期数的id,多个用-隔开
		//$transaction_id='1237832483287432832478';	//微信支付返回的订单id
		//$payment_type='微信';
		//更新流水表
		//$flowparams['stages_id']=$stages_id;
	//$flowparams['serial_number']=$transaction_id;
	//	$flowparams['repayment_amount']=app::get('syscart')->rpcCall('get.money',array('stages_id'=>$stages_id));
		//$flowparams['payment_type']='微信';
	
		//$res=app::get('syscart')->rpcCall('update.flow',$flowparams);   ///接口调通

		//$stagesparams['stages_id']=$stages_id;
		//$stagesparams['serial_number']=$transaction_id;
		//$stagesparams['lease_fines']=0;
		//$stagesparams['payment']=$payment_type;
		//$stagesparams['status']=2;
		
		//app::get('syscart')->rpcCall('update.stages',$stagesparams);
		
        $pagedata=$this->getLeaseInfo();
        $pagedata['ownerInfo']=app::get('syscart')->model('owner')->getRow('company_name,mobile,owner_name',array('owner_id'=>$this->owner_id));
        return view::make('topwap/lease/paymentscurrent.html', $pagedata);
    }

    /**
     * 充值记录列表
     * @return mixed
     */
    public function rechargelist(){
        $pagedata['test']=11;
        return view::make('topwap/lease/rechargelist.html', $pagedata);
    }

    /**
     * 充值页面
     * @return mixed
     */
    public function rechargepage(){
        $stagesId=input::get('stages_id');
        $stagesIdGp=explode('-',$stagesId);

        $stagesModel=app::get('syscart' )->model('stages');
        $params['mobile']=$this->mobile;
        $params['stages_id|in']=$stagesIdGp;
        $stagesList=$stagesModel->getList('owner_id,due_amount,deductible_amount',$params);
        $payment_total=0;
        foreach($stagesList as $stage){
            $payment_total+=($stage['due_amount']-$stage['deductible_amount']);
        }
        $pagedata['payment_total']=$payment_total;
        $pagedata['ownerInfo']=app::get('syscart')->model('owner')->getRow('company_name,mobile,owner_name',array('mobile'=>$this->mobile));
        $pagedata['stages_id']=$stagesId;
        return view::make('topwap/lease/rechargepage.html',$pagedata);
    }

    /**
     * 获取租赁账单列表和各个租赁的当月账单信息
     * @return mixed
     */
    private function getLeaseInfo(){
        $owner_id=$this->owner_id;
        $params['owner_id']=$owner_id;
        $params['status']=3;
        $leaseList=app::get('syscart')->rpcCall('lease.list',$params);
        $pagedata['count']=$leaseList['count'];
        $pagedata['total']=$leaseList['total'];
        //弃用借口数据，重新从还款期数计算
        unset($leaseList['count']);
        unset($leaseList['total']);
        //获取每辆车的当月账单
        $planLeaseBanlance=0;
		$overdue_fine=0;   //滞纳金
		$payment_total=0;
        foreach($leaseList as $key => $lease){
            $leaseList[$key]['cur_stage']=$this->getCurStageByLeaseId($lease['lease_id']);
			$overdue_fine+=$leaseList[$key]['cur_stage']['fines'];      //计算滞纳金的总
			$payment_total+=$leaseList[$key]['cur_stage']['cur_repayment_total'];
            $planLeaseBanlance+=$lease['lease_balance'];

            //TODO::确认当前显示的剩余金额是否需要直接去掉抵扣的押金总额和定金总额
            /*if($leaseList[$key]['status']==1 && empty($leaseList[$key]['cur_stage'])){
                $planLeaseBanlance+=$lease['lease_total'];
            }else{
                $planLeaseBanlance+=$lease['lease_balance'];
                $stageLeftTotal=$leaseList[$key]['lease_each']*($leaseList[$key]['lease_stages']-$leaseList[$key]['repaid_stages']);
                if($leaseList[$key]['cur_stage']['payment_status']==1){
                    $stageLeftTotal=$stageLeftTotal;
                }elseif($leaseList[$key]['cur_stage']['payment_status']==2 || $leaseList[$key]['cur_stage']['payment_status']==3 ){
                    $stageLeftTotal=$stageLeftTotal-$leaseList[$key]['cur_stage']['deductible_amount'];
                }
                $leaseList[$key]['plan_lease_banlance']=$stageLeftTotal;
                $planLeaseBanlance+=$stageLeftTotal;
            }*/
        }
		//$payment_total=$payment_total+$overdue_fine;     //总的还款额=还款额+滞纳金
        $pagedata['total']['plan_lease_banlance']=$planLeaseBanlance;
        $pagedata['leaseList']=$leaseList;
        $pagedata['ownerInfo']=app::get('syscart')->model('owner')->getRow('company_name,mobile,owner_name',array('owner_id'=>$this->owner_id));
        $pagedata['payment']=$this->getCurStagesTotal();
		$pagedata['payment']['overdue_fine']=$overdue_fine;
		$pagedata['payment']['payment_total']=$payment_total;
        return $pagedata;
    }

    /**
     * 获取当期各项金额合计
     * @return mixed
     */
    private function getCurStagesTotal(){
        $stagesModel=app::get('syscart' )->model('stages');
        $params['owner_id']=$this->owner_id;    //这个人的手机号
        $params['payment_status|in']=[2,3];
        $time=time();
        $firstday=strtotime(date('Y-m-01',$time));
        $lastday=strtotime(date('Y-m-30',$time));
        $params['repay_time|between']=[$firstday,$lastday];
        $stageslist=$stagesModel->getList('stages_id,due_amount,deductible_amount,remarks,lease_fines',$params);
        $due_total='';//应该还款的总额
        $deductible_total='';//抵扣的总额
        $overdue_fine=0;//滞纳金总额
        foreach($stageslist as $k=>$v){
            $due_total+=$v['due_amount'];								//计算应还款总额
            $deductible_total+=$v['deductible_amount'];//计算本期的抵扣总额
            //$overdue_fine+=$v['lease_fines'];
        }
        $payment_total=$due_total-$deductible_total;         //计算本期还款总额
        //计算本期应该还款的总额
        //$payment['overdue_fine']=$overdue_fine;
        $payment['deductible_total']=$deductible_total;
        //$payment['payment_total']=$payment_total;
        return $payment;
    }

    /**
     * 获取当前汽车租赁的账单信息
     * @param $lease_id
     * @return mixed
     */
    private function getPaymentByLeaseId($lease_id){
        $stagesModel=app::get('syscart')->model('stages');
        $params['lease_id']=$lease_id;
        $params['owner_id']=$this->owner_id;    //车主id
        $params['payment_status|in']=[1,3];
        $time=time();
        $firstday=strtotime(date('Y-m-01',$time));
        $lastday=strtotime(date('Y-m-30',$time));
        $params['repay_time|between']=[$firstday,$lastday];
        $cur_stage=$stagesModel->getRow('due_amount,deductible_amount,remarks,lease_fines',$params);
        $due_total='';                                   //应该还款的总额
        $deductible_total='';
        $overdue_fine=0;						//抵扣的总额
        $due_total+=$cur_stage['due_amount'];									//计算应还款总额
        $deductible_total+=$cur_stage['deductible_amount'];		//计算本期的抵扣总额
        $overdue_fine+=$cur_stage['lease_fines'];
        $payment_total=$due_total-$deductible_total;         //计算本期还款总额
        //计算本期应该还款的总额
        $payment['overdue_fine']=$overdue_fine;
        $payment['payment_total']=$payment_total;

        return $payment;
    }

    /**
     * 查询还款期数信息
     * @param $params
     * @return mixed
     */
    private function getRepayPlan($params){
        $stages=app::get('syscart')->model('stages')->getList('*',$params);
        return $stages;
    }

    /**
     * 获取当前租赁车辆的当月账单信息
     * @param $lease_id
     * @return mixed
     */
    private function getCurStageByLeaseId($lease_id){
        $stagesModel=app::get('syscart')->model('stages');
        $params['owner_id']=$this->owner_id;    //这个人的手机号
        $params['lease_id']=$lease_id;
        $time=time();
        $firstday=strtotime(date('Y-m-01',$time));
        $lastday=strtotime(date('Y-m-30',$time));
        $params['repay_time|between']=[$firstday,$lastday];
		//$params['payment_status|noequal']=1;
        $curStage=$stagesModel->getRow('stages_id,due_amount,deductible_amount,remarks,lease_fines,payment_status,status',$params);
		$fines=app::get('syscart')->rpcCall('update.fines',array('stages_id'=>$curStage['stages_id']));
        if(!empty($curStage)){
            $due_total='';//应该还款的总额
            //计算本期还款总额
			if($curStage['payment_status']==1){
				$due_total=0;
			}else{
				$due_total=$curStage['due_amount']-$curStage['deductible_amount']+$fines;
			}
            $curStage['cur_repayment_total']=$due_total;
        }
		$curStage['fines']=$fines;    //计算出滞纳金
        return $curStage;
    }

}