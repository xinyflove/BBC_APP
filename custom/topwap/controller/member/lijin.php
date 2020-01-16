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

    public function cardExchangeLijin()
    {
        $pagedata['title'] = app::get('topwap')->_('兑换礼金');
        return $this->page('topwap/member/lijin/cardExchangeLijin.html',$pagedata);
    }

    public function doExchange()
    {
        $post_data = input::get();

        $db = app::get('topwap')->database();
        $db->beginTransaction();

        try {
            $cash_card_model = app::get('sysshop')->model('cash_card');

            // 验证卡号
            $card = $cash_card_model->getRow('*', ['card_id' => $post_data['card_id']]);
            if(empty($card)){
                throw new LogicException('卡号或密码错误！');
            }
            // 验证密码
            if($card['exchange_password'] != $post_data['exchange_password']){
                throw new LogicException('卡号或密码错误！');
            }
            // 是否兑换
            if($card['user_id']){
                throw new LogicException('此卡已被兑换，请更换卡号。');
            }

            // 更改为兑换状态
            $result = $cash_card_model->update(['user_id' => userAuth::id(), 'exchange_time' => time()], ['card_id' => $post_data['card_id'], 'user_id' => 0]);
            if(!$result){
                throw new LogicException('系统繁忙，请稍候再试！');
            }

            $updateParams = array(
                'shop_id' => $card['shop_id'],
                'user_id' => userAuth::id(),
                'type' => 'obtain',
                'lijin' => $card['value'],
                'behavior' => '礼金卡兑换',
                'remark' => "当前礼金来自礼金卡：".$card['card_id'],
            );
            // 增加用户礼金
            app::get('topwap')->rpcCall('user.lijin.update',$updateParams);

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            $msg = $e->getMessage();
            return $this->splash('error', null, $msg, true);
        }
        return $this->splash('success', null, "恭喜你，成功兑换{$card['value']}礼金！", true);
    }
}

