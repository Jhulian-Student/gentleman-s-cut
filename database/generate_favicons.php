<?php
// database/generate_favicons.php
// Generates high-quality PNG icons and favicon.ico for The Gentleman's Cut

function drawBarberEmblem($size) {
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, false);
    imagesavealpha($img, true);

    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefilledrectangle($img, 0, 0, $size, $size, $transparent);
    imagealphablending($img, true);
    imageantialias($img, true);

    $cx = $size / 2;
    $cy = $size / 2;
    $r = ($size / 2) - ($size * 0.04);

    // Color palette
    $dark = imagecolorallocate($img, 18, 18, 18);
    $darkInner = imagecolorallocate($img, 28, 28, 28);
    $gold = imagecolorallocate($img, 201, 162, 39);
    $goldBright = imagecolorallocate($img, 245, 215, 127);
    $goldDark = imagecolorallocate($img, 150, 114, 16);

    // Outer gold border
    imagefilledellipse($img, $cx, $cy, $r * 2 + ($size * 0.08), $r * 2 + ($size * 0.08), $gold);
    // Dark disc
    imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $dark);
    // Inner fine gold ring
    imagesetthickness($img, max(1, (int)($size * 0.02)));
    imageellipse($img, $cx, $cy, $r * 2 - ($size * 0.12), $r * 2 - ($size * 0.12), $goldBright);

    // Draw Crossed Barber Shears (Scissors)
    imagesetthickness($img, max(2, (int)($size * 0.045)));

    // Scissor blade 1 (bottom-left to top-right)
    $bladeLen = $size * 0.32;
    imageline($img, $cx - $bladeLen * 0.8, $cy + $bladeLen * 0.8, $cx + $bladeLen, $cy - $bladeLen, $goldBright);
    // Scissor blade 2 (bottom-right to top-left)
    imageline($img, $cx + $bladeLen * 0.8, $cy + $bladeLen * 0.8, $cx - $bladeLen, $cy - $bladeLen, $goldBright);

    // Finger rings (loops at bottom)
    $ringR = max(3, (int)($size * 0.10));
    imagesetthickness($img, max(2, (int)($size * 0.035)));
    imageellipse($img, $cx - ($size * 0.22), $cy + ($size * 0.25), $ringR * 2, $ringR * 2, $goldBright);
    imageellipse($img, $cx + ($size * 0.22), $cy + ($size * 0.25), $ringR * 2, $ringR * 2, $goldBright);

    // Tang on right ring
    imageline($img, $cx + ($size * 0.30), $cy + ($size * 0.20), $cx + ($size * 0.35), $cy + ($size * 0.14), $goldBright);

    // Comb in center
    $combW = $size * 0.44;
    $combH = max(2, (int)($size * 0.04));
    imagefilledrectangle($img, $cx - ($combW / 2), $cy - ($size * 0.08), $cx + ($combW / 2), $cy - ($size * 0.08) + $combH, $gold);
    
    // Comb teeth
    $teethCount = 9;
    $toothStep = $combW / ($teethCount + 1);
    imagesetthickness($img, max(1, (int)($size * 0.02)));
    for ($i = 1; $i <= $teethCount; $i++) {
        $tx = (int)(($cx - ($combW / 2)) + ($i * $toothStep));
        $ty1 = (int)($cy - ($size * 0.08) + $combH);
        $ty2 = (int)($ty1 + ($size * 0.07));
        imageline($img, $tx, $ty1, $tx, $ty2, $gold);
    }

    // Pivot screw
    $screwR = max(2, (int)($size * 0.05));
    imagefilledellipse($img, (int)$cx, (int)$cy, $screwR * 2, $screwR * 2, $dark);
    imagesetthickness($img, max(1, (int)($size * 0.025)));
    imageellipse($img, (int)$cx, (int)$cy, $screwR * 2, $screwR * 2, $goldBright);

    // Gentleman mustache below center
    if ($size >= 32) {
        $mustacheWidth = $size * 0.42;
        $my = (int)($cy + ($size * 0.12));
        // Left curl
        imagearc($img, (int)($cx - ($mustacheWidth * 0.26)), $my, (int)($mustacheWidth * 0.48), (int)($size * 0.14), 0, 180, $goldBright);
        // Right curl
        imagearc($img, (int)($cx + ($mustacheWidth * 0.26)), $my, (int)($mustacheWidth * 0.48), (int)($size * 0.14), 0, 180, $goldBright);
    }

    return $img;
}

// Generate sizes
$sizes = [
    16 => __DIR__ . '/../images/favicon-16x16.png',
    32 => __DIR__ . '/../images/favicon-32x32.png',
    180 => __DIR__ . '/../images/apple-touch-icon.png',
    192 => __DIR__ . '/../images/logo-192.png',
    512 => __DIR__ . '/../images/logo-512.png',
];

$pngData = [];

foreach ($sizes as $sz => $path) {
    $gd = drawBarberEmblem($sz);
    imagepng($gd, $path);
    if ($sz === 16 || $sz === 32) {
        ob_start();
        imagepng($gd);
        $pngData[$sz] = ob_get_clean();
    }
    echo "Saved: $path\n";
}

// Build standard .ico file containing 16x16 and 32x32
$icoPath = __DIR__ . '/../favicon.ico';
$icoImages = [16, 32];
$header = pack('vvv', 0, 1, count($icoImages));
$offset = 6 + (count($icoImages) * 16);
$entries = '';
$dataBlock = '';

foreach ($icoImages as $sz) {
    $data = $pngData[$sz];
    $sizeBytes = strlen($data);
    $entry = pack('CCCCvvVV', 
        $sz == 256 ? 0 : $sz, 
        $sz == 256 ? 0 : $sz, 
        0, 
        0, 
        1, 
        32, 
        $sizeBytes, 
        $offset
    );
    $entries .= $entry;
    $dataBlock .= $data;
    $offset += $sizeBytes;
}

file_put_contents($icoPath, $header . $entries . $dataBlock);
echo "Saved: $icoPath\n";

// Also copy favicon.ico into images/ for relative link safety
copy($icoPath, __DIR__ . '/../images/favicon.ico');
echo "Saved: images/favicon.ico\n";
