/**
 * simple overlay for kissy editor using lazyRun
 * @author yiminghe@gmail.com
 * @refer http://yiminghe.javaeye.com/blog/734867
 */
KISSY.Editor.add("overlay", function() {
    // 每次实例都要载入!
    //console.log("overlay loaded!");
    var S = KISSY,
        KE = S.Editor,
        UA = S.UA,
        focusManager = KE.focusManager,
        Node = S.Node,
        Event = S.Event,
        DOM = S.DOM,
        dialogMarkUp = "<div class='ke-dialog' " +
            ">" +
            "<div class='ke-dialog-wrapper'>" +
            "<div class='ke-hd'>" +
            "<span class='ke-hd-title'>" +
            "@title@" +
            "</span>"
            + "<a class='ke-hd-x' href='#'>" +
            "<span class='ke-close'>X</span>" +
            "</a>"
            + "</div>" +
            "<div class='ke-bd'>" +
            "</div>" +
            "<div class='ke-ft'>" +
            "</div>" +
            "</div>" +
            "</div>",
        focusMarkup = "<a " +
            "href='#' " +
            "class='ke-focus' " +
            "style='" +
            "width:0;" +
            "height:0;" +
            "margin:0;" +
            "padding:0;" +
            "overflow:hidden;" +
            "outline:none;" +
            "font-size:0;'" +
            "></a>",
        mask ,
        loadingMask,
        noVisibleStyle = {
            "left":"-9999px",
            top:"-9999px"
        },
        loadingBaseZindex = KE.baseZIndex(KE.zIndexManager.LOADING);

    //全局的不要重写
    if (KE.SimpleOverlay) return;

    function Overlay() {
        var self = this;
        Overlay.superclass.constructor.apply(self, arguments);
        self._init();
    }


    Overlay.mask = function(zIndex) {
        if (!mask) {
            /**
             * 遮罩层
             */
            mask = new Overlay({
                el:new Node("<div>"),
                cls:"ke-mask",
                focusMgr:false,
                draggable:false
            });
            mask.el.css({
                "width":"100%",
                "background-color": "#000000",
                "height": DOM.docHeight(),
                "opacity": 0.15
            });
        }
        zIndex = zIndex || loadingBaseZindex;
        mask.el.css("z-index", zIndex);
        mask.show({left:0,top:0});
    };
    Overlay.unmask = function() {
        mask && mask.hide();
    };


    Overlay.loading = function(el) {
        if (!loadingMask) {
            loadingMask = new Overlay({
                el:new Node("<div>"),
                focusMgr:false,
                cls:"ke-loading",
                shortkey:false,
                draggable:false
            });
            loadingMask.el.css({
                opacity:0.15,
                border:0
            });
        }

        var width,height,offset,zIndex;
        if (el) {
            offset = el.offset();
            width = el[0].offsetWidth;
            height = el[0].offsetHeight;
            zIndex = parseInt(el.css("z-index")) + 1;
            //在元素的中间
            loadingMask.el.css("background-attachment", "scroll");
        } else {
            //DOM.addClass(document.documentElement, "ke-overflow-hidden");
            offset = {
                left:0,
                top:0
            };
            width = "100%";
            height = DOM.docHeight();
            zIndex = loadingBaseZindex;
            //在视窗的中间
            loadingMask.el.css("background-attachment", "fixed");
        }

        loadingMask.el.css({
            width:width,
            height:height,
            "z-index":zIndex
        });
        loadingMask.show(offset);
        return loadingMask;
    };

    Overlay.unloading = function() {
        //DOM.removeClass(document.documentElement, "ke-overflow-hidden");
        loadingMask && loadingMask.hide();
    };


    Overlay.ATTRS = {
        title:{value:""},
        width:{value:"500px"},
        height:{},
        cls:{},
        shortkey:{value:true},
        visible:{value:false},
        "zIndex":{value:KE.baseZIndex(KE.zIndexManager.OVERLAY)},
        //帮你管理焦点
        focusMgr:{value:true},
        mask:{value:false},
        draggable:{value:true}
    };

    S.extend(Overlay, S.Base, {
        _init:function() {
            var self = this;
            self._createEl();
            var el = self.el;
            el.css("z-index", self.get("zIndex"));
            /**
             * 窗口显示与隐藏
             */
            self.on("afterVisibleChange", function(ev) {
                var v = ev.newVal;
                if (v) {
                    if (typeof v == "boolean") {
                        self.center();
                    } else el.offset(v);
                    self.fire("show");
                } else {
                    el.css(noVisibleStyle);
                    self.fire("hide");
                }
            });

            /**
             * 关联编辑器焦点保留与复原
             */
            if (self.get("focusMgr")) {
                self._initFocusNotice();
                self.on("afterVisibleChange", self._editorFocusMg, self);
            }


            /**
             * 键盘快捷键注册
             */
            self.on("afterVisibleChange", function(ev) {
                var v = ev.newVal;
                if (v && self.get("shortkey")) {
                    self._register();
                } else {
                    self._unregister();
                }
            });


            if (self.get("mask")) {
                /**
                 * 遮罩层与ie6遮罩垫片同步
                 */
                self.on("show", function() {
                    Overlay.mask(self.get("zIndex") - 1);
                });
                self.on("hide", function() {
                    Overlay.unmask();
                });
            }

            self.on("afterZIndexChange", function(ev) {
                el.css("z-index", ev.newVal)
            });
            KE.Utils.lazyRun(this, "_prepareShow", "_realShow");

        },
        _register:function() {
            var self = this;
            Event.on(document, "keydown", self._keydown, self);
            //mask click support
            //if (mask) {
            //    mask.on("click", self.hide, self);
            //}
        },
        //esc keydown support
        _keydown:function(ev) {
            //esc
            if (ev.keyCode == 27) {
                this.hide();
                //停止默认行为，例如取消对象选中
                ev.halt();
            }
        },
        _unregister:function() {
            var self = this;
            Event.remove(document, "keydown", self._keydown, self);
            //if (mask) {
            //    mask.detach("click", self.hide, self);
            //}
        },
        _createEl:function() {
            //just manage container
            var self = this,
                el = self.get("el");
            if (!el) {
                //also gen html
                el = new Node(
                    dialogMarkUp.replace(/@title@/,
                        self.get("title"))).appendTo(document.body
                    );
                var head = el.one(".ke-hd"),
                    height = self.get("height");
                self.body = el.one(".ke-bd");
                self.foot = el.one(".ke-ft");
                self._title = head.one("h1");
                el.one(".ke-hd-x").on("click", function(ev) {
                    ev.preventDefault();
                    self.hide();
                });
                if (height) {
                    self.body.css({
                        "height": height,
                        "overflow":"auto"
                    });
                }


                /**
                 *  是否支持标题头拖放
                 */
                var draggable = self.get("draggable");
                if (draggable) {
                    var dragPos = {
                        "all":el ,
                        "foot":self.foot,
                        "body":self.body,
                        "head":head
                    };
                    if (draggable === true)
                        draggable = head;
                    else
                        draggable = dragPos[draggable];
                    if (draggable) {
                        new KE.Drag({
                            node:el,
                            handlers:{
                                id:draggable
                            }
                        });
                    }
                }
            } else {
                //已有元素就用dialog包起来
                self.originalEl = el;
                if (!el[0].parentNode ||
                    //ie新节点 为 fragment 类型
                    el[0].parentNode.nodeType != KE.NODE.NODE_ELEMENT) {
                    el = new Node("<div class='ke-dialog'>")
                        .append(new Node("<div class='ke-dialog-wrapper'>")
                        .append(el))
                        .appendTo(document.body);
                } else {
                    var w = new Node("<div class='ke-dialog'>");
                    w.insertBefore(el);
                    w.append(new Node("<div class='ke-dialog-wrapper'>").append(el));
                    el = w;
                }
            }
            if (self.get("cls")) {
                el.addClass(self.get("cls"));
            }
            if (self.get("width")) {
                el.css("width", self.get("width"));
            }

            self.set("el", el);
            //expose shortcut
            self.el = el;
            //初始状态隐藏
            el.css(noVisibleStyle);
        },

        center :function() {
            var el = this.el,
                bw = el.width(),
                bh = el.height(),
                vw = DOM.viewportWidth(),
                vh = DOM.viewportHeight(),
                bl = (vw - bw) / 2 + DOM.scrollLeft(),
                bt = (vh - bh) / 2 + DOM.scrollTop();
            if ((bt - DOM.scrollTop()) > 200) bt -= 150;

            bl = Math.max(bl, DOM.scrollLeft());
            bt = Math.max(bt, DOM.scrollTop());

            el.css({
                left: bl + "px",
                top: bt + "px"
            });
        },


        _getFocusEl:function() {
            var self = this,fel = self._focusEl;
            if (fel) {
                return fel;
            }
            //焦点管理，显示时用a获得焦点
            fel = new Node(focusMarkup)
                .appendTo(self.el);
            return self._focusEl = fel;
        }        ,

        _initFocusNotice:function() {
            var self = this,
                f = self._getFocusEl();
            f.on("focus", function() {
                self.fire("focus");
            });
            f.on("blur", function() {
                self.fire("blur");
            });
        },

        /**
         * 焦点管理，弹出前记住当前的焦点所在editor
         * 隐藏好重新focus当前的editor
         */
        _editorFocusMg:function(ev) {
            var self = this,
                editor = self._focusEditor,
                v = ev.newVal;
            //console.log(v + " change");
            //将要出现
            if (v) {
                //保存当前焦点editor
                self._focusEditor = focusManager.currentInstance();
                editor = self._focusEditor;
                //聚焦到当前窗口
                if (!UA.webkit) {
                    //webkit 滚动到页面顶部
                    //使得编辑器失去焦点，促使ie保存当前选择区域（位置）
                    self._getFocusEl()[0].focus();
                }
                {
                    /*
                     * IE BUG: If the initial focus went into a non-text element (e.g. button,image),
                     * then IE would still leave the caret inside the editing area.
                     */
                    if (UA.ie && editor) {
                        var $selection = editor.document.selection,
                            $range = $selection.createRange();
                        if ($range) {
                            if (
                            //修改ckeditor，如果单纯选择文字就不用管了
                            //$range.parentElement && $range.parentElement().ownerDocument == editor.document
                            //||
                            //缩放图片那个框在ie下会突出浮动层来
                                $range.item && $range.item(0).ownerDocument == editor.document) {
                                var $myRange = document.body.createTextRange();
                                $myRange.moveToElementText(self.el._4e_first()[0]);
                                $myRange.collapse(true);
                                $myRange.select();
                            }
                        }
                    }
                }

            }
            //将要隐藏
            else {
                editor && editor.focus();
            }
        },
        _prepareShow:function() {
            if (UA.ie == 6) {
                /**
                 * 窗口垫片-shim
                 */
                var self = this,
                    el = self.el,
                    d_iframe = new Node(
                        "<" + "iframe class='ke-dialog-iframe'" +
                            "></iframe>");
                d_iframe.css(S.mix({
                    opacity:0
                }));
                d_iframe.insertBefore(self.el.one(".ke-dialog-wrapper"));
            }
        },

        loading:function() {
            return Overlay.loading(this.el);
        },

        unloading:function() {
            Overlay.unloading();
        },
        _realShow : function(v) {
            this.set("visible", v || true);
        },
        show:function(v) {
            this._prepareShow(v);
        },
        hide:function() {
            this.set("visible", false);
        }
    });
    KE.Utils.lazyRun(Overlay.prototype,
        "_prepareLoading",
        "_realLoading");
    KE.SimpleOverlay = Overlay;
});
