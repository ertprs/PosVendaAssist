<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

include "funcoes.php";


$erro = "";

if (strlen($_POST["acao"]) > 0 ) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0 )  $acao = strtoupper($_GET["acao"]);

if (strlen($acao) > 0 && $acao == "PESQUISAR") {
	if (strlen(trim($_POST["data_inicial"])) > 0) $x_data_inicial = trim($_POST["data_inicial"]);
	if (strlen(trim($_GET["data_inicial"])) > 0)  $x_data_inicial = trim($_GET["data_inicial"]);
	
	$aux_data_inicial = str_replace("/","",$x_data_inicial);
	$aux_data_inicial = str_replace("-","",$aux_data_inicial);
	$aux_data_inicial = str_replace(".","",$aux_data_inicial);
	$aux_data_inicial = fnc_so_numeros($aux_data_inicial);
	
	if (strlen($aux_data_inicial) < 8) $erro = "Data inicial em formato inválido";
	
	if (strlen($erro) == 0){
		$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
		
		if (strlen(trim($_POST["data_final"])) > 0) $x_data_final   = trim($_POST["data_final"]);
		if (strlen(trim($_GET["data_final"])) > 0) $x_data_final = trim($_GET["data_final"]);
		
		$x_data_final   = fnc_formata_data_pg($x_data_final);
		
		if (strlen($x_data_inicial) > 0 && $x_data_inicial != "null") {
			$x_data_inicial = str_replace("'", "", $x_data_inicial);
			$dia_inicial = substr($x_data_inicial, 8, 2);
			$mes_inicial = substr($x_data_inicial, 5, 2);
			$ano_inicial = substr($x_data_inicial, 0, 4);
			$data_inicial = $dia_inicial . "/" . $mes_inicial . "/" . $ano_inicial;
		}else{
			$erro .= " Informe a Data Inicial para realizar a pesquisa. ";
		}
		
		if (strlen($x_data_final) > 0 && $x_data_final != "null") {

			$aux_data_final = str_replace("/","",$x_data_final);
			$aux_data_final = str_replace("-","",$aux_data_final);
			$aux_data_final = str_replace(".","",$aux_data_final);
			$aux_data_final = fnc_so_numeros($aux_data_final);
			
			if (strlen($aux_data_final) < 8) $erro = "Data final em formato inválido";
			
			if (strlen($erro) == 0){
				$x_data_final = str_replace("'", "", $x_data_final);
				$dia_final = substr($x_data_final, 8, 2);
				$mes_final = substr($x_data_final, 5, 2);
				$ano_final = substr($x_data_final, 0, 4);
				$data_final = $dia_final . "/" . $mes_final . "/" . $ano_final;
			}
		}else{
			$erro .= " Informe a Data Inicial para realizar a pesquisa. ";
		}
		
		if (strlen(trim($_POST["status"])) > 0) $status = trim($_POST["status"]);
		if (strlen(trim($_GET["status"])) > 0)  $status = trim($_GET["status"]);
		
		$link_status = "http://" . $HTTP_HOST . $REQUEST_URI . "?data_inicial=" . $_POST["data_inicial"] . "&data_final=" . $_POST["data_final"] . "&acao=PESQUISAR";
		setcookie("LinkStatus", $link_status);
	}
}

$layout_menu = "os";
$title = "Relação de Status da Ordem de Serviço";

include "cabecalho.php";


#--------- TULIO 19/04 - Acertar SQL , Restringir a no maximo 1 mes - Colocar mais parametros para restringir
// somente Fabiola
//if ($ip <> '12.148.189.25' AND $ip <> '201.0.9.216'){
//	echo "<h1>Programa em Manutenção</h1>";
//	exit;
//}
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12 px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12 px;
	font-weight: normal;
}
</style>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<br>

<? if (strlen($erro) > 0) { ?>
<table width="600" border="0" cellspacing="0" cellpadding="2" align="center" class="Error">
	<tr>
		<td><?echo $erro?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="acao">
<table width="400" border="0" cellspacing="0" cellpadding="2" align="center">
	<tr class="Titulo">
		<td colspan="4"><b>PESQUISE ENTRE DATAS</b></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td align='left'>Data Inicial</td>
		<td align='left'>Data Final</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>
			<input type="text" name="data_inicial" size="12" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
			&nbsp;
			<img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataInicial')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td>
			<input type="text" name="data_final" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
			&nbsp;
			<img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" height='20' valign='middle'>
		<td colspan='4' align='center'>
			<select name="status" size="1" class="frm">
			<option <?if ($status == "00") echo " selected ";?> value='00'>Todas</option>
			<option <?if ($status == "01") echo " selected ";?> value='01'>Aprovadas</option>
			<option <?if ($status == "13") echo " selected ";?> value='13'>Recusadas</option>
			<option <?if ($status == "14") echo " selected ";?> value='14'>Acumuladas</option>
			<option <?if ($status == "15") echo " selected ";?> value='15'>Excluídas</option>
			</select>
		</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4"><img border="0" src="imagens/btn_pesquisar_400.gif" onClick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

<br>

<?
if (strlen($acao) > 0 && strlen($erro) == 0) {
	##### OS FINALIZADAS #####
/*
	$sql =	"SELECT DISTINCT
					tbl_posto_fabrica.codigo_posto                                   ,
					tbl_os.os                                                        ,
					tbl_os.sua_os                                                    ,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')    AS data_digitacao ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')     AS data_abertura  ,
					tbl_os.pecas                                                     ,
					tbl_os.mao_de_obra                                               ,
					TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS aprovado       ,
					TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao   ,
					tbl_os_status.status_os                                          ,
					tbl_os_status.observacao
			FROM tbl_os
			JOIN tbl_os_extra USING (os)
			JOIN tbl_posto    USING (posto)
			JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_extrato   ON tbl_extrato.extrato = tbl_os_extra.extrato
			LEFT JOIN tbl_os_status ON  tbl_os.os          = tbl_os_status.os
			WHERE tbl_os.data_digitacao::date BETWEEN '$x_data_inicial' AND '$x_data_final' ";
	if ($status == "01") $sql.= "AND tbl_os_status.status_os NOT IN (13,14,15) ";
	if ($status == "13") $sql.= "AND tbl_os_status.status_os = 13 ";
	if ($status == "14") $sql.= "AND tbl_os_status.status_os = 14 ";
	if ($status == "15") $sql.= "AND tbl_os_status.status_os = 15 ";
	$sql .= " AND tbl_os.data_fechamento NOTNULL
			AND tbl_os.posto   = $login_posto
			AND tbl_os.fabrica = $login_fabrica";
*/
	// N O V O
	$sql = "SELECT  DISTINCT
					tbl_posto_fabrica.codigo_posto                                    ,
					tbl_os.os                                                         ,
					tbl_os.sua_os                                                     ,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')    AS data_digitacao  ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')     AS data_abertura   ,
					TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')   AS data_fechamento ,
					TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY')        AS finalizada      ,
					tbl_os.pecas                                                      ,
					tbl_os.mao_de_obra                                                ,
					tbl_extrato.extrato                                               ,
					TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS aprovado        ,
					TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao    ,
					(
						select tbl_os_status.status_os 
						from tbl_os_status 
						where tbl_os_status.os = tbl_os.os 
						order by data desc limit 1
					)                                              AS status_os       ,
					(
						select tbl_os_status.observacao 
						from tbl_os_status 
						where tbl_os_status.os = tbl_os.os 
						order by data desc limit 1
					)                                              AS observacao
			FROM  tbl_os 
			LEFT JOIN  tbl_os_extra          ON tbl_os_extra.os           = tbl_os.os
			LEFT JOIN  tbl_extrato           ON tbl_extrato.extrato       = tbl_os_extra.extrato
			JOIN  tbl_posto_fabrica          ON tbl_posto_fabrica.posto   = tbl_os.posto
											AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
			LEFT JOIN  tbl_os_status         ON tbl_os_status.os          = tbl_os.os
			WHERE tbl_os.data_digitacao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59' ";
	
	//if ($status == "00") $sql.= "AND (tbl_os_status.status_os     IN (13,14,15) OR tbl_os_status.os IS NULL) ";
	if ($status == "01") $sql.= "AND (tbl_os_status.status_os NOT IN (13,14,15) OR tbl_os_status.os IS NULL) ";
	if ($status == "13") $sql.= "AND tbl_os_status.status_os = 13 ";
	if ($status == "14") $sql.= "AND tbl_os_status.status_os = 14 ";
	if ($status == "15") $sql.= "AND tbl_os_status.status_os = 15 ";
	
	$sql .= " AND tbl_os.data_fechamento NOTNULL
			AND tbl_os.posto   = $login_posto
			AND tbl_os.fabrica = $login_fabrica
			GROUP BY tbl_posto_fabrica.codigo_posto    ,
					 tbl_os.os                         ,
					 tbl_os.sua_os                     ,
					 tbl_os.data_digitacao             ,
					 tbl_os.data_abertura              ,
					 tbl_os.data_fechamento            ,
					 tbl_os.finalizada                 ,
					 tbl_os.pecas                      ,
					 tbl_os.mao_de_obra                ,
					 tbl_extrato.extrato               ,
					 tbl_extrato.aprovado              ,
					 tbl_extrato.data_geracao
			ORDER BY tbl_os.sua_os";
	$res = pg_exec($con,$sql);
//if ($ip == "201.0.9.216") echo nl2br($sql)."<br>".pg_numrows($res);

#echo "\n\n<!-- $sql -->\n\n";

	if (pg_numrows($res) > 0) {
		echo "<table width='700' border='0' cellspacing='2' cellpadding='0' align='center'>";
		echo "<tr>";
		echo "<td width='20' height='20' bgcolor='#FFE1E1'>&nbsp;</td>";
		echo "<td><font size=1>&nbsp; <b>OS RECUSADA pelo fabricante</b></font></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td colspan='2' height='5'></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td width='20' height='20' bgcolor='#D7FFE1'>&nbsp;</td>";
		echo "<td><font size='1'>&nbsp; <b>OS ACUMULADA pelo fabricante</b> (Clique na linha da OS p/ realizar a alteração desejada na <a href='os_parametros.php'>Consulta de OS</a>)</font></td>";
		echo "</tr>";
		echo "</table>";
		echo "<br>";
		
		echo "<input type='hidden' name='qtde_os' value='" . pg_numrows($res) . "'>";
		
		echo "<table width='650' border='1' cellpadding='2' cellspacing='0' align='center' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='8'>RELAÇÃO DE OS</td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td>OS</td>";
		echo "<td>DIGITAÇÃO</td>";
		echo "<td>ABERTURA</td>";
		echo "<td>FECHAMENTO</td>";
		echo "<td>TOTAL</td>";
		echo "<td>PROTOCOLO</td>";
		echo "<td>STATUS</td>";
		echo "</tr>";
		
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$codigo_posto    = trim(pg_result($res,$i,codigo_posto));
			$os              = trim(pg_result($res,$i,os));
			$sua_os          = trim(pg_result($res,$i,sua_os));
			$data_digitacao  = trim(pg_result($res,$i,data_digitacao));
			$data_abertura   = trim(pg_result($res,$i,data_abertura));
			$data_fechamento = trim(pg_result($res,$i,data_fechamento));
			$finalizada      = trim(pg_result($res,$i,finalizada));
			$pecas           = trim(pg_result($res,$i,pecas));
			$mao_de_obra     = trim(pg_result($res,$i,mao_de_obra));
			$total           = $custo_pecas + $mao_de_obra;
			$extrato         = trim(pg_result($res,$i,extrato));
			$aprovado        = trim(pg_result($res,$i,aprovado));
			$data_geracao    = trim(pg_result($res,$i,data_geracao));
			$status_os       = trim(pg_result($res,$i,status_os));
			$observacao      = trim(pg_result($res,$i,observacao));

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			
			if ($status_os == 13) {
				$cor = "#FFE1E1";
				$rowspan = "2";
				$rowspan = "1";
			}else{
				$rowspan = "1";
			}
			
			if ($status_os == 14) {
				$cor = "#D7FFE1";
			}
			
			echo "<tr class='Conteudo' bgcolor='$cor'";
			if ($status_os == 14 OR $status_os == 13) echo " onclick=\"javascript: window.location='os_consulta_lite.php?acao=PESQUISAR&opcao5=5&numero_os=$sua_os';\" style='cursor: hand;'";
			echo ">";
			echo "<td nowrap rowspan='$rowspan'>";
			if ($login_fabrica == 1) echo $codigo_posto;
			echo $sua_os;
			echo "</td>";
			echo "<td nowrap align='center'>" . $data_digitacao . "</td>";
			echo "<td nowrap align='center'>" . $data_abertura . "</td>";
			echo "<td nowrap align='center'><acronym title='Data de fechamento digitada: $data_fechamento' style='cursor: help;'>" . $finalizada . "</acronym></td>";
			echo "<td nowrap align='right'>" . number_format($total,2,",",".") . "</td>";
			echo "<td nowrap align='center'>" . $os . "</td>";
			echo "<td nowrap align='center'>";
//echo "\n\n<!-- DATA GERACAO: ".strlen($data_geracao)."<BR> APROVADO: ".strlen($aprovado)."<BR> STATUS: ".strlen($status_os)." -->\n\n";

//			if     (strlen($data_geracao) > 0  AND strlen($aprovado) == 0 AND strlen($status_os) == 0) echo "Em aprovação";
			if     (strlen($data_geracao) > 0  AND strlen($aprovado) == 0 AND $status_os <> 13)        echo "Em aprovação";
			elseif (strlen($data_geracao) == 0 AND strlen($aprovado) == 0 AND strlen($status_os) == 0) echo "Finalizada";
			elseif (strlen($aprovado) > 0 AND $status_os <> 13)                                        echo "Aprovada";
			elseif ($status_os == 13 and  strlen($extrato) == 0)                                       echo "Recusada";
			elseif ($login_fabrica == 19 and $status_os == 13 and  strlen($extrato) > 0)               echo "Recusada";
			elseif ($status_os == 14 and  strlen($extrato) == 0)                                       echo "Acumulada";
			elseif ($status_os == 15 and  strlen($extrato) == 0)                                       echo "Excluída";
//echo "\n\n<!-- $sua_os || $status_os -->\n\n";
			echo "</td>";
			echo "</tr>\n";

			if ($login_fabrica == 19 AND strlen($observacao) > 0 AND strtoupper($observacao) <> "ACEITA" AND strtoupper($observacao) <> "IMPORTADA" AND $status_os == 13) {
				echo "<tr class='Conteudo' bgcolor='$cor'>";
				echo "<td colspan='7'><b>Obs. Fábrica: </b>" . $observacao . "</td>";
				echo "</tr>";
			}elseif (strlen($aprovado) == 0 AND strlen($observacao) > 0 AND $status_os <> 14) {
				echo "<tr class='Conteudo' bgcolor='$cor'>";
				echo "<td colspan='7'><b>Obs. Fábrica: </b>" . $observacao . "</td>";
				echo "</tr>";
			}
		}
		echo "</table>";
		echo "<br>";
/*		echo "<img border='0' src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_relatorio.acao.value == '') { document.frm_relatorio.acao.value='GRAVAR'; document.frm_relatorio.submit(); }else{ alert('Aguarde submissão...'); } \" style='cursor: hand;'>";
		echo "<br><br>";*/
		
		$achou = "sim";
	}else{
		$achou = "nao";
	}
	
	if ($status == "00" OR $status == "15") {
		$sql = "SELECT  tbl_os_excluida.codigo_posto                                        ,
						tbl_os_excluida.sua_os                                              ,
						tbl_os_excluida.referencia_produto                                  ,
						tbl_os_excluida.serie                                               ,
						tbl_os_excluida.nota_fiscal                                         ,
						to_char(tbl_os_excluida.data_nf,'DD/MM/YYYY')       AS data_nf      ,
						to_char(tbl_os_excluida.data_exclusao,'DD/MM/YYYY') AS data_exclusao
				FROM    tbl_os_excluida
				WHERE   tbl_os_excluida.fabrica = $login_fabrica
				AND     tbl_os_excluida.posto   = $login_posto
				ORDER BY tbl_os_excluida.data_exclusao;";
		$res = pg_exec($con,$sql);
		
		if (pg_numrows($res) > 0) {
			echo "<br>";
			
			echo "<input type='hidden' name='qtde_os' value='" . pg_numrows($res) . "'>";
			
			echo "<table width='600' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr class='Titulo'>";
			echo "<td colspan='7'>RELAÇÃO DE OS EXCLUÍDAS</td>";
			echo "</tr>";
			echo "<tr class='Titulo'>";
			echo "<td>OS</td>";
			echo "<td>PRODUTO</td>";
			echo "<td>SÉRIE</td>";
			echo "<td>NOTA FISCAL</td>";
			echo "<td>DATA NF</td>";
			echo "<td>DATA EXCLUSÃO</td>";
			echo "<td>STATUS</td>";
			echo "</tr>";
			
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
				$sua_os         = trim(pg_result($res,$i,sua_os));
				$produto        = trim(pg_result($res,$i,referencia_produto));
				$serie          = trim(pg_result($res,$i,serie));
				$nota_fiscal    = trim(pg_result($res,$i,nota_fiscal));
				$data_nf        = trim(pg_result($res,$i,data_nf));
				$data_exclusao  = trim(pg_result($res,$i,data_exclusao));
				
				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
				
				if ($status == "00" OR $status == "15") {
					$cor = "#FFE1E1";
				}
				
				echo "<tr class='Conteudo' bgcolor='$cor'>";
				echo "<td>";
				if ($login_fabrica == 1) {
					echo $codigo_posto;
				}
				echo $sua_os;
				echo "</td>";
				echo "<td align='center'>" . $produto . "</td>";
				echo "<td align='right'>" . $serie . "</td>";
				echo "<td align='right'>" . $nota_fiscal . "</td>";
				echo "<td align='right'>" . $data_nf . "</td>";
				echo "<td align='center'>" . $data_exclusao . "</td>";
				echo "<td align='center'>Excluída</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<br>";
			$achou = "sim";
		}else{
			$achou = "nao";
		}
	}
	
	if ($achou == "nao") {
		echo "<table border='0' cellpadding='2' cellspacing='0' align='center'>";
		echo "<tr height='50'>";
		echo "<td valign='middle' align='center'><img src='imagens/atencao.gif' border='0'>
			<font size=\"2\"><b>Não foram encontrados registros excluídos com os parâmetros informados/digitados!!!</b></font>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}
	
	##### OS NÃO FINALIZADAS #####
	if ($status == "00" OR $status == "15") {
		$sql =	"SELECT tbl_posto_fabrica.codigo_posto                                   ,
						tbl_os.os                                                        ,
						tbl_os.sua_os                                                    ,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')    AS data_digitacao ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')     AS data_abertura  ,
						tbl_os.pecas                                                     ,
						tbl_os.mao_de_obra                                               ,
						(
							SELECT tbl_os_status.status_os
							FROM tbl_os_status
							WHERE tbl_os_status.os = tbl_os.os
							ORDER BY data DESC LIMIT 1
						)                                              AS status_os      ,
						(
							SELECT tbl_os_status.observacao
							FROM tbl_os_status
							WHERE tbl_os_status.os = tbl_os.os
							ORDER BY data DESC LIMIT 1
						)                                              AS observacao
				FROM tbl_os
				JOIN tbl_os_extra USING (os)
				JOIN tbl_posto    USING (posto)
				JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_os.data_digitacao::date BETWEEN '$x_data_inicial' AND '$x_data_final'
				AND tbl_os.finalizada      ISNULL
				AND tbl_os.data_fechamento ISNULL
				AND tbl_os_extra.extrato   ISNULL
				AND tbl_os.posto   = $login_posto
				AND tbl_os.fabrica = $login_fabrica;";
	//if ($ip == "201.0.9.216") { echo nl2br($sql)."<br>".pg_numrows($res); exit; }
		$res = pg_exec($con,$sql);
		
		if (pg_numrows($res) > 0) {
			echo "<table width='600' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr class='Titulo'>";
			echo "<td colspan='7'>RELAÇÃO DE OS NÃO FINALIZADAS</td>";
			echo "</tr>";
			echo "<tr class='Titulo'>";
			echo "<td>OS</td>";
			echo "<td>DIGITAÇÃO</td>";
			echo "<td>ABERTURA</td>";
			echo "<td>TOTAL</td>";
			echo "<td>PROTOCOLO</td>";
			echo "<td>STATUS</td>";
			echo "</tr>";

			$extrato = '';
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
				$os             = trim(pg_result($res,$i,os));
				$sua_os         = trim(pg_result($res,$i,sua_os));
				$data_digitacao = trim(pg_result($res,$i,data_digitacao));
				$data_abertura  = trim(pg_result($res,$i,data_abertura));
				$pecas          = trim(pg_result($res,$i,pecas));
				$mao_de_obra    = trim(pg_result($res,$i,mao_de_obra));
				$total          = $custo_pecas + $mao_de_obra;
				$status_os       = trim(pg_result($res,$i,status_os));
				$observacao      = trim(pg_result($res,$i,observacao));

				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
				
				if ($status_os == 13) {
					$cor = "#FFE1E1";
					$rowspan = "2";
				}else{
					$rowspan = "1";
				}

				echo "<tr class='Conteudo' bgcolor='$cor'";
				if ($status_os == 14 OR $status_os == 13) echo " onclick=\"javascript: window.location='os_consulta_lite.php?acao=PESQUISAR&opcao5=5&numero_os=$sua_os';\" style='cursor: hand;' TITLE='CLIQUE PARA ACESSAR A OS'";
				echo ">";
				echo "<td rowspan='$rowspan'>";
				if ($login_fabrica == 1) echo $codigo_posto;
				echo $sua_os;
				echo "</td>";
				echo "<td align='center'>" . $data_digitacao . "</td>";
				echo "<td align='center'>" . $data_abertura . "</td>";
				echo "<td align='right'>" . number_format($total,2,",",".") . "</td>";
				echo "<td align='center'>" . $os . "</td>";
				echo "<td align='center'>";
				if     ($status_os == 13 and strlen($extrato) == 0)                                        echo "Recusada";
				elseif ($status_os == 14 and strlen($extrato) == 0)                                        echo "Acumulada";
				elseif ($status_os == 15 and strlen($extrato) == 0)                                        echo "Excluída";
				else                                                                                       echo "Aguardando finalização";
				echo "</td>";
				echo "</tr>";
				
				if (strlen($observacao) > 0 AND $status_os <> 14) {
					echo "<tr class='Conteudo' bgcolor='$cor'";
					if ($status_os == 14 OR $status_os == 13) echo " onclick=\"javascript: window.location='os_consulta_lite.php?acao=PESQUISAR&opcao5=5&numero_os=$sua_os';\" style='cursor: hand;' TITLE='CLIQUE PARA ACESSAR A OS'";
					echo ">";
					echo "<td colspan='6'><b>Obs. Fábrica: </b><a href=\"os_consulta_lite.php?acao=PESQUISAR&opcao5=5&numero_os=$sua_os\">" . $observacao . "</a></td>";
					echo "</tr>";
				}
			}
			echo "</table>";
			echo "<br>";
		}
	}
	##### OS SEDEX FINALIZADAS #####

	$sql = "SELECT tbl_posto_fabrica.codigo_posto                                     ,
					tbl_os_sedex.os_sedex                                              ,
					tbl_os_sedex.sua_os_origem                                         ,
					tbl_os_sedex.sua_os_destino                                        ,
					TO_CHAR(tbl_os_sedex.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					tbl_os_sedex.total_pecas                                           ,
					tbl_os_sedex.despesas                                              ,
					tbl_os_sedex.total                                                 ,
					TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS aprovado         ,
					TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao     
			FROM tbl_os_sedex
			JOIN tbl_posto           on tbl_posto.posto = tbl_os_sedex.posto_origem
			JOIN tbl_posto_fabrica   ON  tbl_posto_fabrica.posto   = tbl_posto.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_extrato    ON tbl_extrato.extrato = tbl_os_sedex.extrato_origem
			WHERE tbl_os_sedex.data_digitacao::date BETWEEN '$x_data_inicial' AND '$x_data_final'
			AND tbl_os_sedex.finalizada   NOTNULL
			AND tbl_os_sedex.posto_origem = $login_posto
			AND tbl_os_sedex.fabrica      = $login_fabrica";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='600' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='7'>RELAÇÃO DE OS SEDEX</td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td>OS SEDEX</td>";
		echo "<td>DIGITAÇÃO</td>";
		echo "<td>PEÇAS</td>";
		echo "<td>DESPESAS</td>";
		echo "<td>TOTAL</td>";
		echo "<td>PROTOCOLO</td>";
		echo "<td>STATUS</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$os_sedex       = trim(pg_result($res,$i,os_sedex));
			$xos_sedex      = "00000".$os_sedex;
			$xos_sedex      = substr($xos_sedex,strlen($xos_sedex) - 5,strlen($xos_sedex));
			$sua_os         = trim(pg_result($res,$i,sua_os_origem));
			$data_digitacao = trim(pg_result($res,$i,data_digitacao));
			$pecas          = trim(pg_result($res,$i,total_pecas));
			$despesas       = trim(pg_result($res,$i,despesas));
			$total          = trim(pg_result($res,$i,total));
			$aprovado       = trim(pg_result($res,$i,aprovado));
			$data_geracao   = trim(pg_result($res,$i,data_geracao));

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

			echo "<tr class='Conteudo' bgcolor='$cor'";
			if ($status_os == 14 OR $status_os == 13) echo " onclick=\"javascript: window.location='os_consulta_lite.php?acao=PESQUISAR&opcao5=5&numero_os=$sua_os';\" style='cursor: hand;'";
			echo ">";
			echo "<td>";
			if ($login_fabrica == 1) {
				echo $codigo_posto;
			}
			echo $xos_sedex;
			echo "</td>";
			echo "<td align='center'>" . $data_digitacao . "</td>";
			echo "<td align='right'>" . number_format($pecas,2,",",".") . "</td>";
			echo "<td align='right'>" . number_format($despesas,2,",",".") . "</td>";
			echo "<td align='right'>" . number_format($total,2,",",".") . "</td>";
			echo "<td align='center'>" . $os . "</td>";
			echo "<td align='center'>";

			if (strlen($data_geracao) > 0 AND strlen($aprovado) == 0 AND strlen($status_os) == 0)      echo "Em aprovação";
			elseif (strlen($data_geracao) == 0 AND strlen($aprovado) == 0 AND strlen($status_os) == 0) echo "Em aprovação";
			elseif (strlen($aprovado) > 0)                                                             echo "Aprovada";
			elseif ($status_os == 13)                                                                  echo "Recusada";
			elseif ($status_os == 14)                                                                  echo "Acumulada";
			elseif ($status_os == 15)                                                                  echo "Excluída:<br><font color='#FF0000'>$observacao</font>";
			echo "</td>";
			echo "</tr>";
			
			if ($status_os == 13) {
				echo "<tr class='Conteudo' bgcolor='$cor'>";
				echo "<td colspan='6'><b>Obs. Fábrica: </b>" . $observacao . "</td>";
				echo "</tr>";
/*				echo "<tr class='Conteudo' bgcolor='$cor'>";
				echo "<td colspan='6'><b>Obs. Posto: </b>";
				echo "<input type='hidden' name='os_status_$i' value='$os_status'>";
				echo "<input type='text' name='obs_$i' value='$obs' size='60'>";
				echo "</td>";
				echo "</tr>";*/
			}
		}
		echo "</table>";
		echo "<br>";
/*		echo "<img border='0' src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_relatorio.acao.value == '') { document.frm_relatorio.acao.value='GRAVAR'; document.frm_relatorio.submit(); }else{ alert('Aguarde submissão...'); } \" style='cursor: hand;'>";
		echo "<br><br>";*/
	}


	##### OS SEDEX NÃO FINALIZADAS #####

	$sql = "SELECT tbl_posto_fabrica.codigo_posto                                     ,
					tbl_os_sedex.os_sedex                                              ,
					tbl_os_sedex.sua_os_origem                                         ,
					tbl_os_sedex.sua_os_destino                                        ,
					TO_CHAR(tbl_os_sedex.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					tbl_os_sedex.total_pecas                                           ,
					tbl_os_sedex.despesas                                              ,
					tbl_os_sedex.total                                                 ,
					TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS aprovado         ,
					TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao     
			FROM tbl_os_sedex
			JOIN tbl_posto           on tbl_posto.posto = tbl_os_sedex.posto_origem
			JOIN tbl_posto_fabrica   ON  tbl_posto_fabrica.posto   = tbl_posto.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_extrato    ON tbl_extrato.extrato = tbl_os_sedex.extrato_origem
			WHERE tbl_os_sedex.data_digitacao::date BETWEEN '$x_data_inicial' AND '$x_data_final'
			AND tbl_os_sedex.finalizada   ISNULL
			AND tbl_os_sedex.posto_origem = $login_posto
			AND tbl_os_sedex.fabrica      = $login_fabrica";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='600' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='7'>RELAÇÃO DE OS SEDEX NÃO FINALIZADAS</td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td>OS SEDEX</td>";
		echo "<td>DIGITAÇÃO</td>";
		echo "<td>PEÇAS</td>";
		echo "<td>DESPESAS</td>";
		echo "<td>TOTAL</td>";
		echo "<td>PROTOCOLO</td>";
		echo "<td>STATUS</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$os_sedex       = trim(pg_result($res,$i,os_sedex));
			$xos_sedex      = "00000".$os_sedex;
			$xos_sedex      = substr($xos_sedex,strlen($xos_sedex) - 5,strlen($xos_sedex));
			$sua_os         = trim(pg_result($res,$i,sua_os_origem));
			$data_digitacao = trim(pg_result($res,$i,data_digitacao));
			$pecas          = trim(pg_result($res,$i,total_pecas));
			$despesas       = trim(pg_result($res,$i,despesas));
			$total          = trim(pg_result($res,$i,total));
			$aprovado       = trim(pg_result($res,$i,aprovado));
			$data_geracao   = trim(pg_result($res,$i,data_geracao));
			
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			
			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td>";
			if ($login_fabrica == 1) echo $codigo_posto;
			echo $xos_sedex;
			echo "</td>";
//			echo "<td rowspan='$rowspan'>" . $codigo_posto . $xos_sedex . "</td>";
			echo "<td align='center'>" . $data_digitacao . "</td>";
			echo "<td align='right'>" . number_format($pecas,2,",",".") . "</td>";
			echo "<td align='right'>" . number_format($despesas,2,",",".") . "</td>";
			echo "<td align='right'>" . number_format($total,2,",",".") . "</td>";
			echo "<td align='center'>" . $os . "</td>";
			echo "<td align='center'>Não finalizada</td>";
			echo "</tr>";
			
		}
		echo "</table>";
		echo "<br>";
/*		echo "<img border='0' src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_relatorio.acao.value == '') { document.frm_relatorio.acao.value='GRAVAR'; document.frm_relatorio.submit(); }else{ alert('Aguarde submissão...'); } \" style='cursor: hand;'>";
		echo "<br><br>";*/
	}


}

include "rodape.php";
?>
