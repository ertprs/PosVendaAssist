<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";
include "autentica_admin.php";

$data_extrato = trim($_GET["data"]);
$peca         = trim($_GET["peca"]);


$msg_erro = "";

$layout_menu = "financeiro";
$title = "Consulta e Manutenção de Extratos do Posto";

include "cabecalho.php";


if (strlen($data_extrato) > 0 AND strlen($peca) > 0 AND strlen($login_fabrica) > 0) {
	$sql = "SELECT	tbl_os.sua_os                       ,
					tbl_os_produto.produto              ,
					tbl_produto.descricao               ,
					tbl_posto.nome                      ,
					tbl_posto_fabrica.codigo_posto      ,
					tbl_peca.descricao AS peca_descricao
			FROM	tbl_os
			JOIN	tbl_os_produto    ON tbl_os_produto.os       = tbl_os.os
			JOIN	tbl_produto       ON tbl_produto.produto     = tbl_os_produto.produto
			JOIN	tbl_os_item       ON tbl_os_item.os_produto  = tbl_os_produto.os_produto
			JOIN	tbl_os_extra      ON tbl_os_extra.os         = tbl_os.os
			JOIN	tbl_posto         ON tbl_posto.posto         = tbl_os.posto
			JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN	tbl_extrato       ON tbl_extrato.extrato     = tbl_os_extra.extrato AND tbl_extrato.fabrica  = $login_fabrica
			JOIN	tbl_peca          ON tbl_peca.peca           = tbl_os_item.peca AND tbl_peca.peca = $peca AND tbl_peca.fabrica = $login_fabrica
			JOIN	(SELECT servico_realizado FROM tbl_servico_realizado WHERE troca_de_peca AND gera_pedido) sr ON tbl_os_item.servico_realizado = sr.servico_realizado 
			WHERE	tbl_extrato.data_geracao BETWEEN '$data_extrato 00:00:00' AND '$data_extrato 23:59:59'
			AND		tbl_extrato.aprovado IS NOT NULL 
			AND		tbl_os.excluida IS NOT TRUE;";
	$res = pg_exec ($con,$sql);

	echo "<p align='center'><font face='Verdana, Tahoma, Arial' size='2'><b>Consulta Ordem de Serviço pela peça:<br>";
	echo " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <i>".pg_result($res,0,peca_descricao)."</i></b></font></p>";

	if (pg_numrows($res) == 0) {
		echo "<center><h1>Não foi encontrada nenhuma OS.</h1></center>";
		echo "<script language='javascript'>";
		//echo "setTimeout('window.close()',2500);";
		echo "</script>";
	}else{
		echo "<table width='600' align='center' border='0' cellspacing='3'>";
		echo "<tr bgcolor='#336600' style='color:#ffffff ; text-align:center; font-family: Verdana'>";
		echo "<td>OS</td>";
		echo "<td>Código - Produto</td>";
		echo "<td>Código - Posto</td>";
		echo "</tr>";
		$cor = '#ffffff';
		if ($i % 2 == 0) $cor = '#eeeeee';
		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			echo "<tr bgcolor='$cor' style='font-color:#000000; font-size:10px; font-family: Verdana'>";
			echo "<td align='center'>".pg_result($res,$i,sua_os)."</td>";
			echo "<td>".pg_result($res,$i,produto)." - ".substr(pg_result($res,$i,descricao),0,15)."</td>";
			echo "<td>".pg_result($res,$i,codigo_posto)." - ".pg_result($res,$i,nome)."</td>";
			echo "</tr>";
		}
		echo "</table>";
	}

	echo "<p align='center'><font face='Verdana, Tahoma, Arial' size='2'><b>Encontrada(s) ".pg_numrows($res)." OS na consulta.</b></font></p>";

}
?>

</body>
</html>