<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

if($login_fabrica <> 1) {
	include("menu_os.php");
	exit;
}

include "funcoes.php";

$erro = "";

if (strlen($_POST["acao"]) > 0 ) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0 )  $acao = strtoupper($_GET["acao"]);

/*
if (strlen($acao) > 0 && $acao == "GRAVAR") {
	$qtde_os = $_POST["qtde_os"];
	if ($qtde_os > 0) {
		$res = pg_exec($con,"BEGIN TRANSACTION");
		for ($i = 0 ; $i < $qtde_os ; $i++) {
			$os  = trim($_POST["os_status_" . $i]);
			$obs = trim($_POST["obs_" . $i]);
			if (strlen($os) > 0 && strlen($obs) > 0) {
				$sql = 	"INSERT INTO tbl_os_status (
							os        ,
							obs       ,
							status_os
						) VALUES (
							$os    ,
							'$obs' ,
							13
						);";
				$res = @pg_exec($con,$sql);
				$erro .= pg_errormessage($con);
			}
		}
		if (strlen($erro) == 0) {
			$res = pg_exec($con,"COMMIT TRANSACTION");
			$link_status = $_COOKIE["LinkStatus"];
			header ("Location: $link_status");
			exit;
		}else{
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
		}
	}
}
*/

if (strlen($acao) > 0 && $acao == "PESQUISAR") {
	if (strlen(trim($_POST["data_inicial"])) > 0) $x_data_inicial = trim($_POST["data_inicial"]);
	if (strlen(trim($_GET["data_inicial"])) > 0) $x_data_inicial = trim($_GET["data_inicial"]);
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
		$x_data_final = str_replace("'", "", $x_data_final);
		$dia_final = substr($x_data_final, 8, 2);
		$mes_final = substr($x_data_final, 5, 2);
		$ano_final = substr($x_data_final, 0, 4);
		$data_final = $dia_final . "/" . $mes_final . "/" . $ano_final;
	}else{
		$erro .= " Informe a Data Inicial para realizar a pesquisa. ";
	}
	
	$link_status = "http://" . $HTTP_HOST . $REQUEST_URI . "?data_inicial=" . $_POST["data_inicial"] . "&data_final=" . $_POST["data_final"] . "&acao=PESQUISAR";
	setcookie("LinkStatus", $link_status);
}

$layout_menu = "os";
$title = "Relação de Status da Ordem de Serviço";

include "cabecalho.php";
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
	<tr bgcolor="#D9E2EF">
		<td colspan="4"><img border="0" src="imagens/btn_pesquisar_400.gif" onClick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

<br>

<?
if (strlen($acao) > 0 && strlen($erro) == 0) {

	##### OS FINALIZADAS #####

	$sql =	"SELECT tbl_posto_fabrica.codigo_posto                                   ,
					tbl_os.os                                                        ,
					tbl_os.sua_os                                                    ,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')    AS data_digitacao ,
					tbl_os.pecas                                                     ,
					tbl_os.mao_de_obra                                               ,
					TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS aprovado       ,
					TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao   ,
					tbl_os_status.os                               AS os_status      ,
					tbl_os_status.observacao
			FROM tbl_os
			JOIN tbl_os_extra USING (os)
			JOIN tbl_posto    USING (posto)
			JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
			LEFT JOIN tbl_os_status ON  tbl_os.os               = tbl_os_status.os
			                        AND tbl_os_status.status_os = 13
			WHERE tbl_os.data_digitacao::date BETWEEN '$x_data_inicial' AND '$x_data_final'
			AND tbl_os.finalizada NOTNULL
			AND tbl_os.posto   = $login_posto
			AND tbl_os.fabrica = $login_fabrica;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='600' border='0' cellspacing='2' cellpadding='0' align='center'>";
		echo "<tr>";
		echo "<td align='center' width='18' height='18' bgcolor='#FFE1E1'>&nbsp;</td>";
		echo "<td align='left'><font size=1>&nbsp; OS recusada pelo fabricante</font></td>";
		echo "</tr>";
		echo "</table>";
		echo "<br>";
		
		echo "<input type='hidden' name='qtde_os' value='" . pg_numrows($res) . "'>";
		
		echo "<table width='600' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='7'>RELAÇÃO DE OS</td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td>OS</td>";
		echo "<td>DIGITAÇÃO</td>";
		echo "<td>PEÇAS</td>";
		echo "<td>MÃO-DE-OBRA</td>";
		echo "<td>TOTAL</td>";
		echo "<td>PROTOCOLO</td>";
		echo "<td>STATUS</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$os             = trim(pg_result($res,$i,os));
			$sua_os         = trim(pg_result($res,$i,sua_os));
			$data_digitacao = trim(pg_result($res,$i,data_digitacao));
			$pecas          = trim(pg_result($res,$i,pecas));
			$mao_de_obra    = trim(pg_result($res,$i,mao_de_obra));
			$total          = $custo_pecas + $mao_de_obra;
			$aprovado       = trim(pg_result($res,$i,aprovado));
			$data_geracao   = trim(pg_result($res,$i,data_geracao));
			$os_status      = trim(pg_result($res,$i,os_status));
			$observacao     = trim(pg_result($res,$i,observacao));

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			
			if (strlen($os_status) > 0) {
				$cor = "#FFE1E1";
				$rowspan = "2";
			}else{
				$rowspan = "1";
			}
			
			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td rowspan='$rowspan'>" . $codigo_posto . $sua_os . "</td>";
			echo "<td align='center'>" . $data_digitacao . "</td>";
			echo "<td align='right'>" . number_format($pecas,2,",",".") . "</td>";
			echo "<td align='right'>" . number_format($mao_de_obra,2,",",".") . "</td>";
			echo "<td align='right'>" . number_format($total,2,",",".") . "</td>";
			echo "<td align='center'>" . $os . "</td>";
			echo "<td align='center'>";
			if (strlen($data_geracao) > 0 AND strlen($aprovado) == 0 AND strlen($os_status) == 0)      echo "Em aprovação";
			elseif (strlen($data_geracao) == 0 AND strlen($aprovado) == 0 AND strlen($os_status) == 0) echo "Em aprovação";
			elseif (strlen($aprovado) > 0)                                                             echo "Aprovada";
			elseif (strlen($os_status) > 0)                                                            echo "Recusado";
			echo "</td>";
			echo "</tr>";
			
			if (strlen($os_status) > 0) {
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

	##### OS NÃO FINALIZADAS #####

	$sql =	"SELECT tbl_posto_fabrica.codigo_posto                                   ,
					tbl_os.os                                                        ,
					tbl_os.sua_os                                                    ,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')    AS data_digitacao ,
					tbl_os.pecas                                                     ,
					tbl_os.mao_de_obra                                               ,
					TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS aprovado       ,
					TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao
			FROM tbl_os
			JOIN tbl_os_extra USING (os)
			JOIN tbl_posto    USING (posto)
			JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
			WHERE tbl_os.data_digitacao::date BETWEEN '$x_data_inicial' AND '$x_data_final'
			AND tbl_os.finalizada ISNULL
			AND tbl_os.posto   = $login_posto
			AND tbl_os.fabrica = $login_fabrica;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='600' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='7'>RELAÇÃO DE OS NÃO FINALIZADAS</td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td>OS</td>";
		echo "<td>DIGITAÇÃO</td>";
		echo "<td>PEÇAS</td>";
		echo "<td>MÃO-DE-OBRA</td>";
		echo "<td>TOTAL</td>";
		echo "<td>PROTOCOLO</td>";
		echo "<td>STATUS</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$os             = trim(pg_result($res,$i,os));
			$sua_os         = trim(pg_result($res,$i,sua_os));
			$data_digitacao = trim(pg_result($res,$i,data_digitacao));
			$pecas    = trim(pg_result($res,$i,pecas));
			$mao_de_obra    = trim(pg_result($res,$i,mao_de_obra));
			$total          = $custo_pecas + $mao_de_obra;
			$aprovado       = trim(pg_result($res,$i,aprovado));
			$data_geracao   = trim(pg_result($res,$i,data_geracao));

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td>" . $codigo_posto . $sua_os . "</td>";
			echo "<td align='center'>" . $data_digitacao . "</td>";
			echo "<td align='right'>" . number_format($pecas,2,",",".") . "</td>";
			echo "<td align='right'>" . number_format($mao_de_obra,2,",",".") . "</td>";
			echo "<td align='right'>" . number_format($total,2,",",".") . "</td>";
			echo "<td align='center'>" . $os . "</td>";
			echo "<td align='center'>";
			echo "Não finalizada";
			//if (strlen($aprovado) == 0 && strlen($data_geracao) == 0) echo "Em aprovação";
			//else                                                      echo "Aprovada";
			echo "</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";
	}
}

include "rodape.php";
?>
