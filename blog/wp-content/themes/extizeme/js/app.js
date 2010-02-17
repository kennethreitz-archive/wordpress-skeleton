WP.App = function() {
    return  {
        menuDone : false,
        mainMenuLevel: 0,
        init: function () {

            Ext.QuickTips.init();

            this.viewport = new WP.Viewport();
            this.cssFixes();

            WP.on('pageactive', function (n) {
                WP.config.linkTarget = n;
            });
            
            this.viewport.getActivePage().setSrc(WP.config.URL);
            WP.fireEvent('pageactive', this.viewport.getActivePage().getFrame().dom.name);
            
        },
        cssFixes: function() {
            Ext.util.CSS.createStyleSheet( [
                '.ext-el-maskG {z-index: 100;position: absolute;top:0;left:0;width: 100%;height: 100%;zoom: 1;}',
                'body {padding: 15px;font-family:verdana,geneva,lucida, "lucida grande",arial,helvetica,sans-serif;font-size:12px;color:#333;}',
                
                'a {color:#1A5C9A;text-decoration:none;}',
                'a:hover {color:#1C417C;text-decoration:underline;}',
                '#header {color:#1A5C9A;text-decoration:none;}',
                
                '.search-item {font:normal 11px tahoma, arial, helvetica, sans-serif;padding:3px 10px 3px 10px;border:1px solid #fff;border-bottom:1px solid #eeeeee;color:#000;}',
                '.search-item h4 {color:#000;display:block;font:inherit;font-weight:bold;font-size:11px;}',
                
                '.widget-special-wrap, .widget-special {display:none;}',
                '.widget ul  {list-style-image:url(http://extjs.cachefly.net/ext-' + Ext.version + '/resources/images/default/menu/menu-parent.gif);margin-left:25px;}',
                '.widget ul li {display:list-item;margin-bottom:5px;margin-top:5px;font-size:12px;line-height:18px;}'
            ].join(''), 'siteFixes');

        }
    }
}(); // end of app