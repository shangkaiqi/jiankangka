<?php
namespace app\index\model;

use think\Model;

class OrderDetail extends Model
{

    // 表名
    protected $name = 'order_detail';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = "create_date";

    protected $updateTime = false;

    protected $deleteTime = false;

     //关联inspect表
    public function inspectDetail()
    {
        return $this->belongsTo('Inspect', 'item', 'id');
    }
    public function extDetail()
    {
        return $this->belongsTo('Inspect', 'physical_result_ext', 'id');
    }

    public static function getInspectDetail($type = '', $orderId = ''){
        $where = array();
        
       
        $where['order_serial_number'] = [
            'eq',
            $orderId
        ];
        $where['physical'] = [
            'eq',
            0
        ];
       
        
        

           
           $inspectDetail = self::with('inspectDetail')->where($where)->select();
           return $inspectDetail;
          
    }
    public static function getExtDetail($type = '', $orderId = ''){
        $where = array();
        
       
        $where['order_serial_number'] = [
            'eq',
            $orderId
        ];
        
       
        
        

           
           $extDetail = self::with('extDetail')->where($where)->select();
           return $extDetail;
          
    }
}
