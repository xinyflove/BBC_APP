<?php

class topshop_ctl_selector_item extends topshop_controller {
    public $limit = 14;

    public function loadSelectGoodsModal()
    {
        if(input::get('limit')){
            $pagedata['limit'] = input::get('limit');
        }

        $isImageModal = true;
        $pagedata['imageModal'] = true;
        $pagedata['textcol'] = input::get('textcol');
        $pagedata['view'] = input::get('view');
        $pagedata['shopCatList'] = app::get('topshop')->rpcCall('shop.authorize.cat',array('shop_id'=>$this->shopId));
		/*add_2018/11/29_by_wanghaichao_start*/
		//筛选广电优选的商品
		if(input::get('mall')){
			$pagedata['mall']=input::get('mall');
		}
		/*add_2018/11/29_by_wanghaichao_end*/
		
        return view::make('topshop/selector/item/index.html', $pagedata);
    }

    public function formatSelectedGoodsRow()
    {
        $itemIds = input::get('item_id');
        $textcol = input::get('textcol');
        $ac = input::get('ac');
        $extendView = input::get('view');
        $itemSku = input::get('item_sku');
        $itemIdsChunk = array_chunk($itemIds, 20);
		/*add_2018/7/11_by_wanghaichao_start*/
		//增加排序的
		$pagedata['sort']=input::get('sort');
		$itemSort=input::get('item_sort');
		if($itemSort){
			foreach($itemSort as $k=>$v){
				$key=str_replace("'",'',$k);
				$sort[$key]=$v;
			}
			$pagedata['itemSort']=$sort;
		}
		/*add_2018/7/11_by_wanghaichao_end*/
		
        $itemsList = array();
        foreach( $itemIdsChunk as $value )
        {
            $searchParams = array(
                'item_id' => implode(',',$value),
                'shop_id' => $this->shopId,
                'fields' => 'item_id,title,image_default_id,cat_id,brand_id,price,nospec,sub_title,item_store',
            );
            $itemsListData = app::get('syspromotion')->rpcCall('item.list.get',$searchParams);
            $itemsList = array_merge($itemsList,$itemsListData);
        }

        foreach($itemsList as $key=>$value)
        {
            if(input::get('pricemin'))
            {
                $itemsList[$key]['discount_min'] = input::get('pricemin');
            }
            if(input::get('pricemax'))
            {
                $itemsList[$key]['discount_max'] = input::get('pricemax');
            }
            if( $itemSku )
            {
                $itemsList[$key]['item_sku'] = $itemSku[$value['item_id']];
            }
        }

        $extends = input::get('extends');
        $extendsData = input::get('extends_data');
        if( count($extends) > 0 )
        {
            $fmtItemExtendsData = [];
            foreach($extendsData as $item)
            {
                $itemId = $item['item_id'];

                $fmtItemExtendsData[$itemId] = $item;
            }

            foreach($itemsList as $key=>$value)
            {
                $itemId = $value['item_id'];
                $itemsList[$key]['extendsData'] = $fmtItemExtendsData[$itemId];
            }

            $pagedata['_input']['extends'] = $extends;
        }

        $datavalues = input::get('values');
        if(count($datavalues) > 0)
        {
            $valuesData = [];
            foreach($datavalues as $item)
            {
                $itemId = $item['item_id'];

                $valuesData[$itemId] = $item;
            }

            foreach($itemsList as $key=>$value)
            {
                $itemId = $value['item_id'];
                $itemsList[$key]['datavalue'] = $valuesData[$itemId];
            }
        }

        $pagedata['_input']['itemsList'] = $itemsList;
        $pagedata['_input']['view'] = $extendView;
        if(!$textcol)
        {
            $pagedata['_input']['_textcol'] = 'title';
        }
        else
        {
            $pagedata['_input']['_textcol'] = explode(',',$textcol);
        }
        $pagedata['ac'] = $ac;
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        $pagedata['is_select_sku'] = input::get('is_select_sku','true');
        return view::make('topshop/selector/item/input-row.html', $pagedata);
    }


    //挂件专用  jiangyunhan 20180530
    public function formatSelectedGoodsRowForWidget()
    {
        $itemIds = input::get('item_id');
        $textcol = input::get('textcol');
        $ac = input::get('ac');
        $extendView = input::get('view');
        $itemSku = input::get('item_sku');

        $itemIdsChunk = array_chunk($itemIds, 20);
        $itemsList = array();
        foreach( $itemIdsChunk as $value )
        {
            $searchParams = array(
                'item_id' => implode(',',$value),
                'shop_id' => $this->shopId,
                'fields' => 'item_id,title,image_default_id,cat_id,brand_id,price,nospec,sub_title',
            );
            $itemsListData = app::get('syspromotion')->rpcCall('item.list.get',$searchParams);
            $itemsList = array_merge($itemsList,$itemsListData);
        }

        foreach($itemsList as $key=>$value)
        {
            if(input::get('pricemin'))
            {
                $itemsList[$key]['discount_min'] = input::get('pricemin');
            }
            if(input::get('pricemax'))
            {
                $itemsList[$key]['discount_max'] = input::get('pricemax');
            }
            if( $itemSku )
            {
                $itemsList[$key]['item_sku'] = $itemSku[$value['item_id']];
            }
        }

        $extends = input::get('extends');
        $extendsData = input::get('extends_data');
        if( count($extends) > 0 )
        {
            $fmtItemExtendsData = [];
            foreach($extendsData as $item)
            {
                $itemId = $item['item_id'];

                $fmtItemExtendsData[$itemId] = $item;
            }

            foreach($itemsList as $key=>$value)
            {
                $itemId = $value['item_id'];
                $itemsList[$key]['extendsData'] = $fmtItemExtendsData[$itemId];
            }

            $pagedata['_input']['extends'] = $extends;
        }

        $datavalues = input::get('values');
        if(count($datavalues) > 0)
        {
            $valuesData = [];
            foreach($datavalues as $item)
            {
                $itemId = $item['item_id'];

                $valuesData[$itemId] = $item;
            }

            foreach($itemsList as $key=>$value)
            {
                $itemId = $value['item_id'];
                $itemsList[$key]['datavalue'] = $valuesData[$itemId];
            }
        }

        $pagedata['_input']['itemsList'] = $itemsList;
        $pagedata['_input']['view'] = $extendView;
        if(!$textcol)
        {
            $pagedata['_input']['_textcol'] = 'title';
        }
        else
        {
            $pagedata['_input']['_textcol'] = explode(',',$textcol);
        }
        $pagedata['ac'] = $ac;
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        $pagedata['is_select_sku'] = input::get('is_select_sku','true');
        return view::make('topshop/selector/item/input-row-widget.html', $pagedata);
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
    public function searchItem($json=true)
    {
        $shopId = $this->shopId;
        $catId = input::get('catId');
        $brandId = input::get('brandId');
        $keywords = input::get('searchname');
        $brandName = input::get('searchbrand');
        $bn = input::get('searchbn');
		/*add_2018/11/29_by_wanghaichao_start*/
		//过滤广电优选商品
		$mall=input::get('mall');
		/*add_2018/11/29_by_wanghaichao_end*/
        $pages = input::get('pages');
        $limit = input::get('limit') ? input::get('limit') : $this->limit;
        $searchParams = array(
            'shop_id' => $shopId,
            'brand_id' => $brandId,
            'approve_status' => 'onsale',
            'search_keywords' => $keywords,
            'bn' => $bn,
            'page_no' => intval($pages),
            'page_size' => intval($limit),
        );
        if($catId)
        {
            $searchParams['cat_id'] = app::get('topshop')->rpcCall('category.cat.get.leafCatId',array('cat_id'=>intval($catId)));
        }
        if(trim($brandName) && trim($brandName) != 'undefined')
        {
            $searchBrandParams = array('brand_name'=>trim($brandName),'fields'=>'brand_id');
            $brand = app::get('topshop')->rpcCall('category.brand.get.list', $searchBrandParams);
            if($brand)
            {
                $tmpBrandIds = array_column($brand, 'brand_id');
                $searchParams['brand_id'] = implode(',', $tmpBrandIds);
            }
            else
            {
                return view::make('topshop/selector/item/list.html', $pagedata);
            }
        }

        $searchParams['fields'] = 'item_id,title,image_default_id,price,brand_id';
		/*add_2018/11/29_by_wanghaichao_start*/
		//过滤广电优选商品
		if(isset($mall) && $mall){
			$searchParams['mall_shop_id']=$this->shopId;
		}		
		/*add_2018/11/29_by_wanghaichao_end*/
		
        $itemsList = app::get('topshop')->rpcCall('item.search', $searchParams);

        $pagedata['itemsList'] = $itemsList['list'];
        $pagedata['total'] = $itemsList['total_found'];
        $totalPage = ceil($itemsList['total_found']/$limit);
        $filter = input::get();
        $filter['pages'] = time();
        $pagers = array(
            'link' => url::action('topshop_ctl_selector_item@searchItem', $filter),
            'current' => $pages,
            'use_app' => 'topshop',
            'total' => $totalPage,
            'token' => time(),
        );
        $pagedata['pagers'] = $pagers;

        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        return view::make('topshop/selector/item/list.html', $pagedata);
    }

    public function getSkuByItemId()
    {
        $searchParams['fields'] = 'sku_id,item_id,title,image_default_id,price,brand_id,spec_info,status';
        $searchParams['item_id'] = input::get('itemId');
        $searchParams['shop_id'] = $this->shopId;
        $skusList = app::get('topshop')->rpcCall('sku.search', $searchParams);
        $pagedata['_input']['skusList'] = $skusList['list'];
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        return view::make('topshop/selector/item/skus.html', $pagedata);
    }

    /**
     * 获取指定商品的sku，并且显示指定的sku
     */
    public function showSkuByitemId()
    {
        $searchParams['fields'] = 'sku_id,item_id,title,image_default_id,price,brand_id,spec_info,status';
        $searchParams['item_id'] = input::get('itemId');
        $searchParams['shop_id'] = $this->shopId;
        $skusList = app::get('topshop')->rpcCall('sku.search', $searchParams);
        $pagedata['_input']['skusList'] = $skusList['list'];
        $pagedata['_input']['sku_ids'] = explode(',',input::get('sku_id'));
        return view::make('topshop/selector/item/show_skus.html', $pagedata);
    }
}
