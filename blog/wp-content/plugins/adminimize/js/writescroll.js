/**
 * Automatically scroll Write pages to a good position
 * code by Dougal Campbell
 * http://dougal.gunters.org/blog/2008/06/03/writescroll
 */
jQuery(function($) {
	// element to scroll
	var h = jQuery('html');
	// position to scroll to
	var wraptop = jQuery('div#wpbody').offset().top;
	var speed = 250; // ms
	h.animate({scrollTop: wraptop}, speed);
});