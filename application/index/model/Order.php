<?php
namespace app\index\model;

use think\Model;

class Order extends Model
{

    // 表名
    protected $name = 'order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = "createdate";

    protected $updateTime = false;

    protected $deleteTime = false;

    public function OrderDetail()
    {
        return $this->belongsTo('OrderDetail', 'order_serial_number', 'order_serial_number', [], 'LEFT')->setEagerlyType(0);
    }
}
