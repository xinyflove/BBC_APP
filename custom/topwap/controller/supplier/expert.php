<?php
class topwap_ctl_supplier_expert extends topwap_controller
{
    /**
     * expert_comment服务
     * @var syssupplier_expert_expertComment
     */
    protected $service_expert_comment;

    public function __construct()
    {
        parent::__construct();
        $this->service_expert_comment = Kernel::single(syssupplier_expert_expertComment::class);
    }

    public function expertCommentList()
    {
        $input = input::get();
        $agent_shop_id = $input['agent_shop_id'];
        $pagedata = array();
        $pagedata['agent_shop_id'] = $agent_shop_id;
        return view::make('topwap/supplier/expert.html',$pagedata);
    }

    /**
     * 上拉刷新接口
     */
    public function ajaxCommentList()
    {
        try{
            $input = input::get();
            $agent_shop_id = $input['agent_shop_id'];
            $curpage = $input['curpage']?$input['curpage']:1;
            $page = $input['page']?$input['page']:20;
            $expert_comment = $this->service_expert_comment->index([
                'order_by'=>'asc',
                'page_no'=>$curpage,
                'page_size'=>$page,
                'field'=>[
                    'a.expert_avatar',
                    'a.expert_name',
                    'b.comment_content',
                    'b.comment_rank',
                    "b.created_time"
                ],
                'filter'=>[
                    'b.agent_shop_id='.$agent_shop_id
                ]
            ]);
            foreach ($expert_comment['data'] as $k=>&$v)
            {
                $v['created_time'] = date('Y-m-d',$v['created_time']);
                $v['expert_avatar'] = base_storager::modifier($v['expert_avatar']);
            }
            if($expert_comment['page_no'] >= $expert_comment['page_total'])
            {
                $expert_comment['hasmore'] = false;
            }else{
                $expert_comment['hasmore'] = true;
            }
            return $this->splash('success',null,$expert_comment,true);
        }catch (\Exception $exception)
        {
            return $this->splash('error',null,$exception->getMessage(),true);
        }
    }
}