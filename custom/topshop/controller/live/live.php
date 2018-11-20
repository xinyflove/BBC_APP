<?php

class topshop_ctl_live_live extends topshop_controller
{
    public $limit = 10;

    public function index()
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

        return $this->splash('success', '', '删除成功', true);
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
}

