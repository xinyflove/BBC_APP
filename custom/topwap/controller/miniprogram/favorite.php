<?php
class topwap_ctl_miniprogram_favorite extends topwap_controller
{
    private $user_id;

    public function __construct()
    {
        $this->user_id = userAuth::getAccountId();
        parent::__construct();
    }

    /**
     * 列表
     * @return mixed
     */
    public function ajaxitems()
    {
        $filter = input::get();
        try {
            $result = $this->_getItems($filter);
            $pagedata['list'] = $result['favitem'];
            /** @var base_db_model $mini_obj */
            $mini_obj = app::get('syssupplier')->model('mini_program');
            foreach ($pagedata['list'] as &$v_list)
            {
                $mini_data = [];
                $item_data = [];
                $v_list['image_default_id'] = base_storager::modifier($v_list['image_default_id']);
                $v_list['good_tags'] = '';
                $v_list['card_name'] = '';  #券名
                $mini_data = $mini_obj->getRow('*',['type'=>2,'goods_id'=>$v_list['item_id']]);
                $fields = 'sysitem_item_count.paid_quantity,item_store';
                $item_data = app::get('topwap')->rpcCall('item.get',['item_id'=>$v_list['item_id'],'fields'=>$fields]);
                $v_list['paid_quantity'] = $item_data['paid_quantity'];
                $v_list['item_store'] = $item_data['store'];
                if($mini_data)
                {
                    $v_list['goods_name'] = $mini_data['good_name'];
                    $v_list['good_tags'] = $mini_data['good_tags'];
                }
                if($item_data['is_virtual'] == 1 && $item_data['confirm_type'] == 1)
                {
                    switch ($item_data['agent_type'])
                    {
                        case 'CASH_VOCHER':
                            $v_list['card_name'] = $item_data['deduct_price'].'元代金券';
                            break;
                        case 'DISCOUNT':
                            $v_list['card_name'] = $item_data['deduct_price'].'折券';
                            break;
                        case 'REDUCE':
                            $v_list['card_name'] = $item_data['deduct_price'].'元满减券';
                            break;
                        default:
                            break;
                    }
                }
            }
            $pagedata['defaultImageId']= kernel::single('image_data_image')->getImageSetting('item');
            foreach ($pagedata['defaultImageId'] as &$v)
            {
                $v['default_image'] = base_storager::modifier($v['default_image']);
            }
            $pagedata['count']= $result['count'];
            $pagedata['total']= $result['total'];
            $pagedata['list'] = array_values($pagedata['list']);
            return response::json([
                'err_no'=>0,
                'data'=>$pagedata,
                'message'=>'列表获取成功'
            ]);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            return response::json([
                'err_no'=>1001,
                'data'=>[
                ],
                'message'=>$msg
            ]);
        }
    }

    /**
     * 删除
     * @return mixed
     */
    public function ajaxDelItem()
    {
        try{
            $params['item_id'] = input::get('id');
            $params['user_id'] = $this->user_id;

            if(empty($params['item_id']))
            {
                return response::json([
                    'err_no'=>1001,
                    'data'=>[
                    ],
                    'message'=>'商品id不能为空'
                ]);
            }

            if (!app::get('topwap')->rpcCall('user.itemcollect.del', $params))
            {
                return response::json([
                    'err_no'=>1001,
                    'data'=>[
                    ],
                    'message'=>'商品收藏删除失败！'
                ]);
            }
            return response::json([
                'err_no'=>0,
                'data'=>[
                ],
                'message'=>'商品收藏删除成功！'
            ]);
        }catch (\Exception $exception)
        {
            return response::json([
                'err_no'=>1001,
                'data'=>[
                ],
                'message'=>$exception->getMessage()
            ]);
        }

    }

    // 获取收藏的商品
    protected function _getItems($filter)
    {
        if(!$filter['pages'])
        {
            $filter['pages'] = 1;
        }
        $params = array(
            'page_no' => $filter['pages'],
            'page_size' => 10,
            'fields' =>'*',
            'user_id'=>$this->user_id,
        );
        $favData = app::get('topwap')->rpcCall('user.itemcollect.list',$params);
        $count = $favData['itemcount'];
        $favList = $favData['itemcollect'];
        $pagedata['favitem']= $favList;
        //处理翻页数据
        if( $count > 0 ) $totalPage = ceil($count/10);
        $pagedata['count'] = (int)$totalPage;
        $pagedata['total'] = (int)$count;
        return $pagedata;
    }
}
