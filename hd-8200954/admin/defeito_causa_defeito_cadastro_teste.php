<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';


if (strlen($_GET["defeito"]) > 0) {
	$defeito = trim($_GET["defeito"]);
}

if (strlen($_POST["defeito"]) > 0) {
	$defeito = trim($_POST["defeito"]);
}

if (strlen($_POST["qtde_item"]) > 0) {
	$qtde_item = trim($_POST["qtde_item"]);
}

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}

if ($btnacao == "gravar") {
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	if (strlen($msg_erro) == 0){
		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$novo                  = $_POST['novo_'.                   $i];
			$defeito_causa_defeito = $_POST['defeito_causa_defeito_'.  $i];
			$causa_defeito         = $_POST['causa_defeito_'.          $i];
			
			if ($novo == 'f' and strlen($causa_defeito) == 0) {
				$sql = "DELETE FROM tbl_defeito_causa_defeito
						WHERE       tbl_defeito_causa_defeito.defeito_causa_defeito = $defeito_causa_defeito";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
			
			if (strlen ($msg_erro) == 0) {
				if (strlen($causa_defeito) > 0) {
					if ($novo == 't'){
						$sql = "INSERT INTO tbl_defeito_causa_defeito (
									defeito       ,
									causa_defeito
								) VALUES (
									$defeito      ,
									$causa_defeito
								)";
					}else{
						$sql = "UPDATE tbl_defeito_causa_defeito SET
									defeito       = $defeito      ,
									causa_defeito = $causa_defeito
								WHERE  tbl_defeito_causa_defeito.defeito_causa_defeito = $defeito_causa_defeito
								AND    tbl_defeito_causa_defeito.defeito               = $defeito ";
					}
					$res = @pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}
		}
	}
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$layout_menu = "cadastro";
$title = "CADASTRO DE CAUSAS DE DEFEITO POR DEFEITO";
include 'cabecalho.php';
?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef;
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff;
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

.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{

10:21 30/07/2010
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
}
</style>

<body>
<div id="wrapper">
<form name="frm_defeito" method="post" action="<? $PHP_SELF ?>">
<input type="hidden" name="defeito" value="<? echo $defeito ?>">

<? if (strlen($msg_erro) > 0) { ?>

<div class='msg_erro'>
	<? echo $msg_erro; ?>
</div>

<? } echo $msg_debug; ?>
<?php
	$msg_sucesso = $_GET['msg'];
	if( strlen( $msg_sucesso ) > 0 && strlen( $msg_erro ) ==0 )
	{
?>	
	
	<table width='700px' class='msg_sucesso' align='center'>
		<tr>
			<td> <?php echo $msg_sucesso ?></td>
		</tr>
	</table>
	
<?	}   ?>

<P>

<table align='center' class='formulario' width='700px' border='0' cellspacing='3' cellpadding='1'>
<? 
if (strlen($defeito) == 0) {
?>
	<tr>
		<td COLSPAN='6' class='titulo_tabela'>Relação de Defeitos Cadastrados</td>
	</tr>
<? 
}else{

$sql = "SELECT  descricao
		FROM    tbl_defeito
		WHERE   fabrica = $login_fabrica
		AND     defeito = $defeito";
$res = pg_exec ($con,$sql);
$descricao = trim(pg_result($res,0,descricao));
?>
	<tr>
		<td COLSPAN='9' class='titulo_coluna'>Selecione as Causas para o Defeito<br />"<? echo $descricao ;?>"</td>
	</tr>
	<tr>
	<td>&nbsp;</td>
		<td align='left'>
<?
$sql = "SELECT  tbl_causa_defeito.causa_defeito,
				tbl_causa_defeito.codigo       ,
				tbl_causa_defeito.descricao
		FROM    tbl_causa_defeito
		WHERE   tbl_causa_defeito.fabrica = $login_fabrica
		ORDER BY lpad(codigo::text,5,' ') ASC";
$res = @pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$y=1;
	
	for($i=0; $i<pg_numrows($res); $i++){
		$causa_defeito = trim(pg_result($res,$i,causa_defeito));
		$codigo        = trim(pg_result($res,$i,codigo));
		$descricao     = trim(pg_result($res,$i,descricao));
		
		if (strlen($defeito) > 0) {
			$sql = "SELECT  tbl_defeito_causa_defeito.defeito_causa_defeito,
							tbl_defeito_causa_defeito.defeito              ,
							tbl_defeito_causa_defeito.causa_defeito
					FROM    tbl_defeito_causa_defeito
					WHERE   tbl_defeito_causa_defeito.defeito       = $defeito
					AND     tbl_defeito_causa_defeito.causa_defeito = $causa_defeito";
			$res2 = @pg_exec($con,$sql);
			
			if (pg_numrows($res2) > 0) {
				$novo                  = 'f';
				$defeito_causa_defeito = trim(pg_result($res2,0,defeito_causa_defeito));
				$xcausa_defeito        = trim(pg_result($res2,0,causa_defeito));
			}else{
				$novo                  = 't';
				$defeito_causa_defeito = "";
				$xcausa_defeito        = "";
			}
		}else{
			$novo                  = 't';
			$defeito_causa_defeito = "";
			$xcausa_defeito        = "";
		}

		$resto = $y % 2;
		$y++;

		if ($xcausa_defeito == $causa_defeito)
			$check = " checked ";
		else
			$check = "";

		echo "<input type='hidden' name='novo_$i' value='$novo'>\n";
		echo "<input type='hidden' name='defeito_causa_defeito_$i' value='$defeito_causa_defeito'>\n";
		echo "<input type='checkbox' name='causa_defeito_$i'       value='$causa_defeito' $check></TD>\n";
		echo "<td>&nbsp;</td>",
			  "<TD align='left'>$codigo </TD>\n";
		echo "<TD align='left'>$descricao";

		if($resto == 0){
			echo "</td></tr>\n";
			echo "<tr><td>&nbsp;</td><td align='left'>\n";
		}else{
			echo "</td>\n";
			echo "<td align='left'>\n";
		}
	}
}

echo "<input type='hidden' name='qtde_item' value='$i'>\n";


?>

<tr>
<td colspan='9'>
<div align='center'>
<input type='hidden' name='btnacao' value=''>
	<div>
		<input type="button" style="background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;" value="&nbsp;" ONCLICK="javascript: if (document.frm_defeito.btnacao.value == '' ) { document.frm_defeito.btnacao.value='gravar' ; document.frm_defeito.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">
		<input type="button" style="background:url(imagens_admin/btn_limpar.gif); width:75px; cursor:pointer;" value="&nbsp;" ONCLICK="javascript: if (document.frm_defeito.btnacao.value == '' ) { document.frm_defeito.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor:pointer;">
	</div>
</form>
</div>
</td>
</tr>
</table>
<? 
} 
?>
<p>
<TABLE width = '700px' class='tabela' align = 'center' border = '0' cellspacing='3' cellpadding='1'>
<!-- <TR>
<td COLSPAN='6' class='titulo_coluna'>Relação dos Defeitos Cadastrados</TD>
</tr> -->
<?
$sql = "SELECT  tbl_defeito.defeito       ,
				tbl_defeito.codigo_defeito,
				tbl_defeito.descricao
		FROM    tbl_defeito
		WHERE   tbl_defeito.fabrica = $login_fabrica
		ORDER BY lpad(tbl_defeito.codigo_defeito::text,10,'0');";
$res = pg_exec ($con,$sql);


for ($x = 0 ; $x < pg_numrows($res) ; $x++){
	$defeito        = trim(pg_result($res,$x,defeito));
	$codigo_defeito = trim(pg_result($res,$x,codigo_defeito));
	$descricao      = trim(pg_result($res,$x,descricao));
	
	$cor = ( $x%2 == 0 ) ? '#F7F5F0' : '#F1F4FA';
	
	echo "<tr bgcolor='$cor'>";
	echo "<td align='center' width='100%'>";
	echo "$codigo_defeito $descricao </td><td align='left'> <input type=\"button\" onClick=\"location.href='$PHP_SELF?defeito=$defeito'\" style=\"background:url(imagens_admin/btn_alterar.gif); width:75px; cursor:pointer;\" value=\"&nbsp;\" />";
	echo "</td>\n";
	
	
}
echo "</tr>\n";
echo "</table>\n";

?>
</form>
</div>
</body>
</html>
