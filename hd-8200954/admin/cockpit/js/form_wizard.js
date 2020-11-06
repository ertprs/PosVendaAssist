var form_wizard = {
    element: null,
    os_kof: null,
    telecontrol_protocol: null,
    os_telecontrol: null,
    ticket: null,
    scheduled: false,
    client_id: null,
    client_name: null,
    current_tab: null,
    priority: null,
    technical: null,
    scheduled_date: null,
    call_type: null,
    call_type_warranty: null,
    product: null,
    distribution_center: null,
    init: function() {
        $(form_wizard.element).html("\
            <nav class='navbar navbar-default' style='margin-bottom: 0px;' >\
                <div class='collapse navbar-collapse' >\
                    <ul id='form-wizard-tabs' class='nav navbar-nav nav-tabs' ></ul>\
                </div>\
            </nav>\
            <div id='form-wizard-tabs-content' class='tab-content' style='height: 100%;' ></<div>\
        ");

        $("#form-wizard-tabs").tab();

        $(form_wizard.element).find("div.tab-content").css({
            border: "1px solid #d4d4d4",
            "border-radius": "0px 0px 4px 4px",
            padding: "20px"
        });

        form_wizard.trigger();
    },
    trigger: function() {
        $(document).on("click", "#form-wizard-tabs li.disabled", function(e) {
            $(this).prevAll("li")
            .filter(function(i, e) { 
                if (!$(e).hasClass("disabled")) { 
                    return true; 
                } 
            })
            .first()
            .find("a")
            .click();
        });

        $(document).on("click", "button.next-tab", function() {
            if ($(this).hasClass("disabled")) {
                return false;
            }

            var id = $(this).parents("div.tab-pane").attr("id");

            $("li[rel='"+id+"']").next("li").find("a").click();
        });

        $(document).on("click", "button.close-alert", function() {
            $(this).parent().hide();
        });
    },
    activeFirstTab: function(callback) {
        form_wizard.current_tab = $("#form-wizard-tabs").find("li").first().attr("rel");

        $("#form-wizard-tabs").find("li").first().addClass("active").find("a").click();
        $("#form-wizard-tabs").find("li").first().nextAll().addClass("disabled");
        $("#form-wizard-tabs-content").find("div.tab-pane").first().addClass("active");

        if (callback) {
            window.delay(function() {
                callback();
            });
        }
    },
    activeNextTab: function(callback) {
        $("li[rel='"+form_wizard.current_tab+"']").next("li").removeClass("disabled");
        $("#"+form_wizard.current_tab).find("button.next-tab").removeClass("disabled").prop({ disabled: false });

        if (callback) {
            callback();
        }
    },
    disableNextTab: function(callback) {
        if (!$("#"+form_wizard.current_tab).find("button.next-tab").hasClass("disabled")) {
            $("#"+form_wizard.current_tab).find("button.next-tab").prop({ disabled: true }).addClass("disabled");
        }

        var next_tabs = $("li[rel='"+form_wizard.current_tab+"']").nextAll("li");

        $(next_tabs).each(function() {
            if (!$(this).hasClass("disabled")) {
                $(this).addClass("disabled");
            }
        });

        if (callback) {
            window.delay(function() {
                callback();    
            });
        }
    },
    showError: function(message) {
        $("#"+form_wizard.current_tab).find("div.alert-danger").show().find("p").html(message);
    },
    hideError: function() {
        $("#"+form_wizard.current_tab).find("div.alert-danger").hide();
    },
    showSuccess: function(message) {
        $("#"+form_wizard.current_tab).find("div.alert-success").show().find("p").html(message);
    },
    hideSuccess: function() {
        $("#"+form_wizard.current_tab).find("div.alert-success").hide();
    },
    showInfo: function() {
        $("#"+form_wizard.current_tab).find("div.alert-info").show();
    },
    hideInfo: function() {
        $("#"+form_wizard.current_tab).find("div.alert-info").hide();  
    }
};

(function ($) {

$.fn.cockpit_form_wizard = function(data) {
    form_wizard.element = $(this);

    if (typeof data == "object") {
        form_wizard.ticket              = data.ticket;
        form_wizard.scheduled           = data.scheduled;
        form_wizard.os_kof              = data.os_kof;
        form_wizard.client_id           = data.client_id;
        form_wizard.client_name         = data.client_name;
        form_wizard.call_type           = data.call_type;
        form_wizard.call_type_warranty  = data.call_type_warranty;
        form_wizard.product             = data.product;
        form_wizard.distribution_center = data.distribution_center;

        if (data.priority.length > 0) {
            form_wizard.priority = data.priority;
        }

        if (data.telecontrol_protocol.length > 0) {
            form_wizard.telecontrol_protocol = parseInt(data.telecontrol_protocol);
        }

        if (data.os_telecontrol.length > 0) {
            form_wizard.os_telecontrol = parseInt(data.os_telecontrol);
        }
    }

    form_wizard.init();
};

})(jQuery);