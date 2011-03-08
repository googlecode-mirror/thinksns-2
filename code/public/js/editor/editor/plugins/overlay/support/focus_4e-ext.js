KISSY.Editor.add("ext-focus", function() {
    var S = KISSY,
        UA = S.UA,
        KE = S.Editor,
        focusManager = KE.focusManager;
    KE.namespace("Ext");

    function FocusExt() {
        S.log("FocusExt init");
        var self = this;
        self.on("renderUI", self._renderUIFocusExt, self);
        self.on("bindUI", self._bindUIFocusExt, self);
        self.on("syncUI", self._syncUIFocusExt, self);
    }

    FocusExt.ATTRS = {
        autoFocus4Editor:true
    };

    FocusExt.prototype = {
        _syncUIFocusExt:function() {
            S.log("_syncUIFocusExt");
        },
        _renderUIFocusExt:function() {
            S.log("_renderUIFocusExt");
            var el = this.get("el");
            this.focus4Editor = new S.Node("<a " +
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
                "></a>").appendTo(el);
        },
        _bindUIFocusExt:function() {
            var self = this;
            self.on("show", self._show4FocusExt, self);
            self.on("hide", self._hide4FocusExt, self);
        },
        _show4FocusExt:function() {
            var self = this;
            //保存当前焦点editor
            self._focusEditor = focusManager.currentInstance();
            var editor = self._focusEditor;
            /*
             * IE BUG: If the initial focus went into a non-text element (e.g. button,image),
             * then IE would still leave the caret inside the editing area.
             */
            if (UA.ie && editor) {
                //聚焦到当前窗口
                //webkit 滚动到页面顶部
                //使得编辑器失去焦点，促使ie保存当前选择区域（位置）
                try {
                    self.focus4Editor[0].focus();
                } catch(e) {
                    S.log(e);
                }
                var $selection = editor.document.selection,
                    $range = $selection.createRange();
                if ($range) {
                    if (
                    //修改ckeditor，如果单纯选择文字就不用管了
                    //$range.parentElement && $range.parentElement().ownerDocument == editor.document
                    //||
                    //缩放图片那个框在ie下会突出浮动层来
                        $range.item
                            && $range.item(0).ownerDocument == editor.document) {
                        var $myRange = document.body.createTextRange();
                        $myRange.moveToElementText(self.get("el")._4e_first()[0]);
                        $myRange.collapse(true);
                        $myRange.select();
                    }
                }
            }


        },
        _hide4FocusExt:function() {
            var editor = this._focusEditor;
            editor && editor.focus();
        }
    };
    KE.Ext.Focus = FocusExt;

}, {
    host:"overlay"
});