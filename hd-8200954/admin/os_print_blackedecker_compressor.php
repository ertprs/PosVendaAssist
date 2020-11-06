<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if (strlen($_GET['os']) > 0) {
	$os   = $_GET['os'];
	$modo = $_GET['modo'];
}else{
	header ("Location: os_parametros.php");
	exit;
}

#------------ Le OS da Base de dados ------------#
if (strlen ($os) > 0) {
	$sql = "SELECT * FROM vw_os_print WHERE os = $os";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		$sua_os              = pg_result ($res,0,sua_os);
		$data_abertura       = pg_result ($res,0,data_abertura);
		$quem_abriu_chamado  = pg_result ($res,0,quem_abriu_chamado);
		$obs_os              = pg_result ($res,0,obs);
		$cliente             = pg_result ($res,0,cliente);
		$cliente_nome        = pg_result ($res,0,cliente_nome);
		$cliente_cpf         = pg_result ($res,0,cliente_cpf);
		$cliente_rg          = pg_result ($res,0,cliente_rg);
		$cliente_endereco    = pg_result ($res,0,cliente_endereco);
		$cliente_numero      = pg_result ($res,0,cliente_numero);
		$cliente_complemento = pg_result ($res,0,cliente_complemento);
		$cliente_bairro      = pg_result ($res,0,cliente_bairro);
		$cliente_cep         = pg_result ($res,0,cliente_cep);
		$cliente_cidade      = pg_result ($res,0,cliente_cidade);
		$cliente_fone        = pg_result ($res,0,cliente_fone);
		$cliente_nome        = pg_result ($res,0,cliente_nome);
		$cliente_estado      = pg_result ($res,0,cliente_estado);
		$cliente_contrato    = pg_result ($res,0,cliente_contrato);
		$posto_endereco      = pg_result ($res,0,posto_endereco);
		$posto_numero        = pg_result ($res,0,posto_numero);
		$posto_cep           = pg_result ($res,0,posto_cep);
		$posto_cidade        = pg_result ($res,0,posto_cidade);
		$posto_estado        = pg_result ($res,0,posto_estado);
		$posto_fone          = pg_result ($res,0,posto_fone);
		$posto_cnpj          = pg_result ($res,0,posto_cnpj);
		$posto_ie            = pg_result ($res,0,posto_ie);
	}

	$sql =	"SELECT	tbl_os_extra.taxa_visita                   ,
					tbl_os_extra.visita_por_km                 ,
					tbl_os_extra.mao_de_obra                   ,
					tbl_os_extra.valor_diaria                  ,
					tbl_os_extra.qtde_horas                    ,
					tbl_os_extra.obs                           ,
					tbl_os_extra.tecnico                       ,
					tbl_os_extra.hora_tecnica                  ,
					tbl_os_extra.classificacao_os              ,
					tbl_condicao.descricao         AS condicao ,
					tbl_posto_fabrica.codigo_posto             
			FROM	tbl_os
			JOIN	tbl_produto USING (produto)
			JOIN	tbl_posto USING (posto)
			JOIN	tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
									  AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			LEFT JOIN tbl_classificacao_os USING (classificacao_os)
			LEFT JOIN tbl_condicao USING (condicao)
			WHERE tbl_os.os = $os";
	$res = pg_exec($con,$sql);

	if (pg_numrows ($res) == 1) {
		$taxa_visita      = pg_result($res,0,taxa_visita);
		$mao_de_obra      = pg_result($res,0,mao_de_obra);
		$valor_diaria     = pg_result($res,0,valor_diaria);
		$qtde_horas       = pg_result($res,0,qtde_horas);
		$obs              = pg_result($res,0,obs);
		$tecnico          = pg_result($res,0,tecnico);
		$classificacao_os = pg_result($res,0,classificacao_os);
		$condicao         = pg_result($res,0,condicao);
		$hora_tecnica     = pg_result($res,0,hora_tecnica);
		$codigo_posto     = pg_result($res,0,codigo_posto);
	}

	$sql = "SELECT	tbl_posto.nome     ,
			tbl_posto_fabrica.contato_endereco AS endereco ,
			tbl_posto_fabrica.contato_numero   AS numero   ,
			tbl_posto_fabrica.contato_cep      AS cep      ,
			tbl_posto.fone     ,
			tbl_posto.cnpj     ,
			tbl_posto.ie       ,
			tbl_posto_fabrica.contato_cidade   AS cidade   ,
			tbl_posto_fabrica.contato_estado   AS estado
		FROM	tbl_posto
		JOIN	tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
								  AND tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN	tbl_os ON tbl_posto.posto = tbl_os.posto
			WHERE tbl_os.os = $os";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) == 1) {
		$posto_nome     = pg_result ($res,0,nome);
		$posto_endereco = pg_result ($res,0,endereco);
		$posto_numero   = pg_result ($res,0,numero);
		$posto_cep      = pg_result ($res,0,cep);
		$posto_cidade   = pg_result ($res,0,cidade);
		$posto_estado   = pg_result ($res,0,estado);
		$posto_fone     = pg_result ($res,0,fone);
		$posto_cnpj     = pg_result ($res,0,cnpj);
		$posto_ie       = pg_result ($res,0,ie);
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
	<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assist?ncia T?cnica e Fabricantes.">
	<meta name      ="KeyWords"      content="Assist?ncia T?cnica, Postos, Manuten??o, Internet, Webdesign, Or?amento, Comercial, J?ias, Callcenter">

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
 	border-left: dotted 0px #000000;
	padding: 1px,1px,1px,1px;
}

.conteudo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	text-align: left;
	background: #ffffff;
	border-right: dotted 0px #a0a0a0;
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
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 09px;
	font-weight: normal;
	border: 1px solid #a0a0a0;
}

.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 09px;
	font-weight: normal;
	border: 1px solid #a0a0a0;
}
</style>

<body>

<TABLE width="650px" border="0" cellspacing="1" cellpadding="2">
<TR class="titulo" style="text-align: center;">
<?
	if ($cliente_contrato <> 't') 
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
	else
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome)."_contrato.gif";
?>
	<TD rowspan="2" style="text-align: left;"><IMG SRC="<? echo $img_contrato ?>" ALT="ORDEM DE SERVIÇO"><br><FONT SIZE="1" COLOR=""><CENTER>OS DE COMPRESSOR</CENTER></FONT></TD>
	<TD style="font-size: 09px;"><? echo $posto_nome; ?></TD>
	<TD>DATA EMISSÂO</TD>
	<TD>NÙMERO</TD>
</TR>

<TR class="titulo">
	<TD style="font-size: 09px; text-align: center; width: 350px; ">
<?
switch (strlen (trim ($posto_cnpj))) {
case 0:
	$posto_cnpj = "&nbsp";
	break;
case 11:
	$posto_cnpj = substr ($posto_cnpj,0,3) . "." . substr ($posto_cnpj,3,3) . "." . substr ($posto_cnpj,6,3) . "-" . substr ($posto_cnpj,9,2);
	break;
case 14:
	$posto_cnpj = substr ($posto_cnpj,0,2) . "." . substr ($posto_cnpj,2,3) . "." . substr ($posto_cnpj,5,3) . "/" . substr ($posto_cnpj,8,4) . "-" . substr ($posto_cnpj,12,2);
	break;
}

	########## CABECALHO COM DADOS DO POSTOS ########## 
	echo $posto_endereco .",".$posto_numero." - CEP ".$posto_cep."<br>";
	echo $posto_cidade ." - ".$posto_estado." - Telefone: ".$posto_fone."<br>";
	echo "CNPJ ".$posto_cnpj ." <br> IE ".$posto_ie;
?>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 14px;">
		<b><? echo $data_abertura ?></b>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 14px;"align='center' nowrap>
		<b><? echo $codigo_posto.$sua_os ?></b>
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
	<TD class="conteudo" colspan='3'><? echo $cliente_nome ?>&nbsp</TD>
	<TD class="titulo">CNPJ/CPF</TD>
	<TD class="conteudo"><? echo $cliente_cpf ?>&nbsp</TD>
	<TD class="titulo">IE/RG</TD>
	<TD class="conteudo"><? echo $cliente_rg ?>&nbsp</TD>
</TR>

<!-- ====== ENDERE?O E TELEFONE ================ -->
<TR>
	<?
	if (strlen ($os) > 0) 
		$cliente_cep = substr ($cliente_cep,0,2) . "." . substr ($cliente_cep,2,3) . "-" . substr ($cliente_cep,5,3);
	?>
	<TD class="titulo">Endereço</TD>
	<TD class="conteudo" colspan='3'><? 	
	if (strlen ($os) > 0) echo $cliente_endereco . ", " . $cliente_numero . " " . $cliente_complemento ?>
	&nbsp</TD>
	<TD class="titulo">CEP</TD>
	<TD class="conteudo"><? echo $cliente_cep ?>&nbsp</TD>
	<TD class="titulo">Telefone</TD>
	<TD class="conteudo"><? echo $cliente_fone ?>&nbsp</TD>
</TR>

<!-- ====== Cep Municipio UF ================ -->
<TR>
	<TD class="titulo">Bairro</TD>
	<TD class="conteudo" colspan=3><? echo $cliente_bairro ?>&nbsp</TD>
	<TD class="titulo">Municipio</TD>
	<TD class="conteudo"><? echo $cliente_cidade ?>&nbsp</TD>
	<TD class="titulo">Estado</TD>
	<TD class="conteudo"><? echo $cliente_estado ?>&nbsp</TD>
</TR>

<!-- ====== CONTATO E CHAMADO ================ -->
<TR>
	<TD class="titulo">Defeito</TD>
	<TD class="conteudo" colspan='3'>
	<? 
	echo $defeito_reclamado 
	?>
	&nbsp</TD>
	<TD class="titulo">Contato</TD>
	<TD class="conteudo"><? echo $quem_abriu_chamado ?>&nbsp</TD>
	<TD class="titulo">Distância</TD>
	<TD class="conteudo"><? echo $qtde_km ?> km&nbsp</TD>
</TR>
<TR>
</TR>

<!-- 
<TR>
	<TD class="titulo">Taxa de visita:</TD>
	<TD class="conteudo">
	<?
	if (strlen ($os) > 0) 
		echo "R$ "; 
	?>&nbsp;</TD>
	<TD class="titulo">Hora t?cnica:</TD>
	<TD class="conteudo">
	<?
		if (strlen ($os) > 0) {
			$hora_tecnica = $hora_tecnica + $mao_de_obra;
			echo "R$ ";
		}
	?>&nbsp;</TD>
	<TD class="titulo">Valor/km:</TD>
	<TD class="conteudo">
	<?
	if (strlen ($os) > 0) 
		echo "R$ "; 
	?>&nbsp;</TD>
	<TD class="titulo">Valor di?ria:</TD>
	<TD class="conteudo">
	<?
	if (strlen ($os) > 0) 
		echo "R$ "; 
	?>&nbsp;</TD>
</TR>
-->
</TABLE>

<!-- ====== MODELO DO APARELHO ================ -->

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<?
	$sql = "SELECT	distinct
					tbl_produto.referencia                                          ,
					tbl_produto.descricao                                           ,
					tbl_produto.voltagem                                            ,
					tbl_os_produto.serie                                            ,
					tbl_os_produto.capacidade                                       ,
					tbl_defeito_reclamado.descricao  AS defeito_reclamado_descricao ,
					tbl_defeito_constatado.descricao AS defeito_constatado_descricao,
					tbl_causa_defeito.descricao      AS causa_defeito_descricao
			FROM	tbl_os_produto
			LEFT JOIN tbl_produto USING (produto)
			LEFT JOIN tbl_defeito_reclamado  USING(defeito_reclamado)
			LEFT JOIN tbl_defeito_constatado USING(defeito_constatado)
			LEFT JOIN tbl_causa_defeito      USING(causa_defeito)
			JOIN	tbl_os ON tbl_os.os = tbl_os_produto.os
			WHERE	tbl_os_produto.os = $os";
	$res = pg_exec ($con,$sql);

	$total_produtos = 0;

	for($i=0; $i<pg_numrows($res); $i++){
?>
<TR>
	<TD rowspan='3'><b><? echo $i + 1; ?></b></TD>
	<TD class="titulo">REFERÊNCIA</TD>
	<TD class="conteudo"><? echo pg_result($res,$i,referencia); ?> &nbsp;</TD>
	<TD class="titulo">DESCRICAO</TD>
	<TD class="conteudo" colspan="4"><? echo pg_result($res,$i,descricao); ?>&nbsp;</TD>
	<TD class="titulo">VOLTAGEM</TD>
	<TD class="conteudo"><? echo pg_result($res,$i,voltagem); ?>&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">CAPACIDADE</TD>
	<TD class="conteudo"><? echo pg_result($res,$i,capacidade); ?> &nbsp;</TD>
	<TD class="titulo">SÉRIE</TD>
	<TD class="conteudo" colspan="6"><? echo pg_result($res,$i,serie); ?> &nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">DEFEITO RECLAMADO</TD>
	<TD class="conteudo" colspan="2"><? echo pg_result($res,$i,defeito_reclamado_descricao); ?> &nbsp;</TD>
	<TD class="titulo">DEFEITO CONSTATADO</TD>
	<TD class="conteudo" colspan="2"><? echo pg_result($res,$i,defeito_constatado_descricao) ?> &nbsp;</TD>
	<TD class="titulo">CAUSA DEFEITO</TD>
	<TD class="conteudo" colspan="2"><? echo pg_result($res,$i,causa_defeito_descricao) ?> &nbsp;</TD>
</TR>
<TR>
	<TD colspan='10' height='1' bgcolor="#000000"></TD>
</TR>
<?
		$total_produtos ++;
	}
?>
</TABLE>

<!-- ======= CONTROLE DE HORAS ========= -->
<TABLE width="650" border="0" cellspacing="0" cellpadding="0">
<TR class="menu_top" bgcolor="#d0d0d0">
	<TD class="menu_top" bgcolor="#d0d0d0" rowspan="2">DATA</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan="2">TEMPO DE SERVIÇO</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" rowspan="2">KM<BR>TOTAL</TD>
</TR>
<TR>
	<TD class="menu_top" style='text-align: center;'>INÍCIO</TD>
	<TD class="menu_top" style='text-align: center;'>TÈRMINO</TD>
</TR>
<?
if (strlen($os) > 0) {
	// seleciona os visita
	$sql = "SELECT os_visita FROM tbl_os_visita WHERE os = $os ORDER BY os_visita";
	$vis = pg_exec ($con,$sql);
}

for( $i = 0 ; $i < 5 ; $i++){
	$class = 'table_line';

	if (strlen($os) > 0 AND strlen($msg_erro) == 0) {
		if (@pg_numrows($vis) > 0) {
			$os_visita = trim(@pg_result($vis,$i,os_visita));
		}

		if (strlen($os_visita) > 0) {
			$sql  =	"SELECT tbl_os_visita.os_visita                                                   ,
						to_char(tbl_os_visita.data,'DD/MM/YYYY')               AS data                ,
						to_char(tbl_os_visita.hora_chegada_cliente,'HH24:MI') AS hora_chegada_cliente ,
						to_char(tbl_os_visita.hora_saida_cliente,'HH24:MI')   AS hora_saida_cliente   ,
						km_chegada_cliente                                                            
					FROM    tbl_os_visita
					WHERE   tbl_os_visita.os        = $os
					AND     tbl_os_visita.os_visita = $os_visita
					AND     tbl_os.posto            = $login_posto
					ORDER BY tbl_os_visita.os_visita";
			$res = pg_exec($con,$sql);

			if (@pg_numrows($res) > 0){
				$data                 = pg_result($res,$i,data);
				$hora_chegada_cliente = pg_result($res,$i,hora_chegada_cliente);
				$hora_saida_cliente   = pg_result($res,$i,hora_saida_cliente);
				$km_chegada_cliente   = pg_result($res,$i,km_chegada_cliente);
				$class = 'table_line';
			}
		}
	}

	echo "<TR>\n";
	echo "	<TD class='$class' width='25%'>".$data."&nbsp;</TD>\n";
	echo "	<TD class='$class' width='25%'>".$hora_chegada_cliente."&nbsp;</TD>\n";
	echo "	<TD class='$class' width='25%'>".$hora_saida_cliente."&nbsp;</TD>\n";
	echo "	<TD class='$class' width='25%'>".$km_chegada_cliente."&nbsp;</TD>\n";
	echo "</TR>\n";

	$data                 = "";
	$hora_chegada_cliente = "";
	$hora_saida_cliente   = "";
	$km_chegada_cliente   = "";
}
?>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo" width="100">OBSERVAÇÔES</TD>
<?
if (strlen($obs) > 0){
?>
	<TD class="conteudo"><? echo $obs; ?>&nbsp;</TD>
</TR>
<?
}else{
?>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<?
}
?>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo" width="100">RELATÓRIO DO TÉCNICO</TD>
<?
if (strlen($tecnico) > 0){
?>
	<TD class="conteudo"><? echo $tecnico; ?>&nbsp;</TD>
</TR>
<?
}else{
?>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<?
}
?>
</TABLE>

<TABLE width="650" border="1" cellspacing="0" cellpadding="0">
<TR>
	<TD class="menu_top" colspan="5" bgcolor="#d0d0d0">PEÇAS</TD>
</TR>
<TR>
	<TD class="menu_top" width='10%'>ITEM</TD>
	<TD class="menu_top" width='20%'>REFERÊNCIA</TD>
	<TD class="menu_top" width='50%'>DESCRIÇÂO</TD>
	<TD class="menu_top" width='10%'>QTDE</TD>
	<TD class="menu_top" width='10%'>UNID</TD>
</TR>

<?
if(strlen($os) > 0){

	$sql = "SELECT	tbl_os_item.os_item                ,
					tbl_os_item.pedido                 ,
					tbl_os_item.qtde                   ,
					tbl_peca.referencia                ,
					tbl_peca.descricao                 ,
					tbl_tabela_item.preco AS preco_item
			FROM	tbl_os 
			JOIN	tbl_os_produto ON tbl_os_produto.os = tbl_os.os 
			JOIN	tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto 
			LEFT JOIN tbl_peca     ON tbl_peca.peca = tbl_os_item.peca 
			JOIN	tbl_tabela     ON tbl_tabela.tabela = 29
			LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca 
									  AND tbl_tabela_item.tabela = tbl_tabela.tabela 
			WHERE	tbl_os.os      = $os
			AND		tbl_os.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql) ;

	if(pg_numrows($res) > 0) {

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$os_item	= pg_result($res,$i,os_item);
			$pedido		= pg_result($res,$i,pedido);
			$peca		= pg_result($res,$i,referencia);
			$qtde		= pg_result($res,$i,qtde);
			$descricao	= pg_result($res,$i,descricao);

			echo "<TR height='20'>\n";
			echo "	<TD class='table_line1'>&nbsp;</TD>\n";
			echo "	<TD class='table_line'>$peca &nbsp;</TD>\n";
			echo "	<TD class='table_line1'>$descricao &nbsp;</TD>\n";
			echo "	<TD class='table_line'>$qtde &nbsp;</TD>\n";
			echo "	<TD class='table_line1'>$unid &nbsp;</TD>\n";
			echo "</TR>\n";
		}

	}else{

		for ( $i = 0 ; $i < $total_produtos + 7 ; $i++ ) {
?>
		<TR>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
		</TR>

<?
		}
	}
}else{

	for( $i = 0 ; $i < 7 ; $i++ ) {
?>
		<TR>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
		</TR>
<?
	}
}
?>
</TABLE>

<TABLE width="650px" border="0" cellspacing="2" cellpadding="0" height=40>
<TR>
	<TD colspan="4">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">EM </TD>
	<TD width='25%'> &nbsp;____&nbsp;/&nbsp;____&nbsp;/&nbsp;________</TD>
	<TD>&nbsp;</TD>
	<TD class="titulo">VISTO DO CLIENTE</TD>
	<TD colspan="3"> _____________________________________________</TD>
</TR>
<TR>
	<TD colspan="4">&nbsp;</TD>
</TR>
</TABLE>

<TABLE width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
</TR>
</TABLE>

<TABLE width="650px" border="0" cellspacing="1" cellpadding="2">
<TR>
	<TD COLSPAN="2" ><FONT SIZE="2" COLOR="" face="verdana">VIA DO CLIENTE</FONT></TD>
</TR>
<TR class="titulo" style="text-align: center;">
<?
	if ($cliente_contrato <> 't') 
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
	else
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome)."_contrato.gif";
?>
	<TD rowspan="2" style="text-align: left;"><IMG SRC="<? echo $img_contrato ?>" ALT="ORDEM DE SERVIÇO"><br><FONT SIZE="1" COLOR=""><CENTER>OS DE COMPRESSOR</CENTER></FONT></TD>
	<TD style="font-size: 09px;"><? echo $posto_nome; ?></TD>
	<TD>DATA EMISSÂO</TD>
	<TD>NÙMERO</TD>
</TR>

<TR class="titulo">
	<TD style="font-size: 09px; text-align: center; width: 350px; ">
<?
switch (strlen (trim ($posto_cnpj))) {
case 0:
	$posto_cnpj = "&nbsp";
	break;
case 11:
	$posto_cnpj = substr ($posto_cnpj,0,3) . "." . substr ($posto_cnpj,3,3) . "." . substr ($posto_cnpj,6,3) . "-" . substr ($posto_cnpj,9,2);
	break;
case 14:
	$posto_cnpj = substr ($posto_cnpj,0,2) . "." . substr ($posto_cnpj,2,3) . "." . substr ($posto_cnpj,5,3) . "/" . substr ($posto_cnpj,8,4) . "-" . substr ($posto_cnpj,12,2);
	break;
}

	########## CABECALHO COM DADOS DO POSTOS ########## 
	echo $posto_endereco .",".$posto_numero." - CEP ".$posto_cep."<br>";
	echo $posto_cidade ." - ".$posto_estado." - Telefone: ".$posto_fone."<br>";
	echo "CNPJ ".$posto_cnpj ." <br> IE ".$posto_ie;
?>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 14px;">
		<b><? echo $data_abertura ?></b>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 14px;"align='center' nowrap>
		<b><? echo $codigo_posto.$sua_os ?></b>
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
	<TD class="conteudo" colspan='3'><? echo $cliente_nome ?>&nbsp</TD>
	<TD class="titulo">CNPJ/CPF</TD>
	<TD class="conteudo"><? echo $cliente_cpf ?>&nbsp</TD>
	<TD class="titulo">IE/RG</TD>
	<TD class="conteudo"><? echo $cliente_rg ?>&nbsp</TD>
</TR>

<!-- ====== ENDERE?O E TELEFONE ================ -->
<TR>
	<?
	if (strlen ($os) > 0) 
		$cliente_cep = substr ($cliente_cep,0,2) . "." . substr ($cliente_cep,2,3) . "-" . substr ($cliente_cep,5,3);
	?>
	<TD class="titulo">Endereço</TD>
	<TD class="conteudo" colspan='3'><? 	
	if (strlen ($os) > 0) echo $cliente_endereco . ", " . $cliente_numero . " " . $cliente_complemento ?>
	&nbsp</TD>
	<TD class="titulo">CEP</TD>
	<TD class="conteudo"><? echo $cliente_cep ?>&nbsp</TD>
	<TD class="titulo">Telefone</TD>
	<TD class="conteudo"><? echo $cliente_fone ?>&nbsp</TD>
</TR>

<!-- ====== Cep Municipio UF ================ -->
<TR>
	<TD class="titulo">Bairro</TD>
	<TD class="conteudo" colspan=3><? echo $cliente_bairro ?>&nbsp</TD>
	<TD class="titulo">Municipio</TD>
	<TD class="conteudo"><? echo $cliente_cidade ?>&nbsp</TD>
	<TD class="titulo">Estado</TD>
	<TD class="conteudo"><? echo $cliente_estado ?>&nbsp</TD>
</TR>

</table>

<TABLE width="650px" border="0" cellspacing="2" cellpadding="0" height=40>
<TR>
	<TD colspan="4">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">EM </TD>
	<TD width='25%'> &nbsp;____&nbsp;/&nbsp;____&nbsp;/&nbsp;________</TD>
	<TD>&nbsp;</TD>
	<TD class="titulo">VISTO DA ASSISTÊNCIA</TD>
	<TD colspan="3"> _____________________________________________</TD>
</TR>
<TR>
	<TD colspan="4">&nbsp;</TD>
</TR>
</TABLE>

<BR><BR><BR><BR>

</BODY>
</html>
<SCRIPT LANGUAGE="JavaScript">
<!--
window.print();
//-->
</SCRIPT>

<CENTER><a href='menu_cadastro.php'><FONT SIZE="2" face="verdana">Voltar para menu de Ordem de Serviço</FONT></a></center>

<BR><BR>