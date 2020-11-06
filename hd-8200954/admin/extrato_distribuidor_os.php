<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";
include "autentica_admin.php";

include "cabecalho.php";
$msg_erro = "";

$layout_menu = "financeiro";
$title = "Consulta e Manutenção de Extratos do Posto por OS";

?>

<p>
<center>
<?
$extrato = trim($_GET['extrato']);
$posto   = trim($_GET['posto']);

$sql = "SELECT  to_char (data_geracao,'DD/MM/YYYY') AS data ,
				to_char (data_geracao,'YYYY-MM-DD') AS periodo ,
				tbl_posto.nome ,
				tbl_posto_fabrica.codigo_posto
		FROM tbl_extrato
		JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_extrato.extrato = $extrato AND tbl_extrato.posto = $posto";
$res = pg_exec ($con,$sql);
$data = pg_result ($res,0,data);
$periodo = pg_result ($res,0,periodo);
$nome = pg_result ($res,0,nome);
$codigo = pg_result ($res,0,codigo_posto);

echo "<font size='+1' face='arial'>Data do Extrato $data </font>";
echo "<br>";
echo "<font size='+0' face='arial'>$codigo - $nome</font>";


?>



<p>
<table width='600' align='center' border='0' style='font-size:12px'>
<tr>
<td align='center' width='25%'><a href='extrato_distribuidor.php?periodo=<? echo $periodo ?>&posto=<?=$posto?>'>Extrato total</a></td>
<td align='center' width='25%'><a href='extrato_distribuidor_posto.php?data=<? echo $periodo ?>'>Extrato dos Postos</a></td>
<td align='center' width='25%'><a href='extrato_distribuidor_retornaveis.php?extrato=<? echo $extrato ?>&posto=<?=$posto?>&data=<? echo $periodo ?>'>Peças <br> Retornáveis</a></td>
<td align='center' width='25%'><a href='extrato_distribuidor.php'>Ver outro extrato</a></td>
</tr>
</table>

<?

$sql = "SELECT  tbl_os.os, 
				tbl_os.sua_os,
				tbl_os.produto,
				tbl_os.consumidor_nome ,
				tbl_produto.referencia AS produto_referencia,
				tbl_produto.descricao  AS produto_descricao ,
				tbl_os.serie,
				tbl_os_extra.mao_de_obra ,
				tbl_os_extra.linha ,
				tbl_os_extra.distribuidor ,
				tbl_linha.nome AS linha_nome ,
				to_char (tbl_os.data_abertura,'DD/MM/YYYY') AS abertura ,
				to_char (tbl_os.data_digitacao,'DD/MM/YYYY') AS digitacao ,
				to_char (tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento
		FROM    tbl_os 
		JOIN    tbl_os_extra ON tbl_os.os = tbl_os_extra.os
		JOIN    tbl_produto  ON tbl_os.produto = tbl_produto.produto
		JOIN    tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
		WHERE   tbl_os.posto = $posto
		AND     tbl_os_extra.extrato = $extrato
		ORDER BY tbl_os_extra.distribuidor , tbl_os_extra.linha, tbl_os_extra.mao_de_obra, tbl_os.sua_os";
$res = pg_exec ($con,$sql);

echo "<table width='600' aling='center' border='0' cellspacing='2'>";

echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
echo "<td align='center' nowrap >OS</td>";
echo "<td align='center' nowrap >Série</td>";
echo "<td align='center' nowrap >Digitação</td>";
echo "<td align='center' nowrap >Abertura</td>";
echo "<td align='center' nowrap >Fechamento</td>";
echo "<td align='center' nowrap >Consumidor</td>";
echo "<td align='center' nowrap >Mão-de-Obra</td>";
echo "<td align='center' nowrap >Produto</td>";
echo "</tr>";

$total = 0 ;
$distribuidor = @pg_result ($res,0,distribuidor) ;
$qtde = 0 ;

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

	$cor = "#FFFFFF";
	if ($i % 2 == 0) $cor = "#FFEECC";

	if (strlen (pg_result ($res,$i,distribuidor)) == 0 and $distribuidor <> pg_result ($res,$i,distribuidor) ) {
		echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
		echo "<td align='center' nowrap colspan='6'>Total via Distribuidor - $qtde OS</td>";
		echo "<td align='right' nowrap >" . number_format ($total,2,",",".") . "</td>";
		echo "<td>&nbsp;</td>";
		echo "</tr>";
		$distribuidor = @pg_result ($res,$i,distribuidor) ;
		$total = 0;
		$qtde  = 0;
	}

	echo "<tr bgcolor='$cor' style='font-size: 10px'>";
	echo "<td nowrap ><a href='os_press.php?os=" . pg_result ($res,$i,os) . "'>" . pg_result ($res,$i,sua_os) . "</a></td>";
	echo "<td nowrap >" . pg_result ($res,$i,serie) . "</td>";
	echo "<td nowrap >" . pg_result ($res,$i,digitacao) . "</td>";
	echo "<td nowrap >" . pg_result ($res,$i,abertura) . "</td>";
	echo "<td nowrap >" . pg_result ($res,$i,fechamento) . "</td>";
	echo "<td nowrap >" . pg_result ($res,$i,consumidor_nome) . "</td>";
	echo "<td nowrap align='right'>" . number_format (pg_result ($res,$i,mao_de_obra),2,",",".") . "</td>";
	echo "<td nowrap >" . pg_result ($res,$i,produto_referencia) . "-" . pg_result ($res,$i,produto_descricao) . "</td>";
	echo "</tr>";

	$total += pg_result ($res,$i,mao_de_obra) ;
	$qtde  += 1 ;

}

echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
if (strlen ($distribuidor) == 0) {
	echo "<td align='center' nowrap colspan='6'>Total via Fabricante - $qtde OS</td>";
}else{
	echo "<td align='center' nowrap colspan='6'>Total via Distribuidor - $qtde OS</td>";
}
echo "<td align='right' nowrap >" . number_format ($total,2,",",".") . "</td>";
echo "<td>&nbsp;</td>";
echo "</tr>";

echo "</table>";


?>

<p><p>

<? include "rodape.php"; ?>