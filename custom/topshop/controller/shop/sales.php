<?php
/**
 * Created by PhpStorm.
 * @desc: 店铺推广页设置
 * @author: admin
 * @date: 2017-12-06 15:35
 */

class topshop_ctl_shop_sales extends topshop_controller{

    /**
     * @name index
     * @desc 推广活动列表页
     * @return html
     * @author: wudi tvplaza
     * @date: 2017-12-22 10:20
     */
    public function index(){
        $this->contentHeaderTitle = app::get('topshop')->_('H5活动推广页面设置');
        $filter['shop_id']=$this->shopId;
        $mdlSales=app::get('syspromotion')->model('sales');
        $sales=$mdlSales->getList('*',$filter);
        $pagedata['sales']=$sales;
        return $this->page('topshop/shop/sales/list.html',$pagedata);
    }


    /**
     * @name index
     * @desc 编辑推广页面
     * @return html
     * @author: wudi tvplaza
     * @date: 2017-12-06 16:41
     */
    public function editSales(){
        $this->contentHeaderTitle = app::get('topshop')->_('编辑活动推广页');
        $filter=input::get();
        if(!empty($filter['sales_id'])){
            $filter['shop_id']=$this->shopId;
            $sales=app::get('syspromotion')->model('sales')->getRow('*',$filter);
            $sales['item']=unserialize($sales['item']);
			$sales['sort']=unserialize($sales['sort']);
            $pagedata['sales']=$sales;
        }
        $item=app::get('sysitem')->model('item')->getList('item_id,title',array('item_id|in'=>$sales['item'],'order'=>''));
		foreach($item as $k=>$v){
			$data[$v['item_id']]=$v;
		}
		$goods=array();
		foreach($sales['item'] as $k=>$v){
			$goods[$k]=$data[$v];
		}
		$pagedata['goods']=$goods;
        $pagedata['shop']['shop_id']=$this->shopId;
        return $this->page("topshop/shop/sales/index.html",$pagedata);
    }


    /**
     * @name saveSales
     * @desc 保存推广活动页面设置
     * @author: wudi tvplaza
     * @date: 2017-12-06 16:41
     */
    public function saveSales(){
        $data=input::get();
		$sort=$data['sort'];
		unset($data['sort']);
		foreach($data['item'] as $k=>$v){
			$data['sort'][$v]=$sort[$k];
		}
        $mdlSales=app::get('syspromotion')->model('sales');
        $data['item']=serialize($data['item']);
		$data['sort']=serialize($data['sort']);
        if(empty($data['sales_id'])){
            $data['create_time']=time();
            $data['last_modified_time']=time();
        }else{
            $data['last_modified_time']=time();
        }

        try{
            $mdlSales->save($data);
        }catch (Exception $e){
            $msg=$e->getMessage();
            return $this->splash('error','','保存失败:'.$msg);
        }
        return $this->splash('success','','保存成功');
    }

    public function delete(){
        $salesId=input::get('sales_id');
        if(empty($salesId)){
            return $this->splash('error','','删除失败：活动编号丢失');
        }

        $filter['sales_id']=$salesId;
        $filter['shop_id']=$this->shopId;
        $mdlSales=app::get('syspromotion')->model('sales');
        try{
            $re=$mdlSales->delete($filter);
        }catch(Exception $e){
            return $this->splash('error','','删除失败：数据操作异常');
        }

        if($re!==false){
            $redirect_url = url::action('topshop_ctl_shop_sales@index');
            return $this->splash('success',$redirect_url,'删除成功');
        }else{
            return $this->splash('success','','删除失败');
        }
    }

}