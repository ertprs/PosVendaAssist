class Cockpit {

    constructor() {
        this.mesesRecuperados = [new Date().getMonth() + 1]    
    }

    getCalendar() {
        return $('#calendar').fullCalendar('getCalendar')
    }
 
    obtemDescricaoDoEvento(evento) {
        return `OS: ${ evento.os } - Usuário: ${ evento.usuario } - Descrição: ${ evento.descricao }`
    } 

    bloquearCampos(){
        $(".data-inicio-agendamento").attr('disabled',true);
        $(".data-fim-agendamento").attr('disabled', true);
        $("#titulo-agendamento").attr('disabled',true);
        $("#confirmar-confirmado-agendamento").attr('disabled', true);
        $("#confirmar-descricao-agendamento").attr('disabled',true);
        $("#confirmar-tecnico-agendamento").attr('disabled', true);
        $("#cancelar_anterior").attr('disabled', true);
        $("#confirmar-justificativa-agendamento").attr('disabled',true);
        $(".iconreagendar").hide();
        $("#botao-remover-evento").show();
    }

    liberarCampos(){
        $(".data-inicio-agendamento").attr('disabled',false);
        $(".data-fim-agendamento").attr('disabled', false);
        $("#titulo-agendamento").attr('disabled',false);
        $("#confirmar-confirmado-agendamento").attr('disabled', false);
        $("#confirmar-descricao-agendamento").attr('disabled',false);
        $("#confirmar-tecnico-agendamento").attr('disabled', false);
        $("#cancelar_anterior").attr('disabled', false);
        $("#confirmar-justificativa-agendamento").attr('disabled',false);

        $(".iconreagendar").show();

        $("#liberar_campos").hide();
        $("#botao-remover-evento").hide();
        $("#botao-editar-evento").show();
    }

    inserirEventoNoCalendario(evento) {
        let calendar = this.getCalendar()
        let corDoEvento = evento.confirmado == 1 ? 'green' : 'blue'

        calendar.renderEvent({
            title  : evento.tituloEvento,
            start  : evento.dataInicio,
            end    : evento.dataFinal,
            allDay : evento.eventoDiaInteiro,
            color  : corDoEvento,
            editable : true,
            description : this.obtemDescricaoDoEvento(evento),
            os : evento.os,
            usuario : evento.usuario,
            informacoes : evento.descricao,
            fabrica_id : evento.fabricaId,
        }, true) // parametro true, permite a persistência do evento adicionado ao calendário mesmo trocando as views
    }

    atualizarEventoNoCalendario(dadosEvento, eventoId) {
        let calendar = this.getCalendar()

        let eventoCalendario = calendar.clientEvents(eventoId)[0] // método que recupera o evento do calendário

        eventoCalendario.color = dadosEvento.confirmado == 1 ? 'green' : 'red'
        eventoCalendario.description = this.obtemDescricaoDoEvento(dadosEvento)
        eventoCalendario.os = dadosEvento.os
        eventoCalendario.usuario = dadosEvento.usuario
        eventoCalendario.informacoes = dadosEvento.descricao
        eventoCalendario.confirmado = dadosEvento.confirmado

        calendar.updateEvent(eventoCalendario)
    }

    inserirEventosDoBancoNoCalendario(eventos) {
        let calendar = this.getCalendar()
        calendar.renderEvents(eventos, true)
    }

    obtemDadosModalEvento() {
        return {
            dataInicio : $('input#data-inicio-agendamento').val(),
            dataFinal : $('input#data-fim-agendamento').val(),
            tituloEvento : $('input#titulo-evento').val(),
            eventoDiaInteiro : $('input#dia-inteiro-agendamento').val(),
            usuario : $('select#tecnico-agendamento').val(),
            os : $('select#os-agendamento').val(),
            confirmado : $('select#confirmado-agendamento').val(),
            descricao : $('input#descricao-agendamento').val(),
            fabricaId : $('input#fabrica-id').val(),
        }
    }

    obtemDadosModalEventoMult() {

        let data_agendamento = $('.data_agendamento');
        let tecnico_id       = $('.tecnico_id'); 

        var retorno = [];
        $(".checa_os:checked").each(function( index ) {
            var posicao = $( this ).attr('posicao'); 
            var fabrica = $( this ).attr('fabrica'); 

            retorno.push({
                dataInicio : $(".data_inicio_agendamento_multi_"+posicao).val(),
                dataFinal : $(".data-fim-agendamento-multi_"+posicao).val(),
                tituloEvento : 'Agendamento de O.S',
                eventoDiaInteiro: false,
                usuario : tecnico_id.val(),
                os : $( this ).val(),
                confirmado : false,
                descricao : 'Agendamento - '+ $( this ).val(),
                fabricaId : fabrica,
            });
        });
        
        return retorno;

    }

    obtemDadosModalEdicao() {
        return {
            dataInicio : $('.data-inicio-agendamento').val(),
            dataFinal : $('.data-fim-agendamento').val(),
            tituloEvento : null,
            eventoDiaInteiro : false,
            usuario : $('select#confirmar-tecnico-agendamento option:selected').text(),
            os : $('#campo_os').val(),
            descricao : $('input#confirmar-descricao-agendamento').val(),
            confirmado : null,
            fabricaId : $('input#confirmar-fabrica-id').val(),
            tecnicoAgenda : $('input#confirmar-tecnico-agenda').val(),
            tecnico_id : $('#confirmar-tecnico-agendamento').val(),
        }
    }

    adicinarEvento(multiplo = false) {
        if (multiplo === true) {
            let novoEventoMult = this.obtemDadosModalEventoMult();

            for (var i = 0; i < novoEventoMult.length; i++) {
                //this.inserirEventoNoCalendario(novoEventoMult[i])
                this.adicionarAgendamentoAllNoBanco(novoEventoMult[i], 'multiplo')
            }            
            //$('#modal-adicionar-evento-all').modal('hide')

        } else {

            let novoEvento = this.obtemDadosModalEvento()

            if (novoEvento.eventoDiaInteiro == 'true') {
                novoEvento.dataInicio = moment(novoEvento.dataInicio).add(1, 'days').format('YYYY-MM-DD')
                novoEvento.dataFinal = moment(novoEvento.dataFinal).add(1, 'days').format('YYYY-MM-DD')

                inserirEventoNoCalendario(novoEvento)
                $('#modal-adicionar-evento').modal('hide')
                return
            }

            this.inserirEventoNoCalendario(novoEvento)
            this.adicionarAgendamentoNoBanco()
            $('#modal-adicionar-evento').modal('hide')
        }
    }
 
    editarEvento(eventoId) {
        let dadosEvento = this.obtemDadosModalEdicao()        
        //this.atualizarEventoNoCalendario(dadosEvento, eventoId)
        this.editarAgendamentoNoBanco()
        //$('div#modal-confirmar-remover-agendamento').modal('hide')
    }

    obtemData(arrayData) {
        return moment(arrayData).format('DD/MM/YYYY HH:mm:ss')
    }

    obtemTextoDoIntervalo(dataInicio, dataFinal, eventoDiaInteiro) {
        if (eventoDiaInteiro) {
            dataInicio = moment(dataInicio).add(1, 'days')
            dataFinal = moment(dataFinal).add(1, 'days')

            return `De: ${ moment(dataInicio).format('DD/MM/YYYY') } até ${ moment(dataFinal).format('DD/MM/YYYY') }`
        }

        return `De: ${ moment(dataInicio).format('DD/MM/YYYY HH:mm:ss') } até ${ moment(dataFinal).format('DD/MM/YYYY HH:mm:ss') }`
    }

    exibeModalDeAdicaoDeEvento(dataInicio, dataFinal, eventoDiaInteiro = false) {
        $('p#intervalo-evento').text(this.obtemTextoDoIntervalo(dataInicio, dataFinal, eventoDiaInteiro))

        $('input#data-inicio-agendamento').val(dataInicio)
        $('input#data-fim-agendamento').val(dataFinal)
        $('input#dia-inteiro-agendamento').val(eventoDiaInteiro)

        $('#modal-adicionar-evento').modal('show')
    }

    removerEvento(eventoId) {
        let calendar = this.getCalendar()

        calendar.removeEvents([eventoId])
        this.removerAgendamentoNoBanco()

        $('div#modal-confirmar-remover-agendamento').modal('hide')
    }

    exibeModalConfirmacaoRemocaoDeEvento(evento) {
        let tituloEvento = this.obtemTextoDoIntervalo(evento.start._i, evento.end._i, evento.allDay)

        $(".data-inicio-agendamento").val(moment(evento.start._i).format('DD/MM/YYYY HH:mm:ss'));
        $(".data-fim-agendamento").val(moment(evento.end._i).format('DD/MM/YYYY HH:mm:ss'));

        $('p#info-intervalo-evento').text(tituloEvento)
        $("#titulo-agendamento").val(evento.description);
        $('input#confirmar-tecnico-agenda').val(evento.tecnico_agenda)
        $('select#confirmar-tecnico-agendamento').val(evento.tecnico_id).trigger('change') // trigger('change') atualizar valor select2

        $('#numero_os').text(evento.os);
        $('#campo_os').val(evento.os);
        $('input#confirmar-descricao-agendamento').val(evento.informacoes)
        $('select#confirmar-confirmado-agendamento').val(evento.confirmado)
        $("#id-os-agendamento").val(evento.os);
        
        $('button#botao-remover-evento').attr('onclick', `cockpit.removerEvento("${ evento._id }")`)
        $('button#botao-editar-evento').attr('onclick', `cockpit.editarEvento("${ evento._id }")`)

        this.bloquearCampos();
        $("#liberar_campos").show();
        $("#botao-editar-evento").hide();

        $('div#modal-confirmar-remover-agendamento').modal('show')
    }

    editarAgendamentoPesquisa(evento) {

        let tituloEvento = this.obtemTextoDoIntervalo(evento.start, evento.end, evento.allDay)

        $(".data-inicio-agendamento").val(moment(evento.start).format('DD/MM/YYYY HH:mm:ss'));
        $(".data-fim-agendamento").val(moment(evento.end).format('DD/MM/YYYY HH:mm:ss'));

        $('p#info-intervalo-evento').text(tituloEvento)
        $("#titulo-agendamento").val(evento.description);
        $('input#confirmar-tecnico-agenda').val(evento.tecnico_agenda)
        $('select#confirmar-tecnico-agendamento').val(evento.tecnico_id).trigger('change') // trigger('change') atualizar valor select2

        $('#numero_os').text(evento.os);
        $('#campo_os').val(evento.os);
        $('input#confirmar-descricao-agendamento').val(evento.informacoes)
        $('select#confirmar-confirmado-agendamento').val(evento.confirmado)
        $("#id-os-agendamento").val(evento.os);
        
        $('button#botao-remover-evento').attr('onclick', `cockpit.removerEvento("${ evento._id }")`)
        $('button#botao-editar-evento').attr('onclick', `cockpit.editarEvento("${ evento._id }")`)

        this.bloquearCampos();
        $("#liberar_campos").show();
        $("#botao-editar-evento").hide();

        $('div#modal-confirmar-remover-agendamento').modal('show')
    }
 

    dataEventoAnteriorDataAtual(dataInicio, dataFinal) {
        return false
    }

    adicionarAgendamentoNoBanco() {
        $.ajax({
            url: 'cockpit_adicionar_agendamento.php',
            type: "POST",
            data: $('form#adicionar-novo-agendamento').serialize(),
            complete: (data) => {
                data = data.responseText
            },
        })
    }
    adicionarAgendamentoAllNoBanco(dados, tipo) {
        $.ajax({
            url: 'cockpit_adicionar_agendamento.php',
            type: "POST",
            data: {dados, tipo},
            complete: (data) => {
                data = $.parseJSON(data.responseText);
                if(data.sucesso){
                    //$(".tr-"+data.sucesso).css('background-color', '#dff0d8');                    
                    $(".tr-"+data.sucesso).remove();
                    $("#error_area").text("");
                    $("#success_area").text("Agendamento(s) realizado(s) com sucesso.");
                }else{
                    $(".tr-"+data.erro).css('background-color', '#f2dede');
                    $("#success_area").text("");
                    $("#error_area").text("Falha ao gravar agendamento! \n" + data.mensagem);
                }
            },
        })
    }

    editarAgendamentoNoBanco() {
        $.ajax({
            url: 'cockpit_editar_agendamento.php',
            type: "POST",
            data: $('form#confirmar-remover-agendamento').serialize(),
            complete: (data) => {
                data = $.parseJSON(data.responseText);
                alert(data.editar);                
            },
        })   
    }

    removerAgendamentoNoBanco() {
        $.ajax({
            url: 'cockpit_remover_agendamento.php',
            type: "POST",
            data: { remover_tecnico_agenda: $('input#confirmar-tecnico-agenda').val(), fabricaId: $("#confirmar-fabrica-id").val(), os: $("#id-os-agendamento").val() },
            complete: (data) => {
                data = data.responseText
            },
        })   
    }

    cancelarAgendamentoNoBanco(tecnico_agenda, numos) {
    
        var motivoCancelamento = prompt("Informe o motivo do cancelamento");

        if(motivoCancelamento.length > 0){
            $.ajax({
                url: 'cockpit_remover_agendamento.php',
                type: "POST",
                data: { remover_tecnico_agenda: tecnico_agenda, fabricaId: $("#confirmar-fabrica-id").val(), os: numos, motivo_cancelamento: motivoCancelamento },
                beforeSend: function () {
                    $(".loading_img_"+tecnico_agenda).show();                    
                },
                complete: (data) => {
                    $(".exportar_"+tecnico_agenda).text('');
                    $(".loading_img_"+tecnico_agenda).hide();
                    data = data.responseText
                    if(data){
                        alert(data);
                    }
                },
            })   
        }
    }

    atualizarDataDoEventoNoBanco(tecnicoAgendaId, dataInicio, dataFinal) {
        $.ajax({
            url: 'cockpit_editar_data_agendamento.php',
            type: "POST",
            data: { 
                tecnico_agenda : tecnicoAgendaId,
                data_inicio : dataInicio,
                data_final : dataFinal,
            },
            complete: (data) => {
                data = data.responseText
            },
        })   
    }

    persistirEventoEditadoNoBanco(agendamento) {
        $.ajax({
            url: 'cockpit_adicionar_agendamento.php',
            type: "POST",
            data: agendamento,
            complete: (data) => {
                data = data.responseText
                var dados = $.parseJSON(data);
                alert(dados.mensagem);
                window.location.href='cockpit.php';
            },
        })
    }

    obtemAgendamentosDoMes(mes) {
        if (this.mesesRecuperados.indexOf(parseInt(mes)) >= 0) {
            return
        }

        this.mesesRecuperados.push(parseInt(mes))

        $.ajax({
            url: 'cockpit_recuperar_agendamento.php',
            type: "POST",
            data: { 
                mes: mes,
                fabrica_id: $('input#fabrica-id').val(),
            },
            complete: (data) => {
                let objetosAgendamento = JSON.parse(data.responseText)
                this.inserirEventosDoBancoNoCalendario(objetosAgendamento)
            },
        })
    }

    obtemAgendamentosPorOS(os) {
        var mes = null;
        var objetosAgendamento = "";
        $.ajax({
            url: 'cockpit_recuperar_agendamento.php',
            type: "POST",
            data: { 
                mes: mes,
                os: os,
                fabrica_id: $('input#fabrica-id').val(),
            },
            complete: (data) => {
                objetosAgendamento = JSON.parse(data.responseText);
                this.editarAgendamentoPesquisa(objetosAgendamento[0]);               
            }
        })
    }

    fecharModal(){
        window.location.href='cockpit.php';
    }
}
