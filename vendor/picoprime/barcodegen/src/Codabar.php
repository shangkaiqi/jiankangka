<?php
/**
 * @author Rafal Wojenkowski <rafal@picoprime.com>
 */

namespace PicoPrime\BarcodeGen;


class Codabar implements BarcodeString
{
    /**
     * Generate barcode in Codabar standard.
     *
     * @return string
     */
    public function generateString($text)
    {
        $codeString = '';
        $text = strtoupper($text);

        $codeArray1 = [
            "1",
            "2",
            "3",
            "4",
            "5",
            "6",
            "7",
            "8",
            "9",
            "0",
            "-",
            "$",
            ":",
            "/",
            ".",
            "+",
            "A",
            "B",
            "C",
            "D"
        ];

        $codeArray2 = [
            "1111221",
            "1112112",
            "2211111",
            "1121121",
            "2111121",
            "1211112",
            "1211211",
            "1221111",
            "2112111",
            "1111122",
            "1112211",
            "1122111",
            "2111212",
            "2121112",
            "2121211",
            "1121212",
            "1122121",
            "1212112",
            "1112122",
            "1112221"
        ];

        for ($X = 1; $X <= strlen($text); $X++) {
            for ($Y = 0; $Y < count($codeArray1); $Y++) {
                if (substr($text, ($X - 1), 1) == $codeArray1[$Y]) {
                    $codeString .= $codeArray2[$Y] . "1";
                }
            }
        }

        return "11221211" . $codeString . "1122121";
    }
}
