KISSY.Editor.add("colorsupport/advanced", function(editor) {

    var S = KISSY,
        KE = S.Editor;


    if (!KE.ColorSupport.ColorPicker) {
        (function() {
            var map = KE.Utils.map,
                DOM = S.DOM;
            DOM.addStyleSheet("" +
                ".ke-color-advanced-picker-left {" +
                "float:left;" +
                "display:inline;" +
                "margin-left:10px;" +
                "}" +

                ".ke-color-advanced-picker-right {" +
                "float:right;" +
                "width:50px;" +
                "display:inline;" +
                "margin:13px 10px 0 0;" +
                "cursor:crosshair;" +
                "}" +
                "" +
                ".ke-color-advanced-picker-right a {" +
                "height:2px;" +
                "line-height:0;" +
                "font-size:0;" +
                "display:block;" +
                "}" +
                "" +

                ".ke-color-advanced-picker-left ul{" +
                "float:left;" +
                "}" +
                ".ke-color-advanced-picker-left li,.ke-color-advanced-picker-left a{" +
                "overflow:hidden;" +
                "width:15px;" +
                "height:16px;" +
                "line-height:0;" +
                "font-size:0;" +
                "display:block;" +
                "}" +
                ".ke-color-advanced-picker-left a:hover{" +
                "width:13px;height:13px;border:1px solid white;" +
                "}" +
                "" +
                ".ke-color-advanced-indicator {" +
                "margin-left:10px;" +
                "padding:2px 34px;" +
                "}", "ke-color-advanced");

            //获取颜色数组
            function GetData(color) {
                if (S.isArray(color)) return color;
                var re = RegExp;
                if (/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i.test(color)) {
                    //#rrggbb
                    return map([ re.$1, re.$2, re.$3 ], function(x) {
                        return parseInt(x, 16);
                    });
                } else if (/^#([0-9a-f])([0-9a-f])([0-9a-f])$/i.test(color)) {
                    //#rgb
                    return map([ re.$1, re.$2, re.$3 ], function(x) {
                        return parseInt(x + x, 16);
                    });
                } else if (/^rgb\((.*),(.*),(.*)\)$/i.test(color)) {
                    //rgb(n,n,n) or rgb(n%,n%,n%)
                    return map([ re.$1, re.$2, re.$3 ], function(x) {
                        return x.indexOf("%") > 0 ? parseFloat(x, 10) * 2.55 : x | 0;
                    });
                }
                return undefined;
            }

            //refer:http://www.cnblogs.com/cloudgamer/archive/2009/03/11/color.html
            //获取颜色梯度方法
            var ColorGrads = (function() {
                //获取颜色梯度数据
                function GetStep(start, end, step) {
                    var colors = [];
                    start = GetColor(start);
                    end = GetColor(end),
                        stepR = (end[0] - start[0]) / step,
                        stepG = (end[1] - start[1]) / step,
                        stepB = (end[2] - start[2]) / step;
                    //生成颜色集合
                    for (var i = 0, r = start[0], g = start[1], b = start[2]; i < step; i++) {
                        colors[i] = [r, g, b];
                        r += stepR;
                        g += stepG;
                        b += stepB;
                    }
                    colors[i] = end;
                    //修正颜色值
                    return map(colors, function(x) {
                        return map(x, function(x) {
                            return Math.min(Math.max(0, Math.floor(x)), 255);
                        });
                    });
                }

                //获取颜色数据
                var frag;

                function GetColor(color) {
                    var ret = GetData(color);
                    if (ret === undefined) {
                        if (!frag) {
                            frag = document.createElement("textarea");
                            frag.style.display = "none";
                            document.body.insertBefore(frag, document.body.childNodes[0]);
                        }
                        try {
                            frag.style.color = color;
                        } catch(e) {
                            return [0, 0, 0];
                        }

                        if (document.defaultView) {
                            ret = GetData(document.defaultView.getComputedStyle(frag, null).color);
                        } else {
                            color = frag.createTextRange().queryCommandValue("ForeColor");
                            ret = [ color & 0x0000ff, (color & 0x00ff00) >>> 8, (color & 0xff0000) >>> 16 ];
                        }
                    }
                    return ret;
                }


                return function(colors, step) {
                    var ret = [], len = colors.length;
                    if (step === undefined) {
                        step = 20;
                    }
                    if (len == 1) {
                        ret = GetStep(colors[0], colors[0], step);
                    } else if (len > 1) {
                        for (var i = 0, n = len - 1; i < n; i++) {
                            var t = step[i] || step;
                            var steps = GetStep(colors[i], colors[i + 1], t);
                            i < n - 1 && steps.pop();
                            ret = ret.concat(steps);
                        }
                    }
                    return ret;
                }
            })();

            function padding2(x) {
                x = "0" + x;
                var l = x.length;
                return x.slice(l - 2, l);
            }

            function Hex(c) {
                c = GetData(c);
                return "#" + padding2(c[0].toString(16))
                    + padding2(c[1].toString(16))
                    + padding2(c[2].toString(16));
            }

            var pickerHtml = "<ul>" +
                map(ColorGrads([ "red", "orange", "yellow", "green", "cyan", "blue", "purple" ], 5), function(x) {
                    return map(ColorGrads([ "white", "rgb(" + x.join(",") + ")" ,"black" ], 5), function(x) {
                        return "<li><a style='background-color" + ":" + Hex(x) + "' href='#'></a></li>";
                    }).join("");
                }).join("</ul><ul>") + "</ul>";


            var panelHtml = "<div class='ke-color-advanced-picker'>" +
                "<div class='ks-clear'>" +
                "<div class='ke-color-advanced-picker-left'>" +
                pickerHtml +
                "</div>" +
                "<div class='ke-color-advanced-picker-right'>" +
                "</div>" +
                "</div>" +
                "<div style='padding:10px;'>" +
                "<label>" +
                "颜色值： " +
                "<input style='width:100px' class='ke-color-advanced-value'/>" +
                "</label>" +
                "<span class='ke-color-advanced-indicator'></span>" +
                "</div>" +
                "</div>";

            var footHtml = "<a class='ke-button ke-color-advanced-ok'>确定</a>&nbsp;&nbsp;&nbsp;" +
                "<a class='ke-button  ke-color-advanced-cancel'>取消</a>";

            function ColorPicker() {
                this._init();
            }

            S.augment(ColorPicker, {
                _init:function() {
                    var self = this;
                    self.win = new KE.SimpleOverlay({
                        mask:true,
                        title:"颜色拾取器",
                        width:"550px"
                    });
                    var win = self.win,
                        body = win.body,
                        foot = win.foot;
                    body.html(panelHtml);
                    foot.html(footHtml);
                    var indicator = body.one(".ke-color-advanced-indicator");
                    var indicatorValue = body.one(".ke-color-advanced-value");
                    var left = body.one(".ke-color-advanced-picker-left");
                    var right = body.one(".ke-color-advanced-picker-right");
                    var ok = foot.one(".ke-color-advanced-ok");
                    var cancel = foot.one(".ke-color-advanced-cancel");
                    ok.on("click", function() {
                        //先隐藏窗口，使得编辑器恢复焦点，恢复原先range
                        self.hide();
                        self.cmd._applyColor(indicatorValue.val());
                    });
                    cancel.on("click", function() {
                        self.hide();
                    });
                    body.on("click", function(ev) {
                        ev.halt();
                        var t = ev.target;
                        if (DOM._4e_name(t) == "a") {
                            var c = Hex(DOM.css(t, "background-color"));
                            if (left._4e_contains(t))self._detailColor(c);
                            indicatorValue.val(c);
                            indicator.css("background-color", c);
                        }
                    });

                    var defaultColor = "#FF9900";
                    self._detailColor(defaultColor);
                    indicatorValue.val(defaultColor);
                    indicator.css("background-color", defaultColor);
                },

                _detailColor:function(color) {
                    var self = this,
                        win = self.win,
                        body = win.body,
                        detailPanel = body.one(".ke-color-advanced-picker-right");

                    detailPanel.html(map(ColorGrads(["#ffffff",color,"#000000"], 40), function(x) {
                        return "<a style='background-color:" + Hex(x) + "'></a>";
                    }).join(""));
                },
                show:function(cmd) {
                    this.cmd = cmd;
                    this.win.show();
                },
                hide:function() {
                    this.win.hide();
                }
            });

            KE.ColorSupport.ColorPicker = ColorPicker;
        })();
    }


    var colorPicker = new KE.ColorSupport.ColorPicker();

    editor.addDialog("colorsupport/advanced", {
        show:function(cmd) {
            colorPicker.show(cmd);
        },
        hide:function() {
            colorPicker.hide();
        }
    });

});