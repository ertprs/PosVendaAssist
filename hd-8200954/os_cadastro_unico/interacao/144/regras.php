<?php
$funcoes_fabrica = [
	"configurar_email_posto"
];

function configurar_email_posto() {
	global $con, $login_fabrica, $areaAdmin, $postoEmailConfig, $posto, $os;

	$postoData = getPostoData($posto);
    $posto_email = $postoData["contato_email"];

	$msg_email = "<h3>A F�brica Hikari interagiu na Ordem de Servi�o <strong>{$os}</strong>.</h3><br />
        <strong style='color: #FF0000;' >Intera��o:</strong> {$interacao_mensagem}<br />
        <a href='http://posvenda.telecontrol.com.br/assist/os_press.php?os={$os}' target='_blank' >clique aqui para visualizar a Ordem de Servi�o</a>";
    $headers = "MIME-Version: 1.0 \r\n
    			Content-type: text/html; charset=iso-8859-1 \r\n
    			From: Telecontrol <noreply@telecontrol.com.br> \r\n";

	$postoEmailConfig = array(
	    "assunto"  => "Hikari - Intera��o na Ordem de Servi�o",
	    "mensagem" => $msg_email,
	    "headers"  => $headers,
	    "email"    => $posto_email
	);

}