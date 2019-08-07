<?php
/**
 * This file is part of Pico Prime Barcode Generator.
 *
 * @author Rafal Wojenkowski <rafal@picoprime.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PicoPrime\BarcodeGen;

use Illuminate\Support\ServiceProvider;

class BarcodeGenServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(BarcodeGenerator::class, function ($app) {
            return new BarcodeGenerator();
        });
    }
}
