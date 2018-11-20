<?php
class topwap_ctl_supplier_food extends topwap_controller
{
    /**
     * expert_comment服务
     * @var syssupplier_expert_expertComment
     */
    protected $service_food;

    public function __construct()
    {
        parent::__construct();
        $this->service_food = Kernel::single(syssupplier_agentOpration_food::class);
    }

    public function foodList()
    {
        $input = input::get();
        $agent_shop_id = $input['agent_shop_id'];
        $curpage = $input['curpage']?$input['curpage']:1;
        $page = $input['page']?$input['page']:20;
        $food = $this->service_food->index([
            'page_no'=>$curpage,
            'page_size'=>$page,
            'filter'=>[
                'agent_shop_id'=>$input['agent_shop_id']
            ],
            'field'=>'agent_opration_food_id'
        ]);
        $pagedata = array();
        $pagedata['data'] = $food;
        $pagedata['agent_shop_id'] = $agent_shop_id;
        return view::make('topwap/supplier/food.html',$pagedata);
    }

    /**
     * 上拉刷新接口
     */
    public function ajaxFoodList()
    {
        try{
            $input = input::get();
            $curpage = $input['curpage']?$input['curpage']:1;
            $page = $input['page']?$input['page']:20;
            $food = $this->service_food->index([
                'page_no'=>$curpage,
                'page_size'=>$page,
                'field'=>'*',
                'filter'=>[
                    'agent_shop_id'=>$input['agent_shop_id']
                ]
            ]);
            foreach ($food['data'] as $k=>&$v)
            {
                $v['food_image'] = base_storager::modifier($v['food_image']);
            }
            if($food['page_no'] >= $food['page_total'])
            {
                $food['hasmore'] = false;
            }else{
                $food['hasmore'] = true;
            }
            return $this->splash('success',null,$food,true);
        }catch (\Exception $exception)
        {
            return $this->splash('error',null,$exception->getMessage(),true);
        }
    }

    /**
     * ajax点赞
     */
    public function ajaxClick()
    {
        if( !userAuth::check() )
        {
            $agent_shop_id = input::get('agent_shop_id');
            $next_page = url::action('topwap_ctl_supplier_food@foodList',['agent_shop_id'=>$agent_shop_id]);
            $url = url::action('topwap_ctl_passport@goLogin', ['next_page' => $next_page]);
            $data['error'] = true;
            $data['redirect'] = $url;
            $data['message'] = app::get('topwap')->_('请登录');
            return response::json($data);
        }
        $input = input::get();
        $agent_opration_food_id = $input['agent_opration_food_id'];
        /** @var dbeav_model $model */
        $model = app::get('syssupplier')->model('agent_opration_food');
        $model_click = app::get('syssupplier')->model('agent_food_click');
        try {
            $click_count = $model_click->count(['user_id' => userAuth::id(), 'agent_opration_food_id' => $agent_opration_food_id]);
            if ($click_count > 0){
                $return_info = [
                    'res' => false,
                    'error_message' => '此商品已经点过赞'
                ];
                return response::json($return_info);die;
            }

            $queryBuilder = app::get('syssupplier')->database()->createQueryBuilder();
            $queryBuilder->update('syssupplier_agent_opration_food', 'a')
                ->set('a.click_num','a.click_num+1')
                ->where('a.agent_opration_food_id='.$agent_opration_food_id);
            $update_res = $queryBuilder->execute();

            $insert_data = [
                'agent_opration_food_id' => $agent_opration_food_id,
                'user_id' => userAuth::id(),
                'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : $_SERVER['HTTP_HOST'],
                'created_at'=>time()            ];
            $model_click->insert($insert_data);
            $return_info = [
                'res' => true,
                'message' => '点赞成功'
            ];

//            $data = $model->getRow('click_num',['agent_opration_food_id'=>$agent_opration_food_id]);
//            $data['click_num']++;
//            $model->update(['click_num'=>$data['click_num']],['agent_opration_food_id'=>$agent_opration_food_id]);
        }catch (\Exception $exception)
        {
            $return_info = [
                'res' => false,
                'error_message' => $exception->getMessage()
            ];
        }
        return response::json($return_info);
//        return $this->splash('success',null,'点赞成功',true);
    }
}

