var technical_schedule = {
    technical_internal: null,
    technical_maximum_amount: null,
    technical_selected: null,
    distance: null,
    returning_distance: null,
    maximum_amount: null,
    early_date: null,
    schedule_data: {},
    priorities: {},
    save_callback: null,
    init_date: null,
    init: function() {
        $("#form-wizard-tabs").append("<li class='form-wizard-tab-technical-schedule' rel='form-wizard-technical-schedule' ><a href='#form-wizard-technical-schedule' data-toggle='tab' >Agenda do Técnico</a></li>");
        $("#form-wizard-tabs-content").append("\
            <div class='tab-pane' id='form-wizard-technical-schedule' >\
                <div class='container-technical-schedule' style='overflow-y: auto; height: 75%;' >\
                    <div class='alert alert-danger' role='alert' style='display: none;' >\
                        <button type='button' class='close close-alert' aria-label='Fechar' >\
                            <span aria-hidden='true'>&times;</span>\
                        </button>\
                        <p></p>\
                    </div>\
                    <div class='alert alert-success' role='alert' style='display: none;' >\
                        <button type='button' class='close close-alert' aria-label='Fechar' >\
                            <span aria-hidden='true'>&times;</span>\
                        </button>\
                        <p></p>\
                    </div>\
                    <div>\
                        <div id='technical-schedule-datepicker' class='col-xs-12 col-sm-12 col-md-3 col-lg-3' ></div>\
                        <div class='col-xs-12 col-sm-12 col-md-8 col-lg-8' >\
                            <table id='technical-schedule-priorities' class='table table-striped table-bordered' style='table-layout: fixed;' >\
                                <thead>\
                                    <tr>\
                                        <th class='titulo_coluna' >Legenda</th>\
                                    </tr>\
                                </thead>\
                                <tbody>\
                                    <tr>\
                                        <td>\
                                            <div class='panel panel-inverse' >\
                                                <div class='panel-heading' >\
                                                    <h6 class='panel-title' >&nbsp;</h6>\
                                                </div>\
                                                <div class='panel-body' >\
                                                    <span style='font-weight: bold;' >Concluido</span>\
                                                </div>\
                                            </div>\
                                        </td>\
                                    </tr>\
                                </tbody>\
                            </table>\
                            <div class='alert alert-info' >Capacidade diária de atendimentos: <strong id='technical-maximum-amount' ></strong></div>\
                        </div>\
                    </div>\
                    <br />\
                    <div id='technical-schedule-week-table' ></div>\
                <div>\
                <br />\
                <div class='module_actions text-right' style='position: fixed; bottom: 0px; margin-bottom: 20px; right: 20px;' >\
                    <button type='button' class='btn-technical-schedule-save btn btn-success' style='width: 150px;' >Agendar</button>\
                </div>\
                <div id='manual_scheduled_modal' class='modal fade' tabindex='-1' role='dialog' >\
                    <div class='modal-dialog modal-sm' >\
                        <div class='modal-content' >\
                            <div class='modal-header' >\
                                <button type='button' class='close close-modal' title='Fechar' ><span>&times;</span></button>\
                                <h4 class='modal-title' >Alterar data do agendamento</h4>\
                            </div>\
                            <div class='modal-body' >\
                                <input type='hidden' id='manual_scheduled_ticket' value='' />\
                                <input type='text' id='manual_scheduled_input' value='' />\
                            </div>\
                            <div class='modal-footer' >\
                                <button id='manual_scheduled_button' type='button' class='btn btn-primary' >Agendar</button>\
                            </div>\
                        </div>\
                    </div>\
                </div>\
                <div id='change_technical_modal' class='modal fase full-size' tabindex='1' role='dialog' >\
                    <div class='modal-dialog full-size' >\
                        <div class='modal-content full-size' >\
                            <iframe class='full-size' scrolling='no' ></iframe>\
                        </div>\
                    </div>\
                </div>\
            </div>\
        ");

        technical_schedule.load_priorities();

        technical_schedule.trigger();
    },
    setTechnical: function(data) {
        technical_schedule.technical_internal       = data.internal
        technical_schedule.technical_maximum_amount = data.maximum_amount
        technical_schedule.technical_selected       = data.id
    },
    trigger: function() {
        $("li.form-wizard-tab-technical-schedule a").on("click", function() {
            if ($(this).parent().hasClass("disabled")) {
                return false;
            }

            if (typeof map != "undefined") {
                technical_schedule.technical_internal       = map.technical_internal
                technical_schedule.technical_maximum_amount = map.technical_maximum_amount
                technical_schedule.technical_selected       = map.technical_selected
                technical_schedule.distance                 = map.distance
                technical_schedule.returning_distance       = map.returning_distance
            }

            form_wizard.current_tab = $(this).parent().attr("rel");

            window.delay(function() {
                if (technical_schedule.datepicker.selected_date == null) {
                    technical_schedule.maximum_amount = null;
                    technical_schedule.early_date = null;
                    technical_schedule.schedule_data = {};

                    technical_schedule.datepicker.init();
                }

                var div_maximum_amount = $("#technical-maximum-amount").parent();

                if (technical_schedule.technical_internal == true) {
                    if (!$(div_maximum_amount).is(":visible")) {
                        $(div_maximum_amount).show();
                    }

                    $("#technical-maximum-amount").text(technical_schedule.technical_maximum_amount);    
                } else if ($(div_maximum_amount).is(":visible")) {
                    $(div_maximum_amount).hide();
                }
            });
        });

        $("button.btn-technical-schedule-save").on("click", function() {
            $("#form-wizard-technical-schedule div.module_actions > button").prop({ disabled: true });
            $(this).text("Agendando...");

            window.delay(function() {
                technical_schedule.save_technical_schedule();
            });
        });

        $(document).on("click", "button.post-it-order-up", function() {
            var post_it = $(this).parents("div.technical-schedule-protocol-draggable");

            var clone = $(post_it).clone();
            var prev  = $(post_it).prev();
            var td    = $(post_it).parent();

            $(post_it).hide("drop", { direction: "left" }, "slow", function() {
                $(post_it).remove();

                $(clone).hide();
                $(prev).before(clone);

                $(clone).show("drop", { direction: "left" }, function() {
                    $(clone).draggable({
                        appendTo: "parent",
                        revert: "invalid"
                    });

                    technical_schedule.reorder(td);
                });
            });
        });

        $(document).on("click", "button.post-it-order-down", "slow", function() {
            var post_it = $(this).parents("div.technical-schedule-protocol-draggable");

            var clone = $(post_it).clone();
            var next  = $(post_it).next();
            var td    = $(post_it).parent();

            $(post_it).hide("drop", { direction: "left" }, "slow", function() {
                $(post_it).remove();

                $(clone).hide();
                $(next).after(clone);

                $(clone).show("drop", { direction: "left" }, function() {
                    $(clone).draggable({
                        appendTo: "parent",
                        revert: "invalid"
                    });

                    technical_schedule.reorder(td);
                });
            });
        });

        $("button.close-modal").on("click", function() {
            $('#manual_scheduled_modal').modal('hide');
        });

        $("#manual_scheduled_button").on("click", function() {
            var date = $("#manual_scheduled_input").val();

            if (!date.length) {
                alert("Informe a data");
                return false;
            }

            date     = date.split("/");
            date     = new Date(date[2], (date[1] - 1), date[0]);

            var ticket  = $("#manual_scheduled_ticket").val();
            var post_it = $("#post-it-"+ticket);

            technical_schedule.remove_schedule($(post_it).data("dateScheduled"), ticket);

            var data = {
                ticket: ticket,
                technical: technical_schedule.technical_selected,
                telecontrol_protocol: $(post_it).dataAttr("telecontrol-protocol"),
                client_name: $(post_it).dataAttr("client-name"),
                description: $(post_it).dataAttr("description"),
                completed: $(post_it).dataAttr("completed"),
                order: $(post_it).dataAttr("order"),
                priority: $(post_it).dataAttr("priority")
            };

            technical_schedule.add_schedule(date.dateToString("-"), data);

            if ($("#"+date.dateToString("-")).length > 0) {
                var clone = $(post_it).clone();
                $(clone).dataAttr({ "date-scheduled": date.dateToString("-") });
            }

            var old_td = $(post_it).parents("td");

            $(post_it).remove();

            technical_schedule.reorder(old_td);

            if ($("#"+date.dateToString("-")).length > 0) {
                technical_schedule.create_post_it(date, data);
                technical_schedule.reorder($("#"+date.dateToString("-")));
            }

            $('#manual_scheduled_modal').modal('hide');
        });

        $("#manual_scheduled_input").datepicker({ minDate: 0, dateFormat: "dd/mm/yy" } ).mask("99/99/9999");
    },
    save_technical_schedule: function() {
        var ajax_data = {
            ajax_save_technical_schedule: true,
            technical: technical_schedule.technical_selected,
            schedule: technical_schedule.schedule_data,
            distance: technical_schedule.distance,
            returning_distance: technical_schedule.returning_distance
        };

        $.each(technical_schedule.schedule_data, function(index, value) {
            if (typeof value == "object" && value.length > 0) {
                value.forEach(function(protocol, i) {
                    if(protocol.telecontrol_protocol != null && protocol.telecontrol_protocol != form_wizard.telecontrol_protocol){
                        delete(technical_schedule.schedule_data[index][i]);
                    }
                });
            }
        }); 

        if (form_wizard.ticket != null) {
            ajax_data.os_kof = form_wizard.os_kof;
        }

        if (typeof map == "object") {
            ajax_data.destiny_latitude  = map.destiny.latitude;
            ajax_data.destiny_longitude = map.destiny.longitude;
        }

        $.ajax({
            async: false,
            url: "cockpit/ajax/technical_schedule.php",
            type: "post",
            data: ajax_data
        }).always(function(response) {
            response = JSON.parse(response);

            if (response.error.length > 0) {
                var array_error_message = [];

                response.error.forEach(function(ticket) {
                    protocol = technical_schedule.get_schedule_data(ticket.ticket);

                    var os_erro     = "";
                    var client_name = protocol.client_name;
                    var description = protocol.description;

                    if (!description.match(/OS Telecontrol/gi) && ticket.os) {
                        description += "<strong>OS Telecontrol</strong><br />"+ticket.os+"<br />";
                        os_erro = "\
                            <br /><strong>Gerou a OS:</strong><br />\
                            "+ticket.os+"\
                        ";

                        if ($("#post-it-"+form_wizard.ticket).length > 0) {
                            var post_it = $("#post-it-"+form_wizard.ticket);
                            $(post_it).dataAttr({ description: description }).find("div.panel-body").html(description);
                        }

                        technical_schedule.update_schedule(
                            null, 
                            ticket.ticket, 
                            {
                                telecontrol_protocol: ticket.telecontrol_protocol,
                                description: description
                            }
                        );
                    }

                    array_error_message.push("\
                        <div>\
                            <strong>"+client_name+"</strong><br />\
                            "+description+"\
                            <strong>Erro:</strong><br />\
                            "+ticket.message+"\
                            "+os_erro+"\
                        </div>\
                    ");

                    if (ticket.ticket == form_wizard.ticket) {
                        $("#form-wizard-technical-schedule div.module_actions > button").prop({ disabled: true });
                    }
                });

                form_wizard.showError(array_error_message.join("<br />"));
            }

            if (Object.size(response.success) > 0) {
                if (form_wizard.ticket != null && response.success[form_wizard.ticket] && form_wizard.scheduled == false) {
                    form_wizard.telecontrol_protocol = response.success[form_wizard.ticket]["telecontrol_protocol"];
                    form_wizard.os_telecontrol       = response.success[form_wizard.ticket]["os"];
                    form_wizard.scheduled            = true;

                    var post_it = $("#post-it-"+form_wizard.ticket);
                    var description = $(post_it).dataAttr("description");

                    if (!description.match(/OS Telecontrol/gi)) {
                        description += "<strong>OS Telecontrol</strong><br />"+form_wizard.os_telecontrol+"<br />";

                        $(post_it).dataAttr({ description: description }).find("div.panel-body").html(description);

                        form_wizard.showSuccess("Atendimento agendado com sucesso, foi gerado a OS "+response.success[form_wizard.ticket]["os"]);
                    } else {
                        form_wizard.showSuccess("Atendimento agendado com sucesso");
                    }
                } else {
                    form_wizard.showSuccess("Alterações salvas com sucesso");

                    if (technical_schedule.save_callback != null && response.success[form_wizard.ticket]) {
                        technical_schedule.save_callback(form_wizard.ticket, technical_schedule.technical_selected);
                    }
                }
            }

            $("button.btn-technical-schedule-save").text("Agendar").prop({ disabled: false });

            $("div.container-technical-schedule").scrollTop(0);
        });
    },
    datepicker: {
        selected_date: null,
        week_start_date: null,
        week_end_date: null,
        init: function() {
            $.fn.datepickerPTBR();

            try {
                $("#technical-schedule-datepicker").datepicker("destroy");
            } catch (e) {
                console.log(e.message);
            }

            var today   = new Date();
            var minDate = new Date(today.getFullYear(), today.getMonth(), (today.getDate() - today.getDay())).removeDays(7);

            $('#technical-schedule-datepicker').datepicker({
                showOtherMonths: true,
                selectOtherMonths: true,
                minDate: minDate,
                dateFormat: "dd/mm/yy",
                onSelect: function(dateText, inst) {
                    technical_schedule.datepicker.selected_date = $(this).datepicker('getDate');

                    technical_schedule.datepicker.week_start_date = new Date(
                        technical_schedule.datepicker.selected_date.getFullYear(), 
                        technical_schedule.datepicker.selected_date.getMonth(), 
                        (technical_schedule.datepicker.selected_date.getDate() - technical_schedule.datepicker.selected_date.getDay())
                    );

                    technical_schedule.datepicker.week_end_date = new Date(
                        technical_schedule.datepicker.selected_date.getFullYear(), 
                        technical_schedule.datepicker.selected_date.getMonth(), 
                        (technical_schedule.datepicker.selected_date.getDate() - technical_schedule.datepicker.selected_date.getDay() + 6)
                    );

                    technical_schedule.datepicker.select_full_week();

                    window.delay(function() {
                        technical_schedule.create_week_table(technical_schedule.load_week_post_it);
                    });
                },
                beforeShowDay: function(date) {
                    var cssClass = '';

                    if(date >= technical_schedule.datepicker.week_start_date && date <= technical_schedule.datepicker.week_end_date) {
                        cssClass = 'ui-datepicker-current-day';
                    }

                    return [true, cssClass];
                },
                onChangeMonthYear: function(year, month, inst) {
                    technical_schedule.datepicker.select_full_week();
                }
            });

            $(document).on("mousemove", '#technical-schedule-datepicker .ui-datepicker-calendar tr', function() {
                $(this).find('td a').addClass('ui-state-hover');
            });

            $(document).on("mouseleave", "#technical-schedule-datepicker .ui-datepicker-calendar tr", function() {
                $(this).find('td a').removeClass('ui-state-hover');
            });

            $("button.btn-technical-schedule-save").prop({ disabled: true });

            window.delay(function() {
                technical_schedule.load_schedule_data();

                if (form_wizard.ticket != null) {
                    technical_schedule.set_early_date();
                } else {
                    technical_schedule.datepicker.set_week_date(new Date());
                }

                technical_schedule.create_week_table(technical_schedule.load_week_post_it);
            });
        },
        select_full_week: function() {
            window.delay(function () {
                $('#technical-schedule-datepicker').find('.ui-datepicker-current-day a').addClass('ui-state-active');
                $("#technical-schedule-datepicker").find("a.ui-state-active").parent("td").prevAll("td").filter(function(e) {
                    if (!$(e).hasClass("ui-state-disabled")) {
                        return true;
                    }
                }).addClass("ui-datepicker-current-day").find("a").addClass("ui-state-active");
                $("#technical-schedule-datepicker").find("a.ui-state-active").parent("td").nextAll("td").addClass("ui-datepicker-current-day").find("a").addClass("ui-state-active");
            });
        },
        remove_full_week: function() {
            $("#technical-schedule-datepicker").find("a.ui-state-active").parent("td").removeClass("ui-datepicker-current-day").find("a").removeClass("ui-state-active");
        },
        set_week_date: function(date) {
            if (typeof date != "object" && date.match(/\-/g)) {
                date = date.split("-");

                date = new Date(date[2], (parseInt(date[1]) - 1), date[0]);
            }

            technical_schedule.datepicker.week_start_date = new Date(
                date.getFullYear(), 
                date.getMonth(), 
                (date.getDate() - date.getDay())
            );

            technical_schedule.datepicker.week_end_date = new Date(
                date.getFullYear(),
                date.getMonth(),
                (date.getDate() - date.getDay() + 6)
            );

            $('#technical-schedule-datepicker').datepicker("setDate", date);

            technical_schedule.datepicker.selected_date = date;
            technical_schedule.datepicker.remove_full_week();
            technical_schedule.datepicker.select_full_week();
        }
    },
    load_priorities: function() {
        $.ajax({
            async: false,
            url: "cockpit/ajax/technical_schedule.php",
            type: "get",
            data: { ajax_load_priorities: true },
            contentType: "application/json",
            dataType: "json"
        }).always(function(response) {
            if (response.error) {
                alert(response.error);
            } else {
                if (form_wizard.ticket != null) {
                    $("#technical-schedule-priorities > tbody > tr").prepend("\
                        <td>\
                            <div class='panel panel-default' >\
                                <div class='panel-heading' >\
                                    <h6 class='panel-title' >&nbsp;<span class='pull-right glyphicon glyphicon-flag' ></span></h6>\
                                </div>\
                                <div class='panel-body' >\
                                    <span style='font-weight: bold;' >Selecionado</span>\
                                </div>\
                            </div>\
                        </td>\
                    ");
                } else {
                    $("#technical-schedule-priorities > tbody > tr").prepend("\
                        <td>\
                            <div class='panel panel-default' >\
                                <div class='panel-heading' >\
                                    <h6 class='panel-title' >&nbsp;<span class='pull-right glyphicon glyphicon-wrench' ></span></h6>\
                                </div>\
                                <div class='panel-body' >\
                                    <span style='font-weight: bold;' >Em Execução</span>\
                                </div>\
                            </div>\
                        </td>\
                        <td>\
                            <div class='panel panel-default' >\
                                <div class='panel-heading' >\
                                    <h6 class='panel-title' >&nbsp;<span class='pull-right glyphicon glyphicon-road' ></span></h6>\
                                </div>\
                                <div class='panel-body' >\
                                    <span style='font-weight: bold;' >Em Deslocamento</span>\
                                </div>\
                            </div>\
                        </td>\
                        <td>\
                            <div class='panel panel-default' >\
                                <div class='panel-heading' >\
                                    <h6 class='panel-title' >&nbsp;<span class='pull-right glyphicon glyphicon-pause' ></span></h6>\
                                </div>\
                                <div class='panel-body' >\
                                    <span style='font-weight: bold;' >OS Pausada</span>\
                                </div>\
                            </div>\
                        </td>\
                    ");
                }

                $.each(response.priorities, function(key, priority) {
                    technical_schedule.priorities[priority.id] = {
                        description: priority.description,
                        color: priority.color,
                        weight: priority.weight
                    };

                    switch(priority.description) {
                        case "Baixa":
                            var post_it_class = "cockpit_baixa";
                            break;

                        case "Baixa KA":
                            var post_it_class = "cockpit_baixa_ka";
                            break;

                        case "Normal":
                            var post_it_class = "cockpit_normal";
                            break;

                        case "Normal KA":
                            var post_it_class = "cockpit_normal_ka";
                            break;

                        case "Alta":
                            var post_it_class = "cockpit_alta";
                            break;

                        case "Alta KA":
                            var post_it_class = "cockpit_alta_ka";
                            break;
                    }

                    $("#technical-schedule-priorities > tbody > tr").append("\
                        <td>\
                            <div class='panel "+post_it_class+"' >\
                                <div class='panel-heading' >\
                                    <h6 class='panel-title' >&nbsp;</h6>\
                                </div>\
                                <div class='panel-body' >\
                                    <span style='font-weight: bold;' >"+priority.description+"</span>\
                                </div>\
                            </div>\
                        </td>\
                    ");
                });

                $("#technical-schedule-priorities > thead > tr > th").attr({ colspan: $("#technical-schedule-priorities > tbody > tr > td").length });
            }
        });
    },
    create_week_table: function(callback) {
        var s_date     = technical_schedule.datepicker.week_start_date;
        var e_date     = technical_schedule.datepicker.week_end_date;
        var date_array = (new Date()).getDates(s_date, e_date);
        var today_date = new Date();

        var i = date_array.length - 1;

        if (date_array.length != 7 && date_array[i].getMonth() == 9) {
                var d = new Date(date_array[i].getFullYear()+"-10-"+(date_array[i].getDate() + 1));
                d.setDate(date_array[i].getDate() + 1);

                date_array.push(
                        d
                );
        }

        $("#technical-schedule-week-table").html("");

        $("#technical-schedule-week-table").append("\
            <table class='table table-bordered' style='table-layout: fixed;' >\
                <thead>\
                    <tr class='titulo_coluna' ></tr>\
                </thead>\
                <tbody>\
                    <tr></tr>\
                </tbody>\
            </table>\
        ");

        date_array.forEach(function(date) {
            $("#technical-schedule-week-table").find("table > thead > tr").append("<th>"+date.dateToString("/")+"</th>");

            var class_disabled = "";

            if (date.valueOf() < today_date.normalizeDate().valueOf()) {
                class_disabled = "bg-muted";
            }

            $("#technical-schedule-week-table").find("table > tbody > tr").append("<td id='"+date.dateToString("-")+"' class='technical-schedule-protocol-droppable "+class_disabled+"' ></td>");
        });

        window.delay(function() {
            technical_schedule.trigger_week_table();
        });

        if (callback) {
            window.delay(function() {
                callback();
            });
        }
    },
    trigger_week_table: function() {
        $("td.technical-schedule-protocol-droppable").filter(function(i, e) {
            if (!$(e).hasClass("bg-muted")) {
                return true;
            }
        }).droppable({
            drop: function(event, ui) {
                var old_date = $(ui.draggable).dataAttr("date-scheduled");

                if (old_date == $(event.target).attr("id")) {
                    $(ui.draggable).css({
                        "left": "0px",
                        "top": "0px"
                    });

                    return false;
                }

                var element = ui.draggable.clone();

                $(element).css({
                    "left": "0px",
                    "top": "0px"
                });

                var date = $(event.target).attr("id");

                technical_schedule.remove_schedule(old_date, element.dataAttr("ticket"));
                technical_schedule.add_schedule(date, {
                    ticket: element.dataAttr("ticket"),
                    technical: technical_schedule.technical_selected,
                    telecontrol_protocol: element.dataAttr("telecontrol-protocol"),
                    client_name: element.dataAttr("client-name"),
                    description: element.dataAttr("description"),
                    completed: element.dataAttr("completed"),
                    order: element.dataAttr("order"),
                    priority: element.dataAttr("priority")
                });

                ui.draggable.remove();

                $(event.target).append(element);
                element.dataAttr({ "date-scheduled": date });

                element.draggable({
                    appendTo: "parent",
                    revert: "invalid"
                });

                window.delay(function() {
                    technical_schedule.reorder(event.target, function() {
                        technical_schedule.reorder($("#"+old_date));
                    });
                });
            }
        });

        $(document).on("click", "button.btn-schedule-date", function() {
            var ticket = $(this).parents("div.technical-schedule-protocol-draggable").data("ticket");
            $("#manual_scheduled_ticket").val(ticket);
            $('#manual_scheduled_modal').modal('show');
        });

        $(document).on("click", "button.btn-change-technical", function() {
            var ticket = $(this).parents("div.technical-schedule-protocol-draggable").data("ticket");

            $("#change_technical_modal iframe").attr({ src: "monitor_tecnico_altera.php?ticket="+ticket });
            $("#change_technical_modal").modal('show');
        });
    },
    reorder: function(td, callback) {
        var order = 1;

        $(td).find("div.technical-schedule-protocol-draggable").each(function() {
            $(this).dataAttr({ order: order });

            technical_schedule.add_order_buttons($(this));
            technical_schedule.update_schedule($(td).attr("id"), $(this).dataAttr("ticket"), { order: order });

            order++;
        });

        if (callback) {
            callback();
        }
    },
    add_order_buttons: function(element) {
        if ($(element).parent().hasClass("bg-muted") || $(element).hasClass("panel-inverse")) {
            return false;
        }

        $(element).find("button.post-it-order-down, button.post-it-order-up").remove();

        if ($(element).next().length > 0) {
            $(element).find("div.panel-footer").prepend("<button type='button' class='btn btn-danger btn-xs pull-left post-it-order-down' ><span class='glyphicon glyphicon-triangle-bottom' ></span></button>");
        }

        if ($(element).prev().length > 0 && !$(element).prev().hasClass("panel-inverse")) {
            $(element).find("div.panel-footer").append("<button type='button' class='btn btn-success btn-xs pull-left post-it-order-up' ><span class='glyphicon glyphicon-triangle-top' ></span></button>");
        }
    },
    get_schedule_data: function(ticket, date) {
        var p;

        if (typeof date == "undefined" || date == null) {
            $.each(technical_schedule.schedule_data, function(date, protocol_array) {
                if (typeof protocol_array == "object" && protocol_array.length > 0) {
                    protocol_array.forEach(function(protocol, i) {
                        if (protocol.ticket == ticket) {
                            p = protocol;
                            return false;
                        }
                    });
                }
            });
        } else {
            technical_schedule.schedule_data[date].forEach(function(protocol, i) {
                if (protocol.ticket == ticket) {
                    p = protocol;
                    return false;
                }
            });
        }

        return p;
    },
    add_schedule: function(date, data) {
        if (typeof technical_schedule.schedule_data[date] == "undefined") {
            technical_schedule.schedule_data[date] = [];
        }

        technical_schedule.schedule_data[date].push(data);
    },
    remove_schedule: function(date, ticket) {
        if (date == null) {
            $.each(technical_schedule.schedule_data, function(date, protocol_array) {
                if (typeof protocol_array == "object" && protocol_array.length > 0) {
                    protocol_array.forEach(function(protocol, i) {
                        if (protocol.ticket == ticket) {
                            delete(technical_schedule.schedule_data[date][i]);
                            return false;
                        }
                    });
                }
            });
        } else {
            technical_schedule.schedule_data[date].forEach(function(protocol, i) {
                if (protocol.ticket == ticket) {
                    delete(technical_schedule.schedule_data[date][i]);
                    return false;
                }
            });
        }
    },
    update_schedule: function(date, ticket, data) {
        if (date == null) {
            $.each(technical_schedule.schedule_data, function(date, array_protocol) {
                array_protocol.forEach(function(protocol, i) {
                    if (protocol.ticket == ticket) {
                        $.each(data, function(key, value) {
                            technical_schedule.schedule_data[date][i][key] = value;
                        });
                        return false;
                    }
                });
            });
        } else {
            technical_schedule.schedule_data[date].forEach(function(protocol, i) {
                if (protocol.ticket == ticket) {
                    $.each(data, function(key, value) {
                        technical_schedule.schedule_data[date][i][key] = value;
                    });
                    return false;
                }
            });
        }
    },
    load_schedule_data: function() {
        if (form_wizard.ticket != null && form_wizard.technical == technical_schedule.technical_selected) {
            var date = new Date(form_wizard.scheduled_date);
        } else if (technical_schedule.init_date != null) {
            var date = new Date(technical_schedule.init_date);
        } else {
            var date = new Date();
        }

        var week_start_date = new Date(
            date.getFullYear(), 
            date.getMonth(), 
            (date.getDate() - date.getDay())
        );

        if ((form_wizard.ticket != null && form_wizard.technical == technical_schedule.technical_selected) || technical_schedule.init_date != null) {
            $('#technical-schedule-datepicker').datepicker("option", "minDate", week_start_date.removeDays(7));
            $('#technical-schedule-datepicker').datepicker("refresh");
        }

        $.ajax({
            async: false,
            url: "cockpit/ajax/technical_schedule.php",
            type: "get",
            data: { ajax_load_schedule_data: true, technical: technical_schedule.technical_selected, week_start_date: week_start_date.removeDays(7).dateToString("/") },
            contentType: "application/json",
            dataType: "json"
        }).always(function(response) {
            if (response.error) {
                alert(response.error);
            } else if (response.success == true) {
                if (response.result.length == 0) {
                    return false;
                }

                response.result.forEach(function(protocol) {
                    technical_schedule.add_schedule(protocol.date, {
                        ticket: protocol.ticket,
                        telecontrol_protocol: protocol.telecontrol_protocol,
                        client_name: protocol.client_name,
                        order: protocol.order,
                        description: "\
                            <strong>OS KOF</strong><br />\
                            "+protocol.os_kof+"<br />\
                            "+((protocol.os_telecontrol != null && protocol.os_telecontrol.length > 0) ? "<strong>OS Telecontrol</strong><br />"+protocol.os_telecontrol+"<br />" : "")+"\
                        ",
                        completed: ((protocol.completed != null) ? protocol.completed : null),
                        priority: protocol.priority,
                        status: protocol.status
                    });

                    if (form_wizard.ticket != null && form_wizard.ticket == protocol.ticket) {
                        if (form_wizard.scheduled == false) {
                            form_wizard.os_telecontrol       = protocol.os_telecontrol;
                            form_wizard.telecontrol_protocol = protocol.telecontrol_protocol;
                            form_wizard.scheduled            = true;
                        }

                        technical_schedule.datepicker.set_week_date(protocol.date);
                    }
                });
            } else {
                alert("Não foi possível carregar as informações");
            }
        });
    },
    create_post_it: function(date, data) {
        if (data.completed != null || $.inArray(data.status, ["Em Deslocamento", "Em Execução"]) != -1) {
            var post_it_class = "panel-inverse";
        } else {
            var post_it_class = "";

            if (data.priority) {
                switch(technical_schedule.priorities[data.priority].description){
                    case "Baixa":
                        post_it_class = "cockpit_baixa";
                        break;

                    case "Baixa KA":
                        post_it_class = "cockpit_baixa_ka";
                        break;

                    case "Normal":
                        post_it_class = "cockpit_normal";
                        break;

                    case "Normal KA":
                        post_it_class = "cockpit_normal_ka";
                        break;

                    case "Alta":
                        post_it_class = "cockpit_alta";
                        break;

                    case "Alta KA":
                        post_it_class = "cockpit_alta_ka";
                        break;
                }
            }
        }

        if (form_wizard.ticket != null) {
            if (data.ticket == form_wizard.ticket) {
                var span_selected = "<span class='pull-right glyphicon glyphicon-flag' ></span>";
            } else {
                var span_selected = "";
            }
        } else {
            switch (data.status) {
                case "Em Execução":
                    var span_selected = "<span class='pull-right glyphicon glyphicon-wrench' ></span>";
                    break;

                case "Em Deslocamento":
                    var span_selected = "<span class='pull-right glyphicon glyphicon-road' ></span>";
                    break;

                case "OS Pausada":
                    var span_selected = "<span class='pull-right glyphicon glyphicon-pause' ></span>";
                    break;

                default:
                    var span_selected = "";
                    break;
            }
        }

        $("#"+date.dateToString("-")).append("\
            <div id='post-it-"+data.ticket+"' data-priority='"+data.priority+"' data-order='"+data.order+"' data-date-scheduled='"+date.dateToString("-")+"' data-completed='"+data.completed+"' data-ticket='"+data.ticket+"' data-telecontrol-protocol='"+data.telecontrol_protocol+"' data-client-name='"+data.client_name+"' data-description='"+data.description+"' class='technical-schedule-protocol-draggable panel "+post_it_class+"' >\
                <div class='panel-heading' >\
                    "+span_selected+"<h6 class='panel-title' >"+data.client_name+"</h6>\
                </div>\
                <div class='panel-body' >\
                    "+data.description+"\
                </div>\
            </div>\
        ");

        if (data.completed == null && $.inArray(data.status, ["Em Deslocamento", "Em Execução"]) == -1) {
            if (form_wizard.ticket == null) {
                $("#post-it-"+data.ticket).append("\
                    <div class='panel-footer text-right' >\
                        <button type='button' class='btn btn-xs btn-warning btn-change-technical' title='Alterar técnico do atendimento' ><span class='glyphicon glyphicon-transfer' ></span></button>\
                        <button type='button' class='btn btn-xs btn-primary btn-schedule-date' title='Definir data de agendamento' ><span class='glyphicon glyphicon-calendar' ></span></button>\
                    </div>\
                ");
            } else {
                $("#post-it-"+data.ticket).append("\
                    <div class='panel-footer text-right' >\
                        <button type='button' class='btn btn-xs btn-primary btn-schedule-date' title='Definir data de agendamento' ><span class='glyphicon glyphicon-calendar' ></span></button>\
                    </div>\
                ");
            }

            $("#post-it-"+data.ticket).draggable({
                appendTo: "parent",
                revert: "invalid"
            });
        }
    },
    load_week_post_it: function() {
        var s_date     = technical_schedule.datepicker.week_start_date;
        var e_date     = technical_schedule.datepicker.week_end_date;
        var date_array = (new Date()).getDates(s_date, e_date);

        var i = date_array.length - 1;

        if (date_array.length != 7 && date_array[i].getMonth() == 9) {
                var d = new Date(date_array[i].getFullYear()+"-10-"+(date_array[i].getDate() + 1));
                d.setDate(date_array[i].getDate() + 1);
                date_array.push(
                        d
                );
        }

        date_array.forEach(function(date) {
            if (typeof technical_schedule.schedule_data[date.dateToString("-")] == "undefined") {
                return;
            }

            $.each(technical_schedule.schedule_data[date.dateToString("-")], function(key, protocol) {
                if (typeof protocol != "undefined") {
                    technical_schedule.create_post_it(date, protocol);
                }
            });

            $("#"+date.dateToString("-")).find("div.technical-schedule-protocol-draggable").each(function() {
                technical_schedule.add_order_buttons($(this));
            });
        });

        $("button.btn-technical-schedule-save").prop({ disabled: false });

        if (technical_schedule.early_date != null) {
            window.delay(function() {
                technical_schedule.reorder($("#"+technical_schedule.early_date));
            });
        }
    },
    set_early_date: function() {
        $.ajax({
            async: false,
            url: "cockpit/ajax/technical_schedule.php",
            type: "get",
            data: { ajax_set_early_date: true, ticket: form_wizard.ticket, technical: technical_schedule.technical_selected },
            contentType: "application/json",
            dataType: "json"
        }).always(function(response) {
            if (response.error) {
                alert(response.error);
            } else if (response.success == true) {
                if (response.date == technical_schedule.early_date || (form_wizard.scheduled == true && (technical_schedule.technical_selected == response.technical || !response.technical))) {
                    return false;
                }

                technical_schedule.maximum_amount = response.maximum_amount;
                technical_schedule.early_date     = response.date;

                technical_schedule.add_schedule(response.date, {
                    ticket: form_wizard.ticket,
                    telecontrol_protocol: form_wizard.telecontrol_protocol,
                    client_name: form_wizard.client_name,
                    description: "\
                        <strong>OS KOF</strong><br />\
                        "+form_wizard.os_kof+"<br />\
                        "+((form_wizard.os_telecontrol || response.os_telecontrol) ? "<strong>OS Telecontrol</strong><br />"+form_wizard.os_telecontrol+"<br />" : "")+"\
                    ",
                    completed: null,
                    priority: form_wizard.priority
                });

                technical_schedule.datepicker.set_week_date(response.date);
            } else {
                alert("Não foi possível carregar as informações");
            }
        });
    }
};
