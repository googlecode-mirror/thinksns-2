KISSY.Editor.add("progressbar",function(){function c(){c.superclass.constructor.apply(this,arguments);this._init()}var b=KISSY,f=b.Editor;if(!f.ProgressBar){var e=b.Node;b.DOM.addStyleSheet(".ke-progressbar {border:1px solid #D6DEE6;position:relative;margin-left:auto;margin-right:auto;background-color: #EAEFF4;background: -webkit-gradient(linear, left top, left bottom, from(#EAEFF4), .to(#EBF0F3)); background: -moz-linear-gradient(top, #EAEFF4, #EBF0F3);filter: progid:DXImageTransform.Microsoft.gradient(startColorstr = '#EAEFF4', endColorstr = '#EBF0F3');}.ke-progressbar-inner {border:1px solid #3571B4;background-color:#6FA5DB;padding:1px;}.ke-progressbar-inner-bg {height:100%;background-color: #73B1E9;background: -webkit-gradient(linear, left top, left bottom, from(#73B1E9), .to(#3F81C8)); background: -moz-linear-gradient(top, #73B1E9, #3F81C8);filter: progid:DXImageTransform.Microsoft.gradient(startColorstr = '#73B1E9', endColorstr = '#3F81C8');}.ke-progressbar-title {width:30px;top:0;left:40%;line-height:1.2;position:absolute;}",
"ke_progressbar");c.ATTRS={container:{},width:{},height:{},progress:{value:0}};b.extend(c,b.Base,{destroy:function(){this.detach();this.el.remove()},_init:function(){var a=this.get("height"),d=new e("<div class='ke-progressbar'  style='width:"+this.get("width")+";height:"+a+";'></div>"),g=this.get("container");a=(new e("<div style='overflow:hidden;'><div class='ke-progressbar-inner' style='height:"+(parseInt(a)-4)+"px'><div class='ke-progressbar-inner-bg'></div></div></div>")).appendTo(d);var h=(new e("<span class='ke-progressbar-title'>")).appendTo(d);
g&&d.appendTo(g);this.el=d;this._title=h;this._p=a;this.on("afterProgressChange",this._progressChange,this);this._progressChange({newVal:this.get("progress")})},_progressChange:function(a){a=a.newVal;this._p.css("width",a+"%");this._title.html(a+"%")}});f.ProgressBar=c}});
