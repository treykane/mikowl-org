<?php

  require __DIR__.'/inc/asset.php';// CACHE BUSTING: asset('css/main.css') -> css/main.css?v=<fingerprint of its contents>

  $ga4_id = '';// GOOGLE ANALYTICS 4 MEASUREMENT ID, LIKE 'G-XXXXXXXXXX'. LEAVE EMPTY FOR NO ANALYTICS.

  // HOW THE PAGE SHOWS UP IN SEARCH RESULTS AND LINK PREVIEWS (SEE README)
  $site_url = 'https://mikowl.org/';// THE ONE ADDRESS SEARCH ENGINES SHOULD LIST, HOWEVER THE PAGE WAS REACHED (www., /index.php...)
  $title = 'mikowl.org - a grassroots millennial art infusion';
  $description = 'See Mikowl.... LOOK AT HIM! A gallery of Mikowl photoshopped into famous paintings, movie scenes, posters, memes, and more.';
  $share_image = 'img/share.jpg';// THE PICTURE IN LINK PREVIEWS: A JPEG ABOUT 1200PX WIDE AND UNDER 300KB (WHATSAPP SKIPS BIGGER ONES)
  $share_alt = "Mikowl leering through the splintered door in the “Here's Johnny!” scene from The Shining";

  $dirname = "img/mikowl/";// IMAGE DIRECTORY (ORIGINALS)
  $webdir = "img/web/";// LIGHTER WEBP COPIES MADE BY bin/optimize-images.php
  $descriptions = json_decode((string) @file_get_contents(__DIR__.'/img/descriptions.json'), true);// ALT TEXT FOR EACH IMAGE, BY FILE NAME
  $images = array();

  foreach (glob(__DIR__.'/'.$dirname."*") as $file) {// FOREACH ALL ITEMS IN THE GLOB
    if (!preg_match('/\.(jpe?g|png|gif|webp|avif)$/i', $file)) {// SKIP ANYTHING THAT IS NOT AN IMAGE
      continue;
    }

    // SERVE THE WEBP COPY WHEN THERE IS ONE, OTHERWISE THE ORIGINAL
    $name = basename($file);
    $path = is_file(__DIR__.'/'.$webdir.$name.'.webp') ? $webdir.$name.'.webp' : $dirname.$name;

    // READ THE REAL DIMENSIONS SO THE BROWSER CAN RESERVE SPACE BEFORE THE IMAGE
    // LOADS. MASONRY CAN THEN LAY OUT THE GRID RIGHT AWAY, EVEN WITH LAZY LOADING.
    $size = @getimagesize(__DIR__.'/'.$path);

    $images[] = array(
      'src'    => asset($path),
      'width'  => $size ? $size[0] : null,
      'height' => $size ? $size[1] : null,
      'alt'    => isset($descriptions[$name])
        ? $descriptions[$name]
        : 'Mikowl: '.ucfirst(trim(preg_replace('/[-_\s]+/', ' ', pathinfo($name, PATHINFO_FILENAME)))),// NOT DESCRIBED YET: GO BY THE FILE NAME
    );
  }// END FOREACH LOOP

  shuffle($images);// A FRESH ORDER ON EVERY PAGE LOAD

  $eager = 6;// THE FIRST FEW IMAGES ARE ABOVE THE FOLD, SO LOAD THEM RIGHT AWAY. THE REST ARE LAZY LOADED.
  $count = count($images);
  $share_size = @getimagesize(__DIR__.'/'.$share_image);

  // STRUCTURED DATA: TELLS SEARCH ENGINES THE SITE'S NAME, THAT THIS PAGE IS AN IMAGE GALLERY, AND WHAT EACH IMAGE SHOWS
  $gallery = array();
  foreach ($images as $image) {
    $gallery[] = array('@type' => 'ImageObject', 'contentUrl' => $site_url.$image['src'], 'caption' => $image['alt']);
  }
  $schema = array(
    '@context' => 'https://schema.org',
    '@graph'   => array(
      array('@type' => 'WebSite', 'url' => $site_url, 'name' => 'mikowl.org', 'alternateName' => 'Mikowl'),
      array('@type' => 'ImageGallery', 'url' => $site_url, 'name' => $title, 'description' => $description, 'associatedMedia' => $gallery),
    ),
  );

?><!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title><?php echo htmlspecialchars($title, ENT_QUOTES); ?></title>
        <meta name="description" content="<?php echo htmlspecialchars($description, ENT_QUOTES); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="max-image-preview:large">
        <meta name="color-scheme" content="dark">
        <meta name="theme-color" content="#110e0b">
        <link rel="canonical" href="<?php echo $site_url; ?>">

        <!-- LINK PREVIEWS: HOW THE PAGE LOOKS WHEN SHARED ON FACEBOOK, X, IMESSAGE, SLACK... -->
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="mikowl.org">
        <meta property="og:url" content="<?php echo $site_url; ?>">
        <meta property="og:title" content="<?php echo htmlspecialchars($title, ENT_QUOTES); ?>">
        <meta property="og:description" content="<?php echo htmlspecialchars($description, ENT_QUOTES); ?>">
        <meta property="og:image" content="<?php echo $site_url.asset($share_image); ?>">
<?php if ($share_size) { ?>
        <meta property="og:image:width" content="<?php echo $share_size[0]; ?>">
        <meta property="og:image:height" content="<?php echo $share_size[1]; ?>">
<?php } ?>
        <meta property="og:image:alt" content="<?php echo htmlspecialchars($share_alt, ENT_QUOTES); ?>">
        <meta name="twitter:card" content="summary_large_image">

        <link rel="icon" href="<?php echo asset('favicon.ico'); ?>" sizes="16x16">
        <link rel="apple-touch-icon" href="<?php echo asset('apple-touch-icon.png'); ?>">

        <link rel="preload" href="fonts/bodoni-moda-roman.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="fonts/bodoni-moda-italic.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="stylesheet" href="<?php echo asset('css/normalize.css'); ?>">
        <link rel="stylesheet" href="<?php echo asset('css/main.css'); ?>">

        <script>document.documentElement.classList.add('js');</script>
        <script src="<?php echo asset('js/vendor/masonry.js'); ?>" defer></script>
        <script src="<?php echo asset('js/main.js'); ?>" defer></script>
        <script src="<?php echo asset('js/lightbox.js'); ?>" defer></script>
<?php if ($ga4_id) { ?>

        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo rawurlencode($ga4_id); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', <?php echo json_encode($ga4_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
        </script>
<?php } ?>
    </head>
    <body>

        <div class="page">

          <header class="masthead">
            <h1 class="wordmark">mikowl<span class="wordmark-tld">.org</span></h1>
            <p class="tagline">a grassroots millennial art infusion</p>
            <p class="count"><span class="count-number"><?php echo $count; ?></span> <?php echo $count === 1 ? 'work' : 'works'; ?></p>
          </header>

          <main>
            <ul class="photos">
<?php foreach ($images as $i => $image) {
  $size = $image['width'] ? ' width="'.$image['width'].'" height="'.$image['height'].'"' : '';
  $loading = $i < $eager ? 'eager' : 'lazy';
  $priority = $i === 0 ? ' fetchpriority="high"' : '';// FETCH THE FIRST IMAGE BEFORE THE REST: IT'S USUALLY THE BIGGEST THING ON SCREEN, AND GOOGLE MEASURES HOW FAST THAT SHOWS UP (LCP)
?>
              <li class="mikey-image">
                <a class="mikey-link" href="<?php echo $image['src']; ?>">
                  <img src="<?php echo $image['src']; ?>" alt="<?php echo htmlspecialchars($image['alt'], ENT_QUOTES); ?>"<?php echo $size; ?> loading="<?php echo $loading; ?>"<?php echo $priority; ?> decoding="async">
                </a>
              </li>
<?php }// END FOREACH LOOP ?>
            </ul><!-- /.photos -->
          </main>

        </div><!-- /.page -->

        <!-- LIGHTBOX: js/lightbox.js OPENS THIS WHEN AN IMAGE IS CLICKED -->
        <dialog class="lightbox" aria-label="Image viewer">
          <div class="lightbox-scrim"></div>
          <div class="lightbox-stage">
            <img class="lightbox-image" alt="">
          </div>
          <p class="lightbox-count" aria-hidden="true"><span class="lightbox-position">1</span> / <?php echo $count; ?></p>
          <p class="lightbox-status visually-hidden" aria-live="polite"></p>
          <button class="lightbox-button lightbox-close" type="button" aria-label="Close" autofocus>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
          </button>
          <button class="lightbox-button lightbox-prev" type="button" aria-label="Previous image">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg>
          </button>
          <button class="lightbox-button lightbox-next" type="button" aria-label="Next image">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg>
          </button>
        </dialog>

        <!-- STRUCTURED DATA FOR SEARCH ENGINES (BUILT AT THE TOP OF THIS FILE) -->
        <script type="application/ld+json"><?php echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?></script>

    </body>
</html>
