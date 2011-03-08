KISSY.Editor.add("bangpai-music/dialog", function(editor) {
    var KE = KISSY.Editor;
    KE.use("bangpai-music/dialog/support", function() {
        editor.addDialog("bangpai-music/dialog", new KE.BangPaiMusic.Dialog(editor));
    });
});