<?php
/**
 * og-image.php — Dynamic OG image (1200×630 PNG)
 *
 * Renders only the week-progress ring, centred on the canvas.
 * Rounded linecaps are simulated by drawing a filled circle at each
 * arc endpoint before punching out the donut hole.
 */
declare(strict_types=1);

ob_start(); // buffer so stray PHP warnings can't corrupt the PNG stream

ini_set('display_errors', '0');
error_reporting(0);

require_once __DIR__ . '/init.php';

// ─── Data ────────────────────────────────────────────────────────────────────

$db         = initDatabase();
$week       = getCurrentWeek($db);
$daysLogged = $week['days_logged'];

// ─── Canvas ──────────────────────────────────────────────────────────────────

$W  = 1200;
$H  = 630;
$im = imagecreatetruecolor($W, $H);
imagealphablending($im, true);

// ─── Palette ─────────────────────────────────────────────────────────────────

$cBg       = imagecolorallocate($im,  10,  10,  15);  // #0a0a0f
$cTrack    = imagecolorallocate($im,  28,  28,  42);  // ring track
$cText     = imagecolorallocate($im, 240, 240, 248);  // near-white
$cSecond   = imagecolorallocate($im, 148, 148, 168);  // secondary / muted
$cGreen    = imagecolorallocate($im,   0, 230, 118);  // #00e676
$cAmber    = imagecolorallocate($im, 255, 179,   0);  // #ffb300
$cProgress = $daysLogged >= 4 ? $cGreen : $cAmber;

// ─── Fonts ───────────────────────────────────────────────────────────────────

$fonts    = __DIR__ . '/assets/fonts/';
$fBlack   = $fonts . 'Inter-Black.ttf';
$fRegular = $fonts . 'Inter-Regular.ttf';

// ─── Helpers ─────────────────────────────────────────────────────────────────

/** Write UTF-8 text horizontally centred at $cx, baseline at $y. */
function ogText(
    GdImage $im, float $size, string $font,
    string $text, int $cx, int $y, int $color
): void {
    $bb = imagettfbbox($size, 0, $font, $text);
    $x  = $cx - (int)(($bb[2] - $bb[0]) / 2);
    imagettftext($im, $size, 0, $x, $y, $color, $font, $text);
}

/**
 * Draw a filled arc-pie clockwise from $startDeg for $sweepDeg degrees.
 * Handles the 360° wrap that imagefilledarc can't natively do in one call.
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

// ─── Ring geometry ───────────────────────────────────────────────────────────
// SVG ring: r=85, stroke-width=12 → outer=91, inner=79 (ratio ~8.7% of r)
// Scale to OG: midR=180 → outerR = round(180 * 91/85) ≈ 193, innerR ≈ 167
// Keeping nice round numbers with similar proportions:

$cx     = (int)($W / 2);   // 600 — exact horizontal centre
$cy     = (int)($H / 2);   // 315 — exact vertical centre
$outerR = 190;
$innerR = 162;
$midR   = (int)(($outerR + $innerR) / 2);  // 176 — ring centreline radius
$capR   = (int)(($outerR - $innerR) / 2);  // 14  — half stroke width

$outerD = $outerR * 2;   // 380
$innerD = $innerR * 2;   // 324

$sweepDeg = ($daysLogged / 7) * 360.0;

// 1. Track (full dark circle)
imagefilledellipse($im, $cx, $cy, $outerD, $outerD, $cTrack);

// 2. Progress arc (clockwise from top = 270°)
ogArc($im, $cx, $cy, $outerD, 270, $sweepDeg, $cProgress);

// 3. Rounded caps — filled circles at the arc endpoints, BEFORE punch-out
//    so the punch-out cleans up any sub-pixel overhang into the hole.
//    Not needed for 0 days (nothing to cap) or 7 days (full circle).
if ($daysLogged > 0 && $daysLogged < 7) {
    // Start cap — always at 270° (12 o'clock)
    $startRad = deg2rad(270);
    $scx = (int)round($cx + $midR * cos($startRad));
    $scy = (int)round($cy + $midR * sin($startRad));
    imagefilledellipse($im, $scx, $scy, $capR * 2, $capR * 2, $cProgress);

    // End cap — at the tip of the progress arc
    $endRad = deg2rad(270 + $sweepDeg);
    $ecx = (int)round($cx + $midR * cos($endRad));
    $ecy = (int)round($cy + $midR * sin($endRad));
    imagefilledellipse($im, $ecx, $ecy, $capR * 2, $capR * 2, $cProgress);
}

// 4. Punch out centre (donut hole)
imagefilledellipse($im, $cx, $cy, $innerD, $innerD, $cBg);

// ─── Text inside ring ────────────────────────────────────────────────────────

$suffix = $daysLogged === 1 ? 'vez' : 'vezes';
$line1  = $daysLogged . ' ' . $suffix;   // e.g. "1 vez" / "5 vezes"
$line2  = 'esta semana';

$bbL1   = imagettfbbox(40, 0, $fBlack,   $line1);
$l1CapH = abs($bbL1[5]);

$bbL2   = imagettfbbox(18, 0, $fRegular, $line2);
$l2CapH = abs($bbL2[5]);

$gap       = 14;
$blockH    = $l1CapH + $gap + $l2CapH;
$blockTopY = $cy - (int)($blockH / 2);

$line1Y = $blockTopY + $l1CapH;
$line2Y = $line1Y + $gap + $l2CapH;

ogText($im, 40, $fBlack,   $line1, $cx, $line1Y, $cText);
ogText($im, 18, $fRegular, $line2, $cx, $line2Y, $cSecond);

// ─── Output ──────────────────────────────────────────────────────────────────

ob_end_clean();
header('Content-Type: image/png');
header('Cache-Control: public, max-age=3600');
imagepng($im, null, 6);
imagedestroy($im);
