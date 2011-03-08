KISSY.Editor.add("bangpai-upload/dialog", function(editor) {
    var S = KISSY,
        KE = S.Editor;
    KE.use("bangpai-upload/dialog/support", function() {
        editor.addDialog("bangpai-upload/dialog", new KE['BangPaiUpload'].Dialog(editor));
    });
}, {
    attach:false
});