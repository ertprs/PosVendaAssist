<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";
include "autentica_admin.php";

if($login_fabrica <> 1) {
	header("Location: menu_callcenter.php");
	exit;
}

##### A T U A L I Z A R #####
if (strlen($_POST["btn_acao"]) > 0) {

	//$caminho = "/www/cgi-bin/blackedecker/entrada";
	$caminho = "/www/assist/www/admin/faturamento";

	// arquivo
	$arquivo = isset($_FILES["arquivo_zip"]) ? $_FILES["arquivo_zip"] : FALSE;

	if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none"){
		// deixar mensagem
		echo "Importando arquivo [ ".$arquivo["name"]." ] !!!<br> Aguarde...<br><br>";
		flush();

		// Tamanho máximo do arquivo (em bytes) 
		$config["tamanho"] = 248000;


		// Verifica o mime-type do arquivo
		if ($arquivo["type"] <> "application/x-zip-compressed") {
			$msg_erro = "Arquivo em formato inválido!";
		} else {
			// Verifica tamanho do arquivo 
			if ($arquivo["size"] > $config["tamanho"]) 
				$msg_erro = "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB.";
		}

		if (strlen($msg_erro) == 0) {
#			// Pega extensão do arquivo
#			preg_match("/\.(txt){1}$/i", $arquivo["name"], $ext);
#			$aux_extensao = "'".$ext[1]."'";

			// Exclui anteriores, qquer extensao
			exec("mv $caminho/*old $caminho/nao_bkp");
			exec("mv $caminho/" . $arquivo["name"] . " $caminho/" . $arquivo["name"] . ".old");
			
			// Faz o upload
			if (strlen($msg_erro) == 0) {
				if (!copy($arquivo["tmp_name"], $caminho."/" . $arquivo["tmp_name"])) {
					$msg_erro = "Arquivo '".$arquivo['name']."' não foi enviado!!!";
				}
				// deixar mensagem

				exec ("rm -f $caminho/*txt");
				exec ("cd $caminho/ ; unzip -o " . $arquivo['name']);

				exec ("mv $caminho/*faturamento_item*    $caminho/faturamento_item_assist.txt");
				exec ("mv $caminho/*faturamento_assist*  $caminho/faturamento_assist.txt");
				exec ("mv $caminho/*pendencia*           $caminho/pendencia_assist.txt");

				echo "Arquivo [ ".$arquivo["name"]." ] importado com sucesso!!!<br><br>";


				exec ("mv $caminho/" .  $arquivo["tmp_name"] . " $caminho/nao_bkp");
				
			}
			
			if (strlen($msg_erro) == 0){
				$executa_1 = 't';
			}
		}
	}//else{
		//$msg_erro = "Selecione o arquivo '<B>faturamento_assist.txt</B>'.";
	//}
	
	flush();
	

	if (strlen($msg_erro) == 0 ) {
		echo "Executando a atualização dos arquivos de faturamento!!!<br> Aguarde...<br><br>";
		system("/www/cgi-bin/blackedecker/importa-faturamento.pl",$ret);
		if ($ret <> "0") {
			$msg_erro .= "Não foi possível fazer a importação dos arquivos de faturamento ($ret). Tente novamente.";
		}

		echo "Executando a atualização dos arquivos de pendências!!!<br> Aguarde...<br><br>";
		system("/www/cgi-bin/blackedecker/importa-pendencia.pl",$ret);
		if ($ret <> "0") {
			$msg_erro .= "Não foi possível fazer a importação dos arquivos de pendências ($ret). Tente novamente.";
		}
	}
	
	if (strlen($msg_erro) == 0) {
		$msg_erro = "Arquivos importados com sucesso!!!";
		exec ("rm -f $caminho/*txt");
	}else{
		echo "<h1>$msg_erro</h1>";
	}
}

$layout_menu = "callcenter";
$title = "Importação do faturamento";

include "cabecalho.php";

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

</style>

<?
echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
if (strlen($msg_erro) > 0) {
	echo "<TR class='menu_top'>\n";
	echo "<TD bgcolor='#ff0000'>$msg_erro</TD>\n";
	echo "</TR>\n";
}
echo "<TR class='menu_top'>\n";
echo "<TD><font size='+1'>Envio de arquivos para atualização</font></TD>\n";
echo "</TR>\n";
echo "</table>\n";

echo "<center style='font-size:12px'><b>Compacte seus arquivos no formato \".zip\". <br> Os arquivos devem conter em seu nome \"faturamento_assist\" , \"faturamento_item\" e \"pendencia\".</b></center>";

echo "<FORM METHOD='POST' ACTION='$PHP_SELF' enctype='multipart/form-data'>";
echo "<input type='hidden' name='btn_acao' value=''>";
echo "ANEXAR ARQUIVO <input type='file' name='arquivo_zip' size='30'>";
echo "<p>";
echo "<img src=\"imagens/btn_gravar.gif\" onclick=\"javascript: if (document.forms[0].btn_acao.value == '' ) { document.forms[0].btn_acao.value='gravar'; document.forms[0].submit(); } else { alert ('Aguarde submissão') }\" ALT=\"Gravar formulário\" border='0' style=\"cursor:pointer;\">";
echo "</FORM>";
?>

<br>

<? include "rodape.php"; ?>
