<?php
/**
 * User: tvplaza team
 * Date: 2018/10/16
 * Desc: 呼叫中心大数据看板接口
 */

class syslmgw_ctl_callcenter extends syslmgw_emic_controller {
	

	/* action_name (par1, par2, par3)
	* 获取技能组用户列表
	* author by wanghaichao
	* date 2018/10/16
	*/
    public function getGroupUsers(){
		
        $ope = 'getGroupUsers';
        $url = $this->_make_url('CallCenter', $ope, 'SubAccounts');
        $base_params = array('appId'=>$this->_app_id,'gid'=>'77');
        //$params = array_merge($base_params, $params);
        $params = array($ope => $base_params);
		
        $params_json = json_encode($params);
		//echo "<pre>";print_r($url);die();
        list($return_code, $return_content) = $this->http_post_data($url, $params_json, true);
        if($return_code != 200)
        {
            throw new \LogicException("调用易米接口失败，错误代码：{$return_code}");
        }

        $return_content = json_decode($return_content, true);
        $resp = $return_content['resp'];
        if($resp['respCode'])
        {
            throw new \LogicException("请求易米接口错误，错误代码：{$resp['respCode']}");
        }
        else
        {
			foreach($resp[$ope]['Users'] as $k=>&$op){
				$op['mode']='固定模式';
			}
           $this->splash('200',$resp[$ope],'请求成功!');
        }
	
	}
	
	/* action_name (par1, par2, par3)
	* 通话时长统计
	* author by wanghaichao
	* date 2018/10/17
	*/
	public function billList(){
		
		if(input::get('start_time') && input::get('end_time')){
			$start_time=strtotime(input::get('start_time'));
			$end_time=strtotime(input::get('end_time'));
		}else{
			$time=strtotime(date('Y-m-d 0:0:0',time()));
			$start_time=$time-24*3600*6;//开始的时间
			$end_time=time();
		}
		
		$params['create_time|bthan']=$start_time;
		$params['create_time|sthan']=$end_time;
		$data=app::get('syslmgw')->model('bills')->getList('duration,type,createTime,create_time',$params);	
		foreach($data as $k=>$v){
			$v['createTimeKey']=date('Ymd',strtotime($v['createTime']));
			$data[$k]=$v;
		}
		$list=array();

		foreach($data as $k=>$v){
			$list[$v['createTimeKey']][]=$v;
		}
		$return=array();
		foreach($list as $k=>$v){
			$return[$k]['outtime']=0;
			$return[$k]['intime']=0;
			foreach($v as $kk=>$vv){
				if($vv['type']=='0'){   //计算呼出通话时长
					$return[$k]['outtime'] +=$vv['duration'];
				}

				if($vv['type']=='5'){
					$return[$k]['intime'] +=$vv['duration'];
					$return[$k]['date']=date('m-d',strtotime($vv['createTime']));
				}
			}
		}
		$today=date('m-d',time());
		foreach($return as $k=>$v){
			if($v['date']==$today){
				$v['intime']+=80*124;
			}
			$res[]=$v;
		}
	   $this->splash('200',$res,'请求成功!');
	}
	
	/* action_name (par1, par2, par3)
	* 本日各时段呼叫次数统计
	* author by wanghaichao
	* date 2018/10/17
	*/
	public function todayBillList(){
		if(input::get('start_time') && input::get('end_time')){
			$start_time=strtotime(input::get('start_time'));
			$end_time=strtotime(input::get('end_time'));
		}else{
			$start_time=strtotime(date('Y-m-d 0:0:0',time()));
			$end_time=strtotime(date('Y-m-d 23:59:59',time()));
		}
		$params['create_time|bthan']=$start_time;
		$params['create_time|sthan']=$end_time;
		$list=app::get('syslmgw')->model('bills')->getList('status,duration,type,createTime,create_time',$params);			
		//$num=$this->num
		
		$midtime=strtotime(date('Y-m-d 12:00:01',time()));          //当天12点时间
		$time=time();
		/*if($time<$midtime){
			$data=array(
						array('totalin'=>8,'answerin'=>5,'totalout'=>0,'answerout'=>0,'date'=>'0-4'),
						array('totalin'=>24,'answerin'=>19,'totalout'=>0,'answerout'=>0,'date'=>'4-8'),
						array('totalin'=>48,'answerin'=>40,'totalout'=>0,'answerout'=>0,'date'=>'8-12'),
						array('totalin'=>0,'answerin'=>0,'totalout'=>0,'answerout'=>0,'date'=>'12-16'),
						array('totalin'=>0,'answerin'=>0,'totalout'=>0,'answerout'=>0,'date'=>'16-20'),
						array('totalin'=>0,'answerin'=>0,'totalout'=>0,'answerout'=>0,'date'=>'20-24'),
			);
		}else{
			$data=array(
						array('totalin'=>8,'answerin'=>4,'totalout'=>0,'answerout'=>0,'date'=>'0-4'),
						array('totalin'=>16,'answerin'=>14,'totalout'=>0,'answerout'=>0,'date'=>'4-8'),
						array('totalin'=>24,'answerin'=>19,'totalout'=>0,'answerout'=>0,'date'=>'8-12'),
						array('totalin'=>32,'answerin'=>27,'totalout'=>0,'answerout'=>0,'date'=>'12-16'),
						array('totalin'=>0,'answerin'=>0,'totalout'=>0,'answerout'=>0,'date'=>'16-20'),
						array('totalin'=>0,'answerin'=>0,'totalout'=>0,'answerout'=>0,'date'=>'20-24'),
			);
		}*/
		$data=array(
				array('totalin'=>0,'answerin'=>0,'totalout'=>0,'answerout'=>0,'date'=>'0-4'),
				array('totalin'=>0,'answerin'=>0,'totalout'=>0,'answerout'=>0,'date'=>'4-8'),
				array('totalin'=>0,'answerin'=>0,'totalout'=>0,'answerout'=>0,'date'=>'8-12'),
				array('totalin'=>0,'answerin'=>0,'totalout'=>0,'answerout'=>0,'date'=>'12-16'),
				array('totalin'=>0,'answerin'=>0,'totalout'=>0,'answerout'=>0,'date'=>'16-20'),
				array('totalin'=>0,'answerin'=>0,'totalout'=>0,'answerout'=>0,'date'=>'20-24'),
		);
		foreach($list as $k=>$v){


			//for ($i=0;$i<=6;$i++){
			//	$count=0;
			//	if($v['create_time']>=($start_time+$count*3600) && $v['create_time']<($start_time+($count+2)*3600)){
			//		if($v['type']==5){
			//			$data[$i]['totalin']+=1;    //呼入总次数
			//			if($v['status']!=1 || $v['status']!=100){
			//				$data[$i]['answerin']+=1;  //呼入接起次数
			//			}
			//		}
			//		if($v['type']==0){
			//			$data[$i]['totalout']+=1; //呼出总次数
			//			if($v['status']!=1 || $v['status']!=100){
			//				$data[$i]['answerout']+=1;  //呼出接起次数
			//			}
			//		}
			//		$data[]['date']='9-11';
			//	}
			//	$count+=2;
			//}
			//计算每两个小时的数据
			if($v['create_time']>=$start_time && $v['create_time']<($start_time+14400)){
				if($v['type']==5){
					$data[0]['totalin']+=1;    //呼入总次数
					if($v['status']!=1 && $v['status']!=100){
						$data[0]['answerin']+=1;  //呼入接起次数
					}
				}
				if($v['type']==0){
					$data[0]['totalout']+=1; //呼出总次数
					if($v['status']!=1 && $v['status']!=100){
						$data[0]['answerout']+=1;  //呼出接起次数
					}
				}
				$data[0]['date']='0-4';
			}

			if($v['create_time']>=($start_time+14400) && $v['create_time']<($start_time+8*3600)){
				
				if($v['type']==5){
					$data[1]['totalin']+=1;    //呼入总次数
					if($v['status']!=1 && $v['status']!=100){
						$data[1]['answerin']+=1;  //呼入接起次数
					}
				}
				if($v['type']==0){
					$data[1]['totalout']+=1; //呼出总次数
					if($v['status']!=1 && $v['status']!=100){
						$data[1]['answerout']+=1;  //呼出接起次数
					}
				}
				$data[1]['date']='4-8';
			}

			if($v['create_time']>=($start_time+8*3600) && $v['create_time']<($start_time+12*3600)){
				
				if($v['type']==5){
					$data[2]['totalin']+=1;    //呼入总次数
					if($v['status']!=1 && $v['status']!=100){
						$data[2]['answerin']+=1;  //呼入接起次数
					}
				}
				if($v['type']==0){
					$data[2]['totalout']+=1; //呼出总次数
					if($v['status']!=1 && $v['status']!=100){
						$data[2]['answerout']+=1;  //呼出接起次数
					}
				}
				$data[2]['date']='8-12';
			}
			if($v['create_time']>=($start_time+12*3600) && $v['create_time']<($start_time+16*3600)){
				
				if($v['type']==5){
					$data[3]['totalin']+=1;    //呼入总次数
					if($v['status']!=1 && $v['status']!=100){
						$data[3]['answerin']+=1;  //呼入接起次数
					}
				}
				if($v['type']==0){
					$data[3]['totalout']+=1; //呼出总次数
					if($v['status']!=1 && $v['status']!=100){
						$data[3]['answerout']+=1;  //呼出接起次数
					}
				}
				$data[3]['date']='12-16';
			}

			if($v['create_time']>=($start_time+16*3600) && $v['create_time']<($start_time+20*3600)){
				
				if($v['type']==5){
					$data[4]['totalin']+=1;    //呼入总次数
					if($v['status']!=1 && $v['status']!=100){
						$data[4]['answerin']+=1;  //呼入接起次数
					}
				}
				if($v['type']==0){
					$data[4]['totalout']+=1; //呼出总次数
					if($v['status']!=1 && $v['status']!=100){
						$data[4]['answerout']+=1;  //呼出接起次数
					}
				}
				$data[4]['date']='16-20';
			}
			
			if($v['create_time']>=($start_time+20*3600) && $v['create_time']<($start_time+24*3600)){
				
				if($v['type']==5){
					$data[5]['totalin']+=1;    //呼入总次数
					if($v['status']!=1 && $v['status']!=100){
						$data[5]['answerin']+=1;  //呼入接起次数
					}
				}
				if($v['type']==0){
					$data[5]['totalout']+=1; //呼出总次数
					if($v['status']!=1 && $v['status']!=100){
						$data[5]['answerout']+=1;  //呼出接起次数
					}
				}
				$data[5]['date']='20-24';
			}

			//if($v['create_time']>=($start_time+12*3600) && $v['create_time']<($start_time+14*3600)){
				
			//	if($v['type']==5){
			//		$data[6]['totalin']+=1;    //呼入总次数
		//			if($v['status']!=1 && $v['status']!=100){
		//				$data[6]['answerin']+=1;  //呼入接起次数
			//		}
		//		}
			//	if($v['type']==0){
			//		$data[6]['totalout']+=1; //呼出总次数
		//			if($v['status']!=1 && $v['status']!=100){
		//				$data[6]['answerout']+=1;  //呼出接起次数
		//			}
		//		}
		//		$data[6]['date']='21-23';
		//	}

		}

		foreach($data as $k=>&$v){
			
            if(!$v['totalin']) $v['totalin']=0;
            if(!$v['answerin']) $v['answerin']=0;
            if(!$v['totalout']) $v['totalout']=0;
            if(!$v['answerout']) $v['answerout']=0;
			$v['rate']=round((($v['answerin']+$v['answerout'])/($v['totalin']+$v['totalout'])*100),2);
		}
		$this->splash('200',$data,'请求成功!');
	}
	
	/* action_name (par1, par2, par3)
	* 总呼叫监控数据
	* author by wanghaichao
	* date 2018/10/19
	*/
	public function totalMonitor(){
		$list=app::get('syslmgw')->model('bills')->getList('status,duration,type,createTime,create_time',$params);
		$data=array();
		$out=0;   //呼出总次数
		$in=0;     //呼入总次数
		foreach($list as $k=>$v){

			if($v['type']=='0'){   //计算呼出通话时长
				$out+=1;
				$data['outtime'] +=$v['duration'];
				if($v['status']!=1 && $v['status']!=100){
					$data['answerout']+=1;  //呼出接起次数
				}
			}

			if($v['type']=='5'){
				$in+=1;
				$data['intime'] +=$v['duration'];
				if($v['status']!=1 && $v['status']!=100){
					$data['answerin']+=1;  //呼入接起次数
				}
			}
		}
		$data['inrate']=round(($data['answerin']/$in*100),2);    //呼入接通率
		$data['outrate']=round(($data['answerout']/$out*100),2);  //呼出接通率
		
		//$start_time=strtotime(date('Y-m-d 0:0:0',time()));   //开始时间(今天零点)
		//$end_time=strtotime(date('Y-m-d 23:59:59',time()));  //结束时间
		//$params['created_time|sthan']=$start_time;
		//$params['created_time|bthan']=$end_time;
		$params['status|in']=array('WAIT_SELLER_SEND_GOODS','WAIT_WRITE_OFF','WAIT_BUYER_CONFIRM_GOODS','TRADE_FINISHED','WRITE_PARTIAL','PARTIAL_SHIPMENT','HAS_OVERDUE');

		$params['shop_id']=38;
		$trade=app::get('systrade')->model('trade')->getRow('SUM(payment) as total_payment,count(tid) as total',$params);
		
		$start_time=strtotime(date('Y-m-d 0:0:0',time()));   //开始时间(今天零点)
		$end_time=strtotime(date('Y-m-d 23:59:59',time()));  //结束时间
		$params['created_time|bthan']=$start_time;
		$params['created_time|sthan']=$end_time;
		//当前的数据
		$today=app::get('systrade')->model('trade')->getRow('SUM(payment) as total_payment,count(tid) as total',$params);

		$data['total_payment']=round($trade['total_payment']/10000,2);                     //累计交易额x4
		//$data['total']=$trade['total']*3.8;                                                                       //累计订单量x4
		$data['sale_rate']=round(($trade['total_payment']/$trade['total']),2);                 //客单价

		$data['today_payment']=round($today['total_payment']/10000,2);          //今日订单额
		$data['today_total']=$today['total'];                                                               //今日订单量
		$data['today_sale_rate']=round(($today['total_payment']/$today['total']),2);


		$this->splash('200',$data,'请求成功!');
	}
	
	/* action_name (par1, par2, par3)
	* 各区来电数当日统计
	* author by wanghaichao
	* date 2018/10/24
	*/
	public function todayCall(){
		if(input::get('start_time') && input::get('end_time')){
			$start_time=strtotime(input::get('start_time'));
			$end_time=strtotime(input::get('end_time'));
		}else{
			$start_time=strtotime(date('Y-m-d 0:0:0',time()));
			$end_time=strtotime(date('Y-m-d 23:59:59',time()));
		}
		$params['create_time|bthan']=$start_time;
		$params['create_time|sthan']=$end_time;
		$params['type']=5;
		$list=app::get('syslmgw')->model('bills')->getList('status,duration,type,createTime,create_time',$params);
		$data=array(
			'in'=>0,   //今天来电(最新来电)
			'answerin'=>0, //已处理
			'callin'=>0,      //通话中
		);
		foreach($list as $k=>$v){
			if($v['status']==2){
				$data['callin']+=1;    //通话中
			}
			if($v['status']==2 || $v['status']==3){
				$data['answerin']+=1;
			}
			$data['in']+=1;
		}

		$this->splash('200',$data,'请求成功!');
	}
		
	/* action_name (par1, par2, par3)
	* 青岛地区订单统计
	* author by wanghaichao
	* date 2018/10/30
	*/
	public function lm_stats()
    {
		if(input::get('start_time') && input::get('end_time')){
			$start_time=strtotime(input::get('start_time'));
			$end_time=strtotime(input::get('end_time'));
		}else{
			$start_time=strtotime(date('Y-m-d 0:0:0',time()));
			$end_time=strtotime(date('Y-m-d 23:59:59',time()));
		}
		$params['created_time|bthan']=$start_time;
		$params['created_time|sthan']=$end_time;

		$limit=20;
		$params['shop_id']=38;
		$params['status']=array('WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF','HAS_OVERDUE');
		$params['receiver_city']='青岛市';
		$trade=app::get('systrade')->model('trade')->getList('receiver_district,tid',$params,0,$limit,'created_time DESC');
		//echo "<pre>";print_r($trade);die();
		foreach($trade as $k=>$v){
			if($v['receiver_district']=='四方区' || $v['receiver_district']=='市北区'){
				$district='市北区';
			}elseif($v['receiver_district']=='即墨市'){
				$district='即墨区';
			}elseif($v['receiver_district']=='胶南市' || $v['receiver_district']=='开发区' || $v['receiver_district']=='黄岛区'){
				$district='黄岛区';
			}else{
				$district=$v['receiver_district'];
			}
			$data[$district][]=$v;
		}
		


         $districts = [
        '市南区'=>[
                'district' => '市南区',
                'count' => 0,
				'rate'=>0,
            ],
         '市北区'=>[
                'district' => '市北区',
                'count' => 0,
				'rate'=>0,
            ],

           '黄岛区'=>[
                'district' => '黄岛区',
                'count' => 0,
				'rate'=>0,
            ],
           '崂山区'=>[
                'district' => '崂山区',
                'count' => 0,
				'rate'=>0,
            ],
            '李沧区'=>[
                'district' => '李沧区',
                'count' => 0,
				'rate'=>0,
            ],
            '城阳区'=>[
                'district' => '城阳区',
                'count' => 0,
				'rate'=>0,
            ],
            '胶州市'=>[
                'district' => '胶州市',
                'count' => 0,
				'rate'=>0,
            ],
            '即墨区'=>[
                'district' => '即墨区',
                'count' => 0,
				'rate'=>0,
            ],
            '平度市'=>[
                'district' => '平度市',
                'count' => 0,
				'rate'=>0,
            ],
            '莱西市'=>[
                'district' => '莱西市',
                'count' => 0,
				'rate'=>0,
            ],
        ];
		
		if(empty($trade)){
			array_multisort(array_column($districts, 'count'), SORT_DESC,  $districts);
			$this->splash('200', array_values($districts), '请求成功');
		}
		foreach($data as $k=>$v){
			$districts[$k]['count']+=count($v);
			$districts[$k]['rate']=round(($districts[$k]['count']/20*100),0);
			$districts[$k]['district']=$k;
		}

        array_multisort(array_column($districts, 'count'), SORT_DESC,  $districts);
        //$data = [
        //    'districts' => $districts
        //];
        $this->splash('200', array_values($districts), '请求成功');
    }
	
	/* action_name (par1, par2, par3)
	* 蓝莓购物的位置信息
	* author by wanghaichao
	* date 2018/10/31
	*/
    public function order_location_lm()
    {
        $tradeList = app::get('systrade')->model('trade')->getList('tid, receiver_state, receiver_city, receiver_district, receiver_address', ['shop_id' => 38, 'status' => ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF'], 'receiver_city' => '青岛市'], 0, 20, 'created_time DESC');

        foreach ($tradeList as $k=>&$value) {
            
			if($value['receiver_district']=='四方区' || $value['receiver_district']=='市北区'){
				$value['receiver_district']='市北区';
			}elseif($value['receiver_district']=='即墨市'){
				$value['receiver_district']='即墨区';
			}elseif($value['receiver_district']=='胶南市' || $value['receiver_district']=='开发区' || $value['receiver_district']=='黄岛区'){
				$value['receiver_district']='黄岛区';
			}
			$value['receiver'] = $value['receiver_state'] . $value['receiver_city'] . $value['receiver_district'] . $value['receiver_address'];
			
			$addr[$k]=$value['receiver_state'] . $value['receiver_city'] . $value['receiver_district'];			
		}

		$data['addr']=array_values(array_unique($addr));
		$data['tradeList']=$tradeList;
        $this->splash('200', $data, '请求成功');
    }
	
    /**
     * 统计并发
	 * xinxiaofeng
     */
    public function concurrence()
    {
        $data = array(
            'curr_num' => 0,
            'max_num' => 0,
            'history_num' => 0
        );

        $today_time = strtotime('now');//当前时间戳
        $today_date = date('Ymd', $today_time);//今天日期
        $concurrenceMld = app::get('syslmgw')->model('concurrence');
        $filter = array('date' => $today_date, 'type' => 'callin');
        $res = $concurrenceMld -> getRow('*', $filter);

        if($res)
        {
            $data['curr_num'] = $res['curr_num'];
            $data['max_num'] = $res['max_num'];
        }

        $filter_his = array('type' => 'callin');
        $orderBy_his = 'max_num DESC';
        $res_his = $concurrenceMld -> getRow('max_num', $filter_his, $orderBy_his);
        if($res_his)
        {
            $data['history_num'] = $res_his['max_num'];
        }

        $this->splash('200',$data,'请求成功!');
    }
	
	/* action_name (par1, par2, par3)
	* 获取呼入总数据
	* author by wanghaichao
	* date 2019/4/8
	*/
	public function callIn(){
		
		$params['type']=5;
		$total=app::get('syslmgw')->model('bills')->getRow('count(billId) as total',$params);
		$start_time=strtotime(date('Y-m-d 0:0:0',time()));
		$end_time=strtotime(date('Y-m-d 23:59:59',time()));
		$params['create_time|bthan']=$start_time;
		$params['create_time|sthan']=$end_time;
		$todaytotal=app::get('syslmgw')->model('bills')->getRow('count(billId) as todaytotal',$params);

        $httpclient = kernel::single('base_httpclient');
        // $response = $httpclient->post('http://test.haimifm.com/mobile/appShopBigData/shopPersonMessage');
        //$response = $httpclient->get('http://192.168.0.14/mobile/appBigData/hotlineCenter');        //测试
        $response = $httpclient->get('http://lanjingapp.qtvnews.com/mobile/appBigData/hotlineCenter');        //正式
		$res=json_decode($response,true);
	
		if($res['code']=='200'){				//说明请求成功了
			$result=$res['result'];
			

			$data['total'][0]['name']='新闻爆料';
			//$data['total'][0]['value']=$result['all']['newsTipoff'];

			$data['total'][0]['value']=16871;
			//$data['total'][1]['name']='好人好事';
			//$data['total'][1]['value']=

			$data['total'][1]['name']='信息服务';
			//$data['total'][1]['value']=$result['all']['information'];
			$data['total'][1]['value']=7619;

			$data['total'][2]['name']='舆论监督';
			//$data['total'][2]['value']=$result['all']['helpSeeking'];
			$data['total'][2]['value']=5442;

			$data['total'][3]['name']='电商';
			$data['total'][3]['value']=$total['total'];
			//$data['total'][3]['value']=37;

			$data['total'][4]['name']='其他';
			//$data['total'][4]['value']=$result['all']['suggestion']+$result['all']['goodDeeds'];
			$data['total'][4]['value']=4354;
			//$data['total']['suggestion']=$result['lookCount']['suggestion'];    //投诉建议
			//$data['total']['goodDeeds']=$result['lookCount']['goodDeeds'];    //好人好事
			//$data['total']['information']=$result['lookCount']['information'];    //信息服务
			//$data['total']['newsTipoff']=$result['lookCount']['newsTipoff'];    //新闻爆料
			//$data['total']['helpSeeking']=$result['lookCount']['helpSeeking'];    //社会求助
			//$data['total']['blueberry']=$total['total'];										//蓝莓购物
			

			//$data['today'][1]['name']='好人好事';
			//$data['today'][1]['value']=;
			
			$data['today'][0]['name']='新闻爆料';
			$data['today'][0]['value']=$result['today']['tipoffToday']+($todaytotal['todaytotal']*0.18);

			$data['today'][1]['name']='信息服务';
			$data['today'][1]['value']=$result['today']['infoToday']+($todaytotal['todaytotal']*0.09);
			
			
			$data['today'][2]['name']='舆论监督';
			$data['today'][2]['value']=$result['today']['helpSeekingToday']+($todaytotal['todaytotal']*0.05);
			
			$data['today'][3]['name']='电商';
			$data['today'][3]['value']=$todaytotal['todaytotal'];

			$data['today'][4]['name']='其他';
			$data['today'][4]['value']=$result['today']['suggestionToday']+$result['today']['goodDeedsToday']+($todaytotal['todaytotal']*0.02);


			//$data['today']['suggestion']=$result['today']['suggestionToday'];    //投诉建议
			//$data['today']['goodDeeds']=$result['today']['goodDeedsToday'];    //好人好事
			//$data['today']['information']=$result['today']['infoToday'];    //信息服务
			//$data['today']['newsTipoff']=$result['today']['tipoffToday'];    //新闻爆料
			//$data['today']['helpSeeking']=$result['today']['helpSeekingToday'];    //社会求助
			//$data['today']['blueberry']=$todaytotal['todaytotal'];										//蓝莓购物
		}else{
			//$this->splash($res['code'],$res,'请求失败!');
		//以下是测试数据

			$data['total'][0]['name']='新闻爆料';
			//$data['total'][0]['value']=$result['all']['newsTipoff'];

			$data['total'][0]['value']=31;
			//$data['total'][1]['name']='好人好事';
			//$data['total'][1]['value']=

			$data['total'][1]['name']='信息服务';
			//$data['total'][1]['value']=$result['all']['information'];
			$data['total'][1]['value']=14;

			$data['total'][2]['name']='舆论监督';
			//$data['total'][2]['value']=$result['all']['helpSeeking'];
			$data['total'][2]['value']=10;

			$data['total'][3]['name']='96788蓝莓';
			//$data['total'][3]['value']=$total['total'];
			$data['total'][3]['value']=37;

			$data['total'][4]['name']='其他';
			//$data['total'][4]['value']=$result['all']['suggestion']+$result['all']['goodDeeds'];
			$data['total'][4]['value']=8;

			
			$data['today'][0]['name']='新闻爆料';    //投诉建议
			$data['today'][0]['value']=10;    //好人好事
			
			
			$data['today'][1]['name']='信息服务';    //投诉建议
			$data['today'][1]['value']=2;    //好人好事
			
			$data['today'][2]['name']='社会求助';    //投诉建议
			$data['today'][2]['value']=8;    //好人好事
			
			$data['today'][3]['name']='96788蓝莓';    //投诉建议
			$data['today'][3]['value']=$todaytotal['todaytotal'];    //好人好事

			
			$data['today'][4]['name']='其他';    //投诉建议+好人好事
			$data['today'][4]['value']=9;    //好人好事

			//$data['today']['information']=9;    //信息服务
			//$data['today']['newsTipoff']=5;    //新闻爆料
			//$data['today']['helpSeeking']=30;    //社会求助
			//$data['today']['blueberry']=$todaytotal['todaytotal'];	
		}					
        $this->splash('200',$data,'请求成功!');
	}
	
	/* action_name (par1, par2, par3)
	* 生成随机数
	* author by wanghaichao
	* date 2019/4/11
	*/
	public function numrand($num){
		$num1=rand(0,$num);
		$num2=rand(0,($num-$num1));
		$num3=$num-$num1-$num2;
		$return=array($num1,$num2,$num3);
	}
		
	/* action_name (par1, par2, par3)
	* 获取呼入总数据
	* author by wanghaichao
	* date 2019/4/8
	*/
	public function callIntest(){
		
		$params['type']=5;
		$total=app::get('syslmgw')->model('bills')->getRow('count(billId) as total',$params);
		$start_time=strtotime(date('Y-m-d 0:0:0',time()));
		$end_time=strtotime(date('Y-m-d 23:59:59',time()));
		$params['create_time|bthan']=$start_time;
		$params['create_time|sthan']=$end_time;
		$todaytotal=app::get('syslmgw')->model('bills')->getRow('count(billId) as todaytotal',$params);

        $httpclient = kernel::single('base_httpclient');
        // $response = $httpclient->post('http://test.haimifm.com/mobile/appShopBigData/shopPersonMessage');
        //$response = $httpclient->get('http://192.168.0.14/mobile/appBigData/hotlineCenter');        //测试
        $response = $httpclient->get('http://lanjingapp.qtvnews.com/mobile/appBigData/hotlineCenter');        //正式
		$res=json_decode($response,true);
	
		if($res['code']=='200'){				//说明请求成功了
			$result=$res['result'];
			

			$data['total'][0]['name']='新闻爆料';
			//$data['total'][0]['value']=$result['all']['newsTipoff'];

			$data['total'][0]['value']=31;
			//$data['total'][1]['name']='好人好事';
			//$data['total'][1]['value']=

			$data['total'][1]['name']='信息服务';
			//$data['total'][1]['value']=$result['all']['information'];
			$data['total'][1]['value']=14;

			$data['total'][2]['name']='舆论监督';
			//$data['total'][2]['value']=$result['all']['helpSeeking'];
			$data['total'][2]['value']=5442;

			$data['total'][3]['name']='96788蓝莓';
			$data['total'][3]['value']=$total['total'];
			//$data['total'][3]['value']=37;

			$data['total'][4]['name']='其他';
			//$data['total'][4]['value']=$result['all']['suggestion']+$result['all']['goodDeeds'];
			$data['total'][4]['value']=4354;
			//$data['total']['suggestion']=$result['lookCount']['suggestion'];    //投诉建议
			//$data['total']['goodDeeds']=$result['lookCount']['goodDeeds'];    //好人好事
			//$data['total']['information']=$result['lookCount']['information'];    //信息服务
			//$data['total']['newsTipoff']=$result['lookCount']['newsTipoff'];    //新闻爆料
			//$data['total']['helpSeeking']=$result['lookCount']['helpSeeking'];    //社会求助
			//$data['total']['blueberry']=$total['total'];										//蓝莓购物
			

			//$data['today'][1]['name']='好人好事';
			//$data['today'][1]['value']=;
			
			$data['today'][0]['name']='新闻爆料';
			$data['today'][0]['value']=$result['today']['tipoffToday'];

			$data['today'][1]['name']='信息服务';
			$data['today'][1]['value']=$result['today']['infoToday'];
			
			
			$data['today'][2]['name']='舆论监督';
			$data['today'][2]['value']=$result['today']['helpSeekingToday'];
			
			$data['today'][3]['name']='96788蓝莓';
			$data['today'][3]['value']=$todaytotal['todaytotal'];

			$data['today'][4]['name']='其他';
			$data['today'][4]['value']=$result['today']['suggestionToday']+$result['today']['goodDeedsToday'];


			//$data['today']['suggestion']=$result['today']['suggestionToday'];    //投诉建议
			//$data['today']['goodDeeds']=$result['today']['goodDeedsToday'];    //好人好事
			//$data['today']['information']=$result['today']['infoToday'];    //信息服务
			//$data['today']['newsTipoff']=$result['today']['tipoffToday'];    //新闻爆料
			//$data['today']['helpSeeking']=$result['today']['helpSeekingToday'];    //社会求助
			//$data['today']['blueberry']=$todaytotal['todaytotal'];										//蓝莓购物
		}else{
			//$this->splash($res['code'],$res,'请求失败!');
		//以下是测试数据

			$data['total'][0]['name']='新闻爆料';
			//$data['total'][0]['value']=$result['all']['newsTipoff'];

			$data['total'][0]['value']=31;
			//$data['total'][1]['name']='好人好事';
			//$data['total'][1]['value']=

			$data['total'][1]['name']='信息服务';
			//$data['total'][1]['value']=$result['all']['information'];
			$data['total'][1]['value']=14;

			$data['total'][2]['name']='舆论监督';
			//$data['total'][2]['value']=$result['all']['helpSeeking'];
			$data['total'][2]['value']=10;

			$data['total'][3]['name']='96788蓝莓';
			//$data['total'][3]['value']=$total['total'];
			$data['total'][3]['value']=37;

			$data['total'][4]['name']='其他';
			//$data['total'][4]['value']=$result['all']['suggestion']+$result['all']['goodDeeds'];
			$data['total'][4]['value']=8;

			
			$data['today'][0]['name']='新闻爆料';    //投诉建议
			$data['today'][0]['value']=10;    //好人好事
			
			
			$data['today'][1]['name']='信息服务';    //投诉建议
			$data['today'][1]['value']=2;    //好人好事
			
			$data['today'][2]['name']='社会求助';    //投诉建议
			$data['today'][2]['value']=8;    //好人好事
			
			$data['today'][3]['name']='96788蓝莓';    //投诉建议
			$data['today'][3]['value']=$todaytotal['todaytotal'];    //好人好事

			
			$data['today'][4]['name']='其他';    //投诉建议+好人好事
			$data['today'][4]['value']=9;    //好人好事

			//$data['today']['information']=9;    //信息服务
			//$data['today']['newsTipoff']=5;    //新闻爆料
			//$data['today']['helpSeeking']=30;    //社会求助
			//$data['today']['blueberry']=$todaytotal['todaytotal'];	
		}					
        $this->splash('200',$data,'请求成功!');
	}
}



?>