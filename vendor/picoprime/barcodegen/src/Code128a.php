<?php
/**
 * @author Rafal Wojenkowski <rafal@picoprime.com>
 */

namespace PicoPrime\BarcodeGen;

class Code128a implements BarcodeString
{
    /**
     * Generate barcode in Code128 family format. This is called
     * differently to generate Code128/Code128b and Code128a.
     *
     * @param $text
     * @param $chksum
     * @param array $codeArray
     * @param string $prefix
     * @param string $suffix
     * @return string
     */
    protected function getCode128String($text, $chksum, array $codeArray, $prefix = '', $suffix = '')
    {
        $codeString = '';
        $codeKeys = array_keys($codeArray);
        $codeValues = array_flip($codeKeys);

        for ($X = 1; $X <= strlen($text); $X++) {
            $activeKey = substr($text, ($X - 1), 1);
            $codeString .= $codeArray[$activeKey];
            $chksum = ($chksum + ($codeValues[$activeKey] * $X));
        }

        $codeString .= $codeArray[$codeKeys[($chksum - (intval($chksum / 103) * 103))]];

        return $prefix . $codeString . $suffix;
    }

    /**
     * Generate barcode in Code128a standard.
     *
     * @param string $text
     * @return string
     */
    public function generateString($text)
    {
        $codeArray = [
            " " => "212222",
            "!" => "222122",
            "\"" => "222221",
            "#" => "121223",
            "$" => "121322",
            "%" => "131222",
            "&" => "122213",
            "'" => "122312",
            "(" => "132212",
            ")" => "221213",
            "*" => "221312",
            "+" => "231212",
            "," => "112232",
            "-" => "122132",
            "." => "122231",
            "/" => "113222",
            "0" => "123122",
            "1" => "123221",
            "2" => "223211",
            "3" => "221132",
            "4" => "221231",
            "5" => "213212",
            "6" => "223112",
            "7" => "312131",
            "8" => "311222",
            "9" => "321122",
            ":" => "321221",
            ";" => "312212",
            "<" => "322112",
            "=" => "322211",
            ">" => "212123",
            "?" => "212321",
            "@" => "232121",
            "A" => "111323",
            "B" => "131123",
            "C" => "131321",
            "D" => "112313",
            "E" => "132113",
            "F" => "132311",
            "G" => "211313",
            "H" => "231113",
            "I" => "231311",
            "J" => "112133",
            "K" => "112331",
            "L" => "132131",
            "M" => "113123",
            "N" => "113321",
            "O" => "133121",
            "P" => "313121",
            "Q" => "211331",
            "R" => "231131",
            "S" => "213113",
            "T" => "213311",
            "U" => "213131",
            "V" => "311123",
            "W" => "311321",
            "X" => "331121",
            "Y" => "312113",
            "Z" => "312311",
            "[" => "332111",
            "\\" => "314111",
            "]" => "221411",
            "^" => "431111",
            "_" => "111224",
            "NUL" => "111422",
            "SOH" => "121124",
            "STX" => "121421",
            "ETX" => "141122",
            "EOT" => "141221",
            "ENQ" => "112214",
            "ACK" => "112412",
            "BEL" => "122114",
            "BS" => "122411",
            "HT" => "142112",
            "LF" => "142211",
            "VT" => "241211",
            "FF" => "221114",
            "CR" => "413111",
            "SO" => "241112",
            "SI" => "134111",
            "DLE" => "111242",
            "DC1" => "121142",
            "DC2" => "121241",
            "DC3" => "114212",
            "DC4" => "124112",
            "NAK" => "124211",
            "SYN" => "411212",
            "ETB" => "421112",
            "CAN" => "421211",
            "EM" => "212141",
            "SUB" => "214121",
            "ESC" => "412121",
            "FS" => "111143",
            "GS" => "111341",
            "RS" => "131141",
            "US" => "114113",
            "FNC 3" => "114311",
            "FNC 2" => "411113",
            "SHIFT" => "411311",
            "CODE C" => "113141",
            "CODE B" => "114131",
            "FNC 4" => "311141",
            "FNC 1" => "411131",
            "Start A" => "211412",
            "Start B" => "211214",
            "Start C" => "211232",
            "Stop" => "2331112"
        ];

        return $this->getCode128String(strtoupper($text), 103, $codeArray, '211412', '2331112');
    }
}
