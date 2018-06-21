<?php

class topstore_ctl_store_image extends topstore_controller {

    public $limit = 30;

    public function index()
    {
		$result = $this->__getListData();

		$pagedata['activeImgType'] = input::get('img_type','item');

        //不同类型的图片，支持不同图片的规格
        $pagedata['imageTypeSize'] = kernel::single('image_data_image')->getImageTypeSize('item');

        $pagedata['imageShopCatList'] = $result['imageShopCatList'];
        $pagedata['activeImageCatId'] = input::get('image_cat_id',0);
        $pagedata['imagedata'] = $result['data']['list'];
        $pagedata['count'] = $result['data']['total'];
        $pagedata['pagers'] = $result['pagers'];

        $this->contentHeaderTitle = app::get('topstore')->_('图片管理');

        return $this->page('topstore/store/image/index.html', $pagedata);
    }

    /**
     * 分页
     * @param $filter
     * @param $count
     * @param $isImageModal
     * @return array
     * xinyufeng
     */
    private function __pager($filter, $count, $isImageModal)
    {
        $params['img_type'] = $filter['img_type'];
        $params['orderBy'] = $filter['orderBy'];
        $params['image_cat_id'] = $filter['image_cat_id'];
        if( $filter['image_name'] )
		{
			$params['image_name'] = $filter['image_name'];
		}

        if( $isImageModal )
        {
            $params['imageModal'] = true;
        }

        $params['pages'] = time();
        $total = ceil($count/$this->limit);
        $pagers = array(
            'link'=>url::action('topstore_ctl_store_image@search',$params),
            'current'=>$filter['page_no'],
            'use_app' => 'topstore',
            'total'=>$total,
            'token'=>time(),
        );
        return $pagers;
    }

    /**
     * @param bool $isImageModal 是否为弹出框
     * xinyufeng
     */
	private function __getListData($isImageModal)
	{
        if( $isImageModal )
        {
            $this->limit = 12;
        }

        $params['store_id'] = $this->storeId;
        $params['page_no'] = intval(input::get('pages',1));
        $params['page_size'] = intval($this->limit);
		$params['img_type'] = input::get('img_type','store');
		$params['image_cat_id'] = input::get('image_cat_id','0');
		$params['orderBy'] = input::get('orderBy','last_modified desc');
		$params['fields'] = '*';
		if( input::get('image_name',false) !== false )
		{
			$params['image_name'] = input::get('image_name');
		}

        $result['data'] = app::get('topstore')->rpcCall('image.store.list', $params);
        $result['pagers'] = $this->__pager($params, $result['data']['total'], $isImageModal);

        //暂时不提供商城图片子分类
        $catListApiParams = [
            'store_id' => $this->storeId,
            'img_type' => $params['img_type'],
            'fields' => 'image_cat_id,image_cat_name',
        ];
        //$result['imageStoreCatList'] = app::get('topstore')->rpcCall('image.store.cat.imagetype.list',$catListApiParams);
        $result['imageStoreCatList'] = array();

		return $result;
	}

    /**
     * 图片搜索
     * @return mixed
     * xinyufeng
     */
	public function search()
	{
        //是否为弹出框的列表
        if( input::get('imageModal',false) )
        {
            $isImageModal = true;
            $pagedata['imageModal'] = true;
        }

		$result = $this->__getListData($isImageModal);
        $pagedata['activeImageCatId'] = input::get('image_cat_id',0);
        $pagedata['imageStoreCatList'] = $result['imageStoreCatList'];
        $pagedata['imagedata'] = $result['data']['list'];
        $pagedata['pagers'] = $result['pagers'];
        $pagedata['image_name'] = input::get('image_name',false);

        $pagedata['imageTypeSize'] = kernel::single('image_data_image')->getImageTypeSize(input::get('img_type','item'));

        return view::make('topstore/store/image/list.html', $pagedata);
	}

	public function delImgLink()
	{
        $params['image_id'] = implode(',', input::get('img_id') );
		try
		{
        	app::get('topstore')->rpcCall('image.delete.imageLink', $params, 'seller');
            $status = 'success';
            $msg = '删除成功';
		}
		catch( Exception $e)
		{
			$msg = $e->getMessage();
            $status = 'error';
		}
        $this->sellerlog('删除图片。图片ID是'.$params['image_id']);
        return $this->splash($status,null,$msg,true);
	}

	public function upImgName()
	{
        $params['url'] = input::get('url');
        $params['image_name'] = input::get('image_name');
        try
        {
            $status = 'success';
            app::get('topstore')->rpcCall('image.store.upImageName', $params, 'seller');
        }
        catch( LogicExpection $e )
        {
            $mag = $e->getMessage();
            $status = 'error';
        }
        $this->sellerlog('更新图片。图片名称是：'.$params['image_name']);
        $msg = '更新成功';
        return $this->splash($status,null,$msg,true);
	}

    /**
     * 图片上传
     * @return mixed
     * xinyufeng
     */
    public function loadImageModal()
    {
        $isImageModal = true;
        $result = $this->__getListData($isImageModal);
        $pagedata['imageStoreCatList'] = $result['imageStoreCatList'];
        $pagedata['imagedata'] = $result['data']['list'];
        $pagedata['pagers'] = $result['pagers'];
        $pagedata['load_id'] = rand(0,999);
        $pagedata['imageModal'] = true;
        if( input::get('isOnlyShow') )
        {
            $pagedata['isOnlyShow'] = input::get('isOnlyShow');
        }
        return view::make('topstore/store/image/upload.html', $pagedata);
    }

    /**
     * 图片移动文件夹弹出框
     */
    public function loadImageMoveCatModal()
    {
        $pagedata['imageId'] = input::get('image_id');

        $imgType = input::get('image_type','item');
        $catListApiParams = [
            'store_id' => $this->storeId,
            'fields' => 'image_cat_id,image_cat_name,img_type',
        ];
        $data = app::get('topstore')->rpcCall('image.store.cat.imagetype.list',$catListApiParams);

        $imageShopCatList['item'] = [];
        $imageShopCatList['store'] = [];
        foreach( (array)$data as $row)
        {
            $imageShopCatList[$row['img_type']][] = $row;
        }
        $pagedata['imageShopCatList'] = $imageShopCatList;
        $pagedata['activeImageType'] = $imgType;
        return view::make('topstore/store/image/modal/moveCat.html', $pagedata);
    }

    /**
     * 加载文件夹管理弹出框
     */
    public function loadImgCatModal()
    {
        $imgType = input::get('image_type','item');
        $imageCatId = input::get('image_cat_id',0);
        $catListApiParams = [
            'store_id' => $this->storeId,
            'img_type' => $imgType,
            'fields' => 'image_cat_id,image_cat_name,img_type',
        ];
        $data = app::get('topstore')->rpcCall('image.store.cat.imagetype.list',$catListApiParams);
        $pagedata['imageShopCatList'] = $data;
        $pagedata['activeImageType'] = $imgType;
        $pagedata['activeImageCatId'] = $imageCatId;

        if( input::get('folderlist') )
        {
            return view::make('topstore/store/image/folderlist.html', $pagedata);
        }
        else
        {
            return view::make('topstore/store/image/modal/folderMange.html', $pagedata);
        }
    }

    public function addImgCat()
    {
        $imgType = input::get('image_type');
        $imageCatName = app::get('topstore')->_('未命名文件夹');
        $catListApiParams = [
            'store_id' => $this->storeId,
            'img_type' => $imgType,
            'fields' => 'image_cat_id,image_cat_name',
        ];
        $data = app::get('topstore')->rpcCall('image.store.cat.imagetype.list',$catListApiParams);
        if( $data )
        {
            $arrImageCatName = array_column($data, 'image_cat_name');
            for( $i=0; $i<20; $i++)
            {
                $tmpImageCatName = $i ? $imageCatName.$i : $imageCatName;
                if( !in_array($tmpImageCatName, $arrImageCatName) )
                {
                    $imageCatName = $tmpImageCatName;
                    break;
                }
            }
        }

        try
        {
            $imageCatId = app::get('topstore')->rpcCall('image.store.cat.add',['image_cat_name'=>$imageCatName, 'img_type'=>$imgType,'store_id'=>$this->storeId]);
            $status = $imageCatId ? 'success' : 'error';
            $msg = $imageCatId ? ['image_cat_id'=>$imageCatId, 'image_cat_name'=>$imageCatName] : app::get('topstore')->_('创建文件夹失败');
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            $status = 'error';
        }
        catch( Exception $e)
        {
            $msg = app::get('topstore')->_('创建文件夹失败');
            $status = 'error';
        }

        if( $status == 'success' )
        {
            $this->sellerlog('创建图片文件夹，image_cat_name：['.$imgType.']:'.$imageCatName);
        }
        return $this->splash($status,null,$msg,true);
    }

    public function delImgCat()
    {
        $imageCatId = input::get('image_cat_id');
        try
        {
            $res = app::get('topstore')->rpcCall('image.store.cat.delete',['image_cat_id'=>$imageCatId, 'store_id'=>$this->storeId]);
            $status = $res ? 'success' : 'error';
            $msg = $res ?  app::get('topstore')->_('成功删除') : app::get('topstore')->_('删除失败');
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            $status = 'error';
        }
        catch( Exception $e)
        {
            $msg = app::get('topstore')->_('删除失败');
            $status = 'error';
        }

        if( $status == 'success' )
        {
            $this->sellerlog('删除图片文，image_cat_id：'.$imageCatId);
        }
        return $this->splash($status,null,$msg,true);
    }

    public function editImgCat()
    {
        $imageCatId = input::get('image_cat_id');
        $imageCatName = trim(input::get('image_cat_name'));
        $validator = validator::make(
            array('image_cat_name' => $imageCatName),
            array('image_cat_name' => 'required|max:10'),
            array('image_cat_name' => '文件夹名称必填|文件名称不能超过10个字')
        );
        if( $validator->fails() )
        {
            $msg = $validator->messages()->first('image_cat_name');
            return $this->splash('error',null,$msg,true);
        }

        try
        {
            $res = app::get('topstore')->rpcCall('image.store.cat.update',['image_cat_id'=>$imageCatId, 'image_cat_name'=>$imageCatName,'store_id'=>$this->storeId]);
            $status = $res ? 'success' : 'error';
            $msg = $res ?  app::get('topstore')->_('创建文件夹成功') : app::get('topstore')->_('创建文件夹失败');
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            $status = 'error';
        }
        catch( Exception $e)
        {
            $msg = app::get('topstore')->_('创建文件夹失败');
            $status = 'error';
        }

        if( $status == 'success' )
        {
            $this->sellerlog('更新图片文件夹名称，image_cat_name：['.$imgType.']:'.$imageCatName);
        }
        return $this->splash($status,null,$msg,true);
    }

    /**
     * 移动图片
     */
    public function moveImageCat()
    {
        $imageId = input::get('image_id');
        $imageCatId = input::get('image_cat_id');
        $moveApiParams = [
            'store_id' => $this->storeId,
            'image_id' => $imageId,
            'image_cat_id' => $imageCatId,
            'img_type' => input::get('img_type'),
        ];

        $msg = '移动完成';
        try
        {
            $result = app::get('topstore')->rpcCall('image.store.move.cat',$moveApiParams);
            $status = 'success';
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            $status = 'error';
        }
        catch( \Exception $e)
        {
            $msg = app::get('topstore')->_('移动失败');
            $status = 'error';
        }

        $params =  array(
            'img_type'=>input::get('img_type','item'),
            'image_cat_id'=>$imageCatId
        );
        $url = url::action('topstore_ctl_store_image@index', $params);

        if( $status == 'success' )
        {
            $this->sellerlog('移动图片，图片ID：'.$imageId);
        }
        return $this->splash($status,$url,$msg,true);
    }
}
