<?php

namespace App\Http\Controllers;

use App\Support\Settings;
use Illuminate\Http\Response;

/**
 * robots.txt を動的に出し分ける。
 *
 * 準備中モードがONの間、静的な robots.txt を置いたままにすると
 * 「更新し忘れて全公開してしまう」事故が起きる。設定と連動させ、
 * 準備中はクロール自体を拒否し、解除後は自動で通常のルールに戻す。
 */
class RobotsController extends Controller
{
    public function __invoke(Settings $settings): Response
    {
        if ($settings->inPreparation()) {
            $body = "User-agent: *\nDisallow: /\n";
        } else {
            $body = implode("\n", [
                'User-agent: *',
                'Disallow: /admin/',
                'Disallow: /login',
                'Disallow: /forgot-password',
                'Disallow: /reset-password/',
                '',
                'Sitemap: '.url('/sitemap.xml'),
                '',
            ]);
        }

        return response($body, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
