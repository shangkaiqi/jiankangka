<?php
namespace app\index\controller;

use app\common\controller\Backend;
use PHPExcel_IOFactory;
use app\common\controller\Frontend;
require './phpexcel/PHPExcel.php';

class Common extends Frontend
{

    protected $orderde = null;

    protected $noNeedRight = [
        '*'
    ];

    protected $noNeedLogin = [
        '*'
    ];

    public function _initialize()
    {
        parent::_initialize();
        $this->orderde = model("OrderDetail");
    }

    /**
     * 更新打印健康证时间
     */
    public function updatePrint()
    {
        $params = $this->request->post('order_num');
        $date['employ_num_time'] = time();
        $where['obs_id'] = $this->busId;
        $where['order_serial_number'] = $params;
        db('order')->where($where)->update($date);
    }

    public function getInspece($parent)
    {
        $inspect = db("inspect")->field("id,name,value,status")
            ->where("parent", "=", $parent)
            ->select();
        return $inspect;
    }

    // 批量打印复验单
    public function pcheckfrom()
    {
        $result = $this->request->get('id');
    }

    public function getCheckResult($order, $type)
    {
        $where['order_serial_number'] = $order;
        $where['physical'] = $type;
        $result = db('order_detail')->where($where)->find();
        return $result;
    }

    /**
     * 获取体检结果
     *
     * @param string $type
     * @return array
     */
    public function inspect($type = '', $orderId = '')
    {
        $where = array();
        $inspect = array();
        $where['type'] = [
            "eq",
            $type
        ];
        $where['order_serial_number'] = [
            'eq',
            $orderId
        ];
        $where['parent'] = [
            "eq",
            0
        ];
        $where['odbs_id'] = [
            "eq",
            $this->busId
        ];
        if ($type == '') {
            $inspect = db("order_detail")->alias('od')
                ->join("inspect i", 'od.item=i.id')
                ->field("i.id,name,item,physical_result,physical_result_ext")
                ->where($where)
                ->group("name")
                ->select();
        } else {
            $inspect = db("order_detail")->alias('od')
                ->join("inspect i", 'od.item=i.id')
                ->field("i.id,name,item,physical_result,physical_result_ext")
                ->where($where)
                ->group("name")
                ->select();
        }
        file_put_contents("testa.txt", db()->getLastSql(), FILE_APPEND);
        $ins = array();
        foreach ($inspect as $key => $val) {
            $in_a = $this->getInspece($val['id']);
            $ins[] = array(
                "name" => $val['name'],
                "values" => $in_a,
                "id" => $val['id'],
                "item" => $val['item'],
                "physical_result" => $val['physical_result'],
                'physical_result_ext' => $val['physical_result_ext']
            );
        }
        return $ins;
    }

    /**
     * 获取待检测信息
     *
     * @return string
     */
    public function wait_physical($uid = '')
    {
        if ($uid == '') {
            return "";
        }
        $where['user_id'] = $uid;
        $where['status'] = 1;
        // 待体检项：
        $result = db('order')->alias('o')
            ->join("order_detail od", "`o`.`order_serial_number` = `od`.`order_serial_number`")
            ->field("physical")
            ->where($where)
            ->select();
        $arr = array();
        foreach ($result as $row) {
            $arr[] = $row['physical'];
        }
        $uArr = array();
        // 体检项：0.血检1.便检2体检3.透视
        if (! in_array(0, $arr)) {
            $uArr[] = "血检";
        }
        if (! in_array(1, $arr)) {
            $uArr[] = "便检";
        }
        if (! in_array(2, $arr)) {
            $uArr[] = "体检";
        }
        if (! in_array(3, $arr)) {
            $uArr[] = "透视";
        }

        $result = implode(" ", $uArr);
        return $result;
    }

    /**
     * 获取id对应的地区
     *
     * @param int $id
     * @return string
     */
    public function getAreaName($id)
    {
        $name = '';
        $where['id'] = $id;
        $name = db('area')->where($where)->find();
        if ($name) {
            return $name['mergename'];
        }
    }

    /**
     * 判断该医院打印卡数量是否超过限制
     *
     * @param int $total
     * @param int $bussid
     * @return boolean
     */
    public function checkcardnumber($bussid, $total = 1)
    {
        $where['bs_id'] = $bussid;
        $number = db("business")->field('physical_num')
            ->where($where)
            ->find();
        if ($number['physical_num'] >= $total)
            return true;
        else
            return false;
    }

    /**
     * 如果是ajax请求返回从业从业类型子项，否则返回全部从业类型
     *
     * @return \think\response\Json|mixed
     */
    public function getemployee()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->get("id");
            file_put_contents("comm-id.txt", $id);
            $employee = db("employee")->field("id,pid,name")
                ->where("pid", "=", $id)
                ->select();
            return json($employee);
        } else {
            $employee = db("employee")->field("id,pid,name")
                ->where("pid", "=", 0)
                ->select();
            return $employee;
        }
    }

    /**
     * 获取从业信息
     *
     * @param int $emId
     * @return array|\think\Model
     */
    public function employee($emId)
    {
        $employee = db("employee")->field("name")
            ->where("id", "=", $emId)
            ->find();
        return $employee;
    }

    /**
     * 保存体检信息
     *
     * @param array $params
     * @return boolean
     */
    public function saveOrderDetail($params, $type, $doctor)
    {
        $status = 0;
        for ($i = 0; $i < count($params['frist']); $i ++) {
            $arr = explode("-", $params['frist'][$i]);
            if ($arr[0] == 0) {
                if ($type == 9) {
                    $where = [
                        'order_serial_number' => $params["order_serial_number"],
                        'item' => $arr[1],
                        'odbs_id' => $this->busId
                    ];
                } else {
                    $where = [
                        'physical' => $type,
                        'order_serial_number' => $params["order_serial_number"],
                        'item' => $arr[1],
                        'odbs_id' => $this->busId
                    ];
                }
                $list = [
                    "physical_result" => 0,
                    "physical_result_ext" => 0,
                    "status" => 1,
                    "doctor" => $doctor
                ];
                $update = $this->orderde->where($where)->update($list);
                if ($update === 0) {
                    $status ++;
                }
            } else {
                $res = $params['result'][$i];
                $sql = "select id,name from fa_inspect where
                                    id=(select parent from fa_inspect where id = $res)  limit 1";
                $ins = db()->query($sql);
                if ($type == 9) {
                    $where = [
                        'order_serial_number' => $params["order_serial_number"],
                        'item' => $arr[1],
                        'odbs_id' => $this->busId
                    ];
                } else {
                    $where = [
                        'physical' => $type,
                        'order_serial_number' => $params["order_serial_number"],
                        'item' => $arr[1],
                        'odbs_id' => $this->busId
                    ];
                }
                $list = [
                    "physical_result" => 1,
                    "physical_result_ext" => $res,
                    "status" => 1,
                    "doctor" => $doctor
                ];
                $update = $this->orderde->where($where)->update($list);
                if ($update === 0) {
                    $status ++;
                }
            }
        }
        return true;
    }

    public function check_resultstatus($orderId)
    {
        $where['order_serial_number'] = $orderId;
        $where['odbs_id'] = $this->busId;
        $result = db('order_detail')->where($where)->select();
        $i = 0;
        foreach ($result as $row) {
            if ($row['physical_result'] != 0) {
                $i ++;
            }
        }
        if ($i == 0) {
            $owhere['order_serial_number'] = $orderId;
            $owhere['obs_id'] = $this->busId;
            $data['physical_result'] = 1;
            $result = db('order')->where($owhere)->update($data);
            return true;
        } else {
            $owhere['order_serial_number'] = $orderId;
            $owhere['obs_id'] = $this->busId;
            $data['physical_result'] = 0;
            $result = db('order')->where($owhere)->update($data);
            return false;
        }
    }

    /**
     * 获取体结果选项检项
     */
    public function getInspect()
    {
        $params = $this->request->get();
        $type = $params['type'];
        $inspect = array();
        if ($type) {
            $inspect = db("inspect")->field("id,name")
                ->where('parent', '=', $params['id'])
                ->select();
        }
        $return = array(
            'type' => $type,
            'inspect' => $inspect
        );
        return json($return);
    }

    /**
     * 导出excel
     *
     * @param string $expTitle
     * @param string $expCellName
     * @param array $expTableData
     */
    public function exportExcel($expTitle, $expCellName, $expTableData)
    {
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle); // 文件名称
        $fileName = 'usersdd' . date('_YmdHis', time()); // or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        // vendor("PHPExcel");

        $objPHPExcel = new \PHPExcel();
        $cellName = array(
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P',
            'Q',
            'R',
            'S',
            'T',
            'U',
            'V',
            'W',
            'X',
            'Y',
            'Z',
            'AA',
            'AB',
            'AC',
            'AD',
            'AE',
            'AF',
            'AG',
            'AH',
            'AI',
            'AJ',
            'AK',
            'AL',
            'AM',
            'AN',
            'AO',
            'AP',
            'AQ',
            'AR',
            'AS',
            'AT',
            'AU',
            'AV',
            'AW',
            'AX',
            'AY',
            'AZ'
        );

        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1'); // 合并单元格
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle . '  Export time:' . date('Y-m-d H:i:s'));
        for ($i = 0; $i < $cellNum; $i ++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
        }
        // Miscellaneous glyphs, UTF-8
        for ($i = 0; $i < $dataNum; $i ++) {
            for ($j = 0; $j < $cellNum; $j ++) {
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), '' . $expTableData[$i][$expCellName[$j][0]]);
            }
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=GB2312;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls"); // attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit();
    }

    /**
     * 批量打印通过检查结果
     *
     * @param array $users
     * @param int $type
     * @return boolean
     */
    public function muilts($users, $type)
    {
        // 根据用户查询属于哪个医院
        $medicine = db("admin")->field("nickname")
            ->where("id", "=", $this->auth->id)
            ->find();
        // 获取用户对应的订单编号
        $order_num = db("physical_users")->field("order_serial_number")
            ->where("id", "in", $users)
            ->select();
        $i = 0;
        foreach ($order_num as $order) {
            $where['order_serial_number'] = $order['order_serial_number'];
            $where['physical'] = $type;
            $where['odbs_id'] = $this->busId;
            $data['physical_result'] = 0;
            $data['physical_result_ext'] = 0;
            $data['doctor'] = $medicine['nickname'];
            $result = db("order_detail")->where($where)->update($data);

            $this->check_resultstatus($order['order_serial_number']);
            if (! $result) {
                $i ++;
            }
        }
        if ($i == 0) {
            return true;
        } else
            return false;
    }

    /**
     * 返回从业类型
     *
     * @param string $str
     */
    public function getEmpName($str)
    {
        $em = json_decode($str, true);
        $parent = $this->employee($em[0]);
        // $son = $this->comm->employee($em[1]);
        // $row['employee'] = $parent['name'] . ">>" . $son['name'];
        return $parent['name'];
    }
}