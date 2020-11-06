<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "RELATÓRIO DE QUEBRA: PEÇAS NOS ÚLTIMOS 12 MESES";

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
</style>

<?


include "cabecalho.php";

$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")-12  , date("d"), date("Y"));
$data_inicial = strftime ("%Y-%m-%d", $data_serv);

$xdata_inicial = $data_inicial .' 00:00:00';
$xdata_final = date("Y-m-d 23:59:59");

$produto = trim($_GET['produto']);
$peca = trim($_GET["peca"]);

//--==== Otimização para rodar o relatório Anual =============================================
echo "<TABLE  border='0' cellspacing='0' cellpadding='2' align='center' name='relatorio' id='relatorio'  style=' border:#485989 1px solid; background-color: #e6eef7 '>";
echo "<tr>";
echo "<td colspan='12' class='Titulo'>PROCESSANDO OS SEGUINTES MESES</td>";
echo "</tr>";
echo "<tr>";
for($x=0;$x<12;$x++){
	$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")-12 +$x  , date("d"), date("Y"));
	$xMES = strftime ("%m/%Y", $data_serv);
	echo "<td width='40' height='40' class='Conteudo' bgcolor='#FFFFDD' id='mes_$x'>$xMES</td>";
}
echo "</tr></table>";
flush();
for($x=0;$x<12;$x++){
	$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")-12 +$x  , date("d"), date("Y"));
	$data_inicial = strftime ("%Y-%m-01", $data_serv);
	$xdata_inicial = $data_inicial .' 00:00:00';

	$sql = "SELECT ('$data_inicial'::DATE + INTERVAL'1 MONTH'- INTERVAL'1 day')::DATE || ' 23:59:59';";
	$res = pg_exec($con,$sql);
	$xdata_final = pg_result($res,0,0);


	$aux = $login_admin;
	if($x==0){
	$sql = "SELECT tbl_os_extra.os ,tbl_extrato.data_geracao
		INTO TEMP tmp_rqpd_$aux
		FROM tbl_os_extra
		JOIN tbl_extrato USING (extrato)
		JOIN tbl_os      USING (os)
		WHERE tbl_extrato.fabrica = $login_fabrica
		AND   tbl_os.produto      = $produto
		AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
		AND   tbl_extrato.liberado IS NOT NULL;
	
		CREATE INDEX tmp_rqpd_OS_$aux ON tmp_rqpd_$aux(os);";
	}else{
		$sql = "INSERT INTO tmp_rqpd_$aux (os,data_geracao) 
			SELECT tbl_os_extra.os ,tbl_extrato.data_geracao
			FROM tbl_os_extra
			JOIN tbl_extrato USING (extrato)
			JOIN tbl_os      USING (os)
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND   tbl_os.produto      = $produto
			AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
			AND   tbl_extrato.liberado IS NOT NULL;";
	}
	$res = pg_exec($con,$sql);
	echo "<script language='javascript'>document.getElementById('mes_$x').style.background = '#D7FFE1'</script>";
	flush();
}
//--========================================================================================


$sql = "
	SELECT	
		tbl_os.sua_os,
		tbl_os.os,
		tbl_peca.referencia,
		tbl_peca.descricao,
		to_char(fcr.data_geracao,'YYYY-MM')As data_geracao, 
		tbl_servico_realizado.descricao as desc_serv_real,
		tbl_defeito.descricao as desc_defeito

	FROM tbl_os
	JOIN tmp_rqpd_$aux         fcr ON tbl_os.os                     = fcr.os
	JOIN tbl_os_produto	       ON tbl_os.os                     = tbl_os_produto.os
	JOIN tbl_os_item	       ON tbl_os_produto.os_produto     = tbl_os_item.os_produto
	JOIN tbl_servico_realizado     ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
	JOIN tbl_defeito	       ON tbl_os_item.defeito           = tbl_defeito.defeito
	JOIN tbl_posto		       ON tbl_os.posto                  = tbl_posto.posto
	JOIN tbl_peca		      ON tbl_peca.peca                  = tbl_os_item.peca
	WHERE tbl_os.excluida                     IS NOT TRUE
	AND   tbl_servico_realizado.troca_de_peca IS     TRUE
	AND   tbl_os.produto   = $produto
	AND   tbl_peca.fabrica = $login_fabrica
	AND   tbl_peca.peca    = $peca
	ORDER BY data_geracao, tbl_peca.peca,tbl_peca.descricao";


$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	
	$sql2= "SELECT  tbl_linha.nome                             ,
					tbl_familia.descricao AS familia_descricao   ,
					tbl_produto.descricao AS produto_descricao 
			FROM  tbl_produto
			JOIN  tbl_familia ON tbl_familia.familia = tbl_produto.familia
			JOIN  tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
			WHERE tbl_produto.produto = $produto";

	$res2 = pg_exec($con,$sql2);
	$nome                      = trim(pg_result($res2,0,nome));
	$familia_descricao         = trim(pg_result($res2,0,familia_descricao));
	$produto_descricao         = trim(pg_result($res2,0,produto_descricao));

	echo "<table border='2' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' width='700' align='center'>\n";
	echo "<tr class='Titulo' >\n";
	echo "<td colspan='6' align='left'>» $nome » $familia_descricao » $produto_descricao</td>\n";
	echo "</tr>\n";
	echo "<tr class='Titulo'>\n";
	echo "<td>Sua OS</td>\n";
	echo "<td>Peça</td>\n";
	echo "<td>Nome</td>\n";
	echo "<td>Data de Geração</td>\n";
	echo "<td>Defeito</td>";
	echo "<td>Serviço Realizado</td>";
	echo "</tr>";

	for ($i=0; $i<pg_numrows($res); $i++){
		$nome			= trim(pg_result($res,$i,descricao));
		$peca           = trim(pg_result($res,$i,referencia));
		$os				= trim(pg_result($res,$i,os));
		$sua_os			= trim(pg_result($res,$i,sua_os));
		$data_geracao	= trim(pg_result($res,$i,data_geracao));
		$desc_serv_real	= trim(pg_result($res,$i,desc_serv_real));
		$desc_defeito	= trim(pg_result($res,$i,desc_defeito));
		$xdata_geracao	= explode('-',$data_geracao);
		$data_geracao	= $xdata_geracao[1].'/'.$xdata_geracao[0];

		if($cor=="#F1F4FA")$cor = '#F7F5F0';
		else               $cor = '#F1F4FA';

		echo "<tr bgcolor = '$cor' class ='table_line'>";
		echo "<td><a href='os_press.php?os=$os' target='blank' class='Mes'>$sua_os</a></td>";
		echo "<td >$peca</td>";
		echo "<td >$nome</td>";
		echo "<td >$data_geracao</td>";
		echo "<td >$desc_defeito</td>";
		echo "<td >$desc_serv_real</td>";
		echo "</tr>";

	}

	
	echo "</table>";
/*	for($i=0; $i<$peca_total ; $i++){
		for($j=0; $j<13 ; $j++)echo $qtde_mes[$i][$j]." - ";
	echo "<br><br>";
	}
*/
}
include 'rodape.php';
?>