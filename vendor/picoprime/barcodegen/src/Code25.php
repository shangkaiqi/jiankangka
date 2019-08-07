<?php
/**
 * @author Rafal Wojenkowski <rafal@picoprime.com>
 */

namespace PicoPrime\BarcodeGen;

class Code25 implements BarcodeString
{
    /**
     * Generate barcode in Code25 standard.
     *
     * @return string
     */
    public function generateString($text)
    {
        $codeString = '';
        $codeArray1 = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        $codeArray2 = [
            "3-1-1-1-3",
            "1-3-1-1-3",
            "3-3-1-1-1",
            "1-1-3-1-3",
            "3-1-3-1-1",
            "1-3-3-1-1",
            "1-1-1-3-3",
            "3-1-1-3-1",
            "1-3-1-3-1",
            "1-1-3-3-1"
        ];

        for ($X = 1; $X <= strlen($text); $X++) {
            for ($Y = 0; $Y < count($codeArray1); $Y++) {
                if (substr($text, ($X - 1), 1) == $codeArray1[$Y]) {
                    $temp[$X] = $codeArray2[$Y];
                }
            }
        }

        for ($X = 1; $X <= strlen($text); $X += 2) {
            if (isset($temp[$X]) && isset($temp[($X + 1)])) {
                $temp1 = explode("-", $temp[$X]);
                $temp2 = explode("-", $temp[($X + 1)]);
                for ($Y = 0; $Y < count($temp1); $Y++) {
                    $codeString .= $temp1[$Y] . $temp2[$Y];
                }
            }
        }

        return "1111" . $codeString . "311";
    }
}
