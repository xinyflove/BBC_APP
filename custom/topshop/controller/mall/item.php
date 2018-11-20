<?php
/*
 * Date: 2018-6-21 14:16:53
 * Author: 王衍生
 * authorEmail: 50634235@qq.com
 * company: 青岛广电电商
 */

/**
 * @brief 商家商品管理
 */
class topshop_ctl_mall_item extends topshop_controller {

    public $limit = 10;
    public $exportLimit = 100;

    /**
     * 推送商品到广电优选
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function pushItem()
    {
        $postData = input::get();
        // return $this->splash('success', '', $postData['item_id'], true);
        try
        {
            // 检查参数
            $this->_checkPost($postData);
            $postData['shop_id'] = $this->shopId;
            // 格式化参数
            // $postData = $this->_formatItemData($postData);
            $result = app::get('topshop')->rpcCall('mall.item.push',$postData);
            if($result)
            {
                $this->sellerlog('推送商品' . $postData['item_id'] . '到广电优选！');
                $msg = app::get('topshop')->_('推送成功');
                return $this->splash('success', '', $msg, true);
            }
        }
        catch (Exception $e)
        {
            return $this->splash('error', '', $e->getMessage(), true);
        }
    }

    /**
     * 从广电优选回撤商品
     * @return string
     * @auth:xinyufeng
     */
    public function deleteItem()
    {
        $postData = input::get();
        try
        {
            // 检查参数
            $this->_checkPost($postData);

            $postData['shop_id'] = $this->shopId;
            $result = app::get('topshop')->rpcCall('mall.item.delete',$postData);
            if($result)
            {
                $this->sellerlog('从广电优选回撤商品' . $postData['item_id']);
                $msg = app::get('topshop')->_('回撤成功');
                return $this->splash('success', '', $msg, true);
            }
        }
        catch (Exception $e)
        {
            return $this->splash('error', '', $e->getMessage(), true);
        }
    }

    /**
     * 从广电优选拉取商品
     * @return string
     */
    public function pullItem()
    {
        $params['item_id'] = input::get('item_id');
        $params['shop_id'] = $this->shopId;
        $params['seller_id'] = $this->sellerId;
        $params['is_compere'] = $this->sellerInfo['is_compere'];

        try
        {
            if(empty($params['item_id']))
            {
                $msg = app::get('topshop')->_('请至少选择一个商品！');
                return $this->splash('error',null,$msg, true);
            }

            //判断商品是否上架,没有上架的则不能拉取
            $status = app::get('sysitem')->model('item_status')->getRow('approve_status',array('item_id'=>$params['item_id']));

            if($status['approve_status'] != 'onsale')
            {
                $msg = app::get('topshop')->_('商品没有上架,暂时不能拉取');
                return $this->splash('error',null,$msg,true);
            }

            app::get('topshop')->rpcCall('mall.item.pull',$params);
        }
        catch(Exception $e)
        {
            return $this->splash('error',null, $e->getMessage(), true);
        }
        
        return $this->splash('success',null,'选货成功!', true);
    }

    /**
     * 更新代售商品
     * @return string
     * @auth xinyufeng
     * @time 2018-07-05
     */
    public function updateItem(){
        $params['item_id']=input::get('item_id');
        $params['shop_id']=$this->shopId;

        try
        {
            $res = app::get('topshop')->rpcCall('mall.item.update',$params);
            if($res)
            {
                $url = input::get('return_to_url') ? : url::action('topshop_ctl_item@itemList');
                return $this->splash('success',$url,'更新成功', true);
            }
        }
        catch(Exception $e)
        {
            return $this->splash('error',null, $e->getMessage(), true);
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $postData
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    protected function _checkPost($postData)
    {
        if(empty($postData['item_id'])){
            throw new LogicException("参数错误！");
        }
    }
}
