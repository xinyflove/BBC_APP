<?php
/**
 * Desc: 用户发布文章信息相关
 * User: zhangshu
 * Date: 2019/4/3
 */
class topwap_ctl_supplier_comment extends topwap_controller
{
    private function header()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header('Content-Type: application/json; charset=utf-8');
    }

    public function comment()
    {
        if( !userAuth::check() ) {
            $next_page = url::to(request::server('REQUEST_URI'));
            if( request::ajax() ) {
                $url = url::action('topwap_ctl_passport@goLogin', ['next_page' => $next_page]);
                $data['error'] = true;
                $data['redirect'] = $url;
                $data['message'] = app::get('topwap')->_('请登录');
                return response::json($data);exit;
            }
            redirect::action('topwap_ctl_passport@goLogin', ['next_page' => $next_page])->send();exit;
        }
        $pageData['shopId'] = input::get('shop_id');
        $pageData['userId'] = userAuth::id();
        return view::make('topwap/supplier/comment.html',$pageData);
    }

    /**
     * 发布文章
     */
    public function saveComment()
    {
        $shopId = input::get('shop_id');
        if (!userAuth::check()) {
            $next_page = url::action('topwap_ctl_supplier_index@home', ['shop_id' => $shopId]);
            $url = url::action('topwap_ctl_passport@goLogin', ['next_page' => $next_page]);
            $msg = app::get('topwap')->_('请登录');
            return $this->splash('error', $url, $msg);
        }
        $userId = userAuth::id();
        $params = input::get();
        $params['user_id'] = $userId;
        $validator = validator::make(
            [
                'description' => $params['description'],
                'shop_name' => $params['shop_name'],
                'shop_addr' => $params['shop_addr'],
                'user_name' => $params['user_name'],
                'user_phone' => $params['user_phone'],
                'image_url' => $params['image_url'],
            ],
            [
                'description' => 'required',
                'shop_name' => 'required',
                'shop_addr' => 'required',
                'user_name' => 'required',
                'user_phone' => 'required',
                'image_url' => 'required',
            ],
            [
                'description' => '内容必填!',
                'shop_name' => '店铺名称必填!',
                'shop_addr' => '店铺地址必填!',
                'user_name' => '姓名必填!',
                'user_phone' => '手机号必填!',
                'image_url' =>'图片必填',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->messagesInfo();
            foreach( $messages as $error ) {
                return $this->splash('error',null,$error[0], true);
            }
        }

        try {
            app::get('syssupplier')->rpcCall('supplier.shop.comment.add', $params);
        } catch(Exception $e) {
            $msg = $e->getMessage();
            return $this->splash('error',null, $msg, true);
        }
        return  $this->splash('success', url::action('topwap_ctl_supplier_index@home', ['shop_id' => $shopId]), app::get('topwap')->_('发布文章成功'), true);
    }

    public function detail()
    {
        $pageData['commentId'] = input::get('comment_id');

        if (userAuth::check()) {
            $userInfo = userAuth::getUserInfo();
            $pageData['userId'] = $userInfo['userId'];
            $pageData['userName'] = $userInfo['login_account'];
            $pageData['headImgUrl'] = $userInfo['headimg_url'];
        } else {
            $pageData['userId'] = '';
            $pageData['userName'] = '';
            $pageData['headImgUrl'] = app::get('sysconf')->getConf('user.default.headimg');
        }
        return view::make('topwap/supplier/comment-detail.html',$pageData);
    }

    /**
     * 发布文章评论
     */
    public function saveCommentReply()
    {
        if (!userAuth::check()) {
            $commentId = input::get('comment_id');

            $next_page = url::action('topwap_ctl_supplier_comment@detail', ['comment_id' => $commentId]);
            $url = url::action('topwap_ctl_passport@goLogin', ['next_page' => $next_page]);
            $msg = app::get('topwap')->_('请登录');
            return $this->splash('error', $url, $msg);
        }
        $params = input::get();
        $userId = userAuth::id();
        $params['user_id'] = $userId;
        try {
            app::get('syssupplier')->rpcCall('supplier.shop.comment.append.reply', $params);
        } catch(Exception $e) {
            $msg = $e->getMessage();
            return $this->splash('error',null, $msg, true);
        }
        return  $this->splash('success',null, app::get('topwap')->_('发布文章评论成功'), true);
    }

    /**
     * 文章访问
     */
    public function updatePageView()
    {
        $userId = userAuth::id();
        $params['user_id'] = $userId;
        $params = input::get();
        try {
            app::get('syssupplier')->rpcCall('supplier.shop.comment.view.update', $params);
        } catch(Exception $e) {
            $msg = $e->getMessage();
            return $this->splash('error',null, $msg, true);
        }
        return  $this->splash('success',null, '', true);
    }

    /**
     * 文章点赞
     */
    public function updateThumbsup()
    {
        $userId = userAuth::id();
        $params['user_id'] = $userId;
        $params = input::get();
        try {
            app::get('syssupplier')->rpcCall('supplier.shop.comment.thumbsup.update', $params);
        } catch(Exception $e) {
            $msg = $e->getMessage();
            return $this->splash('error',null, $msg, true);
        }
        return  $this->splash('success',null, '', true);
    }

    /**
     * 文章详情
     */
    public function getCommentData()
    {
        $params = input::get();
        $data = app::get('syssupplier')->rpcCall('supplier.shop.comment.get', $params);
        echo json_encode($data);
    }

    public function getCommentList()
    {
        $params = input::get();
        $params['fields'] = 'comment_id, user_name, description, thumbs_up, is_top, modified_time,user_id';
        $data = app::get('syssupplier')->rpcCall('supplier.shop.comment.list', $params);
        echo json_encode($data);
    }

    /**
     * 获取用户发布文章数量
     */
    public function getUserCommentCount()
    {
        $params = input::get();
        $data = app::get('syssupplier')->rpcCall('supplier.shop.comment.getCount', $params);
        echo json_encode($data);
    }
}