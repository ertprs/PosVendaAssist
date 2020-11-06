<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";

if($login_fabrica <> 1) {
	header("Location: menu_financeiro.php");
	exit;
}

$msg ="";

$layout_menu = "financeiro";
$title = "IMPORTAÇÃO BLACK";

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


		if ($arquivo["type"] <> "text/plain") {$msg_erro = "Arquivo em formato inválido!";}

		if (strlen($msg_erro) == 0) {
			// Faz o upload
			$nome_arquivo = $caminho;
			//if (!@move_uploaded_file($arquivo["tmp_name"], $nome_arquivo)) { //Adicionei o @ para não dar problema de saída antes dos  header
				//$msg_erro = "Arquivo '".$arquivo['name']."' não recebido!!!";
			//}else{
				$f = fopen($nome_arquivo, "r");
				$i=1;
				$msg_erro = "";
				while (!feof($f)){
					$buffer = fgets($f, 4096);
					if($buffer <> "\n" and strlen(trim($buffer))>0){
						list($codigo_posto, $razao, $protocolo, $valor, $emissao, $vencimento, $os, $pagamento, $banco, $agencia, $conta_corrente) = explode(";", $buffer);
						if (strlen($pagamento) == 0) {
							if(strlen($protocolo)>0){
								$msg_erro .= "<tr bgcolor='#FF9900' ><td width='10%'>linha $i </td> <td nowrap>Protocolo $protocolo nao tem data de pagamento</td></tr>";
							}else{
								$msg_erro .= "<tr bgcolor='#FF9900' ><td width='10%'>linha $i </td> <td nowrap>Protocolo está vazio!</td></tr>";

							}
						}else{
							$sql = "SELECT extrato
									FROM tbl_extrato
									WHERE fabrica = $login_fabrica
									AND protocolo = TRIM('$protocolo');";
							$res = pg_exec ($con,$sql);
							if(pg_result_error($res)){
								$msg_erro .= "\n".pg_result_error($res);
							}else{
								if (pg_numrows($res) == 0) {
									$msg_erro .= "\n<tr bgcolor='#FF9900'><td width='10%'>linha:$i </td> <td nowrap>Protocolo: $protocolo nao localizado em extrato</td></tr> \n";
								}else{
									//print "Extrato $extrato";
									$extrato = trim(pg_result($res, 0, extrato));
									### VERIFICA EXISTÊNCIA DO STATUS DO EXTRATO
									$sql = "SELECT tbl_extrato_status.extrato
											FROM   tbl_extrato_status
											WHERE  tbl_extrato_status.extrato = $extrato
											and tbl_extrato_status.pendencia is not true;";
									$res = pg_exec ($con,$sql);
									if(pg_result_error($res)){
										$msg_erro .= "\n".pg_result_error($res);
									}

									$obs = "Data do pagamento $pagamento" ;
									//FABIOLA SOLICITOU QUE DEIXASSE APENAS A DATA, POIS NAO ESTAO MAIS MANDANDO BANCO, AGENCIA E CONTA CORRENTE   HD 8409 11/12/2007 TAKASHI

									$pagamento = substr ($pagamento,6,4) . "-" . substr ($pagamento,3,2) . "-" . substr ($pagamento,0,2);
									$pagamento = preg_replace('/^(\d{2}).(\d{2}).(\d{4})/', '$3-$2-$1', $pagamento);
									### INCLUI O STATUS DO EXTRATO QUE NÃO EXISTE
									if (pg_numrows($res)== 0) {
										$sql = "INSERT INTO tbl_extrato_status (fabrica, extrato, data, obs, pendente, confirmacao_pendente, pendencia) 		VALUES
												($login_fabrica, $extrato , '$pagamento' ,'$obs', null, null, 'f');";
										$res = pg_exec ($con,$sql);
										if(pg_result_error($res)){
											$msg_erro .= "\n".pg_result_error($res);
										}else{
											$msg.= "<tr bgcolor='#FF9900'><td  width='10%'>$i </td> <td nowrap>Extrato $protocolo pago na data $pagamento</td></tr>";
										}
									}else{
										$sql = "UPDATE tbl_extrato_status
												SET data = '$pagamento',
													obs = '$obs' ,
													confirmacao_pendente = null,
													pendente = null,
													pendencia = 'f'
												WHERE tbl_extrato_status.extrato = $extrato
												AND pendencia is not true 
												and admin_conferiu isnull;";
										$res = pg_exec ($con,$sql);
										if(pg_result_error($res)){
											$msg_erro .= "\n".pg_result_error($res);
											$extrato = "";
										}else{
											$msg .= "<tr bgcolor='#66CCFF'><td width='10%'>linha:$i </td> <td nowrap>Extrato $protocolo pago na data $pagamento </td></tr>";

										}
									}
								}
							}
						}
					$i++;
					}
				}
				fclose($f);
				$msg .=  "<tr class='sucesso'><td colspan='2'><H1>Arquivo ( ".$arquivo["name"]." ) importado com sucesso!!!</H1></td></tr>";
		}
		flush();


		if (strlen($msg_erro) == 0 ) {
			$msg .= "<tr bgcolor='#66CCFF'><td colspan='2'><h1>Executando a atualização dos PAGAMENTOS!<br> Aguarde...</h1></td></tr>";
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
	echo "<tr class='msg_erro'><td colspan='2'>//$msg_erro</td></tr>";
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
            Envio de arquivo de pagamento para atualização
            </td>
        </tr>
        <tr>
            <td colspan='3'>&nbsp;</td>
        </tr>
        <tr>
        	<td colspan='6'>
        		<b>Formato:</b> Codigo Posto; Razão Social; Extrato; Valor; Emissão; Vencimento; OS; Data Pagamento
        	</td>
        </tr>
        <tr>
            <td colspan='6'>&nbsp;</td>
        </tr>
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
