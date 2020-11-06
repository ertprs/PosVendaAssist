<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';

include 'funcoes.php';

$msg_erro = "";

$layout_menu = "auditoria";
$title = "Relatório de Postos com OSs Abertas";

include "cabecalho.php";

include "javascript_pesquisas.php";

?>

<script language="JavaScript" src="js/cal2.js"></script>
<script language="JavaScript" src="js/cal_conf2.js"></script>

<script language="JavaScript">
// ========= Função PESQUISA DE POSTO POR CÓDIGO OU NOME ========= //
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}

</script>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}
.topo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
</style>

<p>

<?
$btn_acao = strtolower($_POST['btn_acao']);

$posto_codigo = trim($_POST["posto_codigo"]);
$posto_nome   = trim($_POST["posto_nome"]);
$uf           = trim($_POST["uf"]);

if (strlen($posto_codigo) == 0 AND strlen($posto_nome) == 0 AND strlen($uf) == 0 AND strlen($btn_acao) > 0)
	$msg_erro = " Preencha pelo menos um dos campos. ";

if (strlen($msg_erro) > 0) { ?>
<table width='500' align='center' border='0' cellspacing='2' cellpadding='2'>
	<tr class='error'>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<? } ?>

<form name='frm_os_posto' action='<? echo $PHP_SELF ?>' method="POST">
<input type="hidden" name='btn_acao' value="">

<table width='500' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class='topo'>
	<td colspan='2'>Pesquisa por Posto ou Estado</td>
</tr>
<tr class='menu_top'>
	<td>Código do Posto</td>
	<td>Nome do Posto</td>
</tr>
<tr>
	<td>
		<input class="frm" type="text" name="posto_codigo" size="13" value="<? echo $posto_codigo ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'codigo')">
	</td>
	<td>
		<input class="frm" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens_admin/btn_lupa.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'nome')" style="cursor:pointer;">
	</td>
</tr>
<tr class='menu_top'>
	<td>Estado</td>
	<td></td>
</tr>
<?
	/* Fucao que exibe os Estados (UF) */
	function selectUF($selUF=""){
		$cfgUf = array("","AC","AL","AM","AP","BA","CE","DF","ES","GO","MA","MG","MS","MT","PA","PB","PI","PR","RJ","RN","RO","RR","RS","SC","SE","SP","TO");
		if($selUF == "") $selUF = $cfgUf[0];

		$totalUF = count($cfgUf) - 1;
		for($currentUF=0; $currentUF <= $totalUF; $currentUF++){
			echo "                      <option value=\"$cfgUf[$currentUF]\"";
			if($selUF == $cfgUf[$currentUF]) print(" selected");
			echo ">$cfgUf[$currentUF]</option>\n";
		}
	}
?>
<tr>
	<td>
		<select name='uf' class="frm">
			<? selectUF($uf); ?>
		</select>
	</td>
	<td>
	</td>
</tr>
</table>

<center>
<img src='imagens_admin/btn_confirmar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_os_posto.btn_acao.value == '' ) { document.frm_os_posto.btn_acao.value='continuar' ; document.frm_os_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Confirmar" border='0'>
</center>

</form>

<br>

<?

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0){

	if (strlen(trim($_POST['data_inicial'])) > 0){
		$data_inicial = trim($_POST['data_inicial']);
		$data_inicial = fnc_formata_data_pg($data_inicial);
	}

	if (strlen(trim($_POST['data_final'])) > 0){
		$data_final = trim($_POST['data_final']);
		$data_final = fnc_formata_data_pg($data_final);
	}

	$posto_codigo = trim($_POST['posto_codigo']);

	if (strlen($posto_codigo) > 0){
		$sqlPosto = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$posto_codigo' and fabrica = $login_fabrica";
		$res = pg_exec($con,$sqlPosto);
		if (pg_numrows($res) > 0){
			$posto = pg_result($res,0,0);
		}
	}

	$uf = trim($_POST['uf']);

	#--------------------------------------------------------------------------
	echo "<table width='700'>";
	echo "<tr class='topo'><td height='50'>OSs EM ABERTO</td></tr>";
	echo "</table>";

	#----------------------- OS Até 5 dias de aberto --------------------------

	$sql =	"SELECT DISTINCT
					tbl_os.sua_os                                               ,
					tbl_posto.nome                                              ,
					tbl_posto_fabrica.codigo_posto                              ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
					(
						SELECT COUNT (tbl_os_item.*) AS qtde_item
						FROM   tbl_os_item
						JOIN   tbl_os_produto USING (os_produto)
						WHERE  tbl_os_produto.os = tbl_os.os
					)                                          AS qtde_item     
			FROM      tbl_posto
			JOIN      tbl_os            ON  tbl_os.posto              = tbl_posto.posto
			LEFT JOIN tbl_os_produto    ON  tbl_os_produto.os         = tbl_os.os
			LEFT JOIN tbl_os_item       ON  tbl_os_item.os_produto    = tbl_os_produto.os_produto
			JOIN      tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
									  AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE     tbl_os.fabrica            = $login_fabrica
			AND       tbl_posto_fabrica.fabrica = $login_fabrica
			AND       tbl_os.data_abertura::date BETWEEN (current_date - INTERVAL '5 days')::date AND (current_date)::date ";

	if (strlen($posto) > 0) $sql .= " AND tbl_os.posto = $posto ";
	if (strlen($uf) > 0)    $sql .= " AND tbl_posto.estado = '$uf' ";

	$sql .= " ORDER BY tbl_posto_fabrica.codigo_posto, tbl_os.sua_os;";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='700'>";
		echo "<tr class='menu_top'>";
		echo "<td colspan='4'>OSs ATÉ 5 DIAS DE ABERTO </td>";
		echo "</tr>";
		echo "<tr class='menu_top'>";
		echo "<td width='20%'>NÚMERO DA OS</td>";
		echo "<td>CÓDIGO DO POSTO</td>";
		echo "<td>NOME DO POSTO</td>";
		echo "<td width='15%'>DATA DE ABERTURA</td>";
		echo "<td width='16'>ITEM</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$cor = "#F7F5F0";
			if ($i % 2 == 0) $cor = '#F1F4FA';

			echo "<tr class='table_line'  style='background-color: $cor;'>";
			echo "<td align='left'>".pg_result($res,$i,codigo_posto).pg_result($res,$i,sua_os)."</td>";
			echo "<td align='left'>".pg_result($res,$i,codigo_posto)."</td>";
			echo "<td align='left'>".pg_result($res,$i,nome)."</td>";
			echo "<td align='center'>".pg_result ($res,$i,data_abertura)."</td>";
			echo "<td align='center'>"; 
			$qtde_item          = trim(pg_result ($res,$i,qtde_item));
			if ($qtde_item > 0) echo"<img src='imagens/img_ok.gif' alt='OS com item'>";
			else                echo "&nbsp;";
			echo "</td>";
			echo "</tr>";
		}

		echo "</table>";
		flush();
		echo "<br>";
	}

	#----------------------- Sem fechamento há mais de 20 dias  --------------------------

	$sql = "SELECT  distinct
					tbl_os.sua_os                                               ,
					tbl_posto.nome                                              ,
					tbl_posto_fabrica.codigo_posto                              ,
					to_char(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
					(
						SELECT COUNT (tbl_os_item.*) AS qtde_item
						FROM   tbl_os_item
						JOIN   tbl_os_produto USING (os_produto)
						WHERE  tbl_os_produto.os = tbl_os.os
					)                                          AS qtde_item     
			FROM      tbl_posto
			JOIN      tbl_os            ON tbl_os.posto           = tbl_posto.posto
			LEFT JOIN tbl_os_produto    ON tbl_os_produto.os      = tbl_os.os
			LEFT JOIN tbl_os_item       ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIN      tbl_posto_fabrica ON tbl_posto.posto        = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica  = $login_fabrica
			WHERE     tbl_os.fabrica            = $login_fabrica
			AND       tbl_posto_fabrica.fabrica = $login_fabrica
			AND       tbl_os.data_abertura::date BETWEEN (current_date - INTERVAL '30 days')::date AND (current_date - INTERVAL '20 days')::date ";

	if (strlen($posto) > 0) $sql .= " AND tbl_os.posto = $posto ";
	if (strlen($uf) > 0)    $sql .= " AND tbl_posto.estado = '$uf' ";

	$sql .= " ORDER BY tbl_posto_fabrica.codigo_posto;";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='700'>";
		echo "<tr class='menu_top'>";
		echo "<td COLSPAN=4>OSs SEM FECHAMENTO HÁ MAIS DE 20 DIAS </td>";
		echo "</tr>";
		echo "<tr class='menu_top'>";
		echo "<td width='20%'>NÚMERO DA OS</td>";
		echo "<td>CÓDIGO DO POSTO</td>";
		echo "<td>NOME DO POSTO</td>";
		echo "<td width='15%'>DATA DE ABERTURA</td>";
		echo "<td width='16'>ITEM</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$cor = "#F7F5F0";
			if ($i % 2 == 0) $cor = '#F1F4FA';

			echo "<tr class='table_line'  style='background-color: $cor;'>";
			echo "<td align='left'>".pg_result($res,$i,codigo_posto).pg_result($res,$i,sua_os)."</td>";
			echo "<td align='left'>".pg_result($res,$i,codigo_posto)."</td>";
			echo "<td align='left'>".pg_result($res,$i,nome)."</td>";
			echo "<td align='center'>".pg_result ($res,$i,data_abertura)."</td>";
			echo "<td align='center'>"; 
			$qtde_item          = trim(pg_result ($res,$i,qtde_item));
			if ($qtde_item > 0) echo"<img src='imagens/img_ok.gif' alt='OS com item'>";
			else                echo "&nbsp;";
			echo "</td>";
			echo "</tr>";
		}
		echo "</table>";
		flush();
		echo "<br>";
	}

	#----------------------- OSs que excederam o prazo limite de 30 dias --------------------------

	$sql = "SELECT  distinct
					tbl_os.sua_os                                               ,
					tbl_posto.nome                                              ,
					tbl_posto_fabrica.codigo_posto                              ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
					(
						SELECT COUNT (tbl_os_item.*) AS qtde_item
						FROM   tbl_os_item
						JOIN   tbl_os_produto USING (os_produto)
						WHERE  tbl_os_produto.os = tbl_os.os
					)                                          AS qtde_item     
			FROM      tbl_posto
			JOIN      tbl_os            ON tbl_os.posto           = tbl_posto.posto
			LEFT JOIN tbl_os_produto    ON tbl_os_produto.os      = tbl_os.os
			LEFT JOIN tbl_os_item       ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIN      tbl_posto_fabrica ON tbl_posto.posto        = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica  = $login_fabrica
			WHERE     tbl_os.fabrica            = $login_fabrica
			AND       tbl_posto_fabrica.fabrica = $login_fabrica
			AND       tbl_os.data_abertura::date < (current_date - INTERVAL '30 days')::date ";

	if (strlen($posto) > 0) $sql .= " AND tbl_os.posto = $posto ";
	if (strlen($uf) > 0)    $sql .= " AND tbl_posto.estado = '$uf' ";

	$sql .= " ORDER BY tbl_posto_fabrica.codigo_posto;";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='700'>";
		echo "<tr class='menu_top'>";
		echo "<td COLSPAN=4>OSs QUE EXCEDERAM O PRAZO LIMITE DE 30 DIAS</td>";
		echo "</tr>";
		echo "<tr class='menu_top'>";
		echo "<td width='20%'>NÚMERO DA OS</td>";
		echo "<td>CÓDIGO DO POSTO</td>";
		echo "<td>NOME DO POSTO</td>";
		echo "<td width='15%'>DATA DE ABERTURA</td>";
		echo "<td width='16'>ITEM</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = "#F7F5F0";
			if ($i % 2 == 0) $cor = '#F1F4FA';

			echo "<tr class='table_line'  style='background-color: $cor;'>";
			echo "<td align='left'>".pg_result($res,$i,codigo_posto).pg_result($res,$i,sua_os)."</td>";
			echo "<td align='left'>".pg_result($res,$i,codigo_posto)."</td>";
			echo "<td align='left'>".pg_result($res,$i,nome)."</td>";
			echo "<td align='center'>".pg_result ($res,$i,data_abertura)."</td>";
			echo "<td align='center'>"; 
			$qtde_item          = trim(pg_result ($res,$i,qtde_item));
			if ($qtde_item > 0) echo"<img src='imagens/img_ok.gif' alt='OS com item'>";
			else                echo "&nbsp;";
			echo "</td>";
			echo "</tr>";
		}
		echo "</table>";
		flush();
		echo "<br>";
	}

	echo "<br>";

}

echo "<br>";

include "rodape.php"; 

?>