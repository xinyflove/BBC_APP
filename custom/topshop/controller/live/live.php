<?php

class topshop_ctl_live_live extends topshop_controller
{
    public $limit = 10;
    protected $_min_w = 1;
    protected $_max_w = 53;
    protected $_week = array(
        '星期日','星期一','星期二','星期三','星期四','星期五','星期六',
    );

    /**
     * 列表页 已遗弃
     * @return html
     */
    public function indexo()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('直播管理');

        $params = input::get();
        $filter['page_no'] = $params['pages'];
        $filter['shop_id'] = $this->shopId;
        $filter['page_size'] = $this->limit;
        $filter['status'] = $params['status'];
        $filter['item_id'] = $params['item_id'];
        $filter['title'] = $params['title'];
        $filter['order'] = 'live_start_time DESC';

        if($params['live_time']){
            $filterTimeArray = explode('-', $params['live_time']); //赠品有效期
            $filter['live_start_time'] = strtotime($filterTimeArray[0]);
            $filter['live_end_time'] = strtotime($filterTimeArray[1]);
        }

        try {
            $liveList = app::get('topshop')->rpcCall('live.list', $filter);
        } catch (\Exception $e) {
            // echo '参数错误:' . $e->getMessage();
            // $url = url::action('topshop_ctl_live_live@index');
            redirect::action('topshop_ctl_live_live@index');
        }
        $params['pages'] = time();
        //分页处理
        $pagers = array(
            'link' => url::action('topshop_ctl_live_live@index', $params),
            'current' => $liveList['current_page'],
            'use_app' => 'topshop',
            'total' => $liveList['page_total'],
            'token' => $params['pages'],
        );
        $params['status'] = $params['status'] ? : 0;
        $pagedata['params'] = $params;
        // jj($params);
        $pagedata['total'] = $liveList['data_count'];
        $pagedata['pagers'] = $pagers;
        $pagedata['data'] = $liveList['data'];
        return $this->page('topshop/live/live/list.html', $pagedata);
    }

    /**
     * 排班首页
     * @auth xinyufeng
     * @return html
     */
    public function index()
    {
        $w = date("W",strtotime("now"));
        $pagedata['w'] = $w;
        $pagedata['min_w'] = $this->_min_w;
        $pagedata['max_w'] = $this->_max_w;
        
        $_filter = array('shop_id'=>$this->shopId, 'disabled'=>0, 'order'=>'sort DESC', 'fields'=>'channel_id,channel_name');
        $channels_data = app::get('topshop')->rpcCall('live.channel.list', $_filter);
        $channels = $channels_data['data'];
        $pagedata['channels'] = $channels;
        $pagedata['channels'] = $channels;
        
        return $this->page('topshop/live/live/schedule.html', $pagedata);
    }

    /**
     * 获取第几周的开始和结束时间戳
     * @auth xinyufeng
     * @param $year
     * @param int $week
     * @return mixed
     */
    protected function _weekday($year, $week=1)
    {
        if($week < $this->_min_w || $week > $this->_max_w)
        {
            $weekday['start'] = 0;
            $weekday['end'] = 0;
            return $weekday;
        }

        $year_start = mktime(0,0,0,1,1,$year);
        $year_end = mktime(0,0,0,12,31,$year);

        // 判断第一天是否为第一周的开始
        $_weekN = intval(date('N',$year_start));
        if($_weekN===1)
        {
            $start = $year_start;//把第一天做为第一周的开始
        }
        else
        {
            if($week == 1)
            {
                $_n = 7-$_weekN;
                $year_end = strtotime("+$_n day", $year_start);
                $weekday['start'] = $year_start;
                $weekday['end'] = $year_end;
                return $weekday;
            }
            else
            {
                $start = strtotime('+1 monday', $year_start);//把第一个周一作为开始
            }
        }

        // 第几周的开始时间
        if ($week===1){
            $weekday['start'] = $start;
        }else{
            $weekday['start'] = strtotime('+'.($week-1).' monday',$start);
        }

        // 第几周的结束时间
        $weekday['end'] = strtotime('+1 sunday',$weekday['start']);
        if (date('Y',$weekday['end'])!=$year){
            $weekday['end'] = $year_end;
        }

        return $weekday;
    }

    public function edit()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('添加直播');

        //面包屑
        $this->runtimePath = array(
            ['url' => url::action('topshop_ctl_index@index'), 'title' => app::get('topshop')->_('首页')],
            ['url' => url::action('topshop_ctl_live_live@index'), 'title' => app::get('topshop')->_('直播管理')],
            ['title' => app::get('topshop')->_('编辑直播')],
        );

        if (input::get('id')) {
            $params['shop_id'] = $this->shopId;
            $params['live_id'] = input::get('id');
            $pagedata = app::get('topshop')->rpcCall('live.info', $params);
            $pagedata['live_time'] = date('Y/m/d H:i', $pagedata['live_start_time']) . '-' . date('Y/m/d H:i', $pagedata['live_end_time']);
            // jj($pagedata['live_time']);
        }

        $_filter = array('shop_id'=>$this->shopId, 'disabled'=>0, 'fields'=>'channel_id,channel_name');
        $channels_data = app::get('topshop')->rpcCall('live.channel.list', $_filter);
        $pagedata['channels'] = $channels_data['data'];
        
        return $this->page('topshop/live/live//edit.html', $pagedata);
    }

    public function save()
    {
        try {
            $data = input::get();
            if (!$data['title']) {
                $msg = '请填写标题';
                return $this->splash('error', '', $msg, true);
            }
            if (!$data['image_default_id']) {
                $msg = '请选择封面图';
                return $this->splash('error', '', $msg, true);
            }
            // if( !$data['sort'])
            // {
            //     $msg = '请填写排序';
            //     return $this->splash('error','',$msg,true);
            // }
            $data['sort'] = is_numeric($data['sort']);

            if (mb_strlen($data['intro']) > 200) {
                $msg = '简介最多200个字符';
                return $this->splash('error', '', $msg, true);
            }

            if (!$data['channel_id']) {
                $msg = '请选择频道';
                return $this->splash('error', '', $msg, true);
            }

            if (count($data['item_id']) < 1) {
                $msg = '请选择商品';
                return $this->splash('error', '', $msg, true);
            }

            if (count($data['item_id']) > 1) {
                $msg = '最多只能选择一个商品';
                return $this->splash('error', '', $msg, true);
            }
            $data['status'] = intval($data['status']) == 1 ? 1 : 2;
            $data['item_id'] = $data['item_id'][0];
            $data['shop_id'] = $this->shopId;

            $dataTimeArray = explode('-', $data['live_time']);//赠品有效期
            $data['live_start_time'] = strtotime($dataTimeArray[0]);
            $data['live_end_time'] = strtotime($dataTimeArray[1]);
            unset($data['live_time']);

            $liveModel = app::get('sysshop')->model('live');
            if ($data['live_id']) {
                $data['modified_time'] = time();
                $msg = '修改直播成功';
            } else {
                $data['created_time'] = time();
                $data['is_del'] = 2;
                $msg = '添加直播成功';
            }
            $liveModel->save($data);
            $this->sellerlog('添加/修改直播。直播名是 ' . $data['title']);
            $url = url::action('topshop_ctl_live_live@index');

            return $this->splash('success', $url, $msg, true);

        } catch (\LogicException $e) {
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg, true);
        }
    }

    public function modifyPwd()
    {
        $params['shop_id'] = $this->shopId;
        $params['seller_id'] = input::get('seller_id');
        $data = app::get('topshop')->rpcCall('account.shop.user.get', $params);
        if (!$data || $data['seller_type'] != '1') {
            $msg = '修改失败';
            return $this->splash('error', $url, $msg, true);
        }

        try {
            $setPwdData['login_password'] = input::get('login_password');
            $setPwdData['psw_confirm'] = input::get('psw_confirm');
            shopAuth::resetPwd($params['seller_id'], $setPwdData);
        } catch (\LogicException $e) {
            $msg = $e->getMessage();
            return $this->splash('error', $url, $msg, true);
        }

        $this->sellerlog('修改密码。账号是 ' . $data['login_account']);
        $msg = '修改成功';
        $url = url::action('topshop_ctl_live_live@index');
        return $this->splash('success', $url, $msg, true);
    }

    public function delete()
    {
        $data = input::get();
        try{
            if(!$data['id']){
                throw new Exception('参数错误');
            }
            $liveModel = app::get('sysshop')->model('live');
            $params['live_id'] = $data['id'];

            if(!$liveModel->delete($params)){
                throw new Exception('删除失败');
            }

        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg, true);
        }
        $this->sellerlog('删除直播。ID是 ' . $data['id']);
        $url = url::action('topshop_ctl_live_live@index');
        return $this->splash('success', $url, '删除成功', true);
    }

    /**
     * 更改直播状态
     */
    public function updateStatus(){
        $data = input::get();

        try{
            if(!in_array($data['status'],['1', '2']) || !$data['id']){
                throw new Exception('参数错误');
            }
            $liveModel = app::get('sysshop')->model('live');
            $params['status'] = $data['status'];
            $params['live_id'] = $data['id'];

            if(!$liveModel->save($params)){
                throw new Exception('修改失败');
            }

        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg, true);
        }
        $this->sellerlog('修改直播状态。ID是 ' . $data['id']);

        return $this->splash('success', '', '更新成功', true);
    }

    /**
     * 第几个周信息
     * @return string
     */
    public function weekInfo()
    {
        $w = input::get('w');
        $y_w = explode('-', date("Y-W",strtotime("now")));

        $y_w[1] = $w ? $w : $y_w[1];
        $weekday = $this->_weekday($y_w[0], $y_w[1]);

        $str = date('Y年n月j日', $weekday['start']) . '-' . date('n月j日', $weekday['end']) . "(第{$y_w[1]}周)";
        
        return $str;
    }

    /**
     * 获取周排班
     * @return mixed
     * @auth xinyufeng
     */
    public function weekSchedule()
    {
        $w = input::get('w');
        $channel_id = input::get('channel_id', 0);
        $y_w = explode('-', date("Y-W",strtotime("now")));
        $y_w[1] = $w ? $w : $y_w[1];
        $weekday = $this->_weekday($y_w[0], $y_w[1]);

        $items = array();

        $row = 'SL.live_id,SL.live_start_time,SL.live_end_time,SI.title';
        $oneday = 24*3600;
        for($d=$weekday['start']; $d<=$weekday['end']; $d+=$oneday)
        {
            $_day_arr = explode('-', date('m月d日-w', $d));
            $_start = $d;
            $_end = $_start+24*3600-1;
            $db = app::get('base')->database();
            $qb = $db->createQueryBuilder();
            $_list = $qb->select($row)
                ->from('sysshop_live', 'SL')
                ->leftJoin('SL', 'sysitem_item', 'SI', 'SL.item_id=SI.item_id')
                ->where("SL.live_start_time >= {$_start}")
                ->andWhere("SL.live_start_time <= {$_end}")
                ->andWhere("SL.shop_id = {$this->shopId}")
                ->andWhere("SL.channel_id = {$channel_id}")
                ->orderBy('live_start_time', 'ASC')->execute()->fetchAll();

            $_item = array(
                'day' => $_day_arr[0].$this->_week[$_day_arr[1]],
                'list' =>$_list,
            );
            $items[] = $_item;
        }
        
        $pagedata['items'] = $items;

        return view::make('topshop/live/live/weeks.html', $pagedata);
    }

    /**
     * 复制排班
     * @auth xinyufeng
     * @return string
     */
    public function copySchedule()
    {
        $copy_date = input::get('copy_date');
        $to_date = input::get('to_date');
        $channel_id = input::get('channel_id');

        if(!$copy_date || !$to_date)
        {
            return $this->splash('error', '', '请选择复制日期或复制到日期', true);
        }
        if($copy_date == $to_date)
        {
            return $this->splash('error', '', '复制日期与复制到日期不能相同', true);
        }

        $liveMdl = app::get('sysshop')->model('live');
        $copy_start = strtotime($copy_date);
        $copy_end = $copy_start+(24*3600-1);
        $count = $liveMdl->count(array('channel_id'=>$channel_id,'live_start_time|bthan'=>$copy_start,'live_start_time|sthan'=>$copy_end));
        if(!$count)
        {
            return $this->splash('error', '', '此复制日期没有排班请重选', true);
        }

        $to_start = strtotime($to_date);
        $to_end = $to_start+(24*3600-1);
        $i = $to_start-$copy_start;
        if($i>0)
        {
            $i = "+{$i}";
        }
        $sql = "INSERT INTO sysshop_live (shop_id,title,intro,item_id,image_default_id,live_url,demand_dir,live_start_time,live_end_time,sort,created_time,modified_time,status,channel_id) SELECT shop_id,title,intro,item_id,image_default_id,live_url,demand_dir,live_start_time {$i} as live_start_time,live_end_time {$i} as live_end_time,sort,unix_timestamp(now()) as created_time,unix_timestamp(now()) as modified_time,status,channel_id from sysshop_live WHERE channel_id={$channel_id} AND shop_id={$this->shopId} AND live_start_time >= {$copy_start} AND live_start_time <= {$copy_end}";
        $liveMdl->delete(array('channel_id'=>$channel_id,'live_start_time|bthan'=>$to_start,'live_start_time|sthan'=>$to_end));

        $db = app::get('base')->database();
        $row = $db->exec($sql);

        if(!$row) return $this->splash('error', '', '复制失败', true);
        $url = url::action('topshop_ctl_live_live@index');
        return $this->splash('success', $url, '复制成功', true);
    }
}

