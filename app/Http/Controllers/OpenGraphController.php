<?php

namespace App\Http\Controllers;

use App\Models\Creator;
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

        // Crisp botanical warm card
        $bg = imagecolorallocate($im, 250, 248, 245);
        $cardBg = imagecolorallocate($im, 255, 255, 255);
        $border = imagecolorallocate($im, 231, 229, 223);
        $emerald = imagecolorallocate($im, 4, 120, 87);
        $deepGreen = imagecolorallocate($im, 6, 78, 59);
        $dark = imagecolorallocate($im, 15, 23, 42);
        $muted = imagecolorallocate($im, 100, 116, 139);

        imagefill($im, 0, 0, $bg);

        // Inner Card Container
        imagefilledrectangle($im, 40, 40, 1160, 590, $cardBg);
        imagerectangle($im, 40, 40, 1160, 590, $border);

        // Badge pill
        imagettftext($im, 14, 0, 90, 150, $emerald, $this->sansFont, "🎙️ COMMUNITY TIME CAPSULES FOR CREATORS");

        // Display Title
        imagettftext($im, 50, 0, 90, 240, $dark, $this->serifFont, "FanVault");
        imagettftext($im, 36, 0, 90, 310, $emerald, $this->serifFont, "Milestone Vaults & Digital Fan Mail");

        // Subtitle
        $lede1 = "Fans seal letters & predictions today — unsealed live on stream tomorrow.";
        $lede2 = "Zero physical mail clutter. Custom creator pricing & direct Stripe payouts.";
        imagettftext($im, 19, 0, 90, 395, $muted, $this->sansFont, $lede1);
        imagettftext($im, 19, 0, 90, 435, $muted, $this->sansFont, $lede2);

        // Social Proof Footer
        $count = Postcard::query()->count();
        $counterText = "🔒 " . number_format($count) . " Community Letters Sealed · getfanvault.com";
        imagettftext($im, 16, 0, 90, 530, $deepGreen, $this->sansFont, $counterText);

        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);

        return response($data, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function creator(string $slug): Response
    {
        $creator = Creator::query()->where('slug', Capsule::slugify($slug))->first();
        if (! $creator) {
            return $this->cover();
        }

        $im = imagecreatetruecolor(1200, 630);

        $bg = imagecolorallocate($im, 250, 248, 245);
        $cardBg = imagecolorallocate($im, 255, 255, 255);
        $border = imagecolorallocate($im, 167, 243, 208);
        $emerald = imagecolorallocate($im, 4, 120, 87);
        $deepGreen = imagecolorallocate($im, 6, 78, 59);
        $dark = imagecolorallocate($im, 15, 23, 42);
        $muted = imagecolorallocate($im, 100, 116, 139);

        imagefill($im, 0, 0, $bg);

        // Card Container
        imagefilledrectangle($im, 40, 40, 1160, 590, $cardBg);
        imagerectangle($im, 40, 40, 1160, 590, $border);

        // Platform Pill
        $platform = strtoupper($creator->platform ?: 'CREATOR');
        imagettftext($im, 14, 0, 90, 140, $emerald, $this->sansFont, "🎙️ {$platform} COMMUNITY VAULT · FANVAULT");

        // Creator Title
        $title = $creator->name . "’s Vault";
        imagettftext($im, 48, 0, 90, 230, $dark, $this->serifFont, $title);

        // Milestone event subtitle
        $active = $creator->activeMilestone();
        $milestoneTitle = $active?->title ?? $creator->milestone_title ?? 'Community Milestone';
        imagettftext($im, 28, 0, 90, 300, $emerald, $this->serifFont, "Celebrating: " . $milestoneTitle);

        // Description / Prompt
        $prompt = $creator->bio ?: "Leave a private letter, milestone prediction, or memories to be unsealed live on stream!";
        $lines = $this->wrapText($prompt, 18, $this->sansFont, 1000);
        $y = 360;
        foreach (array_slice($lines, 0, 2) as $line) {
            imagettftext($im, 18, 0, 90, $y, $muted, $this->sansFont, $line);
            $y += 35;
        }

        // Unlock Date Footer Pill
        $unlockDate = $active?->formattedUnlockDate() ?? $creator->formattedUnlockDate();
        $footerText = "🔓 Unlocks Live on Stream: " . $unlockDate . " · getfanvault.com/with/" . $creator->slug;
        imagettftext($im, 16, 0, 90, 530, $deepGreen, $this->sansFont, $footerText);

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
