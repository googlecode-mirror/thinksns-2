/**
 * bangpai source editor for kissy editor
 * @author: yiminghe@gmail.com
 */
KISSY.Editor.add("bangpai-sourcearea", function(editor) {
    var KE = KISSY.Editor,
        S = KISSY,
        Node = S.Node,
        UA = S.UA;
    //firefox 3.5 不支持，有bug
    if (UA.gecko < 1.92) return;
    KE.use("bangpai-sourcearea/support", function() {
        var a=new KE.BangPaiSourceArea(editor);
        editor.on("destroy",function(){
           a.destroy();
        });
    });
},
{
    attach:false
});
