<?php

namespace App\Providers;

use App\Services\ImageService;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;

/**
 * 画像処理の driver を環境に合わせて選ぶ。
 *
 * エックスサーバーの共用プランでは Imagick が使えないことがあるため、
 * 入っていれば Imagick、無ければ GD にフォールバックする。
 * どちらでも WebP は出力できる。
 */
class ImageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ImageManager::class, function () {
            $driver = extension_loaded('imagick') ? new ImagickDriver : new GdDriver;

            return new ImageManager($driver);
        });

        $this->app->singleton(ImageService::class);
    }
}
