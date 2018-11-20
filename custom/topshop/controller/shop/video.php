<?php

class topshop_ctl_shop_video extends topshop_controller {

    public $limit = 30;

    public function index()
    {
		$result = $this->__getListData();

		$pagedata['activeImgType'] = input::get('video_type','item');

        //不同类型的图片，支持不同图片的规格
        $pagedata['videoTypeSize'] = kernel::single('video_data_video')->getVideoTypeSize('item');

        $pagedata['videoShopCatList'] = $result['videoShopCatList'];
        $pagedata['activeVideoCatId'] = input::get('video_cat_id',0);
        $pagedata['videodata'] = $result['data']['list'];
        $pagedata['count'] = $result['data']['total'];
        $pagedata['pagers'] = $result['pagers'];

        $this->contentHeaderTitle = app::get('topshop')->_('图片管理');

        return $this->page('topshop/shop/video/index.html', $pagedata);
    }

    private function __pager($filter, $count, $isVideoModal)
    {
        $params['video_type'] = $filter['video_type'];
        $params['orderBy'] = $filter['orderBy'];
        $params['video_cat_id'] = $filter['video_cat_id'];
        if( $filter['video_name'] )
		{
			$params['video_name'] = $filter['video_name'];
		}

        if( $isVideoModal )
        {
            $params['videoModal'] = true;
        }

        $params['pages'] = time();
        $total = ceil($count/$this->limit);
        $pagers = array(
            'link'=>url::action('topshop_ctl_shop_video@search',$params),
            'current'=>$filter['page_no'],
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>time(),
        );
        return $pagers;
    }

    /**
     * @param bool $isVideoModal 是否为弹出框
     */
	private function __getListData($isVideoModal)
	{
        if( $isVideoModal )
        {
            $this->limit = 8;
        }

        $params['shop_id'] = $this->shopId;
        $params['page_no'] = intval(input::get('pages',1));
        $params['page_size'] = intval($this->limit);
		$params['video_type'] = input::get('video_type','item');
		$params['video_cat_id'] = input::get('video_cat_id',0);
		$params['orderBy'] = input::get('orderBy','last_modified desc');
		$params['fields'] = '*';
		if( input::get('video_name',false) !== false )
		{
			$params['video_name'] = input::get('video_name');
		}
        $result['data'] = app::get('topshop')->rpcCall('video.shop.list', $params);
        $result['pagers'] = $this->__pager($params, $result['data']['total'], $isVideoModal);

        $catListApiParams = [
            'shop_id' => $this->shopId,
            'video_type' => $params['video_type'],
            'fields' => 'video_cat_id,video_cat_name',
        ];

        $result['videoShopCatList'] = app::get('topshop')->rpcCall('video.shop.cat.videotype.list',$catListApiParams);
		return $result;
	}

	public function search()
	{
        //是否为弹出框的列表
        if( input::get('videoModal',false) )
        {
            $isVideoModal = true;
            $pagedata['videoModal'] = true;
        }
		$result = $this->__getListData($isVideoModal);
        $pagedata['activeVideoCatId'] = input::get('video_cat_id',0);
        $pagedata['videoShopCatList'] = $result['videoShopCatList'];
        $pagedata['videodata'] = $result['data']['list'];
        $pagedata['pagers'] = $result['pagers'];
        $pagedata['video_name'] = input::get('video_name',false);

        $pagedata['videoTypeSize'] = kernel::single('video_data_video')->getVideoTypeSize(input::get('video_type','item'));

        return view::make('topshop/shop/video/list.html', $pagedata);
	}

	public function delImgLink()
	{
        $params['video_id'] = implode(',', input::get('img_id') );
		try
		{
        	app::get('topshop')->rpcCall('video.delete.videoLink', $params, 'seller');
            $status = 'success';
            $msg = '删除成功';
		}
		catch( Exception $e)
		{
			$msg = $e->getMessage();
            $status = 'error';
		}
        $this->sellerlog('删除图片。图片ID是'.$params['video_id']);
        return $this->splash($status,null,$msg,true);
	}

	public function upImgName()
	{
        $params['url'] = input::get('url');
        $params['video_name'] = input::get('video_name');
        try
        {
            $status = 'success';
            app::get('topshop')->rpcCall('video.shop.upVideoName', $params, 'seller');
        }
        catch( LogicExpection $e )
        {
            $mag = $e->getMessage();
            $status = 'error';
        }
        $this->sellerlog('更新图片。图片名称是：'.$params['video_name']);
        $msg = '更新成功';
        return $this->splash($status,null,$msg,true);
	}

    public function loadVideoModal()
    {
        $isVideoModal = true;
        $result = $this->__getListData($isVideoModal);
        $pagedata['videoShopCatList'] = $result['videoShopCatList'];
        $pagedata['videodata'] = $result['data']['list'];
        $pagedata['pagers'] = $result['pagers'];
        $pagedata['load_id'] = rand(0,999);
        $pagedata['videoModal'] = true;
        if( input::get('isOnlyShow') )
        {
            $pagedata['isOnlyShow'] = input::get('isOnlyShow');
        }
        return view::make('topshop/shop/video/upload.html', $pagedata);
    }

    /**
     * 图片移动文件夹弹出框
     */
    public function loadVideoMoveCatModal()
    {
        $pagedata['videoId'] = input::get('video_id');

        $imgType = input::get('video_type','item');
        $catListApiParams = [
            'shop_id' => $this->shopId,
            'fields' => 'video_cat_id,video_cat_name,video_type',
        ];
        $data = app::get('topshop')->rpcCall('video.shop.cat.videotype.list',$catListApiParams);

        $videoShopCatList['item'] = [];
        $videoShopCatList['shop'] = [];
        foreach( (array)$data as $row)
        {
            $videoShopCatList[$row['video_type']][] = $row;
        }
        $pagedata['videoShopCatList'] = $videoShopCatList;
        $pagedata['activeVideoType'] = $imgType;
        return view::make('topshop/shop/video/modal/moveCat.html', $pagedata);
    }

    /**
     * 加载文件夹管理弹出框
     */
    public function loadImgCatModal()
    {
        $imgType = input::get('video_type','item');
        $videoCatId = input::get('video_cat_id',0);
        $catListApiParams = [
            'shop_id' => $this->shopId,
            'video_type' => $imgType,
            'fields' => 'video_cat_id,video_cat_name,video_type',
        ];
        $data = app::get('topshop')->rpcCall('video.shop.cat.videotype.list',$catListApiParams);
        $pagedata['videoShopCatList'] = $data;
        $pagedata['activeVideoType'] = $imgType;
        $pagedata['activeVideoCatId'] = $videoCatId;

        if( input::get('folderlist') )
        {
            return view::make('topshop/shop/video/folderlist.html', $pagedata);
        }
        else
        {
            return view::make('topshop/shop/video/modal/folderMange.html', $pagedata);
        }
    }

    public function addImgCat()
    {
        $imgType = input::get('video_type');
        $videoCatName = app::get('topshop')->_('未命名文件夹');
        $catListApiParams = [
            'shop_id' => $this->shopId,
            'video_type' => $imgType,
            'fields' => 'video_cat_id,video_cat_name',
        ];
        $data = app::get('topshop')->rpcCall('video.shop.cat.videotype.list',$catListApiParams);
        if( $data )
        {
            $arrVideoCatName = array_column($data, 'video_cat_name');
            for( $i=0; $i<20; $i++)
            {
                $tmpVideoCatName = $i ? $videoCatName.$i : $videoCatName;
                if( !in_array($tmpVideoCatName, $arrVideoCatName) )
                {
                    $videoCatName = $tmpVideoCatName;
                    break;
                }
            }
        }

        try
        {
            $videoCatId = app::get('topshop')->rpcCall('video.shop.cat.add',['video_cat_name'=>$videoCatName, 'video_type'=>$imgType,'shop_id'=>$this->shopId]);
            $status = $videoCatId ? 'success' : 'error';
            $msg = $videoCatId ? ['video_cat_id'=>$videoCatId, 'video_cat_name'=>$videoCatName] : app::get('topshop')->_('创建文件夹失败');
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            $status = 'error';
        }
        catch( Exception $e)
        {
            $msg = app::get('topshop')->_('创建文件夹失败');
            $status = 'error';
        }

        if( $status == 'success' )
        {
            $this->sellerlog('创建图片文件夹，video_cat_name：['.$imgType.']:'.$videoCatName);
        }
        return $this->splash($status,null,$msg,true);
    }

    public function delImgCat()
    {
        $videoCatId = input::get('video_cat_id');
        try
        {
            $res = app::get('topshop')->rpcCall('video.shop.cat.delete',['video_cat_id'=>$videoCatId, 'shop_id'=>$this->shopId]);
            $status = $res ? 'success' : 'error';
            $msg = $res ?  app::get('topshop')->_('成功删除') : app::get('topshop')->_('删除失败');
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            $status = 'error';
        }
        catch( Exception $e)
        {
            $msg = app::get('topshop')->_('删除失败');
            $status = 'error';
        }

        if( $status == 'success' )
        {
            $this->sellerlog('删除图片文，video_cat_id：'.$videoCatId);
        }
        return $this->splash($status,null,$msg,true);
    }

    public function editImgCat()
    {
        $videoCatId = input::get('video_cat_id');
        $videoCatName = trim(input::get('video_cat_name'));
        $validator = validator::make(
            array('video_cat_name' => $videoCatName),
            array('video_cat_name' => 'required|max:10'),
            array('video_cat_name' => '文件夹名称必填|文件名称不能超过10个字')
        );
        if( $validator->fails() )
        {
            $msg = $validator->messages()->first('video_cat_name');
            return $this->splash('error',null,$msg,true);
        }

        try
        {
            $res = app::get('topshop')->rpcCall('video.shop.cat.update',['video_cat_id'=>$videoCatId, 'video_cat_name'=>$videoCatName,'shop_id'=>$this->shopId]);
            $status = $res ? 'success' : 'error';
            $msg = $res ?  app::get('topshop')->_('创建文件夹成功') : app::get('topshop')->_('创建文件夹失败');
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            $status = 'error';
        }
        catch( Exception $e)
        {
            $msg = app::get('topshop')->_('创建文件夹失败');
            $status = 'error';
        }

        if( $status == 'success' )
        {
            $this->sellerlog('更新图片文件夹名称，video_cat_name：['.$imgType.']:'.$videoCatName);
        }
        return $this->splash($status,null,$msg,true);
    }

    /**
     * 移动图片
     */
    public function moveVideoCat()
    {
        $videoId = input::get('video_id');
        $videoCatId = input::get('video_cat_id');
        $moveApiParams = [
            'shop_id' => $this->shopId,
            'video_id' => $videoId,
            'video_cat_id' => $videoCatId,
            'video_type' => input::get('video_type'),
        ];

        $msg = '移动完成';
        try
        {
            $result = app::get('topshop')->rpcCall('video.shop.move.cat',$moveApiParams);
            $status = 'success';
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            $status = 'error';
        }
        catch( \Exception $e)
        {
            $msg = app::get('topshop')->_('移动失败');
            $status = 'error';
        }

        $params =  array(
            'video_type'=>input::get('video_type','item'),
            'video_cat_id'=>$videoCatId
        );
        $url = url::action('topshop_ctl_shop_video@index', $params);

        if( $status == 'success' )
        {
            $this->sellerlog('移动图片，图片ID：'.$videoId);
        }
        return $this->splash($status,$url,$msg,true);
    }
}
