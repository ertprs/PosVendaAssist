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

if (strlen($_GET["finaliza"]) > 0) {
	$finaliza = trim($_GET["finaliza"]);
}

$sql = "SELECT	tbl_posto_fabrica.codigo_posto,
				tbl_posto_fabrica.tipo_posto  ,
				tbl_posto_fabrica.pedido_em_garantia,
				tbl_posto_fabrica.pedido_faturado,
				tbl_posto.nome                ,
				tbl_posto.email               ,
				tbl_posto.estado
		FROM	tbl_posto_fabrica
		JOIN	tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
		WHERE	tbl_posto_fabrica.posto = $login_posto
		AND		tbl_posto_fabrica.fabrica = $login_fabrica";
$res_posto = pg_exec ($con,$sql);
//echo $sql;
if (pg_numrows ($res_posto) == 0 OR strlen (trim (pg_errormessage($con))) > 0 ) {
	header ("Location: index.php");
}else{
	$codigo             = trim(pg_result($res_posto,0,codigo_posto));
	$tipo_posto         = trim(pg_result($res_posto,0,tipo_posto));
	$pede_peca_garantia = trim(pg_result($res_posto,0,pedido_em_garantia));
	$pede_peca_faturada = trim(pg_result($res_posto,0,pedido_faturado));
	$nome               = trim(pg_result($res_posto,0,nome));
	$email              = trim(pg_result($res_posto,0,email));
	$estado             = trim(pg_result($res_posto,0,estado));
}

$btn_finalizar = $HTTP_POST_VARS['btn_finalizar'];
if ($btn_finalizar == '1' ) {
	if (strlen($_POST["peca_faltante"]) > 0) $peca_faltante = trim($_POST["peca_faltante"]);
/*	
	$sql = "INSERT INTO tbl_posto_peca_faltante (
				posto         ,
				texto
			) VALUES (
				$posto        ,
				'$peca_faltante'
			);";
	$res0 = @pg_exec ($con,$sql);
	
	if (strlen ( pg_errormessage ($con) ) > 0) {
		$erro = pg_errormessage ($con) ;
	}
*/	
	if (strlen($erro) == 0) {
		$subject    = "Equipamentos parados em oficina";
		$from_nome  = "$nome";
		$from_email = "$email";
		
/*
		if ($estado == 'MS' OR $estado == 'PR' OR $estado == 'SC' OR $estado == 'RS'){
			$to_nome  = "DIOGO ROCHA";
			$to_email = "drocha@blackedecker.com.br";
		}elseif ($estado == 'GO' OR $estado == 'SP' OR $estado == 'MG' OR $estado == 'ES' OR $estado == 'RJ'){
			$to_nome  = "ANDERSON CAMILO";
			$to_email = "acamilo@blackedeker.com.br";
		}else{
			$to_nome  = "Christoph Schafer";
			$to_email = "cschafer@blackedecker.com.br";
		}
*/

		#sudeste -SP MG RJ  ES
		#sul - PR RS SC   # centro : GO MT DF  MS
		#norte e nordeste - os outros estado

		# Alterado por Fabio em 27/07/2007 HD 3246
		if ($estado == 'MG' OR $estado == 'RJ' OR $estado == 'ES'){
			$to_nome  = "SABRINA AMARAL";
			$to_email = "samaral@blackedecker.com.br";
		}elseif ($estado == 'PR' OR $estado == 'RS' OR $estado == 'SC' OR $estado == 'GO' OR $estado == 'MT' OR $estado == 'DF' OR $estado == 'MS'){
			$to_nome  = "ANDRÉ UEMURA";
			$to_email = "andre_uemura@blackedecker.com.br";
		}elseif( $estado == 'SP'){
			$to_nome  = "FERNANDA SILVA";
			$to_email = "fernanda_silva@blackedecker.com.br";
		}else{
			$to_nome  =  "JOSÉ REINALDO";
			$to_email = "jreinaldo@blackedecker.com.br";
		}
		
		$mensagem  = "<table width='600' border='0' cellpadding='2' cellspacing='2' align='center'>\n";
		$mensagem .= "<tr>\n";
		
		$mensagem .= "<td width='100%' align='left' bgcolor='#FFF4F4'>\n";
		$mensagem .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
		$mensagem .= "<b>Segue relação de equipamentos parados no posto:</b>\n";
		$mensagem .= "</font>\n";
		$mensagem .= "</td>\n";
		
		$mensagem .= "</tr>\n";
		$mensagem .= "<tr>\n";
		
		$mensagem .= "<td width='100%' align='left' bgcolor='#FFF4F4'>\n";
		$mensagem .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
		$mensagem .= "$codigo - $nome\n";
		$mensagem .= "</font>\n";
		$mensagem .= "</td>\n";
		
		$mensagem .= "</tr>\n";
		$mensagem .= "<tr>\n";
		
		$mensagem .= "<td width='100%' align='left' bgcolor='#F2F2FF'>\n";
		$mensagem .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
		$mensagem .= nl2br($peca_faltante);
		$mensagem .= "</font>\n";
		$mensagem .= "</td>\n";
		
		$mensagem .= "</tr>\n";
		$mensagem .= "</table>\n";
		
		$cabecalho  = "MIME-Version: 1.0\n";
		$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n";
		$cabecalho .= "From:" .$from_nome. "<" .$from_email. ">\n";
		$cabecalho .= "To:" .$to_nome. "<" .$to_email. ">\n";
		$cabecalho .= "Return-Path: <". $from_email .">\n";
		$cabecalho .= "X-Priority: 1\n";
		$cabecalho .= "X-MSMail-Priority: High\n";
		$cabecalho .= "X-Mailer: PHP/" . phpversion();

//	6/10/2009 MLG - Gera arquivo no cgi-bin da fábrica para mostar na tela dos admin
		$arq_email = "/var/www/cgi-bin/blackedecker/saida/peca_falante_$login_posto"."_".date("Y-m-d-H-i").".eml";
		$txt_email = "$cabecalho\nDate: ".date("Y-m-d H:i")."\nSubject: $subject, posto $login_codigo_posto\n\n$mensagem\n";
		file_put_contents($arq_email, $txt_email);
?>		<p>
<?		echo "Gravar mensagem em: <b>$arq_email</b><br><h5>Mensagem:</h5>$txt_email";?>
		</p>
<?
		mail ("$to_email" , utf8_encode("$subject") , utf8_encode("$mensagem") , "$cabecalho");
		
		$from_nome  = "";
		$from_email = "";
		$to_nome    = "";
		$to_email   = "";
		$subject    = "";
		$cabecalho  = "";
	}
	
	if (strlen($erro) == 0) {
		header ("Location: $PHP_SELF?finaliza=ok");
		exit;
	}else{
		$msg  = "<b>Foi detectado o seguinte erro: </b><br>";
		$msg .= $erro;
	}
}

$layout_menu = "tecnica";

$title		= "Informação sobre peças faltantes";

include "cabecalho.php";

?>

<table width="650" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align="center" class="f_<?echo $css;?>_10">
		<b>
		<? echo $msg; ?>
		</b>
	</td>
</tr>
</table>

<? if (strlen($finaliza) == 0) { ?>

<form name="frmpecafaltante" method="post" action="<? echo $PHP_SELF ?>">

<table width="650" align="center" border="0" cellpadding="0" cellspacing="0" align="center">
<tr>
	<td width='100%' align="left" valign='top' class="f_<?echo $css?>_12">
		<p align="justify" style="margin-top:5;margin-bottom:5;margin-left:5;margin-right:5" class="f_<?echo $css?>_10">
			Informe abaixo quais equipamentos estão parados em sua oficina por falta de peças:
		</p>
	</td>
</tr>
<tr>
	<td width='100%' align="left" valign='top' class="f_<?echo $css?>_12">
		<p align="justify" style="margin-top:5;margin-bottom:5;margin-left:5;margin-right:5" class="f_<?echo $css?>_10">
		<b>
		Favor destacar:
		<br>
		- Modelo e voltagem do equipamento.
		<br>
		- Motivo porque está parado.
		<br>
		- Quais peças estão faltando.
		<br>
		- Fez pedido de peças para Black & Decker? Qual o número e data?
		<br>
		- Fez pedido para seu Distribuidor? Quando e qual é o Distribuidor?
		</b>
		</p>
	</td>
</tr>
<tr>
	<td width='100%' align="left" valign='top' class="f_<?echo $css?>_12">
		<p align="justify" style="margin-top:5;margin-bottom:5;margin-left:5;margin-right:5" class="f_<?echo $css?>_10">
			<b>Informe na sequência: Código do equipamento, código da peça, quantidade de peças necessárias e o motivo pelo qual se encontra parado</b>.
		</p>
	</td>
</tr>
<tr>
	<td width='100%' align="center" valign='top' class="f_<?echo $css?>_12">
		<textarea name="peca_faltante" cols="40" rows="5" style="width:550px"><?echo $peca_faltante?></textarea>
	</td>
</tr>
<tr>
	<td width='100%' align="center" valign='top' class="f_<?echo $css?>_12">
		<input type='hidden' name='btn_finalizar' value='0'>
		<input type='submit' name='btnacao' class='btnrel' value='Enviar...' onclick="javascript: if ( document.frmpecafaltante.btn_finalizar.value == '0' ) { document.frmpecafaltante.btn_finalizar.value='1'; document.frmpecafaltante.submit() ; } else { alert ('Aguarde submissão da OS...'); }">
	</td>
</tr>
</table>


</form>

<? }else{ ?>

<table width="650" align="center" border="0" cellpadding="0" cellspacing="0">
<tr>
	<td width='100%' align="left" valign='top' class="f_<?echo $css?>_12">
		<p align="justify" style="margin-top:5;margin-bottom:5;margin-left:5;margin-right:5" class="f_<?echo $css?>_10">
			<b><? echo pg_result ($res_posto,0,nome) ?></b>, as informações foram encaminhadas para a Black & Decker. Obrigado.
		</p>
		<p>
<?		echo "Gravar mensagem em: <b>$arq_email</b><br><h5>Mensagem:</h5>$txt_email";?>
		</p>
	</td>
</tr>
</table>

<? } ?>
<?include 'rodape.php';?>
