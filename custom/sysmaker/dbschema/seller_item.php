<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/6/15
 * Time: 14:57
 */
return  array(
    'columns'=> array(
        'id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true,
            'comment' => app::get('sysitem')->_('id'),
        ),
        'item_id' => array(
            'type' => 'table:item@sysitem',
            'required' => true,
            'label' => app::get('sysitem')->_('商品id'),
            'comment' => app::get('sysitem')->_('商品ID'),
            'in_list' => true,
            'default_in_list' => true,
            'order' => 10,
        ),
        'seller_id' => array(
            'type' => 'table:shop@sysshop',
            'required' => true,
            'label' => app::get('sysitem')->_('主持人id'),
            'comment' => app::get('sysitem')->_('主持人id'),
            'in_list' => true,
            'default_in_list' => true,
            'order' => 11,
        ),
        'created_time' => array(
            'type' => 'time',
            'in_list' => true,
            'default_in_list' => true,
            'comment' => app::get('sysitem')->_('创建时间'),
            'label' => app::get('sysitem')->_('创建时间'),
            'order' => 14,
        ),
        'modified_time' => array(
            'type' => 'last_modify',
            'label' => app::get('sysitem')->_('更新时间'),
            'comment' => app::get('sysitem')->_('最后更新时间'),
            'in_list' => true,
            'default_in_list' => true,
            'order' => 15,
        ),
        'deleted' => array(
            'type' => array(
                0 => app::get('sysitem')->_('正常'),
                1 => app::get('sysitem')->_('删除'),
            ),
            'default' => 0,
            'comment' => app::get('sysitem')->_('是否删除'),
        ),
    ),
    'primary' => 'id',
    'index' => array(
        'ind_unique' => [
            'columns' => ['seller_id', 'item_id'],
            'prefix' => 'unique',
        ],
    ),
    'comment' => app::get('sysitem')->_('主持人商品关联表'),
);