/**
 * forecolor support for kissy editor
 * @author : yiminghe@gmail.com
 */
KISSY.Editor.add("forecolor", function(editor) {
    var S = KISSY,
        KE = S.Editor,
        ColorSupport = KE.ColorSupport;
    var COLOR_STYLES = {
        element        : 'span',
        styles        : { 'color' : '#(color)' },
        overrides    : [
            { element : 'font', attributes : { 'color' : null } }
        ]
    };
    editor.addPlugin(function() {
        new ColorSupport({
            editor:editor,
            styles:COLOR_STYLES,
            title:"文本颜色",
            contentCls:"ke-toolbar-color",
            text:"color"
        });
    });
});
