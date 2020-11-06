<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));

if (strlen($_GET["capacidade_valores"]) > 0)  $capacidade_valores = trim($_GET["capacidade_valores"]);
if (strlen($_POST["capacidade_valores"]) > 0) $capacidade_valores = trim($_POST["capacidade_valores"]);

if (strlen($_GET["cadastrar"]) > 0)  $cadastrar = trim($_GET["cadastrar"]);
if (strlen($_POST["cadastrar"]) > 0) $cadastrar = trim($_POST["cadastrar"]);

if ($btnacao == "gravar") {

	$capacidade_de     = trim($_POST["capacidade_de"]);
	$capacidade_ate    = trim($_POST["capacidade_ate"]);
	$valor_regulagem   = trim($_POST["valor_regulagem"]);
	$valor_certificado = trim($_POST["valor_certificado"]);

	$capacidade_de     = str_replace(",","",$capacidade_de);
	$capacidade_ate    = str_replace(",","",$capacidade_ate);
	$valor_regulagem   = str_replace(",","",$valor_regulagem);
	$valor_certificado = str_replace(",","",$valor_certificado);

	if (strlen($capacidade_de)==0 or strlen($capacidade_ate)==0){
		$msg_erro .= "Informe a capacidade!<br>";
	}else{
		$xcapacidade_de  = $capacidade_de;
		$xcapacidade_ate = $capacidade_ate;
	}

	if ($xcapacidade_de > $xcapacidade_ate){
		$msg_erro .= "Capacidade DE deve ser inferior a capacidade ATÉ!<br>";
	}

	if (strlen($valor_regulagem)==0){
		$msg_erro .= "Informe o valor da regulagem!<br>";
	}else{
		$xvalor_regulagem = $valor_regulagem;
	}

	if (strlen($valor_certificado)==0){
		$msg_erro .= "Informe o valor do certificado!<br>";
	}else{
		$xvalor_certificado = $valor_certificado;
	}

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	if(strlen($msg_erro) == 0){

		if (strlen($capacidade_valores)>0){
			$sql = "UPDATE  tbl_capacidade_valores SET
							capacidade_de     = $xcapacidade_de,
							capacidade_ate    = $xcapacidade_ate,
							valor_regulagem   = $xvalor_regulagem,
							valor_certificado = $xvalor_certificado
					WHERE tbl_capacidade_valores.capacidade_valores = $capacidade_valores
					AND   tbl_capacidade_valores.fabrica            = $login_fabrica ;";
			#echo nl2br($sql);
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);
		}else{
			$sql = "INSERT INTO tbl_capacidade_valores (
							fabrica          ,
							capacidade_de    ,
							capacidade_ate   ,
							valor_regulagem  ,
							valor_certificado 
						) VALUES (
							$login_fabrica     ,
							$xcapacidade_de    ,
							$xcapacidade_ate   ,
							$xvalor_regulagem  ,
							$xvalor_certificado
						)";
			$res = pg_exec ($con,$sql);
			#echo nl2br($sql);
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);
		}
	}

	if(strlen($msg_erro) == 0){
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	
	}
}

if (strlen($capacidade_valores) > 0) {
	$sql = "SELECT 	tbl_capacidade_valores.capacidade_valores   ,
					tbl_capacidade_valores.capacidade_de        ,
					tbl_capacidade_valores.capacidade_ate       ,
					tbl_capacidade_valores.valor_regulagem      ,
					tbl_capacidade_valores.valor_certificado     
			FROM    tbl_capacidade_valores
			WHERE   tbl_capacidade_valores.capacidade_valores  = $capacidade_valores
			AND     tbl_capacidade_valores.fabrica = $login_fabrica;";
	$res = @pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$capacidade_valores  = trim(pg_result($res,0,capacidade_valores));
		$capacidade_de       = trim(pg_result($res,0,capacidade_de));
		$capacidade_ate      = trim(pg_result($res,0,capacidade_ate));
		$valor_regulagem     = trim(pg_result($res,0,valor_regulagem));
		$valor_certificado   = trim(pg_result($res,0,valor_certificado));
	}
}

$layout_menu = "cadastro";
$title = "Valores por Capacidade";
include 'cabecalho.php';

?>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(document).ready(function() {
		$('input[@rel=data]').datePicker({startDate:'01/01/2000'});
		$("input[@rel=data]").maskedinput("99/99/9999");
		$("input[@rel=data2]").maskedinput("99/99/9999");
	});

</script>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;

}

</style>
<? if(strlen($msg_erro) > 0){ ?>
<TABLE  width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<TR align='center'>
	<TD class='error'><? echo $msg_erro; ?></TD>
</TR>
</TABLE>
<? } ?>

<? if (strlen($capacidade_valores) > 0 OR $cadastrar == '1') {?>
<br>
<form method='POST' name="frm_cadastro" action="<? echo $PHP_SELF; ?>">
<input type='hidden' name='capacidade_valores' value='<? echo $capacidade_valores; ?>'>
<input type='hidden' name='cadastrar' value='<? echo $cadastrar; ?>'>
<input type='hidden' name='btnacao' value=''>

<table width="350px" cellpadding="3" align='center' cellspacing="2" border="0">
	<TR class="menu_top">
		<TD colspan='2'>
<?
	if (strlen($capacidade_valores) > 0){
		echo "Alterar Parâmetro por Capacidade";
	}else{
		echo "Novo Parâmetro por Capacidade";
	}
?>
		</TD>
	</TR>
	<TR class="menu_top">
		<TD align='center'>De</TD>
		<TD align='center'>Até</TD>
	</TR>
	<TR class="table_line">
		<Td align='center'><input size='12' maxlength='10' type='text' name='capacidade_de' id='capacidade_de' value='<?=$capacidade_de?>' class='frm' ></td>
		<Td align='center'><input size='12' maxlength='10' type='text' name='capacidade_ate' id='capacidade_ate' value='<?=$capacidade_ate?>' class='frm' ></td>
	</TR>
</table>

<table width="350px" cellpadding="3" align='center' cellspacing="2" border="0">
	<TR class="menu_top">
		<TD>Valor Regulagem</TD>
		<TD>Valor Certificado</TD>
	</TR>
	<TR class="table_line">
		<TD align='center'><INPUT size='12' maxlength='10' TYPE='text' NAME='valor_regulagem' id='valor_regulagem' value='<?=$valor_regulagem?>' class='frm' style='text-align:right'></TD>
		<TD align='center'><INPUT size='12' maxlength='10' TYPE='text' NAME='valor_certificado' id='valor_certificado' value='<?=$valor_certificado?>' class='frm' style='text-align:right'></TD>
	</TR>
</table>
<table width="250px" cellpadding="3" align='center' cellspacing="2" border="0">
<TR>

<TD><IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_cadastro.btnacao.value == '' ) { document.frm_cadastro.btnacao.value='gravar' ; document.frm_cadastro.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor: pointer;"></TD>
<TD><IMG SRC="imagens_admin/btn_limpar.gif" ONCLICK="javascript: if (document.frm_cadastro.btnacao.value == '' ) { document.frm_cadastro.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor: pointer;"></TD>

</TR>
</table>
</FORM>

<BR>
<? } ?>

<BR>

<table width="400px" cellpadding="2" align='center' cellspacing="2" border="0">
	<caption class="menu_top">Valores por Capacidade</caption>
	<TR class="menu_top">
		<TD>De</TD>
		<TD>Até</TD>
		<TD>Valor Regulagem</TD>
		<TD>Valor Certificado</TD>
		<TD>&nbsp;</TD>
	</TR>
<?
	$sql = "SELECT  tbl_capacidade_valores.capacidade_valores    ,
					tbl_capacidade_valores.capacidade_de         ,
					tbl_capacidade_valores.capacidade_ate        ,
					tbl_capacidade_valores.valor_regulagem       ,
					tbl_capacidade_valores.valor_certificado      
			FROM    tbl_capacidade_valores
			WHERE   tbl_capacidade_valores.fabrica = $login_fabrica
			ORDER BY tbl_capacidade_valores.capacidade_de ;";
	$res = @pg_exec ($con,$sql);
	
	for ($i=0; $i < pg_numrows($res); $i++) {
		$capacidade_valores	= trim(pg_result($res,$i,capacidade_valores));
		$capacidade_de		= trim(pg_result($res,$i,capacidade_de));
		$capacidade_ate		= trim(pg_result($res,$i,capacidade_ate));
		$valor_regulagem	= trim(pg_result($res,$i,valor_regulagem));
		$valor_certificado	= trim(pg_result($res,$i,valor_certificado));

		echo "<TR class='table_line'>";
		echo "<TD nowrap>".$capacidade_de."</TD>";
		echo "<TD nowrap>".$capacidade_ate."</TD>";
		echo "<TD nowrap align='right'>".number_format($valor_regulagem,2,",","")."</TD>";
		echo "<TD nowrap align='right'>".number_format($valor_certificado,2,",","")."</TD>";
		echo "<td nowrap><a href='$PHP_SELF?capacidade_valores=$capacidade_valores'>Alterar</a></td>";
		echo "</TR>";
	}

?>	
</table>

<p>
<a href='<?$PHP_SELF?>?cadastrar=1'>Cadastrar novo parâmetro</a>
</p>


<?	include "rodape.php"; ?>
