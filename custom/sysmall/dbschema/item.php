<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/6/15
 * Time: 14:57
 */
return  array(
    'columns'=> array(
        'mall_item_id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true,
            'comment' => app::get('sysmall')->_('item_id'),
        ),
        'item_id' => array(
            'type' => 'table:item@sysitem',
            'required' => true,
            'label' => app::get('sysmall')->_('商品名称'),
            'comment' => app::get('sysmall')->_('商品ID'),
            'in_list' => true,
            'default_in_list' => true,
            'order' => 10,
        ),
        'shop_id' => array(
            'type' => 'table:shop@sysshop',
            'required' => true,
            'label' => app::get('sysmall')->_('店铺名称'),
            'comment' => app::get('sysmall')->_('店铺ID'),
            'in_list' => true,
            'default_in_list' => true,
            'order' => 11,
        ),
        'status' => array(
            'type' => array(
                'pending' => app::get('sysmall')->_('待审核'),
                'refuse' => app::get('sysmall')->_('审核拒绝'),
                'onsale' => app::get('sysmall')->_('出售中'),
                'instock' => app::get('sysmall')->_('库中'),
            ),
            'default' => 'pending',
            'label' => app::get('sysmall')->_('商品状态'),
            'comment' => app::get('sysmall')->_('商品状态'),
            'in_list' => true,
            'default_in_list' => true,
            'order' => 12,
        ),
        'reason'=>array(
            'type' => 'string',
            'length' => 500,
            'in_list'=>true,
            'default_in_list'=>true,
            'label' => app::get('sysmall')->_('不通过原因'),
            'comment' => app::get('sysmall')->_('审核不通过原因'),
            'order' => 13,
        ),
        'created_time' => array(
            'type' => 'time',
            'in_list' => true,
            'default_in_list' => true,
            'comment' => app::get('sysmall')->_('创建时间'),
            'label' => app::get('sysmall')->_('创建时间'),
            'order' => 14,
        ),
        'modified_time' => array(
            'type' => 'last_modify',
            'label' => app::get('sysmall')->_('更新时间'),
            'comment' => app::get('sysmall')->_('最后更新时间'),
            'in_list' => true,
            'default_in_list' => true,
            'order' => 15,
        ),
        'deleted' => array(
            'type' => array(
                0 => app::get('sysmall')->_('正常'),
                1 => app::get('sysmall')->_('删除'),
            ),
            'default' => 0,
            'comment' => app::get('sysmall')->_('是否删除'),
        ),
    ),
    'primary' => 'mall_item_id',
    'index' => array(
        'ind_unique' => [
            'columns' => ['shop_id', 'item_id'],
            'prefix' => 'unique',
        ],
    ),
    'comment' => app::get('sysmall')->_('选货商品表'),
);