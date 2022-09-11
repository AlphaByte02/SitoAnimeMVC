$(document).ready(function () {
    $("a").on("click", function (event) {
        if (this.hash !== "") {
            event.preventDefault();
            let hash = this.hash;
            hash = hash.substring(1);

            if (document.getElementById(hash) != null) {
                $("html, body").animate(
                    {
                        scrollTop:
                            document.getElementById(hash).offsetTop -
                            (document.getElementById("navbar") != null
                                ? document.getElementById("navbar").offsetHeight
                                : 0), // $(hash).offset().top - 55
                    },
                    700
                );
            }
        }
    });
});
