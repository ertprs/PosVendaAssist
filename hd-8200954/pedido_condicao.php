<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (!function_exists("file_put_contents")) {
	function file_put_contents($filename,$data,$append=false) {
	    $mode = ($append)?"ab":"wb";
// 	    if (!is_writable($filename)) return false;
		$file_resource = fopen($filename,$mode);
		if (!$file_resource===false):
		    system ("chmod 664 $filename");
			$bytes = fwrite($file_resource, $data);
		else:
		    return false;
		endif;
		fclose($file_resource);
		return $bytes;
	}
}

if (strlen($_GET['btn_acao']) > 0)   $btn_acao = trim ($_GET['btn_acao']);
if (strlen($_POST['btn_acao']) > 0)  $btn_acao = trim ($_POST['btn_acao']);

if (strlen($_GET['condicao']) > 0)   $condicao = trim ($_GET['condicao']);
if (strlen($_POST['condicao']) > 0)  $condicao = trim ($_POST['condicao']);

if ($btn_acao == "gravar") {

	if (strlen ($condicao) > 0) {

		$res = pg_query($con, "BEGIN TRANSACTION");

		$condicao_1          = $condicao;
		$condicao_promocao_1 = "957";
		$condicao_promocao_2 = "958";
		$lista_condicoes = array(	$condicao_1,
									$condicao_promocao_1,
									$condicao_promocao_2
								);

		$sql = "UPDATE tbl_posto_fabrica SET
						condicao_escolhida = 'f'
				WHERE fabrica = $login_fabrica
				AND   posto   = $login_posto   ";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_last_error($con);

		$sql= " DELETE FROM tbl_black_posto_condicao
				WHERE posto = $login_posto
				AND id_condicao IN (
					SELECT condicao
					FROM tbl_condicao
					WHERE fabrica = $login_fabrica
				)";
		#echo nl2br($sql);
		$res = pg_query ($con,$sql);
		$msg_erro .= pg_last_error($con);

		for ($i=0; $i< count($lista_condicoes); $i++){

			$condicao = $lista_condicoes[$i];

			if (strlen($condicao)==0){
				continue;
			}

			$sql = "SELECT condicao, descricao
					FROM tbl_condicao
					WHERE fabrica = $login_fabrica
					AND  condicao = $condicao";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res)>0){
				$condicao           = pg_fetch_result($res,0,condicao);
				$condicao_descricao = pg_fetch_result($res,0,descricao);

				if ($condicao_1 == $condicao){
					$condicao_descricao_selecionda = $condicao_descricao;
				}
			}else{
				$msg_erro .= "Condição de pagamento não encontrada";
			}

			if(strlen($msg_erro)==0){
				$sql = "SELECT	posto    ,
								data     ,
								condicao ,
								id_condicao
						FROM tbl_black_posto_condicao
						WHERE posto     = $login_posto
						AND id_condicao = $condicao";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)==0){
					if(strlen($msg_erro)==0){
						$sql= "INSERT INTO tbl_black_posto_condicao(
										posto,
										data,
										condicao,
										id_condicao
								)values(
										$login_posto,
										current_timestamp,
										'$condicao_descricao',
										$condicao);";
						#echo nl2br($sql);
						$res = pg_query ($con,$sql);
						if (strlen (pg_last_error($con)) > 0 ) {
							$msg_erro .= pg_last_error($con);
							$msg_erro .= substr($msg_erro,6);
						}
					}
				}

				/* hd 51205 - 14/11/2008 */
				$sql = "SELECT  posto    ,
								condicao
						FROM tbl_posto_condicao
						WHERE posto     = $login_posto
						AND   condicao  = $condicao";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)==0){
					if(strlen($msg_erro)==0){
						$sql= "INSERT INTO tbl_posto_condicao(
										posto,
										condicao,
										tabela
								)values(
										$login_posto,
										$condicao,
										31
								);";
						#echo nl2br($sql);
						$res = pg_query ($con,$sql);
						if (strlen (pg_last_error($con)) > 0 ) {
							$msg_erro .= pg_last_error($con);
							$msg_erro .= substr($msg_erro,6);
						}
					}
				}
			}
		}

		if (strlen($msg_erro) == 0) {

			include __DIR__."/class/communicator.class.php";

			if(empty($externalId)) {
				$externalId = "smtp@posvenda";
				if (in_array($login_fabrica, array(169,170))){
					$remetente = "naorespondablueservice@carrier.com.br";
				}else{
					$remetente = "noreply@telecontrol.com.br";
				}

			}else{
				$remetente = $externalEmail;
			}

			$mailer = new TcComm($externalId);


			$sqlX ="SELECT tbl_posto.email
					FROM tbl_posto
					JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_posto.posto = $login_posto;";
			$resX = pg_query($con,$sqlX);
			if (pg_num_rows($resX) == 1) {
				$posto_email = pg_fetch_result($resX,0,0);
			}

			if ($login_fabrica == 1)
			{
				$sqlRP = "SELECT email FROM tbl_admin WHERE fabrica = $login_fabrica AND responsavel_postos IS TRUE";
				$resRP = pg_query($con,$sqlRP);

				if (pg_num_rows($resRP) > 0)
				{
					$emailRP = Array();
					for ($i = 0; $i < pg_num_rows($resRP); $i++)
					{
						$emailRP[] = pg_fetch_result($resRP,$i,"email");
					}
				}
			}

			$emailRP[] = "helpdesk@telecontrol.com.br";

			$assunto      = "Definir condição de pagamento: Posto ".$login_codigo_posto;

			$mensagem  = "<b>Nota: Este e-mail é gerado automaticamente. <br>**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM </b>****.<BR><BR><BR>";
			$mensagem .= "<font face='Arial, Verdana, Times, Sans' size='4' color='#000000'>\n";
			$mensagem .= "<b>Condição de Pagamento do Posto</b>";
			$mensagem .= "</font>\n";
			$mensagem .= "<br>\n";
			$mensagem .= "<br>\n";
			$mensagem .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
			$mensagem .= "Posto <b>".$login_codigo_posto." - ".$login_nome."</b>:";
			$mensagem .= "<br>\n";
			$mensagem .= "Condição de pagamento selecionda: ".$condicao_descricao_selecionda."\n";
			$mensagem .= "<br><br>\n";
			$mensagem .= "<a href='http://posvenda.telecontrol.com.br/assist/admin/posto_cadastro.php?posto=$login_posto'>Clique aqui para acessar o Cadastro do Posto</a> <br> ou copie e cole o link em seu navegador: http://posvenda.telecontrol.com.br/assist/admin/posto_cadastro.php?posto=$login_posto \n";
			$mensagem .= "<br><br>\n";
			$mensagem .= "<b>O Posto aguarda a autorização para fazer pedido de peças.\n";
			$mensagem .= "<br><br>\n {$para}";
			$mensagem .= "</font>";

			//	28/9/2009 MLG - Gera arquivo no cgi-bin da fábrica para mostar na tela dos admin
			$arq_email = "/var/www/cgi-bin/blackedecker/saida/cond_pgto_$login_codigo_posto"."_".date("Y-m-d-H-i").".eml";
			$txt_email = "$cabecalho\nTo:$para\nDate: ".date("Y-m-d H:i")."\nSubject: $assunto\n\n$mensagem\n";
			file_put_contents($arq_email, $txt_email);

			$res = $mailer->sendMail(
				$emailRP,
				$assunto,
				$mensagem,
				$remetente
			);


		}

		#$msg_erro .= "Teste";

		if (strlen($msg_erro) == 0) {
			$res = pg_query ($con,"COMMIT TRANSACTION");
			header("Location: pedido_cadastro.php");
			exit;
		}else{
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}else{
		$msg_erro = "Selecione a condição de pagamento.";
	}
}


/* HD 23738 */
$sql = "SELECT  tbl_posto_fabrica.escolhe_condicao  ,
				tbl_posto_fabrica.condicao_escolhida
		FROM    tbl_posto_fabrica
		JOIN    tbl_posto USING(posto)
		WHERE   tbl_posto_fabrica.posto   = $login_posto
		AND     tbl_posto_fabrica.fabrica = $login_fabrica";
$res_posto = @pg_query ($con,$sql);
if(pg_num_rows($res_posto)>0){
	$escolhe_condicao   = trim(pg_fetch_result ($res_posto,0,escolhe_condicao));
	$condicao_escolhida = trim(pg_fetch_result ($res_posto,0,condicao_escolhida));

	if ($condicao_escolhida == "f" OR $condicao_escolhida == "t") {
		header("Location: pedido_cadastro.php");
		exit;
	}
}


$title = "Telecontrol - Assistência Técnica - Cadastro de Condição de Pagamento";
$layout_menu = 'os';
include "cabecalho.php";


if (strlen ($msg_erro) > 0){
?>

<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
<tr>
	<td height="27" valign="middle" align="center">
		<b><font face="Arial, Helvetica, sans-serif" color="#FF3333">
<?
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo $erro . $msg_erro;
?>
		</font></b>
	</td>
</tr>
</table>

<? } ?>

<form name="frm_cadastro" method="post" action="<? echo $PHP_SELF ?>">

<table width="750" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">

<tr>
<td align='center'><b>CONDIÇÃO DE PAGAMENTO</b>
<br>
<br><p><font size='2'><b>Prezado cliente</b>,
<br><br>Informamos que o  seu posto foi nomeado para adquirir peças direto com a fábrica. <br>Gentileza selecionar a condição de pagamento que deseja trabalhar.</font></p>
</td>
</tr>

<tr>
	<td align='center'>
	<br>
	<br>
		<b>Selecione a Condição de Pagamento</b>
		<br>
		<SELECT NAME="condicao">
		<?
		echo "<option ></option>";
			$sql = "SELECT  tbl_condicao.condicao       ,
					tbl_condicao.codigo_condicao,
					tbl_condicao.descricao
			FROM    tbl_condicao
			WHERE   tbl_condicao.fabrica = $login_fabrica
			AND     tbl_condicao.visivel IS TRUE
			AND     tbl_condicao.condicao IN (51,53,55,52,54,73,57,56)
			ORDER BY lpad(codigo_condicao::char(10),10,'0');";
			$res = @pg_query ($con,$sql);
			$arq_email = "/var/www/cgi-bin/blackedecker/posto_$login_codigo_posto-cond_".date("Y-m-d-H-i").".eml";
			echo $arq_email."\n";
			for ($i=0; $i < pg_num_rows($res); $i++) {
				$xcondicao			= trim(pg_fetch_result($res,$i,condicao));
				$codigo_condicao	= trim(pg_fetch_result($res,$i,codigo_condicao));
				$descricao			= trim(pg_fetch_result($res,$i,descricao));
				echo "<option value='$xcondicao'"; if($aux_condicao == $xcondicao){ echo "selected";} echo ">$descricao</option>\n";
			}
		?>
		</SELECT>
	</td>
</tr>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_gravar.gif' onclick="javascript:
			if (document.frm_cadastro.btn_acao.value == '' ) {
				if (document.frm_cadastro.condicao.value == ''){
					alert('Selecione a condição de pagamento!')
					return;
				}
				if (confirm('A condição de pagamento ' + document.frm_cadastro.condicao.options[document.frm_cadastro.condicao.selectedIndex].text + ' foi escolhida, confirma? \n\n OK para continuar\nCancelar para voltar')){
					document.frm_cadastro.btn_acao.value='gravar' ;
					document.frm_cadastro.submit();
				}
			}else {
					alert ('Aguarde submissão')
			}
				" alt="Gravar" border='0' style="cursor:pointer;" title='<?=$arq_email?>'>
	</td>
</tr>

</table>

</form>

<p>

<? include "rodape.php";?>
