<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_GET["classificacao_os"]) > 0)  $classificacao_os = trim($_GET["classificacao_os"]);
if (strlen($_POST["classificacao_os"]) > 0) $classificacao_os = trim($_POST["classificacao_os"]);
if (strlen($_POST["btnacao"]) > 0)          $btnacao = trim($_POST["btnacao"]);

if ($btnacao == "deletar" and strlen($classificacao_os) > 0 ) {

	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_classificacao_os
			WHERE  fabrica = $login_fabrica
			AND    classificacao_os = $classificacao_os;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strpos ($msg_erro,'classificacao_os_fk') > 0) $msg_erro = "Esta classificação não pode ser excluída.";

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar") {

	$descricao = trim($_POST["descricao"]);
	$garantia  = trim($_POST['garantia']);
	$ativo     = trim($_POST['ativo']);


	if (strlen($descricao) > 0) {
		$aux_descricao = "'".$descricao."'";
	}else{
		$msg_erro = "Favor informar a descrição da classificação.";
	}

	if (strlen ($garantia) == 0) {
		$aux_garantia = "'f'";
	}else{
		$aux_garantia = "'t'";
	}
	
	if (strlen ($ativo) == 0) {
		$aux_ativo = "'f'";
	}else{
		$aux_ativo = "'t'";
	}
	
	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($classificacao_os) == 0) {
			$sql = "INSERT INTO tbl_classificacao_os (
						fabrica       ,
						descricao     ,
						garantia      ,
						ativo         
					) VALUES (
						$login_fabrica        ,
						$aux_descricao        ,
						$aux_garantia         ,
						$aux_ativo            
					);";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			$res = @pg_exec ($con,"SELECT CURRVAL ('seq_classificacao_os')");
			$classificacao_os  = pg_result ($res,0,0);
		}else{
			$sql = "UPDATE  tbl_classificacao_os SET
							descricao     = $aux_descricao,
							garantia      = $aux_garantia ,
							ativo         = $aux_ativo
					WHERE   fabrica = $login_fabrica
					AND     classificacao_os = $classificacao_os;";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			$msg = "Informações gravadas com sucesso!";
			header ("Location: $PHP_SELF?msg=1");
			exit;
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}


if (strlen($classificacao_os) > 0 AND strlen($msg_erro)==0) {
	$sql = "SELECT  descricao, garantia, ativo
			FROM    tbl_classificacao_os
			WHERE   fabrica = $login_fabrica
			AND     classificacao_os = $classificacao_os;";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
		$descricao     = trim(pg_result($res,0,descricao));
		$garantia      = trim(pg_result($res,0,garantia));
		$ativo         = trim(pg_result($res,0,ativo));
	}
}

$layout_menu = "cadastro";
$title = "Cadastro de Classificação de OS";
include 'cabecalho.php';
?>

<style type='text/css'>
.conteudo {
	font: bold xx-small Verdana, Arial, Helvetica, sans-serif;
	color: #000000;
}
.ok {
	color: #0E4B20;
	text-align: center;
	font: bold 16px Verdana, Arial, Helvetica, sans-serif;
	background-color: #D7FFD9;
}
</style>

<form name="frm_cadastro" method="post" action="<? $PHP_SELF ?>">
<input type="hidden" name="classificacao_os" value="<? echo $classificacao_os ?>">

<? if (strlen($msg_erro) > 0) { ?>
<div class='error'>
	<? echo $msg_erro; ?>
</div>
<? } ?>

<? if (strlen($msg) > 0) { ?>
<div class='ok'>
	Informações alteradas com sucesso!
</div>
<? } ?>

<br>
<table width='400' border='0' class='conteudo' cellpadding='2' cellspacing='1' align='center'>
	<tr bgcolor='#D9E2EF'>
		<td>CLASSIFICAÇÃO DA OS</b></td>
		<td>GARANTIA</td>
		<td>ATIVO</td>
	</tr>
	<tr>
		<td align='left'>
			<input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="50" maxlength="50">
		</td>
		<td align='center'>
			<input type="checkbox" class="frm" name="garantia" <? if ($garantia == 't' ) echo " checked " ?> value='t' >
		</td>
		<td align='center'>
			<input type="checkbox" class="frm" name="ativo" <? if ($ativo == 't' ) echo " checked " ?> value='t' >
		</td>
	</tr>
</table>

<br>

<input type='hidden' name='btnacao' value=''>
<IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_cadastro.btnacao.value == '' ) { document.frm_cadastro.btnacao.value='gravar' ; document.frm_cadastro.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">
<IMG SRC="imagens_admin/btn_apagar.gif" ONCLICK="javascript: if (document.frm_cadastro.btnacao.value == '' ) { document.frm_cadastro.btnacao.value='deletar' ; document.frm_cadastro.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar Informação" border='0' style="cursor:pointer;">
<IMG SRC="imagens_admin/btn_limpar.gif" ONCLICK="javascript: if (document.frm_cadastro.btnacao.value == '' ) { document.frm_cadastro.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor:pointer;">

</form>



<?

echo "<br><br><center><font size='2'><b>Relação das Classificações de OS</b><BR>
	<I>Para efetuar alterações, clique na descrição da classificação.</i></font>
	</center>";

$sql = "SELECT  classificacao_os   ,
				descricao          ,
				garantia           ,
				CASE WHEN ativo = 't' THEN '0' ELSE '1' END as ordem,
				ativo             
		FROM    tbl_classificacao_os
		WHERE   fabrica = $login_fabrica
		ORDER BY ordem, descricao;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {

	echo "<table width='500' border='0' class='conteudo' cellpadding='2' cellspacing='1' align='center'>\n";
	echo "<tr bgcolor='#D9E2EF'>\n";
	echo "<td align='left'>Descrição</td>";
	echo "<td align='left'>Garantia</td>";
	echo "<td align='left'>Ativo</td>";
	echo "</tr>\n";
	for ($i = 0 ; $i < pg_numrows($res) ; $i++){
		$classificacao_os = trim(pg_result($res,$i,classificacao_os));
		$descricao        = trim(pg_result($res,$i,descricao));
		$garantia         = trim(pg_result($res,$i,garantia));
		$ativo            = trim(pg_result($res,$i,ativo));

		$cor = ($i % 2 == 0) ? '#F1F4FA' : '#FFFFFF';

		if ($garantia=='t'){
			$garantia = "Sim";
		}else{
			$garantia = "Não";
		}

		echo "<tr bgcolor='$cor'>\n";
		echo "<td align='left'>";
		echo "<a href='$PHP_SELF?classificacao_os=$classificacao_os'>$descricao</a>";
		echo "</td>\n";
		echo "<td align='left'>".$garantia."</td>\n";
		echo "<td>";
		if ($ativo == 't') echo "Ativo";
		else               echo "Inativo";
		echo "</td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
	echo "<br>\n";
}

include "rodape.php";
?>