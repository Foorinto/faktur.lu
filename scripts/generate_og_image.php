<?php

/**
 * Génère public/images/og-default.png (1200×630) avec le logo faktur.lu
 * Brand : violet #9b5de5, accent turquoise #00f5d4, jaune #fee440
 *
 * Usage : php scripts/generate_og_image.php
 */

// Dimensions OG standard
$width = 1200;
$height = 630;

// Charger le logo source
$logoPath = __DIR__ . '/../public/images/logo.png';
if (!file_exists($logoPath)) {
    echo "ERROR: logo.png introuvable à $logoPath\n";
    exit(1);
}

$logo = imagecreatefrompng($logoPath);
$logoW = imagesx($logo);
$logoH = imagesy($logo);

// Canvas principal
$img = imagecreatetruecolor($width, $height);
imagealphablending($img, true);
imagesavealpha($img, true);

// Couleurs
$bgColor      = imagecolorallocate($img, 255, 255, 255);  // blanc
$primaryColor = imagecolorallocate($img, 155, 93, 229);    // #9b5de5 primary-500
$accentColor  = imagecolorallocate($img, 0, 245, 212);     // #00f5d4 turquoise
$darkAccent   = imagecolorallocate($img, 0, 168, 150);     // #00a896
$slate900     = imagecolorallocate($img, 15, 23, 42);
$slate700     = imagecolorallocate($img, 51, 65, 85);
$slate500     = imagecolorallocate($img, 100, 116, 139);
$yellowAccent = imagecolorallocate($img, 254, 228, 64);    // #fee440

// Fond avec dégradé subtil violet pâle → blanc (haut → bas)
for ($y = 0; $y < $height; $y++) {
    $progress = $y / $height;
    $r = (int)(247 + (255 - 247) * $progress);
    $g = (int)(243 + (255 - 243) * $progress);
    $b = (int)(255 - (255 - 255) * $progress);
    $color = imagecolorallocate($img, $r, $g, $b);
    imageline($img, 0, $y, $width, $y, $color);
}

// Bandeau gauche violet vertical (40px)
imagefilledrectangle($img, 0, 0, 40, $height, $primaryColor);

// Forme accent turquoise en haut à droite (cercles)
$turquoiseTransparent = imagecolorallocatealpha($img, 0, 245, 212, 100);
imagefilledellipse($img, $width - 100, 100, 200, 200, $turquoiseTransparent);
$yellowTransparent = imagecolorallocatealpha($img, 254, 228, 64, 110);
imagefilledellipse($img, $width - 200, 250, 120, 120, $yellowTransparent);

// Logo en haut à gauche (resize)
$targetLogoH = 100;
$targetLogoW = (int)($logoW * ($targetLogoH / $logoH));
$logoX = 100;
$logoY = 80;
imagecopyresampled($img, $logo, $logoX, $logoY, 0, 0, $targetLogoW, $targetLogoH, $logoW, $logoH);

// Polices
$fontBold = '/System/Library/Fonts/Supplemental/Arial Bold.ttf';
$fontRegular = '/System/Library/Fonts/Supplemental/Arial.ttf';
if (!file_exists($fontBold)) $fontBold = '/Library/Fonts/Arial Bold.ttf';
if (!file_exists($fontRegular)) $fontRegular = '/Library/Fonts/Arial.ttf';
if (!file_exists($fontBold)) {
    echo "WARNING: Arial Bold introuvable, fallback sur GD bitmap\n";
    $fontBold = null;
}

if ($fontBold) {
    // Titre principal
    imagettftext($img, 56, 0, 100, 280, $slate900, $fontBold, 'Logiciel de Facturation');
    imagettftext($img, 56, 0, 100, 360, $primaryColor, $fontBold, 'au Luxembourg');

    // Subtitle
    imagettftext($img, 28, 0, 100, 430, $slate700, $fontRegular, 'Conforme FAIA · Peppol · 5 langues · Hébergement UE');

    // Badges en bas (avec un point de couleur au lieu de ✓ pour compat fonts)
    $badgeY = 530;
    $badges = [
        ['text' => 'FAIA 2.01', 'x' => 100],
        ['text' => 'Peppol B2G', 'x' => 280],
        ['text' => 'FR · DE · EN · LB · PT', 'x' => 490],
        ['text' => 'Essai 14 jours gratuits', 'x' => 800],
    ];
    foreach ($badges as $badge) {
        $bbox = imagettfbbox(20, 0, $fontBold, $badge['text']);
        $textW = $bbox[2] - $bbox[0];
        // Pill background arrondie (rectangle violet pâle)
        imagefilledrectangle($img, $badge['x'] - 25, $badgeY - 28, $badge['x'] + $textW + 15, $badgeY + 10, imagecolorallocate($img, 245, 240, 255));
        // Petit point coloré à gauche
        imagefilledellipse($img, $badge['x'] - 12, $badgeY - 8, 10, 10, $accentColor);
        imagettftext($img, 20, 0, $badge['x'], $badgeY, $primaryColor, $fontBold, $badge['text']);
    }

    // URL en bas à droite
    imagettftext($img, 22, 0, $width - 200, $height - 40, $slate500, $fontBold, 'faktur.lu');
} else {
    // Fallback sans TTF
    imagestring($img, 5, 100, 280, 'Logiciel de Facturation Luxembourg', $slate900);
    imagestring($img, 5, 100, 360, 'Conforme FAIA - Peppol - 5 langues', $primaryColor);
    imagestring($img, 4, 100, 530, '14 jours gratuits - faktur.lu', $primaryColor);
}

// Save
$outputPath = __DIR__ . '/../public/images/og-default.png';
imagepng($img, $outputPath, 9);
copy($outputPath, __DIR__ . '/../public/images/og-image.png');

imagedestroy($img);
imagedestroy($logo);

echo "OK: og-default.png et og-image.png générés (1200x630)\n";
echo "Tailles: " . filesize($outputPath) . " bytes\n";
