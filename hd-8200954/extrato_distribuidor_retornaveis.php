<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_e_distribuidor <> 't') {
	header ("Location: new_extrato_posto.php");
	exit;
}

$msg_erro = "";

$layout_menu = "os";
$title = "Peças Retornáveis do Extrato";

include "cabecalho.php";

?>

<script language="JavaScript">
function fnc_consulta_os(data_extrato,peca) {
	window.open("new_extrato_distribuidor_retornaveis_os.php?data="+data_extrato+"&peca="+peca, "Pesquisa", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=637, height=400, top=50, left=50");
}
</script>

<p>
<center>
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
<td align='center' width='50%'><a href='new_extrato_distribuidor.php?periodo=<? echo $data ?>'>Ver extrato total</a></td>
<td align='center' width='50%'><a href='new_extrato_distribuidor.php'>Ver outro extrato</a></td>
</tr>
</table>


<br>
<font face='arial' size='+1' color='#330066'>As seguintes peças devem ser coletadas dos seus postos, 
<br>e enviadas para a Fábrica</font>
<br>
<br>
<font face='arial' size='2' color='#330066'>Para ver a OS relacionada a cada peça, clique no código da peça.</font>

<p>

<?

if (strlen ($data) > 0) {
	$sql = "SELECT  tbl_os_extra.linha           ,
					tbl_linha.nome AS linha_nome ,
					tbl_peca.peca                ,
					tbl_peca.referencia          ,
					tbl_peca.descricao           ,
					SUM (tbl_os_item.qtde) AS qtde 
			FROM tbl_os 
			JOIN (
				SELECT tbl_os_extra.os
				FROM tbl_os_extra 
				JOIN (
					SELECT tbl_extrato.extrato
					FROM   tbl_extrato
					WHERE  tbl_extrato.fabrica = $login_fabrica 
					AND    (tbl_extrato.posto = $login_posto OR tbl_extrato.posto IN (SELECT posto FROM tbl_posto_linha WHERE distribuidor = $login_posto))
					AND    tbl_extrato.data_geracao BETWEEN '$data_inicio' AND '$data_final' 
					AND    tbl_extrato.aprovado IS NOT NULL 
				) extrato ON tbl_os_extra.extrato = extrato.extrato
				AND tbl_os_extra.linha IN (SELECT DISTINCT linha FROM tbl_posto_linha WHERE tbl_posto_linha.distribuidor = $login_posto)
				) os ON tbl_os.os = os.os
			JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
			JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
			JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN tbl_linha ON tbl_os_extra.linha = tbl_linha.linha
			JOIN (SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao FROM tbl_peca WHERE tbl_peca.fabrica = $login_fabrica AND (devolucao_obrigatoria OR aguarda_inspecao)) tbl_peca ON tbl_os_item.peca = tbl_peca.peca
			JOIN (SELECT servico_realizado FROM tbl_servico_realizado WHERE troca_de_peca AND gera_pedido) sr ON tbl_os_item.servico_realizado = sr.servico_realizado 
			WHERE tbl_os.excluida IS NOT TRUE
			GROUP BY tbl_os_extra.linha , tbl_linha.nome, tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao 
			ORDER BY tbl_linha.nome, tbl_peca.descricao ";
#echo $sql;
#exit;

	$res = pg_exec ($con,$sql);

	$linha_ant = "";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		if ($linha_ant <> pg_result ($res,$i,linha) ) {
			if (strlen ($linha_ant) > 0) {
				echo "</table>";
				flush();
			}

			echo "<table width='450' align='center' border='0' cellspacing='3'>\n";
			echo "<tr bgcolor='#336600'>\n";
			echo "<td colspan='3' align='center'><font size='+1' color='#ffffff'>Peças da Linha: " . pg_result ($res,$i,linha_nome) . "</font>";
			if (pg_result ($res,$i,linha) == 2 OR pg_result ($res,$i,linha) == 4 ) {
				echo "<br><font size='0' color='#FFCC00'><b>Estas peças não precisam retornar, mas devem ficar à disposição para inspeção do fabricante por 90 dias;<br>Ou seguir critério determinado por seu distribuidor.</b></font>";
			}
			echo "</td>\n";
			echo "</tr>\n";

			echo "<tr bgcolor='#336600' style='color:#ffffff ; text-align:center ' >\n";
			echo "<td>Peça</td>\n";
			echo "<td>Descrição</td>\n";
			echo "<td>Qtde</td>\n";
			echo "</tr>\n";

			$linha_ant = pg_result ($res,$i,linha);

		}

		$cor = '#ffffff';
		if ($i % 2 == 0) $cor = '#eeeeee';
		echo "<FORM name='frm_extrato'>\n";
		echo "<tr bgcolor='$cor' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
		echo "<td><a href=\"javascript: fnc_consulta_os ('$data','".pg_result ($res,$i,peca)."')\">" . pg_result ($res,$i,referencia) . "</a></td>\n";
		echo "<td>" . pg_result ($res,$i,descricao) . "</td>\n";
		echo "<td align='right'>" . pg_result ($res,$i,qtde) . "</td>\n";
		echo "</tr>\n";
		echo "</FORM>\n";

	}

	echo "</table>\n";

}

?>

<p><p>

<? include "rodape.php"; ?>