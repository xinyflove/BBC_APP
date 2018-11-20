<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-13
 * Desc: 店铺创客关联表
 */
return array(
    'columns' => array(
        'shop_id' => array(
            'type' => 'table:shop@sysshop',
            'required' => true,
            'comment' => app::get('sysmaker')->_('关联店铺'),
        ),
        'seller_id' => array(
            'type' => 'table:seller',
            'required' => true,
            'comment' => app::get('sysmaker')->_('关联创客id'),
        ),
        'status' => array(
            'type' => array(
                'pending' => app::get('sysmaker')->_('待审核'),
                'refuse' => app::get('sysmaker')->_('审核拒绝'),
                'success' => app::get('sysmaker')->_('审核通过'),
            ),
            'default' => 'pending',
            'label' => app::get('sysmaker')->_('审核状态'),
            'comment' => app::get('sysmaker')->_('审核状态'),
            'in_list' => true,
            'default_in_list' => true,
            'order' => 12,
        ),
        'reason'=>array(
            'type' => 'string',
            'length' => 500,
            'in_list'=>true,
            'default_in_list'=>true,
            'label' => app::get('sysmaker')->_('不通过原因'),
            'comment' => app::get('sysmaker')->_('审核不通过原因'),
            'order' => 13,
        ),
        'created_time' => array(
            'type' => 'time',
            'in_list' => true,
            'default_in_list' => true,
            'comment' => app::get('sysmaker')->_('创建时间'),
            'label' => app::get('sysmaker')->_('创建时间'),
            'order' => 14,
        ),
        'modified_time' => array(
            'type' => 'last_modify',
            'label' => app::get('sysmaker')->_('更新时间'),
            'comment' => app::get('sysmaker')->_('最后更新时间'),
            'in_list' => true,
            'default_in_list' => true,
            'order' => 15,
        ),
        'deleted' => array(
            'type' => array(
                0 => app::get('sysmaker')->_('正常'),
                1 => app::get('sysmaker')->_('删除'),
            ),
            'default' => 0,
            'comment' => app::get('sysmaker')->_('是否删除'),
        ),
    ),
    'primary' => ['shop_id', 'seller_id'],
    'comment' => app::get('sysmaker')->_('店铺创客关联表'),
);
