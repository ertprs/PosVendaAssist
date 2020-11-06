
//Define calendar(s): addCalendar ("Unique Calendar Name", "Window title", "Form element's name", Form name")

// Calendários das telas de Cadastramento de Ordem de Serviço
addCalendar("dataOSAbertura", "Selecione a Data", "data_abertura", "frm_os");
addCalendar("dataOSFechamento", "Selecione a Data", "data_fechamento", "frm_os");
addCalendar("dataOSNf", "Selecione a Data", "data_nf", "frm_os");
addCalendar("dataPesquisaInicial_01", "Selecione a Data", "data_inicial_01", "frm_pesquisa"); // usado em os_revenda_parametros.php
addCalendar("dataPesquisaFinal_01"  , "Selecione a Data", "data_final_01"  , "frm_pesquisa");
// usado na sedex_parametros.php
addCalendar("DataDespesaInicial", "Selecione a Data", "data_inicial_01", "frmdespesa");
addCalendar("DataDespesaFinal"  , "Selecione a Data", "data_final_01"  , "frmdespesa");
addCalendar("dataFechamento"  , "Selecione a Data", "data_fechamento"  , "frm_os"); // usado na os_fechamento.php

addCalendar("DataInicial"  , "Selecione a Data", "data_inicial"  , "frm_relatorio"); // Usado no programa os_relatorio_blackedecker.php
addCalendar("DataFinal"  , "Selecione a Data", "data_final"  , "frm_relatorio"); // Usado no programa os_relatorio_blackedecker.php

addCalendar("DataInicialComunicado"  , "Selecione a Data", "data_inicial"  , "frm_comunicado"); // Usado no programa comunicado_mostra.php
addCalendar("DataFinalComunicado"    , "Selecione a Data", "data_final"    , "frm_comunicado"); // Usado no programa comunicado_mostra.php

// default settings for English
// Uncomment desired lines and modify its values
setFont("Verdana, Arial, Helvetica, sans-serif", 9);
setWidth(90, 1, 15, 1);
//setColor("#cccccc", "#cccccc", "#ffffff", "#ffffff", "#333333", "#cccccc", "#333333");
// setFontColor("#333333", "#333333", "#333333", "#ffffff", "#333333");
setFormat("dd/mm/yyyy");
// setSize(200, 200, -200, 16);

// setWeekDay(0);
setMonthNames("Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
setDayNames("Domingo", "Segunda-Feira", "Terça-Feira", "Quarta--Feira", "Quinta-Feira", "Sexta-Feira", "Sábado");
setLinkNames("[Fechar]", "[Limpar]");
