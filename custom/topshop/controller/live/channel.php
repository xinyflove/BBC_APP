<?php

class topshop_ctl_live_channel extends topshop_controller
{
    public $limit = 10;

    public function index()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('频道管理');

        $params = input::get();
        $filter['page_no'] = $params['pages'];
        $filter['shop_id'] = $this->shopId;
        $filter['page_size'] = $this->limit;
        $filter['order'] = 'sort DESC';

        try {
            $channelList = app::get('topshop')->rpcCall('live.channel.list', $filter);
        } catch (\Exception $e) {
            redirect::action('topshop_ctl_live_channel@index');
        }

        //分页处理
        $params['pages'] = time();
        $pagers = array(
            'link' => url::action('topshop_ctl_live_channel@index', $params),
            'current' => $channelList['current_page'],
            'use_app' => 'topshop',
            'total' => $channelList['page_total'],
            'token' => $params['pages'],
        );

        $pagedata['count'] = $channelList['data_count'];
        $pagedata['pagers'] = $pagers;
        $pagedata['data'] = $channelList['data'];
        
        return $this->page('topshop/live/channel/list.html', $pagedata);
    }

    public function edit()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('添加频道');

        //面包屑
        $this->runtimePath = array(
            ['url' => url::action('topshop_ctl_index@index'), 'title' => app::get('topshop')->_('首页')],
            ['url' => url::action('topshop_ctl_live_channel@index'), 'title' => app::get('topshop')->_('频道管理')],
            ['title' => app::get('topshop')->_('编辑频道')],
        );

        if (input::get('id')) {
            $this->contentHeaderTitle = app::get('topshop')->_('修改频道');
            $params['shop_id'] = $this->shopId;
            $params['channel_id'] = input::get('id');
            $pagedata = app::get('topshop')->rpcCall('live.channel.info', $params);
        }

        return $this->page('topshop/live/channel/edit.html', $pagedata);
    }

    public function save()
    {
        try {
            $data = input::get();
            if (!$data['channel_name']) {
                $msg = '请填写频道名称';
                return $this->splash('error', '', $msg, true);
            }

            $data['sort'] = $data['sort'] ? $data['sort'] : 0;
            $data['disabled'] = $data['disabled'] ? $data['disabled'] : 0;
            $data['shop_id'] = $this->shopId;

            $channelModel = app::get('sysshop')->model('channel');
            if ($data['channel_id']) {
                $msg = '修改成功';
            } else {
                $data['created_time'] = time();
                $msg = '添加成功';
            }

            $channelModel->save($data);
            $this->sellerlog('添加/修改频道。频道名称是 ' . $data['channel_name']);
            $url = url::action('topshop_ctl_live_channel@index');

            return $this->splash('success', $url, $msg, true);

        } catch (\LogicException $e) {
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg, true);
        }
    }

    public function delete()
    {
        $data = input::get();
        try{
            if(!$data['id']){
                throw new Exception('参数错误');
            }
            $channelModel = app::get('sysshop')->model('channel');
            $params['channel_id'] = $data['id'];

            if(!$channelModel->delete($params)){
                throw new Exception('删除失败');
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg, true);
        }
        $this->sellerlog('删除频道。ID是 ' . $data['id']);

        return $this->splash('success', '', '删除成功', true);
    }

    /**
     * 更改频道启用状态
     */
    public function updateDisabled(){
        $data = input::get();

        try{
            if(!in_array($data['disabled'],['0', '1']) || !$data['id']){
                throw new Exception('参数错误');
            }
            $channelModel = app::get('sysshop')->model('channel');
            $params['disabled'] = $data['disabled'];
            $params['channel_id'] = $data['id'];

            if(!$channelModel->save($params)){
                throw new Exception('修改失败');
            }

        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg, true);
        }
        $this->sellerlog('修改频道启用状态。ID是 ' . $data['id']);

        return $this->splash('success', '', '修改成功', true);
    }
}

