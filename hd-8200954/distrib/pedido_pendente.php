<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$title='Pendência da Fábrica com o Distribuidor';
$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include 'autentica_usuario.php';
}

include "gera_relatorio_pararelo_include.php";


if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0)  $btn_acao = trim($_GET["btn_acao"]);

if (strlen(trim($_POST["referencia"])) > 0) $referencia = trim($_POST["referencia"]);
if (strlen(trim($_GET["referencia"])) > 0)  $referencia = trim($_GET["referencia"]);

if (strlen(trim($_POST["descricao"])) > 0) $descricao = trim($_POST["descricao"]);
if (strlen(trim($_GET["descricao"])) > 0)  $descricao = trim($_GET["descricao"]);

#$title = "DETALHAMENTO DE NOTA FISCAL";
#$layout_menu = 'pedido';

#include "cabecalho.php";
?>

<html>
<head>
<title>Pendência de Peças</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>

<? include 'menu.php' ?>


<center><h1>Pendência de Peças</h1></center>

<p>

<?

if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	include "gera_relatorio_pararelo_verifica.php";
}

if (strlen($msg_erro) > 0) { ?>
	<table width="600" border="0" cellspacing="0" cellpadding="2" align="center" class="Error">
		<tr>
			<td><?echo $msg_erro?></td>
		</tr>
	</table>
	<br>
	<? 
} 

?>

<center>
<form name='frm_estoque' action='<? echo $PHP_SELF ?>' method='post'>

Referência da Peça <input type='text' size='10' name='referencia'>
Descrição da Peça <input type='text' size='30' name='descricao'>

<br>

<input type='submit' name='btn_acao' value='Pesquisar'>
&nbsp;&nbsp;&nbsp;&nbsp;
<input type='submit' name='btn_acao' value='Ver Pendência mais 10 dias'>

</form>
</center>


<?

if (strlen($btn_acao)>0){

	echo "<p>Relatório gerado em ".date("d/m/Y")." às ".date("H:i")."</p>";

	if (strlen ($referencia) > 2) {
		$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_posto_estoque.qtde, fabrica.qtde_fabrica, transp.qtde_transp, para.referencia AS para_referencia, para.descricao AS para_descricao, tbl_posto_estoque_localizacao.localizacao, pendente.qtde_pendente
				FROM   tbl_peca 
				LEFT JOIN tbl_posto_estoque             ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
				LEFT JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
				LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de
				LEFT JOIN tbl_peca para ON tbl_depara.peca_para = para.peca
				LEFT JOIN (SELECT peca, SUM (qtde - qtde_faturada - qtde_cancelada) AS qtde_fabrica FROM tbl_pedido_item JOIN tbl_pedido USING (pedido) WHERE ((tbl_pedido.posto = $login_posto AND tbl_pedido.tipo_pedido = 2) OR (tbl_pedido.distribuidor = $login_posto AND tbl_pedido.tipo_pedido = 3 ) ) AND tbl_pedido.fabrica IN (".implode(",", $fabricas).") GROUP BY tbl_pedido_item.peca) fabrica ON tbl_peca.peca = fabrica.peca
				LEFT JOIN (SELECT peca, SUM (qtde - qtde_faturada_distribuidor - qtde_cancelada) AS qtde_pendente FROM tbl_pedido_item JOIN tbl_pedido USING (pedido) WHERE tbl_pedido.distribuidor = $login_posto AND tbl_pedido.posto <> tbl_pedido.distribuidor AND tbl_pedido.fabrica IN (".implode(",", $fabricas).") AND (tbl_pedido.status_pedido_posto NOT IN (3,13) OR tbl_pedido.status_pedido_posto IS NULL) GROUP BY tbl_pedido_item.peca) pendente ON tbl_peca.peca = pendente.peca
				LEFT JOIN (SELECT peca, SUM (qtde) AS qtde_transp FROM tbl_faturamento_item JOIN tbl_faturamento USING (faturamento) WHERE tbl_faturamento.posto = $login_posto AND tbl_faturamento.fabrica IN (".implode(",", $fabricas).") AND tbl_faturamento.conferencia IS NULL GROUP BY tbl_faturamento_item.peca) transp ON tbl_peca.peca = transp.peca
				WHERE  (tbl_posto_estoque.posto = $login_posto OR tbl_posto_estoque.posto IS NULL)
				AND    pendente.qtde_pendente > 0
				AND    (tbl_peca.referencia ILIKE '%$referencia%' OR para.referencia ILIKE '%$referencia%')
				AND    tbl_peca.fabrica IN (".implode(",", $fabricas).")
				ORDER BY tbl_peca.descricao";
	}

	if (strlen ($descricao) > 2) {
		$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_posto_estoque.qtde, fabrica.qtde_fabrica, transp.qtde_transp, para.referencia AS para_referencia, para.descricao AS para_descricao, tbl_posto_estoque_localizacao.localizacao, pendente.qtde_pendente
				FROM   tbl_peca 
				LEFT JOIN tbl_posto_estoque             ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
				LEFT JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
				LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de
				LEFT JOIN tbl_peca para ON tbl_depara.peca_para = para.peca
				LEFT JOIN (SELECT peca, SUM (qtde - qtde_faturada - qtde_cancelada) AS qtde_fabrica FROM tbl_pedido_item JOIN tbl_pedido USING (pedido) WHERE ((tbl_pedido.posto = $login_posto AND tbl_pedido.tipo_pedido = 2) OR (tbl_pedido.distribuidor = $login_posto AND tbl_pedido.tipo_pedido = 3 ) ) AND tbl_pedido.fabrica IN (".implode(",", $fabricas).") GROUP BY tbl_pedido_item.peca) fabrica ON tbl_peca.peca = fabrica.peca
				LEFT JOIN (SELECT peca, SUM (qtde - qtde_faturada_distribuidor - qtde_cancelada) AS qtde_pendente FROM tbl_pedido_item JOIN tbl_pedido USING (pedido) WHERE tbl_pedido.distribuidor = $login_posto AND tbl_pedido.posto <> tbl_pedido.distribuidor AND tbl_pedido.fabrica IN (".implode(",", $fabricas).") AND (tbl_pedido.status_pedido_posto NOT IN (3,13) OR tbl_pedido.status_pedido_posto IS NULL) GROUP BY tbl_pedido_item.peca) pendente ON tbl_peca.peca = pendente.peca
				LEFT JOIN (SELECT peca, SUM (qtde) AS qtde_transp FROM tbl_faturamento_item JOIN tbl_faturamento USING (faturamento) WHERE tbl_faturamento.posto = $login_posto AND tbl_faturamento.fabrica IN (".implode(",", $fabricas).") AND tbl_faturamento.conferencia IS NULL GROUP BY tbl_faturamento_item.peca) transp ON tbl_peca.peca = transp.peca
				WHERE  (tbl_posto_estoque.posto = $login_posto OR tbl_posto_estoque.posto IS NULL)
				AND    pendente.qtde_pendente > 0
				AND    (tbl_peca.descricao ILIKE '%$descricao%' OR para.descricao ILIKE '%$descricao%')
				AND    tbl_peca.fabrica IN (".implode(",", $fabricas).")
				ORDER BY tbl_peca.descricao";
	}

	if (strtolower ($btn_acao) == "pesquisar" and strlen ($referencia) == 0 and strlen ($descricao) == 0) {
		$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_posto_estoque.qtde, fabrica.qtde_fabrica, transp.qtde_transp, para.referencia AS para_referencia, para.descricao AS para_descricao, tbl_posto_estoque_localizacao.localizacao, pendente.qtde_pendente
				FROM   tbl_peca 
				LEFT JOIN tbl_posto_estoque             ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
				LEFT JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
				LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de
				LEFT JOIN tbl_peca para ON tbl_depara.peca_para = para.peca
				LEFT JOIN (SELECT peca, SUM (qtde - qtde_faturada - qtde_cancelada) AS qtde_fabrica FROM tbl_pedido_item JOIN tbl_pedido USING (pedido) WHERE ((tbl_pedido.posto = $login_posto AND tbl_pedido.tipo_pedido = 2) OR (tbl_pedido.distribuidor = $login_posto AND tbl_pedido.tipo_pedido = 3 ) ) AND tbl_pedido.fabrica IN (".implode(",", $fabricas).") GROUP BY tbl_pedido_item.peca) fabrica ON tbl_peca.peca = fabrica.peca
				LEFT JOIN (SELECT peca, SUM (qtde - qtde_faturada_distribuidor - qtde_cancelada) AS qtde_pendente FROM tbl_pedido_item JOIN tbl_pedido USING (pedido) WHERE tbl_pedido.distribuidor = $login_posto AND tbl_pedido.posto <> tbl_pedido.distribuidor AND tbl_pedido.fabrica IN (".implode(",", $fabricas).") AND (tbl_pedido.status_pedido_posto NOT IN (3,13) OR tbl_pedido.status_pedido_posto IS NULL) GROUP BY tbl_pedido_item.peca) pendente ON tbl_peca.peca = pendente.peca
				LEFT JOIN (SELECT peca, SUM (qtde) AS qtde_transp FROM tbl_faturamento_item JOIN tbl_faturamento USING (faturamento) WHERE tbl_faturamento.posto = $login_posto AND tbl_faturamento.fabrica IN (".implode(",", $fabricas).") AND tbl_faturamento.conferencia IS NULL GROUP BY tbl_faturamento_item.peca) transp ON tbl_peca.peca = transp.peca
				WHERE  (tbl_posto_estoque.posto = $login_posto OR tbl_posto_estoque.posto IS NULL)
				AND    pendente.qtde_pendente > 0
				AND    tbl_peca.fabrica IN (".implode(",", $fabricas).")
				ORDER BY tbl_peca.descricao";
	}

	if (substr (strtolower ($btn_acao),0,3) == "ver" ) {
		$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_posto_estoque.qtde, fabrica.qtde_fabrica, transp.qtde_transp, para.referencia AS para_referencia, para.descricao AS para_descricao, tbl_posto_estoque_localizacao.localizacao, pendente.qtde_pendente
				FROM   tbl_peca
				JOIN (SELECT DISTINCT tbl_pedido_item.peca 
						FROM tbl_pedido_item JOIN tbl_pedido USING (pedido)
						WHERE tbl_pedido.distribuidor = $login_posto
						AND   tbl_pedido.fabrica      IN (".implode(",", $fabricas).")
						AND   tbl_pedido.status_pedido_posto IN (7,11,12)
						AND   tbl_pedido_item.qtde > tbl_pedido_item.qtde_faturada_distribuidor + tbl_pedido_item.qtde_cancelada
						LIMIT 100) mais_antigas ON tbl_peca.peca = mais_antigas.peca
				LEFT JOIN tbl_posto_estoque             ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
				LEFT JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
				LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de
				LEFT JOIN tbl_peca para ON tbl_depara.peca_para = para.peca
				LEFT JOIN (SELECT peca, SUM (qtde - qtde_faturada - qtde_cancelada) AS qtde_fabrica FROM tbl_pedido_item JOIN tbl_pedido USING (pedido) WHERE ((tbl_pedido.posto = $login_posto AND tbl_pedido.tipo_pedido = 2) OR (tbl_pedido.distribuidor = $login_posto AND tbl_pedido.tipo_pedido = 3 ) ) AND tbl_pedido.fabrica IN (".implode(",", $fabricas).") GROUP BY tbl_pedido_item.peca) fabrica ON tbl_peca.peca = fabrica.peca
				LEFT JOIN (SELECT peca, SUM (qtde - qtde_faturada_distribuidor - qtde_cancelada) AS qtde_pendente FROM tbl_pedido_item JOIN tbl_pedido USING (pedido) WHERE tbl_pedido.distribuidor = $login_posto AND tbl_pedido.posto <> tbl_pedido.distribuidor AND tbl_pedido.fabrica IN (".implode(",", $fabricas).") AND (tbl_pedido.status_pedido_posto NOT IN (3,13) OR tbl_pedido.status_pedido_posto IS NULL) GROUP BY tbl_pedido_item.peca) pendente ON tbl_peca.peca = pendente.peca
				LEFT JOIN (SELECT peca, SUM (qtde) AS qtde_transp FROM tbl_faturamento_item JOIN tbl_faturamento USING (faturamento) WHERE tbl_faturamento.posto = $login_posto AND tbl_faturamento.fabrica IN (".implode(",", $fabricas).") AND tbl_faturamento.conferencia IS NULL GROUP BY tbl_faturamento_item.peca) transp ON tbl_peca.peca = transp.peca
				WHERE  (tbl_posto_estoque.posto = $login_posto OR tbl_posto_estoque.posto IS NULL)
				AND    pendente.qtde_pendente > tbl_posto_estoque.qtde
				AND    tbl_peca.fabrica IN (".implode(",", $fabricas).")
				ORDER BY tbl_peca.descricao";
	}


	if (strlen ($descricao) > 2 or strlen ($referencia) > 2 or strlen ($btn_acao) > 0 ) {
		$res = pg_exec ($con,$sql);

		echo "<table align='center' border='1' cellspacing='3' cellpaddin='3'>";
		echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
		echo "<td>Referência</td>";
		echo "<td>Descrição</td>";
		echo "<td>Pendente</td>";
		echo "<td>Estoque</td>";
		echo "<td>Fábrica</td>";
		echo "<td>Transp.</td>";
		echo "<td>Localização</td>";
		echo "</tr>";

		$total_pendente = 0;
		$total_estoque  = 0;
		$total_fabrica  = 0;
		$total_transp   = 0;

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

			$cor = "";
			if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) $cor = '#dddddd';
			
			echo "<tr bgcolor='$cor'>";

			echo "<td>";
			echo pg_result ($res,$i,referencia);
			if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_referencia);
			echo "</td>";

			echo "<td>";
			echo pg_result ($res,$i,descricao);
			if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_descricao);
			echo "</td>";

			echo "<td align='right'>&nbsp;";
			echo pg_result ($res,$i,qtde_pendente);
			echo "</td>";

			echo "<td align='right'>&nbsp;";
			echo pg_result ($res,$i,qtde);
			echo "</td>";

			echo "<td align='right'>&nbsp;";
			echo pg_result ($res,$i,qtde_fabrica);
			echo "</td>";

			echo "<td align='right'>&nbsp;";
			echo pg_result ($res,$i,qtde_transp);
			echo "</td>";

			echo "<td align='left'>&nbsp;";
			echo pg_result ($res,$i,localizacao);
			echo "</td>";

			echo "</tr>";

			$total_pendente += pg_result ($res,$i,qtde_pendente);
			$total_estoque  += pg_result ($res,$i,qtde);
			$total_fabrica  += pg_result ($res,$i,qtde_fabrica);
			$total_transp   += pg_result ($res,$i,qtde_transp);

		}

		echo "<tr bgcolor='#6666CC'>";

		echo "<td colspan='2'> TOTAL </td>";

		echo "<td align='right'>&nbsp;";
		echo $total_pendente;
		echo "</td>";

		echo "<td align='right'>&nbsp;";
		echo $total_estoque;
		echo "</td>";

		echo "<td align='right'>&nbsp;";
		echo $total_fabrica;
		echo "</td>";

		echo "<td align='right'>&nbsp;";
		echo $total_transp;
		echo "</td>";

		echo "<td>&nbsp;</td>";

		echo "</tr>";

		echo "</table>";

	}
}

?>

<? #include "rodape.php"; ?>

</body>
</html>
<?
include'rodape.php';
?>