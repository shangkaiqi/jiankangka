<?php
namespace app\index\controller\auth;

use app\index\model\AuthGroup;
use app\index\model\AuthGroupAccess;
use app\common\controller\Backend;
use fast\Random;
use fast\Tree;
use think\Db;
use app\index\controller\Business;
use app\common\controller\Frontend;

/**
 * 管理员管理
 *
 * @icon fa fa-users
 * @remark 一个管理员可以有多个角色组,左侧的菜单根据管理员所拥有的权限进行生成
 */
class Admin extends Frontend
{

    /**
     *
     * @var \app\index\model\Admin
     */
    protected $model = null;

    protected $buss = null;

    protected $bussExt = null;

    protected $childrenGroupIds = [];

    protected $childrenAdminIds = [];

    protected $pid = '';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Admin');
        $this->buss = model("Business");

        $this->childrenAdminIds = $this->auth->getChildrenAdminIds(true);
        $this->childrenGroupIds = $this->auth->getChildrenGroupIds(true);

        $groupList = collection(AuthGroup::where('id', 'in', $this->childrenGroupIds)->select())->toArray();

        Tree::instance()->init($groupList);
        $groupdata = [];
        if ($this->auth->isSuperAdmin()) {
            $result = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0));
            foreach ($result as $k => $v) {
                $groupdata[$v['id']] = $v['name'];
            }
        } else {
            $result = [];
            $groups = $this->auth->getGroups();
            foreach ($groups as $m => $n) {
                $childlist = Tree::instance()->getTreeList(Tree::instance()->getTreeArray($n['id']));
                $temp = [];
                foreach ($childlist as $k => $v) {
                    $temp[$v['id']] = $v['name'];
                }
                $result[__($n['name'])] = $temp;
            }
            $groupdata = $result;
        }

        $this->view->assign('groupdata', $groupdata);
        $this->assignconfig("admin", [
            'id' => $this->auth->id
        ]);

        // 先判断会员组
        $adminGroup = db("auth_group")->alias("ag")
            ->field("pid")
            ->join("auth_group_access aga", "ag.id = aga.group_id")
            ->where("aga.uid", "=", $this->auth->id)
            ->find();
        $this->pid = $adminGroup['pid'];
        $this->view->assign("pid", $adminGroup['pid'] == 0 ? 1 : 0);
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            // 如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $childrenGroupIds = $this->childrenGroupIds;
            $groupName = AuthGroup::where('id', 'in', $childrenGroupIds)->column('id,name');
            $authGroupList = AuthGroupAccess::where('group_id', 'in', $childrenGroupIds)->field('uid,group_id')->select();

            $adminGroupName = [];
            foreach ($authGroupList as $k => $v) {
                if (isset($groupName[$v['group_id']]))
                    $adminGroupName[$v['uid']][$v['group_id']] = $groupName[$v['group_id']];
            }
            $groups = $this->auth->getGroups();
            foreach ($groups as $m => $n) {
                $adminGroupName[$this->auth->id][$n['id']] = $n['name'];
            }
            list ($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->where($where)
                ->where('id', 'in', $this->childrenAdminIds)
                ->order($sort, $order)
                ->count();

            $list = $this->model->where($where)
                ->where('id', 'in', $this->childrenAdminIds)
                ->field([
                'password',
                'salt',
                'token'
            ], true)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach ($list as $k => &$v) {
                $groups = isset($adminGroupName[$v['id']]) ? $adminGroupName[$v['id']] : [];
                $v['groups'] = implode(',', array_keys($groups));
                $v['groups_text'] = implode(',', array_values($groups));
            }
            unset($v);
            $result = array(
                "total" => $total,
                "rows" => $list
            );

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $au = $this->model->get([
                    'id' => $this->auth->id
                ]);
                Db::startTrans();
                $last_id = $au['businessid'];
                $user = db("admin")->lock(true)->where("username",$params['username'])->find();
                if ($user) {
                    $this->error("该用户已存在");
                }
                $user['id'] = '';
                $user['salt'] = Random::alnum();
                $user['password'] = md5(md5($params['password']) . $user['salt']);
                $user['avatar'] = '/assets/img/avatar.png'; // 设置新管理员默认头像。
                $user['username'] = $params['username'];
                $user['email'] = $params['email'];
                $user['nickname'] = $params['nickname'];
                $user['status'] = $params['status'];
                $user['businessid'] = $last_id;
                // 设置新管理员默认头像。
                $result = $this->model->validate('Admin.add')->save($user);
                if ($result === false) {
                    Db::rollBack();
                    $this->error($this->model->getError());
                }
                Db::commit();
                $group = $this->request->post("group/a");

                // 过滤不允许的组别,避免越权
                $group = array_intersect($this->childrenGroupIds, $group);
                $dataset = [];
                foreach ($group as $value) {
                    $dataset[] = [
                        'uid' => $this->model->id,
                        'group_id' => $value
                    ];
                }
                model('AuthGroupAccess')->saveAll($dataset);
                $this->success();
            }
            $this->error();
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get([
            'id' => $ids
        ]);
        if (! $row)
            $this->error(__('No Results were found'));
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if ($params['password']) {
                    $params['salt'] = Random::alnum();
                    $params['password'] = md5(md5($params['password']) . $params['salt']);
                } else {
                    unset($params['password'], $params['salt']);
                }
                // 这里需要针对username和email做唯一验证
                $adminValidate = \think\Loader::validate('Admin');
                $adminValidate->rule([
                    'username' => 'require|max:50|unique:admin,username,' . $row->id,
                    'email' => 'require|email|unique:admin,email,' . $row->id
                ]);
                $result = $row->validate('Admin.edit')->save($params);
                if ($result === false) {
                    $this->error($row->getError());
                }

                // 先移除所有权限
                model('AuthGroupAccess')->where('uid', $row->id)->delete();

                $group = $this->request->post("group/a");

                // 过滤不允许的组别,避免越权
                $group = array_intersect($this->childrenGroupIds, $group);

                $dataset = [];
                foreach ($group as $value) {
                    $dataset[] = [
                        'uid' => $row->id,
                        'group_id' => $value
                    ];
                }
                model('AuthGroupAccess')->saveAll($dataset);
                $this->success();
            }
            $this->error();
        }
        $grouplist = $this->auth->getGroups($row['id']);
        $groupids = [];
        foreach ($grouplist as $k => $v) {
            $groupids[] = $v['id'];
        }
        $this->view->assign("row", $row);
        $this->view->assign("groupids", $groupids);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            // 避免越权删除管理员
            $childrenGroupIds = $this->childrenGroupIds;
            $adminList = $this->model->where('id', 'in', $ids)
                ->where('id', 'in', function ($query) use ($childrenGroupIds) {
                $query->name('auth_group_access')
                    ->where('group_id', 'in', $childrenGroupIds)
                    ->field('uid');
            })
                ->select();
            if ($adminList) {
                $deleteIds = [];
                foreach ($adminList as $k => $v) {
                    $deleteIds[] = $v->id;
                }
                $deleteIds = array_diff($deleteIds, [
                    $this->auth->id
                ]);
                if ($deleteIds) {
                    $this->model->destroy($deleteIds);
                    model('AuthGroupAccess')->where('uid', 'in', $deleteIds)->delete();
                    $this->success();
                }
            }
        }
        $this->error();
    }

    /**
     * 批量更新
     *
     * @internal
     */
    public function multi($ids = "")
    {
        // 管理员禁止批量操作
        $this->error();
    }

    /**
     * 下拉搜索
     */
    public function selectpage()
    {
        $this->dataLimit = 'auth';
        $this->dataLimitField = 'id';
        return parent::selectpage();
    }
}
