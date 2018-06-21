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
            'shop_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'店铺有id'],
            'supplier_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'供应商id'],
            'agent_shop_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'线下店id'],
            'voucher_code' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'赠品的核销码'],
            'write_type' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'核销类型'],
        );
        return $data;
    }
	//核销的逻辑
	public function write($params){
		unset($params['oauth']);
		$filter = [];
		$filter['deleted'] = 0;
		$filter['gift_code'] = $params['voucher_code'];
		
// 		$params['deleted']=0;
// 		$params['gift_code']=$params['voucher_code'];
// 		unset($params['voucher_code']);
		$gainObj=app::get('sysactivityvote')->model('gift_gain');
		$info=$gainObj->getRow('status,shop_id,supplier_id',$filter);
		if(empty($info)){
			return 'none';   //没有卡券信息
		}
		if($info['status']==1){
			return 'have_write';   //已经被核销
		}
		$update = [];
		$update['status']=1;   //说明已经被核销
		$update['used_time']=time();   //核销的时间
		switch ($params['write_type'])
		{
		    case 'SHOP':
		        if($info['shop_id'] != $params['shop_id'])
		        {
		            throw new \LogicException('该劵不能由其它店铺核销！');
		        }
		        $update['write_shop_id'] = $params['shop_id'];
		        $update['write_type'] = 'SHOP';
		        break;
		    case 'SUPPLIER':
		        if($info['supplier_id'] != $params['supplier_id'])
		        {
		            throw new \LogicException('该劵不能由其它供应商核销！');
		        }
		        $update['write_supplier_id'] = $params['supplier_id'];
		        $update['write_type'] = 'SUPPLIER';
		        break;
	        case 'AGENTSHOP':
	            $update['write_agent_shop_id'] = $params['agent_shop_id'];
	            $update['write_type'] = 'AGENTSHOP';
	            break;
            default:
                break;
		}
		$res=$gainObj->update($update,$filter);
		if($res){
			return 'write_success';
		}else{
			return 'write_fail';
		}
	}
}