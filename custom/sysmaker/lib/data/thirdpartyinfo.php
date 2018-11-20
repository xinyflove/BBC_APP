<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-16
 * Desc: 第三方数据处理
 */
class sysmaker_data_thirdpartyinfo {

    public $thirdpartyinfoModel = null;

    public function __construct()
    {
        $this->thirdpartyinfoModel = app::get('sysmaker')->model('thirdpartyinfo');
    }

    /**
     * 保存第三方数据
     * @param $data
     * @param $msg
     * @return bool
     */
    public function saveThirdData($data, &$msg)
    {
        $msg = '保存第三方数据成功';
        $thirdData = array(
            'flag' => $data['flag'],
            'openid' => $data['openid'],
            'nickname' => $data['nickname'],
            'sex' => $data['sex'],
            'figureurl' => $data['figureurl'],
            'country' => $data['country'],
            'province' => $data['province'],
            'city' => $data['city'],
        );

        $r = $this->thirdpartyinfoModel->getRow('id', $thirdData);
        if(!$r)//没有匹配到数据
        {
            $filter = array('openid' => $thirdData['openid']);
            $res = $this->thirdpartyinfoModel->getRow('id', $filter);
            if($res)
            {
                // 更新
                $id = $this->thirdpartyinfoModel->update($thirdData, $filter);
            }
            else
            {
                // 新增
                $thirdData['create_time'] = time();
                $id = $this->thirdpartyinfoModel->insert($thirdData);
            }

            if(!$id)
            {
                $msg = '保存第三方数据失败';
                return false;
            }
        }
        
        return true;
    }
}