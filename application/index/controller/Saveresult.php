<?php
namespace app\index\controller;

use app\common\controller\Backend;
use app\common\controller\Frontend;

class Saveresult extends Frontend
{

    protected $noNeedRight = [
        '*'
    ];

    public function _initialize()
    {
        parent::_initialize();
    }

    public function bodySave(){
        
        $params = $this->request->post("rows/a");
        
    }
}