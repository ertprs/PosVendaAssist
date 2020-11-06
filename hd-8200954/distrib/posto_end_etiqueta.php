<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
//include 'autentica_usuario.php';

if (count(array_filter($_GET))) {
	include_once '../helpdesk/mlg_funciones.php';
	$posto = anti_injection($_GET['posto']);

	//Validações
	try {
		if (!is_numeric($posto)) throw new exception("ID do posto informado inválido!");

		$sql = <<<POSTO
SELECT	nome,
	endereco,
	numero,
	complemento,
	bairro,
	cep,
	cidade,
	estado,
	fone,
	email
  FROM	tbl_posto
 WHERE	tbl_posto.posto = $posto 
POSTO;
		$res = @pg_query($con, $sql);
		if (!is_resource($res)) throw new exception("Erro ao conectar como Banco de Dados!");

		extract(pg_fetch_assoc($res, 0), EXTR_PREFIX_ALL, 'posto');

		//Formatando valores
		$posto_fone = (strlen($posto_fone) < 18) ? preg_replace('/\D/', '', $posto_fone):$posto_fone;
		$posto_fone = preg_replace('/^(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $posto_fone);

		$posto_endereco_completo = $posto_endereco;
		$posto_endereco_completo .= (strpos($posto_endereco, trim($posto_numero))===false) ?
										", $posto_numero" : '';

		//vCards do Posto e da Telecontrol (para o QRCode)
		$posto_vCard = <<<vCARD
BEGIN:VCARD
VERSION:2.0
ORG:$posto_nome
PHONE:$posto_fone
EMAIL:$posto_email
END:VCARD
vCARD;
		$telecontrol_vCard = <<<TCvCARD
BEGIN:VCARD
VERSION:2.0
ORG:ACÁCIAELETRO PAULISTA LTDA.
TEL:(14)3402-6588
EMAIL:distribuidor@telecontrol.com.br
END:VCARD
TCvCARD;
		//$posto_vCard		= str_replace(array(' ',"\n"), array('%20','%0A'), urlencode($posto_vCard));
		$posto_vCard		= urlencode(utf8_encode($posto_vCard));
		$telecontrol_vCard	= urlencode(utf8_encode($telecontrol_vCard));
		$cep_barCode		= "http://barcode.tec-it.com/barcode.ashx?code=Code128&modulewidth=fit&data=$posto_cep&dpi=150&imagetype=png&rotation=0&color=&bgcolor=&fontcolor=";
		$gQRUrl = 'https://chart.googleapis.com/chart';
		$qrCode_posto = "$gQRUrl?cht=qr&choe=ISO-8859-1&chld=Q|3&chs=200x200&chl=$posto_vCard";
		$qrCode_tc	  = "$gQRUrl?cht=qr&choe=ISO-8859-1&chld=Q|3&chs=200x200&chl=$telecontrol_vCard";
	}
	catch (Exception $e) {
		die("<div class='erro'>" . $e->getMessage() . '</div>');
	}
}
else { //Chamou sem parâmetros!
	echo "<script type='text/javascript'>window.close();</script>";
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="pt-br">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1">
	<title>Imprimir etiqueta do posto <?=$posto_nome?></title>
	<style type="text/css">
	@media screen,print {
		h2 {
			color: white;
			font-size: 24px;
			text-align:center;
			font-weight:bold;
			margin: 0.5em;
			width: 250px;
		}
		div#etiqueta {
			position: relative;
			font-size: 16px;
			margin: auto;
			font-family: Arial, Free Sans, sans-serif;
			text-align: left;
		}
		div#etiqueta>p {position:relative}
		div#etiqueta>p>img { /* qrCode com o endereço completo */
			position: absolute;
			top:  -1em;
			right: 1em;
			width: 200px;
			height: 200px;
		}
	}
	@media print {
		@page {
			size: 21.0 cm  29.7 cm; /* WxH */
			margin-top:		0.5 cm;
			margin-left:	1.5 cm;
			margin-right:	1.5 cm;
			margin-bottom:	2.0 cm;
			orphans:0;
			widows: 2;
		}
		body {
			color: black;
			background-color:#FFF;
			background-image:url();
			margin:	0;
			padding:0;
		}
		.no-print {display:none}
		#etiqueta {font-size: 16pt;}
		#etiqueta p {padding-left:5em;}
	}
	</style>
</head>
<body>
	<div id="etiqueta">
		<h2><img src='../imagens/h2_destinatario.png' alt='Destinatário' /></h2>
		<p>
			<img src="<?=$qrCode_posto?>" alt="Endereço completo" />
			<strong><?=$posto_nome?></strong><br />
			<?=$posto_endereco_completo?><br />
				<?=$posto_complemento?><br />
			<?=$posto_bairro?><br />
			<?=$posto_cidade .' - ' . $posto_estado?><br />
			<?=$posto_cep?><br />
		</p>
		<div align='center'>
			<img src="<?=$cep_barCode?>" width='145' alt="CEP do Posto" />
		</div>
		<hr width='98%' align='center' height='1' />
		<h2><img src='../imagens/h2_remetente.png' alt='Destinatário' /></h2>
		<p>
			<img src="<?=$qrCode_tc?>" alt="Endereço completo" />
			<strong>ACÁCIAELETRO PAULISTA EIRELI EPP</strong><br />
			AVENIDA CARLOS ARTÊNCIO, Nº 420-B<br />
			FRAGATA<br />
			17519-255<br />
			MARÍLIA - SP<br />
		</p>
	</div>
	<script type='text/javascript'>window.document.print();</script>
</body>
</html>
