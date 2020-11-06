<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_GET["status"]) > 0) {
	$status = trim($_GET["status"]);
}

if (strlen($_POST["status"]) > 0) {
	$status = trim($_POST["status"]);
}

if (strlen($HTTP_POST_VARS["btnacao"]) > 0) {
	$btnacao = trim($HTTP_POST_VARS["btnacao"]);
}


if ($btnacao == "deletar") {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_status
			WHERE  fabrica      = $login_fabrica
			AND    status = $status;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$descricao		= $HTTP_POST_VARS["descricao"];
		$afeta_pedido	= $HTTP_POST_VARS["afeta_pedido"];
		$afeta_os		= $HTTP_POST_VARS["afeta_os"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar") {
	if (strlen($msg_erro) == 0) {
		if (strlen($HTTP_POST_VARS["descricao"]) > 0) {
			$aux_descricao = "'". trim($HTTP_POST_VARS["descricao"]) ."'";
			$aux_descricao = str_replace('"', "'", $aux_descricao);
		}else{
			$aux_descricao = "null";
		}
	}
	
	if ($aux_descricao == "null") {
		$msg_erro = "Favor informar a descrição do status.";
	}
	
	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($status) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_status (
						descricao   ,
						afeta_pedido,
						afeta_os    ,
						fabrica     
					) VALUES (
						$aux_descricao ,
						'$afeta_pedido',
						'$afeta_os'    ,
						$login_fabrica 
					)";
		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE tbl_status SET
							descricao = $aux_descricao    ,
							afeta_pedido = '$afeta_pedido',
							afeta_os = '$afeta_os'        
					WHERE  fabrica      = $login_fabrica
					AND    status = $status;";
		}
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$descricao = $HTTP_POST_VARS["descricao"];
		$afeta_pedido	= $HTTP_POST_VARS["afeta_pedido"];
		$afeta_os		= $HTTP_POST_VARS["afeta_os"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


###CARREGA REGISTRO
if (strlen($status) > 0) {
	$sql = "SELECT  descricao   ,
					afeta_pedido,
					afeta_os    
			FROM    tbl_status
			WHERE   fabrica      = $login_fabrica
			AND     status = $status;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$descricao		= trim(pg_result($res,0,descricao));
		$afeta_pedido	= trim(pg_result($res,0,afeta_pedido));
		$afeta_os		= trim(pg_result($res,0,afeta_os));
	}
}
?>
<?
	$layout_menu = "cadastro";
	$title = "Cadastramento de Status";
	include 'cabecalho.php';
?>

<p>
<div id='wrapper'>
<form name="frm_status" method="post" action="<? $PHP_SELF ?>">
<input type="hidden" name="status" value="<? echo $status ?>">


<? if (strlen($msg_erro) > 0) { ?>

<div id="wrapper">
	<b><? echo $msg_erro; ?></b>
</div>

<? } ?>

<div id="wrapper">
	<div id="middleCol" style="width: 265px; ">
		<b>Descrição</b>
	</div>
	<div id="middleCol" style="width: 120px; ">
		<b>Afeta pedido</b>
	</div>
	<div id="middleCol" style="width: 120px; ">
		<b>Afeta OS</b>
	</div>
</div>

<div id='middleCol' style='width: 200px; '>
	<input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="40" maxlength="50">
</div>
<div id='middleCol' style='width: 120px; '>
<?
	if($afeta_pedido == 't')
		echo "<input type='radio' name='afeta_pedido' value='t' checked>Sim &nbsp; <input type='radio' name='afeta_pedido' value='f'>Não ";
	else
		echo "<input type='radio' name='afeta_pedido' value='t'>Sim &nbsp; <input type='radio' name='afeta_pedido' value='f' checked>Não ";
?>
</div>
<div id='middleCol' style='width: 120px; '>
<?
	if($afeta_os == 't')
		echo "<input type='radio' name='afeta_os' value='t' checked>Sim &nbsp; <input type='radio' name='afeta_os' value='f'>Não ";
	else
		echo "<input type='radio' name='afeta_os' value='t'>Sim &nbsp; <input type='radio' name='afeta_os' value='f' checked>Não ";

?>
</div>

</div>

<p><p>

<div id='wrapper'>
	<input type='hidden' name='btnacao' value=''>
	<IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_status.btnacao.value == '' ) { document.frm_status.btnacao.value='gravar' ; document.frm_status.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">
	<IMG SRC="imagens_admin/btn_apagar.gif" ONCLICK="javascript: if (document.frm_status.btnacao.value == '' ) { document.frm_status.btnacao.value='deletar' ; document.frm_status.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar Informação" border='0' style="cursor:pointer;">
	<IMG SRC="imagens_admin/btn_limpar.gif" ONCLICK="javascript: if (document.frm_status.btnacao.value == '' ) { document.frm_status.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor:pointer;">
</div>

<p>

<div id="subBanner">
	<h1>
		Relação de Status
	</h1>
</div>

<div id="wrapper">
	<b>Para efetuar alterações, clique na descrição do status.</b>
</div>

<?
$sql = "SELECT  status      ,
				descricao   ,
				afeta_pedido,
				afeta_os    
		FROM    tbl_status
		WHERE   fabrica = $login_fabrica
		ORDER BY descricao";
$res0 = pg_exec ($con,$sql);

echo "<BLOCKQUOTE>";

for ($y = 0 ; $y < pg_numrows($res0) ; $y++){

	$status		= trim(pg_result($res0,$y,status));
	$descricao	= trim(pg_result($res0,$y,descricao));

	echo "<div id=\"middlecol\" align=\"left\">\n";
	echo "<a href='$PHP_SELF?status=$status'>$descricao</a>\n";
	echo "</div>\n";

}

echo "</BLOCKQUOTE>";

echo "</div>\n";
?>

</form>
</div>
<?
	include "rodape.php";
?>