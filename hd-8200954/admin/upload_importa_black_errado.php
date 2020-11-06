<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";
include "autentica_admin.php";

if($login_fabrica <> 1) {
	header("Location: menu_callcenter.php");
	exit;
}

$msg ="";

##### A T U A L I Z A R #####
if (strlen($_POST["btn_acao"]) > 0) {

	$caminho = "/www/cgi-bin/blackedecker/entrada";


	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

	if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none"){
		// deixar mensagem
		$msg_inicio =  "<tr ><td colspan ='2'>Importando arquivo [ ".$arquivo["name"]." ] !!!<br> Aguarde...</td></tr>";
		flush();

		$config["tamanho"] = 2048000;

		if ($arquivo["type"] <> "text/plain") {
		//	$msg_erro = "Arquivo em formato inválido!";
		} else {
			if ($arquivo["size"] > $config["tamanho"]) 
				$msg_erro = "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB.";
		}

		if (strlen($msg_erro) == 0) {
			// Faz o upload
			$nome_arquivo = $caminho."/".$arquivo["name"];
			if (!copy($arquivo["tmp_name"], $nome_arquivo)) {
				$msg_erro = "Arquivo '".$arquivo['name']."' não foi enviado!!!";
			}else{
				$f = fopen("/www/cgi-bin/blackedecker/entrada/".$arquivo["name"], "r");

				//open same file and use "w" to clear file 

				//$f=fopen("some.txt","r");

				//loop through array using foreach
				$i=1;
				$msg_erro = "";
				while (!feof($f)){
					$buffer = fgets($f, 4096);
					if($buffer <> "\n" and strlen(trim($buffer))>0){
						list($codigo_posto, $razao, $protocolo, $valor, $emissao, $vencimento, $os, $pagamento, $banco, $agencia, $conta_corrente) =    explode(";", $buffer);

						if (strlen($pagamento) == 0) {
							if(strlen($protocolo)>0){
								$msg_erro .= "<tr bgcolor='#FF9900' ><td width='10%'>linha $i </td> <td nowrap>Protocolo $protocolo nao tem data de pagamento</td></tr>";
							}else{
								$msg_erro .= "<tr bgcolor='#FF9900' ><td width='10%'>linha $i </td> <td nowrap>Protocolo está vazio!</td></tr>";

							}
						}else{
							$sql = "SELECT extrato 
									FROM tbl_extrato 
									WHERE protocolo = $protocolo;";
							$result = pg_exec ($con,$sql);
							if(pg_result_error($res)){
								$msg_erro .= "\n".pg_result_error($res);
							}else{					
								if (pg_numrows($result) == 0) {
									$msg_erro .= "\n<tr bgcolor='#FF9900'><td width='10%'>linha:$i </td> <td nowrap>Protocolo: $protocolo nao localizado em extrato</td></tr> \n";
								}else{
									//print "Extrato $extrato";

									$extrato = trim(pg_result($result, 0, extrato));
									### VERIFICA EXISTÊNCIA DO STATUS DO EXTRATO
									$sql = "SELECT tbl_extrato_status.extrato
											FROM   tbl_extrato_status
											WHERE  tbl_extrato_status.extrato = $extrato;";
									$result = pg_exec ($con,$sql);
									if(pg_result_error($res)){
										$msg_erro .= "\n".pg_result_error($res);
									}
												
									$obs = "Data do pagamento $pagamento Banco $banco Agencia $agencia CC $conta_corrente" ;
									$pagamento = substr ($pagamento,6,4) . "-" . substr ($pagamento,3,2) . "-" . substr ($pagamento,0,2);
									### INCLUI O STATUS DO EXTRATO QUE NÃO EXISTE
									if (pg_numrows($res)== 0) {
										$sql = "INSERT INTO tbl_extrato_status (extrato, data, obs, pendente, confirmacao_pendente, pendencia) 		VALUES
												($extrato , '$pagamento' ,'$obs', null, null, 'f');";
										$result = pg_exec ($con,$sql);
										
										if(pg_result_error($res)){
											$msg_erro .= "\n".pg_result_error($res);
										}else{					
											$msg.= "<tr bgcolor='#FF9900'><td  width='10%'>$i </td> <td nowrap>Extrato $protocolo pago na data $pagamento no Banco $banco Agencia $agencia C/c $conta_corrente</td></tr>";
											//echo "Extrato $protocolo pago na data $pagamento no Banco $banco Agencia $agencia C/c $conta_corrente";
										}
									}else{
										$sql = "UPDATE tbl_extrato_status 
												SET data = '$pagamento', 
													obs = '$obs' ,
													confirmacao_pendente = null,
													pendente = null,
													pendencia = 'f'
												WHERE tbl_extrato_status.extrato = $extrato;";
										$result = pg_exec ($con,$sql);
											
										if(pg_result_error($res)){
											$msg_erro .= "\n".pg_result_error($res);
											$extrato = "";
										}else{
											$msg .= "<tr bgcolor='#66CCFF'><td width='10%'>linha:$i </td> <td nowrap>Extrato $protocolo pago na data $pagamento no Banco $banco Agencia $agencia C/c $conta_corrente</td></tr>";

										}
									}
								}
							}
						}
					$i++;
					}
				}
				fclose($f);
			}
			$msg .=  "<tr><td colspan='2'><H1>Arquivo ( ".$arquivo["name"]." ) importado com sucesso!!!</H1></td></tr>";
		}
		flush();
	
		/*
		if (strlen($msg_erro) == 0 ) {
			$msg .= "<tr bgcolor='#66CCFF'><td colspan='2'><h1>Executando a atualização dos PAGAMENTOS!<br> Aguarde...</h1></td></tr>";
		}
		*/
	}
}

$layout_menu = "callcenter";
$title = "Importação BLACK";

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
echo "<TABLE width='600' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
if (strlen($msg_inicio) > 0) {

	echo "$msg_inicio";

}
if (strlen($msg_erro) > 0) {
	echo "<tr bgcolor='#FF0033'><td colspan='2'><font color='#000000'><h1>Erros encontrados:</h1></font></td></tr>";
	echo "$msg_erro";

}
if (strlen($msg) > 0) {
	echo "<tr ><td colspan='2'>&nbsp;</td></tr>";

	echo "<tr bgcolor='#6699FF'><td colspan='2'><font color='#000000'><h1>Pagamentos atualizados com sucesso</h1></font></td></tr>";
	echo "$msg\n";

}

echo "<TR class='menu_top'>\n";
echo "<TD colspan='2'><font size='+1'>Envio de arquivo de pagamento para atualização</font></TD>\n";
echo "</TR>\n";
echo "</table>\n";

echo "<center style='font-size:12px'><br><b>O arquivo deve ser do tipo: \".txt\".</b></center>";

echo "<FORM METHOD='POST' ACTION='$PHP_SELF' enctype='multipart/form-data'>";
echo "<input type='hidden' name='btn_acao' value=''><br>";
echo "<b>ANEXAR ARQUIVO </b>
		<input type='file' name='arquivo' size='30'>";
echo "<p>";
echo "<img src=\"imagens/btn_gravar.gif\" onclick=\"javascript: if (document.forms[0].btn_acao.value == '' ) { document.forms[0].btn_acao.value='gravar'; document.forms[0].submit(); } else { alert ('Aguarde submissão') }\" ALT=\"Gravar Formulario\" border='0' style=\"cursor:pointer;\">";
echo "</FORM>";
?>

<br>

<? include "rodape.php"; ?>
