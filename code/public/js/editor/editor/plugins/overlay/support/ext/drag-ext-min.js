KISSY.add("ext-drag",function(b){function c(){b.log("drag init");this.on("bindUI",this._bindUIDragExt,this);this.on("renderUI",this._renderUIDragExt,this);this.on("syncUIUI",this._syncUIDragExt,this)}b.namespace("Ext");c.ATTRS={handlers:{value:[]},draggable:{value:true}};c.prototype={_uiSetHandlers:function(a){b.log("_uiSetHanlders");a&&a.length>0&&this.__drag.set("handlers",a)},_syncUIDragExt:function(){b.log("_syncUIDragExt")},_renderUIDragExt:function(){b.log("_renderUIDragExt")},_bindUIDragExt:function(){b.log("_bindUIDragExt");
var a=this.get("el");this.__drag=new b.Draggable({node:a,handlers:this.get("handlers")})},_uiSetDraggable:function(a){b.log("_uiSetDraggable");var d=this.__drag;if(a){d.detach("drag");d.on("drag",this._dragExtAction,this)}else d.detach("drag")},_dragExtAction:function(a){this.set("xy",[a.left,a.top])},__destructor:function(){b.log("DragExt __destructor");var a=this.__drag;a&&a.destroy()}};b.Ext.Drag=c});
