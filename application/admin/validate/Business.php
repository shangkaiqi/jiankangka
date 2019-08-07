<?php
namespace app\admin\validate;

use think\Validate;

class Business extends Validate
{

    /**
     * 验证规则
     */
    protected $rule = [
        'busisessname' => 'require|max:50|unique:business',
        'physical_num' => 'require',
        'phone' => 'require',
        'connect' => 'require',
        'address' => 'require',
        'email'=>'require|email'
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'physical_num' => 'require',
        'phone.require' => '请填写正确的手机号',
        'busisessname.require' => '医院名称不能为空',
        'busisessname.unique' => '医院名称不能重复',
        'connect.require' => '联系人不能为空',
        'address.require' => '地址不能为空',
        'email.require' => '邮箱不能为空'
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'add' => [
            'phone',
            'busisessname',
            'connect',
            'address',
            'email'
        ],
        'edit' => [
        ]
    ];

    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'phone' => __('联系人手机'),
            'busisessname' => __('busisessname'),
            'connect' => __('Connect'),
            'address' => __('address')
        ];
        parent::__construct($rules, $message, $field);
    }
}
