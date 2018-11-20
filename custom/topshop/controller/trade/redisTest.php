<?php
class topshop_ctl_trade_redisTest {

    /**
     * 测试测试服务器的redis冻结库存操作
     */
    public function testRedis()
    {
        $arrParams['item_id'] = 253;
        $arrParams['sku_id'] = 797;
        $arrParams['quantity'] = 2;
        kernel::single('sysitem_trade_store')->freezeItemStore($arrParams);
        kernel::single('sysitem_trade_store')->unfreezeItemStore($arrParams);
    }
}
