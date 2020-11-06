<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica<>10){
	header ("Location: index.php");
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
		$sql =" UPDATE tbl_hd_chamado 
				SET status = '$status' , 
					atendente = $transfere
				WHERE hd_chamado = $hd_chamado";
		//if($login_admin ==568)	echo "sql-1 $sql<br>";
		$res = pg_exec ($con,$sql);
}

if(strlen($hd_chamado)>0){
	$sql =" select sum(case when data_termino is null then current_timestamp else data_termino end - data_inicio ) from tbl_hd_chamado_atendente where hd_chamado = $hd_chamado;";
	//if($login_admin ==568)	echo "sql-2 $sql<br>";
	$res = pg_exec($con, $sql);
	if(pg_numrows($res)>0)
	$horas= pg_result ($res,0,0);	
}


if (strlen ($btn_acao) > 0) {

	if($_POST['comentario'])          { $comentario      = trim ($_POST['comentario']);}
	if($_POST['titulo'])              { $titulo          = trim ($_POST['titulo']);}
	if($_POST['status'])              { $status          = trim ($_POST['status']);}
	if($_POST['transfere'])           { $transfere       = trim ($_POST['transfere']);}
	if($_POST['categoria'])           { $categoria       = trim ($_POST['categoria']);}
	if($_POST['sequencia'])           { $sequencia       = trim ($_POST['sequencia']);}
	if($_POST['interno'])             { $interno         = trim ($_POST['interno']);}
	if($_POST['exigir_resposta'])     { $exigir_resposta = trim ($_POST['exigir_resposta']);}
	if($_POST['hora_desenvolvimento']){ $hora_desenvolvimento = trim($_POST['hora_desenvolvimento']);}
	if($_POST['cobrar'])              { $cobrar = trim($_POST['cobrar']);}
	if($_POST['prazo_horas'])         { $prazo_horas = trim($_POST['prazo_horas']);}
	if($_POST['tipo_chamado'])        { $tipo_chamado = trim($_POST['tipo_chamado']);}
	
	if(strlen($categoria)==0){
		$msg_erro = "Escolha a categoria";
	}
//	echo ">== $cobrar<="; 
	if($cobrar=="t"){
		$xcobrar = "'t'";
	}else{
		$xcobrar = "'f'";
	}
//echo $xcobrar; exit;
	if(strlen($prazo_horas)>0){
		$xprazo_horas = "$prazo_horas";
	}else{
		$xprazo_horas = "null";
	}

	if(strlen($tipo_chamado)>0){
		$xtipo_chamado = "$tipo_chamado";
	}else{
		$xtipo_chamado = "null";
	}


	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

	$sql = "SELECT categoria , status FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
	//if($login_admin ==568)	echo "sql-4 $sql<br>";
	$res = pg_exec ($con,$sql);
	$categoria_anterior = pg_result ($res,0,categoria);
	$status_anterior    = pg_result ($res,0,status);

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

/*takashi colocou isso 30-05-07 2495 qdo solicitamos resposta do usuário e interagimos no chamado, setava como false novamente.. se der problema pode retirar*/
		$sql = "Select exigir_resposta from tbl_hd_chamado where hd_chamado=$hd_chamado";
		//if($login_admin ==568)	echo "sql-5 $sql<br>";
		$res = pg_exec ($con,$sql);
		$xexigir_resposta = pg_result($res,0,0);

		//wellington colocou este if, pois este campo pode ser nulo e para chamados novos nao estava setando exigir resposta
		if (strlen($xexigir_resposta)==0) $xexigir_resposta = 'f';
/*takashi 30-05-07*/

	
	if (strlen ($exigir_resposta) > 0) $exigir_resposta = 't';
	else $exigir_resposta = 'f';
	
	if (strlen ($interno) > 0) $xinterno = 't';
	else $xinterno = 'f';
	if (strlen($msg_erro) == 0){
		$res = pg_exec($con,"BEGIN TRANSACTION");
		//if($login_admin ==568)	echo "sql-begin;<br>";
		//EXECUTA A ROTINA PARA SETAR O TERMINO NO HD_CHAMADO_ITEM
		$sql =" UPDATE tbl_hd_chamado_item
				SET termino = current_timestamp
				WHERE hd_chamado_item in(SELECT hd_chamado_item
							 FROM tbl_hd_chamado_item
							 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
								AND termino IS NULL
							 ORDER BY hd_chamado_item desc
							 LIMIT 1 );";

		//if($login_admin ==568)	echo "sql-6 $sql<br>";
		$res = pg_exec ($con,$sql);
		

		$sql =" UPDATE tbl_hd_chamado 
				SET status = '$status' , 
					atendente = $transfere,
					categoria = '$categoria', 
					prazo_horas = $xprazo_horas,
					tipo_chamado = $xtipo_chamado,
					cobrar = $xcobrar ";
if($xexigir_resposta=='f')$sql .= ", exigir_resposta = '$exigir_resposta'  ";/*takashi colocou isso 30-05-07 2495 */
				$sql .= " WHERE hd_chamado = $hd_chamado";
	//	echo "sql-7 $sql<br>";
		$res = pg_exec ($con,$sql);

		if ($categoria <> $categoria_anterior) {
			$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin, interno) VALUES ($hd_chamado, 'Categoria Alterada de $categoria_anterior para <b> $categoria </b>',$login_admin, 't')";
			//if($login_admin ==568)	echo "sql-8 $sql<br>";
			$res = pg_exec ($con,$sql);
		}

		if ($status == "Resolvido" AND $status_anterior == "Execução") {
			$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin) VALUES ($hd_chamado, 'Chamado resolvido. Se você não concordar com a solução basta inserir novo comentário para reabrir o chamado.',$login_admin)";
			//if($login_admin ==568)	echo "sql-9 $sql<br>";
			$res = pg_exec ($con,$sql);
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
			//if($login_admin ==568)	echo "sql-10 $sql<br>";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			$res = @pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado_item')");
			$hd_chamado_item  = pg_result ($res,0,0);
			
			if (strlen ($msg_erro) == 0 and strlen($hd_chamado_item) > 0) {

				$config["tamanho"] = 2048000; // Tamanho máximo do arquivo (em bytes) 

				if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){

					// Verifica o mime-type do arquivo
					if (!preg_match("/\/(zip|pdf|msword|pjpeg|jpeg|png|gif|bmp|vnd.ms-excel|richtext|plain|html)$/", $arquivo["type"])){
						$msg_erro = "Arquivo em formato inválido!";
					} else { // Verifica tamanho do arquivo 
						if ($arquivo["size"] > $config["tamanho"])
							$msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
					}
					if (strlen($msg_erro) == 0) {

						// Pega extensão do arquivo
						preg_match("/\.(pdf|doc|gif|bmp|png|jpg|jpeg|rtf|xls|txt){1}$/i", $arquivo["name"], $ext);
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
			//if($login_admin ==568)	echo "sql-11 $sql<br>";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			if (pg_numrows($res) > 0) {
				$hd_chamado_atendente =  pg_result($res,0,hd_chamado_atendente);
				$hd_chamado_atual     = pg_result($res,0,hd_chamado);
			}

			if($hd_chamado_atual <> $hd_chamado){

				//EXECUTA A ROTINA PARA SETAR O TERMINO NO HD_CHAMADO_ITEM
				$sql =" UPDATE tbl_hd_chamado_item
						SET termino = current_timestamp
						WHERE hd_chamado_item in(SELECT hd_chamado_item
									 FROM tbl_hd_chamado_item
									 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
										AND termino IS NULL
									 ORDER BY hd_chamado_item desc
									 LIMIT 1 );";

			//if($login_admin ==568)	echo "sql-12 $sql<br>";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				
				if(strlen($hd_chamado_atendente)>0){
					$sql = "UPDATE tbl_hd_chamado_atendente
									SET data_termino = CURRENT_TIMESTAMP
									WHERE hd_chamado_atendente = $hd_chamado_atendente
									AND   admin               =  $login_admin
									AND   data_termino IS NULL
									";
		//if($login_admin ==568)	echo "sql-13 $sql<br>";
					$res = pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
				}

				$sql = "INSERT INTO tbl_hd_chamado_atendente(
												hd_chamado ,
												admin      ,
												data_inicio
										)VALUES(
										$hd_chamado       ,
										$login_admin      ,
										CURRENT_TIMESTAMP
										)";
		//if($login_admin ==568)	echo "sql-14 $sql<br>";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				$sql="SELECT CURRVAL('seq_hd_chamado_atendente');";
						//if($login_admin ==568)	echo "sql- select curval seq_hd_chaamado_atend<br>";
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
		//if($login_admin ==568)	echo "sql-15 $sql<br>";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);

				$sql = "UPDATE tbl_hd_chamado_atendente
					SET data_termino = CURRENT_TIMESTAMP
					WHERE admin                = $login_admin
					AND   hd_chamado           = $hd_chamado
					AND   hd_chamado_atendente = $hd_chamado_atendente";
		//if($login_admin ==568)	echo "sql-16 $sql<br>";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);

				$sql= "UPDATE tbl_controle_acesso_arquivo SET
					data_fim = CURRENT_DATE,
					hora_fim = CURRENT_TIME,
					status   = 'finalizado'
					WHERE hd_chamado = $hd_chamado";
		//if($login_admin ==568)	echo "sql-17 $sql<br>";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

		}

		
		
		$msg_erro = substr($msg_erro,6);
		if(strlen($msg_erro) > 0){
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		//if($login_admin ==568)	echo "sql-rollback<br>";
			$msg_erro = 'Não foi possível Inserir o Chamado';
		}else{
			$res = @pg_exec($con,"COMMIT");
		//	$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
					//if($login_admin ==568)	echo "sql- commit<br>";
			if($status == 'Resolvido' OR $exigir_resposta == 't'){
				$sql="SELECT nome_completo,email,tbl_admin.admin 
							FROM tbl_admin 
							JOIN tbl_hd_chamado ON tbl_hd_chamado.admin = tbl_admin.admin 
							WHERE hd_chamado = $hd_chamado";
				$res = pg_exec ($con,$sql);
				$email                = pg_result($res,0,email);
				$nome                 = pg_result($res,0,nome_completo);
				$adm                  = pg_result($res,0,admin);
				
				$chave1=md5($hd_chamado);
				$chave2=md5($adm);
				$email_origem  = "suporte@telecontrol.com.br";
				$email_destino = $email;
				$assunto       = "Seu chamado n° $hd_chamado foi RESOLVIDO";
				$corpo.="<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR 
						NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
						<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> - 
						<STRONG>$titulo</STRONG>&nbsp; </P>
						<P align=left>$nome,</P>
						<P align=justify>Seu chamado foi&nbsp;<FONT 
						color=#006600><STRONG>resolvido</STRONG></FONT> pelo suporte Telecontrol, você 
						tem <U>3(três) dias para concordar com a solução do chamado</U>. Caso 
						<STRONG>não haja manifestação</STRONG>&nbsp;será 
						considerado&nbsp;<STRONG>resolvido automaticamente. </STRONG>Caso 
						não concorde&nbsp;com a resolução do chamado <STRONG>insira um 
						comentário</STRONG> para <STRONG>reabrir o chamado</STRONG>.</P>
						<P align=justify>Se após este prazo o problema/dúvida continuar, abra um novo 
						chamado.</P>
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
			}
			header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
		}
	}
}



if(strlen($hd_chamado) > 0){
	$sql = "UPDATE tbl_hd_chamado SET atendente = $login_admin WHERE hd_chamado = $hd_chamado AND atendente IS NULL";
	$res = pg_exec ($con,$sql);

	$sql= " SELECT tbl_hd_chamado.hd_chamado                             ,
					tbl_hd_chamado.admin                                 ,
					to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
					tbl_hd_chamado.titulo                                ,
					tbl_hd_chamado.categoria                             ,
					tbl_hd_chamado.status                                ,
					tbl_hd_chamado.duracao                               ,
					tbl_hd_chamado.atendente                             ,
					tbl_hd_chamado.fabrica_responsavel                   ,
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
		$nome                 = pg_result($res,0,nome_completo);
		$email                = pg_result($res,0,email);
		$prioridade           = pg_result($res,0,prioridade);
		$fone             = pg_result($res,0,fone);
		$nome_completo        = pg_result($res,0,nome_completo);
		$fabrica_nome         = pg_result($res,0,fabrica_nome);
		$login                = pg_result($res,0,login);
		$prazo_horas          = pg_result($res,0,prazo_horas);
		$previsao_termino     = pg_result($res,0,previsao_termino);
		$previsao_termino_interna= pg_result($res,0,previsao_termino_interna);
		$hora_desenvolvimento = pg_result($res,0,hora_desenvolvimento);
			$cobrar = pg_result($res,0,cobrar);
				$tipo_chamado = pg_result($res,0,tipo_chamado);
	}else{
		$msg_erro="Chamado não encontrado";
	}
}



$TITULO = "ADM - Responder Chamado";

include "menu.php";
?>
<script type="text/javascript" src="js/ajax_busca.js"></script>
<script>
function recuperardados(hd_chamado) {
	var programa = document.frm_chamada.programa.value;
	if(programa.length > 4 ){
		var busca = new BUSCA();
		busca.Updater("ajax_listar_programa.php?digito="+programa+"&hd_chamado="+escape(hd_chamado),"conteudo","get","carregando os dados...");
	}
}
</script>
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />

<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});


 $(document).ready(function(){
   $(".relatorio tr").mouseover(function(){$(this).addClass("over");}).mouseout(function(){$(this).removeClass("over");});
   $(".relatorio tr:even").addClass("alt");
   });
   
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


	table.relatorio {
		border-collapse: collapse;
		width: 750px;
		font-size: 1.1em;
		border-left: 1px solid #8BA4EB;
		border-right: 1px solid #8BA4EB;
	}

	table.relatorio th {
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

	table.relatorio tr.over td {
		background: #bcd4ec;
	}
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




<table width = '750' align = 'center' border='0' cellpadding='2' cellspacing='2' style='font-family: arial ; font-size: 12px'>

<form name='frm_chamada' action='<? echo $PHP_SELF ?>' method='post' enctype="multipart/form-data">
<input type='hidden' name='hd_chamado' value='<?= $hd_chamado?>'>

<?
if (strlen ($hd_chamado) > 0) {
/*	echo "<tr>";
	echo "<td colspan='4' align='center' class = 'Titulo2' height='30'><strong>Chamado nº. $hd_chamado </strong></td>";
	echo "</tr>";*/
}
?>


<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px;'><strong>Título </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><?= $titulo ?> </td>
	
	<td width="60" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Abertura </strong></td>
	<td  bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'><?= $data ?> </td>

</tr>
<tr>
	<td  bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px '><strong>Solicitante </strong></td>
	<td  bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><?= $login ?> </td>
	<td width="60" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Chamado </strong></td>
	<td    bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'><strong><font  color='#FF0033' size='4'><?=$hd_chamado?></font></strong></td>
	<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Nome </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><?= $nome ?></td>
	<td width="60" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Fábrica </strong></td>
	<td  bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'><?= $fabrica_nome ?> </td>
</tr>

<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Email </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><?= $email ?></td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Fone </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'  align='center'><?= $fone ?></td>
</tr>

<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Atendente </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><?= $atendente_nome ?></td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Status </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'  align='center'><?= $status ?></td>
</tr>



</table>
<table>




<!--
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Email </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<input type='text' size='30' name='email' value='<?= $email ?>' <? if (strlen ($hd_chamado) > 0) echo " readonly " ?> class='caixa'></td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Fone </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<input type='text' size='25' name='fone' value='<?= $fone ?>' <? if (strlen ($hd_chamado) > 0) echo " readonly " ?> class='caixa'></td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Prazo </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'align='center' ><?=$prazo_horas?> Hrs</td>
</tr>

<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Previsão Interno </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><? echo $previsao_termino_interna ?></td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Previsão Término</strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><? echo $previsao_termino ?></td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Hrs. Desenv.</strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
	<input type='text' size='5' name='hora_desenvolvimento' value='<?= $hora_desenvolvimento ?>' class='caixa'> Hrs</td>
</tr>
-->
</table>


<?

$sql= "SELECT   tbl_hd_chamado_item.hd_chamado_item,
		to_char (tbl_hd_chamado_item.data,'DD/MM HH24:MI') AS data   ,
		to_char((tbl_hd_chamado_item.TERMINO -tbl_hd_chamado_item.DATA), 'HH24:MI') AS tempo_trabalho,
		tbl_hd_chamado_item.comentario                               ,
		tbl_hd_chamado_item.interno                                  ,
		tbl_admin.nome_completo                            AS autor  ,
		(select to_char(sum(termino - data),'HH24:MI') from tbl_hd_chamado_item where hd_chamado_item = tbl_hd_chamado_item.hd_chamado_item) as a,
		tbl_hd_chamado_item.status_item
		FROM tbl_hd_chamado_item 
		JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_item.admin
		WHERE hd_chamado = $hd_chamado
		ORDER BY hd_chamado_item";
$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {
	echo "<BR><BR><table width = '750' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio' style='font-family: arial; font-size:11px'>";
echo "<thead>";
	echo "<tr  bgcolor='#D9E8FF'>";
	echo "<th><strong>Nº</strong></th>";
	echo "<th  nowrap><strong>Data</strong></th>";
	echo "<th  nowrap><strong>Tmp Trab.</strong></th>";
	echo "<th><strong>  Comentário </strong></th>";
	echo "<th  ><strong> Anexo </strong></th>";
	echo "<th nowrap ><strong>Autor </strong></th>";
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
		$tempo_trabalho  = pg_result($res,$i,tempo_trabalho);
		
		$autor = explode(" ",$autor);
		$autor = $autor[0];

		echo "<tr  style='font-family: arial ; font-size: 12px' height='25' bgcolor='$cor'>";
		echo "<td nowrap width='25'>$x </td>";
		echo "<td nowrap width='50'>$data_interacao </td>";
		echo "<td nowrap width='40'>$tempo_trabalho</td>";
		echo "<td  width='520'>";
		if ($status_item == 'Resolvido'){

			echo "<span class='resolvido'><b>Chamado foi resolvido nesta interação</b></span>";

		}
		if($interno == 't'){
			echo "<span class='interno'><b>Chamado interno</b></span>";

		}
		echo "<font size='1'>" . nl2br(str_replace($filtro,"", $item_comentario)) . "</td>";
		echo "<td width='25'>";
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
		echo "<td nowrap width='50'>$autor</td>";
		echo "</tr>";


	}	
	echo "</tbody>";
	echo "</table>";
}
?>





<table width = '750' align = 'center' border='0' cellpadding='2'  style='font-family: arial ; font-size: 12px'>
<tr>
<td>
<table width = '500' align = 'center' border='0' cellpadding='2'  style='font-family: arial ; font-size: 12px'>
<?
if ($status == "Análise") {
?>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Seqüência </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<input type='radio' name='sequencia' value='CONTINUA'>Continua em Análise
		<br>
		<input type='radio' name='sequencia' value='AGUARDANDO'>Aguard.Execução
		<br>
		<input type='radio' name='sequencia' value='SEGUE'>Vai para Execução
	</td>
</tr>
<? } ?>

<?
if ($status == "Aguard.Execução") {
?>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Seqüência </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<input type='radio' name='sequencia' value='CONTINUA'>Continua Aguard.Execução
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type='radio' name='sequencia' value='SEGUE'>Vai para Execução

	</td>

</tr>
<? } 

if ($status == "Execução") {
?>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Seqüência </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<input type='radio' name='sequencia' value='CONTINUA'>Continua em Execução
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type='radio' name='sequencia' value='SEGUE'>Resolvido

	</td>

</tr>
<? } ?>
<tr>
<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width:1px'  align='center' colspan='2'>
<textarea name='comentario' cols='60' rows='6' wrap='VIRTUAL'><?echo $comentario;?></textarea><br>
<input type='checkbox' name='exigir_resposta' value='t'>Exigir resposta do usuário
<input type='checkbox' name='interno' value='t'>Chamado Interno
</td>

</tr>
<tr>
<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px' align='center' colspan='2'>
Arquivo <input type='file' name='arquivo' size='50' class='frm'>
</td>
</tr>
<tr>
<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px' align='center' colspan='2'>
<center><input type='submit' name='btn_acao' value='Responder Chamado'>
</center>
</td>
</tr>
</table>
</td>
<td valign='top'>

	<table width = '250' align = 'center'  cellpadding='2' cellspacing='1' border='0' style='font-family: arial; font-size:11px'>
	<tr>
		<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px' colspan='2' align='center'><strong><font size='5'><?echo $hd_chamado; ?></font></strong></td>
		</tr>
	<tr>
	<tr>
		<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Status </strong></td>
		<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align = 'center' >
			<select name="status" size="1"  style='width: 150px;'>
			<!--<option value=''></option>-->
			<option value='Novo'      <? if($status=='Novo')      echo ' SELECTED '?> >Novo</option>
			<option value='Análise'   <? if($status=='Análise')   echo ' SELECTED '?> >Análise</option>
			<option value='Execução'  <? if($status=='Execução')  echo ' SELECTED '?> >Execução</option>
			<option value='Aguard.Execução'  <? if($status=='Aguard.Execução')  echo ' SELECTED '?> >Aguard.Execução</option>
			<option value='Aprovação' <? if($status=='Aprovação') echo ' SELECTED '?> >Aprovação</option>
			<option value='Cancelado' <? if($status=='Cancelado') echo ' SELECTED '?> >Cancelado</option>
			<option value='Resolvido' <? if($status=='Resolvido') echo ' SELECTED '?> >Resolvido</option>
			</select>
		</td>
		</tr>
	<tr>
	<td bgcolor='#CED8DE' style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>Atendente</strong></td>
	<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'  align='center' >
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
		<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Categoria </strong></td>
		<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
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
		<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Tipo </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
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
		<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Prazo </strong></td>
		<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
		<input type='text' size='2' maxlength ='5' name='prazo_horas' value='<?= $prazo_horas ?>' <?
		if($analista_hd <> "sim") echo "readonly";
		?> class='caixa'> Hr.
		
		</td>
	</tr>
<? if($analista_hd == "sim"){ ?>
	<tr>
		<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Cobrar ? </strong></td>
		<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
		<input type='checkbox' name='cobrar' value='t' <? if ($cobrar == "t") echo "Checked";?>> Sim
		
		</td>
	</tr>

<? }else{ ?>
	<input type='hidden' name='cobrar' value='<? echo $cobrar;?>'>
	<tr>
		<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Cobrar ? </strong></td>
		<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
		<? if ($cobrar == "t"){ echo "Sim";}else{ echo "Não";}
?> 
		
		</td>
	</tr>
<? } ?>
	<tr>
		<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Arquivo:</strong></td>
		<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
		<input name='programa' id='programa'value='' class='caixa' size='25' onKeyUp = 'recuperardados(<? $hd_chamado?>)' onblur='this.value=""'><br>
		</td>
	</tr>
	<tr>
	<td  colspan='2'><div id='conteudo' class='Chamados2' style='position: absolute;opacity:.80;'>Digite no mínimo <br>4 caracteres</div></td>
	</tr>


	</TABLE>



</td>
</table>
<?
/*--=== ARQUIVOS SOLICITADOS ==================================================--*/
echo "<br><DIV class='exibe' id='dados' value='1' align='center'><font size='1'>Por favor aguarde um momento, carregando os dados...<br><img src='../imagens/carregar_os.gif'></DIV>";
echo "<script language='javascript'>Exibir('dados','','','');</script>";


echo "<script language=\"JavaScript1.2\">editor_generate('comentario');</script>";

echo "</form>";


?>



<? include "rodape.php" ?>
