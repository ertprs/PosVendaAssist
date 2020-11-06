<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

#if ($login_fabrica <> 3) {
#	header ("Location: menu_os.php");
#	exit;
#}

$sql = "SELECT pedir_sua_os 
		FROM tbl_fabrica
		WHERE fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);

$pedir_sua_os = pg_result($res,0,pedir_sua_os);

if ($pedir_sua_os == 'f') {
	header ("Location: os_upload_sem_sua_os.php");
	exit;
}

include 'funcoes.php';

echo `mkdir /tmp/os_upload/`;
echo `chmod 777 /tmp/os_upload/`;


if (($login_posto <> 1537 and $login_posto <> 4311 and $login_posto <> 6591) or $login_fabrica <> 3) {
#	echo "<h1> Apenas posto Piloto pode utilizar este programa em caráter de teste </h1>";
#	exit;
}


if (strlen ($_POST['enviar']) > 0) {

	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

	$tamanho = $_POST['MAX_FILE_SIZE'];

	$msg_erro = "";
	$msg_ok   = "";
	
	if (strlen ($msg_erro) == 0 AND ($arquivo["size"] > $tamanho OR $arquivo["size"] == 0) ) {
		$msg_erro = "Arquivo em tamanho muito grande! (" . $arquivo["size"] . " x " . $tamanho . ")" ; 
	}

	if (strlen ($msg_erro) == 0 AND strlen($arquivo["tmp_name"]) == 0) {
		$msg_erro = "Arquivo não foi enviado!!!";
	}

	if (strlen ($msg_erro) == 0 AND strtolower ($arquivo["name"]) <> "ordens.txt" ) {
		$msg_erro = "Nome do arquivo deve ser <b>ordens.txt</b>";
	}

	if (strlen ($msg_erro) == 0 AND !preg_match("/\/(plain)$/", $arquivo["type"])){
		$msg_erro = "Arquivo em formato inválido!<br>" . $arquivo["type"];
	}


	if (strlen($msg_erro) == 0) {
		$nome_destino = "/tmp/os_upload/" . $login_posto . "-" . date("Y-m-d-H-i-s") . ".txt" ;
		if (copy ($arquivo["tmp_name"], $nome_destino ) )  {
			$msg_ok = "Arquivo enviado com sucesso, aguarde atualização";
		}else{
			$msg_erro = "Erro ao gravar arquivo no servidor";
		}
	}
}



/* $title = Aparece no sub-menu e no título do Browser ===== */
$title = "UPLOAD de Ordem de Serviço"; 

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'os';

include "cabecalho.php";
?>

<center>
<h1>Upload de Ordens de Serviço</h1>
</center>


<? if (strlen ($msg_erro) > 0) { ?>
<table width='500' align='center' bgcolor='#FF6633'>
<tr>
	<td align='center'><font size='+1' color='#ffffff'><? echo $msg_erro ?></font></td>
</tr>
</table>
<? } ?>


<? if (strlen ($msg_ok) > 0) { ?>
<table width='500' align='center' bgcolor='#3333FF'>
<tr>
	<td align='center'><font size='+1' color='#ffffff'><? echo $msg_ok ?></font></td>
</tr>
</table>
<? } ?>


<?
flush();


#-------------------- Processamento do arquivo Linha por Linha ------------------#
if (strlen($msg_erro) == 0 and strlen ($msg_ok) > 0 AND 1==1 ) {
	
	echo "<table width='550' align='center' border='1'>";
	echo "<tr bgcolor='#3333FF'>";
	echo "<td align='center'><font size='-1' color='#ffffff'>OS</font></td>";
	echo "<td align='center'><font size='-1' color='#ffffff'>Seq.</font></td>";
	echo "<td align='center'><font size='-1' color='#ffffff'>Série</font></td>";
	echo "<td align='center'><font size='-1' color='#ffffff'>Abertura</font></td>";
//	echo "<td align='center'><font size='-1' color='#ffffff'>OS_WEB</font></td>";
	echo "<td align='center'><font size='-1' color='#ffffff'>Status OS</font></td>";
	echo "<td align='center'><font size='-1' color='#ffffff'>Peça</font></td>";
	echo "<td align='center'><font size='-1' color='#ffffff'>Status Peça</font></td>";
	echo "</tr>";

	$fp = fopen ($nome_destino,'r');
	while (!feof($fp) and $fp ) {
		$linha = fgets($fp, 2000);

		if (strlen (trim ($linha)) > 10) {
			list ($up_fabrica , $up_cnpj , $up_sua_os , $up_sequencial , $up_consumidor_revenda , $up_data_abertura , $up_data_fechamento , $up_produto_referencia , $up_serie , $up_cpf_cnpj , $up_consumidor_nome , $up_consumidor_fone , $up_revenda_cnpj , $up_revenda_nome , $up_revenda_fone , $up_nota_fiscal , $up_data_nf , $up_defeito_reclamado , $up_defeito_constatado , $up_causa_defeito , $up_peca_referencia , $up_qtde , $up_defeito , $up_servico_realizado , $up_voltagem , $up_codigo_fabricacao , $up_type , $up_satisfacao , $up_laudo, $up_subproduto , $up_posicao, $up_solucao) = explode ("\t",$linha);

			list ($up_fabrica , $up_cnpj , $up_sua_os , $up_sequencial , $up_consumidor_revenda , $up_data_abertura , $up_data_fechamento , $up_produto_referencia , $up_serie , $up_cpf_cnpj , $up_consumidor_nome , $up_consumidor_fone , $up_revenda_cnpj , $up_revenda_nome , $up_revenda_fone , $up_nota_fiscal , $up_data_nf , $up_defeito_reclamado , $up_defeito_constatado , $up_causa_defeito , $up_peca_referencia , $up_qtde , $up_defeito , $up_servico_realizado) = explode ("\t",$linha);

			#$linha = urlencode ($linha);
			if ($up_fabrica == 3 AND strlen ($up_produto_referencia) < 6 ) {
				$up_produto_referencia = "000000" . trim ($up_produto_referencia);
				$up_produto_referencia = substr ($up_produto_referencia,strlen ($up_produto_referencia)-6);
			}

			if (strlen (trim ($up_fabrica)) > 0) {
				$sql = "SELECT posto 
						FROM tbl_posto 
						WHERE cnpj = '$up_cnpj' AND posto = $login_posto";
				$resX = pg_exec ($con,$sql);
				
				if (pg_numrows ($resX) == 0) {
					echo $up_fabrica ; 
					echo $up_cnpj;
//					echo $up_sua_os ; echo $up_sequencial; echo $up_consumidor_revenda ; echo $up_data_abertura ; echo $up_data_fechamento ; echo $up_produto_referencia ; echo $up_serie ; echo $up_cpf_cnpj ; echo $up_consumidor_nome ; echo $up_consumidor_fone ; echo $up_revenda_cnpj ; echo  $up_revenda_nome ; echo  $up_revenda_fone ; echo  $up_nota_fiscal ; echo $up_data_nf ; echo $up_defeito_reclamado ; echo  $up_defeito_constatado ; echo  $up_causa_defeito ; echo  $up_peca_referencia ; echo  $up_qtde ; echo  $up_defeito ; echo $up_servico_realizado ; echo $up_voltagem ; echo $up_codigo_fabricacao ; echo $up_type ; echo $up_satisfacao ; echo $up_laudo; echo  $up_subproduto ;echo  $up_posicao; echo $up_solucao;
					echo "</table><h1>CNPJ não confere com código do posto<br>$sql";
					exit;
				}

				$dv = substr ($up_cnpj,1,1) * substr ($up_cnpj,6,1);
				if (strlen ($up_data_abertura) == 10) {
					$up_data_abertura = substr ($up_data_abertura,8,2) . "/" . substr ($up_data_abertura,5,2) . "/" . substr ($up_data_abertura,0,4);
				}

				if (strlen ($up_data_fechamento) == 10) {
					$up_data_fechamento = substr ($up_data_fechamento,8,2) . "/" . substr ($up_data_fechamento,5,2) . "/" . substr ($up_data_fechamento,0,4);
				}

				if (strlen ($up_data_nf) == 10) {
					$up_data_nf = substr ($up_data_nf,8,2) . "/" . substr ($up_data_nf,5,2) . "/" . substr ($up_data_nf,0,4);
				}

				if ($up_defeito == "82") $up_defeito = "74";

				$sua_os_x = $up_sua_os;
				if (strlen($up_sequencial) > 0) $sua_os_x = $up_sua_os."-".$up_sequencial;

				//se tiver data de fechamento, armazena em um array e limpa a data antes de gravar
				//pois se tiver mais de uma peça vai fechar a OS no primeiro envio
				if (strlen($up_data_fechamento) > 0) {
					
					//verifica se esta os ja esta no array
					$os_repetida = 'f';
					for($i=0; $i<count($fechamento_fabrica); $i++) {
						if ( ($os_repetida == 'f') and ($fechamento_fabrica[$i] == $up_fabrica) and ($fechamento_sua_os[$i] == $sua_os_x) and ($fechamento_cnpj[$i] == $up_cnpj) ) {
							$os_repetida = 't';
						}
					}

					//se ainda nao esta no array de fechamento inclui
					if ($os_repetida == 'f') {
						$fechamento_fabrica[]         = $up_fabrica;
						$fechamento_sua_os[]          = $sua_os_x;
						$fechamento_cnpj[]            = $up_cnpj;
						$fechamento_data_fechamento[] = $up_data_fechamento;
					}
				}

if($login_fabrica == 3){
				$up_consumidor_estado = "SP";
				$up_consumidor_cidade = "MARILIA";
				$up_consumidor_revenda = "C";
}

				$data_string  = "fabrica=$up_fabrica&";
				$data_string .= "cnpj=$up_cnpj&";
				$data_string .= "dv=$dv&";
				$data_string .= "sua_os=$sua_os_x&";
				$data_string .= "sequencial=$up_sequencial&";
				$data_string .= "consumidor_revenda=$up_consumidor_revenda&";
				$data_string .= "data_abertura=$up_data_abertura&";
				$data_string .= "data_fechamento=&";
				$data_string .= "produto_referencia=$up_produto_referencia&";
				$data_string .= "serie=$up_serie&";
				$data_string .= "cpf_cnpj=$up_cpf_cnpj&";
				$data_string .= "consumidor_nome=$up_consumidor_nome&";
				$data_string .= "consumidor_fone=$up_consumidor_fone&";
if($login_fabrica == 3){
				$data_string .= "consumidor_cidade=$up_consumidor_cidade&";
				$data_string .= "consumidor_estado=$up_consumidor_estado&";
}
				$data_string .= "revenda_cnpj=$up_revenda_cnpj&";
				$data_string .= "revenda_nome=$up_revenda_nome&";
				$data_string .= "revenda_fone=$up_revenda_fone&";
				$data_string .= "nota_fiscal=$up_nota_fiscal&";
				$data_string .= "data_nf=$up_data_nf&";
				$data_string .= "defeito_reclamado=$up_defeito_reclamado&";
				$data_string .= "defeito_constatado=$up_defeito_constatado&";
				$data_string .= "causa_defeito=$up_causa_defeito&";
				$data_string .= "peca_referencia=$up_peca_referencia&";
				$data_string .= "qtde=$up_qtde&";
				$data_string .= "defeito=$up_defeito&";
				$data_string .= "servico_realizado=$up_servico_realizado&";
				$data_string .= "voltagem=$up_voltagem&";
				$data_string .= "codigo_fabricacao=$up_codigo_fabricacao&";
				$data_string .= "type=$up_type&";
				$data_string .= "satisfacao=$up_satisfacao&";
				$data_string .= "laudo=$up_laudo&";
				$data_string .= "solucao_os=$up_solucao&";


				#---------------- Postando Dados --------------
				$referer  = $_SERVER["SCRIPT_URI"];
				
				$URL_Info = parse_url("http://posvenda.telecontrol.com.br/assist/os_post_novo.php");

				$request  = "POST "  . $URL_Info["path"] . " HTTP/1.1\n";
				$request .= "Host: " . $URL_Info["host"] . "\n";
				$request .= "Referer: $referer\n";
				$request .= "Cookie: teste=sou o posto X \n";
				$request .= "Content-type: application/x-www-form-urlencoded\n";
				$request .= "Content-length: " . strlen ($data_string) . "\n";
				$request .= "Connection: close\n";
				$request .= "\n";
				$request .= $data_string . "\n";

				$post = fsockopen($URL_Info["host"],80);
				$retorno = "";
				fputs($post, $request);
				while(!feof($post)) {
					$retorno .= fgets($post, 128);
				}
				fclose($post);

				#--------------- Retorno OS por OS ---------------#
				$os_web = substr ($retorno, strpos ($retorno,"<OK-I>")+6, 30) ;
				$os_web = trim (substr ($os_web , 0, strpos ($os_web ,"<OK-F><!--OFFLINE-F-->")));
				$status_os = "<!-- $retorno -->";
				//if ($ip == "200.158.35.181") echo "<!-- retorno = $retorno -->";


				if (strlen ($os_web) > 0 and strpos ($status_os ,"<ERRO-I>") == 0) {
					$status_os = "OK";
				}else{
					if (strpos ($status_os ,"<ERRO-I>") <> 0) {

						//pega o erro da os
						$status_os = substr ($status_os, strpos($status_os,"<ERRO-I>")+8, 300) ;
						$status_os = "ERRO ".trim(substr ($status_os , 0, strpos ($status_os,"<ERRO-F>")));
					
						$erro_fechada = "f";
						//se for erro de os ja fechada, pega o numero da os no erro para imprimir
						if (strpos ($status_os ,"Esta OS já foi fechada anteriormente") <> 0) {
							$erro_fechada = "t";
							$status_os = "AVISO: Esta OS já foi fechada anteriormente";
							//$os_web = substr ($status_os, strpos($status_os,"Erro encontrado: OS ")+20, 10) ;
							//$os_web = trim(substr ($os_web , 0, strpos($os_web,"já foi fechada anteriormente")-2));
						}

						//seta erro no array para esta OS
						for($i=0; $i<count($fechamento_fabrica); $i++) {
							if ( ($fechamento_fabrica[$i] == $up_fabrica) and ($fechamento_sua_os[$i] == $sua_os_x) and ($fechamento_cnpj[$i] == $up_cnpj) ) {
								if ($erro_fechada == "f") $fechamento_erro_os[$i] = "ERRO";
								if ($erro_fechada == "t") $fechamento_erro_os[$i] = "Esta OS já foi fechada anteriormente";

								$setou_erro = 't';
							}
						}


					}
				}

				
				$status_peca = substr ($retorno, strpos ($retorno,"<!-- INICIO PECA -->")+20);
				$status_peca = substr ($status_peca,0,strpos ($status_peca,"<!-- FINAL PECA -->"));

				if (strpos ($retorno,"OK OS_ITEM") > 0) {
					$status_peca = "OK";
				}else{ 
					if (strpos($retorno,"<OK-I><AVISO-I>Peça já lançada") > 0) {
						$status_peca = "JA LANÇADA";
					}else{
						$causa_erro = "";
						if (strpos ($retorno,"<ERRO-I>") > 0) {
							$causa_erro  = substr ($retorno,strpos ($retorno,"<ERRO-I>")+8);
							$causa_erro  = substr ($causa_erro,0,strpos ($causa_erro,"<ERRO-F>"));




							$erro_fechada = "f";
							//se for erro de os ja fechada, pega o numero da os no erro para imprimir
							if (strpos ($causa_erro ,"Esta OS já foi fechada anteriormente") <> 0) {
								$erro_fechada = "t";
								$status_peca = "AVISO: Esta OS já foi fechada anteriormente";
								//$os_web = substr ($status_os, strpos($status_os,"Erro encontrado: OS ")+20, 10) ;
								//$os_web = trim(substr ($os_web , 0, strpos($os_web,"já foi fechada anteriormente")-2));
							}

							if ($erro_fechada == "f") {
								//seta erro no array para esta OS
								for($i=0; $i<count($fechamento_fabrica); $i++) {
									if ( ($fechamento_fabrica[$i] == $up_fabrica) and ($fechamento_sua_os[$i] == $sua_os_x) and ($fechamento_cnpj[$i] == $up_cnpj) ) {
										$fechamento_erro_os[$i] = "ERRO";
										$setou_erro = 't';
									}
								}
								$status_peca = "ERRO " . $status_peca . $causa_erro ;
							}
						}




					}
				}

				//se não setou erro, seta ok para ficar na mesma posicao dos dados do array
				if (strlen($up_data_fechamento) > 0 and $setou_erro <> 't') {
					for($i=0; $i<count($fechamento_fabrica); $i++) {
						if (($fechamento_erro_os[$i] <> "ERRO") and ($fechamento_erro_os[$i] <> "Esta OS já foi fechada anteriormente") and ($fechamento_fabrica[$i] == $up_fabrica) and ($fechamento_cnpj[$i] == $up_cnpj) and ($fechamento_data_abertura[$i] == $up_data_abertura) and ($fechamento_serie[$i] == $up_serie) ) {
							$fechamento_erro_os[$i] = "OK";
						}
					}
				}

				if (strlen ($up_peca_referencia) == 0) {
					$status_peca = "&nbsp;";
					$up_peca_referencia = "&nbsp;";
				}

				echo "<tr style='font-size:10px'>";

				echo "<td nowrap>&nbsp;";
				echo $sua_os_x;
				echo "</td>";

				echo "<td nowrap>&nbsp;";
				echo $up_sequencial;
				echo "</td>";

				echo "<td nowrap>&nbsp;";
				echo $up_serie;
				echo "</td>";

				echo "<td nowrap>&nbsp;";
				echo $up_data_abertura;
				echo "</td>";

/*				echo "<td nowrap>";
				echo $os_web;
				echo "</td>";*/

				echo "<td nowrap>&nbsp;";
				echo $status_os;
				echo "</td>";

				echo "<td nowrap>&nbsp;";
				echo $up_peca_referencia;
				echo "</td>";

				echo "<td nowrap>&nbsp;";
				echo $status_peca;
				echo "</td>";

				echo "</tr>";
			}else{
				echo "<tr style='font-size:10px'>";

				echo "<td nowrap>&nbsp;";
				echo $up_sua_os ;
				echo "</td>";

				echo "<td colspan='9'>";
				echo "Não foi passado o número do fabricante<br>";
				echo "</td>";
				echo "</tr>";
			}

		}
	}
	fclose($fp);
	unlink ($nome_destino);

	echo "</table>";

	echo "<BR>";
	echo "<table width='550' align='center' border='1'>";
	echo "<tr bgcolor='#3333FF'>";
	echo "<td align='center'><font size='-1' color='#ffffff'>OS</font></td>";
	echo "<td align='center'><font size='-1' color='#ffffff'>Fechamento</font></td>";
	echo "<td align='center'><font size='-1' color='#ffffff'>Status Fechamento</font></td>";

	//faz os fechamentos a partir dos dados do array
	for($i=0; $i<count($fechamento_fabrica); $i++){
		if ($fechamento_erro_os[$i] <> 'ERRO' and $fechamento_erro_os[$i] <> 'Esta OS já foi fechada anteriormente') {
			$xdata_fechamento = substr ($fechamento_data_fechamento[$i],6,4) . "-" . substr ($fechamento_data_fechamento[$i],3,2) . "-" . substr ($fechamento_data_fechamento[$i],0,2);
			$sql = "SELECT os 
					FROM tbl_os 
					WHERE fabrica = $fechamento_fabrica[$i] 
					AND posto = (SELECT posto FROM tbl_posto WHERE cnpj='$fechamento_cnpj[$i]') 
					AND sua_os = '$fechamento_sua_os[$i]'
					AND data_fechamento IS NULL";
			$res = pg_exec ($con,$sql);

			if (pg_numrows ($res) > 0) {
				$os_web = pg_result($res,0,os);

				echo "<!-- INICIO FECHAMENTO OS -->";

				$res = pg_exec ($con,"BEGIN TRANSACTION");
				$sql = "UPDATE tbl_os SET data_fechamento = '$xdata_fechamento'::date 
						WHERE tbl_os.os = $os_web";
				$res       = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage ($con);

				if (strlen ($msg_erro) == 0) {
					$sql = "SELECT fn_finaliza_os($os_web, $fechamento_fabrica[$i])";
					$res = @pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
				}

				if (strlen ($msg_erro) > 0) {
					$res = @pg_exec ($con,"ROLLBACK TRANSACTION");

					//echo "<!--OFFLINE-I--><ERRO-I>ERRO NO FECHAMENTO DA OS $os_web: $msg_erro<ERRO-F><!--OFFLINE-F--><!-- ERRO NO FECHAMENTO DA OS -->";

					$status_fechamento = "ERRO NO FECHAMENTO DA OS $os_web: $msg_erro";
				}else{
					$res = @pg_exec ($con,"COMMIT TRANSACTION");
					$status_fechamento = "OS Fechada com sucesso.";
				}

				echo "<!-- FIM FECHAMENTO OS -->";
			}
		}else{
			if (strpos ($fechamento_erro_os[$i] ,"fechada anteriormente") <> 0) {
				$status_fechamento = $fechamento_erro_os[$i];
			} else {
				$status_fechamento = "ERRO: Verifique Status da OS e Status da Peça";
			}
		}

		echo "<tr style='font-size:10px'>";
		echo "<td nowrap>&nbsp;";
		echo $fechamento_sua_os[$i];
		echo "</td>";
		
		echo "<td nowrap>&nbsp;";
		echo $fechamento_data_fechamento[$i];
		echo "</td>";

		echo "<td nowrap>&nbsp;";
		echo $status_fechamento;
		echo "</td>";

		echo "</tr>";

	}
	echo "</table>";
}


?>

<form enctype = "multipart/form-data" name="frm_upload" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="MAX_FILE_SIZE" value="500000">
Arquivo contendo suas Ordens de Serviço
<br>
<input type='file' name='arquivo' size='30'>
<br>
<input type='submit' value='Enviar' name='enviar'>
</form>


<p>

<table width='500' align='center' border='1'>
<tr bgcolor='#333399'>
	<td align='center'>
		<font size='-1' color='#ffffff'><b>
		Definições para envio de Ordens de Serviço por Arquivo
		</b></font>
	</td>
</tr>

<tr>
	<td style='font-size:12px'>
		<ul>
		<li> O arquivo deve ter o nome <b>ordens.txt</b> em letras minúsculas
		<li> Os campos devem ser separados por <b>TAB</b> chr(9)
		<li> Repetir o registro quando a Ordem de Serviço tiver mais de uma peça
		<li> Enviar a Ordem de Serviço até que o site confirme seu fechamento
		<li> O campo FÁBRICA deve vir preenchido com o número <b><? echo $login_fabrica?></b>
		<li> CNPJ deve vir sem formatação, com 14 posições 
		<li> CPF deve vir sem formatação, com 11 posições
		<li> Todas as datas devem vir no formato YYYY-MM-DD
		<li> Não enviar datas em branco, apenas respeitar a tabulação
		<p>
		<li> Baixe aqui um <a href='os-upload.xls'>exemplo</a> em Excel
		<li> Lembre-se de exportar o arquivo em formato texto, delimitado por <b>TAB</b>
		</ul>
	</td>
</tr>

<tr bgcolor='#333399'>
	<td align='center'>
		<font size='-1' color='#ffffff'><b>
		Lay-Out do Arquivo
		</b></font>
	</td>
</tr>

<tr>
	<td style='font-size:12px'>
		<b> Dependendo do Fabricante, alguns campos são obrigatórios </b>
		<p>

		<ul>
		<li> Código do Fabricante (<? echo $login_fabrica ?>)
		<li> CNPJ do Posto Autorizado
		<li> Número da Ordem de Serviço
		<li> Sequencial em caso de Ordem de Serviço de Revenda
		<li> "C" para Ordem de Serviço de Consumidor e "R" para Revenda
		<li> Data da abertura
		<li> Data de fechamento (nulo se ainda não fechada)
		<li> Referência do Produto
		<li> Número de Série
		<li> CPF ou CNPJ do Consumidor
		<li> Nome do Consumidor
		<li> Telefone do Consumidor
		<li> CNPJ da Revenda
		<li> Nome da Revenda
		<li> Fone da Revenda
		<li> Número da Nota Fiscal de Compra
		<li> Data da Compra
		<li> Código do Defeito Reclamado
		<li> Código do Defeito Constatado
		<li> Código da Causa do Defeito
		<p>
		<li> Referência da Peça
		<li> Quantidade trocada da Peça
		<li> Código do Defeito da Peça
		<li> Código do Serviço Realizado
		<li> Código da Solução (Utilizar a mesma tabela do Serviço Realizado)

		<? if ($login_fabrica == 1) { ?>
		<p>
		<li> Voltagem do Aparelho
		<li> Código de Fabricação
		<li> Type
		<li> Satisfação DeWALT ("S" se aplicável, ou "N")
		<li> Laudo Técnico (obrigatório para Satisfação DeWALT. Não enviar <b>ENTER<//b>
		<? } ?>

		</ul>
	</td>
</tr>

<tr bgcolor='#333399'>
	<td align='center'>
		<font size='-1' color='#ffffff'><b>
		Tabela Necessárias para Integração
		</b></font>
	</td>
</tr>

<tr>
	<td style='font-size:12px'>
		<b> Conteúdo do arquivo compactado </b>
		<ul>
		<li> Planilha EXCEL com as seguintes pastas:
		<ul>
			<li> Cadastro de Produtos
			<li> Cadastro de Peças
			<li> Lista Básica dos Produtos
			<li> Tabela de Defeito Reclamado
			<li> Tabela de Defeito Constatado
			<li> Tabela de Causas de Defeito
			<li> Tabela de Defeitos de Peças
			<li> Tabela de Serviços Realizados
		</ul>
		</ul>
		<p>
		<center><a href='tabela_os_upload_xls.php'><b>Download dos arquivos</b></a></center>
		<!--<center><a href='os-arquivos.zip'><b>os-arquivos.zip</b></a></center>-->
		<p>
	</td>
</tr>

</table>



<p>

<? include "rodape.php";?>
