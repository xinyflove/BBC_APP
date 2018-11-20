<?php

class topwap_ctl_member_lijin extends topwap_ctl_member {

    public $limit = 20;
    public $shop_id;
    function __construct($app=null)
    {
        parent::__construct($app);
        $this->shop_id = app::get('sysshop')->getConf('sysshop.tvshopping.shop_id');
    }

    public function index()
    {
        $filter = input::get();
        $pagedata = $this->__getLijins($filter);
        // add start 王衍生 20170928  注释掉
        // if (!$pagedata) return redirect::back();
        // add end 王衍生 20170928
        $pagedata['title'] = app::get('topwap')->_('我的礼金');
        return $this->page('topwap/member/lijin/index.html',$pagedata);
    }

    // ajax输出数据
    public function ajaxLijin()
    {
        $filter = input::get();
        try {
            $pagedata = $this->__getLijins($filter);
            $data['html'] = view::make('topwap/member/lijin/list.html',$pagedata)->render();
            $data['pages'] = $pagedata['pages'];
            $data['length'] = count($pagedata['lijindata']);
            $data['success'] = true;

        } catch (Exception $e) {
            $msg = $e->getMessage();
            return $this->splash('error', null, $msg, true);
        }
        return $this->splash('success', null, $data, true);

        // return response::json($data);exit;
    }

    public function __getLijins($filter)
    {
        if(!$filter['pages'])
        {
            $filter['pages'] = 1;
        }
        $pageSize = $this->limit;
        $current = ($filter['pages'] >=1 || $filter['pages'] <= 100) ? $filter['pages'] : 1;

        $params = array(
                'page_no' => intval($current),
                'page_size' => intval($pageSize),
                // 'orderBy' => 'modified_time desc',
                'user_id' => userAuth::id(),
                'shop_id' => $this->shop_id,
        );
        $data = app::get('topwap')->rpcCall('user.lijin.list',$params);
        // jj($data);
        //总页数(数据总数除每页数量)
        $pagedata['userlijin'] = $data['datalist']['user'];
        $pagedata['lijindata'] = $data['datalist']['lijin'];
        if($data['totalnum'] > 0) $total = ceil($data['totalnum']/$pageSize);
        // if($total<$filter['pages']) return array();
        $pagedata['count'] = $data['totalnum'];
        $pagedata['pages'] = $filter['pages'];
        $pagedata['pagers'] = array(
                'link'=>'',
                'current'=>$current,
                'total'=>$total,
        );
        return $pagedata;
    }

}

