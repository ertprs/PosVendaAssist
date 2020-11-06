<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";
include "autentica_admin.php";
#####ESTE PROGRAMA IMPORTA O RECIBO DO PEDIDO, ATUALIZA O FATURAMENTO E ATUALIZA O ESTOQUE ######

$layout_menu = "callcenter";
$title = "IMPORTAÇÃO DE FATURAMENTO DE PEÇAS";

include "cabecalho.php";

##### A T U A L I Z A R #####
if (strlen($_POST["btn_acao"]) > 0 AND $_FILES["arquivo_zip"]["tmp_name"] != NULL) {

	//$caminho = "/www/cgi-bin/blackedecker/entrada";
	$caminho = "/www/assist/www/admin/faturamento/suggar/entrada";

	// arquivo
	$arquivo = isset($_FILES["arquivo_zip"]) ? $_FILES["arquivo_zip"] : FALSE;

	if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none"){
		// deixar mensagem
		//echo "Importando arquivo [ ".$arquivo["name"]." ] !!!<br> Aguarde...<br><br>";
		flush();

		// Tamanho máximo do arquivo (em bytes)
		$config["tamanho"] = 4096000;
		$extensao = explode(".", $arquivo["name"]);
		$extensao = strtolower($extensao[count($extensao)-1]);

		// Verifica o mime-type do arquivo
		if (($arquivo["type"] == "application/x-zip-compressed") || ($arquivo["type"] == "application/zip") || ($arquivo["type"] == "application/octet-stream" && $extensao == "zip")) {
			// Verifica tamanho do arquivo
			if ($arquivo["size"] > $config["tamanho"])
				$msg_erro = "Arquivo em tamanho muito grande! Deve ser de no máximo 4MB";
		} else {
			$msg_erro = "Arquivo em formato inválido! Dados técnicos: tipo = " . $arquivo["type"] . " - extensão = $extensao";
		}

		if (strlen($msg_erro) == 0) {

			// Exclui anteriores, qquer extensao
			exec("mv -f $caminho/*old $caminho/nao_bkp");
			exec("mv -f $caminho/*txt $caminho/nao_bkp");
			exec("mv -f $caminho/*TXT $caminho/nao_bkp");

			$arq_data = date("Ymd-His");

			// Faz o upload
			if (strlen($msg_erro) == 0) {
				if (!copy($arquivo["tmp_name"], $caminho."/Faturamento-$arq_data.zip")) {
					$msg_erro = "Arquivo '".$arquivo['name']."' não foi enviado!!!";
				}

				exec ("cd $caminho/ ; unzip -o Faturamento-$arq_data.zip");
				exec ("mv $caminho/*telecontrol-pedido*          $caminho/telecontrol-pedido.TXT");
				exec ("mv $caminho/*telecontrol-embarque*        $caminho/telecontrol-embarque.TXT");
				exec ("mv $caminho/*telecontrol-nf-itens*        $caminho/telecontrol-nf-itens.TXT");
				exec ("mv $caminho/*telecontrol-nf*              $caminho/telecontrol-nf.TXT");
				exec ("mv $caminho/*telecontrol-nf-canceladas*   $caminho/telecontrol-nf-canceladas.TXT");
				exec ("mv -f $caminho/" . $arquivo["name"] . " $caminho/$arq_data-".$arquivo["name"]);
			}
			if (strlen($msg_erro) == 0){
				$executa_1 = 't';
			}
		}
	}

	flush();

	if (strlen($msg_erro) == 0 ) {
		@system("/www/cgi-bin/suggar/importa-peca.pl"); /*importa-peca.pl seria importa recibo, porem nao consegui criar arquivo com esse nome*/
		@system("/www/cgi-bin/suggar/importa-faturamento.pl",$ret);
//		echo $ret;
		if ($ret <> "0") {
			$msg_erro .= "Faturamento não importado!";
			$remetente    = "Telecontrol <telecontrol@telecontrol.com.br>";
			$destinatario = "helpdesk@telecontrol.com.br,paulo@telecontrol.com.br";
			$assunto      = "FATURAMENTO SUGGAR - ERRO";
			$mensagem     = "Erro ao atualizar faturamento da SUGGAR.<BR> Arquivo inserido $arq_data por $login_login.<BR><BR>Verificar admin/upload_importa_suggar.php ";
			$headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
			mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
			$msg_erro .= "Não foi possível fazer a importação dos arquivos de faturamento. Tente novamente.";
		}else{
			$msg .= "Faturamento importado com sucesso!";
			$remetente    = "Telecontrol <telecontrol@telecontrol.com.br>";
			$destinatario = "takashi@telecontrol.com.br";
			$assunto      = "ATUALIZAR FATURAMENTO SUGGAR";
			$mensagem     = "TAKASHI,<BR> Atualizar faturamento da SUGGAR.<BR> Arquivo inserido $arq_data por $login_login.<BR><BR>";
			$headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
			//hd 44542 - retirei pois não encontrei motivo para disparar este email
			//mail($destinatario,$assunto,$mensagem,$headers);


			$remetente    = "Telecontrol <telecontrol@telecontrol.com.br>";
			$destinatario = "marilene@suggar.com.br";
			$assunto      = "Faturamento importado com sucesso!";
			$mensagem     = "$login_login,<BR> Faturamento importado com sucesso!";
			$headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
			mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);

			system("/www/cgi-bin/suggar/importa-estoque.pl");
//		echo "enviou";
		}

	}

}elseif (strlen($_POST["btn_acao"]) > 0)
    $msg_erro = "Arquivo inválido!";

?>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

.table_line2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>

<?
echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
if (strlen($msg_erro) > 0) {
	echo "<TR class='msg_erro'>\n";
	echo "<TD bgcolor='#ff0000'>$msg_erro</TD>\n";
	echo "</TR>\n";
}
if (strlen($msg) > 0) {
	echo "<TR class='texto_avulso'>\n";
	echo "<TD bgcolor='#005EEA'>$msg</TD>\n";
	echo "</TR>\n";
}
echo "<TR class='titulo_tabela'>\n";
echo "<TD><font size='+1'>Envio de arquivos para atualização</font></TD>\n";
echo "</TR>";
echo "</table>";

echo "<FORM METHOD='POST' ACTION='$PHP_SELF' enctype='multipart/form-data'>";
echo "<table width='700px' align='center' class='formulario' cellpadding='1'>";
	echo "<tr>";
		echo "<td align='center' style='padding: 10px'>";
			echo "<input type='hidden' name='btn_acao' value=''>";
			echo "ANEXAR ARQUIVO <input type='file' name='arquivo_zip' size='30'>";
		echo "</td>";
	echo "</tr>";
	
	echo "<tr><td>&nbsp;</td></tr>";
	
	echo "<tr>";
		echo "<td align='center'>";
		echo "<input type='button' value='Gravar' onclick=\"javascript: if (document.forms[0].btn_acao.value == '' ) { document.forms[0].btn_acao.value='gravar'; document.forms[0].submit(); } else { alert ('Aguarde submissão') }\" ALT=\"Gravar formulário\" border='0' style=\"cursor:pointer;\">";	
		echo "</td>";
	echo "</tr>";
echo "</table>";

echo "</FORM>";
?>

<br>

<? include "rodape.php"; ?>
