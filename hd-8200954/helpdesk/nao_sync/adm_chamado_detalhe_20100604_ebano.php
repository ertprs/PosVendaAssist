<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica<>10){
	header ("Location: index.php");
}

$atualiza_hd = $_GET['atualiza_hd'];
if(strlen($atualiza_hd)==0){
	$atualiza_hd = $_POST['atualiza_hd'];
}

if(strlen($atualiza_hd)>0){
	$hd   = $_GET['hd'];
	$hr   = $_GET['hr'];
	$prazo= $_GET['prazo'];
	if(strlen($hd)>0 and strlen($hr)>0){
		$sql = "UPDATE tbl_hd_chamado set hora_desenvolvimento = $hr
				where hd_chamado = $hd
				and fabrica_responsavel = $login_fabrica";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		
		echo (strlen($msg_erro)==0) ? "Atualizado com Sucesso!" : "Ocorreu o seguinte erro $msg_erro";
	}
	if(strlen($hd)>0 and strlen($prazo)>0){
		$sql = "UPDATE tbl_hd_chamado set prazo_horas= $prazo 
				where hd_chamado = $hd
				and fabrica_responsavel = $login_fabrica";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		echo (strlen($msg_erro)==0) ? "Prazo atualizado!" : "Ocorreu o seguinte erro $msg_erro";
	}
	exit;
}

$atualiza_previsao_termino = trim($_GET['atualiza_previsao_termino']);
if(strlen($atualiza_previsao_termino)==0){
	$atualiza_previsao_termino = trim($_POST['atualiza_previsao_termino']);
}

if(strlen($atualiza_previsao_termino)>0){
	$hd   = trim($_GET['hd']);
	$data = trim($_GET['data_previsao']);
	if(strlen($hd)>0 and strlen($data)>15 AND $data != "____-__-__ __:__"){
		$Xdata = substr($data,6,4)."-".substr($data,3,2)."-".substr($data,0,2)." ".substr($data,11,5);
		$sql = "UPDATE tbl_hd_chamado SET previsao_termino = '$Xdata'
				WHERE hd_chamado = $hd
				AND fabrica_responsavel = $login_fabrica";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		echo (strlen($msg_erro)==0) ? "Atualizado com Sucesso!" : "Ocorreu o seguinte erro: $msg_erro";
	}
	exit;
}

//HD 7277 Paulo - tirar acento do arquivo upload
function retira_acentos( $texto ){
 $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
 $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
 return str_replace( $array1, $array2, $texto );
}

if($_POST['hd_chamado']) $hd_chamado = trim ($_POST['hd_chamado']);
if($_GET ['hd_chamado']) $hd_chamado = trim ($_GET ['hd_chamado']);

if($_POST['btn_tranferir']) $btn_tranferir = trim ($_POST['btn_tranferir']);

if (strlen ($btn_tranferir) > 0) {
if($_POST['transfere'])           { $transfere         = trim ($_POST['transfere']);}
		$data_resolvido = "";
		if($status == 'Resolvido'){
			$data_resolvido = " data_resolvido = current_timestamp ,";
		}
		$sql =" UPDATE tbl_hd_chamado
				SET status = '$status' ,
					$data_resolvido
					atendente = $transfere
				WHERE hd_chamado = $hd_chamado";
		$res = pg_exec ($con,$sql);
}

if(strlen($hd_chamado)>0){
	$sql =" select sum(case when data_termino is null then current_timestamp else data_termino end - data_inicio ) from tbl_hd_chamado_atendente where hd_chamado = $hd_chamado;";
	$res = pg_exec($con, $sql);
	if(pg_numrows($res)>0)
	$horas= pg_result ($res,0,0);
}


if(strlen($_POST['btn_telefone']) > 0){ // HD 39347

	$res = pg_exec($con,"BEGIN TRANSACTION");

	$sql =" SELECT hd_chamado_item
		FROM tbl_hd_chamado_item
		WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
			AND termino IS NULL
		ORDER BY hd_chamado_item desc
		LIMIT 1 ;";

	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);
	if(pg_numrows($res)>0){

		$hd_chamado_item = pg_result($res,0,hd_chamado_item);

		//Faz update do hd_chamado_item com o tempo de execução da ultima interação.
		$sql =" UPDATE tbl_hd_chamado_item
				SET termino = current_timestamp
				WHERE hd_chamado_item in(SELECT hd_chamado_item
							 FROM tbl_hd_chamado_item
							 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
								AND termino IS NULL
							 ORDER BY hd_chamado_item desc
							 LIMIT 1 );";

		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	$sql ="select	hd_chamado_atendente  ,
					hd_chamado            ,
					data_termino
			from tbl_hd_chamado_atendente
			where admin = $login_admin
			order by data_termino desc
			limit 1";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
		$xhd_chamado           = pg_result($res,0,hd_chamado);
		$data_termino          = pg_result($res,0,data_termino);
		$hd_chamado_atendente  = pg_result($res,0,$hd_chamado_atendente);
		if(strlen($data_termino)==0){/*atendente estava trabalhando com algum chamado*/

			$sql =	"INSERT INTO tbl_hd_chamado_item (
							hd_chamado                   ,
							comentario                   ,
							interno                      ,
							admin                        ,
							data                         ,
							termino                      ,
							atendimento_telefone
						) VALUES (
							$xhd_chamado                                                  ,
							'Chamado interrompido para atendimento de telefone'           ,
							't'                                                           ,
							$login_admin                                                  ,
							current_timestamp                                             ,
							current_timestamp                                             ,
							't'
						);";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			if(strlen($msg_erro)==0){
				$sql = "update tbl_hd_chamado_atendente set data_termino=current_timestamp
						where hd_chamado_atendente = $hd_chamado_atendente";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}
	$sql = "INSERT INTO tbl_hd_chamado_atendente(
				hd_chamado ,
				admin      ,
				data_inicio,
				atendimento_telefone
				)VALUES(
				$xhd_chamado       ,
				$login_admin       ,
				CURRENT_TIMESTAMP  ,
				't'
				)";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

	if(strlen($msg_erro) > 0){
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
	}else{
		$res = @pg_exec($con,"COMMIT");
		header ("Location: $PHP_SELF?hd_chamado=$xhd_chamado");
	}
} // HD 39347

if (strlen ($btn_acao) > 0) {

	if($_POST['comentario'])          { $comentario      = trim ($_POST['comentario']);}
	if($_POST['transfere'])           { $transfere         = trim ($_POST['transfere']);}
	if($_POST['status'])              { $status          = trim ($_POST['status']);}
	if($_POST['categoria'])           { $categoria       = trim ($_POST['categoria']);}
	if($_POST['sequencia'])           { $sequencia       = trim ($_POST['sequencia']);}
	if($_POST['interno'])             { $interno         = trim ($_POST['interno']);}
	if($_POST['exigir_resposta'])     { $exigir_resposta = trim ($_POST['exigir_resposta']);}
	if($_POST['hora_desenvolvimento']){ $hora_desenvolvimento = trim($_POST['hora_desenvolvimento']);}
	if($_POST['cobrar'])              { $cobrar = trim($_POST['cobrar']);}
	if($_POST['prioridade'])          { $prioridade = trim($_POST['prioridade']);}

	if($_POST['prazo_horas'])         { $prazo_horas = trim($_POST['prazo_horas']);}
	if($_POST['tipo_chamado'])        { $tipo_chamado = trim($_POST['tipo_chamado']);}

	if(strlen($categoria)==0){
		$msg_erro = "Escolha a categoria";
	}
	$xprioridade  = ($prioridade=="t") ? "'t'" : "'f'";
	$xcobrar      = ($cobrar=="t") ? "'t'" : "'f'";
	$xprazo_horas = (strlen($prazo_horas)>0) ? "$prazo_horas" : "null";
	$xtipo_chamado= (strlen($tipo_chamado)>0) ? "$tipo_chamado" :  "null";

	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

	$sql = "SELECT categoria , status, atendente FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
	$res = pg_exec ($con,$sql);
	$categoria_anterior = pg_result ($res,0,categoria);
	$status_anterior    = pg_result ($res,0,status);
	$atendente_anterior = pg_result ($res,0,atendente);

	if (strlen($comentario) < 3)$msg_erro="Comentário muito pequeno";

	if (strlen($hora_desenvolvimento)==0){
		$hora_desenvolvimento = " NULL ";
	}

	#-------- De Análise para Execução -------
	if (strlen ($sequencia) == 0 AND $status == "Análise" AND $status_anterior == "Análise") {
		$msg_erro = "Escolha a seqüência da tarefa. Ou continua em análise, ou vai para Execução.";
	}
	if ($sequencia == "SEGUE" AND $status_anterior == "Análise") $status = "Execução" ;

	if ($sequencia == "AGUARDANDO" AND $status_anterior == "Análise") $status = "Aguard.Execução" ;

	#-------- De Execução para Resolvido -------
	if (strlen ($sequencia) == 0 AND $status == "Execução" AND $status_anterior == "Execução") {
		$msg_erro = "Escolha a seqüência da tarefa. Ou continua em execução ou está resolvido.";
	}

	if ($sequencia == "SEGUE" AND $status_anterior == "Execução") $status = "Resolvido" ;
	if ($sequencia == "SEGUE" AND $status_anterior == "Aguard.Execução") $status = "Execução" ;

	if ($status == "Novo" AND $status_anterior == "Novo") $status = "Análise";


	$sql = "Select exigir_resposta from tbl_hd_chamado where hd_chamado=$hd_chamado";
	$res = pg_exec ($con,$sql);
	$xexigir_resposta = pg_result($res,0,0);

	if (strlen($xexigir_resposta)==0) $xexigir_resposta = 'f';

	$exigir_resposta = (strlen ($exigir_resposta) > 0) ? 't' : 'f';
	$xinterno = (strlen ($interno) > 0) ? 't' : 'f';

	if (strlen($msg_erro) == 0){
		$res = pg_exec($con,"BEGIN TRANSACTION");
		//EXECUTA A ROTINA PARA SETAR O TERMINO NO HD_CHAMADO_ITEM
		$sql =" UPDATE tbl_hd_chamado_item
				SET termino = current_timestamp
				WHERE hd_chamado_item in(SELECT hd_chamado_item
							 FROM tbl_hd_chamado_item
							 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
								AND termino IS NULL
							 ORDER BY hd_chamado_item desc
							 LIMIT 1 );";
		$res = pg_exec ($con,$sql);
		if($status == 'Resolvido'){
			$data_resolvido = " data_resolvido = current_timestamp ,";
		}
		$sql =" UPDATE tbl_hd_chamado
				SET status = '$status' ,
					$data_resolvido
					atendente = $transfere,
					categoria = '$categoria',
					prioridade = $xprioridade,
					tipo_chamado = $xtipo_chamado,
					cobrar = $xcobrar ";
					if($xexigir_resposta=='f'){
						$sql .= ", exigir_resposta = '$exigir_resposta'  ";
					}
		$sql .= " WHERE hd_chamado = $hd_chamado";
		$res = pg_exec ($con,$sql);

		if ($atendente_anterior <> $transfere) {
			$transferiu = "sim";
			$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin, interno) VALUES ($hd_chamado, 'Chamado Transferido',$login_admin, 't')";
			$res = pg_exec ($con,$sql);
			$sql = "UPDATE tbl_hd_chamado set atendente = $transfere WHERE hd_chamado = $hd_chamado";
			$res = pg_exec ($con,$sql);
		}

		if ($categoria <> $categoria_anterior) {
			$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin, interno) VALUES ($hd_chamado, 'Categoria Alterada de $categoria_anterior para <b> $categoria </b>',$login_admin, 't')";
			$res = pg_exec ($con,$sql);
		}

		if ($status == "Resolvido" AND $status_anterior == "Execução") {
			#$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin) VALUES ($hd_chamado, 'Chamado resolvido. Se você não concordar com a solução basta inserir novo comentário para reabrir o chamado.',$login_admin)";
			//if($login_admin ==568)	echo "sql-9 $sql<br>";
			#$res = pg_exec ($con,$sql);
		}

		// HD 17195
		if($transferiu == "sim" and $status != "Cancelado" AND ($status_anterior == "Novo" OR $status_anterior == "Análise") ) {
			$sql="SELECT sv.email AS supervisor_email       ,
						 sv.nome_completo AS supervisor_nome,
						 sv.admin AS supervisor_admin       ,
						 admin.email                        ,
						 admin.nome_completo                ,
						 tbl_hd_chamado.status              ,
						 to_char(previsao_termino,'DD/MM/YYYY') as previsao_termino                   ,
						 titulo
					FROM tbl_hd_chamado
					JOIN tbl_admin sv on tbl_hd_chamado.fabrica=sv.fabrica
					JOIN tbl_admin admin on tbl_hd_chamado.admin= admin.admin
					WHERE sv.help_desk_supervisor IS TRUE
					AND   admin.admin                 <> 19
					AND   sv.email IS NOT NULL
					AND   previsao_termino IS NOT NULL
					AND   hd_chamado=$hd_chamado
					limit 1 ";
			$res = pg_exec ($con,$sql);
			if(pg_numrows($res) > 0){
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$supervisor_email  = pg_result($res,$x,supervisor_email);
					$supervisor_nome   = pg_result($res,$x,supervisor_nome);
					$supervisor_admin  = pg_result($res,$x,supervisor_admin);
					$admin_email       = pg_result($res,$x,email);
					$admin_nome        = pg_result($res,$x,nome_completo);
					$status            = pg_result($res,$x,status);
					$previsao_termino  = pg_result($res,$x,previsao_termino);
					$titulo            = pg_result($res,$x,titulo);

					if(strlen($supervisor_email) > 0 and strlen($admin_email) >0 ){
						$chave1 = md5($hd_chamado);
						$chave2 = md5($supervisor_admin);
						$email_origem  = "suporte@telecontrol.com.br";
						$email_destino = $supervisor_email." ; ".$admin_email;

						$assunto       = "O chamado n° $hd_chamado foi aprovado para desenvolvimento e está em estado $status ";

						$corpo = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
						<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> -
						<STRONG>$titulo</STRONG>&nbsp; </P>
						<P align=left>$admin_nome,</P>
						<P align=justify>
						Previsão do término deste chamado é $previsao_termino.
						</P>
						<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://www.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Clique aqui para ver este chamado</b></u></a>.</P>
						<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br </P>";

						$body_top = "--Message-Boundary\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
						$body_top .= "Content-transfer-encoding: 7BIT\n";
						$body_top .= "Content-description: Mail message body\n\n";

						if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
							$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
						}else{
							$msg_erro = "Não foi possível enviar o email. ";
						}
					}
				}
			}
		}

		if($status== "Aprovação" AND ($status_anterior <> "Cancelado" OR $status_anterior <> "Resolvido")){

			$res = pg_exec($con,"BEGIN TRANSACTION");
			$sql="SELECT hora_desenvolvimento,data_aprovacao
					FROM tbl_hd_chamado
					WHERE hd_chamado=$hd_chamado";
			$res = pg_exec ($con,$sql);
			if(pg_numrows($res) == 0){
				$msg_erro="Suporte, para o supervisor aprovar a execução, terá que preencher a Hora de desenvolvimento";
			}else{
				$hora_desenvolvimento = pg_result($res,0,hora_desenvolvimento);
				$data_aprovacao		  = pg_result($res,0,data_aprovacao);

				if($hora_desenvolvimento == 0 or strlen($hora_desenvolvimento)==0){
					$msg_erro="Suporte, para o supervisor aprovar a execução, terá que preencher a Hora de desenvolvimento";
				}

				if(strlen($data_aprovacao) > 0){
					$sql2="UPDATE tbl_hd_chamado set data_aprovacao = null where hd_chamado=$hd_chamado";
					$res2=pg_exec($con,$sql2);
					$msg_erro = pg_errormessage($con);

					$sql3="SELECT to_char(current_date,'MM') as mes,to_char(current_date,'YYYY') as ano";
					$res3=pg_exec($con,$sql3);
					$mes=pg_result($res3,0,mes);
					$ano=pg_result($res3,0,ano);

					$sql4=" UPDATE tbl_hd_franquia set
							hora_utilizada=(hora_utilizada-hora_desenvolvimento)
							FROM  tbl_hd_chamado
							WHERE tbl_hd_chamado.fabrica=tbl_hd_franquia.fabrica
							AND   hd_chamado=$hd_chamado
							AND   mes=$mes
							AND   ano=$ano
							AND   tbl_hd_franquia.periodo_fim is null";
					$res4 = pg_exec ($con,$sql4);
					$msg_erro = pg_errormessage($con);

				}
			}
			if(strlen($msg_erro) ==0){
				$sql=" UPDATE tbl_hd_chamado SET
						data_envio_aprovacao=current_timestamp
						WHERE hd_chamado=$hd_chamado";
				$res = pg_exec ($con,$sql);

				$sql = "SELECT nome_completo,email,tbl_admin.admin
								FROM tbl_admin
								JOIN tbl_hd_chamado ON tbl_hd_chamado.fabrica = tbl_admin.fabrica
								WHERE tbl_hd_chamado.hd_chamado    = $hd_chamado
								AND tbl_admin.help_desk_supervisor IS TRUE
								AND tbl_admin.email IS NOT NULL
								AND tbl_admin.admin                 <> 19";
				$res = pg_exec ($con,$sql);
				if (pg_numrows($res) > 0) {
					$conta = ($login_fabrica==20) ? "3" : pg_numrows($res);
					for($i =0;$i<$conta;$i++) {
						
						$supervisor_email  = pg_result($res,$i,email);
						$supervisor_nome   = pg_result($res,$i,nome_completo);
						$supervisor_adm    = pg_result($res,$i,admin);

						$chave1 = md5($hd_chamado);
						$chave2 = md5($supervisor_adm);
						$email_origem  = "suporte@telecontrol.com.br";
						$email_destino = $supervisor_email;

						$assunto       = "O chamado n° $hd_chamado está aguardando sua aprovação";

						$corpo = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
						<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> -
						<STRONG>$titulo</STRONG>&nbsp; </P>
						<P align=left>$nome,</P>
						<P align=justify>
						Precisamos de sua aprovação em faturamento de horas para continuarmos atendendo o chamado.
						</P>
						<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://www.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Clique aqui para ver este chamado</b></u></a>.</P>
						<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br </P>";

						$body_top = "--Message-Boundary\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
						$body_top .= "Content-transfer-encoding: 7BIT\n";
						$body_top .= "Content-description: Mail message body\n\n";

						if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
							$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
						}else{
							$msg_erro = "Não foi possível enviar o email. ";
						}
					}
				}
			}
			if(strlen($msg_erro) > 0){
				$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			}else{
				$res = @pg_exec($con,"COMMIT");
			}
		}


		if (strlen ($comentario) > 0) {
			$sql ="INSERT INTO tbl_hd_chamado_item (
						hd_chamado                                                   ,
						comentario                                                   ,
						admin                                                        ,
						status_item                                                  ,
						interno
					) VALUES (
						$hd_chamado                                                  ,
						'$comentario'                                                ,
						$login_admin                                                 ,
						'$status'                                                    ,
						'$xinterno'
					);";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			$res = @pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado_item')");
			$hd_chamado_item  = pg_result ($res,0,0);

			if (strlen ($msg_erro) == 0 and strlen($hd_chamado_item) > 0) {

				$config["tamanho"] = 2048000; // Tamanho máximo do arquivo (em bytes)

				if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){

					// Verifica o mime-type do arquivo
					if (!preg_match("/.*\/(pdf|msword|pjpeg|jpeg|png|gif|bmp|vnd.ms-excel|richtext|plain|html|zip|vnd.openxmlformats|vnd.ms-powerpoint)/", $arquivo["type"])) {
						$msg_erro = "Arquivo em formato inválido!";
					} else { // Verifica tamanho do arquivo
						if ($arquivo["size"] > $config["tamanho"])
							$msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
					}
					if (strlen($msg_erro) == 0) {

						// Pega extensão do arquivo
						preg_match("/\.(pdf|doc|gif|bmp|png|jpg|jpeg|rtf|xls|odt|ods|docx|xlsx|csv|txt){1}$/i", $arquivo["name"], $ext);
						$aux_extensao = "'".$ext[1]."'";

						$arquivo["name"]=retira_acentos($arquivo["name"]);
						$nome_sem_espaco = implode("", explode(" ",$arquivo["name"]));

						// Gera um nome único para a imagem
						$nome_anexo = "/www/assist/www/helpdesk/documentos/" . $hd_chamado_item."-".strtolower ($nome_sem_espaco);

						// Faz o upload da imagem
						if (strlen($msg_erro) == 0) {

							if (copy($arquivo["tmp_name"], $nome_anexo)) {
							}else{
								$msg_erro = "Arquivo não foi enviado!!!";
							}
						}//fim do upload da imagem
					}//fim da verificação de erro
				}//fim da verificação de existencia no apache
			}//fim de todo o upload

			//--======================================================================
			$sql = "SELECT hd_chamado_atendente,
							hd_chamado
							FROM tbl_hd_chamado_atendente
							WHERE admin = $login_admin
							AND   data_termino IS NULL
							ORDER BY hd_chamado_atendente DESC LIMIT 1";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			if (pg_numrows($res) > 0) {
				$hd_chamado_atendente =  pg_result($res,0,hd_chamado_atendente);
				$hd_chamado_atual     = pg_result($res,0,hd_chamado);
			}

			if(($hd_chamado_atual <> $hd_chamado) or $transferiu == "sim"){
				//se eu tiver interagindo em outro chamado ou transferindo

				//fecho o chamado_item
				$sql =" UPDATE tbl_hd_chamado_item
						SET termino = current_timestamp
						WHERE hd_chamado_item in(SELECT hd_chamado_item
									 FROM tbl_hd_chamado_item
									 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
										AND termino IS NULL
									 ORDER BY hd_chamado_item desc
									 LIMIT 1 );";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);

				if(strlen($hd_chamado_atendente)>0){
					$sql = "UPDATE tbl_hd_chamado_atendente
									SET data_termino = CURRENT_TIMESTAMP
									WHERE hd_chamado_atendente = $hd_chamado_atendente
									AND   admin               =  $login_admin
									AND   data_termino IS NULL
									";
					$res = pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}
			/*IGOR - 12/08/2008 - SE FOR SUPORTE, NÃO CONTA TEMPO DE ANALISE NO CHAMADO SE NÃO FOR Execução*/
			if($login_admin == 435 and 1==2){
				$sql =" UPDATE tbl_hd_chamado_item
						SET termino = current_timestamp
						WHERE hd_chamado_item in(SELECT hd_chamado_item
									 FROM tbl_hd_chamado_item
									 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
										AND termino IS NULL
									 ORDER BY hd_chamado_item desc
									 LIMIT 1 );";

				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);

				//fecha o atendimento se tiver algum aberto
				$sql = "UPDATE tbl_hd_chamado_atendente
								SET data_termino = CURRENT_TIMESTAMP
								WHERE hd_chamado_atendente = (
																SELECT hd_chamado_atendente
																FROM tbl_hd_chamado_atendente
																WHERE admin = 435
																AND   data_termino IS NULL
																ORDER BY hd_chamado_atendente DESC LIMIT 1
															)
								AND   admin               =  $login_admin
								AND   data_termino IS NULL
								";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}


			if($hd_chamado_atual <> $hd_chamado){ // se tiver interagindo em outro chamado eu insiro um novo
					$sql = "INSERT INTO tbl_hd_chamado_atendente(
													hd_chamado ,
													admin      ,
													data_inicio
											)VALUES(
											$hd_chamado       ,
											$login_admin      ,
											CURRENT_TIMESTAMP
											)";
					$res = pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
					$sql="SELECT CURRVAL('seq_hd_chamado_atendente');";
					$res = pg_exec ($con,$sql);
					$hd_chamado_atendente =  pg_result($res,0,0);
			}
			if($status == 'Resolvido'){
				//EXECUTA A ROTINA PARA SETAR O TERMINO NO HD_CHAMADO_ITEM
				$sql =" UPDATE tbl_hd_chamado_item
						SET termino = current_timestamp
						WHERE hd_chamado_item in(SELECT hd_chamado_item
									 FROM tbl_hd_chamado_item
									 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
										AND termino IS NULL
									 ORDER BY hd_chamado_item desc
									 LIMIT 1 );";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);

				$sql = "UPDATE tbl_hd_chamado_atendente
					SET data_termino = CURRENT_TIMESTAMP
					WHERE admin                = $login_admin
					AND   hd_chamado           = $hd_chamado
					AND   hd_chamado_atendente = $hd_chamado_atendente";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);

				$sql= "UPDATE tbl_controle_acesso_arquivo SET
					data_fim = CURRENT_DATE,
					hora_fim = CURRENT_TIME,
					status   = 'finalizado'
					WHERE hd_chamado = $hd_chamado";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}

		$msg_erro = substr($msg_erro,6);
		if(strlen($msg_erro) > 0){
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg_erro = 'Não foi possível Inserir o Chamado';
		}else{
			$res = @pg_exec($con,"COMMIT");
			if($status == 'Resolvido' OR $exigir_resposta == 't'){
				$sql="SELECT nome_completo,email,tbl_admin.admin, tbl_hd_chamado.fabrica
							FROM tbl_admin
							JOIN tbl_hd_chamado ON tbl_hd_chamado.admin = tbl_admin.admin
							WHERE hd_chamado = $hd_chamado";
				$res = pg_exec ($con,$sql);
				$email                = pg_result($res,0,email);
				$nome                 = pg_result($res,0,nome_completo);
				$adm                  = pg_result($res,0,admin);
				$fabrica              = pg_result($res,0,fabrica);

				$chave1 = md5($hd_chamado);
				$chave2 = md5($adm);
				$email_origem  = "suporte@telecontrol.com.br";
				$email_destino = $email;
				$assunto       = "Seu chamado n° $hd_chamado foi RESOLVIDO";
				$corpo.="<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR
						NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
						<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> -
						<STRONG>$titulo</STRONG>&nbsp; </P>
						<P align=left>$nome,</P>
						<P align=justify>Seu chamado foi&nbsp;<FONT
						color=#006600><STRONG>resolvido</STRONG></FONT> pelo suporte Telecontrol, Caso esteja com algum problema,
						<STRONG>insira um comentário para que o suporte verifique o que ocorreu. </STRONG></P>
						<P align=justify>Lembre-se: Não precisa fazer comentário de agradecimento, pois o sistema vai entender que o chamado foi mal resolvido!</P>
						<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://www.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Clique aqui para ver seu chamado</b></u></a>.</P>
						<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br
						</P>";

				if($exigir_resposta=='t' and $status<>'Resolvido' ){

					$assunto       = "Seu chamado n° $hd_chamado está aguardando sua resposta";

					$corpo = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR
							NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
							<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> -
							<STRONG>$titulo</STRONG>&nbsp; </P>
							<P align=left>$nome,</P>

							<P align=justify>
							Precisamos de sua posição para continuarmos atendendo o chamado.
							</P>
							<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://www.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Clique aqui para ver seu chamado</b></u></a>.</P>
							<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br
							</P>";
				}

				$body_top = "--Message-Boundary\n";
				$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
				$body_top .= "Content-transfer-encoding: 7BIT\n";
				$body_top .= "Content-description: Mail message body\n\n";

				if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
					$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
				}else{
					$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
				}

				#HD 16226
				if($exigir_resposta=='t' and $status<>'Resolvido' AND $xinterno=='f' and $fabrica==3){
					$sql = "SELECT nome_completo,email,tbl_admin.admin
							FROM tbl_admin
							JOIN tbl_hd_chamado ON tbl_hd_chamado.fabrica = tbl_admin.fabrica
							WHERE tbl_hd_chamado.hd_chamado    = $hd_chamado
							AND tbl_admin.help_desk_supervisor IS TRUE
							AND tbl_admin.admin                 <> 19";
					$res = pg_exec ($con,$sql);
					if (pg_numrows($res) > 0) {
						$surpevisor_email  = pg_result($res,0,email);
						$surpevisor_nome   = pg_result($res,0,nome_completo);
						$surpevisor_adm    = pg_result($res,0,admin);

						$chave1 = md5($hd_chamado);
						$chave2 = md5($surpevisor_adm);
						$email_origem  = "suporte@telecontrol.com.br";
						$email_destino = $surpevisor_email;
						$assunto       = "O chamado n° $hd_chamado está aguardando resposta";
						$corpo = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
								<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> -
								<STRONG>$titulo</STRONG>&nbsp; </P>
								<P align=left>$nome,</P>
								<P align=justify>Estamos aguardando a posição do(a) $nome para continuarmos atendendo o chamado.</P>
								<p>O seguinte comentário foi inserido no chamado: <br><i>$comentario</i></p>
								<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br
								</P>";

						//<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://www.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$surpevisor_adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Clique aqui para ver o chamado</b></u></a>.</P>
						$body_top = "--Message-Boundary\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
						$body_top .= "Content-transfer-encoding: 7BIT\n";
						$body_top .= "Content-description: Mail message body\n\n";

						if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
							$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
						}
					}
				}
			}
			if($status =='Resolvido'){
				$sql = "
				SELECT
				hd_chamado_melhoria

				FROM
				tbl_hd_chamado_melhoria

				WHERE
				hd_chamado=$hd_chamado
				";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res)) {
					//A variável abaixo armazena qual o admin responsável por gerenciar as Melhorias
					//em Programas, normalmente o Tester, ele receberá e-mails de notificações
					$admin_responsavel_melhorias = 2310;

					$sql = "
					SELECT
					email

					FROM
					tbl_admin

					WHERE
					admin=$admin_responsavel_melhorias
					";
					$res = pg_query($con, $sql);
					$email = pg_result($res, email);

					$mensagem = "O chamado $hd_chamado possui melhorias associadas a ele e foi Resolvido nesta data.<br>
					Por favor, acessar o sistema de melhorias em programas para validar.<br>
					<br>
					Suporte Telecontrol";

					$headers .= "MIME-Version: 1.0\n";
					$headers .= "Content-type: text/html; charset=iso-8859-1\n";
					$headers .= "To: $email" . "\r\n";
					$headers .= "From: Telecontrol Melhorias <suporte@telecontrol.com.br>";// . "\r\n";

					$titulo = "Melhorias: Chamado $hd_chamado Resolvido";

					mail($to, $titulo, $mensagem, $headers);
				}

				?>
				<script type="text/javascript">
					if(confirm('Deseja registrar alterações no Change Log?') == true){
						window.location="change_log_insere.php?hd_chamado=<?echo $hd_chamado;?>";
					}else{
						window.location="adm_chamado_detalhe.php?hd_chamado=<?echo $hd_chamado;?>";
					}
				</script>
				<?
			}else{
				header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
			}
		}
	}
}



if(strlen($hd_chamado) > 0){
	$sql = "UPDATE tbl_hd_chamado SET atendente = $login_admin WHERE hd_chamado = $hd_chamado AND atendente IS NULL";
	$res = pg_exec ($con,$sql);

	$sql= " SELECT tbl_hd_chamado.hd_chamado                             ,
					tbl_hd_chamado.admin                                 ,
					to_char (tbl_hd_chamado.data,'DD/MM/YYYY HH24:MI') AS data,
					tbl_hd_chamado.titulo                                ,
					tbl_hd_chamado.categoria                             ,
					tbl_hd_chamado.status                                ,
					tbl_hd_chamado.duracao                               ,
					tbl_hd_chamado.atendente                             ,
					tbl_hd_chamado.fabrica_responsavel                   ,
					tbl_hd_chamado.fabrica                               ,
					tbl_hd_chamado.prioridade                            ,
					tbl_hd_chamado.prazo_horas                           ,
						tbl_hd_chamado.cobrar,
						tbl_hd_chamado.tipo_chamado,
					tbl_hd_chamado.hora_desenvolvimento                  ,
					to_char (tbl_hd_chamado.previsao_termino,'DD/MM/YYYY HH24:MI') AS previsao_termino,
					to_char (tbl_hd_chamado.previsao_termino_interna,'DD/MM/YYYY HH24:MI') AS previsao_termino_interna,
					tbl_fabrica.nome   AS fabrica_nome                   ,
					tbl_admin.login                                      ,
					tbl_admin.nome_completo                              ,
					tbl_admin.fone                                       ,
					tbl_admin.email                                      ,
					atend.nome_completo AS atendente_nome
			FROM tbl_hd_chamado
			JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
			JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica
			LEFT JOIN tbl_admin atend ON tbl_hd_chamado.atendente = atend.admin
			WHERE hd_chamado = $hd_chamado";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$admin                = pg_result($res,0,admin);
		$data                 = pg_result($res,0,data);
		$titulo               = pg_result($res,0,titulo);
		$categoria            = pg_result($res,0,categoria);
		$status               = pg_result($res,0,status);
		$atendente            = pg_result($res,0,atendente);
		$atendente_nome       = pg_result($res,0,atendente_nome);
		$fabrica_responsavel  = pg_result($res,0,fabrica_responsavel);
		$fabrica              = pg_result($res,0,fabrica);
		$nome                 = pg_result($res,0,nome_completo);
		$email                = pg_result($res,0,email);
		$prioridade           = pg_result($res,0,prioridade);
		$fone                 = pg_result($res,0,fone);
		$nome_completo        = pg_result($res,0,nome_completo);
		$fabrica_nome         = pg_result($res,0,fabrica_nome);
		$login                = pg_result($res,0,login);
		$prazo_horas          = pg_result($res,0,prazo_horas);
		$previsao_termino     = pg_result($res,0,previsao_termino);
		$previsao_termino_interna = pg_result($res,0,previsao_termino_interna);
		$hora_desenvolvimento     = pg_result($res,0,hora_desenvolvimento);
		$cobrar                   = pg_result($res,0,cobrar);
		$tipo_chamado             = pg_result($res,0,tipo_chamado);

		//HD 218848: Criação do questionário na abertura do Help Desk
		$sql = "SELECT * FROM tbl_hd_chamado_questionario WHERE hd_chamado=$hd_chamado";
		$res = pg_query($sql);
		
		if (pg_num_rows($res)) {
			$mostra_questionario = true;
			$necessidade = pg_result($res, 0, necessidade);
			$funciona_hoje = pg_result($res, 0, funciona_hoje);
			$objetivo = pg_result($res, 0, objetivo);
			$local_menu = pg_result($res, 0, local_menu);
			$http = pg_result($res, 0, http);
			$tempo_espera = pg_result($res, 0, tempo_espera);
			$impacto = pg_result($res, 0, impacto);
		}
	}else{
		$msg_erro="Chamado não encontrado";
	}
}



$TITULO = "ADM - Responder Chamado";

include "menu.php";
?>
<script type="text/javascript" src="js/ajax_busca.js"></script>
<? if($login_admin ==822 or $login_admin==398 or $login_admin==1375 ) {
	echo "<script type='text/javascript' src='../admin/js/fckeditor/fckeditor.js'></script>";
}
?>
<script>
<? if($login_admin ==822 or $login_admin==398 or $login_admin==1375 ) { ?>
window.onload = function(){
	var oFCKeditor = new FCKeditor( 'comentario' ) ;
	oFCKeditor.BasePath = "../admin/js/fckeditor/" ;
	oFCKeditor.ToolbarSet = 'Chamado' ;
	oFCKeditor.ReplaceTextarea() ;
}
<?}?>
function recuperardados(hd_chamado) {
	var programa = document.frm_chamada.programa.value;
	if(programa.length > 4 ){
		var busca = new BUSCA();
		busca.Updater("ajax_listar_programa.php?digito="+programa+"&hd_chamado="+escape(hd_chamado),"conteudo","get","carregando os dados...");
	}
}
function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}
function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}


var http3 = new Array();


function atualizaHr(hd,hr){
	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();
	var campo = document.getElementById('result');

	if (campo==false) return;
	if (campo.style.display=="block"){
		campo.style.display = "none";
	}else{
		campo.style.display = "block";
	}

	url = "<?$PHP_SELF;?>?atualiza_hd=true&hd="+hd+"&hr="+hr+"&data="+curDateTime;
	http3[curDateTime].open('get',url);
	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = " <font size='1' face='verdana'> Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				campo.innerHTML = results;
				/*	if (campo==false) return;
					if (campo.style.display=="block"){
						campo.style.display = "none";
					}else{
						campo.style.display = "block";
					}*/
			}else {
				alert('Ocorreu um erro');
			}
		}
	}
	http3[curDateTime].send(null);

}

function atualizaPrazo(hd,prazo){
	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();
	var campo = document.getElementById('result');

	if (campo==false) return;
	if (campo.style.display=="block"){
		campo.style.display = "none";
	}else{
		campo.style.display = "block";
	}

	url = "<?$PHP_SELF;?>?atualiza_hd=true&hd="+hd+"&prazo="+prazo+"&data="+curDateTime;
	http3[curDateTime].open('get',url);
	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = " <font size='1' face='verdana'> Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				campo.innerHTML = results;
			}else {
				alert('Ocorreu um erro');
			}
		}
	}
	http3[curDateTime].send(null);

}

function atualizaPrevisaoTermino(hd,data){

	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();
	var campo = document.getElementById('result2');

	if (campo==false) return;
	if (campo.style.display=="block"){
		campo.style.display = "none";
	}else{
		campo.style.display = "block";
	}

	url = "<?$PHP_SELF;?>?atualiza_previsao_termino=true&hd="+hd+"&data_previsao="+data+"&data="+curDateTime;
	http3[curDateTime].open('get',url);
	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = " <font size='1' face='verdana'> Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				campo.innerHTML = results;
				/*	if (campo==false) return;
					if (campo.style.display=="block"){
						campo.style.display = "none";
					}else{
						campo.style.display = "block";
					}*/
			}else {
				alert('Ocorreu um erro');
			}
		}
	}
	http3[curDateTime].send(null);

}
</script>
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />


<? include "javascript_calendario.php"; ?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$("#previsao_termino").maskedinput("99/99/9999 99:99");
	});
</script>

<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script>
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
});

 $(document).ready(function(){
	$(".relatorio tr").mouseover(function(){$(this).addClass("over");}).mouseout(function(){$(this).removeClass("over");});
	$(".relatorio tr:even").addClass("alt");
});

</script>

<script language="JavaScript">
function abrir(URL) {
	var width = 300;
	var height = 290;
	var left = 99;
	var top = 99;

	window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
}
</script>

<style>
.resolvido{
	background: #259826;
	color: #FCFCFC;
	float: left;
	clear: none;
	padding: 2px 2px 2px 2px;
	width: 100%;
	font-style: normal;
	font-variant: normal;
	font-weight: bold;
	text-align: center;
	}
.interno{
	background: #FFE0B0;
	color: #000000;
	float: left;
	clear: none;
	padding: 2px 2px 2px 2px;
	width: 100%;
	font-style: normal;
	font-variant: normal;
	font-weight: bold;
	text-align: center;
	}

	table.tab_cabeca{
		border:1px solid #3e83c9;
		font-family: Verdana;
		font-size: 11px;

	}
	.titulo_cab{
		background: #C9D7E7;
		padding: 5px;
		color: #000000;
		font: bold;
	}
	.sub_label{
		background: #E7EAF1;
		padding: 5px;
		color: #000000;

	}
	table.relatorio {
		font-family: Verdana;
		font-size: 11px;
		border-collapse: collapse;
		width: 750px;
		font-size: 1.1em;
		border-left: 1px solid #8BA4EB;
		border-right: 1px solid #8BA4EB;
	}

	table.relatorio th {
		font-family: Verdana;
		font-size: 11px;
		background: #3e83c9;
		color: #fff;
		font-weight: bold;
		padding: 2px 2px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
		padding-top: 5px;
		padding-bottom: 5px;
	}

	table.relatorio td {
		font-family: Verdana;
		font-size: 11px;
		padding: 1px 5px 5px 5px;
		border-bottom: 1px solid #95bce2;
		line-height: 15px;
	}

/*
	table.sample td * {
		padding: 1px 11px;
	}
*/
	table.relatorio tr.alt td {
		background: #ecf6fc;
	}
<? if($login_admin != 822) { ?>
	table.relatorio tr.over td {
		background: #bcd4ec;
	}
<? } ?>

	table.relatorio tr.clicado td {
		background: #FF9933;
	}
	table.relatorio tr.sem_defeito td {
		background: #FFCC66;
	}
	table.relatorio tr.mais_30 td {
		background: #FF0000;
	}
	table.relatorio tr.erro_post td {
		background: #99FFFF;
	}

	</style>



<table width = '750' class = 'tab_cabeca' align = 'center' border='0' cellpadding='2' cellspacing='2' >

<form name='frm_chamada' action='<? echo $PHP_SELF ?>' method='post' enctype="multipart/form-data">
<input type='hidden' name='hd_chamado' value='<?= $hd_chamado?>'>
<tr>
	<td class='titulo_cab' width='10'><strong>Título </strong></td>
	<td class='sub_label'><?= $titulo ?> </td>

	<td class='titulo_cab' width="60"><strong>Abertura </strong></td>
	<td  class='sub_label'align='center'><?= $data ?> </td>

</tr>
<tr>
	<td class='titulo_cab' ><strong>Solicitante </strong></td>
	<td  class='sub_label' ><?= $login ?> </td>
	<td class='titulo_cab' width="60" ><strong>Chamado </strong></td>
	<td  class='sub_label'align='center'><strong><font  color='#FF0033' size='4'><?=$hd_chamado?></font></strong></td>
	<tr>
	<td class='titulo_cab' ><strong>Nome </strong></td>
	<td class='sub_label'><?= $nome ?></td>
	<td class='titulo_cab' width="60"><strong>Fábrica </strong></td>
	<td  class='sub_label' align='center'><?= $fabrica_nome ?> </td>
</tr>

<tr>
	<td class='titulo_cab'><strong>e-mail </strong></td>
	<td class='sub_label'><?= $email ?></td>
	<td class='titulo_cab'><strong>Fone </strong></td>
	<td  class='sub_label' align='center'><?= $fone ?></td>
</tr>

<tr>
	<td class='titulo_cab' ><strong>Atendente </strong></td>
	<td class='sub_label'><?= $atendente_nome ?></td>
	<td class='titulo_cab'><strong>Status </strong></td>
	<td  class='sub_label' align='center'><?= $status ?></td>
</tr>
<!-- HD 218848: Criação do questionário na abertura do Help Desk -->
<?
if ($mostra_questionario) {
	$desabilita_questionario = "readonly";
	$desabilita_questionario_combo = "disabled";
?>
<tr>
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;O que você precisa que seja feito?</strong>
	</td>
</tr>
<tr>
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
		<? echo $necessidade; ?>
	</td>
</tr>

<tr>
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;Como funciona hoje?</strong>
	</td>
</tr>
<tr>
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
		<? echo $funciona_hoje; ?>
	</td>
</tr>

<tr>
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;Qual o objetivo desta solicitação? Que problema visa resolver?</strong>
	</td>
</tr>
<tr>
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
		<? echo $objetivo; ?>
	</td>
</tr>

<tr>
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;Esta rotina terá impacto financeiro para a empresa? Por quê?</strong>
	</td>
</tr>
<tr>
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
		<? echo $impacto; ?>
	</td>
</tr>

<tr>
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;Em que local do sistema você precisa de alteração?</strong>
	</td>
</tr>
<tr>
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
	<?

	switch($local_menu) {
		case "admin_gerencia":
			echo "Administração: Gerência";
		break;

		case "admin_callcenter":
			echo "Administração: CallCenter";
		break;

		case "admin_cadastro":
			echo "Administração: Cadastro";
		break;

		case "admin_infotecnica":
			echo "Administração: Info Técnica";
		break;

		case "admin_financeiro":
			echo "Administração: Financeiro";
		break;

		case "admin_auditoria":
			echo "Administração: Auditoria";
		break;

		case "posto_os":
			echo "Área do Posto: Ordem de Serviço";
		break;

		case "posto_infotecnica":
			echo "Área do Posto: Info Técnica";
		break;

		case "posto_pedidos":
			echo "Área do Posto: Pedidos";
		break;

		case "posto_cadastro":
			echo "Área do Posto: Cadastro";
		break;

		case "posto_tabelapreco":
			echo "Área do Posto: Tabela Preço";
		break;
	}

	?>
	</td>
</tr>

<tr>
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;Quanto tempo é possível esperar por esta mudança?</strong>
	</td>
</tr>
<tr>
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
	<?
	switch ($tempo_espera) {
		case "0":
		echo "Imediato";
		break;

		case "1":
		echo "1 Dia";
		break;

		case "2":
		echo "2 Dias";
		break;

		case "3":
		echo "3 Dias";
		break;

		case "4":
		echo "4 Dias";
		break;

		case "5":
		echo "5 Dias";
		break;

		case "6":
		echo "6 Dias";
		break;

		case "7":
		echo "1 Semana";
		break;

		case "14":
		echo "2 Semanas";
		break;

		case "21":
		echo "3 Semanas";
		break;

		case "30":
		echo "1 Mês";
		break;

		case "60":
		echo "2 Meses";
		break;

		case "90":
		echo "3 Meses";
		break;

		case "180":
		echo "6 Meses";
		break;

		case "360":
		echo "1 Ano";
		break;
		
		default:
			echo "$tempo_espera Dias";
	}
	?>
	</td>
</tr>

<tr>
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;Endereço HTTP da tela onde está sendo solicitada a alteração:</strong>
	</td>
</tr>
<tr>
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
		http://<? echo $http; ?>
	</td>
</tr>
<?
}
?>
</table>

<?
if($login_admin == 822) {
	$cond = " AND tbl_hd_chamado_item.comentario not ilike 'Término de trabalho automático'
			  AND tbl_hd_chamado_item.comentario not ilike 'Chamado Transferido'
			  AND tbl_hd_chamado_item.comentario not ilike 'Categoria Alterada de%' ";
}
$sql= "SELECT   tbl_hd_chamado_item.hd_chamado_item,
		to_char (tbl_hd_chamado_item.data,'DD/MM/YY HH24:MI') AS data   ,
		tbl_hd_chamado_item.comentario                               ,
		tbl_hd_chamado_item.interno                                  ,
		tbl_admin.nome_completo                            AS autor  ,
		(select to_char(sum(termino - data),'HH24:MI') from tbl_hd_chamado_item where hd_chamado_item = tbl_hd_chamado_item.hd_chamado_item) as a,
		tbl_hd_chamado_item.status_item
		FROM tbl_hd_chamado_item
		JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_item.admin
		WHERE hd_chamado = $hd_chamado
		$cond
		ORDER BY hd_chamado_item";
$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {
	echo "<BR><BR><table width = '750' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio'>";
	echo "<thead>";
		echo "<tr  bgcolor='#D9E8FF'>";
		echo "<th><strong>Nº</strong></th>";
		echo "<th  nowrap><strong>Data</strong></th>";
		//echo "<th  nowrap><strong>Tmp Trab.</strong></th>";
		echo "<th><strong>  Comentário </strong></th>";
		echo "<th  ><strong> Anexo </strong></th>";
		echo "<th  ><strong>Autor </strong></th>";
		echo "</tr>";
	echo "</thead>";
	echo "<tbody>";
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$x=$i+1;
		$hd_chamado_item = pg_result($res,$i,hd_chamado_item);
		$data_interacao  = pg_result($res,$i,data);
		$autor           = pg_result($res,$i,autor);
		$item_comentario = pg_result($res,$i,comentario);
		$status_item     = pg_result($res,$i,status_item);
		$interno         = pg_result($res,$i,interno);
		//$tempo_trabalho  = pg_result($res,$i,tempo_trabalho);

		//$autor = explode(" ",$autor);
		//$autor = $autor[0];

		echo "<tr  bgcolor='$cor'>";
		echo "<td nowrap width='25'>$x </td>";
		echo "<td nowrap width='50'>$data_interacao </td>";
		//echo "<td nowrap width='40'>$tempo_trabalho</td>";
		echo "<td  width='520'>";
		if ($status_item == 'Resolvido'){

			echo "<span class='resolvido'><b>Chamado foi resolvido nesta interação</b></span>";

		}
		if($interno == 't'){
			echo "<span class='interno'><b>Chamado interno</b></span>";

		}
		$xcomentario = strtoupper($item_comentario);
		if(strpos($xcomentario,"<DIV") > 0 or strpos($xcomentario,"<TR") > 0){
			$item_comentario = strip_tags($item_comentario,'<p><br><a>');
		}
		echo "<font size='1'>" . nl2br(str_replace($filtro,"", $item_comentario)) . "</td>";
		echo "<td width='25'>";

		$dir = "documentos/";
		$dh  = opendir($dir);
//		echo "$hd_chamado_item";
		while (false !== ($filename = readdir($dh))) {
			if (strpos($filename,"$hd_chamado_item") !== false){
				$po = strlen($hd_chamado_item);
				if(substr($filename, 0,$po)==$hd_chamado_item){
					echo "<!--ARQUIVO-I-->&nbsp;&nbsp;<a href=documentos/$filename target='blank'><img src='imagem/clips.gif' border='0'>Baixar</a>&nbsp;&nbsp;<!--ARQUIVO-F-->";
				}
			}
		}
		echo "</td>";
		echo "<td >$autor</td>";
		echo "</tr>";
	}
	echo "</tbody>";
	echo "</table>";
}
?>



<BR>
<table width = '750' align = 'center' border='0' cellpadding='2'  style='font-family: arial ; font-size: 12px'>
<tr>
<td valign='top'>
<table width = '500' align = 'center' class='tab_cabeca' border='0' cellpadding='2'  >
<?
if ($status == "Análise") {
?>
<tr>
	<td class = 'titulo_cab'><strong>Seqüência </strong></td>
	<td class='sub_label'>
		<input type='radio' name='sequencia' value='CONTINUA' id='continua'><label for='continua'>Continua em Análise</label>
		<br>
		<input type='radio' name='sequencia' value='AGUARDANDO' id='aguardando'><label for='aguardando'>Aguard.Execução</label>
		<br>
		<input type='radio' name='sequencia' value='SEGUE' id='segue'><label for='segue'>Vai para Execução</label>
	</td>
</tr>
<? } ?>

<?
if ($status == "Aguard.Execução") {
?>
<tr>
	<td class = 'titulo_cab' ><strong>Seqüência </strong></td>
	<td class='sub_label'>
		<input type='radio' name='sequencia' value='CONTINUA' id='continua'><label for='continua'>Continua Aguard.Execução</label>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type='radio' name='sequencia' value='SEGUE' id='segue'><label for='segue'>Vai para Execução</label>
	</td>
</tr>
<? }

if ($status == "Execução") {
?>
<tr>
	<td class = 'titulo_cab'><strong>Seqüência </strong></td>
	<td  class='sub_label'>
		<input type='radio' name='sequencia' value='CONTINUA' id='continua'><label for='continua'>Continua em Execução</label>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type='radio' name='sequencia' value='SEGUE' id='segue'><label for='segue'>Resolvido</label>
	</td>
</tr>
<? } ?>
<tr>
	<td  align='center' colspan='2'  class='sub_label'>
		<textarea name='comentario' cols='60' rows='6' wrap='VIRTUAL' id='comentario'><?echo $comentario;?></textarea><br>
		<input type='checkbox' name='exigir_resposta' value='t' id='exigir_resposta'><label for='exigir_resposta'>Exigir resposta do usuário</label>
		<input type='checkbox' name='interno' value='t' id='interno'><label for='interno'>Chamado Interno</label>
	</td>
</tr>
<tr>
	<td align='center' colspan='2' class='sub_label'>
	Arquivo <input type='file' name='arquivo' size='50' class='frm'>
	</td>
</tr>
<tr>
	<td  align='center' colspan='1' class='sub_label'>
		<center><input type='submit' name='btn_telefone' value='Telefone'>
		</center>
		<td  align='center' colspan='1' class='sub_label'>
		<center><input type='submit' name='btn_acao' value='Responder Chamado'>
		</center>
	</td>
</tr>
</table>
</td>
<td valign='top'>

	<table width = '250' align = 'center' class='tab_cabeca'  cellpadding='2' cellspacing='1' border='0' >
	<tr>
		<td colspan='2' align='center' class='titulo_cab'><strong><font size='5'><?echo $hd_chamado; ?></font></strong></td>
		</tr>
	<tr>
	<tr>
		<td class ='sub_label'><strong>Status </strong></td>
		<td class ='sub_label'  align = 'center' >
			<select name="status" size="1"  style='width: 150px;'>
			<!--<option value=''></option>-->
			<option value='Novo'      <? if($status=='Novo')      echo ' SELECTED '?> >Novo</option>
			<option value='Análise'   <? if($status=='Análise')   echo ' SELECTED '?> >Análise</option>
			<option value='Execução'  <? if($status=='Execução')  echo ' SELECTED '?> >Execução</option>
			<option value='Aguard.Execução'  <? if($status=='Aguard.Execução')  echo ' SELECTED '?> >Aguard.Execução</option>
			<option value='Aprovação' <? if($status=='Aprovação') echo ' SELECTED '?> >Aprovação</option>
			<option value='Cancelado' <? if($status=='Cancelado') echo ' SELECTED '?> >Cancelado</option>
			<option value='Aguard.Verifica' <? if($status=='Aguard.Verifica') echo ' SELECTED '?> >Aguard.Verificação</option>
			<option value='Resolvido' <? if($status=='Resolvido') echo ' SELECTED '?> >Resolvido</option>
			<option value='Aguard.Admin'  <? if($status=='Aguard.Admin')  echo ' SELECTED '?> >Aguard.Admin</option>
			</select>
		</td>
		</tr>
	<tr>
	<td  class ='sub_label'><strong>Atendente</strong></td>
	<td  class ='sub_label' align='center' >
	<?
	$sql = "SELECT  *
			FROM    tbl_admin
			WHERE   tbl_admin.fabrica = 10
			and ativo is true
			ORDER BY tbl_admin.nome_completo;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<select class='frm' style='width: 150px;' name='transfere'>\n";
		echo "<option value=''>- ESCOLHA -</option>\n";

		for ($x = 0 ; $x < pg_numrows($res) ; $x++){
			$aux_admin = trim(pg_result($res,$x,admin));
			$aux_nome_completo  = trim(pg_result($res,$x,nome_completo));

			echo "<option value='$aux_admin'"; if ($atendente == $aux_admin) echo " SELECTED "; echo "> $aux_nome_completo</option>\n";
		}
		echo "</select>\n";
	}
	?>
	</td>
	</tr>
	<tr>
		<td class ='sub_label' ><strong>Categoria </strong></td>
		<td  class ='sub_label' align='center'>
			<select name="categoria" size="1"  style='width: 150px;'>
			<option></option>
			<option value='Ajax' <? if($categoria=='Ajax') echo ' SELECTED '?> >Ajax, JavaScript</option>
			<option value='Design' <? if($categoria=='Design') echo ' SELECTED '?> >Design</option>
			<option value='Implantação' <? if($categoria=='Implantação') echo ' SELECTED '?> >Implantação</option>
			<option value='Integração' <? if($categoria=='Integração') echo ' SELECTED '?> >Integração (ODBC, Perl)</option>
			<option value='Linux' <? if($categoria=='Linux') echo ' SELECTED '?> >Linux, Hardware, Data-Center</option>
			<option value='Novos' <? if($categoria=='Novos') echo ' SELECTED '?> >Novos Projetos</option>
			<option value='SQL' <? if($categoria=='SQL') echo ' SELECTED '?> >Otimização de SQL e Views</option>
			<option value='PHP' <? if($categoria=='PHP') echo ' SELECTED '?> >PHP</option>
			<option value='PL' <? if($categoria=='PL') echo ' SELECTED '?> >PL/PgSQL, functions e triggers</option>
			<option value='Postgres' <? if($categoria=='Postgres') echo ' SELECTED '?> >Postgres</option>
			<option value='Suporte Telefone' <? if($categoria=='Suporte Telefone') echo ' SELECTED '?> >Suporte Telefone</option>
			</select>
		</td>
	</tr>
	<tr>
		<td class ='sub_label'><strong>Tipo </strong></td>
	<td  class ='sub_label' align='center'>
			<select name="tipo_chamado" size="1"  style='width: 150px;'>
	<?
	$sql = "SELECT	tipo_chamado,
						descricao
				FROM tbl_tipo_chamado
				ORDER BY descricao;";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
				for($i=0;pg_numrows($res)>$i;$i++){
					$xtipo_chamado = pg_result($res,$i,tipo_chamado);
					$xdescricao    = pg_result($res,$i,descricao);
					echo "<option value='$xtipo_chamado' ";
					if($tipo_chamado == $xtipo_chamado){echo " SELECTED ";}
					echo " >$xdescricao</option>";
				}
		}
	?>
	</select>
	</td>

	</tr>
	<tr>
		<?
		if(strlen($hd_chamado)>0){
			if($login_admin == 822 ) {
				$cond1 = " AND tbl_hd_chamado_atendente.admin =822 ";
			}
			$wsql ="SELECT SUM(case when data_termino is null
								THEN current_timestamp
								ELSE data_termino end - data_inicio )
					FROM tbl_hd_chamado_atendente
					JOIN tbl_admin using(admin)
					WHERE hd_chamado = $hd_chamado
					$cond1
					/*AND   responsabilidade in ('Analista de Help-Desk','Programador')*/";
			$wres = pg_exec($con, $wsql);
			if(pg_numrows($wres)>0)
			$horas= pg_result ($wres,0,0);
			if(strlen($horas)==0){
				$horas = "00:00:00";
			}
			$xhoras = explode(":",$horas);
			$horas = $xhoras[0].":".$xhoras[1];
		}
		?>

		<td  class ='sub_label'><strong>Trabalhadas </strong></td>
		<?
		echo "<td  class ='sub_label'align='center' acronym title='";

		$sqlx = "SELECT tbl_admin.login,
						tbl_hd_chamado_atendente.data_inicio,
						TO_CHAR(tbl_hd_chamado_atendente.data_inicio,'DD/MM/YYYY hh24:mi:ss') as inicio,
						TO_CHAR(tbl_hd_chamado_atendente.data_termino,'hh24:mi:ss') as fim
				FROM tbl_hd_chamado_atendente
				JOIN tbl_admin USING(admin)
				WHERE hd_chamado = $hd_chamado
				ORDER BY tbl_hd_chamado_atendente.data_inicio";
		$resx = pg_exec($con, $sqlx);

		for ($i=0;$i<pg_numrows($resx);$i++) {
			echo pg_result($resx,$i,login)." (".pg_result($resx,$i,inicio)." - ".pg_result($resx,$i,fim).")\n";
		}
		echo "'> $horas h</td>";
		?>
	</tr>
	</tr>

<? if($analista_hd == "sim"){ ?>
	<tr>
		<td  class ='sub_label' title='Hora interna que será pago para o analista desenvolvedor'><strong>Prazo(?)</strong></td>
		<td  class ='sub_label'align='center'>
		<input type='text' size='2' maxlength ='5' name='prazo_horas' value='<?= $prazo_horas ?>' class='caixa' onblur="javascript:checarNumero(this);atualizaPrazo('<?echo $hd_chamado;?>',this.value)"> h
		<div id='result' style='position:absolute; display:none; border: 1px solid #949494;background-color: #F1F0E7;width:150px;'>
		</div>
		</td>
	</tr>
	<tr>
		<td  class ='sub_label' title='Horas que será deduzida da quantidade de horas da franquia do fabricante.'><strong>Horas a cobrar(?)</strong></td>
		<td  class ='sub_label'align='center'>
		<input type='text' size='2' maxlength ='5' name='hora_desenvolvimento' value='<?= $hora_desenvolvimento ?>' <?
		?> class='caixa' onblur="javascript:checarNumero(this);atualizaHr('<?echo $hd_chamado;?>',this.value)"> h<BR>
		<input type='text' size='16' maxlength ='16' name='previsao_termino' id='previsao_termino' value='<?= $previsao_termino ?>' <?
		?> class='caixa' onblur="javascript:atualizaPrevisaoTermino('<?echo $hd_chamado;?>',this.value)"> Dt
		<div id='result2' style='position:absolute; display:none;  border: 1px solid #949494;background-color: #F1F0E7;width:100px;'>
		</div>
		</td>
	</tr>
	<tr>
		<td class ='sub_label' ><strong>Cobrar ? </strong></td>
		<td  class ='sub_label' align='center'>
		<input type='checkbox' name='cobrar' value='t' <? if ($cobrar == "t") echo "Checked";?>> Sim

		</td>
	</tr>
	<tr>
		<td class ='sub_label' ><strong>Prioridade ? </strong></td>
		<td  class ='sub_label' align='center'>
		<input type='checkbox' name='prioridade' value='t' <? if ($prioridade == "t") echo "Checked";?>> Sim

		</td>
	</tr>
<? }else{ ?>
	<tr>
		<td  class ='sub_label'><strong>Desenvol.</strong></td>
		<td  class ='sub_label'align='center'>
		<?= $hora_desenvolvimento ?> h
		</td>
	</tr>
	<input type='hidden' name='cobrar' value='<? echo $cobrar;?>'>
	<tr>
		<td  class ='sub_label'><strong>Cobrar ? </strong></td>
		<td  class ='sub_label' align='center'>
		<? if ($cobrar == "t"){ echo "Sim";}else{ echo "Não";}?>
		</td>
	</tr>
	<input type='hidden' name='prioridade' value='<? echo $prioridade;?>'>
	<tr>
		<td  class ='sub_label'><strong>Prioridade ? </strong></td>
		<td  class ='sub_label' align='center'>
		<? if ($prioridade == "t"){ echo "Sim";}else{ echo "Não";}?>
		</td>
	</tr>
<? } ?>
	<tr>
		<td class ='sub_label'><B>Horas Pendentes</B></td>
		<td  class ='sub_label' align="center">
			<a href="javascript:abrir('adm_analistas_hora.php');"><strong>Horas por Analistas</strong></a>
		</td>
	</tr>

	<tr>
		<td  class ='sub_label'><strong>Arquivo:</strong></td>
		<td   class ='sub_label' align='center'>
		<input name='programa' id='programa'value='' class='caixa' size='25' onKeyUp = 'recuperardados(<? echo $hd_chamado?>)' onblur='this.value=""'><br>
		</td>
	</tr>
	<tr>
	<td  class ='sub_label' colspan='2'><div id='conteudo' class='Chamados2' style='position: absolute;opacity:.80;'>Digite no mínimo <br>4 caracteres</div>&nbsp;</td>
	</tr>
	</TABLE>
</td>
</tr>
<tr>
<td colspan='2'>

<?

$sql = "SELECT
			tbl_arquivo.descricao AS arquivo,
			to_char (tbl_controle_acesso_arquivo.data_inicio,'DD/MM') AS data_inicio,
			to_char (tbl_controle_acesso_arquivo.hora_inicio,'HH24:MI') AS hora_inicio,
			to_char (tbl_controle_acesso_arquivo.data_fim,'DD/MM') AS data_fim,
			to_char (tbl_controle_acesso_arquivo.hora_fim,'HH24:MI') AS hora_fim
		FROM tbl_arquivo
		JOIN tbl_controle_acesso_arquivo USING(arquivo)
		WHERE hd_chamado=$hd_chamado
		ORDER BY tbl_controle_acesso_arquivo.data_inicio";
$res_arquivos = pg_exec ($con,$sql);
echo "<table width = '750' align = 'center' class='tab_cabeca'  cellpadding='2' cellspacing='1' border='0' >";
if (@pg_numrows($res_arquivos) > 0) {
	echo "<tr  bgcolor='#D9E8FF'; style='font-family: arial ; font-size: 10px ;'>\n";
	echo "<td nowrap style='border-bottom:1px solid #cecece'><b>Início</b></td>\n";
	echo "<td nowrap style='border-bottom:1px solid #cecece'align='center'><b>Histórico dos Arquivos Utilizados</b></td>\n";
	echo "<td nowrap style='border-bottom:1px solid #cecece'><b>Fim</b></td>\n";
	echo "</tr>\n";
	$arquivo = "";
	$data_inicio = "";
	$data_fim = "";
	for ($k = 0 ; $k < pg_numrows ($res_arquivos) ; $k++) {
		$arquivo	.= str_replace ("/var/www/assist/www/","",pg_result($res_arquivos,$k,arquivo))."<br>";
		$data_inicio.= pg_result($res_arquivos,$k,data_inicio)."  ".pg_result($res_arquivos,$k,hora_inicio)."<br>";
		$data_fim.= pg_result($res_arquivos,$k,data_fim)."  ".pg_result($res_arquivos,$k,hora_fim)."<br>";
	}
	echo "<tr style='font-family: arial ; font-size: 10px ;' height='25'>\n";
	echo "<td nowrap>$data_inicio</td>\n";
	echo "<td align='left' style='padding-left:10px'>$arquivo</td>\n";
	echo "<td nowrap>$data_fim</td>\n";
	echo "</tr>\n";
}
?>
</table>
</td>
</tr>

</table>
<?
/*--=== ARQUIVOS SOLICITADOS ==================================================--*/
echo "<br><DIV class='exibe' id='dados' value='1' align='center'><font size='1'>Por favor aguarde um momento, carregando os dados...<br><img src='../imagens/carregar_os.gif'></DIV>";
echo "<script language='javascript'>Exibir('dados','','','');</script>";

if($login_admin <> 822 and $login_admin <> 398 and $login_admin<>1375){
	echo "<script language=\"JavaScript1.2\">editor_generate('comentario');</script>";
}
echo "</form>";
?>

<? include "rodape.php" ?>
