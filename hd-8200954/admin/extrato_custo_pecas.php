<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

if ($login_fabrica <> 1) {
	echo "<h1>Fechamento de Extrato realizado pela TELECONTROL</h1>";
	exit;
}


$msg_erro = "";

$btn_acao = trim(strtolower($_POST["btn_acao"]));


if ($btn_acao == "gravar custo das peças"){
	$extrato    = $_POST["extrato"];
	$qtde_itens = $_POST["qtde_itens"];


	$res = pg_exec ($con,"BEGIN TRANSACTION");

	for ($i = 0 ; $i < $qtde_itens ; $i++) {
		$peca       = $_POST['peca_'       . $i ];
		$custo_peca = $_POST['custo_peca_' . $i ];

		$custo_peca = str_replace (",",".",$custo_peca);

		$sql = "SELECT fn_custo_peca_manual ($extrato,$peca,$custo_peca)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = @pg_errormessage($con);
		if (strlen ($msg_erro) > 0) {
			break;
		}
	}


	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header("Location: extrato_custo_pecas.php?msg=Gravado com Sucesso!");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$msg = $_GET['msg'];
$layout_menu = "financeiro";
$title = "EXTRATO - CUSTO DAS PEÇAS";

include "cabecalho.php";

?>
<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}


table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>
<p>

<? if (strlen($msg_erro) > 0){ ?>
<TABLE align='center' width="700">
<TR>
	<TD class="msg_erro"><? echo $msg_erro; ?></TD>
</TR>
</TABLE>
<? } ?>

<? if (strlen($msg) > 0){ ?>
<TABLE align='center' width="700">
<TR>
	<TD class="sucesso"><? echo $msg; ?></TD>
</TR>
</TABLE>
<? } ?>

<?

if (strlen ($msg_erro) == 0) $extrato = $_GET['extrato'];

if (strlen ($extrato) == 0) {

	$sql = "SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.nome, tbl_extrato.extrato
			FROM tbl_extrato
			JOIN tbl_posto USING (posto)
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_extrato.fabrica
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND   tbl_extrato.aprovado IS NULL";
	$res = pg_exec ($con,$sql);

	echo "<table border='0' width='700' cellspacing='1' cellpadding='5' align='center' class='tabela'>";
	echo "<tr class='titulo_tabela'>";
	echo "<td colspan='3' style='font-size:13px;'>Extratos que Dependem de Custo de Peças</td>";
	echo "</tr>";
	echo "<tr class='titulo_coluna'>";
	echo "<td>Extrato</td>";
	echo "<td>Código <br> Posto</td>";
	echo "<td>Posto</td>";
	echo "</tr>";
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	$cor='#F7F5F0';
	if ($i % 2 == 0) $cor = '#F1F4FA';
		echo "<tr bgcolor='$cor'>";

		echo "<td align='left'>";
		echo "<a href='$PHP_SELF?extrato=" . pg_result ($res,$i,extrato) . "'>";
		echo pg_result ($res,$i,extrato);
		echo "</a>";
		echo "</td>";

		echo "<td align='left'>";
		echo pg_result ($res,$i,codigo_posto);
		echo "</td>";

		echo "<td align='left'>";
		echo pg_result ($res,$i,nome);
		echo "</td>";

		echo "</tr>";
	}

	echo "</table>";

}else{

	
	$sql = "SELECT posto FROM tbl_extrato WHERE extrato = $extrato AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$posto = pg_result ($res,0,0);

	$sql = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, os_item.qtde, os_item.custo_peca, os_item.custo_peca_manual, preco.preco
			FROM tbl_peca 
			JOIN (SELECT tbl_os_item.peca, tbl_os_item.custo_peca, tbl_os_item.custo_peca_manual, SUM (tbl_os_item.qtde) AS qtde
					FROM tbl_os
					JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
					JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
					JOIN tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					WHERE tbl_os_extra.extrato = $extrato
					AND   tbl_os.fabrica = $login_fabrica
					GROUP BY tbl_os_item.peca, tbl_os_item.custo_peca, tbl_os_item.custo_peca_manual
			) os_item ON tbl_peca.peca = os_item.peca 
			LEFT JOIN (SELECT tbl_tabela_item.peca, tbl_tabela_item.preco
					FROM tbl_tabela_item
					JOIN tbl_tabela USING (tabela)
					JOIN tbl_posto_condicao ON tbl_tabela.tabela = tbl_posto_condicao.tabela
					WHERE tbl_posto_condicao.posto = $posto AND tbl_posto_condicao.condicao = 51
			) preco ON tbl_peca.peca = preco.peca
			ORDER BY tbl_peca.referencia";
#echo $sql;
	$res = pg_exec ($con,$sql);

	echo "<table border='0' width='700' cellspacing='1' cellpadding='5' align='center' class='tabela'>";
	echo "<tr class='titulo_coluna'>";
	echo "<td colspan='4' style='font-size:13px;'>Digite o Custo das Peças</td>";
	echo "</tr>";
	echo "</tr>";
	echo "<tr class='titulo_coluna'>";
	echo "<td>Referência</td>";
	echo "<td>Descrição</td>";
	echo "<td>Qtde</td>";
	echo "<td>Custo</td>";
	echo "</tr>";
	echo "<form method='post' action='$PHP_SELF' name='frm_custo_peca'>";
	echo "<input type='hidden' name='extrato' value='$extrato'>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	$cor='#F7F5F0';
	if ($i % 2 == 0) $cor = '#F1F4FA';
		echo "<tr  bgcolor='$cor'>";
		echo "<input type='hidden' name='peca_$i' value='" . pg_result ($res,$i,peca) . "' >";

		echo "<td align='left'>";
		echo pg_result ($res,$i,referencia);
		echo "</td>";

		echo "<td align='left'>";
		echo pg_result ($res,$i,descricao);
		echo "</td>";

		echo "<td align='right'>";
		echo pg_result ($res,$i,qtde);
		echo "</td>";

		echo "<td align='left'>";
		$custo_peca_manual = pg_result ($res,$i,custo_peca_manual);
		if ($custo_peca_manual == 't' or strlen ($custo_peca_manual) == 0 ) {
			$custo_peca = pg_result ($res,$i,custo_peca);
			if (strlen (pg_result ($res,$i,preco)) > 0) {
				$custo_peca = pg_result ($res,$i,preco);

				/* COLOCAR AQUI OS CALCULOS DA TABELA 30 DIAS PARA CUSTO DA PEÇA */

			}
			$custo_peca = number_format ($custo_peca,2,",",".");

			echo "<input type='text' name='custo_peca_$i' value='$custo_peca' size='6' maxlength='8' class='frm'>";
		}else{
			$custo_peca = number_format (pg_result ($res,$i,custo_peca),2,",",".");
			echo pg_result ($res,$i,custo_peca);
		}
		echo "</td>";

		echo "</tr>";
	}

	echo "</table>";

	echo "<center>";
	echo "<input type='hidden' name='qtde_itens' value='$i'>";
	echo "<input type='submit' name='btn_acao' value='Gravar Custo das Peças'>";
	echo "</center>";


}


?>
<p>

<p>
<? include "rodape.php"; ?>