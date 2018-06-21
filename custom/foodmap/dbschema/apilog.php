<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2017/12/21
 * Time: 10:00
 */
return  array(
    'columns'=> array(
        'apilog_id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true, // 必须
            'comment' => app::get('foodmap')->_('序号'),
        ),
		'api_type' => array (
			'type' => 'string',
			'length' => 50,
			'default' => '',
			'required' => true,
			'comment' => app::get('foodmap')->_('接口类型'),
			'label' => app::get('foodmap')->_('接口类型'),
			'in_list' => true,
			'default_in_list' => true,
			//'filterdefault' => true,
		),
		'api_url' => array (
			'type' => 'string',
			'default' => '',
			'required' => true,
			'comment' => app::get('foodmap')->_('接口链接'),
			'label' => app::get('foodmap')->_('接口链接'),
			'in_list' => true,
			'default_in_list' => true,
		),
        'api_param' => array (
            'type' => 'text',
            'default' => '',
            'required' => false,
            'comment' => app::get('foodmap')->_('接口参数'),
            'label' => app::get('foodmap')->_('接口参数'),
        ),
        'api_url_status' => array (
            'type' => array(
                0 => app::get('foodmap')->_('成功'),
                1 => app::get('foodmap')->_('失败'),
            ),
            'default' => 0,
            'comment' => app::get('foodmap')->_('接口链接调用状态'),
            'label' => app::get('foodmap')->_('接口链接调用状态'),
			'in_list' => true,
			'default_in_list' => true,
            'filtertype' => 'normal', // 高级搜索
            'filterdefault' => 'true',
        ),
        'api_url_msg' => array (
            'type' => 'string',
            'default' => '',
            'required' => false,
            'comment' => app::get('foodmap')->_('接口链接调用信息'),
            'label' => app::get('foodmap')->_('接口链接调用信息'),
        ),
        'api_res_code' => array (
            'type' => 'string',
            'length' => 10,
            'default' => '',
            'required' => false,
            'comment' => app::get('foodmap')->_('接口链接调用返回编码'),
            'label' => app::get('foodmap')->_('接口链接调用返回编码'),
			'in_list' => true,
			'default_in_list' => true,
        ),
        'api_res_data' => array (
            'type' => 'text',
            'default' => '',
            'required' => false,
            'comment' => app::get('foodmap')->_('接口链接调用返回数据'),
            'label' => app::get('foodmap')->_('接口链接调用返回数据'),
        ),
        'api_log_code' => array (
            'type' => 'string',
            'length' => 10,
            'default' => '',
            'required' => false,
            'comment' => app::get('foodmap')->_('接口调用编码'),
            'label' => app::get('foodmap')->_('接口调用编码'),
        ),
        'api_log_msg' => array (
            'type' => 'string',
            'default' => '',
            'required' => false,
            'comment' => app::get('foodmap')->_('接口调用信息'),
            'label' => app::get('foodmap')->_('接口调用信息'),
			'in_list' => true,
			'default_in_list' => true,
        ),
        'create_time' => array(
            'type' => 'time',
            'required' => true,
            'label' => app::get('foodmap')->_('创建时间'),
            'comment' => app::get('foodmap')->_('创建时间'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'modified_time' => array(
            'type' => 'last_modify',
            'required' => false,
            'label' => app::get('foodmap')->_('修改时间'),
            'comment' => app::get('foodmap')->_('修改时间'),
            'width' => 110,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'deleted' => array(
            'type' => array(
                0 => app::get('foodmap')->_('正常'),
                1 => app::get('foodmap')->_('删除'),
            ),
            'default' => 0,
            'comment' => app::get('foodmap')->_('是否已删除'),
        )
    ),
    'primary' => 'apilog_id',	//主键
    'comment' => app::get('foodmap')->_('调用接口日志'),
);
