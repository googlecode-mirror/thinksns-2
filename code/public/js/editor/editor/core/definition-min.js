KISSY.Editor.add("definition",function(j){var q=true,v=false,y=j.Utils,x=document,l=KISSY,f=l.UA,r=l.DOM,n=l.Node,o=l.Event,C=j.focusManager,E=y.tryThese,F=1,k="document.open();"+(y.isCustomDomain()?'document.domain="'+x.domain+'";':"")+"document.close();",G="<div  class='ke-editor-wrap'  > <div class='"+".ke-editor-tools".substring(1)+"' ></div><div class='"+".ke-textarea-wrap".substring(1)+'\'><iframe  style="width:100%;height:100%;border:none;"  width="100%"  height="100%"  frameborder="0"  title="kissy-editor"  src="'+
(f.ie?"javascript:void(function(){"+encodeURIComponent(k)+"}())":"")+'"  tabIndex="'+(f.webkit?-1:"$(tabIndex)")+'"  allowTransparency="true" ></iframe></div><div class=\''+".ke-editor-status".substring(1)+"'></div></div>";j.SOURCE_MODE=0;j.WYSIWYG_MODE=1;j.SOURCE_MODE=j.SOURCE_MODE;j.WYSIWYG_MODE=j.WYSIWYG_MODE;l.augment(j,{init:function(b){var a=this;a.__commands={};a.__dialogs={};a.__plugins={};f.ie&&r.addClass(x.body,"ke-ie"+f.ie);if(f.trident)r.addClass(x.body,"ke-trident"+f.trident);else if(f.gecko)r.addClass(x.body,
"ke-gecko");else f.webkit&&r.addClass(x.body,"ke-webkit");var c=new n(G.replace(/\$\(tabIndex\)/,b.attr("tabIndex")));a.editorWrap=c;a._UUID=F++;C.register(a);a.wrap=c.one(".ke-textarea-wrap");a.wrap=a.wrap;a.iframe=a.wrap.one("iframe");a.iframe=a.iframe;a.toolBarDiv=c.one(".ke-editor-tools");a.toolBarDiv=a.toolBarDiv;a.textarea=b;a.textarea=a.textarea;a.statusDiv=c.one(".ke-editor-status");a.statusDiv=a.statusDiv;y.preventFocus(a.toolBarDiv);var e=b._4e_style("width"),d=b._4e_style("height");if(e){c.css("width",
e);b.css("width","100%")}a.textarea.css("display","none");c.insertAfter(b);a.wrap[0].appendChild(b[0]);if(d){a.wrap.css("height",d);b.css("height","100%")}c=a.iframe;a.on("dataReady",function(){a._ready=q;j.fire("instanceCreated",{editor:a})});f.gecko?c.on("load",a._setUpIFrame,a):a._setUpIFrame();a.cfg.attachForm&&b[0].form&&a._attachForm()},destroy:function(){if(!this.__destroyed){var b=this.editorWrap,a=this.textarea,c=this.document,e=this.iframe[0].contentWindow;this.sync();j.focusManager.remove(this);
o.remove(c);o.remove(c.documentElement);o.remove(c.body);o.remove(e);this.iframe.detach();c=this.__plugins;for(var d in c)if(c.hasOwnProperty(d)){e=c[d];e.destroy&&e.destroy()}this.fire("destroy");a.insertBefore(b);b.remove();a.css({width:b.css("width"),height:this.wrap.css("height")});a.show();this.__commands=this.__dialogs=this.__plugins=null;this.detach();this.__destroyed=true}},_attachForm:function(){var b=this,a=new n(b.textarea[0].form);a.on("submit",b.sync,b);b.on("destroy",function(){a.detach("submit",
b.sync,b)})},useDialog:function(b,a){var c=this,e=j.Overlay;e&&e.loading();c.use(b,function(){var d=c.getDialog(b);if(d){a(d);e&&e.unloading()}else l.later(arguments.callee,50,false,c)})},addDialog:function(b,a){this.__dialogs[b]=a},getDialog:function(b){return this.__dialogs[b]},destroyDialog:function(b){var a=this.__dialogs[b];a&&a.destroy();this.__dialogs[b]=null},addCommand:function(b,a){this.__commands[b]=a},hasCommand:function(b){return this.__commands[b]},execCommand:function(b){var a=this.__commands[b],
c=l.makeArray(arguments);c.shift();c.unshift(this);return a.exec.apply(a,c)},getMode:function(){return this.textarea.css("display")=="none"?j.WYSIWYG_MODE:j.SOURCE_MODE},getData:function(b){var a;a=this.getMode()==j.WYSIWYG_MODE?this.document&&this.document.body?this.document.body.innerHTML:"":this.textarea.val();if(this.htmlDataProcessor)a=b?this.htmlDataProcessor.toHtml(a,"p"):this.htmlDataProcessor.toServer(a,"p");a=l.trim(a);if(/^<p>((&nbsp;)|\s)*<\/p>$/.test(a))a="";return a},setData:function(b){var a=
b;if(this.htmlDataProcessor)a=this.htmlDataProcessor.toDataFormat(b,"p");this.document.body.innerHTML=a;this.getMode()!=j.WYSIWYG_MODE&&this.textarea.val(b)},sync:function(){this.textarea.val(this.getData())},_getRawData:function(){return this.document.body.innerHTML},_setRawData:function(b){this.document.body.innerHTML=b},_prepareIFrameHtml:function(b){var a=this.cfg,c=a.customStyle;a=a.customLink;var e="",d=j.Utils.debugUrl("../theme/editor-iframe.css");if(a)for(var g=0;g<a.length;g++)e+='<link href="'+
a[g]+'" rel="stylesheet"/>';return"<!doctype html><html><head><title>${title}</title><link href='"+d+"' rel='stylesheet'/><style>"+(c||"")+"</style>"+e+"</head><body class='ke-editor'>&nbsp;"+(b?'<script id="ke_actscript" type="text/javascript">'+(y.isCustomDomain()?'document.domain="'+x.domain+'";':"")+'window.parent.KISSY.Editor._initIFrame("'+b+'");<\/script>':"")+"</body></html>"},getSelection:function(){return j.Selection.getSelection(this.document)},focus:function(){var b=this.document,a=r._4e_getWin(b);
f.webkit&&a&&a.parent&&a.parent.focus();f.webkit&&a&&a.focus();b&&b.body.focus();this.notifySelectionChange()},blur:function(){r._4e_getWin(this.document).blur();this.document&&this.document.body.blur()},addCustomStyle:function(b){var a=this.cfg,c=this.document;a.customStyle=a.customStyle||"";a.customStyle+="\n"+b;a=c.createElement("style");c.getElementsByTagName("head")[0].appendChild(a);if(a.styleSheet)a.styleSheet.cssText=b;else a.appendChild(c.createTextNode(b))},addCustomLink:function(b){var a=
this.cfg,c=this.document;a.customLink=a.customLink||[];a.customLink.push(b);a=c.createElement("link");a.rel="stylesheet";c.getElementsByTagName("head")[0].appendChild(a);a.href=b},removeCustomLink:function(b){for(var a=this.cfg,c=l.makeArray(this.document.getElementsByTagName("link")),e=0;e<c.length;e++)c[e].href==b&&r._4e_remove(c[e]);a.customLink=a.customLink||[];a=a.customLink;b=l.indexOf(b,a);b!=-1&&a.splice(b,1)},_setUpIFrame:function(){function b(){g=d.document;a.document=g;c.detach();g.open("text/html",
"replace");g.write(e);g.close()}var a=this,c=a.iframe,e=a._prepareIFrameHtml(a._UUID),d=c[0].contentWindow,g;try{g=d.document}catch(s){c[0].src=c[0].src;if(f.ie<7){setTimeout(b,10);return}}b()},ready:function(b){this._ready?b():this.on("dataReady",b)},addPlugin:function(b,a,c){this.__plugins=this.__plugins;c=c||{};c.func=a;this.__plugins[b]=c},usePlugin:function(b){var a=this.__plugins[b];if(a)if(!(a.status&&!a.duplicate)){b=this.Env.mods[b].requires||[];for(var c=0;c<b.length;c++)this.usePlugin(b[c]);
a.func.call(a);a.status=1}},_monitor:function(){var b=this;b._monitorId&&clearTimeout(b._monitorId);b._monitorId=setTimeout(function(){var a=b.getSelection();if(a&&!a.isInvalid){var c=a.getStartElement(),e=new j.ElementPath(c);if(!b.previousPath||!b.previousPath.compare(e)){b.previousPath=e;b.fire("selectionChange",{selection:a,path:e,element:c})}}},100)},notifySelectionChange:function(){this.previousPath=null;this._monitor()},insertElement:function(b,a,c){var e=this;e.focus();setTimeout(function(){var d,
g=b._4e_name(),s=j.XHTML_DTD,m=j.RANGE,w=j.NODE,A=s.$block[g],z=e.getSelection(),h=z&&z.getRanges(),i,t,u,p;if(!h||h.length==0){var D=arguments,H=D.callee;setTimeout(function(){H.apply(e,D)},30)}else{e.fire("save");for(var B=h.length-1;B>=0;B--){i=h[B];i.deleteContents();d=!B&&b||b._4e_clone(q);a&&a(d);if(A)for(;(u=i.getCommonAncestor(v,q))&&(p=s[u._4e_name()])&&!(p&&p[g]);)if(u._4e_name()in s.span)i.splitElement(u);else if(i.checkStartOfBlock()&&i.checkEndOfBlock()){i.setStartBefore(u);i.collapse(q);
u._4e_remove()}else i.splitBlock();i.insertNode(d);t||(t=d)}if(t){g=t._4e_nextSourceNode(q);s=e.document;p=j.XHTML_DTD;if(p.$inline[d._4e_name()]){g=new n(s.createTextNode(" "));g.insertAfter(t)}else if(g){if(g._4e_name()=="br"&&p[g.parent()._4e_name()].p){p=new n("<p>&nbsp;</p>",null,s);g[0].parentNode.replaceChild(p[0],g[0]);g=p}}else{p=new n("<p>&nbsp;</p>",null,s);p.insertAfter(t);g=p}i.moveToPosition(t,m.POSITION_AFTER_END);g&&g[0].nodeType==w.NODE_ELEMENT&&i.moveToElementEditablePosition(g);
z.selectRanges([i]);e.focus();d&&d._4e_scrollIntoView();setTimeout(function(){e.fire("save")},10);c&&c(d)}}},0)},insertHtml:function(b){var a=this;if(a.htmlDataProcessor)b=a.htmlDataProcessor.toDataFormat(b);if(f.webkit){var c=r.create(b,null,a.document);c=c.nodeType==11?l.makeArray(c.childNodes):[c];for(var e=0;e<c.length;e++)a.insertElement(new n(c[e]))}else{a.focus();setTimeout(function(){var d=a.getSelection();d=d&&d.getRanges();if(!d||d.length==0){var g=arguments,s=g.callee;setTimeout(function(){s.apply(a,
g)},30)}else{a.fire("save");if(document.selection){d=document.selection;d.type=="Control"&&d.clear();d.createRange().pasteHTML(b)}else a.document.execCommand("inserthtml",v,b);setTimeout(function(){a.fire("save")},10)}},0)}}});j._initIFrame=function(b){function a(h){E(function(){d.designMode="on";setTimeout(function(){d.designMode="off";m.focus();if(!arguments.callee.retry)arguments.callee.retry=q},50)},function(){d.designMode="off";r.attr(m,"contentEditable",v);r.attr(m,"contentEditable",q);!h&&
a(1)})}var c=C.getInstance(b);b=c.textarea[0];var e=c.iframe[0].contentWindow,d=c.document,g=c.cfg,s=d.getElementById("ke_actscript");r._4e_remove(s);var m=d.body;if(f.ie){m.hideFocus=q;m.disabled=q;m.contentEditable=q;m.removeAttribute("disabled")}else setTimeout(function(){if(f.gecko||f.opera)m.contentEditable=q;else if(f.webkit)m.parentNode.contentEditable=q;else d.designMode="on"},0);if(f.webkit){o.on(d,"click",function(h){var i=new n(h.target);l.inArray(i._4e_name(),["input","select"])&&h.preventDefault()});
o.on(d,"mouseup",function(h){var i=new n(h.target);l.inArray(i._4e_name(),["input","textarea"])&&h.preventDefault()})}if(f.gecko||f.ie||f.opera){var w;w=new n(r.insertAfter((new n('<span tabindex="-1" style="position:absolute; left:-10000" role="presentation"></span>'))[0],b));w.on("focus",function(){c.focus()});c.activateGecko=function(){f.gecko&&c.iframeFocus&&w[0].focus()};c.on("destroy",function(){w.detach();w.remove()})}if(f.ie&&d.compatMode=="CSS1Compat"||d.documentMode||f.gecko||f.opera){var A=
d.documentElement;o.on(A,"mousedown",function(h){if(h.target==A){f.gecko&&a(v);w[0].focus()}})}o.on(e,"focus",function(){if(f.gecko)a(v);else f.opera&&m.focus();c.notifySelectionChange()});f.gecko&&o.on(c.document,"mousedown",function(){c.iframeFocus||a(v)});if(f.ie){o.on(d,"keydown",function(h){if(h.keyCode in{8:1,46:1}){var i=c.getSelection(),t=i.getSelectedElement();if(t){c.fire("save");var u=i.getRanges()[0].createBookmark();t._4e_remove();i.selectBookmarks([u]);c.fire("save");h.preventDefault()}}});
if(d.compatMode=="CSS1Compat"){var z={33:1,34:1};o.on(d,"keydown",function(h){h.keyCode in z&&setTimeout(function(){c.getSelection().scrollIntoView()},0)})}}setTimeout(function(){f.ie&&setTimeout(function(){if(d){m.runtimeStyle.marginBottom="0px";m.runtimeStyle.marginBottom=""}},1E3)},0);setTimeout(function(){c.fire("dataReady");var h=g.disableObjectResizing,i=g.disableInlineTableEditing;if(h||i)try{d.execCommand("enableObjectResizing",v,!h);d.execCommand("enableInlineTableEditing",v,!i)}catch(t){o.on(m,
f.ie?"resizestart":"resize",function(u){var p=new n(u.target);if(h||p._4e_name()==="table"&&i)u.preventDefault()})}},10);f.webkit&&o.on(d,"mousedown",function(h){h=new n(h.target);l.inArray(h._4e_name(),["img","hr","input","textarea","select"])&&c.getSelection().selectElement(h)});f.gecko&&o.on(d,"dragstart",function(h){var i=new n(h.target);i._4e_name()==="img"&&/ke_/.test(i[0].className)&&h.preventDefault()});C.add(c)};document.documentMode>7&&function(){for(var b=l.all("link"),a=0;a<b.length;a++){var c=
new n(b[a]),e=c.attr("href");e.indexOf("editor-pkg-min-mhtml.css")!=-1&&c.attr("href",e.replace(/editor-pkg-min-mhtml\.css/g,"editor-pkg-min-datauri.css"))}}();k=j.prototype;y.extern(k,{setData:k.setData,getData:k.getData,insertElement:k.insertElement,insertHtml:k.insertHtml,ready:k.ready,addCustomStyle:k.addCustomStyle,addCommand:k.addCommand,hasCommand:k.hasCommand,execCommand:k.execCommand,useDialog:k.useDialog,addDialog:k.addDialog,getDialog:k.getDialog,getMode:k.getMode,sync:k.sync,getSelection:k.getSelection,
focus:k.focus,blur:k.blur,notifySelectionChange:k.notifySelectionChange})});
