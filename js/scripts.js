jQuery(document).ready(function($) {
  $('a[rel="external"]').click( function() {
   window.open( $(this).attr('href') );
   return false;
  });
});
