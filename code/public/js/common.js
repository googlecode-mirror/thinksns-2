//设置黑名单
function setBlacklist(uid,type){
	$.post(U('home/Account/setBlackList') , {uid:uid,type:type} ,function(txt){
		ui.success('设置成功');
		location.reload();
	})
}

//空间关注操作
function dofollow(type,target,uid){
	var html = '';
	$('#follow_state').html( '<img src="'+ _THEME_+'/images/icon_waiting.gif" width="15">' );
	$.post( U('weibo/Operate/follow') ,{uid:uid,type:type},function(txt){
		if(txt=='12'){
			html = followState('havefollow');
		}else if(txt=='13'){
			html = followState('eachfollow');
		}else if(txt=='00'){
			ui.error('对方不允许你关注');
			html = followState('unfollow',target,uid);
		}else{
			html = followState();
		}
		$('#follow_state').html( html );
	});
}

//列表关注操作
function dolistfollow(type,target,uid){
	var html = '';
	var target=target;
	var uid=uid;
	$("#follow_list_"+uid).html( '<img src="'+ _THEME_+'/images/icon_waiting.gif" width="15">' );
	$.post( U('weibo/Operate/follow') ,{uid:uid,type:type},function(txt){
		if(txt=='12'){
			html = followState('havefollow',target,uid);
		}else if(txt=='13'){
			html = followState('eachfollow',target,uid);
		}else if(txt=='00'){
			ui.error('对方不允许你关注');
			html = followState('unfollow',target,uid);
		}else{
			html = followState('',target,uid);
		}
		$("#follow_list_"+uid).html( html );
	});
}

//关注状态
function followState(type,target,uid){
	target = target || 'dofollow';
	uid    = uid    || _UID_;
	if(type=='havefollow'){
		html = '<div class="btn_relation"><span>已关注&nbsp;&nbsp;|&nbsp;&nbsp;</span><a href="javascript:void(0);" onclick="'+target+'(\'unflollow\',\''+target+'\','+uid+')">取消</a></div>';
	}else if(type=='eachfollow'){
		html = '<div class="btn_relation btn_relation2"><span>互相关注&nbsp;&nbsp;|&nbsp;&nbsp;</span><a href="javascript:void(0);" onclick="'+target+'(\'unflollow\',\''+target+'\','+uid+')">取消</a></div>';
	}else{
		html = '<a class="add_atn" href="javascript:void(0);" onclick="'+target+'(\'dofollow\',\''+target+'\','+uid+')">加关注</a>';
	}
	return html;
}

ui = window.ui ||{
	success:function(message,error){
		var style = (error==1)?"html_clew_box clew_error ":"html_clew_box";
		var html   =   '<div class="" id="ui_messageBox" style="display:none">'
					   + '<div class="html_clew_box_close"><a href="javascript:void(0)" onclick="$(this).parents(\'.html_clew_box\').hide()" title="关闭">关闭</a></div>'
					   + '<div class="html_clew_box_con" id="ui_messageContent">&nbsp;</div></div>';
		var init      =  0;
		
		var showMessage = function( message ){		
			if( !init ){
				$('body').append( html );
				init = 1;
			}
			

			
			$( '#ui_messageContent' ).html( message );
			$('#ui_messageBox').attr('class',style);
			
			var v =  ui.box._viewport() ;
			
			jQuery('<div class="boxy-modal-blackout"></div>')
	        .css(jQuery.extend(ui.box._cssForOverlay(), {
	            zIndex: 99, opacity: 0.2
	        })).appendTo(document.body);
			
			
			$( '#ui_messageBox' ).css({
				left:( v.left + v.width/2  - $( '#ui_messageBox' ).outerWidth()/2 ) + "px",
				top:(  v.top  + v.height/2 - $( '#ui_messageBox' ).outerHeight()/2 ) + "px"
			});			
			
			$( '#ui_messageBox' ).fadeIn("slow");
		}
		
		
		var closeMessage = function(){
			setTimeout( function(){  
				$( '#ui_messageBox' ).fadeOut("fast",function(){
					jQuery('.boxy-modal-blackout').remove(); 
				});
			} , 1500);
		}
		
		showMessage( message );
		closeMessage();

	},
	
	error:function(message){
		ui.success(message,1);
	},

	load:function(){
		var init = 0
		var loadingBox = '<div class="html_clew_box" id="ui_loading" style="display:none"><div class="html_clew_box_con"><span class="ico_waiting">加载中……</span></div></div>';
		if( !init ){
			$('body').append( loadingBox );
			init = 1;
		}
		
		$( '#ui_loading' ).css({
			right:100+"px",
			top:($(document).scrollTop())+"px"
		});
		$( '#ui_loading' ).fadeIn("slow");
	},
	
	loaded:function(){
		var loadingBox = '#ui_loading';
		$( loadingBox ).fadeOut("slow");
	},
	
	quicklogin:function(){
		ui.box.load( U('home/public/quick_login') ,{title:'快速登录'});
	},
	
	sendmessage:function(touid){
		touid = touid || '';
		ui.box.load(U('home/Message/post',['touid='+touid]), {title:'发私信'});
	},
	
	confirm:function(o,text){
		var callback = $(o).attr('callback');
		text = text || '确定要做此项操作吗？';
		this.html = '<div id="ts_ui_confirm" class="ts_confirm"><span class="txt"></span><br><input type="button" value="确定"  class="btn_b mr5"><input type="button" value="取消"  class="btn_w"></div>';
		if( $('#ts_ui_confirm').html()==null ){
			$('body').append(this.html);
		}
		var position = $(o).offset();
		$('#ts_ui_confirm').css({"top":position.top+"px","left":position.left-($("#ts_ui_confirm").width()/2)+"px","display":"none"});
		$("#ts_ui_confirm .txt").html(text);
		$('#ts_ui_confirm').fadeIn("fast");
		$("#ts_ui_confirm .btn_w").one('click',function(){
			$('#ts_ui_confirm').fadeOut("fast");
		});
		$("#ts_ui_confirm .btn_b").one('click',function(){
			$('#ts_ui_confirm').fadeOut("fast");
			eval(callback);
		});
	},
	
	emotions:function(o){
		$('div .talkPop').hide();
		this.emotdata = $("div").data("emotdata");
		this.html = '<div class="talkPop alL" id="emotions" style="*padding-top:20px;">'
				 + '<div style="position: relative; height: 7px; line-height: 3px;">'
				 + '<img src="http://develop.thinksns.com/ts_beta_2_0/public/themes/classic/images/zw_img.gif" style="margin-left: 10px; position: absolute;" class="talkPop_arrow"></div>'
				 + '<div class="talkPop_box">'
				 + '<div class="close" style="height:20px"><a onclick=" $(\'#emotions\').remove()" class="del" href="javascript:void(0)" title="关闭"> </a><span class="pl5">点击插入表情</span></div>'
				 + '<div class="faces_box" id="emot_content"><img src="'+ _THEME_+'/images/icon_waiting.gif" width="20" class="alM"></div></div></div>';
		target_set = $(o).attr('target_set');
		$('body').append(this.html);
		var position = $(o).offset();
		$('#emotions').css({"top":position.top+"px","left":position.left+"px"});
		
		var _this = this;
		if(!this.emotdata){
			$.post( U('home/user/emotions'),{target:$(this).attr('target_set')} ,function(txt){
				txt = eval('('+txt+')');
				$("div").data("emotdata",txt);
				_this.showContent(txt);
			})
		}else{
			_this.showContent(this.emotdata);
		};

		this.showContent = function(data){  //显示表情内容
			var content ='';
			$.each(data,function(i,n){
				content+='<a href="javascript:void(0)" onclick="ui.emotions_c(\''+n.emotion+'\',\''+target_set+'\')"><img src="'+ _THEME_ +'/images/expression/miniblog/'+ n.filename +'" /></a>';
			});
			content+='<div class="c"></div>';
			$('#emot_content').html(content);
		};
		
		$('body').live('click',function(event){
			if( $(event.target).attr('target_set')!=target_set ){
				$('#emotions').remove();
			}
		})
	},
	
	emotions_c:function(emot,target){
		$("#"+target).val( $("#"+target).val()+emot+' ' );
		
		var textArea = document.getElementById(target);
		var strlength = textArea.value.length;
		if (document.selection) { //IE
			 var rng = textArea.createTextRange();
			 rng.collapse(true);
			 rng.moveStart("character",strlength)
		}else if (textArea.selectionStart || (textArea.selectionStart == '0')) { // Mozilla/Netscape…
	        textArea.selectionStart = strlength;
	        textArea.selectionEnd = strlength;
	    }
		if(target=='content_publish'){
			weibo.checkInputLength('#content_publish',140);
		}
		$("#"+target).focus();
		$('#emotions').remove();
	},	
	
	countNew:function(){
		$.getJSON( U('home/user/countNew') ,function(txt){
			if(txt.total!="0"){
				$("#count_total").html( txt.total );
				$("#count_total_div").show();
			}
			if(txt.message!="0"){
				$("#count_message").html('('+txt.message+')');
			}
			
			if(txt.appmessage!="0"){
				$("#count_appmessage").html('('+txt.appmessage+')');
			}
			
			if(txt.notify!="0"){
				$("#count_notify").html('('+txt.notify+')');
			}
			
			if(txt.comment!="0"){
				$("#count_comment").html('('+txt.comment+')');
				$("#app_left_count_comment").html("(<font color=\"red\">"+txt.comment+"</font>)");
			}
			if(txt.atme!="0"){
				$("#count_atme").html('('+txt.atme+')');
				$("#app_left_count_atme").html("(<font color=\"red\">"+txt.atme+"</font>)");
			}
		});
	},
	
	getarea:function(prefix,init_style,init_p,init_c){
		var style = (init_style)?'class="'+init_style+'"':'';
		var html = '<select name="'+prefix+'_province" '+style+'><option>省/直辖市</option></select> <select name="'+prefix+'_city" '+style+'><option value=0>不限</option></select>';
		document.write(html);
		// _PUBLIC_+'/js/area.js'
		$.getJSON(U('home/Public/getArea'), function(json){
			json = json.provinces;
			var province ='<option>省/直辖市</option>';
			$.each(json,function(i,n){
				var pselected='';
				var cselected='';
				var city='<option>不限</option>';
				if(n.id==init_p){
					 pselected = 'selected="true"';
					 $.each(n.citys,function(j,m){
							for(var p in m){
								cselected = (p==init_c)?'selected="true"':'';
								city+='<option value="'+p+'" '+cselected+'>'+m[p]+'</option>';
							};
					 });
					 $("select[name='"+prefix+"_city']").html(city);
				}
				province+='<option value="'+n.id+'" rel="'+i+'" '+pselected+'>'+n.name+'</option>';
			});
			
			$("select[name='"+prefix+"_province']").live('change',function(){
				var city='<option>不限</option>';
				var handle =  $(this).find('option:selected').attr('rel');
				if( handle ){
					var t =  json[handle].citys;
					$.each(t,function(j,m){
						for(var p in m){
							city+='<option value='+p+'>'+m[p]+'</option>';
						};
					});
				};
				$("select[name='"+prefix+"_city']").html(city);
			});
			$("select[name='"+prefix+"_province']").html(province);
		}); 
	}

	
};

function AutoResizeImage(maxWidth,maxHeight,objImg){
	var img = new Image();
	img.src = objImg.src;
	var hRatio;
	var wRatio;
	var Ratio = 1;
	var w = img.width;
	var h = img.height;
	wRatio = maxWidth / w;
	hRatio = maxHeight / h;
	if (maxWidth ==0 && maxHeight==0){
		Ratio = 1;
	}else if (maxWidth==0){//
		if (hRatio<1) Ratio = hRatio;
	}else if (maxHeight==0){
		if (wRatio<1) Ratio = wRatio;
	}else if (wRatio<1 || hRatio<1){
		Ratio = (wRatio<=hRatio?wRatio:hRatio);
	}
	if (Ratio<1){
		w = w * Ratio;
		h = h * Ratio;
	}
	objImg.height = h;
	objImg.width = w;
}

//模拟ts U函数
function U(url,params){
	var website = _ROOT_+'/index.php';
	url = url.split('/');
	if(url[0]=='' || url[0]=='@') url[0] = APPNAME;
	website = website+'?app='+url[0]+'&mod='+url[1]+'&act='+url[2];
	if(params){
		params = params.join('&');
		website = website + '&' + params;
	}
	return website;
}


/**
 * http://www.openjs.com/scripts/events/keyboard_shortcuts/
 * Version : 1.00.A
 * By Binny V A
 * 键盘绑定事件
 * License : BSD
 */
function shortcut(shortcut,callback,opt) {
	//Provide a set of default options
	var default_options = {
		'type':'keydown',
		'propagate':false,
		'target':document
	}
	if(!opt) opt = default_options;
	else {
		for(var dfo in default_options) {
			if(typeof opt[dfo] == 'undefined') opt[dfo] = default_options[dfo];
		}
	}

	var ele = opt.target
	if(typeof opt.target == 'string') ele = document.getElementById(opt.target);
	var ths = this;

	//The function to be called at keypress
	var func = function(e) {
		e = e || window.event;

		//Find Which key is pressed
		if (e.keyCode) code = e.keyCode;
		else if (e.which) code = e.which;
		var character = String.fromCharCode(code).toLowerCase();

		var keys = shortcut.toLowerCase().split("+");
		//Key Pressed - counts the number of valid keypresses - if it is same as the number of keys, the shortcut function is invoked
		var kp = 0;
		
		//Work around for stupid Shift key bug created by using lowercase - as a result the shift+num combination was broken
		var shift_nums = {
			"`":"~",
			"1":"!",
			"2":"@",
			"3":"#",
			"4":"$",
			"5":"%",
			"6":"^",
			"7":"&",
			"8":"*",
			"9":"(",
			"0":")",
			"-":"_",
			"=":"+",
			";":":",
			"'":"\"",
			",":"<",
			".":">",
			"/":"?",
			"\\":"|"
		}
		//Special Keys - and their codes
		var special_keys = {
			'esc':27,
			'escape':27,
			'tab':9,
			'space':32,
			'return':13,
			'enter':13,
			'backspace':8,

			'scrolllock':145,
			'scroll_lock':145,
			'scroll':145,
			'capslock':20,
			'caps_lock':20,
			'caps':20,
			'numlock':144,
			'num_lock':144,
			'num':144,
			
			'pause':19,
			'break':19,
			
			'insert':45,
			'home':36,
			'delete':46,
			'end':35,
			
			'pageup':33,
			'page_up':33,
			'pu':33,

			'pagedown':34,
			'page_down':34,
			'pd':34,

			'left':37,
			'up':38,
			'right':39,
			'down':40,

			'f1':112,
			'f2':113,
			'f3':114,
			'f4':115,
			'f5':116,
			'f6':117,
			'f7':118,
			'f8':119,
			'f9':120,
			'f10':121,
			'f11':122,
			'f12':123
		}


		for(var i=0; k=keys[i],i<keys.length; i++) {
			//Modifiers
			if(k == 'ctrl' || k == 'control') {
				if(e.ctrlKey) kp++;

			} else if(k ==  'shift') {
				if(e.shiftKey) kp++;

			} else if(k == 'alt') {
					if(e.altKey) kp++;

			} else if(k.length > 1) { //If it is a special key
				if(special_keys[k] == code) kp++;

			} else { //The special keys did not match
				if(character == k) kp++;
				else {
					if(shift_nums[character] && e.shiftKey) { //Stupid Shift key bug created by using lowercase
						character = shift_nums[character]; 
						if(character == k) kp++;
					}
				}
			}
		}

		if(kp == keys.length) {
			callback(e);

			if(!opt['propagate']) { //Stop the event
				//e.cancelBubble is supported by IE - this will kill the bubbling process.
				e.cancelBubble = true;
				e.returnValue = false;

				//e.stopPropagation works only in Firefox.
				if (e.stopPropagation) {
					e.stopPropagation();
					e.preventDefault();
				}
				return false;
			}
		}
	}

	//Attach the function with the event	
	if(ele.addEventListener) ele.addEventListener(opt['type'], func, false);
	else if(ele.attachEvent) ele.attachEvent('on'+opt['type'], func);
	else ele['on'+opt['type']] = func;
}

// 图片缩放
function photo_resize(name,sizeNum){
	var newWidth = $(name).width();
    $(name +" img").each(function(){
        
        var width = sizeNum || 728;
        var images = $(this);
        
        //判断是否是IE
        if (-[1, ]) {
            image = new Image();
            image.src = $(this).attr('src');
            image.onload = function(){
                if (image.width >= width) {
                    images.click(function(){
                        tb_show("", this.src, false);
                    });
                    images.width(width);
                    images.height(width / image.width * image.height);
                }
            }
        }
        else {
            if (images.width() >= width) {
                images.click(function(){
                    tb_show("", this.src, false);
                });
                images.width(width);
                images.height(width / images.width() * images.height());
            }
        }

		
		//image.attr('rel','imageGroup');

    });
}