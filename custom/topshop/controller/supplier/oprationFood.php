<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2018/5/23
 * Time: 17:36
 */
class topshop_ctl_supplier_oprationFood extends topshop_controller implements base_tvplaza_restful
{
    /**
     * food service
     * @var syssupplier_agentOpration_food
     */
    protected $service_food;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->service_food = Kernel::single(syssupplier_agentOpration_food::class);
    }

    /**
     * 列表
     * @return mixed
     */
    public function index()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('线下店菜品配置');
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
            $pagedata['data'] = $this->service_food->index([
                'filter'=>[
                    'agent_shop_id'=>$input['agent_shop_id']
                ]
            ])['data'];
        }catch (\Exception $exception)
        {
            $url = action('topshop_ctl_supplier_list@index');
            echo $exception->getMessage();die;
//            return $this->splash('error',$url,$exception->getMessage(),false);
        }
        return $this->page('topshop/supplier/oprationFood/index.html',$pagedata);
    }

    /**
     * 创建
     * @return mixed
     */
    public function create()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('线下店菜品添加');
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
        $pagedata = array();
        $pagedata['agent_shop_id'] = $input['agent_shop_id'];
        return $this->page('topshop/supplier/oprationFood/edit.html',$pagedata);
    }

    /**
     * 展示
     * @return mixed
     */
    public function show()
    {
        // 只展示不编辑，暂不用
    }

    /**
     * 编辑
     * @return mixed
     */
    public function edit()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('线下店菜品编辑');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_supplier_list@index'),
                'title' => app::get('topshop')->_('线下店列表')
            ]
        );
        try{
            $input = input::get();
            if(!$input['agent_opration_food_id'])
            {
                throw new \LogicException('agent_opration_food_id参数不存在');
            }
            $pagedata = array();
            $pagedata['data'] = $this->service_food->show(
                [
                    'filter'=>[
                        'agent_opration_food_id'=>$input['agent_opration_food_id']
                    ]
                ]
            );
            $pagedata['agent_shop_id'] = $pagedata['data']['agent_shop_id'];
            return $this->page('topshop/supplier/oprationFood/edit.html',$pagedata);
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
            $add['agent_shop_id'] = $input['agent_shop_id'];
            $add['food_name'] = $input['food_name'];
            $add['food_image'] = $input['food_image'];
            $add['food_price'] = $input['food_price'];
            $add['click_default'] = $input['click_default'];
            $add['food_label'] = $input['food_label'];
            $add['food_description'] = $input['food_description'];
            $add['order_sort'] = $input['order_sort'];
            $this->service_food->store([
                 'store_data'=>$add
            ]);
            $url = action('topshop_ctl_supplier_oprationFood@index',['agent_shop_id'=>$input['agent_shop_id']]);
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
            if(!$input['agent_opration_food_id'])
            {
                throw new \LogicException('agent_shop_food_id不能为空');
            }
            $this->validator_data($input);
            $update = $input;

            $this->service_food->update(
                [
                    'update_field'=>$update,
                    'filter'=>[
                        'agent_opration_food_id'=>$input['agent_opration_food_id']
                    ]
                ]
            );
            $url = action('topshop_ctl_supplier_oprationFood@index',['agent_shop_id'=>$input['agent_shop_id']]);
            return $this->splash('success',$url,'编辑成功',true);
        }catch (\Exception $exception)
        {
            return $this->splash('error',null,$exception->getMessage(),true);
        }
    }

    /**
     * 删除
     * @param $id
     * @return mixed
     */
    public function destroy()
    {
        try{
            $input = input::get();
            if(!$input['agent_opration_food_id'])
            {
                throw new \LogicException('agent_shop_food_id不能为空');
            }
            $this->service_food->destroy(
              [
                  'filter'=>[
                      'agent_opration_food_id'=>$input['agent_opration_food_id']
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
                $this->service_food->order_sort_upDown([
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
                    'agent_shop_id'=>$input['agent_shop_id'],
                    'food_name'=>$input['food_name'],
                    'food_image'=>$input['food_image'],
                    'food_price'=>$input['food_price']
                ],
                [
                    'agent_shop_id'=>'required',
                    'food_name'=>'required',
                    'food_image'=>'required',
                    //'food_price'=>'required|numeric',
                    'food_price'=>'numeric',
                ],
                [
                    'agent_shop_id'=>'线下店id不存在',
                    'food_name'=>'菜名名称不能为空',
                    'food_image'=>'菜品图片不能为空',
                    //'food_price'=>'菜品价格不能为空|价格必须为数字类型',
                    'food_price'=>'价格必须为数字类型',
                ]
            );
            $validator->newFails();
            $label_str = mb_strlen($input['food_label']);
            if($label_str > 7)
            {
                throw  new \LogicException('菜品标签最多为6个字');
            }
        }catch (\Exception $exception)
        {
            throw $exception;
        }
    }
}