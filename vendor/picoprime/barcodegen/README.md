# Barcode Generator

[![Author](https://secure.gravatar.com/avatar/074618e37f640d13d402830f61092d09?d=identicon&s=50)](https://twitter.com/raffwpp)

Barcode Generator is a simple library that helps you create barcodes images.
It's designed for Laravel 5 and it can create PNG images or DATA-URL strings.
Based on great [php-generator](https://github.com/davidscotttufts/php-barcode) 
by [David Tufts](https://github.com/davidscotttufts)

# Installation

Through Composer, obviously:

```
composer require picoprime/barcodegen
```

or you can edit composer.json file and add `"picoprime/barcodegen": "~1.0"` to 
your "require" section.

# Setup

Successfully tested in Laravel 5. Steps to make it work:

* edit "require" section in composer.json as described above
* **Ignore this step on Laravel 5.5 or newer - package will be auto discovered** 
edit config/app.php file and add `PicoPrime\BarcodeGen\BarcodeGenServiceProvider::class,` 
to providers
* create controller or add new methods to existing controllers (example is in "docs" 
folder). You can use `PicoPrime\BarcodeGen\BarcodeGenerator` class directly or 
`PicoPrime\BarcodeGen\BarcodeGen` facade. `BarcodeGenerator` can be injected as well.
Before you call `generate()` method you have to pass variables to `init()` like so:

```
$this->barcode
    ->init($text, $size, $orientation, $codeType, $scale)
    ->generate()
```

or

```
$this->barcode
    ->generate($text, $size, $orientation, $codeType, $scale)
```

where:

* "text" is the text that you want to transform into barcode,
* "size" is barcode's height in pixels. If you need to change width as well then use 
"scale" together with "size"!
* "orientation" does what it says - changes barcode's orientation. Available: 
horizontal and vertical,
* "codeType" is the type of code that you want to generate. Available: code128, 
code128a, code39, code25, codabar.
* "scale" - if you need wider or just bigger barcode enter a number: 1 - default, 
2 - 2x bigger, 2.5, ...

You can also pass these parameters as assoc or numeric array, like so:

```
$this->barcode
    ->generate(compact('text', 'size', 'orientation', 'codeType', 'scale'))
```

or using facade:

```
\PicoPrime\BarcodeGen\BarcodeGen::generate(['textToTransform', 50, 'horizontal', 'code128', 1])
```

Last step to generate image is to send whatever has been generated above 
to `->response('png')` or `->encode('data-url')`.
Response will create Laravel's response and will display an image, whilst 
"encode" will create a string.

Please take a look at example controller in `docs` folder.


### Routes

You can create routes as you like. Two examples that we usually use:

```
Route::get('barcode/img/{text}/{size?}/{scale?}/{codeType?}/{orientation?}', 'BarcodeController@barcodeAsPng');
Route::get('barcode/url/{text}/{size?}/{scale?}/{codeType?}/{orientation?}', 'BarcodeController@barcodeAsDataUrl');
```

Please take a look at example route in `docs` folder.


### Frontend use

Finally to use it in frontend, just use your route in `img` "src" attribute:

```
<img src="/barcode/img/someText" alt="barcode">
```

This example should generate horizontal, 50px, code128 barcode for "someText".


## Issues

Please feel free to create an issue on GitHub if you come across any errors or if you
have an idea for improvement.


# Enjoy

Oh and if you've come down this far, you might as well follow me on [twitter](https://twitter.com/raffwpp)
or check out my [company's website](https://picoprime.com).
