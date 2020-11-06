<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

$msg_erro = "";

$layout_menu = "callcenter";
$title       = "UPLOADE DE ARQUIVO DE VENDA";
include "cabecalho.php";

?>


<style>
.Titulo {
	text-align: center;
	font-family: Verdana;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}

.Conteudo{
	font-family: Verdana;
	font-size: 10px;
	color: #333333;
}
.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; 
	background-color: #990000;
}

#Propaganda{
	text-align: justify;
}

</style>

<?
$importa = $_POST["importar"];
if(strlen($importa) > 0){
	$caminho = "/www/cgi-bin/salton/entrada/";
	$nome_arquivo = "venda_fabrica";

	$arquivo       = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

	if(strlen($arquivo["tmp_name"])==0) $msg_erro = "Selecione um arquivo";

	if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none"){
		$config["tamanho"] = 2048000;
		if ($arquivo["size"] > $config["tamanho"]) 
			$msg_erro = "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB.";

		if (strlen($msg_erro) == 0) {
			system ("rm -f $nome_arquivo");

			$dat = date("yyyy-mm-dd");
			$nome_arquivo_aux= $nome_arquivo;
			$nome_arquivo = $caminho.$nome_arquivo.".txt";
			if (!copy($arquivo["tmp_name"], $nome_arquivo)) {
				$msg_erro .= "Arquivo '".$arquivo['name']."' não foi enviado!!!";
			}else{
				system("/www/cgi-bin/salton/importa-venda-fabrica.pl",$ret);
			}
		}
	}
}


echo "<table style=' border:#485989 1px solid; background-color: #F0F4FF' align='center' width='750' border='0' cellspacing='0'>\n";

echo "<tr height='20' bgcolor='#BCCBE0'>\n";
echo "<td align='left' colspan='4'><b>UPLOAD DE ARQUIVO DE VENDA</b>&nbsp;</td>\n";
echo "</tr>\n";

echo "<tr><td colspan='4'>\n";
?>
<br>
	

		<div  class='Erro'><?=$msg_erro?></div>
		<div id='Propaganda'>
		O layout para UPLOAD de ARQUIVO DE VENDA deve conter apenas as seguintes <b>colunas</b>:<br>
		- REFERÊNCIA do Produto da <b>fábrica</b>;<br>
		- MÊS(2 Dígitos. Ex: <?=date('m')?>);<br>
		- ANO(4 Dígitos. Ex: <?=date('Y')?>);<br>
		- QUANTIDADE DA VENDA;<br>
		Este arquivo poderá ser preenchido no excel. Depois salvar como, escolher o tipo de arquivo (Salvar como tipo) : Escolher Texto em UTF-8 (*.txt) separado por TAB(/t)<br>
		OBS.: Neste arquivo tem que conter somente as informções(REFERÊNCIA, MÊS, ANO E QUANTIDADE DA VENDA), cabeçalhos ou outras informações deverão ser EXCLUÍDAS antes de salvar como txt.<br>
		<br><br>

		</div>
		<form name="frm_upload" method="post" action="<? echo "$PHP_SELF#auto" ?>" enctype='multipart/form-data'>
		<table class='Conteudo'>
			<tr>
				<td align='right' ><b>Arquivo</b>&nbsp;</td>
				<td align='left' colspan='3'><b><input type='file' name='arquivo' size='30' class='Caixa'></td>
			</tr>
		</table>

		<table style=' border:#B63434 1px solid; background-color: #EED5D2' align='center' width='100%' border='0'height='40'>
			<tr>
				<td width='50' valign='middle'  align='LEFT' colspan='4'><input type='submit' name='importar' value='Importar'></td>
				<td ><div id='saida' style='display:inline;'><?=$msg_erro?><?=$msg?></div></td>
			</tr>
		</table>
		</form>
</td></tr></table>
<br clear=both>

<? include "rodape.php"; ?>
