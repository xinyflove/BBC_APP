<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/5/18
 * Time: 14:41
 */

class topwap_ctl_supplier_buyer extends topwap_controller
{
    /**
     * @var mixed
     * 买手推荐服务
     */
    private $service_buyer;
    /**
     * @var mixed
     * 买手标签tag
     */
    private $service_buyer_tag;

    public function __construct()
    {
        parent::__construct();
        $this->service_buyer = Kernel::single(syssupplier_buyer_buyer::class);
        $this->service_buyer_tag = Kernel::single(syssupplier_buyer_tag::class);
    }

    /**
     * @return mixed
     * 买手推荐列表
     */
    public function buyerIndex()
    {
        $fields = 'template_path,widget_type,params,widget_type';
        $filter = array(
            'page_type'=>'buyer',
            'widget_type'=>'slider_ad',
            'disabled'=>'0',
            'deleted'=>'0',
        );
        $widget_instance = app::get('syssupplier')->model('widget_instance')->getRow($fields, $filter);
        $widget_instance['data'] = unserialize($widget_instance['params']);
        $pagedata['widget'] = $widget_instance;
        unset($widget_instance['params']);
        if(input::get('shop_id'))
        {
            $pagedata['shop_id'] = input::get('shop_id');
        }
        return view::make('topwap/supplier/buyer_index.html',$pagedata);
    }

    /**
     * @return mixed
     * 买手列表详情
     */
     public function buyerDetail()
     {
         $id = input::get('id');
         try
         {
             $buyer_row = $this->service_buyer->show(
                 [
                     'field'=>'*',
                     'filter'=>[
                         'id'=>$id
                     ]
                 ]
             );
             $pagedata['buyer_row'] = $buyer_row;
             return view::make('topwap/supplier/buyer_detail.html',$pagedata);
         }catch (\Exception $exception)
         {
             echo $exception->getMessage();die;
         }
     }

    /**
     * @return mixed
     * 获取买手推荐列表
     */
     public function ajaxGetBuyerList()
     {
         $postdata = input::get();
         $page_no = input::get('page_no',1);
         $page_size = input::get('page_size',1);
         $laud_id_array = [];
         if($postdata['buyer_search_name'])
         {
             $filter = ['groom_title|has' => trim($postdata['buyer_search_name'])];
         }
         try
         {
             if(input::get('shop_id'))
             {
                 $filter['shop_id'] = input::get('shop_id');
             }
             $filter_array = [
                 'page_no'   => $page_no,
                 'page_size' => $page_size,
                 'field'     =>'*',
                 'order_by'  =>'created_time desc',
                 'filter'    =>$filter,
             ];

             $buyer_info = $this->service_buyer->index(
                 $filter_array
             );
             if(count($buyer_info['data']) > 0)
             {
                 $tag_ids  = array_column($buyer_info['data'], 'groom_tag');
                 $groom_id_array  = array_column($buyer_info['data'], 'id');
                 $tag_ids = array_filter($tag_ids);
                 $tag_id_array = [];
                 foreach($tag_ids as $tag_id)
                 {
                     $tag_id_array = array_merge($tag_id_array, explode(',',$tag_id));
                 }
                 $tag_id_array = array_unique($tag_id_array);

                 if(userAuth::check())
                 {
                     $buyer_groom_laud_mdl = app::get('syssupplier')->model('buyer_groom_laud');
                     $laud_list = $buyer_groom_laud_mdl->getList('*', ['user_id'=> userAuth::id(), 'buyer_groom_id|in' => $groom_id_array]);
                     $laud_id_array = array_column($laud_list, 'buyer_groom_id');
                 }
             }

             if(!empty($tag_id_array)) {
                 $tag_info = $this->service_buyer_tag->index(
                     [
                         'field' => '*',
                         'order_by' => 'created_time desc',
                         'filter' => [
                             'tag_id|in' => $tag_id_array
                         ]
                     ]
                 );
                 $tag_array = array_bind_key($tag_info['data'], 'tag_id');
             }

             foreach($buyer_info['data'] as &$buyers)
             {
                 $tag_name = [];
                 if(!empty($tag_id_array))
                 {
                     foreach(explode(',',$buyers['groom_tag']) as $tag_id)
                     {
                         if($tag_array[$tag_id]['tag_name'])
                         {
                             array_push($tag_name, $tag_array[$tag_id]['tag_name']);
                         }
                     }
                 }
                 if(!empty($laud_id_array) && in_array($buyers['id'], $laud_id_array))
                 {
                     $buyers['laud_class'] =  'user-liked';
                 }
                 else
                 {
                     $buyers['laud_class'] =  'user-like';
                 }
                 $buyers['tag_name'] = $tag_name;
                 $buyers['groom_image'] = base_storager::modifier($buyers['groom_image']);
                 $buyers['href'] = url::action('topwap_ctl_supplier_buyer@buyerDetail',['id'=>$buyers['id']]);
             }

             return response::json($buyer_info);
         }
         catch (\Exception $exception) {
             $buyer_info = [
                 'error_message'=>$exception->getMessage()
             ];
             return response::json($buyer_info);
         }
     }

     /**
     * @return mixed
      * 点赞
     */
     public function laud()
     {
         if( !userAuth::check() )
         {
             $next_page = url::action('topwap_ctl_supplier_buyer@buyerIndex');
             $url = url::action('topwap_ctl_passport@goLogin', ['next_page' => $next_page]);
             $data['error'] = true;
             $data['redirect'] = $url;
             $data['message'] = app::get('topwap')->_('请登录');
             return response::json($data);
         }
         $id = input::get('id');
         try
         {
             $buyer_groom_laud_mdl = app::get('syssupplier')->model('buyer_groom_laud');
             $laud_count = $buyer_groom_laud_mdl->count(['user_id'=> userAuth::id(), 'buyer_groom_id' => $id]);
             if($laud_count > 0)
             {
                 $return_info = [
                     'res' => false,
                     'error_message' => '此商品已经点过赞'
                 ];
                 return response::json($return_info);die;
             }

             $queryBuilder = app::get('syssupplier')->database()->createQueryBuilder();
             $queryBuilder->update('syssupplier_buyer_groom', 'sbg')
                                        ->set('sbg.laud_quantity','sbg.laud_quantity+1')
                                        ->where('sbg.id='.$id);
             $update_res = $queryBuilder->execute();

             $insert_data = [
                 'buyer_groom_id' => $id,
                 'user_id' => userAuth::id(),
                 'created_time' => time(),
                 'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : $_SERVER['HTTP_HOST'],
             ];
             $buyer_groom_laud_mdl->insert($insert_data);
             $return_info = [
                 'res' => true,
                 'error_message' => ''
             ];
         }
         catch (\Exception $exception) {
             $return_info = [
                 'res' => false,
                 'error_message' => $exception->getMessage()
             ];
         }
        return response::json($return_info);
     }

}