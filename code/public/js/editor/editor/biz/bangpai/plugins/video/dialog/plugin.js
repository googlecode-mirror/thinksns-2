KISSY.Editor.add("bangpai-video/dialog", function(editor) {
    var S = KISSY,
        KE = S.Editor;
    KE.use("bangpai-video/dialog/support", function() {
        editor.addDialog("bangpai-video/dialog", new KE.BangPaiVideo.Dialog(editor));
    });
});