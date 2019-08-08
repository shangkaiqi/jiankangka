<?php
namespace app\index\controller\result;

use app\common\controller\Backend;
use app\index\controller\Common;
use app\common\controller\Frontend;

/**
 *
 * @desc便检结果录入
 * @icon fa fa-circle-o
 */
class Conveniresult extends Frontend
{

    protected $model = null;

    protected $user = null;

    protected $comm = null;

    protected $inspect = null;

    protected $orderde = null;

    protected $admin = null;

    protected $type = "1";

    // 开关权限开启
    protected $noNeedRight = [
        '*'
    ];

    public function _initialize()
    {
        parent::_initialize();
        $this->inspect = model("Inspect");
        $this->admin = model("admin");
        $this->orderde = model("OrderDetail");
        $comm = new Common();
        $this->comm = $comm;

        $this->view->assign("pid", $comm->getEmployee());
        $this->model = model("PhysicalUsers");
    }

    /**
     * 血检用户列表
     *
     * @return string
     */
    public function index()
    {
        // 当前是否为关联查询
        $this->relationSearch = true;
        // 设置过滤方法
        $this->request->filter([
            'strip_tags'
        ]);
        if ($this->request->isAjax()) {
            // 如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list ($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $where1['bs_id'] = $this->busId;
            $where1['is_del'] = 0;
            $total = $this->model->with([
                'order'
            ])
                ->where($where)
                ->where($where1)
                ->order($sort, $order)
                ->count();

            $list = $this->model->with([
                'order'
            ])
                ->where($where)
                ->where($where1)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $row => $v) {
                $resWhere['order_serial_number'] = $v['order_serial_number'];
                $resWhere['odbs_id'] = $this->busId;
                $resWhere['physical'] = $this->type;
                $res = $this->orderde->field("physical_result")
                    ->where($resWhere)
                    ->select();
                $status = 0;
                $count = count($res);
                $status1 = 0;
                $status0 = 0;
                foreach ($res as $r) {
                    if ($r['physical_result'] == 2) {
                        $status ++;
                    }
                    if ($r['physical_result'] == 1) {
                        $status1 ++;
                    }
                    if ($r['physical_result'] == 0) {
                        $status0 ++;
                    }
                }
                if($status == $count){
                    $list[$row]['physical_result'] = 2;
                }else if($status1){
                    $list[$row]['physical_result'] = 1;
                }else if($status0 == $count){
                    $list[$row]['physical_result'] = 0;
                }
            }
            $list = collection($list)->toArray();
            $result = array(
                "total" => $total,
                "rows" => $list
            );

            return json($result);
        }
        return $this->view->fetch();
    }

    public function edit($ids = null)
    {
        $row = $this->model->get([
            'id' => $ids
        ]);

        $username = $this->admin->get([
            'id' => $this->auth->id
        ]);
        if (! $row)
            $this->error(__('No Results were found'));
        if ($this->request->isPost()) {
            $params = $this->request->post();
            $status = 0;

            if ($params) {
                $result = $this->comm->saveOrderDetail($params, $this->type, $username['nickname']);
                if ($result) {
                    $this->comm->check_resultstatus($params["order_serial_number"]);
                    $this->success('保存成功', "index", '', 1);
                } else {
                    $this->error('没有变更数据', 'index');
                }
            }
        }
        $ins = $this->comm->inspect($this->type, $row['order_serial_number']);
        $this->view->assign("inspect", $ins);
        $this->view->assign("wait_physical", $this->comm->wait_physical($ids));
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 批量操作通过
     */
    public function mulit($ids = null)
    {
        $user = $this->request->get("id");
        $users = explode(",", $user);
        $result = $this->comm->muilts($users, $this->type);
        if ($result) {
            $this->success('保存成功');
        }else{
            $this->success('暂无操作');
        }
    }
}