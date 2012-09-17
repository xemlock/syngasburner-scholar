window.jQuery && window.jQuery(function($) {
    $(".scholar-collapsible:not(.scholar-collapsible-disabled)").each(function() {
        var j = $(this),
            h = j.children('.scholar-collapsible-heading'),
            c = j.children('.scholar-collapsible-content');

        if (j.hasClass('scholar-collapsible-collapsed')) {
            c.css('display', 'none');
        }

        h.click(function() {
            if (j.hasClass('animating')) {
                return;
            }
            j.addClass('animating');

            if (j.hasClass('scholar-collapsible-collapsed')) {
                j.removeClass('scholar-collapsible-collapsed').addClass('scholar-collapsible-open');
                c.slideDown(function() {
                    j.removeClass('animating');
                });
            } else {
                j.removeClass('scholar-collapsible-open').addClass('scholar-collapsible-collapsed');
                c.slideUp(function() {
                    j.removeClass('animating');
                });
            }
        });
    });
});
