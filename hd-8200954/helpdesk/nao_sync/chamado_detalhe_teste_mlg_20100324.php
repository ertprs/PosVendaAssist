<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

// MLG El HTML empieza en la línea 800...

// ...pero aquí empieza todo.

// Variables
$a_abas_admin = array("Gerência" => array("Consultas","Relatórios",
										  "Relatórios Call-Center",
										  "Tarefas Administrativas",
										  "Pesquisa de Opinião,3"),
					  "Call-Center" => array("Call-Center","Call-Center Relatórios",
					  						 "Ordens de Serviço","Revendas - Ordens de Serviço",
											 "Sedex - Ordens de Serviço,1.25","Pedidos de Peças",
											 "Diversos","Relatórios Call-Center",
											 "Pedidos de Peças/Produtos",
											 "Gerenciamento De Revendas,3",
											 "Informações Sobre Peças,14"),
					  "Cadastro" =>	Array("Informações Cadastrais da América Latina,20",
					  					  "Cadastros Referentes a Produtos",
										  "Cadastros Referentes a Pedidos De Peças",
										  "Cadastros de Defeitos - Exceções",
										  "Cadastros Referentes a Extrato",
										  "Cadastros de Transacionadores e Outros Cadastros",
										  "Consulta Loja Virtual,3.10.35"),
					  "Info Técnica,Financeiro,Auditoria");
$a_abas_posto = explode(",","Ordem de Serviço,Info Técnico,Pedidos,Cadastro,Tabela de Preço");


//HD 7277 Paulo - tirar acento do arquivo upload
function retira_acentos($texto) {
	$array1 = explode(",","á,à,â,ã,ä,é,è,ê,ë,í,ì,î,ï,ó,ò,ô,õ,ö,ú,ù,û,ü,ç,".
						  "Á,À,Â,Ã,Ä,É,È,Ê,Ë,Í,Ì,Î,Ï,Ó,Ò,Ô,Õ,Ö,Ú,Ù,Û,Ü,Ç,n°,N°");
	$array2 = explode(",","a,a,a,a,a,e,e,e,e,i,i,i,i,o,o,o,o,o,u,u,u,u,c,".
						  "A,A,A,A,A,E,E,E,E,I,I,I,I,O,O,O,O,O,U,U,U,U,C,num,Num");
	return str_replace($array1, $array2, $texto);
}

//  Limpa a string para evitar SQL injection
function anti_injection($string) {
	$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
	return strtr(strip_tags(trim($string)), $a_limpa);
}
function parse_filename($filename, $parte='nome') { //Pode ser 'nome' ou 'ext'
	$ponto	= strpos($filename, ".");
	$nome   = substr($filename, 0, $ponto);
	$ext    = substr($filename, $ponto);
	return ($parte=='nome')?$nome:$ext;
}
// FIM MLG Geral

/*	AJAX Novo para UpLoad de anexos... Tem que devolver 'false' em caso de erro ou
	o html para mostrar o nome do arquivo e o thumbnail (se for imagem) ou o ícone
	do tipo de arquivo, se tivermos.
*/
if ($_POST['ajax']=='fileupload') {
	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : false;
        if (!$arquivo==false) { // test if file was posted
			$arquivo["name"]=retira_acentos($arquivo["name"]);
            $nome_arquivo = parse_filename($arquivo['name']);
            $extensao = parse_filename($arquivo['name'], "ext"); //file extension
			$a_extensao = explode("|","pdf|doc|jpg|jpeg|png|gif|bmp|xls|xlb|odt|ods|odp|rtf|txt|htm|html|zip|ppt");
            if (in_array($extensao, $a_extensao)) { // file filter...
				$nome_sem_espaco = implode("", explode(" ",$arquivo["name"]));
				$nome_anexo = "/www/assist/www/helpdesk/documentos/" . $hd_chamado_item."-".strtolower($nome_sem_espaco);
                if (move_uploaded_file($arquivo['tmp_name'], $nome_anexo)) { // move posted file...
/*  Aqui vem o código que tem que devolver, para inserir:
	<a>x</a> 	<img> 			nome_arquivo
	excluir		ícone/thumbnail o nome do arquivo original  */?>
            <li class='anexo'>
                <input type="hidden" name="ajax" value="excluir">
                <input type="hidden" name="anexo" value="<?=$nome_anexo?>">
                <span title='Excluir anexo' name='excluir'> X </span>
                &nbsp;&nbsp;
                <img src='<?=$imagem_anexo?>' alt='ico' class='thickbox'>
                &nbsp;&nbsp;<?echo $arquivo['name'];?>
            </li>
<?                } else {
					echo "false|Erro ao gravar o arquivo! Tente novamente ou contate com o Suporte";
					exit;
				}
            }
    }
    else {
		echo "false|Erro no upload!!";
		exit;
	}
}
//  FIM MLG Ajax Upload


$hd_chamado		= anti_injection($_REQUEST['hd_chamado']);
$btn_acao		= anti_injection($_REQUEST['btn_acao']);
$btn_resolvido	= anti_injection($_REQUEST['btn_resolvido']);
$msg			= anti_injection($_GET['msg']);
$aguardando_resposta = anti_injection($_GET['aguardando_resposta']);

if(strlen( $hd_chamado)>0 ){
	$sql="SELECT * FROM tbl_hd_chamado WHERE hd_chamado=$hd_chamado AND fabrica=$login_fabrica";
	$res=pg_query($con,$sql);
	if(pg_num_rows($res) ==0){
		header("Location: http://www.telecontrol.com.br");
		exit;
	}
}
if(strlen($btn_resolvido)>0){
	$sql= "UPDATE tbl_hd_chamado
				  SET resolvido  = CURRENT_TIMESTAMP,
				  exigir_resposta= NULL
				WHERE hd_chamado = $hd_chamado";
	$res = @pg_query ($con,$sql);
}

if(strlen( $hd_chamado)>0 AND $aguardando_resposta=='1'){
	$sql= "UPDATE tbl_hd_chamado SET resolvido = NULL, exigir_resposta = 't'
			WHERE hd_chamado = $hd_chamado";
	$res = @pg_query ($con,$sql);
	header("Location: chamado_lista.php?status=Análise&exigir_resposta=t");
	exit;
}

if (strlen ($btn_acao) > 0) {
	if($_POST['comentario'])         {$comentario	= trim ($_POST['comentario']);}
	if($_POST['titulo'])             {$titulo		= trim ($_POST['titulo']);}
	if($_POST['categoria'])          {$categoria	= trim ($_POST['categoria']);}
	if($_POST['nome'])               {$nome			= trim ($_POST['nome']);}
	if($_POST['email'])              {$email		= trim ($_POST['email']);}
	if($_POST['fone'])               {$fone			= trim ($_POST['fone']);}
	if($_POST['status'])             {$status		= trim ($_POST['status']);}
	if($_POST['combo_tipo_chamado']) {$xtipo_chamado= trim ($_POST['combo_tipo_chamado']);}
	if(strlen($xtipo_chamado)==0)    {$xtipo_chamado= trim ($_POST['combo_tipo_chamado_2']);}

	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : false;

/*--==VALIDAÇÕES=====================================================--*/

	#Nos casos de Reabrir camado, inserir um novo chamado e enviar email para Samuel - HD 16445
	if(strlen($hd_chamado)>0){
		$sql= " SELECT hd_chamado,atendente,titulo
				FROM tbl_hd_chamado
				WHERE hd_chamado = $hd_chamado
				AND status = 'Resolvido'
				AND resolvido IS NOT NULL ";
		$res = @pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			# HD 41597 - Francisco Ambrozio
			#   Quando o título anterior tinha aspas simples dava erro
			$titulo_anterior_tmp     = pg_result($res,0,titulo);
			$titulo_anterior         = str_replace("'", "", $titulo_anterior_tmp);
			$hd_chamado_anterior = $hd_chamado;
			$hd_chamado = "";
			$status_anterior = "REABRIR";
			$xtipo_chamado = "5"; #Chamado de erro caso for reaberto
		}
	}

	//SETA P/ USUARIO "SUPORTE"
	$fabricante_responsavel = 10;
	if (strlen ($atendente) == 0) $atendente = "435";

	if (strlen($comentario) < 2){
		$msg_erro="Comentário muito pequeno";
	}else{
	 	$comentario =  str_replace($filtro,"", $comentario);
	}

	if (strlen($xtipo_chamado) ==0){
		$msg_erro = "Escolha o tipo de chamado";
	}else{
	 	$xtipo_chamado =  str_replace($filtro,"", $xtipo_chamado);
	}

	if (strlen($fone) < 7){
		$msg_erro = "Entre com o número do telefone!";
	}
	if (strlen($email) == 0){
		$msg_erro = "Por favor insira seu email!";
	}
	if (strlen($fone) == 0){
		$msg_erro = "Por favor insira um telefone para contato!";
	}
	if (strlen($titulo) < 5){
		$msg_erro = "Título muito pequeno";
	}
	if (strlen($titulo) == 0){
			$msg_erro="Por favor insira um titulo!";
	}

	//CASO SEJA UM ERRO OU UMA ALTERAÇÃO.
	/*	if($categoria=='Erro' OR $categoria=='Alteração') $prioridade = 0;
		else                                              $prioridade = 5; 	*/

	if (strlen($msg_erro) == 0){
		$res = @pg_query($con,"BEGIN TRANSACTION");


		//CASO A FÁBRICA TENHA UM SUPERVISOR O CHAMADO VAI PARA ANÁLISE DO MESMO
		if(strlen($hd_chamado)==0){

			$sql = "SELECT admin 
					FROM  tbl_admin 
					WHERE fabrica = $login_fabrica 
					AND   help_desk_supervisor is true;";

			$res = @pg_query ($con,$sql);
//$tipo_chamado <> '5'  qdo eh 5  nao cai para aprovacao cai direto no hd 7863
			if (pg_num_rows($res) > 0 and $xtipo_chamado <> '5') {
				
				$sql2="SELECT help_desk_supervisor
						FROM  tbl_admin
						WHERE fabrica = $login_fabrica
						AND   admin   = $login_admin";
				$res2=@pg_query($con,$sql2);
				
				$help_desk_supervisor=pg_result($res2,0,help_desk_supervisor);
				$status = ($help_desk_supervisor=='t')?'Novo':'Aprovação';
			}
		}else{
			$status='Novo';
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
				$res=@pg_query($con,$sql);
				
				$sql="SELECT * from tmp_comentario where (comentario ilike '%OK%' or comentario ilike '%OBRIGAD%')";
				$res=@pg_query($con,$sql);
				if(pg_num_rows($res) >0){
					$msg_erro="Não é necessário responder o chamado com \"OK\" ou \"OBRIGADO\". Para reabrir o chamado basta colocar um comentário sem a palavra OK e Obrigado!";
				}
			}
		}
		// HD 20496 limitar o tamanho do titulo
		$titulo=substr($titulo,0,50);
		if(strlen($msg_erro) ==0 ){
			$sql =	"INSERT INTO tbl_hd_chamado (
						admin                  ,
						fabrica                ,
						fabrica_responsavel    ,
						titulo                 ,
						atendente              ,
						tipo_chamado           ,
						prioridade             ,
						status
					) VALUES (
						$login_admin           ,
						$login_fabrica         ,
						$fabricante_responsavel,
						'$titulo'              ,
						$atendente             ,
						$xtipo_chamado         ,
						'$prioridade'		   ,
						'$status'                                                    
					);";
	//echo $sql;
	//exit;
				$res = @pg_query ($con,$sql);
				$msg_erro	   .= pg_errormessage($con);
				$msg_erro		= substr($msg_erro,6);
				$res			= @pg_query ($con,"SELECT CURRVAL ('seq_hd_chamado')");
				$hd_chamado		= pg_result ($res,0,0);
				$dispara_email	= "SIM";
			}//fim do inserir chamado
			
			/* Comentado a solicitação do Samuel para não atualizar mais os dados
			$sql = "SELECT admin  FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
			$res = @pg_query ($con,$sql);
			$xadmin                = pg_result($res,0,admin);
				if($xadmin ==$login_admin){
				$sql =	"UPDATE tbl_admin SET
							nome_completo               = '$nome'                      ,
							email                       = '$email'                     ,
							fone                        = '$fone'
						WHERE admin      = $login_admin";
		
				$res = @pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con);
				$msg_erro = substr($msg_erro,6);
			}*/
	}

	if(strlen($msg_erro)==0){
		if ($status_anterior=='REABRIR'){
			$r_comentario = "'Continuação de atendimento do chamado Nº $hd_chamado_anterior'";
			$sql =	"INSERT INTO tbl_hd_chamado_item (
						hd_chamado   ,	comentario   ,
						status_item  ,	admin
					) VALUES (
						$hd_chamado  ,	$r_comentario,
						'$status'    ,	435)";
			$res = @pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);
		}
		$sql =	"INSERT INTO tbl_hd_chamado_item (
					hd_chamado   ,
					comentario   ,
					status_item  ,
					admin
				) VALUES (
					$hd_chamado  ,
					'$comentario',
					'$status'    ,
					$login_admin
				);";


		$res = @pg_query ($con,$sql);
		$msg_erro .= pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);

		$res = @pg_query ($con,"SELECT CURRVAL ('seq_hd_chamado_item')");
		$hd_chamado_item  = pg_result ($res,0,0);

		$sql = " SELECT * FROM tbl_admin 
				 WHERE fabrica = $login_fabrica 
				 AND help_desk_supervisor = 't' ";
		$res = @pg_query ($con,$sql);

//QUANDO O CHAMADO FOR REABERTO SETA ELE EM ANÁLISE
		if($status<>'Novo'and $status<>'Aprovação'){
			//ENVIAR EMAIL SE O CHAMADO ESTAVA SETADO PARA EXIGIR RESPOSTA INFORMANDO O ANALISTA QUE O ADMIN RESPONDEU
			$sql    = "SELECT coalesce(exigir_resposta,'f') FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
			$res = @pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
			if (strlen($msg_erro) == 0) {
				$exigir_resposta = pg_result($res,0,0);
			}

			$sql    = "UPDATE tbl_hd_chamado
							SET status = 'Análise', exigir_resposta = 'f'
							WHERE hd_chamado = $hd_chamado";
			$res = @pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
			
			$sql    = "UPDATE tbl_hd_chamado_item
							SET status_item = 'Análise'
							WHERE hd_chamado_item = $hd_chamado_item";
			$res = @pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
		$msg_erro = substr($msg_erro,6);
	
	}

if (strlen ($msg_erro) == 0) {
/* ROTINA DE UPLOAD DE ARQUIVO      //Desativada 22/07/09 MLG
			$config["tamanho"] = 2097152; // Tamanho máximo do arquivo (2MiB em bytes)

			if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){

				// Verifica o mime-type do arquivo
				//Estava comentado, Paulo tirou sob ordem de Sono
				if (!preg_match("/\/(pdf|msword|pjpeg|jpeg|png|gif|bmp|vnd.ms-excel|richtext|plain|html|zip|vnd.ms-powerpoint)$/", $arquivo["type"])){
					$msg_erro = "Arquivo em formato inválido!";
				} else { // Verifica tamanho do arquivo 
					if ($arquivo["size"] > $config["tamanho"])
						$msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
				}
				if (strlen($msg_erro) == 0) {
					// Pega extensão do arquivo
					preg_match("/\.(pdf|doc|gif|bmp|png|jpg|jpeg|rtf|xls|txt|zip|ppt){1}$/i", $arquivo["name"], $ext);
					$aux_extensao = "'".$ext[1]."'";
					
					$arquivo["name"]=retira_acentos($arquivo["name"]);
					$nome_sem_espaco = implode("", explode(" ",$arquivo["name"]));
					
					// Gera um nome único para a imagem
					$nome_anexo = "/www/assist/www/helpdesk/documentos/" . $hd_chamado_item."-".strtolower($nome_sem_espaco);

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

//FIM DO ANEXO DO ARQUIVO   */

//ENVIA EMAIL PARA SUPERVISOR DA FÁBRICA
		$sql="SELECT  admin, email
				 FROM tbl_admin
				WHERE fabrica =$login_fabrica
				  AND help_desk_supervisor is true";
		$res=@pg_query ($con,$sql);
		if(pg_num_rows($res) > 0) {
			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
				$email_supervisor=trim(pg_result($res,$i,email));
//AND $login_fabrica == 6 tirei, agora manda email para todos supervisores
				if( strlen($email_supervisor) > 0 AND strlen($dispara_email) > 0 AND strlen($msg_erro)==0 ){
					$email_origem  = "suporte@telecontrol.com.br";
					$assunto       = "Novo Chamado aberto";

					$corpo = "<br>Foi inserido um novo CHAMADO no HELP DESK do sistema TELECONTROL ASSIST e é necessário a sua análise para aprovação.\n\n";
					$corpo.= "<br>Chamado n°: $hd_chamado\n\n";
					$corpo.= "<br>Titulo: $titulo \n";
					$corpo.= "<br>Solicitante: $nome <br>Email: $email\n\n";
					$corpo.= "<br><a href='http://www.telecontrol.com.br/assist/helpdesk/chamado_detalhe.php?hd_chamado=$hd_chamado'>CLIQUE AQUI PARA VER O CHAMADO</a> \n\n";
					$corpo.= "<br><br>Telecontrol\n";
					$corpo.= "<br>www.telecontrol.com.br\n";
					$corpo.= "<br>_______________________________________________\n";
					$corpo.= "<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

					$body_top  = "--Message-Boundary\n";
					$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
					$body_top .= "Content-transfer-encoding: 7BIT\n";
					$body_top .= "Content-description: Mail message body\n\n";

					if ( mail($email_supervisor, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " )){

					//	$msg .= "<br>Foi enviado um email para: ".$email_supervisor."<br>";
					}else{
						//$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.<br>";
					}

				}
			}
		}


	//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO

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
				$corpo.= "<br>Foi aberto um novo chamado com n°: ".$hd_chamado."\n\n";
				$corpo.= "<br>Titulo: ".$titulo." \n";
				$corpo.= "<br>Solicitante: ".$nome." <br>Email: ".$email."\n\n";
				$corpo.= "<br>Comentário inserido: <br><p><i>".$comentario."</i></p>\n\n";

				$corpo.= "<br><a href='http://www.telecontrol.com.br/assist/helpdesk/adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>CLIQUE AQUI PARA VER O CHAMADO</a> \n\n";
				$corpo.= "<br><br>Telecontrol\n";
				$corpo.= "<br>www.telecontrol.com.br\n";
				$corpo.= "<br>_______________________________________________\n";
				$corpo.= "<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

				$body_top  = "--Message-Boundary\n";
				$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
				$body_top .= "Content-transfer-encoding: 7BIT\n";
				$body_top .= "Content-description: Mail message body\n\n";
		//$corpo = $body_top.$corpo;

				if ( mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
					$msg .= "<br>Foi enviado um email para: ".$email_destino."<br>";
				}else{
					$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.<br>";
				}
			}
		}

		
		//ENVIA EMAIL PARA ANALISTA CASO O CHAMADO ESTEJA COMO EXIGIR RESPOSTA
		if ($exigir_resposta == 't') {
			$email_origem  = "suporte@telecontrol.com.br";
			$email_destino = "suporte@telecontrol.com.br";

			$sql = "SELECT email 
					FROM  tbl_admin 
					JOIN  tbl_hd_chamado ON tbl_hd_chamado.atendente = tbl_admin.admin
					WHERE tbl_hd_chamado.hd_chamado = $hd_chamado";
			$res = @pg_query($con, $sql);
			if (pg_num_rows($res) > 0) {
				$email_destino = trim(pg_result($res,0,0));
			}
			
			$assunto       = "Interação no chamado $hd_chamado";
			$corpo = "";
			$corpo.= "<br>O chamado $hd_chamado que estava aguardando resposta recebeu uma interação por parte do admin.\n\n";
			$corpo.= "<br>Chamado n°: $hd_chamado\n\n";
			$corpo.= "<br>Titulo: $titulo \n";
			$corpo.= "<br>Solicitante: $nome <br>Email: $email\n\n";
			$corpo.= "<br><br>Telecontrol\n";
			$corpo.= "<br>www.telecontrol.com.br\n";
			$corpo.= "<br>_______________________________________________\n";
			$corpo.= "<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

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
			$res = @pg_query ($con,"ROLLBACK TRANSACTION");
			$msg_erro .= 'Não foi possível Inserir o Chamado. ';
		}else{
			$res = @pg_query($con,"COMMIT");
		//		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
			header ("Location: chamado_detalhe.php?hd_chamado=$hd_chamado&msg=$msg");
			exit;
		}
	}
}


if(strlen($hd_chamado)>0){

	$sql= " SELECT tbl_hd_chamado.hd_chamado, tbl_hd_chamado.admin
				FROM tbl_hd_chamado
					JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
					JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica
				WHERE hd_chamado = $hd_chamado
					AND tbl_hd_chamado.admin = $login_admin
					AND tbl_hd_chamado.status = 'Resolvido'
					AND tbl_hd_chamado.resolvido IS NULL ";
	$res = @pg_query ($con,$sql);
	if (pg_num_rows($res) > 0) {
		$sql= "UPDATE tbl_hd_chamado SET 
					resolvido       = CURRENT_TIMESTAMP , 
					exigir_resposta = NULL
				WHERE hd_chamado = $hd_chamado";
		$res = @pg_query ($con,$sql);
	}

	$sql= " SELECT tbl_hd_chamado.hd_chamado                             ,
					tbl_hd_chamado.admin                                 ,
					TO_CHAR(tbl_hd_chamado.data,'DD/MM HH24:MI') AS data ,
					tbl_hd_chamado.titulo                                ,
					tbl_hd_chamado.categoria                             ,
					tbl_hd_chamado.status                                ,
					tbl_hd_chamado.atendente                             ,
					tbl_hd_chamado.fabrica_responsavel                   ,
					tbl_hd_chamado.resolvido                             ,
					tbl_hd_chamado.tipo_chamado                          ,
					tbl_fabrica.nome AS fabrica_nome                     ,
					tbl_admin.login                                      ,
					tbl_admin.nome_completo AS nome                      ,
					tbl_admin.fone                                       ,
					tbl_admin.email                                      ,
					at.nome_completo AS atendente_nome
			FROM tbl_hd_chamado
				JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
				JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica
				LEFT JOIN tbl_admin at ON tbl_hd_chamado.atendente = at.admin
			WHERE hd_chamado = $hd_chamado";

	$res = @pg_query ($con,$sql);
	if (pg_num_rows($res) > 0) {
		$a_result = pg_fetch_assoc($res,0);
		foreach ($a_result as $campo => $valor) {
			$$campo = $valor;   // Cria as variáveis com o nome do campo e seta o valor
		}
	}else{
		$msg_erro .="Chamado não encontrado";
	}
}else{
	$status="Novo";
	$login = $login_login;
	$data = date("d/m/Y");
	$sql = "SELECT * FROM tbl_admin WHERE admin = $login_admin";
	$resX = @pg_query ($con,$sql);
	$nome	= pg_result($resX,0,nome_completo);
	$email	= pg_result($resX,0,email);
	$fone	= pg_result($resX,0,fone);
}

$TITULO = "Lista de Chamados - Telecontrol Help-Desk";
if($sistema_lingua == 'ES') $TITULO = "Lista de solicitudes - Telecontrol Help-Desk";
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
</style>

<form name='frm_chamado' id='frm_chamado' action='<?= $PHP_SELF ?>'
	method='POST' enctype='multipart/form-data'>
<input type='hidden' name='hd_chamado' value='<?= $hd_chamado ?>'>
<input type='hidden' name='status'	   value='<?= $status ?>'>

<table align='center' id='tbl_frm'>

<?
if (strlen ($hd_chamado) > 0) {
	//echo "<tr>";
	//echo "<td colspan='4' align='center' class = 'Titulo2' height='30'><strong>Chamado nº. $hd_chamado </strong></td>";
	//echo "</tr>";
}
if (strlen($msg)>0){
	echo "<p>".$msg."</p>";
}
?>

	<tr>
		<td  width="140">&nbsp;Abertura</td>
		<td><?= $data ?></td>
		<td width="100">
				<? echo ($sistema_lingua=='ES')?"Solicitud":"Chamado";?>
		</td>
		<td width="100" style='font-weight: bold;color:#c13;'>&nbsp;
		<? if(strlen($hd_chamado)>0){
			echo "&nbsp;$hd_chamado";
		}?>
		</td>
	</tr>

<?
if (strlen ($hd_chamado) > 0) {
	if($sistema_lingua == "ES"){
		if($status=="Aprovação") $status="Aprobación:";
		if($status=="Análise")   $status="Análisis";
		if($status=="Execução")  $status="Ejecución";
		if($status=="Novo")      $status="Nuevo";
		if($status=="Resolvido") $status="Resuelto";
	}

	?>
	<tr>
		<td><strong>&nbsp;Status </strong></td>
		<td>&nbsp;<?= $status ?> </td>
		<td>&nbsp;Analista</td>
		<td>&nbsp;<?=$atendente_nome?></td>
	</tr>
<? } ?>

	<tr>
		<td>&nbsp;<?echo ($sistema_lingua=='ES')?"Nombre Usuario":"Nome Usuário";?></td>
		<td>&nbsp;
			<input type='text' size='60' maxlength='100'  name='nome' value='<?= $nome ?>'
				  class='Caixa'>
		</td>
		<td>&nbsp;Login</td>
		<td>&nbsp;<?=$login?></td>
	</tr>

	<tr>
		<td>&nbsp;<?echo ($sistema_lingua=='ES')?"Correo":"Email";?></td>
		<td>&nbsp;
			<input type='text' size='60' name='email' maxlength='100'
				  value='<?= $email ?>' class='Caixa'>
		</td>
		<td>&nbsp;<?echo ($sistema_lingua=='ES')?"Teléfono":"Fone";?></td>
		<td>&nbsp;
			<input type='text' size='20' maxlength='20' name='fone'
				  value='<?=$fone ?>' class='Caixa'>
		</td>
	</tr>

	<tr>
		<td>&nbsp;Título</td>
		<td>&nbsp;
			<input type='text' size='60' name='titulo' maxlength='50'
				  value=<?echo "'$titulo'". (strlen ($hd_chamado)>0)?" READONLY":""; ?> class='Caixa'>
		</td>
		<td>&nbsp;Tipo</td>
		<td>
	<?
		$sql = "SELECT	tipo_chamado, descricao
					FROM tbl_tipo_chamado
					ORDER BY descricao;";
		$res = @pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			if (strlen ($hd_chamado) > 0 and strlen($tipo_chamado)>0) {
				echo " <input type='hidden' size='60' name='combo_tipo_chamado_2' value='$tipo_chamado'>";
			}
			echo "<SELECT name='combo_tipo_chamado' size='1'";
			if (strlen ($hd_chamado) > 0 and strlen($tipo_chamado)>0) {echo " disabled ";}
			echo " class='Caixa'>";
		//	echo "<option></option>";
			if($sistema_lingua<>"ES"){
				for($i=0;pg_num_rows($res)>$i;$i++){
					list ($xtipo_chamado,$xdescricao) = pg_fetch_row($res,$i);
					echo "<OPTION value='$xtipo_chamado'";
					echo ($tipo_chamado == $xtipo_chamado)?" SELECTED>":">";}
					echo "$xdescricao</OPTION>";
				}
			}else{
			    $a_tipo_chamado = array(1 => "Alteración de datos",
										2 => "Cambio en página o proceso",
										3 => "Sugerencia de mejora",
										4 => "Nuevo programa o proceso",
										5 => "Error en programa");
                natsort($a_tipo_chamado);   // Ordena o array por descrição
	            foreach ($a_tipo_chamado as $tc_tipo=>$tc_desc) {
	                $tc_tipo = $tc_tipo . ($tipo_chamado == '$tc_tipo')?" SELECTED":"";
	            	echo "<OPTION value='$tc_tipo'>$tc_desc</OPTION>";
	            }
			}
			echo "</SELECT>";
	?>
		</td>
	</tr>
</table>
<?
$sql= "SELECT   tbl_hd_chamado_item.hd_chamado_item,
		TO_CHAR(tbl_hd_chamado_item.data,'DD/MM HH24:MI') AS data	,
				tbl_hd_chamado_item.comentario						,
				tbl_hd_chamado_item.admin							,
				tbl_admin.nome_completo AS autor
		FROM tbl_hd_chamado_item
			JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_item.admin
		WHERE hd_chamado = $hd_chamado
		AND interno IS NOT TRUE
		ORDER BY hd_chamado_item";
$res = @pg_query ($con,$sql);

if (@pg_num_rows($res) > 0) {
	echo "<BR>";
	echo "<table align='center' style='width: 750px;font-family:verdana;font-size: 11px;border:0;border-collapse:collapse;border-spacing:0;'>";
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='7' align = 'center' width='100%' style='font-size:14px;color:#666666'><b>";
  if($sistema_lingua == 'ES') echo "Interacciones";
  else                        echo "Interações";
  echo "</b></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "<tr bgcolor='#D9E8FF' style='color: #666666'>";
	echo "<td ><strong align='center'>Nº </strong></td>";
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
	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
		$x=$i+1;
		$hd_chamado_item = pg_result($res,$i,hd_chamado_item);
		$data_interacao  = pg_result($res,$i,data);
		$admin           = pg_result($res,$i,admin);
		$autor           = pg_result($res,$i,autor);
		$item_comentario = pg_result($res,$i,comentario);

		$sql2 = "SELECT fabrica FROM tbl_admin WHERE admin = $admin";
		//echo $sql2;
		$res2 = @pg_query ($con,$sql2);
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
			CASE WHEN CURRENT_DATE - data::DATE > 14
				THEN 'nao'
				ELSE 'sim'
			END AS permissao
			FROM tbl_hd_chamado_item 
			WHERE hd_chamado = $hd_chamado
			AND interno IS NOT TRUE
			ORDER BY tbl_hd_chamado_item.data DESC
			LIMIT 1";
	$res = @pg_query ($con,$sql);
	if (pg_num_rows($res) > 0) {
		list($data_ultima_interacao,$permissao) = pg_fetch_row($res,0);
	}
}

echo "<center>";

if (strlen ($hd_chamado) > 0) {
	if ($status == 'Resolvido') {
		echo "<P class='resolvido'>";
		if($sistema_lingua == "ES") echo "Esta solicitud está resuelta.";
		else                        echo "Este chamado está resolvido.";
		echo "</P>";

		if ($permissao == 'sim'){
			echo "<P style='color: #60f;font-size: 0.9em;'>";
			if($sistema_lingua == "ES") echo "Si no está de acuerdo con la solución, puede reabrirla escribiendo una respuesta.<br>";
			else                        echo "Para novas interações sobre este chamado, digite uma mensagem abaixo.<br> 
Estamos considerando que você leu a conclusão deste chamado. <br>Caso queira deixar pendente para você, <a href='$PHP_SELF?hd_chamado=$hd_chamado&aguardando_resposta=1'>clique aqui</a>, o chamado continuará com status de aguardando sua resposta.";
			echo "<font face='verdana' color='#00CC00' size='-1'><br>";

			if (strlen($resolvido)== 0){ 
				if($sistema_lingua == "ES") {
					echo "Si está de acuerdo con la solución, pulse el botón RESUELTO";
				} else {
					echo "Se você concorda com a solução clique no botão RESOLVIDO";
				}
			}
			echo "</b><br>";
		}
	}else{
		echo "<p style='color:#666666'>";
		if($sistema_lingua == 'ES') echo "Escriba un texto para continuar con la solicitud";
		else                        echo "Digite o texto para dar continuidade ao chamado";
		echo "</p>";
	}
}else{
	echo "<p style='color:#666666'>";
	echo "Digite o texto do seu chamado";
	echo "</p>";
}

if ($permissao == 'sim' ){?>
    <table width = '750' align = 'center' cellpadding='2'  style=' font-size: 11px'>
        <tr>
            <td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
                <textarea name='comentario' cols='90' rows='10' class='Caixa' wrap='VIRTUAL'>".$comentario."</textarea><br>
                <script language='JavaScript1.2'>editor_generate('comentario');</script>
            </td>
        </tr>    
        <tr>
            <td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
<?	echo ($sistema_lingua == 'ES')?"Archivo: ":"Arquivo: ";?>
                <input type='file' name='arquivo' size='70' class='Caixa'>
<?	#if (strlen($resolvido) > 0){ echo "DISABLED";}?>
                <div id="jcupload_content">
                        <!-- jcUpload dialog output -->
                </div>

            </td>
        </tr>    
        <tr>
        	<td align='center'>
        	   <font color='red' size=1>O ARQUIVO NÃO SERÁ ANEXADO CASO O TAMANHO EXCEDA O LIMITE DE 2 MB.</font>
            <td>
        </tr>    
	</table>
<?
}

if($status=='Resolvido' AND  strlen($resolvido)==0){
	echo "<input type='submit' name='btn_resolvido' value='";
	if($sistema_lingua == "ES") echo "RESOLVIDO - Concordo com la solución";
	else                        echo "RESOLVIDO - CONCORDO COM A SOLUÇÃO...";
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
