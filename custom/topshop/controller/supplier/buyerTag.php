<?php
/**
 * Created by PhpStorm.
 * User: fanglongji
 * Date: 2018-05-31
 * Time: 11:16
 */
class topshop_ctl_supplier_buyerTag extends topshop_controller implements base_tvplaza_restful{

    /**
     * lib
     */
    private $service_buyer_tag;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->service_buyer_tag = Kernel::single(syssupplier_buyer_tag::class);
    }

    /**
     * @return mixed|void
     * 买手标签列表
     */
    public function index()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('买手标签');
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
            $buyer_info = $this->service_buyer_tag->index(
                [
                    'page_no'  => $page_no,
                    'page_size'=>7,
                    'field'    =>'*',
                    'order_by' =>'created_time asc',
                    'filter'   =>[
                        'shop_id'=>$this->shopId
                    ]
                ]
            );
            $pagedata['tag_list'] = $buyer_info['data'];
            $totalPage = $buyer_info['page_total'];
            $pagersFilter['pages'] = time();
            $pagers = array(
                'link'=>url::action('topshop_ctl_supplier_buyerTag@index',$pagersFilter),
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
        return $this->page('topshop/supplier/tag/tag_index.html',$pagedata);
    }

    /**
     * @return mixed|void
     * 添加买手标签
     */
    public function create()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('买手标签添加');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_supplier_list@index'),
                'title' => app::get('topshop')->_('线下店列表')
            ]
        );
        return $this->page('topshop/supplier/tag/tag_edit.html');
    }

    /**
     * @return html|mixed
     * 编辑买手标签
     */
    public function edit()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('买手标签编辑');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_supplier_buyerTag@index'),
                'title' => app::get('topshop')->_('买手标签列表')
            ]
        );
        try{
            $input = input::get();
            if(!$input['tag_id'])
            {
                throw new \LogicException('tag_id参数不存在');
            }
            $pagedata = array();
            $buyer_info = $this->service_buyer_tag->show(
                [
                    'filter'=>[
                        'tag_id'=>$input['tag_id']
                    ]
                ]
            );
            $pagedata['tag_row'] = $buyer_info;
            return $this->page('topshop/supplier/tag/tag_edit.html',$pagedata);
        }catch (\Exception $exception)
        {
            echo $exception->getMessage();die;
        }
    }

    /**
     * @return mixed|string
     * 更新买手标签
     */
    public function update()
    {
        $input = input::get();
        try{
            $this->validator_data($input);
            $update = $input;
            $this->service_buyer_tag->update(
                [
                    'update_field'=>$update,
                    'filter'=>[
                        'tag_id'=>$input['tag_id']
                    ]
                ]
            );
            $url = action('topshop_ctl_supplier_buyerTag@index');
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
     * 保存买手标签
     */
    public function store()
    {
        $input = input::get();
        try{
            $this->validator_data($input);
            $add = array();
            $add['shop_id'] = $this->shopId;
            $add['tag_name'] = $input['tag_name'];
            $this->service_buyer_tag->store([
                'store_data'=>$add
            ]);
            $url = action('topshop_ctl_supplier_buyerTag@index');
            return $this->splash('success',$url,'添加成功',true);
        }catch (\Exception $exception)
        {
            return $this->splash('error',null,$exception->getMessage(),true);
        }
    }

    /**
     * @return mixed|string
     * 删除买手标签
     */
    public function destroy()
    {
        try{
            $input = input::get();
            if(!$input['tag_id'])
            {
                throw new \LogicException('tag_id不能为空');
            }
            $this->service_buyer_tag->destroy(
                [
                    'filter'=>[
                        'tag_id'=>$input['tag_id']
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
                    'tag_name'=>$input['tag_name'],
                ],
                [
                    'tag_name'=> 'required|max:4',
                ],
                [
                    'tag_name'=> '标签名称不能为空且长度不能大于4个字符',
                ]
            );
            $validator->newFails();
        }catch (\Exception $exception)
        {
            throw $exception;
        }
    }
}