<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/5/18
 * Time: 14:41
 */

class topwap_ctl_supplier_agentShopInfo extends topwap_controller
{
    /**
     * food 服务
     * @var syssupplier_agentOpration_food
     */
    protected $service_food;

    /**
     * person 服务
     * @var syssupplier_agentOpration_person
     */
    protected $service_person;

    /**
     * video 服务
     * @var syssupplier_agentOpration_video
     */
    protected $service_video;

    /**
     * expert_comment 服务
     * @var syssupplier_expert_expertComment
     */
    protected $service_expert_comment;

    public function __construct()
    {
        parent::__construct();
        $this->service_food = Kernel::single(syssupplier_agentOpration_food::class);
        $this->service_person = Kernel::single(syssupplier_agentOpration_person::class);
        $this->service_video = Kernel::single(syssupplier_agentOpration_video::class);
        $this->service_expert_comment = Kernel::single(syssupplier_expert_expertComment::class);
    }

    public function agentShopDetail()
     {
         //获取线下店信息
         $receive_data = input::get();
         $shop_id = $receive_data['shop_id'];
         $agent_shop_id = $receive_data['agent_shop_id'];
         $supplier_id = $receive_data['supplier_id'];
         //此供应商下的所有线下店
         $agent_shop_info = app::get('topwap')->rpcCall('supplier.agent.shop.list',['shop_id'=>$shop_id,'supplier_id'=>$supplier_id]);
         $agent_shop_list = array_bind_key($agent_shop_info['data'],'agent_shop_id');
         $current_agent_shop = $agent_shop_list[$agent_shop_id];
         $current_agent_shop['carouse_image_list'] = explode(',',$current_agent_shop['carouse_image_list']);
         unset($agent_shop_list[$agent_shop_id]);
         $pagedata['agent_shop_list'] = $agent_shop_list;
         $pagedata['current_agent_shop'] = $current_agent_shop;




         //微信分享
         $wxAppId = app::get('site')->getConf('site.appId');
         $wxAppsecret = app::get('site')->getConf('site.appSecret');
         $jssdk = new topwap_wechat_jssdk($wxAppId,$wxAppsecret);
         $pagedata['signPackage'] = $jssdk->GetSignPackage();



         //本线下店发放的优惠活动
         //topwap_ctl_offlinepay_pay@getOfflinePayCoupon
         $voucher_list = $this->getThisAgentShopCoupon($agent_shop_id);
         $pagedata['voucher_list'] = $voucher_list;
        //获取全场打折
         $all_hold_filter['disabled'] = 0;
         $all_hold_filter['agent_shop_id'] = $agent_shop_id;
         $all_hold_info = app::get('topwap')->rpcCall('supplier.agent.activity.list', $all_hold_filter)['data'][0];
         $pagedata['all_hold_info'] = $all_hold_info;
         //本线下店的推荐菜
         $pagedata['opration_food'] = $this->service_food->index(
             [
                 'page_no'=>1,
                 'page_size'=>10,
                 'field'=>'food_name,food_image',
                 'order_by'=>'order_sort asc',
                 'filter'=>[
                     'agent_shop_id'=>$agent_shop_id
                 ]
             ]
         );

         //本线下店的人员配置
         $pagedata['opration_person'] = $this->service_person->index(
             [
                 'page_no'=>1,
                 'page_size'=>10,
                 'field'=>[
                     'a.agent_opration_person_id as agent_opration_person_id',
                     'a.agent_shop_id as agent_shop_id',
                     'a.person_image as person_image',
                     'a.person_name as person_name',
                     'a.order_sort as order_sort',
                     'a.person_phone as person_phone',
                     'a.person_qq as person_qq',
                     'a.person_weixin as person_weixin',
                     'a.person_email as person_email',
                     'a.person_description as person_description',
                     'a.order_sort as order_sort',
                     'b.person_position_id as person_position_id',
                     'b.position_name as person_name',
                 ],
                 'order_by'=>'asc',
                 'filter'=>[
                     'agent_shop_id='.$agent_shop_id
                 ]
             ]
         );
         //本线下店的视频
         $pagedata['opration_video'] = $this->service_video->index(
             [
                 'page_no'=>1,
                 'page_size'=>10,
                 'field'=>'video_name,video_link',
                 'order_by'=>'order_sort asc',
                 'filter'=>[
                     'agent_shop_id'=>$agent_shop_id
                 ]
             ]
         );
         //获取本线下店简介
         $pagedata['shop_profile'] = $current_agent_shop['shop_profile'];
         //本线下店评论
         $expert_comment = $this->service_expert_comment->index([
             'field'=>[
                 'a.expert_avatar',
                 'a.expert_name',
                 'b.comment_content',
                 'b.comment_rank',
                 "b.created_time"
             ],
             'page_no'=>1,
             'page_size'=>1,
             'order_by'=>'asc',
             'filter'=>[
                 'b.agent_shop_id='.$agent_shop_id
             ]
         ]);
         foreach ($expert_comment['data'] as $k=>&$value)
         {
             $value['created_time'] = date('Y-d',$value['created_time']);
             for($i = 0, $iMax = ceil($value['comment_rank']); $i< $iMax; $i++)
             {
                 $value['comment_rank_arr'][] = '<i class="icon"></i>';
             }
         }
         $pagedata['shop_comment'] = $expert_comment;
         $pagedata['expert_comment_count'] = $expert_comment['count'];
//         dump($pagedata);die;
         return view::make('topwap/supplier/agent_shop_info.html', $pagedata);
     }
     /**
      * 获取该线下店的优惠券
      */
    public function getThisAgentShopCoupon($agent_shop_id)
    {
        $filter['confirm_type']  = 1;
        $filter['approve_status']  = 'onsale';
        $filter['orderBy']       = 'created_time DESC';
        $filter['use_platform']  = '0,2';
        $filter['disabled']      = 0;
        $filter['agent_shop_id'] = ','.$agent_shop_id.',';
        $filter['start_time']    = time();
        $filter['end_time']      = time();
        $filter['fields']    = "item_id,title,agent_type,min_consum,max_deduct_price,deduct_price,agent_price,image_default_id,store.store,store.freez";

        $item_info = app::get('topwap')->rpcCall('item.search',$filter);

        $show_item_array = [];
        $can_be_get = [];
        $cannot_be_get = [];
        foreach($item_info['list'] as $item)
        {
            if($item['agent_type'] === 'CASH_VOCHER')
            {
                $item['show_price'] = floatval($item['deduct_price']);
                $item['show_unit'] = '元';
                $item['show_title'] = '代金券';
            }
            elseif($item['agent_type'] === 'DISCOUNT')
            {
                $item['show_price'] = floatval($item['deduct_price']);
                $item['show_unit'] = '折';
                $item['show_title'] = '满折券';
            }
            elseif($item['agent_type'] === 'REDUCE')
            {
                $item['show_price'] = floatval($item['deduct_price']);
                $item['show_unit'] = '元';
                $item['show_title'] = '满减券';
            }
            if((int)$item['min_consum'])
            {
                $item['min_desc']  = '最低消费：'.floatval($item['min_consum']).'元';
            }
            else
            {
                $item['min_desc'] = '最低消费：无限制';
            }
            if((int)$item['max_deduct_price'])
            {
                $item['max_desc'] = '最大抵扣：'.floatval($item['max_deduct_price']).'元';
            }
            else
            {
                $item['max_desc'] = '最大抵扣：无限制';
            }
            $real_store = $item['store'] - $item['freez'];
            if($real_store <= 10)
            {
                $item['re_percent'] = '仅剩余'.$real_store.'件';
            }
            else
            {
                $item['re_percent'] = '剩余'.round(($real_store/$item['store'])*100).'%';
            }
            $item['real_store'] = $real_store;
            if($real_store === 0)
            {
                $cannot_be_get[] = $item;
            }
            else
            {
                $can_be_get[] = $item;
            }
        }
        $show_item_array = array_merge($can_be_get,$cannot_be_get);
        $return_data = $show_item_array;
        return $return_data;
    }
}