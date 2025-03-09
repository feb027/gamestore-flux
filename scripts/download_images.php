<?php
// Create images directory if it doesn't exist
$imagesDir = __DIR__ . '/../images/games';
if (!file_exists($imagesDir)) {
    mkdir($imagesDir, 0777, true);
}

// List of game images to download
$games = [
    'horizon-zero-dawn.jpg' => 'https://cdn.cloudflare.steamstatic.com/steam/apps/1151620/header.jpg',
];

// Download each image
foreach ($games as $filename => $url) {
    $filepath = $imagesDir . '/' . $filename;
    if (!file_exists($filepath)) {
        echo "Downloading {$filename}...\n";
        $image = file_get_contents($url);
        if ($image !== false) {
            file_put_contents($filepath, $image);
            echo "Successfully downloaded {$filename}\n";
        } else {
            echo "Failed to download {$filename}\n";
        }
    } else {
        echo "{$filename} already exists\n";
    }
}

echo "\nDone downloading images!\n";

// Create a placeholder image for games without Steam headers
$placeholder = imagecreatetruecolor(460, 215);
$bgColor = imagecolorallocate($placeholder, 30, 30, 30);
$textColor = imagecolorallocate($placeholder, 255, 255, 255);
imagefill($placeholder, 0, 0, $bgColor);
imagestring($placeholder, 5, 160, 100, "Game Image", $textColor);

$placeholderPath = $imagesDir . '/placeholder.jpg';
imagejpeg($placeholder, $placeholderPath);
imagedestroy($placeholder);

echo "Created placeholder image\n"; 