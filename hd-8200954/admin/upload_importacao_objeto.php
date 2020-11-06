<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastro";
include "autentica_admin.php";

if($login_fabrica <> 11 and $login_fabrica <> 172) {
	header("Location: menu_cadastro.php");
	exit;
}

$msg ="";

$layout_menu = "cadastro";
$title = "IMPORTAÇÃO ARQUIVO NUMERO OBJETO";

include "cabecalho.php";

##### A T U A L I Z A R #####
if (strlen($_POST["btn_acao"]) > 0) {

	$caminho = $_FILES['arquivo']['tmp_name'];


	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
	if(strlen($arquivo["tmp_name"])==0){
		$msg_erro = "Selecione um Arquivo para Continuar";
	}
	
	if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none"){
		// deixar mensagem
		//$msg_inicio =  "<tr ><td colspan ='2'>Importando arquivo [ ".$arquivo["name"]." ] !!!<br> Aguarde...</td></tr>";
		//flush(); // Não faz sentido se não há saída para o buffer
		$config["tamanho"] = 2048575;
		if ($arquivo["type"] <> "text/plain") {$msg_erro = "Arquivo em formato inválido!";}
		if ($arquivo["size"] > $config["tamanho"]) { 
			$msg_erro = "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB.";
		}
		if (strlen($msg_erro) == 0) {
			// Faz o upload
			$nome_arquivo = $caminho;
			//if (!@move_uploaded_file($arquivo["tmp_name"], $nome_arquivo)) { //Adicionei o @ para não dar problema de saída antes dos  header
				//$msg_erro = "Arquivo '".$arquivo['name']."' não recebido!!!";
			//}else{
				//$f = fopen($nome_arquivo, "r");
			$i=1;
			$msg_erro = "";
			$file_contants = file_get_contents($nome_arquivo);
			$file_contants = explode("\n",$file_contants);
			//var_dump($file_contants);
			foreach ($file_contants as $key => $linha){
				//echo $linha;
				$i=$key+1;
				if ($linha == "\n"){
					continue;
				}
				unset ($cnpj, $notafiscal, $numero_objeto);
				list($cnpj, $notafiscal, $numero_objeto) = explode(";", $linha);
				$cnpj = str_replace("\r","",$cnpj);
				$notafiscal = str_replace("\r","",$notafiscal);
				$numero_objeto = str_replace("\r","",$numero_objeto);
				if (strlen($numero_objeto) > 0 ){
						if(strlen($cnpj)>0){
							
						$sql = "SELECT faturamento
								FROM tbl_faturamento 
								JOIN tbl_posto using (posto) 
								WHERE fabrica = $login_fabrica 
								AND cnpj = '$cnpj'
								AND nota_fiscal = '$notafiscal';";
								//echo$sql;
						$res = pg_query ($con,$sql);
						if(!strlen(pg_last_error())){
							if (pg_num_rows($res) == 0) {
								$msg_erro .= "\n<tr bgcolor='#FF9900'><td width='10%'>linha:$i </td> <td nowrap>Numero do faturamento nao encontrado pra posto e nota fiscal $cnpj , $notafiscal</td></tr> \n";
							}else{
								$conhecimento = pg_fetch_result($res,0,'faturamento');
								$sql = "UPDATE tbl_faturamento 
										SET conhecimento = '$numero_objeto'
										WHERE faturamento = $conhecimento;";
								$res = pg_query ($con,$sql);
								if (strlen(pg_last_error()) > 0){
									$msg_erro .= "\n".pg_last_error();
									$extrato = "";
								}else{
									$msg .= "<tr bgcolor='#66CCFF'><td width='10%'>linha:$i </td> <td nowrap>Numero do Objeto $numero_objeto inserido</td></tr>";
								}
							}
						}
						}else{
								$msg_erro .= "<tr bgcolor='#FF9900' ><td width='10%'>linha $i </td> <td nowrap>CNPJ do posto está vazio!</td></tr>";
						}
				}else{
					$msg_erro .= "<tr bgcolor='#FF9900' ><td width='10%'>linha $i </td> <td nowrap>Numero do Objeto $numero_objeto vazio</td></tr>";
				}
			}
		}
	}
}
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



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
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
}


</style>

<?
echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
if (strlen($msg_inicio) > 0) {

	echo "$msg_inicio";

}
if (strlen($msg_erro) > 0) {
	echo "<tr class='msg_erro'><td colspan='2'>$msg_erro</td></tr>";
	echo "";

}
if (strlen($msg) > 0) {
	echo "<tr ><td colspan='2'>&nbsp;</td></tr>";

	echo "<tr class='sucesso'><td colspan='2'>$msg</td></tr>";
	

}

echo "</table>\n";

?>
<form method='POST' action='<?$PHP_SELF?>' enctype='multipart/form-data'>
    <table align='center' class='formulario' width='700'>
        <tr class='titulo_tabela'>
            <td colspan='3'>
            Envio de arquivo de número de objeto para atualização
            </td>
        </tr>
        
		<tr class='subtitulo'>
            <td colspan='3'>
            Arquivo .txt deverá conter as colunas CNPJ;NOTAFISCAL;NUMEROOBJETO
            </td>
        </tr>
        <tr>
            <td colspan='3'>&nbsp;</td>
        </tr>
        <tr>
		<tr>
            <td width='80'>&nbsp;</td>
            <td align='right'>
                <input type='hidden' name='btn_acao' value=''>
                Anexar arquivo .txt
            </td>
            <td align='left'>
                <input type='file' name='arquivo' size='40' class='frm'>
            </td>
        </tr>
        <tr><td colspan='3'>&nbsp;</td></tr>
        <tr>
        <td colspan='3' align='center'>
            <input type='button' style='background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;'
                  value='&nbsp;' onclick="if (document.forms[0].btn_acao.value == ''){document.forms[0].btn_acao.value='gravar'; document.forms[0].submit(); }else{alert('Aguarde submissão')}" ALT='Gravar Formulario' border='0'>
            </td>
        </tr>
    </table>
</form>

<br>

<? include "rodape.php"; ?>
