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
	if (date("H")<8){
		

		//$caminho = "/www/cgi-bin/blackedecker/entrada";
		$caminho = "/www/assist/www/admin/faturamento/entrada";

		// arquivo
		$arquivo = isset($_FILES["arquivo_zip"]) ? $_FILES["arquivo_zip"] : FALSE;

		if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none"){
			// deixar mensagem
			//echo "Importando arquivo [ ".$arquivo["name"]." ] !!!<br> Aguarde...<br><br>";
			flush();

			// Tamanho máximo do arquivo (em bytes) 
			$config["tamanho"] = 2048000;


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
				exec("mv -f $caminho/*old $caminho/nao_bkp");
				exec("mv -f $caminho/*txt $caminho/nao_bkp");
				#exec("mv -f $caminho/*zip $caminho/bkp");
				//exec("mv $caminho/" . $arquivo["name"] . " $caminho/" . $arquivo["name"] . ".old");
				
				// Faz o upload
				if (strlen($msg_erro) == 0) {
					if (!copy($arquivo["tmp_name"], $caminho."/" . $arquivo["name"])) {
						$msg_erro = "Arquivo '".$arquivo['name']."' não foi enviado!!!";
					}
					// deixar mensagem

					//exec ("rm -f $caminho/*txt");
					exec ("cd $caminho/ ; unzip -o " . $arquivo['name']);

					$arq_data = date("Ymd-His");

					exec ("mv $caminho/*retsspop*      $caminho/retsspop.txt");
					exec ("mv $caminho/*retsspgar*     $caminho/retsspgar.txt");
					exec ("mv $caminho/*retsspace*     $caminho/retsspace.txt");
					exec ("mv $caminho/*retsspsedex*   $caminho/retsspsedex.txt");

					exec ("mv $caminho/*retsspnfop*    $caminho/retsspnfop.txt");
					exec ("mv $caminho/*retsspnfgar*   $caminho/retsspnfgar.txt");
					exec ("mv $caminho/*retsspnfsed*   $caminho/retsspnfsed.txt");
					exec ("mv $caminho/*retsspnface*   $caminho/retsspnface.txt");

					//echo "Arquivo [ ".$arquivo["name"]." ] importado com sucesso!!!<br><br>";
					exec ("mv -f $caminho/" . $arquivo["name"] . " $caminho/$arq_data-".$arquivo["name"]);
					exec ("mv -f $caminho/$arq_data-".$arquivo["name"]." $caminho/bkp");

	//				exec("mv -f $caminho/*.txt $caminho/entrada");

					$msg = "Arquivo importado com sucesso!";
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
			echo "<p>Executando a atualização dos arquivos de pendências...<br> Aguarde...<br><br></p>";
			system("/www/cgi-bin/blackedecker/importa-novas-pendencias_nf.pl",$ret);
			if ($ret <> "0") {
				$msg_erro .= "Não foi possível fazer a importação dos arquivos de pendência ($ret). Tente novamente.";
			}
		}
		
		if (strlen($msg_erro) == 0) {
			echo "<p>Concluído</p>";
			$msg = "Arquivos importados com sucesso!!!";
			#exec ("rm -f $caminho/*txt");
		}else{
			echo "<h1>$msg_erro</h1>";
		}
	}else{
		echo "<font color ='red'>Desculpe, mas expirou o prazo de envio do arquivo!!!</font>";
		exit;
	
	}


}

$layout_menu = "callcenter";
$title = "Importação de Pendências";

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
	#HD 16934
	echo "<p>Importação é feita automaticamente.</p>";
	include "rodape.php";
	exit;
?>
<?
echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
if (strlen($msg_erro) > 0) {
	echo "<TR class='menu_top'>\n";
	echo "<TD bgcolor='#ff0000'>$msg_erro</TD>\n";
	echo "</TR>\n";
	$msg="";
}
if (strlen($msg) > 0) {
	echo "<TR class='menu_top'>\n";
	echo "<TD bgcolor='#005EEA'>$msg</TD>\n";
	echo "</TR>\n";
}
echo "<TR class='menu_top'>\n";
echo "<TD><font size='+1'>Envio de arquivos para atualização</font></TD>\n";
echo "</TR>\n";
echo "</table>\n";

echo "<center style='font-size:12px'><b>Compacte seus arquivos no formato '.zip'. <br> Os arquivos devem conter em seu nome 'retsspop.txt' , 'retsspgar.txt', 'retsspace.txt.txt', 'retsspsedex.txt.txt', 'retsspnfop.txt', 'retsspnfgar.txt', 'retsspnface.txt' e 'retsspnfsed.txt' </b><br><br><b>IMPORTANTE: o arquivo não pode ultrapassar 2MB de tamanho. Caso ultrapasse, entre em contato com o suporte Telecontrol</b><br><br><b style='color:red'>O arquivo será importado no momento do envio. Não importe duas vezes o mesmo arquivo!</b></center>";

if (date("H")<8){
	echo "<FORM METHOD='POST' ACTION='$PHP_SELF' enctype='multipart/form-data'>";
	echo "<input type='hidden' name='btn_acao' value=''>";
	echo "ANEXAR ARQUIVO <input type='file' name='arquivo_zip' size='30'>";
	echo "<p>";
	echo "<img src=\"imagens/btn_gravar.gif\" onclick=\"javascript: if (document.forms[0].btn_acao.value == '' ) { document.forms[0].btn_acao.value='gravar'; document.forms[0].submit(); } else { alert ('Aguarde submissão') }\" ALT=\"Gravar formulário\" border='0' style=\"cursor:pointer;\">";
	echo "</FORM>";
}else{
	echo "<br><br><h1>Horário para importação: até as 08:00</h1>";
}
?>

<br>

<? include "rodape.php"; ?>
