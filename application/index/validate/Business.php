<?php
namespace app\index\validate;

use think\Validate;

class Business extends Validate
{

    /**
     * 验证规则
     */
    protected $rule = [
        // 'physical_num' => 'require',
        'phone' => 'require',
        'busisess_name' => 'require|max:50|unique',
        'connect' => 'require',
        'address' => 'require'
    ];

    /**
     * 提示消息
     */
    protected $message = [
        // 'physical_num' => 'require',
        'phone.require' => '请填写正确的手机号',
        'busisess_name.require' => '医院名称不能为空',
        'busisess_name.unique' => '医院名称不能重复',
        'connect.require' => '联系人不能为空',
        'address.require' => '地址不能为空'
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'add' => [
            'phone',
            'busisess_name',
            'connect',
            'address'
        ],
        'edit' => [
            'phone',
            'busisess_name',
            'connect',
            'address'
        ]
    ];
    
//     public function __construct(array $rules = [], $message = [], $field = [])
//     {
//         $this->field = [
//             'phone' => __('联系人手机'),
//             'busisess_name' => __('Busisess_name'),
//             'connect' => __('Connect'),
//             'address' => __('Area'),
//         ];
//         parent::__construct($rules, $message, $field);
//     }
}
