<?php
/**
 * 转盘抽奖会员奖品操作类
 *
 * @Author 王衍生 50634235@qq.com
 */
class topwap_ctl_member_lottery extends topwap_ctl_member
{
   public function prizeList()
   {

        if(input::get('result_id') && input::get('addr_id'))
        {
            $this->saveAddr();
        }
        $pagedata['title'] = app::get('topwap')->_('我的奖品');
        $pagedata['lottery_link'] = $_COOKIE['lottery_link'];
        return $this->page('topwap/member/lottery/index.html', $pagedata);
    }

    public function ajaxGetprizeList(){

        $pageSize = $this->limit;
        $page_no = input::get('page_no');
        $params = array(
            'page_no' =>intval($page_no),
            'page_size' => intval($pageSize),
            'user_id' => userAuth::id(),
            'bonus_type' => ['custom','coupin'],
            );

        $data = app::get('topwap')->rpcCall('lottery.result.list',$params);

        $pagedata['data'] = $data['datalist'];

        if($pagedata['data']){
            $pagedata['html'] = view::make('topwap/member/lottery/list.html',$pagedata)->render();
        }else{
            $pagedata['html'] = view::make('topwap/empty/vote.html',$pagedata)->render();
        }
        $pagedata['pages'] = $page_no;
        $pagedata['pagers']['total'] = ceil($data['totalnum']/$pageSize);
        $pagedata['success'] = true;
        return response::json($pagedata);
    }

   	public function saveAddr(){

        $params['user_id'] = userAuth::id();
        $params['addr_id'] = input::get('addr_id');
        $userAddr = app::get('topwap')->rpcCall('user.address.info',$params);

        $objMdllottery = app::get('syspromotion')->model('lottery_result');

        $lottery = $objMdllottery->getRow('lottery_id', ['user_id' => userAuth::id(), 'result_id' => input::get('result_id')]);
        if(!$lottery){
            return;
        }

        $addrData = array(
            'receiver_name' => $userAddr['name'],
            'receiver_area' => $userAddr['area'],
            'addr' => $userAddr['addr'],
            'receiver_zip' => $userAddr['zip'],
            'receiver_phone'=>$userAddr['mobile'],
            'result_id' => input::get('result_id'),
            'lottery_id' => $lottery['lottery_id'],
        );

        try{
            $data = array(
                'user_id'=>userAuth::id(),
                'lottery_id'=>$lottery['lottery_id'],
                'result_id'=>input::get('result_id'),
                'addrData' => $addrData,
            );
            app::get('topwap')->rpcCall('promotion.lottery.updateAddr', $data);
        }
        catch(Exception $e)
        {
            // ff($e->getMessage());
            return ;
        }
    }

    // 收货地址列表
    public function addrList()
    {
        $pagedata['mode'] = input::get('mode');

        $pagedata['result_id'] = input::get('result_id');

        $pagedata['userAddrList'] = $this->__getAddrList();
        return $this->page('topwap/member/lottery/addr_list.html', $pagedata);
    }

    private function __getAddrList()
    {
        $params['user_id'] = userAuth::id();
        //会员收货地址
        $userAddrList = app::get('topwap')->rpcCall('user.address.list',$params,'buyer');
        $count = $userAddrList['count'];
        $userAddrList = $userAddrList['list'];

        foreach ($userAddrList as $key => $value) {
            $userAddrList[$key]['area'] = explode(":",$value['area'])[0];
        }
        return $userAddrList;
    }
}

