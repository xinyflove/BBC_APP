<?php
/**
 * Created by PhpStorm.
 * User: jiangyunhan
 * Date: 2018-06-04
 * Time: 9:43
 */
class topshop_ctl_supplier_publicAccount extends topshop_controller
{
    //订阅号列表
    public function index()
    {

        $page = input::get('page', 1);
        $pagedata = array();
        $params = array();
        $url = url::action('topwap_ctl_supplier_pubAccount@index',array('shop_id'=>$this->shopId));
        $this->contentHeaderTitle = app::get('topshop')->_('订阅号管理(wap端链接 '.$url.')');
        $params['shop_id'] = $this->shopId;
        $params['page_no'] = $page;
        $params['page_size'] = 5;
        $data = app::get('topshop')->rpcCall('shop.supplier.publicaccount.list',$params);
        $pagedata['data'] = $data['data'];

        //分页处理
        $pagers = array(
            'link' => url::action('topshop_ctl_supplier_publicAccount@index', [ 'page' => time()]),
            'current' => $data['current_page'],
            'use_app' => 'topshop',
            'total' => $data['page_total'],
            'token' => time(),
        );
        $pagedata['total'] = $data['data_count'];
        $pagedata['pagers'] = $pagers;

        return $this->page('topshop/supplier/publicAccount/list.html', $pagedata);
    }

    public function edit()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('添加订阅号');
        //面包屑
        $this->runtimePath = array(
            ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],
            ['url'=> url::action('topshop_ctl_supplier_publicAccount@index'),'title' => app::get('topshop')->_('订阅号管理')],
            ['title' => app::get('topshop')->_('添加订阅号')],
        );
        if( input::get('public_account_id') )
        {
            $params['shop_id'] = $this->shopId;
            $params['public_account_id'] = input::get('public_account_id');
            $data = app::get('topshop')->rpcCall('shop.supplier.publicaccount.list',$params);
            if( $data )
            {
                $pagedata['data'] = $data['data'][0];
            }
        }
        $params['shop_id'] = $this->shopId;
        return $this->page('topshop/supplier/publicAccount/edit.html', $pagedata);
    }

    public function save()
    {
        try
        {
            if( input::get('public_account_id') )
            {
                $params = input::get();
                if(strpos($params['url'],'http') !==false){
                    $params['modified_time'] = time();
                    $params['shop_id'] = $this->shopId;
                    app::get('topshop')->rpcCall('shop.supplier.publicaccount.update',$params);
                    $msg = '修改订阅号信息成功';
                }else{
                    $msg = '订阅号链接地址必须以http或者https开头';
                    return $this->splash('error','',$msg,true);
                }
            }
            else
            {
                $params = input::get();
                if(strpos($params['url'],'http') !==false){
                    $params['shop_id'] = $this->shopId;
                    $params['write_time'] = time();
                    $params['modified_time'] = time();
                    app::get('topshop')->rpcCall('shop.supplier.publicaccount.add',$params);
                    $msg = '添加订阅号信息成功';
                }else{
                    $msg = '订阅号链接地址必须以http或者https开头';
                    return $this->splash('error','',$msg,true);
                }
            }
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }

        $url = url::action('topshop_ctl_supplier_publicAccount@index');
        return $this->splash('success',$url,$msg,true);
    }

    public function delete()
    {
        $publicId = input::get('public_account_id', false);
        if( !$publicId )
        {
            $msg = '删除失败';
            return $this->splash('error','',$msg,true);
        }
        try
        {
            $params['public_account_id'] = $publicId;
            app::get('topshop')->rpcCall('shop.supplier.publicaccount.delete',$params);
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }

        $msg = '删除成功';
        $url = url::action('topshop_ctl_supplier_publicAccount@index');
        return $this->splash('success',$url,$msg,true);
    }
}