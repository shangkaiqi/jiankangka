<?php
namespace app\index\validate;

use think\Validate;

class Register extends Validate
{

    /**
     * 验证规则
     */
    protected $rule = [
        'name' => 'require|max:50',
        'identitycard' => 'require',
        'sex' => 'sex',
        'age' => 'require|between:1,120',
        'phone' => 'require|phone',
        'order_serial_number' => 'require'
    ];

    protected function phone($value,$rule='',$data='',$field=''){
        if(strlen($value) == 11){
            return true;
        }else{
            return "手机号长度应该为11位";
        }
    }
    
    /**
     * 提示消息
     */
    protected $message = [
        'name.require' => '名字不能为空',
        'name.max' => '字符不能超过50个字符',
        'identitycard.require' => '身份证不能为空',
        'phone.require'=>'请输入手机号',
        'age.require' => '年龄不能为空',
        'age.between' => '请输入合法年龄',
        'phone.require' => '手机不能为空',
//         'employee.require' => '从业类型不能为空',
        'order_serial_number.require' => '订单编号不能为空'
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'add' => [
            'name',
            'identitycard',
            'age',
            'phone',
            'employee',
            'order_serial_number'
        ],
        'edit' => [
        ]
    ];

    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'name' => __('Name'),
            'identitycard' => __('Identitycard'),
            'age' => __('Age'),
            'phone' => __('Phone'),
            'employee' => __('Employee'),
            'order_serial_number' => __('Order_serial_number')
        ];
        parent::__construct($rules, $message, $field);
    }
}
