<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";

include "funcoes.php";

$erro = "";



if (strlen($_GET["resolvido"])  > 0){
$resolvido= $_GET["resolvido"];
$sql = "UPDATE tbl_extrato_status 
				set pendente = 'f',
					confirmacao_pendente = 'f'
		WHERE extrato=$resolvido 
		and pendente = 't' 
		and confirmacao_pendente = 't'";
//$res = pg_exec($con,$sql);
$extrato=$resolvido;
}



if (strlen(trim($_GET["extrato"])) > 0)  $extrato = trim($_GET["extrato"]);
if (strlen(trim($_POST["extrato"])) > 0) $extrato = trim($_POST["extrato"]);
if (strlen(trim($_POST["acao"])) > 0)    $acao = trim($_POST["acao"]);
if (strlen(trim($_POST["ped_adv"])) > 0)  $ped_adv = trim($_POST["ped_adv"]);
//if (strlen(trim($_POST["arquivo"])) > 0)  $arquivo = trim($_POST["arquivo"]);



function retira_acentos( $texto ){
 $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
 $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
 return str_replace( $array1, $array2, $texto );
}

if($ped_adv == 'pendencia'){
	$pendencia   = "'t'";
	$advertencia = "'f'";
}else{
	$pendencia   = "'f'";
	$advertencia = "'t'";
}


if ($acao == "ALTERAR") {



$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;







				$config["tamanho"] = 2048000; // Tamanho máximo do arquivo (em bytes) 

				if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){
					// Verifica o mime-type do arquivo
					if (!preg_match("/\/(zip|x-zip|x-zip-compressed|x-compress|x-compressed|pdf|msword|doc|word|x-msw6|x-msword|pjpeg|jpeg|png|gif|bmp|msexcel|xls|vnd.ms-excel|richtext|plain|html)$/", $arquivo["type"])){
						$msg_erro = "Arquivo em formato inválido!";
					} else { // Verifica tamanho do arquivo 
						if ($arquivo["size"] > $config["tamanho"]) {
							$msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";

						}

					}

					if (strlen($msg_erro) == 0) {
						// Pega extensão do arquivo
						preg_match("/\.(pdf|doc|gif|bmp|png|jpg|jpeg|rtf|xls|txt){1}$/i", $arquivo["name"], $ext);
						$aux_extensao = "'".$ext[1]."'";
						
						$arquivo["name"]=retira_acentos($arquivo["name"]);
						$nome_sem_espaco = implode("", explode(" ",$arquivo["name"]));

						// Gera um nome único para a imagem
						$nome_anexo = "/www/assist/www/admin/documentos/" . $extrato."-".strtolower ($nome_sem_espaco);

						// Faz o upload da imagem
						if (strlen($msg_erro) == 0) {
							if (copy($arquivo["tmp_name"], $nome_anexo)) {
							}else{
								$msg_erro = "Arquivo não foi enviado!!!";
							}
						}//fim do upload da imagem
					}//fim da verificação de erro
				}//fim da verificação de existencia no apache
				
$arquivoi = $arquivo["name"];
	$x_obs = trim($_POST["obs"]);
	$pendente = "'t'";
	if (strlen($x_obs) == 0) $erro .= " Preencha o campo Observação. ";
	
	if (strlen($erro) == 0) {
		$sql = "INSERT INTO tbl_extrato_status (
						extrato    ,
						obs        ,
						data       ,
						pendente   ,
						pendencia  ,
						advertencia,
						arquivo    
					) VALUES (
						$extrato          ,
						'$x_obs'          ,
						current_timestamp ,
						$pendente         ,
						$pendencia        ,
						$advertencia      ,
						'$arquivoi'
				);";
//echo $sql;
/*
pendente = informa para o posto que esta pendente
confirmacao_pendente = admin confirma que a pendecia esta resolvida
*/
	//	$res = @pg_exec ($con,$sql);
		$erro = pg_errormessage($con);

		if (strlen($erro) == 0 and $pendente=="'t'") {
		
			echo $xsql = "SELECT tbl_posto_fabrica.contato_email as email,
							tbl_posto_fabrica.codigo_posto
					from tbl_posto 
					join tbl_extrato on tbl_posto.posto = tbl_extrato.posto 
					join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto
										  and tbl_posto_fabrica.fabrica = $login_fabrica
					where extrato=$extrato";
			$xres = pg_exec($con,$xsql);
			$xemail_posto  = pg_result($xres,0,email);
			$xcodigo_posto = pg_result($xres,0,codigo_posto);

			$xsql = "SELECT protocolo from tbl_extrato where extrato=$extrato";
			$xres = pg_exec($con,$xsql);
			$xprotocolo = pg_result($xres,0,protocolo);

		echo	$xsql = "SELECT nome_completo, fone, email from tbl_admin where admin=$login_admin and fabrica=$login_fabrica limit 1";
//			echo "$xsql";
			$xres = @pg_exec($con,$xsql);
			$xnome_completo = @pg_result($xres,0,nome_completo);
			$xfone          = @pg_result($xres,0,fone);
			$xemail_admin   = @pg_result($xres,0,email);

			//hd 7548 - acrescentado o extrato e o posto no assunto
			$remetente    = "Black&Decker <waldir@telecontrol.com.br>"; 
			$destinatario = "waldir@telecontrol.com.br"; 

			if($ped_adv == "pendencia"){
				$assunto      = "Pendência em extrato ($xprotocolo), posto $xcodigo_posto"; 
			}else{
				$assunto      = "Alerta em extrato ($xprotocolo), posto $xcodigo_posto"; 
			}
			$mensagem     = "Prezado Posto Autorizado,<BR><BR>Você tem uma";
			if($ped_adv == 'pendencia') { $mensagem .= " pendência para enviar para a";}else{ $mensagem .= " alerta da";}
			$mensagem .= " Blackedecker no extrato de número $xprotocolo.<BR><BR>";
			if($ped_adv == 'pendencia') { $mensagem .= "<b>Pendência</b>";}else{ $mensagem .= "<b>Alerta</b>";}
			$mensagem .= "<BR>".nl2br($x_obs)."<BR><BR>"; 
			if (strlen($arquivo) > 0) {
			$mensagem .= "Existe um anexo junto a";
			if($ped_adv == 'pendencia') { $mensagem .= "<b>Pendência</b>";}else{ $mensagem .= "<b>Alerta</b>";}
			$mensagem .= "<BR><BR>Para visualizar o anexo entre em:";
			$mensagem .= "http://www.telecontrol.com.br/assist/extrato_status_aprovado.php?extrato=$extrato&tipo=pendencia";
			}
			$headers="Return-Path: <waldir@telecontrol.com.br>\nFrom:".$remetente."\nBcc:$waldir@telecontrol.com.br \nContent-type: text/html\n"; 
			
			
			if ( mail($destinatario,$assunto,$mensagem,$headers) ) {
				/*echo "<script language='JavaScript'>\n";
				echo "window.close();";
				echo "</script>";		*/
			}else{
				echo "erro";
			}
		}else{
			$xsql = "SELECT nome_completo, fone, email from tbl_admin where admin=$login_admin and fabrica=$login_fabrica limit 1";
//			echo "$xsql";
			$xres = @pg_exec($con,$xsql);
			$xnome_completo = @pg_result($xres,0,nome_completo);
			$xfone          = @pg_result($xres,0,fone);
			$xemail_admin   = @pg_result($xres,0,email);

			$remetente    = "Telecontrol <suporte@telecontrol.com.br>"; 
			$destinatario = "$xemail_admin"; 

			$assunto      = "Erro ao cadastrar Alerta/Pendência"; 

			$mensagem     = "Ocorreu um erro ao gravar a seguinte Alerta/Pendência:<br><br>";
			$mensagem .= "<BR>".nl2br($x_obs)."<BR><BR>"; 

			$headers="Return-Path: <$xemail_admin>\nFrom:".$remetente."\nBcc:$xemail_admin \nContent-type: text/html\n"; 
			
			//if ( mail($destinatario,$assunto,$mensagem,$headers) ) {
				/*echo "<script language='JavaScript'>\n";
				echo "window.close();";
				echo "</script>";		*/
		//	}else{
		//		echo "erro";
		//	}
		}
	}
}
?>

<html>

<head>

<title>Observação do Status do Extrato</title>

<style>
input {
	BORDER-RIGHT: #888888 1px solid;
	BORDER-TOP: #888888 1px solid;
	FONT-WEIGHT: bold;
	FONT-SIZE: 8pt;
	BORDER-LEFT: #888888 1px solid;
	BORDER-BOTTOM: #888888 1px solid;
	FONT-FAMILY: Verdana;
	BACKGROUND-COLOR: #f0f0f0
}
.erro {
  color: white;
  text-align: center;
  font: bold 12px Verdana, Arial, Helvetica, sans-serif;
  background-color: #FF0000;
}
.tabela {
    font-family: Verdana, Tahoma, Arial;
    font-size: 10pt;
    text-align: center;
}
</style>

</head>

<body>

<?
// CARREGA DADOS DO EXTRATO
$sql =	"SELECT TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao  ,
				TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS data_aprovado ,
				tbl_posto_fabrica.codigo_posto                 AS posto_codigo  ,
				tbl_posto.nome                                 AS posto_nome
		FROM tbl_extrato
		JOIN tbl_posto          ON  tbl_posto.posto           = tbl_extrato.posto
		JOIN tbl_posto_fabrica  ON  tbl_extrato.posto         = tbl_posto_fabrica.posto
								AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_extrato.extrato = $extrato;";
$res = pg_exec($con,$sql);

if (pg_numrows($res) == 1) {
	$data_geracao   = trim(pg_result($res,0,data_geracao));
	$data_aprovado  = trim(pg_result($res,0,data_aprovado));
	$posto_codigo   = trim(pg_result($res,0,posto_codigo));
	$posto_nome     = trim(pg_result($res,0,posto_nome));
	$posto_completo = $posto_codigo . " - " . $posto_nome;
}

if (strlen($erro) > 0) {
	$obs = trim($_POST["obs"]);
	echo "<div class='erro'>$erro</div>";
}
?>

<form name="frm_extrato" method="post" action="<?echo $PHP_SELF?>" enctype="multipart/form-data">

<input type="hidden" name="extrato" value="<?echo $extrato?>">
<input type="hidden" name="acao">

<table width='100%' border='0' cellspacing='1' cellpadding='1' class='tabela'>
	<tr>
		<td width='100%' colspan="3"><b>Posto</b></td>
	</tr>
	<tr>
		<td width='100%' colspan="3"><?echo substr($posto_completo,0,40)?></td>
	</tr>
	<tr>
		<td width='100%' colspan="3" height="5"></td>
	</tr>
	<tr>
		<td  width='50%' colspan="2"><b>Data Geração</b></td>
		<td width='50%'><b>Data Aprovado</b></td>
	</tr>
	<tr>
		<td width='50%' colspan="2"><?echo $data_geracao?></td>
		<td width='50%'><?echo $data_aprovado?></td>
	</tr>
</table><BR>

<table width='300' border='0' cellspacing='0' cellpadding='0' class='tabela' align='center'>
	<tr>
		<td  width='50%'><b><INPUT TYPE="radio" NAME="ped_adv" value='pendencia' CHECKED>Pendência</b></td>
		<td width='50%'><b><INPUT TYPE="radio" NAME="ped_adv" value='advertencia'>Alerta</b></td>
	</tr>
</table><BR>


<?
$xsql = "SELECT 	tbl_extrato_status.obs                            ,
				to_char(tbl_extrato_status.data,'DD/MM/YYYY') as data ,
				tbl_extrato_status.pendente                           ,
				tbl_extrato_status.advertencia                        ,
				tbl_extrato_status.confirmacao_pendente               ,
				tbl_extrato_status.extrato                            ,
				tbl_extrato_status.arquivo
		FROM tbl_extrato_status 
		WHERE extrato = $extrato 
		and (pendente notnull OR advertencia notnull)
		and obs not ilike 'Data do pagamento%'";
$xres = pg_exec($con,$xsql);
//echo "$xsql";
if(pg_numrows($xres)>0){
?>
<table width='100%' border='0' cellspacing='1' cellpadding='5' style='font-family: verdana; font-size: 11px'  bgcolor='#596D9B'>
<tr>
	<td width='80px' align='center'><font color='#FFFFFF'><b>Situação</b></FONT></td>
	<td><font color='#FFFFFF'><b>Pendência</b></FONT></td>
</tr>
<?	for($x=0;pg_numrows($xres)>$x;$x++){
		$xobs                  = pg_result($xres,$x,obs);
		$xdata                 = pg_result($xres,$x,data);
		$xpendente             = pg_result($xres,$x,pendente);
		$xadvertencia          = pg_result($xres,$x,advertencia);
		$xconfirmacao_pendente = pg_result($xres,$x,confirmacao_pendente);
		$xextrato              = pg_result($xres,$x,extrato);
		$xarquivo              = pg_result($xres,$x,arquivo);

if($xpendente=="t" and strlen($xconfirmacao_pendente)==0){$situacao = "Aguardando posto";}
if($xpendente=="f" and $xconfirmacao_pendente=="f"){$situacao = "Resolvido";}
if($xpendente=="t" and $xconfirmacao_pendente=="t"){$situacao = "Aguardando admin";}
if(strlen($xpendente)==0 and strlen($xconfirmacao_pendente)==0){$situacao = "Observação";}
//if($xpendente=="t" and $xconfirmacao_pendente=="t"){$situacao = "Aguardando admin";}
if($xadvertencia=='t'){ $tipo_interacao = "Alerta"; }else{ $tipo_interacao = "Pendência"; }
		$cor = "#d0e0f6"; 
		if ($x % 2 == 0) $cor = '#efeeea';

		echo "<tr bgcolor='$cor'>";
		echo "<td align='center'><font size='1'>$xdata<BR><B>";
		if($situacao=="Aguardando admin"){ 
			echo "<a href=\"javascript: if (confirm('Confirmo que recebi do PA a pendência faltante') == true) { window.location='$PHP_SELF?resolvido=$extrato'; }\">$situacao </a>";
		}else{
			echo "$situacao";
		}
		echo "</b></font><br><FONT COLOR='#868686'>$tipo_interacao</FONT></td>";
		echo "<td ><font size='2'>".nl2br($xobs)."</font></td>";

		echo "</tr>";

	}

?>

</table>
<table width='100%' border='0' cellspacing='1' cellpadding='5' style='font-family: verdana; font-size: 11px'  bgcolor='#596D9B'>
<tr>
	<td><font color='#FFFFFF'><b>Arquivo</b></FONT></td>
</tr>
<tr><?PHP
		$dir = "documentos/";
		$dh  = opendir($dir);
		while (false !== ($xarquivo = readdir($dh))) {
			if (strpos($xarquivo,"$xextrato") !== false){
		$po = strlen($extrato);
				if(substr($xarquivo, 0,$po)==$xextrato){
		echo "<td>";
		echo $extrato;
		echo $po;
		$teste = $xextrato-$xarquivo;
		echo $teste;
		echo "<!--ARQUIVO-I-->&nbsp;&nbsp;<a href=documentos/$xarquivo target='blank'><img src='documentos/clips.gif' border='0'>Baixar</a>&nbsp;&nbsp;<!--ARQUIVO-F-->";
		echo "</td>";
				}
			}
		}
		?>
</tr>
</table>
<? } ?>
<table width='100%' border='1' cellspacing='1' cellpadding='1' class='tabela'>

	<tr>
		<td width='100%' colspan="3" height="5"></td>
	</tr>
	<tr>
		<td width='25%' valign="top"><b>Obs.:</b></td>
		<td width='75%' colspan="2"><textarea name="obs" cols='80' rows='10' ><?echo $obs?></textarea></td>
	</tr>
	<tr>
		<td colspan='3' align='center' width='100%'>
			Arquivo
			<input type="file" name='arquivo' size='50'>
		</td>
	<tr>
</table>
<br>
<center>
<img border="0" src="imagens_admin/btn_confirmar.gif" style="cursor: hand;" onclick="javascript: if (document.frm_extrato.acao.value == '') { document.frm_extrato.acao.value='ALTERAR'; document.frm_extrato.submit(); }else{ alert('Aguarde Submissão...'); }" alt="Clique aqui para inserir a obs.">
</center>

</form>

</body>

</html>
