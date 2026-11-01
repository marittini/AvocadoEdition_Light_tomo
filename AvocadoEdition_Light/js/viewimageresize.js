(function($) {
    $.fn.viewimageresize = function(selector)
    {
        var cfg = {
                selector: "img"
            };

        if(typeof selector == "object") {
            cfg = $.extend(cfg, selector);
        } else {
            if(selector) {
                cfg = $.extend({ selector: selector });
            }
        }

        // 이모티콘(img.emoticon)은 크기를 따로 정하므로 건드리지 않는다
        var $img = this.find(cfg.selector).not(".emoticon");
        var $this = this;

        $img.removeAttr("height")
            .css("height", "");

        function image_resize()
        {
            var width = $this.width();

            $img.each(function() {
                // 아직 안 불러온 그림(너비 0)은 건너뛴다. 그대로 두면 width="0"이 붙어 사라진다.
                if(!$(this).width() && $(this).data("width") == undefined) return;
                if($(this).data("width") == undefined)
                    $(this).data("width", $(this).width());

                if($(this).data("width") > width) {
                    $(this).removeAttr("width")
                           .removeAttr("height")
                           .css("width","")
                           .css("height", "");

                    if($(this).data("width") > width) {
                        $(this).css("width", "100%");
                    }
                } else {
                    $(this).attr("width", $(this).data("width"));
                }
            });
        }

        $(window).on("load", function() {
            image_resize();
        });
        $img.on("load", function() {
            image_resize();
        });

        $(window).on("resize", function() {
            image_resize();
        });
    }

    $.fn.viewimageresize2 = function(selector)
    {
        var cfg = {
                selector: "img"
            };

        if(typeof selector == "object") {
            cfg = $.extend(cfg, selector);
        } else {
            if(selector) {
                cfg = $.extend({ selector: selector });
            }
        }

        var $img = this.find(cfg.selector).not(".emoticon");
        var $this = this;

        function image_resize()
        {
            var width = $this.width();

            $img.each(function() {
                $(this).removeAttr("width")
                       .removeAttr("height")
                       .css("width","")
                       .css("height", "");

                if(!$(this).width() && $(this).data("width") == undefined) return;
                if($(this).data("width") == undefined)
                    $(this).data("width", $(this).width());

                if($(this).data("width") > width) {
                    $(this).css("width", "100%");
                }
            });
        }

        $(window).on("resize", function() {
            image_resize();
        });

        image_resize();
    }
}(jQuery));