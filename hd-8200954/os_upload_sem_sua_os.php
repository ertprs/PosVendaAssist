<?
//ESTE PROGRAMA É UTILIZADO PARA FABRICAS QUE NÃO DIGITAM O NUMERO DA OS

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$self=basename($PHP_SELF);

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico == 'automatico'){
	$login_fabrica = trim($_GET["login_fabrica"]);
	$login_posto   = trim($_GET["login_posto"]);
	$automatico    = true;
}else{
	include 'autentica_usuario.php';
}

include 'funcoes.php';


$data_inicio= date("Y-m-d-H-i-s");

if (strlen ($_POST['enviar']) > 0) {

	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

	$tamanho = $_POST['MAX_FILE_SIZE'];

	$msg_erro = "";
	$msg_ok   = "";

	if (strlen ($msg_erro) == 0 AND ($arquivo["size"] > $tamanho OR $arquivo["size"] == 0) ) {
		 if($sistema_lingua=='ES') $msg_erro = "¡Tamaño del archivo es muy grande! (" . $arquivo["size"] . " x " . $tamanho . ")" ;
		else $msg_erro = "Arquivo em tamanho muito grande! (" . $arquivo["size"] . " x " . $tamanho . ")" ;
	}

	if (strlen ($msg_erro) == 0 AND strlen($arquivo["tmp_name"]) == 0) {
		 if($sistema_lingua=='ES') $msg_erro = "¡Archivo no fue enviado!";
		else $msg_erro = "Arquivo não foi enviado!!!";
	}

	if (strlen ($msg_erro) == 0 AND strtolower ($arquivo["name"]) <> "ordens.txt" ) {
		 if($sistema_lingua=='ES') $msg_erro = "Nombre del archivo debe ser <b>ordens.txt</b>";
		else $msg_erro = "Nome do arquivo deve ser <b>ordens.txt</b>";
	}

	if (strlen ($msg_erro) == 0 AND !preg_match("/\/(plain)$/", $arquivo["type"])){
		 if($sistema_lingua=='ES') $msg_erro = "¡Archivo en el formato inválido!";
		else $msg_erro = "Arquivo em formato inválido!<br>" . $arquivo["type"];
	}


	$tamanho_arquivo = $arquivo["size"] ;

	$sql = "select upload_os, to_char((data - current_timestamp - interval '1 minute'), 'ss') as tempo
			from tbl_upload_os
			where fabrica= $login_fabrica
				AND posto=$login_posto
				AND data > current_timestamp - interval '1 minute'
				AND tamanho_arquivo = '$tamanho_arquivo'::int;";
	$res = pg_exec($con,$sql);


	if (strlen($msg_erro) == 0 and pg_numrows($res)==0) {


		$sql = "select upload_os
				from tbl_upload_os
				where fabrica= $login_fabrica
					AND posto=$login_posto
					AND executado IS NULL;";
		$res = pg_exec($con,$sql);

		if(pg_numrows($res)> 15){
			$msg_erro= "Você já atingiu o limite máximo de 10 arquivos enviados. Qualquer dúvida ligar para Telecontrol.";
		}else{


			$email = $_POST['email'];

			$sql = "INSERT INTO tbl_upload_os(fabrica, posto, data, programa, titulo, tamanho_arquivo, email)
			VALUES ($login_fabrica, $login_posto,current_timestamp, '$PHP_SELF','$title', $tamanho_arquivo, '$email')";
			$res = pg_exec($con,$sql);

			$sql = " select currval ('upload_os_seq');";
			$res = pg_exec($con,$sql);
			$upload_os = pg_result($res,0,0);

			$data_inicio= date("Y-m-d-H-i-s");
			$nome_destino = "os_upload/" .$login_posto . "-". $login_fabrica. "-upload_os_nao_concluido_".$upload_os.".txt" ;

			$sql = "UPDATE tbl_upload_os set arquivo='$nome_destino' where upload_os = $upload_os;";
			$res = pg_exec($con,$sql);

			if (copy ($arquivo["tmp_name"], $nome_destino )) {
				$msg_ok = "Arquivo enviado com sucesso. Será processado de noite o arquivo, e em seguida enviado um email com o retorno.";
			}else{
				$msg_erro = "Erro ao gravar arquivo no servidor";
				$sql = "DELETE FROM tbl_upload_os where upload_os = $upload_os;";
				$res = pg_exec($con,$sql);
			}
		}
	}else{
		if(pg_numrows($res)>0){
			$tempo= (pg_result($res,0,tempo) +60);
			$msg_erro= "Por segurança, agora é necessário esperar $tempo segundos para enviar um novo arquivo de Upload ";
		}

	}
}



/* $title = Aparece no sub-menu e no título do Browser ===== */
 if($sistema_lingua=='ES') $title="Upload de Ordenes de Servicio ";
else $title = "UPLOAD de Ordem de Serviço";

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'os';

if (!$automatico){
	include "cabecalho.php";
}

?>
<style>
.table {
	border: 3px groove #C0C0C0;
	width: 500px;
}

.tr{
	background-color:#333399;
}
</style>

<BR>

<? if (!$automatico){ ?>
<TABLE border='0' class='table'>
<TR class='tr'>
	<TD align='center'><h1><font color='red'><? if($sistema_lingua=='ES') echo "¡Atención!"; else echo  "Atenção!"; ?></font ></h1></TD>
</TR>
<TR style='font-size:12px;'>
	<TD><font color='black'><? if($sistema_lingua=='ES') echo "A partir del día 14/03/2008 el Upload de Ordenes de Servicio va a ser ejecutadas por una rutina automática ."; else echo "A Partir do dia 14/03/2008 a Carga de OS será executada pela Telecontrol através de uma rotina automática."; ?></font ></TD>
</TR>
<TR style='font-size:12px;'>
	<TD><font color='black'><? if($sistema_lingua=='ES') echo "Será enviado el archivo por Servicio, procesado en la madrugada y vuelva en el email cadastrado en el Telecontrol."; else echo "O processo ocorre com o envio do arquivo pelo Posto Autorizado, carga das OSs com início às 20h e retorno do log no email do posto."; ?></font ></TD>
</TR>
<TR style='font-size:12px; font-weight:bold; text-align:center;'>
	<TD><font color='red'><? if($sistema_lingua=='ES') echo "Envie sólo una vez cada archivo."; else echo "Envie apenas uma única vez cada arquivo."; ?></font ></TD>
</TR>
</TABLE>
<? } ?>

<BR>
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

$upload_os= trim($_GET["upload_os"]);
$arquivo=   trim($_GET["arquivo"]);

#-------------------- Processamento do arquivo Linha por Linha ------------------#
if (strlen($msg_erro) == 0 and strlen ($gera_automatico) > 0 and strlen($arquivo)>0 and strlen($upload_os)>0 ) {

	echo "<table width='550' align='center' border='1'>";
	echo "<tr bgcolor='#3333FF'>";
	echo "<td align='center'><font size='-1' color='#ffffff'>OS WEB</font></td>";
	echo "<td align='center'><font size='-1' color='#ffffff'>OS POSTO</font></td>";
	echo "<td align='center'><font size='-1' color='#ffffff'>Seq.</font></td>";
	// HD 8014 Paulo colocou para aparecer nome da revenda
	echo "<td align='center'><font size='-1' color='#ffffff'>Cliente</font></td>";
	echo "<td align='center'><font size='-1' color='#ffffff'>Revenda</font></td>";
	echo "<td align='center'><font size='-1' color='#ffffff'>Série</font></td>";
	echo "<td align='center'><font size='-1' color='#ffffff'>Abertura</font></td>";
	echo "<td align='center'><font size='-1' color='#ffffff'>Status OS</font></td>";
	echo "<td align='center'><font size='-1' color='#ffffff'>Peça</font></td>";
	echo "<td align='center'><font size='-1' color='#ffffff'>Status Peça</font></td>";

	echo "</tr>";
	$nome_destino = $arquivo;
	$fp = fopen ($nome_destino,'r');
	while (!feof($fp) and $fp ) {
		$linha = fgets($fp, 2000);

		if (strlen (trim ($linha)) > 10) {

			if($login_fabrica ==20){
				list ($up_fabrica , $up_cnpj , $up_sua_os , $up_sequencial , $up_consumidor_revenda , $up_data_abertura , $up_data_fechamento , $up_produto_referencia , $up_serie , $up_cpf_cnpj , $up_consumidor_nome , $up_consumidor_fone , $up_revenda_cnpj , $up_revenda_nome , $up_revenda_fone , $up_nota_fiscal , $up_data_nf , $up_defeito_reclamado , $up_defeito_constatado , $up_causa_defeito , $up_peca_referencia , $up_qtde , $up_defeito , $up_servico_realizado , $up_tipo_atendimento , $up_segmento_atuacao , $up_promotor_treinamento , $up_satisfacao , $up_laudo, $up_subproduto , $up_posicao, $up_solucao) = explode ("\t",$linha);

			}else{
				if($login_posto==6032 or $login_posto==17708 or $login_posto==39480 or $login_posto==6359){
					list ($up_fabrica , $up_cnpj , $up_sua_os , $up_sequencial , $up_consumidor_revenda , $up_data_abertura , $up_data_fechamento , $up_produto_referencia , $up_serie , $up_cpf_cnpj , $up_consumidor_nome , $up_consumidor_fone , $up_consumidor_cidade , $up_consumidor_estado , $up_revenda_cnpj , $up_revenda_nome , $up_revenda_fone , $up_nota_fiscal , $up_data_nf , $up_defeito_reclamado , $up_defeito_constatado , $up_causa_defeito , $up_peca_referencia , $up_qtde , $up_defeito , $up_servico_realizado , $up_voltagem , $up_codigo_fabricacao , $up_type , $up_satisfacao , $up_laudo, $up_subproduto , $up_posicao, $up_solucao) = explode ("\t",$linha);
				}else{
					list ($up_fabrica , $up_cnpj , $up_sua_os , $up_sequencial , $up_consumidor_revenda , $up_data_abertura , $up_data_fechamento , $up_produto_referencia , $up_serie , $up_cpf_cnpj , $up_consumidor_nome , $up_consumidor_fone , $up_revenda_cnpj , $up_revenda_nome , $up_revenda_fone , $up_nota_fiscal , $up_data_nf , $up_defeito_reclamado , $up_defeito_constatado , $up_causa_defeito , $up_peca_referencia , $up_qtde , $up_defeito , $up_servico_realizado , $up_voltagem , $up_codigo_fabricacao , $up_type , $up_satisfacao , $up_laudo, $up_subproduto , $up_posicao, $up_solucao) = explode ("\t",$linha);
				}


				if ($up_fabrica == 3 AND strlen ($up_produto_referencia) < 6 ) {
					$up_produto_referencia = "000000" . trim ($up_produto_referencia);
					$up_produto_referencia = substr ($up_produto_referencia,strlen ($up_produto_referencia)-6);
				}
			}

			### HD 314894
			if(strpos($up_serie,'"')!== false){
				echo "<font color = 'red'>Campo número de série em formato invalido!</font>";
				exit;
			}

			if(strpos($up_nota_fiscal,'"')!== false){
				echo "<font color = 'red'>Campo nota fiscal em formato invalido!</font>";
				exit;
			}

			if(strpos($up_data_abertura,'/')!==false){
				$up_data_abertura = implode('-',array_reverse(explode('/',$up_data_abertura)));
			}

			if(strpos($up_data_fechamento,'/')!==false){
				$up_data_fechamento = implode('-',array_reverse(explode('/',$up_data_fechamento)));
			}

			if(strpos($up_data_nf,'/')!==false){
				$up_data_nf = implode('-',array_reverse(explode('/',$up_data_nf)));
			}
			### Fim

			if (strlen (trim ($up_fabrica)) > 0) {
				if($login_fabrica <> $up_fabrica){
					echo "<font color = 'red'>Fábrica do arquivo não confere com a fábrica que está logado!</font>";
					exit;
				}
			}

			if (strlen (trim ($up_fabrica)) > 0) {

				//pega o codigo do subproduto
				$sql = "SELECT produto
						FROM tbl_produto
						JOIN tbl_linha using (linha)
						WHERE fabrica = $up_fabrica
						AND referencia = '$up_subproduto'";
				$resX = pg_exec ($con,$sql);

				$subproduto = "";

				if (pg_numrows ($resX) > 0) {
					$subproduto = pg_result($resX,0,produto);
				}


				//verifica se o CNJP é do posto
				$sql = "SELECT posto
						FROM tbl_posto
						WHERE cnpj = '$up_cnpj'
						AND posto = $login_posto";
				$resX = pg_exec ($con,$sql);

				if (pg_numrows ($resX) == 0) {
					echo "</table><h1>CNPJ não confere com código do posto<br>$sql";
					exit;
				}


				$dv = substr ($up_cnpj,1,1) * substr ($up_cnpj,6,1);


				if (strlen ($up_data_fechamento) == 10) {
					$up_data_fechamento = substr ($up_data_fechamento,8,2) . "/" . substr ($up_data_fechamento,5,2) . "/" . substr ($up_data_fechamento,0,4);
				}

				if (strlen ($up_data_nf) == 10) {
					$up_data_nf = substr ($up_data_nf,8,2) . "/" . substr ($up_data_nf,5,2) . "/" . substr ($up_data_nf,0,4);
				}

				//britania inativou defeito 82 agora é 84
				if ($up_defeito == "82") $up_defeito = "74";


				//se tiver data de fechamento, armazena em um array e limpa a data antes de gravar
				//pois se tiver mais de uma peça vai fechar a OS no primeiro envio
				if (strlen($up_data_fechamento) > 0) {

					//verifica se esta os ja esta no array
					$os_repetida = 'f';
					for($i=0; $i<count($fechamento_fabrica); $i++) {
						if ( ($os_repetida == 'f') and ( ($fechamento_fabrica[$i] == $up_fabrica) and ($fechamento_cnpj[$i] == $up_cnpj) and ($fechamento_data_abertura[$i] == $up_data_abertura) and ($fechamento_serie[$i] == $up_serie) ) ) {
							$os_repetida = 't';
						}
					}

					//se ainda nao esta no array de fechamento inclui
					if ($os_repetida == 'f') {
						$fechamento_fabrica[]         = $up_fabrica;
						$fechamento_cnpj[]            = $up_cnpj;
						$fechamento_data_abertura[]   = $up_data_abertura;
						$fechamento_serie[]           = $up_serie;
						$fechamento_data_fechamento[] = $up_data_fechamento;
						$fechamento_sua_os_offline[]  = $up_sua_os;

						/*IGOR HD 10693 - 28/12/2007 */
						if(strlen($up_nota_fiscal) < 6){
							$sql = "SELECT LPAD('$up_nota_fiscal',6, '0');";
							$res_nf = pg_exec ($con,$sql);
							if (pg_numrows($res_nf) > 0) {
								$xup_nota_fiscal = trim(pg_result($res_nf,0,0));
							}
						}else{
							$sql = "SELECT substr('$up_nota_fiscal',length('$up_nota_fiscal')-5,length('$up_nota_fiscal'));";
							$res_nf = pg_exec ($con,$sql);
							if (pg_numrows($res_nf) > 0) {
								$xup_nota_fiscal = trim(pg_result($res_nf,0,0));
							}
						}
						$fechamento_nota_fiscal[]     = $xup_nota_fiscal;
					}
				}

if($login_fabrica == 3){
				$up_consumidor_estado = "SP";
				$up_consumidor_cidade = "(OS VIA UPLOAD)";
				//$up_consumidor_revenda = "C";
}

				$data_string  = "fabrica=$up_fabrica&";
				$data_string .= "cnpj=$up_cnpj&";
				$data_string .= "dv=$dv&";
				$data_string .= "sua_os=$up_sua_os&";
				$data_string .= "sequencial=$up_sequencial&";
				$data_string .= "consumidor_revenda=$up_consumidor_revenda&";
				$data_string .= "data_abertura=$up_data_abertura&";
				$data_string .= "data_fechamento=&";
				$data_string .= "produto_referencia=$up_produto_referencia&";
				$data_string .= "serie=$up_serie&";
				$data_string .= "cpf_cnpj=$up_cpf_cnpj&";
				$data_string .= "consumidor_nome=$up_consumidor_nome&";
				$data_string .= "consumidor_fone=$up_consumidor_fone&";
if($login_fabrica == 3 or $login_posto == 6032 or $login_posto == 17708 or $login_posto==39480 or $login_posto == 6359){
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

			/*ADICIONADO PARA A BOSCH - IGOR - HD 10450*/
				$data_string .= "tipo_atendimento=$up_tipo_atendimento&";
				$data_string .= "segmento_atuacao=$up_segmento_atuacao&";
				$data_string .= "promotor_treinamento=$up_promotor_treinamento&";
				$data_string .= "sua_os_offline=$up_sua_os&";

				$data_string .= "satisfacao=$up_satisfacao&";
				$data_string .= "laudo=$up_laudo&";
				$data_string .= "subproduto=$subproduto&";
				$data_string .= "posicao=$up_posicao&";
				$data_string .= "solucao_os=$up_solucao&";
$data_string_x = $data_string ;
//echo $data_string_x ;




/*TESTE DE ENVIO - IGOR*/
/*
$email_origem  = "igor@telecontrol.com.br";
$email_destino = "igor@telecontrol.com.br";

$corpo.="<br>\n";
$corpo.="<br>_______________________________________________\n";
$corpo.="<br>DATA_STRING: $data_string_x <br>\n";
$corpo.="<br>nota_fiscal=$up_nota_fiscal&<br>\n";
$corpo.="<br>nota_fiscal= $xnota_fiscal &<br>\n";
$corpo.="<br><br>Telecontrol\n";
$corpo.="<br>www.telecontrol.com.br\n";

$body_top = "--Message-Boundary\n";
$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
$body_top .= "Content-transfer-encoding: 7BIT\n";
$body_top .= "Content-description: Mail message body\n\n";
@mail($email_destino, stripslashes("email 1"), $corpo, "From: ".$email_origem." \n $body_top " );

*/




//echo $data_string."<br><br>";

				#---------------- Postando Dados --------------
				$referer  = $_SERVER["SCRIPT_URI"];

				$URL_Info = parse_url("http://ww2.telecontrol.com.br/assist/os_post_sem_sua_os.php");

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
					//$retorno_=$retorno ;
				}
				fclose($post);

				$setou_erro = 'f';

				#--------------- Retorno OS por OS ---------------#
				$os_web = substr ($retorno, strpos ($retorno,"<OK-I>")+6, 30) ;
				$os_web = trim (substr ($os_web , 0, strpos ($os_web ,"<OK-F><!--OFFLINE-F-->")));





				$status_os = "<!-- $retorno -->";
				if (strlen ($os_web) > 0 ) {
					$status_os = "OK";
				}else{
					if (strpos ($status_os ,"<ERRO-I>") <> 0) {

						//pega o erro da os
						$status_os = substr ($status_os, strpos($status_os,"<ERRO-I>")+8, 300) ;
						$status_os = "ERRO ".trim(substr ($status_os , 0, strpos ($status_os,"<ERRO-F>")));

						if (strpos($status_os,"data_nf_superior_data_abertura") <> 0) {
							$status_os = "Data da nota fiscal não pode ser superior a data de abertura da OS";
						}


						//se for erro de os ja fechada, pega o numero da os no erro para imprimir
						if (strpos ($status_os ," foi fechada anteriormente") <> 0) {
							$os_web = substr ($status_os, strpos($status_os,"ERRO Erro encontrado: OS ")+25, 10) ;
							$os_web = trim(substr ($os_web , 0, strpos($os_web," foi fechada anteriormente")-2));
						}
					}


					//seta erro no array para esta OS
					for($i=0; $i<count($fechamento_fabrica); $i++) {
						if ( ($fechamento_fabrica[$i]       == $up_fabrica) and
							 ($fechamento_cnpj[$i]          == $up_cnpj) and
							 ($fechamento_data_abertura[$i] == $up_data_abertura) and
							 ($fechamento_serie[$i]         == $up_serie) ) {

							$fechamento_erro_os[$i] = "ERRO";
							$fechamento_erro_os_motivo[$i]="Erro 1 - ";
							$setou_erro = 't';
						}
					}
				}

//				if (strpos ($retorno,"JA CADASTRADA OS WEB") > 0) $status_os = "OK (anterior)";

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

							//seta erro no array para esta OS
							for($i=0; $i<count($fechamento_fabrica); $i++) {
								if ( ($fechamento_fabrica[$i] == $up_fabrica) and ($fechamento_cnpj[$i] == $up_cnpj) and ($fechamento_data_abertura[$i] == $up_data_abertura) and ($fechamento_serie[$i] == $up_serie) ) {
									$fechamento_erro_os[$i] = "ERRO";
									$fechamento_erro_os_motivo[$i]="ERRO:". $status_peca . $causa_erro ;
									$setou_erro = 't';
								}
							}
							$status_peca = "ERRO " . $status_peca . $causa_erro ;
						}
					}
				}

				//se não setou erro, seta ok para ficar na mesma posicao dos dados do array
				if (strlen($up_data_fechamento) > 0 and $setou_erro <> 't') {
					for($i=0; $i<count($fechamento_fabrica); $i++) {
						if (($fechamento_erro_os[$i] <> "ERRO") and ($fechamento_fabrica[$i] == $up_fabrica) and ($fechamento_cnpj[$i] == $up_cnpj) and ($fechamento_data_abertura[$i] == $up_data_abertura) and ($fechamento_serie[$i] == $up_serie) ) {
							$fechamento_erro_os[$i] = "OK";
							$fechamento_erro_os_motivo[$i]="OK";
						}
					}
				}


				if (strlen ($up_peca_referencia) == 0) {
					$status_peca = "&nbsp;";
					$up_peca_referencia = "&nbsp;";
				}


				if (strlen ($up_data_abertura) == 10) {
					$up_data_abertura = substr ($up_data_abertura,8,2) . "/" . substr ($up_data_abertura,5,2) . "/" . substr ($up_data_abertura,0,4);
				}

				//hd 5733
				if (strlen($os_web) >0) {
					$sql = "SELECT sua_os
							FROM tbl_os
							WHERE fabrica = $login_fabrica
							AND posto = $login_posto
							AND os = $os_web";
					$res = pg_exec($con, $sql);

					if (pg_numrows($res) > 0) $sua_os = pg_result($res,0,0);
				} else {
					$sua_os = "";
				}


				echo "<tr style='font-size:10px'>";

				echo "<td>";

				//hd 5733
				//echo $os_web;
				echo $sua_os;
/*				if(strlen($sua_os)==0){
					echo "TESTE: ".trim(substr ($status_os,
						strpos($status_os,"foi fechada anteriormente")-12
						,
						strpos($status_os,"foi fechada anteriormente")-2
						));
				}
*/
				echo "</td>";

				echo "<td>";
				echo $up_sua_os;
				echo "</td>";

				echo "<td>";
				echo $up_sequencial;
				echo "</td>";

				if($up_consumidor_revenda == 'R'){
					//SE FOR REVENDA NÃO MOSTRA O CLIENTE
					echo "<td nowrap>&nbsp;";
					echo "</td>";
				}else{
					echo "<td nowrap>&nbsp;";
					echo $up_consumidor_nome;
					echo "</td>";
				}

				//8014 Paulo colocou
				echo "<td nowrap>&nbsp;";
				echo $up_revenda_nome;
				echo "</td>";

				echo "<td>";
				echo $up_serie;
				echo "</td>";

				echo "<td>";
				echo $up_data_abertura;
				echo "</td>";

				echo "<td nowrap>";
				echo $status_os;
				echo "</td>";

				echo "<td nowrap>";
				echo $up_peca_referencia;
				echo "</td>";

				echo "<td nowrap>&nbsp;";
				echo $status_peca;
				echo "</td>";

				echo "</tr>";
			}else{
				echo "<tr style='font-size:10px'>";

				echo "<td>";
				echo $up_sua_os ;
				echo "</td>";

				echo "<td colspan='9'>";
				echo "Não foi passado o número do fabricante<br>";
				echo "</td>";
				echo "</tr>";
			}
			flush();
		}
	}
	fclose($fp);


	$arq_final = str_replace ( "upload_os_nao_concluido", "bkp", $nome_destino). "_ini_". $data_inicio."_fim_".date("Y-m-d-H-i-s") . ".bkp" ;
	if (copy ($nome_destino, $arq_final ) )  {
		//$msg_ok = "Arquivo enviado com sucesso, aguarde até que a página seja carregada completamente.";
	}else{
		$msg_erro = "Erro ao gravar arquivo no servidor.";
	}


//	$os_web = pg_result($res,0,os);

	if(strlen($msg_erro)==0){
		unlink ($nome_destino);

		/*ATUALIZA QUANDO O UPLOAD FOR CONCLUIDO*/
		$sql = "UPDATE tbl_upload_os SET executado = CURRENT_TIMESTAMP
			WHERE upload_os = $upload_os";

		$res = pg_exec ($con,$sql);
	}

	echo "</table>";



	echo "<BR>";
	echo "<table width='550' align='center' border='1'>";
	echo "<tr bgcolor='#3333FF'>";
	echo "<td align='center'><font size='-1' color='#ffffff'>OS</font></td>";
	echo "<td align='center'><font size='-1' color='#ffffff'>Fechamento</font></td>";
	echo "<td align='center'><font size='-1' color='#ffffff'>Status Fechamento</font></td>";

	//faz os fechamentos a partir dos dados do array
	for($i=0; $i<count($fechamento_fabrica); $i++){
		if ($fechamento_erro_os[$i] <> 'ERRO') {
			$xdata_fechamento = substr ($fechamento_data_fechamento[$i],6,4) . "-" . substr ($fechamento_data_fechamento[$i],3,2) . "-" . substr ($fechamento_data_fechamento[$i],0,2);

			$sql = "SELECT os
					FROM tbl_os
					WHERE fabrica = $fechamento_fabrica[$i]
					AND posto = (SELECT posto FROM tbl_posto WHERE cnpj='$fechamento_cnpj[$i]')
					AND UPPER(TRIM(sua_os_offline)) = UPPER(TRIM('$fechamento_sua_os_offline[$i]'))
					AND data_abertura = '$fechamento_data_abertura[$i]'
					AND data_fechamento IS NULL
					AND sua_os_offline is not null";
			$res = @pg_exec ($con,$sql);


			if (pg_numrows($res) == 0 ){
				//VERIFICA SE OS JA EXISTE POR data_abertura + serie + posto + nota
				if($fabrica == 3 and ($posto ==1537 OR $posto ==6359)) {
					$sql = "SELECT os 
							FROM tbl_os 
							WHERE fabrica = $fechamento_fabrica[$i]
								AND posto = (SELECT posto FROM tbl_posto WHERE cnpj='$fechamento_cnpj[$i]')
								AND data_abertura = '$fechamento_data_abertura[$i]'
								AND upper(substr(nota_fiscal,length(nota_fiscal)-5,length(nota_fiscal))) = upper('$fechamento_nota_fiscal[$i]')
								AND upper(serie) = upper('$fechamento_serie[$i]')
								AND data_fechamento IS NULL ;";
					$res = pg_exec ($con,$sql);
				}else{
					$sql = "SELECT os 
							FROM tbl_os 
							WHERE fabrica = $fechamento_fabrica[$i]
							AND posto = (SELECT posto FROM tbl_posto WHERE cnpj='$fechamento_cnpj[$i]')
							AND data_abertura = '$fechamento_data_abertura[$i]'
							AND upper(substr(nota_fiscal,length(nota_fiscal)-5,length(nota_fiscal))) = upper('$fechamento_nota_fiscal[$i]')
							AND upper(serie) = upper('$fechamento_serie[$i]')
							AND data_fechamento IS NULL ;";
					$res = pg_exec ($con,$sql);
				}

			}


/* HD: 128849 - IGOR 05/08/2009
			$sql = "SELECT os
					FROM tbl_os
					WHERE fabrica = $fechamento_fabrica[$i]
					AND posto = (SELECT posto FROM tbl_posto WHERE cnpj='$fechamento_cnpj[$i]')
					AND data_abertura = '$fechamento_data_abertura[$i]'
					AND upper(serie) = upper('$fechamento_serie[$i]')
					AND data_fechamento IS NULL";
			$res = @pg_exec ($con,$sql);
*/
			$os_web = @pg_result($res,0,os);

			if (pg_numrows ($res) > 0) {
				echo "<!-- INICIO FECHAMENTO OS -->";

				$res = pg_exec ($con,"BEGIN TRANSACTION");
				$sql = "UPDATE tbl_os SET data_fechamento = '$xdata_fechamento'::date WHERE tbl_os.os = $os_web";
				$res       = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage ($con);

				$sql = " UPDATE tbl_os SET data_conserto = data_fechamento WHERE data_conserto  IS NULL and data_fechamento IS NOT NULL AND os = $os_web;
					";
				$res = pg_exec($con,$sql);
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
			$status_fechamento = "ERRO: Verifique Status da OS e Status da Peça - Complemento: ".$fechamento_erro_os_motivo[$i];
		}

		$sql = "SELECT os, sua_os
				FROM tbl_os
				WHERE fabrica = $fechamento_fabrica[$i]
				AND posto = (SELECT posto FROM tbl_posto WHERE cnpj='$fechamento_cnpj[$i]')
				AND data_abertura = '$fechamento_data_abertura[$i]'
				AND upper(serie) = upper('$fechamento_serie[$i]')";
		$res = pg_exec ($con,$sql);
		$os_web = @pg_result($res,0,os);

		//hd 5733
		$sua_os = @pg_result($res,0,sua_os);

		echo "<tr style='font-size:10px'>";
		echo "<td nowrap>";
		//hd 5733
		//echo $os_web;
		echo $sua_os;
		echo "</td>";

		echo "<td>";
		echo $up_data_fechamento;
		echo "</td>";

		echo "<td>";
		echo $status_fechamento;
		echo "</td>";

		echo "</tr>";

	}
	//

	echo "</table>";

}
//echo "<font color = 'red'>aqui: $data_string_x</font>"

?>

<form enctype = "multipart/form-data" name="frm_upload" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="MAX_FILE_SIZE" value="500000">

<span style='font-size:12px; font-weight:bold;'><? if($sistema_lingua=='ES') echo "ARCHIVO QUE CONTÉN SUS ÓRDENES DE SERVICIO"; else echo "ARQUIVO CONTENDO SUAS ORDENS DE SERVIÇO"; ?></span>
<br><BR>

<input type='file' name='arquivo' size='30'>
<br><BR>

<span style='font-size:11px; font-weight:bold;'>Email :</span>
<input type='text' value='' name='email' size='30'>
<br>

<div style='width: 550px; margin:5px; padding:5px; background-color:#FFCCCC; font-size:11px; font-weight:bold;'>O campo email é usado para postos que mais de uma pessoa faz Upload de OS. <BR> Se deixar em branco será enviado no email do posto cadastrado na Telecontrol.</div>

<br>
<input type='hidden' value='' name='enviar'>
<input type='button' value='Enviar' name='acao' onClick="
	javascript:
	if (document.frm_upload.enviar.value==''){
		document.frm_upload.enviar.value = 'enviar';
		document.frm_upload.submit();
	}else{
		alert('Aguarde submissão.');
	}
">
</form>


<p>

<table class='table'  align='center' >
<tr class='tr'>
	<td align='center'>
		<font size='-1' color='#ffffff'><b>
		<?if($sistema_lingua=='ES') echo "Definiciones para enviar las Órdenes de Servicio por el archivo";
		else echo "Definições para envio de Ordens de Serviço por Arquivo"; ?>
		</b></font>
	</td>
</tr>

<tr>
	<td style='font-size:12px'>
		<ul>
		<li><? if($sistema_lingua=='ES') echo "El archivo debe tener el nombre de <b>ordens.txt</b> en letras muy pequeñas(en menusculas)"; else echo "O arquivo deve ter o nome <b>ordens.txt</b> em letras minúsculas"; ?>
		<li><? if($sistema_lingua=='ES') echo "Los campos deben ser separados por el <b>TAB</b>"; else echo "Os campos devem ser separados por <b>TAB</b> chr(9)";?>
		<li><? if($sistema_lingua=='ES') echo "	Repetir el registro cuando la orden de servicio tiene más que una pieza"; else echo "Repetir o registro quando a Ordem de Serviço tiver mais de uma peça"; ?>
		<li><? if($sistema_lingua=='ES') echo "	Enviar la orden de servicio hasta que el sitio confirma su cierre"; else echo " Enviar a Ordem de Serviço até que o site confirme seu fechamento";?>
		<li><? if($sistema_lingua=='ES') echo "	El espacio FÁBRICA debe venir llenado del número <b>$login_fabrica</b>"; else echo " O campo FÁBRICA deve vir preenchido com o número <b>$login_fabrica</b>"; ?>
		<li><? if($sistema_lingua=='ES') echo "	ID 1 debe venir sin el formato, con 14 posiciones "; else echo "CNPJ deve vir sem formatação, com 14 posições ";?>
		<li><? if($sistema_lingua=='ES') echo "	ID cliente debe venir sin el formato, con 11 posiciones"; else echo " CPF deve vir sem formatação, com 11 posições";?>
		<li><? if($sistema_lingua=='ES') echo "	Todas las fechas deben venir en el formato YYYY-MM-DD"; else echo " Todas as datas devem vir no formato YYYY-MM-DD"; ?>
		<li><? if($sistema_lingua=='ES') echo "	No enviar fechas en blanco de las fechas, respetar solamente el tabulación"; else echo " Não enviar datas em branco, apenas respeitar a tabulação";?>
		<p>
		<li><? if($sistema_lingua=='ES') {
			echo "	Baje acá un <a href='os-upload-bosch-es.xls'>ejemplo</a> en el formato Excel";
		}else{
			echo " Baixe aqui um ";
			if($login_fabrica ==20) {
				echo "<a href='os-upload-bosch.xls'>";
			}else{
				echo "<a href='os-upload.xls'>";
			}
			echo "exemplo</a> em Excel"; }
		?>
		<li><? if($sistema_lingua=='ES') echo "	Recuerda aquí exportar el archivo en el formato del texto, limitado por la tecla TAB"; else echo "Lembre-se de exportar o arquivo em formato texto, delimitado por <b>TAB</b>";?>
		</ul>
	</td>
</tr>

<tr class='tr'>
	<td align='center'>
		<font size='-1' color='#ffffff'><b><?if($sistema_lingua=='ES') echo "Lay-Out del Archivo"; else echo "Lay-Out do Arquivo"; ?>
		</b></font>
	</td>
</tr>
<tr>
	<td style='font-size:12px'>
		<b><?if($sistema_lingua=='ES') echo "Dependiendo del fabricante, algunos espacios son obligatorios"; else echo "Dependendo do Fabricante, alguns campos são obrigatórios"; ?></b>
		<p>

		<ul>
		<li> <?if($sistema_lingua=='ES') echo "Código del fabricante ( $login_fabrica )"; else echo "Código do Fabricante ( $login_fabrica )"; ?>
		<li> <?if($sistema_lingua=='ES') echo "ID1 del taller "; else echo "CNPJ do Posto Autorizado"; ?>
		<li> <?if($sistema_lingua=='ES') echo "Número de la Orden de Servicio"; else echo "Número da Ordem de Serviço"; ?>
		<li> <?if($sistema_lingua=='ES') echo "Secuencial en caso de la orden de servicio de Tienda"; else echo "Sequencial em caso de Ordem de Serviço de Revenda";?>
		<li> <?if($sistema_lingua=='ES') echo "\"C\" para la orden de servicio del consumidor y \"R\" para Tienda"; else echo "\"C\" para Ordem de Serviço de Consumidor e \"R\" para Revenda";?>
		<li> <?if($sistema_lingua=='ES') echo "Fecha de la abertura"; else echo "Data da abertura";?>
		<li> <?if($sistema_lingua=='ES') echo "Fecha del cierre (nulo si aún no esta toda cerrada)"; else echo "Data de fechamento (nulo se ainda não fechada)";?>
		<li> <?if($sistema_lingua=='ES') echo "referencia del producto"; else echo "Referência do Produto";?>
		<li> <?if($sistema_lingua=='ES') echo "Número serial"; else echo "Número de Série";?>
		<li> <?if($sistema_lingua=='ES') echo "ID 1 o ID del cliente"; else echo "CPF ou CNPJ do Consumidor";?>
		<li> <?if($sistema_lingua=='ES') echo "Nombre del cliente"; else echo "Nome do Consumidor";?>
		<li> <?if($sistema_lingua=='ES') echo "Teléfono del cliente"; else echo "Telefone do Consumidor";?>
		<li> <?if($sistema_lingua=='ES') echo "ID1 de la Tienda"; else echo "CNPJ da Revenda";?>
		<li> <?if($sistema_lingua=='ES') echo "Nombre de la Tienda"; else echo "Nome da Revenda";?>
		<li> <?if($sistema_lingua=='ES') echo "Teléfono de la Tienda"; else echo "Fone da Revenda";?>
		<li> <?if($sistema_lingua=='ES') echo "Número de la factura de compra"; else echo "Número da Nota Fiscal de Compra";?>
		<li> <?if($sistema_lingua=='ES') echo "Fecha de compra"; else echo "Data da Compra";?>
		<li> <?if($sistema_lingua=='ES') echo "Código del defecto protestado"; else echo "Código do Defeito Reclamado";?>
		<li> <?if($sistema_lingua=='ES') echo "Código del defecto constatado"; else echo "Código do Defeito Constatado";?>
		<li> <?if($sistema_lingua=='ES') echo "Código de la causa del defecto"; else echo "Código da Causa do Defeito";?>
		<p>
		<li> <?if($sistema_lingua=='ES') echo "Referencia de la pieza"; else echo "Referência da Peça";?>
		<li> <?if($sistema_lingua=='ES') echo "Cuantidad cambiada de la pieza"; else echo "Quantidade trocada da Peça";?>
		<li> <?if($sistema_lingua=='ES') echo "Código de Defecto de la pieza"; else echo "Código do Defeito da Peça";?>
		<li> <?if($sistema_lingua=='ES') echo "Código de servicio hecho"; else echo "Código do Serviço Realizado";?>
		<li> <?if($sistema_lingua=='ES') echo "Código de solución (utilice la misma tabla del servicio hecho)"; else echo "Código da Solução (Utilizar a mesma tabela do Serviço Realizado)";?>

		<? if ($login_fabrica == 1) { ?>
		<p>
		<li> Voltagem do Aparelho
		<li> Código de Fabricação
		<li> Type
		<li> Satisfação DeWALT ("S" se aplicável, ou "N")
		<li> Laudo Técnico (obrigatório para Satisfação DeWALT. Não enviar <b>ENTER</b>
		<? } ?>

		</ul>
	</td>
</tr>

<tr class='tr'>
	<td align='center'>
		<font size='-1' color='#ffffff'><b><?if($sistema_lingua=='ES') echo "Tabla necesaria para la integración"; else echo "Tabela Necessárias para Integração"; ?>
		</b></font>
	</td>
</tr>

<tr>
	<td style='font-size:12px'>
		<b> <?if($sistema_lingua=='ES') echo "Contenido del  Archivo"; else echo "Conteúdo do arquivo compactado"; ?> </b>
		<ul>
		<li> <?if($sistema_lingua=='ES') echo "Archivo Excel con las siguientes carpetas:";else echo "Planilha EXCEL com as seguintes pastas:"; ?>
		<ul>
			<li> <?if($sistema_lingua=='ES') echo "Registro de productos ";else echo "Cadastro de Produtos"; ?>
			<li> <?if($sistema_lingua=='ES') echo "Registro de repuesto";else echo "Cadastro de Peças"; ?>
			<li> <?if($sistema_lingua=='ES') echo "Lista básica de los productos";else echo "Lista Básica dos Produtos"; ?>
			<li> <?if($sistema_lingua=='ES') echo "Tabla del defecto protestado";else echo "Tabela de Defeito Reclamado"; ?>
			<li> <?if($sistema_lingua=='ES') echo "Tabla del defecto constatado ";else echo "Tabela de Defeito Constatado"; ?>
			<li> <?if($sistema_lingua=='ES') echo "Tabla con las causas del defecto";else echo "Tabela de Causas de Defeito"; ?>
			<li> <?if($sistema_lingua=='ES') echo "Tabla del defecto de las piezas";else echo "Tabela de Defeitos de Peças"; ?>
			<li> <?if($sistema_lingua=='ES') echo "Tabla de servicios hechos";else echo "Tabela de Serviços Realizados"; ?>
		</ul>
		</ul>
		<p>
		<center><a href='tabela_os_upload_xls.php'><b><?if($sistema_lingua=='ES') echo "Download de archivos"; else echo "Download dos arquivos"; ?></b></a></center>
		<p>
	</td>
</tr>

</table>


<?//echo "retorno: $retorno_";?>
<p>

<? include "rodape.php";?>
