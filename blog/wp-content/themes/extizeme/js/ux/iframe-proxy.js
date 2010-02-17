Ext.data.IFrameProxy = Ext.extend(Ext.data.DataProxy, {
    constructor: function(config) {
        Ext.data.IFrameProxy.superclass.constructor.call(this);
        Ext.applyIf(this, config);
        Ext.applyIf(this, {
            MIFP:  new Ext.ux.ManagedIframePanel({
                cls:'x-hidden',
                renderTo:Ext.getBody()
            })
        });
    },
    load : function(params, reader, callback, scope, arg){
        var separator = this.url.indexOf('?') === -1 ? '?' : '&';
        this.MIFP.setSrc(this.url + separator + Ext.urlEncode(params));
        var result;
        this.MIFP.on('documentloaded', function () {
            try {
                result = reader.readRecords( this.MIFP.getFrameBody() );
            }catch(e){
                this.fireEvent("loadexception", this, arg, null, e);
                callback.call(scope, null, arg, false);
                return;
            }
            this.fireEvent("load", this, result);
            callback.call(scope, result, arg, true);
        }, this);
    }
});
