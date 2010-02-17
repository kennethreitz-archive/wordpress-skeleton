Ext.ns('Browser');

Browser.Gallery = Ext.extend(Ext.Window, {
    initComponent: function() {
        var cfg = {
            galleryItems:[],
            currentIndex:0
        };
        Ext.applyIf(this, cfg);
        Ext.applyIf(this.initialConfig, cfg);
        
        Ext.apply(this, {
            closable:true,
            shadow:false,
            plain:true,
            bodyStyle:'border-width:1px 1px 0 1px',
            constrain :true,
            //autoScroll:true,
            width:240,
            height:250,
            resizable:false,
            modal:true,
            bbar:[{
                text:'Open Image',
                scope:this,
                handler: function(){
                    window.open(this.prevImg.src);
                }
            },'->',{
                text:'Previous',
                id:'gallery-prev',
                scope:this,
                handler: function(){
                    this.currentIndex--;
                    this.disableEnableGalleryBtns();
                    this.setupImg();
                }
            },{
                text:'Next',
                id:'gallery-next',
                scope:this,
                handler: function(){
                    this.currentIndex++;
                    this.disableEnableGalleryBtns();
                    this.setupImg();
                }
            }],
            html:'<div class="ext-el-mask-msg x-mask-loading" style="left: 66px; top: 86px;"><div>Loading...</div></div>'
        });
        // call parent
		Browser.Gallery.superclass.initComponent.apply(this, arguments);

        this.animateTarget = this.galleryItems.elements[this.currentIndex];
        this.themeProxy =  new Ext.ux.ManagedIframePanel({
                cls:'x-hidden',
                renderTo:Ext.getBody()
        });
        this.themeProxy.on('documentloaded', function(f){
            this.replaceTitleByFrameContent(f);
            this.replaceBodyByFrameContent(f);
        }, this);
        this.on('startscale', function(a,b,c) {
            this.body.hide();
        }, this);
        this.on('completescale', function(a,b,c) {
            this.body.fadeIn();
        }, this);
    },
    onRender:function() {
        // call parent
		Browser.Gallery.superclass.onRender.apply(this, arguments);
        this.setupImg();
        this.disableEnableGalleryBtns();
    },
    beforeDestroy: function() {
        this.themeProxy.events['documentloaded'].clearListeners();
        // call parent
		Browser.Gallery.superclass.beforeDestroy.apply(this, arguments);
    },
    disableEnableGalleryBtns: function() {
        var i = this.getBottomToolbar().items;
        var prev = i.itemAt(1);
        var next = i.itemAt(2);
        if (this.currentIndex === 0) {
            prev.disable();
        } else if (this.currentIndex == this.galleryItems.elements.length-1) {
            next.disable();
        } else {
            prev.enable();
            next.enable();
        }
    },
    setupImg: function(showMask) {
        this.body.maskG('Loading...', 'x-mask-loading');
        var link = this.guesLink(this.galleryItems.elements[this.currentIndex]);
        this.themeProxy.setSrc(link.href + '?noext');
    },
    replaceTitleByFrameContent: function(f) {
        var t = f.select('title');
        if (t.elements[0]) {
            this.setTitle(t.elements[0].innerHTML);
        }
    },
    replaceBodyByFrameContent: function(f) {
        var bBox = Ext.getBody().getBox();
        var maxW = bBox.width;
        var maxH = bBox.height;
        
        var baseImg = f.select('img[class*=attachment]').elements[0] || f.select('img').elements[0];
        
        var img = {
            width   : (baseImg.naturalWidth||baseImg.width),
            height  : (baseImg.naturalHeight||baseImg.height),
            src     : baseImg.src
        };
        
        Ext.DomHelper.overwrite(this.body, {
            tag     : 'img', 
            id      : Ext.util.MD5(img.src), 
            src     : img.src, 
            width   : img.width, 
            height  : img.height, 
            style   : 'position:relative;top:0;left:0;'
        });
        
        if (this.prevImg === undefined || this.prevImg.width !== img.width) {
            this.scale(Math.min(img.width+16, maxW), Math.min(img.height+59, maxH));
        } else {
            Ext.get(Ext.util.MD5(img.src)).fadeIn();
        }
        this.prevImg = img;
    },
    guesLink: function(item) {
        return Ext.get(item).child('a', true);
    }
});
Ext.reg('gallery', Browser.Gallery);