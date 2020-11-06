<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

function validatemail($email=""){ 
	if (preg_match("/^[a-z]+([\._\-]?[a-z0-9]+)+@+[a-z0-9\._-]+\.+[a-z]{2,3}$/", $email)) { 
//validacao anterior [a-z0-9\._-]
		$valida = "1";
	}
	else {
		$valida = "0"; 
	}
	return $valida; 
}

//HD 223175: Habilitando question�rio para todas as f�bricas
$habilita_questionario = true;

//HD 7277 Paulo - tirar acento do arquivo upload
function retira_acentos( $texto ){
 $array1 = array("�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�" , "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�","n�","N�");
 $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","n","N" );
 return str_replace( $array1, $array2, $texto );
}

if($_GET ['hd_chamado']) $hd_chamado = trim ($_GET ['hd_chamado']);
if($_POST['hd_chamado']) $hd_chamado = trim ($_POST['hd_chamado']);

if($_GET ['btn_acao']) $btn_acao = trim ($_GET ['btn_acao']);
if($_POST['btn_acao']) $btn_acao = trim ($_POST['btn_acao']);

if($_GET ['btn_resolvido']) $btn_resolvido = trim ($_GET ['btn_resolvido']);
if($_POST['btn_resolvido']) $btn_resolvido = trim ($_POST['btn_resolvido']);

if($_GET ['aguardando_resposta']) $aguardando_resposta = trim ($_GET ['aguardando_resposta']);

if($_GET ['msg'])        $msg        = trim ($_GET ['msg']);

if(strlen( $hd_chamado)>0 ){
	$sql="SELECT * from tbl_hd_chamado where hd_chamado=$hd_chamado and fabrica=$login_fabrica";
	$res=pg_exec($con,$sql);
	if(pg_numrows($res) ==0){
		header("Location: http://www.telecontrol.com.br");
		exit;
	}
}
if(strlen($btn_resolvido)>0){
	$sql= "UPDATE tbl_hd_chamado set resolvido = CURRENT_TIMESTAMP , exigir_resposta=null WHERE hd_chamado = $hd_chamado";
	$res = @pg_exec ($con,$sql);
}

if(strlen( $hd_chamado)>0 AND $aguardando_resposta=='1'){
	$sql= "UPDATE tbl_hd_chamado set resolvido = NULL, exigir_resposta = 't' 
			WHERE hd_chamado = $hd_chamado";
	$res = @pg_exec ($con,$sql);
	header("Location: chamado_lista.php?status=An�lise&exigir_resposta=t");
	exit;
}



if (strlen ($btn_acao) > 0) {

	if($_POST['comentario'])          { $comentario      = trim ($_POST['comentario']);}
	if($_POST['titulo'])              { $titulo          = trim ($_POST['titulo']);}
	if($_POST['categoria'])           { $categoria       = trim ($_POST['categoria']);}
	if($_POST['nome'])                { $nome            = trim ($_POST['nome']);}
	if($_POST['email'])               { $email           = trim ($_POST['email']);}
	if($_POST['fone'])                { $fone            = trim ($_POST['fone']);}
	if($_POST['status'])              { $status          = trim ($_POST['status']);}
	if($_POST['combo_tipo_chamado'])  { $xtipo_chamado   = trim ($_POST['combo_tipo_chamado']);}
	if(strlen($xtipo_chamado)==0)     { $xtipo_chamado   = trim ($_POST['combo_tipo_chamado_2']);}

	$necessidade = trim ($_POST['necessidade']);
	$funciona_hoje = trim ($_POST['funciona_hoje']);
	$objetivo = trim ($_POST['objetivo']);
	$local_menu = trim ($_POST['local_menu']);
	$http = trim ($_POST['http']);
	$tempo_espera = intval(trim ($_POST['tempo_espera']));
	$impacto = trim ($_POST['impacto']);

	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

/*--==VALIDA��ES=====================================================--*/

	#Nos casos de Reabrir camado, inserir um novo chamado e enviar email para Samuel - HD 16445
	//HD 197505: O chamado somente gerar� um novo chamado no caso do cliente j� ter aprovado o chamado anteriormente
	//			 Caso n�o tenha aprovado, gerar� uma intera��o no mesmo chamado e devolver� para execu��o
	$hd_chamado = trim ($_POST['hd_chamado']);
	//echo "chamado = :".$hd_chamado; exit;
	if(strlen($hd_chamado)>0){
		$sql= "
		SELECT
		hd_chamado,
		atendente,
		titulo

		FROM
		tbl_hd_chamado

		WHERE
		hd_chamado = $hd_chamado
		AND status = 'Resolvido'
		AND resolvido IS NOT NULL
		AND data_resolvido IS NOT NULL
		";
		$res = @pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			# HD 41597 - Francisco Ambrozio
			#   Quando o t�tulo anterior tinha aspas simples dava erro
			$titulo_anterior_tmp     = pg_result($res,0,titulo);
			$titulo_anterior         = str_replace("'", "", $titulo_anterior_tmp);
			$hd_chamado_anterior = $hd_chamado;
			$status_anterior = "REABRIR";
			$xtipo_chamado = "5"; #Chamado de erro caso for reaberto
			echo $hd_chamado; exit;
		}else{
			$sql= "
			SELECT
			hd_chamado,
			atendente,
			titulo

			FROM
			tbl_hd_chamado

			WHERE
			hd_chamado = $hd_chamado
			AND status = 'Resolvido'
			AND resolvido IS NULL
			AND data_resolvido IS NOT NULL
			";
			$res = @pg_exec ($con,$sql);
			if (pg_numrows($res) > 0) {
				# HD 41597 - Francisco Ambrozio
				#   Quando o t�tulo anterior tinha aspas simples dava erro
				$titulo_anterior_tmp     = pg_result($res,0,titulo);
				$titulo_anterior         = str_replace("'", "", $titulo_anterior_tmp);
				# se quiser reabrir o mesmo, setar a vari�vel "REABRIR MESMO"  caso contrario
				# somente reabrir....vai abrir um erro. COmo j� foi validado com o Suporte, n�o ser�
				# possivel reabrir...e se colocar reabrir...o chamado esta voltando para o admin que
				# desenvolveu...e isto nao poder�.
				#$status_anterior = "REABRIR MESMO";
				$status_anterior = "REABRIR";
			}
		}
	}elseif ($habilita_questionario) {
		//HD 218848: Cria��o do question�rio na abertura do Help Desk
		//HD 227276: Para chamado de erro o question�rio ser� reduzido
		if (intval($_POST["combo_tipo_chamado"]) == 5) {
		}
		else {
			if (strlen($necessidade) < 20) $msg_erro .= "<br>Descreva <u>O que voc� precisa que seja feito?</u> com no m�nimo 20 caracteres";
			if (strlen($funciona_hoje) < 10) $msg_erro .= "<br>Descreva <u>Como funciona hoje?</u> com no m�nimo 10 caracteres";
			if (strlen($objetivo) < 20) $msg_erro .= "<br>Descreva <u>Qual o objetivo desta solicita��o? Que problema visa resolver?</u> com no m�nimo 20 caracteres";
			if (strlen($impacto) < 3) $msg_erro .= "<br>Descreva <u>Esta rotina ter� impacto financeiro para a empresa? Por qu�?</u> com no m�nimo 3 caracteres";
		}

		if (strlen($local_menu) == 0) $msg_erro .= "<br>Escolha uma op��o em <u>Em que local do sistema voc� precisa de altera��o?</u>";
		if (strlen($http) < 10) $msg_erro .= "<br>Digite <u>Endere�o HTTP da tela aonde est� sendo solicitada a altera��o:</u> com no m�nimo 10 caracteres";

		//HD 218848: Quando for chamado de erro, � obrigat�rio enviar o printscreen da tela
		if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){
		}
		elseif (intval($_POST["combo_tipo_chamado"]) == 5) {
			$msg_erro = "Para chamado de erro, por favor, anexe um PrintScreen (imagem) da tela aonde o erro ocorreu";
		}
	}

	//SETA P/ USUARIO "SUPORTE"
	$fabricante_responsavel = 10;
	if (strlen ($atendente) == 0) $atendente = "435";

	if (strlen($comentario) < 2){
		$msg_erro="Coment�rio muito pequeno";
	}else{
	 	$comentario =  str_replace($filtro,"", $comentario);
	}


	if (strlen($xtipo_chamado) ==0){
		$msg_erro = "Escolha o tipo de chamado";
	}else{
	 	$xtipo_chamado =  str_replace($filtro,"", $xtipo_chamado);
	}

	if (strlen($fone) < 7){
		$msg_erro = "Entre com o n�mero do telefone!";
	}
	if (strlen($email) == 0){
		$msg_erro = "Por favor insira seu email!";
	}
	if (strlen($fone) == 0){
		$msg_erro = "Por favor insira um telefone para contato!";
	}
	if (strlen($titulo) < 5){
		$msg_erro = "T�tulo muito pequeno";
	}
	if (strlen($titulo) == 0){
			$msg_erro="Por favor insira um titulo!";
	}

	//CASO SEJA UM ERRO OU UMA ALTERA��O.
	/*	if($categoria=='Erro' OR $categoria=='Altera��o') $prioridade = 0;
		else                                              $prioridade = 5; 	*/

	if (strlen($msg_erro) == 0){
		$res = @pg_exec($con,"BEGIN TRANSACTION");


		//CASO A F�BRICA TENHA UM SUPERVISOR O CHAMADO VAI PARA AN�LISE DO MESMO
		if(strlen($hd_chamado)==0){

			$sql = "SELECT admin 
					FROM  tbl_admin 
					WHERE fabrica = $login_fabrica 
					AND   help_desk_supervisor is true;";

			$res = @pg_exec ($con,$sql);
//$tipo_chamado <> '5'  qdo eh 5  nao cai para aprovacao cai direto no hd 7863
			if (pg_numrows($res) > 0 and $xtipo_chamado <> '5') {
				
				$sql2="SELECT help_desk_supervisor
						FROM  tbl_admin
						WHERE fabrica = $login_fabrica
						AND   admin   = $login_admin";
				$res2=@pg_exec($con,$sql2);
				
				$help_desk_supervisor=pg_result($res2,0,help_desk_supervisor);

				if($help_desk_supervisor=='t'){
					//por causa da nova forma de help-desk, os chamados dos supervisores tamb�m ir�o para aprova��o
					$status='Aprova��o';
				}else{
					$status='Aprova��o';
				}
			}else{
				$status='An�lise';
			}

			$prioridade = 'f';

			if ($status_anterior=='REABRIR'){
				$prioridade = 't';
				$titulo     = $hd_chamado_anterior .'-'.$titulo_anterior ;

				$xcomentario=strtoupper($comentario);
				// HD 18929
				if(strlen($xcomentario) >0){
					$sql="CREATE TEMP TABLE tmp_comentario ( comentario text );
						INSERT INTO tmp_comentario (comentario)values('$xcomentario');	";
					$res=@pg_exec($con,$sql);
					
					$sql="SELECT * from tmp_comentario where (comentario ilike '%OK%' or comentario ilike '%OBRIGAD%')";
					$res=@pg_exec($con,$sql);
					if(pg_numrows($res) >0){
						$msg_erro="N�o � necess�rio responder o chamado com \"OK\" ou \"OBRIGADO\". Para reabrir o chamado basta colocar um coment�rio sem a palavra OK e Obrigado!";
					}
					
				}
			}
			// HD 20496 limitar o tamanho do titulo
			$titulo=substr($titulo,0,50);
			if(strlen($msg_erro) ==0 ){
				$sql =	"INSERT INTO tbl_hd_chamado (
							admin                                                        ,
							fabrica                                                      ,
							fabrica_responsavel                                          ,
							titulo                                                       ,
							atendente                                                    ,
							tipo_chamado                                                 ,
							prioridade                                                   ,
							status                                               
						) VALUES (
							$login_admin                                                 ,
							$login_fabrica                                               ,
							$fabricante_responsavel                                      ,
							'$titulo'                                                    ,
							$atendente                                                   ,
							$xtipo_chamado                                               ,
							'$prioridade',
							'$status'                                                    
						);";
	//echo $sql;
	//exit;
				$res = @pg_exec ($con,$sql);
				
				$msg_erro .= substr(pg_errormessage($con), 6);
				
				$res = @pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado')");
				$hd_chamado  = pg_result ($res,0,0);

				//HD 218848: Cria��o do question�rio na abertura do Help Desk
				if ($habilita_questionario) {
					$sql = "
					INSERT INTO
					tbl_hd_chamado_questionario (
					hd_chamado,
					necessidade,
					funciona_hoje,
					objetivo,
					local_menu,
					http,
					tempo_espera,
					impacto
					)

					VALUES (
					$hd_chamado,
					'$necessidade',
					'$funciona_hoje',
					'$objetivo',
					'$local_menu',
					'$http',
					'$tempo_espera',
					'$impacto'
					)
					";
					$res = @pg_exec ($con,$sql);
					$msg_erro .= substr(pg_errormessage($con), 6);
				}
				
				$dispara_email = "SIM";
			}//fim do inserir chamado
			

			/* Comentado a solicita��o do Samuel para n�o atualizar mais os dados
			$sql = "SELECT admin  FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
			$res = @pg_exec ($con,$sql);
			$xadmin                = pg_result($res,0,admin);
				if($xadmin ==$login_admin){
				$sql =	"UPDATE tbl_admin SET
							nome_completo               = '$nome'                      ,
							email                       = '$email'                     ,
							fone                        = '$fone'
						WHERE admin      = $login_admin";
		
				$res = @pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
				$msg_erro = substr($msg_erro,6);
			}*/
		}



		if(strlen($msg_erro)==0){
			if ($status_anterior == "REABRIR MESMO") {
				$sql = "UPDATE tbl_hd_chamado SET data_resolvido=NULL WHERE hd_chamado=$hd_chamado";
				$res = pg_query($con, $sql);
				$msg_erro .= pg_errormessage($con);
			}

			if ($status_anterior=='REABRIR'){

				$sql =	"INSERT INTO tbl_hd_chamado_item (
							hd_chamado                                                     ,
							comentario                                                     ,
							status_item                                                    ,
							admin                                                          
						) VALUES (
							$hd_chamado                                                    ,
							'Continua��o de atendimento do chamado N� $hd_chamado_anterior',
							'$status'                                                      ,
							435                                                            
						);";
				$res = @pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
				$msg_erro = substr($msg_erro,6);
			}
			$sql =	"INSERT INTO tbl_hd_chamado_item (
						hd_chamado                                                   ,
						comentario                                                   ,
						status_item                                                  ,
						admin                                                        
					) VALUES (
						$hd_chamado                                                  ,
						'$comentario'                                                ,
						'$status'                                                    ,
						$login_admin                                                  
					);";


			$res = @pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);

			$res = @pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado_item')");
			$hd_chamado_item  = pg_result ($res,0,0);

			$sql = " SELECT * FROM tbl_admin 
					 WHERE fabrica = $login_fabrica 
					 AND help_desk_supervisor = 't' ";

			$res = @pg_exec ($con,$sql);

	//QUANDO O CHAMADO FOR REABERTO SETA ELE EM AN�LISE
			if($status<>'Novo'and $status<>'Aprova��o'){
				//ENVIAR EMAIL SE O CHAMADO ESTAVA SETADO PARA EXIGIR RESPOSTA INFORMANDO O ANALISTA QUE O ADMIN RESPONDEU
				$sql    = "SELECT coalesce(exigir_resposta,'f') FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
				$res = @pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
				if (strlen($msg_erro) == 0) {
					$exigir_resposta = pg_result($res,0,0);
				}

				$sql    = "UPDATE tbl_hd_chamado SET status = 'An�lise', exigir_resposta = 'f' WHERE hd_chamado = $hd_chamado";
				$res = @pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
				
				$sql    = "UPDATE tbl_hd_chamado_item SET status_item = 'An�lise' WHERE hd_chamado_item = $hd_chamado_item";
				$res = @pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
			$msg_erro = substr($msg_erro,6);
		
		}
//ROTINA DE UPLOAD DE ARQUIVO
		if (strlen ($msg_erro) == 0) {
			$config["tamanho"] = 2048000; // Tamanho m�ximo do arquivo (em bytes) 

			if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){

				// Verifica o mime-type do arquivo
				//Estava comentado, Paulo tirou sob ordem de Sono
				echo  $arquivo["type"];
				if (!preg_match("/.*\/(pdf|msword|pjpeg|jpeg|png|gif|bmp|vnd.ms-excel|richtext|plain|html|zip|vnd.openxmlformats|vnd.ms-powerpoint)/", $arquivo["type"])){
					$msg_erro = "Arquivo em formato inv�lido!";
				} else { // Verifica tamanho do arquivo 
					if ($arquivo["size"] > $config["tamanho"])
						$msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no m�ximo 2MB. Envie outro arquivo.";
				}
				if (strlen($msg_erro) == 0) {
					// Pega extens�o do arquivo
					preg_match("/\.(pdf|doc|docx|gif|bmp|png|jpg|jpeg|rtf|xls|txt|zip|ppt){1}$/i", $arquivo["name"], $ext);
					$aux_extensao = "'".$ext[1]."'";
					
					$arquivo["name"]=retira_acentos($arquivo["name"]);
					$nome_sem_espaco = implode("", explode(" ",$arquivo["name"]));
					
					// Gera um nome �nico para a imagem
					$nome_anexo = "/www/assist/www/helpdesk/documentos/" . $hd_chamado_item."-".strtolower ($nome_sem_espaco);

					// Faz o upload da imagem
					if (strlen($msg_erro) == 0) {
						if (copy($arquivo["tmp_name"], $nome_anexo)) {
						}else{
							$msg_erro = "Arquivo n�o foi enviado!!!";
						}
					}//fim do upload da imagem
				}//fim da verifica��o de erro
			}//fim da verifica��o de existencia no apache
		}//fim de todo o upload

//FIM DO ANEXO DO ARQUIVO
	//ENVIA EMAIL PARA SUPERVISOR DA F�BRICA
		$sql="SELECT admin,
					 email
				FROM tbl_admin
				WHERE fabrica =$login_fabrica
				AND help_desk_supervisor is true";
		@$res=pg_query($con,$sql);
		if(pg_num_rows($res) > 0) {
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$email_supervisor=trim(pg_result($res,$i,email));
//AND $login_fabrica == 6 tirei, agora manda email para todos supervisores
				if( strlen($email_supervisor) > 0 AND strlen($dispara_email) > 0 AND strlen($msg_erro)==0 ){
					$email_origem  = "suporte@telecontrol.com.br";
					$assunto       = "Novo Chamado aberto";

					$corpo = "<br>Foi inserido um novo CHAMADO no HELP DESK do sistema TELECONTROL ASSIST e � necess�rio a sua an�lise para aprova��o.\n\n";
					$corpo.= "<br>Chamado n�: $hd_chamado\n\n";
					$corpo.= "<br>Titulo: $titulo \n";
					$corpo.= "<br>Solicitante: $nome <br>Email: $email\n\n";
					$corpo.= "<br><a href='http://www.telecontrol.com.br/assist/helpdesk/chamado_detalhe.php?hd_chamado=$hd_chamado'>CLIQUE AQUI PARA VER O CHAMADO</a> \n\n";
					$corpo.= "<br><br>Telecontrol\n";
					$corpo.= "<br>www.telecontrol.com.br\n";
					$corpo.= "<br>_______________________________________________\n";
					$corpo.= "<br>OBS: POR FAVOR N�O RESPONDA ESTE EMAIL.";

					$body_top  = "--Message-Boundary\n";
					$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
					$body_top .= "Content-transfer-encoding: 7BIT\n";
					$body_top .= "Content-description: Mail message body\n\n";
					
					if ( mail($email_supervisor, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " )){

					//	$msg .= "<br>Foi enviado um email para: ".$email_supervisor."<br>";
					}else{
						//$msg_erro = "N�o foi poss�vel enviar o email. Por favor entre em contato com a TELECONTROL.<br>";
					}

				}
			}
		}


	//ENVIA EMAIL PARA POSTO PRA CONFIRMA��O

		if( strlen($dispara_email) > 0 AND strlen($msg_erro)==0 ){

			$email_origem  = "suporte@telecontrol.com.br";
			$email_destino = "suporte@telecontrol.com.br";
			
			// HD  24442
			if($xtipo_chamado == '5'){ // se for chamado de erro manda email diferenciado
				$assunto       = "ERRO - Novo Chamado de ERRO aberto";
			}

			if ($status_anterior=='REABRIR'){
				$email_destino .= ', samuel@telecontrol.com.br';
				$assunto = "Chamado REABERTO - Referente ao Chamado ".$hd_chamado_anterior;
				$corpo = "";
				$corpo.= "<br>O chamado ".$hd_chamado_anterior." que estava RESOLVIDO foi reaberto.\n\n";
				$corpo.= "<br>Foi aberto um novo chamado com n�: ".$hd_chamado."\n\n";
				$corpo.= "<br>Titulo: ".$titulo." \n";
				$corpo.= "<br>Solicitante: ".$nome." <br>Email: ".$email."\n\n";
				$corpo.= "<br>Coment�rio inserido: <br><p><i>".$comentario."</i></p>\n\n";

				$corpo.= "<br><a href='http://www.telecontrol.com.br/assist/helpdesk/adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>CLIQUE AQUI PARA VER O CHAMADO</a> \n\n";
				$corpo.= "<br><br>Telecontrol\n";
				$corpo.= "<br>www.telecontrol.com.br\n";
				$corpo.= "<br>_______________________________________________\n";
				$corpo.= "<br>OBS: POR FAVOR N�O RESPONDA ESTE EMAIL.";

				$body_top  = "--Message-Boundary\n";
				$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
				$body_top .= "Content-transfer-encoding: 7BIT\n";
				$body_top .= "Content-description: Mail message body\n\n";
		//$corpo = $body_top.$corpo;

				if ( mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
					$msg .= "<br>Foi enviado um email para: ".$email_destino."<br>";
				}else{
					$msg_erro = "N�o foi poss�vel enviar o email. Por favor entre em contato com a TELECONTROL.<br>";
				}
			}
		}

		
		//ENVIA EMAIL PARA ANALISTA CASO O CHAMADO ESTEJA COMO EXIGIR RESPOSTA
		//if ($exigir_resposta == 't') {
		//ENVIAR EMAIL SEMPRE QUE ALGU�M FIZER INTERA��O 21/05/2010 - Samuel
		if (1 == 1) {
			$email_origem  = "suporte@telecontrol.com.br";
			$email_destino = "suporte@telecontrol.com.br";

			$sql = "SELECT email 
					FROM  tbl_admin 
					JOIN  tbl_hd_chamado ON tbl_hd_chamado.atendente = tbl_admin.admin
					WHERE tbl_hd_chamado.hd_chamado = $hd_chamado";
			$res = @pg_exec($con, $sql);
			if (pg_numrows($res) > 0) {
				$email_destino = trim(pg_result($res,0,0));
			}
			
			$assunto       = "Intera��o no chamado $hd_chamado";
			$corpo = "";
			$corpo.= "<br>O chamado $hd_chamado que estava aguardando resposta recebeu uma intera��o por parte do admin.\n\n";
			$corpo.= "<br>Chamado n�: $hd_chamado\n\n";
			$corpo.= "<br>Titulo: $titulo \n";
			$corpo.= "<br>Solicitante: $nome <br>Email: $email\n\n";
			$corpo.= "<br>Intera��o: $comentario\n\n";
			$corpo.= "<br><br>Telecontrol\n";
			$corpo.= "<br>www.telecontrol.com.br\n\n";
			$corpo.= "<br>_______________________________________________\n";
			$corpo.= "<br>OBS: POR FAVOR N�O RESPONDA ESTE EMAIL.";

			$body_top  = "--Message-Boundary\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";
			//$corpo = $body_top.$corpo;

			if ( mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
				$msg .= "<br>Foi enviado um email para: ".$email_destino."<br>";

			}
		}


		if(strlen($msg_erro) > 0){
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg_erro .= 'N�o foi poss�vel Inserir o Chamado. ';
		}else{
			$res = @pg_exec($con,"COMMIT");
		//		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			header ("Location: chamado_detalhe.php?hd_chamado=$hd_chamado&msg=$msg");
			exit;
		}
	}
}

if(strlen($hd_chamado)>0){
	//HD 197505 - Retiradas 16 linhas que faziam a atualiza��o autom�tica do campo tbl_hd_chamado.resolvido automaticamente
	//caso existam problemas, olhar arquivos nao_sync anteriores � resolu��o do chamado

	$sql= " SELECT tbl_hd_chamado.hd_chamado                              ,
					tbl_hd_chamado.admin                                 ,
					to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data   ,
					tbl_hd_chamado.titulo                                ,
					tbl_hd_chamado.categoria                             ,
					tbl_hd_chamado.status                                ,
					tbl_hd_chamado.atendente                             ,
					tbl_hd_chamado.fabrica_responsavel                   ,
					tbl_hd_chamado.resolvido                             ,
					tbl_hd_chamado.tipo_chamado                          ,
					tbl_fabrica.nome                                     ,
					tbl_admin.login                                      ,
					tbl_admin.nome_completo                              ,
					tbl_admin.fone                                       ,
					tbl_admin.email                                      ,
					at.nome_completo AS atendente_nome
			FROM tbl_hd_chamado
			JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
			JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica
			LEFT JOIN tbl_admin at ON tbl_hd_chamado.atendente = at.admin
			WHERE hd_chamado = $hd_chamado";

	$res = @pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$hd_chamado           = pg_result($res,0,hd_chamado);
		$admin                = pg_result($res,0,admin);
		$data                 = pg_result($res,0,data);
		$titulo               = pg_result($res,0,titulo);
		$categoria            = pg_result($res,0,categoria);
		$status               = pg_result($res,0,status);
		$atendente            = pg_result($res,0,atendente);
		$resolvido            = pg_result($res,0,resolvido);
		$fabrica_responsavel  = pg_result($res,0,fabrica_responsavel);
		$nome                 = pg_result($res,0,nome_completo);
		$email                = pg_result($res,0,email);
		$fone                 = pg_result($res,0,fone);
		$fabrica_nome         = pg_result($res,0,nome);
		$login                = pg_result($res,0,login);
		$atendente_nome       = pg_result($res,0,atendente_nome);
		$tipo_chamado         = pg_result($res,0,tipo_chamado);

		//HD 218848: Cria��o do question�rio na abertura do Help Desk
		$sql = "SELECT * FROM tbl_hd_chamado_questionario WHERE hd_chamado=$hd_chamado";
		$res = pg_query($con, $sql);

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
		$msg_erro .="Chamado n�o encontrado";
	}
}else{
	if ($habilita_questionario) {
		$mostra_questionario = true;
	}
	
	$status="Novo";
	$login = $login_login;
	$data = date("d/m/Y");
	$sql = "SELECT * FROM tbl_admin WHERE admin = $login_admin";
	$resX = @pg_exec ($con,$sql);

	$nome                 = pg_result($resX,0,nome_completo);
	$email                = pg_result($resX,0,email);
	$fone                 = pg_result($resX,0,fone);

}

$TITULO = "Lista de Chamadas - Telecontrol Help-Desk";
if($sistema_lingua == 'ES') $TITULO = "Lista de llamados - Telecontrol Help-Desk";
$ONLOAD = "frm_chamado.titulo.focus()";
if (strlen ($hd_chamado) > 0) $ONLOAD = "";
include "menu.php";
?>
<style>
.btn{

	font-size: 12px;
	font-family: Arial;
	color:#00CC00;
	font-weight: bold;
}

.questionarioCaixa{
        BORDER-RIGHT: #6699CC 1px solid;
        BORDER-TOP: #6699CC 1px solid;
        FONT: 8pt Arial ;
        BORDER-LEFT: #6699CC 1px solid;
        BORDER-BOTTOM: #6699CC 1px solid;
        BACKGROUND-COLOR: #FFFFFF;
		width: 500px;
}

</style>

<script language=javascript>

function verificaTipoChamado() {
	switch ($("#combo_tipo_chamado").val()) {
		case "5":
			if ($("#necessidade").val() == "") {
				$(".escondeparaerro").css("display", "none");
				$("#tempo_espera").val("0");
		}
		break;

		default:
			$(".escondeparaerro").css("display", "table-row");
			$("#tempo_espera").val("7");
	}
}

$(document).ready( function () {
	verificaTipoChamado();
})

</script>

<form name='frm_chamado' action='<? echo $PHP_SELF ?>' method='POST' enctype='multipart/form-data' >
<input type='hidden' name='hd_chamado' value='<?= $hd_chamado ?>'>
<input type='hidden' name='status' value='<?= $status ?>'>

<table width = '750' align = 'center' border='0' cellpadding='2'  style='font-family: verdana ; font-size: 11px'>

<?
if (strlen ($hd_chamado) > 0) {
	//echo "<tr>";
	//echo "<td colspan='4' align='center' class = 'Titulo2' height='30'><strong>Chamado n�. $hd_chamado </strong></td>";
	//echo "</tr>";
}
if (strlen($msg)>0){
	echo "<p>".$msg."</p>";
}
?>

<tr>
	<td  width="140"bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Abertura </strong></td>
	<td      bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><?= $data ?> </td>
	
	<td  width="100" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px' valign='middle'><strong><?if($sistema_lingua=='ES')echo "Llamado";else echo "Chamado";?></strong></td>
	<td     width="100"         bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;
	<? if(strlen($hd_chamado)>0){ ?>
	<font color='#CC1136'><strong>&nbsp;<?=$hd_chamado?> </strong></font>
	<?}?>
	</td>
	
</tr>

<?
if (strlen ($hd_chamado) > 0) {
	if($sistema_lingua == "ES"){
		if($status=="Aprova��o") $status="aprobaci�n:";
		if($status=="An�lise")   $status="Analisis";
		if($status=="Execu��o")  $status="Ejecuci�n";
		if($status=="Novo")      $status="Nuevo";
		if($status=="Resolvido") $status="Resuelto";
	}

	?>
	<tr>
		<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Status </strong></td>
		<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<?= $status ?> </td>
		<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Analista </strong></td>
		<td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;<?=$atendente_nome?> </strong></td>
	</tr>
<? } ?>

<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;<?if($sistema_lingua=='ES')echo "Nombre Usuario";else echo "Nome Usu�rio";?> </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<input type='text' size='60' maxlength='100'  name='nome' value='<?= $nome ?>' class='Caixa'></td>
	<td  bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Login </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<?= $login ?> </td>
</tr>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;<?if($sistema_lingua=='ES')echo "Correo";else echo "e-mail";?> </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<input type='text' size='60' name='email' maxlength='100' value='<?=$email ?>' class='Caixa'></td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;<?if($sistema_lingua=='ES')echo "Tel�fono";else echo "Fone";?> </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<input type='text' size='20' maxlength='20' name='fone' value='<?=$fone ?>' class='Caixa'></td>
</tr>


<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;T�tulo </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<input type='text' size='60' name='titulo' maxlength='50' value='<?= $titulo ?>' <? if (strlen ($hd_chamado) > 0) echo " readonly " ?> class='Caixa'> </td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Tipo </strong></td>

	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
<?
	$sql = "SELECT	tipo_chamado,
					descricao 
			FROM tbl_tipo_chamado 
			ORDER BY descricao;";
	$res = @pg_exec($con,$sql);
	if(pg_numrows($res)>0){
if (strlen ($hd_chamado) > 0 and strlen($tipo_chamado)>0){ echo " <input type='hidden' size='60' name='combo_tipo_chamado_2'  value='$tipo_chamado' >"; }
		echo "<select name=\"combo_tipo_chamado\" id=\"combo_tipo_chamado\" size=\"1\" onchange='verificaTipoChamado()' ";
		if (strlen ($hd_chamado) > 0 and strlen($tipo_chamado)>0){ echo " disabled "; }
		echo " class='Caixa'>";
	//	echo "<option></option>";
		if($sistema_lingua<>"ES"){
			for($i=0;pg_numrows($res)>$i;$i++){
				$xtipo_chamado = pg_result($res,$i,tipo_chamado);
				$xdescricao    = pg_result($res,$i,descricao);
				echo "<option value='$xtipo_chamado' ";	
				if($tipo_chamado == $xtipo_chamado){echo " SELECTED ";}
				echo " >$xdescricao</option>";
			}
		}else{
			echo "<option value='1' ";
			if($tipo_chamado == '1'){  echo ' SELECTED ';}
			echo " >Alteraci�n de datos</option>";

			echo "<option value='5' ";
			if($tipo_chamado == '5'){  echo ' SELECTED ';}
			echo " >Error en programa</option>";

			echo "<option value='2' ";
			if($tipo_chamado == '2'){  echo ' SELECTED ';}
			echo " >Cambio de pantalla o proceso</option>";

			echo "<option value='4' ";
			if($tipo_chamado == '4'){  echo ' SELECTED ';}
			echo " >Nuevo programa o proceso</option>";

			echo "<option value='3' ";
			if($tipo_chamado == '3'){  echo ' SELECTED ';}
			echo " >Sugesti�n de mejor�a</option>";
		}
		echo "</select>";
	}
?>
		
		
		
	</td>


</tr>

<!-- HD 218848: Cria��o do question�rio na abertura do Help Desk -->
<?
if ($mostra_questionario) {
	if (strlen($hd_chamado)) {
		$desabilita_questionario = "readonly";
		$desabilita_questionario_combo = "disabled";
	}
?>
<tr class="escondeparaerro">
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;O que voc� precisa que seja feito?</strong>
	</td>
</tr>
<tr class="escondeparaerro">
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
		<textarea <?=$desabilita_questionario?> name='necessidade' id='necessidade' class='questionarioCaixa' cols=90 rows=5><? echo $necessidade; ?></textarea>
	</td>
</tr>

<tr class="escondeparaerro">
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;Como funciona hoje?</strong>
	</td>
</tr>
<tr class="escondeparaerro">
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
		<textarea <?=$desabilita_questionario?> name='funciona_hoje' id='funciona_hoje' class='questionarioCaixa' cols=90 rows=5><? echo $funciona_hoje; ?></textarea>
	</td>
</tr>

<tr class="escondeparaerro">
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;Qual o objetivo desta solicita��o? Que problema visa resolver?</strong>
	</td>
</tr>
<tr class="escondeparaerro">
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
		<textarea <?=$desabilita_questionario?> name='objetivo' id='objetivo' class='questionarioCaixa' cols=90 rows=5><? echo $objetivo; ?></textarea>
	</td>
</tr>

<tr class="escondeparaerro">
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;Esta rotina ter� impacto financeiro para a empresa? Por qu�?</strong>
	</td>
</tr>
<tr class="escondeparaerro">
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
		<textarea <?=$desabilita_questionario?> name='impacto' id='impacto' class='questionarioCaixa' cols=90 rows=5><? echo $impacto; ?></textarea>
	</td>
</tr>

<tr>
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;Em que local do sistema voc� precisa de altera��o?</strong>
	</td>
</tr>
<tr>
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
		<select <?=$desabilita_questionario_combo?> name="local_menu" id="local_menu" class='questionarioCaixa' style='color: #000000;'>
			<option value="">..... Escolha .....</option>
			<option value="admin_gerencia" <? if ($local_menu == "admin_gerencia") echo "selected" ?>>Administra��o: Ger�ncia</option>
			<option value="admin_callcenter" <? if ($local_menu == "admin_callcenter") echo "selected" ?>>Administra��o: CallCenter</option>
			<option value="admin_cadastro" <? if ($local_menu == "admin_cadastro") echo "selected" ?>>Administra��o: Cadastro</option>
			<option value="admin_infotecnica" <? if ($local_menu == "admin_infotecnica") echo "selected" ?>>Administra��o: Info T�cnica</option>
			<option value="admin_financeiro" <? if ($local_menu == "admin_financeiro") echo "selected" ?>>Administra��o: Financeiro</option>
			<option value="admin_auditoria" <? if ($local_menu == "admin_auditoria") echo "selected" ?>>Administra��o: Auditoria</option>
			<option value="posto_os" <? if ($local_menu == "posto_os") echo "selected" ?>>�rea do Posto: Ordem de Servi�o</option>
			<option value="posto_infotecnica" <? if ($local_menu == "posto_infotecnica") echo "selected" ?>>�rea do Posto: Info T�cnica</option>
			<option value="posto_pedidos" <? if ($local_menu == "posto_pedidos") echo "selected" ?>>�rea do Posto: Pedidos</option>
			<option value="posto_cadastro" <? if ($local_menu == "posto_cadastro") echo "selected" ?>>�rea do Posto: Cadastro</option>
			<option value="posto_tabelapreco" <? if ($local_menu == "posto_tabelapreco") echo "selected" ?>>�rea do Posto: Tabela Pre�o</option>
		</select>
	</td>
</tr>

<tr class="escondeparaerro">
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;Quanto tempo � poss�vel esperar por esta mudan�a?</strong>
	</td>
</tr>
<tr class="escondeparaerro">
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
		<select <?=$desabilita_questionario_combo?> name="tempo_espera" id="tempo_espera" class='questionarioCaixa' style='color: #000000;'>
		<?
		if ($tempo_espera == "") $tempo_espera = "7";
		?>
			<option value="0" <? if ($tempo_espera == "0") echo "selected" ?>>Imediato</option>
			<option value="1" <? if ($tempo_espera == "1") echo "selected" ?>>1 Dia</option>
			<option value="2" <? if ($tempo_espera == "2") echo "selected" ?>>2 Dias</option>
			<option value="3" <? if ($tempo_espera == "3") echo "selected" ?>>3 Dias</option>
			<option value="4" <? if ($tempo_espera == "4") echo "selected" ?>>4 Dias</option>
			<option value="5" <? if ($tempo_espera == "5") echo "selected" ?>>5 Dias</option>
			<option value="6" <? if ($tempo_espera == "6") echo "selected" ?>>6 Dias</option>
			<option value="7" <? if ($tempo_espera == "7") echo "selected" ?>>1 Semana</option>
			<option value="14" <? if ($tempo_espera == "14") echo "selected" ?>>2 Semanas</option>
			<option value="21" <? if ($tempo_espera == "21") echo "selected" ?>>3 Semanas</option>
			<option value="30" <? if ($tempo_espera == "30") echo "selected" ?>>1 M�s</option>
			<option value="60" <? if ($tempo_espera == "60") echo "selected" ?>>2 Meses</option>
			<option value="90" <? if ($tempo_espera == "90") echo "selected" ?>>3 Meses</option>
			<option value="180" <? if ($tempo_espera == "180") echo "selected" ?>>6 Meses</option>
			<option value="360" <? if ($tempo_espera == "360") echo "selected" ?>>1 Ano</option>
		</select>
	</td>
</tr>

<tr>
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;Endere�o HTTP da tela onde est� sendo solicitada a altera��o:</strong>
	</td>
</tr>
<tr>
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px;' align='center'>
		<input <?=$desabilita_questionario?> size=90 type="text" name='http' id='http' value='<?= $http ?>' class='questionarioCaixa' />
	</td>
</tr>
<?
}	//if ($mostra_questionario) {

?>

</table>
<?

$sql= "SELECT   tbl_hd_chamado_item.hd_chamado_item,
		to_char (tbl_hd_chamado_item.data,'DD/MM HH24:MI') AS data   ,
				tbl_hd_chamado_item.comentario                            ,
				tbl_hd_chamado_item.admin                                 ,
				tbl_admin.nome_completo AS autor                          
		FROM tbl_hd_chamado_item 
		JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_item.admin
		WHERE hd_chamado = $hd_chamado
		AND interno is not true
		ORDER BY hd_chamado_item";
$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {
	echo "<BR><table width = '750' align = 'center' border='0' cellpadding='0' cellspacing='0' style='font-family: verdana ; font-size: 11px'>";
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='7' align = 'center' width='100%' style='font-size:14px;color:#666666'><b>";
  if($sistema_lingua == 'ES') echo "Interacciones";
  else                        echo "Intera��es";
  echo "</b></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "<tr bgcolor='#D9E8FF' style='color: #666666'>";
	echo "<td ><strong align='center'>N� </strong></td>";
	echo "<td ><img src='/assist/imagens/pixel.gif' width='10'></td>";
	echo "<td nowrap align='center'><strong>";
	if($sistema_lingua == 'ES') echo "Fecha";
	else                        echo "Data";
	echo "</strong></td>";
	echo "<td ><img src='/assist/imagens/pixel.gif' width='10'></td>";
	echo "<td align='center' ><strong>";
	if($sistema_lingua == 'ES') echo "Comentario";
  else                        echo "Coment&aacute;rio";
  echo "</strong></td>";
	echo "<td  nowrap align='center'><img src='/assist/imagens/pixel.gif' width='10'><strong>Anexo </strong></td>";
	echo "<td nowrap align='center'><strong>Autor </strong></td>";
	echo "</tr>";
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$x=$i+1;
		$hd_chamado_item = pg_result($res,$i,hd_chamado_item);
		$data_interacao  = pg_result($res,$i,data);
		$admin           = pg_result($res,$i,admin);
		$autor           = pg_result($res,$i,autor);
		$item_comentario = pg_result($res,$i,comentario);

		$sql2 = "SELECT fabrica FROM tbl_admin WHERE admin = $admin";
		//echo $sql2;
		$res2 = @pg_exec ($con,$sql2);
		$fabrica_autor = pg_result($res2,0,0);
		//if($fabrica_autor==10) $autor="Suporte";
		$cor='#ffffff';
		if ($i % 2 == 0) $cor = '#F2F7FF';

		echo "<tr  style=' height='25' bgcolor='$cor'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td nowrap width='20'>$x </td>";
		echo "<td></td>";
		echo "<td nowrap>$data_interacao </td>";
		echo "<td></td>";
		echo "<td >" . nl2br ($item_comentario) . "</td>";

		echo "<td>";
		$dir = "documentos/";
		$dh  = opendir($dir);
//		echo "$hd_chamado_item";
		while (false !== ($filename = readdir($dh))) {
			if (strpos($filename,"$hd_chamado_item") !== false){
			//echo "$filename\n\n";
				$po = strlen($hd_chamado_item);
				if(substr($filename, 0,$po)==$hd_chamado_item){

					echo "<!--ARQUIVO-I-->&nbsp;&nbsp;<a href=documentos/$filename target='blank'><img src='imagem/clips.gif' border='0'>Baixar</a>&nbsp;&nbsp;<!--ARQUIVO-F-->";
				}
				
			}
		}
		echo "</td>";
		echo "<td nowrap > $autor</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
	}
	
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='7' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>";
}

$permissao = 'sim';

if (strlen($hd_chamado)>0 AND $status == 'Resolvido') {
	$sql= "SELECT	TO_CHAR (data,'DD/MM HH24:MI') AS data,
					CASE WHEN CURRENT_DATE - data::DATE > 14 THEN 'nao' ELSE 'sim' END AS permissao
			FROM tbl_hd_chamado_item 
			WHERE hd_chamado = $hd_chamado
			AND interno IS NOT TRUE
			ORDER BY tbl_hd_chamado_item.data DESC
			LIMIT 1";
	$res = @pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
		$data_ultima_interacao = pg_result($res,0,data);
		$permissao             = pg_result($res,0,permissao);
	}
}

echo "<center>";

if (strlen ($hd_chamado) > 0) {
	echo "<br>";
	if ($status == 'Resolvido') {
		echo "<b><font face='verdana' color='#666666'>";
		if($sistema_lingua == "ES") echo "Este llamado esta resolvido";
		else                        echo "Este chamado est� resolvido.";
		echo "</font></b><br>";

		if ($permissao == 'sim'){
			echo "<b><font face='verdana' color='#6600FF' size='-1'>";
			if($sistema_lingua == "ES"){
				echo "Si no concordas con la soluci�n, puede reabrirlo digitando una mensaje abajo";
			}else{
				echo "Para novas intera��es sobre este chamado, digite uma mensagem abaixo.<br> ";
			}
			echo "</font><font face='verdana' color='#00CC00' size='-1'><br>";

			if (strlen($resolvido)== 0){ 
				if($sistema_lingua == "ES") {
					echo "Si concordas con la soluci�n haga un click no bot�n RESOLVIDO";
				}else{
					echo "Se voc� concorda com a solu��o clique no bot�o RESOLVIDO";
				}
			}
			echo "</font></b><br>";
		}
	}else{
		echo "<b><font face='verdana' color='#666666'>";
		if($sistema_lingua == 'ES') echo "Digite el texto para continuar el llamado";
		else                        echo "Digite o texto para dar continuidade ao chamado";
		echo "</font></b><br>";
		if ($status == 'Aprova��o') {
			echo "<font color='red'>";
			if($sistema_lingua == 'ES') echo "�El responsable del 'Help-Desk' debe APROBAR la solicitud [chamado] para que Telecontrol pueda proseguir";
			else                        echo "O respons�vel pelo Help-Desk na F�brica precisa APROVAR o chamado para o Telecontrol dar continuidade!";
			echo "</font>";
		}

	}
}else{
	echo "<b><font face='verdana' color='#666666'>";
	echo "Digite o texto do seu chamado";
	echo "</b></font><br>";
}

if ($permissao == 'sim' ){
	echo "<table width = '750' align = 'center' cellpadding='2'  style=' font-size: 11px'>";
	echo "<tr>";
	echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>";
	echo "<textarea name='comentario' cols='90' rows='10' class='questionarioCaixa' wrap='VIRTUAL'>".$comentario."</textarea><br>";
	echo "<script language=\"JavaScript1.2\">editor_generate('comentario');</script>";

	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>";
	if($sistema_lingua == 'ES') echo "Archivo";
	else                        echo "Arquivo ";
	echo "<input type='file' name='arquivo' size='70' class='Caixa'";
	#if (strlen($resolvido) > 0){ echo "DISABLED";}
	echo">";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td align='center'>";
	echo "<font color='red' size=1>O ARQUIVO N�O SER� ANEXADO CASO O TAMANHO EXCEDA O LIMITE DE 2 MB.</font> ";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
if($status=='Resolvido' AND  strlen($resolvido)==0){
	echo "<input type='submit' name='btn_resolvido' value='";
	if($sistema_lingua == "ES") echo "RESOLVIDO - Concordo com la soluci�n";
	else                        echo "RESOLVIDO - CONCORDO COM A SOLU��O...";
	echo "' class='btn' ><br>";
}

if ($permissao == 'sim' ){
	echo "<input type='submit' name='btn_acao' value='";
	if($sistema_lingua == 'ES') echo "Enviar llamado";
	else                        echo "Enviar Chamado";
	echo "'";
	#if (strlen($resolvido) > 0) { echo "DISABLED";}
	echo ">";
}
echo "</center>";
?>
		</td>
	</tr>
</table>
</form>

<? include "rodape.php" ?>
