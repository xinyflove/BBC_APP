<?php
/**
 * Created by PhpStorm.
 * User: fanglongji
 * Date: 2018-05-31
 * Time: 11:16
 */
class topshop_ctl_supplier_buyer extends topshop_controller implements base_tvplaza_restful{

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

    public function __construct($app)
    {
        parent::__construct($app);
        $this->service_buyer = Kernel::single(syssupplier_buyer_buyer::class);
        $this->service_buyer_tag = Kernel::single(syssupplier_buyer_tag::class);
    }

    /**
     * @return mixed|void
     * 买手推荐列表
     */
    public function index()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('买手推荐');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_supplier_list@index'),
                'title' => app::get('topshop')->_('线下店列表')
            ]
        );
        try
        {
            $trans_data = input::get();
            $page_no = input::get('pages',1);
            $pagedata = array();
            $buyer_info = $this->service_buyer->index(
                [
                    'page_no'  => $page_no,
                    'page_size'=>7,
                    'field'    =>'*',
                    'order_by' =>'created_time desc',
                    'filter'   =>[
                        'shop_id'=>$this->shopId
                    ]
                ]
            );

            if(count($buyer_info['data']) > 0)
            {
                $tag_ids  = array_column($buyer_info['data'], 'groom_tag');
                $tag_ids = array_filter($tag_ids);
                $tag_id_array = [];
                foreach($tag_ids as $tag_id)
                {
                    $tag_id_array = array_merge($tag_id_array, explode(',',$tag_id));
                }
                $tag_id_array = array_unique($tag_id_array);
            }

            if(!empty($tag_id_array))
            {
                $tag_info = $this->service_buyer_tag->index(
                    [
                        'field'    =>'*',
                        'order_by' =>'created_time desc',
                        'filter'   =>[
                            'tag_id|in'=>$tag_id_array
                        ]
                    ]
                );

                $tag_array = array_bind_key($tag_info['data'], 'tag_id');

                foreach($buyer_info['data'] as &$buyers)
                {
                    $tag_name = '';
                    foreach(explode(',',$buyers['groom_tag']) as $tag_id)
                    {
                        if($tag_name != '')
                        {
                            $tag_name .= '/'.$tag_array[$tag_id]['tag_name'];
                        }
                        else
                        {
                            $tag_name = $tag_array[$tag_id]['tag_name'];
                        }
                    }
                    $buyers['tag_name'] = $tag_name;
                }
            }
            $pagedata['buyer_list'] = $buyer_info['data'];
            $totalPage = $buyer_info['page_total'];
            $pagersFilter['pages'] = time();
            $pagers = array(
                'link'=>url::action('topshop_ctl_supplier_buyer@index',$pagersFilter),
                'current' => $page_no,
                'use_app' => 'topshop',
                'total'   => $totalPage,
                'token'   => time(),
            );
            $pagedata['pagers'] = $pagers;
            $pagedata['total'] = $buyer_info['count'];
        }
        catch (\Exception $exception)
        {
            echo $exception->getMessage();die;
        }
        return $this->page('topshop/supplier/buyer/buyer_index.html',$pagedata);
    }

    /**
     * @return mixed|void
     * 添加买手推荐
     */
    public function create()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('买手推荐添加');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_supplier_list@index'),
                'title' => app::get('topshop')->_('线下店列表')
            ]
        );
        $tag_info = $this->service_buyer_tag->index(
            [
                'field'    =>'*',
                'order_by' =>'created_time desc',
                'filter'   =>[
                    'shop_id'=>$this->shopId
                ]
            ]
        );
        $pagedata['tag_list'] = $tag_info['data'];
        return $this->page('topshop/supplier/buyer/buyer_edit.html', $pagedata);
    }

    /**
     * @return html|mixed
     * 编辑买手推荐
     */
    public function edit()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('买手推荐编辑');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_supplier_buyer@index'),
                'title' => app::get('topshop')->_('买手推荐列表')
            ]
        );
        try{
            $input = input::get();
            if(!$input['id'])
            {
                throw new \LogicException('id参数不存在');
            }
            $pagedata = array();
            $buyer_info = $this->service_buyer->show(
                [
                    'filter'=>[
                        'id'=>$input['id']
                    ]
                ]
            );
            if(!empty($buyer_info))
            {
                $buyer_info['groom_tag'] = explode(',',$buyer_info['groom_tag']);
            }
            $pagedata['buyer_row'] = $buyer_info;

            $tag_info = $this->service_buyer_tag->index(
                [
                    'field'    =>'*',
                    'order_by' =>'created_time desc',
                    'filter'   =>[
                        'shop_id'=>$this->shopId
                    ]
                ]
            );
            $pagedata['tag_list'] = $tag_info['data'];

            return $this->page('topshop/supplier/buyer/buyer_edit.html',$pagedata);
        }catch (\Exception $exception)
        {
            echo $exception->getMessage();die;
        }
    }

    /**
     * @return mixed|string
     * 更新买手推荐
     */
    public function update()
    {
        $input = input::get();
        try{
            $this->validator_data($input);

            $input['groom_tag'] = isset($input['groom_tag']) ? implode(',', $input['groom_tag']) : '0';
            $input['groom_buy_url'] = isset($input['groom_buy_url']) ? $input['groom_buy_url'] : ' ';
            $update = $input;
            $this->service_buyer->update(
                [
                    'update_field'=>$update,
                    'filter'=>[
                        'id'=>$input['id']
                    ]
                ]
            );
            $url = action('topshop_ctl_supplier_buyer@index');
            return $this->splash('success',$url,'编辑成功',true);
        }catch (\Exception $exception)
        {
            return $this->splash('error',null,$exception->getMessage(),true);
        }
    }

    public function show()
    {

    }

    /**
     * @return mixed|string
     * 保存买手推荐信息
     */
    public function store()
    {
        $input = input::get();
        try{
            $this->validator_data($input);
            $add = array();
            $add['shop_id'] = $this->shopId;
            $add['groom_title'] = $input['groom_title'];
            $add['groom_summary'] = $input['groom_summary'];
            $add['groom_image'] = $input['groom_image'];
            $add['groom_content'] = $input['groom_content'];
            $add['groom_tag'] = implode(',', $input['groom_tag']);
            $this->service_buyer->store([
                'store_data'=>$add
            ]);
            $url = action('topshop_ctl_supplier_buyer@index');
            return $this->splash('success',$url,'添加成功',true);
        }catch (\Exception $exception)
        {
            return $this->splash('error',null,$exception->getMessage(),true);
        }
    }

    /**
     * @return mixed|string
     * 删除买手推荐信息
     */
    public function destroy()
    {
        try{
            $input = input::get();
            if(!$input['id'])
            {
                throw new \LogicException('id不能为空');
            }
            $this->service_buyer->destroy(
                [
                    'filter'=>[
                        'id'=>$input['id']
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
     * @param $input
     * @throws Exception
     * 验证过来的数据
     */
    protected function validator_data($input)
    {
        try{
            $validator = validator::make(
                [
                    'groom_title'=>$input['groom_title'],
                    'groom_summary'=>$input['groom_summary'],
                    'groom_image'=>$input['groom_image'],
                    'groom_content'=>$input['groom_content'],
                ],
                [
                    'groom_title'=> 'required',
                    'groom_summary'=> 'required',
                    'groom_image'=> 'required',
                    'groom_content'=> 'required',
                ],
                [
                    'groom_title'=> '商品名称不能为空',
                    'groom_summary'=> '商品简介不能为空',
                    'groom_image'=> '商品图片不能为空',
                    'groom_content'=> '商品详情不能为空',
                ]
            );
            $validator->newFails();
        }catch (\Exception $exception)
        {
            throw $exception;
        }
    }
}