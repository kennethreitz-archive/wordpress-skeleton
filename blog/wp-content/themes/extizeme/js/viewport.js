WP.Viewport = Ext.extend(Ext.Viewport, {
    menuDone : false,
    initComponent: function () {
        Ext.apply(this, {
            layout:'fit',
            items:[{
                border:false,
                tbar:[{
                    text:(WP.config.homeBtn ? WP.config.homeBtn.name:'Home'),
                    handler: this.setSrc.createDelegate(this, [WP.extizeUrl(WP.config.homeBtn ? WP.config.homeBtn.url : WP.config.URL)])
                }],
                layout:'border',
                items:[{
                    xtype: 'wp-sidebar',
                    region:'east',
                    width: WP.config.viewport.east,
                    minWidth:250,
                    maxWidth:600,
                    split:true,
                    margins:'5 5 5 0',
                    cmargins:'5 0 5 0',
                    collapsible:true,
                    collapseMode:'mini',
                    collapsed:true,
                    title:'&#160;',
                    border:false,
                    style:'border-width:1px 1px 0 1px'
                },{
                    xtype:'tabpanel',
                    activeTab:0,
                    region:'center',
                    margins:'5 0 5 5',
                    enableTabScroll:true,
                    plugins: new Ext.ux.TabCloseMenu(),
                    items: [{
                        xtype:'wp-page',
                        title:'Loading...',
                        closable: false
                    }]
                },{
                    region:'north',
                    height:40,
                    xtype:'container',
                    autoEl:{},
                    layout:'column',
                    items:[{
                        columnWidth:1,
                        xtype:'box',
                        autoEl:{
                            tag:'div',
                            id:'header',
                            style:'padding:8px 0 0 5px;font-size:24px;font-weight:bold;font-family:Georgia,,Times,serif;font-style:normal;',
                            html:'&#160;'
                        }
                    },{
                        xtype:'container',
                        width:WP.config.viewport.east+5,
                        height:'auto',
                        style:'padding-top:12px;',
                        autoEl:{},
                        items: {xtype:'wp-livesearch'}
                    }]
                }]
            }]
        });
		WP.Viewport.superclass.initComponent.apply(this, arguments);
    },
    onRender: function() {
        WP.Viewport.superclass.onRender.apply(this, arguments);
        WP.on('pageclicked', this.addPagePanel.createDelegate(this));
        WP.on('setpagesrc', this.setSrc.createDelegate(this));
        WP.on('pageheaderfound',  this.setHead.createDelegate(this));
        WP.on('pageloaded',  this.setMainMenu.createDelegate(this));
   },
    getActivePage: function () {
        return this.items.itemAt(0).layout.center.panel.getActiveTab();
    },
    setSrc: function (src) {
        this.getActivePage().setSrc(src);
    },
    setHead: function(header) {
        Ext.get('header').update(header.innerHTML);
    },
    gotoUrl: function(url) {
        this.getActivePage().setSrc(WP.extizeUrl(url));
    },
    addPagePanel: function(url, name , activate) {
        var m = this.items.itemAt(0).layout.center.panel;
        if (m.items.get(escape(url))) {
            m.setActiveTab(escape(url));
            return;
        }
        var n = m.add({
            xtype:'wp-page',
            title:WP.formatLinkName(name),
            id: escape(url),
            defaultSrc: url,
            closable: true
        });
        if (activate) {
            m.setActiveTab(n);
            WP.fireEvent('pageactive', n.getFrame().dom.name, url, name);
        }
        WP.fireEvent('pageadded', url, name);
    },
    setMainMenu: function(frame) {
        if (!this.menuDone) {
            var elements = frame.select('#wp-tb-items//li');
            var mainMenuTb = this.items.itemAt(0).getTopToolbar();
            elements.each(function(el) {
                    var l = el.dom.firstChild.href;
                    if(el.query('li').length != 0) {
                        this.mainMenuLevel = 0;
                        this.mainMenuNested(el, mainMenuTb);
                    } else {
                        mainMenuTb.add({
                            text:el.dom.firstChild.innerHTML,
                            handler: this.gotoUrl.createDelegate(this, [l])
                        });
                    } 
                }.createDelegate(this)
            );
            // setup feeds button
            if (WP.config.entriesRSSBtn) {
                mainMenuTb.add('->',{
                    iconCls:'icon-feed',
                    tooltip: WP.config.entriesRSSBtn.name,
                    handler: function () {
                        document.location.href= WP.config.entriesRSSBtn.url;
                    }
                });
            }
            this.menuDone = true;
        }
    },
    mainMenuNested: function(el, toolbar){
        var l = el.dom.firstChild.href;            
        var nestedMenu = new Ext.menu.Menu();
        // on the first this.mainMenuLevel creates MenuButton(SplitButton) otherwise simple menu item
        if (this.mainMenuLevel < 1) {
            var btn = new Ext.Toolbar.MenuButton({
                text:el.dom.firstChild.innerHTML,
                handler: this.gotoUrl.createDelegate(this, [l]),
                menu: nestedMenu
            });
        } else {
            var btn = new Ext.menu.Item({
                text:el.dom.firstChild.innerHTML,
                handler: this.gotoUrl.createDelegate(this, [l]),
                menu:nestedMenu
            });
        }
        this.mainMenuLevel++;
        // iterates through pages list and creates menu elements
        el.select('//li').each(function (elsub) {
            var l = elsub.dom.firstChild.href;
            if (elsub.query('//li').length != 0 ) {
                this.mainMenuNested(elsub, nestedMenu);
            } else {
                nestedMenu.add({
                    text: elsub.dom.firstChild.innerHTML, 
                    handler: this.gotoUrl.createDelegate(this, [l])
                }); 
            }
        }.createDelegate(this));
        toolbar.add(btn);
    }
});

Ext.reg('wp-viewport', WP.Viewport);