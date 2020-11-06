<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

/*-----------------------------------------------------------------------------
FormataTexto($text, $return)
$text = texto a ser alterado
$return = lower (minuscula) / upper (maiuscula)
Pega uma string e retorna em letras minusculas ou maiscular, depende re return
-----------------------------------------------------------------------------*/
function FormataTexto($text,$return){
 if (!empty($text)) {
	# Convert values from Lower to Upper
	$arrayLower=array('ç'
					  ,'â','ã','à','á','ä'
					  ,'é','è','ê','ë'
					  ,'í','ì','î','ï'
					  ,'ó','ò','ô','õ','ö'
					  ,'ú','ù','û','ü');

	$arrayUpper=array('Ç'
					  ,'Â','Ã','À','Á','Ä'
					  ,'É','È','Ê','Ë'
					  ,'Í','Ì','Î','Ï'
					  ,'Ó','Ò','Ô','Õ','Ö'
					  ,'Ú','Ù','Û','Ü');

	if ($return == 'lower') {
	   # Convert values from Upper to Lower
	   $text=strtolower($text);
	   for($i=0; $i<count($arrayLower); $i++) {
		  $text=str_replace($arrayUpper[$i], $arrayLower[$i], $text);
	   }
	} elseif ($return == 'upper') {
	   # Convert values from Lower to Upper
	   $text=strtoupper($text);
	   for($i=0; $i<count($arrayLower); $i++) {
		  $text=str_replace($arrayLower[$i], $arrayUpper[$i], $text);
	   }
	}
	return($text);
 }
}

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
	$posto              = trim(pg_result($res_posto,0,posto));
	$codigo_posto       = trim(pg_result($res_posto,0,codigo_posto));
	$pedido_faturado    = trim(pg_result($res_posto,0,pedido_faturado));
	$pedido_em_garantia = trim(pg_result($res_posto,0,pedido_em_garantia));
	$suframa            = trim(pg_result($res_posto,0,suframa));
	$tipo_posto         = trim(pg_result($res_posto,0,tipo_posto));
}

$aux_pedido_faturado    = ($pedido_faturado == 't') ? 'true' : 'false';
$aux_pedido_em_garantia = ($pedido_em_garantia == 't') ? 'true' : 'false';
$aux_suframa            = ($suframa == 't') ? 'true' : 'false';

$layout_menu = "os";
$title = "Comunicados";

include "cabecalho.php";
?>

<style>
.date {
	background: #F8F8F8;
	font-size: 10px;
	color: #666666;
	text-align: center;
	font-family: Verdana, Arial, Tahoma;
	padding: 0.25em;
	padding-right: 2em;
	padding-left: 2em;
	letter-spacing: 0.5px;
}
</style>

<table width="80%" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td valign='top'>
		<p align="justify" style="margin-top:5;margin-bottom:5;margin-left:5;margin-right:5">
			<b><? echo pg_result ($res_posto,0,nome) ?></b>, estes são os comunicados enviados para você:
		</p>
	</td>
</tr>
<tr>
	<td valign='top'>
		<TABLE width="100%" border="0" cellpadding="2" cellspacing="0" align="center">
		<TR>
			<TD width="50%" class="date" style="font-size:14px; margin-top:5;margin-bottom:5;margin-left:5;margin-right:5">
				<a href='<? echo $PHP_SELF."?tipo=Novos"; ?>'><B>Novos</B></a>
			</TD>
			<TD width="50%" class="date" style="font-size:14px; margin-top:5;margin-bottom:5;margin-left:5;margin-right:5">
				<a href='<? echo $PHP_SELF."?tipo=Lidos"; ?>'><B>Lidos</B></a>
			</TD>
		</TR>
		</TABLE>
	</td>
</tr>
<tr>
	<td valign='top' class="date" style="font-size:14px; margin-top:5;margin-bottom:5;margin-left:5;margin-right:5">
		<b>Comunicados <? echo $tipo; ?></b>
	</td>
</tr>
<tr>
	<td><br>
<?
if (strlen($_GET['tipo']) > 0) $tipo = $_GET['tipo'];

if (strlen($tipo) > 0){
	if ($tipo == "Novos") {
		$sql =	"SELECT tbl_comunicado.comunicado                                     ,
						tbl_comunicado.descricao                                        ,
						tbl_comunicado.destinatario_especifico                        ,
						to_char(tbl_comunicado.data, 'DD/MM/YYYY') AS data_envio,
						tbl_comunicado.pedido_em_garantia                             ,
						tbl_comunicado.pedido_faturado                             ,
						tbl_comunicado.suframa
				FROM    tbl_comunicado
				WHERE  (tbl_comunicado.destinatario_especifico ILIKE '%$codigo_posto%' 
				OR      tbl_comunicado.destinatario IN ('$tipo_posto'));";
		$res = pg_exec($con,$sql);

		for ($k=0; $k < pg_numrows($res); $k++) {
			$nao                     = false;
			$comunicado              = pg_result($res,$k,comunicado);
			$xpede_peca_garantia     = pg_result($res,$k,pedido_em_garantia);
			$xpede_peca_faturada     = pg_result($res,$k,pedido_faturado);
			$xsuframa                = pg_result($res,$k,suframa);
			$destinatario_especifico = pg_result($res,$k,destinatario_especifico);

			$sql = "SELECT  to_char(tbl_comunicado_posto_blackedecker.data_confirmacao, 'DD/MM/YYYY') AS data_confirmacao
					FROM    tbl_comunicado_posto_blackedecker
					WHERE   tbl_comunicado_posto_blackedecker.comunicado = $comunicado
					AND     tbl_comunicado_posto_blackedecker.posto      = $posto;";
			$res0 = pg_exec($con,$sql);

			if (pg_numrows($res0) > 0) {
				$data_confirmacao = trim(pg_result($res0,0,data_confirmacao));
				$virgula = "";
				if (trim($xpede_peca_garantia) == trim($aux_pedido_em_garantia) OR trim($xpede_peca_garantia) == 'todos'){
					if (trim($xpede_peca_faturada) == trim($aux_pedido_faturado) OR trim($xpede_peca_faturada) == 'todos'){
						if (trim($xsuframa) == trim($aux_suframa) OR trim($xsuframa) == 'todos'){
							if (strlen($data_confirmacao) == 0) {
								if ($k+1 <= pg_numrows($res)) {
									$virgula .= ", ";
								}
								$nao_lidos .= $virgula . "$comunicado";
							}
						}
					}
				}
			}else{
				$virgula = "";
				if (trim($xpede_peca_garantia) == trim($aux_pedido_em_garantia) OR trim($xpede_peca_garantia) == 'todos'){
					if (trim($xpede_peca_faturada) == trim($aux_pedido_faturado) OR trim($xpede_peca_faturada) == 'todos'){
						if (trim($xsuframa) == trim($aux_suframa) OR trim($xsuframa) == 'todos'){
							if ($k+1 <= pg_numrows($res) ) {
								$virgula .= ", ";
							}
							$nao_lidos .= $virgula . "$comunicado";
						}
					}
				}
			}
		}
	}

	$nao_lidos = substr($nao_lidos,1);

	if ($tipo == 'Lidos') {
		$sql = "SELECT 	tbl_comunicado.comunicado                                     ,
						tbl_comunicado.descricao                                        ,
						tbl_comunicado.destinatario_especifico                        ,
						to_char(tbl_comunicado.data, 'DD/MM/YYYY') AS data_envio, 
						to_char(tbl_comunicado_posto_blackedecker.data_confirmacao, 'DD/MM/YYYY') AS data_confirmacao,
						tbl_comunicado.pedido_em_garantia                             ,
						tbl_comunicado.pedido_faturado                             ,
						tbl_comunicado.suframa
				FROM	tbl_comunicado
				JOIN    tbl_comunicado_posto_blackedecker ON tbl_comunicado_posto_blackedecker.comunicado = tbl_comunicado.comunicado
				JOIN    tbl_posto            ON tbl_posto.posto                 = tbl_comunicado_posto_blackedecker.posto
				WHERE   (tbl_comunicado.destinatario_especifico ilike '%$codigo_posto%' 
				OR      tbl_comunicado.destinatario IN ('$tipo_posto'))
				AND     tbl_comunicado_posto_blackedecker.posto = $posto
				ORDER BY tbl_comunicado.comunicado DESC ";
	}else{
		$sql = "SELECT 	tbl_comunicado.comunicado                                     ,
						tbl_comunicado.descricao                                        ,
						tbl_comunicado.destinatario_especifico                        ,
						to_char(tbl_comunicado.data, 'DD/MM/YYYY') AS data_envio,
						''                                               AS data_confirmacao,
						tbl_comunicado.pedido_em_garantia                             ,
						tbl_comunicado.pedido_faturado                             ,
						tbl_comunicado.suframa
				FROM	tbl_comunicado
				WHERE   (tbl_comunicado.destinatario_especifico ILIKE '%$codigo_posto%' OR tbl_comunicado.destinatario IN ('$tipo_posto'))
				AND     tbl_comunicado.comunicado IN ($nao_lidos)
				ORDER BY tbl_comunicado.comunicado DESC ";
	}
	$res = @pg_exec($con,$sql);
	$total = @pg_numrows($res);
	
	for ($k=0; $k < $total; $k++) {
		$comunicado              = pg_result($res,$k,comunicado);
		$assunto                 = FormataTexto(pg_result($res,$k,descricao),'upper');
		$destinatario_especifico = pg_result($res,$k,destinatario_especifico);
		$data_envio              = pg_result($res,$k,data_envio);
		$data_confirmacao        = pg_result($res,$k,data_confirmacao);
		$xpede_peca_garantia     = pg_result($res,$k,pedido_em_garantia);
		$xpede_peca_faturada     = pg_result($res,$k,pedido_faturado);
		$xsuframa                = pg_result($res,$k,suframa);
/*
		$sql = "SELECT  to_char(tbl_comunicado_posto.data_confirmacao, 'DD/MM/YYYY') AS data_confirmacao
				FROM    tbl_comunicado_posto
				WHERE   tbl_comunicado_posto.comunicado = $comunicado
				AND     tbl_comunicado_posto.posto      = $posto ";
		$res0 = pg_exec($con,$sql);
		
		if (pg_numrows($res0) > 0) $data_confirmacao = trim(pg_result($res0,0,data_confirmacao));
		else                       $data_confirmacao = "";
*/
		if (strlen($destinatario_especifico) == 0){
			if (trim($xpede_peca_garantia) == trim($aux_pede_peca_garantia) OR trim($xpede_peca_garantia) == 'todos'){
				if (trim($xpede_peca_faturada) == trim($aux_pede_peca_faturada) OR trim($xpede_peca_faturada) == 'todos'){
					if (trim($xsuframa) == trim($aux_suframa) OR trim($xsuframa) == 'todos'){
						if (strlen($data_confirmacao) > 0) $lido = 't'; else $lido = '';
						/*
						echo "<tr class='f_".$css."_10' >\n";
						echo "<td align='left'>\n";
						echo "<b><a class='f_".$css."_10' href='comunicado_visualiza.php?comunicado=$comunicado&lido=$lido' target='_blank'>$assunto</a></b>\n";
						echo "</td>\n";
						echo "<td align='center'>$data_envio</td>\n";
						echo "<td align='center'>$data_confirmacao</td>\n";
						echo "</tr>\n";
						*/
						echo "<p>";
						echo "<span class=date align=center>$data_envio ";
						if (strlen($data_confirmacao) > 0) echo "| Lido em: $data_confirmacao";
						echo "</span><br>\n";
						echo "<a href='comunicados_blackedecker_visualiza.php?comunicado=$comunicado&lido=$lido' target='_blank'>$assunto</a>\n";
						echo "</p>\n";
					}
				}
			}
		}else{
			if (strlen($data_confirmacao) > 0) $lido = 't'; else $lido = '';
/*			
			echo "<tr class='f_".$css."_10' >\n";
			echo "<td align='left'>\n";
			echo "<b><a class='f_".$css."_10' href='comunicado_visualiza.php?comunicado=$comunicado&lido=$lido' target='_blank'>$assunto</a></b>\n";
			echo "</td>\n";
			echo "<td align='center'>$data_envio</td>\n";
			echo "<td align='center'>$data_confirmacao</td>\n";
			echo "</tr>\n";
*/
			echo "<p>";
			echo "<span class=date align=center>$data_envio ";
			if (strlen($data_confirmacao) > 0) echo "| Lido em: $data_confirmacao";
			echo "</span><br>\n";
			echo "<a href='comunicados_blackedecker_visualiza.php?comunicado=$comunicado&lido=$lido' target='_blank'>$assunto</a>\n";
			echo "</p>\n";
		}
	}
}
?>
	</td>
</tr>
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
</table>

<?include 'rodape.php';?>
