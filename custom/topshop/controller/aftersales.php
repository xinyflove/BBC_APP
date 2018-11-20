<?php
class topshop_ctl_aftersales extends topshop_controller {

    public $limit = 10;

    public function index()
    {
        $pagedata = $this->__searchListData();

        $pagedata['refund_type'] = array(
            'ONLY_REFUND' => app::get('topshop')->_('仅退款'),
            'REFUND_GOODS' => app::get('topshop')->_('退货退款'),
            'EXCHANGING_GOODS' => app::get('topshop')->_('换货'),
        );
        $pagedata['progress'] = array(
            '0' => app::get('topshop')->_('等待审核'),
            '1' => app::get('topshop')->_('等待买家回寄'),
            '2' => app::get('topshop')->_('待确认收货'),
            '5' => app::get('topshop')->_('商家已收货'),
            '8' => app::get('topshop')->_('等待退款'),
            '9' => app::get('topshop')->_('换货中'),
            '3-4-6-7' => app::get('topshop')->_('已完成'),//换货的时候可以直接在商家处理结束
        );

        $pagedata['complaints'] = array(
                'NOT_COMPLAINTS' => app::get('topshop')->_('未投诉'),
                'WAIT_SYS_AGREE' => app::get('topshop')->_('买家已发起投诉'),
                'FINISHED' => app::get('topshop')->_('投诉受理'),
                'BUYER_CLOSED' => app::get('topshop')->_('买家撤销投诉'),
                'CLOSED' => app::get('topshop')->_('投诉驳回'),
        );

        //获取默认图片信息
        $pagedata['defaultImageId']= kernel::single('image_data_image')->getImageSetting('item');
        $this->contentHeaderTitle = app::get('topshop')->_('退换货管理');

        return $this->page('topshop/aftersales/list.html', $pagedata);
    }

    private function __searchListData()
    {
        $params = input::get();
        $data['filter'] = $params;
        $this->__checkParams($params);
        $params['shop_id'] = $this->shopId;
        $params['page_no'] = intval(input::get('pages',1));
        $params['page_size'] = intval($this->limit);
        $params['fields'] = 'aftersales_bn,aftersales_type,shop_id,created_time,oid,tid,num,progress,status,sku,gift_data,init_shop_id';
        try{
            $result = app::get('topshop')->rpcCall('aftersales.list.get', $params, 'seller');
            $result['list'] = $this->__proResult($result);
        }
        catch(Exception $e)
        {
            $result = array();
        }

        $data['count'] = $result['total_found'];
        $data['list'] = $result['list'];

        $tids=array_column($data['list'],'tid')?array_column($data['list'],'tid'):array(0);
        $oids=array_column($data['list'],'oid')?array_column($data['list'],'oid'):array(0);
        $paymentInfo=app::get('ectools')->model("trade_paybill")->getList('*',array('status'=>'succ','tid|in'=>$tids));
        $payinfo=array_bind_key($paymentInfo,'tid');
        $refundInfo=app::get('ectools')->model('refunds')->getList('*',array('status'=>'succ','oid|in'=>$oids));
        $refunds=array_bind_key($refundInfo,'oid');
        foreach($data['list'] as $key => $value){
            $data['list'][$key]['payinfo']=$payinfo[$value['tid']];
            $data['list'][$key]['refundinfo']=$refunds[$value['oid']];
        }
        //处理翻页数据
        $filter = input::get();
        $filter['pages'] = time();
        if($result['total_found']>0) $total = ceil($result['total_found']/$this->limit);
        $current = input::get('pages',1);
        $current = $total < $current ? $total : $current;
        $data['pagers'] = array(
            'link'=>url::action('topshop_ctl_aftersales@search',$filter),
            'current'=>$current,
            'total'=>$total,
            'use_app'=>'shop',
            'token'=>$filter['pages'],
        );
       return $data;
    }

    public function detail()
    {

        $requestParams = ['shop_id'=>$this->shopId];
        $shopConf = app::get('topshop')->rpcCall('open.shop.develop.conf', $requestParams);
        $pagedata['develop_mode'] = ($shopConf['develop_mode'] == 'DEVELOP' || $shopConf['shopexProduct'] == 'bind' ) ? 'DEVELOP' : 'PRODUCT';

        $params['aftersales_bn'] = input::get('bn');
        $params['shop_id'] = $this->shopId;
        $tradeFields = 'trade.status,trade.pay_type,trade.receiver_name,trade.user_id,trade.post_fee,trade.receiver_state,trade.receiver_city,trade.created_time,trade.receiver_district,trade.receiver_address,trade.receiver_mobile,trade.receiver_phone';
        $params['fields'] = 'aftersales_bn,shop_id,aftersales_type,sendback_data,description,sendconfirm_data,shop_explanation,admin_explanation,user_id,reason,evidence_pic,created_time,oid,tid,num,progress,status,sku,refunds_reason,gift_data,init_shop_id,'.$tradeFields;
        /*add_2017-11-16_by_xinyufeng_start*/
        $params['fields'] .= ',voucher_ids,item_id';
        /*add_2017-11-16_by_xinyufeng_end*/
        try{
            $result = app::get('topshop')->rpcCall('aftersales.get', $params,'seller');
        }
        catch(Exception $e)
        {
            echo $e->getMessage();exit;
            redirect::action('topshop_ctl_aftersales@index')->send();exit;
        }
        $result['evidence_pic'] = $result['evidence_pic'] ? explode(',',$result['evidence_pic']) : null;
        $result['sendback_data'] = $result['sendback_data'] ? unserialize($result['sendback_data']) : null;
        $result['sendconfirm_data'] = $result['sendconfirm_data'] ? unserialize($result['sendconfirm_data']) : null;
        $payinfo=app::get('ectools')->model('trade_paybill')->getRow('*',array('status'=>'succ','tid'=>$result['tid']));
        $refundinfo=app::get('ectools')->model('refunds')->getRow('*',array('status'=>'succ','oid'=>$result['oid']));
        $result['payinfo']=$payinfo;
        $result['refundinfo']=$refundinfo;
        if( $result['user_id'] )
        {
             $userName = app::get('topshop')->rpcCall('user.get.account.name', ['user_id' => $result['user_id']], 'seller');
             $pagedata['userName'] = $userName[$result['user_id']];
        }

        if( $result['sendback_data']['corp_code']  && $result['sendback_data']['corp_code'] != "other")
        {
            $logiData = explode('-',$result['sendback_data']['corp_code']);
            $result['sendback_data']['corp_code'] = $logiData[0];
            $result['sendback_data']['logi_name'] = $logiData[1];
        }

        if( $result['sendconfirm_data']['corp_code'] && $result['sendconfirm_data']['corp_code'] != "other")
        {
            $logiData = explode('-',$result['sendconfirm_data']['corp_code']);
            $result['sendconfirm_data']['corp_code'] = $logiData[0];
            $result['sendconfirm_data']['logi_name'] = $logiData[1];
        }

        //快递公司代码
        $corpData = app::get('topshop')->rpcCall('shop.dlycorp.getlist',['shop_id'=>$this->shopId]);
        $pagedata['corpData'] = $corpData['list'];

        $pagedata['info'] = $result;

        //获取默认图片信息
        $pagedata['defaultImageId']= kernel::single('image_data_image')->getImageSetting('item');

        //商家退款信息
        if(in_array($result['progress'],['7','8']))
        {
            $refunds = app::get('topshop')->rpcCall('aftersales.refundapply.list.get',['fields'=>'status,total_price','oid'=>$result['oid']]);
            $refunds = $refunds['list'][0];
            $pagedata['refunds'] = $refunds;
        }

        $pagedata['tracking'] = app::get('syslogistics')->getConf('syslogistics.order.tracking');

        /*add_20170915_by_fanglongji_start*/
        $roleFilter['seller_id']=$this->sellerId;
        $sellerRole=app::get('sysshop')->model('seller')->getRow('seller_type',$roleFilter);
        $pagedata['seller_role']=$sellerRole['seller_type'];
        /*add_20170915_by_fanglongji_end*/

        $this->contentHeaderTitle = app::get('topshop')->_('退换货详情');
        //面包屑
        $this->runtimePath = array(
            ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],
            ['url'=> url::action('topshop_ctl_aftersales@index'),'title' => app::get('topshop')->_('退换货管理')],
            ['title' => app::get('topshop')->_('退换货详情')],
        );

        /*add_2017-11-16_by_xinyufeng_start*/
        //判断是否是虚拟商品
        $pagedata['is_virtual'] = 0;
        $itemInfo = app::get('topshop')->rpcCall('item.get',['fields'=>'is_virtual','item_id'=>$pagedata['info']['item_id']]);
        if($itemInfo['is_virtual']){
            $pagedata['is_virtual'] = 1;
            //退款卡券id
            $voucher_ids_arr = explode(',', $pagedata['info']['voucher_ids']);
            // 优惠券支付价格=子订单支付价格/子订单数量
            $voucher_payment = round($pagedata['info']['sku']['payment'] / $pagedata['info']['num'], 2);
            foreach ($voucher_ids_arr as $voucher_id){
                //获取卡券信息
                $voucherParam = array('voucher_id'=>$voucher_id,'fields'=>'*');
                $voucherInfo = app::get('topshop')->rpcCall('voucher.get.info', $voucherParam);
                $voucherInfo['price'] = $pagedata['info']['sku']['price'];
                $voucherInfo['payment'] = $voucher_payment;
                $pagedata['info']['voucher_info'][] = $voucherInfo;
                $pagedata['info']['voucher_price'] += $voucherInfo['payment'];
            }
        }
        /*add_2017-11-16_by_xinyufeng_end*/

        return $this->page('topshop/aftersales/detail.html', $pagedata);
    }

    public function search()
    {
        $pagedata = $this->__searchListData();

        $pagedata['complaints'] = array(
                'NOT_COMPLAINTS' => app::get('topshop')->_('未投诉'),
                'WAIT_SYS_AGREE' => app::get('topshop')->_('买家已投诉'),
                'FINISHED' => app::get('topshop')->_('投诉受理'),
                'BUYER_CLOSED' => app::get('topshop')->_('买家撤销投诉'),
                'CLOSED' => app::get('topshop')->_('投诉驳回'),
        );
        //获取默认图片信息
        $pagedata['defaultImageId']= kernel::single('image_data_image')->getImageSetting('item');

        return view::make('topshop/aftersales/item.html', $pagedata);
    }

    private function __checkParams(&$params)
    {
        foreach($params as $key=>$value)
        {
            if( empty($value) && $key != "progress"  ) unset($params[$key]);

            if($key == "progress" )
            {
                if( $value == "all" )
                {
                    unset($params['progress']);
                }
                else
                {
                    $progress = explode('-',$params['progress']);
                    $params['progress'] = implode(',',$progress);
                }
            }

            if($key == "created_time")
            {
                $times = explode('-',$value);
                if(array_filter($times))
                {
                    $params['created_time']= json_encode($times);
                }
            }
        }
    }

    public function sendConfirm()
    {
        $postdata = input::get();
        $postdata['shop_id'] = $this->shopId;

        if($postdata['corp_code'] == "other" && !$postdata['logi_name'])
        {
            return $this->splash('error',"","其他物流公司不能为空",true);
        }
        if(!$postdata['logi_no']) return $this->splash('error',"","运单号不可为空",true);
        if(strlen($postdata['logi_no']) < 6) return $this->splash('error',"","运单号不可小于6",true);
        if(strlen($postdata['logi_no']) > 20) return $this->splash('error',"","运单号不可大于20",true);
        //if(!$postdata['mobile']) return $this->splash('error',"","收货人手机不可为空",true);
        //if(!$postdata['receiver_address']) return $this->splash('error',"","收货地址不可为空",true);

        try
        {
            $result = app::get('topshop')->rpcCall('aftersales.send.confirm',$postdata,'seller');
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',$url,$msg,true);
        }
        $this->sellerlog('售后操作。换货重新发货。申请售后的订单编号是'.$postdata['aftersales_bn']);
        $url = url::action('topshop_ctl_aftersales@detail', array('bn'=>$postdata['aftersales_bn']));
        $msg = '操作成功';
        return $this->splash('success',$url,$msg,true);
    }

    /**
     * 审核售后申请
     */
    public function verification()
    {

        $postdata = input::get();
        $url = url::action('topshop_ctl_aftersales@detail', array('bn'=>$postdata['aftersales_bn']));

        $postdata['shop_id'] = $this->shopId;
        try
        {
            $result = app::get('topshop')->rpcCall('aftersales.check',$postdata,'seller');
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',$url,$msg,true);
        }
        $aType = array(
            'ONLY_REFUND' => app::get('topshop')->_('仅退款'),
            'REFUND_GOODS' => app::get('topshop')->_('退货退款'),
            'EXCHANGING_GOODS' => app::get('topshop')->_('换货'),
        );
        $this->sellerlog('处理售后申请。售后类型：'.$aType[$postdata['aftersales_type']].' 售后编号：'.$postdata['aftersales_bn']);
        return $this->splash('success',$url,'操作成功',true);
    }

    /**
     * 处理返回数据
     * @param array $result
     *
     * @return array
     * */

    private function __proResult($result)
    {
        $oids = array_column($result['list'], 'oid');
        $oids= array_unique($oids);
        $tmpList = array();

        foreach ($oids as $ov)
        {
            foreach ($result['list'] as $val)
            {
                if($ov == $val['oid'])
                {
                    $tmpList[$ov][] = $val;
                }
            }
        }

        // 添加投诉状态
        foreach ($tmpList as &$tval)
        {
            if(count($tval) <= 1)
            {
                continue;
            }

            foreach ($tval as $k=>$v)
            {
                if($k!=0 && $v['progress'] == 3)
                {
                    $tval[$k]['complaints_finished'] = 1;
                }
            }

            /*
            if($tval[0]['progress'] == 3)
            {
                if($tval[0]['sku']['complaints_status'] == 'NOT_COMPLAINTS')
                {
                    $tval[0]['arge'] = 1;
                }
            }
            */
        }

        // 取得结果
        $proList = array();
        foreach ($tmpList as $vv)
        {
            $proList = array_merge($proList, $vv);
        }

        // 根据售后发起时间逆向排序
        $tmpResultList = array();
        foreach ($proList as $pval)
        {
            $tmpResultList[$pval['created_time']] = $pval;
        }
        krsort($tmpResultList);

        return $tmpResultList;
    }

    /* aftersales_refund()
     * 函数说明：商家售后退款
     * 参数说明：
     * authorbyfanglongji
     * 2017-09-15
     */
    public function aftersales_refund(){
        $db = app::get('sysaftersales')->database();
        $db->beginTransaction();
        $postdata = input::get();
        try{
            // 售后服务编号
            $filter['aftersales_bn'] = $postdata['aftersales_bn'];
            $filter['shop_id'] = $this->shopId;

            $objMdlRefunds = app::get('sysaftersales')->model('refunds');
            $refunds = $objMdlRefunds->getRow('refunds_id,refund_bn,status,aftersales_bn,refund_fee,total_price,refunds_type,user_id,shop_id,tid,oid',$filter);
            //货到付款的单不进行在线退款操作
            $refunds['pay_type'] = $postdata['pay_type'];
            kernel::single('topshop_refunds')->dorefund($refunds);
            $db->commit();
            $this->sellerlog('处理售后订单退款申请。申请ID是'.$postdata['aftersales_bn']);

            $refund_data['tid']  = $refunds['tid'];
            $refund_data['oid']  = $refunds['oid'];
            $refund_data['shop_id']  = $this->shopId;
            $refund_data['push_type']= 'REFUND_GOODS';

            event::fire('kingdee.refund',[$refunds['tid'], $this->shopId, null, 'REFUND_GOODS', $refunds['oid']]);
        }
        catch(LogicException $e)
        {
            $db->rollback();
            return $this->splash('error',null, $e->getMessage(), true);
        }

        /*add_2017-11-17_by_xinyufeng_start*/
        $filter = array();
        $filter['aftersales_bn'] = $postdata['aftersales_bn'];
        $filter['shop_id'] = $this->shopId;
        $objMdlAftersales = app::get('sysaftersales')->model('aftersales');
        $aftersales = $objMdlAftersales->getRow('voucher_ids,oid',$filter);
        if($aftersales['voucher_ids']){
            $after_order = app::get('systrade')->model('order')->getRow('confirm_type',['oid'=>$aftersales['oid']]);
            $params = array();
            $params['voucher_id'] = $aftersales['voucher_ids'];
            if($after_order['confirm_type'] == 1)
            {
                app::get('sysaftersales')->rpcCall('offline.virtual.coupon', $params);
            }
            else
            {
                $res = app::get('topshop')->rpcCall('trade.virtual.coupon', $params);
            }
            /*add_201711211623_by_wudi_start:voucher aftersale settlement*/
            $aftersalesRefundInfo=app::get('sysaftersales')->model('refunds')->getRow('*',array('aftersales_bn'=>$postdata['aftersales_bn']));
            $tradeData['shop_id']=$aftersalesRefundInfo['shop_id'];
            $tradeData['status']='TRADE_FINISHED';
            $tradeData['payment']=$aftersalesRefundInfo['order_price'];
            $tradeData['pay_time']=$aftersalesRefundInfo['order_price'];
            $tradeData['orders'][0]['tid']=$aftersalesRefundInfo['tid'];
            $tradeData['orders'][0]['oid']=$aftersalesRefundInfo['oid'];
            /*add_201711211623_by_wudi_end*/
            $re = kernel::single('sysclearing_settle')->generate($tradeData,'3');
        }
        /*add_2017-11-17_by_xinyufeng_endt*/

        $url = url::action('topshop_ctl_aftersales@detail', array('bn'=>$postdata['aftersales_bn']));
        return $this->splash('success', $url, '退款成功', true);
    }
}
