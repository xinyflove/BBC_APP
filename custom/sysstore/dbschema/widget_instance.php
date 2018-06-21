<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/3
 * Time: 17:27
 * Desc: 挂件表
 */
return  array(
    'columns'=> array(
        'widget_id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true, // 必须
            'comment' => app::get('sysstore')->_('序号'),
        ),
        'store_id' => array(
            'type' => 'table:store@sysstore',
            'required' => true,
            'comment' => app::get('sysstore')->_('商城id'),
            'label' => app::get('sysstore')->_('商城名称'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'widget_name' => array(
            'type' => 'string',
            'default' => '',
            'required' => true,
            'comment' => app::get('sysstore')->_('挂件名称'),
        ),
        'page_type' => array(
            'type' => array(
                'home' => app::get('sysstore')->_('首页'),
                'list' => app::get('sysstore')->_('列表页'),
                'active' => app::get('sysstore')->_('活动页'),
            ),
            'default' => 'home',
            'comment' => app::get('sysstore')->_('页面类型'),
            'label' => app::get('sysstore')->_('页面类型'),
        ),
        'widget_type' => array(
            'type' => 'string',
            'required' => true,
            'comment' => app::get('sysstore')->_('挂件类型'),
            'label' => app::get('sysstore')->_('挂件类型'),
            'width' => 150,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'params'=>array(
            'type'=>'text',
            'default' => '',
            'label' => app::get('sysstore')->_('挂件参数'),
            'comment' => app::get('sysstore')->_('挂件参数'),
        ),
        'order_sort' => array(
            'type' => 'number',
            'comment' => app::get('sysstore')->_('排序'),
            'label' => app::get('sysstore')->_('排序'),
            'width' => 110,
            'default' => 0,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'template_path' => array(
            'type' => 'string',
            'required' => false,
            'comment' => app::get('sysstore')->_('挂件模版路径'),
        ),
        'disabled' => array(
            'type' => array(
                0 => app::get('sysstore')->_('启用'),
                1 => app::get('sysstore')->_('禁用'),
            ),
            'default' => 0,
            'comment' => app::get('sysstore')->_('是否禁用'),
        ),
        'created_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('sysstore')->_('创建时间'),
            'comment' => app::get('sysstore')->_('创建时间'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'modified_time' => array(
            'type' => 'last_modify',
            'required' => false,
            'label' => app::get('sysstore')->_('修改时间'),
            'comment' => app::get('sysstore')->_('修改时间'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'deleted' => array(
            'type' => array(
                0 => app::get('sysstore')->_('正常'),
                1 => app::get('sysstore')->_('删除'),
            ),
            'default' => 0,
            'comment' => app::get('sysstore')->_('是否已删除'),
        )

    ),
    'primary' => 'widget_id',	//主键
    'index' => array (  //索引
        'id' => [
            'columns' => ['widget_id', 'store_id'],   // 需要建立索引的字段名
        ],
        'name' => [
            'columns' => ['widget_type'],   // 需要建立索引的字段名
        ],
    ),
    'comment' => app::get('sysstore')->_('挂件实例表'),
);