const cockpit = new Cockpit()

const configuracoes_169 = {
    defaultView: 'agendaWeek', // formato de semana
    contentHeight: "auto", // ajusta a altura do calendário
    allDaySlot: true, // linha do dia todo
    hiddenDays: [ 0 ], // remove o domingo do calendário
    minTime: '07:00:00',
    maxTime: '20:00:00',
    header: {
        left:   'title',
        center: '',
        right:  'month agendaWeek today prev,next'
    },
    events: [
    ],
    eventMouseover: function (event, jsEvent, view) {
        $(this).popover(
            {
                title: 'Dados do Agendamento',
                content: event.title+ ' - '+'Tecnico: '+event.usuario,
                placement: 'top,left',
                container: '',
            }
        )
        $(this).popover('show')
    },
    eventMouseout: function (event, jsEvent, view) {
        $(this).popover('hide')                
    },
    selectable: true,
    select: function (inicio, fim, jsEvent, view) {
        if (view.name == 'month') {
            let calendar = cockpit.getCalendar()
            calendar.changeView('agendaWeek')

            return
        }

        let dataDeInicioEvento = cockpit.obtemData(inicio._i)
        let dataDeTerminoEvento = cockpit.obtemData(fim._i)
        let eventoDiaInteiro = inicio._ambigTime

        if (cockpit.dataEventoAnteriorDataAtual(dataDeInicioEvento, dataDeTerminoEvento)) {
            alert('Data do evento anterior a data atual, por favor selecione outro período!')
        }

        cockpit.exibeModalDeAdicaoDeEvento(dataDeInicioEvento, dataDeTerminoEvento, eventoDiaInteiro)
    },
    eventClick: function (event, jsEvent) {
        cockpit.exibeModalConfirmacaoRemocaoDeEvento(event)
    },
    eventDrop: function (event) { // função chamada após mover o agendamento com drag and drop   
        console.log('teste');     
        /*let agendamento = {
            fabrica_id: event.fabrica_id,
            tecnico_id: event.tecnico_id,
            os: event.os,
            data_inicio: cockpit.obtemData(event.start._i),
            data_termino: cockpit.obtemData(event.end._i),
            confirmado: event.confirmado,
            descricao: event.informacoes,
        }

        cockpit.persistirEventoEditadoNoBanco(agendamento)*/
    },
    eventResizeStop: function (event) {
        console.log('eventResizeStop');
        // aplicar adição de agendamento ou ediação do mesmo, confirmar com Waldir a regra a ser seguida
    },
    viewRender: function (view, element) {
        let mesCalendario = moment(view.intervalEnd._d).format('M')
        let mesAtual = moment(Date.now()._d).format('M')

        if (mesCalendario != mesAtual) {
            let agendamentos = cockpit.obtemAgendamentosDoMes(mesCalendario)
        }
    },
}