<?php
namespace app\admin\validate;

use think\Validate;

class Admin extends Validate
{

    /**
     * 验证规则
     */

    //
    //
    //
    // age
    //
    //
    //
    protected $rule = [
        'username' => 'require|max:50|unique',
//         'businessid' => 'require',
        'email' => 'require|email',
        'nickname' => 'require'
        // 'age' => 'require|number|between:1,120',
        // 'phone' => 'require|mobile',
        // 'employee' => 'require|email|unique:admin,email',
        // 'company' => 'require'
    ];

    /**
     * 提示消息
     */
    protected $message = [];

    /**
     * 字段描述
     */
    protected $field = [];

    /**
     * 验证场景
     */
    protected $scene = [
        'add' => [
            'name',
//             'businessid',
            'email',
            'nickname'
        ],
        'edit' => [
            'username'
        ]
    ];

    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'name' => __('name'),
            'identitycard' => __('identitycard'),
            'type' => __('type'),
            'sex' => __('sex'),
            'age' => __('age'),
            'phone' => __('phone'),
            'company' => __('company')
        ];
        parent::__construct($rules, $message, $field);
    }
}
