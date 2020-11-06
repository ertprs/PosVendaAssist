<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastro";
include 'autentica_admin.php';

$btn_acao = trim(strtolower($_POST['btn_acao']));

$msg_erro = "";

if (strlen($_GET ['posto']) > 0) $posto = trim($_GET ['posto']);
if (strlen($_POST['posto']) > 0) $posto = trim($_POST['posto']);

if (strlen($_GET ['peca_referencia']) > 0) $peca = trim($_GET ['peca_referencia']);
if (strlen($_POST['peca_referencia']) > 0) $peca = trim($_POST['peca_referencia']);

if (strlen ($peca) > 0) {
	$sql = "SELECT peca FROM tbl_peca where referencia = '$peca' AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$peca_referencia = pg_result ($res,0,0);
}

if (strlen ($posto) > 0) {
	$sql = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $posto";
	$res = pg_exec ($con,$sql);
	$codigo_posto = pg_result ($res,0,0);

}

##### G R A V A R   P E D I D O #####
if ($btn_acao == "gravar") {

	$peca = trim ($_POST['peca_referencia']);
	$qtde = trim ($_POST['qtde']);

	$sql = "SELECT peca FROM tbl_peca where referencia = '$peca' AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$peca = pg_result ($res,0,0);

	$sql = "INSERT INTO tbl_peca_represada (fabrica, posto, peca, qtde, ativo) VALUES ($login_fabrica, $posto, $peca, $qtde, 't')";
	$res = @pg_exec ($con,$sql);

	$sql = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$codigo_posto = pg_result ($res,0,0);

	$peca_referencia = "";
	$peca_descricao  = "";
	$qtde = "";

}



$layout_menu = "cadastro";
$title       = "PEÇAS REPRESADAS";

include "cabecalho.php";

?>

<script language="JavaScript">

function fnc_pesquisa_peca (campo, campo2, tipo) {

	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		peca_referencia	= campo;
		peca_descricao	= campo2;
		janela.focus();
	}
	else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}

function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
	else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}
</script>

<style type="text/css">


.titulo_coluna{
background-color:#596d9b;
font: bold 11px "Arial";
color:#FFFFFF;
text-align:center;
}
.titulo_tabela{
background-color:#596d9b;
font: bold 14px "Arial";
color:#FFFFFF;
text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
	text-align:center;

}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 0px ;
	color:#ffffff;
	background-color: #596D9B
}

.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px;
}

</style>

<? if (strlen ($msg_erro) > 0) { ?>
<br>
<table class="table" width="700" border="0" cellpadding="0" cellspacing="0" >
	<tr>
		<td valign="middle" align="center" class='error'>
			<?
			if (strpos($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0)
				$msg_erro = "Esta ordem de serviço já foi cadastrada";

			echo $msg_erro;
			?>
		</td>
	</tr>
</table>
<? } ?>



<?

if (strlen ($posto) == 0) $codigo_posto = $_POST['codigo_posto'];

if (strlen (trim ($codigo_posto)) == 0) {
	echo "<form name='frm_pedido' method='post' action='$PHP_SELF'>";
	echo "<table width='700' align='center' border='0' cellspacing='0' cellpadding='3' class='formulario'>";
	echo "<tr class='titulo_tabela'>";
	echo "<td colspan='3' >Cadastro de Peças Represadas</td>";
	echo "</tr>";
	echo "<tr >";
	echo "<td width='70' >&nbsp;</td>";
	echo "<td align='left'>";
	echo "Distribuidor";
	echo "</td>";
	echo "<td align='left'>";
	echo "Razão Social";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td bgcolor='#D9E2EF'>&nbsp;</td>";
	echo "<td align='left' bgcolor='#D9E2EF' >";
	echo "<input type='text' name='codigo_posto' size='14' maxlength='14' value='$codigo_posto' class='frm'>&nbsp;<img src='../imagens/lupa.png' style='cursor: pointer;' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto2 (document.frm_pedido.codigo_posto,document.frm_pedido.nome_posto,'codigo')\">";
	echo "</td>";
	echo "<td align='left' bgcolor='#D9E2EF' >\n";
	echo "<input type='text' name='nome_posto' size='50' maxlength='60' value='$nome_posto' class='frm'>&nbsp;<img src='../imagens/lupa.png' style='cursor: pointer;' border='0' 	align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto2 (document.frm_pedido.codigo_posto,document.frm_pedido.nome_posto,'nome')\">\n";
	echo "</td>";
	echo "</tr>";
	echo '<tr>';
	echo '<td align=\'center\' colspan=\'3\' bgcolor=\'#D9E2EF\'>';
	echo "<input type='submit' name='btn_acao' value='Lançar Peças Represadas'>";
	echo '</td>';
	echo '</tr>';
	echo "</table>";
	echo "</form>";
}

?>

<br>

<?
if (strlen ($posto) == 0) $codigo_posto = $_POST['codigo_posto'];
if (strlen (trim ($codigo_posto)) > 0) {

	//echo "<center><a href='$PHP_SELF' style='font:bold 14px Arial;'>Ver outro Posto</a></center><p>";

	$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto'";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
		echo "<h1>Código do posto não cadastrado</h1>";
		exit;
	}
	$posto = pg_result ($res,0,0);

	$sql = "SELECT nome, cidade FROM tbl_posto WHERE posto = $posto";
	$res = pg_exec ($con,$sql);

	//echo "<center><b>$codigo_posto - " . pg_result ($res,0,nome) . " - " . pg_result ($res,0,cidade) . "</b></center>";
	echo "<table align='center' width='700'>";
	echo "<tr class='titulo_tabela'>";
	echo "<td colspan='3'>";
	echo "Distribuidor: ".$codigo_posto." - " . pg_result ($res,0,nome) . " - " . pg_result ($res,0,cidade);
	echo "</td>";
	echo "</tr>";
	echo "</table>";

	$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_peca_represada.qtde, tbl_peca_represada.ativo, os_qtde.os_qtde FROM tbl_peca_represada JOIN tbl_peca USING (peca) LEFT JOIN (SELECT peca_represada, SUM (qtde) AS os_qtde FROM tbl_os_item WHERE peca_represada = tbl_peca_represada.peca_represada GROUP BY tbl_os_item.peca_represada) os_qtde USING (peca_represada) WHERE tbl_peca_represada.posto = $posto ORDER BY tbl_peca.referencia";
	$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_peca_represada.qtde, tbl_peca_represada.ativo, (SELECT SUM(qtde) FROM tbl_os_item WHERE peca_represada = tbl_peca_represada.peca_represada) AS os_qtde FROM tbl_peca_represada JOIN tbl_peca USING (peca) WHERE tbl_peca_represada.posto = 6359 ORDER BY tbl_peca.referencia";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		echo "<table width='700px' align='center' border='0' class='formulario'>";
		echo "<tr class='titulo_tabela'>";
		echo "<td nowrap colspan='4'>Peças Já Represadas Neste Posto</td>";
		echo "</tr>";
		echo "<tr><td colspan='4'>&nbsp;</td></tr>";
		echo "<tr class='titulo_coluna'>";
		echo "<td nowrap>Referência</td>";
		echo "<td nowrap>Descrição</td>";
		echo "<td nowrap>Represar</td>";
		echo "<td nowrap>Já Represada</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = "#F7F5F0";
			if ($i % 2 == 0)
			{
				$cor = '#F1F4FA';
			}
			echo "<tr style='font-size: 11px' bgcolor='$cor'>";

			echo "<td nowrap align='left'>";
			echo pg_result ($res,$i,referencia);
			echo "</td>";

			echo "<td nowrap align='left'>";
			echo pg_result ($res,$i,descricao);
			echo "</td>";

			echo "<td nowrap align='right'>";
			echo pg_result ($res,$i,qtde);
			echo "</td>";

			echo "<td nowrap align='right'>";
			echo pg_result ($res,$i,os_qtde);
			echo "</td>";

			echo "</tr>";
		}

		echo "</table>";

	}

	echo "<form name='frm_pedido' method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='posto' value='$posto'>";
	echo "<table width='700px' align='center' border='0' cellspacing='0' class='formulario'>";
	echo "<tr><td colspan='4'>&nbsp;</td></tr>";
	echo "<tr>";
	echo "<td width='50'>&nbsp;</td>";
	echo "<td nowrap align='left'>Referência</td>";
	echo "<td nowrap align='left'>Descrição</td>";
	echo "<td nowrap align='left'>Qtde Represar</td>";

	echo "</tr>";

	echo "<tr>";
	echo "<td>&nbsp;</td>";
	echo "<td nowrap align='left'>";
	echo "<input type='text' name='peca_referencia' size='15' value='$peca_referencia' class='frm'> <img src='../imagens/lupa.png' alt='Clique para pesquisar por referência da peça' border='0' hspace='5' align='absmiddle' onclick=\"fnc_pesquisa_peca (document.frm_pedido.peca_referencia, window.document.frm_pedido.peca_descricao, 'referencia')\" style='cursor: pointer;'>";
	echo "</td>";

	echo "<td nowrap align='left'>";
	echo "<input type='text' name='peca_descricao' size='25' value='$peca_descricao' class='frm'> <img src='../imagens/lupa.png' alt='Clique para pesquisar por referência da peça' border='0' hspace='5' align='absmiddle' onclick=\"fnc_pesquisa_peca (document.frm_pedido.peca_referencia, window.document.frm_pedido.peca_descricao, 'descricao')\" style='cursor: pointer;'>";
	echo "</td>";

	echo "<td nowrap align='left'>";
	echo "<input type='text' name='qtde' size='8'  value='$qtde' class='frm'>";
	echo "</td>";

	echo "</tr>";

	echo "<tr>";
	echo "<td colspan='4'>";
	echo "&nbsp;";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td colspan='4'>";
	echo "<input type='button' value='&nbsp;' style='background:url(imagens_admin/btn_gravar.gif); width:75px;cursor:pointer;' onclick=\"javascript: if (document.frm_pedido.btn_acao.value == '') { document.frm_pedido.btn_acao.value='gravar'; document.frm_pedido.submit() }else{ alert('Aguarde submissão') }\" ALT='Gravar formulário' border='0' >";
	echo "</td>";
	echo "</tr>";
	echo "</table>";

	echo "<input type='hidden' name='btn_acao' value=''>";

	echo "</form>";
}

?>



<?
echo "<hr>";
echo "<p>";

echo "<table width='700px' align='center' border='0' cellspacing='1'>";
echo "<tr class='titulo_tabela'>";
echo "<td colspan='2'>
		Postos com Peças Represadas
	  </td>";
echo "</tr>";
echo "<tr class='titulo_coluna' style='background-color: #7092BE'>";

echo "<td nowrap>Código</td>";
echo "<td nowrap>Razão Social</td>";

echo "</tr>";

$sql = "SELECT DISTINCT tbl_posto.posto, tbl_posto_fabrica.codigo_posto, tbl_posto.nome FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica JOIN tbl_peca_represada ON tbl_posto.posto = tbl_peca_represada.posto WHERE tbl_peca_represada.ativo ORDER BY tbl_posto.nome";
$res = pg_exec ($con,$sql);

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	$cor = "#F7F5F0";
		if ($i % 2 == 0)
		{
			$cor = '#F1F4FA';
		}
	echo "<tr  bgcolor='$cor' style='font:bold 11px Arial;'>";

	echo "<td align='left'>";
	echo "<a href='$PHP_SELF?posto=" . pg_result ($res,$i,posto) . "'>";
	echo pg_result ($res,$i,codigo_posto);
	echo "</a>";
	echo "</td>";

	echo "<td align='left'>";
	echo pg_result ($res,$i,nome);
	echo "</td>";

	echo "</tr>";
}

echo "</table>";

?>


<? include "rodape.php"; ?>
