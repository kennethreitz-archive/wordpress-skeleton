WP.Page = Ext.extend(Ext.ux.ManagedIframePanel, {
    entryFontSize: 12,
    initComponent: function() {
        /* default or alterable configurables */
        var cfg = { };
        //use applyIf so can reconfigure instances            
        Ext.applyIf(this, cfg);
        Ext.applyIf(this.initialConfig, cfg);

        this.frameId = Ext.id();

        /* nonalterable */
        Ext.apply(this, {
            loadMask:{hideOnReady :false,msg:'Loading Site...'},
            frameConfig: {autoCreate:{ name : 'name-'+this.frameId, id: this.frameId }},  
            autoScroll : true,
            autoShow: true,
            defaultSrc: this.defaultSrc,
            bbar: new Ext.Toolbar({
                cls:'x-statusbar',
                items:[{
                    iconCls: 'x-tbar-loading',
                    handler: this.frameRefresh.createDelegate(this)
                },{
                    text:'Add Comment',
                    hidden:true
                },{
                    text:'Open Gallery',
                    hidden:true
                },
                '->',
                'Loading...',{
                    xtype:'cycle',
                    showText: true,
                    items:[{
                        size:10,
                        text:'90%'
                    },{
                        size:12,
                        text:'100%',
                        checked:true
                    },{
                        size:16,
                        text:'120%'
                    },{
                        size:18,
                        text:'140%'
                    },{
                        size:20,
                        text:'160%'
                    },{
                        size:22,
                        text:'180%'
                    },{
                        size:24,
                        text:'200%'
                    },{
                        size:36,
                        text:'300%'
                    },{
                        size:48,
                        text:'400%'
                    }],
                    changeHandler:function (slider, b) {
                        this.entryFontSize = b.size;
                        this.makeEntryFontSize();
                    },
                    scope:this
                }]
            })
        });
        WP.Page.superclass.initComponent.apply(this, arguments);
    },
    onRender: function() {
        WP.Page.superclass.onRender.apply(this, arguments);
        
        this.on('activate', function (){
            WP.fireEvent('pageactive', this.getFrame().dom.name);
        }, this);
        // simple status styling
        Ext.fly(this.getStatusLink().getEl().parentNode).addClass('x-status-text-panel');
        /**
         * event handlers
         */
        this.getFrame().on('domready', function(f){
            WP.fireEvent('pageready', f);
            f.getDoc().on('click', this.onDocClick.createDelegate(this));
            f.getDoc().on('contextmenu', this.onDocContextMenu.createDelegate(this));
            this.makeEntryFontSize();
        }, this);
        
        this.getFrame().on('documentloaded', function(f) {
            var wordpressHeader = false,
                wordpressWidgets = false,
                wordpressGallery = false,
                commentform = false;
                
            WP.fireEvent('pageloaded', f);
            this.setupFrame(f);
            
            wordpressHeader = f.select('#header h1').elements[0] || f.select('#header h2').elements[0] || f.select('title').elements[0]
            if (wordpressHeader) {
                WP.fireEvent('pageheaderfound', wordpressHeader);
            }
            wordpressWidgets = f.select('.widget, .block, .sidebar-module, .blog-categories');
            if (wordpressWidgets.elements && wordpressWidgets.elements.length>0) {
                WP.fireEvent('pagewidgetsfound', wordpressWidgets);
            }
            wordpressGallery = f.select('.gallery-item');
            if (wordpressGallery.elements, wordpressGallery.elements.length>0) {
                WP.fireEvent('pagegalleryfound', wordpressGallery);
                this.setupGalleryBtn(wordpressGallery);
            }
/*

            commentform = f.get('commentform');
            if (commentform) {
                WP.fireEvent('pagecommentformfound', commentform);
                this.setupCommentBtn();
            }
*/
        }, this);
        
        this.getFrame().on('unload', function(f) {
            WP.fireEvent('pageunloaded', f);
            this.getFrame().showMask();
            this.unSetupFrame();
        }, this)
    },
    unSetupFrame: function() {
        this.getCommentBtn().hide();
        this.getGalleryBtn().hide();
    },
    setupFrame: function(f) {
        if (f.domWritable()) {
            var uri = WP.unExtizeUrl(f.getDocumentURI());
            var frameTitle = f.select('title').elements[0];
            // setup title
            if (frameTitle !== undefined) {
                this.setTitle(WP.formatTitle(frameTitle.innerHTML) + '&#160;');
            } else {
                this.setTitle(WP.formatTitle(uri) + '&#160;');
            }
            // setup statusbar 
            var location = [
                '<a ext:qtip="Permanent link." href="', 
                uri, 
                '" style="text-decoration:none;">', 
                Ext.util.Format.ellipsis(uri,40), 
                '</a>'
            ].join('');
        } else {
            // setup statusbar 
            var location = '<span ext:qtip="As this breaks same origin policy, we are not able to manage..." style="text-decoration:none;color:#dd1100;font-weight:bold;">Unmanageable.</span>';
        }
        Ext.fly(this.getStatusLink().getEl()).update(location);
    },
    setupCommentBtn: function() {
        var btn = this.getCommentBtn();
        btn.show();
        btn.handler = function () {
            this.openCommentForm.createDelegate(this);
        };
    },
    setupGalleryBtn: function(galleryItems) {
        var btn = this.getGalleryBtn();
        btn.show();
        btn.handler = function () {
            var gallery = new Browser.Gallery({
                currentIndex:0,
                galleryItems:galleryItems
            });
            gallery.show();
        };
    },
    onDocClick: function (e) {
        if (this.menu) {
            this.menu.hide();
        }
        var target = e.getTarget('A');
        if (target && target.nodeName === 'A') {
            target.href = WP.extizeUrl(target.href);
            if (e.ctrlKey) {
                e.stopEvent();
                WP.fireEvent('pageclicked', WP.extizeUrl(target.href), target.innerHTML ,e.shiftKey);
            }
        }  
    },
    onDocContextMenu: function (e) {
        var targetA = e.getTarget('A');

        if (targetA && targetA.nodeName === 'A') {
            e.preventDefault();
            this.menu = WP.ctxMenuA(targetA, this);
            /**
             *  1. Browser.mb.config.viewport.west
             *  2. scroll.left
             *  3. frameBox.x
             */
            var scroll = this.getScroll();
            var frameBox = this.getFrame().getBox();
            this.menu.showAt([e.getPageX()+WP.config.viewport.west-scroll.left+frameBox.x, e.getPageY()-scroll.top+frameBox.y]);

        }
    },
    getScroll: function() {
        var d = this.getFrameDoc().dom, doc = this.getFrameDocument();
        if(d == doc || d == doc.body){
            var l, t;
            if(Ext.isIE && Ext.isStrict){
                l = doc.documentElement.scrollLeft || (doc.body.scrollLeft || 0);
                t = doc.documentElement.scrollTop || (doc.body.scrollTop || 0);
            }else{
                l = this.getFrameWindow().pageXOffset || (doc.body.scrollLeft || 0);
                t = this.getFrameWindow().pageYOffset || (doc.body.scrollTop || 0);
            }
            return {left: l, top: t};
        }else{
            return {left: d.scrollLeft, top: d.scrollTop};
        }    
    },
    makeEntryFontSize: function() {
        var b = this.entryFontSize;
        this.getFrame().select('.entry, .entry *, .excerpt, .excerpt *, .post-con *, .storycontent *').applyStyles({
            'font-size'   :b +'px',
            'line-height' :b+(b/2) + 'px'
        });
    },
    frameRefresh: function() {
        var f = this.getFrame();
        if (f.domWritable()) {
            this.setSrc(f.getDocumentURI());
        }
    },
    getRefreshBtn: function() {
        return this.getBottomToolbar().items.itemAt(0);
    },
    getCommentBtn: function() {
        return this.getBottomToolbar().items.itemAt(1);
    },
    getGalleryBtn: function() {
        return this.getBottomToolbar().items.itemAt(2);
    },
    getStatusLink: function() {
        return this.getBottomToolbar().items.itemAt(4);
    }
});
Ext.reg('wp-page', WP.Page);