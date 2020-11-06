<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

if (strlen($_GET["tipo_posto_condicao"]) > 0)  $tipo_posto_condicao = trim($_GET["tipo_posto_condicao"]);
if (strlen($_POST["tipo_posto_condicao"]) > 0) $tipo_posto_condicao = trim($_POST["tipo_posto_condicao"]);

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim($_POST["btnacao"]);

if ($btnacao == "gravar") {
	if (strlen($_POST["tabela"]) > 0)
		$aux_tabela = trim($_POST["tabela"]);
	else
		$msg_erro = "Favor informar a tabela.";

	if (strlen($_POST["tipo_posto"]) > 0)
		$aux_tipo_posto = trim($_POST["tipo_posto"]);
	else
		$msg_erro = "Favor informar o tipo de posto";


	if (strlen($msg_erro) == 0){

		$res = pg_exec ($con,"BEGIN TRANSACTION");
		echo $qtde_item;
		for ($i = 0 ; $i < $qtde_item ; $i++) {
				$condicao     = $_POST['condicao_' . $i];
				$aux_condicao = $_POST['aux_condicao_' . $i];

			if ($aux_condicao == 62) $tabela = 47;

			$sqlC = "SELECT condicao FROM tbl_posto_condicao WHERE condicao = $condicao";
			
			$resC = pg_exec($con,$sqlC);

			if(pg_numrows($resC)==0){
				if(strlen($aux_condicao) > 0 AND strlen($condicao) == 0) {
					 $sql = "SELECT fn_tipo_posto_condicao($login_fabrica,$aux_condicao,$tipo_posto,$tabela,'f')";
				}elseif(strlen($aux_condicao) > 0 AND strlen($condicao) > 0) {
					echo $sql = "SELECT fn_tipo_posto_condicao($login_fabrica,$aux_condicao,$tipo_posto,$tabela,'t')";
				}
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}

	}
	
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
		exit;
	}else{
		$tabela     = $POST["tabela"];
		$tipo_posto = $POST["tipo_posto"];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$msg = $_GET['msg'];
$layout_menu = "cadastro";
$title       = "CADASTRAMENTO DA CONDIÇÃO DE PAGAMENTO POR TIPO DE POSTO";
include 'cabecalho.php';

?>

<body>

<style type="text/css">
.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

color: #7092BE
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}
</style>

<form name="frm_tipo_posto_condicao" method="post" action="<? $PHP_SELF ?>">
<input type="hidden" name="tipo_posto_condicao" value="<? echo $tipo_posto_condicao ?>">

<? 
	echo $msg_debug;
?>

<table width='700' border='0' cellspacing='2' cellpadding='2' align='center' class="formulario">
	<? if (strlen($msg_erro) > 0) { ?>
		<tr class="msg_erro">
			<td colspan="4"><? echo $msg_erro; ?></td>
		</tr>
	<? } ?>

	<? if (strlen($msg) > 0) { ?>
		<tr class="sucesso">
			<td colspan="4"><? echo $msg; ?></td>
		</tr>
	<? } ?>

	<tr class="titulo_tabela"><td colspan="4">Cadastrar Condição de Pagamento</td></tr>
	<TR>
		<td width="200">
		<TD width='100' >Tabela</TD>
		<TD width='100' >Tipo de Posto</TD>
	</TR>
	<TR>
		<td width="200">
		<TD width='100' align="center">
			<select name="tabela" class="frm">
				<option selected></option>
<?

if ($login_fabrica == 66 or $login_fabrica == 14) {
	$sql_and = ' and ativa is true ';
}

$sql = "SELECT * 
		FROM   tbl_tabela
		WHERE  tbl_tabela.fabrica = $login_fabrica 
		$sql_and";

if ($login_fabrica == 1) {
	$sql .= "AND tbl_tabela.sigla_tabela = 'BASE2' ";
}

$sql .= "ORDER BY descricao ASC";
$res = @pg_exec ($con,$sql);
echo $sql;
if (pg_numrows($res) > 0) {
	for($i=0; $i<pg_numrows($res); $i++){
		echo "<option value='".pg_result($res,$i,tabela)."' ";
		if (pg_result($res,$i,tabela) == $tabela) echo " selected";
		echo ">".pg_result($res,$i,descricao)."</option>";
	}
}
?>
			</select>
		</TD>
		<TD width='100' align="center">
			<select name="tipo_posto" class="frm">
				<option selected></option>
<?
$sql = "SELECT * 
		FROM tbl_tipo_posto 
		WHERE fabrica = $login_fabrica
		ORDER BY descricao ASC";
$res = @pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	for($i=0; $i<pg_numrows($res); $i++){
		echo "<option value='".pg_result($res,$i,tipo_posto)."' ";
		if (pg_result($res,$i,tipo_posto) == $tipo_posto) echo " selected";
		echo ">".pg_result($res,$i,descricao)."</option>";
	}
}
?>
			</select>
		</TD>
		<td align="left">&nbsp; &nbsp;&nbsp;
			<input type="button" style='background:url(imagens_admin/btn_confirmar.gif); width:95px; cursor:pointer;' ONCLICK="javascript: if (document.frm_tipo_posto_condicao.btnacao.value == '' ) { document.frm_tipo_posto_condicao.btnacao.value='confirmar' ; document.frm_tipo_posto_condicao.submit() } else { alert ('Aguarde submissão') }" ALT="Confirma dados" border='0' value="&nbsp;">
		</td>
	</TR>
</table>

<br>

<?
if(strlen($tabela) > 0 AND strlen($tipo_posto) > 0){
	echo "<table border='0' cellspacing='2' cellpadding='5' align='center' width='700' class='formulario'>";
	echo "	<tr class='titulo_tabela'>";
	echo "		<td COLSPAN='5' >Selecione as Condições de Pagamento</td>";

	echo "	</tr>";
	echo "	<tr>";
	echo "<td width='200'>&nbsp;</td>";
	echo "		<td align='left' width='5'>";

	$sql = "SELECT	*
			FROM	tbl_condicao 
			WHERE	fabrica = $login_fabrica
			AND		tabela  = $tabela
			AND		visivel is true
			ORDER BY lpad(trim(tbl_condicao.codigo_condicao::text)::text,10,'0') ASC";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		$y=1;

		for($i=0; $i<pg_numrows($res); $i++){

			$condicao        = trim(pg_result($res,$i,condicao));
			$codigo_condicao = trim(pg_result($res,$i,codigo_condicao));
			$descricao       = trim(pg_result($res,$i,descricao));

			$sql = "select	distinct condicao, 
							visivel 
					from	tbl_posto_condicao 
					join	tbl_posto_fabrica on tbl_posto_condicao.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica =$login_fabrica
					join	tbl_tabela on tbl_posto_condicao.tabela = tbl_tabela.tabela
					where	tbl_posto_fabrica.fabrica = $login_fabrica
					and		tbl_posto_condicao.condicao = $condicao
					and		tbl_posto_fabrica.tipo_posto = $tipo_posto";
			$res2 = pg_exec($con,$sql);
//echo $sql;
			if (pg_numrows($res2) > 0) {
				$novo      = 'f';
				$visivel   = trim(pg_result($res2,0,visivel));
				$xcondicao = trim(pg_result($res2,0,condicao));
			}else{
				$novo      = 't';
				$visivel   = "f";
				$xcondicao = "";
			}

			$resto = $y % 2;
			$y++;

			$check = ($visivel == 't') ? "checked" : "";

			echo "<input type='hidden' name='novo_$i' value='$novo'>\n";
			echo "<input type='hidden' name='aux_condicao_$i' value='$condicao'>\n";
			echo "<input type='checkbox' name='condicao_$i' value='$condicao' $check></TD>\n";
			echo "<TD align='left' class='table_line1'>$codigo_condicao - $descricao";

			if($resto == 0){
				echo "</td></tr>\n";
				echo "<tr> <td width='110'>&nbsp;</td><td align='left' width='5'>\n";
			}else{
				echo "</td>\n";
				echo "<td align='left' width='5'>\n";
			}
		}
	}

	echo "<input type='hidden' name='qtde_item' value='$i'>\n";
	echo "<td width='110'>&nbsp;</td>";
	echo "<tr> <td colspan='5' align='center'>";
	?>
	<input type='button' value='Gravar' ONCLICK="javascript: if (document.frm_tipo_posto_condicao.btnacao.value == '' ) { document.frm_tipo_posto_condicao.btnacao.value='gravar' ; document.frm_tipo_posto_condicao.submit() } else { alert ('Aguarde submissão') }" ALT='Gravar formulário' border='0' style='cursor:pointer;'>
	<?
	echo "</td></tr>";
	echo "</table>";
}
?>

<div align='center'>
<input type='hidden' name='btnacao' value=''>

<!-- 
		<IMG SRC="imagens_admin/btn_apagar.gif" ONCLICK="javascript: if (document.frm_tipo_posto_condicao.btnacao.value == '' ) { document.frm_tipo_posto_condicao.btnacao.value='deletar' ; document.frm_tipo_posto_condicao.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar tipo_posto_condicao" border='0' style="cursor:pointer;">
		<IMG SRC="imagens_admin/btn_limpar.gif" ONCLICK="javascript: if (document.frm_tipo_posto_condicao.btnacao.value == '' ) { document.frm_tipo_posto_condicao.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor:pointer;">
 -->

</form>
</div>
<p>

</form>

<br>

<? include 'rodape.php'; ?>
