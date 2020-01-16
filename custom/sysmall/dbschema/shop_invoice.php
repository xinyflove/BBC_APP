<?php
return  array(
    'columns' => array(
        'id' => array(
            'type' => 'number',
            'autoincrement' => true,
            'required' => true,
            'comment' => app::get('sysmall')->_('序号'),
        ),
        'shop_id' => array(
            'type' => 'table:shop@sysshop',
            'required' => true,
            'comment' => app::get('sysmall')->_('所属店铺id'),
        ),
        'invoice_name' => array(
            'type' => 'string',
            'length' => 100,
            'required' => true,
            'comment' => app::get('sysmall')->_('发票抬头|公司名称'),
        ),
        'invoice_class' => array(
            'type' => array(
                'general' => app::get('sysmall')->_('一般纳税人'),
                'small' => app::get('sysmall')->_('小规模纳税人'),
            ),
            'default' => 'general',
            'required' => true,
            'comment' => app::get('sysmall')->_('公司纳税类别'),
        ),
        'registration_number' => array(
            'type' => 'string',
            'length' => 100,
            'required' => true,
            'comment' => app::get('sysmall')->_('纳税人识别号|纳税人登'),
        ),
        'contact_way'=> array(
            'type' => 'string',
            'length' => 20,
            'required' => true,
            'comment' => app::get('sysmall')->_('联系方式|联系电话'),
        ),
        'addr' => array(
            'type' => 'string',
            'length' => 100,
            'required' => true,
            'comment' => app::get('sysmall')->_('地址|公司地址'),
        ),
        'deposit_bank' => array(
            'type' => 'string',
            'length' => 25,
            'required' => true,
            'comment' => app::get('sysmall')->_('开户行名称|开户银行'),
        ),
        'card_number' => array(
            'type'=>'string',
            'length' => 50,
            'required' => true,
            'comment' => app::get('sysmall')->_('银行卡号|银行账户'),
        ),
        'kingdee_custom_code' => array(
            'type'=>'string',
            'length' => 100,
            'required' => true,
            'comment' => app::get('sysmall')->_('金蝶客户编码'),
        ),
        'kingdee_custom_id' => array(
            'type'=>'number',
            'default' => 0,
            'comment' => app::get('sysmall')->_('金蝶客户主键'),
        ),
    ),
    'primary' => 'id',
    'index' => array(
        'ind_shop_id' => ['columns' => ['shop_id']],
    ),
    'comment' => app::get('sysmall')->_('店铺发票信息表'),
);
