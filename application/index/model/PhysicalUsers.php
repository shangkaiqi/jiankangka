<?php
namespace app\index\model;

use think\Model;

class PhysicalUsers extends Model
{

    protected $pk = "id";
    // 数据库
    protected $connection = 'database';

    // 表名
    protected $name = 'physical_users';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = "registertime";

    protected $updateTime = false;

    protected $deleteTime = false;

    // 追加属性
    protected $append = ['id'];

    public function order()
    {
        return $this->belongsTo('app\index\model\Order', 'id', 'user_id', [], 'LEFT')->setEagerlyType(0);
    }
}
