<?php
/**
 * Tvplaza
 *  当月所有期数,租赁一步
 * @user  小超
 * @email  1013631519@qq.com
 */

class topshop_ctl_syscart_lease extends topshop_controller
{
	public $limit=10;
	
	//把当月所有人的账单明细查询出来
	
	public function index(){
		//获得查询条件
        $postSend = input::get();
		$params['page_no']=$postSend['page'];
		$stagesModel = app::get('syscart')->model('stages');
	
		if(isset($postSend['status']) && !empty($postSend['status'])){
			$params['status']=$postSend['status'];
		}
		//查询当月
		$time=time();
		$firstday=strtotime(date('Y-m-01',$time));
		$lastday=strtotime(date('Y-m-30',$time));
		$params['repay_time|between']=[$firstday,$lastday];

        $count = $stagesModel->count($params);
		$pagedata['count']=$count;
        $page =  $params['page_no'] ? $params['page_no'] : 1;
        $limit = $this->limit;
        $pageTotal = ceil($count/$limit);
        $currentPage = ($pageTotal < $page) ? $pageTotal : $page; //防止传入错误页面，返回最后一页信息
        $offset = ($currentPage-1) * $limit;
        $postSend['page'] = time();
        $link = url::action('topshop_ctl_syscart_lease@index',$postSend);
        $pagedata['pagers'] = $this->__pagers($pagedata['count'],$page,$limit,$link);
		$stagesList=$stagesModel->getList('*',$params,$offset,$limit,$orderBy);
		//取出押金和定金
		$pagedata['lease']=app::get('syscart')->model('lease_cart')->getRow('front_money,lease_deposit',array('lease_id'=>$postSend['postSend']));
		//取出总和和已经还款金额
		$pagedata['total']=$stagesModel->getRow('SUM(`due_amount`) as due_total,SUM(`repayment_amount`) as repayment_total',$params);
		$pagedata['surplus_total']=$pagedata['total']['due_total']-$pagedata['total']['repayment_total'];   //剩余未还款金额
		$pagedata['stagesList']=$stagesList;
		$pagedata['show']=true;
		return $this->page('topshop/syscart/stageslist.html', $pagedata);
		
	}

	//快捷租车
	public function leaseCart(){
		$pagedata['ownlist']=app::get('syscart')->model('owner')->getList('*',array('shop_id'=>$this->shopId));

		return $this->page('topshop/syscart/lease/cart.html',$pagedata);
	}

	//租赁设置
	public function setting(){
		$pagedata['setting']=app::get('syscart')->model('setting')->getRow('*',array('shop_id'=>$this->shopId));
	
		return $this->page('topshop/syscart/lease/setting.html',$pagedata);
	}

	//保存设置
	public function savesetting(){
		$params=input::get();
		$setting=app::get('syscart')->model('setting');
		if($params['setting_id']==''){
			unset($params['setting_id']);
			$params['shop_id']=$this->shopId;
			$params['created_time']=time();
		}
		if($params['setting_id']){
			$setting_id=$params['setting_id'];
			unset($params['setting_id']);
			$res=$setting->update($params,array('setting_id'=>$setting_id));
		}else{
			$res=$setting->save($params);
		}
		if($res){
			$msg = app::get('topshop')->_('保存成功');
			return $this->splash('success',$url,$msg,true);
		}else{
			$msg = app::get('topshop')->_('保存失败');
			return $this->splash('error',$url,$msg,true);
		}
	}

	//换租操作

	public function forrent(){
		$lease_id=input::get('lease_id');
		$lease_cartinfo=app::get('syscart')->model('lease_cart')->getRow('cart_id,cart_number',array('lease_id'=>$lease_id));
		$pagedata['cart_info']=$lease_cartinfo;

		return $this->page('topshop/syscart/lease/forrent.html',$pagedata);
	}
	
	//换租处理逻辑
	public function saveRent(){
		$params=input::get();
		if(empty($params['cart_id'])){
			return $this->splash('error','','请选择车辆!',true);
		}
		$new_cart_id=$params['cart_id'];
		$old_cart_id=$params['old_cart_id'];
		$cartModel=app::get('syscart')->model('cart');     //cart模型
		$leaseModel=app::get('syscart')->model('lease_cart'); //主表模型
		//主表数据
		$lease=$leaseModel->getRow('lease_id,remarks,owner_id',array('cart_id'=>$old_cart_id));   //根据旧的cart_id去出原来的数据
		$lease_id=$lease['lease_id'];

		$new_cart_info=$cartModel->getRow('*',array('cart_id'=>$new_cart_id));

		$old_cart_info=$cartModel->getRow('*',array('cart_id'=>$old_cart_id));

		$old_params['status']=3;              //说明被弃用了
		$old_params['remarks']=$params['remarks'].",该车已被这牌号为:".$new_cart_info['cart_number'].",车架号为:".$new_cart_info['frame_code']."顶用!";
		
		$new_params['status']=1;
		
		$lease_params['cart_id']=$new_cart_id;
		$lease_params['cart_name']=$new_cart_info['cart_name'];
		$lease_params['cart_number']=$new_cart_info['cart_number'];
		$lease_params['remarks']=$params['remarks']."已换车,原车车牌号为:".$old_cart_info['cart_number'].",原车车架号为:".$old_cart_info['frame_code'].$lease['remarks'];
		$db = app::get('syscart')->database();
		$db->beginTransaction();    //开启事物
		try
		{ 
			$leaseModel->update($lease_params,array('lease_id'=>$lease_id));   //更新主表操作
			$cartModel->update($old_params,array('cart_id'=>$old_cart_id));
			$cartModel->update($new_params,array('cart_id'=>$new_cart_id));
			$db->commit();
			$url = url::action('topshop_ctl_syscart_syscart@leaselist');
			return $this->splash('success',$url,'换车成功!',true);
		}catch(Exception $e)
		{
			$db->rollback();
			return $this->splash('error','','换车失败了!',true);
		}
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
	
}

	




?>