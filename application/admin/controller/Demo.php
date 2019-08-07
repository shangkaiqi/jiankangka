<?php
namespace app\admin\controller;

use app\common\controller\Backend;
use PHPExcel_IOFactory;
require './phpexcel/PHPExcel.php';
/**
 * 透视检查
 *
 * @icon fa fa-circle-o
 */
class Demo extends Backend
{

    protected $model = null;

    protected $order = null;

    protected $orderDetail = null;
    
    protected $noNeedLogin = ['*'];
    // 开关权限开启
    protected $noNeedRight = [
        '*'
    ];

    public function _initialize()
    {
        parent::_initialize();
        // $this->model = model("PhysicalUsers");

        $this->order = model("Order");
        $this->orderDetail = model("OrderDetail");
    }

    public function index()
    {
        $this->expUser();
    }
    protected function demo(){
        
        $res = db('physical_users')->field("id,name,sex,age,phone,identitycard")->select();
        //创建一个PHPExcel类
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()
        ->setCellValue("A1", 'ID')
        ->setCellValue("B1", '姓名')
        ->setCellValue("C1", '性别')
        ->setCellValue("D1", '年龄')
        ->setCellValue("E1", '手机')
        ->setCellValue("F1", '身份证');
        $currow=$currow+1;
        foreach ($res as $key=>$vo){
            $currow=$currow+1;
            $sheet->setCellValue('A'.$currow,$vo['id'])
            ->setCellValue('B'.$currow,$vo['name'])
            ->setCellValue('C'.$currow,$vo['sex'])
            ->setCellValue('D'.$currow,$user['age'])
            ->setCellValue('E'.$currow,$user['phone'])
            ->setCellValue('F'.$currow,$vo['identitycard']);
        }
        
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="store.xls"');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        $objPHPExcel->disconnectWorksheets();
        
    }
    protected function excelsave($phpexcel,$filename){
        //创建一个Excel文档并下载保存
        $phpwriter=new \PHPExcel_Writer_Excel2007($phpexcel);
        header('Content-Type: application/vnd.ms-excel');//设置文档类型
        header('Content-Disposition: attachment;filename="'.$filename.'".xls"');//设置文件名
        header('Cache-Control: max-age=0');
        $phpwriter->save('php://output');
    }
    
    public function exportExcel($expTitle,$expCellName,$expTableData){
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName ='usersdd'.date('_YmdHis',time());//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
//         vendor("PHPExcel");
        
        $objPHPExcel = new \PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle.'  Export time:'.date('Y-m-d H:i:s'));
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]);
        }
        // Miscellaneous glyphs, UTF-8
        for($i=0;$i<$dataNum;$i++){
            for($j=0;$j<$cellNum;$j++){
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3), $expTableData[$i][$expCellName[$j][0]]);
            }
        }
        
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=GB2312;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
   
}