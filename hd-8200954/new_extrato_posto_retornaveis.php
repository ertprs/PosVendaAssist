<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";

$layout_menu = "os";
$title = "Peças Retornáveis do Extrato";

include "cabecalho.php";

?>

<p>
<center>
<?
$extrato = trim($_GET['extrato']);

$sql = "SELECT  to_char (data_geracao,'DD/MM/YYYY') AS data ,
				to_char (data_geracao,'YYYY-MM-DD') AS periodo ,
				tbl_posto.nome ,
				tbl_posto_fabrica.codigo_posto
		FROM tbl_extrato
		JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_extrato.extrato = $extrato ";
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
<table width='550' align='center' border='0' style='font-size:12px'>
<tr>
<td align='center' width='33%'><a href='new_extrato_posto.php?extrato=<? echo $extrato ?>'>Ver extrato total</a></td>
<td align='center' width='33%'><a href='new_extrato_posto.php'>Ver outro extrato</a></td>
</tr>
</table>


<br>
<font face='arial' size='+1' color='#330066'>As seguintes peças devem ser devolvidas para o fabricante 
<br>ou para o distribuidor, conforme orientação.</font>

<p>

<?

if (strlen ($extrato) > 0) {
	$sql = "SELECT  tbl_os_extra.linha , 
					tbl_linha.nome AS linha_nome , 
					tbl_peca.referencia , tbl_peca.descricao , 
					SUM (tbl_os_item.qtde) AS qtde 
			FROM tbl_os 
			JOIN (SELECT tbl_os_extra.os FROM tbl_os_extra WHERE extrato = $extrato) os ON tbl_os.os = os.os
			JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
			JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
			JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN tbl_linha ON tbl_os_extra.linha = tbl_linha.linha
			JOIN (SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao FROM tbl_peca WHERE tbl_peca.fabrica = 3 AND (devolucao_obrigatoria OR aguarda_inspecao)) tbl_peca ON tbl_os_item.peca = tbl_peca.peca
			JOIN (SELECT servico_realizado FROM tbl_servico_realizado WHERE troca_de_peca AND gera_pedido) sr ON tbl_os_item.servico_realizado = sr.servico_realizado 
			WHERE tbl_os.excluida IS NOT TRUE
			GROUP BY tbl_os_extra.linha , tbl_linha.nome, tbl_peca.referencia, tbl_peca.descricao 
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

			echo "<table width='450' align='center' border='0' cellspacing='3'>";
			echo "<tr bgcolor='#336600'>";
			echo "<td colspan='3' align='center'><font size='+1' color='#ffffff'>Peças da Linha: " . pg_result ($res,$i,linha_nome) . "</font>";
			if (pg_result ($res,$i,linha) == 2 OR pg_result ($res,$i,linha) == 4 ) {
				echo "<br><font size='0' color='#FFCC00'><b>Estas peças não precisam retornar, mas devem ficar à disposição para inspeção do fabricante por 90 dias;<br>Ou seguir critério determinado por seu distribuidor.</b></font>";
			}
			echo "</td>";
			echo "</tr>";

			echo "<tr bgcolor='#336600' style='color:#ffffff ; text-align:center ' >";
			echo "<td>Peça</td>";
			echo "<td>Descrição</td>";
			echo "<td>Qtde</td>";
			echo "</tr>";

			$linha_ant = pg_result ($res,$i,linha);

		}

		$cor = '#ffffff';
		if ($i % 2 == 0) $cor = '#eeeeee';

		echo "<tr bgcolor='$cor' style='font-color:#000000 ; align:left ; font-size:10px ' >";
		echo "<td>" . pg_result ($res,$i,referencia) . "</td>";
		echo "<td>" . pg_result ($res,$i,descricao) . "</td>";
		echo "<td align='right'>" . pg_result ($res,$i,qtde) . "</td>";
		echo "</tr>";

	}

	echo "</table>";

}

?>

<p><p>

<? include "rodape.php"; ?>