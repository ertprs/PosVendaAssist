<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

if ($login_fabrica <> 1) {
	header ("Location: os_extrato.php");
	exit;
}

$msg_erro = "";

$layout_menu = "os";
$title = "Extratos";

include "cabecalho.php";
?>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<?
$sql =	"SELECT DISTINCT
				tbl_extrato.extrato                                            ,
				tbl_extrato.protocolo                                          ,
				tbl_extrato.data_geracao                       AS ordem        ,
				TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao ,
				tbl_extrato.mao_de_obra                                        ,
				tbl_extrato.mao_de_obra_postos                                 ,
				tbl_extrato.pecas                                              ,
				tbl_extrato.total                                              ,
				tbl_extrato.aprovado                                           ,
				tbl_extrato.posto                                              ,
				tbl_posto_fabrica.codigo_posto                                 ,
				tbl_posto.nome                                                 ,
				tbl_extrato_financeiro.data_envio                              ,
				tbl_extrato_status.obs                                         ,
				tbl_os_status.status_os
		FROM      tbl_extrato
		JOIN      tbl_posto              ON tbl_posto.posto                = tbl_extrato.posto
		JOIN      tbl_posto_fabrica      ON tbl_posto_fabrica.posto        = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica      = $login_fabrica
		LEFT JOIN tbl_os_status          ON tbl_os_status.extrato          = tbl_extrato.extrato
		LEFT JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato
		LEFT JOIN tbl_extrato_status     ON tbl_extrato_status.extrato     = tbl_extrato.extrato
		WHERE tbl_extrato.fabrica = $login_fabrica
		AND   tbl_extrato.posto   = $login_posto
		AND   tbl_extrato.aprovado NOTNULL
		GROUP BY tbl_extrato.extrato               ,
				 tbl_extrato.protocolo             ,
				 tbl_extrato.data_geracao          ,
				 tbl_extrato.mao_de_obra           ,
				 tbl_extrato.mao_de_obra_postos    ,
				 tbl_extrato.pecas                 ,
				 tbl_extrato.total                 ,
				 tbl_extrato.aprovado              ,
				 tbl_extrato.posto                 ,
				 tbl_posto_fabrica.codigo_posto    ,
				 tbl_posto.nome                    ,
				 tbl_extrato_financeiro.data_envio ,
				 tbl_extrato_status.obs            ,
				 tbl_os_status.status_os
		ORDER BY ordem DESC";
$res = pg_exec($con,$sql);

// echo nl2br($sql) . "<br>" . pg_numrows($res);

echo "<table width='700' height='16' border='0' cellspacing='0' cellpadding='0' align='center'>";
echo "<tr>";
echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
echo "<td align='left'><font size=1><b>&nbsp; Extrato Avulso</b></font></td>";
echo "</tr>";
echo "</table>";
echo "<br>";

		echo "<h3><center><b>Obs.: Após o envio do extrato ao financeiro, o prazo para pagamento é de aproximadamente 15 dias.</b></center></h3>";

echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
if (pg_numrows($res) > 0) {
	echo "<tr class='menu_top'>\n";
	echo "<td>EXTRATO</td>\n";
	echo "<td>POSTO</td>\n";
	echo "<td>DATA GERAÇÃO</td>\n";
	echo "<td>TOTAL</td>\n";
	echo "<td>TOTAL + AVULSO</td>\n";
	echo "<td>STATUS</td>\n";
	echo "<td>AÇÕES</td>\n";
	echo "</tr>\n";

	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$xmao_de_obra       = 0;
		$posto              = trim(pg_result($res,$i,posto));
		$posto_codigo       = trim(pg_result($res,$i,codigo_posto));
		$posto_nome         = trim(pg_result($res,$i,nome));
		$extrato            = trim(pg_result($res,$i,extrato));
		$data_geracao       = trim(pg_result($res,$i,data_geracao));
		$mao_de_obra        = trim(pg_result($res,$i,mao_de_obra));
		$mao_de_obra_postos = trim(pg_result($res,$i,mao_de_obra_postos));
		$pecas              = trim(pg_result($res,$i,pecas));
		$extrato            = trim(pg_result($res,$i,extrato));
		$total_avulso       = trim(pg_result($res,$i,total));
		$protocolo          = trim(pg_result($res,$i,protocolo));
		$data_envio         = trim(pg_result($res,$i,data_envio));
		$obs                = trim(pg_result($res,$i,obs));
		$aprovado           = trim(pg_result($res,$i,aprovado));
		$status_os          = trim(pg_result($res,$i,status_os));

		if (strlen($aprovado) > 0 AND strlen($data_envio) == 0 AND strlen($status_os) == 0) $status = "Aguardando documentação";
		if (strlen($aprovado) > 0 AND strlen($data_envio)  > 0 AND strlen($status_os) == 0) $status = "Enviado para o financeiro";
		//if (strlen($aprovado) > 0 AND strlen($data_envio) == 0 AND strlen($status_os)  > 0) $status = "Pendente";
		if (strlen($aprovado) > 0 AND strlen($data_envio) == 0 AND strlen($status_os)  > 0) $status = "Aguardando documentação";

		# soma valores
		$xmao_de_obra += $mao_de_obra_postos;
		$xvrmao_obra   = $mao_de_obra_postos;

		if ($xvrmao_obra == 0)  $xvrmao_obra   = $mao_de_obra;
		if ($xmao_de_obra == 0) $xmao_de_obra += $mao_de_obra;

		$total = $xmao_de_obra + $pecas;

		if ($i % 2 == 0) {
			$cor = "#F1F4FA";
			$btn = "azul";
		}else{
			$cor = "#F7F5F0";
			$btn = "amarelo";
		}

		##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
		if (strlen($extrato) > 0) {
			$sql = "SELECT COUNT(*) AS existe
					FROM tbl_extrato_lancamento
					WHERE extrato = $extrato
					AND   posto   = $login_posto
					AND   fabrica = $login_fabrica";
			$res_avulso = pg_exec($con,$sql);
			if (@pg_numrows($res_avulso) > 0) {
				if (@pg_result($res_avulso,0,existe) > 0) $cor = "#FFE1E1";
			}
		}
		##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####

		echo "<tr class='table_line' style='background-color: $cor;'>\n";
		echo "<td align='center'>$protocolo</td>\n";
		echo "<td nowrap><acronym title='POSTO: $posto_codigo\nRAZÃO SOCIAL: $posto_nome' style='cursor: help;'>$posto_codigo - " . substr($posto_nome,0,20) . "</acronym></td>\n";
		echo "<td align='center'>$data_geracao</td>\n";
		echo "<td align='right' nowrap> R$ ". number_format($total,2,",",".") ."</td>\n";
		echo "<td align='right' nowrap> R$ ". number_format($total_avulso,2,",",".") ."</td>\n";
		echo "<td align='center' nowrap>$status</td>\n";
		echo "<td><img src='imagens/btn_imprimir.gif' onclick=\"javascript: janela=window.open('os_extrato_detalhe_print_blackedecker.php?extrato=$extrato','extrato');\" ALT=\"Imprimir detalhado\" border='0' style=\"cursor:pointer;\"></td>\n";
		echo "</tr>\n";

		if (strlen($obs) > 0) {
			echo "<tr class='table_line' style='background-color: $cor;'>\n";
			echo "<td nowrap colspan='7'><b>OBS.:</b> $obs</td>\n";
			echo "</tr>\n";
		}
	}
}else{
	echo "<tr class='table_line'>\n";
	echo "<td align='center'>NENHUM EXTRATO FOI ENCONTRADO</td>\n";
	echo "</tr>\n";
}
echo "</table>\n";

echo "<br>";

include "rodape.php";
?>
