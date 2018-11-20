<?php
/**
 * User: tvplaza team
 * Date: 2018/10/18
 * Desc:
 */
class syslmgw_emic_createBill extends syslmgw_emic_controller {

	private $time_s;//今天开始时间
	private $time_e;//明天开始时间

    /**
     * 应用话单下载
     */
	public function downloadBills()
    {
		$this->time_s = date('Ymd', strtotime('now')) . '000000';//今天开始时间
		$this->time_e = date('Ymd', strtotime('+1 day')) . '000000';//明天开始时间

        $maxNumber = 500;//每次获取最大记录数量
		//获取 小于当前时间&&最大的billId
		$bills_res = app::get('syslmgw')->model('bills')->getRow('billId', array('createTime|lthan'=>$this->time_s), 'billId DESC');
        $lastMaxId = $bills_res['billId'];

		$ope = 'billList';
		$url = $this->_make_url('Applications', $ope, 'Accounts');

		if($lastMaxId)
        {
			$api_params = array('appId'=>$this->_app_id,'maxNumber'=>$maxNumber,'lastMaxId'=>$lastMaxId);
		}
        else
        {
            //api开通时间为2018-10-10
            $api_params = array('appId'=>$this->_app_id,'maxNumber'=>$maxNumber,'startTime' => '20181010000000');
		}

        $api_params = array($ope => $api_params);
		$api_params_json = json_encode($api_params);
        $call_time = strtotime('now');
		list($return_code, $return_content) = $this->http_post_data($url, $api_params_json, true);
        $end_time = strtotime('now');
        $run_time = $end_time - $call_time;

		if($return_code != 200)
		{
            $data = array(
                'api_url' => $url,
                'call_time' => $call_time,
                'run_time' => $run_time,
                'resp_code' => $return_code,
                'params' => $api_params_json,
            );
            $this->_write_log($data);
			//throw new \LogicException("调用易米接口失败，错误代码：{$return_code}");
		}

		$return_content = json_decode($return_content, true);
		$resp = $return_content['resp'];
		if($resp['respCode'])
		{
            $data = array(
                'api_url' => $url,
                'call_time' => $call_time,
                'run_time' => $run_time,
                'resp_code' => $resp['respCode'],
                'params' => $api_params_json,
            );
            $this->_write_log($data);
			//throw new \LogicException("请求易米接口错误，错误代码：{$resp['respCode']}");
		}
		else
		{
            if($resp[$ope]['number'])
            {
				$this->_del_today_bill();//先删除今天的数据
                $this->__insertBill($resp[$ope]['records']);
                $data = array(
                    'api_url' => $url,
                    'call_time' => $call_time,
                    'run_time' => $run_time,
                    'resp_code' => $resp['respCode'],
                    'params' => $api_params_json,
                );
                $this->_write_log($data);
            }
		}
	}

	/* action_name (par1, par2, par3)
	* 插入数据表方法
	* author by wanghaichao
	* date 2018/10/18
	*/
	private function __insertBill($data)
    {
		if($data)
        {
			$insertSql="INSERT INTO syslmgw_bills (`billId`,`type`,`subAccountSid`,`switchNumber`,`callId`,`caller`,`called`,`userData`,`createTime`,`establishTime`,`hangupTime`,`status`,`duration`,`subDetails`,`subNumber`,`useNumber`,`adtel`,`create_time`) VALUES ";

			foreach($data as $k=>$v)
            {
				$SubDetails = empty($v['SubDetails']) ? '' : serialize($v['SubDetails']);
				$subAccountSid = empty($v['subAccountSid']) ? '' : $v['subAccountSid'];
				$userData = empty($v['userData']) ? '' : serialize($v['userData']);
				$subNumber = empty($v['subNumber']) ? '' : $v['subNumber'];
				$useNumber = empty($v['useNumber']) ? '' : $v['useNumber'];
				$adtel = empty($v['adtel']) ? '' : $v['adtel'];

				$create_time=strtotime($v['createTime']);

				$insertSql.="('{$v['billId']}','{$v['type']}','{$subAccountSid}','{$v['switchNumber']}','{$v['callId']}','{$v['caller']}','{$v['called']}','{$userData}','{$v['createTime']}','{$v['establishTime']}','{$v['hangupTime']}','{$v['status']}','{$v['duration']}','{$SubDetails}','{$subNumber}','{$useNumber}','{$adtel}','{$create_time}'),";
			}

			$insertSql=substr($insertSql,0,-1);
			$res=app::get('base')->database()->executeUpdate($insertSql);
			if($res)
            {
				return true;
			}
		}
	}

	/**
	 * 删除当天的通话数据
	 */
	protected function _del_today_bill()
	{
		$params = array(
			'createTime|bthan' => $this->time_s,//大于等于今天开始时间
			'createTime|lthan' => $this->time_e,//小于明天开始时间
		);

		$billsMdl = app::get('syslmgw')->model('bills');
		$billsMdl->delete($params);
	}
}