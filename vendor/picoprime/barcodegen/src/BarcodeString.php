<?php
/**
 * @author Rafal Wojenkowski <rafal@picoprime.com>
 */

namespace PicoPrime\BarcodeGen;

interface BarcodeString
{
    public function generateString($text);
}
