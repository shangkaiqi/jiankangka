<?php
namespace app\index\controller\service;

use app\common\controller\Backend;
use app\index\controller\Common;
use app\common\controller\Frontend;

/**
 * 体检列表
 *
 * @icon fa fa-circle-o
 */
class Search extends Frontend
{

    protected $model = null;

    protected $comm = null;

    // 开关权限开启
    protected $noNeedRight = [
        '*'
    ];

    protected $noNeedLogin = [
        'expUser'
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
        $comm = new Common();
        $this->comm = $comm;
        $this->view->assign("pid", $comm->getEmployee());
    }

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
            foreach ($list as $row) {
                $row['registertime'] = date("Y-m-d H:i:s", $row['registertime']);
                // $row->visible(['name','identitycard','type','sex','age','phone','employee','company','order_serial_number']);
                // $row->visible(['order']);
                // $row->getRelation('order')->visible(['order_id', 'order_serial_number', 'bus_number']);
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

    public function edit($ids = '')
    {
        $list = $this->model->get([
            'id' => $ids
        ]);
        if ($this->request->isPost()) {
            $params = $this->request->isPost("row/a");
            if ($params) {
                
            }
        }
        $this->view->assign("row", $list);
        return $this->view->fetch();
    }
    /**
     * 打印复印单
     */
    public function printword(){  
        $params = $this->request->get("id");
        $print = $this->getPrint($params);
        $time = date("Y年m月d日", time());
        $hosp = "";
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
                                LODOP.ADD_PRINT_IMAGE(10, 60, 102, 126, "<img src=\"http://39.100.89.92:8082/barcodegen/html/image.php?filetype=PNG&dpi=85&scale=3&rotation=0&font_family=Arial.ttf&font_size=19&text={$print['order_serial_number']}&thickness=35&start=A&code=BCGcode128\">");
                				LODOP.ADD_PRINT_TEXT(43, 150, 465, 45, "河北省食品药品从业人员健康检查表");
                				LODOP.SET_PRINT_STYLEA(0, "FontName", "黑体");
                				LODOP.SET_PRINT_STYLEA(0, "FontSize", 20);
                				LODOP.SET_PRINT_STYLEA(0, "Alignment", 2);
                				LODOP.ADD_PRINT_SHAPE(4, 150, 46, 702, 2, 0, 1, "#000000");
                				LODOP.ADD_PRINT_TEXT(122, 50, 79, 26, "体检日期: ");
                				LODOP.SET_PRINT_STYLEA(0, "FontSize", 11);
                				LODOP.ADD_PRINT_TEXT(122, 570, 160, 26, "编号：{$print['order_serial_number']}");
                				LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                				LODOP.ADD_PRINT_TEXT(122, 140, 170, 26, "{$time}");
                				LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                				LODOP.ADD_PRINT_HTM(160, 50, 465, 126, document.getElementById("print_6").innerHTML);
                				LODOP.ADD_PRINT_IMAGE(160, 600, 102, 126, "<img src=\"https://s.cn.bing.net/th?id=ODL.9e05e5966ac2c0e81bd7a7d5b2bd29ec&w=146&h=146&c=7&rs=1&qlt=80&pid=RichNav\"/>");
                				LODOP.SET_PRINT_STYLEA(0, "TransColor", "#0F0100");
                				LODOP.ADD_PRINT_TABLE(290, 56, 680, 760, document.getElementById("print_8").innerHTML);

		                        if (LODOP.SET_PRINTER_INDEX({$hosp['print_form_id']}))
                				LODOP.PREVIEW();
                			}
                		</script>
                	</head>
                	
                	<body>
                		<button id="print" style="display:none">打印文件</button>
                		<div id="print_6" style="display:none">
                			<table width="500" style="float: left;">
                				<tr height="40">
                					<td>姓名：<span style="text-decoration: underline; font-size: 16px;">{$print['name']}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></td>
                					<td>性别:<span style="text-decoration: underline; font-size: 16px;">{$print['sex']}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></td>
                					<td>年龄:<span style="text-decoration: underline; font-size: 16px;">{$print['age']}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></td>
                				</tr>
                				<tr height="40">
                					<td>从业类别：<span style="text-decoration: underline; font-size: 16px;">{$print['employee']}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></td>
                					<td>体检单位：<span style="text-decoration: underline; font-size: 16px;">{$print['company']}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></td>
                				</tr>
                				<tr height="40">
                					<td>身份证号:<span style="text-decoration: underline; font-size: 16px;">{$print['identitycard']}&nbsp;&nbsp;&nbsp;</span></td>
                				</tr>
                			</table>
                		</div>
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
                								<span style="font-family:宋体;">正常</span>
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
                								<span style="font-family:宋体;">正常</span>
                							</p>
                						</td>
                						<td colspan="2" style="border:solid windowtext 1.0pt;" width="41">
                							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
                								<span style="font-family:宋体;">脾</span><span style="font-family:宋体;"></span>
                							</p>
                						</td>
                						<td colspan="4" style="border:solid windowtext 1.0pt;" width="182">
                							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
                								<span style="font-family:宋体;">正常</span>
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
                								<span style="font-family:宋体;">无</span>
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
                								<span style="font-family:宋体;">无</span>
                							</p>
                						</td>
                						<td colspan="3" style="border:solid windowtext 1.0pt;" width="77">
                							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
                								<span style="font-family:宋体;">医师签名</span><span style="font-family:宋体;"></span>
                							</p>
                						</td>
                						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
                							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
                								<span style="font-family:宋体;">{$docter['body']}</span>
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
                								style="padding-left:10px;font-size:14.0pt;font-family:仿宋_GB2312; color: red; height: 60px; display: inline-block; line-height:60px">
                								*说明：发现谷丙转氨酶异常的，加做
                								<span style="font-size:14.0pt;font-family:宋体;">HAV-IgM、HEV-IgM两个指标。</span>
                							</span>
                						</td>
                					</tr>
                				</tbody>
                			</table>
                		</div>
                	</body>
                	
                	</html>
EOF;
        $this->success("", "index");
    }
    
    
    protected function getPrint($userid)
    {
        $result = db("physical_users")->where("id", "=", $userid)->find();
        $result['sex'] = $result['sex'] == 0 ? "男" : "女";
        $result['employee'] = $this->comm->getEmpName($result['employee']);
        return $result;
    }
   //批量健康卡
    public function printMulit()
    {
        $params = $this->request->get("id");
        $ids = explode(",", $params);
        $uid = db("physical_users")->where('id', "in", $ids)->select();
        // 循环遍历每一个用户
        $printArr = array();
        foreach ($uid as $row) {
            // 获取订单信息
            $where['order_serial_number'] = $row['order_serial_number'];
            $printInfo = db("order")->where($where)->find();
            // 获取体检单位
            $hosp = db("business")->field("busisess_name,avatar,print_form_id")
                ->where("bs_uuid", "=", $printInfo['bus_number'])
                ->find();
            $printInfo['name'] = $row['name'];
            $printInfo['sex'] = $row['sex'] == 0 ? "男" : "女";
            $printInfo['employee'] = $row['employee'];
            $printInfo['company'] = $hosp['busisess_name'];
            $printInfo['avatar'] = $hosp['avatar'];
            $printInfo['images'] = $row['images'];
            $printInfo['endtime'] = date('Y-m-d',strtotime('+1year'));
            $printInfo['physictype'] = $row['employee_id'];
            
            
            $date['employ_num_time'] = time();
            $where['obs_id']= $this->busId;
            $where['order_serial_number']= $row['order_serial_number'];
            db('order')->where($where)->update($date);   
            
            $printArr[] = $this->html($printInfo);
            
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
                $(\"#print\").click(function () {
                    setTimeout(\"print()\",500);//延时3秒
                })
                $(\"#print\").trigger(\"click\");
            })
                
			function print() {
                LODOP = getLodop();
                LODOP.PRINT_INITA(\"0\", \"0\", \"86.6mm\", \"56.4mm\", \"打印控件功能演示_Lodop功能_在线编辑获得程序代码\");
                {$str}

		        if (LODOP.SET_PRINTER_INDEX({$print}))
                LODOP.PREVIEW();
            }
            </script>

		<button id=\"print\" style=\"display:none\">打印文件</button>";
        $this->success('', 'index', "", 1);
    }

    private function html($print)
    {
        $html = <<<EOF
	        LODOP.NewPage();
            LODOP.ADD_PRINT_TEXT("32mm", "25mm", "100", "30", "{$print['name']}");//姓名
            LODOP.SET_PRINT_STYLEA(0, "FontName", "华文楷体");
            LODOP.SET_PRINT_STYLEA(0, "FontSize", 8);
            LODOP.ADD_PRINT_TEXT("32mm", "48mm", "100", "30", "{$print['sex']}");//性别
            LODOP.SET_PRINT_STYLEA(0, "FontName", "华文楷体");
            LODOP.SET_PRINT_STYLEA(0, "FontSize", 8);
            LODOP.ADD_PRINT_TEXT("36mm", "25mm", "100", "30", "{$print['employee']}");//从业类别
            LODOP.SET_PRINT_STYLEA(0, "FontName", "华文楷体");
            LODOP.SET_PRINT_STYLEA(0, "FontSize", 8);
            LODOP.ADD_PRINT_TEXT("40.5mm", "25mm", "100", "30", "{$print['obtain_employ_number']}"); //健康证号
            LODOP.SET_PRINT_STYLEA(0, "FontName", "华文楷体");
            LODOP.SET_PRINT_STYLEA(0, "FontSize", 8);
            LODOP.ADD_PRINT_TEXT("45mm", "25mm", "100", "30", "{$print['endtime']}");//到期时间
            LODOP.SET_PRINT_STYLEA(0, "FontName", "华文楷体");
            LODOP.SET_PRINT_STYLEA(0, "FontSize", 8);
            LODOP.ADD_PRINT_IMAGE("30mm","62mm","16mm","20mm","<img style=\"position:absolute;left:1px;top:1px;\" height='75' width='62' src=\"data:image/jpeg;base64,{$print['images']}\"/><img style=\"position:absolute;left:10px;top:30px;\" height='50' width='50' border='0' src='http://39.100.89.92:8080/{$print['avatar']}' style='z-index: 999'/>");
            LODOP.ADD_PRINT_IMAGE("29mm","51mm","11.91mm","11.91mm","<img src=\"http://39.100.89.92:8080/qrcode/build?text=http://39.100.89.92:8080&label=FastAdmin&size=35&padding=2\">"); //二维码
            LODOP.ADD_PRINT_TEXT("50mm", "25mm", "100", "30", "{$print['company']}");//体检单位
            LODOP.SET_PRINT_STYLEA(0, "FontName", "华文楷体");
            LODOP.SET_PRINT_STYLEA(0, "FontSize", 8);
EOF;
        $html1 = <<<EOF
	        LODOP.NewPage();
            LODOP.ADD_PRINT_TEXT("36mm", "48mm", 97, 30, "{$print['employee']}");  //从业类别
            LODOP.SET_PRINT_STYLEA(0, "FontSize", 9);
            LODOP.ADD_PRINT_TEXT("40mm", "48mm", 100, 30, "{$print['name']}");  //姓名
            LODOP.SET_PRINT_STYLEA(0, "FontName", "华文楷体");
            LODOP.SET_PRINT_STYLEA(0, "FontSize", 9);
            LODOP.ADD_PRINT_TEXT("40mm", "76mm", 50, 30, "{$print['sex']}");  //性别
            LODOP.SET_PRINT_STYLEA(0, "FontSize", 9);
            LODOP.SET_PRINT_STYLEA(0, "Angle", 4);
            LODOP.ADD_PRINT_IMAGE("25mm","10mm","16mm","20mm","<img style=\"position:absolute;left:1px;top:1px;\" height='75' width='62' src=\"data:image/jpeg;base64,{$print['images']}\"/><img style=\"position:absolute;left:10px;top:30px;\" height='50' width='50' border='0' src='http://39.100.89.92:8080/{$print['avatar']}' style='z-index: 999'/>");//图片
            LODOP.ADD_PRINT_IMAGE("25mm","27mm","11.91mm","11.91mm","<img src=\"http://39.100.89.92:8080/qrcode/build?text=http://39.100.89.92:8080&label=FastAdmin&size=35&padding=2\">"); //二维码
            LODOP.ADD_PRINT_TEXT("44.5mm", "48mm", 157, 30, "2019年12月31日");//到期时间
            LODOP.SET_PRINT_STYLEA(0, "FontName", "华文楷体");
            LODOP.SET_PRINT_STYLEA(0, "FontSize", 9);
            LODOP.ADD_PRINT_TEXT("50mm", "23mm", 230, 29, "{$print['obtain_employ_number']}"); //健康正号
            LODOP.SET_PRINT_STYLEA(0, "FontSize", 9);
EOF;
        return $print['physictype']==1 ? $html : $html1;
    }

    /**
     *
     * @desc导出Excel
     */
    public function expUser()
    {
        $params = $this->request->get('id');        
        // 导出Excel
        $xlsCell = array(
            array(
                'id',
                '账号序列'
            ),
            array(
                'name',
                '名字'
            ),
            array(
                'identitycard',
                '身份证号'
            ),
            array(
                'sex',
                '性别'
            ),
            array(
                'age',
                '院系'
            ),
            array(
                'phone',
                '电话'
            ),
            array(
                'employee',
                '从业类别'
            ),
            array(
                'company',
                '体检单位'
            ),
            array(
                'physictype',
                '体检类别'
            ),
            array(
                'registertime',
                '体检时间'
            ),
            array(
                'order_serial_number',
                '体检编号'
            ),
            array(
                'busisess_name',
                '体检医院'
            ),
            array(
                'obtain_employ_number',
                '健康证号'
            ),
            array(
                'order_status',
                '体检状态'
            )
        );
        $xlsData = db('physical_users')->alias("pu")
            ->join("order o","o.order_serial_number = pu.order_serial_number")
            ->join("business b","o.bus_number=b.bs_uuid")
            ->where("pu.id", "in", $params)
            ->field("pu.id,pu.name,pu.identitycard,o.physical_result,pu.sex,pu.age,pu.phone,pu.employee,pu.company,pu.physictype,pu.registertime,pu.order_serial_number, b.busisess_name, o.obtain_employ_number,o.order_status,employ_num_time")
            ->select();
        foreach ($xlsData as $k => $v) {
            $xlsData[$k]['sex'] = $v['sex'] == 0 ? '男' : '女';
            $xlsData[$k]['employee'] = $this->comm->getEmpName($v['employee']);
            $xlsData[$k]['registertime'] = date("Y-m-d H:m:s", $v['registertime']);
            $status = '';
            if($v['employ_num_time']){                
                $status = "已出证";
            }
            if($v['physical_result'] == 0)
                $status = "未通过";
            if($v['physical_result'] == 1)
                $status = "已通过";
            $xlsData[$k]['order_status'] = $status;
        }
        $this->comm->exportExcel("userPhysial", $xlsCell, $xlsData);
    }
}