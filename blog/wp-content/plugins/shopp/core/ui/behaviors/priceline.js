function addPriceLine(a,w,am,u){var M=jQuery.noConflict();var aj=pricingidx;var m=M('<div id="row-'+aj+'" class="priceline" />');if(u=="after"){m.insertAfter(a)}else{if(u=="before"){m.insertBefore(a)}else{m.appendTo(a)}}var U=M('<div class="pricing-label" />').appendTo(m);var ad=M('<label for="label['+aj+']" />').appendTo(U);var ac=M('<input type="hidden" name="price['+aj+'][label]" id="label['+aj+']" />').appendTo(U);ac.change(function(){ad.text(M(this).val())});var r=M('<input type="hidden" name="price['+aj+'][id]" id="priceid-'+aj+'" />').appendTo(U);var p=M('<input type="hidden" name="price['+aj+'][product]" id="product['+aj+']" />').appendTo(U);var an=M('<input type="hidden" name="price['+aj+'][context]" />').appendTo(U);var Q=M('<input type="hidden" name="price['+aj+'][optionkey]" class="optionkey" />').appendTo(U);var k=M('<input type="hidden" name="price['+aj+'][options]" />').appendTo(U);var A=M('<input type="hidden" name="sortorder[]" value="'+aj+'" />').appendTo(U);var Z="";M(priceTypes).each(function(i,at){Z+='<option value="'+at.value+'">'+at.label+"</option>"});var aq=M('<select name="price['+aj+'][type]" id="type-'+aj+'"></select>').html(Z).appendTo(U);var ab=M('<div class="pricing-ui clear" />').appendTo(m);var al=M("<table/>").addClass("pricing-table").appendTo(ab);var N=M("<tr/>").appendTo(al);var x=M("<tr/>").appendTo(al);var R=M("<th/>").appendTo(N);var ar=M('<label for="price['+aj+']">'+PRICE_LABEL+"</label>").appendTo(R);var aa=M("<td/>").appendTo(x);var L=M('<input type="text" name="price['+aj+'][price]" id="price['+aj+']" value="0" size="10" class="selectall right"  />').appendTo(aa);M("<br />").appendTo(aa);M('<input type="hidden" name="price['+aj+'][tax]" value="on" />').appendTo(aa);var O=M('<input type="checkbox" name="price['+aj+'][tax]" id="tax['+aj+']" value="off" />').appendTo(aa);var ae=M('<label for="tax['+aj+']"> '+NOTAX_LABEL+"</label><br />").appendTo(aa);var c=M('<th><label for="sale['+aj+']"> '+SALE_PRICE_LABEL+"</label></th>").appendTo(N);var B=M('<input type="checkbox" name="price['+aj+'][sale]" id="sale['+aj+']" />').prependTo(c);M('<input type="hidden" name="price['+aj+'][sale]" value="off" />').prependTo(c);var X=M("<td/>").appendTo(x);var G=M("<span>"+NOT_ON_SALE_TEXT+"</span>").addClass("status").appendTo(X);var y=M("<span/>").addClass("fields").appendTo(X).hide();var F=M('<input type="text" name="price['+aj+'][saleprice]" id="saleprice['+aj+']" size="10" class="selectall right" />').appendTo(y);var C=M("<th/>").appendTo(N);var v=M('<td width="80%" />').appendTo(x);M('<input type="hidden" name="price['+aj+'][donation][var]" value="off" />').appendTo(v);var K=M('<input type="checkbox" name="price['+aj+'][donation][var]" id="donation-var['+aj+']" value="on" />').appendTo(v);M('<label for="donation-var['+aj+']"> '+DONATIONS_VAR_LABEL+"</label><br />").appendTo(v);M('<input type="hidden" name="price['+aj+'][donation][min]" value="off" />').appendTo(v);var b=M('<input type="checkbox" name="price['+aj+'][donation][min]" id="donation-min['+aj+']" value="on" />').appendTo(v);M('<label for="donation-min['+aj+']"> '+DONATIONS_MIN_LABEL+"</label>").appendTo(v);var t=M('<th><label for="shipping-'+aj+'"> '+SHIPPING_LABEL+"</label></th>").appendTo(N);var f=M('<input type="checkbox" name="price['+aj+'][shipping]" id="shipping-'+aj+'" />').prependTo(t);M('<input type="hidden" name="price['+aj+'][shipping]" value="off" />').prependTo(t);var ak=M("<td/>").appendTo(x);var g=M("<span>"+FREE_SHIPPING_TEXT+"</span>").addClass("status").appendTo(ak);var J=M("<span/>").addClass("fields").appendTo(ak).hide();var W=M('<input type="text" name="price['+aj+'][weight]" id="weight['+aj+']" size="8" class="selectall right" />').appendTo(J);var s=M('<label for="weight['+aj+']" title="Weight"> '+WEIGHT_LABEL+((weightUnit)?" ("+weightUnit+")":"")+"</label><br />").appendTo(J);var e=M('<input type="text" name="price['+aj+'][shipfee]" id="shipfee['+aj+']" size="8" class="selectall right" />').appendTo(J);var D=M('<label for="shipfee['+aj+']" title="Additional shipping fee calculated per quantity ordered (for handling costs, etc)"> '+SHIPFEE_LABEL+"</label><br />").appendTo(J);var S=M('<th><label for="inventory['+aj+']"> '+INVENTORY_LABEL+"</label></th>").appendTo(N);var j=M('<input type="checkbox" name="price['+aj+'][inventory]" id="inventory['+aj+']" />').prependTo(S);M('<input type="hidden" name="price['+aj+'][inventory]" value="off" />').prependTo(c);var T=M("<td/>").appendTo(x);var l=M("<span>"+NOT_TRACKED_TEXT+"</span>").addClass("status").appendTo(T);var ap=M("<span/>").addClass("fields").appendTo(T).hide();var ag=M('<input type="text" name="price['+aj+'][stock]" id="stock['+aj+']" size="8" class="selectall right" />').appendTo(ap);var I=M('<label for="stock['+aj+']"> '+IN_STOCK_LABEL+"</label>").appendTo(ap);var P=M("<br/>").appendTo(ap);var z=M('<input type="text" name="price['+aj+'][sku]" id="sku['+aj+']" size="8" title="Enter a unique tracking number for this product option." class="selectall" />').appendTo(ap);var h=M('<label for="sku['+aj+']" title="'+SKU_LABEL_HELP+'"> '+SKU_LABEL+"</label>").appendTo(ap);var af=M('<th><label for="download['+aj+']">Product Download</label></th>').appendTo(N);var V=M('<td width="31%" />').appendTo(x);var H=M("<div></div>").html("No product download.").appendTo(V);var o=M('<td rowspan="2" class="controls" width="75" />').appendTo(N);if(storage=="fs"){var E=M("<div></div>").prependTo(V).hide();var ao=M('<input type="text" name="price['+aj+'][downloadpath]" value="" title="Enter file path relative to: '+productspath+'" class="filepath" />').appendTo(E).change(function(){M(this).removeClass("warning").addClass("verifying");M.ajax({url:fileverify_url+"&action=wp_ajax_shopp_verify_file",type:"POST",data:"filepath="+M(this).val(),timeout:10000,dataType:"text",success:function(i){ao.removeClass("verifying");if(i=="OK"){return}if(i=="NULL"){ao.addClass("warning").attr("title",FILE_NOT_FOUND_TEXT)}if(i=="ISDIR"){ao.addClass("warning").attr("title",FILE_ISDIR_TEXT)}if(i=="READ"){ao.addClass("warning").attr("title",FILE_NOT_READ_TEXT)}}})});var q=M('<button type="button" class="button-secondary"><small>By File Path</small></button>').appendTo(o).click(function(){E.slideToggle()})}var ai=M('<div id="flash-product-uploader-'+aj+'"></div>').appendTo(o);var n=M('<button type="button" class="button-secondary"><small>'+UPLOAD_FILE_BUTTON_TEXT+"</small></button>").appendTo(o);var ah=new FileUploader(M(ai).attr("id"),n,aj,H);var Y=new Object();Y.id=pricingidx;Y.options=w;Y.data=am;Y.row=m;Y.rowid=0;Y.label=ac;Y.links=new Array();Y.inputs=new Array(aq,L,O,B,F,K,b,f,W,e,j,ag,z);Y.disable=function(){aq.val("N/A").trigger("change.value")};Y.updateKey=function(){Q.val(xorkey(this.options))};Y.updateLabel=function(){var i="";var at="";if(this.options){M(this.options).each(function(au,av){if(i==""){i=M(productOptions[av]).val()}else{i+=", "+M(productOptions[av]).val()}if(at==""){at=av}else{at+=","+av}})}if(i==""){i=DEFAULT_PRICELINE_LABEL}this.label.val(htmlentities(i)).change();k.val(at)};Y.updateTabindex=function(i){M.each(this.inputs,function(au,at){M(at).attr("tabindex",((i+1)*100)+au)})};Y.linkInputs=function(i){Y.links.push(i);M.each(Y.inputs,function(au,at){if(!at){return}var av="change.linkedinputs";if(M(at).attr("type")=="checkbox"){av="click.linkedinputs"}M(at).bind(av,function(){var ax=M(this).val();var aw=M(this).attr("checked");M.each(Y.links,function(ay,az){M.each(linkedPricing[az],function(aB,aA){if(aA==xorkey(Y.options)){return}if(!pricingOptions[aA]){return}if(M(at).attr("type")=="checkbox"){M(pricingOptions[aA].inputs[au]).attr("checked",aw)}else{M(pricingOptions[aA].inputs[au]).val(ax)}M(pricingOptions[aA].inputs[au]).trigger("change.value")})})})})};Y.unlinkInputs=function(i){if(i!==false){index=M.inArray(i,Y.links);Y.links.splice(index,1)}M.each(Y.inputs,function(au,at){if(!at){return}var av="blur.linkedinputs";if(M(at).attr("type")=="checkbox"){av="click.linkedinputs"}M(at).unbind(av)})};Y.updateKey();Y.updateLabel();var d=new Object();d.All=new Array(R,aa,c,X,t,ak,S,T,af,V,o,C,v);if(pricesPayload){d.Shipped=new Array(R,aa,c,X,t,ak,S,T);d.Download=new Array(R,aa,c,X,af,V,o);d.Virtual=new Array(R,aa,c,X,S,T)}else{d.Shipped=new Array(R,aa,t,ak);d.Virtual=new Array(R,aa);d.Download=new Array(R,aa)}d.Donation=new Array(R,aa,C,v);aq.bind("change.value",function(){var i=aq.val();M.each(d.All,function(){M(this).hide()});ar.html(PRICE_LABEL);if(d[i]){M.each(d[i],function(){M(this).show()})}if(aq.val()=="Donation"){ar.html(AMOUNT_LABEL);O.attr("checked","true").trigger("change.value")}});B.bind("change.value",function(){if(this.checked){G.hide();y.show()}else{G.show();y.hide()}if(M.browser.msie){M(this).blur()}}).click(function(){if(M.browser.msie){M(this).trigger("change.value")}if(this.checked){F.focus().select()}});f.bind("change.value",function(){if(this.checked){g.hide();J.show()}else{g.show();J.hide()}if(M.browser.msie){M(this).blur()}}).click(function(){if(M.browser.msie){M(this).trigger("change.value")}if(this.checked){W.focus().select()}});j.bind("change.value",function(){if(this.checked){l.hide();ap.show()}else{l.show();ap.hide()}if(M.browser.msie){M(this).blur()}}).click(function(){if(M.browser.msie){M(this).trigger("change.value")}if(this.checked){ag.focus().select()}});L.bind("change.value",function(){this.value=asMoney(this.value)}).trigger("change.value");F.bind("change.value",function(){this.value=asMoney(this.value)}).trigger("change.value");e.bind("change.value",function(){this.value=asMoney(this.value)}).trigger("change.value");W.bind("change.value",function(){var i=new Number(this.value);this.value=i.roundFixed(3)}).trigger("change.value");if(am&&am.context){an.val(am.context)}else{an.val("product")}if(am&&am.label){ac.val(htmlentities(am.label)).change();aq.val(am.type);r.val(am.id);p.val(am.product);z.val(am.sku);L.val(asMoney(am.price));if(am.sale=="on"){B.attr("checked","true").trigger("change.value")}if(am.shipping=="on"){f.attr("checked","true").trigger("change.value")}if(am.inventory=="on"){j.attr("checked","true").trigger("change.value")}if(am.donation){if(am.donation["var"]=="on"){K.attr("checked",true)}if(am.donation.min=="on"){b.attr("checked",true)}}F.val(asMoney(am.saleprice));e.val(asMoney(am.shipfee));W.val(am.weight).trigger("change.value");ag.val(am.stock);if(am.download){if(am.filedata.mimetype){am.filedata.mimetype=am.filedata.mimetype.replace(/\//gi," ")}H.attr("class","file "+am.filedata.mimetype).html(am.filename+"<br /><small>"+readableFileSize(am.filesize)+"</small>").click(function(){window.location.href=adminurl+"admin.php?page=shopp-lookup&download="+am.download})}if(am.tax=="off"){O.attr("checked","true")}}else{if(aq.val()=="Shipped"){f.attr("checked","true").trigger("change.value")}}quickSelects(m);aq.change();if(w){pricingOptions[xorkey(w)]=Y}M("#prices").val(pricingidx++);return m};