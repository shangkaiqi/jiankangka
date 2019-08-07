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
}
