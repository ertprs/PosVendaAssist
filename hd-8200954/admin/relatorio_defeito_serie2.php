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

	if (strlen($pesquisa_mes) == 0) $msg .= " Informe o mês para realizar a pesquisa. ";
	if (strlen($pesquisa_ano) == 0) $msg .= " Informe o ano para realizar a pesquisa. ";

	if (strlen($msg) == 0) {
		if (strlen($pesquisa_ano) == 2 OR strlen($pesquisa_ano) == 4) {
			if ($pesquisa_ano >= 50 && strlen($pesquisa_ano) == 2) $pesquisa_ano = "19" . $pesquisa_ano;
			elseif ($pesquisa_ano < 50 && strlen($pesquisa_ano) == 2) $pesquisa_ano = "20" . $pesquisa_ano;
		}else{
			$msg .= " Informe o ano para realizar a pesquisa. ";
		}
	}

	##### Pesquisa de produto #####
	$linha = trim($_POST["linha"]);
	if (strlen($linha) == 0) $msg .= " Informe a linha para realizar a pesquisa. ";
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
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>Mês</td>
		<td>Ano</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>
			<select name="pesquisa_mes" size="1" class="frm">
				<option value=""></option>
				<?
				$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
				for ($i = 1 ; $i <= count($meses) ; $i++) {
					echo "<option value='" . str_pad($i, 2, "0", STR_PAD_LEFT) . "'";
					if ( $pesquisa_mes == str_pad($i, "0", STR_PAD_LEFT) ) echo " selected";
					echo ">" . $meses[$i] . "</option>";
				}
				?>
			</select>
		</td>
		<td>
			<select name="pesquisa_ano" size="1" class="frm">
				<option value=""></option>
				<option value="2005" <? if ($pesquisa_ano == "2005") echo "selected"; ?>>2005</option>
				<option value="2004" <? if ($pesquisa_ano == "2004") echo "selected"; ?>>2004</option>
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td colspan="2">Linha</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td colspan="2">
			<?
			$sql =	"SELECT tbl_linha.linha        ,
							tbl_linha.codigo_linha ,
							tbl_linha.nome         
					FROM tbl_linha
					WHERE tbl_linha.fabrica = $login_fabrica
					ORDER BY tbl_linha.nome;";
			$res = @pg_exec ($con,$sql);
			if (pg_numrows($res) > 0) {
				echo "<select name='linha' size='1' class='frm'>";
				echo "<option value=''></option>";
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
					echo "<option value='" . pg_result($res,$i,linha) . "'";
					if ($linha == pg_result($res,$i,linha)) echo " selected";
					echo ">" . pg_result($res,$i,nome) . "</option>";
				}
				echo "</select>";
			}
			?>
		</td>
		<td width="10">&nbsp;</td>
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
	$data_inicial = date("Y-m-01", mktime(0, 0, 0, $pesquisa_mes, 1, $pesquisa_ano));
	$data_final   = date("Y-m-t", mktime(23, 59, 59, $pesquisa_mes, 1, $pesquisa_ano));

	$sql = "SELECT tbl_defeito.defeito                      ,
				   upper(tbl_defeito.descricao) AS descricao,
				   tbl_os.serie                             ,
				   count(tbl_os.serie) as total
			FROM   tbl_os
			JOIN   tbl_produto    USING (produto)
			JOIN   tbl_os_produto USING (os)
			JOIN   tbl_os_item    USING (os_produto)
			JOIN   tbl_defeito       ON tbl_defeito.defeito = tbl_os_item.defeito
									AND tbl_defeito.fabrica = $login_fabrica
			WHERE  tbl_os.fabrica = $login_fabrica
			AND    tbl_os.serie NOTNULL
			AND    tbl_os.linha = $linha
			AND tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
			GROUP BY tbl_defeito.defeito,
				   tbl_defeito.descricao,
				   tbl_os.serie         
			ORDER BY tbl_defeito.descricao, tbl_os.serie;";
	$res = pg_exec($con,$sql);

//if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql)."<br><br>".pg_numrows($res);

	if (pg_numrows($res) > 0) {
		if ($pesquisa_mes{0} == 0) $pesquisa_mes = str_replace("0", "", $pesquisa_mes);
		echo "<table width='300' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td colspan=2>$produto_referencia - $produto_descricao</td>";
		echo "</tr>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td colspan=2>" . $meses[$pesquisa_mes] . " - $pesquisa_ano</td>";
		echo "</tr>";
		echo "</table>";
		
		echo "<br>";
		
		echo "<table width='300' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$defeito   = pg_result($res,$i,defeito);
			$descricao = pg_result($res,$i,descricao);
			$serie     = pg_result($res,$i,serie);
			$total     = pg_result($res,$i,total);
			
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			
			if ($defeito != $defeito_anterior) {
				echo "<tr class='Titulo' height='15'>";
				echo "<td colspan=2 nowrap>$descricao</td>";
				echo "</tr>";
				echo "<tr class='Titulo' height='15'>";
				echo "<td nowrap>SÉRIE</td>";
				echo "<td nowrap>QTD</td>";
				echo "</tr>";
			}
			
			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td nowrap align='left'> &nbsp; $serie</td>";
			echo "<td nowrap align='right'> &nbsp; $total</td>";
			echo "</tr>";
			
			$defeito_anterior = $defeito;
		}
		echo "</table>";
	}
}
echo "<br>";

include "rodape.php";
?>
