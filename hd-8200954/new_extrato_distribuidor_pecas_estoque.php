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
$title = "Peças do Estoque Próprio";

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
<font face='arial' size='+1' color='#330066'>As seguintes peças foram utilizadas do Estoque próprio do Posto Autorizado.
<br>

<p>

<?

if (strlen ($data) > 0) {
	$sql = "SELECT tbl_posto_fabrica.codigo_posto ,
	               tbl_posto.nome                 ,
				   tbl_peca.referencia            ,
				   tbl_os.sua_os                  ,
				   tbl_os.os                      ,
				   tbl_os_extra.extrato           ,
				   tbl_peca.descricao             ,
				   tbl_os_item.qtde               ,
				   tbl_tabela_item.preco
			FROM   tbl_os
			JOIN   tbl_posto ON tbl_os.posto = tbl_posto.posto
			JOIN   tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
			JOIN   tbl_os_extra ON tbl_os.os = tbl_os_extra.os
			JOIN   tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
			JOIN   tbl_os_produto ON tbl_os.os = tbl_os_produto.os
			JOIN   tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN   tbl_peca       ON tbl_os_item.peca = tbl_peca.peca
			JOIN   tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
			LEFT JOIN tbl_tabela_item    ON tbl_os.tabela = tbl_tabela_item.tabela AND tbl_os_item.peca = tbl_tabela_item.peca
			WHERE  tbl_extrato.data_geracao BETWEEN '$data_inicio' AND '$data_final' 
			AND    tbl_extrato.aprovado IS NOT NULL 
			AND    tbl_extrato.fabrica = $login_fabrica
			AND    (tbl_extrato.posto = $login_posto OR tbl_extrato.posto IN (SELECT posto FROM tbl_posto_linha WHERE distribuidor = $login_posto) )
			AND    tbl_os_item.servico_realizado = 43
			ORDER BY tbl_posto.nome , tbl_os.sua_os";
			
#echo $sql;
#exit;

	$res = pg_exec ($con,$sql);

	$posto_ant = "";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		if ($posto_ant <> pg_result ($res,$i,codigo_posto) ) {
			if (strlen ($posto_ant) > 0) {
				echo "</table>";
				flush();
			}

			echo "<table width='550' align='center' border='0' cellspacing='3'>\n";
			echo "<tr bgcolor='#0066CC'>\n";
			echo "<td colspan='6' align='center'><font size='+1' color='#ffffff'>Posto " . pg_result ($res,$i,codigo_posto) . " - " . pg_result ($res,$i,nome) . "</font>";
			echo "</td>\n";
			echo "</tr>\n";

			echo "<tr bgcolor='#336600' style='color:#ffffff ; text-align:center ' >\n";
			echo "<td>OS</td>\n";
			echo "<td>Extrato</td>\n";
			echo "<td>Peça</td>\n";
			echo "<td>Descrição</td>\n";
			echo "<td>Qtde</td>\n";
			echo "<td>Valor</td>\n";
			echo "</tr>\n";

			$posto_ant = pg_result ($res,$i,codigo_posto);

		}

		$cor = '#ffffff';
		if ($i % 2 == 0) $cor = '#eeeeee';
#		echo "<FORM name='frm_extrato'>\n";
#		echo "<tr bgcolor='$cor' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
#		echo "<td><a href=\"javascript: fnc_consulta_os ('$data','".pg_result ($res,$i,peca)."')\">" . pg_result ($res,$i,referencia) . "</a></td>\n";
#		echo "<td>" . pg_result ($res,$i,descricao) . "</td>\n";
#		echo "<td align='right'>" . pg_result ($res,$i,qtde) . "</td>\n";
#		echo "</tr>\n";
#		echo "</FORM>\n";

		echo "<tr bgcolor='$cor' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
		echo "<td>" . pg_result ($res,$i,sua_os) . "</td>\n";
		echo "<td>" . pg_result ($res,$i,extrato) . "</td>\n";
		echo "<td>" . pg_result ($res,$i,referencia) . "</td>\n";
		echo "<td>" . pg_result ($res,$i,descricao) . "</td>\n";
		echo "<td>" . pg_result ($res,$i,qtde) . "</td>\n";
		echo "<td>" . pg_result ($res,$i,preco) . "</td>\n";
		echo "</tr>\n";

	}

	echo "</table>\n";

}

?>

<p><p>

<? include "rodape.php"; ?>