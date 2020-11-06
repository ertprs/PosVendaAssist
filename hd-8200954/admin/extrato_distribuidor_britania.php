<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";
include "autentica_admin.php";

if ($login_e_distribuidor <> 't') {
	header ("Location: new_extrato_posto.php");
	exit;
}


#echo "<h1>Programa em Manutenção</h1>";
#exit;

$msg_erro = "";

$layout_menu = "financeiro";
$title = "Consulta e Manutenção de Extratos do Distribuidor";

include "cabecalho.php";
?>
<style>
.Mensagem{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#7192C4;
	font-weight: bold;
}
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
	}
</style>


<p>
<center>
<font size='+1' face='arial'>Data do Extrato</font>
<?
$periodo = trim($_POST['periodo']);
if (strlen ($periodo) == 0) $periodo = trim ($_GET['periodo']);

if (strlen ($periodo) == 0) {
	$sql = "SELECT  DISTINCT
					date_trunc('day',tbl_extrato.data_geracao)      AS data_extrato,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data        ,
					to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') AS periodo
			FROM    tbl_extrato
			JOIN    tbl_posto_linha ON tbl_extrato.posto = tbl_posto_linha.posto
			WHERE   (tbl_posto_linha.distribuidor = $login_posto OR tbl_extrato.posto = $login_posto)
			AND     tbl_extrato.fabrica = $login_fabrica
			AND     tbl_extrato.aprovado IS NOT NULL
			AND     tbl_extrato.data_geracao >= '2005-03-30'
			ORDER   BY  to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') DESC";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<form name='frm_periodo' method='post' action='$PHP_SELF'>";
		echo "<select name='periodo' onchange='javascript:frm_periodo.submit()'>\n";
		echo "<option value=''></option>\n";
		
		for ($x = 0 ; $x < pg_numrows($res) ; $x++){
			$aux_data  = trim(pg_result($res,$x,data));
			$aux_extr  = trim(pg_result($res,$x,data_extrato));
			$aux_peri  = trim(pg_result($res,$x,periodo));
			
			echo "<option value='$aux_peri' "; if ($periodo == $aux_peri) echo " SELECTED "; echo ">$aux_data</option>\n";
		}
		
		echo "</select>\n";
		echo "</form>";
	}
}else{
	$periodo_inicial = $periodo . " 00:00:00";
	$periodo_final   = $periodo . " 23:59:59";

	$sql = "SELECT	tbl_linha.nome                        AS linha_nome            ,
					tbl_os_extra.mao_de_obra              AS unitario              ,
					COUNT(*) AS qtde                                               ,
					ROUND (SUM (mao_de_obra)::numeric,2)           AS mao_de_obra_posto     ,
					ROUND (SUM (mao_de_obra_adicional)::numeric,2) AS mao_de_obra_adicional ,
					ROUND (SUM (adicional_pecas)::numeric,2)       AS adicional_pecas
			FROM
				(SELECT tbl_os_extra.os 
				FROM tbl_os_extra 
				JOIN tbl_extrato USING (extrato)
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   tbl_extrato.data_geracao BETWEEN '$periodo_inicial' AND '$periodo_final'
				AND   (tbl_extrato.posto = $login_posto OR tbl_os_extra.distribuidor = $login_posto)
				) os 
			JOIN tbl_os_extra ON os.os = tbl_os_extra.os
			JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
			GROUP BY tbl_linha.nome, tbl_os_extra.mao_de_obra
			ORDER BY tbl_linha.nome";

	$sql = "SELECT  tbl_linha.nome                        AS linha_nome  ,
					tbl_os_extra.mao_de_obra              AS unitario    ,
					COUNT(*) AS qtde                                     ,
					ROUND (SUM (mao_de_obra)::numeric,2)           AS mao_de_obra_posto     ,
					ROUND (SUM (mao_de_obra_adicional)::numeric,2) AS mao_de_obra_adicional ,
					ROUND (SUM (adicional_pecas)::numeric,2)       AS adicional_pecas
			FROM tbl_os_extra
			JOIN tbl_linha ON tbl_os_extra.linha = tbl_linha.linha
			WHERE tbl_os_extra.extrato IN (SELECT extrato FROM tbl_extrato 
					WHERE fabrica = $login_fabrica
					AND tbl_extrato.data_geracao BETWEEN '$periodo_inicial' AND '$periodo_final'
					AND   (tbl_extrato.posto = $login_posto OR tbl_extrato.posto IN (
						SELECT posto 
						FROM tbl_posto_linha 
						JOIN tbl_linha USING (linha) 
						WHERE distribuidor = $login_posto AND tbl_linha.fabrica = $login_fabrica
					) )
			)
			GROUP BY tbl_linha.nome, tbl_os_extra.mao_de_obra
			ORDER BY tbl_linha.nome";

	
	$Xsql = "SELECT	tbl_linha.nome AS linha_nome              ,
					tbl_os_extra.mao_de_obra AS unitario      ,
					COUNT (*) AS qtde                         ,
					SUM (tbl_os_extra.mao_de_obra)           AS mao_de_obra_posto ,
					SUM (tbl_os_extra.mao_de_obra_adicional) AS mao_de_obra_adicional ,
					SUM (tbl_os_extra.adicional_pecas)       AS adicional_pecas ,
						";

#if ($_SERVER['REMOTE_ADDR'] == '201.0.9.216') { echo $sql ; flush(); exit; } ;
#echo $sql;
flush();
	$res = pg_exec ($con,$sql);

	$x_periodo = substr ($periodo,8,2) . "/" . substr ($periodo,5,2) . "/" . substr ($periodo,0,4) ;
	echo $x_periodo;

	echo "<table width='500' align='center' border='1' cellspacing='2'>";
	echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
	echo "<td align='center'>Linha</td>";
	echo "<td align='center'>M.O.Unit.</td>";
	echo "<td align='center'>Qtde</td>";
	echo "<td align='center'>M.O.Postos</td>";
	echo "<td align='center'>M.O.Adicional</td>";
	if ($login_posto == 4311) {
		echo "<td align='center'>Adicional Peças</td>";
	}
	echo "</tr>";

	$total_qtde            = 0 ;
	$total_mo_posto        = 0 ;
	$total_mo_adicional    = 0 ;
	$total_adicional_pecas = 0 ;

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		echo "<tr style='font-size: 10px'>";

		echo "<td>";
		echo pg_result ($res,$i,linha_nome);
		echo "</td>";

		echo "<td align='right'>";
		echo number_format (pg_result ($res,$i,unitario),2,',','.');
		echo "</td>";

		echo "<td align='right'>";
		echo number_format (pg_result ($res,$i,qtde),0,',','.');
		echo "</td>";

		echo "<td align='right'>";
		echo number_format (pg_result ($res,$i,mao_de_obra_posto),2,',','.');
		echo "</td>";

		echo "<td align='right'>";
		echo number_format (pg_result ($res,$i,mao_de_obra_adicional),2,',','.');
		echo "</td>";

		if ($login_posto == 4311) {
			echo "<td align='right'>";
			echo number_format (pg_result ($res,$i,adicional_pecas),2,',','.');
			echo "</td>";
		}


		echo "</tr>";

		$total_qtde            += pg_result ($res,$i,qtde) ;
		$total_mo_posto        += pg_result ($res,$i,mao_de_obra_posto) ;
		$total_mo_adicional    += pg_result ($res,$i,mao_de_obra_adicional) ;
		$total_adicional_pecas += pg_result ($res,$i,adicional_pecas) ;

	}

	echo "<tr bgcolor='#FF9900' style='font-size:14px ; color:#ffffff ; font-weight:bold ' >";
	echo "<td align='center'>TOTAIS</td>";
	echo "<td align='center'></td>";
	echo "<td align='right'>" . number_format ($total_qtde           ,0,",",".") . "</td>";
	echo "<td align='right'>" . number_format ($total_mo_posto       ,2,",",".") . "</td>";
	echo "<td align='right'>" . number_format ($total_mo_adicional   ,2,",",".") . "</td>";
	if ($login_posto == 4311) {
		echo "<td align='right'>" . number_format ($total_adicional_pecas,2,",",".") . "</td>";
	}
	echo "</tr>";

	echo "</table>";

	echo "<p align='center'>";
#	echo "<font style='font-size:12px'>Enviar Nota Fiscal de Prestação de Serviços para o fabricante<br>no valor de <b>R$ " . trim (number_format ($total_mo_posto + $total_mo_adicional + $total_adicional_pecas,2,",",".")) . "</b> descontados os tributos na forma da Lei.";
}

if (strlen ($periodo) > 0) {
	echo "<p>";
	echo "<a href='new_extrato_distribuidor_posto.php?data=$periodo'>Ver extratos dos postos</a>";

	echo "<p>";
	echo "<a href='new_extrato_distribuidor_retornaveis.php?data=$periodo'>Peças Retornáveis</a>";

	echo "<p>";
	echo "<a href='new_extrato_distribuidor.php'>Outro extrato</a>";

	if ($login_posto == 595) {
		echo "<p>";
		echo "<a href='new_extrato_distribuidor_pecas_estoque.php?data=$periodo'>Peças do Estoque</a>";
	}

	if ($login_posto == 4311) {
		echo "<p>";
		echo "<a href='new_extrato_distribuidor_adicional_pecas.php?data=$periodo'>Adicional de Peças</a>";
	}

}


?>

<p><p>

<? include "rodape.php"; ?>
