/**
 * dd support for kissy editor
 * @author:yiminghe@gmail.com
 */
KISSY.Editor.add("dd", function() {
    var S = KISSY,
        KE = S.Editor,
        Event = S.Event,
        UA = S.UA,
        DOM = S.DOM,
        Node = S.Node;
    if (KE.DD) return;
    KE.DD = {};

    function Manager() {
        var self = this;
        Manager.superclass.constructor.apply(self, arguments);
        self._init();
    }

    Manager.ATTRS = {
        /**
         * mousedown 后 buffer 触发时间  timeThred
         */
        bufferTime: { value: 200 },

        /**
         * 当前激活的拖动对象，在同一时间只有一个值，所以不是数组
         */
        activeDrag: { }
    };

    /*
     负责拖动涉及的全局事件：
     1.全局统一的鼠标移动监控
     2.全局统一的鼠标弹起监控，用来通知当前拖动对象停止
     3.为了跨越iframe而统一在底下的遮罩层
     */
    S.extend(Manager, S.Base, {
        _init: function() {
            var self = this;
            self._showShimMove = KE.Utils.throttle(self._move, self, 30);
        },

        /*
         全局鼠标移动事件通知当前拖动对象正在移动
         注意：chrome8: click 时 mousedown-mousemove-mouseup-click 也会触发 mousemove
         */
        _move: function(ev) {
            var activeDrag = this.get('activeDrag');
            S.log("move");
            if (!activeDrag) return;
            //防止 ie 选择到字
            ev.preventDefault();
            this._clearSelection();
            activeDrag._move(ev);
        },

        /**
         * 当前拖动对象通知全局：我要开始啦
         * 全局设置当前拖动对象，
         * 还要根据配置进行 buffer 处理
         * @param drag
         */
        _start: function(drag) {
            var self = this,
                bufferTime = self.get("bufferTime") || 0;

            //事件先要注册好，防止点击，导致 mouseup 时还没注册事件
            self._registerEvent();

            //是否中央管理，强制限制拖放延迟
            if (bufferTime) {
                self._bufferTimer = setTimeout(function() {
                    self._bufferStart(drag);
                }, bufferTime);
            } else {
                self._bufferStart(drag);
            }
        },

        _bufferStart: function(drag) {
            var self = this;
            self.set('activeDrag', drag);

            //真正开始移动了才激活垫片
            self._activeShim();
            drag._start();
        },

        /**
         * 全局通知当前拖动对象：你结束拖动了！
         * @param ev
         */
        _end: function(ev) {
            var self = this,
                activeDrag = self.get("activeDrag");
            self._unregisterEvent();
            if (self._bufferTimer) {
                clearTimeout(self._bufferTimer);
                self._bufferTimer = null;
            }
            self._shim && self._shim.css({
                display:"none"
            });

            if (!activeDrag) return;
            activeDrag._end(ev);
            self.set("activeDrag", null);
        },

        /**
         * 垫片只需创建一次
         */
        _activeShim: function() {
            var self = this,doc = document;
            //创造垫片，防止进入iframe，外面document监听不到 mousedown/up/move
            self._shim = new Node("<div " +
                "style='" +
                //red for debug
                "background-color:red;" +
                "position:absolute;" +
                "left:0;" +
                "width:100%;" +
                "top:0;" +
                "z-index:" +
                //覆盖iframe上面即可
                KE.baseZIndex(KE.zIndexManager.DD_PG)
                + ";" +
                "'></div>").appendTo(doc.body);
            //0.5 for debug
            self._shim.css("opacity", 0);
            self._activeShim = self._showShim;
            self._showShim();
        },

        _showShim: function() {
            var self = this;
            self._shim.css({
                display: "",
                height: DOM.docHeight()
            });
            self._clearSelection();
        },
        _clearSelection:function() {
            //清除由于浏览器导致的选择文字
            if (window.getSelection) {
                window.getSelection().removeAllRanges();
            }
            //防止 ie 莫名选择文字
            else if (document.selection) {
                document.selection.empty();
            }
        },

        /**
         * 开始时注册全局监听事件
         */
        _registerEvent: function() {
            var self = this,doc = document;
            S.log("_registerEvent");
            Event.on(doc, "mouseup", self._end, self);
            Event.on(doc, "mousemove", self._showShimMove, self);
        },

        /**
         * 结束时需要取消掉，防止平时无谓的监听
         */
        _unregisterEvent: function() {
            var self = this,doc = document;
            S.log("_unregisterEvent");
            Event.remove(doc, "mousemove", self._showShimMove, self);
            Event.remove(doc, "mouseup", self._end, self);
        }
    });

    KE.DD.DDM = new Manager();
    var DDM = KE.DD.DDM;

    /*
     拖放纯功能类
     */
    function Draggable() {
        var self = this;
        Draggable.superclass.constructor.apply(self, arguments);
        self._init();
    }

    Draggable.ATTRS = {
        //拖放节点
        node:{},
        //handler 集合，注意暂时必须在 node 里面
        handlers:{value:{}}
    };

    S.extend(Draggable, S.Base, {
        _init:function() {
            var self = this,
                node = self.get("node"),
                handlers = self.get("handlers");
            //DDM.reg(node);
            if (S.isEmptyObject(handlers)) {
                handlers[node[0].id] = node;
            }
            for (var h in handlers) {
                if (!handlers.hasOwnProperty(h)) continue;
                var hl = handlers[h],ori = hl.css("cursor");
                if (!hl._4e_equals(node)) {
                    if (!ori || ori === "auto")
                        hl.css("cursor", "move");
                    //ie 不能被选择了
                    //hl._4e_unselectable();
                }
            }
            node.on("mousedown", self._handleMouseDown, self);
        },
        _check:function(t) {
            var handlers = this.get("handlers");
            for (var h in handlers) {
                if (!handlers.hasOwnProperty(h)) continue;
                if (handlers[h].contains(t)
                    ||
                    //子区域内点击也可以启动
                    handlers[h]._4e_equals(t)) return true;
            }
            return false;
        },

        /**
         * 鼠标按下时，查看触发源是否是属于 handler 集合，
         * 保存当前状态
         * 通知全局管理器开始作用
         * @param ev
         */
        _handleMouseDown:function(ev) {
            var self = this,
                t = new Node(ev.target);
            if (!self._check(t)) return;
            //chrome 阻止了 flash 点击？？
            if (!UA.webkit) {
                //firefox 默认会拖动对象地址
                ev.preventDefault();
            }
            //
            DDM._start(self);

            var node = self.get("node"),
                mx = ev.pageX,
                my = ev.pageY,
                nxy = node.offset();
            self.startMousePos = {
                left:mx,
                top:my
            };
            self.startNodePos = nxy;
            self._diff = {
                left:mx - nxy.left,
                top:my - nxy.top
            };

        },
        _move:function(ev) {
            this.fire("move", ev)
        },
        _end:function() {
            this.fire("end");
        },
        _start:function() {
            this.fire("start");
        }
    });

    /*
     拖放实体，功能反应移动时，同时移动节点
     */
    function Drag() {
        Drag.superclass.constructor.apply(this, arguments);
    }

    S.extend(Drag, Draggable, {
        _init:function() {
            var self = this;
            Drag.superclass._init.apply(self, arguments);
            var node = self.get("node");
            self.on("move", function(ev) {
                var left = ev.pageX - self._diff.left,
                    top = ev.pageY - self._diff.top;
                node.offset({
                    left:left,
                    top:top
                })
            });
        }
    });

    KE.Draggable = Draggable;
    KE.Drag = Drag;

});