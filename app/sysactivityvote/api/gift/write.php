 <?php
/* 
* 核销赠品逻辑
* author by wanghaichao
* date 2017/10/23
*/
class sysactivityvote_api_gift_write{
    public $apiDescription = "获取赠品列表";
    public function getParams()
    {
        $data['params'] = array(
            'supplier_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'供应商id'],
            'voucher_code' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'赠品的核销码'],
        );
        return $data;
    }
	//核销的逻辑
	public function write($params){
		unset($params['oauth']);
		$params['deleted']=0;
		$params['gift_code']=$params['voucher_code'];
		unset($params['voucher_code']);
		$gainObj=app::get('sysactivityvote')->model('gift_gain');
		$info=$gainObj->getRow('status',$params);
		if(empty($info)){
			return 'none';   //没有卡券信息
		}
		if($info['status']==1){
			return 'have_write';   //已经被核销
		}
		$update['status']=1;   //说明已经被核销
		$update['used_time']=time();   //核销的时间
		$res=$gainObj->update($update,$params);
		if($res){
			return 'write_success';
		}else{
			return 'write_fail';
		}
	}
}