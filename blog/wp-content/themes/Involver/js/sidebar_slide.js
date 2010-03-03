window.addEvent('domready', function() {
	
	var status = {
		'true': 'CLOSE',
		'false': 'OPEN'
	};
	
	var myVerticalSlide = new Fx.Slide('vertical_slide');

	$('v_toggle').addEvent('click', function(e){
		e.stop();
		myVerticalSlide.toggle();
	});
	
	myVerticalSlide.addEvent('complete', function() {
		$('v_toggle').set('html', status[myVerticalSlide.open]);
	});
	
	myVerticalSlide.hide();

});