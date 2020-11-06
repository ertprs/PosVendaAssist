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
	$caminho = "/www/assist/www/admin/faturamento/entrada";

	// arquivo
	$arquivo = isset($_FILES["arquivo_importado"]) ? $_FILES["arquivo_importado"] : FALSE;

	if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none"){
		// deixar mensagem
		//echo "Importando arquivo [ ".$arquivo["name"]." ] !!!<br> Aguarde...<br><br>";
		flush();

		// Tamanho máximo do arquivo (em bytes) 
		$config["tamanho"] = 30004800;


		// Verifica o mime-type do arquivo
		if ($arquivo["type"] <> "text/plain") {
			$msg_erro = "Arquivo em formato inválido!";
		} else {
			// Verifica tamanho do arquivo 
			if ($arquivo["size"] > $config["tamanho"]) 
				$msg_erro = "Arquivo em tamanho muito grande! Deve ser de no máximo 200 KB.";
		}

		if (strlen($msg_erro) == 0) {

			// Faz o upload
			if (strlen($msg_erro) == 0) {
				$nome_arquivo = $caminho."/".$arquivo['name'];

				if (!copy($arquivo["tmp_name"], $nome_arquivo)) {
					$msg_erro = "Arquivo '".$arquivo['name']."' não foi enviado!!!";
				}
				// deixar mensagem
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
		system("/www/cgi-bin/blackedecker/importa-estoque.pl ".$arquivo['name'],$ret);
		if ($ret <> "0") {
			$msg_erro .= "Não foi possível fazer a importação dos arquivos de estoque ($ret). Tente novamente.";
			$remetente    = "Telecontrol <telecontrol@telecontrol.com.br>"; 
			$destinatario = "takashi@telecontrol.com.br"; 
			$assunto      = "Importação de Estoque com erro"; 
			$mensagem     = "Importação de estoque black&decker ocorreu erro, favor verificar arquivo tmp/blackedecker/importa-estoque.err<BR>
			Arquivo importado: /admin/faturamento/entrada/fatpecas.txt <BR>
			Arquivo bkp: /admin/faturamento/entrada/bkp/fatpecas.txt-data_hoje.txt <BR>
			Php executado : /admin/faturamento_importa_estoque.php<BR>
			Pl executado : /cgi-bin/blackedecker/importa-estoque.pl<BR>
			";
			$headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n"; 
			@mail($destinatario,$assunto,$mensagem,$headers);
			
		}
	}
	
	if (strlen($msg_erro) == 0) {
		echo "<p>Concluído</p>";
		$msg = "Arquivos importados com sucesso!!!";
	}else{
		echo "<h1>$msg_erro</h1>";
	}
}

$layout_menu = "callcenter";
$title = "Importação de Estoque";

include "cabecalho.php";

	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "<TR class='menu_top'>\n";
	echo "<TD bgcolor='#005EEA'>A partir do dia 08/09/2008 foi retirada essa rotina manual.</TD>\n";
	echo "</TR>\n";
	echo "<TR class='menu_top'>\n";
	echo "<TD bgcolor='#005EEA'>Dúvidas, entrar em contato com a Telecontrol.</TD>\n";
	echo "</TR>\n";
	echo "<TR class='menu_top'>\n";
	echo "<TD bgcolor='#005EEA'>Assunto Tratado no chamado: 38222.</TD>\n";
	echo "</TR>\n";

	echo "</TABLE>\n";


exit;

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
	$msg="";
}
if (strlen($msg) > 0) {
	echo "<TR class='menu_top'>\n";
	echo "<TD bgcolor='#005EEA'>$msg</TD>\n";
	echo "</TR>\n";
}
echo "<TR class='menu_top'>\n";
echo "<TD><font size='+1'>Envio de arquivos para atualização do ESTOQUE</font></TD>\n";
echo "</TR>\n";
echo "</table>\n";

echo "<center style='font-size:12px'><b>Importação do arquivo ( ex.:fat05122007.txt) de peças FATURADAS no dia anterior pela Black&Decker<BR> para alimentação de estoque dos postos autorizados.</b><br><br><b>IMPORTANTE: o arquivo não pode ultrapassar 200 kb de tamanho.<BR> Caso ultrapasse, entre em contato com o suporte Telecontrol</b><br><br><b style='color:red'>O arquivo será importado no momento do envio. Não importe duas vezes o mesmo arquivo!</b></center>";

echo "<FORM METHOD='POST' ACTION='$PHP_SELF' enctype='multipart/form-data'>";
echo "<input type='hidden' name='btn_acao' value=''>";
echo "ANEXAR ARQUIVO <input type='file' name='arquivo_importado' size='50'>";
echo "<p>";
echo "<img src=\"imagens/btn_gravar.gif\" onclick=\"javascript: if (document.forms[0].btn_acao.value == '' ) { document.forms[0].btn_acao.value='gravar'; document.forms[0].submit(); } else { alert ('Aguarde submissão') }\" ALT=\"Gravar formulário\" border='0' style=\"cursor:pointer;\">";
echo "</FORM>";
?>

<br>

<? include "rodape.php"; ?>
