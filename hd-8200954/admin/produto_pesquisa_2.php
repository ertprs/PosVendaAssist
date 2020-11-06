<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'funcoes.php';
include 'cabecalho_pop_produtos.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title><?= traduz("Pesquisa Produto..."); ?></title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css">
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css">
	<link rel="stylesheet" type="text/css" href="js/thickbox.css" media="screen">
	<script type="text/javascript" src="js/jquery-1.6.2.js"></script>
	<script src="js/thickbox.js"		type="text/javascript"></script>

<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

table.tabela tr td {
	font-family: verdana;
	font: bold 11px "Arial";
	border-collapse: collapse;
	border:1px solid #596d9b;
}


</style>
</head>
<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
<br>

<?php
//se for Lenoxx, faz uma condição para ser passado no join com a tbl_linha para filtrar produtos
if(($login_fabrica == 11) && (!empty($_GET["linha"]))){
	$linha = $_GET["linha"];
	$sqlLinha = " and tbl_produto.linha = $linha and
					  tbl_linha.ativo ";
}

if ($login_fabrica == 42) {
	$tipo_atendimento = $_GET["tipo_atendimento"];
	if(!empty($tipo_atendimento)) {
		$sql = "SELECT entrega_tecnica FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
		$res = pg_query($con, $sql);

		$entrega_tecnica = pg_result($res, 0, "entrega_tecnica");
	}
}

if ($login_fabrica == 42) {
		if ($entrega_tecnica == "t") {
			$sql_entrega_tecnica = " AND tbl_produto.entrega_tecnica IS TRUE ";
		} else if ($entrega_tecnica == "f") {
			$sql_entrega_tecnica = " AND tbl_produto.entrega_tecnica IS FALSE ";
		}
}

if($login_fabrica == 1){
	$programa_troca = $_GET['exibe'];
	if(preg_match("os_cadastro_troca_black.php", $programa_troca)){
		$troca_valor = 't';
	}
	$mostra_inativo = (preg_match("lbm_cadastro.php",$programa_troca)) ? "t" : "f";

    if ($mostra_inativo != 't') {
        $posto_codigo = filter_input(INPUT_GET,'posto');

        if (!empty($posto_codigo)) {
            $sql_posto = "
                SELECT  tbl_tipo_posto.posto_interno
                FROM    tbl_tipo_posto
                JOIN    tbl_posto_fabrica   ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
                JOIN    tbl_posto           ON tbl_posto.posto              = tbl_posto_fabrica.posto
                AND     tbl_posto_fabrica.fabrica       = $login_fabrica
                AND     tbl_posto_fabrica.codigo_posto  = '$posto_codigo'";
//                 exit(nl2br($sql_posto));
            $res_posto = pg_query($con, $sql_posto);
            $posto_interno = pg_fetch_result($res_posto,0,posto_interno);
        }
    }
}

$mapa_linha = trim(strtolower($_GET['mapa_linha']));
$tipo       = trim(strtolower($_GET['tipo']));
$familia    = trim(strtolower($_GET['familia']));
$limpa    = trim(strtolower($_GET['limpa']));

//hd 285292 adicionei este bloco para pegar a marca do admin
if ($login_fabrica == 30) {

	$sql_om = "SELECT substr(tbl_marca.nome,0,6) as marca from tbl_admin join tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_admin.cliente_admin join tbl_marca ON tbl_marca.marca = tbl_cliente_admin.marca where tbl_admin.admin = $login_admin";

	$res_om = pg_exec($con,$sql_om);

	if (pg_num_rows($res_om)>0) {
		$marca = pg_result($res_om,0,0);
	}

	if ($marca=='AMBEV') {
		$sql = "select marca from tbl_produto
				JOIN tbl_marca using(marca)
				WHERE  substr(nome,1,5) = '$marca'";

		$res = pg_exec($con,$sql);

		$array_marca = array();

		for ($i=0;$i<pg_num_rows($res);$i++) {
			$array_marca[$i] .= pg_result($res,$i,0);
		}

		$marcas = implode(',',$array_marca);
	}
}

//hd 285292 troque *from pelos campos
if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["campo"]));
	//echo "<h4>Pesquisando por <b>descrição do produto</b>: $descricao</h4>";
	//echo "<p>";
	$sql = "SELECT
					produto,
					tbl_produto.linha,";
		if($login_fabrica == 96)
		   	$sql .="referencia_fabrica as referencia, ";
		else
			$sql .="referencia, ";

		   $sql .= "
					descricao,
					garantia,
					mao_de_obra,
					tbl_produto.ativo,
					off_line,
					tbl_linha.fabrica as fabrica_i,
					valor_troca,
					ipi,
					capacidade,
					voltagem
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha ";
	if($login_fabrica == 11 && (!empty($_GET["linha"]) ) ){
		$sql .= $sqlLinha;
	}
	if($login_fabrica == 30 and strlen($login_cliente_admin)>0 ){
		$sql .= " JOIN     tbl_cliente_admin ON tbl_cliente_admin.marca = tbl_produto.marca and tbl_cliente_admin.cliente_admin = (select cliente_admin from tbl_admin where admin = $login_admin) ";
	}

	$condFab = (!in_array($login_fabrica, [11,172])) ? "AND tbl_linha.fabrica = $login_fabrica" : "AND tbl_linha.fabrica IN (11,172)";

	$sql .= " WHERE    (	tbl_produto.descricao ilike '%$descricao%' OR
						tbl_produto.nome_comercial ilike '%$descricao%'
						)
			$condFab
			$sql_entrega_tecnica";
	//comentado chamado 230 19-06			AND      tbl_produto.ativo";
	if (($login_fabrica == 1 AND $mostra_inativo <>"t") or $login_fabrica==7 or $login_fabrica ==59 or $login_fabrica == 52) {
        if ((!empty($posto_interno)) && ($login_fabrica == 1 && $posto_interno == 't')) {
            $sql .=  " AND tbl_produto.uso_interno_ativo ";
        } else {
            $sql .=  " AND      tbl_produto.ativo "; //hd 14501 22/2/2008 - HD 35014
        }
	}
	if (!in_array($login_fabrica, [11,14,59,172])) {
		$sql .= " AND      tbl_produto.produto_principal ";
	}
	//comentado chamado 230 honorato	if ($login_fabrica == 14) $sql .= " AND tbl_produto.abre_os IS TRUE ";

	if (strlen($familia) and is_numeric($familia)) {
		$sql .= " AND tbl_produto.familia = $familia";
	}

	//hd 285292 adicinei este union
	if ($login_fabrica == 30 and $marca == 'AMBEV') {
		$sql .= " UNION
					SELECT
					produto,
					tbl_produto.linha,
					referencia,
					descricao,
					garantia,
					mao_de_obra,
					tbl_produto.ativo,
					off_line,
					tbl_linha.fabrica as fabrica_i,
					valor_troca,
					ipi,
					capacidade,
					voltagem
					FROM tbl_produto where tbl_produto.marca in ($marcas) and (tbl_produto.descricao ilike '%$descricao%' or tbl_produto.nome_comercial ilike '%$descricao%') ";
	}


	$sql .= " ORDER BY 4 ";

// 	exit(nl2br($sql));
	$res = pg_exec ($con,$sql);
	if (@pg_numrows ($res) == 0) {
		echo "<h1>".traduz("Produto $descricao não encontrado.")."</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if ($tipo == "referencia") {
	if($login_fabrica != 96){
		$referencia = trim(strtoupper($_GET["campo"]));
		$referencia = str_replace(".","",$referencia);
		$referencia = str_replace(",","",$referencia);
		$referencia = str_replace("'","",$referencia);
		$referencia = str_replace("''","",$referencia);
		$referencia = str_replace("-","",$referencia);
		$referencia = str_replace("/","",$referencia);
		if($login_fabrica == 85){
			$referencia = str_replace(" ","",$referencia);
		}
	}else
		$referencia = trim($_GET["campo"]);

	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência do produto</b>: <i>$referencia</i></font>";
	//echo "<p>";
//hd 285292 troque *from pelos campos
	$sql = "SELECT
					produto,
					tbl_produto.linha,";
		if($login_fabrica == 96)
		   	$sql .="referencia_fabrica as referencia, ";
		else
			$sql .="referencia, ";

		   $sql .= "
					descricao,
					garantia,
					mao_de_obra,
					tbl_produto.ativo,
					off_line,
					tbl_linha.fabrica as fabrica_i,
					valor_troca,
					ipi,
					capacidade,
					voltagem
			FROM     tbl_produto
 			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha ";
 	if($login_fabrica == 11){
		$sql .= $sqlLinha;
	}
	if($login_fabrica == 30 and strlen($login_cliente_admin)>0 ){
		$sql .= " JOIN     tbl_cliente_admin ON tbl_cliente_admin.marca = tbl_produto.marca and tbl_cliente_admin.cliente_admin = (select cliente_admin from tbl_admin where admin = $login_admin) ";
	}

	$condFab = (!in_array($login_fabrica, [11,172])) ? "AND tbl_linha.fabrica = $login_fabrica" : "AND tbl_linha.fabrica IN (11,172)";

	if($login_fabrica != 96){
		if($login_fabrica == 129) $referencia = str_replace(" ","",$referencia);
		$sql .= " WHERE    tbl_produto.referencia_pesquisa ILIKE '%$referencia%'
				$condFab
				$sql_entrega_tecnica";
	}else{
			$sql .= " WHERE (tbl_produto.referencia LIKE '%$referencia%' OR UPPER(tbl_produto.referencia_fabrica) LIKE UPPER('%$referencia%'))
				AND      tbl_linha.fabrica = $login_fabrica";
	}

	if (($login_fabrica == 1 AND $mostra_inativo <>"t") or $login_fabrica==7 or $login_fabrica == 59 or $login_fabrica == 52 or $login_fabrica == 15) {
        if ((!empty($posto_interno)) && ($login_fabrica == 1 && $posto_interno == 't')) {
            $sql .=  " AND tbl_produto.uso_interno_ativo ";
        } else {
            $sql .=  " AND      tbl_produto.ativo "; //hd 14501 22/2/2008 - HD 35014
        }
	}
	if (!in_array($login_fabrica, [11,14,59,172])) {
		$sql .= " AND      tbl_produto.produto_principal  is true";
	}

	if (strlen($familia) and is_numeric($familia)) {
		$sql .= " AND tbl_produto.familia = $familia";
	}

	//hd 285292 adicionei o union
	if ($login_fabrica == 30 and $marca == 'AMBEV') {
		$sql .= " UNION
					SELECT
					produto,
					tbl_produto.linha,
					referencia,
					descricao,
					garantia,
					mao_de_obra,
					tbl_produto.ativo,
					off_line,
					fabrica_i,
					valor_troca,
					ipi,
					capacidade,
					voltagem
					FROM tbl_produto where tbl_produto.marca in ($marcas)  and tbl_produto.referencia_pesquisa ilike '%$referencia%'";
	}

	$sql .= " ORDER BY";
	if ($login_fabrica == 45) {
		$sql .= " 3, ";
	}

	$sql .= " 4 ";
// exit(nl2br($sql));
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>".traduz("Produto $referencia não encontrado")."</h1>";
		echo "<script language='javascript'>";
		#echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}


#############################TUDO#################################
if ($tipo == "tudo") {
	$campo = trim(strtoupper($_GET["campo"]));
	$campo = str_replace(".","",$campo);
	$campo = str_replace(",","",$campo);
	$campo = str_replace("'","",$campo);
	$campo = str_replace("''","",$campo);
	$campo = str_replace("-","",$campo);
	$campo = str_replace("/","",$campo);

	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência do produto</b>: <i>$referencia</i></font>";
	//echo "<p>";
//hd 285292 troque *from pelos campos
	$sql = "SELECT
					produto,
					tbl_produto.linha,";
		if($login_fabrica == 96)
		   	$sql .="referencia_fabrica as referencia, ";
		else
			$sql .="referencia, ";

		   $sql .= "
					descricao,
					garantia,
					mao_de_obra,
					tbl_produto.ativo,
					off_line,
					tbl_linha.fabrica as fabrica_i,
					valor_troca,
					ipi,
					capacidade,
					voltagem
			FROM     tbl_produto
 			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha ";
	if($login_fabrica == 30 and strlen($login_cliente_admin)>0 ){
		$sql .= " JOIN     tbl_cliente_admin ON tbl_cliente_admin.marca = tbl_produto.marca and tbl_cliente_admin.cliente_admin = (select cliente_admin from tbl_admin where admin = $login_admin) ";
	}
	$sql .= " WHERE    ( tbl_produto.referencia_pesquisa ILIKE '%$campo%' OR tbl_produto.descricao ILIKE '%$campo%')
			AND      tbl_linha.fabrica = $login_fabrica
			$sql_abre_os
			$sql_entrega_tecnica";
	if (($login_fabrica == 1 AND $mostra_inativo <>"t") or $login_fabrica==7 or $login_fabrica == 59 or $login_fabrica == 52) {
		if ($login_fabrica == 1 && $posto_interno == 't') {
            $sql .=  " AND tbl_produto.uso_interno_ativo ";
        } else {
            $sql .=  " AND      tbl_produto.ativo "; //hd 14501 22/2/2008 - HD 35014
        }
	}
	if (!in_array($login_fabrica, [11,14,59,172])) {
		$sql .= " AND      tbl_produto.produto_principal  is true";
	}
	//hd 285292 adicionei o union
	if ($login_fabrica == 30 and $marca == 'AMBEV') {
		$sql .= " UNION
					SELECT
					produto,
					tbl_produto.linha,
					referencia,
					descricao,
					garantia,
					mao_de_obra,
					tbl_produto.ativo,
					off_line,
					fabrica_i,
					valor_troca,
					ipi,
					capacidade,
					voltagem
					FROM tbl_produto where tbl_produto.marca in ($marcas)  and (tbl_produto.referencia_pesquisa ilike '%$campo%' OR tbl_produto.descricao ILIKE '%$campo%')";
	}
	$sql .= " ORDER BY";
	if ($login_fabrica == 45) {
		$sql .= " 3, ";
	}

	$sql .= " 4 ";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>".traduz("Produto $campo não encontrado")."</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if (pg_numrows($res) == 1) {
	$valor_troca = trim(pg_result($res,0,valor_troca));
	$ipi         = trim(pg_result($res,0,ipi));
	$capacidade  = trim(pg_result($res,0,capacidade));
	$voltagem    = trim(pg_result($res,0,voltagem));
	$produto    = trim(pg_result($res,0,produto));
	$referencia = trim(pg_result($res,0,referencia));
	$fabrica_i  = trim(pg_result($res,0,fabrica_i));

	if($login_fabrica == 164){
		$fn_constatado = " window.opener.busca_defeito_constatado({$produto}, {$posicao}); ";
	}

	if (in_array($login_fabrica, [11,172])) {

		$arrDadosProduto = valida_produto_pacific_lennox($referencia);

		if (count($arrDadosProduto["fabrica"]) > 1) {

			$perguntar = "t";

		}

		if ($perguntar == "t") { ?>

			<script>

				var posicao = '<?= $posicao ?>';

				$("#botoes_sim_nao_"+posicao, window.opener.document).show();
				$("input[name=possui_codigo_interno_"+posicao+"]").prop("checked", false);

			</script>

		<?php
		} else { ?>

			<script>

				var posicao = '<?= $posicao ?>';

				$("#botoes_sim_nao_"+posicao, window.opener.document).hide();
				$("input[name=possui_codigo_interno_"+posicao+"]").prop("checked", false);

			</script>

		<?php
		}
	}


	if (strlen($ipi)>0 AND $ipi != "0"){
		$valor_troca = $valor_troca * (1 + ($ipi /100));
	}
	$tipo = $_GET[ 'tipo' ];
	echo "<script language='JavaScript'> $fn_constatado \n";
	if ($tipo == 'tudo') {
			echo "descricaoo.value  = '". $referencia .' - '.$descricao . "';";
			echo "produto.value ='" . $produto . "' ;";
	}else{

	echo (($login_fabrica == 59 && $_GET["voltagem"] == "t") || $login_fabrica == 1 || $_GET["voltagem"] == "t" and ($login_fabrica <> 52)) ? " if (typeof voltagem != 'undefined') { voltagem.value = '$voltagem'; } " : "";
	echo ($troca_valor=='t') ? "valor_troca.value='$valor_troca' ; " : "";
	echo ($_GET["proximo"] == "t") ? "proximo.focus();" : "";
	echo "referencia.value = '".trim(pg_result($res,0,referencia))."';";
	echo "descricao.value  = '". str_replace ('"','',trim(pg_result($res,0,descricao))) . "';";
	echo ($login_fabrica==7) ? " if (window.capacidade){ capacidade.value = '$capacidade';}; " : "";
	echo "this.close();";
	if ($login_fabrica == 1){
		echo " window.opener.verifica_produtos_troca('$referencia'); ";
	}
	echo "</script>\n";
}
}
echo "<script language='JavaScript'>\n";
echo "<!--\n";
echo "this.focus();\n";
echo "// -->\n";
echo "</script>\n";

$colspan = "4";

if (in_array($login_fabrica, [11,172])) {

	$colspan = "5";
	$fabricaColuna = "<td>Fabrica</td>";
	
}

echo "<table width='100%' border='0' class='tabela' cellspacing='1'>\n";
if($tipo=="descricao")
		echo "<tr class='titulo_tabela'><td colspan='{$colspan}'><font style='font-size:14px;'>".traduz('Pesquisando por <b>descrição do produto</b>').": $descricao</b>: $nome</font></td></tr>";
	if($tipo=="referencia")
		echo "<tr class='titulo_tabela'><td colspan='{$colspan}'><font style='font-size:14px;'>".traduz('Pesquisando por <b>referência do produto</b>').": $referencia</font></td></tr>";


echo "<tr class='titulo_coluna'>{$fabricaColuna}<td>".traduz('Código')."</td><td>".traduz('Nome')."</td><td>Voltagem</td><td>&nbsp;</td>";

for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
	$produto    = trim(pg_result($res,$i,produto));
	$linha      = trim(pg_result($res,$i,linha));
	$descricao  = trim(pg_result($res,$i,descricao));
	$voltagem   = trim(pg_result($res,$i,voltagem));
	$referencia = trim(pg_result($res,$i,referencia));
	$garantia   = trim(pg_result($res,$i,garantia));
	$mobra      = str_replace(".",",",trim(pg_result($res,$i,mao_de_obra)));
	$ativo      = trim(pg_result($res,$i,ativo));
	$off_line   = trim(pg_result($res,$i,off_line));
	$capacidade = trim(pg_result($res,$i,capacidade));
	$fabrica_i  = trim(pg_result($res,$i,fabrica_i));

	$descricao = str_replace ('"','',$descricao);
	$descricao = str_replace("'","",$descricao);
	$descricao = str_replace("''","",$descricao);

	$valor_troca = trim(pg_result($res,$i,valor_troca));
	$ipi         = trim(pg_result($res,$i,ipi));

	if (strlen($ipi)>0 AND $ipi != "0") {
		$valor_troca = $valor_troca * (1 + ($ipi /100));
	}

	$mativo = ($ativo == 't') ? "ATIVO" : "INATIVO";

	if($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
		echo "<tr bgcolor='$cor'>\n";

	if (in_array($login_fabrica, [11,172])) {

		$nomeFab = ($fabrica_i == 11) ? "AULIK" : "PACIFIC";

		echo "<td>{$nomeFab}</td>";

	}

	echo "<td>\n";

	echo "<a href=\"javascript: ";
	if (strlen($_GET['lbm']) > 0) {
		echo "produto.value = '$produto'; ";
	}
	if ($login_fabrica == 1) {
		echo "descricao.value = '$descricao $voltagem'; if (window.voltagem) { voltagem.value = '$voltagem' ; }";
		if ($_GET["voltagem"] == "t") echo "voltagem.value = '$voltagem'; ";
	}else{
		echo "descricao.value = '$descricao'; ";
	}
		echo "referencia.value = '" .$referencia ."'; ";
	if($mapa_linha =='t'){
		echo " if (typeof mapa_linha != 'undefined') { mapa_linha.value = $linha; } ";
	}
	if ($login_fabrica == 59 && $_GET["voltagem"] == "t") {
		echo " voltagem.value = '$voltagem'; ";
	}
	if ($_GET["proximo"] == "t") {
		echo "proximo.focus(); ";
	}
	if ($login_fabrica==7){
		echo " if (window.capacidade){ capacidade.value = '$capacidade';}; ";
	}

	if ($login_fabrica==1 AND $limpa == TRUE){
		echo " window.opener.limpa_troca(); ";
	}

	if ($login_fabrica == 1 && $limpa == TRUE){
		echo " window.opener.verifica_produtos_troca('$referencia'); ";
	}

	echo "referencia.focus;window.close(); \" >";
	echo "$referencia\n";
	echo "</a>\n";
	echo "</td>\n";

	echo "<td>\n";
	echo "<a href=\"javascript: ";
	if (strlen($_GET['lbm']) > 0) {
		echo "produto.value = '$produto'; ";
	}
	if ($login_fabrica == 1) {
		echo "descricao.value = '$descricao $voltagem'; if (window.voltagem) { voltagem.value = '$voltagem' ; }";
		if ($_GET["voltagem"] == "t") echo "voltagem.value = '$voltagem'; ";
	}else{
		echo "descricao.value = '$descricao'; ";
	}
	echo "referencia.value = '$referencia'; ";
	if($mapa_linha =='t'){
		echo " if (typeof mapa_linha != 'undefined') { mapa_linha.value = $linha; } ";
	}

	if ($troca_valor=='t') {
		echo "valor_troca.value='$valor_troca' ; ";
	}
	if (($login_fabrica == 59 && $_GET["voltagem"] == "t") || $login_fabrica == 1 || $_GET["voltagem"] == "t" AND strlen($voltagem) > 0 and $login_fabrica <> 52) {
		echo " if (typeof voltagem != 'undefined') { voltagem.value = '$voltagem'; } ";
	}

	if ($_GET["proximo"] == "t") {
		echo "proximo.focus(); ";
	}
	if ($login_fabrica==7){
		echo " if (window.capacidade){ capacidade.value = '$capacidade';}; ";
	}

	if( $tipo == 'tudo' )
	{
		echo "descricao.value = '".$referencia." - ".$descricao . "';";
		echo "produto.value ='".$produto . "';";
	}
	if ($login_fabrica==1 AND $limpa == TRUE){
		echo " window.opener.limpa_troca(); ";
	}

	if ($login_fabrica == 1){
		echo " window.opener.verifica_produtos_troca('$referencia'); ";
	}

	$posicao = (isset($_REQUEST["posicao"])) ? $_REQUEST["posicao"] : 0;

	if($login_fabrica == 164){
		$fn_constatado = "; window.opener.busca_defeito_constatado({$produto}, {$posicao})";
	}


	if (in_array($login_fabrica, [11,172])) {

		$arrDadosProduto = valida_produto_pacific_lennox($referencia);

		if (count($arrDadosProduto["fabrica"]) > 1) {

			$perguntar = "t";

		}

		if ($perguntar == "t") {

			$acaoBtn = "$('#botoes_sim_nao_".$posicao."', window.opener.document).show();";

		} else { 

			$acaoBtn = "$('#botoes_sim_nao_".$posicao."', window.opener.document).hide();";

		}

	}

	echo ";referencia.focus();descricao.focus() $fn_constatado;{$acaoBtn} window.close(); \" >";
	echo "$descricao\n";
	echo "</a>\n";
	echo "</td>\n";

	echo "<td>&nbsp;\n";
	echo "$voltagem\n";
	echo "</td>\n";

	echo "<td>\n";
	echo "$mativo\n";
	echo "</td>\n";
	if ($login_fabrica==3) {
		$imagem = "imagens_produtos/$login_fabrica/pequena/$produto.jpg";
		echo "<td title='$imagem' bgcolor='#FFFFFF' align='center'>\n";
		if (file_exists("/var/www/assist/www/$imagem")) {
			$tag_imagem = "<A href='../".str_replace("pequena", "media", $imagem)."' class='thickbox'>\n";
			$tag_imagem.= "<IMG src='../$imagem' valign='middle' style='border: 2px solid #FFCC00' class='thickbox' height='40'></A>\n";
			echo $tag_imagem;
		}
		echo "</td>\n";
	}
	echo "</tr>\n";
}
echo "</table>\n";
?>

</body>
</html>
