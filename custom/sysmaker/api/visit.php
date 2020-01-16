<?php
/* 
* 插入主持人访问表中
* author by wanghaichao
* date 2018/6/22
*/
class sysmaker_api_visit{

    public $apiDescription = "插入主持人访问表中";

    public function getParams()
    {
        $return['params'] = array(
			'seller_id'=>['type'=>'int','valid'=>'required','description'=>'主持人的id','example'=>'店铺id','default'=>''],
			//'ip'=>'type'
        );
        //$return['extendsFields'] = ['order','activity', 'delivery'];
        return $return;
    }

    /**
     * @param $params
     * @插入访问表中
	 * @wanghaichao
	 * @date 2018/11/20
     */
    public function saveVisit($params){
		$visitObj=app::get('sysmaker')->model('visit');
		$data['ip']=request::getClientIp();
		$data['create_time']=time();
		$data['seller_id']=$params['seller_id'];
		$filter['create_time|than']=strtotime(date('Y-m-d 00:00:00', time()));
		$filter['create_time|lthan']=time();
		$filter['ip']=$data['ip'];
		$filter['seller_id']=$data['seller_id'];
		$visit=$visitObj->getRow('id',$filter);
		if($visit){
			return true;
		}else{
			$visitObj->insert($data);
			return true;
		}
    }
}
