<?php
namespace app\index\controller\result;

use app\common\controller\Backend;
use app\index\controller\Common;
use app\common\controller\Frontend;

/**
 *
 * @desc结果录入
 * @icon fa fa-circle-o
 */
class Resultcheck extends Frontend
{

    protected $blood = 0;

    protected $type = 0;

    protected $comm = '';

    protected $orderde = null;

    protected $inspect = null;

    protected $admin = null;

    protected $order = null;

    // 开关权限开启
    protected $noNeedRight = [
        '*'
    ];

    /**
     * Register模型对象
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->inspect = model("Inspect");
        $this->admin = model("Admin");
        $this->order = model("Order");
        $this->orderde = model("OrderDetail");
        $comm = new Common();
        $this->comm = $comm;
    }

    public function index()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if(strlen($params['search'])==4){                    
                    $order_id = date("Ymd", time()) . $params['search'];
                }else if(strlen($params['search'])==12){   
                    $order_id = $params['search'];
                }else{
                    $this->error("请输入正确的体检编号");
                }
                $where['order_serial_number'] = $order_id;
                $where['bs_id'] = $this->busId;
                $uid = db("physical_users")->where($where)->find();
                if (! $uid) {
                    $this->error("用户不存在");
                }

                /**
                 * 血检信息
                 *
                 * @var Ambiguous $result
                 */
                $blood = array();

                $blood = $this->comm->inspect(0, $order_id);
                $this->view->assign("blood", $blood);

                /**
                 * 便检信息
                 *
                 * @var Ambiguous $result
                 */

                $conven = array();

                $conven = $this->comm->inspect(1, $order_id);
                $this->view->assign("conven", $conven);

                /**
                 * 体检信息
                 *
                 * @var Ambiguous $result
                 */
                $body = array();

                $body = $this->comm->inspect(2, $order_id);
                $this->view->assign("body", $body);
                /**
                 * 透視信息
                 *
                 * @var Ambiguous $result
                 */
                $tous = array();
                $tous = $this->comm->inspect(3, $order_id);
                $this->view->assign("tous", $tous);

                $this->view->assign("userinfo", $uid);
                return $this->view->fetch("search");
            } else {
                $this->error();
            }
        }

        return $this->view->fetch();
    }

    public function save()
    {
        $params = $this->request->post();
        $username = $this->admin->get([
            'id' => $this->auth->id
        ]);
        $status = 0;
       
        if ($params) {
            $result = $this->comm->saveOrderDetail($params,9,$username['nickname']);
            $this->comm->check_resultstatus($params["order_serial_number"]);
            $this->comm->checkOrderStatus($params['order_serial_number']);
            if ($status == 0) {
                $this->success('保存成功', "index", '', 1);
            } else {
                $this->error('', 'index');
            }
        }
    }

    public function saveResult($params, $type)
    {
        $username = $this->admin->get([
            'id' => $this->auth->id
        ]);

        foreach ($params as $index) {
            $inspectInfo = $this->inspect->get([
                "id" => $index
            ]);
            $inspectStatus = $this->inspect->get([
                "id" => $inspectInfo['parent']
            ]);
            $where = [
                'physical' => $type,
                'order_serial_number' => $params['ordernum'],
                'item' => $index
            ];

            $list = [
                "physical_result" => 1,
                "status" => 1,
                "physical_result" => $inspectStatus['name'],
                "physical_result_ext" => $inspectInfo['name'],
                "doctor" => $username['nickname']
            ];
            $update = $this->orderde->where($where)->update($list);
            if (! $update) {
                $status = 1;
            }
        }
    }
}