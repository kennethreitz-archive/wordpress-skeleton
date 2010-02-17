jQuery(document).ready(function() {
jQuery(function() {
var zIndexNumber = 1000;
jQuery('ul').each(function() {
jQuery(this).css('zIndex', zIndexNumber);
zIndexNumber -= 10;
});});

jQuery("#front_menu ul").css('opacity', 0.9);
jQuery("#front_menu ul").css({display: "none"}); // Opera Fix
jQuery("#front_menu li").hover(function(){
jQuery(this).find('ul:first').css({visibility: "visible",display: "none"}).show();
},function(){
jQuery(this).find('ul:first').css({visibility: "hidden"});
});
});


