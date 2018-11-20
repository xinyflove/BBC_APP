<?php
class topshop_ctl_shop_dlycorp extends topshop_controller{
    public $limit = 1000;
    public function index()
    {
        $postFilter = input::get();
        $page = $postFilter['pages'] ? intval($postFilter['pages']) : 1;
        //获取我签约的物流
        $dlycorp = app::get('topshop')->rpcCall('shop.dlycorp.getlist',['shop_id'=>$this->shopId]);
        $dlycorp = array_bind_key($dlycorp['list'],'corp_id');
        //获取全部物流

        $params = array(
            'fields'=>'corp_id,corp_code,corp_name',
            'page_no' => intval($page),
            'page_size' => intval($this->limit),
        );
        $corpData = app::get('topshop')->rpcCall('logistics.dlycorp.get.list',$params);
        $data = $corpData['data'];
        foreach( $data as $key=>$value)
        {
            if($dlycorp[$value['corp_id']])
            {
                $data[$key]['is_open'] = true;
            }
        }
        $pagedata['page'] = $page;
        $pagedata['corpData'] = $data;
        $pagedata['count'] = $corpData['total_found'];
        $pagedata['pagers'] = $this->__pager($postFilter,$page,$corpData['total_found']);

        $this->contentHeaderTitle = app::get('topshop')->_('物流公司');
        return $this->page('topshop/shop/dlycorp.html', $pagedata);
    }

    /**
     * @brief 签约物流公司，最多5个
     *
     * @return
     */
    public function signDlycorp()
    {
        $postdata['corp_id'] = input::get('corp_id');
        $postdata['shop_id'] = $this->shopId;

        try{
            app::get('topshop')->rpcCall('shop.dlycorp.save',$postdata);
        }catch(Exception $e){
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }
        $this->sellerlog('开启物流公司');
        $msg = app::get('topshop')->_('保存成功');
        $url = url::action('topshop_ctl_shop_dlycorp@index',['pages'=>input::get('pages')]);
        return $this->splash('success',$url,$msg,true);
    }

    public function cancelDlycorp()
    {
        $postdata = input::get();
        $postdata['shop_id'] = $this->shopId;
        try{
            app::get('topshop')->rpcCall('shop.dlycorp.remove',$postdata);
        }catch(Exception $e){
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }
        $this->sellerlog('取消物流公司');
        $msg = app::get('topshop')->_('保存成功');
        $url = url::action('topshop_ctl_shop_dlycorp@index');
        return $this->splash('success',$url,$msg,true);
    }

    private function __pager($postFilter,$page,$count)
    {
        $postFilter['pages'] = time();
        $total = ceil($count/$this->limit);
        $pagers = array(
            'link'=>url::action('topshop_ctl_shop_dlycorp@index',$postFilter),
            'current'=>$page,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>time(),
        );
        return $pagers;

    }

    public function editRule()
    {
        $postFilter = input::get();
        //获取我签约的物流
        $corpData = app::get('topshop')->rpcCall('shop.dlycorp.getinfo',['shop_id'=>$this->shopId, 'corp_id' => $postFilter['corp_id']]);
        //获取全部物流
        // jj($corpData);
        if($corpData['valuation']=='1'){
            $corpData['byweight']['fee_conf'] = $corpData['fee_conf'];
        }
        if($corpData['valuation']=='2'){
            $corpData['bynum']['fee_conf'] = $corpData['fee_conf'];
        }
        if($corpData['valuation']=='3'){
            $corpData['bymoney']['fee_conf'] = $corpData['fee_conf'];
        }
        unset($corpData['fee_conf']);
        $pagedata['corpData'] = $corpData;
        $this->contentHeaderTitle = app::get('topshop')->_('编辑物流公司计价规则');
        return $this->page('topshop/shop/editdlycorprule.html', $pagedata);
    }

    public function saveRule()
    {
        $params = input::get();
        // jj($postdata);
        $shop_id = $this->shopId;
        // $corpData = app::get('topshop')->rpcCall('shop.dlycorp.getinfo',['shop_id'=>$this->shopId, 'corp_id' => $postdata['corp_id']]);
        $params['shop_id'] = $this->shopId;

        if($params['valuation']=='1' && $params['fee_conf']){
            $params['fee_conf'] = json_encode($params['fee_conf']);
        }
        try{
            app::get('topshop')->rpcCall('shop.dlycorp.save',$params);
        }catch(Exception $e){
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }
        $this->sellerlog('开启物流公司计价规则');
        $msg = app::get('topshop')->_('保存成功');
        $url = url::action('topshop_ctl_shop_dlycorp@index',['pages'=>input::get('pages')]);
        return $this->splash('success',$url,$msg,true);
    }
}
