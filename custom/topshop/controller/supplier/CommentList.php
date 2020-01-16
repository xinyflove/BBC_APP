<?php
/**
 * Created by PhpStorm.
 * User: jiangyunhan
 * Date: 2019-03-29
 * Time: 9:43
 */
class topshop_ctl_supplier_CommentList extends topshop_controller
{
    //评论文章列表
    public function index()
    {

        $page = input::get('page', 1);
        $pagedata = array();
        $params = array();

        $this->contentHeaderTitle = app::get('topshop')->_('评论文章管理');
        //$params['shop_id'] = $this->shopId;
        $params['page_no'] = $page;
        $params['page_size'] = 10;
        $data = app::get('topshop')->rpcCall('shop.supplier.comment.list',$params);
        
        $pagedata['data'] = $data['data'];

        //分页处理
        $pagers = array(
            'link' => url::action('topshop_ctl_supplier_CommentList@index', [ 'page' => time()]),
            'current' => $data['current_page'],
            'use_app' => 'topshop',
            'total' => $data['page_total'],
            'token' => time(),
        );
        $pagedata['total'] = $data['data_count'];
        $pagedata['pagers'] = $pagers;

        return $this->page('topshop/supplier/comment/list.html', $pagedata);
    }


    public function commentSearch(){

        $page = input::get('page', 1);
        $keyword = input::get('keyword');
        $keyword = trim($keyword);
        $phone = input::get('phone');
        $phone = trim($phone);
        $user_name = input::get('user_name');
        $user_name = trim($user_name);

        $pagedata = array();
        $params = array();

        $this->contentHeaderTitle = app::get('topshop')->_('评论文章管理');
        //$params['shop_id'] = $this->shopId;
        $params['page_no'] = $page;
        $params['page_size'] = 10;
        $params['keyword'] = $keyword;
        $params['phone'] = $phone;
        $params['user_name'] = $user_name;

        //模糊查询 like的用法
        $params['filter'] = [
            'description|has' => $keyword
        ];
        $data = app::get('topshop')->rpcCall('shop.supplier.comment.list',$params);

        $pagedata['data'] = $data['data'];

        //分页处理
        $pagers = array(
            'link' => url::action('topshop_ctl_supplier_CommentList@index', [ 'page' => time()]),
            'current' => $data['current_page'],
            'use_app' => 'topshop',
            'total' => $data['page_total'],
            'token' => time(),
        );
        $pagedata['total'] = $data['data_count'];
        $pagedata['pagers'] = $pagers;
        $pagedata['search_keywords']['keyword'] = $keyword;
        $pagedata['search_keywords']['phone'] = $phone;
        $pagedata['search_keywords']['user_name'] = $user_name;
        return $this->page('topshop/supplier/comment/list.html', $pagedata);


    }

    public function edit()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('编辑评论文章');
        //面包屑
        $this->runtimePath = array(
            ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],
            ['url'=> url::action('topshop_ctl_supplier_CommentList@index'),'title' => app::get('topshop')->_('评论文章管理')],
            ['title' => app::get('topshop')->_('编辑评论文章')],
        );
        if( input::get('comment_id') )
        {
            //$params['shop_id'] = $this->shopId;
            $params['comment_id'] = input::get('comment_id');
            $data = app::get('topshop')->rpcCall('shop.supplier.comment.info',$params);
            if( $data )
            {
                $pagedata['data'] = $data['data'][0];
            }
        }

        //$params['shop_id'] = $this->shopId;
        return $this->page('topshop/supplier/comment/edit.html', $pagedata);
    }

    public function save()
    {
        try {
            if( input::get('comment_id') )
            {
                $params = input::get();
                $params['modified_time'] = time();

                if (empty($params['top_pic'])) {
                    throw new \LogicException('请选择用户评论文章图片');
                }

                app::get('topshop')->rpcCall('shop.supplier.comment.update',$params);

                $msg = '编辑评论文章成功';
                $url = url::action('topshop_ctl_supplier_CommentList@index');
                return $this->splash('success',$url,$msg,true);
            } else {
                throw new \LogicException('编辑评论文章失败');
            }
        } catch( \LogicException $e ) {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }
    }

    public function delete()
    {
        $commentId = input::get('comment_id', false);
        if( !$commentId )
        {
            $msg = '删除失败';
            return $this->splash('error','',$msg,true);
        }
        try
        {
            $params['comment_id'] = $commentId;
            app::get('topshop')->rpcCall('shop.supplier.comment.delete',$params);
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }

        $msg = '删除成功';
        $url = url::action('topshop_ctl_supplier_CommentList@index');
        return $this->splash('success',$url,$msg,true);
    }


    //设置审核状态
    public function setStatus(){
        try{
            $input = input::get();
            if(!$input['comment_id'])
            {
                throw new \LogicException('主键id不能为空');
            }
            if(!$input['status'])
            {
                throw new \LogicException('审核状态值不能为空');
            }
            $model = app::get('syssupplier')->model('comment_list');
            if($input['status'] === 'true')
            {
                $update_res = $model->update(['status'=>1],['comment_id'=>$input['comment_id']]);
                return $this->splash('success',null,'审核通过！',true);
            }else{
                $update_res = $model->update(['status'=>0],['comment_id'=>$input['comment_id']]);
                return $this->splash('success',null,'审核未通过！',true);
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
            $model = app::get('syssupplier')->model('comment_list');

            $update_res = $model->update(['order_sort'=>$input['value']],['comment_id'=>$input['pk']]);
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
}