<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/6/15
 * Time: 14:06
 */
class sysmall_ctl_item extends desktop_controller{

    /**
     * 列表数据
     * @return mixed
     */
    public function lists()
    {
        $actions = array(
            array(
                'label'=>app::get('sysmall')->_('删除'),
                'icon' => 'download.gif',
                'submit' => '?app=sysmall&ctl=item&act=doDelete',
                'confirm' => app::get('sysmall')->_('确定要删除选中商品？'),
            ),
        );
        return $this->finder('sysmall_mdl_item',array(
            'title' => '商品列表',
            'actions' => $actions,
            'use_buildin_delete' => false,
            'use_buildin_filter'=> true,
            'base_filter'=>array( //对列表数据进行过滤筛选
                'deleted'=> 0,
            ),
        ));
    }

    /**
     * 列表tab
     * @return array
     */
    public function _views()
    {
        $subMenu = array(
            0=>array(
                'label'=>app::get('sysmall')->_('待审核'),
                'optional'=>false,
                'filter'=>array(
                    'status'=>'pending',
                ),
            ),
            1=>array(
                'label'=>app::get('sysmall')->_('审核通过'),
                'optional'=>false,
                'filter'=>array(
                    'status'=>'onsale',
                ),

            ),
            2=>array(
                'label'=>app::get('sysmall')->_('审核驳回'),
                'optional'=>false,
                'filter'=>array(
                    'status'=>'refuse',
                ),
            ),
            3=>array(
                'label'=>app::get('sysmall')->_('全部'),
                'optional'=>false,
            ),
        );
        return $subMenu;
    }

    /**
     * 审核页面
     * @param $item_id
     */
    public function check($item_id)
    {
        $pagedata = array('item_id' => $item_id);

        return $this->page('sysmall/item/check.html', $pagedata);
    }

    /**
     * 保存审核数据
     */
    public function checkSave()
    {
        $this->begin();

        $postdata = input::get('mall_item');
        $objMallDataItem = kernel::single('sysmall_data_item');
        $res = $objMallDataItem->update($postdata, $msg);

        // 由于原始商品更新数据,提示代售商品更新数据
        if($postdata['status'] == 'onsale')
        {
            $sellData = array('init_is_change' => 1);
            $sellFilter = array(
                'init_item_id' => $postdata['item_id'],
            );
            app::get('sysitem')->model('item')->update($sellData, $sellFilter);
        }

        $this->end($res, $msg);
    }

    /**
     * 删除选货商品
     */
    public function doDelete()
    {
        $mall_item_id = input::get('mall_item_id');
        $this->begin('?app=sysmall&ctl=item&act=lists');
        
        if(empty($mall_item_id))
        {
            $msg = "请选择要操作的数据项";
            $this->end(false,$msg);
        }

        try{
            $filter = array('mall_item_id' => $mall_item_id);
            kernel::single('sysmall_data_item')->delete($filter);
        }catch(Exception $e){
            $msg = $e->getMessage();
            $this->end(false,$msg);
        }

        $this->end(true,app::get('sysmall')->_('删除数据成功'));
    }
}