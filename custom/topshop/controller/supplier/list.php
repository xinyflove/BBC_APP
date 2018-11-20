<?php
/**
 * Created by PhpStorm.
 * User: jiangyunhan
 * Date: 2018-05-15
 * Time: 11:16
 */
class topshop_ctl_supplier_list extends topshop_controller{
    //线下店列表
    public function index()
    {
        $page = input::get('page', 1);
        $cat =  input::get('cat');

        $this->contentHeaderTitle = app::get('topshop')->_('线下店列表');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_index@index'),
                'title' => app::get('topshop')->_('首页')
            ],
            [
                'url' => url::action('topshop_ctl_account_supplier@index'),
                'title' => app::get('topshop')->_('供应商管理')
            ],
            [
                'title' => app::get('topshop')->_('线下店管理')
            ]
        );
        $pagedata = array();
        $params = array();
        $params['page_no'] = $page;
        if(!empty($cat)){
            $params['agent_category_id'] = $cat;
        }

        try {
            $agentShopData = app::get('topshop')->rpcCall('supplier.agent.shop.list', $params);
            $supplierList = app::get('topshop')->rpcCall('supplier.shop.list', ['is_audit'=>'PASS']);
            $supplierList = array_bind_key($supplierList, 'supplier_id');
        } catch (\Exception $e) {
            echo '参数错误:' . $e->getMessage();
            die;
        }

        foreach ($agentShopData['data'] as &$v) {
            $v['supplier_name'] = $supplierList[$v['supplier_id']]['supplier_name'];
            if ($v['type'] === 'HOME') {
                $v['type'] = '总店';
            } else {
                $v['type'] = '分店';
            }
        }
        //分页处理
        $pagers = array(
            'link' => url::action('topshop_ctl_supplier_list@index', [ 'page' => time()]),
            'current' => $agentShopData['current_page'],
            'use_app' => 'topshop',
            'total' => $agentShopData['page_total'],
            'token' => time(),
        );
        $pagedata['total'] = $agentShopData['data_count'];
        $pagedata['pagers'] = $pagers;
        $pagedata['data'] = $agentShopData;

        return $this->page('topshop/supplier/agentList.html', $pagedata);
    }

    /**
     * 线下店搜索
     */
    public function agentShopSearch()
    {
        $page = input::get('page', 1);
        $name = input::get('name');
        $name = trim($name);
        $this->contentHeaderTitle = app::get('topshop')->_('线下店列表');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_index@index'),
                'title' => app::get('topshop')->_('首页')
            ],
            [
                'url' => url::action('topshop_ctl_account_supplier@index'),
                'title' => app::get('topshop')->_('供应商管理')
            ],
            [
                'title' => app::get('topshop')->_('线下店管理')
            ]
        );
        $pagedata = array();
        //$pagedata['supplier_id'] = input::get('supplier_id');
        $params = array();

        $params['page_no'] = $page;
        $params['filter'] = [
            'name|has' => $name
        ];
        try {
            $agentShopData = app::get('topshop')->rpcCall('supplier.agent.shop.list', $params);
            $supplierList = app::get('topshop')->rpcCall('supplier.shop.list', ['is_audit'=>'PASS']);
            $supplierList = array_bind_key($supplierList, 'supplier_id');
        } catch (\Exception $e) {
            echo '参数错误:' . $e->getMessage();
            die;
        }
        foreach ($agentShopData['data'] as &$v) {
            $v['supplier_name'] = $supplierList[$v['supplier_id']]['supplier_name'];
            if ($v['type'] === 'HOME') {
                $v['type'] = '总店';
            } else {
                $v['type'] = '分店';
            }
        }
        //分页处理
        $pagers = array(
            'link' => url::action('topshop_ctl_supplier_list@index', ['page' => time()]),
            'current' => $agentShopData['current_page'],
            'use_app' => 'topshop',
            'total' => $agentShopData['page_total'],
            'token' => time(),
        );
        $pagedata['total'] = $agentShopData['data_count'];
        $pagedata['pagers'] = $pagers;
        $pagedata['data'] = $agentShopData;
        $pagedata['search_keywords']['name'] = $name;
        return $this->page('topshop/supplier/agentList.html', $pagedata);
    }

    /**
     * 编辑排序
     */
    public function order_sort()
    {
        try{
            $input = input::get();
            if(!$input['pk'])
            {
                throw new \LogicException('主键id不能为空');
            }
            if(!$input['value'])
            {
                throw new \LogicException('排序值不能为空');
            }
            $model = app::get('syssupplier')->model('agent_shop');
            $update_res = $model->update(['order_sort'=>$input['value']],['agent_shop_id'=>$input['pk']]);
            if(!is_integer($update_res))
            {
                throw new \RuntimeException('更新排序失败');
            }
            return $this->splash('success',null,'排序成功！',true);
        }catch (\Exception $exception)
        {
            return $this->splash('error',null,$exception->getMessage(),true);
        }
    }

    /**
     * 置顶功能
     */
    public function top()
    {
        try{
            $input = input::get();
            if(!$input['agent_shop_id'])
            {
                throw new \LogicException('主键id不能为空');
            }
            if(!$input['state'])
            {
                throw new \LogicException('置顶状态值不能为空');
            }
            $model = app::get('syssupplier')->model('agent_shop');
            if($input['state'] === 'true')
            {
                $update_res = $model->update(['top'=>time()],['agent_shop_id'=>$input['agent_shop_id']]);
                return $this->splash('success',null,'置顶成功！',true);
            }else{
                $update_res = $model->update(['top'=>0],['agent_shop_id'=>$input['agent_shop_id']]);
                return $this->splash('success',null,'取消置顶！',true);
            }
//            if(!is_integer($update_res))
//            {
//                throw new \RuntimeException('置顶失败');
//            }
        }catch (\Exception $exception)
        {
            return $this->splash('error',null,$exception->getMessage(),true);
        }

    }

    /**
     * 线下店简介编辑
     */
    public function agentProfile()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('线下店简介');
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_index@index'),
                'title' => app::get('topshop')->_('首页')
            ],
            [
                'url' => url::action('topshop_ctl_account_supplier@index'),
                'title' => app::get('topshop')->_('供应商管理')
            ],
            [
                'title' => app::get('topshop')->_('线下店管理')
            ]
        );
        try{
            $input = input::get();
            if(!$input['agent_shop_id'])
            {
                throw new \LogicException('agent_shop_id参数不存在');
            }
            $model = app::get('syssupplier')->model('agent_shop');
            $data = $model->getRow('agent_shop_id,shop_profile,agent_desc_src',['agent_shop_id'=>$input['agent_shop_id']]);
            $pagedata = array();
            $pagedata['agent_shop_id'] = $input['agent_shop_id'];
            $pagedata['data'] = $data;
            return $this->page('topshop/supplier/profile.html',$pagedata);
        }catch (\Exception $exception)
        {
            echo $exception->getMessage();die;
        }
    }

    /**
     * 线下店简介编辑提交
     */
    public function agentProfileStore()
    {
        try{
            $input = input::get();
//            var_dump($input);die;
            if(!$input['agent_shop_id'])
            {
                throw new \LogicException('agent_shop_id参数不存在');
            }
            if(!$input['agent_desc_src'])
            {
                throw new \LogicException('简介图片必传');
            }
            $model = app::get('syssupplier')->model('agent_shop');
            $update_res = $model->update(['shop_profile'=>$input['shop_profile'],'agent_desc_src'=>$input['agent_desc_src']],['agent_shop_id'=>$input['agent_shop_id']]);
            if(!is_integer($update_res))
            {
                return $this->splash('error',null,'更新简介失败',true);
            }else
            {
                return $this->splash('success',null,'更新简介成功',true);
            }
        }catch (\Exception $exception)
        {
            return $this->splash('error',null,$exception->getMessage(),true);
        }
    }
}