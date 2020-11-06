<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE> New Document </TITLE>
<META NAME="Generator" CONTENT="EditPlus">
<META NAME="Author" CONTENT="">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">
</HEAD>


<?

if(strlen($_POST['bancadas_1']) > 0 ) $bancadas_1 = trim($_POST['bancadas_1']);
else $msg_erro = "Selecione Sim ou Não para FORRAÇÃO PARA PREVENIR RISCOS NOS APARELHOS";

if(strlen($_POST['bancadas_2']) > 0 ) $bancadas_2 = trim($_POST['bancadas_2']);
else $msg_erro = "Selecione Sim ou Não para DISJUNTOR ELETROMAGNÉTICO";

if(strlen($_POST['bancadas_3']) > 0 ) $bancadas_3 = trim($_POST['bancadas_3']);
else $msg_erro = "Selecione Sim ou Não para TRANSFORMADOR ISOLADOR";

if(strlen($_POST['bancadas_4']) > 0 ) $bancadas_4 = trim($_POST['bancadas_4']);
else $msg_erro = "Selecione Sim ou Não para EM CASO DE TV, LAMPADA SÉRIE";

if(strlen($_POST['bancadas_5']) > 0 ) $bancadas_5 = trim($_POST['bancadas_5']);
else $msg_erro = "Selecione Sim ou Não para ILUMINAÇÃO INDIVIDUAL";

if(strlen($_POST['bancadas_6']) > 0 ) $bancadas_6 = trim($_POST['bancadas_6']);
else $msg_erro = "Selecione Sim ou Não para SUPORTE SUPERIOR PARA INSTRUMENTOS";

//recepcao
if(strlen($_POST['recepcao_1']) > 0 ) $recepcao_1 = trim($_POST['recepcao_1']);
else $msg_erro = "Selecione Sim ou Não para LOCAL E EQUIPAMENTOS ESPECÍFICO PARA TESTES DOS APARELHOS CONSERTADOS";

if(strlen($_POST['recepcao_2']) > 0 ) $recepcao_2 = trim($_POST['recepcao_2']);
else $msg_erro = "Selecione Sim ou Não para LOCAL ESPECÍFICO PARA O CLIENTE ESPERAR";

if(strlen($_POST['recepcao_3']) > 0 ) $recepcao_3 = trim($_POST['recepcao_3']);
else $msg_erro = "Selecione Sim ou Não para BALCÃO OU LOCAL DE ATENDIMENTO SEPARADO DA OFICINA";


//Deposito
if(strlen($_POST['deposito_1']) > 0 ) $deposito_1 = trim($_POST['deposito_1']);
else $msg_erro = "Selecione Sim ou Não para PRATELEIRAS PARA TODOS OS APARELHOS";

if(strlen($_POST['deposito_2']) > 0 ) $deposito_2 = trim($_POST['deposito_2']);
else $msg_erro = "Selecione Sim ou Não para AS PRATELEIRAS SÃO FORRADAS PARA EVITAR RISCOS NOS APARELHOS";

if(strlen($_POST['deposito_3']) > 0 ) $deposito_3 = trim($_POST['deposito_3']);
else $msg_erro = "Selecione Sim ou Não para É DIVIDIDO EM ÁREAS COMO : PRONTOS, AG.PEÇA, AG.APROVAÇÃO DE ORÇAMENTO, GARANTIA , ETC.";


//Estoque
if(strlen($_POST['estoque_1']) > 0 ) $estoque_1 = trim($_POST['estoque_1']);
else $msg_erro = "Selecione Sim ou Não para CONTROLES ITEM A ITEM DAS QUANTIDADES";

if(strlen($_POST['estoque_2']) > 0 ) $estoque_2 = trim($_POST['estoque_2']);
else $msg_erro = "Selecione Sim ou Não para COMPUTADOR EXCLUSIVO PARA USO NO ESTOQUE";

if(strlen($_POST['estoque_3']) > 0 ) $estoque_3 = trim($_POST['estoque_3']);
else $msg_erro = "Selecione Sim ou Não para CONTROLE DE REQUISIÇÕES DE PEÇAS";

if(strlen($_POST['estoque_4']) > 0 ) $estoque_4 = trim($_POST['estoque_4']);
else $msg_erro = "Selecione Sim ou Não para ACOMODAÇÃO CORRETA DOS COMPONENTES";


//Área técnica
if(strlen($_POST['tecnicos_formados']) > 0 ) $tecnicos_formados = trim($_POST['tecnicos_formados']);
else $msg_erro = "Digite os técnicos formados";

if(strlen($_POST['tecnicos_cada_area']) > 0 ) $tecnicos_cada_area = trim($_POST['tecnicos_cada_area']);
else $msg_erro = "Digite os técnicos para cada área";

if(strlen($_POST['tecnicos_qtde']) > 0 ) $tecnicos_qtde = trim($_POST['tecnicos_qtde']);
else $msg_erro = "Digite a quantidade de técnicos";

//if(strlen($_POST['tecnicos_treinados']) > 0 ) $tecnicos_treinados = trim($_POST['tecnicos_treinados']);
//else $msg_erro = "Digite os técnicos treinados";

?>








<BODY>

<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='1' style='font-size: 12px'>

<TR>
	<TD align='center' style='font-size: 18px'><b>Questionário de Estrutura</b></TD>
</TR>
</TABLE>
<BR>
<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='1' style='font-size: 12px'>
<TR align='center' bgcolor='#9699A0' style='font-weight: bold'>
	<TD width='550'>INSTRUMENTOS</TD>
	<TD>MODELO</TD>
	<TD>QUANT.</TD>
</TR>
<TR>
	<TD>MULTÍMETRO DIGITAL</TD>
	<TD><INPUT TYPE="text" NAME="modelo_2"></TD>
	<TD><INPUT TYPE="text" NAME="qtde_2"></TD>
</TR>
<TR>
	<TD>MULTÍMETRO ANALÓGICO</TD>
	<TD><INPUT TYPE="text" NAME="modelo_3"></TD>
	<TD><INPUT TYPE="text" NAME="qtde_3"></TD>
</TR>
<TR>
	<TD>GERADOR DE BARRAS</TD>
	<TD><INPUT TYPE="text" NAME="modelo_4"></TD>
	<TD><INPUT TYPE="text" NAME="qtde_4"></TD>
</TR>
<TR>
	<TD>GERADOR DE ÁUDIO</TD>
	<TD><INPUT TYPE="text" NAME="modelo_5"></TD>
	<TD><INPUT TYPE="text" NAME="qtde_5"></TD>
</TR>
<TR>
	<TD>GERADOR DE RF</TD>
	<TD><INPUT TYPE="text" NAME="modelo_6"></TD>
	<TD><INPUT TYPE="text" NAME="qtde_6"></TD>
</TR>
<TR>
	<TD>GERADOR DE RF</TD>
	<TD><INPUT TYPE="text" NAME="modelo_7"></TD>
	<TD><INPUT TYPE="text" NAME="qtde_7"></TD>
</TR>
<TR>
	<TD>LASER POWER METER</TD>
	<TD><INPUT TYPE="text" NAME="modelo_8"></TD>
	<TD><INPUT TYPE="text" NAME="qtde_8"></TD>
</TR>
<TR>
	<TD>ANALISADOR DE CINESCÓPIOS</TD>
	<TD><INPUT TYPE="text" NAME="modelo_9"></TD>
	<TD><INPUT TYPE="text" NAME="qtde_9"></TD>
</TR>
<TR>
	<TD>SIMULADOR DE LINHA TELEFÔNICA</TD>
	<TD><INPUT TYPE="text" NAME="modelo_10"></TD>
	<TD><INPUT TYPE="text" NAME="qtde_10"></TD>
</TR>
<TR>
	<TD>ESTAÇÃO DE SOLDA COM TEMPERATURA CONTROLADA</TD>
	<TD><INPUT TYPE="text" NAME="modelo_11"></TD>
	<TD><INPUT TYPE="text" NAME="qtde_11"></TD>
</TR>
<TR>
	<TD>ESTAÇÃO DE SOLDA A AR QUENTE</TD>
	<TD><INPUT TYPE="text" NAME="modelo_12"></TD>
	<TD><INPUT TYPE="text" NAME="qtde_12"></TD>
</TR>
<TR>
	<TD>PULSEIRA ANTI-ESTÁTICA</TD>
	<TD><INPUT TYPE="text" NAME="modelo_14"></TD>
	<TD><INPUT TYPE="text" NAME="qtde_14"></TD>
</TR>
<TR>
	<TD>MANTA ANTI-ESTÁTICA</TD>
	<TD><INPUT TYPE="text" NAME="modelo_15"></TD>
	<TD><INPUT TYPE="text" NAME="qtde_15"></TD>
</TR>
<TR>
	<TD>FERRO DE SOLDAR</TD>
	<TD><INPUT TYPE="text" NAME="modelo_16"></TD>
	<TD><INPUT TYPE="text" NAME="qtde_16"></TD>
</TR>
<TR>
	<TD>PARAFUSADEIRA</TD>
	<TD><INPUT TYPE="text" NAME="modelo_17"></TD>
	<TD><INPUT TYPE="text" NAME="qtde_17"></TD>
</TR>
</TABLE>
<br>
<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='1' style='font-size: 12px'>
<TR bgcolor='#9699A0' style='font-weight: bold'>
	<TD width='550'>AS BANCADAS POSSUEM:</TD>
	<TD>SIM</TD>
	<TD>NÂO</TD>
</TR>
<TR>
	<TD>FORRAÇÃO PARA PREVENIR RISCOS NOS APARELHOS</TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_1" value='sim'></TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_1" value='nao'></TD>
</TR>
<TR>
	<TD>DISJUNTOR ELETROMAGNÉTICO</TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_2" value='sim'></TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_2" value='nao'></TD>
</TR>
<TR>
	<TD>TRANSFORMADOR ISOLADOR</TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_3" value='sim'></TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_3" value='nao'></TD>
</TR>
<TR>
	<TD>EM CASO DE TV, LAMPADA SÉRIE</TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_4" value='sim'></TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_4" value='nao'></TD>
</TR>
<TR>
	<TD>ILUMINAÇÃO INDIVIDUAL</TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_5" value='sim'></TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_5" value='nao'></TD>
</TR>
<TR>
	<TD>SUPORTE SUPERIOR PARA INSTRUMENTOS</TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_6" value='sim'></TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_6" value='nao'></TD>
</TR>
</TABLE>
<br>

<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='1' style='font-size: 12px'>
<TR bgcolor='#9699A0' style='font-weight: bold'>
	<TD width='550'>A RECEPÇÂO POSSUI:</TD>
	<TD>SIM</TD>
	<TD>NÂO</TD>
</TR>
<TR>
	<TD>LOCAL E EQUIPAMENTOS ESPECÍFICO PARA TESTES DOS APARELHOS CONSERTADOS</TD>
	<TD><INPUT TYPE="radio" NAME="recpcao_1" value='sim'></TD>
	<TD><INPUT TYPE="radio" NAME="recpcao_1" value='nao'></TD>
</TR>
<TR>
	<TD>LOCAL ESPECÍFICO PARA O CLIENTE ESPERAR</TD>
	<TD><INPUT TYPE="radio" NAME="recpcao_2" value='sim'></TD>
	<TD><INPUT TYPE="radio" NAME="recpcao_2" value='nao'></TD>
</TR>
<TR>
	<TD>BALCÃO OU LOCAL DE ATENDIMENTO SEPARADO DA OFICINA</TD>
	<TD><INPUT TYPE="radio" NAME="recpcao_3" value='sim'></TD>
	<TD><INPUT TYPE="radio" NAME="recpcao_3" value='nao'></TD>
</TR>
</TABLE>
<br>

<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='1' style='font-size: 12px'>
<TR bgcolor='#9699A0' style='font-weight: bold'>
	<TD width='550'>DEPÓSITO DE APARELHOS POSSUI:</TD>
	<TD>SIM</TD>
	<TD>NÂO</TD>
</TR>
<TR>
	<TD>PRATELEIRAS PARA TODOS OS APARELHOS</TD>
	<TD><INPUT TYPE="radio" NAME="deposito_1" value='sim'></TD>
	<TD><INPUT TYPE="radio" NAME="deposito_1" value='nao'></TD>
</TR>
<TR>
	<TD>AS PRATELEIRAS SÃO FORRADAS PARA EVITAR RISCOS NOS APARELHOS</TD>
	<TD><INPUT TYPE="radio" NAME="deposito_2" value='sim'></TD>
	<TD><INPUT TYPE="radio" NAME="deposito_2" value='nao'></TD>
</TR>
<TR>
	<TD>É DIVIDIDO EM ÁREAS COMO : PRONTOS, AG.PEÇA, AG.APROVAÇÃO DE ORÇAMENTO, GARANTIA , ETC.</TD>
	<TD><INPUT TYPE="radio" NAME="deposito_3" value='sim'></TD>
	<TD><INPUT TYPE="radio" NAME="deposito_3" value='nao'></TD>
</TR>
</TABLE>
<br>

<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='1' style='font-size: 12px'>
<TR bgcolor='#9699A0' style='font-weight: bold'>
	<TD width='550'>O ESTOQUE POSSUI:</TD>
	<TD>SIM</TD>
	<TD>NÂO</TD>
</TR>
<TR>
	<TD>CONTROLES ITEM A ITEM DAS QUANTIDADES</TD>
	<TD><INPUT TYPE="radio" NAME="estoque_1" value='sim'></TD>
	<TD><INPUT TYPE="radio" NAME="estoque_1" value='nao'></TD>
</TR>
<TR>
	<TD>COMPUTADOR EXCLUSIVO PARA USO NO ESTOQUE</TD>
	<TD><INPUT TYPE="radio" NAME="estoque_2" value='sim'></TD>
	<TD><INPUT TYPE="radio" NAME="estoque_2" value='nao'></TD>
</TR>
<TR>
	<TD>CONTROLE DE REQUISIÇÕES DE PEÇAS</TD>
	<TD><INPUT TYPE="radio" NAME="estoque_3" value='sim'></TD>
	<TD><INPUT TYPE="radio" NAME="estoque_3" value='nao'></TD>
</TR>
<TR>
	<TD>ACOMODAÇÃO CORRETA DOS COMPONENTES</TD>
	<TD><INPUT TYPE="radio" NAME="estoque_4" value='sim'></TD>
	<TD><INPUT TYPE="radio" NAME="estoque_4" value='nao'></TD>
</TR>
</TABLE>
<br>

<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='1' style='font-size: 12px'>
<TR align='center' bgcolor='#9699A0' style='font-weight: bold'>
	<TD>ÁREA TÉCNICA</TD>
</TR>
<TR>
	<TD>
		TÉCNICOS FORMADOS EM :( DESCREVA)<br>
		<TEXTAREA NAME="tecnicos_formados" ROWS="3" COLS="70"></TEXTAREA>
	</TD>
</TR>
<TR>
	<TD>
		POSSUI UM TÉCNICO PARA CADA ÁREA (áudio, vídeo, etc)<br>
		<TEXTAREA NAME="tecnicos_cada_area" ROWS="3" COLS="70"></TEXTAREA>
	</TD>
</TR>
<TR>
	<TD>
		QUANTOS TÉCNICOS ?<br>
		<TEXTAREA NAME="tecnicos_qtde" ROWS="1" COLS="70"></TEXTAREA>
	</TD>
</TR>
<TR>
	<TD>
		TREINAMENTOS QUE OS TÉCNICOS JÁ FIZERAM:( DESCREVA)<br>
		<TEXTAREA NAME="tecnicos_treinados" ROWS="3" COLS="70"></TEXTAREA>
	</TD>
</TR>
</TABLE>


</BODY>
</HTML>
