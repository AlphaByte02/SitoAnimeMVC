(function (defaults, $) {
    "use strict";

    $.fn.simpleCheckboxTable = function (options) {
        var settings = $.extend({}, defaults, options);

        return this.each(function (i, elem) {
            var table = $(elem);
            table
                .find("thead th:nth-child(1) input[type='checkbox']")
                .on("change", function () {
                    $(this)
                        .closest("thead")
                        .next("tbody")
                        .find(
                            "td:nth-child(1) input[type='checkbox']:not(:disabled)" +
                                ($(this).is(":checked") ? ":not(:checked)" : "")
                        )
                        .prop("checked", $(this).is(":checked"))
                        .trigger("change");
                })
                .end()
                .find("tbody tr")
                .on("click", function (e) {
                    if (!$(e.target).is("input[type='checkbox']") && !$(e.target).prev().is("input[type='checkbox']")) {
                        var checkbox = $(this).find("td:nth-child(1) input[type='checkbox']:not(:disabled)");
                        checkbox.prop("checked", !checkbox.is(":checked")).trigger("change");
                    }
                })
                .end()
                .find("tbody td:nth-child(1) input[type='checkbox']")
                .on("change", function (e, fireChange = true) {
                    var checkboxs = $(this)
                        .closest("tbody")
                        .find("td:nth-child(1) input[type='checkbox']:not(:disabled)");
                    var thead = $(this).closest("tbody").prev("thead");

                    var uncheckedCheckboxes = checkboxs.filter(":not(:checked)"),
                        checkedCheckboxes = checkboxs.filter(":checked"),
                        isCheckedAll = checkboxs.length == checkedCheckboxes.length,
                        isCheckedSome = checkboxs.length != checkedCheckboxes.length && checkedCheckboxes.length != 0;

                    thead.find("th:nth-child(1) input[type='checkbox']").prop("checked", isCheckedAll);
                    thead
                        .find("th:nth-child(1) input[type='checkbox']")
                        .prop("indeterminate", !isCheckedAll && isCheckedSome);

                    if (fireChange) {
                        settings.onCheckedStateChanged.call(table, $(this));

                        if (isCheckedAll) {
                            settings.onAllChecked.call(table);
                        }
                    }
                })
                .trigger("change", [false])
                .end()
                .find("tbody td a, input[type='checkbox']")
                .on("click", function (e) {
                    e.stopPropagation();
                })
                .end();
        });
    };
})(
    {
        onCheckedStateChanged: function () {},
        onAllChecked: function () {},
    },
    jQuery
);
