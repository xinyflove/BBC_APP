<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2018/6/19
 * Time: 15:18
 */
class sysmall_finder_item {
    /*操作列开始*/
    public $column_edit = '操作';
    public $column_edit_order = 2;
    public $column_edit_width = 200;

    /**
     * 编辑链接
     * @param $colList
     * @param $list
     */
    public function column_edit(&$colList, $list){
        foreach($list as $k=>$row)
        {
            if($row['status'] == 'pending')
            {
                $editUrl = '?app=sysmall&ctl=item&act=check&finder_id='.$_GET['_finder']['finder_id'].'&p[0]='.$row['item_id'];
                $editTar = 'target="dialog::{title:\''.app::get('sysmall')->_('审核').'\', width:400, height:250}"';
                $html = '<a href="'.$editUrl.'" '.$editTar.'>'.app::get('sysmall')->_('审核').'</a>';

                $colList[$k] = $html;
            }
        }
    }
    /*操作列结束*/

    /*缩略图列开始*/
    public $column_image_default_id = "缩略图";
    public $column_image_default_id_order = 1;

    /**
     * @param $colList
     * @param $list
     */
    public function column_image_default_id(&$colList, $list)
    {
        foreach($list as $k=>$row)
        {
            if($row['item_id'])
            {
                $item = app::get('sysitem')->model('item')
                    ->getRow('image_default_id', array('item_id'=>$row['item_id']));
                if($item['image_default_id'])
                {
                    $src = base_storager::modifier($item['image_default_id']);
                    $colList[$k] = "<a href='$src' class='img-tip pointer' target='_blank' onmouseover='bindFinderColTip(event);'><span><i class='fa fa-picture-o'></i></span></a>";
                }
            }
        }
    }
    /*缩略图列结束*/

    /*查看列-基本信息开始*/
    public $detail_basic = '基本信息';
    public function detail_basic($id)
    {
        $where['mall_item_id'] = $id;
        $where['fields'] = "item_id";
        $mall_item = app::get('sysmall')->rpcCall('mall.item.get',$where);//获取选货商品详情数据

        $params['item_id'] = $mall_item['item_id'];
        $params['fields'] = "*,item_store";
        $pagedata = app::get('sysitem')->rpcCall('item.get',$params);//获取原始商品详情数据

        // 获取运费模板开始
        $tmpParams = array(
            'shop_id' => $pagedata['shop_id'],
            'template_id' => $pagedata['dlytmpl_id'],
            'status' => 'on',
            'fields' => 'shop_id,name,template_id',
        );
        $templateInfo = app::get('sysitem')->rpcCall('logistics.dlytmpl.get',$tmpParams);
        $pagedata['templateName'] = $templateInfo['name'];
        // 获取运费模板结束

        // 获取所属分类开始
        $catParams = array(
            'shop_id' => $pagedata['shop_id'],
            'cat_id' =>$pagedata['cat_id'],
            'fields' => 'cat_id,cat_name,is_leaf,parent_id,level');
        $pagedata['catInfo'] = app::get('sysitem')->rpcCall('category.cat.get.data',$catParams);
        // 获取所属分类结束

        // 获取店铺分类开始
        $shop_cat_id = explode(',',$pagedata['shop_cat_id']);
        $shopCatParams = array(
            'shop_id' => $pagedata['shop_id'],
            'cat_id' => $shop_cat_id['1'],
            'fields' => 'cat_id,cat_name,is_leaf,parent_id,level'
        );
        $shopCatData = app::get('sysitem')->rpcCall('shop.cat.get',$shopCatParams);
        foreach ($shopCatData as $key => $value) {
            $pagedata['childCatName'] = $value['cat_name'];
            $pagedata['parent_id'] = $value['parent_id'];
            if($value['parent_id']){
                $pagedata['parentCatInfo'] = app::get('sysitem')->rpcCall('shop.cat.get',array('shop_id' => $pagedata['shop_id'],'cat_id'=>$value['parent_id'],'fields'=>'cat_name'));
            }
        }
        // 获取店铺分类结束

        return view::make('sysmall/item/detail.html',$pagedata)->render();
    }
    /*查看列-基本信息结束*/
}