<?php
namespace app\index\validate;

use think\Validate;

class Enregister extends Validate
{

    /**
     * 验证规则
     */
    protected $rule = [
        'username' => 'require|max:50|unique:admin',
        'nickname' => 'require',
        'password' => 'require',
        'email' => 'require|email|unique:admin,email'
    ];
}