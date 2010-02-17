
WP.Sidebar = Ext.extend(Ext.Panel, {
    initComponent: function () {
        /* default or alterable configurables */
        var cfg = { };
        //use applyIf so can reconfigure instances            
        Ext.applyIf(this, cfg);
        Ext.applyIf(this.initialConfig, cfg);

    if (WP.config.sidebar === 'accordion') {
        Ext.apply(this, {
            layout:'accordion'
        });
    } else {
        Ext.apply(this, {
            layout:'anchor',
            title:false,
            collapsible:false,
            baseCls:'x-plain',
            autoScroll:true,
            bodyStyle:'border-bottom-width:1px'
        });
    }
        WP.Sidebar.superclass.initComponent.apply(this, arguments);
    },
    onRender: function() {
        WP.Sidebar.superclass.onRender.apply(this, arguments);
        WP.on('pagewidgetsfound', this.setup.createDelegate(this));
    },
    setup: function(col) {
        if (col.elements.length===0) {
            this.hide();
            return;
        }
        var q = Ext.DomQuery;

        col.each(function(w){
            if (w.dom.id == 'extizeme-switch') { 
                return;
            }
            var t = q.selectNode('.widgettitle', w.dom)||q.selectNode('.widget-title', w.dom)||q.selectNode('.title', w.dom)||q.selectNode('*', w.dom);
            if (t && t.innerHTML) {
                var widgetId = 'widget-' + Ext.util.MD5(t.innerHTML);
                if (!Ext.getCmp(widgetId)) {
                    this.add({
                        accordionItem: (WP.config.sidebar === 'accordion'? true:false),
                        id: widgetId,
                        xtype:'widget',
                        title:WP.formatTitle(t.innerHTML || ''),
                        content: w
                    });
                } else {
                    Ext.getCmp(widgetId).updateContent(w);
                }
            }
        }, this);
        this.expand();
    }
});

Ext.reg('wp-sidebar', WP.Sidebar);