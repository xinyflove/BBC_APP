<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2018/5/28
 * Time: 15:12
 */

class topshop_ctl_supplier_expert_expert extends topshop_controller implements base_tvplaza_restful
{
    /**
     * expert service
     * @var syssupplier_expert_expert
     */
    protected $service_expert;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->service_expert = Kernel::single(syssupplier_expert_expert::class);
    }

    /**
     * 列表
     * @return mixed
     */
    public function index()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('专家列表');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_supplier_list@index'),
                'title' => app::get('topshop')->_('线下店列表')
            ]
        );
        try{
            $input = input::get();
            $pagedata = array();
            $pagedata['data'] = $this->service_expert->index([
                'filter'=>[
                    'shop_id'=>$this->shopId
                ]
            ])['data'];
        }catch (\Exception $exception)
        {
            $url = action('topshop_ctl_supplier_list@index');
            echo $exception->getMessage();die;
        }
        return $this->page('topshop/supplier/expert/expert_index.html',$pagedata);
    }

    /**
     * 创建
     * @return mixed
     */
    public function create()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('专家添加');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_supplier_list@index'),
                'title' => app::get('topshop')->_('线下店列表')
            ]
        );
        return $this->page('topshop/supplier/expert/expert_edit.html');
    }

    /**
     * 展示
     * @return mixed
     */
    public function show()
    {
        //暂未使用
    }

    /**
     * 编辑
     * @return mixed
     */
    public function edit()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('专家编辑');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_supplier_list@index'),
                'title' => app::get('topshop')->_('专家列表')
            ]
        );
        try{
            $input = input::get();
            if(!$input['expert_id'])
            {
                throw new \LogicException('expert_id参数不存在');
            }
            $pagedata = array();
            $pagedata['data'] = $this->service_expert->show(
                [
                    'filter'=>[
                        'expert_id'=>$input['expert_id']
                    ]
                ]
            );
            return $this->page('topshop/supplier/expert/expert_edit.html',$pagedata);
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
            $add['shop_id'] = $this->shopId;
            $add['expert_name'] = $input['expert_name'];
            $add['expert_profile'] = $input['expert_profile'];
            $add['comment_password'] = $input['comment_password'];
            $add['expert_account'] = $input['expert_account'];
            $add['expert_avatar'] = $input['expert_avatar'];
            $add['order_sort'] = $input['order_sort'];
            $this->service_expert->store([
                'store_data'=>$add
            ]);
            $url = action('topshop_ctl_supplier_expert_expert@index');
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
            if(!$input['expert_id'])
            {
                throw new \LogicException('expert_id不能为空');
            }
            $this->validator_data($input);
            $update = $input;
            $this->service_expert->update(
                [
                    'update_field'=>$update,
                    'filter'=>[
                        'expert_id'=>$input['expert_id']
                    ]
                ]
            );
            $url = action('topshop_ctl_supplier_expert_expert@index');
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
            if(!$input['expert_id'])
            {
                throw new \LogicException('expert_id不能为空');
            }
            $this->service_expert->destroy(
                [
                    'filter'=>[
                        'expert_id'=>$input['expert_id']
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
                $this->service_expert->order_sort_upDown([
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
        try{
            $validator = validator::make(
                [
                    'expert_name'=>$input['expert_name'],
                    'expert_account'=>$input['expert_account'],
                    'expert_avatar'=>$input['expert_avatar'],
                ],
                [
                    'expert_name'=>'required',
                    'expert_account'=>'required',
                    'expert_avatar'=>'required',
                ],
                [
                    'expert_name'=>'专家名称不能为空',
                    'expert_account'=>'专家账号不能为空',
                    'expert_avatar'=>'专家头像不能为空',
                ]
            );
            $validator->newFails();
            $expert = $this->service_expert->show([
                'filter'=>[
                    'expert_account'=>$input['expert_account']
                ]
            ]);
            if(!empty($expert))
            {
                throw new \LogicException('专家账号已存在');
            }
        }catch (\Exception $exception)
        {
            throw $exception;
        }
    }
}