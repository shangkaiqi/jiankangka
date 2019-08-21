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
class Repair extends Frontend
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
            if($params){
            	$identitycard = $params['search'];
            	$where['identitycard'] = $identitycard;
            	$where['is_print'] = 1;
            	//根据身份证号查询最后一次打卡信息，也就是要补卡的信息
            	$printInfo = db('order')->where($where)
                             ->order('createdate desc')
                             ->find();
                if(!$printInfo){
                    $this->error("用户不存在");
                }                 
	          
	          //最后一次打卡的到期时间要固定
	          $employNumTime =$printInfo['employ_num_time'];
	          $validTime = $printInfo['valid_time'];

	          $this->assign('printTime',$employNumTime);
	          $this->assign('validTime',$validTime);

	          $order_id = $printInfo['order_serial_number'];
	          //根据编号查询个人信息
	          $where1['order_serial_number'] = $order_id;
	          //$where1['bs_id'] = $this->busId;
	          $where1['is_del'] = 0;
	          $uid = db('physical_users')->where($where1)->find();

         
            
                 // 获取体检单位
                 $hosp = db("business")->field("bs_id,busisess_name,avatar,print_card_id")
                     ->where("bs_uuid", "=", $printInfo['bus_number'])
                     ->find();
                $printInfo['name'] = $uid['name'];
                $printInfo['sex'] = $uid['sex'] == 0 ? "男" : "女";
                $printInfo['is_print'] = $printInfo['is_print'] == 0?"未打卡":"已打卡";
                $printInfo['age'] = $uid['age'];
                $printInfo['employee'] = $uid['employee'];
                $printInfo['images'] = $uid['images'];
                $printInfo['company'] = $hosp['busisess_name'];
                $printInfo['physictype'] = $uid['employee_id']; // 1公共卫生2食药安全
                $printInfo['identitycard'] = $uid['identitycard']; //身份证号
                $printInfo['avatar'] = $hosp['avatar'];
                $printInfo['endtime'] = date('Y-m-d',$printInfo['valid_time']);
                $printInfo['time'] = date('Y-m-d',time());
                $printInfo['print_card_id'] = $hosp['print_card_id'];

                // 判断打印卡数量是否超过限制量
                $printInfo['is_out'] = $this->comm->checkcardnumber($hosp['bs_id']);
                
                //打卡次数，根据身份证号查询往次打卡信息
                $countWhere['is_print'] = 1;
                $countWhere['identitycard'] = $uid['identitycard'];
                $printCounts = db('order')->where($countWhere)->count();
                $countsInfo = db('order')->where($countWhere)->order('employ_num_time desc')->select();
                    
                $this->view->assign('print_counts',$printCounts);//打卡次数
                $this->view->assign('counts_info',$countsInfo);//打卡信息

                $this->view->assign("body", $uid);
                $this->view->assign("print", $printInfo);
                
                $this->view->assign("order_num", $order_id);

                return $this->view->fetch("search");
            } else {
                $this->error();
            }
        }
        return $this->view->fetch();
    }

   
}