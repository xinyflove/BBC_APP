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
        'widgets_id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true, // 必须
            'comment' => app::get('sysstore')->_('序号'),
        ),
        'widgets_name' => array(
            'type' => 'string',
            'default' => '',
            'required' => true,
            'comment' => app::get('sysstore')->_('挂件名称'),
            'label' => app::get('sysstore')->_('挂件名称'),
            'width' => 150,
            'in_list' => true,
            'default_in_list' => true,
            'searchtype' => 'has',
        ),
        'widgets_title' => array(
            'type' => 'string',
            'default' => '',
            'required' => true,
            'comment' => app::get('sysstore')->_('挂件标题'),
            'label' => app::get('sysstore')->_('挂件标题'),
            'width' => 150,
            'in_list' => true,
            'default_in_list' => true,
            'searchtype' => 'has',
        ),
        'widgets_desc'=>array(
            'type'=>'text',
            'default' => '',
            'label' => app::get('sysstore')->_('挂件描述'),
            'comment' => app::get('sysstore')->_('挂件描述'),
        ),
        'widgets_author' => array(
            'type' => 'string',
            'default' => '',
            'required' => true,
            'comment' => app::get('sysstore')->_('挂件作者'),
            'label' => app::get('sysstore')->_('挂件作者'),
        ),
        'platform' => array(
            'type' => array(
                'pc' => app::get('sysstore')->_('PC'),
                'wap' => app::get('sysstore')->_('WAP'),
            ),
            'default' => 'wap',
            'comment' => app::get('sysstore')->_('平台'),
            'label' => app::get('sysstore')->_('平台'),
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
        /*'deleted' => array(
            'type' => array(
                0 => app::get('sysstore')->_('正常'),
                1 => app::get('sysstore')->_('删除'),
            ),
            'default' => 0,
            'comment' => app::get('sysstore')->_('是否已删除'),
        )*/

    ),
    'primary' => 'widgets_id',	//主键
    'index' => array (  //索引
        'id' => [
            'columns' => ['widgets_id'],   // 需要建立索引的字段名
        ],
        'name' => [
            'columns' => ['widgets_name'],   // 需要建立索引的字段名
        ],
    ),
    'comment' => app::get('sysstore')->_('挂件表'),
);