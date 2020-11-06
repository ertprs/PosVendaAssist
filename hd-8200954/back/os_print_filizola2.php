<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (strlen($HTTP_GET_VARS['os']) > 0) {
	$os = $HTTP_GET_VARS['os'];
}else{
	header ("Location: os_relacao.php");
	exit;
}

#------------ Le OS da Base de dados ------------#
if (strlen ($os) > 0) {
	$sql = "SELECT  * FROM vw_os_print WHERE os = $os AND posto = $login_posto";

#echo "<br>".$sql."<br>";

	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$sua_os				= pg_result ($res,0,sua_os);
		$data_abertura		= pg_result ($res,0,data_abertura);
		$chamado			= pg_result ($res,0,chamado);
		$quem_abriu_chamado	= pg_result ($res,0,quem_abriu_chamado);
		$obs				= pg_result ($res,0,obs);
		$descricao_equipamento = pg_result ($res,0,descricao_equipamento);
		$nome_comercial     = pg_result ($res,0,nome_comercial);
		$defeito_reclamado  = pg_result ($res,0,defeito_reclamado);
		$cliente			= pg_result ($res,0,cliente);
		$cliente_nome		= pg_result ($res,0,cliente_nome);
		$cliente_cpf		= pg_result ($res,0,cliente_cpf);
		$cliente_rg 		= pg_result ($res,0,cliente_rg);
		$cliente_endereco	= pg_result ($res,0,cliente_endereco);
		$cliente_numero		= pg_result ($res,0,cliente_numero);
		$cliente_complemento= pg_result ($res,0,cliente_complemento);
		$cliente_bairro		= pg_result ($res,0,cliente_bairro);
		$cliente_cep		= pg_result ($res,0,cliente_cep);
		$cliente_cidade		= pg_result ($res,0,cliente_cidade);
		$cliente_fone		= pg_result ($res,0,cliente_fone);
		$cliente_nome		= pg_result ($res,0,cliente_nome);
		$cliente_estado		= pg_result ($res,0,cliente_estado);
		$cliente_contrato	= pg_result ($res,0,cliente_contrato);
		$posto_endereco		= pg_result ($res,0,posto_endereco);
		$posto_numero		= pg_result ($res,0,posto_numero);
		$posto_cep			= pg_result ($res,0,posto_cep);
		$posto_cidade		= pg_result ($res,0,posto_cidade);
		$posto_estado		= pg_result ($res,0,posto_estado);
		$posto_fone			= pg_result ($res,0,posto_fone);
		$posto_cnpj			= pg_result ($res,0,posto_cnpj);
		$posto_ie			= pg_result ($res,0,posto_ie);
	}
}

$title = "Ordem de Serviço Balcão - Impressão";

?>

<html>

<head>

	<title><? echo $title ?></title>

	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<meta name      ="Generator"     content="na mão...">
	<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
	<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">

	<link type="text/css" rel="stylesheet" href="css/css_press.css">

</head>

<style type="text/css">

body {
	margin: 0px,0px,0px,0px;
}

.titulo {
	font-family: normal Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 7px;
	text-align: left;
	color: #000000;
	background: #ffffff;
	border-bottom: dotted 1px #000000;
	/*border-right: dotted 1px #a0a0a0;*/
 	border-left: dotted 1px #000000;
	padding: 1px,1px,1px,1px;
}

.conteudo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	text-align: left;
	background: #ffffff;
	border-right: dotted 1px #a0a0a0;
	/*border-left: dotted 1px #a0a0a0;*/
	border-bottom: dotted 1px #a0a0a0;
	padding: 1px,1px,1px,1px;
}

.borda {
	border: solid 1px #c0c0c0;
}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	border: 1px solid #a0a0a0;
	color:#000000;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 15px;
	font-weight: normal;
	border: 1px solid #a0a0a0;
}

.table_line1 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 09px;
	font-weight: normal;
	border: 1px solid #a0a0a0;
}
</style>

<body>

<TABLE width="600px" border="0" cellspacing="1" cellpadding="0">
<TR class="titulo" style="text-align: center;">
<?
	if ($cliente_contrato == 'f') 
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
	else
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome)."_contrato.gif";
?>
	<TD rowspan="2" style="text-align: left;"><IMG SRC="<? echo $img_contrato ?>" ALT="ORDEM DE SERVIÇO"></TD>
	<TD style="font-size: 09px;">INDÚSTRIAS FILIZOLA S/A</TD>
	<TD>DATA EMISSÃO</TD>
	<TD>NÚMERO</TD>
</TR>

<TR class="titulo">
	<TD style="font-size: 09px; text-align: center; width: 350px; ">
<?
	########## CABECALHO COM DADOS DO POSTOS ########## 
	echo $posto_endereco .",".$posto_numero." - CEP ".$posto_cep."<br>";
	echo $posto_cidade ." - ".$posto_estado." - Telefone: ".$posto_fone."<br>";
	echo "CNPJ/CPF ".$posto_cnpj ." - IE/RG ".$posto_ie;
?>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 14px;">
<?	########## DATA DE ABERTURA ########## ?>
		<b><? echo $data_abertura ?></b>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 14px;">
<?	########## SUA OS ########## ?>
		<b><? echo $sua_os ?></b>
	</TD>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="conteudo" colspan="7">Dados do Cliente</TD>
</TR>

<?	########## DADOS DO CLIENTE ########## ?>

<?
if (strlen (trim ($cliente_ie)) == 0) $cliente_ie = "&nbsp";


switch (strlen (trim ($cliente_cpf))) {
case 0:
	$cliente_cpf = "&nbsp";
	break;
case 11:
	$cliente_cpf = substr ($cliente_cpf,0,3) . "." . substr ($cliente_cpf,3,3) . "." . substr ($cliente_cpf,6,3) . "-" . substr ($cliente_cpf,9,2);
	break;
case 14:
	$cliente_cpf = substr ($cliente_cpf,0,2) . "." . substr ($cliente_cpf,2,3) . "." . substr ($cliente_cpf,5,3) . "/" . substr ($cliente_cpf,8,4) . "-" . substr ($cliente_cpf,12,2);
	break;
}

?>

<TR>
	<TD class="titulo">Raz.Soc.</TD>
	<TD class="conteudo" colspan='2'><? echo $cliente_nome ?>&nbsp</TD>
	<TD class="titulo">CNPJ</TD>
	<TD class="conteudo"><? echo $cliente_cpf ?>&nbsp</TD>
	<TD class="titulo">I.E.</TD>
	<TD class="conteudo"><? echo $cliente_rg ?>&nbsp</TD>
</TR>

<!-- ====== ENDEREÇO E TELEFONE ================ -->
<TR>
	<TD class="titulo">Endereço</TD>
	<TD class="conteudo" colspan='2'><? echo $cliente_endereco . ", " . $cliente_numero . " " . $cliente_complenento . $cliente_bairro ?>&nbsp</TD>
	<TD class="titulo">CEP</TD>
	<TD class="conteudo"><? echo $cliente_cep ?>&nbsp</TD>
	<TD class="titulo">Telefone</TD>
	<TD class="conteudo"><? echo $cliente_fone ?>&nbsp</TD>
</TR>

<!-- ====== Cep Municipio UF ================ -->
<TR>
	<TD class="titulo">Motivo</TD>
	<TD class="conteudo" colspan='2'><? 
	if (strlen (trim ($nome_comercial)) > 0) {
#		echo $nome_comercial ;
		echo $descricao_equipamento;
	}else{
		echo $descricao_equipamento;
	}
	echo " / " . $defeito_reclamado ?>&nbsp</TD>
	<TD class="titulo">Municipio</TD>
	<TD class="conteudo"><? echo $cliente_cidade ?>&nbsp</TD>
	<TD class="titulo">Estado</TD>
	<TD class="conteudo"><? echo $cliente_estado ?>&nbsp</TD>
</TR>

<!-- ====== CONTATO E CHAMADO ================ -->
<TR>

	<TD class="titulo">Contato</TD>
	<TD class="conteudo" colspan="2"><? echo $quem_abriu_chamado ?>&nbsp</TD>
	<TD class="titulo">Chamado</TD>
	<TD class="conteudo" colspan="3"><? echo $chamado ?>&nbsp</TD>
</TR>
<TR>
</TR>

<!-- ====== MOTIVO ================ -->
<TR>
	<TD class="titulo">Obs.:</TD>
	<TD class="conteudo" colspan="6"><? echo $obs ?></TD>
</TR>
</TABLE>

<!-- ======= CONTROLE DE HORAS ========= -->

<TABLE width="650" border="0" cellspacing="0" cellpadding="0">
<TR class="menu_top" bgcolor="#d0d0d0">
	<TD class="menu_top" bgcolor="#d0d0d0">&nbsp;</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan="2">SAÍDA</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan="2">CHEGADA</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan="2">SAÍDA</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan="2">CHEGADA</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan="2">SAÍDA</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan="2">CHEGADA</TD>
</TR>
<TR>
	<TD class="table_line">Local</TD>
	<TD class="table_line" colspan="2">&nbsp;</TD>
	<TD class="table_line" colspan="2">&nbsp;</TD>
	<TD class="table_line" colspan="2">&nbsp;</TD>
	<TD class="table_line" colspan="2">&nbsp;</TD>
	<TD class="table_line" colspan="2">&nbsp;</TD>
	<TD class="table_line" colspan="2">&nbsp;</TD>
</TR>

<TR class="table_line1">
	<TD class="table_line1">DATA</TD>
	<TD class="table_line1" width="50">HORA</TD>
	<TD class="table_line1" width="50">Km</TD>
	<TD class="table_line1" width="50">HORA</TD>
	<TD class="table_line1" width="50">Km</TD>
	<TD class="table_line1" width="50">HORA</TD>
	<TD class="table_line1" width="50">Km</TD>
	<TD class="table_line1" width="50">HORA</TD>
	<TD class="table_line1" width="50">Km</TD>
	<TD class="table_line1" width="50">HORA</TD>
	<TD class="table_line1" width="50">Km</TD>
	<TD class="table_line1" width="50">HORA</TD>
	<TD class="table_line1" width="50">Km</TD>
</TR>

<TR class="table_line">
	<TD class="table_line">__/__/____</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR class="table_line">
	<TD class="table_line">__/__/____</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR class="table_line">
	<TD class="table_line">__/__/____</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR class="table_line">
	<TD class="table_line">__/__/____</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR class="table_line">
	<TD class="table_line">__/__/____</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR class="table_line">
	<TD class="table_line1" colspan="2">Veículo Utilizado:</TD>
	<TD class="table_line" colspan="6">&nbsp;</TD>
	<TD class="table_line1" colspan="2">Chapa:</TD>
	<TD class="table_line" colspan="3">&nbsp;</TD>
</TR>
</TABLE>
<P>
<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">SÉRIE</TD>
	<TD class="conteudo"   width="80"><!-- <? echo $cliente_nome ?> -->&nbsp</TD>
	<TD class="titulo">CAPACIDADE</TD>
	<TD class="conteudo" width="80"><!-- <? echo $cliente_cpf ?> -->&nbsp</TD>
	<TD class="titulo">MODELO</TD>
	<TD class="conteudo" colspan="2"><? echo $descricao_equipamento ?>&nbsp</TD>
	<TD class="titulo">INSTALAÇÃO</TD>
	<TD class="conteudo">___/___/____</TD>
</TR>
<TR>
	<TD class="titulo">FATURA</TD>
	<TD class="conteudo" colspan='6'><!-- <? echo $cliente_nome ?> -->&nbsp</TD>
	<TD class="titulo">GARANTIA</TD>
	<TD class="conteudo">___/___/____</TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">ANORMALIDADES ENCONTRADAS</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">&nbsp;</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">&nbsp;</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">&nbsp;</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">CAUSA DAS ANORMALIDADES</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">&nbsp;</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">&nbsp;</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">&nbsp;</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">MEDIDAS CORRETIVAS</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">&nbsp;</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">&nbsp;</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">&nbsp;</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">PEÇAS SUBSTITUIDAS</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">&nbsp;</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">RECOMENDAÇÕES AO CLIENTE</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">&nbsp;</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">PARÂMETROS ENCONTRADOS</TD>
	<TD class="conteudo">P4:</TD>
	<TD class="conteudo" width="70">&nbsp;</TD>
	<TD class="conteudo">P5:</TD>
	<TD class="conteudo" width="70">&nbsp;</TD>
	<TD class="conteudo">P6:</TD>
	<TD class="conteudo" width="70">&nbsp;</TD>
	<TD class="conteudo">LACRE ENCONTRADO:</TD>
	<TD class="conteudo" width="70">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">PARÂMETROS ATUAIS</TD>
	<TD class="conteudo">P4:</TD>
	<TD class="conteudo">&nbsp;</TD>
	<TD class="conteudo">P5:</TD>
	<TD class="conteudo">&nbsp;</TD>
	<TD class="conteudo">P6:</TD>
	<TD class="conteudo">&nbsp;</TD>
	<TD class="conteudo">LACRE ATUAL:</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">OBSERVAÇÕES</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">&nbsp;</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
</TABLE>


<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="conteudo">LIBERAÇÃO FINANCEIRO</TD>
	<TD class="conteudo">CLIENTE: CONFIRMAMOS A EXECUÇÃO DOS SERVIÇOS</TD>
</TR>
<TR>
	<TD class="conteudo">___/___/______</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<TR>
	<TD class="conteudo">&nbsp; Visto</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<TR>
	<TD class="conteudo">&nbsp;</TD>
	<TD class="conteudo">Carimbo e Assinatura</TD>
</TR>
</TABLE>
<P>
<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">TIPO DA OS</TD>
	<TD class="conteudo">&nbsp;</TD>
	<TD class="titulo">CONDIÇÃO DE PAGAMENTO</TD>
	<TD class="conteudo">&nbsp;</TD>
	<TD class="titulo">VALOR R$</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
</TABLE>

<!-- <script language="JavaScript">
	window.print();
</script> -->


</BODY>
</html>

