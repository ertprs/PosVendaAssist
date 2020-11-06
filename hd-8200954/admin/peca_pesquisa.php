<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

include 'cabecalho_pop_pecas.php';

$multipeca = $_GET['multipeca'];

if (isset($multipeca) && strlen($multipeca) > 0) {
    $multipeca = true;
    $posicao   = $_GET["posicao"];
} else {
    $multipeca = false;
}

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Peças... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />
<style>
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

.msg_erro{
background-color:#FF0000;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
}

.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{
background-color: #7092BE;
font:bold 11px Arial;
color: #FFFFFF;
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
}
</style>
<script>
<?php if ($login_fabrica == 1 && $multipeca == true) { ?>
	function Retorna (descricao,referencia) {
		var posicao   = '<?=$posicao;?>';
        opener.parent.retorna_peca(referencia, descricao, posicao);
        window.close();
        return false;
	}
<?php } ?>
// var descricao = window.opener.descricao;
// var referencia = window.opener.referencia_pesquisa_peca;
</script>
</head>

<body style="margin: 0px 0px 0px 0px;" >

<br>

<?
if($login_fabrica == 1){
	$status = $_GET["status"];

	if($status == "indispl"){
		$cond_status_peca = " AND tbl_peca.informacoes = upper('$status') ";
	}
}

$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "descricao") {

	$descricao = utf8_decode(trim (strtoupper($_GET["campo"])));
	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>descrição da peça</b>: <i>$descricao</i></font>";
	//echo "<p>";
	
	$sql = "SELECT  tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.ipi,
					tbl_peca.origem,
					tbl_peca.estoque,
					tbl_peca.unidade,
					tbl_peca.ativo
			FROM     tbl_peca
			JOIN     tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
			WHERE    tbl_peca.descricao ilike '%$descricao%'
			AND      tbl_peca.fabrica = $login_fabrica
			$cond_status_peca
			ORDER BY tbl_peca.descricao;";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Peça '$descricao' não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if ($tipo == "referencia") {

	$referencia = trim (strtoupper($_GET["campo"]));
	$referencia = str_replace (".","",$referencia);
	$referencia = str_replace ("-","",$referencia);
	$referencia = str_replace ("/","",$referencia);
	$referencia = str_replace (" ","",$referencia);

	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência da peça</b>: <i>$referencia</i></font>";
	//echo "<p>";

	//where tbl_peca.referencia_pesquisa ilike '%$referencia%'
	$sql = "SELECT  tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.ipi,
					tbl_peca.origem,
					tbl_peca.estoque,
					tbl_peca.unidade,
					tbl_peca.ativo
			FROM     tbl_peca
			JOIN     tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
			WHERE    tbl_peca.referencia_pesquisa ilike '%$referencia%'
			AND      tbl_peca.fabrica = $login_fabrica
			$cond_status_peca
			ORDER BY tbl_peca.descricao;";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Peça '$referencia' não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}
echo "<script language='JavaScript'>\n";
echo "<!--\n";
echo "this.focus();\n";
echo "// -->\n";
echo "</script>\n";

echo "<table width='100%' border='0' class='tabela'>\n";
//if ($ip == '192.168.0.66') echo $sql."<BR>";
	if($tipo == "descricao"){
		echo "<tr class='titulo_tabela'>";
		echo "<td colspan='2'>";
		echo "Pesquisando por <b>descrição da peça</b>: $descricao";
		echo "</tr>";
		echo "</tr>";
	}
	elseif($tipo=="referencia"){
		echo "<tr class='titulo_tabela'>";
		echo "<td colspan='2'>";
		echo "Pesquisando por <b>referência da peça</b>: $referencia";
		echo "</tr>";
		echo "</tr>";
	}
	echo "<tr class='titulo_coluna'>
			<td>Código</td>
			<td>Descrição</td>
		</tr>";
for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
	$peca       = trim(pg_result($res,$i,peca));
	$referencia = trim(pg_result($res,$i,referencia));
	$descricao  = trim(pg_result($res,$i,descricao));
	//$ipi        = trim(pg_result($res,$i,ipi));
	//$origem     = trim(pg_result($res,$i,origem));
	//$estoque    = trim(pg_result($res,$i,estoque));
	//$unidade    = trim(pg_result($res,$i,unidade));
	
	$cor = ( $i%2 ) ? '#F7F5F0' : '#F1F4FA';
	
if ($login_fabrica == 20) {
	$ativo      = trim(pg_result($res,$i,ativo));
	
	if ($ativo == 't') {
		$ativo = 'Ativo';
	}
	else {
		$ativo = 'Não Ativo';
	}

}
	$descricao = str_replace ('"','',$descricao);
	//$referencia = substr ($referencia,0,2) . "." . substr ($referencia,2,2) . "." . substr ($referencia,4,2) . "-" . substr ($referencia,6,1);

	echo "<tr bgcolor='$cor'>\n";
	
	echo "<td>\n";
	if ($login_fabrica == 1 && $multipeca == true) {
		echo "<a href=\"javascript: Retorna('$descricao','$referencia'); \" >";
	} else {
		if ($_GET['forma'] == 'reload') {
			echo "<a href=\"javascript: opener.document.location = retorno + '?peca=$peca' ; this.close() ;\" > " ;
		}else{
			echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; this.close() ; \" >";
		}
	}
	echo "$referencia\n";
	echo "</td>\n";
	
	echo "<td>\n";
	if ($login_fabrica == 1 && $multipeca == true) {
		echo "<a href=\"javascript: Retorna('$descricao','$referencia'); \" >";
	} else {
		if ($_GET['forma'] == 'reload') {
			echo "<a href=\"javascript: opener.document.location = retorno + '?peca=$peca' ; this.close() ;\" > " ;
		}else{
			echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; this.close() ; \" >";
		}
	}
	
	echo "$descricao\n";
	echo "</a>\n";
	echo "</td>\n";

/*  Código comentado porquê a parte acima que traz os dados do banco também estava, então montando as colunas sem dado algum. hd268395
	echo "<td>\n";
	echo "$ipi\n";
	echo "</td>\n";
	
	echo "<td>\n";
	echo "$origem\n";
	echo "</td>\n";

	echo "<td>\n";
	echo "$estoque\n";
	echo "</td>\n";

	echo "<td>\n";
	echo "$unidade\n";
	echo "</td>\n";

	echo "<td>\n";
	echo "$ativo\n";
	echo "</td>\n";
	
	echo "</tr>\n";
*/
}
echo "</table>\n";

?>

</body>
</html>
