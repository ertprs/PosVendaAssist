<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";

//HD6481 - Tectoy
if ($login_fabrica <> 2 and $login_fabrica <> 11) {
	echo "<h1><center>Fechamento de Extrato realizado pela TELECONTROL</center></h1>";
	exit;
}



$erro = "";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET ["acao"]) > 0) $acao = strtoupper($_GET ["acao"]);


$btn_extrato = $_POST['btn_extrato'];

if (strlen ($btn_extrato) > 0) {
	$qtde_extrato = $_POST['qtde_extrato'];

	for ($i = 0 ; $i < $qtde_extrato ; $i++) {
		$posto = $_POST['gerar_' . $i];
		if (strlen ($posto) > 0) {
			$res = pg_exec($con,"BEGIN TRANSACTION");

			$sql = "SELECT fn_fechamento_extrato ($posto, $login_fabrica, '$data_limite'::date);";
			$res = pg_exec($con,$sql);

			$extrato = pg_result($res,0,0);
			$erro .= pg_errormessage($con);

			if (strlen($erro) == 0 AND strlen($extrato) > 0){
				$sql = "SELECT fn_calcula_extrato($login_fabrica, $extrato)";
				$res = pg_exec ($con,$sql);

				if ( ($login_fabrica <> 6) and ($login_fabrica <> 11) ) {
					$sql = "SELECT fn_aprova_extrato($posto, $login_fabrica, $extrato)";
					$res = pg_exec ($con,$sql);
					$erro .= pg_errormessage($con);
					$res = pg_exec ($con,"UPDATE tbl_extrato SET liberado = aprovado WHERE extrato = $extrato");
				}
			}

			if (strlen($erro) > 0) break;
		}
	}

	if (strlen($erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		header("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}



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
function fnc_pesquisa_posto(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
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
		<td>
			Data Limite<br>
			<input type="text" name="data_limite_01" size="13" maxlength="10" value="<? if (strlen($data_limite) > 0) echo $data_limite; else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
			<img src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="javascript: showCal('DataLimite01');" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td align="center"><img src="imagens_admin/btn_pesquisar_400.gif" onClick="javascript: document.FormExtrato.btn_acao.value='BUSCAR'; document.FormExtrato.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</form>
</table>

<?
$btn_acao    = $_POST['btn_acao'];
$data_limite = $_POST['data_limite_01'];
if (strlen ($btn_acao) > 0) {
	$data_limite = str_replace("-", "", $data_limite);
	$data_limite = str_replace("_", "", $data_limite);
	$data_limite = str_replace(".", "", $data_limite);
	$data_limite = str_replace(",", "", $data_limite);
	$data_limite = str_replace("/", "", $data_limite);
	
	$data_limite = substr ($data_limite,4,4) . "-" . substr ($data_limite,2,2) . "-" . substr ($data_limite,0,2)." 23:59:59";

	$sql = "SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.posto, tbl_posto.nome, os.qtde
			FROM   tbl_posto
			JOIN   tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN  (SELECT tbl_os.posto, COUNT(*) AS qtde
					FROM tbl_os
					JOIN tbl_os_extra USING (os)
					WHERE  tbl_os.finalizada      IS NOT NULL 
					AND    tbl_os.data_fechamento IS NOT NULL 
					AND    tbl_os.data_fechamento <= '$data_limite' 
					AND    tbl_os.excluida        IS NOT TRUE 
					AND    tbl_os_extra.extrato   IS NULL 
					AND    tbl_os.fabrica          = $login_fabrica
					AND    tbl_os.posto           <> 6359
					GROUP BY tbl_os.posto
			) os ON tbl_posto.posto = os.posto
			ORDER BY tbl_posto.nome";
	$res = pg_exec ($con,$sql) ;

	echo "<table width='500' align='center'>";
	echo "<form method='post' name='frm_extrato' action='$PHP_SELF'>";
	echo "<tr align='center' bgcolor='#D9E2EF'>";
	echo "<td><b>Fechar</b></td>";
	echo "<td><b>Código</b></td>";
	echo "<td><b>Nome</b></td>";
	echo "<td><b>Qtde OS</b></td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$posto = pg_result ($res,$i,posto);

		echo "<tr align='left' style='font-size:10px' id='linha_$i' >";
		echo "<td><input type='checkbox' name='gerar_$i' value='$posto' onclick=\"javascript: if (this.checked) { linha_$i.bgColor='#eeeeee' }else{ linha_$i.bgColor='#ffffff' } \" ></td>";
		echo "<td>" . pg_result ($res,$i,codigo_posto) . "</td>";
		echo "<td>" . pg_result ($res,$i,nome) . "</td>";
		echo "<td align='right'>" . pg_result ($res,$i,qtde) . "</td>";
		echo "</tr>";
	}


	echo "<input type='hidden' name='qtde_extrato' value='$i'>";
	echo "<input type='hidden' name='data_limite' value='$data_limite'>";

	echo "<tr bgcolor='#D9E2EF'>";
	echo "<td colspan='4' align='center'>";
	echo "<center><input type='submit' name='btn_extrato' value='Gerar Extratos'></center>";
	echo "</td>";
	echo "</tr>";
	echo "</form>";


	echo "</table>";
}

echo "<br>";

include "rodape.php";

?>
