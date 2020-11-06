<?
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$btn_acao = strtolower ($_POST['btn_acao']);

if ($btn_acao == "continuar") {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql =	"SELECT britania_fama.posto
			FROM    britania_fama
			WHERE   britania_fama.posto = $login_posto";
	$res = @pg_exec($con,$sql);
	
	if (pg_numrows($res) == 0) {
		$sql = "INSERT INTO britania_fama (posto) values ($login_posto)";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	
	$ja_chegaram = $_POST['ja_chegaram'];
	$quantos_chegaram = trim($_POST['quantos_chegaram']);
	$tem_parados = $_POST['tem_parados'];
	$quantos_parados = trim($_POST['quantos_parados']);
	
	if (strlen($ja_chegaram) > 0 AND strlen($tem_parados) > 0) {
		if ($tem_parados == "t" AND strlen($quantos_parados) == 0) $msg_erro = "Quantidade de DVDs aguardando soluções não está preenchido.";
		if ($ja_chegaram == "t" AND strlen($quantos_chegaram) == 0) $msg_erro = "Quantidade de consertos não está preenchido.";
		
		if (strlen ($msg_erro) == 0) {
			if (strlen($quantos_chegaram) == 0 OR $ja_chegaram == 'f') $quantos_chegaram = 'null';
			if (strlen($quantos_parados) == 0 OR $tem_parados == 'f') $quantos_parados = 'null';
			
			$sql =	"UPDATE britania_fama SET 
						ja_chegaram			= '$ja_chegaram',
						quantos_chegaram	= $quantos_chegaram,
						tem_parados			= '$tem_parados',
						quantos_parados		= $quantos_parados
					WHERE posto = $login_posto";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) > 0) {
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}else{
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header("Location: login.php");
		exit;
	}

}

/* $title = Aparece no sub-menu e no título do Browser ===== */
$title = "Telecontrol ASSIST - Gerenciamento de Assistência Técnica";

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'os';

/*HD  12713 tirar cabeçalho antigo que nem existe mais no diretorio /assist/www/ 
include 'cabecalho_login.php';*/
include 'cabecalho.php'
?>

<hr>
<h1><? echo $login_nome ?></h1>
<!-- AQUI VAI INSERIDO OS RELATÓRIOS E OS FORMS -->

<div id="container"><h2><IMG SRC="imagens/bemVindo<? echo $login_fabrica_nome ?>.gif" ALT="Bem Vindo!!!"></h2></div>

<? if (strlen ($msg_erro) > 0) { ?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '600'>
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<? } ?>

<br>

<div style="width: 600; border: 1px dotted silver; padding: 10px;">
	<img border="0" src="imagens/esclamachion1.gif" align="left">
	<font color='#990000'>Favor, responder às questões abaixo e verificar o seguinte Comunicado: <b>Configuração DVD Fama e Game</b></font><br>
	<br>
	<FORM METHOD=POST name='frm_perguntas' ACTION="<? echo $PHP_SELF; ?>">
	<p align="left"><b>1)</b> Já chegou ao seu Posto Autorizado para conserto o DVD Fama ou DVD Game?<br>
	<br>
	<input type="radio" name="ja_chegaram" value="f"> Não &nbsp; &nbsp; <input type="radio" name="ja_chegaram" value="t" checked> Sim, quantos: <input type="text" size="4" name="quantos_chegaram"><br>
	<br>
	<b>2)</b> Há DVDs Fama ou Game aguardando solução?<br>
	<br>
	<input type="radio" name="tem_parados" value="f"> Não &nbsp; &nbsp; <input type="radio" name="tem_parados" value="t" checked> Sim, quantos: <input type="text" size="4" name="quantos_parados"><br>
	<br>
	<br>
	Pendências ou Dúvidas, favor entrar em contato com o departamento de Assistência Técnica da Britania Eletrodomesticos S.A.</p>
	<br>
	<table width="90%" border="0" cellpadding="2" cellspacing="1" align="center" bgcolor="#D9E2EF">
		<tr>
			<td bgcolor="#FFFFFF" align="center">
				<font face="Verdana, Tahoma, Arial" size="2">Por favor, informem seus técnicos e atendentes sobre este comunicado de configuração dos DVDs Fama e Game, pois se trata de um procedimento simples, evitando que o produto fique parado no posto autorizado.</font>
			</td>
		</tr>
	</table>
	<br>
	<input type="hidden" name="btn_acao" value="">
	<img border="0" src="imagens/btn_continuar.gif" style="cursor:pointer" onclick="javascript: if (document.frm_perguntas.btn_acao.value == '' ) { document.frm_perguntas.btn_acao.value='continuar' ; document.frm_perguntas.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar">
	</form>
</div>

<? include "rodape.php"; ?>