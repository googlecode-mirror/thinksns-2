KISSY.add("ext-align",function(b){function e(){b.log("align init");this.on("bindUI",this._bindUIAlign,this);this.on("renderUI",this._renderUIAlign,this);this.on("syncUI",this._syncUIAlign,this)}function l(a,c){var g=c.charAt(0),f=c.charAt(1),d,i,j,k;if(a){a=new m(a);d=a.offset();i=a[0].offsetWidth;j=a[0].offsetHeight}else{d={left:h.scrollLeft(),top:h.scrollTop()};i=h.viewportWidth();j=h.viewportHeight()}k=d.left;d=d.top;if(g==="c")d+=j/2;else if(g==="b")d+=j;if(f==="c")k+=i/2;else if(f==="r")k+=i;
return{left:k,top:d}}b.namespace("Ext");var h=b.DOM,m=b.Node;b.mix(e,{TL:"tl",TC:"tc",TR:"tr",CL:"cl",CC:"cc",CR:"cr",BL:"bl",BC:"bc",BR:"br"});e.ATTRS={align:{}};e.prototype={_bindUIAlign:function(){b.log("_bindUIAlign")},_renderUIAlign:function(){b.log("_renderUIAlign")},_syncUIAlign:function(){b.log("_syncUIAlign")},_uiSetAlign:function(a){b.log("_uiSetAlign");b.isPlainObject(a)&&this.align(a.node,a.points,a.offset)},align:function(a,c,g){var f,d=this.get("el");f=h.offset(d);a=l(a,c[0]);c=l(d,
c[1]);c=[c.left-a.left,c.top-a.top];this.set("xy",[f.left-c[0]+ +g[0],f.top-c[1]+ +g[1]])},center:function(a){this.set("align",{node:a,points:[e.CC,e.CC],offset:[0,0]})},__destructor:function(){b.log("align __destructor")}};b.Ext.Align=e});