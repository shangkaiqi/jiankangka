<?php
        $params = $this->request->post("row/a");
        $identitycard = $params['search'];//身份证号
        $where['identitycard'] = $identitycard;
        $where['is_print'] = 1;
        $userOrder = db('order')->where($where)
                                   ->order('createdate desc')
                                   ->find();
        //根据编号查询个人信息
        $userSerialNum =$userOrder['order_serial_number'];
        $userInfo = db('physical_users')->where('order_serial_number','=',$userSerialNum)->find();


        