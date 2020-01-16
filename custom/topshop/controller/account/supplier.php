<?php

// 小超
// 供应商管理
// 2017/7/24
// wanghaichao@tvplaza.cn
class topshop_ctl_account_supplier extends topshop_controller
{
    protected $agent_activity_type = [
        'ALL_DISCOUNT'=>'全场打折'
    ];

    protected $bank_array = ['qd_bank'=>'青岛银行', 'pa_bank'=>'平安银行', 'ns_bank'=>'农商银行'];

    protected $supplierPageLimit = 20;

    public function index()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('供应商账号管理');

        $params=input::get();
        $params['shop_id']=$this->shopId;
        $filter=$this->__chekSupplierFilter($params);
        $count=app::get('sysshop')->model('supplier')->count($filter);

        $page=$params['pages']?$filter['pages']:1;

        $limit=$this->supplierPageLimit;
        $pageTotal=ceil($count/$limit);
        $currentPage =($pageTotal < $page) ? $pageTotal : $page;
        $offset = ($currentPage-1) * $limit;

        $params['page_no']=$offset;
        $params['page_size']=$limit;
        $params['pages']=time();

        $data = app::get('topshop')->rpcCall('supplier.shop.list.page', $params);

        if($this->isHmShop) {
            $supplier_ids = array_column($data, 'supplier_id');
            if(!empty($supplier_ids)) {
                $objMdlHmSupplier = app::get('sysshop')->model('hm_supplier');
                $hm_supplier_list = $objMdlHmSupplier->getList('*', ['supplier_id|in' => $supplier_ids]);
                $hm_supplier_list = array_bind_key($hm_supplier_list, 'supplier_id');
                foreach($data as &$v) {
                    $v['is_synced'] = $hm_supplier_list[$v['supplier_id']]['is_synced'];
                }
            }
        }
        $pagers = array(
            'link'=>url::action('topshop_ctl_account_supplier@index',$params),
            'current'=>$currentPage,
            'use_app' => 'topshop',
            'total'=>$pageTotal,
            'token'=>time(),
        );
        $pagedata['data'] = $data;
        $pagedata['shopInfo'] = $this->shopInfo;
        $pagedata['pagers']=$pagers;
        $pagedata['supplier_name']=$params['supplier_name'];
        $pagedata['company_name']=$params['company_name'];
        $pagedata['mobile']=$params['mobile'];


        $pagedata['is_hm_shop'] = $this->isHmShop;
        return $this->page('topshop/account/supplier/list.html', $pagedata);
    }

    /**
     * 线下店列表
     *
     * @return html
     */
    public function agentShopList()
    {
        $page = input::get('page', 1);
        $this->contentHeaderTitle = app::get('topshop')->_('线下店列表');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_index@index'),
                'title' => app::get('topshop')->_('首页')
            ],
            [
                'url' => url::action('topshop_ctl_account_supplier@index'),
                'title' => app::get('topshop')->_('供应商管理')
            ],
            [
                'title' => app::get('topshop')->_('线下店管理')
            ]
        );
        $pagedata = array();
        $pagedata['supplier_id'] = input::get('supplier_id');
        $params = array();
        $params['shop_id'] = $this->shopId;
        $params['supplier_id'] = $pagedata['supplier_id'];
        $params['page_no'] = $page;
        try {
            $agentShopData = app::get('topshop')->rpcCall('supplier.agent.shop.list', $params);
            $supplierList = app::get('topshop')->rpcCall('supplier.shop.list', ['shop_id' => $this->shopId,'is_audit'=>'PASS']);
            $supplierList = array_bind_key($supplierList, 'supplier_id');
        } catch (\Exception $e) {
            echo '参数错误:' . $e->getMessage();
            die;
        }
        foreach ($agentShopData['data'] as &$v) {
            $v['supplier_name'] = $supplierList[$v['supplier_id']]['supplier_name'];
            if ($v['type'] === 'HOME') {
                $v['type'] = '总店';
            } else {
                $v['type'] = '分店';
            }
        }
        //分页处理
        $pagers = array(
            'link' => url::action('topshop_ctl_account_supplier@agentShopList', ['supplier_id' => $pagedata['supplier_id'], 'page' => time()]),
            'current' => $agentShopData['current_page'],
            'use_app' => 'topshop',
            'total' => $agentShopData['page_total'],
            'token' => time(),
        );
        $pagedata['total'] = $agentShopData['data_count'];
        $pagedata['pagers'] = $pagers;
        $pagedata['data'] = $agentShopData;
        $pagedata['shopInfo'] = $this->shopInfo;
        return $this->page('topshop/account/supplier/shop_list.html', $pagedata);
    }

    /**
     * 线下店搜索
     */
    public function agentShopSearch()
    {
        $page = input::get('page', 1);
        $name = input::get('name');
        $this->contentHeaderTitle = app::get('topshop')->_('线下店列表');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_index@index'),
                'title' => app::get('topshop')->_('首页')
            ],
            [
                'url' => url::action('topshop_ctl_account_supplier@index'),
                'title' => app::get('topshop')->_('供应商管理')
            ],
            [
                'title' => app::get('topshop')->_('线下店管理')
            ]
        );
        $pagedata = array();
        $pagedata['supplier_id'] = input::get('supplier_id');
        $params = array();
        $params['shop_id'] = $this->shopId;
        $params['supplier_id'] = $pagedata['supplier_id'];
        $params['page_no'] = $page;
        $params['filter'] = [
            'name|has' => $name
        ];
        try {
            $agentShopData = app::get('topshop')->rpcCall('supplier.agent.shop.list', $params);
            $supplierList = app::get('topshop')->rpcCall('supplier.shop.list', ['shop_id' => $this->shopId,'is_audit'=>'PASS']);
            $supplierList = array_bind_key($supplierList, 'supplier_id');
        } catch (\Exception $e) {
            echo '参数错误:' . $e->getMessage();
            die;
        }
        foreach ($agentShopData['data'] as &$v) {
            $v['supplier_name'] = $supplierList[$v['supplier_id']]['supplier_name'];
            if ($v['type'] === 'HOME') {
                $v['type'] = '总店';
            } else {
                $v['type'] = '分店';
            }
        }
        //分页处理
        $pagers = array(
            'link' => url::action('topshop_ctl_account_supplier@agentShopList', ['supplier_id' => $pagedata['supplier_id'], 'page' => time()]),
            'current' => $agentShopData['current_page'],
            'use_app' => 'topshop',
            'total' => $agentShopData['page_total'],
            'token' => time(),
        );
        $pagedata['total'] = $agentShopData['data_count'];
        $pagedata['pagers'] = $pagers;
        $pagedata['data'] = $agentShopData;
        $pagedata['search_keywords']['name'] = $name;
        return $this->page('topshop/account/supplier/shop_list.html', $pagedata);
    }

    /**
     * 线下店编辑
     * @return html
     */
    public function agentShop()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('添加线下店');

        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_index@index'),
                'title' => app::get('topshop')->_('首页')
            ],
            [
                'url' => url::action('topshop_ctl_account_supplier@index'),
                'title' => app::get('topshop')->_('供应商管理')
            ],
            [
                'title' => app::get('topshop')->_('线下店管理')
            ]
        );
        $pagedata = array();


        if (input::get('agent_shop_id')) {
            $params['agent_shop_id'] = input::get('agent_shop_id');
            $data = app::get('topshop')->rpcCall('supplier.agent.shop.get', $params);
            $data['carouse_image_list'] = explode(',',$data['carouse_image_list']);
            $pagedata['supplier_id'] = $data['supplier_id'];
            if ($data) {
                $pagedata['data'] = $data;
            }
//            dump($pagedata);die;
        } else {
            $pagedata['supplier_id'] = input::get('supplier_id');
            $pagedata['supplier'] = app::get('topshop')->rpcCall('supplier.shop.get', ['supplier_id'=>$pagedata['supplier_id']]);
            //设置线下店账号：
            while (true)
            {
                static $i = 0;
                $rand = $this->getRandStr(5);
                $confirm_password = app::get('syssupplier')->model('agent_shop')->getRow('agent_shop_id',['login_account'=>$rand]);
                if(!$confirm_password)
                {
                    break;
                }
                $i++;
                if($i>100)
                {
                    echo '该供应商不能大于1000家分店!';die;
                }
            }

            $pagedata['supplier']['login_account_default'] = $rand.$pagedata['supplier']['code'];
            $data = [];
            foreach($this->bank_array as $bank=>$bank_name)
            {
                if($bank === 'ns_bank')
                {
                    $industry_model = app::get('syssupplier')->model($bank.'_industry_category');
                    $list = $industry_model->getList('*',['disabled'=>0, 'deleted'=>0]);
                    $data['industry_category'][$bank] = $list;
                }
            }
            $pagedata['data'] = $data;
        }
        $pagedata['shop'] = $this->shopInfo;
        //线下店分类获取
        $agentCat = app::get('topshop')->rpcCall('supplier.agent.category.list');
        $pagedata['agentCatData'] = $agentCat['data'];
//        dump($pagedata);die;
        return $this->page('topshop/account/supplier/shop.html', $pagedata);
    }

    private function getRandStr($len)
    {
        $chars = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
            "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
            "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
            "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
            "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
            "3", "4", "5", "6", "7", "8", "9"
        );
        $charsLen = count($chars) - 1;
        shuffle($chars);
        $output = "";
        for ($i=0; $i<$len; $i++)
        {
            $output .= $chars[mt_rand(0, $charsLen)];
        }
        return $output;
    }

    /**
     * 线下店收款二维码
     * @return html
     */
    public function agentShopQr()
    {
        $agent_shop_id = input::get('agent_shop_id');
        if (!$agent_shop_id) {
            echo 'agent_shop_id不能为空';
            die;
        }
        try {
            $data = app::get('topshop')->rpcCall('supplier.agent.shop.get', ['agent_shop_id' => $agent_shop_id]);
        } catch (\Exception $e) {
            echo $e->getMessage();
            die;
        }
        $pagedata = array();
        $data['qr_code'] = base_storager::modifier($data['qr_code']);
        $pagedata['data'] = $data;
        return $this->page('topshop/account/supplier/shopqr.html', $pagedata);
    }

    public function agentShopQrSave()
    {
        $input = input::get();
        $validator = validator::make(
            [
                $input['img'],
                $input['qr'],
                $input['width'],
                $input['height'],
                $input['agent_shop_id']
            ],
            [
                'required',
                'required',
                'required',
                'required',
                'required'
            ],
            [
                '背景图不能为空',
                '二维码不能为空',
                '宽度不能为空',
                '长度不能为空',
                '线下店id不能为空'
            ]);
        if ($validator->fails())
        {
            $messages = $validator->messagesInfo();
            foreach( $messages as $error )
            {
                return $this->splash('error',null,$error[0]);
            }
        }

//        $input['qr'] = request::server()['DOCUMENT_ROOT'].$input['qr'];
//        $input['img'] = request::server()['DOCUMENT_ROOT'].$input['img'];
        $res = preg_match('/(.+)(\.)(png|jpg)/',$input['img'],$img_arr);
        $input['img_type'] = $img_arr[3];
        if(!in_array($img_arr[3],['jpg','png']))
        {
            return $this->splash('error',null,'只能上传png或者jpg类型的图片');
        }
        if(false === strpos($input['img'],'http://'))
        {
            $input['img'] = base_storager::modifier($input['img']);
        }
        if(false === strpos($input['qr'],'http://'))
        {
            $input['qr'] = base_storager::modifier($input['qr']);
        }
//        if(!file_exists($input['img']))
//        {
//            return $this->splash('error',null,'本地背景图不存在');
//        }
//        if(!file_exists($input['qr']))
//        {
//            return $this->splash('error',null,'本地二维码不存在');
//        }
//        var_dump($input);die;
//        if(!file_get_contents($input['img'],null,null,-1,1) ? true : false)
//        {
//            return $this->splash('error',null,'背景图不存在');
//        }
//        if(!file_get_contents($input['qr'],null,null,-1,1) ? true : false)
//        {
//            return $this->splash('error',null,'二维码不存在');
//        }
        $qr_lib = Kernel::single(syssupplier_qrcode::class);
        $qr_lib->qrProduct($input);
        return $this->splash('success',null,'合成图片成功！');
    }

    /**
     * 插入一条银行信息
     */
    public function bank_insert($params, $agent_shop_id)
    {
        $this->bank_info_handle($params);
        foreach($this->bank_array as $bank=>$bank_name)
        {
            $bank_data = $params[$bank];
            if(!$bank_data['card_number'])
            {
                unset($bank_data['is_public']);
            }
            $bank_data['agent_shop_id'] = $agent_shop_id;
            $bank_data['modified_time'] = time();
            $bank_model = app::get('syssupplier')->model($bank);
            $agent_account = $bank_model->count(['agent_shop_id' => $agent_shop_id, 'disabled'=>0, 'deleted'=>0]);
            if($agent_account > 0)
            {
                throw new Exception('此线下店'.$bank_name.'的账户信息已经存在');
            }

            $bank_model->insert($bank_data);
        }
    }
    /**
     * 银行信息处理
     */
    public function bank_info_handle(&$params)
    {
        $params['qd_bank']['card_number'] = trim($params['qd_bank']['card_number']);
        $params['pa_bank']['card_number'] = trim($params['pa_bank']['card_number']);
        $params['ns_bank']['card_number'] = trim($params['ns_bank']['card_number']);

        $params['ns_bank']['twolecomm_id'] = trim($params['ns_bank']['twolecomm_id']);

        $params['qd_bank']['mer_id'] = trim($params['qd_bank']['mer_id']);
        $params['qd_bank']['mer_tid'] = trim($params['qd_bank']['mer_tid']);

        $qd_bank_result = ($params['qd_bank']['card_number'] && $params['qd_bank']['card_account_name'] && $params['qd_bank']['deposit_bank'] && $params['qd_bank']['mer_id'] && $params['qd_bank']['mer_tid']);
        $pa_bank_result = ($params['pa_bank']['card_number'] && $params['pa_bank']['card_account_name'] && $params['pa_bank']['deposit_bank']);
        $ns_bank_result = ($params['ns_bank']['card_number'] && $params['ns_bank']['card_account_name'] && $params['ns_bank']['deposit_bank'] && $params['ns_bank']['category_code']);
        if(!$qd_bank_result && !$pa_bank_result && !$ns_bank_result)
        {
            throw new Exception('单个银行信息填写不完整，不能提交');
        }

        $open_num = 0;
        foreach($this->bank_array as $bank=>$bank_name)
        {
            if($params[$bank]['is_open'] === 'on')
            {
                $open_num += 1;
            }
            else
            {
                $params[$bank]['is_open'] = 'off';
            }
            if($params[$bank]['card_number'])
            {
                if(!preg_match('/^\d*$/',$params[$bank]['card_number']) || !(strlen($params[$bank]['card_number'])>=16 && strlen($params[$bank]['card_number'])<=19))
                {
                    throw new Exception($bank_name.'卡号不正确');
                }
            }

            if($params[$bank]['twolecomm_id'])
            {
                if(!preg_match('/^\d*$/',$params[$bank]['card_number']))
                {
                    throw new Exception($bank_name.'卡号格式不正确');
                }
            }
        }
        if($open_num > 1)
        {
            throw new Exception('不能同时开启多加银行收款');
        }
    }
    /**
     * 更新信息的处理
     */
    public function update_bank_info_handle($params)
    {
        $this->bank_info_handle($params);

        $agent_shop_id = $params['agent_shop_id'];

        foreach($this->bank_array as $bank=>$bank_name)
        {
            $bank_model = app::get('syssupplier')->model($bank);
            $old_bank_info = $bank_model->getRow('*', ['agent_shop_id' => $agent_shop_id, 'disabled'=>0, 'deleted'=>0]);

            $update_data = $params[$bank];
            if(!$update_data['card_number'])
            {
                unset($update_data['is_public']);
            }

            $update_data['modified_time'] = time();
            if(empty($old_bank_info))
            {
                $update_data['agent_shop_id'] = $agent_shop_id;
                $bank_model->insert($update_data);
                continue;
            }
            if($old_bank_info['deposit_bank'] && ($params[$bank]['deposit_bank'] != $old_bank_info['deposit_bank']))
            {
                throw new Exception($bank_name.'的开户行不能修改');
            }
            if($old_bank_info['card_number'] && ($params[$bank]['card_number'] != $old_bank_info['card_number']))
            {
                throw new Exception($bank_name.'的卡号不能修改');
            }
            if($old_bank_info['card_account_name'] && ($params[$bank]['card_account_name'] != $old_bank_info['card_account_name']))
            {
                throw new Exception($bank_name.'的账户名称不能修改');
            }
            if($old_bank_info['is_public'] && ($params[$bank]['is_public'] != $old_bank_info['is_public']))
            {
                throw new Exception($bank_name.'的账户类型不能修改');
            }
            if($old_bank_info['twolecomm_id'] && ($params[$bank]['twolecomm_id'] != $old_bank_info['twolecomm_id']))
            {
                throw new Exception($bank_name.'的二级商户标识不能修改');
            }
            if($old_bank_info['category_code'] && ($params[$bank]['category_code'] != $old_bank_info['category_code']))
            {
                throw new Exception($bank_name.'的类目不能修改');
            }
            if($old_bank_info['mer_tid'] && ($params[$bank]['mer_tid'] != $old_bank_info['mer_tid']))
            {
                throw new Exception($bank_name.'的终端号不能修改');
            }
            if($old_bank_info['mer_id'] && ($params[$bank]['mer_id'] != $old_bank_info['mer_id']))
            {
                throw new Exception($bank_name.'的商户号不能修改');
            }

            $bank_model->update($update_data, ['agent_shop_id' => $agent_shop_id]);
        }
    }
    /**
     * 二维码下载
     */
    public function qrUpload()
    {
        $filename = input::get('file');
        //设置头信息
        if(input::get('type') == 'yulan')
        {
            $filename = 'http://www.tvplaza.cn/'.$filename;
        }
//        $filename = request::server()['DOCUMENT_ROOT'].$filename;
        header('Content-Disposition:attachment;filename=' . basename($filename));
        header('Content-Length:' . filesize($filename));
        //读取文件并写入到输出缓冲
        readfile($filename);
    }

    /**
     * 线下店保存
     */
    public function agentShopSave()
    {
        $params = input::get();
        $agent_shop_id = 0;
        $supplier_id = $params['supplier_id'];
        $validator = validator::make(
            [$params['id_number']],
            ['Idnumber'],
            ['身份证号未填写或者格式不正确']
        );
        if($validator->fails())
        {
            $err_msg = $validator->messagesInfo();
            return $this->splash('error', '', $err_msg, true);
        }
        //$params['agent_img_src'] = $params['carouse_image_list'][0];
        $params['carouse_image_list'] = implode(',',$params['carouse_image_list']);
        $db = app::get('sysshop')->database();
        $db->beginTransaction();
        try {
            if ($params['agent_shop_id']) {
                //不允许修改登录用户名：
                if(isset($params['login_account']) && !empty($params['login_account']))
                {
                    unset($params['login_account']);
                }
                //更新时的银行配置信息处理
                $this->update_bank_info_handle($params);
                $params['shop_id'] = $this->shopId;
                app::get('topshop')->rpcCall('supplier.agent.shop.update', $params);
                $msg = '修改线下店成功';
                $url = url::action('topshop_ctl_account_supplier@agentShopList',['supplier_id'=>$supplier_id]);
            } else {
//                $params = input::get();
                unset($params['agent_shop_id']);
                //所配银行配置信息处理
                $params['shop_id'] = $this->shopId;
                $params['offline_multi'] = $this->shopInfo['offline_multi'];
                if($params['login_password']==''){
                    $params['login_password'] = 'qdgd8888';
                }

                $agent_shop_id = app::get('topshop')->rpcCall('supplier.agent.shop.add', $params);
                $this->bank_insert($params, $agent_shop_id);
                $msg = '创建线下店成功';
                $url = url::action('topshop_ctl_account_supplier@agentShopList',['supplier_id'=>$supplier_id]);
            }
        } catch (Exception $e) {
            $db->rollback();
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg, true);
        }
        $db->commit();

        return $this->splash('success', $url, $msg, true);
    }

    /**
     * 线下店删除
     * @return string
     */
    public function agentShopDel()
    {
        $agentShopId = input::get('agent_shop_id', false);
        if (!$agentShopId) {
            $msg = '删除失败';
            return $this->splash('error', '', $msg, true);
        }
        try {
            $params['agent_shop_id'] = $agentShopId;
            app::get('topshop')->rpcCall('supplier.agent.shop.delete', $params);
        } catch (\LogicException $e) {
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg, true);
        }

        $msg = '删除成功';
        $url = url::action('topshop_ctl_account_supplier@index');
        return $this->splash('success', $url, $msg, true);
    }

    /**
     * 线下店活动列表
     */
    public function agentActivityList()
    {
        $page = input::get('page', 1);
        $agent_shop_id = input::get('agent_shop_id');
        $this->contentHeaderTitle = app::get('topshop')->_('线下店活动列表');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_index@index'),
                'title' => app::get('topshop')->_('首页')
            ],
            [
                'url' => url::action('topshop_ctl_account_supplier@index'),
                'title' => app::get('topshop')->_('供应商管理')
            ],
            [
                'title' => app::get('topshop')->_('线下店管理')
            ]
        );
        $pagedata = array();
        $params = array();
        $params['page_no'] = $page;
        try {
            $activityData = app::get('topshop')->rpcCall('supplier.agent.activity.list',[
                'page_no'=>$page,
                'agent_shop_id'=>$agent_shop_id,
                'deleted'=>0
            ]);
        } catch (\Exception $e) {
            echo '参数错误:' . $e->getMessage();
            die;
        }
        foreach ($activityData['data'] as $k=>$v)
        {
            $activityData['data'][$k]['activity_type_name'] = $this->agent_activity_type[$v['activity_type']];
        }
        //分页处理
        $pagers = array(
            'link' => url::action('topshop_ctl_account_supplier@agentActivityList', ['agent_shop_id' => $pagedata['agent_shop_id'], 'page' => time()]),
            'current' => $activityData['current_page'],
            'use_app' => 'topshop',
            'total' => $activityData['page_total'],
            'token' => time(),
        );
        $pagedata['total'] = $activityData['data_count'];
        $pagedata['pagers'] = $pagers;
        $pagedata['agent_shop_id'] = $agent_shop_id;
        $pagedata['data'] = $activityData;
        return $this->page('topshop/account/supplier/agent_activity_list.html', $pagedata);
    }

    /**
     * 线下店活动详情
     */
    public function agentActivity()
    {
        $agent_activity_id = input::get('agent_activity_id');
        $agent_shop_id = input::get('agent_shop_id');
        if($agent_activity_id)
        {
            $this->contentHeaderTitle = app::get('topshop')->_('编辑线下店活动');
        }else{
            $this->contentHeaderTitle = app::get('topshop')->_('添加线下店活动');
        }

        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_index@index'),
                'title' => app::get('topshop')->_('首页')
            ],
            [
                'url' => url::action('topshop_ctl_account_supplier@index'),
                'title' => app::get('topshop')->_('供应商管理')
            ],
            [
                'title' => app::get('topshop')->_('线下店管理')
            ]
        );
        $pagedata = array();

        if ($agent_activity_id) {
            $params['agent_activity_id'] = $agent_activity_id;
            $params['agent_shop_id'] = $agent_shop_id;
            $data = app::get('topshop')->rpcCall('supplier.agent.activity.get', $params);
            if ($data) {
                $data['activity_type_name'] = $this->agent_activity_type[$data['activity_type']];
                $data['start_time'] = date('Y/m/d H:i',$data['start_time']);
                $data['end_time'] = date('Y/m/d H:i',$data['end_time']);
                $pagedata['data'] = $data;
            }
        }
        $pagedata['agent_shop_id'] = $agent_shop_id;
        return $this->page('topshop/account/supplier/agent_activity.html', $pagedata);
    }

    /**
     * 线下店活动保存
     */
    public function agentActivitySave()
    {
        $params = input::get();
        $time_arr = explode('-',$params['activity_time']);
//        var_dump($params['activity_time']);
//        var_dump($time_arr);die;
        $params['start_time'] = $time_arr[0];
        $params['end_time'] = $time_arr[1];
//        var_dump($params);die;
        try {
            if ($params['agent_activity_id']) {
                app::get('topshop')->rpcCall('supplier.agent.activity.update', $params);
                $msg = '修改线下店活动成功';
            } else {
//                $params = input::get();
                unset($params['agent_activity_id']);
                $agent_shop_id = app::get('topshop')->rpcCall('supplier.agent.activity.add', $params);
                $msg = '创建线下店活动成功';
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg, true);
        }
        $url = url::action('topshop_ctl_account_supplier@agentActivityList',['agent_shop_id'=>$params['agent_shop_id']]);
        return $this->splash('success', $url, $msg, true);
    }

    /**
     * 线下店活动删除
     */
    public function agentActivityDel()
    {
        $agentActivityId = input::get('agent_activity_id', false);
        if (!$agentActivityId) {
            $msg = '删除失败';
            return $this->splash('error', '', $msg, true);
        }
        try {
            $params['agent_activity_id'] = $agentActivityId;
            app::get('topshop')->rpcCall('supplier.agent.activity.delete', $params);
        } catch (\LogicException $e) {
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg, true);
        }

        $msg = '删除成功';
//        $url = url::action('topshop_ctl_account_supplier@index');
        return $this->splash('success', null, $msg, true);
    }

    public function edit()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('添加供应商');

        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_index@index'),
                'title' => app::get('topshop')->_('首页')
            ],
            [
                'url' => url::action('topshop_ctl_account_list@index'),
                'title' => app::get('topshop')->_('账号管理')
            ],
            [
                'title' => app::get('topshop')->_('添加子帐号')
            ]
        );

        if (input::get('supplier_id')) {
            $params['supplier_id'] = input::get('supplier_id');
            $data = app::get('topshop')->rpcCall('supplier.shop.get', $params);
            if ($data) {
                $pagedata = $data;
            }
        }
        if($this->isHmShop) {

            $hm_cate_list = [];
            $hm_obj = kernel::single('ectools_huimin');
            $hm_cate_list = $hm_obj->getSupplierCate($this->shopId);
            $pagedata['hm_cate_list'] = $hm_cate_list;

            $objMdlHmSupplier = app::get('sysshop')->model('hm_supplier');
            $hm_supplier_info = $objMdlHmSupplier->getRow('*', ['supplier_id' => input::get('supplier_id')]);
            $pagedata['hm_supplier_info'] = $hm_supplier_info;
        }
        $pagedata['shop'] = $this->shopInfo;
        $pagedata['seller_id'] = $this->sellerId;
        $pagedata['is_hm_supplier'] = false;
        $pagedata['is_hm_shop'] = $this->isHmShop;
        return $this->page('topshop/account/supplier/edit.html', $pagedata);
    }

    /**
     * @return html
     * 惠民商户资料
     */
    public function hmEdit()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('完善商户资料');
        $this->runtimePath = [];

        $params['supplier_id'] = input::get('supplier_id') ? input::get('supplier_id') : $_SESSION['huimin_supplier_id'];
        $data = app::get('topshop')->rpcCall('supplier.shop.get', $params);
        if ($data) {
            $pagedata = $data;
        }

        $hm_cate_list = [];
        $hm_obj = kernel::single('ectools_huimin');
        $hm_cate_list = $hm_obj->getSupplierCate($this->shopId);
        $pagedata['hm_cate_list'] = $hm_cate_list;

        $objMdlHmSupplier = app::get('sysshop')->model('hm_supplier');
        $hm_supplier_info = $objMdlHmSupplier->getRow('*', ['supplier_id' => input::get('supplier_id')]);
        $pagedata['hm_supplier_info'] = $hm_supplier_info;

        $pagedata['shop'] = $this->shopInfo;
        $pagedata['seller_id'] = $this->sellerId;
        $pagedata['is_hm_supplier'] = true;
        $pagedata['is_hm_shop'] = true;
        return $this->page('topshop/account/supplier/edit.html', $pagedata);
    }

    public function save()
    {
        // if( !input::get('role_id',false) )
        // {
        // $msg = '请选择角色';
        // return $this->splash('error','',$msg,true);
        // }
        $params = input::get();
        $supplierId = 0;
        //todo 供应商推送金蝶得开启事务
//        $db = app::get('sysshop')->database();
//        $db->beginTransaction();
        try {
            if (input::get('supplier_id')) {

                $params = input::get();
                $params['shop_id'] = $this->shopId;
                app::get('topshop')->rpcCall('supplier.shop.update', $params);

                if($this->checkHuiminSupplierLogin()) {
                    $hm_update_data['hm_cate_id'] = $params['hm_supplier_cate_id'];
                    $hm_update_data['hm_cate_name'] = $params['hm_supplier_cate_name'];
                    $hm_update_data['district'] = $params['district'];
                    $hm_update_data['district_name'] = $params['district_name'];

                    $hm_sup_filter['supplier_id'] = input::get('supplier_id');
                    $objMdlHmSupplier = app::get('sysshop')->model('hm_supplier');
                    $has_count = $objMdlHmSupplier->count($hm_sup_filter);
                    if($has_count > 0) {
                        $objMdlHmSupplier->update($hm_update_data, $hm_sup_filter);
                    } else {
                        $hm_insert_data['supplier_id'] = input::get('supplier_id');
                        $hm_insert_data['hm_cate_id'] = $params['hm_supplier_cate_id'];
                        $hm_insert_data['hm_cate_name'] = $params['hm_supplier_cate_name'];
                        $hm_insert_data['district'] = $params['district'];
                        $hm_insert_data['district_name'] = $params['district_name'];
                        $objMdlHmSupplier->insert($hm_insert_data);
                    }

                    $supplier_id = $params['supplier_id'];
                    $objMdlSupplier = app::get('sysshop')->model('supplier');
                    $supplier_data = $objMdlSupplier->getRow('supplier_id, supplier_name, company_name, is_audit', ['supplier_id' => $supplier_id]);
                    if($supplier_data['is_audit'] == 'PASS') {
                        $this->handleSync($supplier_data);
                    }
                }
                $msg = '修改供应商成功';
                $supplierId = $params['supplier_id'];
            } else {
                $params = input::get();
                unset($params['supplier_id']);
                $params['shop_id'] = $this->shopId;
                $auditStatus=app::get('sysshop')->rpcCall('supplier.shop.audit',array('shop_id'=>7));
                if($auditStatus==false) return $this->splash('error','','获取供应商审核状态失败');
                $params['is_audit']=$auditStatus;
                $supplierId = app::get('topshop')->rpcCall('supplier.shop.add', $params);
                $msg = '创建供应商成功';
            }

            //todo 以下为供应商推送金蝶的逻辑
//            $tv_shop_id = app::get('sysshop')->getConf('sysshop.tvshopping.shop_id');
//            if($supplierId && $tv_shop_id == $this->shopId)
//            {
//                $supplierModel = app::get('sysshop')->model('supplier');
//                $supplier_info = $supplierModel->getRow('*',['supplier_id'=>$supplierId]);
//                $kingdee_supplier_id = $supplier_info['kingdee_supplier_id'];
//
//                $sup_gen_data = [
//                    'to_org_id' => 400,
//                    'company_name'        =>  $supplier_info['invoice_name'],
//                    'company_addr'        =>  $supplier_info['addr'],
//                    'registration_number' =>  $supplier_info['registration_number'],
//                    'deposit_bank'        =>  $supplier_info['deposit_bank'],
//                    'card_number'         =>  $supplier_info['card_number'],
//                    'supplier_id'         =>  $supplierId,
//                    'throw'               =>  true,//表示接口抛出异常
//                ];
//
//                $kingdeeSupplierModel = kernel::single('sysclearing_kingdeeSupplier');
//                //todo 以下逻辑为了兼容之前已经创建的供应商，只有当金蝶编码和主键同时存在的时候才认为是更新操作，
//                //todo 只有两者都不存在的时候才认为是创建操作
//                if($supplier_info['kingdee_supplier_id'] && $supplier_info['kingdee_supplier_isn']) {
//                    $sup_gen_data['kingdee_supplier_isn'] = $supplier_info['kingdee_supplier_isn'];
//                    $sup_gen_res = $kingdeeSupplierModel->generate($sup_gen_data);
//                    if(!$sup_gen_res)
//                    {
//                        throw new Exception('金蝶供应商创建失败');
//                    }
//                }
//
//                if(!$supplier_info['kingdee_supplier_id'] && !$supplier_info['kingdee_supplier_isn']) {
//                    $sprint_id = sprintf("%06d", $supplierId);
//                    $kingdee_supplier_id = $sup_update['kingdee_supplier_id'] = 'GYS089.'.$sprint_id;
//                    $sup_gen_data['kingdee_supplier_id'] = $kingdee_supplier_id;
//
//                    $sup_gen_res = $kingdeeSupplierModel->generate($sup_gen_data);
//                    if(!$sup_gen_res)
//                    {
//                        throw new Exception('金蝶供应商创建失败');
//                    }
//                }
//
//                if(!$supplier_info['kingdee_supplier_isn'])
//                {
//                    $supplier_params['FUseOrgId'] = 227509;//电视购物组织对应的金蝶主键
//                    $supplier_params['FNumber'] = $kingdee_supplier_id;
//                    $supplier_query_res = $kingdeeSupplierModel->queryInfo($supplier_params);
//                    if(empty($supplier_query_res))
//                    {
//                        throw new Exception('未查询到金蝶内的供应商信息');
//                    }
//                    if(empty($supplier_query_res[0][0])) {
//                        throw new Exception('未获取到供应商的金蝶编码主键');
//                    }
//                    $sup_update['kingdee_supplier_isn'] = $supplier_query_res[0][0];
//                    $sup_update['kingdee_supplier_id'] = $kingdee_supplier_id;
//                    $supplierModel->update($sup_update, ['supplier_id' => $supplierId]);
//                }
//            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
//            $db->rollback();
            return $this->splash('error', '', $msg, true);
        }

        $this->sellerlog('添加/修改供应商账号是 ' . $params['login_account']);
        $url = url::action('topshop_ctl_account_supplier@index');

        //如果是惠民二级商户登陆，则跳转至当前页面
        if($this->checkHuiminSupplierLogin()) {
            $url = url::action('topshop_ctl_account_supplier@hmEdit', ['supplier_id' => $supplierId]);
        }

        // 暂不同步
        /*
         * if($supplierId){
         * app::get('foodmap')->rpcCall('supplier.sync', ['supplier_id'=> $supplierId]);
         * }
         */
//        $db->commit();
        return $this->splash('success', $url, $msg, true);
    }

    public function modifyPwd()
    {
        $params['supplier_id'] = input::get('supplier_id');
        $data = app::get('topshop')->rpcCall('supplier.shop.get', $params);
        if (! $data) {
            $msg = '修改失败';
            return $this->splash('error', $url, $msg, true);
        }

        try {
            $setPwdData['login_password'] = input::get('login_password');
            $setPwdData['psw_confirm'] = input::get('psw_confirm');
            shopAuth::resetSupplierPwd($params['supplier_id'], $setPwdData);
        } catch (\LogicException $e) {
            $msg = $e->getMessage();
            return $this->splash('error', $url, $msg, true);
        }

        $this->sellerlog('供应商修改密码。账号是 ' . $data['login_account']);
        $msg = '修改成功';
        $url = url::action('topshop_ctl_account_supplier@index');
        return $this->splash('success', $url, $msg, true);
    }

    /**
     * 修改线下店密码
     */
    public function modifyPwdAgentShop()
    {
        try{
            $params['agent_shop_id'] = input::get('agent_shop_id');
            $data = app::get('topshop')->rpcCall('supplier.agent.shop.get', $params);
            if (! $data) {
                $msg = '修改失败';
                return $this->splash('error', '', $msg, true);
            }

            try {
                $setPwdData['login_password'] = input::get('login_password');
                $setPwdData['psw_confirm'] = input::get('psw_confirm');
                $setPwdData['agent_shop_id'] = $params['agent_shop_id'];
                agentShopAuth::resetPwd($setPwdData);
            } catch (\LogicException $e) {
                $msg = $e->getMessage();
                return $this->splash('error', '', $msg, true);
            }

            $this->sellerlog('线下店修改密码。账号是 ' . $data['login_account']);
            $msg = '修改成功';
            $url = url::action('topshop_ctl_account_supplier@agentShopList',['supplier_id'=>input::get('supplier_id')]);
            return $this->splash('success', $url, $msg, true);
        }catch (\Exception $exception)
        {
            return $this->splash('error', '', $exception->getMessage(), true);
        }
    }

    public function delete()
    {
        $supplierId = input::get('supplier_id', false);
        if (! $supplierId) {
            $msg = '删除失败';
            return $this->splash('error', '', $msg, true);
        }
        // 判断供应商是否有商品存在
        $goods = app::get('sysitem')->model('item')->getRow('item_id', array(
            'supplier_id' => $supplierId
        ));
        if ($goods) {
            return $this->splash('error', '', '该供应商已经绑定商品,不能删除', true);
        }
        $offlineStore=app::get('syssupplier')->model('agent_shop')->count(array('supplier_id'=>$supplierId,'delete'=>0));
        if($offlineStore > 0){
            return $this->splash('error', '', '该供应商已经存在线下分店数据，请清理后操作', true);
        }
        try {
            $params['supplier_id'] = $supplierId;
            $params['shop_id'] = $this->shopId;
            app::get('topshop')->rpcCall('supplier.shop.delete', $params);
        } catch (\LogicException $e) {
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg, true);
        }

        $this->sellerlog('删除供应商。账号ID是 ' . $supplierId);
        $msg = '删除成功';
        $url = url::action('topshop_ctl_account_supplier@index');
        return $this->splash('success', $url, $msg, true);
    }

    /**
     * @name chkExistingSupplier
     * @desc check existing supplier beforen add one
     * @return mixed
     * @author: wudi tvplaza
     * @date: 2018-01-18 9:43
     */
    public function chkExistingSupplier(){
        $this->contentHeaderTitle = app::get('topshop')->_('添加供应商');
        $pageData=array();
        return $this->page('topshop/account/supplier/chk_existing_supplier.html',$pageData);
    }

    /**
     * @name doChkExistingSupplier
     * @desc 检测供应商是否存在
     * @author: wudi tvplaza
     * @date: 2018-01-18 10:56
     */
    public function doChkExistingSupplier(){
        $postFilter = input::get();
        if(empty($postFilter['keyword'])){
            return $this->splash('error','','请输入关键词');
        }else{
            $data = app::get('topshop')->rpcCall('supplier.shop.search',['keyword'=>$postFilter['keyword'],'is_audit|in'=>['PASS','REJECTED']]);
            $pagedata['data'] = $data;
            $pagedata['shopid']=$this->shopId;
            return view::make('topshop/account/supplier/chk_existing_result.html', $pagedata);
        }

    }

    /**
     * @name __chekSupplierFilter
     * @desc format supplier filter params
     * @param $params
     * @author: wudi tvplaza
     * @date: 2018-03-10 16:53
     */
    private function __chekSupplierFilter($params){
        $filter=[];
        foreach($params as $k => $v){
            if(!empty($v)){
                if(!empty($params['supplier_name']))
                {
                    $filter['supplier_name|has'] = $params['supplier_name'];
                    unset($params['supplier_name']);
                }
                elseif(!empty($params['company_name']))
                {
                    $filter['company_name|has'] = $params['company_name'];
                    unset($params['company_name']);
                }
                elseif(!empty($params['mobile']))
                {
                    $filter['mobile|has'] = $params['mobile'];
                    unset($params['mobile']);
                }else{
                    $filter[$k]=$v;
                }
            }
        }
        $filter['deleted']=0;
        return $filter;
    }

    public function ajaxMerchantEntry()
    {
        $post_data = input::get();
        $agent_shop_id = $post_data['agent_shop_id'];
        $bank = $post_data['bank'];
        $result = 'FAIL';
        $message = '入驻成功';
        try
        {
            $agent_shop_model = app::get('syssupplier')->model('agent_shop');
            $agent_shop_info = $agent_shop_model->getRow('*',['agent_shop_id' => $agent_shop_id]);
            if($agent_shop_info['shop_id'] !== $this->shopId)
            {
                throw new Exception('不能入驻非此频道的线下店');
            }
            $shop_info = app::get('ectools')->rpcCall('shop.get', ['shop_id'=>$agent_shop_info['shop_id']]);
            $ns_firstcomm_id = $shop_info['ns_firstcomm_id'];
            $mer_key = trim($shop_info['ns_firstcomm_key']);
            $bank_model = app::get('syssupplier')->model($bank);
            $bank_info = $bank_model->getRow('*',['agent_shop_id' => $agent_shop_id, 'disabled'=>0, 'deleted'=>0]);
            if($bank_info['is_entry'] == 1)
            {
                throw new Exception('此线下店已经入驻');
            }

            if(empty($agent_shop_info['district']))
            {
                throw new Exception('省市区编码不存在');
            }

            $params = array_merge($agent_shop_info, $bank_info);
            $params = array_merge($params,['firstcomm_id'=>$ns_firstcomm_id,'mer_key'=>$mer_key]);
            $district = explode('/',$agent_shop_info['district']);
            $params['prov'] = $district[0];
            $params['pref']  = $district[1];
            $params['country']  = isset($district[2]) ? $district[2] : '';

            $params = app::get('ectools')->getConf('ectools_payment_plugin_nswxpay');
            $params = unserialize($params);
            $fee_rate = $params['setting']['pay_fee'];
            $params['fee_rate'] = $fee_rate;

            app::get('syssupplier')->rpcCall('supplier.agent.merchant.entry',$params);
            $result = 'SUCCESS';
        }
        catch(Exception $e)
        {
            $message = $e->getMessage();
        }
        $data['result'] = $result;
        $data['message'] = $message;
        $data['url']    = url::action('topshop_ctl_account_supplier@agentShop',['agent_shop_id'=>$agent_shop_id]);
        if($data['result'] !== 'SUCCESS')
        {
            return $this->splash('error','',$message);
        }
        return response::json($data);
    }

    /**
     * @return string
     * 审核供应商
     */
    public function auditSupplier()
    {
        $request_data = input::get();

        $is_reject = $request_data['is_reject'];
        $supplier_id = $request_data['supplier_id'];
        $objMdlSupplier = app::get('sysshop')->model('supplier');
        $supplier_data = $objMdlSupplier->getRow('is_audit, supplier_id, supplier_name, company_name', ['supplier_id' => $supplier_id]);
        $is_audit = $supplier_data['is_audit'];

        if($is_reject && $is_audit != 'REJECTED') {
            $update_data['is_audit'] = 'REJECTED';
            $audit_logo = '拒绝';
        } else {
            if($is_audit == 'PENDING' || $is_audit == 'REJECTED') {
                $update_data['is_audit'] = 'FIRST_TRIAL';
                $audit_logo = '初步审核';
            } elseif($is_audit == 'FIRST_TRIAL' && $this->sellerId == '64') {
                $update_data['is_audit'] = 'PASS';
                $audit_logo = '最终审核';
            } else {
            }
        }

        if($update_data['is_audit']) {
            $objMdlSupplier->update($update_data, ['supplier_id' => $supplier_id]);
            $this->sellerlog($audit_logo.'惠民供应商'.$supplier_id .'操作人id：'.$this->sellerId);
        }
        //如果是终审 则同步商户信息至惠民平台，因终审权限在山东省文旅厅手中，防止网络问题，将同步放在最后，
        //如果同步不成功，可在列表中单独点击同步按钮
        if($update_data['is_audit'] = 'PASS') {
           $this->handleSync($supplier_data);
        }
        $url = url::action('topshop_ctl_account_supplier@index');
        return $this->splash('success',$url,'成功');
    }

    /**
     * 同步至惠民
     */
    public function singleSyncToHm()
    {
        $request_data = input::get();
        $supplier_id = $request_data['supplier_id'];

        try {
            if(!$this->isHmShop) {
                throw new Exception('只有惠民店铺允许操作');
            }
            $objMdlSupplier = app::get('sysshop')->model('supplier');
            $supplier_info = $objMdlSupplier->getRow('supplier_id, supplier_name, company_name, is_audit', ['supplier_id' => $supplier_id]);

            if($supplier_info['is_audit'] != 'PASS') {
                throw new Exception('未审核通过的商户不允许同步');
            }

            $sync_res = $this->handleSync($supplier_info);
            if($sync_res['status'] === false) {
                throw new Exception($sync_res['err_msg']);
            }
        } catch(Exception $e) {
            return $this->splash('error',null,$e->getMessage(),true);
        }
        return $this->splash('success',null, '成功！',true);
    }

    /**
     * @param $supplier_info
     * @throws Exception
     * 同步共同处理的地方
     */
    public function handleSync($supplier_info)
    {
        $supplier_id = $supplier_info['supplier_id'];

        $objMdlHmSupplier = app::get('sysshop')->model('hm_supplier');
        $hm_supplier_info = $objMdlHmSupplier->getRow('*', ['supplier_id' => $supplier_id]);

        $district_name = explode('/', $hm_supplier_info['district_name']);
        $prov_name = $district_name[0];
        $city_name = $district_name[1];
        $area_name = $district_name[2];

        $sync_params['supplier_id'] = $supplier_id;
        $sync_params['supplier_name'] = $supplier_info['supplier_name'];
        $sync_params['company_name']  = $supplier_info['company_name'];
        $sync_params['prov_name'] = $prov_name;
        $sync_params['city_name'] = $city_name;
        $sync_params['area_name'] = $area_name;
        $sync_params['cate_name'] = $hm_supplier_info['hm_cate_id'];

        $hm_obj = kernel::single('ectools_huimin');
        $sync_res = $hm_obj->syncSupplierInfo($sync_params);
        if($sync_res['status'] === true) {
            $objMdlHmSupplier->update(['is_synced' => 1], ['supplier_id' => $supplier_id]);
            $return_data['status'] = true;
            $return_data['err_msg'] = '';
        } else {
            $return_data['status'] = false;
            $return_data['err_msg'] = $sync_res['err_msg'];
        }
        return $return_data;
    }
}


