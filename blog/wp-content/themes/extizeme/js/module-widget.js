WP.Widget = Ext.extend(Ext.Panel, {
    initComponent: function() {
        var cfg = {
            content: '',
            title:'',
            accordionItem:true
        };
        Ext.applyIf(this, cfg);
        Ext.applyIf(this.initialConfig, cfg);

        if (this.accordionItem) {
            Ext.apply(this, {
                border:false,
                bodyStyle:'padding:10px;border-bottom-width:1px;',
                cls: 'accordion-item widget'
            });
        } else {
            Ext.apply(this, {
                frame:true,
                style:'margin-bottom:10px;',
                bodyStyle:'padding:10px;',
                collapsible:true
            });
        }
        
        Ext.applyIf(this, {
            cls:'widget',
            html: this.questBody(),
            autoScroll:true
        });

        // call parent
		WP.Widget.superclass.initComponent.apply(this, arguments);

        var form = this.content.select('form').elements[0];
        if (form) {
            this.on('render', function() {
                f = this.body.select('form');
                f.on('submit', function(e, form) {
                    var com = Ext.getCmp('main').getActiveTab().getFrame().dom;
                    form.target = com.name;
                });
            }, this, {delay:100});
        }
    },
    onRender: function() {
        // call parent
		WP.Widget.superclass.onRender.apply(this, arguments);
        //this.body.update(this.questBody(), true);
        this.body.on('click', this.onClick.createDelegate(this));
        this.body.on('contextmenu', this.onContextMenu.createDelegate(this));

    },
    questBody: function() {
        var gettingBody = this.content.select('.widgetbody');
        if (gettingBody.elements[0]) {
            return gettingBody.elements[0].innerHTML;
        } else {
            return this.content.dom.innerHTML;
        }
    },
    updateContent: function (content) {
        this.content = content;
        this.body.update(this.questBody());
    },
    onClick: function(e) {
        var target = e.getTarget('A');
        if (target && target.nodeName === 'A') {
            target.href = WP.extizeUrl(target.href);
            target.target = WP.config.linkTarget;
            if (e.ctrlKey) {
                e.stopEvent();
                if (target && target.href && target.nodeName === 'A') {
                    WP.fireEvent('pageclicked', target.href, target.innerHTML ,e.shiftKey);
                }  
            }
        }
    },
    onContextMenu: function(e) {
        var target = e.getTarget('A');
        if (target && target.href && target.nodeName === 'A') {
            e.preventDefault();
            target.href = WP.extizeUrl(target.href);
            var frame = WP.App.viewport.getActivePage().getFrame();
            this.menu = WP.ctxMenuA(target, frame);
            this.menu.showAt(e.getXY());

        }
    }
});
Ext.reg('widget', WP.Widget);