<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 *
 * 商家管理中心菜单定义
 */

return array(
    /*
    |--------------------------------------------------------------------------
    | 商家管理中心之首页
    |--------------------------------------------------------------------------
     */
    'index' => array(
        'label' => '首页',
        'display' => true,
        'action' => 'topshop_ctl_index@index',
        'icon' => 'fa fa-home',
        'menu' => array(
            array(
                'label'=>'首页',
                'display'=>false,
                'as'=>'topshop.index',
                'action'=>'topshop_ctl_index@index',
                'url'=>'/',
                'method'=>'get'
            ),
            array(
                'label'=>'浏览器检测',
                'display'=>false,
                'as'=>'topshop.browserTip',
                'action'=>'topshop_ctl_index@browserTip',
                'url'=>'browserTip.html',
                'method'=>'get'
            ),
        )
    ),
    'offline' => array(
        'label' => '优惠引擎',
        'display' => true,
        'action' => 'topshop_ctl_offline_trade@index',
        'icon' => 'glyphicon glyphicon-list-alt',
        'menu' => array(

            array('label'=>'线下收款订单','display'=>true,'as'=>'topshop.offline.trade.list','action'=>'topshop_ctl_offline_trade@index','url'=>'offline/trade.html','method'=>['get','post']),
            array('label'=>'线下收款ajax','display'=>false,'as'=>'topshop.offline.trade.list.ajax','action'=>'topshop_ctl_offline_trade@search','url'=>'offline/trade-ajax.html','method'=>['get','post']),
            array('label'=>'线下收款详情','display'=>false,'as'=>'topshop.offline.trade.detail','action'=>'topshop_ctl_offline_tradedetail@index','url'=>'offline/trade-detail.html','method'=>['get','post']),

            array('label'=>'宣传基金管理','display'=>true,'as'=>'topshop.ads.expense','action'=>'topshop_ctl_offline_ads@expense','url'=>'offline/ads_expense.html','method'=>['get','post']),
            array('label'=>'宣传基金管理详情','display'=>false,'as'=>'topshop.ads.expense.detail','action'=>'topshop_ctl_offline_ads@expenseDetail','url'=>'offline/ads-expense-detail.html','method'=>['get','post']),
            array('label'=>'编辑宣传基金使用记录','display'=>false,'as'=>'topshop.ads.edit','action'=>'topshop_ctl_offline_ads@editExpense','url'=>'offline/ads_edit.html','method'=>['get','post']),
            array('label'=>'保存宣传基金使用记录','display'=>false,'as'=>'topshop.ads.save','action'=>'topshop_ctl_offline_ads@saveExpense','url'=>'offline/ads_save.html','method'=>['get','post']),
            array('label'=>'删除宣传基金使用记录','display'=>false,'as'=>'topshop.ads.del','action'=>'topshop_ctl_offline_ads@delExpense','url'=>'offline/ads_del.html','method'=>['get','post']),
            array('label'=>'删除宣传基金使用记录操作','display'=>false,'as'=>'topshop.ads.del','action'=>'topshop_ctl_offline_ads@dodel','url'=>'offline/ads_del_do.html','method'=>['get','post']),
            array('label'=>'获取供应商线下店列表','display'=>false,'as'=>'topshop.ads.store.list','action'=>'topshop_ctl_offline_ads@getStoreListBySupplierId','url'=>'offline/ads-store-list.html','method'=>['get','post']),
            array('label'=>'获取供应商线下店广告费用额度','display'=>false,'as'=>'topshop.ads.store.ads.fee','action'=>'topshop_ctl_offline_ads@getAvailableAdsFee','url'=>'offline/ads-store-ads-fee.html','method'=>['get','post']),
        )
    ),
    /*
    |--------------------------------------------------------------------------
    | 商家管理中心之交易管理
    |--------------------------------------------------------------------------
     */
    'trade' => array(
        'label' => '交易',
        'display' => true,
        'action' => 'topshop_ctl_trade_list@index',
        'icon' => 'fa fa-credit-card',
        'menu' => array(
            array('label'=>'订单管理','display'=>true,'as'=>'topshop.trade.index','action'=>'topshop_ctl_trade_list@index','url'=>'list.html','method'=>'get'),
            array('label'=>'订单搜索','display'=>false,'as'=>'topshop.trade.search','action'=>'topshop_ctl_trade_list@search','url'=>'trade/search.html','method'=>['get','post']),
            array('label'=>'订单详情','display'=>false,'as'=>'topshop.trade.detail','action'=>'topshop_ctl_trade_detail@index','url'=>'detail.html','method'=>'get'),
            /*add_2018-2-23_by_gurundong_start*/
            array('label'=>'ajax订单详情','display'=>false,'as'=>'topshop.trade.ajaxGetAgentPrice0','action'=>'topshop_ctl_trade_detail@ajaxGetAgentPrice0','url'=>'ajaxGetAgentPrice0.html','method'=>'get'),
            array('label'=>'订单详情','display'=>false,'as'=>'topshop.trade.getAgentPrice0','action'=>'topshop_ctl_trade_detail@getAgentPrice0','url'=>'getAgentPrice0.html','method'=>'get'),
            /*add_2017-2-23_by_gurundong_end*/
            array('label'=>'订单物流','display'=>false,'as'=>'topshop.trade.detail.logi','action'=>'topshop_ctl_trade_detail@ajaxGetTrack','url'=>'detail.html','method'=>'post'),
            array('label'=>'添加订单备注','display'=>false,'as'=>'topshop.trade.detail.memo','action'=>'topshop_ctl_trade_detail@setTradeMemo','url'=>'setMemo.html','method'=>'post','middleware'=>['topshop_middleware_developerMode']),
            array('label'=>'修改订单价格页面','display'=>false,'as'=>'topshop.trade.modifyPrice','action'=>'topshop_ctl_trade_list@modifyPrice','url'=>'modifyprice.html','method'=>'get'),
            array('label'=>'保存修改订单价格','display'=>false,'as'=>'topshop.trade.modifyPrice.post','action'=>'topshop_ctl_trade_list@updatePrice','url'=>'updateprice.html','method'=>'post'),
            array('label'=>'订单发货','display'=>false,'as'=>'topshop.trade.delivery','action'=>'topshop_ctl_trade_flow@goDelivery','url'=>'delivery.html','method'=>'get'),
            // 王衍生-2018/07/03-start
            array('label'=>'代卖订单发货','display'=>false,'as'=>'topshop.trade.muumi.delivery','action'=>'topshop_ctl_trade_muumi_flow@goDelivery','url'=>'muumi/delivery.html','method'=>'get'),
            // 王衍生-2018/07/03-end
			/*add_2017-11-16_by_xinyufeng_start*/
			array('label'=>'虚拟商品退款开启操作','display'=>false,'as'=>'topshop.trade.virtual.refund','action'=>'topshop_ctl_trade_virtual@refundSwitch','url'=>'virtualRefundSwitch.html','method'=>'post'),
			/*add_2017-11-16_by_xinyufeng_end*/

            //订单货到付款时订单完成操作
            array('label'=>'ajax请求订单完成页面','display'=>false,'as'=>'topshop.trade.finish','action'=>'topshop_ctl_trade_list@ajaxFinishTrade','url'=>'ajaxfinish.html','method'=>'get'),
            array('label'=>'订单收钱并收货','display'=>false,'as'=>'topshop.trade.postfinish','action'=>'topshop_ctl_trade_list@finishTrade','url'=>'finish.html','method'=>'post'),

            //订单取消列表
            //ajax 请求订单信息以取消
            array('label'=>'ajax请求订单取消页面','display'=>false,'as'=>'topshop.trade.close','action'=>'topshop_ctl_trade_list@ajaxCloseTrade','url'=>'ajaxclose.html','method'=>'get','middleware'=>['topshop_middleware_developerMode']),
            array('label'=>'ajax请求订单拒收页面','display'=>false,'as'=>'topshop.trade.rejection','action'=>'topshop_ctl_trade_list@ajaxCloseRejection','url'=>'ajaxrejection.html','method'=>'get'),
            array('label'=>'ajax请求发送自提提货码页面','display'=>false,'as'=>'topshop.trade.ajaxSendDeliverySms','action'=>'topshop_ctl_trade_list@ajaxSendDeliverySms','url'=>'ajaxSendDeliverySms.html','method'=>'get'),
            array('label'=>'ajax请求验证自提提货码页面','display'=>false,'as'=>'topshop.trade.ajaxCheckDeliveryVcode','action'=>'topshop_ctl_trade_list@ajaxCheckDeliveryVcode','url'=>'ajaxCheckDeliveryVcode.html','method'=>'get'),
            array('label'=>'发送自提提货码页面','display'=>false,'as'=>'topshop.trade.sendDeliverySms','action'=>'topshop_ctl_trade_list@sendDeliverySms','url'=>'sendDeliverySms.html','method'=>'post'),
            array('label'=>'验证自提提货码页面','display'=>false,'as'=>'topshop.trade.checkDeliveryVcode','action'=>'topshop_ctl_trade_list@checkDeliveryVcode','url'=>'checkDeliveryVcode.html','method'=>'post'),
            array('label'=>'订单取消','display'=>false,'as'=>'topshop.trade.postclose','action'=>'topshop_ctl_trade_list@closeTrade','url'=>'close.html','method'=>'post'),

            array('label'=>'呼叫中心订单管理','display'=>true,'as'=>'topshop.trade.call.index','action'=>'topshop_ctl_trade_callList@index','url'=>'call_list.html','method'=>'get','shop_belong'=>'LM'),
            array('label'=>'呼叫中心订单搜索','display'=>false,'as'=>'topshop.trade.call.search','action'=>'topshop_ctl_trade_callList@search','url'=>'trade/call_search.html','method'=>['get','post'],'shop_belong'=>'LM'),
            array('label'=>'呼叫中心订单详情','display'=>false,'as'=>'topshop.trade.call.detail','action'=>'topshop_ctl_trade_callDetail@index','url'=>'call_detail.html','method'=>'get','shop_belong'=>'LM'),
            array('label'=>'呼叫中心添加订单备注','display'=>false,'as'=>'topshop.trade.call.detail.memo','action'=>'topshop_ctl_trade_callDetail@setTradeMemo','url'=>'call_setMemo.html','method'=>'post','middleware'=>['topshop_middleware_developerMode'],'shop_belong'=>'LM'),

            array('label'=>'订单取消管理','display'=>true,'as'=>'topshop.trade.cancel.index','action'=>'topshop_ctl_trade_cancel@index','url'=>'cancel/list.html','method'=>'get'),
            /*add_2017923_by_wudi_start*/
            array('label'=>'卡券核销管理','display'=>true,'as'=>'topsohp.voucher.index','action'=>'topshop_ctl_trade_voucher@index','url'=>'vwrite/list.html','method'=>'get'),
            array('label'=>'虚拟订单查找列表','display'=>false,'as'=>'topsohp.voucher.seach','action'=>'topshop_ctl_trade_voucher@search','url'=>'vouchersearch/list.html','method'=>['get','post']),
            array('label'=>'虚拟订单日期延长','display'=>false,'as'=>'topsohp.voucher.changetime','action'=>'topshop_ctl_trade_voucher@ajaxChangeTime','url'=>'voucherchangetime/list.html','method'=>'post'),
            /*add_2017923_by_wudi_end*/
            /*add_20180129_by_gurundong_start*/
            array('label'=>'线下支付核销管理','display'=>true,'as'=>'topsohp.agent.voucher.index','action'=>'topshop_ctl_trade_agentVoucher@index','url'=>'vwrite/agent_voucher/list.html','method'=>'get'),
            array('label'=>'线下店订单查找列表','display'=>false,'as'=>'topsohp.agent.voucher.seach','action'=>'topshop_ctl_trade_agentVoucher@search','url'=>'agentvouchersearch/list.html','method'=>['get','post']),
            array('label'=>'线下店订单查找列表','display'=>false,'as'=>'topsohp.agent.voucher.changetime','action'=>'topshop_ctl_trade_agentVoucher@ajaxChangeTime','url'=>'agentvoucherchangetime/list.html','method'=>'post'),
            /*add_20180129_by_gurundong_end*/
            array('label'=>'订单取消详情','display'=>false,'as'=>'topshop.trade.cancel.detail','action'=>'topshop_ctl_trade_cancel@detail','url'=>'cancel/detail.html','method'=>'get'),
            array('label'=>'订单取消搜索','display'=>false,'as'=>'topshop.trade.cancel.search','action'=>'topshop_ctl_trade_cancel@ajaxSearch','url'=>'trade/cancel/search.html','method'=>['get','post']),
            array('label'=>'审核取消订单','display'=>false,'as'=>'topshop.trade.cancel.check','action'=>'topshop_ctl_trade_cancel@shopCheckCancel','url'=>'trade/cancel/check.html','method'=>'post','middleware'=>['topshop_middleware_developerMode']),
            /*add_20170911_by_fanglongji_start*/
            array('label'=>'取消订单退款操作','display'=>false,'as'=>'topshop.trade.cancel.refund','action'=>'topshop_ctl_trade_cancel@cancel_refund','url'=>'trade/cancel/refund.html','method'=>['get','post']),
            /*add_20170911_by_fanglongji_end*/

            //店铺模板配置
            array('label'=>'快递模板配置','display'=>true,'as'=>'topshop.dlytmpl.index','action'=>'topshop_ctl_shop_dlytmpl@index','url'=>'wuliu/logis/templates.html','method'=>'get'),
            array('label'=>'快递模板配置编辑','display'=>false,'as'=>'topshop.dlytmpl.edit','action'=>'topshop_ctl_shop_dlytmpl@editView','url'=>'wuliu/logis/templates/create.html','method'=>'get'),
            array('label'=>'快递运费模板保存','display'=>false,'as'=>'topshop.dlytmpl.save','action'=>'topshop_ctl_shop_dlytmpl@savetmpl','url'=>'wuliu/logis/templates.html','method'=>'post'),
            array('label'=>'快递运费模板删除','display'=>false,'as'=>'topshop.dlytmpl.delete','action'=>'topshop_ctl_shop_dlytmpl@remove','url'=>'wuliu/logis/remove.html','method'=>'post'),
            array('label'=>'判断快递运费模板名称是否存在','display'=>false,'as'=>'topshop.dlytmpl.isExists','action'=>'topshop_ctl_shop_dlytmpl@isExists','url'=>'wuliu/logis/isExists.html','method'=>'post'),
            array('label'=>'物流公司','display'=>true,'as'=>'topshop.dlycorp.index','action'=>'topshop_ctl_shop_dlycorp@index','url'=>'wuliu/logis/dlycorp.html','method'=>'get'),
            array('label'=>'物流公司签约','display'=>false,'as'=>'topshop.dlycorp.save','action'=>'topshop_ctl_shop_dlycorp@signDlycorp','url'=>'wuliu/logis/savecorp.html','method'=>'post'),
            array('label'=>'物流公司解约','display'=>false,'as'=>'topshop.dlycorp.cancel','action'=>'topshop_ctl_shop_dlycorp@cancelDlycorp','url'=>'wuliu/logis/cancelcorp.html','method'=>'post'),

            array('label'=>'物流公司编辑计价','display'=>false,'as'=>'topshop.dlycorp.rule.edit','action'=>'topshop_ctl_shop_dlycorp@editRule','url'=>'wuliu/logis/editrule.html','method'=>'get'),
            array('label'=>'物流公司保存计价','display'=>false,'as'=>'topshop.dlycorp.rule.save','action'=>'topshop_ctl_shop_dlycorp@saveRule','url'=>'wuliu/logis/saverule.html','method'=>'post'),

            array('label'=>'京东物流授权页','display'=>false,'as'=>'jdwl.authorize','action'=>'topshop_ctl_shop_dlycorp@jdwlAuthorize','url'=>'jdwl/authorize.html','method'=>'get'),

            /*add_20170914_by_fanglongji_start*/
            //支付方式
            array('label'=>'上传证书','display'=>false,'as'=>'topshop.payment.upload.cert','action'=>'topshop_ctl_shop_payment@upload_cert','url'=>'payment/uploadCert.html','method'=>'post'),

            array('label'=>'支付方式','display'=>true,'as'=>'topshop.payment.index','action'=>'topshop_ctl_shop_payment@index','url'=>'payment/index.html','method'=>'get'),
            array('label'=>'支付方式','display'=>false,'as'=>'topshop.payment.savePayment','action'=>'topshop_ctl_shop_payment@savePayment','url'=>'payment/savePayment.html','method'=>'post'),
            /*add_20170914_by_fanglongji_end*/
            /*add_20170915_by_wudi_start*/
            array('label'=>'开发者工具','display'=>false,'as'=>'topshop.trade.settlement','action'=>'topshop_ctl_trade_dev@tools','url'=>'dev.html','method'=>'get'),
            /*add_20170915_by_wudi_end*/
            // 王衍生-2018/07/02-start
            array('label'=>'推送商品订单管理','display'=>true,'as'=>'topshop.trade.muumi.index','action'=>'topshop_ctl_trade_muumi_list@index','url'=>'muumi/list.html','method'=>'get'),
            array('label'=>'推送商品订单详情','display'=>false,'as'=>'topshop.trade.muumi.detail','action'=>'topshop_ctl_trade_muumi_detail@index','url'=>'muumi/detail.html','method'=>'get'),
            array('label'=>'推送商品订单物流','display'=>false,'as'=>'topshop.trade.muumi.detail.logi','action'=>'topshop_ctl_trade_muumi_detail@ajaxGetTrack','url'=>'muumi/detail.html','method'=>'post'),

            array('label'=>'修改推送商品物流信息','display'=>false,'as'=>'topshop.delivery.muumi.info.modify','action'=>'topshop_ctl_trade_muumi_detail@modifyLogisticInfo','url'=>'muumi/modifydelivery.html','method'=>'get'),

            // 王衍生-2018/07/02-end

			//本店铺上传商品订单取消管理
			/*add_2018/7/4_by_wanghaichao_start*/

            array('label'=>'推送商品订单取消管理','display'=>true,'as'=>'topshop.trade.cancel.push','action'=>'topshop_ctl_trade_cancel@pushItemCancelTrade','url'=>'cancel/pushlist.html','method'=>'get'),

            array('label'=>'订单取消搜索','display'=>false,'as'=>'topshop.trade.cancel.search','action'=>'topshop_ctl_trade_cancel@ajaxPushSearch','url'=>'trade/cancel/pushsearch.html','method'=>['get','post']),

			array('label'=>'推送商品订单取消详情','display'=>false,'as'=>'topshop.trade.cancel.detail.push','action'=>'topshop_ctl_trade_cancel@pushDetail','url'=>'cancel/pushdetail.html','method'=>'get'),
			/*add_2018/7/4_by_wanghaichao_end*/

        ),
    ),

    /*
    |--------------------------------------------------------------------------
    | 商家管理中心之商家商品管理
    |--------------------------------------------------------------------------
     */
    'item' => array(
        'label' => '商品',
        'display' => true,
        'action'=> 'topshop_ctl_item@itemList',
        'icon' => 'fa fa-cube',
        'menu' => array(
            array('label'=>'商品列表','display'=>true,'as'=>'topshop.item.list','action'=>'topshop_ctl_item@itemList','url'=>'item/itemList.html','method'=>'get'),
            array('label'=>'商品搜索','display'=>false,'as'=>'topshop.item.search','action'=>'topshop_ctl_item@searchItem','url'=>'item/search.html','method'=>['get','post']),
            array('label'=>'发布商品','display'=>true,'as'=>'topshop.item.add','action'=>'topshop_ctl_item@add','url'=>'item/add.html','method'=>'get'),
            array('label'=>'ajax获取运费模板','display'=>false,'as'=>'topshop.item.ajax.tmpl','action'=>'topshop_ctl_item@ajaxTmpl','url'=>'item/ajaxtmpl.html','method'=>'get'),
            array('label'=>'供应商搜索','display'=>false,'as'=>'topshop.item.supplier.search','action'=>'topshop_ctl_item@search_supplier','url'=>'item/supplier-search.html','method'=>'post'),
			/*add_2018/10/10_by_wanghaichao_start*/
            array('label'=>'供货商模态框选择','display'=>false,'as'=>'topshop.item.supplier.mode','action'=>'topshop_ctl_item@modeSupplier','url'=>'item/supplier-mode.html','method'=>['post','get']),
			/*add_2018/10/10_by_wanghaichao_end*/

            array('label'=>'线下店列表','display'=>false,'as'=>'topshop.item.agent.list','action'=>'topshop_ctl_item@agent_shop_list','url'=>'item/supplier-agent-shop.html','method'=>'post'),
            array('label'=>'编辑商品','display'=>false,'as'=>'topshop.item.edit','action'=>'topshop_ctl_item@edit','url'=>'item/edit.html','method'=>'get'),
            array('label'=>'下载商品地址二维码','display'=>false,'as'=>'topshop.item.qrconde.down','action'=>'topshop_ctl_item@qrDown','url'=>'item/qrDown.html','method'=>'get'),
            /*add_20171101_by_fanglongji_start*/
            array('label'=>'更改商品销量','display'=>false,'as'=>'topshop.item.update.quantity','action'=>'topshop_ctl_item@updateItemPaidQuantity','url'=>'item/updateItemPaidQuantity.html','method'=>'post'),
            array('label'=>'修改商品销量','display'=>false,'as'=>'topshop.trade.modifyitempaidquqntity','action'=>'topshop_ctl_item@modifyItemPaidQuqntity','url'=>'modifyitempaidquqntity.html','method'=>'get'),
            array('label'=>'修改物流信息','display'=>false,'as'=>'topshop.delivery.info.modify','action'=>'topshop_ctl_trade_detail@modifyLogisticInfo','url'=>'modifydelivery.html','method'=>'get'),
            /*add_20171101_by_fanglongji_end*/
            array('label'=>'库存报警','display'=>true,'as'=>'topshop.storepolice.add','action'=>'topshop_ctl_item@storePolice','url'=>'item/storepolice.html','method'=>'get'),
            array('label'=>'保存商品库存报警','display'=>false,'as'=>'topshop.storepolice.save','action'=>'topshop_ctl_item@saveStorePolice','url'=>'item/savestorepolice.html','method'=>'post'),
            array('label'=>'设置商品状态','display'=>false,'as'=>'topshop.item.setStatus','action'=>'topshop_ctl_item@setItemStatus','url'=>'item/setItemStatus.html','method'=>'post'),
            array('label'=>'删除商品','display'=>false,'as'=>'topshop.item.delete','action'=>'topshop_ctl_item@deleteItem','url'=>'item/deleteItem.html','method'=>'post'),
            array('label'=>'创建商品','display'=>false,'as'=>'topshop.item.create','action'=>'topshop_ctl_item@storeItem','url'=>'item/storeItem.html','method'=>'post'),
            // 保存代售商品 @auth:xinyufeng
            array('label'=>'保存代售商品','display'=>false,'as'=>'topshop.item.create.agent','action'=>'topshop_ctl_item@storeItemAgent','url'=>'item/storeItemAgent.html','method'=>'post'),

            array('label'=>'店铺分类','display'=>true,'as'=>'topshop.item.cat.index','action'=>'topshop_ctl_item_cat@index','url'=>'categories.html','method'=>'get'),
            array('label'=>'店铺分类保存','display'=>false,'as'=>'topshop.item.cat.store','action'=>'topshop_ctl_item_cat@storeCat','url'=>'categories.html','method'=>'post'),
            array('label'=>'店铺分类删除','display'=>false,'as'=>'topshop.item.cat.delete','action'=>'topshop_ctl_item_cat@removeCat','url'=>'categories/remove.html','method'=>'post'),
            array('label'=>'获取店铺支持品牌','display'=>false,'as'=>'topshop.item.brand','action'=>'topshop_ctl_item@ajaxGetBrand','url'=>'categories/getbrand.html','method'=>'post'),
            array('label'=>'获取店铺的运费模板','display'=>false,'as'=>'topshop.item.dlytmpls','action'=>'topshop_ctl_item@ajaxGetDlytmpls','url'=>'getdlytmpls.html','method'=>'get'),
            array('label'=>'更新商品的运费模板','display'=>false,'as'=>'topshop.item.update.dlytmpls','action'=>'topshop_ctl_item@updateItemDlytmpl','url'=>'updatedlytmpls.html','method'=>'post'),
            array('label'=>'商品导出','display'=>false,'as'=>'topshop.item.export','action'=>'topshop_ctl_item_importexport@export','url'=>'exportitem.html','method'=>'get'),
            array('label'=>'商品导入','display'=>false,'as'=>'topshop.item.import','action'=>'topshop_ctl_item_importexport@import','url'=>'importitem.html','method'=>'post'),
            array('label'=>'导出商品上传模板','display'=>false,'as'=>'topshop.item.export.tmpl','action'=>'topshop_ctl_item_importexport@downLoadImportTmpl','url'=>'exportitemtmpl.html','method'=>'get'),
            array('label'=>'商品上传模板','display'=>false, 'as'=>'topshop.item.import.tmpl', 'action'=>'topshop_ctl_item_importexport@importView','url'=>'importview.html','method'=>'get'),

            //图片管理
            array('label'=>'图片管理','display'=>true,'as'=>'topshop.image.index','action'=>'topshop_ctl_shop_image@index','url'=>'image.html','method'=>'get'),
            array('label'=>'根据条件搜索图片,tab切换','as'=>'topshop.image.search','display'=>false,'action'=>'topshop_ctl_shop_image@search','url'=>'image/search.html','method'=>'post'),
            array('label'=>'删除图片','display'=>false,'as'=>'topshop.image.delete','action'=>'topshop_ctl_shop_image@delImgLink','url'=>'image/delimglink.html','method'=>'post'),
            array('label'=>'修改图片名称','display'=>false,'as'=>'topshop.image.upname','action'=>'topshop_ctl_shop_image@upImgName','url'=>'image/upimgname.html','method'=>'post'),
            array('label'=>'商家使用图片加载modal','display'=>false,'as'=>'topshop.image.loadModal','action'=>'topshop_ctl_shop_image@loadImageModal','url'=>'image/loadimagemodal.html','method'=>'get'),
            array('label'=>'加载图片移动文件夹弹出框','display'=>false,'as'=>'topshop.image.move.cat.loadModal','action'=>'topshop_ctl_shop_image@loadImageMoveCatModal','url'=>'image/loadImageMoveCatModal.html','method'=>'post'),
            array('label'=>'图片移动文件夹','display'=>false,'as'=>'topshop.image.move.cat','action'=>'topshop_ctl_shop_image@moveImageCat','url'=>'image/loadImageMoveCat.html','method'=>'post'),

            array('label'=>'加载文件夹管理弹出框','display'=>false,'as'=>'topshop.image.cat.loadImgCatModal','action'=>'topshop_ctl_shop_image@loadImgCatModal','url'=>'image/loadImgCatModal.html','method'=>'post'),
            array('label'=>'创建图片文件夹','display'=>false,'as'=>'topshop.image.add.cat','action'=>'topshop_ctl_shop_image@addImgCat','url'=>'image/loadImageCreateCat.html','method'=>'post'),
            array('label'=>'删除图片文件夹','display'=>false,'as'=>'topshop.image.del.cat','action'=>'topshop_ctl_shop_image@delImgCat','url'=>'image/loadImageDelCat.html','method'=>'post'),
            array('label'=>'编辑图片文件夹','display'=>false,'as'=>'topshop.image.update.cat','action'=>'topshop_ctl_shop_image@editImgCat','url'=>'image/loadImageEditCat.html','method'=>'post'),

            /*add_start_gurundong_20171026*/
            //直播热售
            array('label'=>'直播热售','display'=>true,'as'=>'topshop.livehot.list','action'=>'topshop_ctl_shop_livehot@list_livehot','url'=>'livehot/list_livehot.html','method'=>'get'),
            array('label'=>'直播热售添加','display'=>false,'as'=>'topshop.livehot.add','action'=>'topshop_ctl_shop_livehot@add','url'=>'livehot/add_livehot.html','method'=>'get'),
            array('label'=>'直播热售提交','display'=>false,'as'=>'topshop.livehot.add_post','action'=>'topshop_ctl_shop_livehot@add_post','url'=>'livehot/add_post_livehot.html','method'=>'post'),
            array('label'=>'直播热售删除','display'=>false,'as'=>'topshop.livehot.delete','action'=>'topshop_ctl_shop_livehot@delete','url'=>'livehot/delete.html','method'=>'post'),
            /*add_end_gurundong_20171026*/

            // 视频管理
            array('label'=>'商家使用视频加载modal','display'=>false,'as'=>'topshop.video.loadModal','action'=>'topshop_ctl_shop_video@loadVideoModal','url'=>'video/loadvideomodal.html','method'=>'get'),
            array('label'=>'根据条件搜索视频,tab切换','as'=>'topshop.video.search','display'=>false,'action'=>'topshop_ctl_shop_video@search','url'=>'video/search.html','method'=>'post'),

            array('label'=>'同步金蝶库存','as'=>'topshop.item.sync.kingdee.inventory','display'=>false,'action'=>'topshop_ctl_item@ajaxSyncKingdeeInventory','url'=>'ajaxSyncKingdeeInventory.html','method'=>'post'),
        ),
    ),

    /*
    |--------------------------------------------------------------------------
    | 商家管理中心之营销管理
    |--------------------------------------------------------------------------
     */
    'promotion' => array(
        'label' => '营销',
        'display' => true,
        'action' => 'topshop_ctl_promotion_fullminus@list_fullminus',
        'icon' => 'fa fa-tags',
        'menu' => array(
            //满减促销
            array('label'=>'满减管理','display'=>true,'as'=>'topshop.fullminus.list','action'=>'topshop_ctl_promotion_fullminus@list_fullminus','url'=>'list_fullminus.html','method'=>'get'),
            array('label'=>'添加/编辑满减','display'=>false,'as'=>'topshop.fullminus.edit','action'=>'topshop_ctl_promotion_fullminus@edit_fullminus','url'=>'edit_fullminus.html','method'=>'get'),
            array('label' =>'查看满减','display'=>false,'as'=>'topshop.fullminus.show','action'=>'topshop_ctl_promotion_fullminus@show_fullminus','url'=>'show_fullminus.html','method'=>'get'),
            array('label'=>'保存满减','display'=>false,'as'=>'topshop.fullminus.save','action'=>'topshop_ctl_promotion_fullminus@save_fullminus','url'=>'save_fullminus.html','method'=>'post'),
            array('label'=>'删除满减','display'=>false,'as'=>'topshop.fullminus.delete','action'=>'topshop_ctl_promotion_fullminus@delete_fullminus','url'=>'delete_fullminus.html','method'=>'post'),
            array('label'=>'取消满减活动','display'=>false,'as'=>'topshop.fullminus.cancel','action'=>'topshop_ctl_promotion_fullminus@cancel_fullminus','url'=>'cancel_fullminus.html','method'=>'post'),
            array('label'=>'提交满减促销审核','display'=>false,'as'=>'topshop.fullminus.submitApprove','action'=>'topshop_ctl_promotion_fullminus@submit_approve','url'=>'submit_fullminus.html','method'=>'post'),


            //满折促销
            array('label'=>'满折管理','display'=>true,'as'=>'topshop.fulldiscount.list','action'=>'topshop_ctl_promotion_fulldiscount@list_fulldiscount','url'=>'list_fulldiscount.html','method'=>'get'),
            array('label'=>'添加/编辑满折','display'=>false,'as'=>'topshop.fulldiscount.edit','action'=>'topshop_ctl_promotion_fulldiscount@edit_fulldiscount','url'=>'edit_fulldiscount.html','method'=>'get'),
            array('label'=>'查看满折','display'=>false,'as'=>'topshop.fulldiscount.show','action'=>'topshop_ctl_promotion_fulldiscount@show_fulldiscount','url'=>'show_fulldiscount.html','method'=>'get'),
            array('label'=>'保存满折','display'=>false,'as'=>'topshop.fulldiscount.save','action'=>'topshop_ctl_promotion_fulldiscount@save_fulldiscount','url'=>'save_fulldiscount.html','method'=>'post'),
            array('label'=>'删除满折','display'=>false,'as'=>'topshop.fulldiscount.delete','action'=>'topshop_ctl_promotion_fulldiscount@delete_fulldiscount','url'=>'delete_fulldiscount.html','method'=>'post'),
            array('label'=>'取消满折活动','display'=>false,'as'=>'topshop.fulldiscount.cancel','action'=>'topshop_ctl_promotion_fulldiscount@cancel_fulldiscount','url'=>'cancel_fulldiscount.html','method'=>'post'),
            array('label'=>'提交满折促销审核','display'=>false,'as'=>'topshop.fulldiscount.submitApprove','action'=>'topshop_ctl_promotion_fulldiscount@submit_approve','url'=>'submit_fulldiscount.html','method'=>'post'),

            // 优惠券促销
            array('label'=>'优惠券管理','display'=>true,'as'=>'topshop.coupon.list','action'=>'topshop_ctl_promotion_coupon@list_coupon','url'=>'list_coupon.html','method'=>'get'),
            array('label'=>'添加/编辑优惠券','display'=>false,'as'=>'topshop.coupon.edit','action'=>'topshop_ctl_promotion_coupon@edit_coupon','url'=>'edit_coupon.html','method'=>'get'),
            array('label'=>'发放优惠券','display'=>false,'as'=>'topshop.coupon.grant','action'=>'topshop_ctl_promotion_coupon@grant_coupon','url'=>'grant_coupon.html','method'=>'get'),
            array('label'=>'发放优惠券到指定用户','display'=>false,'as'=>'topshop.coupon.grant.code','action'=>'topshop_ctl_promotion_coupon@grant_coupon_code','url'=>'grant_coupon_code.html','method'=>'post'),
            array('label'=>'查看优惠券','display'=>false,'as'=>'topshop.coupon.show','action'=>'topshop_ctl_promotion_coupon@show_coupon','url'=>'show_coupon.html','method'=>'get'),
            array('label'=>'保存优惠券','display'=>false,'as'=>'topshop.coupon.save','action'=>'topshop_ctl_promotion_coupon@save_coupon','url'=>'save_coupon.html','method'=>'post'),
            array('label'=>'删除优惠券','display'=>false,'as'=>'topshop.coupon.delete','action'=>'topshop_ctl_promotion_coupon@delete_coupon','url'=>'delete_coupon.html','method'=>'post'),
            array('label'=>'取消优惠券','display'=>false,'as'=>'topshop.coupon.cancel','action'=>'topshop_ctl_promotion_coupon@cancel_coupon','url'=>'cancel_coupon.html','method'=>'post'),
            array('label'=>'提交优惠券审核','display'=>false,'as'=>'topshop.coupon.submitApprove','action'=>'topshop_ctl_promotion_coupon@submit_approve','url'=>'submit_coupon.html','method'=>'post'),
            // X件Y折促销
            array('label'=>'X件Y折管理','display'=>true,'as'=>'topshop.xydiscount.list','action'=>'topshop_ctl_promotion_xydiscount@list_xydiscount','url'=>'list_xydiscount.html','method'=>'get'),
            array('label'=>'添加/编辑X件Y折','display'=>false,'as'=>'topshop.xydiscount.edit','action'=>'topshop_ctl_promotion_xydiscount@edit_xydiscount','url'=>'edit_xydiscount.html','method'=>'get'),
            array('label'=>'查看X件Y折','display'=>false,'as'=>'topshop.xydiscount.show','action'=>'topshop_ctl_promotion_xydiscount@show_xydiscount','url'=>'show_xydiscount.html','method'=>'get'),
            array('label'=>'保存X件Y折','display'=>false,'as'=>'topshop.xydiscount.save','action'=>'topshop_ctl_promotion_xydiscount@save_xydiscount','url'=>'save_xydiscount.html','method'=>'post'),
            array('label'=>'删除X件Y折','display'=>false,'as'=>'topshop.xydiscount.delete','action'=>'topshop_ctl_promotion_xydiscount@delete_xydiscount','url'=>'delete_xydiscount.html','method'=>'post'),
            array('label'=>'取消X件Y折','display'=>false,'as'=>'topshop.xydiscount.cancel','action'=>'topshop_ctl_promotion_xydiscount@cancel_xydiscount','url'=>'cancel_xydiscount.html','method'=>'post'),
            array('label'=>'提交X件Y折促销审核','display'=>false,'as'=>'topshop.xydiscount.submitApprove','action'=>'topshop_ctl_promotion_xydiscount@submit_approve','url'=>'submit_xydiscount.html','method'=>'post'),
            // 王衍生-2018/09/12-start
            // 会员日X件Y折促销
            array('label'=>'会员日管理','display'=>true,'as'=>'topshop.festivaldiscount.list','action'=>'topshop_ctl_promotion_festivaldiscount@list_festivaldiscount','url'=>'list_festivaldiscount.html','method'=>'get'),
            array('label'=>'添加/编辑会员日','display'=>false,'as'=>'topshop.festivaldiscount.edit','action'=>'topshop_ctl_promotion_festivaldiscount@edit_festivaldiscount','url'=>'edit_festivaldiscount.html','method'=>'get'),
            array('label'=>'查看会员日','display'=>false,'as'=>'topshop.festivaldiscount.show','action'=>'topshop_ctl_promotion_festivaldiscount@show_festivaldiscount','url'=>'show_festivaldiscount.html','method'=>'get'),
            array('label'=>'保存会员日','display'=>false,'as'=>'topshop.festivaldiscount.save','action'=>'topshop_ctl_promotion_festivaldiscount@save_festivaldiscount','url'=>'save_festivaldiscount.html','method'=>'post'),
            array('label'=>'删除会员日','display'=>false,'as'=>'topshop.festivaldiscount.delete','action'=>'topshop_ctl_promotion_festivaldiscount@delete_festivaldiscount','url'=>'delete_festivaldiscount.html','method'=>'post'),
            array('label'=>'取消会员日','display'=>false,'as'=>'topshop.festivaldiscount.cancel','action'=>'topshop_ctl_promotion_festivaldiscount@cancel_festivaldiscount','url'=>'cancel_festivaldiscount.html','method'=>'post'),
            array('label'=>'提交会员日促销审核','display'=>false,'as'=>'topshop.festivaldiscount.submitApprove','action'=>'topshop_ctl_promotion_festivaldiscount@submit_approve','url'=>'submit_festivaldiscount.html','method'=>'post'),
            // 王衍生-2018/09/12-end
            // 活动报名
            array('label'=>'活动报名','display'=>true,'as'=>'topshop.activity.registeredlist','action'=>'topshop_ctl_promotion_activity@registered_activity','url'=>'registered.html','method'=>'get'),
            array('label'=>'活动列表','display'=>false,'as'=>'topshop.activity.activitylist','action'=>'topshop_ctl_promotion_activity@activity_list','url'=>'activitylist.html','method'=>'get'),
            array('label'=>'历史报名','display'=>false,'as'=>'topshop.activity.historyregisteredlist','action'=>'topshop_ctl_promotion_activity@historyregistered_activity','url'=>'historyregistered.html','method'=>'get'),
            array('label'=>'历史报名详情','display'=>false,'as'=>'topshop.activity.historyregistereddetial','action'=>'topshop_ctl_promotion_activity@historyregistered_detail','url'=>'historyregistered_detail.html','method'=>'get'),
            array('label'=>'添加/编辑活动申请','display'=>false,'as'=>'topshop.activity.edit','action'=>'topshop_ctl_promotion_activity@canregistered_apply','url'=>'edit_activity.html','method'=>'get'),
            array('label'=>'已报名不活动详情','display'=>false,'as'=>'topshop.activity.canregistered.detail','action'=>'topshop_ctl_promotion_activity@canregistered_detail','url'=>'canregistered_detail.html','method'=>'get'),
            array('label'=>'保存申请活动','display'=>false,'as'=>'topshop.activity.save','action'=>'topshop_ctl_promotion_activity@canregistered_apply_save','url'=>'save_activity.html','method'=>'post'),
            array('label'=>'活动列表页活动详情','display'=>false,'as'=>'topshop.activity.noregistered.detail','action'=>'topshop_ctl_promotion_activity@noregistered_detail','url'=>'noregistered_detail.html','method'=>'get'),
            array('label'=>'查看报名商品sku','display'=>false,'as'=>'topshop.activity.item.sku','action'=>'topshop_ctl_promotion_activity@showSkuByitemId','url'=>'register_item_sku.html','method'=>'get'),


            //组合促销
            array('label'=>'组合促销管理','display'=>true,'as'=>'topshop.package.list','action'=>'topshop_ctl_promotion_package@list_package','url'=>'list_package.html','method'=>'get'),
            array('label'=>'添加/编辑组合促销','display'=>false,'as'=>'topshop.package.edit','action'=>'topshop_ctl_promotion_package@edit_package','url'=>'edit_package.html','method'=>'get'),
            array('label'=>'查看组合促销','display'=>false,'as'=>'topshop.package.show','action'=>'topshop_ctl_promotion_package@show_package','url'=>'show_package.html','method'=>'get'),
            array('label'=>'保存组合促销','display'=>false,'as'=>'topshop.package.save','action'=>'topshop_ctl_promotion_package@save_package','url'=>'save_package.html','method'=>'post'),
            array('label'=>'删除组合促销','display'=>false,'as'=>'topshop.package.delete','action'=>'topshop_ctl_promotion_package@delete_package','url'=>'delete_package.html','method'=>'post'),
            array('label'=>'取消组合促销','display'=>false,'as'=>'topshop.package.cancel','action'=>'topshop_ctl_promotion_package@cancel_package','url'=>'cancel_package.html','method'=>'post'),
            array('label'=>'提交组合促销审核','display'=>false,'as'=>'topshop.package.submitApprove','action'=>'topshop_ctl_promotion_package@submit_approve','url'=>'submit_package.html','method'=>'post'),
            //赠品促销
            array('label'=>'赠品促销管理','display'=>true,'as'=>'topshop.gift.list','action'=>'topshop_ctl_promotion_gift@list_gift','url'=>'list_gift.html','method'=>'get'),
            array('label'=>'添加/编辑赠品促销','display'=>false,'as'=>'topshop.gift.edit','action'=>'topshop_ctl_promotion_gift@edit_gift','url'=>'edit_gift.html','method'=>'get'),
            array('label'=>'查看赠品促销','display'=>false,'as'=>'topshop.gift.show','action'=>'topshop_ctl_promotion_gift@show_gift','url'=>'show_gift.html','method'=>'get'),
            array('label'=>'保存赠品促销','display'=>false,'as'=>'topshop.gift.save','action'=>'topshop_ctl_promotion_gift@save_gift','url'=>'save_gift.html','method'=>'post'),
            array('label'=>'删除赠品促销','display'=>false,'as'=>'topshop.gift.delete','action'=>'topshop_ctl_promotion_gift@delete_gift','url'=>'delete_gift.html','method'=>'post'),
            array('label'=>'取消赠品促销','display'=>false,'as'=>'topshop.gift.cancel','action'=>'topshop_ctl_promotion_gift@cancel_gift','url'=>'cancel_gift.html','method'=>'post'),
            array('label'=>'提交赠品促销审核','display'=>false,'as'=>'topshop.gift.submitApprove','action'=>'topshop_ctl_promotion_gift@submit_approve','url'=>'submit_gift.html','method'=>'post'),

            array('label'=>'购物券管理','display'=>true,'as'=>'topshop.voucher.list','action'=>'topshop_ctl_promotion_voucher@index','url'=>'voucher/list.html','method'=>'get'),
            array('label'=>'购物券详情','display'=>false,'as'=>'topshop.voucher.detail','action'=>'topshop_ctl_promotion_voucher@detail','url'=>'voucher/detail.html','method'=>'get'),
            array('label'=>'购物券报名','display'=>false,'as'=>'topshop.voucher.apply','action'=>'topshop_ctl_promotion_voucher@apply','url'=>'voucher/apply.html','method'=>'post'),


            // 转盘抽奖 王衍生
            array('label'=>'转盘抽奖','display'=>true,'as'=>'topshop.lottery.list','action'=>'topshop_ctl_promotion_lottery@list_lottery','url'=>'list_lottery.html','method'=>'get'),
            array('label'=>'添加/编辑转盘抽奖','display'=>false,'as'=>'topshop.lottery.edit','action'=>'topshop_ctl_promotion_lottery@edit_lottery','url'=>'edit_lottery.html','method'=>'get'),
            array('label'=>'查看转盘抽奖','display'=>false,'as'=>'topshop.lottery.show','action'=>'topshop_ctl_promotion_lottery@show_lottery','url'=>'show_lottery.html','method'=>'get'),
            array('label'=>'保存转盘抽奖','display'=>false,'as'=>'topshop.lottery.save','action'=>'topshop_ctl_promotion_lottery@save_lottery','url'=>'save_lottery.html','method'=>'post'),
            array('label'=>'删除转盘抽奖','display'=>false,'as'=>'topshop.lottery.delete','action'=>'topshop_ctl_promotion_lottery@delete_lottery','url'=>'delete_lottery.html','method'=>'post'),

            array('label'=>'转盘抽奖记录','display'=>false,'action'=>'topshop_ctl_promotion_lottery@log_detail_list','url'=>'lottery_log.html','method'=>'get'),
            array('label'=>'更改转盘抽奖状态','display'=>false,'action'=>'topshop_ctl_promotion_lottery@updateStatus','url'=>'lottery_update_status.html','method'=>'post'),

            array('label'=>'取消转盘抽奖','display'=>false,'as'=>'topshop.lottery.cancel','action'=>'topshop_ctl_promotion_lottery@cancel_lottery','url'=>'cancel_lottery.html','method'=>'post'),

            //礼金管理
            array('label'=>'礼金管理','display'=>true,'as'=>'topshop.member.cash.config','action'=>'topshop_ctl_cash_manage@config','url'=>'cash/config.html','method'=>'get', 'middleware'=>['topshop_middleware_permissionLanmei']),
            array('label'=>'礼金配置保存','display'=>false,'as'=>'topshop.member.cash.config.save','action'=>'topshop_ctl_cash_manage@save','url'=>'cash/save.html','method'=>'post', 'middleware'=>['topshop_middleware_permissionLanmei']),
            array('label'=>'礼金更改记录','display'=>false,'as'=>'topshop.member.cash.logs','action'=>'topshop_ctl_cash_manage@cashChangeLogs','url'=>'cash/change_log.html','method'=>'get', 'middleware'=>['topshop_middleware_permissionLanmei']),
            array('label'=>'礼金更改记录','display'=>false,'as'=>'topshop.member.ajax.cash.logs','action'=>'topshop_ctl_cash_manage@ajaxCashChangeLogs','url'=>'cash/ajax_change_log.html','method'=>'post', 'middleware'=>['topshop_middleware_permissionLanmei']),

            array('label'=>'礼金卡管理','display'=>false,'as'=>'topshop.member.cash.card','action'=>'topshop_ctl_cash_card@card','url'=>'cash/card.html','method'=>['get', 'post'], 'middleware'=>['topshop_middleware_permissionLanmei']),
            array('label'=>'礼金卡生成','display'=>false,'as'=>'topshop.member.cash.card.create','action'=>'topshop_ctl_cash_card@createCard','url'=>'cash/card_create.html','method'=>'get', 'middleware'=>['topshop_middleware_permissionLanmei']),
            array('label'=>'礼金卡保存','display'=>false,'as'=>'topshop.member.cash.card.save','action'=>'topshop_ctl_cash_card@cardSave','url'=>'cash/card_save.html','method'=>'post', 'middleware'=>['topshop_middleware_permissionLanmei']),
            array('label'=>'礼金卡导出','display'=>false,'as'=>'topshop.member.cash.card.export.view','action'=>'topshop_ctl_cash_card@exportView','url'=>'cash/card_export_view.html','method'=>'get', 'middleware'=>['topshop_middleware_permissionLanmei']),
            array('label'=>'礼金卡导出','display'=>false,'as'=>'topshop.member.cash.card.export','action'=>'topshop_ctl_cash_card@export','url'=>'cash/card_export.html','method'=>'post', 'middleware'=>['topshop_middleware_permissionLanmei']),

            // 兑换码管理
            array('label'=>'兑换方案管理','display'=>true,'as'=>'topshop.exchange.code.index','action'=>'topshop_ctl_promotion_exchangecode@project_list','url'=>'exchangecode/project/list.html','method'=>'get', 'shop_belong'=>'LM', 'middleware'=>['topshop_middleware_permissionLanmei']),
            array('label'=>'查看兑换方案','display'=>false,'as'=>'topshop.exchange.code.show','action'=>'topshop_ctl_promotion_exchangecode@project_show','url'=>'exchangecode/project/show.html','method'=>'get', 'middleware'=>['topshop_middleware_permissionLanmei']),
            array('label'=>'编辑兑换方案','display'=>false,'as'=>'topshop.exchange.code.edit','action'=>'topshop_ctl_promotion_exchangecode@project_edit','url'=>'exchangecode/project/edit.html','method'=>'get', 'middleware'=>['topshop_middleware_permissionLanmei']),
            array('label'=>'保存兑换方案','display'=>false,'as'=>'topshop.exchange.code.save','action'=>'topshop_ctl_promotion_exchangecode@project_save','url'=>'exchangecode/project/save.html','method'=>'post', 'middleware'=>['topshop_middleware_permissionLanmei']),
            array('label'=>'导出兑换卡号页','display'=>false,'as'=>'topshop.exchange.code.export.view','action'=>'topshop_ctl_promotion_exchangecode@exportView','url'=>'exchangecode/project/export/view.html','method'=>'get', 'middleware'=>['topshop_middleware_permissionLanmei']),
            array('label'=>'导出兑换卡号','display'=>false,'as'=>'topshop.exchange.code.export.','action'=>'topshop_ctl_promotion_exchangecode@export','url'=>'exchangecode/project/export.html','method'=>'post', 'middleware'=>['topshop_middleware_permissionLanmei']),
            //会员等级管理
            array('label'=>'等级管理','display'=>true,'as'=>'topshop.member.account.grade','action'=>'topshop_ctl_account_grade@manage','url'=>'account/grade.html','method'=>'get'),
            array('label'=>'等级编辑','display'=>false,'as'=>'topshop.member.account.grade.edit','action'=>'topshop_ctl_account_grade@editGrade','url'=>'account/grade_edit.html','method'=>'get'),
            array('label'=>'等级保存','display'=>false,'as'=>'topshop.member.account.grade.save','action'=>'topshop_ctl_account_grade@saveGrade','url'=>'account/grade_save.html','method'=>'post'),
            array('label'=>'等级删除','display'=>false,'as'=>'topshop.member.account.grade.del','action'=>'topshop_ctl_account_grade@delGrade','url'=>'account/grade_del.html','method'=>'post'),
            //会员权益管理
            array('label'=>'会员权益管理','display'=>true,'as'=>'topshop.member.account.interests','action'=>'topshop_ctl_account_interests@manage','url'=>'account/interests.html','method'=>'get'),
            array('label'=>'会员权益保存','display'=>false,'as'=>'topshop.member.account.interests.save','action'=>'topshop_ctl_account_interests@save','url'=>'account/interests_save.html','method'=>'post'),
            //广告管理
            array('label'=>'广告管理','display'=>true,'as'=>'topshop.member.advert','action'=>'topshop_ctl_advert@manage','url'=>'account/advert.html','method'=>'get'),
            array('label'=>'广告设置保存','display'=>false,'as'=>'topshop.member.advert.save','action'=>'topshop_ctl_advert@save','url'=>'account/advert_save.html','method'=>'post'),
        )
    ),

    /*
    |--------------------------------------------------------------------------
    | 商家管理中心之店铺管理
    |--------------------------------------------------------------------------
     */
    'shop' => array(
        'label' => '店铺',
        'display' => true,
        'action' => 'topshop_ctl_shop_setting@index',
        'icon' => 'fa fa-building',
        'menu' => array(
            //店铺配置
            array('label'=>'店铺基本配置','display'=>true,'as'=>'topshop.shopsetting.index','action'=>'topshop_ctl_shop_setting@index','url'=>'setting.html','method'=>'get'),
			/*add_2017/9/25_by_wanghaichao_start*/
            array('label'=>'店铺其他配置','display'=>true,'as'=>'topshop.shopsetting.ext_setting','action'=>'topshop_ctl_shop_setting@ext_setting','url'=>'ext_setting.html','method'=>'get'),
            array('label'=>'保存店铺其他配置','display'=>false,'as'=>'topshop.shopsetting.save_ext_setting','action'=>'topshop_ctl_shop_setting@save_ext_setting','url'=>'save_ext_setting.html','method'=>'post'),
			/*add_2017/9/25_by_wanghaichao_end*/

			/*add_2018/8/24_by_wanghaichao_start*/
			//会员签到积分
            array('label'=>'积分设置','display'=>true,'as'=>'topshop.member.getpoint','action'=>'topshop_ctl_point_sign@index','url'=>'point/sign.html','method'=>'get'),
            array('label'=>'保存积分规则','display'=>false,'as'=>'topshop.member.point.save','action'=>'topshop_ctl_point_sign@save','url'=>'point/save.html','method'=>'post'),
			/*add_2018/8/24_by_wanghaichao_end*/

            array('label'=>'店铺配置保存','display'=>false,'as'=>'topshop.shopsetting.save','action'=>'topshop_ctl_shop_setting@saveSetting','url'=>'setting/save.html','method'=>'post'),

            array('label'=>'二级域名','display'=>true,'as'=>'topshop.subdomain.index','action'=>'topshop_ctl_shop_subdomain@index','url'=>'subdomain.html','method'=>'get'),
            array('label'=>'二级域名保存','display'=>false,'as'=>'topshop.subdomain.save','action'=>'topshop_ctl_shop_subdomain@saveSubdomain','url'=>'subdomain/save.html','method'=>'post'),

            array('label'=>'商家通知','display'=>true,'as'=>'topshop.shopnotice','action'=>'topshop_ctl_shop_notice@index','url'=>'shop/shopnotice.html','method'=>'get'),
            array('label'=>'商家通知详情','display'=>false,'as'=>'topshop.shopnotice.detail','action'=>'topshop_ctl_shop_notice@noticeInfo','url'=>'shop/shopnoticeinto.html','method'=>'get'),

			/*modify_2017/9/26_by_wanghaichao_start*/
			/*
            array('label'=>'店铺装修','display'=>true,'as'=>'topshop.decorate.index','action'=>'topshop_ctl_shop_decorate@index','url'=>'decorate.html','method'=>'get'),
			*/
            //店铺装修
            array('label'=>'PC端店铺装修','display'=>true,'as'=>'topshop.decorate.index','action'=>'topshop_ctl_shop_decorate@index','url'=>'decorate.html','method'=>'get'),
			/*modify_2017/9/26_by_wanghaichao_end*/
            array('label'=>'店铺装修弹出框','display'=>false,'as'=>'topshop.decorate.dialog','action'=>'topshop_ctl_shop_decorate@dialog','url'=>'decorate/dialog.html','method'=>'get'),
            array('label'=>'店铺装修配置','display'=>false,'as'=>'topshop.decorate.save','action'=>'topshop_ctl_shop_decorate@save','url'=>'decorate/save.html','method'=>'post'),

            //H5端店铺装修
            // array('label'=>'店铺装修(新)','display'=>flase,'as'=>'topshop.newwap.decorate.index','action'=>'topshop_ctl_shop_newDecorate@index','url'=>'new_decorate.html','method'=>'get'),
            array('label'=>'H5端店铺装修','display'=>true,'as'=>'topshop.newwap.decorate.edit','action'=>'topshop_ctl_shop_newDecorate@edit','url'=>'new_decorate/edit.html','method'=>'get','text_icon'=>'new', 'bg'=>'bg-green'),
            array('label'=>'H5端店铺装修配置保存','display'=>false,'as'=>'topshop.newwap.decorate.save','action'=>'topshop_ctl_shop_newDecorate@save','url'=>'new_decorate/save.html','method'=>'post'),

            array('label'=>'H5推广页面设置','display'=>true,'as'=>'topshop.wap.sales.index','action'=>'topshop_ctl_shop_sales@index','url'=>'wapsales/index.html','method'=>'get'),
            array('label'=>'H5推广页面编辑','display'=>false,'as'=>'topshop.wap.sales.edit','action'=>'topshop_ctl_shop_sales@editSales','url'=>'wapsales/edit.html','method'=>'get'),
            array('label'=>'H5推广页面保存设置','display'=>false,'as'=>'topshop.wap.sales.save','action'=>'topshop_ctl_shop_sales@saveSales','url'=>'wapsales/save.html','method'=>'post'),
            array('label'=>'H5推广页面删除活动','display'=>false,'as'=>'topshop.wap.sales.del','action'=>'topshop_ctl_shop_sales@delete','url'=>'wapsales/del.html','method'=>'get'),

            //APP端店铺装修
            array('label'=>'APP端店铺装修','display'=>true,'as'=>'topshop.app.decorate.edit','action'=>'topshop_ctl_shop_appDecorate@edit','url'=>'app_decorate/edit.html','method'=>'get','text_icon'=>'new', 'bg'=>'bg-green'),
            array('label'=>'APP端店铺装修配置保存','display'=>false,'as'=>'topshop.app.decorate.save','action'=>'topshop_ctl_shop_appDecorate@save','url'=>'app_decorate/save.html','method'=>'post'),

            //移动端店铺配置
            array('label'=>'移动端店铺装修','display'=>true,'as'=>'topshop.mobile.decorate.index','action'=>'topshop_ctl_wap_decorate@index','url'=>'wapdecorate.html','method'=>'get'),
            array('label'=>'移动端店铺装修弹出框','display'=>false,'as'=>'topshop.mobile.decorate.dialog','action'=>'topshop_ctl_wap_decorate@dialog','url'=>'wapdecorate/dialogs.html','method'=>'get'),
            array('label'=>'移动端店铺装修顺序保存','display'=>false,'as'=>'topshop.mobile.decorate.saveSort','action'=>'topshop_ctl_wap_decorate@saveSort','url'=>'wapdecorate/saveSort.html','method'=>'post'),
            array('label'=>'移动端店铺装修标签配置','display'=>false,'as'=>'topshop.mobile.decorate.addTags','action'=>'topshop_ctl_wap_decorate@addTags','url'=>'wapAddTags.html','method'=>'get'),
            array('label'=>'移动端店铺装修配置','display'=>false,'as'=>'topshop.mobile.decorate.save','action'=>'topshop_ctl_wap_decorate@save','url'=>'wapdecorate/save.html','method'=>'post'),
            array('label'=>'移动端店铺装修标签配置删除','display'=>false,'as'=>'topshop.mobile.decorate.ajaxWidgetsDel','action'=>'topshop_ctl_wap_decorate@ajaxWidgetsDel','url'=>'wapdecorate/ajaxWidgetsDel.html','method'=>'post'),
            array('label'=>'移动端店铺装修标签配置开启','display'=>false,'as'=>'topshop.mobile.decorate.openTags','action'=>'topshop_ctl_wap_decorate@openTags','url'=>'wapdecorate/opentags.html','method'=>'post'),
            array('label'=>'移动端店铺装修前台商品显示','display'=>false,'as'=>'topshop.mobile.decorate.ajaxCheckShowItems','action'=>'topshop_ctl_wap_decorate@ajaxCheckShowItems','url'=>'wapdecorate/ajaxCheckShowItems.html','method'=>'post'),
            array('label'=>'移动端店铺装修前台广告商品显示检查','display'=>false,'as'=>'topshop.mobile.decorate.checkImageSlider','action'=>'topshop_ctl_wap_decorate@checkImageSlider','url'=>'wapdecorate/checkImageSlider.html','method'=>'post'),




            array('label'=>'商家入驻信息','display'=>true,'as'=>'topshop.shopapply.info','action'=>'topshop_ctl_shop_shopinfo@index','url'=>'shop/shopapplyinfo.html','method'=>'get'),

            //开发者中心
            array('label'=>'开发者中心','display'=>true,'as'=>'topshop.open.developer.center','action'=>'topshop_ctl_open@index','url'=>'developer.html','method'=>'get', 'middleware'=>['topshop_middleware_selfManagement']),
            array('label'=>'开发者中心商家参数配置保存','display'=>false,'as'=>'topshop.open.developer.shop.conf.save','action'=>'topshop_ctl_open@setConf','url'=>'saveDevelopConf.html','method'=>'post', 'middleware'=>['topshop_middleware_selfManagement']),
            array('label'=>'开发者中心商家申请开通','display'=>false,'as'=>'topshop.open.developer.shop.apply','action'=>'topshop_ctl_open@applyForOpen','url'=>'applyDevelop.html','method'=>'post', 'middleware'=>['topshop_middleware_selfManagement']),
            array('label'=>'创建shopex节点','display'=>false,'as'=>'topshop.open.shopex.node','action'=>'topshop_ctl_open@createShopexNode','url'=>'create/shopex/node.html','method'=>'get', 'middleware'=>['topshop_middleware_selfManagement']),
            array('label'=>'申请绑定shopex产品','display'=>false,'as'=>'topshop.open.shopex.bind','action'=>'topshop_ctl_open@applyBindShopexProduct','url'=>'create/shopex/bind.html','method'=>'post', 'middleware'=>['topshop_middleware_selfManagement']),

            //安全中心
            array('label'=>'安全中心','display'=>true,'as'=>'topshop.auth.safe.index','action'=>'topshop_ctl_auth_index@index','url'=>'authsafe.html','method'=>'get'),
            array('label'=>'验证登录密码','display'=>false,'as'=>'topshop.auth.safe.checkpwd','action'=>'topshop_ctl_auth_index@checkPassword','url'=>'authpwd.html','method'=>'get'),
            array('label'=>'验证登录密码','display'=>false,'as'=>'topshop.auth.safe.docheckpwd','action'=>'topshop_ctl_auth_index@doCheckPassword','url'=>'doauthpwd.html','method'=>'post'),
            array('label'=>'验证信息','display'=>false,'as'=>'topshop.auth.safe.auth','action'=>'topshop_ctl_auth_index@auth','url'=>'authing.html','method'=>'get'),
            array('label'=>'发送验证码','display'=>false,'as'=>'topshop.auth.send.code','action'=>'topshop_ctl_auth_code@send','url'=>'sendcode.html','method'=>'post'),
            array('label'=>'验证认证信息','display'=>false,'as'=>'topshop.auth.check.code','action'=>'topshop_ctl_auth_code@checkAuth','url'=>'checkauth.html','method'=>'post'),
            array('label'=>'修改认证信息','display'=>false,'as'=>'topshop.auth.update.code','action'=>'topshop_ctl_auth_code@updateAuth','url'=>'updateauth.html','method'=>'get'),
            array('label'=>'修改认证信息','display'=>false,'as'=>'topshop.auth.updatecheck.code','action'=>'topshop_ctl_auth_code@updateAuthCheck','url'=>'updatecheck.html','method'=>'post'),
            array('label'=>'验证输入的数据','display'=>false,'as'=>'topshop.auth.isError.code','action'=>'topshop_ctl_auth_code@isErrorInfo','url'=>'auth/iserrorinfo.html','method'=>'get'),

            array('label'=>'申请类目权限','display'=>true,'as'=>'topshop.applycat.list','action'=>'topshop_ctl_shop_applycat@index','url'=>'applycat.html','method'=>'get'),
            array('label'=>'申请类目权限弹框','display'=>false,'as'=>'topshop.applycat.ajax','action'=>'topshop_ctl_shop_applycat@goApplyCat','url'=>'goapplycat.html','method'=>'get'),
            array('label'=>'申请类目权限提交','display'=>false,'as'=>'topshop.applycat.save','action'=>'topshop_ctl_shop_applycat@doApplyCat','url'=>'doapplycat.html','method'=>'post'),
            array('label'=>'申请类目权限删除','display'=>false,'as'=>'topshop.applycat.remove','action'=>'topshop_ctl_shop_applycat@removeApplyCat','url'=>'removeapplycat.html','method'=>'post'),
            array('label'=>'申请类目权限验证','display'=>false,'as'=>'topshop.applycat.get','action'=>'topshop_ctl_shop_applycat@getApplyCat','url'=>'apply-cat.html','method'=>'post'),

            // 店铺文章
            ['label'=>'文章管理','display'=>true,'as'=>'topshop.article.list','action'=>'topshop_ctl_shop_article@index','url'=>'article.html','method'=>'get'],
            ['label'=>'文章分类','display'=>false,'as'=>'topshop.article.nodes.list','action'=>'topshop_ctl_shop_article@nodes','url'=>'article-nodes.html','method'=>'get'],
            ['label'=>'保存分类','display'=>false,'as'=>'topshop.article.nodes.save','action'=>'topshop_ctl_shop_article@saveNode','url'=>'article-save-node.html','method'=>'post'],
            ['label'=>'添加/编辑分类','display'=>false,'as'=>'topshop.article.nodes.edit','action'=>'topshop_ctl_shop_article@editNode','url'=>'article-edit-node.html','method'=>'get'],
            ['label'=>'删除分类','display'=>false,'as'=>'topshop.article.nodes.del','action'=>'topshop_ctl_shop_article@delNode','url'=>'article-del-node.html','method'=>'post'],
            ['label'=>'添加/编辑文章','display'=>false,'as'=>'topshop.article.edit','action'=>'topshop_ctl_shop_article@editArticle','url'=>'article-edit.html','method'=>'get'],
            ['label'=>'保存文章','display'=>false,'as'=>'topshop.article.save','action'=>'topshop_ctl_shop_article@saveArticle','url'=>'article-save.html','method'=>'post'],
            ['label'=>'删除文章','display'=>false,'as'=>'topshop.article.del','action'=>'topshop_ctl_shop_article@delArticle','url'=>'article-del.html','method'=>'post'],

        )
    ),

    /*
    |--------------------------------------------------------------------------
    | 商家管理中心之客户服务
    |--------------------------------------------------------------------------
     */
    'aftersales' => array(
        'label' => '售后',
        'display' => true,
        'action' => 'topshop_ctl_aftersales@index',
        'icon' => 'fa fa-comments',
        'menu' => array(
            array('label'=>'退换货管理','display'=>true,'as'=>'topshop.aftersales.list','action'=>'topshop_ctl_aftersales@index','url'=>'aftersales-list.html','method'=>'get'),
            array('label'=>'退换货详情','display'=>false,'as'=>'topshop.aftersales.detail','action'=>'topshop_ctl_aftersales@detail','url'=>'aftersales-detail.html','method'=>'get'),
            array('label'=>'退换货搜索','display'=>false,'as'=>'topshop.aftersales.search','action'=>'topshop_ctl_aftersales@search','url'=>'aftersales-search.html','method'=>'post'),
            array('label'=>'退换货搜索','display'=>false,'as'=>'topshop.aftersales.search','action'=>'topshop_ctl_aftersales@search','url'=>'aftersales-search.html','method'=>'get'),
            array('label'=>'审核退换货申请','display'=>false,'as'=>'topshop.aftersales.verification','action'=>'topshop_ctl_aftersales@verification','url'=>'aftersales-verification.html','method'=>'post','middleware'=>['topshop_middleware_developerMode'] ),
            array('label'=>'换货重新发货','display'=>false,'as'=>'topshop.aftersales.sendConfirm','action'=>'topshop_ctl_aftersales@sendConfirm','url'=>'aftersales-send.html','method'=>'post'),
            /*add_20170915_by_fanglongji_start*/
            array('label'=>'售后退款','display'=>false,'as'=>'topshop.aftersales.refund','action'=>'topshop_ctl_aftersales@aftersales_refund','url'=>'aftersales-refund.html','method'=>['get','post']),
            /*add_20170915_by_fanglongji_end*/
            array('label'=>'售后重新发起上门取件','display'=>false,'as'=>'topshop.aftersales.retake.goods','action'=>'topshop_ctl_aftersales@retakeGoods','url'=>'aftersales-retake-goods.html','method'=>['post']),
            //评价管理&DSR管理
            array('label'=>'评价列表','display'=>true,'as'=>'topshop.rate.list','action'=>'topshop_ctl_rate@index','url'=>'rate-list.html','method'=>'get'),
            array('label'=>'评价搜索','display'=>false,'as'=>'topshop.rate.search','action'=>'topshop_ctl_rate@search','url'=>'rate-search.html','method'=>'get'),
            array('label'=>'评价详情','display'=>false,'as'=>'topshop.rate.detail','action'=>'topshop_ctl_rate@detail','url'=>'rate-detail.html','method'=>'get'),
            array('label'=>'评价回复','display'=>false,'as'=>'topshop.rate.reply','action'=>'topshop_ctl_rate@reply','url'=>'rate-reply.html','method'=>'post'),

            array('label'=>'申诉列表','display'=>true,'as'=>'topshop.rate.appeal.list','action'=>'topshop_ctl_rate_appeal@appealList','url'=>'rate-appeal-list.html','method'=>'get'),
            array('label'=>'申诉搜索','display'=>false,'as'=>'topshop.rate.appeal.search','action'=>'topshop_ctl_rate_appeal@search','url'=>'rate-appeal-search.html','method'=>'get'),
            array('label'=>'申诉详情','display'=>false,'as'=>'topshop.rate.appeal.detail','action'=>'topshop_ctl_rate_appeal@appeaInfo','url'=>'rate-appeal-info.html','method'=>'get'),
            array('label'=>'评价申诉','display'=>false,'as'=>'topshop.rate.appeal','action'=>'topshop_ctl_rate_appeal@appeal','url'=>'rate-appeal.html','method'=>'post'),

            array('label'=>'评价概况','display'=>true,'as'=>'topshop.rate.count','action'=>'topshop_ctl_rate_count@index','url'=>'rate-count.html','method'=>'get'),

            //咨询管理
            array('label'=>'咨询列表','display'=>true,'as'=>'topshop.gask.list','action'=>'topshop_ctl_consultation@index','url'=>'gask-list.html','method'=>'get'),
            array('label'=>'咨询回复','display'=>false,'as'=>'topshop.gask.reply','action'=>'topshop_ctl_consultation@doReply','url'=>'gask-reply.html','method'=>'post'),
            array('label'=>'咨询筛选','display'=>false,'as'=>'topshop.gask.screening','action'=>'topshop_ctl_consultation@screening','url'=>'gask-screening.html','method'=>'get'),
            array('label'=>'回复删除','display'=>false,'as'=>'topshop.gask.delete','action'=>'topshop_ctl_consultation@doDelete','url'=>'gask-del.html','method'=>'post'),
            array('label'=>'显示或关闭咨询与回复','display'=>false,'as'=>'topshop.gask.display','action'=>'topshop_ctl_consultation@doDisplay','url'=>'gask-display.html','method'=>'post'),

            //im相关管理-365webcall配置
            array('label'=>'365webcall配置','display'=>true,'as'=>'topshop.im.webcall.index','action'=>'topshop_ctl_im_webcall@index','url'=>'im-webcall.html','method'=>'get'),
            array('label'=>'365webcall配置','display'=>false,'as'=>'topshop.im.webcall.applyPage','action'=>'topshop_ctl_im_webcall@applyPage','url'=>'im-webcall-apply.html','method'=>'get'),
            array('label'=>'365webcall配置','display'=>false,'as'=>'topshop.im.webcall.save','action'=>'topshop_ctl_im_webcall@save','url'=>'im-webcall-save.html','method'=>'post'),
            array('label'=>'365webcall申请','display'=>false,'as'=>'topshop.im.webcall.apply','action'=>'topshop_ctl_im_webcall@apply','url'=>'im-webcall-apply.html','method'=>'post'),
            // 王衍生-2018/07/05-start
            array('label'=>'推送商品退换货管理','display'=>true,'as'=>'topshop.aftersales.muumi.list','action'=>'topshop_ctl_aftersalesmuumi@index','url'=>'aftersales/muumi/list.html','method'=>'get'),
            array('label'=>'推送商品退换货管理','display'=>false,'as'=>'topshop.aftersales.muumi.search','action'=>'topshop_ctl_aftersalesmuumi@search','url'=>'aftersales/muumi/search.html','method'=>'get'),
            array('label'=>'推送商品退换货管理','display'=>false,'as'=>'topshop.aftersales.muumi.search','action'=>'topshop_ctl_aftersalesmuumi@search','url'=>'aftersales/muumi/search.html','method'=>'post'),
            array('label'=>'推送商品退换货详情','display'=>false,'as'=>'topshop.aftersales.muumi.detail','action'=>'topshop_ctl_aftersalesmuumi@detail','url'=>'aftersales/muumi/detail.html','method'=>'get'),
            array('label'=>'推送商品审核退换货申请','display'=>false,'as'=>'topshop.aftersales.muumi.verification','action'=>'topshop_ctl_aftersalesmuumi@verification','url'=>'aftersales/muumi/verification.html','method'=>'post','middleware'=>['topshop_middleware_developerMode']),
            array('label'=>'推送商品换货重新发货','display'=>false,'as'=>'topshop.aftersales.muumi.sendConfirm','action'=>'topshop_ctl_aftersalesmuumi@sendConfirm','url'=>'aftersales/muumi/send.html','method'=>'post'),

            // 王衍生-2018/07/05-end
        ),
    ),

    'shopinfo' => array(
        'label' => '结算',
        'display' => true,
        'action' => 'topshop_ctl_shop_shopinfo@index',
        'icon' => 'fa fa-calculator',
        'menu' => array(
            array('label'=>'商家结算汇总','display'=>false,'as'=>'topshop.settlement','action'=>'topshop_ctl_clearing_settlement@index','url'=>'shop/settlement.html','method'=>'get'),
            array('label'=>'商家结算明细','display'=>false,'as'=>'topshop.settlement.detail','action'=>'topshop_ctl_clearing_settlement@detail','url'=>'shop/settlement_detail.html','method'=>'get'),
            array('label'=>'购物券补贴明细','display'=>true,'as'=>'topshop.subsidy.voucher.detail','action'=>'topshop_ctl_clearing_vouchersubsidy@detail','url'=>'shop/voucher/subsidy/detail.html','method'=>'get'),
            array('label'=>'商家保证金详情','display'=>true,'as'=>'topshop.guaranteeMoney.detail','action'=>'topshop_ctl_guaranteeMoney_list@index','url'=>'guaranteeMoney/list.html','method'=>'get'),
            array('label'=>'保证金交易记录搜索','display'=>false,'as'=>'topshop.guaranteeMoney.search','action'=>'topshop_ctl_guaranteeMoney_list@search','url'=>'guaranteeMoney/search.html','method'=>['get','post']),
            array('label'=>'保证金交易记录详情页面','display'=>false,'as'=>'topshop.guaranteeMoney.logdetail','action'=>'topshop_ctl_guaranteeMoney_list@ajaxLogDetail','url'=>'ajaxdetail.html','method'=>'get'),
            array('label'=>'平台结算明细(收退款)','display'=>true,'as'=>'topshop.settlement.bill.confirm','action'=>'topshop_ctl_clearing_settlement@billDetail','url'=>'settlement_billconfirm.html','method'=>'get'),
            array('label'=>'平台结算明细(收退货)','display'=>true,'as'=>'topshop.sysstat.itemtrade.confirm','action'=>'topshop_ctl_sysstat_itemtrade@confirmDetail','url'=>'sysstat/itemconfirm.html','method'=>'get'),
            array('label'=>'平台结算日报','display'=>true,'as'=>'topshop.settle.daily','action'=>'topshop_ctl_clearing_settlement@settleDaily','url'=>'sysstat/settleDaily.html','method'=>'get'),
            array('label'=>'城通货款对账列表','display'=>true,'as'=>'topshop.settlement.bill.ct','action'=>'topshop_ctl_clearing_chengtong_settlement@billDetail','url'=>'ct_settle.html','method'=>'get','shop_belong'=>'LM'),
            array('label'=>'城通货款对账','display'=>false,'as'=>'topshop.balance.account.ct','action'=>'topshop_ctl_clearing_chengtong_settlement@balanceAccount','url'=>'ct_balance_account.html','method'=>'get','shop_belong'=>'LM'),
            array('label'=>'城通物流运费导入视图','display'=>false,'as'=>'topshop.settlement.bill.ct.view','action'=>'topshop_ctl_import_ctpostfee@view','url'=>'ct_post_fee_view.html','method'=>'get','shop_belong'=>'LM'),
            array('label'=>'城通物流运费导入','display'=>false,'as'=>'topshop.settlement.bill.ct.import','action'=>'topshop_ctl_import_ctpostfee@import','url'=>'ct_post_fee_import.html','method'=>'post','shop_belong'=>'LM'),
            array('label'=>'城通物流结算导出','display'=>false,'as'=>'topshop.settlement.bill.ct.export','action'=>'topshop_ctl_export_ctbill@exportCtBill','url'=>'ct_settle_export.html','method'=>'get','shop_belong'=>'LM'),
            array('label'=>'城通物流对比列表','display'=>true,'as'=>'topshop.post.fee.ct','action'=>'topshop_ctl_clearing_chengtong_settlement@postFeeDetail','url'=>'ct_post_fee.html','method'=>'get','shop_belong'=>'LM'),
            array('label'=>'城通物流运费导出','display'=>false,'as'=>'topshop.post.fee.ct.export','action'=>'topshop_ctl_export_ctpostfee@exportPostFee','url'=>'ct_post_fee_export.html','method'=>'get','shop_belong'=>'LM'),
            array('label'=>'城通物流运费上传文件','display'=>false,'as'=>'topshop.settlement.bill.ct.upload.csv','action'=>'topshop_ctl_import_ctpostfee@uploadCsvFile','url'=>'ct_post_fee_upload_csv.html','method'=>'post','shop_belong'=>'LM'),
            array('label'=>'发票列表','display'=>true,'as'=>'topshop.settlement.trade.invoice','action'=>'topshop_ctl_clearing_invoice@invoiceList','url'=>'trade_invoice.html','method'=>'get','shop_belong'=>'LM'),
            array('label'=>'导出发票','display'=>false,'as'=>'topshop.settlement.export.invoice','action'=>'topshop_ctl_clearing_invoice@exportInvoice','url'=>'export_invoice.html','method'=>'get','shop_belong'=>'LM'),
            array('label'=>'导入发票视图','display'=>false,'as'=>'topshop.settlement.import.invoice.view','action'=>'topshop_ctl_clearing_invoice@view','url'=>'import_invoice_view.html','method'=>'get','shop_belong'=>'LM'),
            array('label'=>'导入发票信息','display'=>false,'as'=>'topshop.settlement.import.invoice.info','action'=>'topshop_ctl_clearing_invoice@invoice_info_import','url'=>'import_invoice_info.html','method'=>'post','shop_belong'=>'LM'),
            array('label'=>'下载导入模板','display'=>false,'as'=>'topshop.settlement.download.invoice.tpl','action'=>'topshop_ctl_clearing_invoice@downLoadCsvTpl','url'=>'download_invoice_tpl.html','method'=>'get','shop_belong'=>'LM'),
            array('label'=>'上传导入信息','display'=>false,'as'=>'topshop.settlement.upload.invoice.tpl','action'=>'topshop_ctl_clearing_invoice@uploadCsvFile','url'=>'upload_invoice_tpl.html','method'=>'post','shop_belong'=>'LM'),


            array('label'=>'提现','display'=>true,'as'=>'topshop.withdraw.list','action'=>'topshop_ctl_clearing_withdraw@index','url'=>'shop/withdraw.html','method'=>'get'),
            array('label'=>'ajax请求提现页面','display'=>false,'as'=>'topshop.withdraw.search','action'=>'topshop_ctl_clearing_withdraw@search','url'=>'shop/withdraw_search.html','method'=>['get','post']),
            array('label'=>'提现详情','display'=>false,'as'=>'topshop.withdraw.detail','action'=>'topshop_ctl_clearing_withdraw@detail','url'=>'shop/withdraw_detail.html','method'=>'get'),
            array('label'=>'编辑提现','display'=>false,'as'=>'topshop.withdraw.edit','action'=>'topshop_ctl_clearing_withdraw@editWithdraw','url'=>'shop/edit_withdraw.html','method'=>'get'),
            array('label'=>'保存提现','display'=>false,'as'=>'topshop.withdraw.save','action'=>'topshop_ctl_clearing_withdraw@saveWithdraw','url'=>'shop/save_withdraw.html','method'=>'post'),
            array('label'=>'删除提现','display'=>false,'as'=>'topshop.withdraw.del','action'=>'topshop_ctl_clearing_withdraw@delWithdraw','url'=>'shop/del_withdraw.html','method'=>'get'),
            array('label'=>'计算提现','display'=>false,'as'=>'topshop.withdraw.calc','action'=>'topshop_ctl_clearing_withdraw@calcWithdraw','url'=>'shop/calc_withdraw.html','method'=>'post'),
            array('label'=>'明细导出','display'=>false,'as'=>'topshop.withdraw.export','action'=>'topshop_ctl_clearing_withdraw@export','url'=>'shop/export_withdraw.html','method'=>'get'),
            array('label'=>'申请备注保存','display'=>false,'as'=>'topshop.withdraw.savecomment','action'=>'topshop_ctl_clearing_withdraw@saveComment','url'=>'shop/savecomment_withdraw.html','method'=>'post'),
            array('label'=>'取消提现申请','display'=>false,'as'=>'topshop.withdraw.dodel','action'=>'topshop_ctl_clearing_withdraw@dodel','url'=>'shop/dodel_withdraw.html','method'=>'post'),
        )
    ),

    'sysstat' => array(
        'label' => '报表',
        'display' => true,
        'action' => 'topshop_ctl_sysstat_sysstat@index',
        'icon' => 'fa fa-area-chart',
        'menu' => array(
            array('label'=>'商家运营概况','display'=>true,'as'=>'topshop.sysstat','action'=>'topshop_ctl_sysstat_sysstat@index','url'=>'sysstat/sysstat.html','method'=>'get'),
            array('label'=>'供应商销售分析','display'=>true,'as'=>'topshop.sysstat.suppliertrade.index','action'=>'topshop_ctl_sysstat_suppliertrade@index','url'=>'sysstat/suppliertrade.html','method'=>'get'),
            array('label'=>'交易数据分析','display'=>true,'as'=>'topshop.stattrade','action'=>'topshop_ctl_sysstat_stattrade@index','url'=>'sysstat/stattrade.html','method'=>'get'),
            array('label'=>'商品销售分析','display'=>true,'as'=>'topshop.sysstat.itemtrade.index','action'=>'topshop_ctl_sysstat_itemtrade@index','url'=>'sysstat/itemtrade.html','method'=>'get'),
            array('label'=>'商品出库分析','display'=>true,'as'=>'topshop.sysstat.itemtrade.settle','action'=>'topshop_ctl_sysstat_itemtrade@detail','url'=>'sysstat/itemsettle.html','method'=>'get'),
            array('label'=>'商品出库明细','display'=>true,'as'=>'topshop.sysstat.itemtrade.settle.detail','action'=>'topshop_ctl_sysstat_itemtrade@settledetail','url'=>'sysstat/itemsettledetail.html','method'=>'get'),
            array('label'=>'问题订单分析','display'=>true,'as'=>'topshop.sysstat.aftersales.index','action'=>'topshop_ctl_sysstat_stataftersales@index','url'=>'sysstat/stataftersales.html','method'=>'get'),
            array('label'=>'流量数据分析','display'=>true,'as'=>'topshop.sysstat.systraffic.index','action'=>'topshop_ctl_sysstat_systraffic@index','url'=>'sysstat/systraffic.html','method'=>'get'),
        )
    ),

    'account' => array(
        'label' => '账号',
        'display' => true,
        'action' => 'topshop_ctl_account_list@index',
        'icon' => 'fa fa-user-secret',
        'menu' => array(
            array('label'=>'账号管理','display'=>true,'as'=>'topshop.account.list','action'=>'topshop_ctl_account_list@index','url'=>'account/list.html','method'=>'get'),
            array('label'=>'编辑账号','display'=>false,'as'=>'topshop.account.edit','action'=>'topshop_ctl_account_list@edit','url'=>'account/edit.html','method'=>'get'),
            array('label'=>'修改账号密码','display'=>false,'as'=>'topshop.account.modifyPwd','action'=>'topshop_ctl_account_list@modifyPwd','url'=>'account/modifypwd.html','method'=>'post'),
            array('label'=>'保存账号','display'=>false,'as'=>'topshop.account.save','action'=>'topshop_ctl_account_list@save','url'=>'account/add.html','method'=>'post'),
            array('label'=>'删除账号','display'=>false,'as'=>'topshop.account.delete','action'=>'topshop_ctl_account_list@delete','url'=>'account/del.html','method'=>'get'),

            array('label'=>'角色管理','display'=>true,'as'=>'topshop.roles.list','action'=>'topshop_ctl_account_roles@index','url'=>'roles/list.html','method'=>'get'),
            array('label'=>'保存角色保存','display'=>false,'as'=>'topshop.roles.save','action'=>'topshop_ctl_account_roles@save','url'=>'roles/save.html','method'=>'post'),
            array('label'=>'编辑角色页面','display'=>false,'as'=>'topshop.roles.edit','action'=>'topshop_ctl_account_roles@edit','url'=>'roles/edit.html','method'=>'get'),
            array('label'=>'删除角色','display'=>false,'as'=>'topshop.roles.delete','action'=>'topshop_ctl_account_roles@delete','url'=>'roles/del.html','method'=>'get'),

            array('label'=>'操作日志','display'=>true,'as'=>'topshop.oplog.list','action'=>'topshop_ctl_account_log@index','url'=>'account/loglist.html','method'=>'get'),

			/*add_2017/9/23_by_wanghaichao_start  增加供应商路由*/
            array('label'=>'供应商账号管理','display'=>true,'as'=>'topshop.account.supplier.list','action'=>'topshop_ctl_account_supplier@index','url'=>'account/supplier.html','method'=>'get'),
            array('label'=>'供应商查询页面','display'=>false,'as'=>'topshop.account.supplier.chkexisting','action'=>'topshop_ctl_account_supplier@chkExistingSupplier','url'=>'account/supplier_chk_existing.html','method'=>'get'),
            array('label'=>'供应商查询操作','display'=>false,'as'=>'topshop.account.supplier.dochkexsiting','action'=>'topshop_ctl_account_supplier@doChkExistingSupplier','url'=>'account/supplier_do_chk.html','method'=>'post'),
            array('label'=>'审核供应商','display'=>false,'as'=>'topshop.account.supplier.audit','action'=>'topshop_ctl_account_supplier@auditSupplier','url'=>'account/supplier_audit.html','method'=>'post'),
            array('label'=>'同步供应商至惠民平台','display'=>false,'as'=>'topshop.account.supplier.sync','action'=>'topshop_ctl_account_supplier@singleSyncToHm','url'=>'account/supplier_sync.html','method'=>'post'),
            array('label'=>'完善惠民商户资料','display'=>true,'as'=>'topshop.hmactive.account.supplier.edit','action'=>'topshop_ctl_account_supplier@hmEdit','url'=>'hmactive/account/supplier_edit.html','method'=>'get' ,'shop_belong'=>'HMSUPPLIER'),

            array('label'=>'供应商编辑账号','display'=>false,'as'=>'topshop.account.supplier.edit','action'=>'topshop_ctl_account_supplier@edit','url'=>'account/supplier_edit.html','method'=>'get'),
            array('label'=>'供应商修改账号密码','display'=>false,'as'=>'topshop.account.supplier.modifyPwd','action'=>'topshop_ctl_account_supplier@modifyPwd','url'=>'account/supplier_modifypwd.html','method'=>'post'),
            array('label'=>'供应商保存账号','display'=>false,'as'=>'topshop.account.supplier.save','action'=>'topshop_ctl_account_supplier@save','url'=>'account/supplier_add.html','method'=>'post'),
            array('label'=>'供应商删除账号','display'=>false,'as'=>'topshop.account.supplier.delete','action'=>'topshop_ctl_account_supplier@delete','url'=>'account/supplier_del.html','method'=>['get','post']),
            /*add_2017/1/15_by_gurundong_start  增加代理商路由*/
            array('label'=>'线下店添加编辑','display'=>false,'as'=>'topshop.account.supplier.agent.shop','action'=>'topshop_ctl_account_supplier@agentShop','url'=>'account/agent/shop.html','method'=>'get'),
            array('label'=>'线下店收款码编辑','display'=>false,'as'=>'topshop.account.supplier.agent.qrcode','action'=>'topshop_ctl_account_supplier@agentShopQr','url'=>'account/agent/shop_qr.html','method'=>'get'),
            array('label'=>'线下店收款码生成','display'=>false,'as'=>'topshop.account.supplier.agent.qrcodesave','action'=>'topshop_ctl_account_supplier@agentShopQrSave','url'=>'account/agent/shop_qr_save.html','method'=>'post'),
            array('label'=>'线下店收款码下载','display'=>false,'as'=>'topshop.account.supplier.agent.qrupload','action'=>'topshop_ctl_account_supplier@qrUpload','url'=>'account/agent/shop_qr_upload.html','method'=>'get'),
            array('label'=>'线下店添加保存','display'=>false,'as'=>'topshop.account.supplier.agent.shop.save','action'=>'topshop_ctl_account_supplier@agentShopSave','url'=>'account/agent/shop_save.html','method'=>'post'),
            array('label'=>'线下店列表','display'=>false,'as'=>'topshop.account.supplier.agent.shop.list','action'=>'topshop_ctl_account_supplier@agentShopList','url'=>'account/agent/shop_list.html','method'=>'get'),
            array('label'=>'线下店搜索','display'=>false,'as'=>'topshop.account.supplier.agent.shop.search','action'=>'topshop_ctl_account_supplier@agentShopSearch','url'=>'account/agent/shop_search.html','method'=>'get'),
            array('label'=>'线下店删除','display'=>false,'as'=>'topshop.account.supplier.agent.shop.del','action'=>'topshop_ctl_account_supplier@agentShopDel','url'=>'account/agent/shop_del.html','method'=>'get'),
            array('label'=>'线下店活动列表','display'=>false,'as'=>'topshop.account.supplier.agent.activity.list','action'=>'topshop_ctl_account_supplier@agentActivityList','url'=>'account/activity/activity_list.html','method'=>'get'),
            array('label'=>'线下店活动添加编辑','display'=>false,'as'=>'topshop.account.supplier.agent.activity','action'=>'topshop_ctl_account_supplier@agentActivity','url'=>'account/activity/activity.html','method'=>'get'),
            array('label'=>'线下店活动添加保存','display'=>false,'as'=>'topshop.account.supplier.agent.activity.save','action'=>'topshop_ctl_account_supplier@agentActivitySave','url'=>'account/activity/activity_save.html','method'=>'post'),
            array('label'=>'线下店活动删除','display'=>false,'as'=>'topshop.account.supplier.agent.activity.del','action'=>'topshop_ctl_account_supplier@agentActivityDel','url'=>'account/activity/activity_del.html','method'=>'get'),
            array('label'=>'线下店入驻','display'=>false,'as'=>'topshop.account.supplier.agent.merchant.entry','action'=>'topshop_ctl_account_supplier@ajaxMerchantEntry','url'=>'account/agent/merchant_entry.html','method'=>'post'),
            array('label'=>'线下店修改密码','display'=>false,'as'=>'topshop.account.supplier.agent.modifypassword','action'=>'topshop_ctl_account_supplier@modifyPwdAgentShop','url'=>'account/agent/modifypassword.html','method'=>'post'),
            /*add_2017/1/15_by_gurundong_end*/
			/*add_2017/9/23_by_wanghaichao_end*/
			/*add_2018/12/4_by_wanghaichao_start*/
			//用户黑名单列表
            array('label'=>'黑名单用户','display'=>true,'as'=>'topshop.account.black','action'=>'topshop_ctl_account_blacklist@index','url'=>'account/blacklist.html','method'=>'get'),
			//添加黑名单用户
            array('label'=>'添加黑名单用户','display'=>false,'as'=>'topshop.account.black.addblack','action'=>'topshop_ctl_account_blacklist@addBlack','url'=>'account/addblacklist.html','method'=>'get'),
			//搜索用户
            array('label'=>'搜索用户','display'=>false,'as'=>'topshop.account.black.searchemember','action'=>'topshop_ctl_account_blacklist@searchMember','url'=>'account/searchmember.html','method'=>['get','post']),
            array('label'=>'移入黑名单','display'=>false,'as'=>'topshop.account.black.save','action'=>'topshop_ctl_account_blacklist@save','url'=>'account/blacksave.html','method'=>['get','post']),
			//从黑名单中删除,恢复购买权限
            array('label'=>'从黑名单中移除','display'=>false,'as'=>'topshop.account.black.del','action'=>'topshop_ctl_account_blacklist@delBlack','url'=>'account/delBlack.html','method'=>['get','post']),
			/*add_2018/12/4_by_wanghaichao_end*/

        )
    ),
    /*add_20180516_by_jiangyunhan_start*/
    'supplier'=>array(
        'label'=>'线下店',
        'display'=>'true',
        'action'=>'topshop_ctl_supplier_list@index',
        'icon'=>'fa fa-institution',
        'menu' => array(
            array('label'=>'线下店列表','display'=>true,'as'=>'topshop.supplier.list','action'=>'topshop_ctl_supplier_list@index','url'=>'supplier/agent/list.html','method'=>'get'),
            array('label'=>'线下店搜索','display'=>false,'as'=>'topshop.supplier.agent.search','action'=>'topshop_ctl_supplier_list@agentShopSearch','url'=>'supplier/agent/agent_search.html','method'=>'get'),
            array('label'=>'线下店排序','display'=>false,'as'=>'topshop.supplier.agent.ordersort','action'=>'topshop_ctl_supplier_list@order_sort','url'=>'supplier/agent/order_sort.html','method'=>'post'),
            array('label'=>'线下店置顶','display'=>false,'as'=>'topshop.supplier.agent.top','action'=>'topshop_ctl_supplier_list@top','url'=>'supplier/agent/top.html','method'=>'post'),
            array('label'=>'线下店分类添加','display'=>false,'as'=>'topshop.supplier.category.save','action'=>'topshop_ctl_supplier_category@saveCat','url'=>'supplier/agent/categorysave.html','method'=>'post'),
            array('label'=>'线下店分类删除','display'=>false,'as'=>'topshop.supplier.category.del','action'=>'topshop_ctl_supplier_category@removeCat','url'=>'supplier/agent/categorydel.html','method'=>'post'),
            array('label'=>'线下店分类列表','display'=>true,'as'=>'topshop.supplier.category.list','action'=>'topshop_ctl_supplier_category@index','url'=>'supplier/agent/categorylist.html','method'=>'get'),
            array('label'=>'线下店分类列表排序','display'=>false,'as'=>'topshop.supplier.category.list.sort','action'=>'topshop_ctl_supplier_category@sortOpt','url'=>'supplier/agent/categorylistsort.html','method'=>'post'),
            array('label'=>'线下店装修','display'=>true,'as'=>'topshop.supplier.decorate.index','action'=>'topshop_ctl_supplier_decorate@index','url'=>'supplier/decorate/index.html','method'=>'get'),
            array('label'=>'线下店装修编辑','display'=>false,'as'=>'topshop.supplier.decorate.edit','action'=>'topshop_ctl_supplier_decorate@edit','url'=>'supplier/decorate/edit.html','method'=>'get'),
            array('label'=>'线下店装修保存','display'=>false,'as'=>'topshop.supplier.decorate.save','action'=>'topshop_ctl_supplier_decorate@save','url'=>'supplier/decorate/save.html','method'=>'post'),
            array('label'=>'线下店挂件状态修改','display'=>false,'as'=>'topshop.supplier.decorate.setStatus','action'=>'topshop_ctl_supplier_decorate@setStatus','url'=>'supplier/decorate/setStatus.html','method'=>'post'),
            array('label'=>'线下店挂件删除','display'=>false,'as'=>'topshop.supplier.decorate.delete','action'=>'topshop_ctl_supplier_decorate@delete','url'=>'supplier/decorate/delete.html','method'=>'post'),
            array('label'=>'线下店挂件排序','display'=>false,'as'=>'topshop.supplier.decorate.sortOpt','action'=>'topshop_ctl_supplier_decorate@sortOpt','url'=>'supplier/decorate/sortOpt.html','method'=>'post'),
            array('label'=>'线下店专家管理','display'=>true,'as'=>'topshop.supplier.expert.index','action'=>'topshop_ctl_supplier_expert_expert@index','url'=>'supplier/expert/index.html','method'=>'get'),
            array('label'=>'线下店专家创建','display'=>false,'as'=>'topshop.supplier.expert.create','action'=>'topshop_ctl_supplier_expert_expert@create','url'=>'supplier/expert/create.html','method'=>'get'),
            array('label'=>'线下店专家编辑','display'=>false,'as'=>'topshop.supplier.expert.edit','action'=>'topshop_ctl_supplier_expert_expert@edit','url'=>'supplier/expert/edit.html','method'=>'get'),
            array('label'=>'线下店专家保存','display'=>false,'as'=>'topshop.supplier.expert.store','action'=>'topshop_ctl_supplier_expert_expert@store','url'=>'supplier/expert/store.html','method'=>'post'),
            array('label'=>'线下店专家更新','display'=>false,'as'=>'topshop.supplier.expert.update','action'=>'topshop_ctl_supplier_expert_expert@update','url'=>'supplier/expert/update.html','method'=>'post'),
            array('label'=>'线下店专家删除','display'=>false,'as'=>'topshop.supplier.expert.destroy','action'=>'topshop_ctl_supplier_expert_expert@destroy','url'=>'supplier/expert/destroy.html','method'=>'post'),
            array('label'=>'线下店专家排序','display'=>false,'as'=>'topshop.supplier.expert.ordersort','action'=>'topshop_ctl_supplier_expert_expert@order_sort','url'=>'supplier/expert/order_sort.html','method'=>'post'),
            array('label'=>'线下店人员职位管理','display'=>true,'as'=>'topshop.supplier.personPosition.index','action'=>'topshop_ctl_supplier_personPosition@index','url'=>'supplier/personPosition/index.html','method'=>'get'),
            array('label'=>'线下店人员职位创建','display'=>false,'as'=>'topshop.supplier.personPosition.create','action'=>'topshop_ctl_supplier_personPosition@create','url'=>'supplier/personPosition/create.html','method'=>'get'),
            array('label'=>'线下店人员职位编辑','display'=>false,'as'=>'topshop.supplier.personPosition.edit','action'=>'topshop_ctl_supplier_personPosition@edit','url'=>'supplier/personPosition/edit.html','method'=>'get'),
            array('label'=>'线下店人员职位保存','display'=>false,'as'=>'topshop.supplier.personPosition.store','action'=>'topshop_ctl_supplier_personPosition@store','url'=>'supplier/personPosition/store.html','method'=>'post'),
            array('label'=>'线下店人员职位更新','display'=>false,'as'=>'topshop.supplier.personPosition.update','action'=>'topshop_ctl_supplier_personPosition@update','url'=>'supplier/personPosition/update.html','method'=>'post'),
            array('label'=>'线下店人员职位删除','display'=>false,'as'=>'topshop.supplier.personPosition.destroy','action'=>'topshop_ctl_supplier_personPosition@destroy','url'=>'supplier/personPosition/destroy.html','method'=>'post'),
            array('label'=>'线下店人员职位排序','display'=>false,'as'=>'topshop.supplier.personPosition.ordersort','action'=>'topshop_ctl_supplier_personPosition@order_sort','url'=>'supplier/personPosition/order_sort.html','method'=>'post'),
            array('label'=>'线下店推荐菜配置列表','display'=>false,'as'=>'topshop.supplier.oprationFood.index','action'=>'topshop_ctl_supplier_oprationFood@index','url'=>'supplier/opration_food/index.html','method'=>'get'),
            array('label'=>'线下店推荐菜配置创建','display'=>false,'as'=>'topshop.supplier.oprationFood.create','action'=>'topshop_ctl_supplier_oprationFood@create','url'=>'supplier/opration_food/create.html','method'=>'get'),
            array('label'=>'线下店推荐菜配置编辑','display'=>false,'as'=>'topshop.supplier.oprationFood.edit','action'=>'topshop_ctl_supplier_oprationFood@edit','url'=>'supplier/opration_food/edit.html','method'=>'get'),
            array('label'=>'线下店推荐菜配置保存','display'=>false,'as'=>'topshop.supplier.oprationFood.store','action'=>'topshop_ctl_supplier_oprationFood@store','url'=>'supplier/opration_food/store.html','method'=>'post'),
            array('label'=>'线下店推荐菜配置更新','display'=>false,'as'=>'topshop.supplier.oprationFood.update','action'=>'topshop_ctl_supplier_oprationFood@update','url'=>'supplier/opration_food/update.html','method'=>'post'),
            array('label'=>'线下店推荐菜配置删除','display'=>false,'as'=>'topshop.supplier.oprationFood.destroy','action'=>'topshop_ctl_supplier_oprationFood@destroy','url'=>'supplier/opration_food/destroy.html','method'=>'post'),
            array('label'=>'线下店推荐菜配置排序','display'=>false,'as'=>'topshop.supplier.oprationFood.ordersort','action'=>'topshop_ctl_supplier_oprationFood@order_sort','url'=>'supplier/opration_food/order_sort.html','method'=>'post'),
            array('label'=>'线下店人员配置列表','display'=>false,'as'=>'topshop.supplier.oprationPerson.index','action'=>'topshop_ctl_supplier_oprationPerson@index','url'=>'supplier/opration_person/index.html','method'=>'get'),
            array('label'=>'线下店人员配置创建','display'=>false,'as'=>'topshop.supplier.oprationPerson.create','action'=>'topshop_ctl_supplier_oprationPerson@create','url'=>'supplier/opration_person/create.html','method'=>'get'),
            array('label'=>'线下店人员配置编辑','display'=>false,'as'=>'topshop.supplier.oprationPerson.edit','action'=>'topshop_ctl_supplier_oprationPerson@edit','url'=>'supplier/opration_person/edit.html','method'=>'get'),
            array('label'=>'线下店人员配置保存','display'=>false,'as'=>'topshop.supplier.oprationPerson.store','action'=>'topshop_ctl_supplier_oprationPerson@store','url'=>'supplier/opration_person/store.html','method'=>'post'),
            array('label'=>'线下店人员配置更新','display'=>false,'as'=>'topshop.supplier.oprationPerson.update','action'=>'topshop_ctl_supplier_oprationPerson@update','url'=>'supplier/opration_person/update.html','method'=>'post'),
            array('label'=>'线下店人员配置删除','display'=>false,'as'=>'topshop.supplier.oprationPerson.destroy','action'=>'topshop_ctl_supplier_oprationPerson@destroy','url'=>'supplier/opration_person/destroy.html','method'=>'post'),
            array('label'=>'线下店人员配置排序','display'=>false,'as'=>'topshop.supplier.oprationPerson.ordersort','action'=>'topshop_ctl_supplier_oprationPerson@order_sort','url'=>'supplier/opration_person/order_sort.html','method'=>'post'),
            array('label'=>'线下店视频配置列表','display'=>false,'as'=>'topshop.supplier.oprationVideo.index','action'=>'topshop_ctl_supplier_oprationVideo@index','url'=>'supplier/opration_video/index.html','method'=>'get'),
            array('label'=>'线下店视频配置创建','display'=>false,'as'=>'topshop.supplier.oprationVideo.create','action'=>'topshop_ctl_supplier_oprationVideo@create','url'=>'supplier/opration_video/create.html','method'=>'get'),
            array('label'=>'线下店视频配置编辑','display'=>false,'as'=>'topshop.supplier.oprationVideo.edit','action'=>'topshop_ctl_supplier_oprationVideo@edit','url'=>'supplier/opration_video/edit.html','method'=>'get'),
            array('label'=>'线下店视频配置保存','display'=>false,'as'=>'topshop.supplier.oprationVideo.store','action'=>'topshop_ctl_supplier_oprationVideo@store','url'=>'supplier/opration_video/store.html','method'=>'post'),
            array('label'=>'线下店视频配置更新','display'=>false,'as'=>'topshop.supplier.oprationVideo.update','action'=>'topshop_ctl_supplier_oprationVideo@update','url'=>'supplier/opration_video/update.html','method'=>'post'),
            array('label'=>'线下店视频配置删除','display'=>false,'as'=>'topshop.supplier.oprationVideo.destroy','action'=>'topshop_ctl_supplier_oprationVideo@destroy','url'=>'supplier/opration_video/destroy.html','method'=>'post'),
            array('label'=>'线下店视频配置排序','display'=>false,'as'=>'topshop.supplier.oprationVideo.ordersort','action'=>'topshop_ctl_supplier_oprationVideo@order_sort','url'=>'supplier/opration_video/order_sort.html','method'=>'post'),
            array('label'=>'线下店专家评论列表','display'=>false,'as'=>'topshop.supplier.expert_comment.index','action'=>'topshop_ctl_supplier_expert_expertComment@index','url'=>'supplier/expert_comment/index.html','method'=>'get'),
            array('label'=>'线下店专家评论创建','display'=>false,'as'=>'topshop.supplier.expert_comment.create','action'=>'topshop_ctl_supplier_expert_expertComment@create','url'=>'supplier/expert_comment/create.html','method'=>'get'),
            array('label'=>'线下店专家评论编辑','display'=>false,'as'=>'topshop.supplier.expert_comment.edit','action'=>'topshop_ctl_supplier_expert_expertComment@edit','url'=>'supplier/expert_comment/edit.html','method'=>'get'),
            array('label'=>'线下店专家评论保存','display'=>false,'as'=>'topshop.supplier.expert_comment.store','action'=>'topshop_ctl_supplier_expert_expertComment@store','url'=>'supplier/expert_comment/store.html','method'=>'post'),
            array('label'=>'线下店专家评论更新','display'=>false,'as'=>'topshop.supplier.expert_comment.update','action'=>'topshop_ctl_supplier_expert_expertComment@update','url'=>'supplier/expert_comment/update.html','method'=>'post'),
            array('label'=>'线下店专家评论删除','display'=>false,'as'=>'topshop.supplier.expert_comment.destroy','action'=>'topshop_ctl_supplier_expert_expertComment@destroy','url'=>'supplier/expert_comment/destroy.html','method'=>'post'),
            array('label'=>'线下店专家评论排序','display'=>false,'as'=>'topshop.supplier.expert_comment.ordersort','action'=>'topshop_ctl_supplier_expert_expertComment@order_sort','url'=>'supplier/expert_comment/order_sort.html','method'=>'post'),
            array('label'=>'线下店简介编辑','display'=>false,'as'=>'topshop.supplier.profile.edit','action'=>'topshop_ctl_supplier_list@agentProfile','url'=>'supplier/profile/edit.html','method'=>'get'),
            array('label'=>'线下店简介提交','display'=>false,'as'=>'topshop.supplier.profile.update','action'=>'topshop_ctl_supplier_list@agentProfileStore','url'=>'supplier/profile/update.html','method'=>'post'),

            array('label'=>'买手推荐','display'=>true,'as'=>'topshop.supplier.buyer.index','action'=>'topshop_ctl_supplier_buyer@index','url'=>'supplier/buyer/index.html','method'=>'get'),
            array('label'=>'买手推荐创建','display'=>false,'as'=>'topshop.supplier.buyer.create','action'=>'topshop_ctl_supplier_buyer@create','url'=>'supplier/buyer/create.html','method'=>'get'),
            array('label'=>'买手推荐编辑','display'=>false,'as'=>'topshop.supplier.buyer.edit','action'=>'topshop_ctl_supplier_buyer@edit','url'=>'supplier/buyer/edit.html','method'=>'get'),
            array('label'=>'买手推荐保存','display'=>false,'as'=>'topshop.supplier.buyer.store','action'=>'topshop_ctl_supplier_buyer@store','url'=>'supplier/buyer/store.html','method'=>'post'),
            array('label'=>'买手推荐更新','display'=>false,'as'=>'topshop.supplier.buyer.update','action'=>'topshop_ctl_supplier_buyer@update','url'=>'supplier/buyer/update.html','method'=>'post'),
            array('label'=>'买手推荐删除','display'=>false,'as'=>'topshop.supplier.buyer.destroy','action'=>'topshop_ctl_supplier_buyer@destroy','url'=>'supplier/buyer/destroy.html','method'=>'post'),

            array('label'=>'买手标签列表','display'=>false,'as'=>'topshop.supplier.buyer.tag.index','action'=>'topshop_ctl_supplier_buyerTag@index','url'=>'supplier/buyer/tag/index.html','method'=>'get'),
            array('label'=>'买手标签创建','display'=>false,'as'=>'topshop.supplier.buyer.tag.create','action'=>'topshop_ctl_supplier_buyerTag@create','url'=>'supplier/buyer/tag/create.html','method'=>'get'),
            array('label'=>'买手标签编辑','display'=>false,'as'=>'topshop.supplier.buyer.tag.edit','action'=>'topshop_ctl_supplier_buyerTag@edit','url'=>'supplier/buyer/tag/edit.html','method'=>'get'),
            array('label'=>'买手标签保存','display'=>false,'as'=>'topshop.supplier.buyer.tag.store','action'=>'topshop_ctl_supplier_buyerTag@store','url'=>'supplier/buyer/tag/store.html','method'=>'post'),
            array('label'=>'买手标签更新','display'=>false,'as'=>'topshop.supplier.buyer.tag.update','action'=>'topshop_ctl_supplier_buyerTag@update','url'=>'supplier/buyer/tag/update.html','method'=>'post'),
            array('label'=>'买手标签删除','display'=>false,'as'=>'topshop.supplier.buyer.tag.destroy','action'=>'topshop_ctl_supplier_buyerTag@destroy','url'=>'supplier/buyer/tag/destroy.html','method'=>'post'),

            array('label'=>'订阅号管理','display'=>true,'as'=>'topshop.public.account.list','action'=>'topshop_ctl_supplier_publicAccount@index','url'=>'supplier/publicaccount/list.html','method'=>'get'),
            array('label'=>'编辑订阅号','display'=>false,'as'=>'topshop.public.account.edit','action'=>'topshop_ctl_supplier_publicAccount@edit','url'=>'supplier/publicaccount/edit.html','method'=>'get'),
            array('label'=>'保存订阅号','display'=>false,'as'=>'topshop.public.account.save','action'=>'topshop_ctl_supplier_publicAccount@save','url'=>'supplier/publicaccount/add.html','method'=>'post'),
            array('label'=>'删除订阅号','display'=>false,'as'=>'topshop.public.account.delete','action'=>'topshop_ctl_supplier_publicAccount@delete','url'=>'supplier/publicaccount/del.html','method'=>'get'),



            /*add_20190329_by_jiangyunhan_start*/
            array('label'=>'评论文章管理','display'=>true,'as'=>'topshop.supplier.comment.list','action'=>'topshop_ctl_supplier_CommentList@index','url'=>'supplier/comment/list.html','method'=>'get'),
            array('label'=>'评论文章搜索','display'=>false,'as'=>'topshop.supplier.comment.search','action'=>'topshop_ctl_supplier_CommentList@commentSearch','url'=>'supplier/comment/search.html','method'=>'get'),
            array('label'=>'评论文章审核','display'=>false,'as'=>'topshop.supplier.comment.setstatus','action'=>'topshop_ctl_supplier_CommentList@setStatus','url'=>'supplier/comment/setstatus.html','method'=>'post'),
            array('label'=>'评论文章排序','display'=>false,'as'=>'topshop.supplier.comment.order','action'=>'topshop_ctl_supplier_CommentList@order_sort','url'=>'supplier/comment/order.html','method'=>'post'),
            array('label'=>'编辑评论文章','display'=>false,'as'=>'topshop.supplier.comment.edit','action'=>'topshop_ctl_supplier_CommentList@edit','url'=>'supplier/comment/edit.html','method'=>'get'),
            array('label'=>'保存评论文章','display'=>false,'as'=>'topshop.supplier.comment.save','action'=>'topshop_ctl_supplier_CommentList@save','url'=>'supplier/comment/add.html','method'=>'post'),
            array('label'=>'删除评论文章','display'=>false,'as'=>'topshop.supplier.comment.delete','action'=>'topshop_ctl_supplier_CommentList@delete','url'=>'supplier/comment/del.html','method'=>'get'),
            /*add_20190329_by_jiangyunhan_end*/

        )
    ),
    /*add_20180516_by_jiangyunhan_end*/

    /*add_20180723_by_jiangyunhan_start*/
    'mini_program'=>array(
        'label'=>'小程序',
        'display'=>'true',
        'action'=>'topshop_ctl_miniprogram_goods@index',
        'icon'=>'fa fa-wechat',
        'menu' => array(
            array('label'=>'小程序商品列表','display'=>true,'as'=>'topshop.miniprogram.goods','action'=>'topshop_ctl_miniprogram_goods@index','url'=>'miniprogram/goods.html','method'=>'get'),
            array('label'=>'小程序商品列表排序','display'=>false,'as'=>'topshop.miniprogram.goods.order','action'=>'topshop_ctl_miniprogram_goods@order_sort','url'=>'miniprogram/goods/order.html','method'=>'post'),
            array('label'=>'编辑小程序商品','display'=>false,'as'=>'topshop.miniprogram.goods.edit','action'=>'topshop_ctl_miniprogram_goods@edit','url'=>'supplier/miniprogram/edit.html','method'=>'get'),
            array('label'=>'保存小程序商品','display'=>false,'as'=>'topshop.miniprogram.goods.save','action'=>'topshop_ctl_miniprogram_goods@save','url'=>'supplier/miniprogram/add.html','method'=>'post'),
            array('label'=>'删除小程序商品','display'=>false,'as'=>'topshop.miniprogram.goods.delete','action'=>'topshop_ctl_miniprogram_goods@delete','url'=>'supplier/miniprogram/del.html','method'=>'get'),
            array('label'=>'小程序商品搜索','display'=>false,'as'=>'topshop.miniprogram.goods.search','action'=>'topshop_ctl_miniprogram_goods@goodSearch','url'=>'supplier/miniprogram/search.html','method'=>'get'),
            array('label'=>'设置商品的显示状态','display'=>false,'as'=>'topshop.miniprogram.goods.setdisabled','action'=>'topshop_ctl_miniprogram_goods@setDisabled','url'=>'supplier/miniprogram/setDisabled.html','method'=>'post'),
            array('label'=>'首页轮播广告图','display'=>true,'as'=>'topshop.miniprogram.indexbanner','action'=>'topshop_ctl_miniprogram_goods@indexBanner','url'=>'miniprogram/banner.html','method'=>['get','post']),

            array('label'=>'概不退款说明','display'=>true,'as'=>'topshop.miniprogram.norefunds','action'=>'topshop_ctl_miniprogram_goods@noRefunds','url'=>'miniprogram/noRefunds.html','method'=>['get','post']),
        )
    ),
    /*add_20180723_by_jiangyunhan_end*/
    'im'=>array(
        'label'=>'客服',
        'display' => true,
        'action' => 'topshop_ctl_im@index',
        'icon' => 'fa fa-comments',
        'menu' => array(
            array('label'=>'在线客服','display'=>true,'as'=>'topshop.im','action'=>'topshop_ctl_im@index','url'=>'im.html','method'=>'get'),
        )
    ),




	/*add_2018/6/19_by_wanghaichao_start
    |--------------------------------------------------------------------------
    | 选货商城相关管理
    |--------------------------------------------------------------------------
     */
	'mall'=>array(
        'label'=>'广电优选',
        'display' => true,
        'action' => 'topshop_ctl_mall_admin_shop@setting',
        'icon' => 'fa fa-shopping-basket',
        'menu' => array(
			array('label'=>'商城入口','display'=>true,'as' => 'topshop.mall.home', 'action'=>'topshop_ctl_mall_home@index','url'=>'mall/home.html','method'=>'get'),
            array('label'=>'店铺发票配置','display'=>true,'as'=>'topshop.mall.admin.invoice.index','action'=>'topshop_ctl_mall_admin_invoice@index','url'=>'mall/admin/shop/invoice.html','method'=>'get'),
            array('label'=>'店铺发票配置保存','display'=>false,'as'=>'topshop.mall.admin.invoice.save','action'=>'topshop_ctl_mall_admin_invoice@save','url'=>'mall/admin/shop/invoice_save.html','method'=>'post'),
			array('label'=>'店铺首页配置','display'=>true,'as'=>'topshop.mall.admin.shop.setting','action'=>'topshop_ctl_mall_admin_shop@setting','url'=>'mall/admin/shop/setting.html','method'=>'get'),
			/*add_2018/11/27_by_wanghaichao_start*/
			array('label'=>'店铺首页配置保存方法','display'=>false,'as'=>'topshop.mall.admin.shop.setting.save','action'=>'topshop_ctl_mall_admin_shop@save','url'=>'mall/admin/shop/save.html','method'=>['get','post']),
			/*add_2018/11/27_by_wanghaichao_end*/

			array('label'=>'拉取商品列表','display'=>true,'as'=>'topshop.mall.admin.list','action'=>'topshop_ctl_mall_admin_list@index','url'=>'mall/admin/list.html','method'=>'get'),
			array('label'=>'商品编辑页','display'=>false,'as'=>'topshop.mall.admin.item.edit','action'=>'topshop_ctl_mall_admin_item@edit','url'=>'mall/admin/item/edit.html','method'=>'get'),
			array('label'=>'商品修改页','display'=>false,'as'=>'topshop.mall.admin.update','action'=>'topshop_ctl_mall_admin_update@index','url'=>'mall/admin/update.html','method'=>'get'),
			array('label'=>'商品修改保存','display'=>false,'as'=>'topshop.mall.admin.update.save','action'=>'topshop_ctl_mall_admin_update@save','url'=>'mall/admin/updateSave.html','method'=>'post'),
            array('label'=>'保存代售商品','display'=>false,'as'=>'topshop.mall.admin.item.save','action'=>'topshop_ctl_mall_admin_item@save','url'=>'mall/admin/item/save.html','method'=>'post'),

            array('label'=>'推送商品列表','display'=>true,'as'=>'topshop.mall.admin.pushlist','action'=>'topshop_ctl_mall_admin_pushlist@index','url'=>'mall/admin/push-list.html','method'=>'get'),
            array('label'=>'商品搜索','display'=>false,'as'=>'topshop.select.search','action'=>'topshop_ctl_select@searchItem','url'=>'select-search.html','method'=>'post'),
			// 编辑代售商品页面 @auth:xinyufeng
            array('label'=>'编辑代售商品','display'=>false,'as'=>'topshop.item.edit.agent','action'=>'topshop_ctl_item@edit_agent','url'=>'item/edit-agent.html','method'=>'get'),

			/*add_2018/7/3_by_wanghaichao_start*/
            array('label'=>'本店铺推送商品结算明细','display'=>true,'as'=>'topshop.b2b.settlement.bill.confirm','action'=>'topshop_ctl_clearing_settlement@collectionDetail','url'=>'collection_billconfirm.html','method'=>'get'),
            array('label'=>'本店铺拉取商品结算明细','display'=>true,'as'=>'topshop.b2b.settlement.pull.bill.confirm','action'=>'topshop_ctl_clearing_settlement@pullCollectionDetail','url'=>'pull_billconfirm.html','method'=>'get'),
			/*add_2018/7/3_by_wanghaichao_end*/

			/*add_2018/6/26_by_wanghaichao_end*/
            array('label'=>'创客佣金设置','display'=>true,'as'=>'topshop.setting.seller.commission','action'=>'topshop_ctl_shop_setting@setCommission','url'=>'setCommission.html','method'=>'get'),
			/*add_2018/11/15_by_wanghaichao_start*/
			/*add_2019/7/31_by_wanghaichao_start*/
			array('label'=>'创客二维码背景设置','display'=>true,'as'=>'topshop.setting.seller.bgqr','action'=>'topshop_ctl_shop_setting@bgqr','url'=>'bgqr.html','method'=>'get'),
			array('label'=>'二维码背景图下载','display'=>false,'as'=>'topshop.setting.seller.download','action'=>'topshop_ctl_shop_setting@download','url'=>'maker/download.html','method'=>'get'),

            array('label'=>'创客结算明细(以核销时间进行结算)','display'=>true,'as'=>'topshop.settlement.ticket.bill.seller.confirm','action'=>'topshop_ctl_clearing_settlement@ticketSellerBill','url'=>'ticketseller_billconfirm.html','method'=>'get'),
			/*add_2019/7/31_by_wanghaichao_end*/
			
			/*add_2018/6/26_by_wanghaichao_start*/
			//创客的商品结算
            array('label'=>'创客结算明细','display'=>true,'as'=>'topshop.settlement.bill.seller.confirm','action'=>'topshop_ctl_clearing_settlement@sellerDetail','url'=>'settlement_seller_billconfirm.html','method'=>'get'),
            array('label'=>'创客佣金汇总','display'=>true,'as'=>'topshop.settlement.bill.seller.summary','action'=>'topshop_ctl_clearing_settlement@summary','url'=>'settlement_seller_summary.html','method'=>'get'),
            array('label'=>'佣金结算功能','display'=>false,'as'=>'topshop.settlement.bill.seller.settlement','action'=>'topshop_ctl_clearing_settlement@settlement','url'=>'settlement_seller_settlement.html','method'=>'post'),
            array('label'=>'创客提现列表','display'=>true,'as'=>'topshop.maker.cash','action'=>'topshop_ctl_mall_maker@index','url'=>'maker.html','method'=>'get'),
			//创客提现明细
			array('label'=>'创客列表','display'=>true,'as'=>'topshop.maker.list','action'=>'topshop_ctl_mall_maker@mlist','url'=>'maker/list.html','method'=>'get'),


            array('label'=>'添加创客提现','display'=>false,'as'=>'topshop.maker.cash.add','action'=>'topshop_ctl_mall_maker@cash','url'=>'cashAdd.html','method'=>'get'),
			array('label'=>'保存创客提现','display'=>false,'as'=>'topshop.maker.cash.save','action'=>'topshop_ctl_mall_maker@save','url'=>'cashSave.html','method'=>['get','post']),
			/*add_2019/8/1_by_wanghaichao_start*/
			//审核申请提现的
			array('label'=>'提现审核','display'=>false,'as'=>'topshop.maker.cash.audit','action'=>'topshop_ctl_mall_maker@cashAudit','url'=>'cashAudit.html','method'=>['get','post']),
			/*add_2019/8/1_by_wanghaichao_end*/
			

			array('label'=>'创客审核','display'=>false,'as'=>'topshop.maker.audit','action'=>'topshop_ctl_mall_maker@audit','url'=>'audit.html','method'=>['get','post']),

			array('label'=>'创客删除','display'=>false,'as'=>'topshop.maker.delete','action'=>'topshop_ctl_mall_maker@mdelete','url'=>'makerdelete.html','method'=>['get','post']),

			/*add_2018/11/15_by_wanghaichao_end*/
			/*add_2019/7/31_by_wanghaichao_start*/
			array('label'=>'创客二维码下载','display'=>false,'as'=>'topshop.maker.qrcode','action'=>'topshop_ctl_mall_maker@qrcode','url'=>'qrcode.html','method'=>['get','post']),
			/*add_2019/7/31_by_wanghaichao_end*/
			/*add_2019/8/26_by_wanghaichao_start*/
			
			array('label'=>'创客详情','display'=>false,'as'=>'topshop.maker.detail','action'=>'topshop_ctl_mall_maker@detail','url'=>'maker/detail.html','method'=>['get','post']),
			/*add_2019/8/26_by_wanghaichao_end*/
			/*add_2019/9/2_by_wanghaichao_start*/
			#协会相关的
			array('label'=>'协会列表','display'=>true,'as'=>'topshop.maker.group.index','action'=>'topshop_ctl_mall_group@index','url'=>'group/index.html','method'=>['get','post']),

			array('label'=>'添加协会','display'=>false,'as'=>'topshop.maker.group.create','action'=>'topshop_ctl_mall_group@create','url'=>'group/create.html','method'=>['get','post']),

			array('label'=>'保存协会','display'=>false,'as'=>'topshop.maker.group.save','action'=>'topshop_ctl_mall_group@save','url'=>'group/save.html','method'=>['get','post']),
			/*add__by_wanghaichao_end*/
        )
	),
	/*add_2018/6/19_by_wanghaichao_end*/


    /*add_20171012_by_xinyufeng_start 礼品活动菜单*/
    'activity'=>array(
        'label'=>'活动',
        'display' => true,
        'action' => 'topshop_ctl_activity_vote@index',
        'icon' => 'fa fa-tasks',
        'menu' => array(
            array('label'=>'礼品活动管理','display'=>true,'as'=>'topshop.activity.vote','action'=>'topshop_ctl_activity_vote@index','url'=>'activity/vote.html','method'=>'get'),
            array('label'=>'添加/编辑礼品活动','display'=>false,'as'=>'topshop.activity.vote.edit','action'=>'topshop_ctl_activity_vote@edit_vote','url'=>'activity/edit_vote.html','method'=>'get'),
            array('label'=>'保存礼品活动','display'=>false,'as'=>'topshop.activity.vote.save','action'=>'topshop_ctl_activity_vote@save_vote','url'=>'activity/save_vote.html','method'=>'post'),
            array('label'=>'查看礼品活动','display'=>false,'as'=>'topshop.activity.vote.show','action'=>'topshop_ctl_activity_vote@show_vote','url'=>'activity/show_vote.html','method'=>'get'),
            array('label'=>'删除礼品活动','display'=>false,'as'=>'topshop.activity.vote.delete','action'=>'topshop_ctl_activity_vote@delete_vote','url'=>'activity/delete_vote.html','method'=>'post'),
            array('label'=>'礼品活动分类管理','display'=>false,'as'=>'topshop.activity.cat','action'=>'topshop_ctl_activity_cat@index','url'=>'activity/cat.html','method'=>'get'),
            array('label'=>'添加/编辑礼品活动分类','display'=>false,'as'=>'topshop.activity.cat.edit','action'=>'topshop_ctl_activity_cat@edit_cat','url'=>'activity/edit_cat.html','method'=>'get'),
            array('label'=>'保存礼品活动分类','display'=>false,'as'=>'topshop.activity.cat.save','action'=>'topshop_ctl_activity_cat@save_cat','url'=>'activity/save_cat.html','method'=>'post'),
            array('label'=>'删除礼品活动分类','display'=>false,'as'=>'topshop.activity.cat.delete','action'=>'topshop_ctl_activity_cat@delete_cat','url'=>'activity/delete_cat.html','method'=>'post'),
            array('label'=>'参赛信息管理','display'=>false,'as'=>'topshop.activity.game','action'=>'topshop_ctl_activity_game@index','url'=>'activity/game.html','method'=>'get'),
            array('label'=>'添加/编辑参赛信息','display'=>false,'as'=>'topshop.activity.game.edit','action'=>'topshop_ctl_activity_game@edit_game','url'=>'activity/edit_game.html','method'=>'get'),
            array('label'=>'保存参赛信息','display'=>false,'as'=>'topshop.activity.game.save','action'=>'topshop_ctl_activity_game@save_game','url'=>'activity/save_game.html','method'=>'post'),
            array('label'=>'删除参赛信息','display'=>false,'as'=>'topshop.activity.game.delete','action'=>'topshop_ctl_activity_game@delete_game','url'=>'activity/delete_game.html','method'=>'post'),
            array('label'=>'编辑参赛详情','display'=>false,'as'=>'topshop.activity.game.detail.edit','action'=>'topshop_ctl_activity_game_detail@edit_game_detail','url'=>'activity/edit_game_detail.html','method'=>'get'),
            array('label'=>'保存参赛详情','display'=>false,'as'=>'topshop.activity.game.detail.save','action'=>'topshop_ctl_activity_game_detail@save_game_detail','url'=>'activity/save_game_detail.html','method'=>'post'),
            array('label'=>'赠品管理','display'=>false,'as'=>'topshop.activity.gift','action'=>'topshop_ctl_activity_gift@index','url'=>'activity/gift.html','method'=>'get'),
            array('label'=>'添加/编辑赠品','display'=>false,'as'=>'topshop.activity.gift.edit','action'=>'topshop_ctl_activity_gift@edit_gift','url'=>'activity/edit_gift.html','method'=>'get'),
            array('label'=>'保存赠品','display'=>false,'as'=>'topshop.activity.gift.save','action'=>'topshop_ctl_activity_gift@save_gift','url'=>'activity/save_gift.html','method'=>'post'),
            array('label'=>'删除赠品','display'=>false,'as'=>'topshop.activity.gift.delete','action'=>'topshop_ctl_activity_gift@delete_gift','url'=>'activity/delete_gift.html','method'=>'post'),

            array('label'=>'专家管理','display'=>false,'as'=>'topshop.activity.vote.expert.list','action'=>'topshop_ctl_activity_vote@expertList','url'=>'activity/vote/expert_list.html','method'=>'get'),

            array('label'=>'专家编辑','display'=>false,'as'=>'topshop.activity.vote.expert.edit','action'=>'topshop_ctl_activity_vote@expertEdit','url'=>'activity/vote/expert_edit.html','method'=>'get'),

            array('label'=>'专家保存','display'=>false,'as'=>'topshop.activity.vote.expert.save','action'=>'topshop_ctl_activity_vote@expertSave','url'=>'activity/vote/expert_save.html','method'=>'post'),

            array('label'=>'专家删除','display'=>false,'as'=>'topshop.activity.vote.expert.delete','action'=>'topshop_ctl_activity_vote@expertDelete','url'=>'activity/vote/expert_delete.html','method'=>'post'),

            array('label'=>'获得赠品记录','display'=>false,'as'=>'topshop.activity.vote.gift.gain.list','action'=>'topshop_ctl_activity_gift@giftGainList','url'=>'activity/vote/gift/gain_list.html','method'=>'get'),

            array('label'=>'奖品说明','display'=>false,'as'=>'topshop.activity.vote.gift.gain.explain','action'=>'topshop_ctl_activity_gift@giftGainExplain','url'=>'activity/vote/gift/gain_explain.html','method'=>'get'),

            array('label'=>'奖品编辑提交','display'=>false,'as'=>'topshop.activity.vote.gift.explain.save','action'=>'topshop_ctl_activity_gift@giftExplainEdit','url'=>'activity/vote/gift/explain_save.html','method'=>'post'),

            array('label'=>'获得赠品记录删除','display'=>false,'as'=>'topshop.activity.vote.gift.gain.list','action'=>'topshop_ctl_activity_gift@giftGainDelete','url'=>'activity/vote/gift/gain_delete.html','method'=>'post'),

            array('label'=>'专家点评','display'=>false,'as'=>'topshop.activity.vote.expert.comment.list','action'=>'topshop_ctl_activity_expert_comment@commentList','url'=>'activity/vote/expert/comment_list.html','method'=>'get'),

            array('label'=>'添加专家点评','display'=>false,'as'=>'topshop.activity.vote.expert.comment.edit','action'=>'topshop_ctl_activity_expert_comment@commentEdit','url'=>'activity/vote/expert/comment_edit.html','method'=>'get'),

            array('label'=>'保存专家点评','display'=>false,'as'=>'topshop.activity.vote.expert.comment.save','action'=>'topshop_ctl_activity_expert_comment@commentSave','url'=>'activity/vote/expert/comment_save.html','method'=>'post'),

            array('label'=>'删除专家点评','display'=>false,'as'=>'topshop.activity.vote.expert.comment.delete','action'=>'topshop_ctl_activity_expert_comment@commentDelete','url'=>'activity/vote/expert/comment_delete.html','method'=>'post'),

            array('label'=>'礼品活动日志列表','display'=>false,'as'=>'topshop.activity.vote.log.list','action'=>'topshop_ctl_activity_vote@voteLogList','url'=>'activity/vote/log_list.html','method'=>'get'),


            array('label'=>'点亮图标','display'=>true,'as'=>'topshop.lighticon.activity.list','action'=>'topshop_ctl_lighticon_activity@index','url'=>'lighticon/activity/list.html','method'=>'get'),
            array('label'=>'添加活动','display'=>false,'as'=>'topshop.lighticon.activity.edit','action'=>'topshop_ctl_lighticon_activity@activity_edit','url'=>'lighticon/activity/edit.html','method'=>'get'),
            array('label'=>'保存活动','display'=>false,'as'=>'topshop.lighticon.activity.save','action'=>'topshop_ctl_lighticon_activity@activity_save','url'=>'lighticon/activity/save.html','method'=>'post'),
            array('label'=>'删除活动','display'=>false,'as'=>'topshop.lighticon.activity.delete','action'=>'topshop_ctl_lighticon_activity@activityStatus','url'=>'lighticon/activity/delete.html','method'=>'post'),

            array('label'=>'图标列表','display'=>false,'as'=>'topshop.lighticon.operand.list','action'=>'topshop_ctl_lighticon_operand@operandList','url'=>'lighticon/operand/list.html','method'=>'get'),
            array('label'=>'编辑图标','display'=>false,'as'=>'topshop.lighticon.operand.edit','action'=>'topshop_ctl_lighticon_operand@operandEdit','url'=>'lighticon/operand/edit.html','method'=>'get'),
            array('label'=>'保存图标','display'=>false,'as'=>'topshop.lighticon.operand.save','action'=>'topshop_ctl_lighticon_operand@operandSave','url'=>'lighticon/operand/save.html','method'=>'post'),
            array('label'=>'删除专家点评','display'=>false,'as'=>'topshop.lighticon.operand.status','action'=>'topshop_ctl_lighticon_operand@operandStatus','url'=>'lighticon/operand/status.html','method'=>'post'),

            array('label'=>'参赛会员列表','display'=>false,'as'=>'topshop.lighticon.participant.list','action'=>'topshop_ctl_lighticon_participant@participantList','url'=>'lighticon/participant/list.html','method'=>'get'),
            array('label'=>'删除参与会员','display'=>false,'as'=>'topshop.lighticon.participant.status','action'=>'topshop_ctl_lighticon_participant@participantStatus','url'=>'lighticon/participant/status.html','method'=>'post'),

            array('label'=>'奖品发货界面','display'=>false,'as'=>'topshop.lighticon.participant.shipping','action'=>'topshop_ctl_lighticon_participant@shippingEdit','url'=>'lighticon/shipping/edit.html','method'=>'get'),

            array('label'=>'奖品发货保存','display'=>false,'as'=>'topshop.lighticon.participant.shipping.save','action'=>'topshop_ctl_lighticon_participant@shippingSave','url'=>'lighticon/shipping/save.html','method'=>'post'),

            array('label'=>'奖品编辑','display'=>false,'as'=>'topshop.lighticon.gift.edit','action'=>'topshop_ctl_lighticon_gift@giftEdit','url'=>'lighticon/gift/edit.html','method'=>'get'),
            array('label'=>'保存奖品','display'=>false,'as'=>'topshop.lighticon.gift.save','action'=>'topshop_ctl_lighticon_gift@giftSave','url'=>'lighticon/gift/save.html','method'=>'post'),


            array('label'=>'点亮日志列表','display'=>false,'as'=>'topshop.lighticon.lightlog.list','action'=>'topshop_ctl_lighticon_lightlog@lightlogList','url'=>'lighticon/lightlog/list.html','method'=>'get'),
        )
    ),
    /*add_20171012_by_xinyufeng_end*/
	/*add_2018/7/17_by_wanghaichao_start*/

    'live' => array(
        'label' => '直播',
        'display' => true,
        'action' => 'topshop_ctl_live_live@index',
        'icon' => 'glyphicon glyphicon-list-alt',
        'menu' => array(
			/*频道管理开始*/
			array('label'=>'频道列表','display'=>true,'as'=>'topshop.live.channel.index','action'=>'topshop_ctl_live_channel@index','url'=>'live/channel/index.html','method'=>'get'),
			
			array('label'=>'添加/编辑频道','display'=>false,'as'=>'topshop.live.channel.edit','action'=>'topshop_ctl_live_channel@edit','url'=>'live/channel/edit.html','method'=>'get'),
			
			array('label'=>'保存频道','display'=>false,'as'=>'topshop.live.channel.save','action'=>'topshop_ctl_live_channel@save','url'=>'live/channel/save.html','method'=>'post'),
			
			array('label'=>'删除频道','display'=>false,'as'=>'topshop.live.channel.delete','action'=>'topshop_ctl_live_channel@delete','url'=>'live/channel/delete.html','method'=>['get']),
			
			array('label'=>'更改频道启用状态','display'=>false,'as'=>'topshop.live.channel.update.disabled','action'=>'topshop_ctl_live_channel@updateDisabled','url'=>'live/update/channel/disabled.html','method'=>'post'),
			/*频道管理结束*/
			
            array('label'=>'直播列表','display'=>true,'as'=>'topshop.live.index','action'=>'topshop_ctl_live_live@index','url'=>'live/live.html','method'=>'get'),
			
			array('label'=>'年周总数','display'=>false,'as'=>'topshop.live.week.info','action'=>'topshop_ctl_live_live@weekInfo','url'=>'live/week-info.html','method'=>'get'),
			
			array('label'=>'排版列表','display'=>false,'as'=>'topshop.live.week.schedule','action'=>'topshop_ctl_live_live@weekSchedule','url'=>'live/week-schedule.html','method'=>'get'),
			
			array('label'=>'复制排班','display'=>false,'as'=>'topshop.live.copy.schedule','action'=>'topshop_ctl_live_live@copySchedule','url'=>'live/copy-schedule.html','method'=>'post'),

            array('label'=>'添加/编辑直播','display'=>false,'as'=>'topshop.live.edit','action'=>'topshop_ctl_live_live@edit','url'=>'live/edit.html','method'=>'get'),

            array('label'=>'保存直播','display'=>false,'as'=>'topshop.live.save','action'=>'topshop_ctl_live_live@save','url'=>'live/save.html','method'=>['get', 'post']),

            array('label'=>'更改直播状态','display'=>false,'as'=>'topshop.live.update.status','action'=>'topshop_ctl_live_live@updateStatus','url'=>'live/update/status.html','method'=>['post']),

            array('label'=>'删除直播','display'=>false,'as'=>'topshop.live.delete','action'=>'topshop_ctl_live_live@delete','url'=>'live/delete.html','method'=>['get']),

            array('label'=>'主播列表','display'=>true,'as'=>'topshop.live.compere.index','action'=>'topshop_ctl_live_compere@index','url'=>'live/compere.html','method'=>'get'),

            array('label'=>'添加主播','display'=>false,'as'=>'topshop.live.compere.edit','action'=>'topshop_ctl_live_compere@edit','url'=>'live/compere_edit.html','method'=>'get'),

            array('label'=>'主播推荐商品','display'=>false,'as'=>'topshop.live.compere.edit','action'=>'topshop_ctl_live_compere@item','url'=>'live/compere_item.html','method'=>'get'),

            array('label'=>'保存主播信息','display'=>false,'as'=>'topshop.live.compere.save','action'=>'topshop_ctl_live_compere@save','url'=>'live/compere_save.html','method'=>['get','post']),

            array('label'=>'添加主播推荐商品','display'=>false,'as'=>'topshop.live.compere.item.add','action'=>'topshop_ctl_live_compere@item_add','url'=>'live/compere_item_add.html','method'=>['get','post']),

            array('label'=>'删除主播','display'=>false,'as'=>'topshop.live.compere.delete','action'=>'topshop_ctl_live_compere@deletecompere','url'=>'live/compere_delete.html','method'=>['get','post']),

            array('label'=>'更改主播排序','display'=>false,'as'=>'topshop.live.compere.updatesort','action'=>'topshop_ctl_live_compere@compereSort','url'=>'live/compere_updatesort.html','method'=>['get','post']),

			/*add_by_xinyufeng_2018-08-01_start*/
			// 电视购物
            ['label'=>'电视购物wap端装修','display'=>false,'as'=>'topshop.tvshopping.wapdecorate.index','action'=>'topshop_ctl_tvshopping_wapdecorate@index','url'=>'tvshopping/wapdecorate/index.html','method'=>'get'],
			/*add_2018/11/23_by_wanghaichao_start*/
			//页面修改
            ['label'=>'电视购物wap端装修页面修改','display'=>false,'as'=>'topshop.tvshopping.wapdecorate.pageEdit','action'=>'topshop_ctl_tvshopping_wapdecorate@pageEdit','url'=>'tvshopping/wapdecorate/pageEdit.html','method'=>'get'],
			/*add_2018/11/23_by_wanghaichao_end*/

            ['label'=>'电视购物wap端装修','display'=>true,'as'=>'topshop.tvshopping.wapdecorate.pagelist','action'=>'topshop_ctl_tvshopping_wapdecorate@pagelist','url'=>'tvshopping/wapdecorate/pagelist.html','method'=>'get'],

			['label'=>'电视购物wap端装修编辑','display'=>false,'as'=>'topshop.tvshopping.wapdecorate.edit','action'=>'topshop_ctl_tvshopping_wapdecorate@edit','url'=>'tvshopping/wapdecorate/edit.html','method'=>'get'],
			['label'=>'电视购物wap端装修保存','display'=>false,'as'=>'topshop.tvshopping.wapdecorate.save','action'=>'topshop_ctl_tvshopping_wapdecorate@save','url'=>'tvshopping/wapdecorate/save.html','method'=>'post'],
			['label'=>'电视购物wap端装修删除','display'=>false,'as'=>'topshop.tvshopping.wapdecorate.delete','action'=>'topshop_ctl_tvshopping_wapdecorate@delete','url'=>'tvshopping/wapdecorate/delete.html','method'=>'post'],
			['label'=>'电视购物wap端装修排序','display'=>false,'as'=>'topshop.tvshopping.wapdecorate.sortOpt','action'=>'topshop_ctl_tvshopping_wapdecorate@sortOpt','url'=>'tvshopping/wapdecorate/sortOpt.html','method'=>'post'],
			['label'=>'电视购物wap端装修状态','display'=>false,'as'=>'topshop.tvshopping.wapdecorate.setStatus','action'=>'topshop_ctl_tvshopping_wapdecorate@setStatus','url'=>'tvshopping/wapdecorate/setStatus.html','method'=>'post'],
			/*add_by_xinyufeng_2018-08-01_end*/
			/*add_2018/11/21_by_wanghaichao_start*/
			//保存页面类型
			['label'=>'电视购物wap端装修状态','display'=>false,'as'=>'topshop.tvshopping.wapdecorate.savePageType','action'=>'topshop_ctl_tvshopping_wapdecorate@savePageType','url'=>'tvshopping/wapdecorate/savePageType.html','method'=>'post'],
			['label'=>'电视购物wap端装修','display'=>false,'as'=>'topshop.tvshopping.wapdecorate.deletePage','action'=>'topshop_ctl_tvshopping_wapdecorate@deletePage','url'=>'tvshopping/wapdecorate/deletePage.html','method'=>'post'],

			['label'=>'电视购物wap端装修页面修改','display'=>false,'as'=>'topshop.tvshopping.wapdecorate.updatePage','action'=>'topshop_ctl_tvshopping_wapdecorate@updatePage','url'=>'tvshopping/wapdecorate/updatePage.html','method'=>'post'],
			/*add_2018/11/21_by_wanghaichao_end*/

		),
	),
	/*add_2018/7/17_by_wanghaichao_end*/

	/*add_2017/12/29_by_wanghaichao_start*/
	//动力传媒 租赁
    'cart' => array(
        'label' => '汽车租赁管理',
        'display' => true,
        'action' => 'topshop_ctl_syscart_syscart@companylist',
        'icon' => 'glyphicon glyphicon-list-alt',
        'menu' => array(
           // array('label'=>'汽车租赁概况','display'=>true,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_syscart@index','url'=>'syscart/syscart.html','method'=>'get'),
			array('label'=>'公司管理','display'=>true,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_syscart@companylist','url'=>'syscart/company.html','method'=>'get'),

            array('label'=>'车主管理','display'=>true,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_syscart@ownerlist','url'=>'syscart/ownerlist.html','method'=>'get'),

            array('label'=>'销售经理管理','display'=>true,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_syscart@sale','url'=>'syscart/sale.html','method'=>'get'),

            array('label'=>'车辆管理','display'=>true,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_syscart@cart','url'=>'syscart/cart.html','method'=>'get'),
            array('label'=>'快捷租车','display'=>true,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_lease@leaseCart','url'=>'syscart/leaseCart.html','method'=>'get'),
            array('label'=>'押金管理','display'=>true,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_syscart@depositlist','url'=>'syscart/depositlist.html','method'=>'get'),
            array('label'=>'当月还款查询','display'=>true,'as'=>'topshop.stages','action'=>'topshop_ctl_syscart_lease@index','url'=>'syscart/same_month.html','method'=>['get','post']),
			array('label'=>'租赁查询','display'=>true,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_syscart@leaselist','url'=>'syscart/list.html','method'=>'get'),
            array('label'=>'流水查询','display'=>true,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_syscart@flowlist','url'=>'syscart/flow.html','method'=>'get'),
			array('label'=>'设置','display'=>true,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_lease@setting','url'=>'syscart/setting.html','method'=>'get'),
            array('label'=>'增加车主信息','display'=>false,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_syscart@ownerAdd','url'=>'syscart/owneradd.html','method'=>['get','post']),
			array('label'=>'增加销售经理','display'=>false,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_syscart@saleAdd','url'=>'syscart/saleadd.html','method'=>['get','post']),

			array('label'=>'滞纳金设置','display'=>false,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_lease@savesetting','url'=>'syscart/savesetting.html','method'=>['get','post']),
            array('label'=>'期数列表','display'=>false,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_syscart@stageslist','url'=>'syscart/stageslist.html','method'=>'get'),
            array('label'=>'增加租赁信息','display'=>false,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_syscart@leaseAdd','url'=>'syscart/leaseadd.html','method'=>['get','post']),
            array('label'=>'增加公司信息','display'=>false,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_syscart@companyAdd','url'=>'syscart/companyadd.html','method'=>['get','post']),

            array('label'=>'换租页面','display'=>false,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_lease@forrent','url'=>'syscart/forrent.html','method'=>['get','post']),

            array('label'=>'换租页面','display'=>false,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_lease@saveRent','url'=>'syscart/saverent.html','method'=>['get','post']),

            array('label'=>'保存租赁数据','display'=>false,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_syscart@leaseSave','url'=>'syscart/leaseSave.html','method'=>['get','post']),
            array('label'=>'押金','display'=>false,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_syscart@depositAdd','url'=>'syscart/depositadd.html','method'=>['get','post']),
            array('label'=>'押金','display'=>false,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_syscart@depositlist','url'=>'syscart/depositlist.html','method'=>['get','post']),
			array('label'=>'删除公司','display'=>false,'as'=>'topshop.syscart.del','action'=>'topshop_ctl_syscart_syscart@companyDel','url'=>'syscart/companydel.html','method'=>['get','post']),

			array('label'=>'删除车主','display'=>false,'as'=>'topshop.syscart.del','action'=>'topshop_ctl_syscart_syscart@deleteOwner','url'=>'syscart/ownerdel.html','method'=>['get','post']),

			array('label'=>'删除车辆','display'=>false,'as'=>'topshop.syscart.del','action'=>'topshop_ctl_syscart_syscart@cartDel','url'=>'syscart/cartdel.html','method'=>['get','post']),

			array('label'=>'删除销售经理','display'=>false,'as'=>'topshop.syscart.del','action'=>'topshop_ctl_syscart_syscart@saleDel','url'=>'syscart/saledel.html','method'=>['get','post']),

            array('label'=>'增加车辆信息','display'=>false,'as'=>'topshop.syscart','action'=>'topshop_ctl_syscart_syscart@cartAdd','url'=>'syscart/cartadd.html','method'=>['get','post']),
			//ajax 模态框
            array('label'=>'ajax请求交纳本期款项模态框','display'=>false,'as'=>'topshop.stages.payment','action'=>'topshop_ctl_syscart_modal_stages@ajaxPayment','url'=>'ajaxpayment.html','method'=>'get'),

			//topshop_ctl_syscart_modal_stages@payment
            array('label'=>'ajax请求交纳本期款项','display'=>false,'as'=>'topshop.stages.payment','action'=>'topshop_ctl_syscart_modal_stages@payment','url'=>'pay-payment.html','method'=>['get','post']),
 		//导出汽车租赁报表
            array('label'=>'汽车租赁excel导出','display'=>false,'as'=>'topshop.lease.export','action'=>'topshop_ctl_syscart_syscart@exportToExcel','url'=>'lease-export.html','method'=>['get','post']),
			/*add_2018/1/19_by_wanghaichao_start*/
            array('label'=>'流水查询导出','display'=>false,'as'=>'topshop.flow.export','action'=>'topshop_ctl_syscart_syscart@exportFlowExcel','url'=>'flow-export.html','method'=>['get','post']),
			/*add_2018/1/19_by_wanghaichao_end*/
			//停租模态框
            array('label'=>'ajax停租','display'=>false,'as'=>'topshop.stages.payment','action'=>'topshop_ctl_syscart_modal_stages@ajaxStopLease','url'=>'ajaxStopLease.html','method'=>'get'),
			//处理停租的逻辑
            array('label'=>'ajax请求停租','display'=>false,'as'=>'topshop.stages.payment','action'=>'topshop_ctl_syscart_modal_stages@stopLease','url'=>'stoplease.html','method'=>['get','post']),
        )
    ),
    /*add_2017/12/29_by_wanghaichao_end*/

    'jinwanda'=>array(
        'label'=>'金万达',
        'display' => true,
        'action' => 'topshop_ctl_jwd@accountBalance',
        'icon' => 'fa fa-institution',
        'menu' => array(
            array('label'=>'核对账单','display'=>true,'as'=>'topshop.jinwanda.account.balance','action'=>'topshop_ctl_jwd@accountBalance','url'=>'jinwanda/account_balance.html','method'=>'get'),
            array('label'=>'导入视图','display'=>false,'as'=>'topshop.jinwanda.balance.view','action'=>'topshop_ctl_jwd@view','url'=>'jinwanda/balance_view.html','method'=>'get'),
            array('label'=>'导出','display'=>false,'as'=>'topshop.jinwanda.export','action'=>'topshop_ctl_jwd@export','url'=>'jinwanda/export.html','method'=>'get'),
            array('label'=>'下载模板文件','display'=>false,'as'=>'topshop.jinwanda.template.download','action'=>'topshop_ctl_jwd@downLoadCsvTpl','url'=>'jinwanda/tpl_download.html','method'=>'get'),
            array('label'=>'上传文件','display'=>false,'as'=>'topshop.jinwanda.file.upload','action'=>'topshop_ctl_jwd@uploadCsvFile','url'=>'jinwanda/file_upload.html','method'=>'post'),
            array('label'=>'对账','display'=>false,'as'=>'topshop.jinwanda.bill.account','action'=>'topshop_ctl_jwd@bill_account','url'=>'jinwanda/bill_account.html','method'=>'post'),
            array('label'=>'同步剩余数量','display'=>false,'as'=>'topshop.jinwanda.sync.number','action'=>'topshop_ctl_jwd@syncSuplusNumber','url'=>'jinwanda/sync_suplus_number.html','method'=>'get'),
        )
    )

);

