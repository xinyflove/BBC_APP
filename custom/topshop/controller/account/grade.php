<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/9/6
 * Time: 19:02
 */

class topshop_ctl_account_grade extends topshop_controller
{
    protected $gradeModel;
    public function __construct($app)
    {
        parent::__construct($app);
        $this->gradeModel = app::get('sysuser')->model('user_grade');
    }

    /**
     * @return html
     * 用户等级管理
     */
    public function manage()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('会员等级管理');
        $shop_id = $this->shopId;
        $can_add = false;
        $filter['shop_id'] = $shop_id;
        try
        {
            $grade_count = $this->gradeModel->count($filter);
            if($grade_count < 8)
            {
                $can_add = true;
            }

            $grade_list = $this->gradeModel->getList('*', $filter);
        }
        catch(Exception $e)
        {
            throw new Exception($e->getMessage());
        }
        $page_data['is_lm'] = $this->isLm;
        $page_data['grade_list'] = $grade_list;
        $page_data['can_add'] = $can_add;
        return $this->page('topshop/account/grade/manage.html',$page_data);
    }

    /**
     * @return html
     * @throws Exception
     * 添加或编辑等级页面
     */
    public function editGrade()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('编辑等级');
        $grade_id = input::get('grade_id', 0);
        $grade_info = [];
        try
        {
            if($grade_id)
            {
                $grade_info = $this->gradeModel->getRow('*',['grade_id' => $grade_id]);
            }
        }
        catch(Exception $e)
        {
            throw new Exception($e->getMessage());
        }
        $page_data['grade_info'] = $grade_info;
        return $this->page('topshop/account/grade/edit.html',$page_data);
    }

    /**
     * @return string
     * 保存等级
     */
    public function saveGrade()
    {
        $post_data = input::get();
        $grade_info = $post_data['grade'];
        try
        {
            $this->__checkGrade($grade_info);
            $grade_info['shop_id'] = $this->shopId;
            $this->gradeModel->save($grade_info);
        }
        catch(Exception $e)
        {
            return $this->splash('error', null, $e->getMessage());
        }

        $url = url::action('topshop_ctl_account_grade@manage');
        return $this->splash('success', $url, app::get('topshop')->_('保存成功'));
    }

    /**
     * @param $data
     * @throws Exception
     * 等级保存信息检查
     */
    private function __checkGrade($data)
    {
        $max_count = 0;
        $validate_count = $this->gradeModel->count(['shop_id' => $this->shopId, 'grade_name' => $data['grade_name']]);
        if($data['grade_id'])
        {
            $max_count = 1;
        }

        if($validate_count > $max_count)
        {
            throw new Exception('等级名称已经存在，不能重复');
        }
    }

    /**
     * @return string
     * 删除等级
     */
    public function delGrade()
    {
        $get_data = input::get();
        $grade_id = $get_data['grade_id'];
        try
        {
            $this->gradeModel->delete(['grade_id'=>$grade_id, 'shop_id' => $this->shopId]);
        }
        catch(Exception $e)
        {
            return $this->splash('error', null, $e->getMessage());

        }
        $url = url::action('topshop_ctl_account_grade@manage');
        return $this->splash('success', $url, app::get('topshop')->_('删除成功'));
    }

}