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

    /**
     * 编辑计价规则
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
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

        $custom_conf_html = CUSTOM_CORE_DIR . '/topshop/view/shop/dlycorprule/' . strtolower($corpData['corp_code']) . '.html';

        if(is_file($custom_conf_html)){
            $pagedata['is_custom'] = strtolower($corpData['corp_code']);
        }else{
            $pagedata['is_custom'] = false;
        }
        $this->contentHeaderTitle = app::get('topshop')->_('编辑物流公司计价规则');
        return $this->page('topshop/shop/editdlycorprule.html', $pagedata);
    }

    /**
     * 保存计价规则
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function saveRule()
    {
        $params = input::get();
        // jj($postdata);
        $shop_id = $this->shopId;
        // $corpData = app::get('topshop')->rpcCall('shop.dlycorp.getinfo',['shop_id'=>$this->shopId, 'corp_id' => $postdata['corp_id']]);
        $params['shop_id'] = $this->shopId;

        if($params['base_conf']){
            $params['base_conf'] = json_encode($params['base_conf']);
        }

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

    /**
     * 京东物流授权页
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function jdwlAuthorize()
    {
        // 本店铺的京东物流配置
        $corp_config = app::get('topshop')->rpcCall('shop.dlycorp.getinfo',['shop_id' => $this->shopId, 'corp_code' => 'JDWL']);

        if(empty(input::get('code')) && (input::get('error') != 'access_denied')){

            $_SESSION['jdwl_state'] = md5(uniqid(rand(), TRUE));

            $getUrl = 'https://oauth.jd.com/oauth/authorize?' . http_build_query([
                'client_id' => $corp_config['base_conf']['app_key'],
                'redirect_uri' => url::action('topshop_ctl_shop_dlycorp@jdwlAuthorize'),
                'response_type' => 'code',
                // 'scope' => 'read',
                'state' => $_SESSION['jdwl_state'],
            ]);
            return $this->splash('success', $getUrl, '授权开始', true);
        }

        $state = input::get('state');
        $code = input::get('code');

        if(($_SESSION['jdwl_state'] != $state) || empty($code) ){
            return $this->page('topshop/error/error.html', []);
        }
        // $state = md5(uniqid(rand(), TRUE));
        $args = ['client_id' => $corp_config['base_conf']['app_key'],
                 'client_secret' => $corp_config['base_conf']['app_secret'],
                 'redirect_uri'=> url::action('topshop_ctl_shop_dlycorp@jdwlAuthorize'),
                 'grant_type' => 'authorization_code',
                //  'state' => $state,
                 'code' => $code];
        try
        {
            // $msg = client::get('https://oauth.jd.com/oauth/token', ['query' => $args])->json();
            $msg = client::get('https://oauth.jd.com/oauth/token', ['query' => $args])->getBody();
            $msg = iconv('GB2312', 'UTF-8', $msg);
            $msg = json_decode($msg, true);
        }
        //ClientException
        catch (ClientException $e)
        {
            $msg = $e->getResponse()->json();
            // throw new \LogicException("error :" . $msg['errcode']. "msg  :". $msg['errmsg']);
            return $this->page('topshop/error/error.html', []);
        }
        $h_key = 'authorize:JDWL:shop_id_' . $this->shopId;
        foreach($msg as $key => $value){
            redis::scene('syslogistics')->hset($h_key, $key, $value);
        }

        redis::scene('syslogistics')->expire($h_key, $msg['expires_in']);
        $time = date('Y-m-d H:i:s', time() + ($msg['expires_in']));

        echo "<script>alert('授权成功！有效期至{$time}');window.close();</script>";
        exit;
    }
}
