(function($){if(FrontEndEditor._loaded){return}FrontEndEditor._loaded=true;(function(){var initializing=false,fnTest=/xyz/.test(function(){xyz})?/\b_super\b/:/.*/;this.Class=function(){};Class.extend=function(prop){var _super=this.prototype;initializing=true;var prototype=new this();initializing=false;for(var name in prop){prototype[name]=(typeof prop[name]=="function"&&typeof _super[name]=="function"&&fnTest.test(prop[name]))?(function(name,fn){return function(){var tmp=this._super;this._super=_super[name];var ret=fn.apply(this,arguments);this._super=tmp;return ret}})(name,prop[name]):prop[name]}function Class(){if(!initializing&&this.init){this.init.apply(this,arguments)}}Class.prototype=prototype;Class.constructor=Class;Class.extend=arguments.callee;return Class}})();var spinner=$("<img>").attr({src:FrontEndEditor.data.spinner,"class":"front-editor-spinner"});var is_overlay=function($el){var attr=[$el.attr("id"),$el.attr("class"),$el.attr("rel")];var tokens=["lightbox","thickbox","shutter","awppost_link"];for(var i in tokens){for(var j in attr){if(attr[j].indexOf(tokens[i])!=-1){return true}}}return false};var resume=function(){if(FrontEndEditor._trap){return}var $link=FrontEndEditor._to_click;if(typeof $link=="undefined"){return}if(typeof $link.attr("href")!="undefined"&&$link.attr("href")!="#"){if($link.attr("target")=="_blank"){window.open($link.attr("href"))}else{window.location.href=$link.attr("href")}}delete FrontEndEditor._to_click};var fieldTypes={};fieldTypes.base=Class.extend({init:function($el,type,name,id){var self=this;self.set_el($el);self.type=type;self.name=name;self.id=id;self.bind(self.el,"click",self.click);self.bind(self.el,"dblclick",self.dblclick)},set_el:function($el){var self=this;self.el=$el;var $parent=self.el.parents("a");if(!$parent.length){return}var $link=$parent.clone(true).html(self.el.html());var $wrap=self.el.clone(true).html($link);$parent.replaceWith($wrap);self.el=$wrap;self.switched=true},click:function(ev){var $el=$(ev.target).closest("a");if(!$el.length){return}if(is_overlay($el)){return}ev.stopImmediatePropagation();ev.preventDefault();FrontEndEditor._to_click=$el;setTimeout(resume,300)},dblclick:function(ev){var self=this;ev.stopPropagation();ev.preventDefault();FrontEndEditor._trap=true},get_content:null,set_content:null,ajax_get_handler:null,ajax_set_handler:null,ajax_get:function(){var self=this;var data={nonce:FrontEndEditor.data.nonce,action:"front-editor",callback:"get",name:self.name,type:self.type,item_id:self.id};$.post(FrontEndEditor.data.ajax_url,data,function(response){self.ajax_get_handler(response)})},ajax_set:function(content){var self=this;content=content||self.get_content();var data={nonce:FrontEndEditor.data.nonce,action:"front-editor",callback:"save",name:self.name,type:self.type,item_id:self.id,content:content};$.post(FrontEndEditor.data.ajax_url,data,function(response){self.ajax_set_handler(response)})},bind:function(element,event,callback){var self=this;element.bind(event,function(ev){callback.call(self,ev)})}});fieldTypes.image=fieldTypes.base.extend({dblclick:function(ev){var self=this;self._super(ev);self.open_box()},open_box:function(){var self=this;tb_show(FrontEndEditor.data.image.change,FrontEndEditor.data.admin_url+"/media-upload.php?post_id=0&type=image&TB_iframe=true&width=640&editable_image=1");var $revert=$('<a id="fee-img-revert" href="#">').text(FrontEndEditor.data.image.revert);$revert.click(function(ev){self.ajax_set(-1)});$("#TB_ajaxWindowTitle").after($revert);$("#TB_closeWindowButton img").attr("src",FrontEndEditor.data.image.tb_close);self.bind($("#TB_iframeContent"),"load",self.replace_button)},replace_button:function(ev){var self=this;var $frame=$(ev.target).contents();$(".media-item",$frame).livequery(function(){var $item=$(this);var $button=$('<a href="#" class="button">').text(FrontEndEditor.data.image.change);$button.click(function(ev){self.ajax_set(self.get_content($item))});$(this).find(":submit, #go_button").replaceWith($button)})},get_content:function($item){var $field;$field=$item.find(".urlfile");if($field.length){return $field.attr("title")}$field=$item.find("#embed-src");if($field.length){return $field.val()}$field=$item.find("#src");if($field.length){return $field.val()}return false},ajax_set_handler:function(url){var self=this;if(url==-1){window.location.reload(true)}else{self.el.find("img").attr("src",url);tb_remove()}}});fieldTypes.thumbnail=fieldTypes.image.extend({replace_button:function(ev){var self=this;var $frame=$(ev.target).contents();$frame.find("#tab-type_url").remove();self._super(ev)},get_content:function($item){return $item.attr("id").replace("media-item-","")}});fieldTypes.input=fieldTypes.base.extend({init:function($el,type,name,id){var self=this;self.spinner=spinner.clone();self._super($el,type,name,id)},input_tag:'<input type="text">',create_input:function(){var self=this;self.input=$(self.input_tag);self.input.attr({id:"edit_"+self.el.attr("id"),"class":"fee-form-content"}).prependTo(self.form)},set_input:function(content){var self=this;self.input.val(content)},get_content:function(){var self=this;return self.input.val()},set_content:function(content){var self=this;if(self.switched){self.el.find("a").html(content)}else{self.el.html(content)}},ajax_get:function(){var self=this;self.el.hide().after(self.spinner.show());self.create_input();self._super()},ajax_set:function(){var self=this;self.el.before(self.spinner.show());self._super()},ajax_get_handler:function(content){var self=this;self.spinner.hide().replaceWith(self.form);self.set_input(content);self.input.focus()},ajax_set_handler:function(content){var self=this;self.set_content(content);self.spinner.hide();self.el.show()},dblclick:function(ev){var self=this;self._super(ev);self.form_handler()},form_handler:function(){var self=this;var form_remove=function(with_spinner){FrontEndEditor._trap=false;self.form.remove();if(with_spinner===true){self.el.before(self.spinner.show())}else{self.el.show()}self.el.trigger("fee_remove_form")};var form_submit=function(){self.ajax_set();form_remove(true)};self.save_button=$("<button>").addClass("fee-form-save").text(FrontEndEditor.data.save_text).click(form_submit);self.cancel_button=$("<button>").addClass("fee-form-cancel").text(FrontEndEditor.data.cancel_text).click(form_remove);var inline=self.type=="input"||self.type=="terminput";self.form=inline?$("<span>"):$("<div>");self.form.addClass("fee-form").addClass("fee-type-"+self.type).addClass("fee-filter-"+self.name).append(self.save_button).append(self.cancel_button);self.bind(self.form,"keypress",self.keypress);self.ajax_get()},keypress:function(ev){var self=this;var keys={ENTER:13,ESCAPE:27};var code=(ev.keyCode||ev.which||ev.charCode||0);if(code==keys.ENTER&&self.type=="input"){self.save_button.click()}if(code==keys.ESCAPE){self.cancel_button.click()}}});fieldTypes.terminput=fieldTypes.input.extend({set_input:function(content){var self=this;self._super(content);self.input.suggest(FrontEndEditor.data.ajax_url+"?action=ajax-tag-search&tax="+self.id.split("#")[1],{multiple:true,resultsClass:"fee-suggest-results",selectClass:"fee-suggest-over",matchClass:"fee-suggest-match"})}});fieldTypes.textarea=fieldTypes.input.extend({input_tag:'<textarea rows="10">'});fieldTypes.rich=fieldTypes.textarea.extend({set_input:function(content){var self=this;self._super(content);self.editor=new nicEditor(FrontEndEditor.data.nicedit).panelInstance(self.input.attr("id"));self.form.find(".nicEdit-main").focus()},get_content:function(){var self=this;return self.pre_wpautop(self.input.val())},pre_wpautop:function(content){var blocklist1,blocklist2;content=content.replace(/<(pre|script)[^>]*>[\s\S]+?<\/\1>/g,function(a){a=a.replace(/<br ?\/?>[\r\n]*/g,"<wp_temp>");return a.replace(/<\/?p( [^>]*)?>[\r\n]*/g,"<wp_temp>")});blocklist1="blockquote|ul|ol|li|table|thead|tbody|tfoot|tr|th|td|div|h[1-6]|p|fieldset";content=content.replace(new RegExp("\\s*</("+blocklist1+")>\\s*","g"),"</$1>\n");content=content.replace(new RegExp("\\s*<(("+blocklist1+")[^>]*)>","g"),"\n<$1>");content=content.replace(/(<p [^>]+>.*?)<\/p>/g,"$1</p#>");content=content.replace(/<div([^>]*)>\s*<p>/gi,"<div$1>\n\n");content=content.replace(/\s*<p>/gi,"");content=content.replace(/\s*<\/p>\s*/gi,"\n\n");content=content.replace(/\n[\s\u00a0]+\n/g,"\n\n");content=content.replace(/\s*<br ?\/?>\s*/gi,"\n");content=content.replace(/\s*<div/g,"\n<div");content=content.replace(/<\/div>\s*/g,"</div>\n");content=content.replace(/\s*\[caption([^\[]+)\[\/caption\]\s*/gi,"\n\n[caption$1[/caption]\n\n");content=content.replace(/caption\]\n\n+\[caption/g,"caption]\n\n[caption");blocklist2="blockquote|ul|ol|li|table|thead|tbody|tfoot|tr|th|td|h[1-6]|pre|fieldset";content=content.replace(new RegExp("\\s*<(("+blocklist2+") ?[^>]*)\\s*>","g"),"\n<$1>");content=content.replace(new RegExp("\\s*</("+blocklist2+")>\\s*","g"),"</$1>\n");content=content.replace(/<li([^>]*)>/g,"\t<li$1>");if(content.indexOf("<object")!=-1){content=content.replace(/<object[\s\S]+?<\/object>/g,function(a){return a.replace(/[\r\n]+/g,"")})}content=content.replace(/<\/p#>/g,"</p>\n");content=content.replace(/\s*(<p [^>]+>[\s\S]*?<\/p>)/g,"\n$1");content=content.replace(/^\s+/,"");content=content.replace(/[\s\u00a0]+$/,"");content=content.replace(/<wp_temp>/g,"\n");return content},ajax_set:function(){var self=this;self.editor.nicInstances[0].saveContent();self._super()}});FrontEndEditor.fieldTypes=fieldTypes;$(document).ready(function($){$(".fee-filter-widget_title, .fee-filter-widget_text").each(function(){var $el=$(this);var id=$el.parents(".widget").attr("id");if(id){$el.attr("data-fee",id)}else{$el.replaceWith($el.html())}});$.each(FrontEndEditor.data.fields,function(name,type){$(".fee-filter-"+name).each(function(){var $el=$(this);var id=$el.attr("data-fee");var parts=id.split("#");switch(name){case"post_meta":type=parts[2];break;case"editable_option":type=parts[1];break}new fieldTypes[type]($el,type,name,id)})});if(FrontEndEditor.data.tooltip){$.fn.qtip.styles.fee={height:10,paddingTop:"4px",paddingRight:"5px",paddingBottom:"6px",paddingLeft:"25px",background:"#bbbebf url("+FrontEndEditor.data.tooltip.icon+") top left no-repeat",color:"#ffffff",textAlign:"left",lineHeight:"100%",fontFamily:"sans-serif",fontSize:"14px",opacity:"0.75",border:{width:0,radius:5,color:"#bbbebf"},tip:"bottomLeft",name:"dark"};$(".fee-field").qtip({content:FrontEndEditor.data.tooltip.text,position:{corner:{target:"topMiddle"},adjust:{x:0,y:-40}},show:{effect:"fade"},style:{name:"fee"}})}})})(jQuery);