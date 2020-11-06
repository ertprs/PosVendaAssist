<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";
include "autentica_admin.php";


##### A T U A L I Z A R #####
if (strlen($_POST["btn_acao"]) > 0) {

	$caminho = "/tmp/".strtolower($login_fabrica_nome)."/importa-depara" ;
    system("mkdir -m 777 -p $caminho");
    system("mkdir -p {$caminho}/nao_bkp");
    $fabrica_nome = strtolower($login_fabrica_nome);

	$arquivo = isset($_FILES["arquivo_zip"]) ? $_FILES["arquivo_zip"] : FALSE;

	if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none"){
		flush();

		$config["tamanho"] = 2048000;

		if ($arquivo["type"] <> 'text/plain') {

			$msg_erro = "Arquivo em formato inválido!";
		} else {
			if ($arquivo["size"] > $config["tamanho"]) 
				$msg_erro = "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB.";
		}

		if (strlen($msg_erro) == 0) {

			exec("mv -f $caminho/*txt $caminho/nao_bkp");
			
			if (strlen($msg_erro) == 0) {
				if (!copy($arquivo["tmp_name"], $caminho."/telecontrol-depara.txt")) {
					$msg_erro = "Arquivo '".$arquivo['name']."' não foi enviado!!!";
				}
				$arq_data = date("Ymd-His");

				$res = pg_exec($con,"SELECT to_char(current_date,'MMYYYY'); ");
				$mes_ano = pg_result($res,0,0);
				$msg = "Arquivo importado com sucesso!";
            }
			if (strlen($msg_erro) == 0){
				$executa_1 = 't';
			}
		}
	}
	
	flush();
	
	if (strlen($msg_erro) == 0 ) {
		system("php ../rotinas/telecontrol/importa-depara.php $login_fabrica $fabrica_nome ",$ret);
		var_dump($ret);
		if ($ret <> "0") {
			$msg_erro .= "Não foi possível fazer a importação das tabelas de de-para ($ret). Verifique seu arquivo.";
		}
	}
	
	if (strlen($msg_erro) == 0) {
		$msg = "Arquivo importado com sucesso!";
	}else{
		echo "<h1></h1>";
	}
}

$layout_menu = "callcenter";
$title = "Importação de Peças de-para";

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
	$msg="";
}
if (strlen($msg) > 0) {
	echo "<TR class='menu_top'>\n";
	echo "<TD bgcolor='#005EEA'>$msg</TD>\n";
	echo "</TR>\n";
}
echo "<TR class='menu_top'>\n";
echo "<TD><font size='+1'>Importação da Peças De-para</font></TD>\n";
echo "</TR>\n";
echo "</table>\n <br /> ";


echo "
<strong>Layout do Arquivo</strong> <br />
Referência peça-de, Referência da Peça-para, Expira em, separados por TAB <br />
Formato: txt<br>
Exemplo: (51651asv &nbsp; &nbsp; 6511sss &nbsp; &nbsp; 01/05/2016 ) <br /> <br />
";


echo "<FORM METHOD='POST' ACTION='$PHP_SELF' enctype='multipart/form-data'>";
echo "<input type='hidden' name='btn_acao' value=''>";
echo "ANEXAR ARQUIVO <input type='file' name='arquivo_zip' size='30'>";
echo "<p>";
echo "<img src=\"imagens/btn_gravar.gif\" onclick=\"javascript: if (document.forms[0].btn_acao.value == '' ) { document.forms[0].btn_acao.value='gravar'; document.forms[0].submit(); } else { alert ('Aguarde submissão') }\" ALT=\"Gravar formulário\" border='0' style=\"cursor:pointer;\">";
echo "</FORM>";
?>

<br>

<? include "rodape.php"; ?>
