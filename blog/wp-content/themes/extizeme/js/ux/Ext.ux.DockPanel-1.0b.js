Ext.namespace('Ext.ux');

Ext.apply(Function.prototype, {
	createAfterAdvice : function(fcn, scope){
		if(typeof fcn != "function"){
			return this;
		}
		var method = this;
		return function() {
			var retval = method.apply(this || window, arguments);
			var args = new Array(retval);
			if(Ext.isArray(arguments))
				args.concat(arguments);
			else if(arguments)
				args.push(arguments[0]);
			return fcn.apply(scope || this || window, args);
		};
	}
});


Ext.ux.DockContainer = {
	onRender : function(){
	},
	highlightDropZone : function(dragenter){
	},
	unhighlightDropZone : function(dragout){
	},
	highlightDropPosition : function(c,x,y){
	},
	unhighlightDropPosition : function(){
	},
	getPosition: function(c) {
	},
	onDock: function(c, activate, position) {
	},
	saveItemsState: function() {
	},
	getPanelXY: function(c) {
	},
	onStartDrag: function(c){
	},
	afterUndock: function(c){
	}
};

Ext.ux.DockContainer.DropTarget = function(el, ct, config) {
	this.ct = ct;
	this.el = Ext.get(el);
	Ext.apply(this, config);
	if(this.containerScroll){
		Ext.dd.ScrollManager.register(this.el);
	}
	Ext.ux.DockContainer.DropTarget.superclass.constructor.call(this, this.el.dom, this.ddGroup || this.group, {isTarget: true});
};

Ext.extend(Ext.ux.DockContainer.DropTarget, Ext.dd.DDTarget, {
	notifyStartDrag : function(dd, x, y){
		this.ct.highlightDropZone(false);
		if(dd.panel.getOwner() == this.ct)
			this.ct.onStartDrag(dd.panel);
	},
	notifyEndDrag : function(dd, x, y){
		this.ct.unhighlightDropZone(false);
		this.ct.unhighlightDropPosition();
	},
	notifyEnter : function(dd, x, y){
		this.ct.highlightDropPosition(dd.panel,x,y);
	},
	notifyOver : function(dd, x, y){
		this.ct.highlightDropZone(true);
		this.ct.highlightDropPosition(dd.panel,x,y);
	},
	notifyOut : function(dd, x, y){
		this.ct.unhighlightDropZone(true);
		this.ct.unhighlightDropPosition();
	},
	afterUndock : function(dd, x, y){
		this.ct.afterUndock(dd.panel);
	}
});



Ext.ux.DockAccordionPanel = function(config) {
	config.layout = 'accordion';
	Ext.ux.DockAccordionPanel.superclass.constructor.call(this, config);
};

Ext.extend(Ext.ux.DockAccordionPanel, Ext.Panel, {
	initComponent: function() {
		Ext.applyIf(this, Ext.ux.DockAccordionPanel.defaults);
		Ext.applyIf(this, Ext.ux.DockContainer);
		Ext.ux.DockAccordionPanel.superclass.initComponent.call(this);
	},
	onRender: function(ct,position) {
		Ext.ux.DockAccordionPanel.superclass.onRender.call(this, ct, position);
		this.dd = new Ext.ux.DockContainer.DropTarget(this.el, this, {ddGroup:this.ddGroup});
		if(!this.highlightCls) this.highlightCls = 'x-dock-highlight';
		this.highlightProxy = this.body.createProxy({tag:'div', cls:this.highlightCls}, Ext.getBody());
		this.highlightProxy.hide();
		this.dropMark = this.body.createChild({tag: 'div', cls: 'x-dock-tab-drop-highlight'});
		this.dropMark.createChild({tag:'img', src:'pos-l.gif', style:'float:left;'});
		this.dropMark.createChild({tag:'img', src:'pos-r.gif', style:'float:right;'});
		this.dropMark.hide();
		this._calcDropPlaces();
	},
	_initDD: function(c) {
		c.win.dd.setOuterHandleElId(c.wrapper.header.id);
		c.wrapper.on('expand', this._calcDropPlaces, this);
		c.docked = true;
	},
	getPosition: function(c) {
		var pos = this.items.indexOf(c.wrapper);
		if(pos == -1) pos = this.items.indexOf(c);
		return pos;
	},
	onDock: function(c, activate, position) {
		var oldw = c.wrapper;
		var w = c.wrap();
		position = position != undefined ? position : this.dropPosition;
		if(position != undefined && position <= this.items.getCount())
			this.insert(position, w);
		else
			this.add(w);
		w.on('render', (function(c) {
			this._initDD(c);
		}).createDelegate(this, [c]));
		this.doLayout();
		if(activate)
			w.expand();
		this._calcDropPlaces();
	},
	saveItemsState: function() {
		this.items.each(function(s) {
			if(s.wrapped) s.wrapped.saveState();
		}, this);
	},
	getPanelXY: function(c) {
		var xy = c.wrapper.header.getXY();
		return xy;
	},
	afterUndock: function(c){
		c.un('expand', this._calcDropPlaces(), this);
		var items = this.items.items;
		var w;
		for(i = 0;i<items.length;i++)
			if(items[i].wrapped == c && c.wrapper != items[i])
				this.remove(items[i]);
		this.doLayout();
		this._calcDropPlaces();
	},
	highlightDropZone: function(dragenter) {
		if(dragenter || this.highlightOnDrag) {
			this.highlightProxy.setBox(this.body.getBox());
			this.highlightProxy.show();
		}
	},
	onStartDrag: function(c){
		c.wrapper.expand();
		this._calcDropPlaces();
	},
	unhighlightDropZone: function(dragout) {
		if(!dragout || !this.highlightOnDrag)
			if(this.highlightProxy)
				this.highlightProxy.hide();
	},
	highlightDropPosition : function(c,x,y){
		var i = 0;
		y = y - this.el.getTop();
		while(y>this.dropPlaces[i]) i++;
		if(this.dropPosition == i) return;
		this.dropPosition = i;
		this.dropMark.setWidth(this.body.getWidth());
		var item = this.items.items[i];
		if(item)
			this.dropMark.setTop(this.items.items[i].el.getTop() - this.body.getTop() - 7);
		else
			this.dropMark.setTop(this.body.getBottom() - this.body.getTop() - 7);
			
		this.dropMark.show();
	},
	unhighlightDropPosition : function(){
		this.dropPosition = -1;
		this.dropMark.hide();
	},
	_calcDropPlaces: function() {
		if(!this.items) {
			this.dropPlaces = [5000];
			return;
		}

		var items = this.items.items;
		if(items.length == 0) {
			this.dropPlaces = [5000];
			return;
		}
		this.dropPlaces = [];
		for(var i=0;i<items.length;i++) {
			var cb = items[i].el.getBox();
			if(!this.dropPlaces[i]) {
				this.dropPlaces.push(cb.y - this.el.getTop() + cb.height / 2);
			}
			else {
				this.dropPlaces[i] = cb.y - this.el.getTop() + cb.height / 2;
			}
			this.dropPlaces.push(5000);
		}
	}
});

Ext.ux.DockAccordionPanel.defaults = {
		ddGroup: 'dock-panels'
};

Ext.ux.DockTabPanel = Ext.extend(Ext.TabPanel, {
	initComponent: function() {
		Ext.applyIf(this, Ext.ux.DockTabPanel.defaults);
		Ext.applyIf(this, Ext.ux.DockContainer);
		this.dropPosition = -1;
		Ext.ux.DockTabPanel.superclass.initComponent.call(this);
	},
	onRender: function(ct, position) {
		Ext.ux.DockTabPanel.superclass.onRender.call(this, ct, position);
		this.dropEl = this.el;
		this.highlightEl = this.el;
		if(!this.highlightCls) this.highlightCls = 'x-dock-highlight';
		if(this.dockTarget == 'header')
			this.dropEl = this.header;
		if(this.highlightTarget == 'stripwrap')
			this.highlightEl = this.stripWrap;
		this.dd = new Ext.ux.DockContainer.DropTarget(this.dropEl, this, {ddGroup:this.ddGroup});
		this.highlightProxy = this.highlightEl.createProxy({tag:'div', cls:this.highlightCls}, Ext.getBody());
		this.highlightProxy.hide();
		
		this.dropMark = this.strip.createChild({tag: 'div', cls: 'x-dock-tab-drop-highlight'});
		this.dropMark.createChild({tag:'img', src:'pos.gif', style:'position:absolute'});
		this.dropMark.hide();
		this._calcDropPlaces();
	},
	getPosition: function(c) {
		return this.items.indexOf(c);
	},
	onStripMouseDown : function(e){
		e.preventDefault();
		if(e.button != 0){
			return;
		}
		var t = this.findTargets(e);
		if(t.close){
			if(t.item.closeAction == 'hide') {
				t.item.undock();
				t.item.win.hide();
				t.item.saveState();
			}
			else {
				this.remove(t.item);
			}
			return;
		}
		if(t.item && t.item != this.activeTab){
			this.setActiveTab(t.item);
		}
	},
	onDock: function(c, activate, position) {
		position = position != undefined ? position : this.dropPosition;
		if(c.ownerCt == this) {
			if(this.items.indexOf(c)<position) position--;
			this.remove(c, false);
		}
		if(position != undefined && position != -1 && position <= this.items.getCount())
			this.insert(position, c);
		else
			this.add(c);
		c.wrapper = null;
		if(activate)
			this.activate(c);
		this._initDD(c);
		this._calcDropPlaces();
	},
	saveItemsState: function() {
		this.items.each(function(s) {
			if(s.win) s.saveState();
		}, this);
	},
	afterUndock: function(c) {
	},
	_initDD: function(c) {
		var tabHeader = Ext.get(this.getTabEl(c));
		c.win.dd.setOuterHandleElId(tabHeader.id);
		c.docked = true;
	},
	highlightDropZone: function(dragenter, e) {
		if(dragenter || this.highlightOnDrag) {
			this.highlightProxy.setBox(this.highlightEl.getBox());
			this.highlightProxy.show();
		}
	},
	unhighlightDropZone: function(dragout) {
		if(!dragout || !this.highlightOnDrag)
			if(this.highlightProxy)
				this.highlightProxy.hide();
	},
	highlightDropPosition : function(c,x,y){
		if(x < this.stripWrap.getLeft()) {
			if(!this.overLeftScroller) {
				this.overLeftScroller = true;
				this.leftRepeater.click();
				clearTimeout(this.leftRepeater.timer);
			}
		}
		else if(x > this.stripWrap.getRight()) {
			if(!this.overRightScroller) {
				this.overRightScroller = true;
				this.rightRepeater.click();
				clearTimeout(this.rightRepeater.timer);
			}
		}
		else {
			this.overRightScroller = false;
			this.overLeftScroller = false;
			var i = 0;
			x = x - this.strip.getLeft();
			while(x>this.dropPlaces[i]) i++;
			if(this.dropPosition == i) return;
			this.dropPosition = i;

			var el = Ext.get(this.strip.dom.childNodes[i]);
			var left = el.getLeft() - (el == this.edge ? 0 : 2);

			this.dropMark.setLeft(left - this.strip.getLeft() - 6);
			this.dropMark.show();

			if(left < this.stripWrap.getLeft()) {
				this.stripWrap.scroll('left',left - this.stripWrap.getLeft(), this.animScroll);
				if(!this.animScroll){
					this.updateScrollButtons();
				}
			}
			else if(left > this.stripWrap.getRight() - 1) {
				this.stripWrap.scroll('left',left - this.stripWrap.getRight() + 1, this.animScroll);
				if(!this.animScroll){
					this.updateScrollButtons();
				}
			}
		}
	},
	unhighlightDropPosition : function(){
		this.dropMark.hide();
		this.dropPosition = -1;
	},
	onStartDrag: function(c){
		this.activate(c);
	},
	_calcDropPlaces: function() {
		var c = this.strip.query('li');
		if(c.length == 1) {
			this.dropPlaces = [{r:5000,p:3}];
			return;
		}
		this.dropPlaces = [];
		for(var i=0;i<c.length-1;i++) {
			var cb = Ext.fly(c[i]).getBox();
			if(!this.dropPlaces[i]) {
				this.dropPlaces.push(cb.x - this.strip.getLeft() + cb.width / 2);
			}
			else {
				this.dropPlaces[i] = cb.x - this.strip.getLeft() + cb.width / 2;
			}
			this.dropPlaces.push(5000);
		}
	}
});

Ext.ux.DockTabPanel.defaults = {
		ddGroup: 'dock-panels'
};

Ext.ux.DockWindow = Ext.extend(Ext.Window, {
	initDraggable : function(){
		this.dd = new Ext.ux.DockPanel.DD(this, this.ddGroup, {}); 
	}
});

Ext.ux.DockPanel = function(config) {
	Ext.apply(this, Ext.ux.DockPanel.defaults)
	Ext.apply(this, config);
};

Ext.ux.DockPanel.prototype = {
	init:function(c) {
		this.win = new Ext.ux.DockWindow({
			id: c.id + "_win",
			layout:'fit',
			plain: true,
			title: c.title,
			ddGroup: this.ddGroup,
			panel: c,
			stateful: true,
			closable: c.closable,
			closeAction: c.closeAction,
			hidemode: 'visibility',
			constrain: this.constrain == true ? this.constrain : false,
			constrainHeader: this.constrainHeader == true ? this.constrainHeader : false,
			width: this.width,
			height: this.height
		});
		this.win.render(Ext.getBody());
		this.win.on('hide', function(){this.saveState();}, c);

		c.getState = c.getState.createAfterAdvice(function(ret){
			var r = this.dockContainer ? {dockContainer: this.dockContainer, position: Ext.ComponentMgr.get(this.dockContainer).getPosition(this)} : (this.win.hidden && this.closeAction == 'hide' ? {state : 'hidden'} : {});
			if(ret) {
				ret.dockinfo = r;
			}
			return ret ? ret : {dockinfo: r};
		});
		c.applyState = c.applyState.createAfterAdvice(function(ret, state){
			if(state && state.dockinfo)
				Ext.apply(this, state.dockinfo);
		});
		
		
		c.initState();
		Ext.apply(c, this);
		
		if(c.dockContainer) {
			c.dock(c.dockContainer, false, c.position);
		}
		else if(c.state == 'hidden') {
			c.hidden = false;
			c.undock();
			c.win.hide();
		}
		else {
			c.undock();
		}
	},
	wrap: function(config) {
		var cfg = {title: this.title, closable: this.closable, border:false, items: this, layout:'fit', wrapped: this};
		if(this.closable) cfg.tools = [{id:'close',handler: function(e, target, panel){
			var w = panel.wrapped;
			if(w.closeAction == 'hide') {
				w.undock();
				w.win.hide();
			}
			else {
				var ct = panel.ownerCt;
				ct.remove(panel, true);
				ct.doLayout();
			}
		}}];
		Ext.applyIf(cfg, config);
		this.wrapper = new Ext.Panel(cfg);
		return this.wrapper;
	},
	getOwner: function() {
		if(this.wrapper) return this.wrapper.ownerCt;
		return this.ownerCt;
	},
	dock: function(id, activate, position) {
		var c = Ext.ComponentMgr.get(id);
		var oct = this.getOwner();
		c.onDock(this, activate, position);
		if(oct && oct != this.win)
			oct.afterUndock(this);
		this.win.hide();
		this.dockContainer = c.id;
		c.saveItemsState();
	},
	undock: function() {
		var oct = this.getOwner();
		this.win.add(this);
		this.wrapper = null;
		this.win.show();
		if(oct && oct != this.win)
			oct.afterUndock(this);
		this.docked = false;
		this.dockContainer = null;
		this.expand();
		this.show();
		this.saveState();
	}
};

Ext.ux.DockPanel.defaults = {
	ddGroup: 'dock-panels'
};

Ext.ux.DockPanel.DD = function(win, ddGroup, config) {
	this.panel = win.panel;
	this.win = win;
	Ext.ux.DockPanel.DD.superclass.constructor.call(this, win.el.id, win.ddGroup);
	this.setHandleElId(win.header.id);
	this.scroll = false;
	this.isTarget = false;
}

Ext.extend(Ext.ux.DockPanel.DD, Ext.dd.DD, {
	moveOnly:false,
	headerOffsets:[100, 25],
	_checkOver: function(box,x,y){
		if(x < box.x || y < box.y || x > (box.x + box.width) || y > (box.y + box.height)) return false;
		return true;
	},
	startDrag : function(x, y){
		var targets = Ext.dd.DragDropMgr.getRelated(this, true);
		for(var i=0;i<targets.length;i++) {
			targets[i].notifyStartDrag(this,x,y);
		}
		if(this.panel.docked) {
			var p = this.panel.wrapper ? this.panel.wrapper : this.panel;
			var q = p.ownerCt;
			q.dd.notifyEnter(this,x,y);
			var xy = p.ownerCt.getPanelXY(this.panel);
			this.deltaX = 30;
			this.deltaY = 12;
		}
		this.panel.docked = false;
		var w = this.win;
			this.proxy = w.ghost();
			this.alignElWithMouse(this.proxy, x, y);
		if(w.constrain !== false){
			var so = w.el.shadowOffset;
			this.constrainTo(w.container, {right: so, left: so, bottom: so});
		}else if(w.constrainHeader !== false){
			var s = this.proxy.getSize();
			this.constrainTo(w.container, {right: -(s.width-this.headerOffsets[0]), bottom: -(s.height-this.headerOffsets[1])});
		}
	},
	b4Drag : Ext.emptyFn,
	onDragEnter : function(e, id) {
		Ext.dd.DragDropMgr.getDDById(id).notifyOver(this,e.getXY()[0], e.getXY()[1]);
		if(this.panel.hideOnEnter)
			this.proxy.hide();
	},
	onDragOver: function(e, id) {
		var target = Ext.dd.DragDropMgr.getDDById(id).notifyOver(this,e.getXY()[0], e.getXY()[1]);
	},
	onDragOut : function(e, id) {
		Ext.dd.DragDropMgr.getDDById(id).notifyOut(this,e.getXY()[0], e.getXY()[1]);
		if(this.panel.hideOnEnter)
			this.proxy.show();
	},
	onDragDrop: function(e,id) {
		var target = Ext.dd.DragDropMgr.getDDById(id);
		this.panel.dock(target.ct.id, true);
		this.win.setAnimateTarget(null);
		this.win.show();
		this.win.unghost();
		this.win.initState();
		this.win.hide();
		this.panel.docked = true;
		this.proxy = null;
	},
	onDrag : function(e){
		if(this.panel.ownerCt != this.win && !this._checkOver(Ext.get(this.panel.getOwner().dd.id).getBox(),e.getXY()[0], e.getXY()[1]))
			this.panel.getOwner().dd.notifyOut(this.panel,e.getXY()[0], e.getXY()[1]);
		this.alignElWithMouse(this.proxy, e.getPageX(), e.getPageY());
	},
	endDrag : function(e){
		var targets = Ext.dd.DragDropMgr.getRelated(this, true);
		for(var i=0;i<targets.length;i++) {
			targets[i].notifyEndDrag(this,e.getXY()[0], e.getXY()[1]);
		}
		if(this.panel.dockonly && !this.panel.docked) {
			this.win.unghost();
			this.panel.docked = true;
			this.proxy = null;
			this.win.setAnimateTarget(null);
			this.win.show();
			this.win.hide(this.panel.el);
		}
		else if(!this.panel.docked) {
			this.panel.oldCt = null;
			this.panel.undock();
			this.win.unghost();
			this.win.saveState();
			this.proxy = null;
		}
	}
});