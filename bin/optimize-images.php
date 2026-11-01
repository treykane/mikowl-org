<?php
/**
 * OPTIMIZE GALLERY IMAGES
 *
 * Saves a lighter WebP copy of every image in img/mikowl/ to img/web/.
 * index.php serves the WebP copy when there is one and the original when
 * there isn't, so a newly added image still shows up before this has run.
 *
 *   php bin/optimize-images.php           convert new or changed images
 *   php bin/optimize-images.php --force   redo every image
 *
 * Run it after adding or replacing images, then commit img/web/ with them.
 * Originals in img/mikowl/ are never modified.
 *
 * Best results come from Google's WebP tools (macOS: brew install webp):
 * cwebp keeps each image's color profile, and gif2webp is the only way to
 * convert animated GIFs. Without them, PHP's GD extension handles still
 * images, but it drops color profiles, so images saved in something other
 * than sRGB (Adobe RGB, for example) can come out looking a little off.
 */

if (PHP_SAPI !== 'cli') {// NEVER RUN FROM A BROWSER
  http_response_code(404);
  exit;
}

const MAX_WIDTH = 1200;// IMAGES SHOW AT MOST ~600 CSS PIXELS WIDE; 1200 COVERS RETINA SCREENS
const QUALITY = 80;// WEBP QUALITY, 0-100

$root = dirname(__DIR__);
$source = $root.'/img/mikowl/';
$target = $root.'/img/web/';
$force = in_array('--force', $argv, true);

$cwebp = find_tool('cwebp');
$gif2webp = find_tool('gif2webp');

if (!$cwebp && !function_exists('imagewebp')) {
  fwrite(STDERR, "Needs cwebp (brew install webp) or PHP's GD extension with WebP support.\n");
  exit(1);
}

echo $cwebp ? "Using cwebp.\n\n" : "cwebp not found; using PHP's GD (color profiles will be dropped).\n\n";

if (!is_dir($target)) {
  mkdir($target, 0755, true);
}

$before = 0;
$after = 0;

foreach (glob($source.'*') as $file) {
  if (!preg_match('/\.(jpe?g|png|gif)$/i', $file)) {// WEBP AND AVIF ORIGINALS ARE ALREADY LIGHT
    continue;
  }

  $name = basename($file);
  $copy = $target.$name.'.webp';

  if (!$force && is_file($copy) && filemtime($copy) >= filemtime($file)) {// ALREADY UP TO DATE
    $before += filesize($file);
    $after += filesize($copy);
    continue;
  }

  $is_gif = (bool) preg_match('/\.gif$/i', $file);

  if ($is_gif && $gif2webp) {
    $ok = run($gif2webp, array('-quiet', '-lossy', '-q', QUALITY, '-m', 6, '-mt', $file, '-o', $copy));
  } elseif ($is_gif && is_animated_gif($file)) {
    echo "  skipped  $name (animated GIF; install gif2webp to convert it)\n";
    $before += filesize($file);
    $after += filesize($file);
    continue;
  } elseif ($cwebp && !$is_gif) {
    $info = @getimagesize($file);
    $resize = $info && $info[0] > MAX_WIDTH ? array('-resize', MAX_WIDTH, 0) : array();
    $ok = run($cwebp, array_merge(array('-quiet', '-q', QUALITY, '-m', 6, '-sharp_yuv', '-metadata', 'icc'), $resize, array($file, '-o', $copy)));
  } else {
    $ok = convert_with_gd($file, $copy);
  }

  if (!$ok || !is_file($copy)) {
    echo "  failed   $name (could not be decoded; the original will be served)\n";
    @unlink($copy);
    $before += filesize($file);
    $after += filesize($file);
    continue;
  }

  clearstatcache(true, $copy);

  if (filesize($copy) >= filesize($file)) {// THE ORIGINAL IS ALREADY SMALLER, SO KEEP SERVING IT
    unlink($copy);
    echo "  kept     $name (original is already smaller)\n";
    $before += filesize($file);
    $after += filesize($file);
    continue;
  }

  printf("  %-8s %s  %s -> %s\n", 'webp', $name, kb(filesize($file)), kb(filesize($copy)));
  $before += filesize($file);
  $after += filesize($copy);
}

foreach (glob($target.'*.webp') as $copy) {// REMOVE COPIES WHOSE ORIGINAL WAS DELETED
  if (!is_file($source.basename($copy, '.webp'))) {
    unlink($copy);
    echo "  removed  ".basename($copy)." (original is gone)\n";
  }
}

printf("\nGallery weight: %s -> %s\n", kb($before), kb($after));

function find_tool($name)
{
  return trim((string) shell_exec('command -v '.escapeshellarg($name).' 2>/dev/null'));
}

function run($tool, $args)
{
  exec(escapeshellarg($tool).' '.implode(' ', array_map('escapeshellarg', $args)).' 2>&1', $output, $status);

  return $status === 0;
}

function convert_with_gd($file, $copy)
{
  if (!function_exists('imagewebp')) {
    return false;
  }

  $info = @getimagesize($file);
  if (!$info) {
    return false;
  }

  switch ($info[2]) {
    case IMAGETYPE_JPEG: $image = @imagecreatefromjpeg($file); break;
    case IMAGETYPE_PNG:  $image = @imagecreatefrompng($file); break;
    case IMAGETYPE_GIF:  $image = @imagecreatefromgif($file); break;
    default: return false;
  }

  if (!$image) {
    return false;
  }

  imagepalettetotruecolor($image);// WEBP NEEDS FULL COLOR; PALETTE PNGS AND GIFS ARE NOT
  imagealphablending($image, false);
  imagesavealpha($image, true);

  $width = imagesx($image);
  $height = imagesy($image);

  if ($width > MAX_WIDTH) {
    $newHeight = (int) round($height * MAX_WIDTH / $width);
    $resized = imagecreatetruecolor(MAX_WIDTH, $newHeight);
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    imagecopyresampled($resized, $image, 0, 0, 0, 0, MAX_WIDTH, $newHeight, $width, $height);
    $image = $resized;
  }

  return imagewebp($image, $copy, QUALITY);
}

function is_animated_gif($file)
{
  return substr_count(file_get_contents($file), "\x21\xF9\x04") > 1;// MORE THAN ONE FRAME
}

function kb($bytes)
{
  return $bytes >= 1048576
    ? number_format($bytes / 1048576, 1).' MB'
    : number_format($bytes / 1024).' KB';
}
