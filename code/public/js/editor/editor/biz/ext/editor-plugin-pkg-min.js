KISSY.Editor.add("xiami-music",function(e){var c=KISSY.Editor,d=e.htmlDataProcessor,j=d&&d.dataFilter;j&&j.addRules({elements:{object:function(f){var h=f.attributes,g=f.attributes.title,b;if(!(h.classid&&String(h.classid).toLowerCase())){for(h=0;h<f.children.length;h++){b=f.children[h];if(b.name=="embed"){if(!c.Utils.isFlashEmbed(b))break;if(/xiami\.com/i.test(b.attributes.src))return d.createFakeParserElement(f,"ke_xiami","xiami-music",true,{title:g})}}return null}for(h=0;h<f.children.length;h++){b=
f.children[h];if(b.name=="param"&&b.attributes.name=="movie")if(/xiami\.com/i.test(b.attributes.value))return d.createFakeParserElement(f,"ke_xiami","xiami-music",true,{title:g})}},embed:function(f){if(!c.Utils.isFlashEmbed(f))return null;if(/xiami\.com/i.test(f.attributes.src))return d.createFakeParserElement(f,"ke_xiami","xiami-music",true,{title:f.attributes.title})}}},4);e.addPlugin("xiami-music",function(){var f=e.addButton("xiami-music",{contentCls:"ke-toolbar-music",title:"\u63d2\u5165\u867e\u7c73\u97f3\u4e50",mode:c.WYSIWYG_MODE,
loading:true});c.use("xiami-music/support",function(){var h=new c.XiamiMusic(e);f.reload({offClick:function(){h.show()},destroy:function(){h.destroy()}})});this.destroy=function(){f.destroy()}})},{attach:false,requires:["fakeobjects"]});
KISSY.Editor.add("xiami-music/support",function(){function e(a){e.superclass.constructor.apply(this,arguments);a.cfg.disableObjectResizing||h.on(a.document.body,j.ie?"resizestart":"resize",function(i){(new d.Node(i.target)).hasClass(f)&&i.preventDefault()})}function c(a){return a._4e_name()==="img"&&!!a.hasClass(f)&&a}var d=KISSY,j=d.UA,f="ke_xiami",h=d.Event,g=d.Editor;d.extend(e,g.Flash,{_config:function(){this._cls=f;this._type="xiami-music";this._contextMenu=b;this._flashRules=["img."+f]},_updateTip:function(a,
i){var k=this.editor.restoreRealElement(i);if(k){a.html(i.attr("title"));a.attr("href",this._getFlashUrl(k))}}});var b={"\u867e\u7c73\u5c5e\u6027":function(a){var i=a.editor.getSelection();i=i&&i.getStartElement();(i=c(i))&&a.show(null,i)}};g.Flash.registerBubble("xiami-music","\u867e\u7c73\u97f3\u4e50\uff1a ",c);g.XiamiMusic=e;g.add({"xiami-music/dialog":{attach:false,charset:"utf-8",fullpath:g.Utils.debugUrl("../biz/ext/plugins/music/dialog/plugin.js")}});g.add({"xiami-music/dialog/support":{attach:false,charset:"utf-8",requires:["flash/dialog/support"],
fullpath:g.Utils.debugUrl("../biz/ext/plugins/music/dialog/support/plugin.js")}})},{attach:false,requires:["flash/support"]});KISSY.Editor.add("checkbox-sourcearea",function(e){var c=KISSY.Editor;KISSY.UA.gecko<1.92||c.use("checkbox-sourcearea/support",function(){var d=new c.CheckboxSourceArea(e);e.on("destroy",function(){d.destroy()})})},{attach:false});
KISSY.Editor.add("checkbox-sourcearea/support",function(){function e(g){this.editor=g;this._init()}var c=KISSY,d=c.Editor,j=c.Node,f=d.SOURCE_MODE,h=d.WYSIWYG_MODE;c.augment(e,{_init:function(){var g=this.editor,b=g.statusDiv;this.holder=(new j("<span style='zoom:1;display:inline-block;height:22px;line-height:22px;'><input style='margin:0 5px;vertical-align:middle;' type='checkbox' /><span style='vertical-align:middle;'>\u7f16\u8f91\u6e90\u4ee3\u7801</span></span>")).appendTo(b);var a=this.el=this.holder.one("input");a.on("click",
this._check,this);g.on("sourcemode",function(){a[0].checked=true});g.on("wysiwygmode",function(){a[0].checked=false})},_check:function(g){this.el[0].checked?this._show():this._hide();g&&g.halt()},_show:function(){d.SourceAreaSupport.exec(this.editor,f)},_hide:function(){d.SourceAreaSupport.exec(this.editor,h)},destroy:function(){this.el.detach();this.holder.remove()}});d.CheckboxSourceArea=e},{attach:false,requires:["sourcearea/support"]});
KISSY.Editor.add("multi-upload",function(e){var c=KISSY.Editor;if(!c.Env.mods["multi-upload/dialog"]){c.add({"multi-upload/dialog":{attach:false,charset:"utf-8",fullpath:c.Utils.debugUrl("../biz/ext/plugins/upload/dialog/plugin.js")}});c.add({"multi-upload/dialog/support":{attach:false,charset:"utf-8",requires:["progressbar","localstorage","overlay"],fullpath:c.Utils.debugUrl("../biz/ext/plugins/upload/dialog/support/plugin.js")}})}e.addPlugin("multi-upload",function(){var d=e.addButton("multi-upload",
{contentCls:"ke-toolbar-mul-image",title:"\u6279\u91cf\u63d2\u56fe",mode:c.WYSIWYG_MODE,offClick:function(){this.editor.useDialog("multi-upload/dialog",function(j){j.show()})},destroy:function(){this.editor.destroyDialog("multi-upload/dialog")}});this.destroy=function(){d.destroy()}})},{attach:false});
KISSY.Editor.add("video",function(e){function c(b){for(var a=0;a<h.length;a++){var i=h[a];if(i.reg.test(b))return i}}var d=KISSY.Editor,j=e.htmlDataProcessor,f=j&&j.dataFilter,h=[],g=e.cfg.pluginConfig;g.video=g.video||{};g=g.video;g.providers&&h.push.apply(h,g.providers);g.getProvider=c;f&&f.addRules({elements:{object:function(b){var a=b.attributes;if(!(a.classid&&String(a.classid).toLowerCase())){for(a=0;a<b.children.length;a++)if(b.children[a].name=="embed"){if(!d.Utils.isFlashEmbed(b.children[a]))break;
if(c(b.children[a].attributes.src))return j.createFakeParserElement(b,"ke_video","video",true)}return null}for(a=0;a<b.children.length;a++){var i=b.children[a];if(i.name=="param"&&i.attributes.name=="movie")if(c(i.attributes.value))return j.createFakeParserElement(b,"ke_video","video",true)}},embed:function(b){if(!d.Utils.isFlashEmbed(b))return null;if(c(b.attributes.src))return j.createFakeParserElement(b,"ke_video","video",true)}}},4);e.addPlugin("video",function(){var b=e.addButton("video",{contentCls:"ke-toolbar-video",
title:"\u63d2\u5165\u89c6\u9891",mode:d.WYSIWYG_MODE,loading:true});d.use("video/support",function(){var a=new d.Video(e);b.reload({offClick:function(){a.show()},destroy:function(){a.destroy()}})});this.destroy=function(){b.destroy()}})},{attach:false,requires:["fakeobjects"]});
KISSY.Editor.add("video/support",function(){function e(){e.superclass.constructor.apply(this,arguments)}function c(a){return a._4e_name()==="img"&&!!a.hasClass(h)&&a}var d=KISSY,j=d.Editor,f=j.Flash,h="ke_video",g=["img."+h];e.CLS_VIDEO=h;e.TYPE_VIDEO="video";d.extend(e,f,{_config:function(){this._cls=h;this._type="video";this._contextMenu=b;this._flashRules=g}});f.registerBubble("video","\u89c6\u9891\u94fe\u63a5\uff1a ",c);j.Video=e;var b={"\u89c6\u9891\u5c5e\u6027":function(a){var i=a.editor.getSelection();(i=(i=i&&i.getStartElement())&&c(i))&&
a.show(null,i)}};j.add({"video/dialog":{attach:false,charset:"utf-8",fullpath:j.Utils.debugUrl("../biz/ext/plugins/video/dialog/plugin.js")}});j.add({"video/dialog/support":{attach:false,charset:"utf-8",requires:["flash/dialog/support"],fullpath:j.Utils.debugUrl("../biz/ext/plugins/video/dialog/support/plugin.js")}})},{attach:false,requires:["flash/support"]});