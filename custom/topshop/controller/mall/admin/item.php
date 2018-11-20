<?php
/**
 * User: xinyufeng
 * Time: 2018-10-26 10:30
 * Desc: 广电优选商品编辑
 */
class topshop_ctl_mall_admin_item extends topshop_ctl_mall_admin_controller {

    public function edit()
    {
        $pagedata['return_to_url'] = request::server('HTTP_REFERER');
        $itemId = intval(input::get('item_id'));
        $pagedata['shopId'] = $this->shopId;

        // 店铺关联的商品品牌列表
        // 商品详细信息
        $params['item_id'] = $itemId;
        $params['shop_id'] = $this->shopId;
        $params['fields'] = "*,sku,item_store,item_status,item_count,item_desc,item_nature,spec_index";
        $pagedata['item'] = app::get('topshop')->rpcCall('item.get',$params);

        // 商家分类及此商品关联的分类标示selected
        $scparams['shop_id'] = $this->shopId;
        $scparams['fields'] = 'cat_id,cat_name,is_leaf,parent_id,level';
        $pagedata['shopCatList'] = app::get('topshop')->rpcCall('shop.cat.get',$scparams);
        $selectedShopCids = explode(',', $pagedata['item']['shop_cat_id']);
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

        // 获取运费模板列表
        $tmpParams = array(
            'shop_id' => $this->shopId,
            'status' => 'on',
            'fields' => 'shop_id,name,template_id',
        );
        $pagedata['dlytmpls'] = app::get('topshop')->rpcCall('logistics.dlytmpl.get.list',$tmpParams);
        $pagedata['dlytmpls']['data'] = array_bind_key($pagedata['dlytmpls']['data'],'template_id');
        /*start whc_商铺获取银行卡信息_2017/9/7*/
        if($pagedata['item']['bank_ids']!=''){
            $pagedata['item']['bank_ids']=explode(',',$pagedata['item']['bank_ids']);
        }
        $pagedata['banks']=$this->__getBanks();
        /*end*/

        // 收入类型列表
        $taxRateCfg=config::get('tax');
        $pagedata['taxrate']=$taxRateCfg;

        /*add_2017/9/23_by_wanghaichao_start 获取供应商信息*/
        $supplier_name = app::get('sysshop')->model('supplier')->getRow('supplier_name',['supplier_id'=>$pagedata['item']['supplier_id'],'is_audit'=>'PASS']);
        $pagedata['item']['supplier_name'] = $supplier_name['supplier_name'];
        /*add_2017/9/23_by_wanghaichao_end*/

        //获取线下店详情
        $agent_shop_ids = explode(',', $pagedata['item']['agent_shop_id']);
        $agent_shop_ids = array_filter($agent_shop_ids,function($v){
            return !empty($v);
        });
        $pagedata['item']['agent_shop_id'] = implode(',', $agent_shop_ids);
        if(is_array($agent_shop_ids)){
            $agentShopData = app::get('topshop')->rpcCall('supplier.agent.shop.search',['agent_shop_id'=>$agent_shop_ids]);
            $agentTemp = array();
            foreach ($agentShopData as $agent_k => $agent_v)
            {
                $agentTemp[] = $agent_v['name'];
            }
            $agent_shop_names = implode(',',$agentTemp);
        }else{
            $agent_shop_names = '';
        }
        $pagedata['item']['agent_shop_names'] = $agent_shop_names;
        $init_shop = app::get('sysshop')->model('shop')->getRow('offline', array('shop_id' => $pagedata['item']['init_shop_id']));
        $pagedata['offline'] = $init_shop['offline'];

        /*add_2017/9/24_by_wanghaichao_start*/
        $pagedata['valid_time']=date('Y/m/d H:i',$pagedata['item']['start_time']).'-'.date('Y/m/d H:i',$pagedata['item']['end_time']);
        if($pagedata['item']['sell_time']){
            $pagedata['sell_time']=date('Y/m/d H:i',$pagedata['item']['sell_time']);
        }
        if($pagedata['item']['sell_time_end']){
            $pagedata['sell_time_end']=date('Y/m/d H:i',$pagedata['item']['sell_time_end']);
        }
        /*add_2017/9/24_by_wanghaichao_end*/

        /*add_2018/6/14_by_王衍生_start*/
        if( $pagedata['item']['video_dir'] )
        {
            unset($params);
            $params['url'] = $pagedata['item']['video_dir'];
            $params['fields'] = '*';

            $video_url_arr = app::get('topshop')->rpcCall('video.item.list', $params);
            // if 为兼容早期视频没有入视频表的视频
            if(!$video_url_arr){
                $video_url_arr[0]['url'] = $pagedata['item']['video_dir'];
            }
            $pagedata['item']['video_dir'] = $video_url_arr;
        }
        /*add_2018/6/14_by_王衍生_end*/

		/*add_2018/11/8_by_wanghaichao_start*/
		$ticket=app::get('sysitem')->model('ticket')->getList('*',array('item_id'=>$itemId));
		if($ticket){
			foreach($ticket as $k=>&$v){
				$supplier=app::get('sysshop')->model('supplier')->getRow('supplier_name',array('supplier_id'=>$v['supplier_id']));
				$v['supplier_name']=$supplier['supplier_name'];
			}
			$pagedata['count']=count($ticket);
			$pagedata['ticket']=$ticket;
		}
		/*add_2018/11/8_by_wanghaichao_end*/

        $this->contentHeaderTitle = app::get('topshop')->_('编辑代售商品');

        return $this->page('topshop/mall/admin/item_edit.html', $pagedata);
    }

    private function __getBanks(){
        $shop_id=$this->shopId;
        $sql="SELECT sb.bank_id,sb.bank_name FROM sysbankmember_member AS sm LEFT JOIN sysbankmember_bank AS sb ON sm.bank_id=sb.bank_id WHERE sm.shop_id=".$shop_id." AND sm.deleted!=1 AND  sb.deleted!=1";
        $banks=app::get('base')->database()->executeQuery($sql)->fetchAll();
        return $banks;
    }

    public function save()
    {
        $postData = input::get();

        try
        {
            // 检查参数
            $this->_checkPost($postData);
            $itemFilter = array(
                'item_id' => $postData['item']['item_id'],
                'shop_id' => $this->shopId,
            );
            $itemData = array(
                'price' => $postData['item']['price'],  // 销售价
                //'dlytmpl_id' => $postData['item']['dlytmpl_id'],  // 运费模板id
                'shop_cat_id' => ','.implode(',', $postData['item']['shop_cids']).',', // 店铺中分类
            );
            $item_res = app::get('sysitem')->model('item')->update($itemData, $itemFilter);

            // 下架商品
            $statusData = array(
                'approve_status' => 'instock',
                'delist_time' => time(),
            );
            app::get('sysitem')->model('item_status')->update($statusData, $itemFilter);

            if($item_res && !empty($postData['item']['sku']))
            {
                $sku_arr = json_decode($postData['item']['sku'], ture);
                $sku_num = count($sku_arr);

                if($sku_num)
                {
                    foreach ($sku_arr as $sku_v)
                    {
                        $skuFilter = array(
                            'sku_id' => $sku_v['sku_id'],
                            'item_id' => $sku_v['item_id'],
                            'shop_id' => $sku_v['shop_id'],
                        );
                        $skuData = array(
                            'price' => $sku_num == 1 ? $itemData['price'] : $sku_v['price'],
                        );

                        $skuMdl = app::get('sysitem')->model('sku');
                        $sku_res = $skuMdl->update($skuData, $skuFilter);
                        unset($skuData, $skuFilter);
                    }
                }

                $this->sellerlog('保存商品。名称是'.$postData['title']);
                $url = input::get('return_to_url') ? : url::action('topshop_ctl_mall_admin_list@index');
                $msg = app::get('topshop')->_('保存成功');

                return $this->splash('success', $url, $msg, true);
            }
        }
        catch (Exception $e)
        {
            return $this->splash('error', '', $e->getMessage(), true);
        }
    }

    // 初步判断数据合法性
    private function _checkPost($postData)
    {
        if(!implode(',', $postData['item']['shop_cids']))
        {
            throw new Exception('店铺分类至少选择一项');
        }
    }
}