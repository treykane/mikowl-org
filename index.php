<!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>mikowl.org - a grassroots milennial art infusion</title>
        <meta name="description" content="See Mikowl.... LOOK AT HIM!">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="apple-touch-icon" href="apple-touch-icon.png">
        <!-- Place favicon.ico in the root directory -->

        <link rel="stylesheet" href="css/normalize.css">
        <link rel="stylesheet" href="css/main.css">
        <script src="js/vendor/modernizr-2.8.3.min.js"></script>
    </head>
    <body>
        <!--[if lt IE 8]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->

        <!-- Add your site or application content here -->

        <main>

          <h1>mikowl.org</h1>
          <h3>a grassroots milennial art infusion</h3>

          <div class="photos">
            <?php

              $dirname = "img/mikowl/";// IMAGE DIRECTORY
              $images = glob($dirname."*");// WHAT WE WANT TO GLOB
              $i = 0;// INTIAL COUNT

              foreach($images as $image) {// FOREACH ALL ITEMS IN THE GLOB
                if ($i++ > 6) {// SETUP A COUNTER SO WE CAN APPLY LAZY LOADING CLASS
                  // IF IMAGE COUNT IS GREATER THAN SIX
                  echo '
                      <div class="mikey-image lazy">
                        <img src="'.$image.'" alt="Mikowl"/>
                      </div>
                    ';

                  } else {
                  // IF IMAGE COUNT IS LESS THAN SIX
                  echo '
                     <div class="mikey-image">
                       <img src="'.$image.'" alt="Mikowl"/>
                     </div>
                   ';
                 }// END ELSE
               }// END FOREACH LOOP

            ?>
          </div><!-- /.photos -->

        </main>

        <script src="https://code.jquery.com/jquery-1.12.0.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.12.0.min.js"><\/script>')</script>
        <script src="js/plugins.js"></script>
        <script src="js/main.js"></script>

        <script>
            (function(b,o,i,l,e,r){b.GoogleAnalyticsObject=l;b[l]||(b[l]=
            function(){(b[l].q=b[l].q||[]).push(arguments)});b[l].l=+new Date;
            e=o.createElement(i);r=o.getElementsByTagName(i)[0];
            e.src='https://www.google-analytics.com/analytics.js';
            r.parentNode.insertBefore(e,r)}(window,document,'script','ga'));
            ga('create','UA-76355317-1','auto');ga('send','pageview');
        </script>
    </body>
</html>
