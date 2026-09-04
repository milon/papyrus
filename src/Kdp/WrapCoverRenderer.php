<?php

declare(strict_types=1);

namespace Milon\Papyrus\Kdp;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Render\VendorNotices;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;

/**
 * Build a single-page paperback wrap PDF (back | spine | front + bleed).
 *
 * Artwork is composed with GD at 300 DPI, then embedded in an mPDF page sized
 * to Amazon's wrap formula ({@see PrintCoverDimensions}).
 */
final class WrapCoverRenderer
{
    public const DPI = 300;

    public function __construct(
        private readonly Project $project,
    ) {}

    /**
     * @return list<string> paths written (PDF always; PNG preview when possible)
     */
    public function render(int $pageCount, string $theme = 'light', ?string $exportDir = null): array
    {
        if (! extension_loaded('gd')) {
            throw new KdpException('ext-gd is required to generate a wraparound cover.');
        }

        $frontName = $this->project->coverImageForTheme($theme);

        if ($frontName === null) {
            throw new KdpException(sprintf(
                'No front cover image configured for theme "%s" (set cover.image or cover.%s).',
                $theme,
                $theme,
            ));
        }

        $frontPath = $this->project->assetsDir.'/'.$frontName;

        if (! is_file($frontPath)) {
            throw new KdpException(sprintf('Front cover asset not found: %s', $frontPath));
        }

        $dims = PrintCoverDimensions::calculate(
            $this->project->documentSize(),
            $pageCount,
            $this->project->kdpConfig()->printPaper,
        );

        $exportDir = $exportDir ?? $this->project->exportDir;

        if (! is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $slug = $this->project->outputSlug();
        $pngPath = sprintf('%s/%s-kdp-print-wrap.png', $exportDir, $slug);
        $pdfPath = sprintf('%s/%s-kdp-print-wrap.pdf', $exportDir, $slug);

        $canvas = $this->compose(
            $dims,
            $frontPath,
            $this->resolveBackCoverPath(),
        );

        if (! imagepng($canvas, $pngPath, 6)) {
            imagedestroy($canvas);
            throw new KdpException(sprintf('Unable to write wrap preview PNG: %s', $pngPath));
        }

        imagedestroy($canvas);

        $this->writePdf($pdfPath, $pngPath, $dims['wrap_width_mm'], $dims['wrap_height_mm']);

        return [$pdfPath, $pngPath];
    }

    /**
     * @param  array{
     *     page_count: int,
     *     paper: string,
     *     bleed_in: float,
     *     spine_in: float,
     *     wrap_width_in: float,
     *     wrap_height_in: float,
     *     wrap_width_mm: float,
     *     wrap_height_mm: float,
     *     spine_text_allowed: bool
     * }  $dims
     */
    private function compose(array $dims, string $frontPath, ?string $backPath): \GdImage
    {
        $widthPx = max(1, (int) round($dims['wrap_width_in'] * self::DPI));
        $heightPx = max(1, (int) round($dims['wrap_height_in'] * self::DPI));
        $bleedPx = (int) round(PrintCoverDimensions::BLEED_IN * self::DPI);
        $trim = $this->project->documentSize();
        $trimW = (int) round(($trim->widthMm / 25.4) * self::DPI);
        $trimH = (int) round(($trim->heightMm / 25.4) * self::DPI);
        $spinePx = max(1, (int) round($dims['spine_in'] * self::DPI));

        $canvas = imagecreatetruecolor($widthPx, $heightPx);

        if ($canvas === false) {
            throw new KdpException('Unable to allocate wrap cover canvas.');
        }

        $front = $this->loadImage($frontPath);
        $spineRgb = $this->resolveSpineColor($front);
        $fill = imagecolorallocate($canvas, $spineRgb[0], $spineRgb[1], $spineRgb[2]);

        if ($fill === false) {
            imagedestroy($front);
            imagedestroy($canvas);
            throw new KdpException('Unable to allocate wrap cover fill colour.');
        }

        imagefilledrectangle($canvas, 0, 0, $widthPx, $heightPx, $fill);

        $backX = $bleedPx;
        $spineX = $bleedPx + $trimW;
        $frontX = $spineX + $spinePx;
        $panelY = $bleedPx;

        if ($backPath !== null) {
            $back = $this->loadImage($backPath);
            $this->coverFit($back, $canvas, $backX, $panelY, $trimW, $trimH);
            imagedestroy($back);
        } else {
            $this->drawTextPanel(
                $canvas,
                $backX,
                $panelY,
                $trimW,
                $trimH,
                $spineRgb,
                $this->project->title(),
                $this->project->author(),
                $this->project->kdpConfig()->metadataDescription,
            );
        }

        if ($dims['spine_text_allowed'] && $spinePx >= 18) {
            $this->drawSpineText($canvas, $spineX, $panelY, $spinePx, $trimH, $spineRgb);
        }

        $this->coverFit($front, $canvas, $frontX, $panelY, $trimW, $trimH);
        imagedestroy($front);

        // Bleed: extend panel edges into the outer bleed (simple edge clone).
        $this->extendBleed($canvas, $bleedPx, $backX, $frontX + $trimW, $panelY, $panelY + $trimH);

        return $canvas;
    }

    private function resolveBackCoverPath(): ?string
    {
        $name = $this->project->kdpConfig()->printBackCover;

        if ($name === null) {
            $cover = $this->project->config['cover'] ?? [];

            if (is_array($cover) && isset($cover['back']) && is_string($cover['back']) && $cover['back'] !== '') {
                $name = $cover['back'];
            }
        }

        if ($name === null) {
            return null;
        }

        $path = $this->project->assetsDir.'/'.$name;

        return is_file($path) ? $path : null;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function resolveSpineColor(\GdImage $front): array
    {
        $hex = $this->project->kdpConfig()->printSpineColor;

        if ($hex !== null) {
            $parsed = $this->parseHexColor($hex);

            if ($parsed !== null) {
                return $parsed;
            }
        }

        $w = imagesx($front);
        $h = imagesy($front);
        $x = max(0, $w - 2);
        $samples = [];

        for ($i = 0; $i < 5; $i++) {
            $y = (int) round(($h - 1) * ($i / 4));
            $rgb = imagecolorat($front, $x, $y);
            $samples[] = [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];
        }

        return [
            (int) round(array_sum(array_column($samples, 0)) / count($samples)),
            (int) round(array_sum(array_column($samples, 1)) / count($samples)),
            (int) round(array_sum(array_column($samples, 2)) / count($samples)),
        ];
    }

    /**
     * @return array{0: int, 1: int, 2: int}|null
     */
    private function parseHexColor(string $hex): ?array
    {
        $hex = ltrim(trim($hex), '#');

        if (preg_match('/^[0-9a-fA-F]{6}$/', $hex) !== 1) {
            return null;
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function loadImage(string $path): \GdImage
    {
        $info = @getimagesize($path);

        if ($info === false) {
            throw new KdpException(sprintf('Unable to read cover image: %s', $path));
        }

        $image = match ($info[2] ?? 0) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };

        if ($image === false) {
            throw new KdpException(sprintf('Unsupported or unreadable cover image: %s', $path));
        }

        return $image;
    }

    private function coverFit(\GdImage $src, \GdImage $dst, int $x, int $y, int $w, int $h): void
    {
        imagecopyresampled($dst, $src, $x, $y, 0, 0, $w, $h, imagesx($src), imagesy($src));
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $bg
     */
    private function drawTextPanel(
        \GdImage $canvas,
        int $x,
        int $y,
        int $w,
        int $h,
        array $bg,
        string $title,
        string $author,
        string $description,
    ): void {
        $luminance = (0.299 * $bg[0]) + (0.587 * $bg[1]) + (0.114 * $bg[2]);
        $fg = $luminance > 140 ? [30, 30, 30] : [245, 245, 245];
        $color = imagecolorallocate($canvas, $fg[0], $fg[1], $fg[2]);

        if ($color === false) {
            return;
        }

        $pad = (int) round(self::DPI * 0.4);
        $cx = $x + (int) round($w / 2);
        $lineY = $y + $pad;

        $this->drawCenteredString($canvas, $title, $cx, $lineY, $color, 5);
        $lineY += (int) round(self::DPI * 0.35);

        if ($author !== '') {
            $this->drawCenteredString($canvas, $author, $cx, $lineY, $color, 3);
            $lineY += (int) round(self::DPI * 0.3);
        }

        if ($description !== '') {
            $wrapped = wordwrap($description, 42, "\n", true);
            foreach (explode("\n", $wrapped) as $line) {
                if ($lineY > $y + $h - $pad) {
                    break;
                }
                $this->drawCenteredString($canvas, $line, $cx, $lineY, $color, 2);
                $lineY += (int) round(self::DPI * 0.18);
            }
        }
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $bg
     */
    private function drawSpineText(\GdImage $canvas, int $spineX, int $y, int $spineW, int $spineH, array $bg): void
    {
        $title = trim($this->project->title());
        $author = trim($this->project->author());
        $label = $author !== '' ? $title.' — '.$author : $title;

        if ($label === '') {
            return;
        }

        $luminance = (0.299 * $bg[0]) + (0.587 * $bg[1]) + (0.114 * $bg[2]);
        $fg = $luminance > 140 ? [20, 20, 20] : [250, 250, 250];

        $font = 3;
        $textW = imagefontwidth($font) * strlen($label);
        $textH = imagefontheight($font);
        $pad = 8;
        $strip = imagecreatetruecolor($textW + $pad * 2, max($spineW - 4, $textH + 4));

        if ($strip === false) {
            return;
        }

        imagesavealpha($strip, true);
        $transparent = imagecolorallocatealpha($strip, 0, 0, 0, 127);

        if ($transparent === false) {
            imagedestroy($strip);

            return;
        }

        imagefill($strip, 0, 0, $transparent);
        $color = imagecolorallocate($strip, $fg[0], $fg[1], $fg[2]);

        if ($color === false) {
            imagedestroy($strip);

            return;
        }

        imagestring($strip, $font, $pad, (int) round((imagesy($strip) - $textH) / 2), $label, $color);
        $rotated = imagerotate($strip, 90, $transparent);
        imagedestroy($strip);

        if ($rotated === false) {
            return;
        }

        $rw = imagesx($rotated);
        $rh = imagesy($rotated);
        $dx = $spineX + (int) round(($spineW - $rw) / 2);
        $dy = $y + (int) round(($spineH - $rh) / 2);
        imagecopy($canvas, $rotated, $dx, $dy, 0, 0, $rw, $rh);
        imagedestroy($rotated);
    }

    private function drawCenteredString(\GdImage $canvas, string $text, int $cx, int $y, int $color, int $font): void
    {
        $text = trim($text);

        if ($text === '') {
            return;
        }

        $w = imagefontwidth($font) * strlen($text);
        imagestring($canvas, $font, $cx - (int) round($w / 2), $y, $text, $color);
    }

    private function extendBleed(\GdImage $canvas, int $bleed, int $contentLeft, int $contentRight, int $contentTop, int $contentBottom): void
    {
        $w = imagesx($canvas);
        $h = imagesy($canvas);

        // Left bleed from left content edge
        for ($x = 0; $x < $bleed; $x++) {
            imagecopy($canvas, $canvas, $x, $contentTop, $contentLeft, $contentTop, 1, $contentBottom - $contentTop);
        }

        // Right bleed
        for ($x = $contentRight; $x < $w; $x++) {
            imagecopy($canvas, $canvas, $x, $contentTop, $contentRight - 1, $contentTop, 1, $contentBottom - $contentTop);
        }

        // Top / bottom
        for ($y = 0; $y < $bleed; $y++) {
            imagecopy($canvas, $canvas, 0, $y, 0, $contentTop, $w, 1);
        }

        for ($y = $contentBottom; $y < $h; $y++) {
            imagecopy($canvas, $canvas, 0, $y, 0, $contentBottom - 1, $w, 1);
        }
    }

    private function writePdf(string $pdfPath, string $pngPath, float $widthMm, float $heightMm): void
    {
        try {
            VendorNotices::silence(function () use ($pdfPath, $pngPath, $widthMm, $heightMm): void {
                $mpdf = new Mpdf([
                    'mode' => 'utf-8',
                    'format' => [$widthMm, $heightMm],
                    'margin_left' => 0,
                    'margin_right' => 0,
                    'margin_top' => 0,
                    'margin_bottom' => 0,
                ]);
                $mpdf->SetDisplayMode('fullpage');
                $absolute = realpath($pngPath);

                if ($absolute === false) {
                    throw new KdpException(sprintf('Wrap PNG missing after write: %s', $pngPath));
                }

                $mpdf->WriteHTML(sprintf(
                    '<div style="position:absolute;left:0;top:0;margin:0;padding:0;"><img src="%s" style="width:%smm;height:%smm;margin:0;padding:0;border:0;"/></div>',
                    htmlspecialchars($absolute, ENT_QUOTES | ENT_HTML5),
                    $widthMm,
                    $heightMm,
                ));
                $mpdf->Output($pdfPath, Destination::FILE);
            });
        } catch (MpdfException $e) {
            throw new KdpException('Unable to write wrap cover PDF: '.$e->getMessage(), previous: $e);
        }
    }
}
