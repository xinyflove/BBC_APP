<?php
/* 
* 获取主持人的访问分析
* author by wanghaichao
* date 2018/6/22
*/
class sysmaker_api_getVisit{

    public $apiDescription = "获取主持人的访问分析";

    public function getParams()
    {
        $return['params'] = array(
			'seller_id'=>['type'=>'int','valid'=>'required','description'=>'主持人的id','example'=>'主持人id','default'=>''],
			'start_time'=>['type'=>'int','valid'=>'','description'=>'起始时间','example'=>'','default'=>''],
			'end_time'=>['type'=>'int','valid'=>'','description'=>'结束时间','example'=>'','default'=>''],
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
    public function get($params){
		if(empty($params['start_time'])){  
			//获取近七天的数据
			$time=strtotime(date('Y-m-d 0:0:0',time()));
			$start_time=$time-24*3600*6;
			$end_time=time();
		}else{
			$start_time=$params['start_time'];
			$end_time=$params['end_time'];
		}
		
		$filter['create_time|than']=$start_time;//开始的时间
		$filter['create_time|lthan']=$end_time;
		$filter['seller_id']=$params['seller_id'];
		$list=app::get('sysmaker')->model('visit')->getList('id,create_time',$filter);
		
		$start_date=date('j',$start_time);
		$end_date=date('j',$end_time);
		$diff_date=$end_date-$start_date;
		for($i=0;$i<=$diff_date;$i++){
			$day=$start_date+$i;
			$data[$day]=0;
		}
		$dlist=array();
		foreach($list as $k=>$v){
			$time=date('j',$v['create_time']);
			$dlist[$time][]=$v;
		}
		foreach($dlist as $k=>$v){
			$data[$k]=count($v);
		}
		$day_arr=array_keys($data);
		$days=implode("日','",$day_arr);
		$days="'".$days."日'";
		$count=implode(',',$data);
		$result=array('days'=>$days,'count'=>$count);
		return $result;
    }
}
