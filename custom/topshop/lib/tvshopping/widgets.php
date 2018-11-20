<?php
/**
 * 电视购物挂件定义文件
 * @auth: xinyufeng
 */

class topshop_tvshopping_widgets {

    private $widgetsMdl;

    /**
     * 挂件信息变量
     * @var array
     */
    public $widgets = array(
        'wap' => array(
            'shopsearch' => array(
                'widget_title' => '搜索框',
                'widget_desc' => '',
            ),
            'todayLive' => array(
                'widget_title' => '今日直播',
                'widget_desc' => '',
            ),
            'itemRecom' => array(
                'widget_title' => '自选商品',
                'widget_desc' => '',
            ),
            'threeImg' => array(
                'widget_title' => '三图导航',
                'widget_desc' => '',
            ),
            'nowLive' => array(
                'widget_title' => '正在直播',
                'widget_desc' => '',
            ),
            'floor' => array(
                'widget_title' => '商品楼层',
                'widget_desc' => '',
            ),
			'topcarousel'=>array(
                'widget_title' => '头部轮播',
                'widget_desc' => '',
            ),
			'catenav'=>array(
                'widget_title' => '分类导航',
                'widget_desc' => '',
            ),
			'midside'=>array(
                'widget_title' => '中部轮播',
                'widget_desc' => '',
            ),
			'compereoneitem'=>array(
                'widget_title' => '主播推荐(单个商品)',
                'widget_desc' => '',
            ),
			'aditem'=>array(
                'widget_title' => '广告商品推荐',
                'widget_desc' => '',
            ),
			'itemlist'=>array(
                'widget_title' => '商品列表(上拉加载)',
                'widget_desc' => '',
            ),
			'comperemoreitem'=>array(
                'widget_title' => '主播推荐(多个商品)',
                'widget_desc' => '',
            ),
        ),
        'pc' => array(),
    );

    public function __construct()
    {
        $this->widgetsMdl = app::get('sysshop')->model('widgets_instance');
    }

    /**
     * 获取挂件实例信息
     * @param $shop_id      店铺id
     * @param $page_type    页面类型
     * @param $platform     平台类型
     * @return mixed
     */
    public function getWidgetsList($shop_id, $page_type, $platform)
    {
        if(empty($shop_id))
        {
            throw new \LogicException("店铺ID不能为空.");
        }
        if(empty($page_type))
        {
            throw new \LogicException("页面类型不能为空.");
        }
        if(empty($platform))
        {
            throw new \LogicException("平台类型不能为空.");
        }

        $fields = '*';
        $orderBy = 'order_sort ASC';
        $filter = array('shop_id' => $shop_id, 'page_type' => $page_type, 'platform' => $platform);
        $list = $this->widgetsMdl->getList($fields, $filter, 0, -1, $orderBy);
        return $list;
    }

    /**
     * 获取使用中的挂件实例信息
     * @param $shop_id
     * @param $page_type
     * @param $platform
     * @return mixed
     */
    public function getUsedWidgets($shop_id, $page_type, $platform)
    {
        if(empty($shop_id))
        {
            throw new \LogicException("店铺ID不能为空.");
        }
        if(empty($page_type))
        {
            throw new \LogicException("页面类型不能为空.");
        }
        if(empty($platform))
        {
            throw new \LogicException("平台类型不能为空.");
        }

        $fields = '*';
        $orderBy = 'order_sort ASC';
        $filter = array('shop_id' => $shop_id, 'page_type' => $page_type, 'platform' => $platform, 'disabled' => 0);
        $list = $this->widgetsMdl->getList($fields, $filter, 0, -1, $orderBy);
        return $list;
    }

    /**
     * 获取挂件数量
     * @param $shop_id      店铺id
     * @param $page_type    页面类型
     * @param $platform     平台类型
     * @return mixed
     */
    public function getWidgetsNum($shop_id, $page_type, $platform)
    {
        if(empty($shop_id) || empty($page_type) || empty($platform))
        {
            throw new \LogicException("参数错误.");
        }

        $filter = array(
            'shop_id' => $shop_id,
            'page_type' => $page_type,
            'platform' => $platform,
        );

        $num = $this->widgetsMdl->count($filter);

        return $num;
    }

    /**
     * @param $data //配置数据
     * @param $msg  //操作提示
     * @return bool //true保存成功
     */
    public function saveWidget($data, &$msg)
    {
        $data['params'] = serialize($data['params']);
        $data['modified_time'] = time();

        if($data['widgets_id'])
        {
            // update
            $res = $this->widgetsMdl->update($data, array('widgets_id'=>$data['widgets_id']));
        }
        else
        {
            // insert
            $data['created_time'] = $data['modified_time'];
            $data['order_sort'] = $this->getWidgetsNum($data['shop_id'], $data['page_type'], $data['platform']) + 1;
            $res = $this->widgetsMdl->insert($data);
        }

        if($res)
        {
            $msg = '挂件保存成功';
            return true;
        }

        $msg = '挂件保存失败';
        return false;
    }

    /**
     * 根据挂件id获取挂件信息
     * @param string $fields
     * @param $widgets_id
     * @return mixed
     */
    public function getWidget($fields = '*', $widgets_id)
    {
        $widget = $this->widgetsMdl->getRow($fields, array('widgets_id' => $widgets_id));
        if($widget)
        {
            $widget['params'] = unserialize($widget['params']);
        }
        return $widget;
    }

    /**
     * 删除数据
     * @param $widgets_id
     * @param $msg
     * @return mixed
     */
    public function delWidget($widgets_id, &$msg)
    {
        // 获取要删除的数据信息
        $w_res = $this->widgetsMdl->getRow('order_sort', array('widgets_id'=>$widgets_id));
        if($w_res)
        {
            $order_sort = $w_res['order_sort']; // 要删除的数据排序
            $flag = $this->widgetsMdl->delete(array('widgets_id'=>$widgets_id));  // 删除数据
            if($flag)
            {
                // 获取排序值大于删除数据的排序值数据列表
                $need_sort_widgets = $this->widgetsMdl->getList('widgets_id', array('shop_id'=>$this->shopId, 'order_sort|than'=>$order_sort));
                if($need_sort_widgets)
                {
                    foreach ($need_sort_widgets as $w)
                    {
                        $this->widgetsMdl->update(array('order_sort'=>$order_sort), array('widgets_id'=>$w['widgets_id']));
                        $order_sort ++;
                    }
                }
                $msg = '删除数据成功';
                return true;
            }
        }

        $msg = '删除数据失败';
        return false;
    }
}