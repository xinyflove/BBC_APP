<?php
class topshop_ctl_promotion_lottery extends topshop_controller{

    /**
     * 转盘抽奖列表
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function list_lottery()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('转盘抽奖管理');
        $filter = input::get();
        if(!$filter['pages'])
        {
            $filter['pages'] = 1;
        }
        $pageSize = 10;
        $params = array(
            'page_no' => $filter['pages'],
            'page_size' => intval($pageSize),
            'fields' => 'lottery_id,lottery_name,status,used_platform,created_time,start_time,end_time',
            'shop_id'=> $this->shopId,
        );
        $lotteryListData = app::get('topshop')->rpcCall('promotion.lottery.list', $params,'seller');
        $count = $lotteryListData['count'];
        $pagedata['lotteryList'] = $lotteryListData['lotterys'];

        //处理翻页数据
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_promotion_lottery@list_lottery', $filter),
            'current'=>$current,
            'total'=>$total,
            'use_app'=>'topshop',
            'token'=>$filter['pages'],
        );

        // $pagedata['now'] = time();
        $pagedata['total'] = $count;
        // $pagedata['examine_setting'] = app::get('sysconf')->getConf('shop.promotion.examine');

        return $this->page('topshop/promotion/lottery/index.html', $pagedata);
    }

    /**
     * 转盘抽奖编辑页
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function edit_lottery()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('新添/编辑转盘抽奖');
        $apiData['lottery_id'] = input::get('lottery_id');
        $pagedata['valid_time'] = date('Y/m/d H:i', time()+60) . '-' . date('Y/m/d H:i', time()+120); //默认时间
        if($apiData['lottery_id'])
        {
            $apiData['shop_id'] = $this->shopId;
            $pagedata = app::get('topshop')->rpcCall('promotion.lottery.get', $apiData);
            $pagedata['valid_time'] = date('Y/m/d H:i', $pagedata['start_time']) . '-' . date('Y/m/d H:i', $pagedata['end_time']);

            $notItems = array_column($pagedata['itemsList'], 'item_id');
            $pagedata['notEndItem'] =  json_encode($notItems,true);
        }

        return $this->page('topshop/promotion/lottery/edit.html', $pagedata);
    }

    /**
     * 转盘抽奖保存
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function save_lottery()
    {
        $data = input::get();
        try
        {
            $data['lottery_type'] = 1;
            $data['shop_id'] = $this->shopId;
            $canuseTimeArray = explode('-', $data['valid_time']);
            $data['start_time']  = strtotime($canuseTimeArray[0]);
            $data['end_time'] = strtotime($canuseTimeArray[1]);
            unset($data['valid_time']);
            kernel::single('syspromotion_lottery')->save($data);
            $this->sellerlog("编辑转盘活动{$apiParams['name']}", 1);
            $url = url::action('topshop_ctl_promotion_lottery@list_lottery');
            $msg = app::get('topshop')->_('保存活动成功');
            return $this->splash('success',$url,$msg,true);
        }
        catch(Exception $e)
        {
            // $db->rollback();
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg, true);
        }
    }

    public function log_detail_list(){
        $this->contentHeaderTitle = app::get('topshop')->_('转盘抽奖中奖记录');
        $filter = input::get();
        if(!$filter['pages'])
        {
            $filter['pages'] = 1;
        }

        $pageSize = 10;

        $params = array(
            'page_no' => $filter['pages'],
            'page_size' => intval($pageSize),
            'lottery_id' => $filter['lottery_id'],
            // 'bonus_type' => ['custom','coupin'],
            );
        $pagedata['lottery_id'] = $filter['lottery_id'];
        $data = app::get('topwap')->rpcCall('lottery.result.list',$params);
        // jj($data);
        $total = ceil($data['totalnum']/$pageSize);
        $pagedata['lotteryList'] = $data['datalist'];

        //处理翻页数据
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_promotion_lottery@log_detail_list', $filter),
            'current'=>$current,
            'total'=>$total,
            'use_app'=>'topshop',
            'token'=>$filter['pages'],
        );
        $pagedata['total'] = $data['totalnum'];

        return $this->page('topshop/promotion/lottery/loglist.html', $pagedata);
    }


    /**
     * 更新活动状态
     */
    public function updateStatus(){
        $data = input::get();
        if(!in_array($data['status'],['stop', 'active'])){
            return $this->splash('error', '', '状态错误', true);
        }
        try{
            app::get('syspromotion')->rpcCall('promotion.lottery.updateStatus',array('lottery_id'=>$data['lottery_id'],'status'=>$data['status']));
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg, true);
        }
        return $this->splash('success', '', '更新成功', true);
    }
}