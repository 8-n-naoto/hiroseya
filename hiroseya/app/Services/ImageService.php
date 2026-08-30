<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * 画像のアップロードと最適化。
 *
 * サイトに出す画像は必ず WebP を経由させる。方針は 3 点:
 *
 *  1. アップロードされた原本はそのまま置かず、長辺を上限まで縮めた「原本相当」を保存する。
 *     店舗の方がスマートフォンで撮った 5MB の写真をそのまま公開領域に置かせない。
 *  2. 表示用に lg / md / sm の 3 サイズを WebP で生成する。
 *     <picture> で出し分け、対応していない環境には JPEG を返す。
 *  3. 生成物のパスは規則で決まるので DB には持たない。
 *     media.path から Media::variantUrl() が組み立てる。
 *
 * 生成されるファイル（例: dishes/misonikomi-a1b2c3.jpg がアップロードされた場合）:
 *   dishes/misonikomi-a1b2c3.jpg        原本相当（フォールバック用）
 *   dishes/misonikomi-a1b2c3_lg.webp    長辺 1600
 *   dishes/misonikomi-a1b2c3_md.webp    長辺 800
 *   dishes/misonikomi-a1b2c3_sm.webp    長辺 400
 *   dishes/misonikomi-a1b2c3_md.jpg     WebP 非対応向けのフォールバック
 */
class ImageService
{
    public function __construct(private readonly ImageManager $manager) {}

    /**
     * アップロードされた画像を保存し、Media レコードを返す。
     *
     * @param  string  $directory  保存先ディレクトリ（dishes / news / home など）
     */
    public function store(UploadedFile $file, string $directory = 'uploads', ?string $alt = null): Media
    {
        $disk = config('filesystems.default') === 'public' ? 'public' : 'public';
        $sizes = config('hiroseya.images.sizes');
        $webpQuality = config('hiroseya.images.webp_quality', 80);
        $jpegQuality = config('hiroseya.images.jpeg_quality', 82);

        $basename = $this->basename($file);
        $extension = 'jpg';
        $path = trim($directory, '/')."/{$basename}.{$extension}";

        $image = $this->manager->read($file->getRealPath());

        // 撮影時の向きを反映してから縮小する。横倒しのまま保存されるのを防ぐ。
        $image = $image->orient();

        $original = $this->fit(clone $image, max($sizes));
        Storage::disk($disk)->put($path, (string) $original->toJpeg($jpegQuality));

        foreach ($sizes as $key => $edge) {
            $resized = $this->fit(clone $image, $edge);

            Storage::disk($disk)->put(
                $this->variantPath($path, $key, 'webp'),
                (string) $resized->toWebp($webpQuality),
            );
        }

        // WebP に対応していない環境向けのフォールバックは中間サイズだけ用意する。
        Storage::disk($disk)->put(
            $this->variantPath($path, 'md', 'jpg'),
            (string) $this->fit(clone $image, $sizes['md'])->toJpeg($jpegQuality),
        );

        return Media::create([
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => 'image/jpeg',
            'size' => Storage::disk($disk)->size($path),
            'width' => $original->width(),
            'height' => $original->height(),
            'alt' => $alt,
            'uploaded_by' => auth()->id(),
        ]);
    }

    /** 画像とその派生ファイルをすべて削除する。 */
    public function delete(Media $media): void
    {
        $disk = Storage::disk($media->disk);

        $disk->delete($media->path);

        foreach (array_keys(config('hiroseya.images.sizes')) as $key) {
            $disk->delete($this->variantPath($media->path, $key, 'webp'));
            $disk->delete($this->variantPath($media->path, $key, 'jpg'));
        }

        $media->delete();
    }

    /**
     * 既存の Media から派生ファイルを作り直す。
     * サイズ設定を変えたときや、移行した画像に派生が無いときに使う。
     */
    public function regenerate(Media $media): bool
    {
        $disk = Storage::disk($media->disk);

        if (! $disk->exists($media->path)) {
            return false;
        }

        $image = $this->manager->read($disk->get($media->path))->orient();
        $sizes = config('hiroseya.images.sizes');
        $webpQuality = config('hiroseya.images.webp_quality', 80);
        $jpegQuality = config('hiroseya.images.jpeg_quality', 82);

        foreach ($sizes as $key => $edge) {
            $disk->put(
                $this->variantPath($media->path, $key, 'webp'),
                (string) $this->fit(clone $image, $edge)->toWebp($webpQuality),
            );
        }

        $disk->put(
            $this->variantPath($media->path, 'md', 'jpg'),
            (string) $this->fit(clone $image, $sizes['md'])->toJpeg($jpegQuality),
        );

        return true;
    }

    /** 長辺を $edge に収める。元より大きくは引き伸ばさない。 */
    private function fit(ImageInterface $image, int $edge): ImageInterface
    {
        if ($image->width() <= $edge && $image->height() <= $edge) {
            return $image;
        }

        return $image->scaleDown($edge, $edge);
    }

    private function variantPath(string $path, string $size, string $extension): string
    {
        return preg_replace('/\.[^.]+$/', '', $path)."_{$size}.{$extension}";
    }

    /**
     * ファイル名。日本語のファイル名はサーバーとURLで事故るため、
     * 元の名前から英数字だけを拾い、短いランダム文字列を足して衝突を避ける。
     */
    private function basename(UploadedFile $file): string
    {
        $stem = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $stem = $stem !== '' ? Str::limit($stem, 40, '') : 'image';

        return $stem.'-'.Str::lower(Str::random(6));
    }
}
