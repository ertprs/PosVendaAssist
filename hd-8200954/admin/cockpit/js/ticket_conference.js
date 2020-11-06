var ticket_conference = {
    init: function() {
        $("#form-wizard-tabs").append("<li class='form-wizard-tab-ticket-conference' rel='form-wizard-ticket-conference' ><a href='#form-wizard-ticket-conference' data-toggle='tab' >Conferência do Ticket</a></li>");
        $("#form-wizard-tabs-content").append("\
            <div class='tab-pane' id='form-wizard-ticket-conference' >\
                <form id='form-ticket-conference' >\
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
                        <p>Ticket salvo com sucesso!</p>\
                    </div>\
                    <div class='alert alert-info' role='alert' style='display: none;' >\
                        <p>Carregando Informações...</p>\
                    </div>\
                    <div id='ticket-conference-content' style='display: none;' >\
                        <p class='text-right text-danger' >* campos obrigatórios</p>\
                        <div class='container-fluid' style='overflow-y: auto; height: 55%;' >\
                            <div id='ticket-conference-inputs' class='row' >\
                            </div>\
                        </div>\
                    </div>\
                    <br />\
                    <div class='text-right' style='position: fixed; bottom: 0px; margin-bottom: 20px; right: 20px;' >\
                        <button type='button' class='btn-ticket-conference-save btn btn-success' style='width: 150px;' >Salvar</button>\
                        <button type='button' class='btn btn-primary next-tab disabled' style='width: 150px;' >Próximo</button>\
                    </div>\
                </form>\
            </div>\
        ");

        ticket_conference.trigger();
    },
    /**
     * Busca CEP
     */
    search_cep: function(cep, method) {
        if (cep.length > 0) {
            var img = $("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } });

            if (typeof method == "undefined" || method.length == 0) {
                method = "webservice";

                $.ajaxSetup({
                        timeout: 5000
                });
            } else {
                $.ajaxSetup({
                        timeout: 10000
                });
            }

            $.ajax({
                async: true,
                url: "ajax_cep.php",
                type: "GET",
                data: { cep: cep, method: method },
                beforeSend: function() {
                    $("select[name=estadoCliente]").next("img").remove();
                    $("select[name=cidadeCliente]").next("img").remove();
                    $("input[name=bairroCliente]").next("img").remove();
                    $("input[name=enderecoCliente]").next("img").remove();

                    $("select[name=estadoCliente]").hide().after(img.clone());
                    $("select[name=cidadeCliente]").hide().after(img.clone());
                    $("input[name=bairroCliente]").hide().after(img.clone());
                    $("input[name=enderecoCliente]").hide().after(img.clone());
                },
                error: function(xhr, status, error) {
                    ticket_conference.search_cep(cep, "database");
                },
                success: function(data) {
                    results = data.split(";");

                    if (results[0] != "ok") {
                        $("select[name=cidadeCliente]").show().next().remove();
                    } else {
                        $("select[name=estadoCliente]").val(results[4]);

                        ticket_conference.search_city(results[4]);
                        results[3] = results[3].replace(/[\(\)\']/g, '');

                        $("select[name=cidadeCliente]").val(results[3].toUpperCase().unaccent());

                        if (results[2].length > 0) {
                            $("input[name=bairroCliente]").val(results[2]);
                        }

                        if (results[1].length > 0) {
                            $("input[name=enderecoCliente]").val(results[1]);
                        }
                    }

                    $("select[name=estadoCliente]").show().next().remove();
                    $("select[name=cidadeCliente]").show().next().remove();
                    $("input[name=bairroCliente]").show().next().remove();
                    $("input[name=enderecoCliente]").show().next().remove();

                    if ($("input[name=bairroCliente]").val().length == 0) {
                        $("input[name=bairroCliente]").focus();
                    } else if ($("input[name=enderecoCliente]").val().length == 0) {
                        $("input[name=enderecoCliente]").focus();
                    }

                    $.ajaxSetup({
                        timeout: 0
                    });
                }
            });
        }
    },
    /**
     * Função que busca as cidades do estado e popula o select cidade
     */
    search_city: function(estado, consumidor_revenda, cidade) {
        $("select[name=cidadeCliente]").find("option").first().nextAll().remove();

        if (estado.length > 0) {
            $.ajax({
                async: false,
                url: "conferencia_integracao.php",
                type: "GET",
                data: { ajax_busca_cidade: true, estado: estado },
                beforeSend: function() {
                    if ($("select[name=cidadeCliente]").next("img").length == 0) {
                        $("select[name=cidadeCliente]").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
                    }
                },
                complete: function(data) {
                    data = $.parseJSON(data.responseText);

                    if (data.error) {
                        alert(data.error);
                    } else {
                        $.each(data.cidades, function(key, value) {
                            var option = $("<option></option>", { value: value, text: value});

                            $("select[name=cidadeCliente]").append(option);
                        });
                    }


                    $("select[name=cidadeCliente]").show().next().remove();
                }
            });
        }

        if(typeof cidade != "undefined" && cidade.length > 0){

            $('select[name=cidadeCliente] option[value='+cidade+']').attr('selected','selected');

        }

    },
    trigger: function() {
        $("button.btn-ticket-conference-save").on("click", function() {
            ticket_conference.validate(ticket_conference.save);
        });

        $("li.form-wizard-tab-ticket-conference a").on("click", function() {
            if ($(this).parent().hasClass("disabled")) {
                return false;
            }

            form_wizard.current_tab = $(this).parent().attr("rel");
        });
    },
    inputs_required: [
        "nomeFantasia",
        "enderecoCliente",
        "bairroCliente",
        "cepCliente",
        "cidadeCliente",
        "estadoCliente",
        "telefoneCliente",
        "modeloKof",
        "defeito",
        "tipoOrdem",
        "patrimonioKof"        
    ],
    inputs_select: [
        "cidadeCliente",
        "estadoCliente",
        "paisCliente",
        "garantia",        
        "defeito",
        "tipoOrdem",
        "descricaoTipo"
    ],
    inputs_lupa: [
        "cepCliente"
    ],
    inputs_hidden: [
        "hdChamadoCockpit",
        "periodoAtendimento",
        "defReclamadoAdicional",
        "device",
        "grupoCatalogoKof",
        "protocoloKof",
        "patrimonioKof",
        "empresa",
        "idCliente",
        "Id_Icebev"
    ],
    inputs_readonly: [
        "dataAbertura"
    ],
    inputs_unique: {
        descricaoTipo: "tipoOrdem",
        codDefeito: "defeito",
    },
    tiposOrdem: {
        ZKR1: "Movimentação",
        ZKR2: "Movimentação",
        ZKR3: "Corretiva",
        ZKR5: "Preventiva",
        ZKR6: "Sanitização",
        ZKR9: "Piso",
        'AMBV-GAR': "AMBEV Garantia Corretiva"
    },tiposGarantia: {
        'nao':'Não',
        'sim':'Sim'
    },
    load: function(ticketData, fnc_pop_form, callback) {
        $("#ticket-conference-content").hide();
        form_wizard.showInfo();

        window.delay(function() {
            $("#ticket-conference-inputs").html("");

            fnc_pop_form(ticketData, callback);
        });
    },
    pop_form_data: function(ticketData, callback) {
        if (typeof ticketData == "object") {
            window.estadoSel    = ticketData.estadoCliente;

            if (typeof ticketData.codDefeito != "undefined") {
                window.defeitoSel   = ticketData.codDefeito;
            } else {
                window.defeitoSel   = ticketData.defeito;
            }

            window.referencia   = ticketData.modeloKof;
            window.tipoOrdemSel = ticketData.tipoOrdem;
            window.tipoGarantiaSel = ticketData.garantia;

            $.each(ticketData, function(label, value) {

                if (typeof ticket_conference.inputs_unique[label] != "undefined") {
                    var value_input_unique = $("input[name='"+ticket_conference.inputs_unique[label]+"']").val();
                    $("input[name='"+ticket_conference.inputs_unique[label]+"']").val(value_input_unique + " " + value);
                } else if (label != "branco") {
                    var title;
                    title = label.replace( /([A-Z])/g, " $1" );
                    title = title.replace("Kof", "Cliente"); 
                    title = title.charAt(0).toUpperCase() + title.slice(1);

                    if ($.inArray(label, ticket_conference.inputs_select) != -1) {

                        if (label == "cidadeCliente") {
                            value = value.replace(/[\(\)\']/g, '');
                            value = value.toUpperCase().unaccent();
                        }

                        $("#ticket-conference-inputs").append("\
                            <div class='form-group col-xs-4 col-sm-4 col-md-4 col-lg-4' >\
                                "+(($.inArray(label, ticket_conference.inputs_required) != -1) ? "<strong class='text-danger' >*</strong> " : "")+"\
                                <label for='"+label+"' >"+title+"</label>\
                                "+ticket_conference.inputSelect(label, value)+"\
                            </div>\
                        ");
                    } else if ($.inArray(label, ticket_conference.inputs_lupa) != -1) {
                        $("#ticket-conference-inputs").append("\
                            <div class='form-group col-xs-4 col-sm-4 col-md-4 col-lg-4' >\
                                "+(($.inArray(label, ticket_conference.inputs_required) != -1) ? "<strong class='text-danger' >*</strong> " : "")+"\
                                <label for='"+label+"' >"+title+"</label>\
                                "+ticket_conference.inputLupa(label, value)+"\
                            </div>\
                        ");
                    } else if ($.inArray(label, ticket_conference.inputs_hidden) != -1) {
                        $("#ticket-conference-inputs").append("<input type='hidden' name='"+label+"' value=\""+value+"\" />");
                    }else {
                        $("#ticket-conference-inputs").append("\
                            <div class='form-group col-xs-4 col-sm-4 col-md-4 col-lg-4' >\
                                "+(($.inArray(label, ticket_conference.inputs_required) != -1) ? "<strong class='text-danger' >*</strong> " : "")+"\
                                <label for='"+label+"' >"+title+"</label>\
                                <input class='form-control col-sm-4' type='text' name='"+label+"' value=\""+value+"\" "+(($.inArray(label, ticket_conference.inputs_readonly) != -1) ? "readonly" : "")+" />\
                            </div>\
                        ");
                    }
                }
            });

            form_wizard.hideInfo();
            $("#ticket-conference-content").show();

            window.delay(function() {
                ticket_conference.triggerCampos();
            });
        }

        if (callback) {
            window.delay(function() {
                callback();
            });
        }
    },
    inputSelect: function(label, selValue) {

        retorno = "<select class='form-control col-sm-4' name='"+label+"'>\
                        <option value='' ></option>";

        if (label == 'paisCliente') {

            $.each(window.arrayPaises, function(value, description) {
                if (value == selValue) {
                    retorno = retorno+"<option value=\""+value+"\" SELECTED>"+description+"</option>\
                    ";
                } else {
                    retorno = retorno+"<option value=\""+value+"\">"+description+"</option>\
                    ";
                }
            });

        } else if (label == 'estadoCliente') {

            $.each(window.arrayEstados, function(value, description) {
                if (value == selValue) {
                    retorno = retorno+"<option value=\""+value+"\" SELECTED>"+description+"</option>\
                    ";
                } else {
                    retorno = retorno+"<option value=\""+value+"\">"+description+"</option>\
                    ";
                }
            });

        } else if (label == 'cidadeCliente') {

            $.ajax({
                async: false,
                url: "conferencia_integracao.php",
                type: "GET",
                data: { ajax_busca_cidade: true, estado: window.estadoSel },
                complete: function(data) {
                    data = $.parseJSON(data.responseText);
                    if (data.error) {
                        alert(data.error);
                    } else {
                        $.each(data.cidades, function(key, value) {
                            if (value == selValue) {
                                retorno = retorno+"<option value=\""+value+"\" SELECTED>"+value+"</option>\
                                ";
                            } else {
                                retorno = retorno+"<option value=\""+value+"\">"+value+"</option>\
                                ";
                            }
                        });
                    }

                }
            });

        } else if (label == 'defeito' || label == 'codDefeito') {
            $.ajax({
                async: false,
                url: "conferencia_integracao.php",
                type: "GET",
                data: {
                    defeito_reclamado: true,
                    referencia: window.referencia,
                    tipo_atendimento: window.tipoOrdemSel
                }
            }).done(function(data){
                data = JSON.parse(data);
                $.each(data.defeitos_reclamados, function(key, value){
                    if (value.codigo == window.defeitoSel) {
                        retorno = retorno+"<option value='"+value.codigo+"' SELECTED>"+value.descricao+"</option>\
                        ";
                    } else {
                        retorno = retorno+"<option value='"+value.codigo+"'>"+value.descricao+"</option>\
                        ";
                    }
                });
            });

        } else if (label == 'tipoOrdem' || label == 'descricaoTipo') {
            $.each(ticket_conference.tiposOrdem, function(key, value){
                if (key == window.tipoOrdemSel) {
                    retorno = retorno+"<option value='"+key+"' SELECTED>"+key+" - "+value+"</option>\
                    ";
                } else {
                    retorno = retorno+"<option value='"+key+"'>"+key+" - "+value+"</option>\
                    ";
                }
            });
        }else if (label == 'garantia') {
            $.each(ticket_conference.tiposGarantia, function(key, value){
                if (key == window.tipoGarantiaSel) {
                    retorno = retorno+"<option value='"+key+"' SELECTED>"+value+"</option>\
                    ";
                } else {
                    retorno = retorno+"<option value='"+key+"'>"+value+"</option>\
                    ";
                }
            });
        }
        retorno = retorno+"</select>\
        ";

        return retorno;
    },
    inputLupa: function(label, value) {
        var retorno;

        if (label == 'cepCliente') {
            retorno = "<div class='input-group'>\
                                <input class='form-control col-sm-4' type='text' name='"+label+"' value=\""+value+"\" />\
                                <div class='input-group-addon' style='cursor:pointer;' onclick='ticket_conference.search_cep($(\"input[name=cepCliente]\").val());'>\
                                    <i class='glyphicon glyphicon-search'></i>\
                                </div>\
                            </div>\
                            ";
        }

        return retorno;
    },
    triggerCampos: function() {
        $('input[name=cepCliente]').mask('99999-999');
    },
    validate: function(callback) {
        form_wizard.disableNextTab();
        form_wizard.hideError();
        form_wizard.hideSuccess();
        $("button.btn-ticket-conference-save").text("Validando...").prop({ disabled: true });

        window.delay(function() {
            ticket_conference.send_validate(callback);
        });
    },
    send_validate: function(callback) {
        Cockpit.Validar($('#form-ticket-conference'));

        window.delay(function() {
            if (callback) {
                callback();
            }
        });
    },
    save: function() {
        if ($("button.btn-ticket-conference-save").data("valid") == true) {
            form_wizard.disableNextTab();
            $("button.btn-ticket-conference-save").text("Salvando...").prop({ disabled: true });

           window.delay(function() {
                ticket_conference.send_save();
            });
        }
    },
    send_save: function() {
        Cockpit.Salvar($('#form-ticket-conference'));
    },
    get_cliente_address: function() {
        var f = $('#form-ticket-conference');

        return {
            address: f.find("input[name=enderecoCliente]").val(),
            neighborhood: f.find("input[name=bairroCliente]").val(),
            city: f.find("select[name=cidadeCliente]").val(),
            state: f.find("select[name=estadoCliente]").val(),
            country: f.find("select[name=paisCliente]").val(),
            zip_code: f.find("input[name=cepCliente]").val()
        };
    }
};