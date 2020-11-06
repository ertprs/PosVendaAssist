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
	
}

$layout_menu = "gerencia";
$title = "RELATÓRIO - OS ABERTA PELO SAC";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
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
<script language="javascript" >
function AbrePeca(produto,n_serie){
	janela = window.open("relatorio_defeito_serie_fabricacao_os.php?produto=" + produto + "&nserie=" + n_serie,"serie",'resizable=1,scrollbars=yes,width=750,height=450,top=0,left=0');
	janela.focus();
}
</script>
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
		<td colspan="4">OS(s) digitada(s) pelo SAC</td>
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
<?
	for ($i = 2004 ; $i <= date("Y") ; $i++) {
				echo "<option value='$i'";
				if ($pesquisa_ano == $i) echo " selected";
				echo ">$i</option>";
			}
?>
		</td>
		<td>&nbsp;</td>
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
	
	$pesquisa_mes;
	$pesquisa_ano = substr($pesquisa_ano, 2, 2);
	$radical_n_serie = $pesquisa_mes.$pesquisa_ano;
//echo "n serie $radical_n_serie<bR><BR>";	

	$sql =" SELECT 	tbl_os.os                                             ,
					tbl_os.sua_os                                         ,
					to_char(tbl_os.data_abertura,'DD/MM') as abertura     ,
					to_char(tbl_os.data_fechamento,'DD/MM')  as fechamento,
					tbl_produto.descricao          as produto             ,
					tbl_os.consumidor_nome                                ,
					tbl_posto.nome                as posto                ,
					tbl_posto_fabrica.codigo_posto                        ,
					tbl_admin.login as admin
			FROM tbl_os
			JOIN tbl_produto using(produto)
			JOIN tbl_admin on tbl_admin.admin =  tbl_os.admin and tbl_admin.fabrica = $login_fabrica
			JOIN tbl_posto  on tbl_posto.posto = tbl_os.posto
			JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.data_digitacao between '$data_inicial' and '$data_final'
			AND tbl_os.admin NOTNULL
			AND tbl_os.admin <> 410
			AND tbl_os.excluida IS NOT TRUE
			ORDER BY tbl_os.data_abertura, tbl_os.sua_os";

	$res = pg_exec($con,$sql);

	//echo nl2br($sql);

	if (pg_numrows($res) > 0) {
echo "<center><font size='1'>* Busca pela data de digitação</font></center>";
		echo "<table width='650' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td>OS</td>";
		echo "<td>Aber.</td>";
		echo "<td>Fech.</td>";
		echo "<td>Produto</td>";
		echo "<td nowrap>Posto</td>";
		echo "<td>Consumidor</td>";
		echo "<td>Login</td>";
		echo "</tr>";
		for($x=0;pg_numrows($res)>$x;$x++)		{
			$os                = pg_result($res,$x,os);
			$sua_os            = pg_result($res,$x,sua_os);
			$abertura          = pg_result($res,$x,abertura);
			$fechamento        = pg_result($res,$x,fechamento);
			$produto           = pg_result($res,$x,produto);
			$consumidor_nome   = pg_result($res,$x,consumidor_nome);
			$posto_nome        = pg_result($res,$x,posto);
			$posto_codigo      = pg_result($res,$x,codigo_posto);
			$admin             = pg_result($res,$x,admin);

			echo "<tr class='Conteudo' height='15'>";
			echo "<td><font size='1'><a href='os_press.php?os=$os' target='blank'>$sua_os</a></font></td>";
			echo "<td align='center'><font size='1'>$abertura</font></td>";
			echo "<td align='center'><font size='1'>$fechamento</font></td>";
			echo "<td align='left' nowrap><font size='1'>$produto</font></td>";
			echo "<td align='left' nowrap><font size='1'>$posto_codigo $posto_nome</font></td>";
			echo "<td align='left' nowrap><font size='1'>$consumidor_nome</font></td>";
			echo "<td align='center'><font size='1'>$admin</font></td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}
echo "<br>";

include "rodape.php";
?>
