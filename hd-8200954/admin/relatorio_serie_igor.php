<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include "autentica_admin.php";

include "funcoes.php";

$msg = "";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

if (strlen($acao) > 0) {

	##### Pesquisa de data #####
	$pesquisa_mes = trim($_POST["pesquisa_mes"]);
	$pesquisa_ano = trim($_POST["pesquisa_ano"]);

	//if (strlen($pesquisa_mes) == 0) $msg .= " Informe o mês para realizar a pesquisa. ";
	//if (strlen($pesquisa_ano) == 0) $msg .= " Informe o ano para realizar a pesquisa. ";

/*	if (strlen($msg) == 0) {
		if (strlen($pesquisa_ano) == 2 OR strlen($pesquisa_ano) == 4) {
			if ($pesquisa_ano >= 50 && strlen($pesquisa_ano) == 2) $pesquisa_ano = "19" . $pesquisa_ano;
			elseif ($pesquisa_ano < 50 && strlen($pesquisa_ano) == 2) $pesquisa_ano = "20" . $pesquisa_ano;
		}else{
			$msg .= " Informe o ano para realizar a pesquisa. ";
		}
	}

*/
##### Pesquisa de produto #####
	$produto_referencia = trim($_POST["produto_referencia"]);
	$produto_descricao  = trim($_POST["produto_descricao"]);

	if (strlen($produto_referencia) > 0 && strlen($produto_descricao) > 0) {
		$produto_referencia = str_replace("-", "", $produto_referencia);
		$produto_referencia = str_replace("_", "", $produto_referencia);
		$produto_referencia = str_replace(".", "", $produto_referencia);
		$produto_referencia = str_replace(",", "", $produto_referencia);
		$produto_referencia = str_replace("/", "", $produto_referencia);

		$sql =	"SELECT tbl_produto.produto    ,
						tbl_produto.referencia ,
						tbl_produto.descricao  
				FROM tbl_produto
				JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
				WHERE tbl_linha.fabrica = $login_fabrica";
		if (strlen($produto_referencia) > 0) $sql .= " AND tbl_produto.referencia_pesquisa = '$produto_referencia'";
#		if (strlen($produto_descricao) > 0)   $sql .= " AND tbl_produto.descricao = '$produto_descricao';";

		$res = pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$produto            = pg_result($res,0,produto);
			$produto_referencia = pg_result($res,0,referencia);
			$produto_descricao  = pg_result($res,0,descricao);
		}else{
			$msg .= " Produto não encontrado. ";
		}
	}else{
		$msg .= " Informe o produto para realizar a pesquisa. ";
	}
}




$layout_menu = "gerencia";
$title = "RELATÓRIO - NÚMERO DE SÉRIE";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<br>

<? if (strlen($msg) > 0) { ?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><?echo $msg?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="acao">
<table width="400" border="0" cellspacing="0" cellpadding="2" align="center">
	<tr class="Titulo">
		<td colspan="4">PESQUISA</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
<?
	echo "<TR bgcolor='#D9E2EF'>\n";
	echo "	<TD ALIGN='center' colscan='1' nowrap>Data Inicial </TD>";
	echo "	<TD ALIGN='center' colscan='1'>";
	echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_inicial' value='$data_inicial' class='frm'>";
	echo "	</TD>\n";
	echo "	<TD ALIGN='center' colscan='1' nowrap>Data Final </TD>";
	echo "	<TD ALIGN='center' colscan='1'>";
	echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_final' value='$data_final' class='frm'>";
	echo "</TD>\n";
	echo "</TR>\n";
?>
<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="2"> Referência do Produto</td>
		<td colspan="2">Descrição do Produto</td>

	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td nowrap align='center' colspan="2">
			<input type="text" name="produto_referencia" size="15" value="<?echo $produto_referencia?>" class="frm">
			<img src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao, 'referencia')" style="cursor: hand;" alt="Clique aqui para pesquisar o produto">
		</td>
		<td nowrap align='center' colspan="2">
			<input type="text" name="produto_descricao" size="20" value="<?echo $produto_descricao?>" class="frm">
			<img src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao, 'descricao')" style="cursor: hand;" alt="Clique aqui para pesquisar o produto">
		</td>

	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4"><img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar"></td>
	</tr>
</table>
</form>

<br>

<?
if (strlen($acao) > 0 && strlen($msg) == 0) {

	// INICIO DA SQL
	$data_inicial = $_POST['data_inicial'];
	if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
	$data_final   = $_POST['data_final'];
	if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];
	$posto_codigo = $_POST['posto_codigo'];
	if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['cnpj'];
	
	$x_data_inicial = trim($data_inicial);
	$x_data_final   = trim($data_final);

	$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
	$x_data_final   = fnc_formata_data_pg($x_data_final);

	$x_data_inicial = str_replace("'","",$x_data_inicial);
	$x_data_final   = str_replace("'","",$x_data_final);

	$data_inicial = $x_data_inicial. " 00:00:00";
	$data_final   = $x_data_final.   " 23:59:59";


//	$data_inicial = date("Y-m-01", mktime(0, 0, 0, $pesquisa_mes, 1, $pesquisa_ano));
//	$data_final   = date("Y-m-t", mktime(23, 59, 59, $pesquisa_mes, 1, $pesquisa_ano));

	$sql = "SELECT  tbl_serie_controle.serie               ,
					tbl_serie_controle.quantidade_produzida,
					count(tbl_os.os) as total
			FROM   tbl_os
			JOIN   tbl_serie_controle ON tbl_os.produto = tbl_serie_controle.produto
									AND  tbl_os.serie   = tbl_serie_controle.serie
			JOIN   tbl_produto    ON tbl_os.produto = tbl_produto.produto
			WHERE  tbl_os.fabrica = $login_fabrica
			AND    tbl_os.produto = $produto
			AND    tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
			AND    tbl_os.excluida IS NOT TRUE
			GROUP BY tbl_serie_controle.serie               ,
					 tbl_serie_controle.quantidade_produzida
			ORDER BY tbl_serie_controle.serie               ,
					 tbl_serie_controle.quantidade_produzida;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		if ($pesquisa_mes{0} == 0) $pesquisa_mes = str_replace("0", "", $pesquisa_mes);
		echo "<center>";
		echo "<table width='350' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<tr class='Titulo' >";
		echo "<td colspan='5'>" . $meses[$pesquisa_mes] . " - $pesquisa_ano</td>";
		echo "</tr>";
		echo "<tr class='Titulo' >";
		echo "<td colspan='5'>$produto_referencia - $produto_descricao</td>";
		echo "</tr>";

		echo "<tr class='Titulo' height='15'>";
		echo "<td nowrap>SÉRIE</td>";
		echo "<td nowrap>QTD PROD.</td>";
		echo "<td nowrap>OCORRÊNCIAS</td>";
		echo "<td nowrap>%</td>";
		echo "<td nowrap>Ver OSs.</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$serie                = pg_result($res,$i,serie);
			$quantidade_produzida = pg_result($res,$i,quantidade_produzida);
			$total                = pg_result($res,$i,total);
			
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			$porcentagem = (($total*100)/$quantidade_produzida);
			$porcentagem =number_format($porcentagem, 2, '.','');
			

			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td nowrap align='left'>&nbsp; $serie</td>";
			echo "<td nowrap align='right'>&nbsp; $quantidade_produzida</td>";
			echo "<td nowrap align='right'>&nbsp; $total</td>";
			echo "<td nowrap align='right'>&nbsp; $porcentagem</td>";
			echo "<td nowrap align='center'><a href='relatorio_serie_detalhe.php?produto=$produto&serie=$serie&data_inicio=$data_inicial&data_fim=$data_final' target='_blank'>&nbsp; Ver Oss.</a></td>";
			echo "</tr>";
			
			$defeito_anterior = $defeito;
		}
		echo "</table>";
	} else {
		echo "Nenhum resultado encontrado.";
	}
}
echo "<br>";

include "rodape.php";
?>
