<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";

if ($btn_acao == "gravar") {

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$qtde_item = $_POST['qtde_item'];

	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$cnpj_posto	= $_POST['cnpj_posto_'    . $i];
		$nota_fiscal= $_POST['nota_fiscal_' . $i];
		$serie		= $_POST['serie_' . $i];
		$data_emissao=$_POST['data_emissao_' . $i];
		$valor_total= $_POST['valor_total_' . $i];

		if (strlen ($cnpj_posto) > 0) {
			$cnpj_posto = str_replace (".","",$cnpj_posto);
			$cnpj_posto = str_replace (" ","",$cnpj_posto);
			$cnpj_posto = str_replace ("-","",$cnpj_posto);
			$cnpj_posto = str_replace ("/","",$cnpj_posto);

			$res = pg_exec ($con,"SELECT posto FROM tbl_posto_fabrica JOIN tbl_posto USING (posto) WHERE tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto.cnpj = '$cnpj_posto'");
			if (pg_numrows ($res) == 0) {
				$msg_erro = "Posto $posto não cadastrado";
				$linha_erro = $i;
				break;
			}else{
				$posto = pg_result ($res,0,posto);
			}

			if (strlen ($data_emissao) == 0)
				$msg_erro = "Data Inválida";

			if (strlen ($msg_erro) == 0) {

				$data_emissao = formata_data ($data_emissao);
				$valor_total  = str_replace (",",".",$valor_total);

				$sql = "INSERT INTO tbl_devolucao (
							posto, 
							nota_fiscal, 
							serie, 
							data_emissao, 
							valor_total
						) VALUES (
							$posto, 
							LPAD ('$nota_fiscal',6,'0') , 
							SUBSTR (TRIM ('$serie'),0,3) , 
							'$data_emissao', 
							$valor_total 
						)";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
				if (strlen($msg_erro) > 0) {
					break ;
				}
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$layout_menu = "financeiro";
$title = "Cadastro de Prestação de Serviço";
include 'cabecalho.php';

?>
<head>
	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />
</head>

<html>
<body onload='javascript: document.frm_prestacao.cnpj_posto_0.focus()'>

<script language="JavaScript">
function fnc_pesquisa_posto(campo, campo2, tipo) {

	alert(campo.value);

	if (tipo == "cnpj"){
		var xcampo = campo;
	}

	if (tipo == "nome"){
		var xcampo = campo2;
	}
	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.cnpj = campo;
		janela.nome = campo2;
		janela.focus();
	}
}
</script>


<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js"></script>


<? 
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";

?>
<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
<tr>
	<td height="27" valign="middle" align="center">
		<b><font face="Arial, Helvetica, sans-serif" color="#FF3333">
		<? echo $msg_erro ?>
		</font></b>
	</td>
</tr>
</table>
<? } ?>

<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">

<tr>
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	<td valign="top" align="center">
		<!-- ------------- Formulário ----------------- -->
		<form name="frm_prestacao" method="post" action="<? echo $PHP_SELF ?>">
		<p>
		<table width="400" border="0" cellspacing="5" cellpadding="0" align='center'>
		<tr height="20" bgcolor="#bbbbbb">
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>CNPJ Posto</b></font></td>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Nome Posto</b></font></td>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Nota</b></font></td>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Série</b></font></td>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Data Emissão</b></font></td>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Valor Total</b></font></td>
		</tr>

		<?
		$qtde_item = 10;
		echo "<input class='frm' type='hidden' name='qtde_item' value='$qtde_item'>";

		for ($i = 0 ; $i < $qtde_item ; $i++) {
			if (strlen ($msg_erro) > 0) {
				$cnpj_posto		= $_POST["cnpj_posto_" . $i];
				$linha			= $_POST["linha_" . $i];
				$nota_fiscal	= $_POST["nota_fiscal_" . $i];
				$serie			= $_POST["serie_" . $i];
				$data_emissao	= $_POST["data_emissao_" . $i];
				$valor_total	= $_POST["valor_total_" . $i];
			}
		?>
		<tr <? if ($linha_erro == $i and strlen ($msg_erro) > 0) echo "bgcolor='#ffcccc' height='30' " ?>>
			<td>
				<input class='frm' type="text" name="cnpj_posto_<? echo $i ?>" size="20" value="<? echo $cnpj_posto ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto (document.frm_prestacao.cnpj_posto_<? echo $i ?>,document.frm_prestacao.nome_posto_<? echo $i ?>,'cnpj')">
			</td>
			<td>
				<input class='frm' type="text" name="nome_posto_<? echo $i ?>" size="35" value="<? echo $nome_posto ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto (document.frm_prestacao.cnpj_posto_<? echo $i ?>,document.frm_prestacao.nome_posto_<? echo $i ?>,'nome')">
			</td>
			<td>
				<input class='frm' type="text" name="nota_fiscal_<? echo $i ?>" size="7" value="<? echo $nota_fiscal ?>">
			</td>
			<td>
				<input class='frm' type="text" name="serie_<? echo $i ?>" size="5" value="<? echo $serie ?>">
			</td>
			<td>
				<input class='frm' type="text" name="data_emissao_<? echo $i ?>" size="12" value="<? echo $data_emissao ?>">
			</td>
			<td>
				<input class='frm' type="text" name="valor_total_<? echo $i ?>" size="10" value="<? echo $valor_total ?>">
			</td>
		</tr>
		<?
		}
		?>
		</table>
	</td>
	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>

<tr>
	<td height="27" valign="botton" align="center" colspan="3">
		<p>
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens_admin/btn_gravar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_prestacao.btn_acao.value == '' ) { document.frm_prestacao.btn_acao.value='gravar' ;  document.frm_prestacao.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar" border='0'>
		
	</td>
</tr>

</form>

</table>

<? include "rodape.php" ?>