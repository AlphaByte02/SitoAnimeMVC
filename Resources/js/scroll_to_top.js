if ($(".back-to-top").length) {
    var scrollTrigger = 300, // px
        backToTop = function () {
            var scrollTop = $(window).scrollTop();
            if (scrollTop > scrollTrigger) {
                $(".back-to-top").removeClass("scale-out");
            } else {
                $(".back-to-top").addClass("scale-out");
            }
        };
    backToTop();

    $(window).on("scroll", function () {
        backToTop();
    });

    $(".back-to-top").on("click", function (e) {
        e.preventDefault();
        $("html,body").animate(
            {
                scrollTop: 0,
            },
            700
        );
    });
}
