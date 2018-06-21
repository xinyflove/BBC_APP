<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/22
 * Time: 14:21
 */
class ljstore_active_gift_list extends ljstore_app_controller {

    /**
     * 获取礼品列表
     * 参数:
     * active_id 活动id
     */
    public function getList()
    {
        $params = input::get();
        $active_id = intval($params['active_id']);
        if(empty($active_id))
        {
            $this->splash('1','参数错误,active_id不为空');
        }

        $giftMdl = app::get('sysactivityvote')->model('gift');
        $limit = $params['page_size'] ? $params['page_size'] : 10;
        $giftTotal = $giftMdl->count(array('active_id'=>$active_id));
        $pageTotal = ceil($giftTotal/$limit);
        $page =  $params['page_no'] ? $params['page_no'] : 1;
        $currentPage = $pageTotal < $page ? $pageTotal : $page;
        $offset = ($currentPage-1) * $limit;

        $fields = 'sg.*, ss.supplier_name';
        $builder = app::get('base')->database()->createQueryBuilder();
        $giftData = $builder->select($fields)
            ->from('sysactivityvote_gift', 'sg')
            ->leftJoin('sg', 'sysshop_supplier', 'ss', 'sg.supplier_id=ss.supplier_id')
            ->where("sg.active_id = '{$active_id}'")
            ->setFirstResult( $offset )
            ->setMaxResults( $limit )
            ->orderBy('sg.gift_id', 'ASC')->execute()->fetchAll();



        $result = array(
            'data' => $giftData,
            'total' => $giftTotal,
        );

        $this->splash('0',$result);
    }
}