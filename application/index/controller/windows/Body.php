<?php
namespace app\index\controller\windows;

use app\common\controller\Backend;
use app\index\controller\Common;
use app\common\controller\Frontend;

/**
 *
 * @desc采血窗口
 *
 * @icon fa fa-circle-o
 */
class Body extends Frontend
{

    protected $model = null;

    protected $orderde = null;

    protected $user = null;

    protected $admin = null;

    protected $type = 2;

    protected $inspect = null;
    protected $comm = null;

    // 开关权限开启
    protected $noNeedRight = [
        '*'
    ];

    public function _initialize()
    {
        $comm = new Common();
        $this->comm = $comm;
        parent::_initialize();
        $this->orderde = model("OrderDetail");
        $this->model = model("Order");
        $this->user = model("PhysicalUsers");
        $this->inspect = model("Inspect");
        $this->admin = model("Admin");


        $this->view->assign("pid", $comm->getemployee());
    }

    public function index()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $order_id = date("Ymd", time()) . $params['search'];
                $where['order_serial_number'] = $order_id;
                $where['bs_id'] = $this->busId;
                $user = db("physical_users")->where($where)->find();
                if (! $user) {
                    $this->error("用户不存在");
                }

                // 修改用户是否采血
                $this->orderde->update([
                    'status' => '1'
                ], [
                    'order_serial_number' => $order_id,
                    'physical' => $this->type,
                    'odbs_id' =>$this->busId
                ]);

                $user['employee'] = $user['employee'];
                $where = [
                    "user_id" => $user["id"],
                    'physical' => $this->type
                ];
                
                $ins = $this->comm->inspect($this->type,$order_id);
                $this->view->assign("inspect", $ins);
                $this->view->assign("wait_physical", $this->comm->wait_physical($user['id']));
                $this->view->assign("body", $user);
                return $this->view->fetch("search");
            } else {
                $this->error();
            }
        }
        $this->view->assign("wait_physical", $this->comm->wait_physical());
        return $this->view->fetch();
    }

    /**
     * 获取从业类别
     */
    public function getEmployee()
    {
        $pid = $this->request->get('pid');
        $where['pid'] = [
            '=',
            $type
        ];
        $categorylist = null;
        if ($type !== '') {
            $categorylist = $employee = db("employee")->field("id,pid,name")
                ->where('pid', '=', '0')
                ->select();
        }
        $this->success('', null, $categorylist);
    }

    /**
     * 保存检查结果
     */
    public function save()
    {
        $params = $this->request->post();
        $username = $this->admin->get([
            'id' => $this->auth->id
        ]);
        if ($params) {
            $result = $this->comm->saveOrderDetail($params,$this->type,$username['nickname']);
            if ($result) {
                $this->comm->check_resultstatus($params["order_serial_number"]);
                $this->success('保存成功', "index", '', 1);
            } else {
                $this->error('没有变更数据', 'index');
            }
        }
        
        
        if ($status == 0) {
            $this->success('保存成功', "index", '', 1);
        } else {
            $this->error('', 'index');
        }
    }
}