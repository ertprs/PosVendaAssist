<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_e_distribuidor == 't') {
	header ("Location: new_extrato_distribuidor.php");
	exit;
}



$extrato = trim($_POST['extrato']);
if (strlen ($extrato) == 0) $extrato = trim ($_GET['extrato']);



$sql = "SELECT * FROM tbl_extrato_devolucao WHERE extrato = $extrato AND nota_fiscal IS NULL";
$res = pg_exec ($con,$sql);
if (pg_numrows ($res) > 0) {
	header ("Location: new_extrato_posto.php");
	exit;
}

$msg_erro = "";

$layout_menu = "os";
$title = "Extratos";

include "cabecalho.php";

?>


<?

if (date('Y-m-d') >= '2006-08-01' AND $login_fabrica == 3 AND $extrato > 58445 ) {
	echo "<table align='center'>";
	echo "<tr>";
	echo "<td>";

	echo "EMITIR A NOTA FISCAL DE SERVIÇOS PARA :";
	echo "<br>";
	echo "<b>";
	echo "BRITÂNIA ELETRODOMÉSTICOS S/A";
	echo "</b>";
	echo "<br>";
	echo "AV.RUI BARBOSA,3000 <br>
		SÃO JOSÉ DOS PINHAIS - PR <br>
		CNPJ: 76.492.701/0001-57 <br>
		IE.10503415-65 <p>
		ENVIAR AS ORDENS DE SERVIÇO E A NOTA <br>
		FISCAL PARA O SEU DISTRIBUIDOR";
	echo "</td>";
	echo "</tr>";
	echo "</table>";


}
?>


<p>
<center>
<font size='+1' face='arial'>Data do Extrato</font>
<?

$sql = "SELECT	tbl_linha.nome AS linha_nome ,
				tbl_linha.linha              ,
				tbl_os_extra.mao_de_obra AS unitario ,
				COUNT(*) AS qtde            ,
				SUM (tbl_os_extra.mao_de_obra) AS mao_de_obra_posto ,
				SUM (tbl_os_extra.mao_de_obra_adicional) AS mao_de_obra_adicional ,
				SUM (tbl_os_extra.adicional_pecas)       AS adicional_pecas       ,
				to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao  ,
				distrib.fantasia AS distrib_nome
		FROM
			(SELECT tbl_os_extra.os 
			FROM tbl_os_extra 
			JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
			WHERE tbl_os_extra.extrato = $extrato
			) os 
		JOIN tbl_os_extra ON os.os = tbl_os_extra.os
		JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
		JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
		LEFT JOIN tbl_posto distrib ON tbl_os_extra.distribuidor = distrib.posto
		GROUP BY tbl_linha.linha, tbl_linha.nome, tbl_os_extra.mao_de_obra, tbl_extrato.data_geracao, distrib.fantasia
		ORDER BY tbl_linha.nome";
$res = pg_exec ($con,$sql);

echo @pg_result ($res,0,data_geracao);

echo "<table width='300' align='center' border='1' cellspacing='2'>";
echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
echo "<td align='center' nowrap >Linha</td>";
echo "<td align='center' nowrap >M.O.Unit.</td>";
echo "<td align='center' nowrap >Qtde</td>";
echo "<td align='center' nowrap >Mão-de-Obra</td>";
#echo "<td align='center' nowrap >Pago via</td>";
echo "<td align='center' nowrap >&nbsp;</td>";
echo "</tr>";

$total_qtde            = 0 ;
$total_mo_posto        = 0 ;
$total_mo_adicional    = 0 ;
$total_adicional_pecas = 0 ;

for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
	echo "<tr style='font-size: 10px'>";

	echo "<td nowrap >";
	echo pg_result ($res,$i,linha_nome);
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo number_format (pg_result ($res,$i,unitario),2,',','.');
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo number_format (pg_result ($res,$i,qtde),0,',','.');
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo number_format (pg_result ($res,$i,mao_de_obra_posto),2,',','.');
	echo "</td>";

#	echo "<td  nowrap align='center'>";
#	$distrib_nome = pg_result ($res,$i,distrib_nome) ;
#	if (strlen ($distrib_nome) == 0) $distrib_nome = "<b>FABR.</b>";
#	echo $distrib_nome;
#	echo "</td>";

	$linha = pg_result ($res,$i,linha) ;
	$mounit = pg_result ($res,$i,unitario) ;

	echo "<td align='right' nowrap>";
	echo "<a href='new_extrato_posto_detalhe.php?extrato=$extrato&linha=$linha&mounit=$mounit'>ver O.S.</a>";
	echo "</td>";

	echo "</tr>";

	$total_qtde            += pg_result ($res,$i,qtde) ;
	$total_mo_posto        += pg_result ($res,$i,mao_de_obra_posto) ;
	$total_mo_adicional    += pg_result ($res,$i,mao_de_obra_adicional) ;
	$total_adicional_pecas += pg_result ($res,$i,adicional_pecas) ;

}

echo "<tr bgcolor='#FF9900' style='font-size:14px ; color:#ffffff ; font-weight:bold ' >";
echo "<td align='center'>TOTAIS</td>";
echo "<td align='center'>&nbsp;</td>";
echo "<td align='right'>" . number_format ($total_qtde           ,0,",",".") . "</td>";
echo "<td align='right'>" . number_format ($total_mo_posto       ,2,",",".") . "</td>";
#echo "<td align='center'>&nbsp;</td>";
echo "<td align='center'>&nbsp;</td>";
echo "</tr>";

echo "</table>";

echo "<p align='center'>";
#echo "<font style='font-size:12px'>Enviar Nota Fiscal de Prestação de Serviços para o fabricante<br>no valor de <b>R$ " . trim (number_format ($total_mo_posto + $total_mo_adicional + $total_adicional_pecas,2,",",".")) . "</b> descontados os tributos na forma da Lei.";

echo "<p>";
echo "<a href='new_extrato_posto.php'>Outro extrato</a>";

#echo "<p>";
#echo "<a href='new_extrato_posto_retornaveis.php?extrato=$extrato'>Peças Retornáveis</a>";

?>

<p><p>

<? include "rodape.php"; ?>
