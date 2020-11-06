<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";

# Fábricas que tem permissão para esta tela
if(!in_array($login_fabrica, array(1))) {
	header("Location: menu_callcenter.php");
	exit;
}

if(isset($_GET['ordem_servico'])){
	header('Content-Type: application/json');
	$os = buscaOS($_REQUEST['ordem_servico']);
	echo json_encode(array_map(utf8_encode,$os));
	die();
}


if($_SERVER["REQUEST_METHOD"] == "POST") {
	header('Content-Type: application/json');
	$post = (object) $_POST;
	$columns = array();
	$params = array();

	switch($post->action) {
		case 'gravar':
		try{
			$advertencia = saveAdvertencia($post);
			echo json_encode(array(
					'success'=>true,
					'message' => utf8_encode('Ocorrência '.$advertencia.' cadastrada com sucesso!')
				));
		}
		catch(Exception $ex){
			list($field,$message) = explode('::',$ex->getMessage());
			$error = array();
			if(!empty($field))
				$error[] = $field;
			echo json_encode(array(
				'error'=>$error,
				'success'=>false,
				'message'=> utf8_encode($message)
				));
		}

		break;

		case 'alterar':
		try{
			$advertencia = saveAdvertenciaUpdate($post);
			echo json_encode(array(
					'success'=>true,
					'message' => utf8_encode('Ocorrência '.$advertencia.' alterada com sucesso!')
				));
		}
		catch(Exception $ex){
			list($field,$message) = explode('::',$ex->getMessage());
			$error = array();
			if(!empty($field))
				$error[] = $field;
			echo json_encode(array(
				'error'=>$error,
				'success'=>false,
				'message'=> utf8_encode($message)
				));
		}

		break;
	}
}

function buscaPosto($codigo_posto){
	global $login_fabrica;
	global $con;
	$sql = 'SELECT
				tbl_posto_fabrica.posto,
				tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome,
				tbl_posto_fabrica.contato_email
			FROM tbl_posto_fabrica
			INNER JOIN tbl_posto
				ON (tbl_posto_fabrica.posto = tbl_posto.posto)
			WHERE codigo_posto LIKE $1
			AND fabrica = $2;';
	$params = array($codigo_posto,$login_fabrica);
	$result = pg_query_params($con,$sql,$params);
	if(!$result)
		throw new Exception(pg_last_error($con));
	if(pg_num_rows($result) != 1)
		return false;
	return pg_fetch_assoc($result);
}

function buscaAdmin(){
	global $con;
	global $fabrica;
	global $login_admin;
	$sql = 'SELECT
				admin,
				fabrica,
				login,
				email
			FROM tbl_admin
			WHERE admin = $1;';
	$params = array($login_admin);
	$result = pg_query_params($con,$sql,$params);
	if(!$result)
		throw new Exception(pg_last_error($con));
	if(pg_num_rows($result) != 1)
		return false;
	return pg_fetch_assoc($result);
}

function saveAdvertencia($postObject){
	global $con;
	global $login_fabrica;
	global $login_admin;

	$posto = buscaPosto($postObject->codigo_posto);
	if(!$posto)
		throw new Exception('codigo_posto::Posto Desconhecido');
	$admin = buscaAdmin();
	if(!$admin)
		throw new Exception('::Falha ao buscar Admin');

	if (strlen($postObject->ordem_servico)) {
		$os = buscaOS($postObject->ordem_servico);
		if(!$os)
			throw new Exception('ordem_servico::Ordem de Serviço não Encontrada');
	}

	$columns = array();
	$params = array();

	if($postObject->action != 'gravar')
		return false;

	switch($postObject->tipo_cadastro) {

		case "boletim_ocorrencia":
			$columns = array_merge($columns,array('numero_sac','tipo_ocorrencia'));
			$params = array_merge($params,array($postObject->numero_sac,$postObject->tipo_ocorrencia));
		break;
		case "advertencia":
			$columns = array_merge($columns,array('numero_advertencia'));
			$params = array_merge($params,array(getNumeroAdvertencia($postObject->codigo_posto)));
		break;
		default:
			throw new Exception('tipo_ocorrencia::Tipo de ocorrência não Suportada');
	}

	$columns = array_merge($columns,
		array(
			'posto',
			'mensagem',
			'fabrica',
			'admin',
			'contato_posto',
			'os',
			'produto',
			'parametros_adicionais'
		)
	);

	$parametros_adicionais = ["nivel_falha"=>$postObject->nivel_falha, "tratativa_atendimento"=>$postObject->tratativa_atendimento, "tipo_falha"=>$postObject->tipo_falha];
	$parametros_adicionais = json_encode($parametros_adicionais);

	$params = array_merge($params,
		array(
			$posto['posto'],
			$postObject->textarea,
			$login_fabrica,
			$login_admin,
			$postObject->contato_posto,
			$os['os'],
			empty($postObject->produto_produto)?null:$postObject->produto_produto,
			$parametros_adicionais
		)
	);

	$sql = 'INSERT INTO tbl_advertencia ';
	$sql.= '('.implode(',',$columns).') VALUES ';
	$sql.= '($'.implode(',$',range(1,count($columns))).')';
	$sql.= ' RETURNING advertencia; ';
	try{
		pg_query($con,"BEGIN TRANSACTION");
		$result = pg_query_params($con, $sql,$params);
		if(!$result)
			throw new Exception('::'.pg_last_error($con));
		$advertencia = pg_fetch_result($result, 0, 0);
		#Envia um e-mail para o posto e para o admin com os dados da advertência
		if($postObject->tipo_cadastro == "advertencia") {
			$assunto     = "Advertência nº $advertencia";
			$to = array($admin['email'],$posto['email']);
			$from = 'Black & Decker<helpdesk@telecontrol.com.br>';
			$postObject->texto .= "<br/> ----------------------------<br/> <b>Esta é uma mensagem automática, em caso de dúvida entrar em contrato com suporte Black & Decker</b>";
			if(!enviaEmail($to,$from,$postObject->texto,$assunto)){
				throw new Exception('::Não foi possivel enviar o E-Mail');
			}
		}
		pg_query ($con,"COMMIT TRANSACTION");
		return $advertencia;
	}
	catch(Exception $ex){
		pg_query ($con,"ROLLBACK TRANSACTION");
		throw $ex;
	}
}

function saveAdvertenciaUpdate($postObject){
	global $con;
	global $login_fabrica;
	global $login_admin;

	$posto = buscaPosto($postObject->codigo_posto);
	if(!$posto)
		throw new Exception('codigo_posto::Posto Desconhecido');
	$admin = buscaAdmin();
	if(!$admin)
		throw new Exception('::Falha ao buscar Admin');

	if (strlen($postObject->ordem_servico)) {
		$os = buscaOS($postObject->ordem_servico);
		if(!$os)
			throw new Exception('ordem_servico::Ordem de Serviço não Encontrada');
	}

	if($postObject->action != 'alterar')
		return false;

	switch($postObject->tipo_cadastro) {

		case "boletim_ocorrencia":
			$xcampos = ", numero_sac = '".$postObject->numero_sac."', tipo_ocorrencia = ".$postObject->tipo_ocorrencia;
		break;
		case "advertencia":
			$xcampos = ", numero_advertencia = '".getNumeroAdvertencia($postObject->codigo_posto)."'";
		break;
		default:
			throw new Exception('tipo_ocorrencia::Tipo de ocorrência não Suportada');
	}

	$prod = (empty($postObject->produto_produto)) ? 'null' : $postObject->produto_produto;

	$parametros_adicionais = ["nivel_falha"=>$postObject->nivel_falha, "tratativa_atendimento"=>$postObject->tratativa_atendimento, "tipo_falha"=>$postObject->tipo_falha];
	$parametros_adicionais = json_encode($parametros_adicionais);

	$sql = " UPDATE tbl_advertencia SET posto = ".$posto['posto'].", 
										mensagem = '".$postObject->textarea."', 
										fabrica = $login_fabrica, 
										admin = $login_admin, 
										contato_posto = '".$postObject->contato_posto."', 
										os = ".$os['os'].",
										produto = $prod, 
										parametros_adicionais = coalesce(parametros_adicionais, '{}') || '$parametros_adicionais'
										$xcampos
			WHERE advertencia = ".$postObject->adv."
			AND fabrica = $login_fabrica ";
	try{
		pg_query($con,"BEGIN TRANSACTION");
		$result = pg_query($con, $sql);
		if(!$result)
			throw new Exception('::'.pg_last_error($con));
		$advertencia = $postObject->adv;
		#Envia um e-mail para o posto e para o admin com os dados da advertência
		/*if($postObject->tipo_cadastro == "advertencia") {
			$assunto     = "Advertência nº $advertencia";
			$to = array($admin['email'],$posto['email']);
			$from = 'Black & Decker<helpdesk@telecontrol.com.br>';
			$postObject->texto .= "<br/> ----------------------------<br/> <b>Esta é uma mensagem automática, em caso de dúvida entrar em contrato com suporte Black & Decker</b>";
			if(!enviaEmail($to,$from,$postObject->texto,$assunto)){
				throw new Exception('::Não foi possivel enviar o E-Mail');
			}
		}*/
		pg_query ($con,"COMMIT TRANSACTION");
		return $advertencia;
	}
	catch(Exception $ex){
		pg_query ($con,"ROLLBACK TRANSACTION");
		throw $ex;
	}
}

function enviaEmail($emailTo,$emailFrom,$content,$subject){
	if(!is_array($emailTo))
		$emailTo = array($emailTo);
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= 'From: '.$emailFrom . "\r\n";
	$headers .= "To: $email" . "\r\n";
	return mail(implode(',',$emailTo), utf8_encode($subject), utf8_encode($content), $headers);
	return true;
}

function buscaOS($sua_os){
	global $con;
	global $login_fabrica;
// 	$posto = substr($os,0,strlen($os)-7);
// 	$sua_os = substr($os,-7);

    $pos = strpos($sua_os, "-");
    if ($pos === false) {
        //hd 47506
        if(strlen ($sua_os) > 11){
            $pos = strlen($sua_os) - (strlen($sua_os)-5);
        } elseif(strlen ($sua_os) > 10) {
            $pos = strlen($sua_os) - (strlen($sua_os)-6);
        } elseif(strlen ($sua_os) > 9) {
            $pos = strlen($sua_os) - (strlen($sua_os)-5);
        }else{
            $pos = strlen($sua_os);
        }
    }else{
        //hd 47506
        if(strlen (substr($sua_os,0,$pos)) > 11){#47506
            $pos = $pos - 7;
        } else if(strlen (substr($sua_os,0,$pos)) > 10) {
            $pos = $pos - 6;
        } elseif(strlen ($sua_os) > 9) {
            $pos = $pos - 5;
        }
    }
    if(strlen ($sua_os) > 9) {
        $xsua_os = substr($sua_os, $pos,strlen($sua_os));
        $codigo_posto = substr($sua_os,0,5);
    }

	$sql = "SELECT  tbl_posto_fabrica.codigo_posto || tbl_os.sua_os AS ordem_servico        ,
                    tbl_posto_fabrica.codigo_posto                  AS codigo_posto         ,
                    tbl_posto.nome                                  AS descricao_posto      ,
                    tbl_posto.posto                                 AS posto                ,
                    tbl_os.consumidor_nome                          AS nome_consumidor      ,
                    CASE WHEN tbl_os_troca.os_troca IS NOT NULL
                         THEN tbl_os.defeito_reclamado_descricao
                         ELSE tbl_defeito_constatado.descricao
                    END                                             AS defeito_constatado   ,
                    tbl_os.os                                                               ,
                    tbl_produto.produto                             AS produto_produto      ,
                    tbl_produto.referencia                          AS produto_referencia   ,
                    tbl_produto.descricao                           AS produto_descricao
			FROM    tbl_os
			JOIN    tbl_produto             ON  tbl_produto.produto         = tbl_os.produto
			JOIN    tbl_posto_fabrica       ON  tbl_os.posto                = tbl_posto_fabrica.posto
                                            AND tbl_os.fabrica              = tbl_posto_fabrica.fabrica
			JOIN    tbl_posto               ON  tbl_posto_fabrica.posto     = tbl_posto.posto
       LEFT JOIN    tbl_defeito_constatado  ON  tbl_os.defeito_constatado   = tbl_defeito_constatado.defeito_constatado
       LEFT JOIN    tbl_os_troca            ON  tbl_os_troca.os             = tbl_os.os
			WHERE   tbl_posto_fabrica.codigo_posto  = '$codigo_posto'
			AND     tbl_os.sua_os                   = '$xsua_os'
			AND     tbl_os.fabrica                  = $login_fabrica
			LIMIT   1;
    ";
    #exit($sql);
// 	$params = array($posto,$sua_os,$login_fabrica);
	$result = pg_query($con,$sql);

	if(!$result || pg_num_rows($result) != 1)
		return false;
	return pg_fetch_assoc($result);
}

function checkOS($os){
	global $con;
	global $login_fabrica;
	$sql = 'SELECT os,posto,fabrica FROM tbl_os WHERE os = $1 AND fabrica = $2 LIMIT 1;';
	$params = array($os,$login_fabrica);
	$result = pg_query_params($con,$sql,$params);
	if(!$result || pg_num_rows($result) != 1)
		return false;
	return pg_fetch_assoc($result);
}

function getNumeroAdvertencia($codigo_posto){
	global $con;
	global $login_fabrica;
	$sql = 'SELECT
				tbl_posto_fabrica.fabrica,
				tbl_posto_fabrica.posto,
				COALESCE(COUNT(tbl_advertencia.*),0) +1 AS numero_ocorrencia
			FROM tbl_posto_fabrica
			LEFT JOIN (SELECT * FROM tbl_advertencia WHERE tipo_ocorrencia IS NULL) AS tbl_advertencia
				ON(tbl_posto_fabrica.posto = tbl_advertencia.posto AND tbl_posto_fabrica.fabrica = tbl_advertencia.fabrica)
			WHERE tbl_posto_fabrica.fabrica = $1 AND tbl_posto_fabrica.codigo_posto LIKE $2
			GROUP BY tbl_posto_fabrica.fabrica,tbl_posto_fabrica.posto
			ORDER BY tbl_posto_fabrica.fabrica,tbl_posto_fabrica.posto;';
	$params = array($login_fabrica,$codigo_posto);
	$result = pg_query_params($con,$sql,$params);
	if(!$result || pg_num_rows($result) != 1)
		throw new Exception('::Falha não Esperada  ('.$codigo_posto.','.pg_num_rows($result).')'.pg_last_error($con));
	return pg_fetch_result($result,0,'numero_ocorrencia');
}

exit;
