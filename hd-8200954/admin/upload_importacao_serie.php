<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include "autentica_admin.php";

if(!in_array($login_fabrica, array(95,108,111,120,146,149,150,154,156,165,167,203))) {
	header("Location: menu_cadastro.php");
	exit;
}
$layout_menu = "cadastro";
$title = "Upload de N�mero de S�rie";

include "cabecalho_new.php";
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
echo $msg;

$layout[95] = array('S�rie','S�rie Pe�a','Refer�ncia Produto','Refer�ncia Pe�a', 'Data Fabrica��o','Quantidade');
$layout[108] = array('S�rie','Refer�ncia Produto','Data Fabrica��o');
$layout[111] = array('S�rie','Refer�ncia Produto','Data Fabrica��o');
$layout[120] = array('S�rie','Refer�ncia Produto','Data Fabrica��o');
$layout[146] = array('S�rie','Refer�ncia Produto','Data Fabrica��o');
$layout[150] = array('S�rie In�cio', 'S�rie Fim', 'N�mero Lote', 'Refer�ncia Produto', 'Data Fabrica��o.' );
$layout[154] = array('S�rie', 'Refer�ncia Produto', 'CNPJ' );
$layout[165] = array('S�rie', 'Refer�ncia Produto', 'Data Fabrica��o (FORMATO AAAA-MM-DD)' );
$layout[167] = array('S�rie','Refer�ncia Produto','Data Venda (FORMATO DD/MM/AAAA)');
$layout[203] = array('S�rie','Refer�ncia Produto','Data Venda (FORMATO DD/MM/AAAA)');
$msg ="";

##### A T U A L I Z A R #####
if (strlen($_POST["btn_acao"]) > 0) {

?>
	<table  border='0' cellpadding='0' cellspacing='0' style='border-style: solid; border-color: #6699CC; border-width:1px;'  align='center' width='750' class='table_msg'>
		<tr class='Titulo' bgcolor=''>
			<td colspan='2' background='../admin/imagens_admin/azul.gif'>
				<font size='3'><b>Execu��o do arquivo</b></font>
			</td>
		</tr>
		<tr>
			<td colspan='2'>
<?php
	$sql = "SELECT lower(nome) FROM tbl_fabrica WHERE fabrica = {$login_fabrica}";
	$res = pg_query($con,$sql);

	$nome_fabrica = pg_fetch_result($res,0,0);
	$nome_fabrica = str_replace(" ", "",$nome_fabrica);

	if(in_array($login_fabrica, array(154,165,167,203))){
		if(!file_exists("/tmp/$nome_fabrica/")){
			system("mkdir /tmp/$nome_fabrica/ 2> /dev/null ; chmod 777 /tmp/$nome_fabrica/");
		}
		$caminho = "/tmp/$nome_fabrica/";

	}else{
		$caminho = "/var/www/cgi-bin/$nome_fabrica/entrada/";
	}

	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
	if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none" AND strlen($msg_erro)==0){
		// deixar mensagem
		$msg .=  "<div class='mensagem'>Importando arquivo [ ".$arquivo["name"]." ] !!!<br> Aguarde...<br><br></div>";
		flush();

		$nome_arquivo     = "num_serie";
		$nome_arquivo_aux = $nome_arquivo;
		$nome_arquivo     = $caminho.$nome_arquivo.".txt";

		$config["tamanho"] = 2048000;

		if ($arquivo["size"] > $config["tamanho"]) {
			$msg_erro = "Arquivo em tamanho muito grande! Deve ser de no m�ximo 2MB.";
		}

		if (strlen($msg_erro) == 0) {
			system ("rm -f $nome_arquivo");
			echo ("\n Salvando o arquivo novo\n");
// echo $nome_arquivo;exit;
			if (!copy($arquivo["tmp_name"], $nome_arquivo)) {
				$msg_erro = "Arquivo '".$arquivo['name']."' n�o foi enviado!!!";
			}else{

				if(in_array($login_fabrica,array(150))){
					$msg_erro = system("php ../rotinas/$nome_fabrica/importa_numero_serie.php",$ret);

				}else if(in_array($login_fabrica, array(154,165,167,203))){
					$msg_erro = system("php ../rotinas/$nome_fabrica/importa-numero-serie.php",$ret);
// 					echo ">>".$ret;exit;
				}else{
					$msg_erro = system("/www/cgi-bin/$nome_fabrica/importa-numero-serie.pl",$ret);
				}

				$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

				if (!empty($msg_erro)) {
					$msg_erro .= "Erro no formato do arquivo, por favor, verifique.<br />Arquivo n�o importado";
				} else {
					$msg .=  "Arquivo ( ".$arquivo["name"]." ) importado com sucesso!!!<br><br>";
				}
			}
		}
	} ?>
			</td>
		</tr>
	</table>
<? }

$arq_erro = "/tmp/$nome_fabrica/imp_numero_serie.err";
if(empty($msg_erro) and file_exists($arq_erro) and filesize($arq_erro) > 0) {
	$abrir = fopen($arq_erro, "r");
	$msg_erro = fread($abrir, filesize($arq_erro));
	fclose($abrir);
}

#echo $msg;

if(strlen(trim($msg)) > 0){
	echo $msg;
}

if(strlen(trim($msg_erro)) > 0){
	echo "<div style='color:red;font-size:15px'>$msg_erro</div>";
}


if (strlen($_POST["btn_acao"]) > 0) {
	$sql = "
		SELECT
			serie,
			referencia_produto,
			TO_CHAR(data_fabricacao,'DD/MM/YYYY') AS data_fabricacao,
			serie_peca,
			referencia_peca
		FROM tbl_numero_serie
		JOIN tbl_numero_serie_peca USING(numero_serie)
		WHERE tbl_numero_serie.fabrica = {$login_fabrica}
		AND tbl_numero_serie.data_carga::DATE = CURRENT_DATE;
	";

	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0 && pg_num_rows($res) <= 100) {	?>
		<br />
		<table align='center' width='700' cellspacing='1' class='tabela'>
			<caption>S�ries importados</caption>
			<tr class='titulo_coluna'>
				<td>S�rie</td>
				<td>Produto</td>
				<td>S�rie Pe�a</td>
				<td>Pe�a</td>
				<td>Data Fabrica��o</td>
			</tr>
		<?php
		$count = pg_num_rows($res);
		for($i=0; $i<$count; $i++){
			$cor = ($i % 2) ? '#F7F5F0' : '#F1F4FA';
		?>
			<tr bgcolor='$cor'>
				<td><?=pg_fetch_result($res,$i,'serie')?></td>
				<td><?=pg_fetch_result($res,$i,'referencia_produto')?></td>
				<td><?=pg_fetch_result($res,$i,'serie_peca')?></td>
				<td><?=pg_fetch_result($res,$i,'referencia_peca')?></td>
				<td><?=pg_fetch_result($res,$i,'data_fabricacao')?></td>
			</tr>
		<?php
		}
		?>
		</table>
		<br />
	<?php
	}
}

if(in_array($login_fabrica, [167, 203])){
	$titulo_texto = " Anexar um arquivo de no m�ximo 2MB no formato txt separado por ( ; Ponto e virgula) com seguinte Layout: ";
}else{
	$titulo_texto = " Anexar um arquivo de no m�ximo 2MB no formato txt separado por Tab com seguinte Layout: ";
}
?>
<div class="texto_avulso">
	<?php echo $titulo_texto; echo implode("; ",$layout[$login_fabrica]); ?>.
</div>
<br />
<form method="POST" action="<?=$PHP_SELF?>" enctype="multipart/form-data">
	<input type='hidden' name='btn_acao' value=''>
	<table  border='0' cellpadding='0' cellspacing='1' class='formulario' align='center' width='700' >
		<tr>
			<td colspan='2' align='center' class='titulo_tabela'>Envio de Arquivo para Atualiza��o</td>
		</tr>
		<tr>
			<td align='center' colspan='2'>&nbsp;</td>
		</tr>
		<tr>
			<td align='center' colspan='2'> &nbsp;ANEXAR ARQUIVO <input type='file' name='arquivo' size='30'></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align='center' colspan='2'>
				<input type='button' onclick="javascript: if (document.forms[0].btn_acao.value == '' ) { document.forms[0].btn_acao.value='gravar'; document.forms[0].submit(); } else { alert ('Aguarde submiss�o'); }" alt="Gravar Formulario" value='Importar'>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
	</table>
</form>

<script>
function escondeMensagem(){
	$('div.mensagem').hide();
	$('table.table_msg').hide();
}
//window.setTimeout('escondeMensagem()', 4000);

</script>
<? include "rodape.php"; ?>
