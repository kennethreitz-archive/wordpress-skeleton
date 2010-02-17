/*                       __  __           _     
       WordPress Plugin |  \/  |         | |    
  _ __   __ _  __ _  ___| \  / | __ _ ___| |__  
 | '_ \ / _` |/ _` |/ _ \ |\/| |/ _` / __| '_ \ 
 | |_) | (_| | (_| |  __/ |  | | (_| \__ \ | | |
 | .__/ \__,_|\__, |\___|_|  |_|\__,_|___/_| |_|
 | |           __/ |  Author: Joel Starnes
 |_|          |___/   URL: pagemash.joelstarnes.co.uk
 
 >>Main javascript include
*/

window.addEvent('domready', function(){ 
	// If user doesn't have Firebug, create empty functions for the console.
	if (!window.console || !console.firebug)
	{
	    var names = ["log", "debug", "info", "warn", "error", "assert", "dir", "dirxml",
	    "group", "groupEnd", "time", "timeEnd", "count", "trace", "profile", "profileEnd"];
	
	    window.console = {};
	    for (var i = 0; i < names.length; ++i)
	        window.console[names[i]] = function() {}
	}
});

/* add timeout to Ajax class */
Ajax = Ajax.extend({
	request: function(){
	if (this.options.timeout) {
		this.timeoutTimer=window.setTimeout(this.callTimeout.bindAsEventListener(this), this.options.timeout);
		this.addEvent('onComplete', this.removeTimer);
	}
	this.parent();
	},
	callTimeout: function () {
		this.transport.abort();
		this.onFailure();
		if (this.options.onTimeout) {
			this.options.onTimeout();

		}
	},
	removeTimer: function() {
		window.clearTimeout(this.timeoutTimer);
	}
});
/* function to retrieve list data and send to server in JSON format */
var saveList = function() {
	var theDump = sortIt.serialize();
	console.group('Database Update');
	console.time('Update Chronometer');
	new Ajax('../wp-content/plugins/pagemash/savelist.php', {
		method: 'post',
		postBody: 'm='+Json.toString(theDump), 
		update: "debug_list", 
		onComplete: function() {
			$('update_status').setText(window.pmash.update);
			new Fx.Style($('update_status'), 'opacity', {duration: 500}).start(0,1).chain(function() {
				new Fx.Style($('update_status'), 'opacity', {duration: 1500}).start(1,0);
			});
			console.log('Database Successfully Updated');
			console.timeEnd('Update Chronometer');
			console.groupEnd();
		},
		timeout: 8500, 
		onTimeout: function() {
			$('update_status').setText('Error: Update Timeout');
			new Fx.Style($('update_status'), 'opacity', {duration: 200}).start(0,1);
			console.timeEnd('Update Chronometer');
			console.error('Error: update confirmation not recieved');
			console.groupEnd();
		}
	}).request();
};
/* toggle the remove class of grandparent */
	var toggleRemove = function(el) {
		el.parentNode.parentNode.parentNode.toggleClass('remove');
		console.log("Page: '%s' has been %s", $E('span.title', el.parentNode.parentNode.parentNode).innerHTML, (el.parentNode.parentNode.hasClass('remove') ? 'HIDDEN': 'MADE VISIBLE' ));
	}


/* ******** dom ready ******** */
window.addEvent('domready', function(){ 
	sortIt = new Nested('pageMash_pages', {
		collapse: true,
		onComplete: function(el) {
			el.setStyle('background-color', '#F1F1F1');
			sortIt.altColor();
			
			$ES('li','pageMash_pages').each(function(el) {
				if( el.getElement('ul') ){
					el.addClass('children');
				} else {
					el.removeClass('children');
				}
			});
		}
	});	
	Nested.implement({
		/* alternate the colours of top level nodes */
		altColor: function(){
			var odd = 1;
			this.list.getChildren().each(function(element, i){
				if(odd==1){
					odd=0;
					element.setStyle('background-color', '#CFE8A8');
				}else{
					odd=1;
					element.setStyle('background-color', '#D8E8E6');
				}
			});
		}
	});
	sortIt.altColor();
	$('update_status').setStyle('opacity', 0);
		
	$('pageMash_submit').addEvent('click', function(e){
		e = new Event(e);
		saveList();
		e.stop();
	});

	var pageMashInfo = new Fx.Slide('pageMashInfo');
	$('pageMashInfo_toggle').addEvent('click', function(e){
		e = new Event(e);
		pageMashInfo.toggle();
		e.stop();
		switch($('pageMashInfo_toggle').getText()) {
			case "Show Further Info":
				$('pageMashInfo_toggle').setText(window.pmash.hideInfo);
			  break    
			case "Hide Further Info":
				$('pageMashInfo_toggle').setText(window.pmash.showInfo);
			  break
		}
	});
	pageMashInfo.hide();
	$('pageMashInfo_toggle').setText(window.pmash.showInfo);
	
	
	/* loop through each page */
	$ES('li','pageMash_pages').each(function(el) {
		/* If the li has a 'ul' child; it has children pages */
		if( el.getElement('ul') ) el.addClass('children');
		
		/* on page dblClick add this event */
		el.addEvent('dblclick', function(e){
			e = new Event(e);
			if(el.hasClass('children')) el.toggleClass('collapsed');
			e.stop();
		});
	});
	
	$('collapse_all').addEvent('click', function(e){ e = new Event(e);
		$ES('li','pageMash_pages').each(function(el) {
			if(el.hasClass('children')) el.addClass('collapsed');
		});
	e.stop(); });
	
	$('expand_all').addEvent('click', function(e){ e = new Event(e);
		$ES('li','pageMash_pages').each(function(el) {
			if(el.hasClass('children')) el.removeClass('collapsed');
		});
	e.stop(); });

	/* disable drag text-selection for IE */
	if (typeof document.body.onselectstart!="undefined")
		document.body.onselectstart=function(){return false}
	
	/* InlineEdit: rename pages */
	$$('#pageMash_pages li span.title').each(function(el){ //#pageMash_pages li span.title
		el.setStyle('cursor','pointer');
		$E('a.rename', el.parentNode).addEvent('click',function(){
			el.inlineEdit({
				onStart:function(el){
					 el.parentNode.addClass('renaming');
				},
				onComplete:function(el,oldContent,newContent){
					el.parentNode.removeClass('renaming').addClass('renamed');
					console.log("Page: '%s' has been RENAMED to: '%s'", oldContent, newContent);
				}
			});
		});
	});
	
	console.info("We're all up and running.")
}); /* close dom ready */