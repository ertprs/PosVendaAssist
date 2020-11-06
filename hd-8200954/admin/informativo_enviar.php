<?php

require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';
require_once 'autentica_admin.php';

$msg_erro = array();
$from = "noreply@blackedecker.com.br";
$from_name = "Black&Decker";

function mail_text($mailto, $from_mail, $from_name, $replyto, $subject, $message) {
    $uid = md5(uniqid(time()));
    $name = basename($file);
	$header  = 'MIME-Version: 1.0' . "\r\n";
	$header .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $header .= "From: ".$from_name." <".$from_mail.">\r\n";
    $header .= "Reply-To: ".$replyto."\r\n";
    if (mail($mailto, utf8_encode($subject), utf8_encode($message), $header)) {
		return true;
    } else {
		return false;
    }
}

$ajax = strlen($ajax) == 0 && isset($_GET["ajax"]) ? $_GET["ajax"] : $ajax;
$ajax = strlen($ajax) == 0 && isset($_POST["ajax"]) ? $_POST["ajax"] : $ajax;

$destinatario_unico = strlen($destinatario_unico) == 0 && isset($_GET["destinatario"]) ? $_GET["destinatario"] : $destinatario_unico;
$destinatario_unico = strlen($destinatario_unico) == 0 && isset($_POST["destinatario"]) ? $_POST["destinatario"] : $destinatario_unico;

$informativo = strlen($informativo) == 0 && isset($_GET["informativo"]) ? $_GET["informativo"] : $informativo;
$informativo = strlen($informativo) == 0 && isset($_POST["informativo"]) ? $_POST["informativo"] : $informativo;

if (strlen($informativo) > 0) {
	try {
		$informativo = intval($informativo);
		
		$sql = "
		SELECT
		*
		
		FROM
		tbl_informativo
		
		WHERE
		informativo={$informativo}
		";
		@$res = pg_query($con, $sql);
		
		if (strlen(pg_last_error($con)) > 0) throw new Exception("<erro msg='".pg_last_error($con)."'>", 1);
		
		if (pg_num_rows($res) == 0) {
			throw new Exception("Informativo não encontrado");
		}
		
		$dados = pg_fetch_array($res);
	}
	catch(Exception $e) {
		unset($_POST["btn_acao"]);
		$msg_erro[] = ($e->getCode() == 1 ? "Falha ao localizar informativo" : "") . $e->getMessage();
	}
}
else {
	$msg_erro[] = "Informativo não informado, impossível continuar";
}

if(is_array($msg_erro) && count($msg_erro) > 0) {
	$msg_erro = implode('<br>', $msg_erro);
	echo "<link href='../telecontrol_oo.css' rel='stylesheet' type='text/css'>";
	echo "<center>";
	echo "<div id='msg_erro' name='msg_erro' class='msg_erro'>{$msg_erro}</div>";
	die;
}

ob_start();
require_once("informativo_email.php");
$html = ob_get_clean();

if (isset($destinatario_unico)) {
	$resultado = mail_text($destinatario_unico, $from, $from_name, $from, $dados["titulo"], $html);
	
	if ($ajax == 1) {
		if ($resultado == 1) {
			echo "ok";
		}
		else {
			$msg_erro[] = $resultado;
			echo "falha|{$resultado}";
		}
		die;
	}
}
else {
	$sql = "
	SELECT
	destinatario,
	email,
	'destinatario' AS tipo

	FROM
	tbl_destinatario

	WHERE
	fabrica={$login_fabrica}
	AND destinatario NOT IN (
		SELECT
		destinatario
		
		FROM
		tbl_informativo_destinatario
		
		WHERE
		informativo={$informativo}
		AND destinatario IS NOT NULL
	)
	/*teste ebano - apagar a linha abaixo*/
	AND destinatario=137

	UNION

	SELECT
	posto AS destinatario,
	contato_email AS email,
	'posto' AS tipo

	FROM
	tbl_posto_fabrica

	WHERE
	fabrica={$login_fabrica}
	AND posto NOT IN (
		SELECT
		posto
		
		FROM
		tbl_informativo_destinatario
		
		WHERE
		informativo={$informativo}
		AND posto IS NOT NULL
	)
	/*teste ebano - apagar a linha abaixo*/
	AND posto=6359

	UNION

	SELECT
	admin AS destinatario,
	email,
	'admin' AS tipo

	FROM
	tbl_admin

	WHERE
	/*teste ebano - descomentar linha abaixo*/
	/*fabrica={$login_fabrica}
	AND */admin NOT IN (
		SELECT
		admin
		
		FROM
		tbl_informativo_destinatario
		
		WHERE
		informativo={$informativo}
		AND admin IS NOT NULL
	)
	/*teste ebano - apagar a linha abaixo*/
	AND admin=1819
	";
	$res = pg_query($con, $sql);
	$n_destinatarios = pg_num_rows($res);

	for($i = 0; $i < $n_destinatarios; $i++) {
		$destinatario = pg_fetch_array($res);
		$resultado = mail_text($destinatario["email"], $from, $from_name, $from, $dados["titulo"], $html);
		echo "Enviado e-mail para {$destinatario["email"]}<br>";
		
		if ($resultado == 1) {
			$sql = "
			INSERT INTO tbl_informativo_destinatario (
			informativo,
			{$destinatario["tipo"]}
			)
			
			VALUES(
			{$informativo},
			{$destinatario["destinatario"]}
			)
			";
			$res_insert = pg_query($con, $sql);
			if (pg_last_error($con)) $msg_erro[] = "Erro ao registrar envio para destinatário {$destinatario["destinatario"]} - {$destinatario["email"]}";
		}
		else {
			$msg_erro[] = "Falha ao enviar mensagem para destinatário {$destinatario["destinatario"]} - {$destinatario["email"]}: {$resultado}";
		}
	}
}

if(is_array($msg_erro) > 0) {
	$msg_erro = implode('<br>', $msg_erro);
	echo "<link href='../telecontrol_oo.css' rel='stylesheet' type='text/css'>";
	echo "<center>";
	echo "<div id='msg_erro' name='msg_erro' class='msg_erro'>{$msg_erro}</div>";
}

?>
