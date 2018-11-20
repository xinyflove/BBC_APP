<?php

class topshop_ctl_selector_users extends topshop_controller {
    public $limit = 10;

    public function loadSelectUsersModal()
    {
        if(input::get('limit')){
            $pagedata['limit'] = input::get('limit');
        }

        $isImageModal = true;
        $pagedata['imageModal'] = true;
        $pagedata['textcol'] = input::get('textcol');
        $pagedata['view'] = input::get('view');
        $pagedata['shopCatList'] = app::get('topshop')->rpcCall('shop.authorize.cat',array('shop_id'=>$this->shopId));
        return view::make('topshop/selector/users/index.html', $pagedata);
    }

    public function formatSelectedUsersRow()
    {
        $userIds = input::get('user_id');

        $userIdsChunk = array_chunk($userIds, 20);

        $usersList = array();
        foreach( $userIdsChunk as $value )
        {
            $searchParams = array(
                'user_ids' => implode(',',$value),
                'fields' => 'user_id, login_account, email, mobile',
            );
            $users_list = app::get('topshop')->rpcCall('user.search.account.list', $searchParams);
            $usersListData = $users_list['user_list'];
            $usersList = array_merge($usersList,$usersListData);
        }

        $pagedata['_input']['usersList'] = $usersList;
        return view::make('topshop/selector/users/input-row.html', $pagedata);
    }

    //根据商家id和3级分类id获取商家所经营的所有品牌
    public function getBrandList()
    {
        $shopId = $this->shopId;
        $catId = input::get('catId');
        $params = array(
            'shop_id'=>$shopId,
            'cat_id'=>$catId,
            'fields'=>'brand_id,brand_name,brand_url'
        );
        $brands = app::get('topshop')->rpcCall('category.get.cat.rel.brand',$params);
        return response::json($brands);
    }

    //根据商家类目id的获取商家所经营类目下的所有商品
    public function searchUser($json=true)
    {
        $user_mobile = input::get('searchmobile');
        $user_name = input::get('searchname');
        $user_email = input::get('searchemail');
        $pages = input::get('pages');
        $limit = input::get('limit') ? input::get('limit') : $this->limit;

        $searchParams = array(
            'account_mobile' => trim($user_mobile),
            'account_name' => trim($user_name),
            'account_email' => trim($user_email),
            'page_no' => intval($pages),
            'page_size' => intval($limit),
        );
        $searchParams['fields'] = 'user_id, login_account, email, mobile';
        $usersList = app::get('topshop')->rpcCall('user.search.account.list', $searchParams);
        $pagedata['user_list'] = $usersList['user_list'];
        $pagedata['total'] = $usersList['count'];
        $totalPage = ceil($usersList['count']/$limit);
        $filter = input::get();
        $filter['pages'] = time();
        $pagers = array(
            'link' => url::action('topshop_ctl_selector_users@searchUser', $filter),
            'current' => $pages,
            'use_app' => 'topshop',
            'total' => $totalPage,
            'token' => time(),
        );
        $pagedata['pagers'] = $pagers;

        return view::make('topshop/selector/users/list.html', $pagedata);
    }

}
