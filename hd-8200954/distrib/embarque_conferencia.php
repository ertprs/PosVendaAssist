<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$etiqueta = $_GET['etiqueta'];
if ($etiqueta == "S"){
	$embarque = $_GET['embarque'];

	$res = pg_exec ($con,"SELECT fn_etiqueta_libera ($embarque)");

#	echo $sql;
	echo "<html><head></head><body><script language='javascript'>window.close();</script></body></html>";
	exit;
}


$cancelar = $_GET['cancelar'];
if ($cancelar == 'S') {
	$posto    = $_GET['posto'];
	$embarque = $_GET['embarque'];
	
	$sql = "SELECT fn_cancela_embarque ($login_posto, $posto, $embarque)";
	$res = pg_exec ($con,$sql);

	header ("Location: embarque.php");
	exit;
}

?>

<html>
<head>
<title>Conferência do Embarque</title>
<style>
a:visited{
	color:#663366;
}
</style>
</head>

<body>

<? include 'menu.php' ?>

<?
$embarque = $_POST['embarque'];
if (strlen ($embarque) == 0) $embarque = $_GET['embarque'];
?>

<center><h1>Conferência do Embarque <? if (strlen ($embarque) > 0) echo "# $embarque" ?></h1></center>

<p>

<?
if (strlen ($embarque) == 0) {
	echo "<center>";
	echo "<form name='frm_embarque' action='$PHP_SELF' method='post'>";
	echo "Selecione o Embarque para Conferir";
	echo "<br>";

	echo "<SELECT SIZE='1' NAME='embarque' onchange='javascript: document.frm_embarque.submit()'>";
	echo "<option></option>";

	$sql = "SELECT tbl_embarque.embarque, tbl_posto.nome, tbl_embarque.posto
			FROM tbl_embarque 
			JOIN tbl_posto ON tbl_embarque.posto = tbl_posto.posto
			WHERE tbl_embarque.distribuidor = $login_posto
			AND   tbl_embarque.faturar IS NULL
			ORDER BY tbl_posto.nome";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$embarque = pg_result ($res,$i,embarque);
		$nome     = pg_result ($res,$i,nome);

		echo "<option value='$embarque' ";
		if ($embarque == $_POST['embarque']) echo " selected ";
		echo " >$nome</option>";
	}

	echo "</select>";
	echo "</form>";
	echo "</center>";

}



$embarque = $_POST['embarque'];
if (strlen ($embarque) == 0) $embarque = $_GET['embarque'];

if (strlen ($embarque) > 0) {
	$sql = "SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.posto, tbl_posto.nome, tbl_posto.endereco, tbl_posto.numero, tbl_posto.complemento, tbl_posto.bairro, tbl_posto.cep , tbl_posto.cidade, tbl_posto.estado FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica IN (".implode(",", $fabricas).") JOIN tbl_embarque ON tbl_embarque.posto = tbl_posto.posto WHERE tbl_embarque.embarque = $embarque AND tbl_embarque.distribuidor = $login_posto";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 0) {
		$sql = "SELECT '' AS codigo_posto, tbl_posto.posto, tbl_posto.nome, tbl_posto.endereco, tbl_posto.numero, tbl_posto.complemento, tbl_posto.bairro, tbl_posto.cep , tbl_posto.cidade, tbl_posto.estado FROM tbl_posto JOIN tbl_embarque ON tbl_embarque.posto = tbl_posto.posto WHERE tbl_embarque.embarque = $embarque AND tbl_embarque.distribuidor = $login_posto";
		$res = pg_exec ($con,$sql);
	}

	$posto    = pg_result ($res,0,posto);
	$nome     = pg_result ($res,0,nome);
	$endereco = pg_result ($res,0,endereco);
	$numero   = pg_result ($res,0,numero);
	$complemento = pg_result ($res,0,complemento);
	$bairro   = pg_result ($res,0,bairro);
	$cep      = pg_result ($res,0,cep);
	$cidade   = pg_result ($res,0,cidade);
	$estado   = pg_result ($res,0,estado);
	$codigo   = pg_result ($res,0,codigo_posto);

	
	if (strlen (trim ($cep)) > 0) $cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5);

	echo "<table width='90%' align='center' border='0'>";
	echo "<tr>";

	echo "<td align='left'>";
	echo "<a href='$PHP_SELF'>Outro Embarque</a>";
	echo "</td>";

	echo "<td align='right'>";
	echo "<a href='$PHP_SELF?embarque=$embarque&etiqueta=S' target='_blank'>Etiquetas</a>";
	echo "</td>";

	echo "</tr>";
	echo "</table>";

	echo "<p>";

	if (strlen ($numero) > 0) $numero = " - nº $numero - ";
	echo "<center>";
	echo "<font size='+2'>$codigo - $nome </font>";
	echo "<br>";
	echo "<font size='+1'>$endereco  $numero  $complemento $bairro <br> $cep - $cidade - $estado </font>";
	echo "</center>";

	echo "<p>";

	$sql = "SELECT  tbl_peca.peca       ,
					tbl_peca.referencia ,
					tbl_peca.descricao  ,
					tbl_peca.ipi        ,
					(SELECT preco FROM tbl_tabela_item WHERE peca = tbl_peca.peca AND tabela IN (15, 116) LIMIT 1) AS preco ,
					SUM (tbl_embarque_item.qtde) AS qtde ,
					tbl_posto_estoque_localizacao.localizacao ,
					CASE WHEN tbl_embarque_item.os_item IS NULL THEN 'FAT' ELSE 'GAR' END AS fat_gar
			FROM    tbl_embarque
			JOIN    tbl_embarque_item USING (embarque)
			JOIN    tbl_peca          USING (peca)
			LEFT JOIN tbl_posto_estoque_localizacao ON tbl_embarque_item.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
			WHERE   tbl_embarque.embarque = $embarque
			AND     tbl_embarque.distribuidor = $login_posto
			GROUP BY fat_gar, tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, preco, tbl_posto_estoque_localizacao.localizacao
			ORDER BY fat_gar, tbl_peca.referencia , tbl_posto_estoque_localizacao.localizacao ";

	$res = pg_exec ($con,$sql);

	echo "<table width='600' align='center' border='1' cellpadding='2' cellspacing='0'>";

	echo "<tr bgcolor='##6666CC' style='color:#ffffff ; font-weight:bold' align='center'>";
#	echo "<td>Localização</td>";
	echo "<td>Fat-Gar</td>";
	echo "<td>Peça</td>";
	echo "<td>Descrição</td>";
	echo "<td>Qtde</td>";
	echo "<td>Preço</td>";
	echo "</tr>";

	$qtde_total = 0;

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$peca                       = trim(pg_result($res,$i,peca)) ;
		$referencia                 = trim(pg_result($res,$i,referencia)) ;
		$descricao                  = trim(pg_result($res,$i,descricao)) ;
		$qtde                       = trim(pg_result($res,$i,qtde)) ;
		$localizacao                = trim(pg_result($res,$i,localizacao)) ;
		$fat_gar                    = trim(pg_result($res,$i,fat_gar)) ;
		$preco                      = trim(pg_result($res,$i,preco)) ;
		$ipi                        = trim(pg_result($res,$i,ipi)) ;
		
		$preco = $preco * (1 + ($ipi / 100));
		if ($fat_gar == "GAR") $preco = $preco / 3;
		$preco = ROUND ($preco,2);

		echo "<tr style='font-size: 12px' >\n";

		if (strlen (trim ($localizacao)) == 0) $localizacao = "&nbsp";

#		echo "<td align='left' nowrap>$localizacao</td>\n";
		echo "<td align='center' nowrap>$fat_gar</td>\n";
		echo "<td align='center' nowrap>$referencia</td>\n";
		echo "<td align='left' nowrap>$descricao</td>\n";
		echo "<td align='center' nowrap>$qtde</td>\n";
		echo "<td align='center' nowrap>" . number_format ($preco,2,",",".") . "</td>\n";

		echo "</tr>\n";

		$qtde_total += $qtde ;

		$preco_total = $preco_total + ($preco * $qtde);
	}

	echo "<tr bgcolor='##6666CC' style='color:#ffffff ; font-weight:bold' align='center'>";
	echo "<td>Qtde Total</td>";
	echo "<td>&nbsp;</td>";
	echo "<td>&nbsp;</td>";
	echo "<td>$qtde_total</td>";
	echo "<td>$preco_total</td>";
	echo "</tr>";


	echo "</table>\n";

	/*
	echo "<hr>";

	echo "<form method='post' action='embarque_nota_fiscal.php' name='frm_nota_fiscal'>";
	echo "<input type='hidden' name='embarque' value='$embarque'>";
	echo "<input type='hidden' name='posto'    value='$posto'>";
	echo "Qtde Volumes <input type='text' size='10' name='qtde_volume'><br>";
	echo "Valor Frete <input type='text' size='10' name='valor_frete'><br>";
	echo "Transportador ";
	echo "<select name='transportadora' size='1'>";
	echo "<option value='1055' SELECTED>VARIG-LOG</option>";
	echo "<option value='1056'>SEDEX</option>";
	echo "<option value='1057'>PROPRIO</option>";
	echo "</select>";
	echo "<p>";
	echo "<input type='submit' name='btn_nf' value='Emitir NF'>";
	echo "</font>";
	echo "<p>";

	*/

}

?>



<p>

<? #include "rodape.php"; ?>

</body>
</html>
