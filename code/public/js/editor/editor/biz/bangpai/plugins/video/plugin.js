/**
 * biz plugin , video about ku6,youku,tudou for bangpai
 * @author:yiminghe@gmail.com
 */
KISSY.Editor.add("bangpai-video", function(editor) {
    var S = KISSY,
        KE = S.Editor,
        dataProcessor = editor.htmlDataProcessor,
        dataFilter = dataProcessor && dataProcessor.dataFilter;

    function getProvider(url) {
        for (var i = 0;
             i < provider.length;
             i++) {
            var p = provider[i];
            if (p.reg.test(url)) {
                return p;
            }
        }
        return undefined;
    }

    var provider = [
        {
            reg:/youku\.com/i,
            width:480,
            height:400,
            detect:function(url) {
                var m = url.match(/id_([^.]+)\.html$/);
                if (m) {
                    return "http://player.youku.com/player.php/sid/" + m[1] + "/v.swf";
                }
                m = url.match(/v_playlist\/([^.]+)\.html$/);
                if (m) {
                    return;
                    //return "http://player.youku.com/player.php/sid/" + m[1] + "/v.swf";
                }
                return url;
            }
        },
        {
            reg:/tudou\.com/i,
            width:480,
            height:400,
            detect:function(url) {
                return url;
            }
        },
        {
            reg:/ku6\.com/i,
            width:480,
            height:400,
            detect:function(url) {
                var m = url.match(/show[^\/]*\/([^.]+)\.html$/);
                if (m) {
                    return "http://player.ku6.com/refer/" + m[1] + "/v.swf";
                }
                return url;
            }
        }
    ];

    var cfg = editor.cfg.pluginConfig;
    cfg["bangpai-video"] = cfg["bangpai-video"] || {};
    var videoCfg = cfg["bangpai-video"];
    if (videoCfg['providers']) {
        provider.push.apply(provider, videoCfg['providers']);
    }
    videoCfg.getProvider = getProvider;
    var CLS_VIDEO = "ke_video",
        TYPE_VIDEO = "bangpai-video";

    dataFilter && dataFilter.addRules({
        elements : {
            'object' : function(element) {
                var attributes = element.attributes,i,
                    classId = attributes['classid']
                        && String(attributes['classid']).toLowerCase();
                if (!classId) {
                    // Look for the inner <embed>
                    for (i = 0; i < element.children.length; i++) {
                        if (element.children[ i ].name == 'embed') {
                            if (!KE.Utils.isFlashEmbed(element.children[ i ]))
                                return null;
                            if (getProvider(element.children[ i ].attributes.src)) {
                                return dataProcessor.createFakeParserElement(element,
                                    CLS_VIDEO, TYPE_VIDEO, true);
                            }
                        }
                    }
                    return null;
                }
                for (i = 0; i < element.children.length; i++) {
                    var c = element.children[ i ];
                    if (c.name == 'param' && c.attributes.name == "movie") {
                        if (getProvider(c.attributes.value)) {
                            return dataProcessor.createFakeParserElement(element,
                                CLS_VIDEO, TYPE_VIDEO, true);
                        }
                    }
                }

            },

            'embed' : function(element) {
                if (!KE.Utils.isFlashEmbed(element))
                    return null;
                if (getProvider(element.attributes.src)) {
                    return dataProcessor.createFakeParserElement(element,
                        CLS_VIDEO, TYPE_VIDEO, true);
                }

            }
            //4 比 flash 的优先级 5 高！
        }}, 4);

    editor.addPlugin("bangpai-video", function() {
        var context = editor.addButton("bangpai-video", {
            contentCls:"ke-toolbar-video",
            title:"插入视频" ,
            mode:KE.WYSIWYG_MODE,
            loading:true
        });

        KE.use("bangpai-video/support", function() {
            var bangPaiVideo = new KE.BangPaiVideo(editor);
            context.reload({
                offClick:function() {
                    bangPaiVideo.show();
                },
                destroy:function() {
                    bangPaiVideo.destroy();
                }
            });
        });

        this.destroy = function() {
            context.destroy();
        };
    });

}, {
    attach:false,
    requires:["fakeobjects"]
});