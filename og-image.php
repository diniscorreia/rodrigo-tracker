<?php
/**
 * og-image.php — Dynamic OG image (1200×630 PNG)
 *
 * Shows the week progress ring + current balance.
 * Requires GD with FreeType support (standard on PHP 8.2+).
 * Cached by crawlers for 1 hour via Cache-Control header.
 */
declare(strict_types=1);

// Buffer all output so any PHP warning can't corrupt the PNG stream
ob_start();

ini_set('display_errors', '0');
error_reporting(0);

require_once __DIR__ . '/init.php';

// ─── Data ────────────────────────────────────────────────────────────────────

$db      = initDatabase();
$balData = calculateBalance($db);
$week    = getCurrentWeek($db);

$balance    = $balData['balance'];
$streak     = $balData['streak'];
$daysLogged = $week['days_logged'];

// ─── Canvas ──────────────────────────────────────────────────────────────────

$W = 1200;
$H = 630;
$im = imagecreatetruecolor($W, $H);
imagealphablending($im, true);

// ─── Palette ─────────────────────────────────────────────────────────────────

$cBg      = imagecolorallocate($im,  10,  10,  15);  // #0a0a0f
$cTrack   = imagecolorallocate($im,  28,  28,  42);  // ring track
$cText    = imagecolorallocate($im, 240, 240, 248);  // near-white
$cMuted   = imagecolorallocate($im,  88,  88, 108);  // muted
$cSecond  = imagecolorallocate($im, 148, 148, 168);  // secondary
$cGreen   = imagecolorallocate($im,   0, 230, 118);  // #00e676
$cAmber   = imagecolorallocate($im, 255, 179,   0);  // #ffb300
$cRed     = imagecolorallocate($im, 255,  72,  72);

$cBalance  = $balance > 0 ? $cGreen : ($balance < 0 ? $cRed : $cText);
$cProgress = $daysLogged >= 4 ? $cGreen : $cAmber;

// ─── Fonts ───────────────────────────────────────────────────────────────────

$fonts    = __DIR__ . '/assets/fonts/';
$fBlack   = $fonts . 'Inter-Black.ttf';
$fBold    = $fonts . 'Inter-Bold.ttf';
$fRegular = $fonts . 'Inter-Regular.ttf';

// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * Write UTF-8 text horizontally centred at $cx, baseline at $y.
 * Returns the text width in pixels.
 */
function ogText(
    GdImage $im, float $size, string $font,
    string $text, int $cx, int $y, int $color
): int {
    $bb = imagettfbbox($size, 0, $font, $text);
    $tw = $bb[2] - $bb[0];
    $x  = $cx - (int)($tw / 2);
    imagettftext($im, $size, 0, $x, $y, $color, $font, $text);
    return $tw;
}

/**
 * Draw a clockwise filled arc from $startDeg for $sweepDeg degrees.
 * Handles the 360° wrap that imagefilledarc can't do natively.
 */
function ogArc(
    GdImage $im, int $cx, int $cy, int $d,
    int $startDeg, float $sweepDeg, int $color
): void {
    if ($sweepDeg <= 0) return;
    if ($sweepDeg >= 360) {
        imagefilledellipse($im, $cx, $cy, $d, $d, $color);
        return;
    }
    $end = $startDeg + (int)round($sweepDeg);
    if ($end <= 360) {
        imagefilledarc($im, $cx, $cy, $d, $d, $startDeg, $end, $color, IMG_ARC_PIE);
    } else {
        imagefilledarc($im, $cx, $cy, $d, $d, $startDeg, 360, $color, IMG_ARC_PIE);
        $rem = $end - 360;
        if ($rem > 0) {
            imagefilledarc($im, $cx, $cy, $d, $d, 0, $rem, $color, IMG_ARC_PIE);
        }
    }
}

// ─── Background ──────────────────────────────────────────────────────────────

imagefill($im, 0, 0, $cBg);

// ─── Subtle glow around the ring ─────────────────────────────────────────────
// Draw semi-transparent halos before the ring so they show only outside the
// ring stroke (the solid track circle will cover the inner ones).

[$gr, $gg, $gb] = $daysLogged >= 4 ? [0, 200, 100] : [220, 140, 0];

$cx     = (int)($W / 2);  // 600
$cy     = 258;
$outerR = 135;             // ring outer radius

for ($gr_r = $outerR + 60; $gr_r >= $outerR - 10; $gr_r -= 5) {
    $dist  = $gr_r - $outerR;                         // 60 → -10
    $alpha = (int)(127 - max(0, 36 - $dist * 0.9));   // subtler further out
    $alpha = max(100, min(127, $alpha));
    $gc    = imagecolorallocatealpha($im, $gr, $gg, $gb, $alpha);
    imagefilledellipse($im, $cx, $cy, $gr_r * 2, $gr_r * 2, $gc);
}

// ─── Ring ────────────────────────────────────────────────────────────────────

$outerD = $outerR * 2;   // 270
$innerD = 206;            // inner diameter → stroke ~32 px

imagefilledellipse($im, $cx, $cy, $outerD, $outerD, $cTrack);  // track
ogArc($im, $cx, $cy, $outerD, 270, ($daysLogged / 7) * 360.0, $cProgress);
imagefilledellipse($im, $cx, $cy, $innerD, $innerD, $cBg);     // punch centre

// ─── Text inside ring ────────────────────────────────────────────────────────

$countStr  = (string)$daysLogged;
$subStr    = 'vezes esta semana';

$bbCount   = imagettfbbox(64, 0, $fBlack,   $countStr);
$countCapH = abs($bbCount[5]);   // height above baseline

$bbSub     = imagettfbbox(13, 0, $fRegular, $subStr);
$subCapH   = abs($bbSub[5]);

$innerGap  = 9;
$blockH    = $countCapH + $innerGap + $subCapH;
$blockTopY = $cy - (int)($blockH / 2);

$countY    = $blockTopY + $countCapH;
$subY      = $countY + $innerGap + $subCapH;

ogText($im, 64, $fBlack,   $countStr, $cx, $countY, $cText);
ogText($im, 13, $fRegular, $subStr,   $cx, $subY,   $cMuted);

// ─── Balance ─────────────────────────────────────────────────────────────────

$absFormatted = number_format(abs($balance), 2, ',', '.');
$sign         = $balance < 0 ? '-' : '';
$balStr       = $sign . $absFormatted . "\u{00a0}€";

$bbBal   = imagettfbbox(76, 0, $fBlack, $balStr);
$balCapH = abs($bbBal[5]);

$balY = 478;
ogText($im, 76, $fBlack,   $balStr, $cx, $balY,       $cBalance);
ogText($im, 13, $fRegular, 'SALDO', $cx, $balY + 26,  $cMuted);

// ─── Streak ──────────────────────────────────────────────────────────────────

if ($streak > 0) {
    $pl  = $streak === 1;
    $str = $streak . ' semana' . ($pl ? '' : 's')
         . ' consecutiva' . ($pl ? '' : 's')
         . ' boa' . ($pl ? '' : 's');
    ogText($im, 15, $fRegular, $str, $cx, 570, $cMuted);
}

// ─── Title ───────────────────────────────────────────────────────────────────

ogText($im, 21, $fBold, 'O Rodrigo Foi Treinar?', $cx, 60, $cSecond);

// Thin rule below title
imageline($im, $cx - 160, 78, $cx + 160, 78, $cTrack);

// ─── Render ──────────────────────────────────────────────────────────────────

// Discard any stray PHP output that could corrupt the PNG
ob_end_clean();

header('Content-Type: image/png');
header('Cache-Control: public, max-age=3600');
imagepng($im, null, 6);
imagedestroy($im);
