(function($){
    var gConfig = {
        cls: {
            subShown: 'sub-shown',
            touched:  'touched'
        },
        sel: {
            menubar: '#menubar',
            menuBtn: '#menu-btn',
            submenu: '.submenu'
        },
        respWidth: 566
    };

    $(document).ready(init);

    function init() {
        var respMenuShown = false;

        // hide shown menu in responsive mode,
        //  and all sub-menus on document clicks
        $(document).on('click', function() {
            if (respMenuShown && isMobile()) {
                $(gConfig.sel.menubar).hide('fast');
                respMenuShown = false;
            }
            $(gConfig.sel.menubar).find('li')
                                 .removeClass(gConfig.cls.subShown);
        });

        // handle link clicks
        $(gConfig.sel.menubar).on('click', 'li a', function() {
            var $li = $(this).parent('li'),
                $sm = $li.find(gConfig.sel.submenu),
                loadLink = true;

            // if the list item has a hidden submenu, show it...
            if ($sm.length && !$li.hasClass(gConfig.cls.subShown)) {
                $li.addClass(gConfig.cls.subShown);
                loadLink = false;
            }

            //...otherwise, load the link
            return loadLink;
        });

        // handle menu button click: toggle menu visibility, set state var
        $(gConfig.sel.menuBtn).on('click', function() {
            $(gConfig.sel.menubar).toggle('fast');
            respMenuShown = !(respMenuShown);
            return false;
        });

        // set touch state on menu button
        $(gConfig.sel.menuBtn).on('touchstart', function() {
            $(this).addClass(gConfig.cls.touched);
        });
        $(gConfig.sel.menuBtn).on('touchend touchmove', function() {
            $(this).removeClass(gConfig.cls.touched);
        });

        // hide or show menubar on window resize
        $(window).on('resize', function() {
            $(gConfig.sel.menubar).toggle((!isMobile() || respMenuShown));
        });

    }

    function isMobile() {
        return ($(window).width() <= gConfig.respWidth);
    }

})(window.jQuery);
