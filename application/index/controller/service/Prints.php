<?php
namespace app\index\controller\service;

use app\common\controller\Backend;
use app\index\controller\Common;
use app\common\controller\Frontend;

/**
 * 打印健康证
 *
 * @icon fa fa-circle-o
 */
class Prints extends Frontend
{

    /**
     * Register模型对象
     */
    protected $model = null;

    protected $comm = null;

    // 开关权限开启
    protected $noNeedRight = [
        '*'
    ];

    public function _initialize()
    {
        parent::_initialize();
        $comm = new Common();
        $this->comm = $comm;
        $this->model = model("PhysicalUsers");
    }

    public function index()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $uid = array();
                $order_id = $params['search'];
                $where['order_serial_number'] = $order_id;
                $where['bs_id'] = $this->busId;
                $uid = db("physical_users")->where($where)->find();
                if (! $uid) {
                    $this->error("用户不存在");
                }
                $this->view->assign("body", $uid);
                // 获取打印信息
                $where_1['order_serial_number'] = $params['search'];
                $printInfo = db("order")->where($where_1)->find();
                // 获取体检单位
                $hosp = db("business")->field("bs_id,busisess_name,avatar,print_card_id")
                    ->where("bs_uuid", "=", $printInfo['bus_number'])
                    ->find();

                $printInfo['name'] = $uid['name'];
                $printInfo['sex'] = $uid['sex'] == 0 ? "男" : "女";
                $printInfo['age'] = $uid['age'];
                $printInfo['employee'] = $uid['employee'];
                $printInfo['images'] = $uid['images'];
                $printInfo['company'] = $hosp['busisess_name'];
                $printInfo['physictype'] = $uid['employee_id']; // 1公共卫生2食药安全
                $printInfo['identitycard'] = $uid['identitycard']; // 1公共卫生2食药安全
                $printInfo['avatar'] = $hosp['avatar'];
                $printInfo['endtime'] = date('Y-m-d',strtotime('+1year'));
                $printInfo['time'] = date('Y-m-d',time());
                $printInfo['print_card_id'] = $hosp['print_card_id'];
                $printInfo['time'] = date('Y-m-d',time());

                // 判断打印卡数量是否超过限制量
                $printInfo['is_out'] = $this->comm->checkcardnumber($hosp['bs_id']);

                $this->view->assign("print", $printInfo);
                $checkresult = $this->checkresult($order_id);
                $this->view->assign("result", $checkresult);
                $this->view->assign("order_num", $order_id);
                return $this->view->fetch("search");
            } else {
                $this->error();
            }
        }
        return $this->view->fetch();
    }

    // 检查结果
    public function checkresult($id)
    {
        $int = 0;
        $str = '';
        $where['order_serial_number'] = $id;
        $result = db("order_detail")->where($where)->select();
        foreach ($result as $row) {
            if ($row['physical_result'] != 0) {
                $int ++;
                // 体检项
                $where1['id'] = $row['item'];
                $ins = db('inspect')->field("name")
                    ->where($where1)
                    ->find();
                $where2['id'] = $row['physical_result_ext'];
                $ins_result = db('inspect')->field("name")
                    ->where($where2)
                    ->find();
                $str .= $ins['name'] . ":" . $ins_result['name'];
                $str .= "  ";
            }
        }
        if ($int == 0) {
            $data['physical_result'] = 1;
            db('order')->where($where)->update($data);
        }
        
        return $str==''?'正常':$str;
    }
}