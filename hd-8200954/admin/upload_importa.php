<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";
include "autentica_admin.php";

if($login_fabrica <> 20) {
	header("Location: menu_callcenter.php");
	exit;
}

$msg ="";

##### A T U A L I Z A R #####
if (strlen($_POST["btn_acao"]) > 0) {

	$caminho = "/www/cgi-bin/bosch/entrada";

	$arquivo = isset($_FILES["arquivo_zip"]) ? $_FILES["arquivo_zip"] : FALSE;

	if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none"){
		// deixar mensagem
		$msg .=  "Importando arquivo [ ".$arquivo["name"]." ] !!!<br> Aguarde...<br><br>";
		flush();

		$config["tamanho"] = 2048000;

		if ($arquivo["type"] <> "application/x-zip-compressed") {
		//	$msg_erro = "Arquivo em formato inválido!";
		} else {
			if ($arquivo["size"] > $config["tamanho"]) 
				$msg_erro = "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB.";
		}

		if (strlen($msg_erro) == 0) {
			// Faz o upload
			$nome_arquivo = $caminho."/IMPORTA_BOSCH_".date("Ymd")."_".$arquivo["name"];
			if (!copy($arquivo["tmp_name"], $nome_arquivo)) {
				$msg_erro = "Arquivo '".$arquivo['name']."' não foi enviado!!!";
			}
			//exec ("rm -f $caminho/IMPORTA_BOSCH*txt");
			exec ("cd $caminho/ ; unzip -o $nome_arquivo");

			exec ("mv $caminho/*pecas* $caminho/IMPORTA_BOSCH_pecas.txt");
			exec ("mv $caminho/*produtos* $caminho/IMPORTA_BOSCH_produtos.txt");
			exec ("mv $caminho/*lbm* $caminho/IMPORTA_BOSCH_lbm.txt");
			exec ("mv $caminho/*custo-tempo* $caminho/IMPORTA_BOSCH_custo-tempo.txt");

			$msg .=  "Arquivo ( ".$arquivo["name"]." ) importado com sucesso!!!<br><br>";

			//exec ("mv $caminho/" . $arquivo["name"] . " $caminho/nao_bkp");
		}
		flush();
	
		if (strlen($msg_erro) == 0 ) {
			$msg .= "Executando a atualização dos PRODUTOS!<br> Aguarde...<br><br>";
		}
		if (strlen($msg_erro) == 0 ) {
			$msg .=  "Executando a atualização das PEÇAS!<br> Aguarde...<br><br>";
		}
		if (strlen($msg_erro) == 0 ) {
			$msg .=  "Executando a atualização das Lista Básica de Materiais!<br> Aguarde...<br><br>";
		}
		if (strlen($msg_erro) == 0 ) {
			$msg .=  "Executando a atualização do CUSTO-TEMPO!<br> Aguarde...<br><br>";
		}
		if (strlen($msg_erro) == 0) {
			//exec ("rm -f $caminho/IMPORTA_BOSCH*txt");
		}else{
			$msg .=  "<h1>$msg_erro</h1>";
		}
	}
}

$layout_menu = "callcenter";
$title = "Importação BOSCH";

include "cabecalho.php";

?>

<style type="text/css">

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

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.formulario{
	background:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.espaco td{
	padding:10px 0 10px;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

</style>

<?
echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='0' class='formulario'>\n";
if (strlen($msg_erro) > 0) {
	echo "<TR class='msg_erro'>\n";
	echo "<TD>$msg_erro</TD>\n";
	echo "</TR>\n";
}
echo "<TR>\n";
echo "<TD class='titulo_tabela'>Envio de arquivos para atualização</TD>\n";
echo "</TR>\n";


echo "<tr><td><center style='font-size:12px'><br>Compacte seus arquivos no formato \".zip\". <br> Os arquivos devem conter em seu nome \"pecas.txt\" , \"produtos.txt\", \"lbm.txt\" e \"custo-tempo.txt\".</center></td></tr>";

echo "<FORM METHOD='POST' ACTION='$PHP_SELF' enctype='multipart/form-data'>";
echo "<input type='hidden' name='btn_acao' value=''>";
echo '<tr><td align="center">';
echo "<b>Anexar Arquivo </b><input type='file' name='arquivo_zip' size='30'>";
echo "<p>";
echo "</FORM>";
echo '</td></tr>';
echo '<tr><td align="center">';
echo "<input type='button' onclick=\"javascript: if (document.forms[0].btn_acao.value == '' ) { document.forms[0].btn_acao.value='gravar'; document.forms[0].submit(); } else { alert ('Aguarde submissão') }\" style=\"cursor:pointer;\" value='Gravar' />";

echo '</td></tr>';
echo '<tr><td>&nbsp;</td></tr>';
echo "</table>\n";
?>

<br>

<? include "rodape.php"; ?>
