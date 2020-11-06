
//Define calendar(s): addCalendar ("Unique Calendar Name", "Window title", "Form element's name", Form name")

// Calendários das telas de Cadastramento de Ordem de Serviço
addCalendar("dataPesquisaInicial_01", "Selecione a Data", "data_inicial_01", "frm_pesquisa");
addCalendar("dataPesquisaFinal_01"  , "Selecione a Data", "data_final_01"  , "frm_pesquisa");
addCalendar("dataPesquisaInicial_01_1", "Selecione a Data", "data_inicial_01", "frm_pesquisa2");
addCalendar("dataPesquisaFinal_01_1"  , "Selecione a Data", "data_final_01"  , "frm_pesquisa2");
addCalendar("dataPesquisaInicial_02", "Selecione a Data", "data_inicial_02", "frm_pesquisa2");
addCalendar("dataPesquisaFinal_02"  , "Selecione a Data", "data_final_02"  , "frm_pesquisa2");
addCalendar("dataPesquisaInicial_03", "Selecione a Data", "data_inicial_03", "frm_pesquisa3");
addCalendar("dataPesquisaFinal_03"  , "Selecione a Data", "data_final_03"  , "frm_pesquisa3");
addCalendar("dataPesquisaIni"  , "Selecione a Data", "data_abertura"  , "frm_os");
addCalendar("dataPesquisaFin"  , "Selecione a Data", "data_digitacao"  , "frm_os");
addCalendar("dataPesquisa"  , "Selecione a Data", "data_abertura"  , "frm_callcenter");
addCalendar("dataPesquisa2"  , "Selecione a Data", "baixado"  , "frm_extrato_os");
addCalendar("dataPesquisaInicial_04", "Selecione a Data", "data_inicial_01", "frmdespesa");
addCalendar("dataPesquisaFinal_04"  , "Selecione a Data", "data_final_01"  , "frmdespesa");
addCalendar("DataPesquisaInicial", "Selecione a Data", "data_inicial", "frm_consulta");
addCalendar("DataPesquisaFinal"  , "Selecione a Data", "data_final"  , "frm_consulta");
addCalendar("DataPesquisa", "Selecione a Data", "data", "frm_callcenter");

addCalendar("PesquisaInicial", "Selecione a Data", "data_inicial", "frm_os_aprovada"); // rel_visao_mix_total.php
addCalendar("PesquisaFinal"  , "Selecione a Data", "data_final"  , "frm_os_aprovada"); // rel_visao_mix_total.php

addCalendar("DataInicial", "Selecione a Data", "data_inicial", "frm_relatorio"); // auditoria_os_fechamento_blackedecker.php
addCalendar("DataFinal", "Selecione a Data", "data_final", "frm_relatorio"); // auditoria_os_fechamento_blackedecker.php

// OS_EXTRATO.PHP
addCalendar("dataPesquisaInicial_Extrato", "Selecione a Data", "data_inicial", "frm_extrato");
addCalendar("dataPesquisaFinal_Extrato"  , "Selecione a Data", "data_final"  , "frm_extrato");
addCalendar("data_Limite" , "Selecione a Data", "data_limite"  , "frm_extrato");

// rel_os_por_posto.php
addCalendar("DataPesquisaInicial", "Selecione a Data", "data_inicial", "frm_os_posto");
addCalendar("DataPesquisaFinal"  , "Selecione a Data", "data_final"  , "frm_os_posto");

addCalendar("dataPesquisa", "Selecione a Data", "baixado", "frm_extrato_os");

addCalendar("DataLimite01", "Selecione a Data", "data_limite_01", "FormExtrato");

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
setDayNames("Domingo", "Segunda-Feira", "Terça-Feira", "Quarta-Feira", "Quinta-Feira", "Sexta-Feira", "Sábado");
setLinkNames("[Fechar]", "[Limpar]");
