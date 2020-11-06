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

if (strlen($_GET['os']) > 0)  $os = $_GET['os'];
if (strlen($_POST['os']) > 0) $os = $_POST['os'];

if (strlen($os) == 0) {
	header("Location: os_parametros.php");
	exit;
}

// #################################################################################//
if (strlen($os) > 0){

	$sql = "SELECT	tbl_os.condicao                          ,
					tbl_os.cobrar_percurso                   ,
					tbl_os.qtde_km                           ,
					tbl_os.visita_por_km                     ,
					tbl_os.taxa_visita                       ,
					tbl_os.qtde_hora                         ,
					tbl_os.hora_tecnica                      ,
					tbl_os.qtde_diaria                       ,
					tbl_os.diaria                            ,
					tbl_os_extra.obs                         ,
					tbl_os_extra.natureza_servico            ,
					tbl_os_extra.desconto_peca               ,
					tbl_os_extra.desconto_peca_recuperada    ,
					tbl_os_extra.faturamento_cliente_revenda ,
					tbl_cliente.consumidor_final             ,
					tbl_cliente.contrato_numero              ,
					tbl_condicao.descricao AS descricao_condicao
			FROM	tbl_os
			LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			LEFT JOIN tbl_cliente USING(cliente)
			LEFT JOIN tbl_condicao USING(condicao)
			WHERE	tbl_os.os    = $os
			AND		tbl_os.fabrica = $login_fabrica ";

	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$descricao_condicao          = pg_result($res,0,descricao_condicao);
		$cobrar_percurso             = pg_result($res,0,cobrar_percurso);
		$qtde_km                     = pg_result($res,0,qtde_km);
		$visita_por_km               = pg_result($res,0,visita_por_km);
		$taxa_visita                 = pg_result($res,0,taxa_visita);
		$qtde_hora                   = pg_result($res,0,qtde_hora);
		$hora_tecnica                = pg_result($res,0,hora_tecnica);
		$qtde_diaria                 = pg_result($res,0,qtde_diaria);
		$diaria                      = pg_result($res,0,diaria);
		$obs                         = pg_result($res,0,obs);
		$natureza_servico            = pg_result($res,0,natureza_servico);
		$desconto_peca               = pg_result($res,0,desconto_peca);
		$desconto_peca_recuperada    = pg_result($res,0,desconto_peca_recuperada);
		$faturamento_cliente_revenda = pg_result($res,0,faturamento_cliente_revenda);
		$consumidor_final            = pg_result($res,0,consumidor_final);
		$contrato_numero             = pg_result($res,0,contrato_numero);
	}
}

// #################################################################################//
if (strlen ($os) > 0) {
	$sql = "SELECT * 
			FROM   vw_os_print 
			WHERE  os = $os";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$sua_os				= pg_result ($res,0,sua_os);
		$data_abertura		= pg_result ($res,0,data_abertura);
		$quem_abriu_chamado	= pg_result ($res,0,quem_abriu_chamado);
		$obs				= pg_result ($res,0,obs);
		$defeito_reclamado  = pg_result ($res,0,defeito_reclamado);

		if ($faturamento_cliente_revenda == "r") {
			$cliente			= pg_result ($res,0,revenda);
			$cliente_nome		= pg_result ($res,0,revenda_nome);
			$cliente_cpf		= pg_result ($res,0,revenda_cnpj);
			$cliente_rg 		= pg_result ($res,0,revenda_ie);
			$cliente_endereco	= pg_result ($res,0,revenda_endereco);
			$cliente_numero		= pg_result ($res,0,revenda_numero);
			$cliente_complemento= pg_result ($res,0,revenda_complemento);
			$cliente_bairro		= pg_result ($res,0,revenda_bairro);
			$cliente_cep		= pg_result ($res,0,revenda_cep);
			$cliente_cep		= substr($cliente_cep,0,5)."-".substr($cliente_cep,5,10);
			$cliente_cidade		= pg_result ($res,0,revenda_cidade);
			$cliente_fone		= pg_result ($res,0,revenda_fone);
			$cliente_nome		= pg_result ($res,0,revenda_nome);
			$cliente_estado		= pg_result ($res,0,revenda_estado);
			$cliente_contrato	= "";
		}else{
			$cliente			= pg_result ($res,0,cliente);
			$cliente_nome		= pg_result ($res,0,cliente_nome);
			$cliente_cpf		= pg_result ($res,0,cliente_cpf);
			$cliente_rg 		= pg_result ($res,0,cliente_rg);
			$cliente_endereco	= pg_result ($res,0,cliente_endereco);
			$cliente_numero		= pg_result ($res,0,cliente_numero);
			$cliente_complemento= pg_result ($res,0,cliente_complemento);
			$cliente_bairro		= pg_result ($res,0,cliente_bairro);
			$cliente_cep		= pg_result ($res,0,cliente_cep);
			$cliente_cep		= substr($cliente_cep,0,5)."-".substr($cliente_cep,5,10);
			$cliente_cidade		= pg_result ($res,0,cliente_cidade);
			$cliente_fone		= pg_result ($res,0,cliente_fone);
			$cliente_nome		= pg_result ($res,0,cliente_nome);
			$cliente_estado		= pg_result ($res,0,cliente_estado);
			$cliente_contrato	= pg_result ($res,0,cliente_contrato);
		}

		$posto_endereco		= pg_result ($res,0,posto_endereco);
		$posto_numero		= pg_result ($res,0,posto_numero);
		$posto_cep			= pg_result ($res,0,posto_cep);
		$posto_cep			= substr($posto_cep,0,5)."-".substr($posto_cep,5,10);
		$posto_cidade		= pg_result ($res,0,posto_cidade);
		$posto_estado		= pg_result ($res,0,posto_estado);
		$posto_fone			= pg_result ($res,0,posto_fone);
		$posto_cnpj			= pg_result ($res,0,posto_cnpj);
		$posto_ie			= pg_result ($res,0,posto_ie);
	}
}

$title = "Relatório de Movimento para Faturamento";

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
	echo "CNPJ/CPF ".$posto_cnpj ." <br>IE/RG ".$posto_ie;
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
	<TD class="conteudo" colspan='2'><? echo $cliente_endereco . ", " . $cliente_numero . " " . $cliente_complenento . $cliente_bairro ?>&nbsp</TD>
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
	<TD class="titulo">Contato</TD>
	<TD class="conteudo" colspan="6"><? echo $quem_abriu_chamado ?>&nbsp</TD>
</TR>
<TR>
</TR>

<!-- ====== MOTIVO ================ -->
<TR>
	<TD class="titulo">Obs.:</TD>
	<TD class="conteudo" colspan="6"><? echo $obs ?>&nbsp</TD>
</TR>
</TABLE>

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
	<TD class="conteudo"><? echo $descricao_condicao ?>&nbsp</TD>
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

<br>

<TABLE class="borda" width="650px" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td class="menu_top" bgcolor="#d0d0d0" rowspan=2>#</td>
		<td class="menu_top" bgcolor="#d0d0d0" colspan=2>EQUIPAMENTO</td>
		<td class="menu_top" bgcolor="#d0d0d0">SÉRIE</td>
		<td class="menu_top" bgcolor="#d0d0d0">CAPACIDADE</td>
		<td class="menu_top" bgcolor="#d0d0d0">DEFEITO<br>RECLAMADO</td>
	</tr>
	<tr>
		<td class="menu_top" bgcolor="#d0d0d0">VALOR DA<br>MÃO-DE-OBRA</td>
		<td class="menu_top" bgcolor="#d0d0d0">REGULAGEM<br>PESO PADRÃO</td>
		<td class="menu_top" bgcolor="#d0d0d0">CERTIFICADO<br>CONFORMIDADE</td>
		<TD class="menu_top" bgcolor="#d0d0d0">SELO</TD>
		<TD class="menu_top" bgcolor="#d0d0d0">LACRE ENCONTRADO</TD>
	</tr>
	<?
	$sql = "SELECT	tbl_produto.referencia                    ,
					tbl_produto.descricao                     ,
					tbl_os_produto.os_produto                 ,
					tbl_os_produto.serie                      ,
					tbl_os_produto.capacidade                 ,
					tbl_os_produto.regulagem_peso_padrao      ,
					tbl_os_produto.certificado_conformidade   ,
					tbl_os_produto.mao_de_obra                ,
					tbl_defeito_reclamado.descricao AS defeito,
					tbl_os_produto.selo                       ,
					tbl_os_produto.lacre_encontrado
			FROM	tbl_os_produto
			JOIN	tbl_produto           USING (produto)
			JOIN	tbl_defeito_reclamado USING (defeito_reclamado)
			WHERE	tbl_os_produto.os = $os
			ORDER BY tbl_os_produto.os_produto";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {

		$cor = ($i/2 == 0) ? '#f8f8f8' : '#e9e9e9';

		echo "<tr bgcolor='$cor'>\n";
		echo "<td class='conteudo' rowspan=2><b>";
		echo $i + 1;
		echo "</b></td>\n";
		echo "<td class='conteudo' colspan=2>".pg_result ($res,$i,referencia)." - ".pg_result ($res,$i,descricao)."</td>\n";
		echo "<td class='conteudo'>".pg_result ($res,$i,serie)."</td>\n";
		echo "<td class='conteudo'>".pg_result ($res,$i,capacidade)."</td>\n";
		echo "<td class='conteudo' align='center'>".pg_result ($res,$i,defeito)."</td>\n";
		echo "</tr>\n";

		echo "<tr bgcolor='$cor'>\n";
		echo "<td class='conteudo' align='right'>".number_format (pg_result ($res,$i,mao_de_obra),2,",",".")."</td>\n";
		echo "<td class='conteudo' align='right'>".number_format (pg_result ($res,$i,regulagem_peso_padrao),2,",",".")."</td>\n";
		echo "<td class='conteudo' align='right'>".number_format (pg_result ($res,$i,certificado_conformidade),2,",",".")."</td>\n";
		echo "<td class='conteudo'>".pg_result ($res,$i,selo)."</td>\n";
		echo "<td class='conteudo'>".pg_result ($res,$i,lacre_encontrado)."</td>\n";
		echo "</tr>\n";

		$total_produtos += (pg_result ($res,$i,regulagem_peso_padrao) + pg_result ($res,$i,certificado_conformidade) + pg_result ($res,$i,mao_de_obra));

		$array_os_produto[$i] = pg_result ($res,$i,os_produto);
		$array_posicao[$i]    = $i+1;

	}

?>
</table>

<br>

<TABLE class='conteudo' width="650" border="0" cellspacing="0" cellpadding="0">
<tr>
	<td align='center'><b>Sub Total</b></td>
	<td width="80" class='table_line2' style='text-align: right; padding-right:7;'><b><?echo number_format($total_produtos,2,',','.')?></b></td>
</tr>
</table>

<br>

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

$total_horas = intval ($qtde_hora);
$minutos     = $qtde_hora - $total_horas ;
$minutos     = number_format(($minutos * 60),2);
$minutos     = intval($minutos);

$hora_geral  = str_pad ($total_horas , 2 , '0' , STR_PAD_LEFT);
$minutos     = str_pad ($minutos     , 2 , '0' , STR_PAD_LEFT);

$hora_geral = $hora_geral . ":" . $minutos ;


$valor_total_horas = $qtde_hora * $hora_tecnica;
echo "<TR>\n";
echo "	<TD class='conteudo'>$hora_geral&nbsp;</TD>\n";
echo "	<TD class='conteudo'>Horas</TD>\n";
echo "	<TD class='conteudo'>total de ".number_format($hora_tecnica,2,',','.')." / hora </div></TD>\n";
echo "	<TD class='conteudo'><div align='right'>" . number_format ($valor_total_horas,2,',','.') . "&nbsp;</div></TD>\n";
echo "</TR>\n";

$total_diaria = $qtde_diaria * $diaria;
echo "<TR>\n";
echo "	<TD class='conteudo'>$qtde_diaria</TD>\n";
echo "	<TD class='conteudo'>Dias</TD>\n";
echo "	<TD class='conteudo'>total de " . number_format ($diaria,2,',','.') . " / dia</TD>\n";
echo "	<TD class='conteudo'><div align='right'>" . number_format ($total_diaria,2,',','.') . "&nbsp;</div></TD>\n";
echo "</TR>\n";

$total_por_deslocamento_km = $qtde_km * $visita_por_km;
echo "<TR>\n";
echo "	<TD class='conteudo'>$qtde_km &nbsp;</TD>\n";
echo "	<TD class='conteudo'>Km</TD>\n";
echo "	<TD class='conteudo'> total de " . number_format ($visita_por_km,2,',','.') . " / Km</TD>\n";
echo "	<TD class='conteudo'><div align='right'>" . number_format ($total_por_deslocamento_km,2,',','.') . "&nbsp;</div></TD>\n";
echo "</TR>\n";

echo "<TR>\n";
echo "	<TD class='conteudo'>&nbsp;</TD>\n";
echo "	<TD class='conteudo'>Taxa de Visita</TD>\n";
echo "	<TD class='conteudo'>&nbsp;</TD>\n";
echo "	<TD class='conteudo'><div align='right'>" . number_format ($taxa_visita,2,',','.') . "&nbsp;</div></TD>\n";
echo "</TR>\n";

$sub_total_os = $total_por_deslocamento_km + $valor_total_horas + $total_diaria + $taxa_visita;
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
	<TD class="menu_top" width='30'>ITEM</TD>
	<TD class="menu_top" width='80'>CÓDIGO</TD>
	<TD class="menu_top" width='50'>QTDE</TD>
	<TD class="menu_top">MATERIAL</TD>
	<TD class="menu_top" width='80'>UNITÁRIO</TD>
	<TD class="menu_top" width='80'>TOTAL</TD>
</TR>

<?
	if(strlen($os) > 0){

		$posicao = "";
		$item    = 0;

		$sql = "SELECT	tbl_os_item.os_item                 ,
						tbl_os_item.pedido                  ,
						tbl_os_item.qtde                    ,
						tbl_os_produto.os_produto           ,
						tbl_peca.referencia                 ,
						tbl_peca.descricao                  ,
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
				$os_produto = pg_result ($res,$i,os_produto);

				$total_geral	= $total_geral + $total;

				for ($j=0; $j<count($array_os_produto); $j++){
					if ($array_os_produto[$j] == $os_produto) $item = $array_posicao[$j];
				}

				echo "<TR height='20'>";
				echo "	<TD class='conteudo'>$item &nbsp;</TD>";
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
			echo "	<TD colspan='5' class='conteudo' style='padding-right:7;'><div align='right'>Desconto: $desconto_peca_recuperada %</div></TD>\n";
			echo "	<TD class='conteudo' style='padding-right:7;'><div align='right'><b>".number_format ($total_geral,2,',','.')."</b></div> &nbsp;&nbsp;</TD>\n";
			echo "</TR>\n";

		}

	}

$total_servicos = $total_geral;

?>

</TABLE>
<!--
<br>

<TABLE class='conteudo' width="650" border="0" cellspacing="0" cellpadding="0">
<tr>
	<td align='center'><b>Total Recuperação</b></td>
	<td width="80" style='text-align: right; padding-right:7;'><div align='right'><b><?echo number_format($total_servicos,2,',','.')?></b></div></td>
</tr>
</table>
-->
<br>

</TABLE>
<br>
<TABLE width="650" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="menu_top" colspan="8" bgcolor="#d0d0d0">Peças</TD>
</TR>
<TR>
	<TD class="menu_top" width='30'>ITEM</TD>
	<TD class="menu_top" width='80'>CÓDIGO</TD>
	<TD class="menu_top" width='50'>QTDE</TD>
	<TD class="menu_top">MATERIAL</TD>
	<TD class="menu_top">IPI</TD>
	<TD class="menu_top">C</TD>
	<TD class="menu_top" width='80'>UNITÁRIO</TD>
	<TD class="menu_top" width='80'>TOTAL</TD>
</TR>

<?
	if(strlen($os) > 0){

		$item    = 0;

		$sql = "SELECT  distinct
						tbl_os_item.os_item                ,
						tbl_os_item.pedido                 ,
						tbl_os_item.qtde                   ,
						tbl_os_produto.os_produto          ,
						tbl_peca.referencia                ,
						tbl_peca.descricao                 ,
						tbl_peca.origem                    ,
						tbl_peca.unidade                   ,
						tbl_peca.ipi                       ,
						tbl_peca.peso                      ,
						tbl_tabela_item.preco AS preco_item
				FROM    tbl_os_item
				LEFT JOIN tbl_peca USING (peca)
				LEFT JOIN tbl_tabela ON tbl_tabela.tabela = 26
				LEFT JOIN tbl_tabela_item USING (peca)
				left JOIN tbl_os_produto USING(os_produto)
				left JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
				WHERE   tbl_os.os      = $os
				AND     tbl_os.fabrica = $login_fabrica
				AND     tbl_os_item.servico_realizado NOT IN (12, 36) ";
		$res = pg_exec ($con,$sql) ;

		$total_geral = 0;
		$total = 0;
		
		if(pg_numrows($res) > 0) {

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
				$os_produto = pg_result ($res,$i,os_produto);

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

				for ($j=0; $j<count($array_os_produto); $j++){
					if ($array_os_produto[$j] == $os_produto) $item = $array_posicao[$j];
				}

				echo "<TR height='20'>";
				echo "	<TD class='conteudo'>$item &nbsp;</TD>";
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
			echo "	<TD colspan='7' class='conteudo' style='padding-right:7;'><div align='right'>Desconto: $desconto_peca %</div></TD>\n";
			echo "	<TD class='conteudo' style='padding-right:7;'><div align='right'><b>".number_format ($total_geral,2,',','.')."</b></div> &nbsp;</TD>\n";
			echo "</TR>\n";

		}

	}
?>
</TABLE>

<BR><BR>

<?
$total_os = $sub_total_os + $total_produtos + $total_servicos + $total_geral;
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