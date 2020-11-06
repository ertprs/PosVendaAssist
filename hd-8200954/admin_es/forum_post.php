<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$title = " F O R U M ";
$layout_menu = 'tecnica';

$btn_acao = strtolower ($_POST['btn_acao']);

if (strlen($_POST['forum']) > 0)     $forum     = trim($_POST['forum']);
if (strlen($_GET['forum']) > 0)      $forum     = trim($_GET['forum']);

if (strlen($_POST['forum_pai']) > 0)  $forum_pai = trim($_POST['forum_pai']);
if (strlen($_GET['forum_pai']) > 0)   $forum_pai = trim($_GET['forum_pai']);


if ($btn_acao == 'publicar'){
	$titulo    = trim($_POST['titulo']);
	$mensagem  = trim($_POST['mensagem']);
	$forum_pai  = trim($_POST['forum_pai']);
	
	if (strlen($mensagem) == 0)
		$msg_erro = "Informe la mensaje";
	else
		$xmensagem = "'".$mensagem."'";
	
	if (strlen($titulo) == 0)
		$msg_erro = "Informe lo título";
	else
		$xtitulo = "'".$titulo."'";
	
	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"BEGIN TRANSACTION");
		
		/*================ INSERE MENSAGEM NA TABELA FORUM===============*/
		$sql = "INSERT INTO tbl_forum (
					fabrica           ,
					admin             ,
					data              ,
					titulo            ,
					mensagem          ,
					liberado          
				) VALUES (
					$login_fabrica   ,
					$login_admin     ,
					current_timestamp,
					$xtitulo         ,
					$xmensagem       ,
					'true'
				)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		
		if (strlen ($msg_erro) == 0) {
			$res   = pg_exec ($con,"SELECT CURRVAL ('seq_forum')");
			$forum = pg_result ($res,0,0);
			$msg_erro = pg_errormessage($con);
		}
	}
	
	if (strlen($forum_pai) > 0) {
		$upd_forum = $forum_pai;
	}else{
		$upd_forum = $forum;
	}
	
	$sql = "UPDATE tbl_forum SET forum_pai = $upd_forum
			WHERE  tbl_forum.forum = $forum;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: forum.php");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}//fim publicar

/*================ LE MENSAGEM DA BASE DE DADOS =========================*/
$forum = $_GET['forum'];

if (strlen ($forum_pai) > 0) {
	$sql = "SELECT	*
			FROM	tbl_forum
			WHERE	tbl_forum.forum   = $forum_pai
			AND		tbl_forum.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 1) {
		$forum    = pg_result ($res,0,forum);
		$titulo   = pg_result ($res,0,titulo);
		$mensagem = pg_result ($res,0,mensagem);
	}
}

/*============= RECARREGA FORM EM CASO DE ERRO ==================*/
if (strlen ($msg_erro) > 0) {
	$forum		= $_POST['forum'];
	$titulo		= $_POST['titulo'];
	$mensagem	= $_POST['mensagem'];
}

include 'cabecalho.php';

?>

<style type='text/css'>

.forum_cabecalho {
	padding: 5px;
	background-color: #FFCC00;
	font-family: arial;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	text-align: center;
	}

.texto {
	padding: 5px;
	font-family: arial;
	font-size: 12px;
	font-weight: bold;
	color: #596D9B;
	text-align: justify;
	}

.corpo {
	padding: 5px;
	font-family: arial;
	font-size: 12px;
	color: #596D9B;
	text-align: justify;
	}

.forum_claro {
	padding: 3px;
	background-color: #CED7E7;
	color: #596D9B;
	text-align: center;
	}


.forum_escuro {
	padding: 3px;
	background-color: #D9E2EF;
	color: #596D9B;
	text-align: center;
	}

a:link.menu {
	color: #63798D;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
}

a:visited.menu {
	color: #63798D;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	text-decoration: none;
}

a:hover.menu {
	color: #FFCC00;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
}

a:link.forum {
	color: #63798D;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}

a:visited.forum {
	color: #63798D;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	text-decoration: none;
}

a:hover.forum {
	color: #0000FF;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}

a:link.botao {
	padding: 20px,20px,20px,20px;
	background-color: #ffcc00;
	color: #000000;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}

a:visited.botao {
	padding: 20px,20px,20px,20px;
	background-color: #ffcc00;
	color: #000000;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	text-decoration: none;
}

a:hover.botao {
	padding: 20px,20px,20px,20px;
	background-color: #596d9b;
	color: #ffffff;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}

</style>

<? 
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<? } ?>

<br>
<!-- <br /> -->

<table width='700px' border='0' cellpadding='0' cellspacing='3'>
<tr>
	<td valign='top'>
		<table width='150px' border='0' cellpadding='0' cellspacing='3' valign='top'>
		<tr>
			<td>
				<img src='imagens/forum_home.gif'>
			</td>
			<td>
				<a href='forum.php' class='menu'>PANTALLA INICIAL</a>
			</td>
		</tr>
		<tr>
			<td colspan='2' class='texto'>
				<FORM NAME='frm_busca' METHOD=POST ACTION="forum.php">
				<input type='hidden' name='exibir' value='todos'>
				
				<BR> BÚSQUEDA <BR>
					<INPUT TYPE="text" NAME="busca" size='13' class='busca'>&nbsp;<INPUT TYPE="submit" name='ok' value='OK' class='busca'>
				</FORM>
			</td>
		</tr>
		</table>
	</td>
	<td>
		<table width='550px' border='0' cellpadding='0' cellspacing='3'>
		<tr class='forum_claro'>
			<td style='text-align: left;'>
				<table width='100%' border='0' cellpadding='0' cellspacing='3'>
				<FORM NAME='frm_mensagem' METHOD=POST ACTION="<? echo $PHP_SELF; ?>">
				<input type="hidden" name="forum_pai" value="<? echo $forum_pai; ?>">
				<input type="hidden" name="forum" value="<? echo $forum; ?>">
				<tr class='corpo'>
					<td>TÍTULO: </td>
					<td><input class="frm" type="text" name="titulo" size="40" maxlength="50" value="<? echo $titulo ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o título da sua mensagem.');"></td>
				</tr>
				<tr class='corpo'>
					<td valign='top'>MENSAJE: </td>
					<td><textarea name='mensagem' class="frm" cols='40' rows='10' value="<? echo $mensagem ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite sua mensagem');"></textarea></td>
				</tr>
				<tr>
					<td align='right'>&nbsp;</td>
					<td align='left'>
						<input type='hidden' name='btn_acao' value=''>
						<a href='#'onclick="javascript: if (document.frm_mensagem.btn_acao.value == '' ) { document.frm_mensagem.btn_acao.value='publicar' ; document.frm_mensagem.submit() } else { alert ('Aguarde submissão') }" class='botao'>Publicar</a>
					</td>
				</tr>
				</FORM>
				</table>
			</td>
		</tr>
	</table>
	</td>
</tr>

</table>

<? include "rodape.php"; ?>