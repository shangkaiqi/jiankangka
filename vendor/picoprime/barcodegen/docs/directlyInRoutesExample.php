<?php

Route::get(
    'barcode/img/{text}/{size?}/{scale?}/{codeType?}/{orientation?}',
    
    function ($text, $size = 50, $scale = 1, $codeType = 'code128', $orientation = 'horizontal') {
        
        $barcode = new \PicoPrime\BarcodeGen\BarcodeGenerator();

        return $barcode
            ->generate(compact('text', 'size', 'orientation', 'codeType', 'scale'))
            ->response('png');
    }
);