KISSY.Editor.add("link",function(h){h.addPlugin("link",function(){function k(a){return a._4e_ascendant(function(c){return c._4e_name()==="a"&&!!c.attr("href")},true)}var l=KISSY,f=l.Editor,p=l.Node,m=f.Style,n={element:"a",attributes:{href:"#(href)",title:"#(title)",_ke_saved_href:"#(_ke_saved_href)",target:"#(target)"}},i={},j=f.Utils.addRes,q=f.Utils.destroyRes,o=h.addButton("link",{contentCls:"ke-toolbar-link",title:"\u63d2\u5165\u94fe\u63a5",mode:f.WYSIWYG_MODE,_getSelectedLink:function(){var a=this.editor.getSelection();
if(a=a&&a.getStartElement())a=k(a);return a},_getSelectionLinkUrl:function(){var a=this.cfg._getSelectedLink.call(this);if(a)return a.attr("_ke_saved_href")||a.attr("href")},_removeLink:function(a){var c=this.editor;c.fire("save");var d=c.getSelection(),b=d.getRanges()[0];if(b&&b.collapsed){b=d.createBookmarks();a._4e_remove(true);d.selectBookmarks(b)}else if(b){a=a[0];d=a.attributes;b={};for(var g=0;g<d.length;g++){var e=d[g];if(e.specified)b[e.name]=e.value}if(a.style.cssText)b.style=a.style.cssText;
(new m(n,b)).remove(c.document)}c.fire("save");c.notifySelectionChange()},_link:function(a,c){var d=this.editor;a._ke_saved_href=a.href;if(c){d.fire("save");c.attr(a);d.fire("save")}else{var b=d.getSelection();b=b&&b.getRanges()[0];if(!b||b.collapsed){b=new p("<a>"+a.href+"</a>",a,d.document);d.insertElement(b)}else{d.fire("save");(new m(n,a)).apply(d.document);d.fire("save")}}d.notifySelectionChange()},offClick:function(){var a=this;a.editor.useDialog("link/dialog",function(c){c.show(a)})},destroy:function(){this.editor.destroyDialog("link/dialog")}});
j.call(i,o);f.use("bubbleview",function(){f.BubbleView.register({pluginName:"link",editor:h,pluginContext:o,func:k,init:function(){var a=this,c=a.get("contentEl");c.html('\u524d\u5f80\u94fe\u63a5\uff1a  <a href=""  target="_blank" class="ke-bubbleview-url"></a> -  <span class="ke-bubbleview-link ke-bubbleview-change">\u7f16\u8f91</span> -  <span class="ke-bubbleview-link ke-bubbleview-remove">\u53bb\u9664</span>');var d=c.one(".ke-bubbleview-url"),b=c.one(".ke-bubbleview-change"),g=c.one(".ke-bubbleview-remove");f.Utils.preventFocus(c);b.on("click",
function(e){a._plugin.call("offClick");e.halt()});g.on("click",function(e){a._plugin.call("_removeLink",a._selectedEl);e.halt()});j.call(a,b,g);a.on("show",function(){var e=a._selectedEl;if(e){e=e.attr("_ke_saved_href")||e.attr("href");d.html(e);d.attr("href",e)}})}});j.call(i,function(){f.BubbleView.destroy("link")})});this.destroy=function(){q.call(i)}})},{attach:false});
