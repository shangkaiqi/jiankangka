<?php
namespace app\index\controller;

use app\common\controller\Frontend;
use Exception;
require_once './phpexcel/PHPExcel.php';

// Including all required classes
require_once ('./barcodegen/class/BCGFontFile.php');
require_once ('./barcodegen/class/BCGColor.php');
require_once ('./barcodegen/class/BCGDrawing.php');

// Including the barcode technology
require_once ('./barcodegen/class/BCGcode39.barcode.php');

/**
 * 透视检查
 *
 * @icon fa fa-circle-o
 */
class Demo extends Frontend
{

    protected $noNeedLogin = [
        '*'
    ];

    // 开关权限开启
    protected $noNeedRight = [
        '*'
    ];

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {

        // Loading Font
        $font = new \BCGFontFile('./barcodegen/font/Arial.ttf', 18);

        // Don't forget to sanitize user inputs
        $text = isset($_GET['text']) ? $_GET['text'] : 'eeeeeeeeeeeeeeeeeeeeeeee';

        // The arguments are R, G, B for color.
        $color_black = new \BCGColor(0, 0, 0);
        $color_white = new \BCGColor(255, 255, 255);

        $drawException = null;
        try {
            $code = new \BCGcode39();
            $code->setScale(2); // Resolution
            $code->setThickness(30); // Thickness
            $code->setForegroundColor($color_black); // Color of bars
            $code->setBackgroundColor($color_white); // Color of spaces
            $code->setFont($font); // Font (or 0)
            $code->parse($text); // Text
        } catch (Exception $exception) {
            $drawException = $exception;
        }

        /*
         * Here is the list of the arguments
         * 1 - Filename (empty : display on screen)
         * 2 - Background color
         */
        $drawing = new \BCGDrawing('', $color_white);
        if ($drawException) {
            $drawing->drawException($drawException);
        } else {
            $drawing->setBarcode($code);
            $drawing->draw();
        }

        // Header that says it is an image (remove it if you save the barcode to a file)
        header('Content-Type: image/png');
        header('Content-Disposition: inline; filename="barcode.png"');

        // Draw (or save) the image into PNG format.
        echo $drawing->finish(\BCGDrawing::IMG_FORMAT_PNG);
    }
}