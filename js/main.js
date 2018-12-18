
$( document ).ready(function() {

// MASONRY GRID
   var $grid = $('.photos').imagesLoaded( function() {
    // init Masonry after all images have loaded
    $grid.masonry({
      itemSelector: '.mikey-image',
    });
 });


}); // END DOCUMENT READY
