/**
 * Shopp TinyMCE Plugin
 * @author Jonathan Davis
 * @copyright Copyright © 2008, Ingenesis Limited, All rights reserved.
 */

(function() {
	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('Shopp');

	tinymce.create('tinymce.plugins.Shopp', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
			ed.addCommand('mceShopp', function() {
				ed.windowManager.open({
					file : url + '/dialog.php',
					width : 320,
					height : 200,
					inline : 1
				}, {
					plugin_url : url // Plugin absolute URL
				});
			});

			// Register example button
			ed.addButton('Shopp', {
				title : 'Shopp.desc',
				cmd : 'mceShopp',
				image : url + '/shopp.png'
			});

			// Add a node change handler, selects the button in the UI when a image is selected
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('Shopp', n.nodeName == 'IMG');
			});
		}, 

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : 'Shopp TinyMCE Plugin',
				author : 'Jonathan Davis',
				authorurl : 'http://insites.ingenesis.net',
				infourl : 'http://shopplugin.net',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('Shopp', tinymce.plugins.Shopp);
})();