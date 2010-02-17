var inlineEdit = new Class({
	getOptions: function(){
		return {
			onComplete: function(el,oldContent,newContent){
			},
			type: 'input'
		};
	},
	initialize: function(element,options){
		this.setOptions(this.getOptions(), options);
		if(!element.innerHTML.toLowerCase().match('<'+this.options.type)){
			this.editting = element;
			this.oldContent = element.innerHTML;
			var content = this.oldContent.trim().replace(new RegExp("<br>", "gi"), "\n");
			this.inputBox = new Element(this.options.type).setProperty('value',content).setStyles('margin:0;background:transparent;width:99.5%;font-size:100%;border:0;');
			if(!this.inputBox.value){this.inputBox.setHTML(content)}
			this.editting.setHTML('');
			this.inputBox.injectInside(this.editting);
			(function(){this.inputBox.focus()}.bind(this)).delay(300);
			this.inputBox.addEvent('change',this.onSave.bind(this));
			this.inputBox.addEvent('blur',this.onSave.bind(this));
			this.inputBox.addEvent('keyup',this.onKeyUp.bindWithEvent(this));
			this.fireEvent('onStart', [this.editting]);
		}
	},
	onKeyUp: function(e){
        if("enter" == e.key)
        {
            this.onSave();
        }
    },
    onSave: function(){
		this.inputBox.removeEvents();
		this.newContent = this.inputBox.value.trim().replace(new RegExp("\n", "gi"), "<br>");
		this.editting.setHTML(this.newContent);
		this.fireEvent('onComplete', [this.editting,this.oldContent,this.newContent]);
	}
});

Element.extend({
	inlineEdit: function(options) {
		return new inlineEdit(this, options);
	}
});

inlineEdit.implement(new Events);
inlineEdit.implement(new Options);
