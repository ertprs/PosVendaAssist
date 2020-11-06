<?php
$funcoes_fabrica = [
	"configurar_email_posto"
];

function configurar_email_posto() {
	global $con, $login_fabrica, $areaAdmin, $postoEmailConfig, $posto, $os;

	$postoData = getPostoData($posto);
    $posto_email = $postoData["contato_email"];

	$msg_email = "<h3>A Fábrica Hikari interagiu na Ordem de Serviço <strong>{$os}</strong>.</h3><br />
        <strong style='color: #FF0000;' >Interação:</strong> {$interacao_mensagem}<br />
        <a href='http://posvenda.telecontrol.com.br/assist/os_press.php?os={$os}' target='_blank' >clique aqui para visualizar a Ordem de Serviço</a>";
    $headers = "MIME-Version: 1.0 \r\n
    			Content-type: text/html; charset=iso-8859-1 \r\n
    			From: Telecontrol <noreply@telecontrol.com.br> \r\n";

	$postoEmailConfig = array(
	    "assunto"  => "Hikari - Interação na Ordem de Serviço",
	    "mensagem" => $msg_email,
	    "headers"  => $headers,
	    "email"    => $posto_email
	);

}