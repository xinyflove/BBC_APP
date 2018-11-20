<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/5/18
 * Time: 14:41
 */

class topwap_ctl_supplier_agentVideoList extends topwap_controller
{
    /**
     * video 服务
     * @var syssupplier_agentOpration_video
     */
    protected $service_video;

    public function __construct()
    {
        parent::__construct();
        $this->service_video = Kernel::single(syssupplier_agentOpration_video::class);
    }

    public function agentVideoList()
     {
         $pagedata = [];
         if(input::get('shop_id'))
         {
             $pagedata['shop_id'] = input::get('shop_id');
         }
         return view::make('topwap/supplier/video_index.html',$pagedata);
     }

     public function ajaxGetVideoList()
     {
         $postdata = input::get();
         if(!$postdata['current'])
         {
             $page_no = 1;
         }
         else
         {
             $page_no = $postdata['current'];
         }

         if($postdata['video_search_name'])
         {
             $filter = ['video_name|has' => trim($postdata['video_search_name'])];
         }
         if(input::get('shop_id'))
         {
             $filter['shop_id'] = input::get('shop_id');
         }
         $filter_array = [
             'page_no'=>$page_no,
             'page_size'=>5,
             'field'=>'agent_opration_video_id,video_name,video_link,agent_shop_id,created_at,video_desc',
             'order_by'=>'order_sort asc',
             'filter'  => $filter
         ];

         $opration_video = $this->service_video->index($filter_array);
         $opration_video['code'] = 200;
         if($opration_video['count'] > 0)
         {
             $agent_shop_id_array = array_column($opration_video['data'],'agent_shop_id');
             $agent_shop_id_array = array_unique($agent_shop_id_array);
             $params = [
                 'filter'=>[
                     'agent_shop_id|in'=>$agent_shop_id_array
                 ],
             ];
             $agentShopData = app::get('topshop')->rpcCall('supplier.agent.shop.list', $params);
             $agentShopList = array_bind_key($agentShopData['data'],'agent_shop_id');
             foreach($opration_video['data'] as &$video_list)
             {
                 $video_list['addr'] = $agentShopList[$video_list['agent_shop_id']]['addr'];
                 $video_list['upload_date'] = date('Y-m-d',$video_list['created_at']);
                 $video_list['image_path'] =  '/app/topwap/statics/images/milier/play.png';
             }
             $opration_video['hasmore'] = false;
             if($opration_video['page_no'] <= $opration_video['page_total'])
             {
                 $opration_video['hasmore'] = true;
             }
         }

         return response::json($opration_video);exit;
     }
}