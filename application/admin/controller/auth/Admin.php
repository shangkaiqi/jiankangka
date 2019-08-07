<?php
namespace app\admin\controller\auth;

use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;
use app\common\controller\Backend;
use fast\Random;
use fast\Tree;
use Exception;
use think\Db;
use app\admin\controller\Business;

/**
 * 管理员管理
 *
 * @icon fa fa-users
 * @remark 一个管理员可以有多个角色组,左侧的菜单根据管理员所拥有的权限进行生成
 */
class Admin extends Backend
{

    /**
     *
     * @var \app\admin\model\Admin
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
                Db::startTrans();
                
                $user = db("admin")->lock(true)->where("username",$params['username'])->find();
                if ($user) {
                    $this->error("该用户已存在");
                }
                
                
                if ($this->pid == 0) {
                    $card = array();
                    $form = array();
                    $card = explode('-', $params['printcard']);
                    $form = explode('-', $params['printform']);
                    $data = [
                        // 'area' => $params['area'],
                        'physical_num' => $params['number'],
                        'phone' => $params['phone'],
                        'busisess_name' => $params['hospital'],
                        'connect' => $params['connect'],
                        'address' => $params['address'],
                        'bs_uuid' => create_uuid(),
                        'isprint' => $params['isprint'],
                        'province' => $params['province'],
                        'city' => $params['city'],
                        'county' => $params['area'],
                        'avatar' => $params['avatar'],
                        'print_card_id' => $card[0],
                        'print_form_id' => $card[0],
                        'print_card' => $form[1],
                        'print_form' => $form[1],
                        'profession' => $params['congye'],
                        'bs_id' => ''
                    ];

                    // $busResult = $this->buss->validate('Business.add')->save($data);
                    // 验证医院是否存在
                    $result = $this->buss->where("busisess_name", "=", $params['hospital'])->find();
                    if ($result['busisess_name'] != null || $result['busisess_name'] != '') {
                        $this->error("该体检单位已存在");
                    }
                    if (strlen($result['phone']) > 11) {

                        $this->error("请输入正确的手机号");
                    }
                    try {
                        $busResult = $this->buss->save($data);
                        if ($busResult === false) {
                            Db::rollBack();
                            $this->error();
                        }
                    } catch (Exception $e) {
                        Db::rollback();
                        $this->error();
                    }
                    $last_id = $this->buss->bs_id;
                }
                if ($this->pid != 0) {
                    $au = $this->model->get([
                        'id' => $this->auth->id
                    ]);
                    $last_id = $au['businessid'];
                }
                
                $user['salt'] = Random::alnum();
                $user['password'] = md5(md5($params['password']) . $user['salt']);
                $user['avatar'] = '/assets/img/avatar.png'; // 设置新管理员默认头像。
                $user['username'] = $params['username'];
                $user['email'] = $params['email'];
                $user['nickname'] = $params['nickname'];
                $user['status'] = $params['status'];
                $user['businessid'] = $last_id;
                // 设置新管理员默认头像。
                try {
                    $result = $this->model->validate('Admin.add')->save($user);
                    if ($result === false) {
                        Db::rollBack();
                        $this->error($this->model->getError());
                    }
                } catch (Exception $e) {
                    Db::rollBack();
                }
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
                try {
                    model('AuthGroupAccess')->saveAll($dataset);
                } catch (Exception $e) {
                    Db::rollBack();
                }

                Db::commit();
                $this->success();
            }
            $this->error();
        }
        $physcal_type = db("employee")->select();
        $this->view->assign("physcal_type", $physcal_type);
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
