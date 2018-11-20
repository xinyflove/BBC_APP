<?php
/* 
* 设置会员登录签到时积分
* author by wanghaichao
* date 2018/8/24
*/
class topshop_ctl_point_sign extends topshop_controller
{
	public function index(){
		$params['shop_id']=$this->shopId;
		$pagedata=app::get('topshop')->rpcCall('shop.point.setting.get',$params);
        return $this->page('topshop/point/sign.html', $pagedata);
	}

	/* action_name (par1, par2, par3)
	* 保存积分规则
	* author by wanghaichao
	* date 2018/8/27
	*/
	public function save(){
		$data=input::get();
		$objMemberPoint=app::get('sysshop')->model('point_setting');
		$res=$objMemberPoint->getRow('id',array('shop_id'=>$this->shopId));
		if($data['id'] || $res){
			$id=$res['id']?$res['id']:$data['id'];
			unset($data['id']);
			$insert['params']=serialize($data);
			$insert['shop_id']=$this->shopId;
			$insert['modified_time']=time();
			$insert['id']=$id;
		}else{
			$insert['params']=serialize($data);
			$insert['shop_id']=$this->shopId;
			$insert['create_time']=time();
		} 

		try{
            $result = $objMemberPoint->save($insert);
            return $this->splash('success',null,'保存成功');
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg);
        }
	}
	

}
	

?>