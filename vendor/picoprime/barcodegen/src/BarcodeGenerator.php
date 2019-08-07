<?php
/**
 * @author Rafal Wojenkowski <rafal@picoprime.com>
 */

namespace PicoPrime\BarcodeGen;

use Exception;
use Intervention\Image\ImageManager;

class BarcodeGenerator
{
    const BLACK_COLOR = '#000';
    const WHITE_COLOR = '#fff';

    protected $image;
    protected $text;
    protected $size;
    protected $orientation;
    protected $codeType;
    protected $scale;

    /**
     * Init basic params.
     *
     * @param mixed $text
     * @param int $size
     * @param string $orientation
     * @param string $codeType
     * @param float $scale
     * @return $this
     */
    public function init(
        $text = '',
        $size = 50,
        $orientation = 'horizontal',
        $codeType = 'code128',
        $scale = 1
    ) {
        $scale = floatval($scale);
        $this->image = new ImageManager();

        if (is_array($text)) {
            $this->initFromArray($text, (float) $size, $orientation, $codeType, $scale);
        } else {
            $this->text = $text;
            $this->size = (int) $size;
            $this->orientation = strtolower($orientation);
            $this->codeType = strtolower($codeType);
            $this->scale = $scale;
        }

        return $this;
    }

    /**
     * Try to grab all required params from passed array.
     * @param array $data
     * @param int $size
     * @param string $orientation
     * @param string $codeType
     * @param float $scale
     */
    protected function initFromArray(array $data, $size, $orientation, $codeType, $scale)
    {
        $this->text = $this->extract(0, 'text', '', $data);
        $this->size = $this->extract(1, 'size', $size, $data);
        $this->orientation = strtolower($this->extract(2, 'orientation', $orientation, $data));
        $this->codeType = strtolower($this->extract(3, 'codeType', $codeType, $data));
        $this->scale = $this->extract(4, 'scale', $scale, $data);
    }

    /**
     * Extract value from given array. Check numeric keys
     * first and then try assoc key.
     *
     * @param int $intKey
     * @param string $textKey
     * @param mixed $default
     * @param array $data
     * @return mixed
     */
    protected function extract($intKey, $textKey, $default, array $data)
    {
        if (isset($data[$intKey])) {
            return $data[$intKey];
        } elseif (isset($data[$textKey])) {
            return $data[$textKey];
        }

        return $default;
    }

    /**
     * Switch between standards and request barcode image.
     *
     * @return \Intervention\Image\Image
     * @throws \Exception
     */
    public function generate()
    {
        if (func_num_args()) {
            $args = func_get_args();
            $args = is_array($args[0]) ? $args[0] : $args;
            $this->init($args);
        }

        $codeString = null;

        switch ($this->codeType) {
            case 'code128':
            case 'code128b':
                $barcode = new Code128();
                break;
            case 'code128a':
                $barcode = new Code128a();
                break;
            case 'code39':
                $barcode = new Code39();
                break;
            case 'code25':
                $barcode = new Code25();
                break;
            case 'codabar':
                $barcode = new Codabar();
                break;
            default:
                throw new Exception('Type of code could not be recognized or is not implemented!');
        }

        $codeString = $barcode->generateString($this->text);

        return $this->generateImage($codeString);
    }

    /**
     * Generate actual barcode image.
     *
     * @param $codeString
     * @return \Intervention\Image\Image
     */
    protected function generateImage($codeString)
    {
        $location = 10 * $this->scale;
        $codeLength = $this->setCodeLength($codeString);
        list($imgWidth, $imgHeight) = $this->setImageDimensions($codeLength);

        $barcode = $this->image->canvas($imgWidth, $imgHeight, '#fff');

        for ($position = 1; $position <= strlen($codeString); $position++) {

            $curSize = $location + (int)(substr($codeString, ($position - 1), 1)) * $this->scale;
            $color = ($position % 2 == 0 ? self::WHITE_COLOR : self::BLACK_COLOR);
            list($startX, $startY, $endX, $endY) = $this->setBlockSize($location, $curSize, $imgWidth, $imgHeight);

            $barcode->rectangle($startX, $startY, $endX, $endY, function ($draw) use ($color) {
                $draw->background($color);
            });

            $location = $curSize;
        }

        return $barcode;
    }

    /**
     * Set black/white stripe's width and height
     * by checking orientation.
     *
     * @param $location
     * @param $curSize
     * @param $imgWidth
     * @param $imgHeight
     * @return array
     */
    protected function setBlockSize($location, $curSize, $imgWidth, $imgHeight)
    {
        if ($this->orientation === 'horizontal') {
            return [$location, 0, $curSize, $imgHeight];
        }

        return [0, $location, $imgWidth, $imgHeight];
    }

    /**
     * Set barcode dimensions by checking orientation.
     *
     * @param $codeLength
     * @return array
     */
    protected function setImageDimensions($codeLength)
    {
        if ($this->orientation === 'horizontal') {
            return [$codeLength, $this->size * $this->scale];
        }

        return [$this->size * $this->scale, $codeLength];
    }

    /**
     * Pad the edges of the barcode.
     *
     * @param $codeString
     * @return int
     */
    protected function setCodeLength($codeString)
    {
        $codeLength = 20;

        for ($i = 1; $i <= strlen($codeString); $i++) {
            $codeLength = $codeLength + (integer)(substr($codeString, ($i - 1), 1));
        }

        return $codeLength * $this->scale;
    }
}
