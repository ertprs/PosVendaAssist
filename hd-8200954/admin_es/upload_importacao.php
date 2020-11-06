<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="gerencia";
include "autentica_admin.php";

if($login_fabrica <> 20) {
	header("Location: menu_callcenter.php");
	exit;
}


$layout_menu = "entradas";
$title = "Importación BOSCH";

include "cabecalho.php";
?>

<p>

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
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

</style>

<?
echo $msg;


$msg ="";

##### A T U A L I Z A R #####
if (strlen($_POST["btn_acao"]) > 0) {
	echo "<TABLE  border='0' cellpadding='0' cellspacing='0' style='border-style: solid; border-color: #6699CC; border-width:1px;'  align='center' width='750' >\n";

	echo "<TR class='Titulo' bgcolor=''>\n";
	echo "<TD colspan='2' background='../admin/imagens_admin/azul.gif'><font size='3'><b>La ejecución del</b></font></TD>\n";
	echo "</TR>\n";
	echo "<TR >\n";
	echo "<TD colspan='2' >";

	$tipo = $_POST["tipo"];

	$caminho = "/tmp/bosch/";

	if(strlen($tipo)==0) $msg_erro = "Seleccione el tipo de archivo";

	if($tipo == 'lbm')			 $nome_arquivo = "lbm";
	if($tipo == 'peca')			 $nome_arquivo = "peca";
	if($tipo == 'peca-al')		 $nome_arquivo = "peca-al";
	if($tipo == 'preco')		 $nome_arquivo = "preco";
	if($tipo == 'peca-preco-al') $nome_arquivo = "peca-preco-al";
	if($tipo == 'custo-tempo')	 $nome_arquivo = "custo-tempo";
	if($tipo == 'produto')		 $nome_arquivo = "produto";
	if($tipo == 'produto-pais')	 $nome_arquivo = "produto-pais";

	$arquivo = isset($_FILES["arquivo_zip"]) ? $_FILES["arquivo_zip"] : FALSE;

	if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none" AND strlen($msg_erro)==0){

		$msg .=  "<br>Importando arquivo [ ".$arquivo["name"]." ] !!!<br> esperar...<br>";
		flush();

		$config["tamanho"] = 2048000;

		if ($arquivo["size"] > $config["tamanho"]) 
			$msg_erro = "Archivo muy grande el tamaño! Debe ser un máximo de 2 MB.";

		if (strlen($msg_erro) == 0) {
			system ("rm -f $nome_arquivo");
			$msg .= "Guardar el nuevo archivo<br>";

			$dat = date("yyyy-mm-dd");
			$nome_arquivo_aux= $nome_arquivo;
			$nome_arquivo = $caminho.$nome_arquivo.".txt";

			echo $msg;

			if (!copy($arquivo["tmp_name"], $nome_arquivo)) {
				$msg_erro = "Arquivo '".$arquivo['name']."' no se envía!!!";
			}else{
				if($tipo == "lbm"){
					system("php ../rotinas/bosch/importa-lbm.php $login_admin",$ret);
				}elseif($tipo == "peca"){
					system("php ../rotinas/bosch/importa-peca-br.php $login_admin",$ret);
				}elseif($tipo == "peca-al"){
					system("php ../rotinas/bosch/importa-peca-al.php $login_admin",$ret);
				}elseif($tipo == "preco"){
					system("php ../rotinas/bosch/importa-preco.php $login_admin",$ret);
				}elseif($tipo == "peca-preco-al"){
					system("php ../rotinas/bosch/importa-peca-preco-pais.php $login_admin $login_pais",$ret);
				}elseif($tipo == "custo-tempo"){
					system("php ../rotinas/bosch/importa-custo-tempo.php $login_admin",$ret);
				}elseif($tipo == "produto"){
					system("php ../rotinas/bosch/importa-produto-novo.php $login_admin",$ret);
				}elseif($tipo == "produto-pais"){
					system("php ../rotinas/bosch/atribui-produto-pais.php $login_admin",$ret);
				}
			}
		}
	}
	echo "</TD>";
	echo "</TR>\n";
}

echo "<TABLE  border='0' cellpadding='0' cellspacing='1' class='formulario' align='center' width='700' >\n";

echo "<FORM METHOD='POST' ACTION='$PHP_SELF' enctype='multipart/form-data'>";
echo "<input type='hidden' name='btn_acao' value=''>";

echo "<TR>\n";
echo "<TD colspan='2' class='titulo_tabela'>Envío de archivos de actualización</TD>\n";
echo "</TR>\n";


echo "<TR>\n";
echo "<TD colspan='1' align='left'><input type='radio' checked name='tipo' value='peca-preco-al' ";if($tipo=='peca-preco-al') echo "CHECKED "; echo "> Importar precios de piezas de Latinoamérica.</TD>\n";
echo "</TR>\n";


echo '<tr><td>&nbsp;</td></tr>';
echo "<tr>";
echo "<td align='left' colspan='2'> &nbsp;Adjuntar archivo <input type='file' name='arquivo_zip' size='30'></td>";
echo "</tr>";
echo '<tr><td>&nbsp;</td></tr>';
echo "<tr>";
echo "<td align='center' colspan='2'>";
echo "<input type='button' onclick=\"javascript: if (document.forms[0].btn_acao.value == '' ) { document.forms[0].btn_acao.value='Registro'; document.forms[0].submit(); } else { alert ('Aguarde submissão') }\" ALT=\"Formulario de inscripción\" value='Registro'>";
echo " <font color='#FF0000'>$msg_erro</font></td>";
echo "</tr>";
echo "</FORM>";
echo '<tr><td>&nbsp;</td></tr>';
echo "</table>\n";

echo "</br><br />\n";

echo "<TABLE  border='0' cellpadding='0' cellspacing='1' class='tabela' align='center' width='700' >\n";
echo "<TR>\n";
echo "<TD colspan='2' class='titulo_tabela' style='font-size:13px;'>Subir archivo txt con los datos 'REFERENCIA' 'PRECIO' 'PAIS' separados por TAB</TD>\n";
echo "</TR>\n";
echo "</TABLE>\n";


echo "<TABLE  border='0' cellpadding='0' cellspacing='1' class='tabela' align='center' width='700' >\n";
echo "<TR>\n";
echo "<TD colspan='2' class='titulo_tabela'>Ejemplos de diseño</TD>\n";
echo "</TABLE>";
echo "<TABLE  border='0' cellpadding='0' cellspacing='1' class='tabela' align='center' width='700' >\n";
echo "<TR bgcolor='#F7F5F0'>\n";
echo "<TD colspan='1' align='left' nowrap><b>REFERENCIA</b></TD>\n";
echo "<TD colspan='1' align='left'  nowrap><b>PRECIO</b></TD>\n";
echo "<TD colspan='1' align='left'  nowrap><b>PAIS (Sigla)</b></TD>\n";
echo "</TR>\n";


echo "<TD colspan='1' align='left' nowrap><b>xxxxxxxxxx</b></TD>\n";
echo "<TD colspan='1' align='left'  nowrap><b>XX.XX</b></TD>\n";
echo "<TD colspan='1' align='left'  nowrap><b>XX</b></TD>\n";
echo "</TR>\n";

echo "<TD colspan='1' align='left' nowrap><b>xxxxxxxxxx</b></TD>\n";
echo "<TD colspan='1' align='left'  nowrap><b>XX.XX</b></TD>\n";
echo "<TD colspan='1' align='left'  nowrap><b>XX</b></TD>\n";
echo "</TR>\n";

echo "<TD colspan='1' align='left' nowrap><b>xxxxxxxxxx</b></TD>\n";
echo "<TD colspan='1' align='left'  nowrap><b>XX.XX</b></TD>\n";
echo "<TD colspan='1' align='left'  nowrap><b>XX</b></TD>\n";
echo "</TR>\n";

echo "</TR>\n";

echo "</TABLE>\n";
?>


<? include "rodape.php"; ?>
