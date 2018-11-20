<?php
/**
 * Created by PhpStorm. 无偿券抢购
 * User: fanglongji
 * Date: 2018/02/23
 * Time: 17:06
 */

class topwap_ctl_offlinepay_rushTickets extends topwap_controller
{
    public function __construct($app)
    {
        parent::__construct($app);
        // 检测是否登录
        if( !userAuth::check() )
        {
            if( request::ajax() )
            {
                $url = url::action('topwap_ctl_passport@goLogin');
                return $this->splash('error', $url, app::get('topwap')->_('请登录'), true);
            }
            redirect::action('topwap_ctl_passport@goLogin')->send();exit;
        }
    }

    /*
     * 优惠券抢购
     */
    public function rushTichets()
    {
        $quantity = input::get('item.quantity');
        $quantity = $quantity ? $quantity : 1;
        $sku_id = intval(input::get('item.sku_id'));

        $db = app::get('systrade')->database();
        $db->beginTransaction();
        try
        {
            $xz_count = $this->getLimitCount($sku_id);

            if($xz_count > 0)
            {
                if((int)$quantity > $xz_count)
                {
                    $msg = "每次限购{$xz_count}件";
                    throw new LogicException($msg);
                }
            }


            $skuInfo = app::get('sysitem')->model('sku')->getRow('item_id', ['sku_id' => $sku_id]);
            $itemInfo = app::get('sysitem')->model('item')->getRow('shop_id, sub_stock, image_default_id, title, supplier_id, start_time, end_time, agent_type', ['item_id' => $skuInfo['item_id']]);

            $voucher_model = app::get('systrade')->model('agent_vocher');
            $filter['user_id'] = userAuth::id();
            $filter['item_id'] = $skuInfo['item_id'];
            $filter['agent_type'] = $itemInfo['agent_type'];
            $filter['careated_time|between'] = [$itemInfo['start_time'],$itemInfo['end_time']];
            $voucher_count  = $voucher_model->count($filter);

            if($xz_count>0 && $voucher_count > $xz_count)
            {
                $msg = "您已经购买过{$voucher_count}件，每次限购{$xz_count}件";
                throw new LogicException($msg);
            }
            $y_count = $voucher_count + $quantity;
            $sur_count = $xz_count-$voucher_count;

            if($xz_count>0 && $y_count > $xz_count)
            {
                $msg="此产品限购{$xz_count}件，您所购数量已达上限";
                throw new LogicException($msg);
            }

            $storeData['item_id']    = $skuInfo['item_id'];
            $storeData['sku_id']     = $sku_id;
            $storeData['quantity']   = $quantity;
            $storeData['sub_stock']  = $itemInfo['sub_stock'];
            $this->__minusStore($storeData);
            $itemInfo['quantity'] = $quantity;
            $this->createOfflineVoucher($itemInfo);
            $db->commit();
            event::fire('tickets.rush', [$skuInfo['item_id'],$quantity]);
            $url = url::action('topwap_ctl_paycenter@freeTicketsFinish',['shop_id' => $itemInfo['shop_id']]);
            return $this->splash('success',$url, '抢购成功',true);
        }
        catch(Exception $e)
        {
            $db->rollback();
            $msg = $e->getMessage();
            return $this->splash('error',null, $msg,true);
        }
    }

    /**
     * @return mixed
     * 小程序抢无偿券
     */
    public function miniRushTichets()
    {
        $post_data = input::get();
        $post_data['item'] = json_decode($post_data['item'], true);
        $quantity = $post_data['item']['quantity'];
        $quantity = $quantity ? $quantity : 1;
        $sku_id = $post_data['item']['sku_id'];

        $db = app::get('systrade')->database();
        $db->beginTransaction();
        try
        {
            $xz_count = $this->getLimitCount($sku_id);

            if($xz_count > 0)
            {
                if((int)$quantity > $xz_count)
                {
                    $msg = "每次限购{$xz_count}件";
                    throw new LogicException($msg);
                }
            }


            $skuInfo = app::get('sysitem')->model('sku')->getRow('item_id', ['sku_id' => $sku_id]);
            $itemInfo = app::get('sysitem')->model('item')->getRow('shop_id, sub_stock, image_default_id, title, supplier_id, start_time, end_time, agent_type', ['item_id' => $skuInfo['item_id']]);

            $voucher_model = app::get('systrade')->model('agent_vocher');
            $filter['user_id'] = userAuth::id();
            $filter['item_id'] = $skuInfo['item_id'];
            $filter['agent_type'] = $itemInfo['agent_type'];
            $filter['careated_time|between'] = [$itemInfo['start_time'],$itemInfo['end_time']];
            $voucher_count  = $voucher_model->count($filter);

            if($xz_count>0 && $voucher_count > $xz_count)
            {
                $msg = "您已经购买过{$voucher_count}件，每次限购{$xz_count}件";
                throw new LogicException($msg);
            }
            $y_count = $voucher_count + $quantity;
            $sur_count = $xz_count-$voucher_count;

            if($xz_count>0 && $y_count > $xz_count)
            {
                $msg="此产品限购{$xz_count}件，您所购数量已达上限";
                throw new LogicException($msg);
            }

            $storeData['item_id']    = $skuInfo['item_id'];
            $storeData['sku_id']     = $sku_id;
            $storeData['quantity']   = $quantity;
            $storeData['sub_stock']  = $itemInfo['sub_stock'];
            $this->__minusStore($storeData);
            $itemInfo['quantity'] = $quantity;
            $this->createOfflineVoucher($itemInfo);
            $db->commit();


            $return_data['err_no'] = 0;
            $return_data['data'] = [];
            $return_data['message'] = '';

            event::fire('tickets.rush', [$skuInfo['item_id'],$quantity]);
        }
        catch(Exception $e)
        {
            $db->rollback();

            $msg = $e->getMessage();
            $return_data['err_no'] = 1001;
            $return_data['data'] = [];
            $return_data['message'] = $msg;
        }
        return response::json($return_data);
    }


    /*
     * 冻结库存
     */
    private function __minusStore($storeData)
    {
        //因为无偿券不需要支付，因此将减库存逻辑合并在一起操作
        //首先进行下单时的减库存相关操作
        $storeData['status'] = 'afterorder';
        $isOrderMinus = app::get('systrade')->rpcCall('item.store.minus', $storeData);
        if(!$isOrderMinus)
        {
            throw new \LogicException(app::get('systrade')->_('冻结库存失败'));
        }
        //再进行支付完成后减库存的相关操作
        $storeData['status'] = 'afterpay';
        $isPayMinus = app::get('systrade')->rpcCall('item.store.minus',$storeData);
        if(!$isPayMinus)
        {
            throw new \LogicException(app::get('systrade')->_('冻结库存失败'));
        }
        return true;
    }
    /*
     * 生成支付核销的卡券
     */
    public function createOfflineVoucher($item_info)
    {
        $voucher_data['tid'] = 0;
        $voucher_data['oid'] = 0;
        $voucher_data['user_id']  = userAuth::id();
        $voucher_data['item_id']  = $item_info['item_id'];
        $voucher_data['title']    = $item_info['title'];
        $voucher_data['shop_id']  = $item_info['shop_id'];
        $voucher_data['supplier_id'] = $item_info['supplier_id'];
        $voucher_data['pic_path'] = $item_info['image_default_id'];
        for($i=0;$i<$item_info['quantity'];$i++)
        {
            app::get('systrade')->rpcCall('trade.create.agent.voucher', $voucher_data);
        }
    }
    /**
     * 验证限制条件
     */
    public function getLimitCount($sku_id)
    {
        $item = app::get('sysitem')->model('sku')->getRow('item_id',array('sku_id' => $sku_id));
        $item_id = $item['item_id'];

        //在商品表里面也有数量限制在这里进行判断
        $item_info = app::get('sysitem')->model('item')->getRow('limit_quantity',array('item_id' => $item_id));
        return $item_info['limit_quantity'];
    }
}