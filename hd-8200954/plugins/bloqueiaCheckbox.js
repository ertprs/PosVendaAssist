(function ($) {
    $.check = {
        validaCheckbox: function(input) {
            if (typeof input == "undefined") {
                throw new Error("Elemento não encontrado");
            } else if ($(input).attr("type") != "checkbox" && $(input).attr("type") != "radio") {
                throw new Error("Elemento não é um checkbox\\radio");
            } else {
                return true;
            }
        },
        fnCheck: function() {
            try {
                if ($.check.validaCheckbox($(this)) === true) {
                    $(this)[0].checked = true;
                }
            } catch (e) {
                console.log(e.message);
            }

            return $(this);
        },
        fnUncheck: function() {
            try {
                if ($.check.validaCheckbox($(this)) === true) {
                    $(this)[0].checked = false;
                }
            } catch (e) {
                console.log(e.message);
            }

            return $(this);
        },
        fnCheckReadonly: function(value) {
            if (typeof value != "boolean") {
                throw new Error("O valor informado deve ser booleano");
            }

            if (value === true) {
                $(this).data("checkReadonlyState", $(this).is(":checked"));
                $(this)[0].readOnly = true;    

                $(this).bind("change", function() {
                    if ($(this).data("checkReadonlyState") == true) {
                        $(this).check();
                    } else {
                        $(this).uncheck();
                    }
                });
            } else {
                $(this).removeData("checkReadonlyState");
                $(this)[0].readOnly = false;

                $(this).unbind("change");
            }

            return $(this);
        }
    };

    $.fn.check         = $.check.fnCheck;
    $.fn.uncheck       = $.check.fnUncheck;
    $.fn.checkreadonly = $.check.fnCheckReadonly;
})(jQuery);