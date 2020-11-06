<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";
include "autentica_admin.php";

include "cabecalho.php";
$msg_erro = "";

$layout_menu = "financeiro";
$title = "Consulta e Manutenção de Extratos do Posto";

$posto  = trim ($_GET['posto']);




?>

<?
$data = trim($_GET['data']);

$data_inicio = $data . " 00:00:00";
$data_final  = $data . "  23:59:59";
$data_extrato = substr ($data,8,2) . "/" . substr ($data,5,2) . "/" . substr ($data,0,4) ;
?>

<p>
<center>
<font size='+1' face='arial'>Data do Extrato <? echo $data_extrato ?></font>

<p>
<table width='400' align='center' border='0'>
<tr>
<td align='center' width='50%'><a href='extrato_distribuidor.php?periodo=<? echo $data ?>&posto=<?=$posto?>'>Ver extrato total</a></td>
<td align='center' width='50%'><a href='extrato_distribuidor.php'>Ver outro extrato</a></td>
</tr>
</table>

<font size='-2'>Clique no código do posto para ver as OS</font>

<?

if (strlen ($data) > 0) {
	$sql = "SELECT  DISTINCT
					tbl_os_extra.linha ,
					tbl_linha.nome AS linha_nome ,
					tbl_os_extra.mao_de_obra
			FROM    tbl_os_extra
			JOIN   (SELECT tbl_os_extra.os FROM tbl_os_extra JOIN tbl_extrato USING (extrato)
						WHERE  (tbl_os_extra.distribuidor = $posto OR tbl_extrato.posto = $posto)
						AND     tbl_extrato.fabrica = $login_fabrica
						AND     tbl_extrato.aprovado IS NOT NULL
						AND     tbl_extrato.data_geracao BETWEEN '$data_inicio' AND '$data_final'
			) oss ON tbl_os_extra.os = oss.os
			JOIN    tbl_os      ON tbl_os_extra.os      = tbl_os.os
			JOIN    tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato
			JOIN    tbl_linha   ON tbl_os_extra.linha   = tbl_linha.linha
			ORDER   BY tbl_linha.nome, tbl_os_extra.mao_de_obra ";
#if ($ip == '201.0.9.216' or substr ($ip,10) == '200.212.63' ) echo $sql."<BR>";
#echo $sql;
flush();
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='500' align='center' border='1' cellspacing='2' cellpadding='2'>";
		echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
		echo "<td align='center'>Código</td>";
		echo "<td align='center'>Posto</td>";
		echo "<td align='center'>Cidade</td>";

		$array_coluna = "";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$array_coluna [$i] = pg_result ($res,$i,linha) . ";" . pg_result ($res,$i,mao_de_obra);

			echo "<td align='center' nowrap>" . pg_result ($res,$i,linha_nome) . "<br> R$ " . trim (number_format (pg_result ($res,$i,mao_de_obra),2,",",".")) . "</td>";
		}

		echo "<td align='center'>TOTAL</td>";
		echo "</tr>";

		$sql = "SELECT  tbl_posto_fabrica.codigo_posto ,
						tbl_os_extra.extrato ,
						tbl_posto.posto ,
						tbl_posto.nome ,
						tbl_posto.cidade ,
						tbl_os_extra.linha ,
						tbl_linha.nome AS linha_nome ,
						tbl_os_extra.mao_de_obra AS unitario ,
						SUM (tbl_os_extra.mao_de_obra) AS mao_de_obra
				FROM    tbl_os_extra
				JOIN   (SELECT tbl_os_extra.os 
						FROM tbl_os_extra 
						JOIN tbl_extrato USING (extrato)
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND   tbl_extrato.data_geracao BETWEEN '$data_inicio' AND '$data_final'
						AND   (tbl_extrato.posto = $posto OR tbl_os_extra.distribuidor = $posto)
				) oss   ON oss.os = tbl_os_extra.os
				JOIN    tbl_linha  ON tbl_os_extra.linha    = tbl_linha.linha
				JOIN    tbl_os     ON tbl_os_extra.os       = tbl_os.os
				JOIN    tbl_posto  ON tbl_os.posto          = tbl_posto.posto
				JOIN    tbl_posto_fabrica ON tbl_os.posto   = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
				JOIN    tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato
				WHERE   (tbl_os_extra.distribuidor = $posto OR tbl_os.posto = $posto)
				AND     tbl_extrato.data_geracao BETWEEN '$data_inicio' AND '$data_final'
				GROUP BY tbl_posto_fabrica.codigo_posto , tbl_os_extra.extrato, tbl_posto.posto, tbl_posto.nome, tbl_posto.cidade , tbl_os_extra.linha , tbl_linha.nome , tbl_os_extra.mao_de_obra
				ORDER BY tbl_posto.cidade , tbl_posto.nome , tbl_linha.nome , tbl_os_extra.mao_de_obra ";
	//if ($ip == '201.0.9.216') echo $sql."<BR>";
		$res = pg_exec ($con,$sql);

		$unitario = 0;
		$linha    = 0;
		$codigo   = "";
		$nome     = "";
		$cidade   = "";
		$total_posto = 0 ;

	#	$array_total = array_fill (0,count($array_coluna),0);
		for ($x = 0 ; $x < count($array_coluna) ; $x++) {
			$array_total [$x] = 0;
		}

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

			if ($codigo <> pg_result ($res,$i,codigo_posto) ) {
				if (strlen ($codigo) > 0) {
					echo "<tr style='font-size:10px'>" ;
					echo "<td nowrap><a href='extrato_distribuidor_os.php?posto=$posto&extrato=$extrato' style='text-decoration: underline' alt='ver ordens de serviço'>" . $codigo . "</a></td>";
					echo "<td nowrap>" . $nome . "</td>";
					echo "<td nowrap>" . $cidade . "</td>";
					echo implode (" " , $array_print) ;
					echo "<td align='right' nowrap>" . number_format ($total_posto,2,",","."). "</td>";
					echo "</tr>";
				}

				$unitario = pg_result ($res,$i,unitario);
				$linha    = pg_result ($res,$i,linha);
				$codigo   = pg_result ($res,$i,codigo_posto);
				$posto    = pg_result ($res,$i,posto);
				$extrato  = pg_result ($res,$i,extrato);
				$nome     = pg_result ($res,$i,nome);
				$cidade   = pg_result ($res,$i,cidade);
				$total_posto = 0 ;

	#			$array_print = array_fill (0,count($array_coluna),"<td>&nbsp;</td>");
				for ($x = 0 ; $x < count($array_coluna) ; $x++) {
					$array_print[$x] = "<td>&nbsp;</td>";
				}

			}

			$pesquisa = pg_result ($res,$i,linha) . ";" . pg_result ($res,$i,unitario) ;
			$coluna = array_search ($pesquisa,$array_coluna);
			$array_print [$coluna] = "<td nowrap align='right'>" . number_format (pg_result ($res,$i,mao_de_obra),2,",",".") . "</td>";
			$array_total [$coluna] = $array_total [$coluna] + pg_result ($res,$i,mao_de_obra);
			$total_posto += pg_result ($res,$i,mao_de_obra) ;
		}

		echo "<tr style='font-size:10px'>" ;
		echo "<td nowrap><a href='new_extrato_distribuidor_os.php?posto=$posto&extrato=$extrato' style='text-decoration: underline' alt='ver ordens de serviço'>" . $codigo . "</a></td>";
		echo "<td nowrap>" . $nome . "</td>";
		echo "<td nowrap>" . $cidade . "</td>";
		echo implode (" " , $array_print) ;
		echo "<td align='right' nowrap>" . number_format ($total_posto,2,",","."). "</td>";
		echo "</tr>";

		$total_geral = 0;
		echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
		echo "<td nowrap align='center' colspan='3'> Totais deste Extrato </td>";
		for ($i = 0 ; $i < count($array_total) ; $i++) {
			echo "<td align='right'>";
			$valor = $array_total[$i];
			$total_geral += $valor ;
			echo number_format ($valor,2,",",".");
			echo "</td>";
		}
		echo "<td align='right' nowrap>" . number_format ($total_geral,2,",","."). "</td>";
		echo "</tr>";

		echo "</table>";
	}else{
		echo "<table width='500' align='center' border='1' cellspacing='2' cellpadding='2'>";
		echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
		echo "<td align='center'>Nenhum extrato de posto encontrado no período selecionado.</td>";
		echo "</tr>";
		echo "</table>";
	}
}


?>

<p><p>

<? include "rodape.php"; ?>