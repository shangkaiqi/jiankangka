<?php

namespace app\index\controller\user;

use app\common\controller\Backend;
use app\common\controller\Frontend;

/**
 * 会员组管理
 *
 * @icon fa fa-users
 */
class Group extends Frontend
{

    /**
     * @var \app\index\model\UserGroup
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('UserGroup');
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    public function add()
    {
        $nodeList = \app\index\model\UserRule::getTreeList();
        $this->assign("nodeList", $nodeList);
        return parent::add();
    }

    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $rules = explode(',', $row['rules']);
        $nodeList = \app\index\model\UserRule::getTreeList($rules);
        $this->assign("nodeList", $nodeList);
        return parent::edit($ids);
    }

}
