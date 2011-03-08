/**
 * background-color support for kissy editor
 * @author : yiminghe@gmail.com
 */
KISSY.Editor.add("bgcolor", function(editor) {
    var S = KISSY,
        KE = S.Editor,
        ColorSupport = KE.ColorSupport,
        colorButton_backStyle = {
            element        : 'span',
            styles        : { 'background-color' : '#(color)' }
        };


    editor.addPlugin(function() {
        new ColorSupport({
            editor:editor,
            styles:colorButton_backStyle,
            title:"背景颜色",
            contentCls:"ke-toolbar-bgcolor",
            text:"bgcolor"
        });
    });
});
