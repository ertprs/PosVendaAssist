<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";

$erro = "";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET ["acao"]) > 0) $acao = strtoupper($_GET ["acao"]);

$layout_menu = "financeiro";
$title = "Pré Fechamento de Extrato";

include "cabecalho.php";

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<script language="JavaScript">
function fnc_pesquisa_posto (campo, campo2, tipo) {
	if (tipo == "nome" ) {
		var xcampo = campo;
	}

	if (tipo == "cnpj" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=300, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.nome	= campo;
		janela.cnpj	= campo2;
		janela.focus();
	}
}
</script>

<script language="JavaScript" src="js/cal2.js"></script>
<script language="JavaScript" src="js/cal_conf2.js"></script>

<? if (strlen($erro) > 0){ ?>
<br>
<table width="420" border="0" cellpadding="2" cellspacing="0" align="center" class="error">
	<tr>
		<td><? echo $erro; ?></td>
	</tr>
</table>
<? } ?>


<?

$data_limite = $_POST['data_limite_01'];

?>

<table width="500" border="0" cellpadding="2" cellspacing="0" align="center">

<form method="post" action="<?echo $PHP_SELF?>" name="FormExtrato">
<input type="hidden" name="btn_acao">
	<tr class="Titulo">
		<td height="30">Informe a Data Limite do Fechamento das OS para geração dos extratos.</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td COLSPAN='2'>
			Data Limite<br>
			<input type="text" name="data_limite_01" size="13" maxlength="10" value="<? if (strlen($data_limite) > 0) echo $data_limite; else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
			<img src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="javascript: showCal('DataLimite01');" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
	</tr>
	<?
echo "<TR  class='Conteudo' bgcolor='#D9E2EF'>\n";
echo "	<TD  ALIGN='center' nowrap>";
echo "CNPJ";
echo "		<input type='text' name='posto_codigo' size='15' value='$posto_codigo' class='frm'>&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' style='cursor: pointer;' onclick=\"javascript: fnc_pesquisa_posto (document.FormExtrato.posto_nome,document.FormExtrato.posto_codigo,'cnpj')\">";

echo "&nbsp;&nbsp;Razão Social ";
echo "<input type='text' name='posto_nome' size='30' value='$posto_nome' class='frm'>&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto (document.FormExtrato.posto_nome,document.FormExtrato.posto_codigo,'nome')\" style='cursor: pointer;'>";
echo "	</TD>";
echo "<TR>\n";
?>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td align="center"><img src="imagens_admin/btn_pesquisar_400.gif" onClick="javascript: document.FormExtrato.btn_acao.value='BUSCAR'; document.FormExtrato.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</form>
</table>

<?
$btn_acao    = $_POST['btn_acao'];
$data_limite = $_POST['data_limite_01'];
$posto_codigo = $_POST['posto_codigo'];
$posto_nome   = $_POST['posto_nome'];
if (strlen ($btn_acao) > 0) {
	$data_limite = str_replace("-", "", $data_limite);
	$data_limite = str_replace("_", "", $data_limite);
	$data_limite = str_replace(".", "", $data_limite);
	$data_limite = str_replace(",", "", $data_limite);
	$data_limite = str_replace("/", "", $data_limite);
	
	$data_limite = substr ($data_limite,4,4) . "-" . substr ($data_limite,2,2) . "-" . substr ($data_limite,0,2)." 23:59:59";
	$cond_0 = " 1=1 ";
	if(strlen($posto_codigo)>0){
		$posto_codigo = str_replace("-", "", $posto_codigo);
		$posto_codigo = str_replace("_", "", $posto_codigo);
		$posto_codigo = str_replace(".", "", $posto_codigo);
		$posto_codigo = str_replace(",", "", $posto_codigo);
		$posto_codigo = str_replace("/", "", $posto_codigo);

		$sql = "Select posto from tbl_posto where cnpj='$posto_codigo'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$posto = pg_result($res,0,0);
			$cond_0 = " tbl_os.posto           = $posto ";
		}
	}

	$sql = "SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.posto, tbl_posto.nome, os.qtde, os.mo
			FROM   tbl_posto
			JOIN   tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN  (SELECT tbl_os.posto, COUNT(*) AS qtde, sum(tbl_os.mao_de_obra) as mo
					FROM tbl_os
					JOIN tbl_os_extra USING (os)
					WHERE  tbl_os.finalizada      IS NOT NULL 
					AND    tbl_os.data_fechamento IS NOT NULL 
					AND    tbl_os.data_fechamento <= '$data_limite' 
					AND    tbl_os.excluida        IS NOT TRUE 
					AND    tbl_os_extra.extrato   IS NULL 
					AND    tbl_os.fabrica          = $login_fabrica
					AND    $cond_0
					GROUP BY tbl_os.posto
			) os ON tbl_posto.posto = os.posto
			ORDER BY tbl_posto.nome";
//echo $sql; //exit;
	$res = pg_exec ($con,$sql) ;
echo "<BR><BR>";
	echo "<form method='post' name='frm_extrato' action='$PHP_SELF'>";
	echo "<table width='500' align='center'>";
	echo "<tr align='center' bgcolor='#D9E2EF'>";
	echo "<td><b>Código</b></td>";
	echo "<td><b>Nome</b></td>";
	echo "<td><b>Qtde OS</b></td>";
	echo "<td><b>Mão-de-obra</b></td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$posto = pg_result ($res,$i,posto);

		echo "<tr align='left' style='font-size:10px' id='linha_$i' >";
		echo "<td align='center'>" . pg_result ($res,$i,codigo_posto) . "</td>";
		echo "<td>" . pg_result ($res,$i,nome) . "</td>";
		echo "<td align='center'>" . pg_result ($res,$i,qtde) . "</td>";
		echo "<td align='center'>" . pg_result ($res,$i,mo) . "</td>";
		echo "</tr>";
	}


	echo "<input type='hidden' name='qtde_extrato' value='$i'>";
	echo "<input type='hidden' name='data_limite' value='$data_limite'>";
	echo "</table>";
echo "</form>";
}

echo "<br>";

include "rodape.php";

?>
