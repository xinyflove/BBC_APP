<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2018/5/28
 * Time: 15:12
 */
class topshop_ctl_supplier_expert_expertComment extends topshop_controller implements base_tvplaza_restful
{

    /**
     * expert_comment service
     * @var syssupplier_expert_expertComment
     */
    protected $service_expert_comment;

    /**
     * expert service
     * @var syssupplier_expert_expert
     */
    protected $service_expert;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->service_expert_comment = Kernel::single(syssupplier_expert_expertComment::class);
        $this->service_expert = Kernel::single(syssupplier_expert_expert::class);
    }

    /**
     * 线下店评论列表
     * @return mixed
     */
    public function index()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('线下店专家评论配置');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_supplier_list@index'),
                'title' => app::get('topshop')->_('线下店列表')
            ]
        );
        try{
            $input = input::get();
            if(!$input['agent_shop_id'])
            {
                throw new \LogicException('agent_shop_id不能为空！');
            }
            $pagedata = array();
            $pagedata['agent_shop_id'] = $input['agent_shop_id'];
            $pagedata['data'] = $this->service_expert_comment->index([
                'field'=>[
                    'b.expert_comment_id as expert_comment_id',
                    'b.expert_id as expert_id',
                    'a.expert_name as expert_name','b.agent_shop_id as agent_shop_id',
                    'b.comment_content as comment_content',
                    'b.comment_rank as comment_rank',
                    'b.order_sort as order_sort'
                ],
                'filter'=>[
                    'b.agent_shop_id = '.$input['agent_shop_id'],
                ],
                'order_by'=>'asc'
            ])['data'];
        }catch (\Exception $exception)
        {
            $url = action('topshop_ctl_supplier_list@index');
            echo $exception->getMessage();die;
        }
        return $this->page('topshop/supplier/expert/expert_comment_index.html',$pagedata);
    }

    /**
     * 创建
     * @return mixed
     */
    public function create()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('线下店专家评论添加');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_supplier_list@index'),
                'title' => app::get('topshop')->_('线下店列表')
            ]
        );
        $input = input::get();
        if(!$input['agent_shop_id'])
        {
            echo 'agent_shop_id不能为空！';
        }
        $expertList = $this->service_expert->index([
            'field'=>'expert_id,expert_name'
        ]);
        $pagedata = array();
        $pagedata['agent_shop_id'] = $input['agent_shop_id'];
        $pagedata['expert_data'] = $expertList['data'];
        return $this->page('topshop/supplier/expert/expert_comment_edit.html',$pagedata);
    }

    /**
     * 展示
     * @return mixed
     */
    public function show()
    {
        //暂未开启
    }

    /**
     * 编辑
     * @return mixed
     */
    public function edit()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('线下店专家评论编辑');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_supplier_list@index'),
                'title' => app::get('topshop')->_('线下店列表')
            ]
        );
        try{
            $input = input::get();
//            dump($input);die;
            if(!$input['expert_comment_id'])
            {
                throw new \LogicException('expert_comment_id参数不存在');
            }
            $expertList = $this->service_expert->index([
                'field'=>'expert_id,expert_name'
            ]);
            $pagedata = array();
            $pagedata['data'] = $this->service_expert_comment->show(
                [
                    'filter'=>[
                        'expert_comment_id'=>$input['expert_comment_id']
                    ]
                ]
            );
            $pagedata['expert_data'] = $expertList['data'];
            $pagedata['agent_shop_id'] = $pagedata['data']['agent_shop_id'];
            return $this->page('topshop/supplier/expert/expert_comment_edit.html',$pagedata);
        }catch (\Exception $exception)
        {
            echo $exception->getMessage();die;
        }
    }

    /**
     * 保存
     * @return mixed
     */
    public function store()
    {
        $input = input::get();
        try{
            $this->validator_data($input);
            $add = array();
            $add['expert_id'] = $input['expert_id'];
            $add['agent_shop_id'] = $input['agent_shop_id'];
            $add['comment_content'] = $input['comment_content'];
            $add['comment_rank'] = ceil($input['comment_rank']);
            $add['order_sort'] = $input['order_sort'];
            $this->service_expert_comment->store([
                'store_data'=>$add
            ]);
            $url = action('topshop_ctl_supplier_expert_expertComment@index',['agent_shop_id'=>$input['agent_shop_id']]);
            return $this->splash('success',$url,'添加成功',true);
        }catch (\Exception $exception)
        {
            return $this->splash('error',null,$exception->getMessage(),true);
        }
    }

    /**
     * 更新
     * @return mixed
     */
    public function update()
    {
        $input = input::get();
        try{
            if(!$input['expert_comment_id'])
            {
                throw new \LogicException('expert_comment_id不能为空');
            }
            $this->validator_data($input);
            $update = $input;
            $this->service_expert_comment->update(
                [
                    'update_field'=>$update,
                    'filter'=>[
                        'expert_comment_id'=>$input['expert_comment_id']
                    ]
                ]
            );
            $url = action('topshop_ctl_supplier_expert_expertComment@index',['agent_shop_id'=>$input['agent_shop_id']]);
            return $this->splash('success',$url,'编辑成功',true);
        }catch (\Exception $exception)
        {
            return $this->splash('error',null,$exception->getMessage(),true);
        }
    }

    /**
     * 删除
     * @return mixed
     */
    public function destroy()
    {
        try{
            $input = input::get();
            if(!$input['expert_comment_id'])
            {
                throw new \LogicException('expert_comment_id不能为空');
            }
            $this->service_expert_comment->destroy(
                [
                    'filter'=>[
                        'expert_comment_id'=>$input['expert_comment_id']
                    ]
                ]
            );
            return $this->splash('success',null,'删除成功！',true);
        }catch (\Exception $exception)
        {
            return $this->splash('error',null,$exception->getMessage(),true);
        }
    }

    /**
     * 编辑排序
     */
    public function order_sort()
    {
        try{
            $input = input::get();

            if($input['pk'] && $input['opt'])
            {
                $this->service_expert_comment->order_sort_upDown([
                    'pk'=>$input['pk'],
                    'opt'=>$input['opt']
                ]);

                $msg = '操作成功';
                return $this->splash('success', null, $msg, true);
            }
            $msg = '操作失败';
            return $this->splash('error', null, $msg, true);
        }catch (\Exception $exception)
        {
            return $this->splash('error',null,$exception->getMessage(),true);
        }
    }

    protected function validator_data($input)
    {
        if(ceil($input['comment_rank']) <1 || ceil($input['comment_rank'])>5)
        {
            throw new \LogicException('评星必须为1到5');
        }
        try{
            $validator = validator::make(
                [
                    'expert_id'=>$input['expert_id'],
                    'agent_shop_id'=>$input['agent_shop_id'],
                    'comment_content'=>$input['comment_content'],
                    'comment_rank'=>$input['comment_rank']
                ],
                [
                    'expert_id'=>'required',
                    'agent_shop_id'=>'required',
                    'comment_content'=>'required',
                    'comment_rank'=>'required'
                ],
                [
                    'expert_id'=>'专家expert_id不能为空',
                    'agent_shop_id'=>'线下店id不能为空',
                    'comment_content'=>'专家评论不能为空',
                    'comment_rank'=>'专家评级不能为空',
                ]
            );
            $validator->newFails();
        }catch (\Exception $exception)
        {
            throw $exception;
        }
    }
}