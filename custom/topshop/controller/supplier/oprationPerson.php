<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2018/5/23
 * Time: 23:23
 */
class topshop_ctl_supplier_oprationPerson extends topshop_controller implements base_tvplaza_restful
{

    /**
     * person service
     * @var syssupplier_agentOpration_person
     */
    protected $service_person;

    /**
     * person service
     * @var syssupplier_personPosition
     */
    protected $service_person_position;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->service_person = Kernel::single(syssupplier_agentOpration_person::class);
        $this->service_person_position = Kernel::single(syssupplier_personPosition::class);
    }

    /**
     * 列表
     * @return mixed
     */
    public function index()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('线下店人员配置');
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
            $pagedata['data'] = $this->service_person->index([
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
                    'b.position_name as position_name',
                ],
                'filter'=>[
                    'a.agent_shop_id='.$input['agent_shop_id']
                ],
                 'order_by'=>'asc'
            ])['data'];
        }catch (\Exception $exception)
        {
            $url = action('topshop_ctl_supplier_list@index');
            echo $exception->getMessage();die;
//            return $this->splash('error',$url,$exception->getMessage(),false);
        }
        return $this->page('topshop/supplier/oprationPerson/index.html',$pagedata);
    }

    /**
     * 创建
     * @return mixed
     */
    public function create()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('线下店人员添加');
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
        $personPositionList = $this->service_person_position->index([
            'field'=>'person_position_id,position_name'
        ]);
        $pagedata = array();
        $pagedata['person_position_data'] = $personPositionList['data'];
        $pagedata['agent_shop_id'] = $input['agent_shop_id'];
        return $this->page('topshop/supplier/oprationPerson/edit.html',$pagedata);
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
        $this->contentHeaderTitle = app::get('topshop')->_('线下店人物编辑');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_supplier_list@index'),
                'title' => app::get('topshop')->_('线下店列表')
            ]
        );
        try{
            $input = input::get();
            if(!$input['agent_opration_person_id'])
            {
                throw new \LogicException('agent_opration_person_id参数不存在');
            }
            $pagedata = array();
            $personPositionList = $this->service_person_position->index([
                'field'=>'person_position_id,position_name'
            ]);
            $pagedata['data'] = $this->service_person->show(
                [
                    'filter'=>[
                        'agent_opration_person_id'=>$input['agent_opration_person_id']
                    ]
                ]
            );
            $pagedata['person_position_data'] = $personPositionList['data'];
            $pagedata['agent_shop_id'] = $pagedata['data']['agent_shop_id'];
            return $this->page('topshop/supplier/oprationPerson/edit.html',$pagedata);
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
            $add['person_name'] = $input['person_name'];
            $add['person_image'] = $input['person_image'];
            $add['person_position_id'] = $input['person_position_id'];
            $add['person_qq'] = $input['person_qq'];
            $add['person_weixin'] = $input['person_weixin'];
            $add['person_phone'] = $input['person_phone'];
            $add['person_email'] = $input['person_email'];
            $add['person_description'] = $input['person_description'];
            $add['order_sort'] = $input['order_sort'];
            $this->service_person->store([
                'store_data'=>$add
            ]);
            $url = action('topshop_ctl_supplier_oprationPerson@index',['agent_shop_id'=>$input['agent_shop_id']]);
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
            if(!$input['agent_opration_person_id'])
            {
                throw new \LogicException('agent_opration_person_id不能为空');
            }
            $this->validator_data($input);
            $update = $input;
            $this->service_person->update(
                [
                    'update_field'=>$update,
                    'filter'=>[
                        'agent_opration_person_id'=>$input['agent_opration_person_id']
                    ]
                ]
            );
            $url = action('topshop_ctl_supplier_oprationPerson@index',['agent_shop_id'=>$input['agent_shop_id']]);
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
            if(!$input['agent_opration_person_id'])
            {
                throw new \LogicException('agent_opration_person_id不能为空');
            }
            $this->service_person->destroy(
                [
                    'filter'=>[
                        'agent_opration_person_id'=>$input['agent_opration_person_id']
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
                $this->service_person->order_sort_upDown([
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
            $filter_arr = [$input['person_phone'],$input['person_qq'],$input['person_weixin']];
            $filter_arr = array_filter($filter_arr);
            $count = count((array)$filter_arr);
            if($count < 1)
            {
                throw new \LogicException('手机号，qq，微信至少填写一项');
            }
            $validator = validator::make(
                [
                    'agent_shop_id'=>$input['agent_shop_id'],
                    'person_name'=>$input['person_name'],
                    'person_image'=>$input['person_image'],
                    'person_email'=>$input['person_email'],
                    'person_phone'=>$input['person_phone'],
                    'person_position_id'=>$input['person_position_id']
                ],
                [
                    'agent_shop_id'=>'required',
                    'person_name'=>'required',
                    'person_image'=>'required',
                    'person_email'=>'email',
                    'person_phone'=>'mobile',
                    'person_position_id'=>'required',
                ],
                [
                    'agent_shop_id'=>'线下店id不存在',
                    'person_name'=>'人员名称不能为空',
                    'person_image'=>'人员图片不能为空',
                    'person_email'=>'邮箱格式不正确',
                    'person_phone'=>'手机号码格式不正确',
                    'person_position_id'=>'人员职位不能为空',
                ]
            );
            $validator->newFails();
        }catch (\Exception $exception)
        {
            throw $exception;
        }
    }
}