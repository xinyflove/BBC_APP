 <?php
/* 
* 用户获取赠品的逻辑
* author by wanghaichao
* date 2017/10/18
*/
class sysactivityvote_api_gift_giftGain{
    public $apiDescription = "获取赠品列表";
    public function getParams()
    {
        $data['params'] = array(           
            'gift_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'作品id'],
			'user_id'=>['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'当前时间'],
			'open_id'=>['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'', 'description'=>'用户的open_id'],
        );
        return $data;
	}
	
	/* action_name (par1, par2, par3)
	* 用户获取赠品逻辑
	* author by wanghaichao
	* date 2017/10/19
	*/
	public function getGift($params){
		$filter=array('gift_id'=>$params['gift_id'],'deleted'=>'0');
		$giftGainObj=app::get('sysactivityvote')->model('gift_gain');
		$gift=app::get('sysactivityvote')->model('gift')->getRow('shop_id,gain_total,gift_name,valid_start_time,valid_end_time,supplier_id,active_id',$filter);
		$supplier_id=$gift['supplier_id'];
		//判断今天是否领过赠品

		$time=time();
		$today=date('Y-m-d 00:00:00',$time);
		$today_start=strtotime($today);   //今天零点的时间戳
		$today_end=$today_start+24*60*60;   //明天零点的时间戳
		$gift_filter['open_id']=$params['open_id'];
		$gift_filter['create_time|than']=$today_start;
		$gift_filter['create_time|lthan']=$today_end;
		$gift_filter['active_id']=$gift['active_id'];
		$gift_gain=app::get('sysactivityvote')->model('gift_gain')->getRow('gift_gain_id',$gift_filter);  //一天只能获得一次奖品
		if($gift_gain){
			return false;
		}
		//根据供应商id生成供应商码
		$insert['gift_code']=$this->code($supplier_id);
		$insert['shop_id']=$gift['shop_id'];
		$insert['active_id']=$gift['active_id'];
		$insert['supplier_id']=$supplier_id;
		$insert['start_time']=$gift['valid_start_time'];
		$insert['end_time']=$gift['valid_end_time'];
		$insert['qr_code']=$this->__qrCode($insert['gift_code']);
		$insert['status']='0';    //等待核销的状态
		$insert['create_time']=time();
		$insert['user_id']=$params['user_id'];
		$insert['open_id']=$params['open_id'];
		$insert['gift_id']=$params['gift_id'];
		$result=$giftGainObj->insert($insert);
		if($result){    //领取成功后更新礼品表,礼品表已领数量减1
			$gain_total=($gift['gain_total']+1);
			$update=app::get('sysactivityvote')->model('gift')->update(array('gain_total'=>$gain_total),array('gift_id'=>$params['gift_id']));
			if($update){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	//根据商品id生成卡券码
	public function code($supplier_id){
		//取出供应商的码
		$supplier=app::get('sysshop')->model('supplier')->getRow('code',array('supplier_id'=>$supplier_id));
		$code=$supplier['code'];
		// 
		$time=time();
		//$str=substr($time,-4);
		$str=rand(1000,9999);
		//随机数
		$rand=rand(10,99);
		$return=array();
		$return['voucher_code']='ZP'.$code.$str.$rand;
		return $return['voucher_code'];
	}
	
    private function __qrCode($voucher_code)
    {
       
		//echo "<pre>";print_r($url);die();
	   $code_url="../public/images/shareimg/{$voucher_code}_code.png"; 
	   if(is_file($code_url)){
			return "/images/shareimg/{$voucher_code}_code.png";
	   }
	   
       $result= getQrcodeUri($voucher_code, 80, 10);

       // $qrCode = new QrCode();
       //$result=$qrCode
       //     ->setText($voucher_code)
      //      ->setSize(80)
        //    ->setPadding(0)
        //    ->setErrorCorrection(1)
        //    ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
        //    ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
       //     ->setLabelFontSize(16)
        //    ->getDataUri('png');   //生成数据流

	   $code_url="../public/images/voucher/{$voucher_code}_code.png"; 
		copy($result,$code_url);   //上传至服务器
		return "/images/voucher/{$voucher_code}_code.png";
    }

}