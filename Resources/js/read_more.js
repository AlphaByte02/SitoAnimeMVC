$(document).ready(function () {
    if ($("p.descrizione").innerHeight() >= 225) {
        // $("p.descrizione").after("<button pressed='false' class='show_hide float-right btn btn-link' style='color: #1875d8;margin-right: 10%;text-decoration: none'>Read More</button>")
        $("p.descrizione").after(
            "<button pressed='false' class='show_hide right waves-effect waves-light btn-small' style='margin-right: 10%;text-decoration: none'>Read More</button>"
        );
    }
    $(".show_hide").on("click", function () {
        if ($(this).attr("pressed") == "false") {
            $(this).prev().removeClass("descrizione-hide");
            $(this).prev().addClass("descrizione-show");
            $(this).text("Read Less");
            $(this).attr("pressed", "true");
        } else {
            $(this).prev().removeClass("descrizione-show");
            $(this).prev().addClass("descrizione-hide");
            $(this).text("Read More");
            $(this).attr("pressed", "false");
        }
    });
});
