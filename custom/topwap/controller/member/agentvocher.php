<?php

class topwap_ctl_member_agentvocher extends topwap_ctl_member{
    public $voucherStatus = array(
        '0' => 'WAIT',    //待核销
        '1' => 'COMPLETE',    //已核销
        '2' => 'REFUNDED',     //已退款
        '3' => 'HAS_OVERDUE',        //已过期
    );

    //获取卡券的
    public function index()
    {
        $filter = input::get();

        $filter['s']='0';
        $pagedata = $this->__getVoucher($filter);
        $pagedata['app'] = isset($_GET['app']) ? $_GET['app'] : '';
        if($filter['keyword'])
        {
            $pagedata['keyword']=$filter['keyword'];
        }
        $pagedata['status']=0;
        return $this->page('topwap/member/offline/list.html', $pagedata);
    }

    /* action_name (par1, par2, par3)
    * 新的获取卡券的逻辑
    * author by fanglongji
    * date 2018/2/1
    */
    public function __getVoucher($postdata)
    {
        if(isset($this->voucherStatus[$postdata['s']]))
        {
            $status=$this->voucherStatus[$postdata['s']];
        }
        else
        {
            $status="";
        }
        $params['status']    = $status;
        if($status === 'WAIT')
        {
            $params['start_time'] = time();
            $params['end_time'] = time();
        }
        if($status === 'HAS_OVERDUE')
        {
            unset($params['status']);
            $params['expire_time'] = time();
        }
        $params['user_id']   = userAuth::id();
        $params['page_no']   = intval($postdata['pages']) ? intval($postdata['pages']) : 1;
        $params['page_size'] = intval($this->limit);
        $params['order_by']  = 'careated_time desc';
        $params['fields']    = '*';

        $voucher_info  = app::get('topwap')->rpcCall('supplier.agentvoucher.get.list',$params);

        $count        = $voucher_info['count'];
        $voucherlist  = $voucher_info['list'];
        //获取线下店的名称
        if($count)
        {
            $agent_shop_ids = array_column($voucherlist, 'agent_shop_id');

            $new_shop_ids = [];
            foreach($agent_shop_ids as $shop_id)
            {
                $shop_id = trim($shop_id,',');
                $new_shop_ids = array_merge($new_shop_ids, explode(',',$shop_id));
            }
            $new_shop_ids = array_unique($new_shop_ids);
            $MdlAgentShop = app::get('syssupplier')->model('agent_shop');
            $agent_shop_info = $MdlAgentShop->getList('agent_shop_id, name',['agent_shop_id'=>$new_shop_ids]);
            $agent_shop_info = array_bind_key($agent_shop_info, 'agent_shop_id');

            foreach( $voucherlist as $k=>&$value)
            {
                $agent_shop_array = explode(',', trim($value['agent_shop_id'],','));
                foreach($agent_shop_array as $agent_shop_id)
                {
                    if($value['agent_shop_name'])
                    {
                        $value['agent_shop_name'] .= '/';
                    }
                    $value['agent_shop_name'] .= $agent_shop_info[$agent_shop_id]['name'];
                }

                if((int)$value['min_consum'])
                {
                    $value['min_desc']  = '最低消费：'.floatval($value['min_consum']).'元';
                }
                else
                {
                    $value['min_desc'] = '最低消费：无限制';
                }
                if((int)$value['max_deduct_price'])
                {
                    $value['max_desc'] = '最大抵扣：'.floatval($value['max_deduct_price']).'元';
                }
                else
                {
                    $value['max_desc'] = '最大抵扣：无限制';
                }
                $value['item_image'] = base_storager::modifier($value['item_image']);
                $value['agent_use_limit'] = (int)$value['agent_use_limit'];
                $value['deduct_price'] = (float)$value['deduct_price'];
                $value['max_deduct_price'] = (float)$value['max_deduct_price'];
                $value['min_consum'] = (float)$value['min_consum'];
                $value['status'] = $status;
            }
        }
        $pagedata['vouchers'] = $voucherlist;
        $pagedata['pagers']   = $this->__pages($postdata['pages'], $postdata, $count);
        $pagedata['count']    = $count;
        $pagedata['title']    = "卡券";  //标题
        $pagedata['status']   = $postdata['s'];  //状态

        return $pagedata;
    }

    /**
     * @brief 订单详情
     *
     * @return
     */
    public function detail()
    {
        $this->setLayoutFlag('vocher_detail');
        $params['sys_oid'] = input::get('oid');
        $params['user_id'] = userAuth::id();
        $pagedata=$this->getData($params);
        //获取发货信息
        $pagedata['title'] = "线下卡券详情";  //标题
        return $this->page('topwap/member/offline/detail.html', $pagedata);
    }


    private function getData($params)
    {
        $voucher  = app::get('systrade')->model('agent_vocher');   //卡券
        $orderObj = app::get('systrade')->model('order');    //子订单
        $supplier = app::get('sysshop')->model('supplier');  //供应商

        //取出卡券的列表
        $voucher_list=$voucher->getList('vocher_id,supplier_id,status,start_time,end_time',$params);
        foreach($voucher_list as $k=>$v){
            $data['start_time']=date('Y-m-d',$v['start_time']);
            $data['end_time']=date('Y-m-d',$v['end_time']);
        }
        //取出订单数据
        $order_filter['user_id'] = $params['user_id'];
        $order_filter['oid'] = $params['sys_oid'];
        $order=$orderObj->getRow('oid,shop_id,item_id,title,price,num,pic_path,supplier_id,payment',$order_filter);

        //获取商品图片
        $item_where = [
            'shop_id'=>$order['shop_id'],
            'item_id'=>$order['item_id'],
            'fields'=>[
                'rows'=>'image_default_id'
            ]
        ];
        $item_info = app::get('topwap')->rpccall('item.get',$item_where);
        $order['pic_path'] = $item_info['image_default_id'];

        //取出供应商的信息
        $supplier=$supplier->getRow('supplier_name,supplier_id,company_name',array('supplier_id'=>$order['supplier_id'],'is_audit'=>'PASS'));
        //取出已经使用的张数
        $params['status']="WRITE_FINISHED";  //已经核销的
        $voucher_use=$voucher->count($params);
        $data['voucher_list']=$voucher_list;
        $data['order']=$order;
        $data['supplier']=$supplier;
        $data['voucher_use']=$voucher_use;
        return $data;
    }

    /**
     * 分页处理
     * @param int $current 当前页
     *
     * @return $pagers
     */
    private function __pages($current,$filter,$count)
    {
        //处理翻页数据
        $current = ($current && $current <= 100 ) ? $current : 1;

        if( $count > 0 ) $totalPage = ceil($count/$this->limit);
        $pagers = array(
            'link'=>url::action('topwap_ctl_member_agentvocher@ajaxVoucherShow',$filter),
            'current'=>intval($current),
            'total'=>intval($totalPage),
        );
        return $pagers;
    }


    public function ajaxVoucherShow()
    {
        $postdata = input::get();
        $pagedata = $this->__getVoucher($postdata);
        if($_SESSION['account']['member']['qrcode_url'])
        {
            $pagedata['qrcode_url'] = $_SESSION['account']['member']['qrcode_url'];
        }
        if($pagedata['vouchers'])
        {
            $data['html'] = view::make('topwap/member/offline/listitem.html',$pagedata)->render();
        }
        else
        {
            $data['html'] = view::make('topwap/empty/trade-voucher.html',$pagedata)->render();
        }
        $pagedata['app'] = $postdata['app'];
        $data['pagers']  = $pagedata['pagers'];
        $data['success'] = true;
        return response::json($data);exit;
    }
}
