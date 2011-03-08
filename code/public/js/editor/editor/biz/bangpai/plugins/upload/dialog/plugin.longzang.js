KISSY.Editor.add("bangpai-upload/dialog", function(editor) {
    var S = KISSY,
        KE = S.Editor,
        UA = S.UA,
        BangPaiUpload = KE.BangPaiUpload,
        DOM = S.DOM,
        JSON = S.JSON,
        PIC_NUM_LIMIT = 15,
        PIC_NUM_LIMIT_WARNING = "系统将只保留 n 张",
        PIC_SIZE_LIMIT = 1000,
        PIC_SIZE_LIMIT_WARNING = "图片太大，请压缩至 n M以下",
        Node = S.Node,
        Overlay = KE.SimpleOverlay,
        KEY = "Multi-Upload-Save",
        store = window[KE.STORE],
        movie = KE.Config.base +
            KE.Utils.debugUrl("plugins/uploader/uploader.longzang.swf?t=" +
                encodeURIComponent("2010-11-05 10:01:17") //+
                // "&rand=" + (+new Date())
                ),
        progressBars = {},
        name = "ke-bangpai-upload",
        FLASH_VERSION_REQUIRED = "10.0.0";
    if (!BangPaiUpload.Dialog) {
        (function() {
            DOM.addStyleSheet(
                ".ke-upload-btn-wrap {" +
                    "position:relative;" +
                    "padding:15px 20px 15px 10px;" +
                    "zoom:1;" +
                    "}" +

                    ".ke-upload-list {" +
                    "width:100%;" +
                    "}" +

                    ".ke-upload-list th {" +
                    "border-top:1px solid #c1c8d1;" +
                    "background-color: #E7E9ED;" +
                    "background: -webkit-gradient(linear, left top, left bottom, from(#E7E9ED), to(#F1F4F7));" +
                    "background: -moz-linear-gradient(top, #E7E9ED, #F1F4F7);" +
                    "}" +

                    ".ke-upload-list td,.ke-upload-list th {" +
                    "padding:0em;" +
                    "height:26px;" +
                    "line-height:26px;" +
                    "text-align:center;" +
                    "border-bottom:1px solid #c1c8d1;" +
                    "}", "ke-BangPaiUpload"
                );

            function BangPaiUploadDialog(editor) {
                this.editor = editor;
                KE.Utils.lazyRun(this, "_prepareShow", "_realShow");
            }

            S.augment(BangPaiUploadDialog, {
                _prepareShow:function() {
                    var self = this,
                        editor = self.editor,
                        bangpaiCfg = editor.cfg["pluginConfig"]["bangpai-upload"];

                    self.dialog = new Overlay({
                        title:"批量上传",
                        mask:false,
                        draggable:"all",
                        //height:"500px",
                        focusMgr:false,
                        width:"600px"
                    });

                    var d = self.dialog;
                    d.foot.hide();
                    var bangpaiUploaderHolder = d.body,
                        btnHolder = new Node(
                            "<div class='ke-upload-btn-wrap'>" +
                                "<span " +
                                "style='" +
                                "margin:0 15px 0 0px;" +
                                "color:#969696;" +
                                "display:inline-block;" +
                                "vertical-align:middle;" +
                                "width:469px;" +
                                "'></span>" +
                                "</div>").appendTo(bangpaiUploaderHolder),
                        listWrap = new Node("<div style='display:none'>")
                            .appendTo(bangpaiUploaderHolder),
                        btn = new KE.TripleButton({
                            text:"浏&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;览",
                            cls:"ke-button",
                            container:btnHolder
                        }),

                        listTableWrap = new Node("<div>" +
                            "<table class='ke-upload-list'>" +
                            "<thead>" +
                            "<tr>" +
                            "<th>" +
                            "序号" +
                            "</th>" +
                            "<th>" +
                            "图片" +
                            "</th>" +
                            "<th>" +
                            "大小" +
                            "</th>" +
                            "<th style='width:35%'>" +
                            "上传进度" +
                            "</th>" +
                            "<th>" +
                            "图片操作" +
                            "</th>" +
                            "</tr>" +
                            "</thead>" +
                            "<tbody>" +
                            "</tbody>" +
                            "</table>" +
                            "</div>").appendTo(listWrap),
                        list = listTableWrap.one("tbody"),
                        upHolder = new Node("<p " +
                            "style='" +
                            "margin:15px 15px 30px; 0;" +
                            "text-align:right;" +
                            "'>" +
                            "<a class='ke-button ke-bangpiaupload-delall'" +
                            " style='margin-right:20px;'>清空列表</a>" +
                            "<a class='ke-button ke-bangpiaupload-ok'>确定上传</a>" +
                            "<a class='ke-button ke-bangpiaupload-insertall'" +
                            " style='margin-left:20px;'>全部插入</a>" +
                            "</p>")
                            .appendTo(listWrap),
                        up = upHolder.one(".ke-bangpiaupload-ok"),
                        insertAll = upHolder.one(".ke-bangpiaupload-insertall"),
                        delAll = upHolder.one(".ke-bangpiaupload-delall"),
                        fid = S.guid(name),
                        statusText = new Node("<span>")
                            .insertBefore(upHolder[0].firstChild);


                    self._sizeLimit = bangpaiCfg.sizeLimit || PIC_SIZE_LIMIT;
                    self._numberLimit = bangpaiCfg.numberLimit || PIC_NUM_LIMIT;

                    var TIP = "允许用户同时上传" +
                        self._numberLimit
                        + "张图片，单张图片容量不超过" +
                        self._sizeLimit / 1000
                        + "M";

                    if (!UA.fpvGEQ(FLASH_VERSION_REQUIRED)) {
                        TIP = "您的flash插件版本过低，该功能不可用，" +
                            "请<a href='http://get.adobe.com/cn/flashplayer/'" +
                            " target='_blank'>点此升级</a>";
                    }

                    btn.disable();
                    self.tipSpan = btnHolder.one("span");
                    self.tipSpan.html(TIP);
                    if (!UA.fpvGEQ(FLASH_VERSION_REQUIRED)) {
                        return;
                    }
                    if (bangpaiCfg.extraHtml) {
                        listTableWrap.append(bangpaiCfg.extraHtml);
                    }

                    self._list = list;
                    self._listTable = list.parent("table");
                    self._listWrap = listWrap;
                    self._ds = bangpaiCfg.serverUrl;
                    self._dsp = bangpaiCfg.serverParams || {};
                    self._fileInput = bangpaiCfg.fileInput || "Filedata";
                    self.statusText = statusText;
                    self.btn = btn;
                    self.up = up;


                    var boffset = btn.el.offset(),
                        fwidth = btn.el.width() * 2,
                        fheight = btn.el.height() * 1.5,
                        flashPos = new Node("<div style='" +
                            ("position:absolute;" +
                                "width:" + fwidth + "px;" +
                                "height:" + fheight + "px;" +
                                "z-index:" + editor.baseZIndex(9999) + ";")
                            + "'>").appendTo(btnHolder);
                    //swfready 要求可见
                    flashPos.offset(boffset);
                    self.flashPos = flashPos;
                    var uploader = new KE.FlashBridge({
                        movie:movie,
                        ajbridge:true,
                        methods:[
                            "getReady",
                            //"cancel",
                            "removeFile",
                            "lock",
                            "unlock",
                            "setAllowMultipleFiles",
                            "setFileFilters",
                            "uploadAll"],
                        holder:flashPos,
                        attrs:{
                            width:fwidth ,
                            height:fheight
                        },
                        params:{
                            wmode:"transparent"
                        },
                        flashVars:{
                            allowedDomain : location.hostname,
                            btn:true,
                            hand:true
                            //menu:true
                        }
                    });

                    self.uploader = uploader;

                    insertAll.on("click", function() {
                        var trs = list.all("tr");
                        for (var i = 0; i < trs.length; i++) {
                            var tr = new Node(trs[i]),
                                url = tr.attr("url");
                            if (url) {
                                editor.insertElement(new Node("<p>&nbsp;<img src='" +
                                    url + "'/>&nbsp;</p>", null, editor.document));
                                self._removeTrFile(tr);
                            }
                        }
                        if (url) {
                            listWrap.hide();
                            d.hide();
                        }
                    });

                    delAll.on("click", function() {
                        var trs = list.all("tr");
                        for (var i = 0; i < trs.length; i++) {
                            var tr = new Node(trs[i]);
                            self._removeTrFile(tr);
                        }
                        listWrap.hide();
                    });

                    list.on("click", function(ev) {
                        var target = new Node(ev.target),tr;
                        ev.halt();
                        if (target.hasClass("ke-upload-insert")) {
                            tr = target.parent("tr"),url = tr.attr("url");
                            editor.insertElement(new Node("<img src='" +
                                url + "'/>", null, editor.document));
                        }
                        if (
                            target.hasClass("ke-upload-delete")
                                ||
                                target.hasClass("ke-upload-insert")
                            ) {
                            tr = target.parent("tr");
                            self._removeTrFile(tr);
                        }
                    });

                    uploader.on("fileSelect", self._onSelect, self);
                    uploader.on("uploadStart", self._onUploadStart, self);
                    uploader.on("uploadProgress", self._onProgress, self);
                    //uploader.on("uploadComplete", self._onComplete, self);
                    uploader.on("uploadCompleteData", self._onUploadCompleteData, self);
                    uploader.on("contentReady", self._ready, self);
                    uploader.on("uploadError", self._uploadError, self);


                    //从本地恢复已上传记录
                    self._restore();
                },
                _removeTrFile:function(tr) {
                    var self = this,
                        fid = tr.attr("fid"),
                        uploader = self.uploader;
                    if (fid) {
                        try {
                            uploader.removeFile(fid);
                        } catch(e) {
                        }
                    }
                    if (progressBars[fid]) {
                        progressBars[fid].destroy();
                        delete progressBars[fid];
                    }
                    tr._4e_remove();
                    self.denable();
                    self._syncStatus();
                },

                _realShow:function() {
                    this.dialog.show();
                },
                show:function() {
                    this._prepareShow();
                },
                _uploadError:function(ev) {
                    var self = this,
                        uploader = self.uploader,
                        id = ev.id || (ev.file && ev.file.id);
                    if (!id) {
                        S.log(ev);
                        return;
                    }
                    var tr = self._getFileTr(id),
                        bar = progressBars[id],
                        status = ev.status;

                    uploader.removeFile(id);
                    if (!ev._custom) {
                        S.log(status);
                        status = "服务器出错或格式不正确";
                    }
                    if (tr) {
                        bar && bar.destroy();
                        tr.one(".ke-upload-progress").html("<div " +
                            "style='color:red;'>" +
                            status +
                            "</div>");
                    }
                },
                _getFileTr:function(id) {
                    var self = this,
                        list = self._list,
                        trs = list.all("tr");
                    for (var i = 0; i < trs.length; i++) {
                        var tr = new Node(trs[i]);
                        if (tr.attr("fid") == id) {
                            return tr;
                        }
                    }
                },
                _onUploadStart:function() {
                    //console.log("_onUploadStart", ev);
                },
                _onComplete:function() {
                    //console.log("_onComplete", ev);
                },
                _onUploadCompleteData:function(ev) {
                    var self = this,
                        data = S.trim(ev.data).replace(/\r|\n/g, ""),
                        id = ev.file.id;
                    S.log(data);
                    if (!data) return;
                    data = JSON.parse(data);
                    if (data.error) {
                        self._uploadError({
                            id:id,
                            _custom:1,
                            status:data.error
                        });
                        return;
                    }
                    var tr = self._getFileTr(id);
                    if (tr) {
                        tr.one(".ke-upload-insert").show();
                        tr.attr("url", data.imgUrl);
                    }
                    //self.denable();
                    self._syncStatus();

                },
                _onProgress:function(ev) {
                    //console.log("_onProgress", ev);
                    var fid = ev.file.id,
                        progess = Math.floor(ev.bytesLoaded * 100 / ev.bytesTotal),
                        bar = progressBars[fid];
                    bar && bar.set("progress", progess);

                },
                ddisable:function() {
                    var self = this;
                    self.uploader.lock();
                    self.btn.disable();
                    self.flashPos.offset({
                        left:-9999,
                        top:-9999
                    });
                },
                denable:function() {
                    var self = this;
                    self.uploader.unlock();
                    self.btn.enable();
                    self.flashPos.offset(self.btn.el.offset());
                },
                _syncStatus:function() {
                    var self = this,
                        list = self._list,
                        seq = 1,
                        trs = list.all("tr");
                    if (trs.length == 0) {
                        self._listWrap.hide();
                    } else {
                        list.all(".ke-upload-seq").each(function(n) {
                            n.html(seq++);
                        });
                        var wait = 0;
                        for (var i = 0; i < trs.length; i++) {
                            var tr = new Node(trs[i]);
                            if (!tr.attr("url")) wait++;
                        }
                        self.statusText.html("队列中剩余" + wait + "张图片"
                            + "，点击确定上传，开始上传。 "
                            );
                    }
                    //当前已上传的文件同步到本地
                    self._save();
                },
                //当前已上传的图片保存下来
                _restore:function() {
                    var self = this,
                        data = store.getItem(KEY),
                        tbl = self._list[0];
                    if (!data) return;
                    data = JSON.parse(decodeURIComponent(data));
                    for (var i = 0; i < data.length; i++) {
                        var d = data[i];
                        d.complete = 1;
                        d.fid = "restore_" + i;
                        var r = self._createFileTr(tbl, d);
                        r.attr("url", d.url);
                    }
                    if (d) {
                        self._listWrap.show();
                        self._syncStatus();
                    }
                },
                _save:function() {
                    var self = this,
                        list = self._list,
                        trs = list.all("tr"),
                        data = [];
                    for (var i = 0; i < trs.length; i++) {
                        var tr = new Node(trs[i]),
                            url = tr.attr("url");
                        if (url) {
                            var size = tr.one(".ke-upload-filesize").html(),
                                name = tr.one(".ke-upload-filename").html();
                            data.push({
                                name:name,
                                size:size,
                                url:url
                            });
                        }
                    }
                    store.setItem(KEY, encodeURIComponent(JSON.stringify(data)));
                },
                _getFilesSize:function(files) {
                    var n = 0;
                    for (var i in files) {
                        if (files.hasOwnProperty(i))
                            n++;
                    }
                    return n;
                },
                _createFileTr:function(tbl, f) {

                    /*
                     chrome not work !
                     kissy bug?
                     var row = new Node("<tr fid='" + id + "'>"
                     + "<td class='ke-upload-seq'>"
                     + "</td>"
                     + "<td>"
                     + f.name
                     + "</td>"
                     + "<td>"
                     + size
                     + "k</td>" +
                     "<td class='ke-upload-progress'>" +
                     "</td>" +
                     "<td>" +
                     "<a href='#' " +
                     "class='ke-upload-insert' " +
                     "style='display:none'>" +
                     "[插入]</a> &nbsp; " +
                     "<a href='#' class='ke-upload-delete'>[删除]</a> &nbsp; "
                     +
                     "</td>"
                     + "</tr>").appendTo(list);
                     */


                    var self = this,
                        id = f.fid,
                        row = tbl.insertRow(-1);
                    DOM.attr(row, "fid", id);
                    var cell = row.insertCell(-1);
                    DOM.attr(cell, "class", 'ke-upload-seq');
                    cell = row.insertCell(-1);
                    if (f.name.length > 18) {
                        f.name = f.name.substring(0, 18) + "...";
                    }
                    DOM.html(cell, f.name);
                    DOM.attr(cell, "class", 'ke-upload-filename');
                    cell = row.insertCell(-1);
                    DOM.html(cell, f.size);
                    DOM.attr(cell, "class", 'ke-upload-filesize');
                    cell = row.insertCell(-1);
                    DOM.attr(cell, "class", 'ke-upload-progress');
                    cell = row.insertCell(-1);
                    DOM.html(cell, "<a href='#' " +
                        "class='ke-upload-insert' " +
                        (f.complete ? "" : "style='display:none'") +
                        ">" +
                        "[插入]</a> &nbsp; " +
                        "<a href='#' class='ke-upload-delete'>" +
                        "[删除]" +
                        "</a> &nbsp;");
                    row = new Node(row);

                    var prog = row.one(".ke-upload-progress");
                    if (parseInt(f.size) > self._sizeLimit) {
                        self._uploadError({
                            id:id,
                            _custom:1,
                            status:PIC_SIZE_LIMIT_WARNING
                                .replace(/n/, self._sizeLimit / 1000)
                        });

                        self.uploader.removeFile(id);

                    } else {
                        progressBars[id] = new KE.ProgressBar({
                            container:row.one(".ke-upload-progress") ,
                            width:"100px",
                            height:"15px"
                        });
                        if (f.complete) {
                            progressBars[id].set("progress", 100);
                        }
                    }

                    return row;
                },
                _onSelect:function(ev) {
                    var self = this,
                        uploader = self.uploader,
                        list = self._list,
                        curNum = 0,
                        //当前队列的所有文件，连续选择的话累计！！！
                        files = ev.fileList,
                        available = self._numberLimit,i;

                    if (files) {


                        //去除已经 ui 显示出来的
                        var trs = list.children("tr");
                        for (i = 0; i < trs.length; i++) {
                            var tr = trs[i],fid = DOM.attr(tr, "fid");
                            fid && files[fid] && (delete files[fid]);
                        }
                        //限额-目前ui的
                        available = self._numberLimit - trs.length;

                        var l = self._getFilesSize(files);

                        if (l > available) {
                            alert(PIC_NUM_LIMIT_WARNING.replace(/n/, self._numberLimit));
                        }

                        if (l >= available) {
                            self.ddisable();
                        }

                        self._listWrap.show();
                        var tbl = self._list[0];
                        //files 是这次新选择的啦！
                        //新选择的随即删除一些
                        for (i in files) {
                            if (!files.hasOwnProperty(i)) continue;
                            curNum ++;
                            var f = files[i],
                                size = Math.floor(f.size / 1000),
                                id = f.id;

                            if (curNum > available) {

                                uploader.removeFile(id);
                                continue;
                            }
                            self._createFileTr(tbl, {
                                size:size + "k",
                                fid:id,
                                name:f.name
                            });
                        }
                        self._syncStatus();
                    }
                },

                _ready:function() {
                    var self = this,
                        uploader = self.uploader,
                        up = self.up,
                        btn = self.btn,
                        flashPos = self.flashPos,
                        normParams = KE.Utils.normParams;
                    if ("ready" != uploader.getReady()) {
                        self.tipSpan.html("您的浏览器不支持该功能，" +
                            "请升级当前浏览器，" +
                            "并同时 <a href='http://get.adobe.com/cn/flashplayer/'" +
                            " target='_blank'>点此升级</a> flash 插件");
                        flashPos.offset({
                            left:-9999,
                            top:-9999
                        });
                        return;
                    }
                    btn.enable();
                    flashPos.offset(btn.el.offset());
                    uploader.setAllowMultipleFiles(true);
                    uploader.setFileFilters([
                        {
                            ext:"*.jpeg;*.jpg;*.png;*.gif",
                            desc:"图片文件( png,jpg,jpeg,gif )"
                        }
                    ]);
                    up.detach();
                    up.on("click", function(ev) {
                        ev.halt();
                        uploader.uploadAll(self._ds,
                            "POST",
                            normParams(self._dsp),
                            self._fileInput);
                    });
                }
            });

            KE.BangPaiUpload.Dialog = BangPaiUploadDialog;
        })();
    }


    editor.addDialog("bangpai-upload/dialog",
        new KE.BangPaiUpload.Dialog(editor));
}, {
    attach:false
});