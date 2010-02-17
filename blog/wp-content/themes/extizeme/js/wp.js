Ext.ns('WP', 'WP.config');
WP = new Ext.util.Observable();
Ext.apply(WP, {
    extizeUrl: function (url) {
        if (url.indexOf('noext') !==-1) {
            return url;
        }
        var cleanUrl = url;
        var hash = '';
        if (url.indexOf('#') !== -1) {
            cleanUrl = url.substring(0, url.indexOf('#'));
            hash = url.substring(url.indexOf('#'), url.length);
        }
        return [
            cleanUrl,
            (cleanUrl.indexOf('?')!==-1 ? '&noext' : '?noext'),
            hash
        ].join('');
    },
    unExtizeUrl: function (url) {
        return url.replace('?noext','').replace('&noext','');
    },
    formatTitle: function(str) {
        var f = Ext.util.Format;        
        return f.ellipsis(f.stripTags(str || '&#160;'), 40);
    },
    formatLinkName: function(str) {
        return (Ext.util.Format.stripTags(str) || 'Link...');
    },
    ctxMenuA: function(target, frame) {
        var menu =  new Ext.menu.Menu({
            items: [{
                text: 'Open',
                handler: function(){
                    frame.setSrc(target.href);
                }
            },{
                text: 'Open in new tab',
                handler: function(){
                    WP.fireEvent('pageclicked', WP.extizeUrl(target.href), target.innerHTML, true);
                }
            }]
        });
        menu.on('hide', function(menu) {
            Ext.destroy(menu);
        });
        return menu;
    }
});
WP.addEvents(
    'pageadded', // viewport
    'pageactive',
    'setpagesrc', // livesearch
    
    'locationchange', 
    'pageloaded',
    'pageready',

    'pageheaderfound',
    'pagewidgetsfound',
    'pagegalleryfound',
    'pageunloaded',

    'pageclicked'
);
