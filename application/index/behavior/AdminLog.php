<?php

namespace app\index\behavior;

class AdminLog
{
    public function run(&$params)
    {
        if (request()->isPost()) {
            \app\index\model\AdminLog::record();
        }
    }
}
