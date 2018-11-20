<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2018/5/23
 * Time: 23:23
 */
class topshop_ctl_supplier_oprationVideo extends topshop_controller implements base_tvplaza_restful
{

    /**
     * video service
     * @var syssupplier_agentOpration_video
     */
    protected $service_video;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->service_video = Kernel::single(syssupplier_agentOpration_video::class);
    }

    /**
     * 列表
     * @return mixed
     */
    public function index()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('线下店视频配置');
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
            $pagedata['data'] = $this->service_video->index([
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
        return $this->page('topshop/supplier/oprationVideo/index.html',$pagedata);
    }

    /**
     * 创建
     * @return mixed
     */
    public function create()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('线下店视频添加');
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
        return $this->page('topshop/supplier/oprationVideo/edit.html',$pagedata);
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
        $this->contentHeaderTitle = app::get('topshop')->_('线下店视频编辑');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_supplier_list@index'),
                'title' => app::get('topshop')->_('线下店列表')
            ]
        );
        try{
            $input = input::get();
            if(!$input['agent_opration_video_id'])
            {
                throw new \LogicException('agent_opration_video_id参数不存在');
            }
            $pagedata = array();
            $pagedata['data'] = $this->service_video->show(
                [
                    'filter'=>[
                        'agent_opration_video_id'=>$input['agent_opration_video_id']
                    ]
                ]
            );
            $pagedata['agent_shop_id'] = $pagedata['data']['agent_shop_id'];
            return $this->page('topshop/supplier/oprationVideo/edit.html',$pagedata);
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
            $add['shop_id'] = $this->shopId;
            $add['video_name'] = $input['video_name'];
            $add['video_link'] = $input['video_link'];
            $add['video_desc'] = trim($input['video_desc']);
            $add['order_sort'] = $input['order_sort'];
            $this->service_video->store([
                'store_data'=>$add
            ]);
            $url = action('topshop_ctl_supplier_oprationVideo@index',['agent_shop_id'=>$input['agent_shop_id']]);
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
            if(!$input['agent_opration_video_id'])
            {
                throw new \LogicException('agent_opration_video_id不能为空');
            }
            $this->validator_data($input);
            $update = $input;
            $this->service_video->update(
                [
                    'update_field'=>$update,
                    'filter'=>[
                        'agent_opration_video_id'=>$input['agent_opration_video_id']
                    ]
                ]
            );
            $url = action('topshop_ctl_supplier_oprationVideo@index',['agent_shop_id'=>$input['agent_shop_id']]);
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
            if(!$input['agent_opration_video_id'])
            {
                throw new \LogicException('agent_opration_video_id不能为空');
            }
            $this->service_video->destroy(
                [
                    'filter'=>[
                        'agent_opration_video_id'=>$input['agent_opration_video_id']
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
                $this->service_video->order_sort_upDown([
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
                    'video_name'=>$input['video_name'],
                    'video_link'=>$input['video_desc'],
                    'video_desc'=>trim($input['video_desc'])
                ],
                [
                    'agent_shop_id'=>'required',
                    'video_name'=>'required',
                    'video_link'=>'required',
                    'video_desc'=>'required|max:15'
                ],
                [
                    'agent_shop_id'=>'线下店id不存在',
                    'video_name'=>'视频名称不能为空',
                    'video_link'=>'视频连接不能为空',
                    'video_desc'=>'视频描述不能为空且最大15个字符',
                ]
            );
            $validator->newFails();
        }catch (\Exception $exception)
        {
            throw $exception;
        }
    }
}