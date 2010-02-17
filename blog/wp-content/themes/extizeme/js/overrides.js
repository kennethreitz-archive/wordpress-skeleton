Ext.apply(Ext, {
    BLANK_IMAGE_URL : (function() {
        if ((Ext.isIE8) || Ext.isGecko) {
            return "data:image/gif;base64,R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";
        } else {
            return "http:/"+"/extjs.cachefly.net/ext-" + Ext.version + "/resources/images/default/s.gif";
        }
    })()
});

Ext.override(Ext.Window, {
    initComponent : function(){
        Ext.Window.superclass.initComponent.call(this);
        this.addEvents(
            'resize',
            'maximize',
            'minimize',
            'restore',
            'startscale',
            'completescale'
        );
    },
    scale: function(w, h) {
        this.fireEvent('startscale', this);
        a = Ext.lib.Anim.motion(this.el, {height: {to: h}, width: {to: w}}, .5, 'easeIn');
        a.onTween.addListener(function(){
            this.center();
            this.syncSize();
            this.syncShadow();
        }, this);
        a.onComplete.addListener(function(){
            this.fireEvent('completescale', this);
        }, this);
        a.animate();
        return true;
    }
});

Ext.override(Ext.Element, {
    maskG : function(msg, msgCls){
        if(this.getStyle("position") == "static"){
            this.addClass("x-masked-relative");
        }
        if(this._maskMsg){
            this._maskMsg.remove();
        }
        if(this._mask){
            this._mask.remove();
        }

        this._mask = Ext.DomHelper.append(this.dom, {cls:"ext-el-maskG"}, true);

        this.addClass("x-masked");
        this._mask.setDisplayed(true);
        if(typeof msg == 'string'){
            this._maskMsg = Ext.DomHelper.append(this.dom, {cls:"ext-el-mask-msg", cn:{tag:'div'}}, true);
            var mm = this._maskMsg;
            mm.dom.className = msgCls ? "ext-el-mask-msg " + msgCls : "ext-el-mask-msg";
            mm.dom.firstChild.innerHTML = msg;
            mm.setDisplayed(true);
            mm.center(this);
        }
        if(Ext.isIE && !(Ext.isIE7 && Ext.isStrict) && this.getStyle('height') == 'auto'){ // ie will not expand full height automatically
            this._mask.setSize(this.getWidth(), this.getHeight());
        }
        return this._mask;
    }
});

Ext.override(Ext.TabPanel, {
	getActiveTabIndex: function() {
		for(var i=0; i<this.items.getCount(); i++) {
			if(this.activeTab == this.items.get(i)) {
				return i;
			}
		}
		return -1;
	},

	showNextTab: function() {
		var i = this.getActiveTabIndex() + 1;
		if(this.items.get(i)) {
			this.setActiveTab(this.items.get(i));
		}
	},

	showPreviousTab: function() {
		var i = this.getActiveTabIndex() - 1;
		if(this.items.get(i)) {
			this.setActiveTab(this.items.get(i));
		}
	}

});
