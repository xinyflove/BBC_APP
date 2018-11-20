<?php

/**
 * Class topwap_ctl_member_voucher
 * add_gurundong_20171019_start
 * 投票奖品中心控制器
 */
class topwap_ctl_member_votegift extends topwap_ctl_member{
    public function index()
    {
        //index默认为未使用列表
        $filter = [];
        $filter = [
            'end_time|than'=>time(),
            'order_by'=>'create_time desc'
        ];
        $pagedata = $this->getGiftList($filter);
        $pagedata['data'] = $this->getGiftInfo($pagedata['data']);
        $pagedata['page_total'] = ceil($pagedata['count']/$this->limit);
        return $this->page('topwap/member/votegift/index.html', $pagedata);
    }

    public function getGiftList($filter){
        if(!$filter["page_no"]){
            $apiParams["page_no"] = 1;
        }
        $pageSize = $this->limit;
        $apiParams['page_size'] = $pageSize;
        //获取奖品列表参数
        //默认为未核销
        $apiParams["status"] = $filter["status"]?$filter["status"]:'0';
        $apiParams['user_id'] = (string)userAuth::id();
        $apiParams["deleted"] = $filter["deleted"]?$apiParams["deleted"]:'0';
        //开始时间小于当前时间
        if($filter['start_time|lthan']){
            $apiParams['start_time|lthan'] = $filter['end_time|than'];
        }
        //结束时间大于当前时间
        if($filter['end_time|than']){
            $apiParams['end_time|than'] = $filter['end_time|than'];
        }
        //结束时间小于当前时间，过期了
        if($filter['end_time|lthan']){
            $apiParams['end_time|lthan'] = $filter['end_time|lthan'];
        }
        if($filter['order_by']){
            $apiParams['order_by'] = $filter['order_by'];
        }
        try{
            $giftList = app::get("topwap")->rpccall("activity.vote.gift.gain.list",$apiParams);
        }catch (Exception $e){
            $msg = $e->getMessage();
            return null;
        }
        return $giftList;
    }

    public function ajaxGiftList(){
        try {
            $status = input::get('status');
            $page_no = input::get('page_no');
            $filter = [];
            //未使用
            if($status == 0){
               $filter = [
                   'status' => '0',
                   'end_time|than'=>time(),
                   'page_no' => $page_no,
                   'order_by'=>'create_time desc'
               ];
            }
            //已使用
            if($status == 1){
                $filter = [
                    'status' => '1',
                    'page_no' => $page_no,
                    'order_by'=>'create_time desc'
                ];
            }
            //已过期
            if($status == 2){
                $filter = [
                    'status' => '0',
                    'end_time|lthan'=>time(),
                    'page_no' => $page_no,
                    'order_by'=>'create_time desc'
                ];
            }
            $pagedata = $this->getGiftList($filter);
            $pagedata['data'] = $this->getGiftInfo($pagedata['data']);
            if($pagedata['data']){
                if($status == 0){
                    $data['html'] = view::make('topwap/member/votegift/list.html',$pagedata)->render();
                }
                if($status == 1){
                    $data['html'] = view::make('topwap/member/votegift/list1.html',$pagedata)->render();
                }
                if($status ==2){
                    $data['html'] = view::make('topwap/member/votegift/list2.html',$pagedata)->render();
                }
            }else{
                $data['html'] = view::make('topwap/empty/vote.html',$pagedata)->render();
            }
            $data['pages'] = $page_no;
            $data['pagers']['total'] = ceil($pagedata['count']/$this->limit);
            $data['success'] = true;
        } catch (Exception $e) {
            $msg = $e->getMessage();
            return $this->splash('error', null, $msg, true);
        }

        return response::json($data);exit;
    }

    private function getGiftInfo($giftData){
        if(is_array($giftData)){
            foreach ($giftData as &$v){
                $gift_info = app::get("sysactivityvote")->model("gift")->getRow("gift_profile",["gift_id"=>$v['gift_id']]);
                $v['gift_profile'] = $gift_info['gift_profile'];
            }
        }
        return $giftData;
    }

}
