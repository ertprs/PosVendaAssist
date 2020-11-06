var Cockpit = {

    GetValuesFrom : function(container) {
        var elementsObject = {};
        var containerElement = container;

        if (typeof container === 'string') {
            var containerElement = $(container);
        }

        containerElement.find('input, select, textarea').each(function (i, element) {
            var currentElementObject = $(element);
            elementsObject[$(element).attr("name")] = currentElementObject.val();
        });

        return elementsObject;
    },

    Salvar : function(form){
        var container = form;
        var dataObj = Cockpit.GetValuesFrom(container);

        var jsonData = JSON.stringify(dataObj);

        $.ajax({
            type: 'post',
            url: 'cockpit/control/cockpit.php',
            data:{
                'acao': 'salvar',
                'ticket': form_wizard.ticket,
                'json': jsonData
            },
            async: false,
            timeout: 5000,
            fail: function(data) {
                form_wizard.disableNextTab(function() {
                    $("button.btn-ticket-conference-save").text("Salvar").prop({ disabled: false });
                    form_wizard.showError("Ocorreu um erro ao salvar as informações, tempo limite esgotado");
                });
            },
            complete: function(data){
                if (data.status != 200) {
                    form_wizard.disableNextTab(function() {
                        $("button.btn-ticket-conference-save").text("Salvar").prop({ disabled: false });
                        form_wizard.showError("Ocorreu um erro ao salvar as informações");
                    });
                } else {
                    var respJson = JSON.parse(data.responseText);

                    if(respJson.success == false){
                        form_wizard.disableNextTab(function() {
                            $("button.btn-ticket-conference-save").text("Salvar").prop({ disabled: false });
                            form_wizard.showError(respJson.message);
                        });
                    } else {
                        form_wizard.activeNextTab(function() {
                            $("button.btn-ticket-conference-save").text("Salvar").prop({ disabled: false });
                            form_wizard.showSuccess("Ticket salvo com sucesso!");
                        });
                    }
                }
            }
        });
    },

    Validar: function(form) {
        var container = form;
        var dataObj = Cockpit.GetValuesFrom(container);

        var jsonData = JSON.stringify(dataObj);

        $.ajax({
            async: false,
            type:'post',
            url: 'cockpit/control/cockpit.php',
            data:{
                'acao': 'validar',
                'json': jsonData
            },
            timeout: 5000,
            fail: function(data) {
                form_wizard.disableNextTab(function() {
                    $("button.btn-ticket-conference-save").text("Salvar").prop({ disabled: false }).data({ valid: false });
                    form_wizard.showError("Ocorreu um erro ao validar as informações, tempo limite esgotado");
                });
            },
            complete: function (data) {
                if (data.status != 200) {
                    form_wizard.disableNextTab(function() {
                        $("button.btn-ticket-conference-save").text("Salvar").prop({ disabled: false }).data({ valid: false });
                        form_wizard.showError("Ocorreu um erro ao validar as informações");
                    });
                } else {
                    var respJson = JSON.parse(data.responseText);

                    if(respJson.success == false){
                        form_wizard.disableNextTab(function() {
                            $("button.btn-ticket-conference-save").text("Salvar").prop({ disabled: false }).data({ valid: false });
                            form_wizard.showError(respJson.message);
                        });
                    } else {
                        form_wizard.activeNextTab(function() {
                            $("button.btn-ticket-conference-save").text("Salvar").prop({ disabled: false }).data({ valid: true });
                        });
                    }
                }
            }
        });
    }

};