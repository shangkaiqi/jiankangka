<?php
namespace app\admin\controller;

use app\common\controller\Backend;
use app\admin\controller\Common;
use Monolog\Logger;
use think\Log;

/**
 *
 * @desc体检登记
 *
 * @icon fa fa-circle-o
 */
class PhysicalUsers extends Backend
{

    protected $multiFields = 'switch';

    protected $model = null;

    protected $order = null;

    protected $orderd = null;

    protected $layout = 'register';

    protected $comm = null;

    // 开关权限开启
    protected $noNeedRight = [
        '*'
    ];

    /**
     * Register模型对象
     *
     * // * @var \app\admin\model\business\Register
     */
    public function _initialize()
    {
        parent::_initialize();

        $this->model = model("PhysicalUsers");
        $this->order = model("Order");
        $this->orderd = model("OrderDetail");
        $comm = new Common();
        $this->comm = $comm;

        $ins = $comm->inspect();
        $this->view->assign("inspect", $ins);

        $this->view->assign("wait_physical", $comm->wait_physical());
        $this->view->assign("pid", $comm->getemployee());
        // 获取结果检查信息
        $inspect_top = db("inspect")->field("id,name,value")->select();
        $this->view->assign("ins", $inspect_top);
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            // 如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list ($where, $sort, $order, $offset, $limit) = $this->buildparams();
            // $total = db("physical_users")->count("id");

            // $userList = db("physical_users")->field("id,name,identitycard,type")->select();
            $total = $this->model->where("bs_id", "=", $this->busId)->count("id");
            $userList = $this->model->where("bs_id", "=", $this->busId)->select();
            foreach ($userList as $row) {
                $row['registertime'] = date("Y-m-d H:i", $row['registertime']);
            }
            $result = array(
                "total" => $total,
                "rows" => $userList
            );

            return json($result);
        }
        return $this->view->fetch();
    }
}