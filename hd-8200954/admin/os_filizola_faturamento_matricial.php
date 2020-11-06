<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica != 7 ){
	header("Location: menu_os.php");
	exit;
}

// ###############################################################
// Funcao para calcular diferenca entre duas horas
// ###############################################################
function calcula_hora($hora_inicio, $hora_fim){
	// Explode
	$ehora_inicio = explode(":",$hora_inicio);
	$ehora_fim    = explode(":",$hora_fim);

	// Tranforma horas em minutos
	$mhora_inicio = ($ehora_inicio[0] * 60) + $ehora_inicio[1];
	$mhora_fim    = ($ehora_fim[0] * 60) + $ehora_fim[1];

	// Subtrai as horas
	$total_horas = ( $mhora_fim - $mhora_inicio );

	// Tranforma em horas
	$total_horas_div = $total_horas / 60;

	// Valor de horas inteiro
	$total_horas_int = intval($total_horas_div);

	// Resto da subtracao = pega minutos
	$total_horas_sub = $total_horas - ($total_horas_int * 60);

	// Horas trabalhadas
	if ($total_horas_sub < 10) $total_horas_sub = "0".$total_horas_sub;
	$horas_trabalhadas = $total_horas_int.":".$total_horas_sub;

	// Retorna valor
	return $horas_trabalhadas;
}
// ###############################################################

$msg_erro = "";
$msg_debug = "";

$qtde_visita = 3;

//if (strlen($_GET['os']) > 0)  $os = $_GET['os'];
//if (strlen($_POST['os']) > 0) $os = $_POST['os'];

if (strlen($_GET['os_faturamento']) > 0)  $os_faturamento = $_GET['os_faturamento'];
if (strlen($_POST['os_faturamento']) > 0) $os_faturamento = $_POST['os_faturamento'];

/*if (strlen($os) == 0) {
	header("Location: os_parametros.php");
	exit;
}*/

if (strlen($os_faturamento) > 0){

	$sql  = "SELECT	tbl_os_faturamento.cliente  ,
					tbl_os_faturamento.revenda  ,
					tbl_os_faturamento.obs      
			 FROM   tbl_os_faturamento
			 WHERE  tbl_os_faturamento.os_faturamento = $os_faturamento;";
	$res = pg_exec ($con,$sql);
	
	$cliente = pg_result($res,0,cliente);
	$revenda = pg_result($res,0,revenda);
	$obs     = pg_result($res,0,obs);
	$obs     = substr (trim ($obs) . str_repeat (" ",35) ,0,35);

	if (strlen($cliente) > 0){
		$sql = "SELECT  tbl_cliente.nome AS cliente_nome,
						tbl_cliente.endereco            ,
						tbl_cliente.numero              ,
						tbl_cliente.bairro              ,
						tbl_cliente.cep                 ,
						tbl_cidade.nome  AS cidade_nome ,
						tbl_cidade.estado               ,
						tbl_cliente.fone                ,
						tbl_cliente.cpf                 ,
						tbl_cliente.rg                    
				FROM tbl_cliente
				JOIN tbl_cidade USING (cidade)
				WHERE tbl_cliente.cliente = $cliente;";
		$res = pg_exec($con,$sql);
		
		if (pg_numrows($res) > 0) {
			$nome      = pg_result($res,0,cliente_nome); 
			$endereco  = pg_result($res,0,endereco);
			$numero    = pg_result($res,0,numero);
			$bairro    = pg_result($res,0,bairro);
			$cep       = pg_result($res,0,cep);
			$cidade    = pg_result($res,0,cidade_nome);
			$estado    = pg_result($res,0,estado); 
			$fone      = pg_result($res,0,fone);
			$cpf       = pg_result($res,0,cpf);
			$rg        = pg_result($res,0,rg);
		}

	}else if (strlen($revenda) > 0){
		$sql = "SELECT  tbl_revenda.nome AS revenda_nome,
						tbl_revenda.endereco            ,
						tbl_revenda.numero              ,
						tbl_revenda.bairro              ,
						tbl_revenda.cep                 ,
						tbl_cidade.nome AS cidade_nome  ,
						tbl_cidade.estado               ,
						tbl_revenda.cnpj                ,
						tbl_revenda.fone                ,
						tbl_revenda.fax                 ,
						tbl_revenda.contato             ,
						tbl_revenda.ie                  
				FROM tbl_revenda
				JOIN tbl_cidade USING (cidade)
				WHERE tbl_revenda.revenda = $revenda";
				$res = pg_exec($con,$sql);
		
		if (pg_numrows($res) > 0) {
			$nome      = pg_result($res,0,revenda_nome); 
			$endereco  = pg_result($res,0,endereco);
			$numero    = pg_result($res,0,numero);
			$bairro    = pg_result($res,0,bairro);
			$cep       = pg_result($res,0,cep);
			$cidade    = pg_result($res,0,cidade_nome);
			$estado    = pg_result($res,0,estado); 
			$fone      = pg_result($res,0,fone);
			$cpf       = pg_result($res,0,cnpj);
			$rg        = pg_result($res,0,ie);
			$fax       = pg_result($res,0,fax);
			$contato   = pg_result($res,0,contato);
		}
	}

	$nome            = substr (trim ($nome) . str_repeat (" ",35) ,0,35);
	$rg              = substr (trim ($rg) . str_repeat (" ",15) ,0,15); 
	switch (strlen (trim ($cpf))) {
	case 0:
		$cpf = "                  ";
		break;
	case 11:
		$cpf = substr ($cpf,0,3) . "." . substr ($cpf,3,3) . "." . substr ($cpf,6,3) . "-" . substr ($cpf,9,2);
		break;
	case 14:
		$cpf = substr ($cpf,0,2) . "." . substr ($cpf,2,3) . "." . substr ($cpf,5,3) . "/" . substr ($cpf,8,4) . "-" . substr ($cpf,12,2);
		break;
	}

	$endereco_numero = $endereco .", ". $numero;
	$endereco_numero = substr (trim ($endereco_numero) . str_repeat (" ",35) ,0,35);

	$cidade_estado = $cidade ." - ". $estado;
	$cidade_estado = substr (trim ($cidade_estado) . str_repeat (" ",27) ,0,27);

	$bairro			= substr (trim ($bairro) . str_repeat (" ",20) ,0,20);
	$cep            = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3);

	$fone			= substr (trim ($fone) . str_repeat (" ",14) ,0,14);

	$fax            = substr (trim ($fax) . str_repeat (" ",14) ,0,14);
	$contato        = substr (trim ($contato) . str_repeat (" ",15) ,0,15);
}

########## CABECALHO COM DADOS DO POSTOS ########## 

$conteudo .= "+------------------------------------------------------------------------------+\r\n";
$conteudo .= "|   ____    _         __   _                                                   |\r\n" ;
$conteudo .= "|  \    /  |_ | |  |    / | | |   /\        CNPJ: 60.613.445/0001-79           |\r\n" ;
$conteudo .= "| /_\  /_\ |  | |_ |  /__ |_| |_ /  \       RUA : JOAQUIM CARLOS, 1236         |\r\n" ;
$conteudo .= "|    \/                                     FONE: (11)60978811                 |\r\n";
$conteudo .= "|------------------------------------------------------------------------------|\r\n" ;
$conteudo .= "|                                                                              |\r\n" ;
$conteudo .= "|                    RELATÓRIO DE MOVIMENTO PARA FATURAMENTO                   |\r\n" ;
$conteudo .= "|                                                                              |\r\n" ;
$conteudo .= "+------------------------------------------------------------------------------+\r\n";
$conteudo .= "| RAZ.SOC .......: $nome                         |\r\n";
if (strlen($revenda) > 0){
$conteudo .= "| CNPJ ..........: $cpf        I.E ...........: $rg  |\r\n";
}else if (strlen($cliente) > 0){
$conteudo .= "| CPF ...........: $cpf        RG ............: $rg  |\r\n";
}
$conteudo .= "| ENDEREÇO ......: $endereco_numero                         |\r\n";
$conteudo .= "| BAIRRO ........: $bairro      CEP ...........:  $cep      |\r\n";
$conteudo .= "| CIDADE ........: $cidade_estado                                 |\r\n";
$conteudo .= "| FONE ..........: $fone                                              |\r\n"; 
if (strlen($revenda) > 0){
$conteudo .= "| FAX ...........: $fone            CONTATO .......: $contato  |\r\n"; 
}
$conteudo .= "+------------------------------------------------------------------------------+\r\n";
$conteudo .= "|                                   COBRANÇA                                   |\r\n";
$conteudo .= "|------------------------------------------------------------------------------|\r\n";
$conteudo .= "| ENDEREÇO ......: $endereco_numero                         |\r\n";
$conteudo .= "| BAIRRO ........: $bairro      CEP ...........:  $cep      |\r\n";
$conteudo .= "| CIDADE ........: $cidade_estado                                 |\r\n";
$conteudo .= "+------------------------------------------------------------------------------+\r\n";
$conteudo .= "\r\n";

/*$sql = "SELECT	tbl_os.sua_os        ,
				tbl_os.taxa_visita   ,
				(tbl_os.visita_por_km * tbl_os.qtde_km ) AS total_km    ,
				(tbl_os.hora_tecnica * tbl_os.qtde_hora) AS total_hora  ,
				(tbl_os.diaria * tbl_os.qtde_diaria) AS total_dias      
		FROM tbl_os 
		JOIN tbl_os_extra USING (os)
		JOIN tbl_os_faturamento USING (os_faturamento)
		WHERE tbl_os_faturamento.os_faturamento = $os_faturamento;";
	$res = pg_exec($con,$sql);

//$conteudo .= "( ".pg_numrows($res)." )\r\n";
	
	if (pg_numrows($res) > 0){
		for ($i = 0; $i < pg_numrows($res); $i++){
			$sua_os        = pg_result($res,$i,sua_os);
			$taxa_visita   = pg_result($res,$i,taxa_visita);
			$total_km      = pg_result($res,$i,total_km);
			$total_hora    = pg_result($res,$i,total_hora);
			$total_dias    = pg_result($res,$i,total_dias);

			$sua_os         = substr (trim ($sua_os) . str_repeat (" ",6) ,0,6);
			$taxa_visita    = substr (trim ($taxa_visita) . str_repeat (" ",8) ,0,8);
			$total_km       = substr (trim ($total_km) . str_repeat (" ",8) ,0,8);
			$total_hora     = substr (trim ($total_hora) . str_repeat (" ",8) ,0,8);
			$total_dias    = substr (trim ($total_dias) . str_repeat (" ",8) ,0,8);
			

			$conteudo .= "+------------------------------------------------------------------------------+\r\n";
			$conteudo .= "|      OS      |     VISITA     |   HORA TEC.   |    M.OBRA    |      KM       |\r\n";
			$conteudo .= "+--------------+----------------+---------------+--------------+---------------|\r\n";
			$conteudo .= "|  $sua_os      |  $taxa_visita      |    $total_hora   |              | $total_km      |\r\n";
			$conteudo .= "+------------------------------------------------------------------------------+\r\n";
			$conteudo .= "|   DIARIA     |     PEÇAS      |    RECOND     |             TOTAL            |\r\n";
			$conteudo .= "+--------------+----------------+---------------+------------------------------|\r\n";
			$conteudo .= "| $total_dias     |                |               |$total                              |\r\n";
			$conteudo .= "+------------------------------------------------------------------------------|\r\n";
		}
	}*/

$conteudo .= "+------------------------------------------------------------------------------+\r\n";
$conteudo .= "| OS .....: 000001       VISITA ....: R$ 85,00      HORA TEC ...: R$ 250,00    |\r\n";
$conteudo .= "| M.OBRA .: R$ 0,00      KM ........: R$ 0,00       DIARIA .....: R$ 0,00      |\r\n";
$conteudo .= "| PECAS ..: R$ 125,00    RECOND ....: R$ 52,00      TOTAL ......: R$ 512,00    |\r\n";
$conteudo .= "+------------------------------------------------------------------------------+\r\n";
$conteudo .= "| OS .....: 000002       VISITA ....: R$ 85,00      HORA TEC ...: R$ 250,00    |\r\n";
$conteudo .= "| M.OBRA .: R$ 0,00      KM ........: R$ 0,00       DIARIA .....: R$ 0,00      |\r\n";
$conteudo .= "| PECAS ..: R$ 125,00    RECOND ....: R$ 52,00      TOTAL ......: R$ 512,00    |\r\n";
$conteudo .= "+------------------------------------------------------------------------------+\r\n";
$conteudo .= "| OS .....: 000003       VISITA ....: R$ 55,00      HORA TEC ...: R$ 50,00     |\r\n";
$conteudo .= "| M.OBRA .: R$ 0,00      KM ........: R$ 0,00       DIARIA .....: R$ 0,00      |\r\n";
$conteudo .= "| PECAS ..: R$ 132,00    RECOND ....: R$ 100,00     TOTAL ......: R$ 337,00    |\r\n";
$conteudo .= "+------------------------------------------------------------------------------+\r\n";
$conteudo .= "| OS .....: 000004       VISITA ....: R$ 35,00      HORA TEC ...: R$ 150,00    |\r\n";
$conteudo .= "| M.OBRA .: R$ 0,00      KM ........: R$ 0,00       DIARIA .....: R$ 0,00      |\r\n";
$conteudo .= "| PECAS ..: R$ 100,00    RECOND ....: R$ 57,00      TOTAL ......: R$ 342,00    |\r\n";
$conteudo .= "+------------------------------------------------------------------------------+\r\n";
$conteudo .= "| TOTAL GERAL            VISITA ....: R$ 260,00     HORA TEC ...: R$ 700,00    |\r\n";
$conteudo .= "| M.OBRA .: R$ 0,00      KM ........: R$ 0,00       DIARIA .....: R$ 0,00      |\r\n";
$conteudo .= "| PECAS ..: R$ 482,00    RECOND ....: R$ 261,00     TOTAL ......: R$ 1703,00   |\r\n";
$conteudo .= "+------------------------------------------------------------------------------+\r\n";
$conteudo .= "|                              VALORES ADICIONAIS                              |\r\n";
$conteudo .= "|                              ==================                              |\r\n";
$conteudo .= "| MONTAGEM ........:  R$ 0,00                  INSTALACAO .....: R$ 0,00       |\r\n";
$conteudo .= "| TAXI ............:  R$ 0,00                  REFEICAO .......: R$ 0,00       |\r\n";
$conteudo .= "| HOTEL ...........:  R$ 0,00                  PASSAGEM .......: R$ 0,00       |\r\n";
$conteudo .= "| PEDAGIO .........:  R$ 0,00                  TOTAL ..........: R$ 0,00       |\r\n";
$conteudo .= "+------------------------------------------------------------------------------+\r\n";
$conteudo .= "| OBS ......: $obs                              |\r\n";
$conteudo .= "+------------------------------------------------------------------------------|\r\n";
$conteudo .= "| TOTAL PARA FATURAMENTO ...................: R$ 1703,00                       |\r\n";
$conteudo .= "+------------------------------------------------------------------------------+\r\n";
$conteudo .= "|                                                                              |\r\n";
$conteudo .= "| LIBERAÇÃO DO FATURAMENTO  ___/___/______    VISTO _____________________      |\r\n";
$conteudo .= "+------------------------------------------------------------------------------+\r\n";


header("Content-type: Telecontrol_Assist");
header("Content-length: " . strlen($conteudo) . " bytes");
header("Content-Disposition: filename=faturamento.telecontrol");
header("Content-Description: Impressão da Faturamento - " . $os);


echo $conteudo ;

flush();
exit;


$title = "Relatório de Movimento para Faturamento";
$layout_menu = "os";
//include 'cabecalho.php';

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
	<script language="javascript" src="js/scripts_hora.js"></script>

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
	font-size: 11px;
	text-align: left;
	background: #ffffff;
	border-right: dotted 1px #a0a0a0;
	/*border-left: dotted 1px #a0a0a0;*/
	border-bottom: dotted 1px #000000;
	padding: 1px,1px,1px,1px;
}

.borda {
	border: solid 1px #a0a0a0;
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
	font-size: 14px;
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

input {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 09px;
	font-weight: normal;
	border: 0x solid #a0a0a0;
	background-color: #FFFFFF;
}

TEXTAREA {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0x solid #a0a0a0;
	background-color: #FFFFFF;
}

</style>

<body>
<?
	if (strlen($msg_erro) > 0){
?>
<TABLE>
<TR>
	<TD><? echo $msg_erro; ?></TD>
</TR>
</TABLE>
<?
	}
echo $msg_debug;
?>
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
	<TD style="border: 1px solid #a0a0a0; font-size: 14px;" nowrap>
<?	########## SUA OS ########## ?>
		<b><? echo $sua_os ?></b>
	</TD>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">

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
	<TD class="conteudo" colspan='2'><? echo $cliente_endereco . ", " . $cliente_numero . " " . $cliente_complemento . $cliente_bairro ?>&nbsp</TD>
	<TD class="titulo">CEP</TD>
	<TD class="conteudo"><? echo $cliente_cep ?>&nbsp</TD>
	<TD class="titulo">Telefone</TD>
	<TD class="conteudo"><? echo $cliente_fone ?>&nbsp</TD>
</TR>

<!-- ====== Cep Municipio UF ================ -->
<TR>
	<TD class="titulo">Bairro</TD>
	<TD class="conteudo" colspan=2><? echo $cliente_bairro ?>&nbsp</TD>
	<TD class="titulo">Municipio</TD>
	<TD class="conteudo"><? echo $cliente_cidade ?>&nbsp</TD>
	<TD class="titulo">Estado</TD>
	<TD class="conteudo"><? echo $cliente_estado ?>&nbsp</TD>
</TR>

<!-- ====== CONTATO E CHAMADO ================ -->
<TR>
	<TD class="titulo">Defeito</TD>
	<TD class="conteudo" colspan='2'><? 
#	if (strlen (trim ($nome_comercial)) > 0) {
#		echo $nome_comercial ;
#		echo $descricao_equipamento;
#	}else{
#		echo $descricao_equipamento;
#	}
	echo $defeito_reclamado ?>&nbsp</TD>
	<TD class="titulo">Contato</TD>
	<TD class="conteudo" colspan="3"><? echo $quem_abriu_chamado ?>&nbsp</TD>
</TR>
<TR>
</TR>

<!-- ====== MOTIVO ================ -->
<TR>
	<TD class="titulo">Obs.:</TD>
	<TD class="conteudo" colspan="6"><? echo $obs ?>&nbsp</TD>
</TR>
</TABLE>

<!-- ====== MODELO DO APARELHO ================ -->
<!-- 
<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">SÉRIE</TD>
	<TD class="conteudo" width="80"><? echo $serie ?>&nbsp</TD>
	<TD class="titulo">CAPACIDADE</TD>
	<TD class="conteudo" width="80"><? echo $capacidade ?>&nbsp</TD>
	<TD class="titulo">MODELO</TD>
	<TD class="conteudo" colspan="2"><? echo $descricao_equipamento ?>&nbsp</TD>
	<TD class="titulo">INSTALAÇÃO</TD>
	<TD class="conteudo">___/___/____</TD>
</TR>
<TR>
	<TD class="titulo">LEITURA</TD>
	<TD class="conteudo" colspan='3'><? echo $leitura ?> &nbsp</TD>
	<TD class="titulo">NF COMPRA/REVENDA</TD>
	<TD class="conteudo" colspan='2'><? echo $nota_fiscal ?>&nbsp</TD>
	<TD class="titulo">GARANTIA</TD>
	<TD class="conteudo">___/___/____</TD>
</TR>
</TABLE>
 -->

<br>

<TABLE class="borda" width="650px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="menu_top" colspan="7" bgcolor="#d0d0d0">COBRANÇA</TD>
</TR>

<TR>
	<TD class="titulo">Endereço</TD>
	<TD class="conteudo" colspan='2'><? echo $cliente_endereco . ", " . $cliente_numero . " " . $cliente_complemento  ?>&nbsp</TD>
	<TD class="titulo">CEP</TD>
	<TD class="conteudo"><? echo $cliente_cep ?>&nbsp</TD>
	<TD class="titulo">Telefone</TD>
	<TD class="conteudo"><? echo $cliente_fone ?>&nbsp</TD>
</TR>
<TR>
	<TD class="titulo">Bairro</TD>
	<TD class="conteudo" colspan=2><? echo $cliente_bairro ?>&nbsp</TD>
	<TD class="titulo">Municipio</TD>
	<TD class="conteudo"><? echo $cliente_cidade ?>&nbsp</TD>
	<TD class="titulo">Estado</TD>
	<TD class="conteudo"><? echo $cliente_estado ?>&nbsp</TD>
</TR>
</TABLE>
<br>
<TABLE class="borda" width="650px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="menu_top" colspan="7" bgcolor="#d0d0d0">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">OS</TD>
	<TD class="conteudo" width="40%" colspan='2'><? echo $sua_os ?>&nbsp</TD>
	<TD class="titulo">Natureza do Serviço</TD>
	<TD class="conteudo" width="25%"><? echo $natureza_servico ?>&nbsp</TD>
	<TD class="titulo">Condições de pgto:</TD>
	<TD class="conteudo"><? echo $condicao_descricao ?>&nbsp</TD>
</TR>
<TR>
	<TD class="titulo">Endereço</TD>
	<TD class="conteudo" colspan='2'><? echo $cliente_endereco . ", " . $cliente_numero . " " . $cliente_complemento . $cliente_bairro; ?>&nbsp</TD>
	<TD class="titulo">Municipio</TD>
	<TD class="conteudo"><? echo $cliente_cidade ?>&nbsp</TD>
	<TD class="titulo">Estado</TD>
	<TD class="conteudo"><? echo $cliente_estado ?>&nbsp</TD>
</TR>
<TR>
	<TD class="titulo">Contato</TD>
	<TD class="conteudo" colspan="2"><? echo $quem_abriu_chamado ?>&nbsp</TD>
	<TD class="titulo">Faturado para:</TD>
	<TD class="conteudo">
<? 
	if ($faturamento_cliente_revenda == 'r') 
		echo "REVENDA";
	else
		echo "CLIENTE";
 ?>&nbsp</TD>
 	<TD class="titulo">Contrato nº </TD>
	<TD class="conteudo"><? echo $contrato_numero ?></TD>

</TR>
</TABLE>

<TABLE width="650" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="menu_top" colspan="13" bgcolor="#d0d0d0"> * Valores em REAL * </TD>
</TR>
<TR class="menu_top" bgcolor="#d0d0d0">
	<TD class="menu_top" bgcolor="#d0d0d0">Quantidade</TD>
	<TD class="menu_top" bgcolor="#d0d0d0">Descrição</TD>
	<TD class="menu_top" bgcolor="#d0d0d0">Valor</TD>
	<TD class="menu_top" bgcolor="#d0d0d0">Valor Total</TD>
</TR>

<?
if (strlen($os) > 0) {
	$sql = "SELECT * FROM tbl_os_visita WHERE os = $os ORDER BY os_visita";
	$vis = @pg_exec ($con,$sql);
}

$total_horas = intval ($qtde_horas);
$minutos     = $qtde_horas - $total_horas ;
$minutos     = number_format(($minutos * 60),2);
$minutos     = intval($minutos);

$hora_geral  = str_pad ($total_horas , 2 , '0' , STR_PAD_LEFT);
$minutos     = str_pad ($minutos     , 2 , '0' , STR_PAD_LEFT);

$hora_geral = $hora_geral . ":" . $minutos ;

if ($visita_por_km == 't') {
	$total_taxa_visita = $taxa_visita * $deslocamento_km;
	$taxa_visita = 0;
}else{
	$total_taxa_visita = $taxa_visita ;
}

if ($mao_de_obra_por_hora == 't') {
	$valor_total_horas = $qtde_horas * $mao_de_obra;
}else{
	$valor_total_horas = $mao_de_obra;
}

echo "<TR>\n";
echo "	<TD class='conteudo'>&nbsp;</TD>\n";
echo "	<TD class='conteudo'>Pesos - padrão</TD>\n";
echo "	<TD class='conteudo'>&nbsp;</TD>\n";
echo "	<TD class='conteudo'><div align='right'>". number_format($regulagem_peso_padrao,2,',','.') . "&nbsp;</div></TD>\n";
echo "</TR>\n";

echo "<TR>\n";
echo "	<TD class='conteudo'>&nbsp;</TD>\n";
echo "	<TD class='conteudo'>Certificado de conformidade</TD>\n";
echo "	<TD class='conteudo'>&nbsp;</TD>\n";
echo "	<TD class='conteudo'><div align='right'>". number_format($certificado_conformidade,2,',','.') . "&nbsp;</div></TD>\n";
echo "</TR>\n";

echo "<TR>\n";
echo "	<TD class='conteudo'>$hora_geral &nbsp;</TD>\n";
echo "	<TD class='conteudo'>Horas</TD>\n";
echo "	<TD class='conteudo'>total de ".number_format($hora_tecnica,2,',','.')." / hora </div></TD>\n";
echo "	<TD class='conteudo'><div align='right'>" . number_format ($valor_total_horas,2,',','.') . "&nbsp;</div></TD>\n";
echo "</TR>\n";

if (strlen($valor_diaria) > 0 AND (strlen($qtde_diaria) == 0 OR $qtde_diaria < 1)) $qtde_diaria = 1;
$total_diaria = $qtde_diaria * $valor_diaria;

echo "<TR>\n";
echo "	<TD class='conteudo'>" . $qtde_diaria . "</TD>\n";
echo "	<TD class='conteudo'>Dias</TD>\n";
echo "	<TD class='conteudo'>total de " . number_format ($valor_diaria,2,',','.') . " / dia</TD>\n";
echo "	<TD class='conteudo'><div align='right'>" . number_format ($total_diaria,2,',','.') . "&nbsp;</div></TD>\n";
echo "</TR>\n";

echo "<TR>\n";
echo "	<TD class='conteudo'>$deslocamento_km &nbsp;</TD>\n";
echo "	<TD class='conteudo'>Km</TD>\n";
echo "	<TD class='conteudo'> total de " . number_format ($taxa_visita,2,',','.') . " / Km</TD>\n";
echo "	<TD class='conteudo'><div align='right'>" . number_format ($total_taxa_visita,2,',','.') . "&nbsp;</div></TD>\n";
echo "</TR>\n";

if ($visita_por_km == 't') {
	echo "<TR>\n";
	echo "	<TD class='conteudo'>&nbsp;</TD>\n";
	echo "	<TD class='conteudo'>Taxa de Visita</TD>\n";
	echo "	<TD class='conteudo'>&nbsp;</TD>\n";
	echo "	<TD class='conteudo'><div align='right'>" . number_format ($taxa_visita,2,',','.') . "&nbsp;</div></TD>\n";
	echo "</TR>\n";
}

$sub_total_os = $total_taxa_visita + $regulagem_peso_padrao + $valor_total_horas + $certificado_conformidade + $total_diaria;

echo "<TR>\n";
echo "	<TD colspan='3' class='conteudo'><div align='right'>Total</div></TD>\n";
echo "	<TD class='conteudo'><div align='right'><b>" . number_format ($sub_total_os,2,',','.') . "</b>&nbsp;</div></TD>\n";
echo "</TR>\n";

?>

</TABLE>
<br>
<TABLE width="650" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="menu_top" colspan="6" bgcolor="#d0d0d0">Recuperação</TD>
</TR>
<TR>
	<TD class="menu_top" width='80'>CODIGO</TD>
	<TD class="menu_top" width='50'>QTDE</TD>
	<TD class="menu_top">MATERIAL</TD>
	<TD class="menu_top" width='80'>UNITÁRIO</TD>
	<TD class="menu_top" width='80'>TOTAL</TD>
</TR>

<?
	if(strlen($os) > 0){
		$sql = "SELECT	tbl_os_item.os_item ,
						tbl_os_item.pedido ,
						tbl_os_item.qtde ,
						tbl_peca.referencia ,
						tbl_peca.descricao ,
						tbl_tabela_item.preco AS preco_item 
				FROM	tbl_os 
				JOIN	tbl_os_produto ON tbl_os_produto.os = tbl_os.os 
				JOIN	tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto 
				LEFT JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca 
				JOIN tbl_tabela ON tbl_tabela.tabela = 29
				LEFT JOIN tbl_tabela_item	ON tbl_tabela_item.peca = tbl_peca.peca 
											AND tbl_tabela_item.tabela = tbl_tabela.tabela 
				WHERE	tbl_os.os      = $os
				AND		tbl_os.fabrica = $login_fabrica
				AND tbl_os_item.servico_realizado = 36";
		$res = pg_exec ($con,$sql) ;
		
		if(pg_numrows($res) > 0) {
			$total_geral = 0;

			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$pedido		= pg_result($res,$i,pedido);
				$peca		= pg_result($res,$i,referencia);
				$qtde		= pg_result($res,$i,qtde);
				$preco		= pg_result($res,$i,preco_item);
				$descricao	= pg_result($res,$i,descricao);
				$total		= $qtde * $preco;

				$total_geral	= $total_geral + $total;

				echo "<TR height='20'>";
				echo "	<TD class='conteudo'>$peca &nbsp;</TD>";
				echo "	<TD class='conteudo'><div align='center'>$qtde &nbsp;</div></TD>";
				echo "	<TD class='conteudo'>$descricao &nbsp;</TD>";
				echo "	<TD class='conteudo' style='padding-right:3;'><div align='right'>".number_format ($preco,2,',','.')." &nbsp;</div></TD>";
				echo "	<TD class='conteudo' style='padding-right:3;'><div align='right'>".number_format ($total,2,',','.')." &nbsp;</div></TD>";
				echo "</TR>";
			}

			if (strlen($desconto_peca_recuperada) > 0 AND strlen($total_geral) > 0) 
				$total_geral = $total_geral - ($total_geral * ($desconto_peca_recuperada / 100));

			echo "<TR height='20'>\n";
			echo "	<TD colspan='4' class='conteudo' style='padding-right:7;'><div align='right'>Desconto: $desconto_peca_recuperada %</div></TD>\n";
			echo "	<TD class='conteudo' style='padding-right:7;'><div align='right'><b>".number_format ($total_geral,2,',','.')."</b></div> &nbsp;&nbsp;</TD>\n";
			echo "</TR>\n";

		}

	}

$total_servicos = $sub_total_os + $total_geral;

?>

</TABLE>

<br>

<TABLE class='conteudo' width="650" border="0" cellspacing="0" cellpadding="0">
<tr>
	<td align='center'><b>Total Serviços</b></td>
	<td width="80" style='text-align: right; padding-right:7;'><div align='right'><b><?echo number_format($total_servicos,2,',','.')?></b></div></td>
</tr>
</table>

<br>

</TABLE>
<br>
<TABLE width="650" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="menu_top" colspan="7" bgcolor="#d0d0d0">Peças</TD>
</TR>
<TR>
	<TD class="menu_top" width='80'>CODIGO</TD>
	<TD class="menu_top" width='50'>QTDE</TD>
	<TD class="menu_top">MATERIAL</TD>
	<TD class="menu_top">IPI</TD>
	<TD class="menu_top">C</TD>
	<TD class="menu_top" width='80'>UNITÁRIO</TD>
	<TD class="menu_top" width='80'>TOTAL</TD>
</TR>

<?
	if(strlen($os) > 0){
		$sql = "SELECT  tbl_os_item.os_item              ,
						tbl_os_item.pedido               ,
						tbl_os_item.qtde                 ,
						tbl_peca.referencia              ,
						tbl_peca.descricao               ,
						tbl_peca.origem                    ,
						tbl_peca.unidade                   ,
						tbl_peca.ipi                       ,
						tbl_peca.peso                      ,
						tbl_tabela_item.preco AS preco_item
				FROM    tbl_os_item
				LEFT JOIN tbl_peca USING (peca)
				LEFT JOIN tbl_tabela ON tbl_tabela.tabela = 29
				LEFT JOIN tbl_tabela_item USING (peca)
				JOIN tbl_os_produto USING(os_produto)
				JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
				WHERE   tbl_os.os      = $os
				AND     tbl_os.fabrica = $login_fabrica
				AND     tbl_os_item.servico_realizado IN (12,56) ;";
		$res = pg_exec ($con,$sql) ;

		$total_geral = 0;
		$total = 0;
		
		if(pg_numrows($res) > 0) {

			/*
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$ipi   = trim(pg_result ($res,$i,ipi));
				$preco = pg_result ($res,$i,qtde) * pg_result ($res,$i,preco_item) ;
				$preco = $preco + ($preco * $ipi / 100);
				$total += $preco;
			}
			*/
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$pedido		= pg_result($res,$i,pedido);
				$peca		= pg_result($res,$i,referencia);
				$qtde		= pg_result($res,$i,qtde);
				$preco		= pg_result($res,$i,preco_item);
				$descricao	= pg_result($res,$i,descricao);
				$origem		= pg_result($res,$i,origem);
				$unidade	= pg_result($res,$i,unidade);
				$ipi		= pg_result($res,$i,ipi);
				$peso		= pg_result($res,$i,peso);

				$preco_sem_ipi = $qtde * $preco;
				if ($consumidor_final <> 'f') {
					$preco = $preco + ($preco_sem_ipi * $ipi / 100);
				}
				$total = $preco;

				$valor_total = $qtde * $preco;
				$total_geral = $total_geral + $total;

				if ($origem == "TER") {
					$origem = "C";
				}else{
					$origem = "T";
				}

				echo "<TR height='20'>";
				echo "	<TD class='conteudo'>$peca &nbsp;</TD>";
				echo "	<TD class='conteudo'><div align='center'>$qtde &nbsp;</div></TD>";
				echo "	<TD class='conteudo'>$descricao&nbsp;</TD>";
				echo "	<TD class='conteudo' style='padding-right:3;'>";
				if ($consumidor_final <> 'f') {
					echo "<div align='right'>".$ipi." %</div>";
				}
				echo "&nbsp;</TD>\n";
				echo "	<TD class='conteudo'><div align='center'>$origem</div></TD>\n";
				echo "	<TD class='conteudo' style='padding-right:3;'><div align='right'>".number_format ($preco_sem_ipi,2,',','.')." &nbsp;</div></TD>";
				echo "	<TD class='conteudo' style='padding-right:3;'><div align='right'>".number_format ($total,2,',','.')." &nbsp;</div></TD>";
				echo "</TR>";
			}

			if (strlen($desconto_peca) > 0 AND strlen($total_geral) > 0) 
				$total_geral = $total_geral - ($total_geral * ($desconto_peca / 100));

			echo "<TR height='20'>\n";
			echo "	<TD colspan='6' class='conteudo' style='padding-right:7;'><div align='right'>Desconto: $desconto_peca %</div></TD>\n";
			echo "	<TD class='conteudo' style='padding-right:7;'><div align='right'><b>".number_format ($total_geral,2,',','.')."</b></div> &nbsp;</TD>\n";
			echo "</TR>\n";

		}

	}
?>
</TABLE>

<BR><BR>

<?
$total_os = $total_servicos + $total_geral;
?>
<TABLE class='conteudo' width="650" border="0" cellspacing="0" cellpadding="0">
<tr>
	<td align='center'><b>Total geral da OS</b></td>
	<td width="80" style='text-align: right; padding-right:7;'><div align='right'><b><?echo number_format($total_os,2,',','.')?></b></div></td>
</tr>
</table>

<br><br>

<TABLE class='conteudo' width="650" border="0" cellspacing="0" cellpadding="0">
<tr>
	<td><b>NF número:</b></td>
	<td>_____________________________</td>
	<td><b>Data de emissão:</b></td>
	<td>_______/_______/______________</td>
</tr>
</table>

<p>
<br>
<br>

<script>
	window.print();
</script>