WP.liveSearch = Ext.extend(Ext.form.ComboBox, {
    initComponent: function () {
        Ext.apply(this, {
            xtype:'combo',
            store: new Ext.data.Store({
                proxy: new Ext.data.IFrameProxy({
                    url: WP.config.URL
                }),
                reader: new Ext.data.XmlReader({
                    record: 'a[rel=bookmark]'
                },[
                    {name:'href', mapping:'@href'},
                    {name:'text', mapping:'@innerHTML'},
                    {name:'snipet'}
                ])
            }),
            minChars: 2,
            resizable:true,
            displayField:'text',
            typeAhead: false,
            emptyText:'Quick search...',
            loadingText: 'Searching...',
            width: WP.config.viewport.east,
            listWidth : 470,
            grow:true,
            growMin : 500,
            pageSize:0,
            hideTrigger:false,
            triggerClass : 'x-form-search-trigger',
            queryParam:'s',
            itemSelector:'.search-item',
            tpl: '<tpl for="."><div class="search-item"><h4>{text}</h4>{href}</div></tpl>',
            onSelect: function(record){ // override default onSelect to do redirect
                WP.fireEvent('setpagesrc', WP.extizeUrl(record.data.href));
            }.createDelegate(this)
        });
        WP.liveSearch.superclass.initComponent.apply(this, arguments);
    }
});

Ext.reg('wp-livesearch', WP.liveSearch);