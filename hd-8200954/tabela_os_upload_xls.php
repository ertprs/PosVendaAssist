<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';
$layout_menu = 'os';
include "cabecalho.php";

$data = date ("d-m-Y-H-i");

echo `mkdir /tmp/assist`;
echo `chmod 777 /tmp/assist`;
//echo `rm /var/www/assist/www/download/tabela-$data-$nome_fabrica-$login_posto.xls`;
echo `rm -f /tmp/assist/dados-upload-$login_fabrica-$data.html`;
echo `rm -f /tmp/assist/dados-upload-$login_fabrica-$data.xls`;
echo `rm -f /var/www/assist/www/download/dados-upload-$login_fabrica-$data.zip`;

echo `rm -f /tmp/assist/dados-upload-lista-basica-$login_fabrica-$data.html`;
echo `rm -f /tmp/assist/dados-upload-lista-basica-$login_fabrica-$data.xls`;
echo `rm -f /tmp/assist/dados-upload-lista-basica-$login_fabrica-$data.txt`;
echo `rm -f /var/www/assist/www/download/dados-upload-lista-basica-$login_fabrica-$data.zip`;
echo `rm -f /var/www/assist/www/download/dados-upload-lista-basica2-$login_fabrica-$data.zip`;


$fp = fopen ("/tmp/assist/dados-upload-$login_fabrica-$data.html","w");
$fp_lista = fopen ("/tmp/assist/dados-upload-lista-basica-$login_fabrica-$data.html","w");
$fp_lista_txt = fopen ("/tmp/assist/dados-upload-lista-basica-$login_fabrica-$data.txt","w");


fputs ($fp,"<html>");
fputs ($fp,"<head>");
fputs ($fp,"<title>OS UPLOAD</title>");
fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
fputs ($fp,"</head>");
fputs ($fp,"<body>");

fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='1' cellspacing='1'>");



//-------------------------------------- produtos ---------------------------------------------//
fputs ($fp, "<tr align='center'>");
if($sistema_lingua=='ES'){
	fputs ($fp, "<td colspan='5'>CÓDIGO DE LA FABRICA: $login_fabrica</TD>");
}else{
	fputs ($fp, "<td colspan='5'>CÓDIGO DA FABRICA: $login_fabrica</TD>");
}
fputs ($fp, "</tr>\n");


fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='1' cellspacing='1'>");

//-------------------------------------- produtos ---------------------------------------------//
fputs ($fp, "<tr align='center'>");
if($sistema_lingua=='ES'){
	fputs ($fp, "<td colspan='5'>PRODUCTOS</TD>");
}else{
	fputs ($fp, "<td colspan='5'>PRODUTOS</TD>");
}
fputs ($fp, "</tr>\n");
fputs ($fp, "<tr bgcolor='#0000FF' align='center'>");
fputs ($fp, "<td nowrap><font color='#FFFFFF'>REFERENCIA</font></TD>");
if($sistema_lingua=='ES'){
	fputs ($fp, "<td nowrap><font color='#FFFFFF'>DESCRIPCIÓN</font></TD>");
}else{
	fputs ($fp, "<td nowrap><font color='#FFFFFF'>DESCRICAO</font></TD>");
}
if($sistema_lingua=='ES'){
	fputs ($fp, "<td nowrap><font color='#FFFFFF'>VOLTAJE</font></TD>");
}else{
	fputs ($fp, "<td nowrap><font color='#FFFFFF'>VOLTAGEM</font></TD>");
}
if($sistema_lingua=='ES'){
	fputs ($fp, "<td nowrap><font color='#FFFFFF'>LÍNEA</font></TD>");
}else{
	fputs ($fp, "<td nowrap><font color='#FFFFFF'>LINHA</font></TD>");
}

fputs ($fp, "<td nowrap><font color='#FFFFFF'>FAMILIA</font></TD>");

fputs ($fp, "</tr>\n");


$sql = "SELECT  tbl_produto.produto,
				tbl_produto.referencia,
				tbl_produto.descricao,
				tbl_produto.voltagem,
				tbl_produto.linha,
				tbl_produto.familia
		FROM tbl_produto
		JOIN tbl_linha using (linha)
		WHERE fabrica = $login_fabrica
		AND tbl_produto.ativo IS TRUE
		AND tbl_produto.produto_principal IS TRUE
		AND tbl_produto.abre_os IS TRUE
		ORDER BY tbl_produto.descricao";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	for ($i = 0 ; $i < pg_numrows($res) ; $i++){
		$produto           = trim(@pg_result($res,$i,produto));
		$produto_descricao = trim(@pg_result($res,$i,descricao));
		//HD 14841
		$sql_idioma = " SELECT * FROM tbl_produto_idioma
						WHERE produto     = $produto
						AND upper(idioma) = '$sistema_lingua'";
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$produto_descricao  = trim(@pg_result($res_idioma,0,descricao));
		}
		fputs ($fp, "<tr>");
		fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,referencia))."</TD>\n");
		fputs($fp,"<TD nowrap>$produto_descricao</TD>\n");
		fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,voltagem))."</TD>\n");
		fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,linha))."</TD>\n");
		fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,familia))."</TD>\n");
		fputs ($fp, "</tr>\n");
	}
}

fputs ($fp, "</table>\n");
fputs ($fp, "<table height='20'><tr class='menu_top'><td colspan='2' align='left'>Total de " . pg_numrows($res) . " resultado(s) encontrado(s).</td></tr></table>");
//---------------------------------------------------------------------------------------------//

//----------------------------------------- peças ---------------------------------------------//
fputs ($fp, "<br>");
fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='1' cellspacing='1'>");
fputs ($fp, "<tr align='center'>");
if($sistema_lingua=='ES'){
	fputs ($fp, "<td colspan='2'>PIEZAS</TD>");
}else{
	fputs ($fp, "<td colspan='2'>PEÇAS</TD>");
}
fputs ($fp, "</tr>\n");
fputs ($fp, "<tr bgcolor='#0000FF' align='center'>");
fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>REFERENCIA</FONT></TD>");
if($sistema_lingua=='ES'){
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>DESCRIPCIÓN</FONT></TD>");
}else{
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>DESCRICAO</FONT></TD>");
}
fputs ($fp, "</tr>\n");

$sql = "SELECT  peca,
				referencia,
				REPLACE(descricao,'<','') as descricao
		FROM tbl_peca
		WHERE fabrica = $login_fabrica
		AND ativo IS TRUE
		ORDER BY descricao";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	for ($i = 0 ; $i < pg_numrows($res) ; $i++){
		$peca            = trim(@pg_result($res,$i,peca));
		$descricao_peca  = trim(@pg_result($res,$i,descricao));
		//hd 14841
		$sql_idioma = "SELECT * FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = '$sistema_lingua'";

		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$descricao_peca  = trim(@pg_result($res_idioma,0,descricao));
		}
		fputs ($fp, "<tr>");
		fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,referencia))."</TD>\n");
		fputs($fp,"<TD nowrap>$descricao_peca</TD>\n");
		fputs ($fp, "</tr>\n");
	}
}

fputs ($fp, "</table>\n");
fputs ($fp, "<table height='20'><tr class='menu_top'><td colspan='2' align='left'>Total de " . pg_numrows($res) . " resultado(s) encontrado(s).</td></tr></table>");
//---------------------------------------------------------------------------------------------//

//------------------------------------------ subconjunto --------------------------------------//
if ($login_fabrica == 14) {
	fputs ($fp, "<br>");
	fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='1' cellspacing='1'>");
	fputs ($fp, "<tr align='center'>");
	fputs ($fp, "<td colspan='4'>SUBCONJUNTO</TD>");
	fputs ($fp, "</tr>\n");
	fputs ($fp, "<tr bgcolor='#0000FF' align='center'>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>REF. PRODUTO</FONT></TD>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>DESC. PRODUTO</FONT></TD>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>REF. SUBCONJUNTO</FONT></TD>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>DESC. SUBCONJUNTO</FONT></TD>");
	fputs ($fp, "</tr>\n");

	//pega todos os produtos para depois separar os subprodutos
	$sqlp = "SELECT tbl_produto.produto,
					tbl_produto.referencia,
					tbl_produto.descricao
			 FROM tbl_produto
			 JOIN tbl_linha using (linha)
			 WHERE fabrica = $login_fabrica
			 AND tbl_produto.ativo IS TRUE
			 AND tbl_produto.produto_principal IS TRUE
			 AND tbl_produto.abre_os IS TRUE
			 ORDER BY tbl_produto.descricao";
	$resp = pg_exec($con,$sqlp);

	if (pg_numrows($resp) > 0) {
		//pega os subprodutos de cada produto inclusive o proprio produto como sendo subprouto dele mesmo
		for ($i = 0 ; $i < pg_numrows($resp) ; $i++){
			$produtop = pg_result($resp,$i,produto);

			$sqls = "SELECT tbl_produto.referencia    as prod_ref,
							tbl_produto.descricao     as prod_des,
							tbl_subproduto.referencia as subp_ref,
							tbl_subproduto.descricao  as subp_des
					FROM tbl_produto
					JOIN (	SELECT  produto_pai           ,
									produto_filho         ,
									tbl_produto.referencia,
									tbl_produto.descricao
							FROM tbl_subproduto
							JOIN tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
					) tbl_subproduto ON tbl_produto.produto = tbl_subproduto.produto_pai
					JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
					AND fabrica = $login_fabrica
					AND tbl_produto.produto = $produtop
					AND tbl_produto.ativo IS TRUE
					AND tbl_produto.abre_os IS TRUE
					AND tbl_produto.produto_principal IS TRUE
					ORDER BY tbl_produto.referencia;";
			$ress = pg_exec($con,$sqls);

			//escreve o proprio produto como sendo seu subproduto
			fputs ($fp, "<tr>");
			fputs($fp,"<TD nowrap>".trim(pg_result($resp,$i,referencia))."</TD>\n");
			fputs($fp,"<TD nowrap>".trim(pg_result($resp,$i,descricao))."</TD>\n");
			fputs($fp,"<TD nowrap>".trim(pg_result($resp,$i,referencia))."</TD>\n");
			fputs($fp,"<TD nowrap>".trim(pg_result($resp,$i,descricao))."</TD>\n");
			fputs ($fp, "</tr>\n");
			if (pg_numrows($res) > 0) {
				for ($x = 0 ; $x < pg_numrows($ress) ; $x++){
					fputs ($fp, "<tr>");
					fputs($fp,"<TD nowrap>".trim(pg_result($ress,$x,prod_ref))."</TD>\n");
					fputs($fp,"<TD nowrap>".trim(pg_result($ress,$x,prod_des))."</TD>\n");
					fputs($fp,"<TD nowrap>".trim(pg_result($ress,$x,subp_ref))."</TD>\n");
					fputs($fp,"<TD nowrap>".trim(pg_result($ress,$x,subp_des))."</TD>\n");
					fputs ($fp, "</tr>\n");
				}
			}
		}

		fputs ($fp, "</table>\n");
		fputs ($fp, "<table height='20'><tr class='menu_top'><td colspan='3' align='left'>Total de " . pg_numrows($res) . " resultado(s) encontrado(s).</td></tr></table>");
	}
}
// ------------------------------------------------------------------------------------ //

//------------------------------------ lista básica -----------------------------------//

if ($login_fabrica == 14 ) {
	fputs ($fp, "<br>");
	fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='1' cellspacing='1'>");
	fputs ($fp, "<tr align='center'>");
	fputs ($fp, "<td colspan='4'>LISTA BÁSICA</TD>");
	fputs ($fp, "</tr>\n");
	fputs ($fp, "<tr bgcolor='#0000FF' align='center'>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>REF. PRODUTO</FONT></TD>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>DESC. PRODUTO</FONT></TD>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>REF. PEÇA</FONT></TD>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>DESC. PEÇA</FONT></TD>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>POSIÇÃO DA PEÇA</FONT></TD>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>QTD. MÁXIMA</FONT></TD>");
	fputs ($fp, "</tr>\n");

	$sql = "SELECT  tbl_produto.referencia as ref_produto,
					tbl_produto.descricao as desc_produto,
					tbl_peca.referencia as ref_peca,
					tbl_peca.descricao as desc_peca,
					tbl_lista_basica.posicao,
					tbl_lista_basica.qtde
			FROM tbl_lista_basica
			JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto
			JOIN tbl_peca ON tbl_peca.peca = tbl_lista_basica.peca
			WHERE tbl_lista_basica.fabrica = $login_fabrica
			ORDER BY ref_produto,ref_peca";
//				AND tbl_lista_basica.ativo IS NOT FALSE

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		for ($i = 0 ; $i < pg_numrows($res) ; $i++){
			fputs ($fp, "<tr>");
			fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,ref_produto))."</TD>\n");
			fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,desc_produto))."</TD>\n");
			fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,ref_peca))."</TD>\n");
			fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,desc_peca))."</TD>\n");
			fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,posicao))."</TD>\n");
			fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,qtde))."</TD>\n");
			fputs ($fp, "</tr>\n");
		}
	}

	fputs ($fp, "</table>\n");
	fputs ($fp, "<table height='20'><tr class='menu_top'><td colspan='3' align='left'>Total de " . pg_numrows($res) . " resultado(s) encontrado(s).</td></tr></table>");
}
//---------------------------------------------------------------------------------------------//


//----------------------------------------- defeito reclamado ---------------------------------//
fputs ($fp, "<br>");
fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='1' cellspacing='1'>");
fputs ($fp, "<tr align='center'>");
if($sistema_lingua=='ES'){
	fputs ($fp, "<td colspan='4'>FALLA RECLAMADA</TD>");
}else{
	fputs ($fp, "<td colspan='4'>DEFEITO RECLAMADO</TD>");
}
fputs ($fp, "</tr>\n");
fputs ($fp, "<tr bgcolor='#0000FF' align='center'>");
fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>CODIGO</FONT></TD>");
if($sistema_lingua=='ES'){
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>DESCRIPCIÓN</FONT></TD>");
}else{
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>DESCRICAO</FONT></TD>");
}
if($sistema_lingua=='ES'){
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>LÍNEA</FONT></TD>");
}else{
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>LINHA</FONT></TD>");
}
fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>FAMILIA</FONT></TD>");
fputs ($fp, "</tr>\n");

$sql = "SELECT  defeito_reclamado,
				descricao,
				linha,
				familia
		FROM tbl_defeito_reclamado
		WHERE fabrica = $login_fabrica
		AND ativo IS TRUE
		ORDER BY descricao";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	for ($i = 0 ; $i < pg_numrows($res) ; $i++){
		$defeito_reclamado            = trim(pg_result($res,$i,defeito_reclamado));
		$defeito_reclamado_descricao  = trim(pg_result($res,$i,descricao));
		//HD 14841
		$sql_idioma = "SELECT * FROM tbl_defeito_reclamado_idioma
						WHERE defeito_reclamado = $defeito_reclamado
						AND upper(idioma)        = '$sistema_lingua'";
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$defeito_reclamado_descricao  = trim(@pg_result($res_idioma,0,descricao));
		}
		fputs ($fp, "<tr>");
		fputs($fp,"<TD nowrap>$defeito_reclamado</TD>\n");
		fputs($fp,"<TD nowrap>$defeito_reclamado_descricao</TD>\n");
		fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,linha))."</TD>\n");
		fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,familia))."</TD>\n");
		fputs ($fp, "</tr>\n");
	}
}

fputs ($fp, "</table>\n");
fputs ($fp, "<table height='20'><tr class='menu_top'><td colspan='2' align='left'>Total de " . pg_numrows($res) . " resultado(s) encontrado(s).</td></tr></table>");
//---------------------------------------------------------------------------------------------//

//----------------------------------------- defeito constatado --------------------------------//
fputs ($fp, "<br>");
fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='1' cellspacing='1'>");
fputs ($fp, "<tr align='center'>");
if($sistema_lingua=='ES'){
	fputs ($fp, "<td colspan='4'>DEFECTO CONSTATADO</TD>");
}else{
	fputs ($fp, "<td colspan='4'>DEFEITO CONSTATADO</TD>");
}
fputs ($fp, "</tr>\n");
fputs ($fp, "<tr bgcolor='#0000FF' align='center'>");
fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>CODIGO</FONT></TD>");
if($sistema_lingua=='ES'){
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>DESCRIPCIÓN</FONT></TD>");
}else{
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>DESCRICAO</FONT></TD>");
}
if($sistema_lingua=='ES'){
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>LÍNEA</FONT></TD>");
}else{
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>LINHA</FONT></TD>");
}
fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>FAMILIA</FONT></TD>");
fputs ($fp, "</tr>\n");

$sql = "SELECT  defeito_constatado,
				descricao,
				linha,
				familia
		FROM tbl_defeito_constatado
		WHERE fabrica = $login_fabrica
		AND ativo IS TRUE
		ORDER BY descricao";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	for ($i = 0 ; $i < pg_numrows($res) ; $i++){
		$defeito_constatado           = trim(@pg_result($res,$i,defeito_constatado));
		$defeito_constatado_descricao = trim(@pg_result($res,$i,descricao));

		$sql_idioma = "SELECT * FROM tbl_defeito_constatado_idioma
						WHERE defeito_constatado = $defeito_constatado
						AND upper(idioma)        = '$sistema_lingua'";
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$defeito_constatado_descricao  = trim(@pg_result($res_idioma,0,descricao));
		}
		fputs ($fp, "<tr>");
		fputs($fp,"<TD nowrap>$defeito_constatado</TD>\n");
		fputs($fp,"<TD nowrap>$defeito_constatado_descricao</TD>\n");
		fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,linha))."</TD>\n");
		fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,familia))."</TD>\n");
		fputs ($fp, "</tr>\n");
	}
}

fputs ($fp, "</table>\n");
fputs ($fp, "<table height='20'><tr class='menu_top'><td colspan='2' align='left'>Total de " . pg_numrows($res) . " resultado(s) encontrado(s).</td></tr></table>");
//---------------------------------------------------------------------------------------------//

//----------------------------------------- defeito das peças --------------------------------//
fputs ($fp, "<br>");
fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='1' cellspacing='1'>");
fputs ($fp, "<tr align='center'>");
if($sistema_lingua=='ES'){
	fputs ($fp, "<td colspan='2'>DEFECTO DE PIEZAS</TD>");
}else{
	fputs ($fp, "<td colspan='2'>DEFEITO DA PEÇAS</TD>");
}
fputs ($fp, "</tr>\n");
fputs ($fp, "<tr bgcolor='#0000FF' align='center'>");
fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>CODIGO</FONT></TD>");
if($sistema_lingua=='ES'){
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>DESCRIPCIÓN</FONT></TD>");
}else{
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>DESCRICAO</FONT></TD>");
}
fputs ($fp, "</tr>\n");

$sql = "SELECT  defeito,
				descricao
		FROM tbl_defeito
		WHERE fabrica = $login_fabrica
		AND ativo IS TRUE
		ORDER BY descricao";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	for ($i = 0 ; $i < pg_numrows($res) ; $i++){
		fputs ($fp, "<tr>");
		fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,defeito))."</TD>\n");
		fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,descricao))."</TD>\n");
		fputs ($fp, "</tr>\n");
	}
}

fputs ($fp, "</table>\n");
fputs ($fp, "<table height='20'><tr class='menu_top'><td colspan='2' align='left'>Total de " . pg_numrows($res) . " resultado(s) encontrado(s).</td></tr></table>");
//---------------------------------------------------------------------------------------------//

//----------------------------------------- serviço realizado --------------------------------//
fputs ($fp, "<br>");
fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='1' cellspacing='1'>");
fputs ($fp, "<tr align='center'>");
if($sistema_lingua=='ES'){
	fputs ($fp, "<td colspan='3'>INDENTIFICACIÓN</TD>");
}else{
	fputs ($fp, "<td colspan='3'>SERVIÇO REALIZADO (USE TAMBÉM PARA O CAMPO SOLUÇÃO)</TD>");
}
fputs ($fp, "</tr>\n");
fputs ($fp, "<tr bgcolor='#0000FF' align='center'>");
fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>CODIGO</FONT></TD>");
fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>DESCRICAO</FONT></TD>");
fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>LINHA</FONT></TD>");
fputs ($fp, "</tr>\n");

$sql = "SELECT  servico_realizado,
				descricao,
				linha
		FROM tbl_servico_realizado
		WHERE fabrica = $login_fabrica
		AND ativo IS TRUE
		ORDER BY descricao";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	for ($i = 0 ; $i < pg_numrows($res) ; $i++){
		$servico_realizado = trim(@pg_result($res,$i,servico_realizado));
		$xsolucao          = trim(@pg_result($res,$i,descricao));

		$sql_idioma = " SELECT * FROM tbl_servico_realizado_idioma
						WHERE servico_realizado = $servico_realizado
						AND upper(idioma)       = '$sistema_lingua'";
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) $xsolucao  = trim(@pg_result($res_idioma,0,descricao));

		fputs ($fp, "<tr>");
		fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,servico_realizado))."</TD>\n");
		fputs($fp,"<TD nowrap>$xsolucao</TD>\n");
		fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,linha))."</TD>\n");
		fputs ($fp, "</tr>\n");
	}
}

fputs ($fp, "</table>\n");
fputs ($fp, "<table height='20'><tr class='menu_top'><td colspan='2' align='left'>Total de " . pg_numrows($res) . " resultado(s) encontrado(s).</td></tr></table>");
//---------------------------------------------------------------------------------------------//



if($login_fabrica == 20 ){
	//----------------------------------- Tipo de Atendimento --------------------------------//
	fputs ($fp, "<br>");
	fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='1' cellspacing='1'>");
	fputs ($fp, "<tr align='center'>");
	if($sistema_lingua=='ES'){
		fputs ($fp, "<td colspan='2'>TIPO DE ATENCIÓN</TD>");
	}else{
		fputs ($fp, "<td colspan='2'>TIPO DE ATENDIMENTO</TD>");
	}
	fputs ($fp, "</tr>\n");
	fputs ($fp, "<tr bgcolor='#0000FF' align='center'>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>CODIGO</FONT></TD>");
	if($sistema_lingua=='ES'){
		fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>DESCRIPCIÓN</FONT></TD>");
	}else{
		fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>DESCRICAO</FONT></TD>");
	}
	fputs ($fp, "</tr>\n");

	$sql = "
	SELECT *
	FROM tbl_tipo_atendimento
	WHERE fabrica = $login_fabrica
		AND ativo IS TRUE
		ORDER BY descricao;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		for ($i = 0 ; $i < pg_numrows($res) ; $i++){
			$x_tipo_atendimento     = trim(pg_result($res,$i,tipo_atendimento));
			$descricao_atendimento  = trim(pg_result($res,$i,descricao));
			//hd 18481
			$sql_idioma = "SELECT * FROM tbl_tipo_atendimento_idioma WHERE tipo_atendimento = $x_tipo_atendimento AND upper(idioma) = '$sistema_lingua'";

			$res_idioma = @pg_exec($con,$sql_idioma);

			if (@pg_numrows($res_idioma) >0) {
				$descricao_atendimento  = trim(@pg_result($res_idioma,0,descricao));
			}
			fputs ($fp, "<tr>");
			fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,tipo_atendimento))."</TD>\n");
			fputs($fp,"<TD nowrap>$descricao_atendimento</TD>\n");
			fputs ($fp, "</tr>\n");
		}
	}

	fputs ($fp, "</table>\n");
	fputs ($fp, "<table height='20'><tr class='menu_top'><td colspan='2' align='left'>Total de " . pg_numrows($res) . " resultado(s) encontrado(s).</td></tr></table>");
	//----------------------------------------------------------------------------------------//
}

if($login_fabrica == 20 ){
	//----------------------------------- Segmento Atuação --------------------------------//
	fputs ($fp, "<br>");
	fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='1' cellspacing='1'>");
	fputs ($fp, "<tr align='center'>");
	if($sistema_lingua=='ES'){
		fputs ($fp, "<td colspan='2'>SEGMENTO DE ACTUACIÓN</TD>");
	}else{
		fputs ($fp, "<td colspan='2'>SEGMENTO ATUAÇÃO</TD>");
	}
	fputs ($fp, "</tr>\n");
	fputs ($fp, "<tr bgcolor='#0000FF' align='center'>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>CODIGO</FONT></TD>");
	if($sistema_lingua=='ES'){
		fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>DESCRIPCIÓN</FONT></TD>");
	}else{
		fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>DESCRICAO</FONT></TD>");
	}
	fputs ($fp, "</tr>\n");

	$sql = "
	SELECT *
	FROM tbl_segmento_atuacao
	WHERE fabrica = $login_fabrica
		AND ativo IS TRUE
		ORDER BY descricao;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		for ($i = 0 ; $i < pg_numrows($res) ; $i++){
				$x_segmento_atuacao = trim(pg_result($res,$i,segmento_atuacao));
				$descricao_segmento = trim(@pg_result($res,$i,descricao));

				$sql_idioma = "SELECT * FROM tbl_segmento_atuacao_idioma WHERE segmento_atuacao = $x_segmento_atuacao AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = @pg_exec($con,$sql_idioma);

				if (@pg_numrows($res_idioma) >0) $descricao_segmento  = trim(@pg_result($res_idioma,0,descricao));
			fputs ($fp, "<tr>");
			fputs($fp,"<TD nowrap>$x_segmento_atuacao</TD>\n");
			fputs($fp,"<TD nowrap>$descricao_segmento</TD>\n");
			fputs ($fp, "</tr>\n");
		}
	}

	fputs ($fp, "</table>\n");
	fputs ($fp, "<table height='20'><tr class='menu_top'><td colspan='2' align='left'>Total de " . pg_numrows($res) . " resultado(s) encontrado(s).</td></tr></table>");
	//----------------------------------------------------------------------------------------//
}


if($login_fabrica == 20 and $sistema_lingua <>'ES'){
	//----------------------------------- Promotor--------------------------------//
	fputs ($fp, "<br>");
	fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='1' cellspacing='1'>");
	fputs ($fp, "<tr align='center'>");
	fputs ($fp, "<td colspan='3'>PROMOTOR TREINAMENTO</TD>");
	fputs ($fp, "</tr>\n");
	fputs ($fp, "<tr bgcolor='#0000FF' align='center'>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>CODIGO</FONT></TD>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>NOME</FONT></TD>");
	fputs ($fp, "</tr>\n");

	$sql = "
			SELECT tbl_promotor_treinamento.promotor_treinamento,
						tbl_promotor_treinamento.nome,
						tbl_promotor_treinamento.email,
						tbl_promotor_treinamento.ativo,
						tbl_escritorio_regional.descricao
			FROM tbl_promotor_treinamento
			JOIN tbl_escritorio_regional USING(escritorio_regional)
			WHERE tbl_promotor_treinamento.fabrica = $login_fabrica
			AND   tbl_promotor_treinamento.ativo ='t'
			ORDER BY tbl_promotor_treinamento.nome;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		for ($i = 0 ; $i < pg_numrows($res) ; $i++){
			fputs ($fp, "<tr>");
			fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,promotor_treinamento))."</TD>\n");
			fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,nome))."</TD>\n");
			fputs ($fp, "</tr>\n");
		}
	}

	fputs ($fp, "</table>\n");
	fputs ($fp, "<table height='20'><tr class='menu_top'><td colspan='2' align='left'>Total de " . pg_numrows($res) . " resultado(s) encontrado(s).</td></tr></table>");
	//----------------------------------------------------------------------------------------//
}


//takashi 29-11
//IGOR HD:10450 - 26/02/2008 ADD BOSCH

if($login_fabrica==3 OR $login_fabrica == 14 OR $login_fabrica == 20 ){
	//----------------------------------------- LISTA BASICA --------------------------------//
	fputs ($fp_lista, "<br>");
	fputs ($fp_lista,"<table width='100%' align='left' border='1' cellpadding='1' cellspacing='1'>");
	fputs ($fp_lista, "<tr align='center'>");
	fputs ($fp_lista, "<td colspan='2'>LISTA BASICA</TD>");
	fputs ($fp_lista, "</tr>\n");
	fputs ($fp_lista, "<tr bgcolor='#0000FF' align='center'>");
	if($sistema_lingua=='ES'){
		fputs ($fp_lista, "<td nowrap><FONT  COLOR='#FFFFFF'>REFERENCIA PRODUCTO</FONT></TD>");
		fputs ($fp_lista, "<td nowrap><FONT  COLOR='#FFFFFF'>REFERENCIA PIEZA</FONT></TD>");
	}else{
		fputs ($fp_lista, "<td nowrap><FONT  COLOR='#FFFFFF'>REFERENCIA PRODUTO</FONT></TD>");
		fputs ($fp_lista, "<td nowrap><FONT  COLOR='#FFFFFF'>REFERENCIA PECA</FONT></TD>");
	}
	fputs ($fp_lista, "</tr>\n");

	if($sistema_lingua=='ES'){
		fputs ($fp_lista_txt, "REFERENCIA PRODUCTO \t REFERENCIA PIEZA\n");
	}else{
		fputs ($fp_lista_txt, "REFERENCIA PRODUTO \t REFERENCIA PEÇA\n");
	}

	$sql = "SELECT tbl_produto.referencia as ref_produto, tbl_peca.referencia as ref_peca
			FROM tbl_lista_basica
			JOIN tbl_produto using(produto)
			JOIN tbl_peca using(peca)
			WHERE tbl_lista_basica.fabrica=$login_fabrica AND tbl_lista_basica.ativo is not false
			ORDER BY tbl_produto.referencia, tbl_peca.referencia ";
	$res = pg_exec($con,$sql);


	if (pg_numrows($res) > 0) {
		for ($i = 0 ; $i < pg_numrows($res) ; $i++){
			fputs ($fp_lista, "<tr>");
			fputs ($fp_lista,"<TD nowrap>".trim(pg_result($res,$i,ref_produto))."</TD>\n");
			fputs ($fp_lista,"<TD nowrap>".trim(pg_result($res,$i,ref_peca))."</TD>\n");
			fputs ($fp_lista, "</tr>\n");
			fputs ($fp_lista_txt, trim(pg_result($res,$i,ref_produto))."\t".trim(pg_result($res,$i,ref_peca)) ."\n");
		}
	}

	fputs ($fp_lista, "</table>\n");
	fputs ($fp_lista, "<table height='20'><tr class='menu_top'><td colspan='2' align='left'>Total de " . pg_numrows($res) . " resultado(s) encontrado(s).</td></tr></table>");
	fputs ($fp_lista_txt, "Total de " . pg_numrows($res) . " resultado(s) encontrado(s)\n");

	//---------------------------------------------------------------------------------------------//
	//takashi 29-11 lista basica
}

fputs ($fp_lista,"</body>");
fputs ($fp_lista,"</html>");
fclose ($fp_lista);

fputs ($fp,"</body>");
fputs ($fp,"</html>");
fclose ($fp);

fputs ($fp_lista_txt,"fim do arquivo");
fclose ($fp_lista_txt);

//gera o xls
echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /tmp/assist/dados-upload-$login_fabrica-$data.xls /tmp/assist/dados-upload-$login_fabrica-$data.html`;

//gera o zip
echo `cd /tmp/assist/; rm -rf dados-upload-$login_fabrica-$data.zip; zip -o dados-upload-$login_fabrica-$data.zip dados-upload-$login_fabrica-$data.xls > /dev/null`;

//move o zip para "/var/www/assist/www/download/"
echo `mv  /tmp/assist/dados-upload-$login_fabrica-$data.zip /var/www/assist/www/download/dados-upload-$login_fabrica-$data.zip`;



//gera o xls
echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /tmp/assist/dados-upload-lista-basica-$login_fabrica-$data.xls /tmp/assist/dados-upload-lista-basica-$login_fabrica-$data.html`;

//gera o zip
echo `cd /tmp/assist/; rm -rf dados-upload-lista-basica-$login_fabrica-$data.zip; zip -o dados-upload-lista-basica-$login_fabrica-$data.zip dados-upload-lista-basica-$login_fabrica-$data.xls > /dev/null`;

//move o zip para "/var/www/assist/www/download/"
echo `mv  /tmp/assist/dados-upload-lista-basica-$login_fabrica-$data.zip /var/www/assist/www/download/dados-upload-lista-basica-$login_fabrica-$data.zip`;

//gera o zip
echo `cd /tmp/assist/; rm -rf dados-upload-lista-basica2-$login_fabrica-$data.zip; zip -o dados-upload-lista-basica2-$login_fabrica-$data.zip dados-upload-lista-basica-$login_fabrica-$data.txt > /dev/null`;


//move o zip para "/var/www/assist/www/download/"
echo `mv  /tmp/assist/dados-upload-lista-basica2-$login_fabrica-$data.zip /var/www/assist/www/download/dados-upload-lista-basica2-$login_fabrica-$data.zip`;

echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
echo"<tr>";
if($sistema_lingua=='ES'){
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Click acá para hacer </font><a href='download/dados-upload-$login_fabrica-$data.zip'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download del archivo</font></a>.</td>";
}else{
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='download/dados-upload-$login_fabrica-$data.zip'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo</font></a>.</td>";
}
echo "</tr>";
echo "</table>";



echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
echo"<tr>";
if($sistema_lingua=='ES'){
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Click acá para hacer </font><a href='download/dados-upload-lista-basica-$login_fabrica-$data.zip'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download del archivo de lista basica</font></a>.</td>";
}else{
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='download/dados-upload-lista-basica-$login_fabrica-$data.zip'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo de lista basica</font></a>.</td>";

}
echo "</tr>";
echo "</table>";


echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
echo"<tr>";
if($sistema_lingua=='ES'){
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Click acá para hacer </font><a href='download/dados-upload-lista-basica2-$login_fabrica-$data.zip'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download del archivo de lista basica en txt</font></a>.</td>";
}else{
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='download/dados-upload-lista-basica2-$login_fabrica-$data.zip'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo de lista basica em txt</font></a>.</td>";
}
echo "</tr>";
echo "</table>";
?>

<p>

<? include "rodape.php"; ?>
