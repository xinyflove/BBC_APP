<?php
class topwap_ctl_supplier_person extends topwap_controller
{
    /**
     * expert_comment服务
     * @var syssupplier_expert_expertComment
     */
    protected $service_person;

    public function __construct()
    {
        parent::__construct();
        $this->service_person = Kernel::single(syssupplier_agentOpration_person::class);
    }

    public function personList()
    {
        $input = input::get();
        $agent_shop_id = $input['agent_shop_id'];
        $curpage = $input['curpage']?$input['curpage']:1;
        $page = $input['page']?$input['page']:20;
        $person = $this->service_person->index([
            'page_no'=>$curpage,
            'page_size'=>$page,
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
        ]);
        $pagedata = array();
        $pagedata['data'] = $person;
        $pagedata['agent_shop_id'] = $agent_shop_id;
        return view::make('topwap/supplier/person.html',$pagedata);
    }

    /**
     * 上拉刷新接口
     */
    public function ajaxPersonList()
    {
        try{
            $input = input::get();
            $curpage = $input['curpage']?$input['curpage']:1;
            $page = $input['page']?$input['page']:20;
            $person = $this->service_person->index([
                'page_no'=>$curpage,
                'page_size'=>$page,
                'field'=>'*',
                'filter'=>[
                    'agent_shop_id'=>$input['agent_shop_id']
                ]
            ]);
            foreach ($person['data'] as $k=>&$v)
            {
                $v['person_image'] = base_storager::modifier($v['person_image']);
            }
            if($person['page_no'] >= $person['page_total'])
            {
                $person['hasmore'] = false;
            }else{
                $person['hasmore'] = true;
            }
            return $this->splash('success',null,$person,true);
        }catch (\Exception $exception)
        {
            return $this->splash('error',null,$exception->getMessage(),true);
        }
    }
}