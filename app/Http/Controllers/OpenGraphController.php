<?php

namespace App\Http\Controllers;

use App\Models\Postcard;
use App\Support\Capsule;
use Illuminate\Http\Response;

class OpenGraphController extends Controller
{
    private string $serifFont;
    private string $sansFont;

    public function __construct()
    {
        $this->serifFont = resource_path('fonts/Georgia.ttf');
        $this->sansFont = resource_path('fonts/Arial.ttf');

        // Fallbacks if fonts are missing in resources
        if (! file_exists($this->serifFont)) {
            $this->serifFont = '/System/Library/Fonts/Supplemental/Georgia.ttf';
        }
        if (! file_exists($this->sansFont)) {
            $this->sansFont = '/System/Library/Fonts/Supplemental/Arial.ttf';
        }
    }

    public function postcard(Postcard $postcard): Response
    {
        $im = imagecreatetruecolor(1200, 630);

        // Cute & Warm Colors
        $outerBg = imagecolorallocate($im, 250, 248, 245);        // #faf8f5 (warm cream)
        $cardBg = imagecolorallocate($im, 255, 255, 255);         // pure crisp white
        $cardBorder = imagecolorallocate($im, 235, 227, 215);     // soft border
        $innerBorder = imagecolorallocate($im, 245, 238, 228);    // inner line
        $textDark = imagecolorallocate($im, 35, 31, 29);          // warm deep stone
        $textMuted = imagecolorallocate($im, 140, 130, 122);      // soft muted
        $coral = imagecolorallocate($im, 255, 90, 82);            // #ff5a52
        $coralSoft = imagecolorallocate($im, 255, 236, 235);      // #ffeceb
        $honey = imagecolorallocate($im, 245, 158, 11);           // #f59e0b
        $white = imagecolorallocate($im, 255, 255, 255);

        // Fill outer background
        imagefill($im, 0, 0, $outerBg);

        // Postcard Card Container
        $cx1 = 45; $cy1 = 35; $cx2 = 1155; $cy2 = 595;
        imagefilledrectangle($im, $cx1, $cy1, $cx2, $cy2, $cardBg);
        imagerectangle($im, $cx1, $cy1, $cx2, $cy2, $cardBorder);
        imagerectangle($im, $cx1 + 10, $cy1 + 10, $cx2 - 10, $cy2 - 10, $innerBorder);

        // Top Brand Pill
        $brandText = "THE INTERNET TIME CAPSULE · POSTCARD TO 2050";
        imagettftext($im, 13, 0, $cx1 + 45, $cy1 + 54, $coral, $this->sansFont, $brandText);

        // Postcard Serial Number
        $numText = "No. " . Capsule::formatNumber($postcard->number);
        imagettftext($im, 24, 0, $cx1 + 45, $cy1 + 96, $textDark, $this->serifFont, $numText);

        // Postage Stamp in Top Right
        $sx1 = $cx2 - 165; $sy1 = $cy1 + 25; $sx2 = $cx2 - 35; $sy2 = $cy1 + 130;
        imagefilledrectangle($im, $sx1, $sy1, $sx2, $sy2, $coralSoft);
        imagerectangle($im, $sx1, $sy1, $sx2, $sy2, $coral);
        imagerectangle($im, $sx1 + 4, $sy1 + 4, $sx2 - 4, $sy2 - 4, $coral);
        imagettftext($im, 15, 0, $sx1 + 24, $sy1 + 42, $coral, $this->sansFont, "2050");
        imagettftext($im, 9, 0, $sx1 + 12, $sy1 + 66, $coral, $this->sansFont, "POSTAGE PAID");
        imagettftext($im, 18, 0, $sx1 + 38, $sy1 + 98, $coral, $this->serifFont, "$5");

        // Teaser Quote
        $teaser = trim($postcard->teaser ?: 'Wish you were here.');
        $wrapped = $this->wrapText($teaser, 30, $this->serifFont, 960);

        $lineY = $cy1 + 215;
        foreach ($wrapped as $idx => $line) {
            $prefix = $idx === 0 ? "“" : "";
            $suffix = $idx === count($wrapped) - 1 ? "”" : "";
            imagettftext($im, 30, 0, $cx1 + 45, $lineY, $textDark, $this->serifFont, $prefix . $line . $suffix);
            $lineY += 52;
        }

        // Horizontal fold line
        $foldY = $cy2 - 150;
        imageline($im, $cx1 + 40, $foldY, $cx2 - 40, $foldY, $innerBorder);

        // Destination Date
        $addressedDate = Capsule::formatDay($postcard->addressed_to->toDateString());
        imagettftext($im, 11, 0, $cx1 + 45, $foldY + 36, $textMuted, $this->sansFont, "DESTINATION MORNING");
        imagettftext($im, 20, 0, $cx1 + 45, $foldY + 68, $coral, $this->serifFont, $addressedDate);

        // From Author details
        $fromText = "From " . $postcard->name . " · " . $postcard->location;
        imagettftext($im, 13, 0, $cx1 + 45, $foldY + 104, $textMuted, $this->sansFont, $fromText);

        // Cute Seal Stamp on Bottom Right
        $waxCx = $cx2 - 120;
        $waxCy = $cy2 - 75;
        $waxRadius = 55;

        imagefilledellipse($im, $waxCx, $waxCy, $waxRadius * 2, $waxRadius * 2, $coral);
        imageellipse($im, $waxCx, $waxCy, ($waxRadius * 2) - 8, ($waxRadius * 2) - 8, $coral);
        imageellipse($im, $waxCx, $waxCy, ($waxRadius * 2) - 12, ($waxRadius * 2) - 12, $white);

        imagettftext($im, 11, 0, $waxCx - 32, $waxCy - 10, $white, $this->sansFont, "SEALED");
        imagettftext($im, 16, 0, $waxCx - 22, $waxCy + 14, $white, $this->serifFont, "2050");
        imagettftext($im, 8, 0, $waxCx - 34, $waxCy + 30, $white, $this->sansFont, "OPENS 1 JAN");

        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);

        return response($data, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400, s-maxage=86400',
            'ETag' => '"og-' . $postcard->id . '"',
        ]);
    }

    public function cover(): Response
    {
        $im = imagecreatetruecolor(1200, 630);

        // Warm, light cheerful cover
        $bg = imagecolorallocate($im, 250, 248, 245);
        $cardBg = imagecolorallocate($im, 255, 255, 255);
        $border = imagecolorallocate($im, 235, 227, 215);
        $coral = imagecolorallocate($im, 255, 90, 82);
        $honey = imagecolorallocate($im, 245, 158, 11);
        $dark = imagecolorallocate($im, 35, 31, 29);
        $muted = imagecolorallocate($im, 115, 105, 98);

        imagefill($im, 0, 0, $bg);

        // Inner Card Container
        imagefilledrectangle($im, 40, 40, 1160, 590, $cardBg);
        imagerectangle($im, 40, 40, 1160, 590, $border);

        // Badge pill
        imagettftext($im, 14, 0, 90, 150, $coral, $this->sansFont, "✨ THE WORLD’S SWEETEST TIME CAPSULE · OPENS 1 JANUARY 2050");

        // Display Title
        imagettftext($im, 46, 0, 90, 240, $dark, $this->serifFont, "Send a postcard");
        imagettftext($im, 46, 0, 90, 310, $coral, $this->serifFont, "to the future 💌");

        // Subtitle
        $lede1 = "Address a $5 digital postcard to any calendar morning before 2050.";
        $lede2 = "One cheeky line on the wall today — your sealed letter unlocks on Jan 1, 2050.";
        imagettftext($im, 19, 0, 90, 395, $muted, $this->sansFont, $lede1);
        imagettftext($im, 19, 0, 90, 435, $muted, $this->sansFont, $lede2);

        // Social Proof Footer
        $count = Postcard::query()->count();
        $counterText = "🔒 " . number_format($count) . " Postcards Sealed · 🎖️ Founding 10,000 Slots · ☕ Only $5";
        imagettftext($im, 16, 0, 90, 530, $honey, $this->sansFont, $counterText);

        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);

        return response($data, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function wrapText(string $text, int $fontSize, string $fontFile, int $maxWidth): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
            $bbox = imagettfbbox($fontSize, 0, $fontFile, $testLine);
            $width = abs($bbox[4] - $bbox[0]);

            if ($width > $maxWidth && $currentLine !== '') {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines;
    }
}
