<?php
namespace app\admin\controller;

use app\common\controller\Backend;
use think\Config;

/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    protected $pid = 0;


    public function _initialize()
    {
        parent::_initialize();
        // 先判断会员组
        $adminGroup = db("auth_group")->alias("ag")
            ->field("pid")
            ->join("auth_group_access aga", "ag.id = aga.group_id")
            ->where("aga.uid", "=", $this->auth->id)
            ->find();
        $this->pid = $adminGroup['pid'] == 0 ? 1 : 0;
    }

    /**
     * 查看
     */
    public function index()
    {

        $seventtime = \fast\Date::unixtime('day', - 7);
        $paylist = $createlist = [];
        for ($i = 0; $i < 7; $i ++) {
            $day = date("Y-m-d", $seventtime + ($i * 86400));
            $createlist[$day] = mt_rand(20, 200);
            $paylist[$day] = mt_rand(1, mt_rand(1, $createlist[$day]));
        }
        $totaluser = 0;
        $printcard_num = 0;
        $have_physical = 0;
        // 医院总数
        if ($this->pid) {
            
            $totaluser = db("business")->count("bs_id");
            $totalviews = db("order")->where("employ_num_time", "neq", "null")->count("order_id");
            $totalorder = db("physical_users")->count("id");
            $have_physical = db("physical_users")->count("id");
            $totalorderamount = db("physical_users")->where("company", "neq", "null")->count("id");
        } else {
            $totaluser = db("admin")->where('id', "=", $this->auth->id)->find();
            $bsId = db('business')->where('bs_id', "=", $totaluser['businessid'])->find();
            $where_1['employ_num_time'] = [
                "neq",
                "null"
            ];
            $where_1["bus_number"] = [
                'eq',
                $bsId['bs_uuid']
            ];
            $totalviews = db("order")->where($where_1)->count("order_id");
            $where_2['employ_num_time'] = [
                "neq",
                "null"
            ];
            $where_2["bus_number"] = [
                'eq',
                $bsId['bs_uuid']
            ];
            $totalorder = db("order")->distinct(true)
                ->field('user_id')
                ->where($where_2)
                ->count("order_id");

            $where['company'] = [
                'neq',
                null
            ];
            $where["bus_number"] = [
                'eq',
                $bsId['bs_uuid']
            ];
            $totalorderamount = db("physical_users")->alias("pu")
                ->join("order o", "pu.order_serial_number=o.order_serial_number")
                ->where($where)
                ->distinct(true)
                ->field('company')
                ->count("id");
            $print['bs_id'] = $totaluser['businessid'];
            $printcard_num = db('business')->where($print)->find();
        }
        $this->view->assign("pid", $this->pid);
        $this->view->assign([
            'totaluser' => $totaluser, // 医院总数
            'totalviews' => $totalviews, // 打印卡总量
            'totalorder' => $totalorder, // 登记体检人员总数
            'totalorderamount' => $have_physical, // 体检单位
            'printcard_num' => $printcard_num['physical_num'],

            'todayuserlogin' => 321,
            'todayusersignup' => 430,
            'todayorder' => 2324,
            'unsettleorder' => 132,
            'sevendnu' => '80%',
            'sevendau' => '32%',
            'paylist' => $paylist,
            'createlist' => $createlist
        ]);

        return $this->view->fetch();
    }
}
