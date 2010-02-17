jQuery(document).ready(function($){

    $("#tml-container").tabs({ 
		select: function(event, ui) {
			setUserSetting( 'tml0', ui.index );
		},
		selected: getUserSetting( 'tml0', 0 )
	});
	
	$("#tml-container div").tabs({
		select: function(event, ui) {
			setUserSetting( 'tml1', ui.index );
		},
		selected: getUserSetting( 'tml1', 0 )
	});
});