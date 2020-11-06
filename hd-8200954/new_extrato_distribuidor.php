<?
# Alterado por Sono em 14/08/2006 - Chamado 442 Help-Desk #

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include "autentica_usuario_financeiro.php";

if ($login_e_distribuidor <> 't') {
	header ("Location: new_extrato_posto.php");
	exit;
}

#echo "<h1>Programa em Manutenção</h1>";
#exit;

$msg_erro = "";

$layout_menu = "os";
$title = "Extratos";

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
<?
if(strlen($msg)>0){
	echo "<br><table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'><img src='imagens/cadeado1.jpg' align='absmiddle'></td><td  class='Mensagem' bgcolor='FFFFFF' align='left'>$msg</td>";
	echo "</tr>";
	echo "</table><br>";
	echo "<a href='os_extrato_senha.php?acao=alterar'>Alterar senha</a>";
	echo "&nbsp;&nbsp;<a href='os_extrato_senha.php?acao=libera'>Liberar tela</a>";
}else{
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'><img src='imagens/cadeado2.jpg' align='absmiddle'></td><td  class='Mensagem' bgcolor='FFFFFF' align='left'><a href='os_extrato_senha.php?acao=inserir' >Esta area não está protegida por senha! <br>Para inserir senha para Restrição do Extrato, clique aqui e saiba mais! </a></td>";
	echo "</tr>";
	echo "</table><br>";
}
?>

<p>
<center>
<?
if ($login_posto=="4311"){
		echo "<font size='+1' face='arial'>Data do Extrato ( Posto Telecontrol )</font>";
		$sql = "SELECT  tbl_extrato.extrato                                            ,
						date_trunc('day',tbl_extrato.data_geracao)      AS data_extrato,
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data        ,
						to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') AS periodo
				FROM    tbl_extrato
				WHERE   tbl_extrato.posto = $login_posto
				AND     tbl_extrato.fabrica = $login_fabrica
				AND     tbl_extrato.aprovado IS NOT NULL
				AND     tbl_extrato.data_geracao >= '2005-03-30'
				ORDER   BY  to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') DESC";
		//echo $sql;

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<form name='frm_extrato' method='GET' action='extrato_posto_devolucao_lgr_tele.php'>";
		echo "<select name='extrato' onchange='javascript:frm_extrato.submit()'>\n";
		echo "<option value=''></option>\n";
		
		for ($x = 0 ; $x < pg_numrows($res) ; $x++){
			$aux_extrato = trim(pg_result($res,$x,extrato));
			$aux_data    = trim(pg_result($res,$x,data));
			$aux_extr    = trim(pg_result($res,$x,data_extrato));
			$aux_peri    = trim(pg_result($res,$x,periodo));
			
			if (1==2 AND $login_fabrica == 3 AND $aux_extr > "2005-11-01" AND $login_posto <> 1053 AND $login_posto <> 1789) {
				echo "<option value=''>Calculando</option>\n";
			}else{
				echo "<option value='$aux_extrato'>$aux_data</option>\n";
			}
		}
		
		echo "</select>\n";
		echo "</form>";
	}
}else{
		echo "<font size='+1' face='arial'>Data do Extrato (Posto)</font>";
		$sql = "SELECT  tbl_extrato.extrato                                            ,
						date_trunc('day',tbl_extrato.data_geracao)      AS data_extrato,
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data        ,
						to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') AS periodo
				FROM    tbl_extrato
				WHERE   tbl_extrato.posto = $login_posto
				AND     tbl_extrato.fabrica = $login_fabrica
				AND     tbl_extrato.aprovado IS NOT NULL
				AND     tbl_extrato.data_geracao >= '2005-03-30'
				ORDER   BY  to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') DESC";
		//echo $sql;

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<form name='frm_extrato' method='GET' action='extrato_posto_devolucao_lgr.php'>";
		echo "<select name='extrato' onchange='javascript:frm_extrato.submit()'>\n";
		echo "<option value=''></option>\n";
		
		for ($x = 0 ; $x < pg_numrows($res) ; $x++){
			$aux_extrato = trim(pg_result($res,$x,extrato));
			$aux_data    = trim(pg_result($res,$x,data));
			$aux_extr    = trim(pg_result($res,$x,data_extrato));
			$aux_peri    = trim(pg_result($res,$x,periodo));
			
			if (1==2 AND $login_fabrica == 3 AND $aux_extr > "2005-11-01" AND $login_posto <> 1053 AND $login_posto <> 1789) {
				echo "<option value=''>Calculando</option>\n";
			}else{
				echo "<option value='$aux_extrato'>$aux_data</option>\n";
			}
		}
		
		echo "</select>\n";
		echo "</form>";
	}
}
?>
<br><br>


<!--

Desabilitado. Distribuidores não pagam mais Mão-de-Obra de seus postos 

--------------------------

Fabio: habilitei novamente para o Distribuidor visualizar o extrato dos postos para conferencia.
Solicitado por Sirlei em 14/09

-->

<font size='+1' face='arial'>Data do Extrato (Distribuidor)</font>
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

	//if ($ip == "201.43.11.216") { echo nl2br($sql); exit; };

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

/*
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
*/

	$sql = "SELECT  tbl_linha.nome                        AS linha_nome  ,
					tbl_os_extra.mao_de_obra              AS unitario    ,
					COUNT(*) AS qtde                                     ,
					ROUND (SUM (tbl_os_extra.mao_de_obra)::numeric,2)           AS mao_de_obra_posto     ,
					ROUND (SUM (tbl_os_extra.mao_de_obra_adicional)::numeric,2) AS mao_de_obra_adicional ,
					ROUND (SUM (tbl_os_extra.adicional_pecas)::numeric,2)       AS adicional_pecas
			FROM (
				SELECT tbl_os_extra.os
				FROM (
					SELECT extrato FROM tbl_extrato
					WHERE fabrica = $login_fabrica
					AND tbl_extrato.data_geracao BETWEEN '$periodo_inicial' AND '$periodo_final'
				) ext 
				JOIN tbl_os_extra ON tbl_os_extra.extrato = ext.extrato
				JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
				WHERE tbl_os.posto = $login_posto

				UNION

				SELECT tbl_os_extra.os
				FROM (
					SELECT extrato FROM tbl_extrato
					WHERE fabrica = $login_fabrica
					AND tbl_extrato.data_geracao BETWEEN '$periodo_inicial' AND '$periodo_final'
				) ext 
				JOIN tbl_os_extra ON tbl_os_extra.extrato = ext.extrato
				JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
				WHERE tbl_os_extra.distribuidor = $login_posto
			) oss
			JOIN tbl_os_extra ON tbl_os_extra.os = oss.os
			JOIN tbl_os ON oss.os = tbl_os.os
			JOIN tbl_linha ON tbl_os_extra.linha = tbl_linha.linha
			GROUP BY tbl_linha.nome , tbl_os_extra.mao_de_obra
			ORDER BY tbl_linha.nome";
/*

SELECT tbl_os_extra.os 
FROM (
	SELECT extrato FROM tbl_extrato
	WHERE fabrica = 3
	AND tbl_extrato.data_geracao BETWEEN '2007-05-01 00:00:00' AND '2007-05-30 23:59:59'
) ext 
JOIN tbl_os_extra ON tbl_os_extra.extrato = ext.extrato
JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
WHERE tbl_os.posto = 4311 
AND   tbl_os.fabrica = 3

UNION

SELECT tbl_os_extra.os 
FROM (
	SELECT extrato FROM tbl_extrato
	WHERE fabrica = 3
	AND tbl_extrato.data_geracao BETWEEN '2007-05-01 00:00:00' AND '2007-05-30 23:59:59'
) ext 
JOIN tbl_os_extra ON tbl_os_extra.extrato = ext.extrato
JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
WHERE tbl_os_extra.distribuidor = 4311 
AND   tbl_os.fabrica = 3





*/

//if ($ip == "201.43.11.216") { echo nl2br($sql); exit; };
	
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
#echo "parte 0 - " . date ("h:i:s");
#flush();
//echo $sql;exit;
	$res = pg_exec ($con,$sql);

#echo "parte 1 - " . date ("h:i:s");
#flush();

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

<!--
Desabilitei. Fabio 14/09/2007
-->

<p><p>

<? include "rodape.php"; ?>
