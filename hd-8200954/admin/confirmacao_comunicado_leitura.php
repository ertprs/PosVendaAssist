<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';

$msg_erro = "";

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = $_POST["btn_acao"];

if ($btn_acao == "pesquisar") {
	$pesquisa_ano = trim($_POST["pesquisa_comunicado"]);

	if (strlen($pesquisa_comunicado) == 0) {
		$msg_erro .= " Selecione um comunicado para fazer a pesquisa. ";
	}
}

$layout_menu = "tecnica";
$title = "Relação de leitura dos comunicados na entrada do site pelos postos";

include "cabecalho.php";
?>

<style type="text/css">
.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	border: 0px;
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
	border: 0px;
}
</style>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<? if (strlen($msg_erro) > 0){ ?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'><?echo $msg_erro?></td>
</tr>
</table>
<? } ?>

<br>

<table width="600" align="center" border="0" cellspacing="0" cellpadding="3">
	<tr bgcolor="#596D9B">
		<td colspan="4" class="menu_top" align="center"><b>Escolha o comunicado para fazer a pesquisa</b></td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td class="table_line" align="center" colspan="3">
			<center>Comunicados referentes aos últimos 3 meses<br>
			&nbsp;<select name="pesquisa_comunicado" size="1" class="frm">
				<option value=""></option>
				<?
				$sql = "SELECT tbl_comunicado.comunicado,
							   TO_CHAR (tbl_comunicado.data, 'DD/MM/YYYY') as data,
							   tbl_comunicado.descricao,
							   tbl_produto.descricao as descricao_produto
						FROM tbl_comunicado
						LEFT JOIN tbl_produto on (tbl_produto.produto = tbl_comunicado.produto)
						WHERE tbl_comunicado.fabrica = $login_fabrica
						AND tbl_comunicado.data > current_date - INTERVAL '3 months'
						AND tbl_comunicado.obrigatorio_site
						ORDER BY tbl_comunicado.data DESC";
				$res = pg_exec($con,$sql);

				if (pg_numrows($res) > 0) {
					for ($i = 0 ; $i < pg_numrows($res); $i++){
						echo "<option value=" . trim(pg_result ($res,$i,comunicado)) . ">";
						echo "(" . trim(pg_result ($res,$i,data)) . ") " . trim(pg_result ($res,$i,descricao_produto)) . " - " . trim(pg_result ($res,$i,descricao));
						echo "</option>";
					}
					
				}
				?>
			</select>&nbsp;</center>
		</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4" style="text-align: center;">
			<input type="hidden" name="btn_acao" value="">
			<img border="0" src="imagens_admin/btn_pesquisar_comunicado_600.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde a submissão...'); }" style="cursor: pointer;" alt='Clique AQUI para pesquisar'></td>
	</tr>
</table>

</form>

<?
if (strlen($_POST["pesquisa_comunicado"]) > 0) $pesquisa_comunicado = $_POST["pesquisa_comunicado"];
if ($btn_acao == "pesquisar" AND strlen($msg_erro) == 0 AND strlen($pesquisa_comunicado) >0) {

	$sql =	"SELECT tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome                ,
					TO_CHAR (confirmado.data_confirmacao, 'DD/MM/YYYY') as data_confirmacao
			FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.credenciamento ilike 'credenciado'
			LEFT JOIN (SELECT posto, data_confirmacao FROM tbl_comunicado_posto_blackedecker WHERE comunicado = $pesquisa_comunicado) confirmado ON confirmado.posto = tbl_posto.posto
			ORDER BY confirmado.data_confirmacao ";
	$res1 = pg_exec($con,$sql);

	$sql = "SELECT tbl_comunicado.comunicado,
				   TO_CHAR (tbl_comunicado.data, 'DD/MM/YYYY') as data,
				   tbl_comunicado.descricao,
				   tbl_produto.descricao as descricao_produto
			FROM tbl_comunicado
			LEFT JOIN tbl_produto on tbl_produto.produto = tbl_comunicado.produto
			WHERE tbl_comunicado.comunicado = $pesquisa_comunicado AND tbl_comunicado.fabrica = $login_fabrica";
	$res2 = pg_exec($con,$sql);

	if (pg_numrows($res2) > 0) 
		echo "<h3>RELAÇÃO DOS POSTOS E DATAS DE LEITURA DO COMUNICADO:<BR>(" . 
															trim(pg_result ($res2,0,data)) . ") " . 
															trim(pg_result ($res2,0,comunicado)) . " - " . 
															trim(pg_result ($res2,0,descricao_produto)) . " - " . 
															trim(pg_result ($res2,0,descricao)) . "</h2>";

	if (pg_numrows($res1) > 0) {
		echo "<h3>Para localizar uma palavra na página, tecle CTRL + F.</h2>";

		echo "<table align='center' border='0' cellspacing='1' cellpadding='2'>";
		echo "<tr bgcolor='#596D9B'>";
		echo "<td class='menu_top' nowrap>Posto</td>";
		echo "<td class='menu_top' nowrap>Data da leitura</td>";
		echo "</TR>";

		for ($j = 0 ; $j < pg_numrows($res1) ; $j++) {
			$posto_codigo   = pg_result($res1, $j, codigo_posto);
			$posto_nome     = pg_result($res1, $j, nome);
			$data           = pg_result($res1, $j, data_confirmacao);
	
			if ($j % 2 == 0) $cor = "#F7F5F0";
			else             $cor = "#F1F4FA";

			echo "<tr class='table_line' bgcolor='$cor'>";
			echo "<td nowrap><acronym title='Código: $posto_codigo | Razão Social: $posto_nome'>" . $posto_codigo . " - " . substr($posto_nome, 0, 20) ." </acronym></td>";
			echo "<td nowrap align='center'>" . $data   . "</td>";
			echo "</tr>";
		}

		echo "</table>";
	}else{
		echo "<h3>Nenhum posto encontrado para este comunicado</h2>";
	}
}
?>

<br>

<? include "rodape.php" ?>
