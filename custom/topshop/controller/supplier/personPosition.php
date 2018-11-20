<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2018/6/1
 * Time: 9:39
 */
class topshop_ctl_supplier_personPosition extends topshop_controller implements base_tvplaza_restful
{
    /**
     * @var syssupplier_personPosition
     */
    protected $service_person_position;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->service_person_position = Kernel::single(syssupplier_personPosition::class);
    }


    /**
     * 列表
     * @return mixed
     */
    public function index()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('线下店人员职位配置');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_supplier_list@index'),
                'title' => app::get('topshop')->_('线下店列表')
            ]
        );
        try{
            $input = input::get();
            $pagedata['data'] = $this->service_person_position->index([])['data'];
        }catch (\Exception $exception)
        {
            $url = action('topshop_ctl_supplier_list@index');
            echo $exception->getMessage();die;
//            return $this->splash('error',$url,$exception->getMessage(),false);
        }
        return $this->page('topshop/supplier/personPosition_index.html',$pagedata);
    }

    /**
     * 创建
     * @return mixed
     */
    public function create()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('线下店人员职位添加');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_supplier_list@index'),
                'title' => app::get('topshop')->_('线下店列表')
            ]
        );
        $input = input::get();
        return $this->page('topshop/supplier/personPosition_edit.html');
    }

    /**
     * 展示
     * @return mixed
     */
    public function show()
    {
        //暂时未用到
    }

    /**
     * 编辑
     * @return mixed
     */
    public function edit()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('线下店人员职位编辑');
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
            $pagedata['data'] = $this->service_person_position->show(
                [
                    'filter'=>[
                        'person_position_id'=>$input['person_position_id']
                    ]
                ]
            );
            return $this->page('topshop/supplier/personPosition_edit.html',$pagedata);
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
            $add['position_name'] = $input['position_name'];
            $add['position_profile'] = $input['position_profile'];
            $add['order_sort'] = $input['order_sort'];
            $this->service_person_position->store([
                'store_data'=>$add
            ]);
            $url = action('topshop_ctl_supplier_personPosition@index');
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
            $this->validator_data($input);
            $update = $input;
            $this->service_person_position->update(
                [
                    'update_field'=>$update,
                    'filter'=>[
                        'person_position_id'=>$input['person_position_id']
                    ]
                ]
            );
            $url = action('topshop_ctl_supplier_personPosition@index');
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
            if(!$input['person_position_id'])
            {
                throw new \LogicException('person_position_id不能为空');
            }
            $this->service_person_position->destroy(
                [
                    'filter'=>[
                        'person_position_id'=>$input['person_position_id']
                    ]
                ]
            );
            return $this->splash('success',null,'删除成功！',true);
        }catch (\Exception $exception)
        {
            return $this->splash('error',null,$exception->getMessage(),true);
        }
    }

    public function order_sort()
    {
        $input = input::get();

        if($input['pk'] && $input['opt'])
        {
            $this->service_person_position->order_sort_upDown([
                'pk'=>$input['pk'],
                'opt'=>$input['opt']
            ]);

            $msg = '操作成功';
            return $this->splash('success', null, $msg, true);
        }
        $msg = '操作失败';
        return $this->splash('error', null, $msg, true);
    }

    protected function validator_data($input)
    {
        try{
            $validator = validator::make(
                [
                    'position_name'=>$input['position_name'],
                ],
                [
                    'position_name'=>'required',
                ],
                [
                    'position_name'=>'人员职位名称不能为空',
                ]
            );
            $validator->newFails();
        }catch (\Exception $exception)
        {
            throw $exception;
        }
    }
}