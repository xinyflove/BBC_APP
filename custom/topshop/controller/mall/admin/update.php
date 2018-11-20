<?php
/**
 * User: xinyufeng
 * Time: 2018-10-26 10:30
 * Desc: 广电优选商品修改操作
 */
class topshop_ctl_mall_admin_update extends topshop_ctl_mall_admin_controller {

    /**
     * 商品修改操作页面
     * @return string
     */
    public function index()
    {
        $input = input::get();
        $html = '';
        switch ($input['op'])
        {
            case 'shop_cat':
                $html =  $this->__updateShopCatPage($input);
                break;
            default:
                break;
        }
        
        return $html;
    }

    /**
     * 保存商品修改
     * @return string
     */
    public function save()
    {
        $input = input::get();
        $json = '';
        switch ($input['op'])
        {
            case 'shop_cat':
                $json =  $this->__updateShopCatSave($input);
                break;
            default:
                break;
        }

        return $json;
    }

    /**
     * 修改商品店铺分类页面
     * @param $params
     * @return mixed
     */
    private function __updateShopCatPage($params)
    {
        $scparams['shop_id'] = $this->shopId;
        $scparams['fields'] = 'cat_id,cat_name,is_leaf,parent_id,level';
        $pagedata['shopCatList'] = app::get('topshop')->rpcCall('shop.cat.get',$scparams);
        $selectedShopCids = explode(',', $params['shop_cat_id']);
        foreach($pagedata['shopCatList'] as &$v)
        {
            if($v['children'])
            {
                foreach($v['children'] as &$vv)
                {
                    if(in_array($vv['cat_id'], $selectedShopCids))
                    {
                        $vv['selected'] = true;
                    }
                }
            }
            else
            {
                if(in_array($v['cat_id'], $selectedShopCids))
                {
                    $v['selected'] = true;
                }
            }
        }

        $pagedata['item_id'] = $params['item_id'];
        $pagedata['op'] = $params['op'];

        return view::make('topshop/mall/admin/update_shop_cat.html', $pagedata);
    }

    /**
     * 保存商品店铺分类
     * @param $params
     * @return string
     */
    private function __updateShopCatSave($params)
    {
        $url = request::server('HTTP_REFERER');

        try
        {
            if(empty($params['item_id']))
            {
                $msg = app::get('topshop')->_('商品ID不能为空');
                return $this->splash('error',null,$msg, true);
            }

            if(empty($params['item']['shop_cids']))
            {
                $msg = app::get('topshop')->_('商品店铺分类不能为空');
                return $this->splash('error',null,$msg, true);
            }

            $updateData = array(
                'shop_cat_id' => ','.implode(',', $params['item']['shop_cids']).',', // 店铺中分类
            );
            $itemMdl = app::get('sysitem')->model('item');
            $itemMdl->update($updateData, array('item_id'=>$params['item_id']));
        }
        catch(Exception $e)
        {
            return $this->splash('error',null, $e->getMessage(), true);
        }

        return $this->splash('success',$url,'修改成功', true);
    }
}