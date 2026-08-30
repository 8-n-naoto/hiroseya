import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

/*
 * 入口を 2 つに分けている。
 *
 *   app.css / app.js   … 管理画面。Tailwind + Alpine。
 *   site.css / site.js … 公開サイト。手書き CSS と素の JS。
 *
 * 公開ページに Tailwind と Alpine を読み込ませないことで、
 * 訪問者が受け取る JS/CSS を数 KB に抑えている。表示速度は検索順位に効く。
 *
 * Web フォントの取得プラグイン（bunny）は外した。和文は OS 標準の
 * 明朝・ゴシックで組むため不要で、ビルド時にネットワークを要求しなくなる。
 */
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/site.css',
                'resources/js/site.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
