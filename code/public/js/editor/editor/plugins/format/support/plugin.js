/**
 * format formatting,modified from ckeditor
 * @modifier: yiminghe@gmail.com
 */
KISSY.Editor.add("format/support", function() {
    var KE = KISSY.Editor,
        S = KISSY,
        Node = S.Node;

    function Format(cfg) {
        var self = this;
        Format['superclass'].constructor.call(self, cfg);
        self._init();
    }

    Format.ATTRS = {
        editor:{}
    };
    var Select = KE.Select;
    S.extend(Format, S.Base, {
        _init:function() {
            var self = this,
                editor = this.get("editor"),
                toolBarDiv = editor.toolBarDiv;
            self.el = new Select({
                container: toolBarDiv,
                value:"",
                doc:editor.document,
                width:self.get("width"),
                popUpWidth:self.get("popUpWidth"),
                title:self.get("title"),
                items:self.get("html"),
                menuContainer:new Node(document.body)
            });
            self.el.on("click", self._vChange, self);
            editor.on("selectionChange", self._selectionChange, self);
            KE.Utils.sourceDisable(editor, self);
        },
        disable:function() {
            this.el.set("state", Select.DISABLED);
        },
        enable:function() {
            this.el.set("state", Select.ENABLED);
        },

        _vChange:function(ev) {
            var self = this,
                editor = self.get("editor"),
                FORMAT_STYLES = self.get("formatStyles"),
                v = ev.newVal,
                pre = ev.prevVal;
            editor.fire("save");
            if (v != pre) {
                FORMAT_STYLES[v].apply(editor.document);
            } else {
                FORMAT_STYLES["p"].apply(editor.document);
                self.el.set("value", "p");
            }
            editor.fire("save");
        },

        _selectionChange:function(ev) {
            var self = this,
                editor = self.get("editor"),
                FORMAT_STYLES = self.get("formatStyles"),
                elementPath = ev.path;
            // For each element into the elements path.
            // Check if the element is removable by any of
            // the styles.
            for (var value in FORMAT_STYLES) {
                if (FORMAT_STYLES[ value ].checkActive(elementPath)) {
                    self.el.set("value", value);
                    return;
                }
            }

            //self.el.reset("value");
        }
    });
    KE.Format = Format;

});
