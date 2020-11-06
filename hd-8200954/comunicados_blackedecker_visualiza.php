<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$sql =	"SELECT tbl_posto.*, tbl_posto_fabrica.*
		FROM tbl_posto
		JOIN tbl_posto_fabrica  ON  tbl_posto.posto           = tbl_posto_fabrica.posto
								AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_posto.posto = $login_posto";
$res_posto = @pg_exec ($con,$sql);
if (@pg_numrows ($res_posto) == 0 OR strlen (trim (pg_errormessage($con))) > 0 ) {
	header ("Location: index.php");
	exit;
}else{
	$posto        = trim(pg_result($res_posto,0,posto));
	$posto_nome   = trim(pg_result($res_posto,0,nome));
	$codigo_posto = trim(pg_result($res_posto,0,codigo_posto));
	$tipo_posto   = trim(pg_result($res_posto,0,tipo_posto));
}

// LOCALIZA OS DADOS DO COMUNICADO
$sql = "SELECT  tbl_comunicado_blackedecker.comunicado                                      ,
				tbl_comunicado_blackedecker.assunto                                         ,
				to_char(tbl_comunicado_blackedecker.data_envio, 'DD/MM/YYYY') AS data_envio ,
				tbl_comunicado_blackedecker.remetente                                       ,
				tbl_comunicado_blackedecker.mensagem                                        ,
				tbl_comunicado_blackedecker.anexo                                           ,
				tbl_comunicado_blackedecker.pede_peca_garantia                              ,
				tbl_comunicado_blackedecker.pede_peca_faturada                              ,
				tbl_comunicado_blackedecker.suframa                                         
		FROM    tbl_comunicado_blackedecker
		LEFT JOIN tbl_comunicado_posto_blackedecker ON tbl_comunicado_posto_blackedecker.comunicado = tbl_comunicado_blackedecker.comunicado
		LEFT JOIN tbl_posto            ON tbl_posto.posto                 = tbl_comunicado_posto_blackedecker.posto
		WHERE   tbl_comunicado_blackedecker.comunicado = $comunicado
		AND     tbl_comunicado_blackedecker.destinatario_especifico ILIKE '%$codigo_posto%'";
$res = pg_exec($con,$sql);
//echo nl2br($sql);
if (pg_numrows($res) == 0) {
	echo "<script>";
	echo "window.close();";
	echo "</script>";
}else{
	// VERIFICA SE JÁ FOI LIDO
	if(strlen($lido) == 0){
		$sql = "INSERT INTO tbl_comunicado_posto_blackedecker (
					comunicado       ,
					posto            ,
					data_confirmacao 
				)VALUES(
					$comunicado       ,
					$posto            ,
					current_timestamp 
				)";
		$resX = @pg_exec($con,$sql);
	}

	$comunicado         = pg_result($res,0,comunicado);
	$remetente          = pg_result($res,0,remetente);
	$data_envio         = pg_result($res,0,data_envio);
	$assunto            = pg_result($res,0,assunto);
	$mensagem           = pg_result($res,0,mensagem);
	$anexo              = pg_result($res,0,anexo);
	$pede_peca_garantia = pg_result($res,0,pede_peca_garantia);
	$pede_peca_faturada = pg_result($res,0,pede_peca_faturada);
	$suframa            = pg_result($res,0,suframa);

	if ($S3_sdk_OK) {
		include S3CLASS;
		if ($S3_online)
			$s3 = new anexaS3('co', (int) $login_fabrica, $comunicado);
	}

}

/*-----------------------------------------------------------------------------
	FormataTexto($text, $return)
	$text   = texto a ser alterado
	$return = 'lower' (minuscula) / 'upper' (maiuscula)
	Pega uma string e retorna em letras minusculas ou maiusculas
	Uso: $texto_upper = FormataTexto($text, 'upper');
-----------------------------------------------------------------------------*/
function FormataTexto($text,$return){
	if (!empty($text)) {

		$arrayLower=array('ç','â','ã','á','à','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','õ','ô','ö','ú','ù','û','ü');
		$arrayUpper=array('Ç','Â','Ã','Á','À','Ä','É','È','Ê','Ë','Í','Ì','Î','Ï','Ó','Ò','Õ','Ô','Ö','Ú','Ù','Û','Ü');

		if ($return == 'lower') {
			$text=strtolower($text);
			for($i=0; $i<count($arrayLower); $i++) {
				$text=str_replace($arrayUpper[$i], $arrayLower[$i], $text);
			}
		} elseif ($return == 'upper') {
			$text=strtoupper($text);
			for($i=0; $i<count($arrayLower); $i++) {
				$text=str_replace($arrayLower[$i], $arrayUpper[$i], $text);
			}
		}
		return($text);
	}
}
# ---------------------------------------------------------------------------------

$layout_menu = "os";
$title = "Comunicado do Sistema";

include "cabecalho.php";
?>

<table width='75%' border="0" cellpadding="5" cellspacing="2" align="center">
<tr>
	<td colspan='4' width='100%' valign='top' class="f_<?echo $css;?>_10">
		<p align="justify" style="margin-top:5;margin-bottom:5;margin-left:5;margin-right:5">
			Comunicado:
		</p>
	</td>
</tr>
<tr>
	<td colspan="4">&nbsp;</td>
</tr>
<tr>
	<td><b>Remetente</b></td>
	<td><? echo FormataTexto($remetente, 'upper'); ?></td>
	<td><B>Data</B></td>
	<td><? echo $data_envio; ?></td>
</tr>
<tr>
	<td><B>Assunto</B></td>
	<td colspan="3"><? echo $assunto; ?></td>
</tr>
<tr>
	<td colspan="4"><B>Mensagem</B></td>
</tr>
<tr>
	<td colspan="4"><? echo "<b>Prezado(a) $posto_nome,</b><br><br>\n".nl2br(stripslashes($mensagem)); ?></td>
</tr>
<?
if ($anexo == 't'){
	if ($S3_online and $s3->temAnexo) {
		$arquivo = $s3->url;
	} else {
		$jpg = "/var/www/blackedecker/www/comunicados/$comunicado.jpg";
		$gif = "/var/www/blackedecker/www/comunicados/$comunicado.gif";
		$pdf = "/var/www/blackedecker/www/comunicados/$comunicado.pdf";
		$doc = "/var/www/blackedecker/www/comunicados/$comunicado.doc";
		$xls = "/var/www/blackedecker/www/comunicados/$comunicado.xls";

		if (file_exists($jpg) == true) $arquivo = "http://www.blackdecker.com.br/comunicados/$comunicado.jpg";
		if (file_exists($gif) == true) $arquivo = "http://www.blackdecker.com.br/comunicados/$comunicado.gif";
		if (file_exists($pdf) == true) $arquivo = "http://www.blackdecker.com.br/comunicados/$comunicado.pdf";
		if (file_exists($doc) == true) $arquivo = "http://www.blackdecker.com.br/comunicados/$comunicado.doc";
		if (file_exists($xls) == true) $arquivo = "http://www.blackdecker.com.br/comunicados/$comunicado.xls";
	}
?>
<tr>
	<td><B>Anexo</B></td>
	<td colspan="3"><a href='<? echo $arquivo; ?>' target='_blank'>Clique aqui</a></td>
</tr>
<?
}
?>
</table>

<br><br>

<? include 'rodape.php'; ?>
