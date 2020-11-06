<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include "autentica_admin.php";

$msg ="";

$btn_importar = trim (strtolower ($_POST['btn_importar']));

if (trim($btn_importar) == "importar") { # HD 185184
	$arquivo                = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
	$produto                = $_POST['produto_excel'];
	if (strlen ($msg_erro) == 0) {
		$config["tamanho"] = 2048000; // Tamanho máximo do arquivo (em bytes)
		if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){
			preg_match("/\.(xls){1}$/i", $arquivo["name"], $ext);
		
			if ($ext[1] <>'xls'){
				$msg_erro = "Arquivo em formato inválido!";
			} else { // Verifica tamanho do arquivo
				if ($arquivo["size"] > $config["tamanho"])
					$msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
			}
			if (strlen($msg_erro) == 0) {
				// Pega extensão do arquivo
				preg_match("/\.(xls){1}$/i", $arquivo["name"], $ext);
				$aux_extensao = "'".$ext[1]."'";

				$nome_sem_espaco = implode("", explode(" ",$arquivo["name"]));

				$nome_anexo = "/var/www/assist/www/admin/xls/tabela_preco.xls";

				if (strlen($msg_erro) == 0) {
					if (copy($arquivo["tmp_name"], $nome_anexo)) {
						require_once 'xls_reader.php';
						$data = new Spreadsheet_Excel_Reader();
						$data->setOutputEncoding('CP1251');
						$data->read('xls/tabela_preco.xls');
						$res = pg_query ($con,"BEGIN TRANSACTION");


							for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
									$sigla_tabela = "";
									$peca   = "";
									$preco   = "";
								for ($j = 1; $j <= $data->sheets[0]['numCols']; $j++) {
									if($data->sheets[0]['numCols'] <> 3) {
									//	$msg_erro .= "Por favor, verificar o conteúdo de Excel, está faltando algumas colunas";
									}

									if(!empty($data->sheets[0]['cells'][$i][$j])){
									switch($j) {
										case 1:$sigla_tabela = str_replace (".","",$data->sheets[0]['cells'][$i][$j]);
											$sql = "SELECT tabela
													FROM tbl_tabela
													WHERE sigla_tabela = '$sigla_tabela'
													AND   fabrica = $login_fabrica ";
											$res = pg_query($con,$sql);
											if(pg_num_rows($res) > 0){
												$tabela = pg_fetch_result($res,0,0);
											}else{
												$msg_erro .= "Tabela $sigla_tabela não encontrada no sistema<br/>";
												continue;
											}
											break;
										case 2:
											$referencia_peca = str_replace (".","",$data->sheets[0]['cells'][$i][$j]);
											$referencia_peca = str_replace ("-","",$referencia_peca);
											$referencia = str_replace ("/","",$referencia_peca);
											$referencia_peca = str_replace (" ","",$referencia_peca);
											$referencia_peca = trim($referencia_peca);
											$sql = " SELECT peca 
													FROM tbl_peca
													WHERE fabrica = $login_fabrica
													AND   (tbl_peca.referencia_pesquisa =  '$referencia_peca' or tbl_peca.referencia ='$referencia_peca'); ";
											$res = @pg_query($con,$sql);
											if(@pg_num_rows($res) > 0){
												$peca = @pg_fetch_result($res,0,0);
											}else{
												$msg_erro .= "Peça ".$data->sheets[0]['cells'][$i][$j]." não encontrada no sistema<br>";
												continue;
											}
											break;
										case 3: $preco = $data->sheets[0]['cells'][$i][$j];
										$preco = str_replace(",",".",$preco);
										break;
									}
									}
								}
								if(strlen($msg_erro) == 0 and strlen($peca) > 0 and strlen($tabela) > 0 and !empty($preco)) {
									$sql = " SELECT peca
											FROM tbl_tabela_item
											WHERE tabela = $tabela
											AND   peca = $peca";
									$res = pg_query($con,$sql);
									if(pg_num_rows($res) > 0){
										$sqlx = " UPDATE tbl_tabela_item 
													SET preco = '$preco'
												WHERE tabela = $tabela 
												AND   peca = $peca";
									}else{
										$sqlx = "INSERT INTO tbl_tabela_item (
													tabela         ,
													peca           ,
													preco          
												) VALUES (
													$tabela        ,
													$peca          ,
													$preco         
										);";
									}
									$resx = @pg_query ($con,$sqlx);
									$msg_erro .= pg_errormessage($con);
								}
							}
						
							if(strlen($msg_erro) == 0) {
								$res = pg_query ($con,"COMMIT TRANSACTION");
								header ("Location: $PHP_SELF?msg=Atualizado com sucesso");
								exit;
							}else{
								$res = pg_query ($con,"ROLLBACK TRANSACTION");
							}
						}
						
						
					}else{
						$msg_erro = "Arquivo não foi enviado!!! Tente outra vez";
					}
				}
			}
		}
	}


$layout_menu = "cadastro";
$title = "Upload de arquivo de preço";

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
.aquivos_aceitos{
    color:#FF0000;
    font: 11px "Arial";
}

</style>

<?
echo "<FORM METHOD='POST' ACTION='$PHP_SELF' enctype='multipart/form-data'>";
echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='0' class='formulario'>\n";
if(isset($_GET['msg'])) {
	echo "<TR class='sucesso'>\n";
	echo "<TD>",$_GET['msg'],"</TD>\n";
	echo "</TR>\n";
}
if (strlen($msg_erro) > 0) {
	echo "<TR class='msg_erro'>\n";
	echo "<TD>$msg_erro</TD>\n";
	echo "</TR>\n";
}
echo "<TR>\n";
echo "<TD class='titulo_tabela'>Upload de arquivo para atualização</TD>\n";
echo "</TR>\n";
echo '<tr><td align="center">';
echo "<p>O Layout do arquivo deve ser: Sigla de tabela, Referência da Peça e Preço</p>";
echo '</td></tr>';
echo '<tr><td align="center">';
echo "<b>Anexar Arquivo </b><input type='file' name='arquivo' size='30'>";
echo "<p align='center' class='aquivos_aceitos'>";
echo "Formato do arquivo: Excel(.xls).";
echo "</p>";
echo "<p>";
echo '</td></tr>';
echo '<tr><td align="center">';
echo "<input type='hidden' name='btn_importar' value=''>";
echo "<input type='button' onclick=\"javascript: if (document.forms[0].btn_importar.value == '' ) { document.forms[0].btn_importar.value='importar'; document.forms[0].submit(); } else { alert ('Aguarde submissão') }\" style=\"cursor:pointer;\" value='Importar' />";

echo '</td></tr>';
echo '<tr><td>&nbsp;</td></tr>';
echo "</table>\n";
echo "</FORM>";
?>

<br>

<? include "rodape.php"; ?>
