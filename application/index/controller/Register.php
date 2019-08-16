<?php
namespace app\index\controller;

use app\common\controller\Backend;
use app\index\controller\Common;
use think\Db;
use think\Session;
use Monolog\Logger;
use think\Log;
use app\common\controller\Frontend;
use think\Exception;

/**
 *
 * @desc体检登记
 *
 * @icon fa fa-circle-o
 */
class Register extends Frontend
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
     * // * @var \app\index\model\business\Register
     */
    public function _initialize()
    {
        parent::_initialize();

        $this->model = model("PhysicalUsers");
        $this->order = model("Order");
        $this->orderd = model("OrderDetail");
        $comm = new Common();
        $this->comm = $comm;


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
            $where1['bs_id'] = $this->busId;
            $where1['is_del'] = 0;
            $total = $this->model->where($where)->where($where1)->count("id");
            $userList = $this->model->where($where)->where($where1)->order($sort, $order)->limit($offset, $limit)->select();
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

    public function add()
    {

        
        // 获取医院唯一标识
        $bs_id = db("admin")->alias("a")
            ->field("b.bs_uuid,isprint,b.charge,b.bs_id,b.print_form_id,profession")
            ->join("business b", "a.businessid = b.bs_id")
            ->where("id", "=", $this->auth->id)
            ->find();
        $physcal_type = db("employee")->select();
        Session::delete("company", '');
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if($params['type'] == 1){                    
                    Session::set("company", $params['company']);
                }
				$ordernum = array();
				$phwhere['order_serial_number'] = ['like',date("Ymd", time()) . "%"];
				$phwhere['bs_id'] = $this->busId;
				
				$ordernum = $result = db('physical_users')->field("order_serial_number")
                    ->where($phwhere)
                    ->order("registertime desc")
                    ->find();
                if ($ordernum) {
                    $resultNum = $ordernum['order_serial_number'] + 1;
                } else {
                    $resultNum = date("Ymd", time()) . "0001";
                }
                $emp = db('employee')->field('name,id')
                    ->where('id', '=', $params['parent'])
                    ->find();

                $param['id'] = '';
                $param['name'] = $params['name'];
                $param['identitycard'] = $params['identitycard'];
                $param['type'] = $params['type'];
                $param['sex'] = $params['sex'];
                $param['images'] = $params['avatar'];
                $param['age'] = $params['age'];
                $param['phone'] = $params['phone'];
                $param['express'] = $params['express'];
                $param['bs_id'] = $this->busId;
                $param['employee'] = $emp['name'];
                $param['employee_id'] = $params['parent'];
                $param['company'] = $params['company'];
                $param['order_serial_number'] = $resultNum;
                $param['barcode'] = $params['barcode'];
                Db::startTrans();
                try {
                    $order_detail = $this->order_detial($resultNum);
                    $result = $this->model->validate("Register.add")->save($param);
                    if ($result === false) {
                        Db::rollBack();
                        $this->error($this->model->getError());
                    }
                }catch (Exception $e){                    
                    Db::rollBack();
                    $this->error($this->model->getError());
                }
                
                if (strlen($bs_id['bs_id']) == 1) {
                    $bs_id['bs_id'] = "00" . $bs_id['bs_id'];
                } else if (strlen($bs_id['bs_id']) == 2) {
                    $bs_id['bs_id'] = "0" . $bs_id['bs_id'];
                }
                $par['user_id'] = $this->model->id;
                $par['order_serial_number'] = $resultNum;
                $par['bus_number'] = $bs_id['bs_uuid'];
                $par['charge'] = $bs_id['charge'];
                $par['order_status'] = '0';
                $par['obtain_employ_type'] = $param['employee'];                
                $par['obs_id'] = $this->busId;
                $prefix = "03".$bs_id['bs_id'] .mt_rand(0,9). date("y", time());
                $ob_where['obtain_employ_number'] = ["like",$prefix."%"];
                $ob_where['obs_id'] = $this->busId;
                $par['identitycard'] = $param['identitycard'];//向order表添加身份证号
                $par['is_print'] = 0;//order表打印状态，0未打卡，1打卡
                $ob_num = $this->order->where($ob_where)->lock(true)->find();
                if(empty($ob_num['obtain_employ_number'])){
                    $obnum = $prefix."000001";
                }else{
                    $obnum = $ob_num['obtain_employ_number']+1;
                }
                $par['obtain_employ_number'] = $obnum;
                if ($params['express']) {
                    $par['address'] = $params['address'];
                }
                try {
                    $order = $this->order->save($par);
                    if ($order === false) {
                        Db::rollBack();
                        $this->error($this->model->getError());
                    }
                    $order_detail_save = $this->orderd->saveAll($order_detail);
                    if($order_detail_save === false){
                        Db::rollBack();
                        $this->error($this->model->getError());
                    }
                } catch (Exception $e) {
                    Db::rollBack();
                }
                Db::commit();
                if($bs_id['isprint']){                    
                    $param['sex'] = $params['sex']==0?"男":"女";
                    $param['time'] = date("Y年m月d日",time());
                    $param['print_form_id'] = $bs_id['print_form_id'];
                    $html = $this->get_html($param);
                    echo $html;
                }
                $this->success();
            }
            $this->error();
        }
        $this->view->assign("isprint", $bs_id['isprint']);
        $this->view->assign("congye", $bs_id['profession']);
        $this->view->assign("physcal_type", $physcal_type);
        return $this->view->fetch();
    }

    // 创建订单详细信息
    public function order_detial($orderNum)
    {
        $ins = db('inspect')->field("id,name,type")
            ->where("parent", "=", "0")
            ->select();
        $list = array();
        foreach ($ins as $res) {
            $param['order_serial_number'] = $orderNum;
            $param['physical'] = $res['type'];
            $param['physical_result'] = 2;
            $param['physical_result_ext'] = '';
            $param['doctor'] = '';
            $param['item'] = $res['id'];
            $param['odbs_id'] = $this->busId;
            $list[] = $param;
        }
        return $list;        
    }

    public function edit($ids = '')
    {
        $list = $this->model->get([
            'id' => $ids
        ]);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                
                $emp = db('employee')->field('name,id')
                ->where('id', '=', $params['parent'])
                ->find();
                $param['name'] = $params['name'];
                $param['identitycard'] = $params['identitycard'];
                $param['type'] = $params['type'];
                $param['sex'] = $params['sex'];
//                 $param['images'] = $params['avatar'];
                $param['age'] = $params['age'];
                $param['phone'] = $params['phone'];
                $param['employee'] = $emp['name'];
                $param['employee_id'] = $params['parent'];
                $param['company'] = $params['company'];
                $where['id'] = $ids;
                $result = $this->model->where($where)->update($param);
                if ($result)
                    $this->success();
                else
                    $this->error();
            }
        }
        $this->view->assign("row", $list);
        return $this->view->fetch();
    }
    public function del($ids = ''){
        Db::startTrans();
        try{            
            $user = $this->model->where('id',$ids)->field('bs_id,order_serial_number')->lock(true)->find();
            if(!$user){
                $this->error($this->model->getError());
            }
            $where['odbs_id'] = $user['bs_id'];
            $where['order_serial_number'] = $user['order_serial_number'];
            $order = $this->orderd->where($where)->field('physical_result')->find();
            
            $data['is_del'] = 1;
            try{
                $this->model->save($data,['id'=>$ids]);
            }catch (Exception $e1){
                Db::rollBack();
                $this->error($this->model->getError());
            }      
        }catch (Exception $e){
            Db::rollBack();
            $this->error($this->model->getError());
        }
        Db::commit();
        $this->success("删除成功");
    }

/**
 * 批量打印体检单
 * 
 */
    public function physical_table()
    {
        $params = $this->request->get('id');
        $print = $this->getPrint($params);
        $printArr = array();
        foreach ($print as $row){
            $row['sex'] = $row['sex'] == 0?"男":"女";
            $printArr[] = $this->lodopJs($row);
        }
        $str = '';
        foreach ($printArr as $row) {
            $str .= $row;
            
        }
        // 获取体检单位        
        $bs_id = db("admin")->alias("a")
            ->field("b.print_form_id")
            ->join("business b", "a.businessid = b.bs_id")
            ->where("id", "=", $this->auth->id)
            ->find();
           $print = $bs_id['print_form_id'];
        $html = $this->getMulit_html();
        echo "<script language=\"javascript\" src=\"/LodopFuncs.js\"></script>
        <script src=\"https://cdn.bootcss.com/jquery/3.4.1/jquery.js\"></script>
            <script>
            $(document).ready(function () {
                $(\"#prints\").click(function () {
                    setTimeout(\"print()\",500);//延时3秒
                })
                $(\"#prints\").trigger(\"click\");
            })
            
			function print() {
                LODOP = getLodop();
                LODOP.PRINT_INITA(9, 0, 794, 1122, \"体检单\");
                {$str}
                
		        if (LODOP.SET_PRINTER_INDEX({$print}))
                LODOP.PREVIEW();
            }
            </script>            
		<button id=\"prints\" style=\"display:none\">打印文件</button>{$html}";
        $this->success();
    }
    
    protected function lodopJs($print){
        $lodop = <<<EOF
        LODOP.NewPage();
        LODOP.SET_PRINT_MODE("PRINT_NOCOLLATE", 1);
        LODOP.ADD_PRINT_IMAGE(70, 60, 500, 100, "<img src=\"http://39.100.89.92:8082/barcodegen/html/image.php?filetype=PNG&dpi=85&scale=1&rotation=0&font_family=Arial.ttf&font_size=11&text={$print['order_serial_number']}&thickness=55&start=A&code=BCGcode128\">");
        LODOP.ADD_PRINT_TEXT(30, 170, 465, 45, "河北省食品药品从业人员健康检查表");
        LODOP.SET_PRINT_STYLEA(0, "FontName", "黑体");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 20);
        LODOP.SET_PRINT_STYLEA(0, "Alignment", 2);
        LODOP.ADD_PRINT_SHAPE(4, 150, 46, 702, 2, 0, 1, "#000000");
        LODOP.ADD_PRINT_TEXT(250, 70, 90, 26, "体检日期:");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 11);
        LODOP.ADD_PRINT_TEXT(250, 340, 160, 26, "编号:{$print['order_serial_number']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(250, 140, 170, 26, "{$print['time']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);

        LODOP.ADD_PRINT_TEXT(170, 70, 90, 26, "姓名:");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(170, 120, 60, 26, "{$print['name']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(170, 210, 80, 26, "性别:");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(170, 260, 60, 26, "{$print['sex']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(170, 300, 80, 26, "年龄:");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(170, 350, 60, 26, "{$print['age']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);

        LODOP.ADD_PRINT_TEXT(170, 400, 102, 26, "从业类别:");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(170, 480, 80, 26, "{$print['employee']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(210, 320, 102, 26, "从业单位:");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(210, 400, 200, 26, "{$print['company']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);

        LODOP.ADD_PRINT_TEXT(210, 70, 100, 26, "身份证号:");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(210, 150, 180, 26, "{$print['identitycard']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);

        
        LODOP.ADD_PRINT_TEXT(1000, 70, 80, 26, "姓名:");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(1000, 120, 200, 26, "{$print['name']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);

        LODOP.ADD_PRINT_TEXT(1000, 280, 100, 26, "身份证号:");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(1000, 370, 200, 26, "{$print['identitycard']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(1000, 200, 80, 26, "性别:");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(1000, 240, 200, 26, "{$print['sex']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);

        LODOP.ADD_PRINT_TEXT(1030, 70, 102, 26, "体检日期: ");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 11);
        LODOP.ADD_PRINT_TEXT(1030, 140, 170, 26, "{$print['time']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(1030, 280, 170, 26, "体检编号：");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(1030, 370, 160, 26, "{$print['order_serial_number']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);

        LODOP.ADD_PRINT_IMAGE(160, 600, 102, 126, "<img style=\"width:102px;height:126px;\" src=\"data:image/jpeg;base64,{$print['images']}\"/>");
        LODOP.SET_PRINT_STYLEA(0, "TransColor", "#0F0100");
        LODOP.ADD_PRINT_TABLE(290, 56, 680, 760, document.getElementById("print_8").innerHTML);
        
        LODOP.ADD_PRINT_TEXT(960, 300, 465, 45, "从业人员健康回执单");
        LODOP.SET_PRINT_STYLEA(0, "FontName", "黑体");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);

        LODOP.ADD_PRINT_IMAGE(990, 580, 200, 126, "<img src=\"http://39.100.89.92:8082/barcodegen/html/image.php?filetype=PNG&dpi=85&scale=1&rotation=0&font_family=Arial.ttf&font_size=11&text={$print['order_serial_number']}&thickness=55&start=A&code=BCGcode128\">");
EOF;
        return $lodop;
    }
    
    protected function getMulit_html(){
        
        $js = <<<EOF
        <div id="print_8" style="display:none">
        			<table class="MsoNormalTable" width="670" style="border-collapse:collapse;border:none;" cellspacing="0"	cellpadding="0" border="1">
        				<tbody>
        					<tr>
        						<td rowspan="2" style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">既往</span></b><b><span style="font-family:宋体;"></span></b>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">病史</span></b><b><span style="font-family:宋体;"></span></b>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="66">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">病</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>名</span></span></b>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">肝</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>类</span></span></b>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">痢</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>疾</span></span></b>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">伤</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>寒</span></span></b>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">肺结核</span></b><b><span style="font-family:宋体;"></span></b>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">皮肤病</span></b><b><span style="font-family:宋体;"></span></b>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">其</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>它</span></span></b>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="66">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">患病时间</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="4" style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">体征</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">心</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="230">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="41">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">肝</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="4" style="border:solid windowtext 1.0pt;" width="182">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">肺</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="230">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="41">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">脾</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="4" style="border:solid windowtext 1.0pt;" width="182">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">皮肤</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="12" style="border:solid windowtext 1.0pt;" width="454">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">手癣 指甲癣 手部湿疹 银屑</span><span
        									style="font-family:宋体;">(<span>或鳞屑</span>)<span>病 渗出性皮肤病 化脓性皮肤病</span></span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">其它</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="230">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="77">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">医师签名</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="2" style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="font-size: 14px;">
        								视力及辨色力<br>（直接接触药品质量检验、验收、养护人员）
        							</p>
        							<p class="MsoNormal">
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="62">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体; padding-left: 10px">视力</span>
                                        <span style="font-family:宋体;"></span>
        							</p>
        							
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="102">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">左</span>
        								<span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="104">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="77">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">右</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="62">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">辨色力</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="5" style="border:solid windowtext 1.0pt;" width="206">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="77">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">医师签名</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="text-align:center;" align="center">
        								<span style="font-family:宋体;">摄影检查</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="text-align:center;" align="center">
        								<span style="font-family:宋体;">胸部</span><span style="font-family:宋体;">x<span>射线</span></span>
        							</p>
        						</td>
        						<td colspan="13" style="border:solid windowtext 1.0pt;" width="491">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        							<p class="MsoNormal">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-indent:210.0pt;">
        								<span style="font-family:宋体;">医师签名</span><span style="font-family:宋体;">:</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="7" style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">实 化</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">验 验</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">室 单</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">检 附</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">查 后</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="4" style="border:solid windowtext 1.0pt;" width="137">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">检</span><span style="font-family:宋体;"><span>&nbsp;
        									</span><span>查</span><span>&nbsp; </span><span>项</span><span>&nbsp;
        									</span><span>目</span></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">单</span><span style="font-family:宋体;"><span>&nbsp;
        									</span><span>位</span><span>&nbsp; </span><span>结</span><span>&nbsp;
        									</span><span>果</span></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">检验师签名</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="2" style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">大便</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">培养</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">痢疾杆菌</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" rowspan="2" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">伤寒或副伤寒</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="3" style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">肝</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">功</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">能</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">谷丙转氨酶</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">HAV-IgM </span><sup><span
        										style="font-size:14.0pt;font-family:宋体;">*</span></sup><sup><span
        										style="font-family:宋体;"></span></sup>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">HEV-IgM </span><sup><span
        										style="font-size:14.0pt;font-family:宋体;">*</span></sup><sup><span
        										style="font-family:宋体;"></span></sup>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="4" style="border:solid windowtext 1.0pt;" width="137">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">其它</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="14" style="border:solid windowtext 1.0pt;" width="565" valign="top">
        							<p style="height:30px;">检查结论:</p>
        							<p style="height:20px;">
        								    主检医师签名(公章):
        									<span style="display:inline-block;width:120px;height:20px;float:right;">
        									年&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        									月&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        									日</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="14" style="border-bottom:none; border-left: none;border-right: none;" width="565" 
        							valign="middle">
        							<span style="padding-left:10px;font-size:12.0pt;font-family:仿宋_GB2312; color: red; height: 40px; display: inline-block; line-height:40px">
        								*说明：发现谷丙转氨酶异常的，加做
        								<span style="font-size:12.0pt;font-family:宋体;">HAV-IgM、HEV-IgM两个指标。</span>
        							</span>
        							<p style="border:1px dashed #000000;border-bottom:none;border-left: none;border-right: none;">
        							
        							</p>
        						</td>
        					</tr>
        
        				</tbody>
        			</table>
        		</div>
EOF;
        return $js;
    }
    public function get_html($print)
    {
        return <<<EOF
        	<!DOCTYPE html>
        	<html>
        	<head>
        		<script language="javascript" src="/LodopFuncs.js"></script>
        		<script src="https://cdn.bootcss.com/jquery/3.4.1/jquery.js"></script>
        		<script>
        			$(document).ready(function () {
        				$("#print").click(function () {
        					setTimeout("print()",500);//延时3秒
        				})
        				$("#print").trigger("click");
        			})
        			function print() {
                        
        				LODOP = getLodop();
        				LODOP.PRINT_INITA(9, 0, 794, 1122, "打印控件功能演示_Lodop功能_在线编辑获得程序代码");
        				LODOP.SET_PRINT_MODE("PRINT_NOCOLLATE", 1);
                        LODOP.ADD_PRINT_IMAGE(40, 60, 102, 126, "<img src=\"http://39.100.89.92:8082/barcodegen/html/image.php?filetype=PNG&dpi=85&scale=1&rotation=0&font_family=Arial.ttf&font_size=8&text=123456789541&thickness=55&start=A&code=BCGcode128\">");
                        LODOP.ADD_PRINT_TEXT(10, 50, 465, 45, "河北省食品药品从业人员健康检查表");

        				LODOP.SET_PRINT_STYLEA(0, "FontName", "黑体");
        				LODOP.SET_PRINT_STYLEA(0, "FontSize", 20);
        				LODOP.SET_PRINT_STYLEA(0, "Alignment", 2);
        				LODOP.ADD_PRINT_SHAPE(4, 150, 46, 702, 2, 0, 1, "#000000");
        				LODOP.ADD_PRINT_TEXT(122, 50, 79, 26, "体检日期: ");
        				LODOP.SET_PRINT_STYLEA(0, "FontSize", 11);
        				LODOP.ADD_PRINT_TEXT(122, 570, 160, 26, "编号：{$print['order_serial_number']}");
        				LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        				LODOP.ADD_PRINT_TEXT(122, 140, 170, 26, "{$print['time']}");
        				LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);

                        LODOP.ADD_PRINT_TEXT(170, 70, 50, 26, "姓名:");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(170, 120, 60, 26, "{$print['name']}");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(170, 240, 60, 26, "性别:");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(170, 300, 60, 26, "{$print['sex']}");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(170, 400, 60, 26, "年龄");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(170, 460, 60, 26, "{$print['age']}");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                
                        LODOP.ADD_PRINT_TEXT(210, 70, 80, 26, "从业类别");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(210, 150, 80, 26, "{$print['employee']}");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(210, 300, 80, 26, "体检单位");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(210, 380, 80, 26, "{$print['company']}");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                
                        LODOP.ADD_PRINT_TEXT(250, 70, 80, 26, "身份证号");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(250, 150, 200, 26, "{$print['identitycard']}");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);


                        LODOP.ADD_PRINT_TEXT(980, 70, 80, 26, "姓名:");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(980, 120, 200, 26, "{$print['name']}");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                
                        LODOP.ADD_PRINT_TEXT(980, 200, 100, 26, "身份证号:");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(980, 270, 200, 26, "{$print['identitycard']}");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(1000, 70, 80, 26, "性别:");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(1000, 120, 200, 26, "{$print['sex']}");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                
                        LODOP.ADD_PRINT_TEXT(1000, 150, 79, 26, "体检日期: ");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 11);
                        LODOP.ADD_PRINT_TEXT(1000, 220, 170, 26, "{$print['time']}");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(1000, 350, 170, 26, "体检编号：");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(1000, 420, 160, 26, "{$print['order_serial_number']}");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);



        				LODOP.ADD_PRINT_IMAGE(160, 600, 102, 126, "<img style=\"width:102px;height:126px;\" src=\"data:image/jpeg;base64,{$print['images']}\"/>");
        				LODOP.SET_PRINT_STYLEA(0, "TransColor", "#0F0100");
        				LODOP.ADD_PRINT_TABLE(290, 56, 680, 760, document.getElementById("print_8").innerHTML);

		                if (LODOP.SET_PRINTER_INDEX({$print['print_form_id']}))
        				LODOP.PREVIEW();
        			}
        		</script>
        	</head>
        	
        	<body>
        		<button id="print" style="display:none">打印文件</button>
        		<div id="print_8" style="display:none">
        			<table class="MsoNormalTable" width="670" style="border-collapse:collapse;border:none;" cellspacing="0"	cellpadding="0" border="1">
        				<tbody>
        					<tr>
        						<td rowspan="2" style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">既往</span></b><b><span style="font-family:宋体;"></span></b>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">病史</span></b><b><span style="font-family:宋体;"></span></b>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="66">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">病</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>名</span></span></b>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">肝</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>类</span></span></b>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">痢</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>疾</span></span></b>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">伤</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>寒</span></span></b>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">肺结核</span></b><b><span style="font-family:宋体;"></span></b>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">皮肤病</span></b><b><span style="font-family:宋体;"></span></b>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">其</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>它</span></span></b>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="66">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">患病时间</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="4" style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">体征</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">心</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="230">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="41">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">肝</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="4" style="border:solid windowtext 1.0pt;" width="182">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">肺</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="230">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="41">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">脾</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="4" style="border:solid windowtext 1.0pt;" width="182">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">皮肤</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="12" style="border:solid windowtext 1.0pt;" width="454">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">手癣 指甲癣 手部湿疹 银屑</span><span
        									style="font-family:宋体;">(<span>或鳞屑</span>)<span>病 渗出性皮肤病 化脓性皮肤病</span></span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">其它</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="230">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="77">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">医师签名</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="2" style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="font-size: 14px;">
        								视力及辨色力<br>（直接接触药品质量检验、验收、养护人员）
        							</p>
        							<p class="MsoNormal">
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="62">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体; padding-left: 10px">视力</span>
                                        <span style="font-family:宋体;"></span>
        							</p>
        							
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="102">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">左</span>
        								<span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="104">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="77">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">右</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="62">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">辨色力</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="5" style="border:solid windowtext 1.0pt;" width="206">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="77">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">医师签名</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="text-align:center;" align="center">
        								<span style="font-family:宋体;">摄影检查</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="text-align:center;" align="center">
        								<span style="font-family:宋体;">胸部</span><span style="font-family:宋体;">x<span>射线</span></span>
        							</p>
        						</td>
        						<td colspan="13" style="border:solid windowtext 1.0pt;" width="491">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        							<p class="MsoNormal">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-indent:210.0pt;">
        								<span style="font-family:宋体;">医师签名</span><span style="font-family:宋体;">:</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="7" style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">实 化</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">验 验</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">室 单</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">检 附</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">查 后</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="4" style="border:solid windowtext 1.0pt;" width="137">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">检</span><span style="font-family:宋体;"><span>&nbsp;
        									</span><span>查</span><span>&nbsp; </span><span>项</span><span>&nbsp;
        									</span><span>目</span></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">单</span><span style="font-family:宋体;"><span>&nbsp;
        									</span><span>位</span><span>&nbsp; </span><span>结</span><span>&nbsp;
        									</span><span>果</span></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">检验师签名</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="2" style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">大便</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">培养</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">痢疾杆菌</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" rowspan="2" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">伤寒或副伤寒</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="3" style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">肝</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">功</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">能</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">谷丙转氨酶</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">HAV-IgM </span><sup><span
        										style="font-size:14.0pt;font-family:宋体;">*</span></sup><sup><span
        										style="font-family:宋体;"></span></sup>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">HEV-IgM </span><sup><span
        										style="font-size:14.0pt;font-family:宋体;">*</span></sup><sup><span
        										style="font-family:宋体;"></span></sup>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="4" style="border:solid windowtext 1.0pt;" width="137">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">其它</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="14" style="border:solid windowtext 1.0pt;" width="565" valign="top">
        							<p class="MsoNormal" style="margin-left:.85pt;text-indent:5.25pt;">
        								<span style="font-family:宋体;">检查结论</span><span style="font-family:宋体;">:</span>
        							</p>
        							<p class="MsoNormal" style="margin-left:.85pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        							<p class="MsoNormal" style="margin-left:.85pt;text-indent:194.25pt;">
        								<span style="font-family:宋体;">主检医师签名</span>
        								<span style="font-family:宋体;">:
        									<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
        									<span>(公章)</span>
        								</span>
        							</p>
        							<p class="MsoNormal" style="margin-left:111.7pt;">
        								<span style="font-family:宋体;">
        									<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
        									<span>年</span><span>&nbsp;&nbsp;
        									</span><span>月</span><span>&nbsp;&nbsp;</span><span>日</span>
        								</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="14" style="border-bottom:none; border-left: none;border-right: none;" width="565"
        							valign="middle">
        							<span
        								style="padding-left:10px;font-size:12.0pt;font-family:仿宋_GB2312; color: red; height: 40px; display: inline-block; line-height:40px">
        								*说明：发现谷丙转氨酶异常的，加做
        								<span style="font-size:12.0pt;font-family:宋体;">HAV-IgM、HEV-IgM两个指标。</span>
        							</span>
        						</td>
        					</tr>
        				</tbody>
        			</table>
        		</div>
        	</body>
        	    
        	</html>
EOF;
    }

    protected function getPrint($userid)
    {
        $result = db("physical_users")->where("id", "in", $userid)->select();
        foreach ($result as $row => $v){
            $v['sex'] = $v['sex'] == 0 ? "男" : "女";
            $result[$row]['time'] = date("Y年m月d日",time());
        }
        return $result;
    }

/**
 * 打印条形码
 * */
    public function barCode()
    {
        $params = $this->request->get('id');
        $print = $this->getPrint($params);
        $printArr = array();
        foreach ($print as $row){
            $row['sex'] = $row['sex'] == 0?"男":"女";
            $printArr[] = $this->lodopJs1($row);
        }
        $str = '';
        foreach ($printArr as $row) {
            $str .= $row;
        }
        // 获取体检单位
        $bs_id = db("admin")->alias("a")
            ->field("b.print_form_id")
            ->join("business b", "a.businessid = b.bs_id")
            ->where("id", "=", $this->auth->id)
            ->find();
        $print = $bs_id['print_form_id'];
        echo "<script language=\"javascript\" src=\"/LodopFuncs.js\"></script>
        <script src=\"https://cdn.bootcss.com/jquery/3.4.1/jquery.js\"></script>
            <script>
            $(document).ready(function () {
                $(\"#prints1\").click(function () {
                    setTimeout(\"print1()\",500);//延时3秒
                })
                $(\"#prints1\").trigger(\"click\");
            })
            
			function print1() {
                LODOP = getLodop();
                LODOP.PRINT_INITA(9, 0, 30, 50, \"条形码\");
                {$str}     
                LODOP.PREVIEW();
            }
            </script>            
	<button id=\"prints1\" style=\"display:none\">打印文件</button>";
//	<button id=\"prints1\" style=\"display:none\">打印文件</button>";
        $this->success();
    }

    protected function lodopJs1($print){
        $lodop = <<<EOF
        LODOP.NewPage();
        LODOP.SET_PRINT_MODE("PRINT_NOCOLLATE", 1);
        LODOP.ADD_PRINT_IMAGE(10, 10, "30mm", "50mm", "<img src=\"http://39.100.89.92:8082/barcodegen/html/image.php?filetype=PNG&dpi=85&scale=1&rotation=0&font_family=Arial.ttf&font_size=11&text={$print['order_serial_number']}&thickness=55&start=A&code=BCGcode128\">");
EOF;
        return $lodop;
    }




}
