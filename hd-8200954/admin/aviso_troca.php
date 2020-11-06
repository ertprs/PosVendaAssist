<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

if (empty($_GET["os"]) || !in_array($login_fabrica, array(141))) {
	exit;
}

$sql = "SELECT
			tbl_os.sua_os,
			tbl_os.serie,
			tbl_produto.referencia,
			tbl_os.consumidor_revenda,
			tbl_os.consumidor_email,
			tbl_revenda.email AS revenda_email
		FROM tbl_os
		INNER JOIN tbl_produto ON tbl_produto.fabrica_i = {$login_fabrica} AND tbl_produto.produto = tbl_os.produto
		LEFT JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
		INNER JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os
		WHERE tbl_os.fabrica = {$login_fabrica}
		AND tbl_os.os = {$os}
		AND tbl_os_troca.ressarcimento IS NOT TRUE";
$res = pg_query($con, $sql);

if (!pg_num_rows($res)) {
	exit;
}

$data               = date("d/m/Y");
$produto_referencia = pg_fetch_result($res, 0, "referencia");
$serie              = pg_fetch_result($res, 0, "serie");
$sua_os             = pg_fetch_result($res, 0, "sua_os");
$consumidor_revenda = pg_fetch_result($res, 0, "consumidor_revenda");

$consumidor_email = "guilherme.monteiro@telecontrol.com.br";
if (strtoupper($consumidor_revenda) == "C") {
	$email = pg_fetch_result($res, 0, "consumidor_email");
} else {
	$email = pg_fetch_result($res, 0, "revenda_email");
}

$conteudo = "<h2 style='text-align: center;'>AVISO DE TROCA DE PRODUTO</h2>
<p style='text-align: center;'>SAO PAULO, {$data}</p>
Informamos para os devidos fins, que em virtude da impossibilidade de reparo do seu
produto modelo {$produto_referencia} de n�mero de s�rie {$serie}, o aparelho foi
recolhido pela Assist�ncia t�cnica UNICOBA. Ser� efetuado a TROCA EM GARANTIA por
outro produto de igual modelo ou similar e encaminhado ao(s) Sr(s) diretamente de nossa
f�brica.
<p>
Solicitamos que aguarde o novo produto no endere�o informado.
</p>

<p>
*Este documento tem car�ter estritamente informativo e sem valor fiscal.<br />
*O prazo de garantia permanece valido a partir da sua Nota Fiscal de compra.
</p>

<p style='text-align: center;'>
O n�mero da sua Ordem de Servi�o �: <b>{$sua_os}</b>
</p>

<b><u>Este � um servi�o de email, n�o responder esta mensagem, ver contatos abaixo.</u></b>

<p>Obrigado por preferir nosso servi�os.</p>
<p>Atenciosamente,</p>
<p>Depto de Assist�ncia T�cnica</p>

<p>
Av. Jabaquara, 2372 - Mirand�polis - S�o Paulo - SP - CEP: 04046-400<br />
Tel. Pessoa F�sica: (11) 5070.1709<br />
E-mail Pessoa F�sica: <u>services@unicoba.com.br</u><br />
Tel. Pessoa Jur�dica: (11) 5078.1911<br />
E-mail Pessoa Jur�dica: <u>at@unicoba.com.br</u><br />
</p>";

echo $conteudo;

if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
	$header  = "MIME-Version: 1.0 \r\n";
	$header .= "Content-type: text/html; charset=iso-8859-1 \r\n";
	$header .= "To: {$email} \r\n";
	$header .= "From: naoresponder@telecontrol.com.br\r\n";

	mail($email, "UNICOBA - AVISO DE TROCA DE PRODUTO", $conteudo, $header);
}

?>