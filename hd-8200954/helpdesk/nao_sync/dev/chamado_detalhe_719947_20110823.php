<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

function is_email($email=""){   // False se não bate...
	if (!$email) return false;
	return (preg_match("/^([0-9a-zA-Z]+([_.-]?[0-9a-zA-Z]+)*@[0-9a-zA-Z]+[0-9,a-z,A-Z,.,-]*(.){1}[a-zA-Z]{2,4})+$/", $email));
}

//HD 223175: Habilitando questionário para todas as fábricas
$habilita_questionario = true;

//HD 7277 Paulo - tirar acento do arquivo upload
function retira_acentos( $texto ){
	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
	return str_replace( $array1, $array2, $texto );
}

if ($_GET ['hd_chamado'])          $hd_chamado          = trim($_GET['hd_chamado']);
if ($_POST['hd_chamado'])          $hd_chamado          = trim($_POST['hd_chamado']);
if ($_GET ['btn_acao'])            $btn_acao            = trim($_GET['btn_acao']);
if ($_POST['btn_acao'])            $btn_acao            = trim($_POST['btn_acao']);
if ($_GET ['btn_resolvido'])       $btn_resolvido       = trim($_GET['btn_resolvido']);
if ($_POST['btn_resolvido'])       $btn_resolvido       = trim($_POST['btn_resolvido']);
if ($_GET ['aguardando_resposta']) $aguardando_resposta = trim($_GET['aguardando_resposta']);
if ($_GET ['msg'])                 $msg                 = trim($_GET['msg']);

if (strlen($hd_chamado) > 0) {

	$sql = "SELECT * from tbl_hd_chamado where hd_chamado=$hd_chamado and fabrica=$login_fabrica";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 0) {
		header("Location: http://www.telecontrol.com.br");
		exit;
	}

}

if (strlen($btn_resolvido) > 0) {
	$sql= "UPDATE tbl_hd_chamado set resolvido = CURRENT_TIMESTAMP , exigir_resposta=null WHERE hd_chamado = $hd_chamado";
	$res = @pg_exec ($con,$sql);
}

if (strlen($hd_chamado) > 0 AND $aguardando_resposta == '1') {

	$sql= "UPDATE tbl_hd_chamado set resolvido = NULL, exigir_resposta = 't' 
			WHERE hd_chamado = $hd_chamado";

	$res = @pg_exec ($con,$sql);
	header("Location: chamado_lista.php?status=Análise&exigir_resposta=t");
	exit;

}

if (strlen ($btn_acao) > 0) {

	if ($_POST['comentario'])          { $comentario      = trim($_POST['comentario']);}
	if ($_POST['titulo'])              { $titulo          = trim($_POST['titulo']);}
	if ($_POST['categoria'])           { $categoria       = trim($_POST['categoria']);}
	if ($_POST['nome'])                { $nome            = trim($_POST['nome']);}
	if ($_POST['email'])               { $email           = trim($_POST['email']);}
	if ($_POST['fone'])                { $fone            = trim($_POST['fone']);}
	if ($_POST['status'])              { $status          = trim($_POST['status']);}
	if ($_POST['combo_tipo_chamado'])  { $xtipo_chamado   = trim($_POST['combo_tipo_chamado']);}
	if (strlen($xtipo_chamado)==0)     { $xtipo_chamado   = trim($_POST['combo_tipo_chamado_2']);}

	$necessidade	= trim($_POST['necessidade']);
	$funciona_hoje	= trim($_POST['funciona_hoje']);
	$objetivo		= trim($_POST['objetivo']);
	$local_menu		= trim($_POST['local_menu']);
	$http			= trim($_POST['http']);
	$tempo_espera	= intval(trim ($_POST['tempo_espera']));
	$impacto		= trim($_POST['impacto']);
	$arquivo		= isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

	#Nos casos de Reabrir camado, inserir um novo chamado e enviar email para Samuel - HD 16445
	//HD 197505: O chamado somente gerará um novo chamado no caso do cliente já ter aprovado o chamado anteriormente
	//			 Caso não tenha aprovado, gerará uma interação no mesmo chamado e devolverá para execução
	// 13/08/2010:  Ébano: SEM CHAMADO: A pedido do Samuel, se reabrir chamado, deve ser como antes: abrir novo
	//				chamado e deve ser de erro
	$hd_chamado = trim ($_POST['hd_chamado']);

	if (strlen($hd_chamado) > 0) {
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
		AND data_resolvido IS NOT NULL
		";
		$res = @pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {

			# HD 41597 - Francisco Ambrozio
			#   Quando o título anterior tinha aspas simples dava erro
			$titulo_anterior	= str_replace("'", "", pg_result($res,0,titulo));
			$hd_chamado_anterior= $hd_chamado;
			$hd_chamado			= "";
			$status_anterior	= "REABRIR";
			$xtipo_chamado		= 5; #Chamado de erro caso for reaberto

		}

	} else if ($habilita_questionario) {

		//HD 218848: Criação do questionário na abertura do Help Desk
		//HD 227276: Para chamado de erro o questionário será reduzido
		if (intval($_POST["combo_tipo_chamado"]) != 5) {

			if (strlen($necessidade) < 20)	 $msg_erro .= "<br>Descreva <u>O que você precisa que seja feito?</u> com no mínimo 20 caracteres";
			if (strlen($funciona_hoje) < 10) $msg_erro .= "<br>Descreva <u>Como funciona hoje?</u> com no mínimo 10 caracteres";
			if (strlen($objetivo) < 20)		 $msg_erro .= "<br>Descreva <u>Qual o objetivo desta solicitação? Que problema visa resolver?</u> com no mínimo 20 caracteres";
			if (strlen($impacto) < 3)		 $msg_erro .= "<br>Descreva <u>Esta rotina terá impacto financeiro para a empresa? Por quê?</u> com no mínimo 3 caracteres";

		}

		if (strlen($local_menu) == 0)	 $msg_erro .= "<br>Escolha uma opção em <u>Em que local do sistema você precisa de alteração?</u>";
		if (strlen($http) < 10)			 $msg_erro .= "<br>Digite <u>Endereço HTTP da tela aonde está sendo solicitada a alteração:</u> com no mínimo 10 caracteres";

		//HD 218848: Quando for chamado de erro, é obrigatório enviar o printscreen da tela
		if (intval($_POST["combo_tipo_chamado"]) == 5) {
			if (!is_array($arquivo)) $msg_erro .= "Para chamado de erro, por favor, anexe um <i>PrintScreen</i> (imagem, de preferência) da tela aonde o erro ocorreu<br>";
		}

	}

	if (strlen($xtipo_chamado) == 0) {
		$msg_erro .= "Escolha o tipo de chamado";
	}

	if (strlen($titulo) == 0 and empty($hd_chamado)) {//HD 711738
		$msg_erro .= "<br />Por favor insira um titulo!";
	} else if (strlen($titulo) < 5 and empty($hd_chamado)) {//HD 711738
		$msg_erro .= "<br />Título muito pequeno";
	}

	//SETA P/ USUARIO "SUPORTE"
	$fabricante_responsavel = 10;
	if (strlen ($atendente) == 0) $atendente = "435";

	if (strlen($comentario) < 2) {
		$msg_erro .= "<br />Comentário muito pequeno";
	} else {
	 	$comentario = str_replace($filtro, '', $comentario);
	}

	//CASO SEJA UM ERRO OU UMA ALTERAÇÃO.
	/*	if($categoria=='Erro' OR $categoria=='Alteração') $prioridade = 0;
		else                                              $prioridade = 5; 	*/

	if (strlen($msg_erro) == 0) {

		$res = @pg_exec($con,"BEGIN TRANSACTION");

		//CASO A FÁBRICA TENHA UM SUPERVISOR O CHAMADO VAI PARA ANÁLISE DO MESMO
		if (strlen($hd_chamado) == 0) {

			$sql = "SELECT admin FROM  tbl_admin WHERE fabrica = $login_fabrica AND help_desk_supervisor IS TRUE;";
			$res = @pg_exec($con, $sql);
			//$tipo_chamado <> '5'  qdo eh 5  nao cai para aprovacao cai direto no hd 7863

			if (pg_numrows($res) > 0 and $xtipo_chamado <> '5' and $xtipo_chamado <> '6') {

				$sql2 = "SELECT help_desk_supervisor FROM  tbl_admin WHERE fabrica = $login_fabrica AND admin = $login_admin";
				$res2 = @pg_exec($con, $sql2);
				
				$help_desk_supervisor = pg_result($res2, 0, 'help_desk_supervisor');

				if ($help_desk_supervisor == 't') {
					//por causa da nova forma de help-desk, os chamados dos supervisores também irão para aprovação
					$status='Aprovação';
				} else {
					$status='Aprovação';
				}

			} else {
				$status='Análise';
			}

			$prioridade = 'f';

			if ($status_anterior == 'REABRIR') {

				$prioridade = 't';
				$titulo     = $hd_chamado_anterior .'-'.$titulo_anterior ;

				$xcomentario=strtoupper($comentario);
				// HD 18929
				if (strlen($xcomentario) > 0) {

					$sql = "CREATE TEMP TABLE tmp_comentario ( comentario text ); INSERT INTO tmp_comentario (comentario)values('$xcomentario');";
					$res = @pg_exec($con,$sql);
					
					$sql = "SELECT * from tmp_comentario where (comentario ilike '%OK%' or comentario ilike '%OBRIGAD%')";
					$res = @pg_exec($con, $sql);

					if (pg_numrows($res) > 0) {
						$msg_erro = "Não é necessário responder o chamado com \"OK\" ou \"OBRIGADO\". Para reabrir o chamado basta colocar um comentário sem a palavra OK e Obrigado!";
					}
					
				}

			}

			// HD 20496 limitar o tamanho do titulo
			$titulo =  pg_escape_string(substr($titulo,0,50)); //HD 307589 - Sobre o erro de 'título muito pequeno'

			if (strlen($msg_erro) == 0) {

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

				$res       = @pg_exec($con, $sql);
				$msg_erro .= substr(pg_errormessage($con), 6);
				
				$res        = @pg_exec($con, "SELECT CURRVAL ('seq_hd_chamado')");
				$hd_chamado = pg_result($res, 0, 0);

				//HD 218848: Criação do questionário na abertura do Help Desk
				if ($habilita_questionario) {

					$sql = " INSERT INTO tbl_hd_chamado_questionario (
								hd_chamado,
								necessidade,
								funciona_hoje,
								objetivo,
								local_menu,
								http,
								tempo_espera,
								impacto
							) VALUES (
								$hd_chamado,
								'$necessidade',
								'$funciona_hoje',
								'$objetivo',
								'$local_menu',
								'$http',
								'$tempo_espera',
								'$impacto'
								)";

					$res = @pg_exec ($con,$sql);
					$msg_erro .= substr(pg_errormessage($con), 6);
				}
				
				$dispara_email = "SIM";

			}//fim do inserir chamado

		}

		if (strlen($msg_erro) == 0) {

			if ($status_anterior == 'REABRIR') {

				$sql = "UPDATE tbl_hd_chamado SET resolvido=NOW() WHERE hd_chamado = $hd_chamado_anterior";
				$res = @pg_exec($con, $sql);

				if (!is_resource($res)) $msg_erro .= substr(pg_last_error($con), 6);

				$sql =	"INSERT INTO tbl_hd_chamado_item (
							hd_chamado                                                     ,
							comentario                                                     ,
							status_item                                                    ,
							admin                                                          
						) VALUES (
							$hd_chamado                                                    ,
							'Continuação de atendimento do chamado Nº $hd_chamado_anterior',
							'$status'                                                      ,
							435                                                            
						);";

				$res = @pg_exec ($con, $sql);

				if (!is_resource($res)) $msg_erro .= substr(pg_last_error($con),6);

			}

			$sql = "INSERT INTO tbl_hd_chamado_item (
						hd_chamado    ,
						comentario    ,
						status_item   ,
						admin 
					) VALUES (
						$hd_chamado   ,
						'$comentario' ,
						'$status'     ,
						$login_admin
					);";


			$res = @pg_exec($con, $sql);

			if (!is_resource($res)) {

				$msg_erro .= substr(pg_last_error($con),6);

			} else {

				$res             = @pg_exec($con, "SELECT CURRVAL ('seq_hd_chamado_item')");
				$hd_chamado_item = pg_result($res, 0, 0);

				$sql = "SELECT * FROM tbl_admin
						 WHERE fabrica = $login_fabrica
						 AND help_desk_supervisor = 't'
						 AND ativo IS TRUE";

				$res = @pg_exec($con, $sql);

			}

			# HD 342829
			$sql    = "UPDATE tbl_hd_chamado SET 
							exigir_resposta = 'f' 
						WHERE hd_chamado = $hd_chamado 
						AND admin IN (SELECT admin FROM tbl_admin WHERE fabrica=tbl_hd_chamado.fabrica)
						AND   exigir_resposta";						
						
			$res = @pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);

			//QUANDO O CHAMADO FOR REABERTO SETA ELE EM ANÁLISE
			if ($status <> 'Novo' and $status <> 'Aprovação' and 1 == 2) { //HD 319460 - REMOVIDO NÃO ALTERA STATUS PARA ANALISE
				//ENVIAR EMAIL SE O CHAMADO ESTAVA SETADO PARA EXIGIR RESPOSTA INFORMANDO O ANALISTA QUE O ADMIN RESPONDEU
				$sql    = "SELECT coalesce(exigir_resposta,'f') FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
				$res = @pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
				if (strlen($msg_erro) == 0) {
					$exigir_resposta = pg_result($res,0,0);
				}

				$sql    = "UPDATE tbl_hd_chamado SET status = 'Análise', exigir_resposta = 'f' WHERE hd_chamado = $hd_chamado";
				$res = @pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql    = "UPDATE tbl_hd_chamado_item SET status_item = 'Análise' WHERE hd_chamado_item = $hd_chamado_item";
				$res = @pg_exec ($con,$sql);
				if (!is_resource($res)) $msg_erro .= substr(pg_last_error($con),6);
			}

		}

		//ROTINA DE UPLOAD DE ARQUIVO
		if (strlen ($msg_erro) == 0) {

			$att_max_size = 2097152; // Tamanho máximo do arquivo (em bytes)

			if ($arquivo['error']==1) {
				if ($arquivo['size']==0) $msg_erro.= 'Tamanho do arquivo inválido! ';
				$msg_erro.= 'O arquivo não pôde ser anexado.<br>';
			}

			if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "" and !$msg_erro) {
			    // array_search with recursive searching, optional partial matches and optional search by key
			    function array_rfind($needle, $haystack, $partial_matches = false, $search_keys = false) {
			        if(!is_array($haystack)) return false;
			        foreach($haystack as $key=>$value) {
			            $what = ($search_keys) ? $key : $value;
			            if($needle===$what) return $key;
			            else if($partial_matches && @strpos($what, $needle)!==false) return $key;
			            else if(is_array($value) && array_rfind($needle, $value, $partial_matches, $search_keys)!==false) return $key;
			        }
			        return false;
			    }

				$a_tipos = array(
					/* Imagens */
					'bmp'	=> 'image/bmp',
					'gif'	=> 'image/gif',
					'ico'	=> 'image/x-icon',
					'jpg'	=> 'image/jpeg;image/pjpeg',
					'jpeg'	=> 'image/jpeg;image/pjpeg',
					'png'	=> 'image/png;image/x-png',
					'tif'	=> 'image/tiff',
					/* Texto */
					'csv'	=> 'text/comma-separated-values;text/csv;application/vnd.ms-excel',
					'eps'	=> 'application/postscript',
					'pdf'	=> 'application/pdf',
					'ps'	=> 'application/postscript',
					'rtf'	=> 'text/rtf',
					'tsv'	=> 'text/tab-separated-values;text/tsv;application/vnd.ms-excel',
					'txt'	=> 'text/plain',
					/* Office */
					'doc'	=> 'application/msword',
					'ppt'	=> 'application/vnd.ms-powerpoint',
					'xls'	=> 'application/vnd.ms-excel',
					'docx'	=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
					'pptx'	=> 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
					'xlsx'	=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
					/* Star/OpenOffice.org */
					'odt'	=> 'application/vnd.oasis.opendocument.text;application/x-vnd.oasis.opendocument.text',
					'ods'	=> 'application/vnd.oasis.opendocument.spreadsheet;application/x-vnd.oasis.opendocument.spreadsheet',
					'odp'	=> 'application/vnd.oasis.opendocument.presentation;application/x-vnd.oasis.opendocument.presentation',
					/* Compactadores */
					'sit'	=> 'application/x-stuffit',
					'hqx'	=> 'application/mac-binhex40',
					'7z'	=> 'application/octet-stream',
					'lha'	=> 'application/octet-stream',
					'lzh'	=> 'application/octet-stream',
					'rar'	=> 'application/octet-stream;application/x-rar-compressed;application/x-compressed',
					'zip'	=> 'application/zip'
				);

				// Pega extensão do arquivo
				$a_att_info	  = pathinfo($arquivo['name']);
				$ext          = $a_att_info['extension'];
				$arquivo_nome = $a_att_info['filename']; // Tira a extensão do nome... PHP 5.2.0+
				$aux_extensao = "'$ext'";

				// Verifica o mime-type do arquivo, ou a extensão
				$tipo = ($arquivo['type'] != '') ? array_rfind($arquivo_type, $a_tipos, true) : array_key_exists($ext, $a_tipos);
				if ($arquivo['type'] == 'application/octet-stream') {
				// Tem navegadores que usam o 'application/octet-stream' para tipos desconhecidos...
					$tipo = array_key_exists($ext, $a_tipos);
				}

				if ($tipo) {// Verifica tamanho do arquivo

					if ($arquivo["size"] > $att_max_size)
						$msg_erro.= "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.<br>";

				} else {

					$msg_erro.= "Arquivo em formato inválido!<br>";

				}

				if (strlen($msg_erro) == 0) { // Processa o arquivo
					//  Substituir tudo q não for caracteres aceitos para nome de arquivo para '_'
					$arquivo_nome = preg_replace("/\W/", '_', retira_acentos($arquivo_nome));

					$nome_anexo = "/www/assist/www/helpdesk/documentos/" . $hd_chamado_item . '-' . strtolower($arquivo_nome) . '.' . $ext;

				}

				if (strlen($msg_erro) == 0) {
					if (!move_uploaded_file($arquivo["tmp_name"], $nome_anexo)) $msg_erro = "O arquivo não foi anexado!!!";
				}

			}

		}//fim do upload

		//ENVIA EMAIL PARA SUPERVISOR DA FÁBRICA
		$sql="SELECT admin,
					 email
				FROM tbl_admin
				WHERE fabrica = $login_fabrica
				AND help_desk_supervisor IS TRUE
				AND ativo IS TRUE";

		@$res     = pg_query($con,$sql);
		$tot_sups = pg_num_rows($res);

		if ($tot_sups > 0 and strlen($msg_erro) == 0) {

			/* 08/09/2010 MLG - HD 291166 - Ao invés de enviar uma mensagem por e-mail, manda um só para o total de destinatários (normalmente tem 1, máx. 3)*/
			for ($i = 0 ; $i < $tot_sups; $i++) {
				$email_supervisor[] = trim(pg_result($res,$i,email));
			}

			$email_destino = implode(',', array_map('is_email', $email_supervisor));

			if ($email_destino != '' AND strlen($dispara_email) > 0 AND strlen($msg_erro) == 0) {

				$email_origem = "helpdesk@telecontrol.com.br";
				$assunto      = "Novo Chamado aberto";

				$body_top  = "From: $email_origem\n"; // Mudei o local do 'From:', para ficar mais claro.
				$body_top .= "--Message-Boundary\n";
				$body_top .= "MIME-Version: 1.0\n";
				$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
				$body_top .= "Content-transfer-encoding: 7BIT\n";
				$body_top .= "Content-description: Mail message body\n\n";

				$corpo  = "<br>Foi inserido um novo CHAMADO no HELP DESK do sistema TELECONTROL ASSIST e é necessário a sua análise para aprovação.\n\n";
				$corpo .= "<br>Chamado n°: $hd_chamado\n\n";
				$corpo .= "<br>Titulo: ". stripslashes($titulo) . "\n";
				$corpo .= "<br>Solicitante: $nome <br>Email: $email\n\n";
				$corpo .= "<br><a href='http://www.telecontrol.com.br/assist/helpdesk/chamado_detalhe.php?hd_chamado=$hd_chamado'>CLIQUE AQUI PARA VER O CHAMADO</a> \n\n";
				$corpo .= "<br><br>Telecontrol\n";
				$corpo .= "<br>www.telecontrol.com.br\n";
				$corpo .= "<br>_______________________________________________\n";
				$corpo .= "<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

				if (mail($email_destino,
						  stripslashes($assunto),
						  $corpo,
						  $body_top)) {

				} else {
					//$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.<br>";
				}

			}

		}

		//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO

		if (strlen($dispara_email) > 0 AND strlen($msg_erro) == 0) {

			$email_origem  = "helpdesk@telecontrol.com.br";
			$email_destino = "helpdesk@telecontrol.com.br";
			
			// HD  24442
			if ($xtipo_chamado == '5') { // se for chamado de erro manda email diferenciado
				$assunto = "ERRO - Novo Chamado de ERRO aberto";
			}

			if ($status_anterior == 'REABRIR') {

				$assunto = "Chamado REABERTO - Referente ao Chamado ".$hd_chamado_anterior;

				$corpo  = "";
				$corpo .= "<br>O chamado ".$hd_chamado_anterior." que estava RESOLVIDO foi reaberto.\n\n";
				$corpo .= "<br>Foi aberto um novo chamado com n°: ".$hd_chamado."\n\n";
				$corpo .= "<br>Titulo: ".stripslashes($titulo)." \n";
				$corpo .= "<br>Solicitante: ".$nome." <br>Email: ".$email."\n\n";
				$corpo .= "<br>Comentário inserido: <br><p><i>".$comentario."</i></p>\n\n";

				$corpo .= "<br><a href='http://www.telecontrol.com.br/assist/helpdesk/adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>CLIQUE AQUI PARA VER O CHAMADO</a> \n\n";
				$corpo .= "<br><br>Telecontrol\n";
				$corpo .= "<br>www.telecontrol.com.br\n";
				$corpo .= "<br>_______________________________________________\n";
				$corpo .= "<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

				$body_top  = "--Message-Boundary\n";
				$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
				$body_top .= "Content-transfer-encoding: 7BIT\n";
				$body_top .= "Content-description: Mail message body\n\n";

				if (mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top ")) {

					$msg .= "<br>Foi enviado um email para: ".$email_destino."<br>";

				} else {

					$msg_erro.= "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.<br>";

				}

			}

		}
		
		//ENVIA EMAIL PARA ANALISTA CASO O CHAMADO ESTEJA COMO EXIGIR RESPOSTA
		//if ($exigir_resposta == 't') {
		//ENVIAR EMAIL SEMPRE QUE ALGUÉM FIZER INTERAÇÃO 21/05/2010 - Samuel
		if (strlen($msg_erro) == 0) {

			$email_origem  = "helpdesk@telecontrol.com.br";
			$email_destino = "helpdesk@telecontrol.com.br";

			$sql = "SELECT email 
					FROM  tbl_admin 
					JOIN  tbl_hd_chamado ON tbl_hd_chamado.atendente = tbl_admin.admin
					WHERE tbl_hd_chamado.hd_chamado = $hd_chamado";

			$res = @pg_exec($con, $sql);

			if (pg_numrows($res) > 0) {
				$email_destino = trim(pg_result($res,0,0));
			}

			if ($email_destino != "suporte@telecontrol.com.br") {
				$email_destino = (strlen($email_destino) > 0) ? "$email_destino, " : '';
				$email_destino.= 'suporte@telecontrol.com.br';
			}

			$assunto = "Interação no chamado $hd_chamado";

			$corpo  = "";
			$corpo .= "<br>O chamado $hd_chamado que estava aguardando resposta recebeu uma interação por parte do admin.\n\n";
			$corpo .= "<br>Chamado n°: $hd_chamado\n\n";
			$corpo .= "<br>Titulo: " . stripslashes($titulo) . " \n";
			$corpo .= "<br>Solicitante: $nome <br>Email: $email\n\n";
			$corpo .= "<br>Interação: $comentario\n\n";
			$corpo .= "<br><br>Telecontrol\n";
			$corpo .= "<br>www.telecontrol.com.br\n\n";
			$corpo .= "<br>_______________________________________________\n";
			$corpo .= "<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

			$body_top  = "--Message-Boundary\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";

			if (mail($email_destino, stripslashes($assunto), $corpo, "From: $email_origem\nBcc: distribuidor@telecontrol.com.br\n$body_top ")) {
				$msg .= "<br>Foi enviado um email para: ".$email_destino."<br>";
			}

		}

		if (strlen($msg_erro) > 0) {

			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg_erro .= ' Não foi possível Inserir o Chamado. ';

		} else {
			$res = @pg_exec($con,"COMMIT");
			header ("Location: $PHP_SELF?hd_chamado=$hd_chamado&msg=$msg");
			exit;
		}

	}

}

if (strlen($hd_chamado) > 0) {
	//HD 197505 - Retiradas 16 linhas que faziam a atualização automática do campo tbl_hd_chamado.resolvido automaticamente
	//caso existam problemas, olhar arquivos nao_sync anteriores à resolução do chamado

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

		//HD 218848: Criação do questionário na abertura do Help Desk
		$sql = "SELECT * FROM tbl_hd_chamado_questionario WHERE hd_chamado=$hd_chamado";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res)) {
			$mostra_questionario = true;
			$necessidade	= pg_result($res, 0, necessidade);
			$funciona_hoje	= pg_result($res, 0, funciona_hoje);
			$objetivo		= pg_result($res, 0, objetivo);
			$local_menu		= pg_result($res, 0, local_menu);
			$http			= pg_result($res, 0, http);
			$tempo_espera	= pg_result($res, 0, tempo_espera);
			$impacto		= pg_result($res, 0, impacto);
		}

	} else {
		$msg_erro .="Chamado não encontrado";
	}

} else {

	if ($habilita_questionario) {
		$mostra_questionario = true;
	}
	
	$status = "Novo";
	$login  = $login_login;
	$data   = date("d/m/Y");

	$sql    = "SELECT * FROM tbl_admin WHERE admin = $login_admin";
	$resX   = @pg_exec($con,$sql);

	$nome  = pg_result($resX, 0, 'nome_completo');
	$email = pg_result($resX, 0, 'email');
	$fone  = pg_result($resX, 0, 'fone');

}

$TITULO = "Lista de Chamados - Telecontrol Help-Desk";
if ($sistema_lingua == 'ES') $TITULO = "Lista de Solicitudes - Telecontrol Help-Desk";
$ONLOAD = "frm_chamado.titulo.focus()";
if (strlen ($hd_chamado) > 0) $ONLOAD = "";

include "menu.php"; ?>

<style>
	.btn {
		font-size: 12px;
		font-family: Arial;
		color:#00CC00;
		font-weight: bold;
	}

	.questionarioCaixa{
			border-right: #6699cc 1px solid;
			border-top: #6699cc 1px solid;
			font: 8pt arial ;
			border-left: #6699cc 1px solid;
			border-bottom: #6699cc 1px solid;
			background-color: #ffffff;
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

	$(document).ready(function () {
		verificaTipoChamado();
	})

</script>

<form name='frm_chamado' action='<? echo $PHP_SELF ?>' method='POST' enctype='multipart/form-data' >
<input type='hidden' name='hd_chamado' value='<?= $hd_chamado ?>'>
<input type='hidden' name='status' value='<?= $status ?>'>

<table width = '750' align = 'center' border='0' cellpadding='2'  style='font-family: verdana ; font-size: 11px'>
<tr>
	<td width="140"bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Abertura </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><?= $data ?> </td>
	
	<td  width="100" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px' valign='middle'><strong><?if($sistema_lingua=='ES')echo "Llamado";else echo "Chamado";?></strong></td>
	<td     width="100"         bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;
	<? if(strlen($hd_chamado)>0){ ?>
	<font color='#CC1136'><strong>&nbsp;<?=$hd_chamado?> </strong></font>
	<?}?>
	</td>
	
</tr><?php

if (strlen ($hd_chamado) > 0) {

	if ($sistema_lingua == "ES") {

		if ($status == "Aprovação") $status = "aprobación:";
		if ($status == "Análise")   $status = "Analisis";
		if ($status == "Execução")  $status = "Ejecución";
		if ($status == "Novo")      $status = "Nuevo";
		if ($status == "Resolvido") $status = "Resuelto";

	}?>

	<tr>
		<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Status </strong></td>
		<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<?= $status ?> </td>
		<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Analista </strong></td>
		<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;<?=$atendente_nome?> </strong></td>
	</tr><?php

}?>

<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;<? if($sistema_lingua=='ES')echo "Nombre Usuario";else echo "Nome Usuário";?> </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<?=$nome ?></td>
	<td  bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Login </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<?=$login ?> </td>
</tr>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;<?if($sistema_lingua=='ES')echo "Correo";else echo "e-mail";?> </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<?=$email ?></td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;<?if($sistema_lingua=='ES')echo "Teléfono";else echo "Fone";?> </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<?=$fone ?></td>
</tr>

<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Título </strong></td>
<?	if (strlen ($hd_chamado) > 0) {	?>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<?=stripslashes($titulo)?></td>
<?	} else {	?>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<input type='text' size='60' name='titulo' maxlength='50' value="<?=stripslashes($titulo)?>"
		<? if (strlen ($hd_chamado) > 0) echo " readonly " ?> class='Caixa' valign='middle'>
	</td>
<?	}	?>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Tipo </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
<?
	if ($sistema_lingua != 'ES') {
		$sql = "SELECT	tipo_chamado,
						descricao
				FROM tbl_tipo_chamado
				ORDER BY descricao;";
		$res = @pg_query($con,$sql);
		if(pg_numrows($res)>0){
			$temp = pg_fetch_all($res);
			foreach($temp as $tipo_data) {
				$tipo_de_hd[$tipo_data['tipo_chamado']] = $tipo_data['descricao'];
			}
			unset($temp);
		}
	} else {
		$tipo_de_hd = array (
        	1	=> 'Alteración de datos',
			2   => 'Cambios en página o proceso',
			5   => 'Error en programa',
			4   => 'Nuevo progama o proceso',
			3   => 'Sugerencia de mejora',
        );
	}
	// die('<pre>'.print_r($a_tipo_de_hd, true).'</pre>');
		if (strlen ($hd_chamado) > 0 and strlen($tipo_chamado)>0) { // Se não é chamado novo e já tem o tipo (deveria...) ?>
			<input type='hidden' size='60' name='combo_tipo_chamado_2' value='<?=$tipo_chamado?>'>
			<input type='hidden' size='60' id='combo_tipo_chamado' disabled value='<?=$tipo_chamado?>'>
			<?=$tipo_de_hd[$tipo_chamado]?>
<?		} else {
?>          <select name="combo_tipo_chamado" id="combo_tipo_chamado" onchange='verificaTipoChamado()'>
<?          foreach($tipo_de_hd as $tipo_tipo=>$tipo_desc) {
				$sel = ($tipo_tipo == $_POST['combo_tipo_chamado']) ? ' SELECTED' : '';
?>				<option value="<?=$tipo_tipo?>"<?=$sel?>><?=$tipo_desc?></option>
<?			}
?>			</select>
<?		}
?>
	</td>
</tr>

<!-- HD 218848: Criação do questionário na abertura do Help Desk --><?php
if ($mostra_questionario) {

	if (strlen($hd_chamado)) {

		$desabilita_questionario       = "readonly";
		$desabilita_questionario_combo = "disabled";

	}?>
	<tr class="escondeparaerro">
		<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
			<strong>&nbsp;O que você precisa que seja feito?</strong>
		</td>
	</tr>
	<tr class="escondeparaerro">
		<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
			<textarea <?=$desabilita_questionario?> name='necessidade' id='necessidade' class='questionarioCaixa' cols=90 rows=5><?=$necessidade;?></textarea>
		</td>
	</tr>
	<tr class="escondeparaerro">
		<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
			<strong>&nbsp;Como funciona hoje?</strong>
		</td>
	</tr>
	<tr class="escondeparaerro">
		<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
			<textarea <?=$desabilita_questionario?> name='funciona_hoje' id='funciona_hoje' class='questionarioCaixa' cols=90 rows=5><?=$funciona_hoje;?></textarea>
		</td>
	</tr>
	<tr class="escondeparaerro">
		<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
			<strong>&nbsp;Qual o objetivo desta solicitação? Que problema visa resolver?</strong>
		</td>
	</tr>
	<tr class="escondeparaerro">
		<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
			<textarea <?=$desabilita_questionario?> name='objetivo' id='objetivo' class='questionarioCaixa' cols=90 rows=5><?=$objetivo; ?></textarea>
		</td>
	</tr>

	<tr class="escondeparaerro">
		<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
			<strong>&nbsp;Esta rotina terá impacto financeiro para a empresa? Por quê?</strong>
		</td>
	</tr>
	<tr class="escondeparaerro">
		<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
			<textarea <?=$desabilita_questionario?> name='impacto' id='impacto' class='questionarioCaixa' cols=90 rows=5><?=$impacto; ?></textarea>
		</td>
	</tr>
	<tr>
		<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
			<strong>&nbsp;Em que local do sistema você precisa de alteração?</strong>
		</td>
	</tr>
	<tr>
		<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
			<select <?=$desabilita_questionario_combo?> name="local_menu" id="local_menu" class='questionarioCaixa' style='color: #000000;'>
				<option value="">..... Escolha .....</option>
				<option value="admin_gerencia" <? if ($local_menu == "admin_gerencia") echo "selected" ?>>Administração: Gerência</option>
				<option value="admin_callcenter" <? if ($local_menu == "admin_callcenter") echo "selected" ?>>Administração: CallCenter</option>
				<option value="admin_cadastro" <? if ($local_menu == "admin_cadastro") echo "selected" ?>>Administração: Cadastro</option>
				<option value="admin_infotecnica" <? if ($local_menu == "admin_infotecnica") echo "selected" ?>>Administração: Info Técnica</option>
				<option value="admin_financeiro" <? if ($local_menu == "admin_financeiro") echo "selected" ?>>Administração: Financeiro</option>
				<option value="admin_auditoria" <? if ($local_menu == "admin_auditoria") echo "selected" ?>>Administração: Auditoria</option>
				<option value="posto_os" <? if ($local_menu == "posto_os") echo "selected" ?>>Área do Posto: Ordem de Serviço</option>
				<option value="posto_infotecnica" <? if ($local_menu == "posto_infotecnica") echo "selected" ?>>Área do Posto: Info Técnica</option>
				<option value="posto_pedidos" <? if ($local_menu == "posto_pedidos") echo "selected" ?>>Área do Posto: Pedidos</option>
				<option value="posto_cadastro" <? if ($local_menu == "posto_cadastro") echo "selected" ?>>Área do Posto: Cadastro</option>
				<option value="posto_tabelapreco" <? if ($local_menu == "posto_tabelapreco") echo "selected" ?>>Área do Posto: Tabela Preço</option>
			</select>
		</td>
	</tr>
	<tr class="escondeparaerro">
		<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
			<strong>&nbsp;Quanto tempo é possível esperar por esta mudança?</strong>
		</td>
	</tr>
	<tr class="escondeparaerro">
		<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
			<select <?=$desabilita_questionario_combo?> name="tempo_espera" id="tempo_espera" class='questionarioCaixa' style='color: #000000;'><?php
			if ($tempo_espera == "") $tempo_espera = "7";?>
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
				<option value="30" <? if ($tempo_espera == "30") echo "selected" ?>>1 Mês</option>
				<option value="60" <? if ($tempo_espera == "60") echo "selected" ?>>2 Meses</option>
				<option value="90" <? if ($tempo_espera == "90") echo "selected" ?>>3 Meses</option>
				<option value="180" <? if ($tempo_espera == "180") echo "selected" ?>>6 Meses</option>
				<option value="360" <? if ($tempo_espera == "360") echo "selected" ?>>1 Ano</option>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
			<strong>&nbsp;Endereço HTTP da tela onde está sendo solicitada a alteração:</strong>
		</td>
	</tr>
	<tr>
		<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px;' align='center'>
			<input <?=$desabilita_questionario?> size=90 type="text" name='http' id='http' value='<?= $http ?>' class='questionarioCaixa' />
		</td>
	</tr><?php

}?>

</table><?php

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
	echo "<BR>";
	echo "<table width = '750' align = 'center' border='0' cellpadding='0' cellspacing='0' style='font-family: verdana ; font-size: 11px'>";
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='7' align = 'center' width='100%' style='font-size:14px;color:#666666'><b>";

	if ($sistema_lingua == 'ES') 
		echo "Interacciones";
	else
		echo "Interações";
	
	echo "</b></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "<tr bgcolor='#D9E8FF' style='color: #666666'>";
	echo "<td ><strong align='center'>Nº </strong></td>";
	echo "<td ><img src='/assist/imagens/pixel.gif' width='10'></td>";
	echo "<td nowrap align='center'><strong>";
	if ($sistema_lingua == 'ES') echo "Fecha";
	else                         echo "Data";
	echo "</strong></td>";
	echo "<td ><img src='/assist/imagens/pixel.gif' width='10'></td>";
	echo "<td align='center' ><strong>";

	if ($sistema_lingua == 'ES') 
		echo "Comentario";
	else
		echo "Coment&aacute;rio";

	echo "</strong></td>";
	echo "<td nowrap align='center'><img src='/assist/imagens/pixel.gif' width='10'><strong>Anexo </strong></td>";
	echo "<td nowrap align='center'><strong>Autor </strong></td>";
	echo "</tr>";

	for ($i = 0; $i < pg_numrows($res) ; $i++) {

		$x = $i + 1;
		$hd_chamado_item = pg_result($res, $i, 'hd_chamado_item');
		$data_interacao  = pg_result($res, $i, 'data');
		$admin           = pg_result($res, $i, 'admin');
		$autor           = pg_result($res, $i, 'autor');
		$item_comentario = pg_result($res, $i, 'comentario');

		$sql2 = "SELECT fabrica FROM tbl_admin WHERE admin = $admin";
		$res2 = @pg_exec ($con,$sql2);

		$fabrica_autor = pg_result($res2, 0, 0);
		$cor = '#ffffff';

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

		while (false !== ($filename = readdir($dh))) {

			if (strpos($filename,"$hd_chamado_item") !== false) {

				$po = strlen($hd_chamado_item);
				if (substr($filename, 0, $po) == $hd_chamado_item) {

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

if (strlen($hd_chamado) > 0 AND $status == 'Resolvido') {

	$sql = "SELECT	TO_CHAR (data,'DD/MM HH24:MI') AS data,
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
		if ($sistema_lingua == "ES") echo "Este llamado esta resolvido";
		else                         echo "Este chamado está resolvido.";
		echo "</font></b><br>";

		if ($permissao == 'sim') {

			echo "<b><font face='verdana' color='#6600FF' size='-1'>";

			if ($sistema_lingua == "ES") {
				echo "Si no concordas con la solución, puede reabrirlo digitando una mensaje abajo";
			} else {
				echo "Para novas interações sobre este chamado, digite uma mensagem abaixo.<br> ";
			}

			echo "</font><font face='verdana' color='#00CC00' size='-1'><br>";

			if (strlen($resolvido)== 0) { 

				if ($sistema_lingua == "ES") {
					echo "Si concordas con la solución haga un click no botón RESOLVIDO";
				} else {
					echo "Se você concorda com a solução clique no botão RESOLVIDO";
				}

			}

			echo "</font></b><br>";

		}

	} else {

		echo "<b><font face='verdana' color='#666666'>";

		if ($sistema_lingua == 'ES') echo "Digite el texto para continuar el llamado";
		else                         echo "Digite o texto para dar continuidade ao chamado";
		echo "</font></b><br>";

		if ($status == 'Aprovação') {

			echo "<font color='red'>";
			if ($sistema_lingua == 'ES') echo "¡El responsable del 'Help-Desk' debe APROBAR la solicitud [chamado] para que Telecontrol pueda proseguir";
			else                         echo "O responsável pelo Help-Desk na Fábrica precisa APROVAR o chamado para o Telecontrol dar continuidade!";
			echo "</font>";

		}

	}

} else {

	echo "<b><font face='verdana' color='#666666'>";
	echo "Digite o texto do seu chamado";
	echo "</b></font><br>";

}

if ($permissao == 'sim') {

	echo "<table width = '750' align = 'center' cellpadding='2'  style=' font-size: 11px'>";
	echo "<tr>";
	echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>";
	echo "<textarea name='comentario' cols='90' rows='10' class='questionarioCaixa' wrap='VIRTUAL'>".$comentario."</textarea><br>";
	echo "<script language=\"JavaScript1.2\">editor_generate('comentario');</script>";

	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>";

	if ($sistema_lingua == 'ES') echo "Archivo";
	else                         echo "Arquivo ";

	echo "<input type='file' name='arquivo' size='70' class='Caixa' />";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td align='center'>";
	echo "<font color='red' size=1>O ARQUIVO NÃO SERÁ ANEXADO CASO O TAMANHO EXCEDA O LIMITE DE 2 MB.</font> ";
	echo "</td>";
	echo "</tr>";
	echo "</table>";

}

if ($status == 'Resolvido' AND strlen($resolvido) == 0) {

	echo "<input type='submit' name='btn_resolvido' value='";
	if ($sistema_lingua == "ES") echo "RESOLVIDO - Concordo com la solución";
	else                         echo "RESOLVIDO - CONCORDO COM A SOLUÇÃO...";
	echo "' class='btn' ><br>";

}

if ($permissao == 'sim') {

	echo "<input type='submit' name='btn_acao' value='";
	if ($sistema_lingua == 'ES') echo "Enviar llamado";
	else                         echo "Enviar Chamado";
	echo "'";
	echo ">";
}

echo "</center>";?>
		</td>
	</tr>
</table>
</form>

<? include "rodape.php" ?>
