<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Sex;
use App\Services\SingleScoreService;
use GdImage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

class OgImageController
{
    private const int WIDTH = 1200;

    private const int HEIGHT = 630;

    private const string FONT_REGULAR = __DIR__.'/../../../resources/fonts/DejaVuSans.ttf';

    private const string FONT_BOLD = __DIR__.'/../../../resources/fonts/DejaVuSans-Bold.ttf';

    public function __invoke(Request $request, SingleScoreService $service): Response
    {
        $sex = Sex::tryFrom($request->query('sex', 'female')) ?? Sex::Female;
        $ageMin = max(18, min(80, (int) $request->query('ageMin', 25)));
        $ageMax = max(18, min(80, (int) $request->query('ageMax', 34)));

        $topResult = $service->getTopRegions($sex, $ageMin, $ageMax, 1)->first();

        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        if (! $image instanceof GdImage) {
            throw new RuntimeException('Failed to create image canvas.');
        }

        $background = $this->color($image, 15, 23, 42);    // slate-900
        $accent = $this->color($image, 244, 63, 94);        // rose-500
        $white = $this->color($image, 255, 255, 255);
        $muted = $this->color($image, 100, 116, 139);       // slate-500
        $subtle = $this->color($image, 30, 41, 59);         // slate-800

        imagefill($image, 0, 0, $background);

        // Decorative rose bar at top
        imagefilledrectangle($image, 0, 0, self::WIDTH, 8, $accent);

        // Card background
        $this->drawRoundedRect($image, 60, 60, self::WIDTH - 60, self::HEIGHT - 60, 20, $subtle);

        // Site name
        imagettftext($image, 18, 0, 100, 140, $accent, self::FONT_BOLD, 'Singelkartan.se');

        // Filter label
        $filterLabel = $sex->label().', '.$ageMin.'–'.$ageMax.' år';
        imagettftext($image, 26, 0, 100, 210, $muted, self::FONT_REGULAR, $filterLabel);

        if ($topResult !== null) {
            $regionName = $topResult->region->name ?? '–';
            $percentage = $topResult->singlePercentage().'%';

            imagettftext($image, 20, 0, 100, 300, $muted, self::FONT_REGULAR, 'Singeltätaste kommunen');
            imagettftext($image, 72, 0, 100, 400, $white, self::FONT_BOLD, $regionName);
            imagettftext($image, 52, 0, 100, 490, $accent, self::FONT_BOLD, $percentage.' singlar');
        } else {
            imagettftext($image, 36, 0, 100, 360, $muted, self::FONT_REGULAR, 'Inga data för detta urval');
        }

        imagettftext($image, 16, 0, 100, self::HEIGHT - 90, $muted, self::FONT_REGULAR, 'Data: SCB · HushallT08CKM · CC0 1.0');

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * @param  int<0, 255>  $r
     * @param  int<0, 255>  $g
     * @param  int<0, 255>  $b
     */
    private function color(GdImage $image, int $r, int $g, int $b): int
    {
        $color = imagecolorallocate($image, $r, $g, $b);

        if ($color === false) {
            throw new RuntimeException("Failed to allocate color rgb({$r},{$g},{$b}).");
        }

        return $color;
    }

    private function drawRoundedRect(GdImage $image, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagefilledellipse($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }
}
