<?php

$script_php = $_SERVER["SCRIPT_NAME"];
$pos = strpos($_SERVER["REQUEST_URI"], $script_php . '/');

if ($pos !== false) {
    header('HTTP/1.1 404 Not Found');
    include __DIR__ . '/../not_found.html';
    exit;
}

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../funcoes.php';

include_once '../class/communicator.class.php';

if ($lista_email==1){
	if (strlen($fabrica)>0){
		$sql ="SELECT TRIM(email) AS email,TRIM(contato_email) AS contato_email
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE fabrica = $fabrica AND tbl_posto_fabrica.posto = $posto";
		$resD = pg_query ($con,$sql) ;
		$email  = trim(pg_fetch_result($resD, 0, 'email'));
		$contato_email  = trim(pg_fetch_result($resD, 0, 'contato_email'));
		if (strlen($contato_email)==0){
			$contato_email = $email;
		}
		if (strlen($contato_email) > 0) {
			echo preg_replace('/^(...).+(@.+)/','$1********$2',$contato_email);
		} else {
			echo "KO";
		}
	exit;
	}
}

$body_top .= 'MIME-Version: 1.0' . "\n";
$body_top .= "Content-type: text/html; charset=UTF-8\n";
// $body_top .= "From: Telecontrol<suporte@telecontrol.com.br>\n";

//  Limpa a string para evitar SQL injection
if (!function_exists('anti_injection')) {
	function anti_injection($string) {
		$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
		return strtr(strip_tags(trim($string)), $a_limpa);
	}
}

/**
 * Devolve o valor '$param' do _GET ou do _POST (se tiver no GET, devolve o GET!) ou NULL se não existe
 *
 * getPost procura nos arrays _GET e _POST (primeiro no _GET) o índice $param. Se achar no _GET, retorna.
 * Se não achar no _GET, procura no _POST.
 * Se não achar em nenhum dos arrays, devolve NULL
 * Se achar, devolve já com o 'anti_injection' conferido.
 *
 * @param string $param //nome do parâmetro
 * @return string or NULL
 */
function getPost($param) {
	if (isset($_GET[$param])  and !is_null($_GET[$param]))	return anti_injection($_GET[$param]);
	if (isset($_POST[$param]) and !is_null($_POST[$param]))	return anti_injection($_POST[$param]);
	return null;
}

$btn_acao	= getPost('btn_acao');
$cnpj		= getPost('cnpj');
$posto		= getPost('posto');
$fabrica	= getPost('fabrica');
$lista_email= $_GET['lista_email'];
$atualizado	= $_GET['atualizado'];

$msg_erro = "";

//  Tradução
include_once 'trad_site/trad_primeiro_acesso_valida.php';
include_once 'trad_site/fn_ttext.php';

//  função que cria o e-mail para enviar, com texto diferenciado dependendo do idioma
//  É uma função porque ele é gerado para o posto e depois para o suporte. Assim só tem um código paraduas vezes
function email_acesso($oid, $codigo_posto, $fabrica, $fabrica_nome, $idioma = 'pt-br', $copia_suporte = false, $token = null) {

	global $email_posto;

	$email = $email_posto;
	$key   = md5($codigo_posto);

	$server = $_SERVER['SERVER_NAME'];
	$dir = explode ( '/', dirname($_SERVER['REQUEST_URI']) );

	unset($dir[count($dir)], $dir[count($dir) - 1] );

	$dir = implode('/',$dir);

	$url_libera_senha = "http://" . $server . $dir . "/externos/alterar_senha.php?token={$token}&tipo=primeiro_acesso";

	switch ($idioma) {
	    case 'es':
			$body  = "\n<br>\n<p>".
					 "Dirección para acceso: <a href='http://www.telecontrol.com.br'>http://www.telecontrol.com.br</a><br>".
					 "Sus datos de acceso al Sistema Telecontrol: </p>
					  Login: <strong>{$codigo_posto}</strong> <br />\n\n";
			if ($copia_suporte) $body .= " Fábrica: $fabrica_nome<br> \n";
			$body .= "<p>Para desbloquear el acceso al Sistema, \n".
					 "<a href='" . $url_libera_senha . "' ".
					 "style='color:blue;font-weight:bold'>HAGA CLICK AQUÍ</a></p>\n".
					 "<p>Si no se abre su navegador, copie esta dirección y péguela en la barra de dirección de su nagevador:<br>\n".
					 "<span style='color:red'>$url_libera_senha</span></p>\n\n".
					 " ---------------------------------------- <br>\n".
					 " TELECONTROL NETWORKING";
	    break;
	    case 'en':
// 	    break;  //  DESCOMENTAR O 'BREAK' QUANDO HOUVER TRADUÇÃO!!!!
	    case 'de':
// 	    break;  //  DESCOMENTAR O 'BREAK' QUANDO HOUVER TRADUÇÃO!!!!
		default:
			$body  = "\n<br>\n<p>".
					 "Endereço de acesso: http://www.telecontrol.com.br<br>".
					 "Seguem os dados de acesso ao sistema: </p>
					  Login de acesso: <strong>{$codigo_posto}</strong> <br />\n\n";
			if ($copia_suporte) $body .= " Fábrica: $fabrica_nome<br> \n";
			$body .= "<p>Para cadastrar sua senha, clique no link abaixo:<br>\n".
					 "<a href='$url_libera_senha' ".
					 "style='color:blue;font-weight:bold'>CLIQUE AQUI</a></p>\n".
					 "<p>Caso não consiga, copie e cole o endereço a seguir no seu navegador:<br>\n".
					 "<span style='color:red'>".$url_libera_senha."</span></p>\n\n".
					 " ---------------------------------------- <br>\n".
					 " TELECONTROL NETWORKING";
		break;
	}
	return $body;
}


if ($btn_acao == ttext($pavalida, "Gravar")){
	include_once('../helpdesk/mlg_funciones.php');
	//pre_echo($_POST);

	$posto				= getPost('posto');
	$fabrica			= getPost('fabrica');
	$senha				= getPost('senha');
	$confirmar_senha	= getPost('confirmar_senha');
	$capital_interior	= getPost('capital_interior');
	$pais				= getPost('pais');
	$email_confirma				= getPost('email_confirma');
	$email				= strtolower(getPost('contato_email'));


	if (strlen($email)==0) {
		$email = strtolower(getPost('contato_email'));
	}

	$sql = "SELECT contato_email
              FROM tbl_posto_fabrica
             WHERE tbl_posto_fabrica.posto   = $posto
               AND trim(contato_email) = '$email_confirma'
               AND tbl_posto_fabrica.fabrica = $fabrica";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) == 0) {
		$msg_erro .= utf8_encode("E-mail digitado não confere com o cadastro, favor entrar em contato com a fábrica <br />");
	}

	if (strlen($fabrica) > 0) {
		$xfabrica = "'".$fabrica."'";
	} else {
		$msg_erro .= ttext($pavalida, "sel_fabrica")."<br />";
	}

	if (strlen($capital_interior) > 0) {
		$xcapital_interior = "'".$capital_interior."'";
	} else {
		$xcapital_interior = 'null';
	}

	$sql = "SELECT posto
              FROM tbl_posto_fabrica
             WHERE tbl_posto_fabrica.posto   = $posto
               AND (tbl_posto_fabrica.senha  = '*' OR primeiro_acesso IS NULL)
               AND tbl_posto_fabrica.fabrica = $xfabrica";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) == 0) {

		$msg_erro .= ttext($pavalida, 'err_senha_cadastrada')."<br />";

	}

	/* DESCOMENTAR
	$reCaptcha   = $_POST["g-recaptcha-response"];

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api2.telecontrol.com.br/institucional/CaptchaV2",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
            "response"   => $reCaptcha,
            "privateKey" => "6LckVVIUAAAAAJvDmHg7_2zDSOKuD7ZABc7MNL2H",
            "ip"         => $_SERVER['REMOTE_ADDR']
        ]),
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "Content-Type: application/json"
        ),
    ));

    $response  = curl_exec($curl);
    $err       = curl_error($curl);

    $objetoRetorno = json_decode($response,1);

    if (!$objetoRetorno["success"]) {
        $msg_erro = "Preencha o reCaptcha <br />";
    }
	*/
	if (empty($msg_erro)) {

			$sql = "SELECT tbl_posto.posto               ,
					TRIM(tbl_posto.email) AS email       ,
					tbl_posto_fabrica.*                  ,
					tbl_posto.pais                       ,
					tbl_posto_fabrica.primeiro_acesso    ,
					tbl_fabrica.nome      AS nome_fabrica,
					tbl_posto_fabrica.posto_fabrica AS oid_posto_fabrica,
					TRIM(tbl_posto_fabrica.contato_email) AS contato_email
				FROM   tbl_posto
				JOIN   tbl_posto_fabrica USING (posto)
				JOIN   tbl_fabrica ON tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
				WHERE  tbl_posto_fabrica.posto   = $posto
				AND    tbl_posto_fabrica.fabrica = $xfabrica";
			$res = @pg_query($con,$sql);

			$codigo_posto    = pg_fetch_result($res,      0, 'codigo_posto');
			$pais            = trim(pg_fetch_result($res, 0, 'pais'));
			$primeiro_acesso = trim(pg_fetch_result($res, 0, 'primeiro_acesso'));
			$fabrica_nome    = pg_fetch_result($res,      0, 'nome_fabrica');
			$posto_email     = trim(pg_fetch_result($res, 0, 'email'));
			$contato_email   = trim(pg_fetch_result($res, 0, 'contato_email'));

			if (strlen($contato_email) >0) {
				$email_posto = $contato_email;
			}
			//echo $primeiro_acesso.$email_posto;
			if (strlen($pais)==0) {
				$pais = "BR";
			}
			$oid_posto_fabrica = pg_fetch_result($res,0,oid_posto_fabrica);

			if (strlen($email_posto) == 0)
				$msg_erro = ttext($pavalida, "err_no_email");

			if (trim(pg_result_error($res)) != '') $msg_erro = pg_result_error($res);

			$res2 = pg_query ($con,"BEGIN TRANSACTION");
				if (strlen($msg_erro) == 0){
					$sql = "UPDATE tbl_posto_fabrica SET
								data_expira_senha = current_date + interval '90day',
								login_provisorio  = TRUE
							WHERE tbl_posto_fabrica.posto   = $posto
							AND   tbl_posto_fabrica.fabrica = $xfabrica ";
					$res = @pg_query ($con,$sql);
					if (!is_resource($res)) $msg_erro = pg_result_error($res);

					if (strlen($primeiro_acesso)==0) {
						$sql = "UPDATE tbl_posto SET
								capital_interior = upper($xcapital_interior)
							WHERE tbl_posto.posto = $posto";

						$res = @pg_query ($con,$sql);
						if (!is_resource($res)) $msg_erro = pg_result_error($res);

						 /* $sql = "UPDATE tbl_posto_fabrica SET
								primeiro_acesso  = current_timestamp
							WHERE posto   = $posto
							  AND fabrica = $fabrica";*/

						//$res = @pg_query ($con,$sql);
						//if (!is_resource($res)) $msg_erro = pg_result_error($res);
					}
				}
	    $xcond = "'CREDENCIADO','EM DESCREDENCIAMENTO'";
		if ($fabrica == 203) {
	    	$xcond = "'EM CREDENCIAMENTO'";
		}

		$sql = "SELECT  tbl_posto.nome                  AS posto_nome  ,
						tbl_posto.cnpj 					AS posto_cnpj  ,	 
				        tbl_posto_fabrica.codigo_posto  AS posto_codigo,
						contato_email                   AS posto_email ,
				        tbl_posto_fabrica.senha         AS posto_senha ,
				        tbl_fabrica.nome              
				          AS fabrica_nome,
						tbl_posto_fabrica.posto_fabrica
				    FROM  tbl_posto
				    JOIN  tbl_posto_fabrica USING (posto)
				    JOIN  tbl_fabrica       USING (fabrica)
				    WHERE  UPPER(tbl_posto_fabrica.credenciamento)  IN ({$xcond})
				      AND  tbl_posto_fabrica.posto   = $posto
					  AND  tbl_posto_fabrica.fabrica = $xfabrica
				      ";
		$res = pg_query($con, $sql);
		if (!is_resource($res)) $msg_erro = pg_last_error($con);

		//die('Qtde.: "' . pg_last_error($con) .  '" / ' . pg_num_rows($res));

		if(pg_num_rows($res) > 0 and strlen($msg_erro) == 0) {

			$posto_nome     = pg_fetch_result($res, 0, 'posto_nome');
			$posto_email    = pg_fetch_result($res, 0, 'posto_email');
			$fabrica_nome   = pg_fetch_result($res, 0, 'fabrica_nome');
			$posto_fabrica  = pg_fetch_result($res, 0, 'posto_fabrica');
			$posto_codigo   = pg_fetch_result($res, 0, 'posto_codigo');

			$token = hash('sha256', $email_destino . ':' . $xfabrica . ':' . microtime() . mt_rand());
			$ip_solicitante = $_SERVER['HTTP_X_FORWARDED_FOR'] ? : $_SERVER['REMOTE_ADDR'];

			// GERA A SENHA
			$data = new DateTime();
			$data_solicitacao = $data->format('Y-m-d H:i:s.u');
			$insert_alteracao_senha = "INSERT INTO tbl_alteracao_posto_senha (posto_fabrica, token, data_solicitacao,tipo_alteracao, ip) VALUES ($posto_fabrica, '$token', '$data_solicitacao', 'primeiro_acesso', '$ip_solicitante')";
			pg_query($con, $insert_alteracao_senha);

			$email_origem  = "suporte@telecontrol.com.br";
			$email_destino = $posto_email;
			$assunto       = "Telecontrol - ".ttext($a_rec_senha, "Primeiro_Acesso");
			$corpo         = email_acesso($posto_fabrica,$posto_codigo, $fabrica, $fabrica_nome, $idioma, false, $token);

			$mailer = new TcComm("noreply@tc");

			$res = $mailer->sendMail(
				$email_destino,
				$assunto,
				utf8_encode($corpo),
				'noreply@tc.id'
			);
			
			if ($res === true){
				$res = pg_query ($con,"COMMIT TRANSACTION");
				$msg_sucesso = ttext($a_rec_senha, "enviado_email").": $email";
				$sucesso = "1";
			}else{
				$msg_erro.= ttext($a_rec_senha, "email_incorreto");
				$res = pg_query ($con,"ROLLBACK TRANSACTION");
			}
		}
		//gravar ip que solicito a troca de senha
		$parametros_adicionais = json_decode($parametros_adicionais, true);
		$parametros_adicionais["ip_esqueceu_senha"] = $_SERVER["REMOTE_ADDR"];
		$parametros_adicionais = json_encode($parametros_adicionais);
		$insertseguranca = "UPDATE tbl_posto_fabrica set parametros_adicionais = '$parametros_adicionais' where posto_fabrica = $posto_fabrica";
		pg_query($con, $insertseguranca);
	}

	/*if (empty($msg_erro)) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		//header ("Location: $PHP_SELF?posto=$posto&fabrica=$fabrica&key=$key");
		exit;
	} else {
		/*
		if (!mail($email_posto, utf8_decode($subject), utf8_decode($body_txt),"noreply@telecontrol.com.br"))  {
			if (empty($msg_erro)) {
				$msg_erro = ttext($pavalida, "err_email_ko");
			}
		} else {
			$res = pg_query ($con,"COMMIT TRANSACTION");
			$key = md5($posto);
			header ("Location: $PHP_SELF?posto=$posto&fabrica=$fabrica&key=$key");
			exit;
		}
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
*/
	/*
	include_once '../class/email/mailer/class.phpmailer.php';
	
	$body = email_acesso($oid_posto_fabrica,$codigo_posto, $senha, $fabrica, $fabrica_nome, $idioma);
	
	$mailer = new PhpMailer(true);
	$mailer->CharSet = "utf8";
	$mailer->IsSMTP();
	$mailer->Mailer = "smtp";

	$mailer->Host = 'ssl://smtp.gmail.com';
	$mailer->Port = '465';
	$mailer->SMTPAuth = true;

	$mailer->Username = "noreply@telecontrol.com.br";
	$mailer->Password = "tele6588";
	$mailer->SetFrom("noreply@telecontrol.com.br", "Suporte Telecontrol");
	$mailer->AddAddress($email_posto,$email_posto );
	$mailer->Subject = ttext($pavalida, "email_subject");
	$mailer->Body = $body;
	$mailer->IsHTML(true);

	try{
		$mailer->Send();
		$res = pg_query ($con,"COMMIT TRANSACTION");
		$key = md5($posto);
		header ("Location: $PHP_SELF?posto=$posto&fabrica=$fabrica&key=$key");
		exit;
	}catch(Exception $e){
		$msg_erro = ttext($pavalida, "err_email_ko");
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
	
	
	if (strlen($msg_erro) == 0){
		$email		= $email_posto;
		//$email		= "suporte@telecontrol.com.br";
		$key		= md5($codigo_posto);
		$assunto	= ttext($pavalida, "email_subject");
		$email_from	= "From: TELECONTROL <suporte@telecontrol.com.br>";
		$body_txt	= email_acesso($oid_posto_fabrica,$codigo_posto, $senha, $fabrica, $fabrica_nome, $idioma);
		$debug = true;
		if(!mail($email, utf8_decode($assunto), utf8_decode($body_txt), $email_from."\n $body_top")) {
			$msg_erro = ttext($pavalida, "err_email_ko");
			if ($debug) echo "$email, $assunto,$email_from<br>".nl2br($body_top);
		}
		#	Cópia para o suporte caso o PA não receba o e-mail. SEMPRE português!
		#  $body_txt = email_acesso($oid_posto_fabrica,$codigo_posto, $senha, $fabrica, $fabrica_nome, "pt-br", true); // comentado no hd_chamado=2615032

		// if(!mail($email_from, utf8_decode($assunto), utf8_decode($body_txt), "$body_top")){
		// 	$msg_erro = ttext($pavalida, "err_email_ko");
		// 	if ($debug) echo "$email, $assunto,$email_from<br>".nl2br($body_top).$msg_erro;
		// }
	}
	if (strlen($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		$key = md5($posto);
		header ("Location: $PHP_SELF?posto=$posto&fabrica=$fabrica&key=$key");
		exit;
	} else {
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
	 */
}

if ($btn_acao == ttext($pavalida, "Confirmar_Dados")) {
	function ConfirmaDado($novo,$atual,$nome) {
		return (strtolower($atual) != strtolower($novo))?"<b>$nome:</b> de <b>$atual</b> para <b>$novo</b>\n<br>\n":"";
	}
	$key				= trim($_GET["key"]);
	$posto				= trim($_POST['posto']);
	$fabrica			= trim($_POST['fabrica']);
	$alteracao_dados	= trim($_POST['alteracao_dados']);
	$pais				= trim($_POST['pais']);

	$contato_email		= trim(utf8_decode($_POST['contato_email']));
	$contato_endereco	= trim(utf8_decode($_POST['contato_endereco']));
	$contato_numero		= trim(utf8_decode($_POST['contato_numero']));
	$contato_complemento= trim(utf8_decode($_POST['contato_complemento']));
	$contato_bairro		= trim(utf8_decode($_POST['contato_bairro']));
	$contato_cep		= trim(utf8_decode($_POST['contato_cep']));
	$contato_cidade		= trim(utf8_decode($_POST['contato_cidade']));
	$posto_key			= md5($posto);

	if ($key == $posto_key AND strlen($posto)>0 AND strlen($fabrica)>0) {

		$sql = "SELECT  tbl_posto.nome                   AS posto_nome  ,
						tbl_posto.cnpj                   AS posto_cnpj  ,
						tbl_posto_fabrica.contato_email  AS posto_email ,
						tbl_posto.pais                                  ,
						tbl_posto_fabrica.codigo_posto                  ,
						tbl_posto_fabrica.senha                         ,
						tbl_fabrica.nome                 AS fabrica_nome,
						tbl_fabrica.email_cadastros      AS email_cadastros,
						tbl_posto_fabrica.contato_email,
						tbl_posto_fabrica.contato_endereco,
						tbl_posto_fabrica.contato_numero,
						tbl_posto_fabrica.contato_complemento,
						tbl_posto_fabrica.contato_bairro,
						tbl_posto_fabrica.contato_cidade,
						tbl_posto_fabrica.contato_cep,
						tbl_posto_fabrica.contato_estado
			FROM tbl_posto
			JOIN tbl_posto_fabrica USING (posto)
			JOIN tbl_fabrica       USING (fabrica)
			WHERE tbl_posto.posto = $posto
			AND tbl_posto_fabrica.fabrica = $fabrica";
		$res = pg_query ($con,$sql);

		if (pg_num_rows ($res) == 1) {
			$posto_nome           = utf8_encode(trim(pg_fetch_result($res,0,posto_nome)));
			$posto_cnpj           = utf8_encode(trim(pg_fetch_result($res,0,posto_cnpj)));
			$posto_email          = utf8_encode(trim(pg_fetch_result($res,0,posto_email)));
			$codigo_posto         = utf8_encode(trim(pg_fetch_result($res,0,codigo_posto)));
			$email_cadastros      = utf8_encode(trim(pg_fetch_result($res,0,email_cadastros)));

			$Xcontato_email       = utf8_encode(trim(pg_fetch_result($res,0,contato_email)));
			$Xcontato_endereco    = utf8_encode(trim(pg_fetch_result($res,0,contato_endereco)));
			$Xcontato_numero      = utf8_encode(trim(pg_fetch_result($res,0,contato_numero)));
			$Xcontato_complemento = utf8_encode(trim(pg_fetch_result($res,0,contato_complemento)));
			$Xcontato_bairro      = utf8_encode(trim(pg_fetch_result($res,0,contato_bairro)));
			$Xcontato_cidade      = utf8_encode(trim(pg_fetch_result($res,0,contato_cidade)));
			$Xcontato_cep         = utf8_encode(trim(pg_fetch_result($res,0,contato_cep)));

			$msg_alteracoes = "";
			$msg_alteracoes.= ConfirmaDado($contato_email,		$Xcontato_email,		"Email");
			$msg_alteracoes.= ConfirmaDado($contato_endereco,	$Xcontato_endereco,		"Endere&ccedil;o");
			$msg_alteracoes.= ConfirmaDado($contato_numero,		$Xcontato_numero,		"N&uacute;mero");
			$msg_alteracoes.= ConfirmaDado($contato_complemento,$Xcontato_complemento,	"Complemento");
			$msg_alteracoes.= ConfirmaDado($contato_bairro,		$Xcontato_bairro,		"Bairro");
			$msg_alteracoes.= ConfirmaDado($contato_cidade,		$Xcontato_cidade,		"Cidade");
			$msg_alteracoes.= ConfirmaDado($contato_cep,		$Xcontato_cep,			"CEP");

			$key = md5($posto);

			if (strlen($msg_alteracoes)>0){
				$assunto	= "Primeiro Acesso - Alterações de Dados";
				$email_from	= "From: TELECONTROL <suporte@telecontrol.com.br>";
				$email_to	= $email_cadastros;

				$body = "\n".
						" Prezado(a)<br><br>\n".
						" O Posto Autorizado: <br>\n".
						" <p>Raz&atilde;o Social: <b>$posto_nome</b><br> \n".
						" CNPJ: <b>$posto_cnpj</b><br> \n".
						" E-Mail: <b>$posto_email</b></p>\n".
						"<p>fez o primeiro acesso mas alterou as seguintes informa&ccedil;&otilde;es:</p>".
						"$msg_alteracoes<br>\n".
						"<br>\n".
						"<b>Essas altera&ccedil;&otilde;es n&atilde;o foram efetivadas no Sistema Telecontrol. ".
						"Por favor, valide esses dados e atualize o cadastro do posto.</b><br><br>\n".
						" ---------------------------------------- <br>\n".
						" TELECONTROL NETWORKING";

				#echo "$email_to, $assunto, $mens_corpo, $email_from.\n $body_top";
				if (mail($email_to, utf8_encode($assunto), utf8_encode($body), $email_from."\n $body_top")) {
					#$msg_erro = "Erro no envio de email de confirmação. Por favor, entre em contato com o suporte.";
					header ("Location: $PHP_SELF?posto=$posto&fabrica=$fabrica&key=$key&atualizado=1");
					exit;
				}
			}
			header ("Location: $PHP_SELF?posto=$posto&fabrica=$fabrica&key=$key&atualizado=1");
			exit;
		}
	}
}

function gerar_senha($tamanho, $maiusculas, $minusculas, $numeros){
	$ma = "ABCDEFGHIJKLMNOPQRSTUVYXWZ"; // $ma contem as letras maiúsculas
	$mi = "abcdefghijklmnopqrstuvyxwz"; // $mi contem as letras minusculas
	$nu = "0123456789"; // $nu contem os números
	$si = "!@#$%¨&*()_+="; // $si contem os símbolos
   
	if ($maiusculas){
		  $senha .= str_shuffle($ma);
	}
   
	if ($minusculas){
		$senha .= str_shuffle($mi);
	}

	if ($numeros){
		$senha .= str_shuffle($nu);
	}

	return substr(str_shuffle($senha),0,$tamanho);
}


// $body_top = "--Message-Boundary\n";
// $body_top .= "Content-type: text/html; charset=UTF-8\n";
// $body_top .= "Content-transfer-encoding: 7BIT\n";
// $body_top .= "Content-description: Mail message body\n\n";
header("Content-Type: text/html;charset=UTF-8");

#$html_titulo = ttext($pavalida, "Primeiro_Acesso");
$pagetitle = ttext($pavalida, "Primeiro_Acesso");

include('site_estatico/header.php');
?>
<!--<link rel="stylesheet" href="css/login_unico_envio_email.css" type="text/css" media="screen" />-->
<!-- DESCOMENTAR <script src='https://www.google.com/recaptcha/api.js?hl=pt-BR&onload=showRecaptcha&render=explicit' async defer></script> -->
<script>
$('body').addClass('pg log-page');

/* DESCOMENTAR
var showRecaptcha = function() {
    grecaptcha.render('reCaptcha', {
        'sitekey' : '6LckVVIUAAAAAEQpRdiIbRSbs_ePTTrQY0L4959J'
    });
};*/

</script>


<?php
if (strlen ($cnpj) > 0) {
	$cnpj = preg_replace('/[-.,+|\/()*_]|\s/', '', $cnpj); //2011-08-09 Postos de fora do Brasil não conseguem

	$sql  = "SELECT * FROM tbl_posto WHERE cnpj = '$cnpj'";
	$res  = @pg_query($con,$sql);

	if (pg_num_rows ($res) == 0) { ?>
	<script language="javascript">
		alert("<?=ttext($pavalida, "CNPJ_KO")?>");
		window.history.back();
	</script>
<?
		//$msg_erro = "CNPJ não cadastrado.";
		//header("Location: index.php");
		exit;
	} else {
		$cnpj				= pg_fetch_result($res,0,'cnpj');
		$posto				= pg_fetch_result($res,0,'posto');
		$nome				= utf8_encode(pg_fetch_result($res,0,'nome'));
		$email				= pg_fetch_result($res,0,'email');
		$capital_interior	= pg_fetch_result($res,0,'capital_interior');
		$pais				= trim(pg_fetch_result($res,0,'pais'));
		if (strlen($pais)==0) $pais = "BR";
	}
	$body_options = "onload='document.frm_cadastro_senha.fabrica.focus();' ";
}

//$body_options = "onload='document.frm_login.login.focus() ;' ";

//include_once "inc_header.php";

if ($pais && $pais != 'BR') $cook_idioma='es';
?>

<script language="JavaScript">
/*  Começa o jQuery 
jQuery().ready(function () {
	jQuery('#email_fabrica').bind("ajaxSend", function(){
		jQuery(this).html("<?=ttext($pavalida,"Aguarde")?>");
	});

	jQuery('#fabrica').change(function() {
		var email	= jQuery('#email_fabrica');
		var fabrica	= jQuery('#fabrica').val();
		var posto	= jQuery('#posto').val();
		if (fabrica=='') {
		    email.html = '';
			return false;
		}
		jQuery.ajax({
				url:	"<?=$PHP_SELF?>",
				data:	"lista_email=1&fabrica="+fabrica+"&posto="+posto,
				cache:	false,
				success:function (resposta) {
					if (resposta == "KO") {
						email.html("<?=ttext($pavalida,"err_no_email")?>");
						email.addClass('vermelho').css('font-size','10px');
					} else {
						email.val(resposta);
						email.addClass('azul').css('font-size','12px');
					}
				}

		});
	});
});  FIM jQuery   */
</script>

<section class="table h-img">
	<?php include('site_estatico/menu-pgi.php'); ?>
	<div class="cell">
		<div class="title"><h2>Primeiro Acesso</h2></div>
		<h3>Cadastro da Senha</h3>
	</div>
</section>

<section class="pad-1 login">
	<div class="main">
	<div class="alerts">
		<?php if (strlen($msg_erro) > 0) { $display_block = "style='display:block;'";} ?>
		<div class="alert error" id="mensagem_envio" <?=$display_block?>><i class="fa fa-exclamation-circle"></i><?php echo $msg_erro;?></div>
	</div>

<?php
$key = '';
if (strlen($msg_sucesso)>0 ) {
	$fabrica = $_GET["fabrica"];
	$posto   = $_GET["posto"]  ;
	$posto_key = md5($posto);
    
    if ($fabrica == 175){
    	$url_acesso = "https://posvenda.telecontrol.com.br/assist/externos/login_unico_new.php";
    }else{
    	$url_acesso = "http://www.telecontrol.com.br/";
		$url_acesso.= ($fabrica==20) ? "assist/bosch.php" : "";
	}
    
    if (!empty($msg_sucesso)) {
		$sql = "SELECT  tbl_posto.nome                   AS posto_nome  ,
						tbl_posto.cnpj                   AS posto_cnpj  ,
						tbl_posto_fabrica.contato_email  AS posto_email ,
						tbl_posto.pais                                  ,
						tbl_posto_fabrica.codigo_posto                  ,
						tbl_posto_fabrica.senha                         ,
						tbl_fabrica.nome                 AS fabrica_nome,
						tbl_posto_fabrica.contato_email,
						tbl_posto_fabrica.contato_endereco,
						tbl_posto_fabrica.contato_numero,
						tbl_posto_fabrica.contato_complemento,
						tbl_posto_fabrica.contato_bairro,
						tbl_posto_fabrica.contato_cidade,
						tbl_posto_fabrica.contato_cep,
						tbl_posto_fabrica.contato_estado
			FROM tbl_posto
			JOIN tbl_posto_fabrica USING (posto)
			JOIN tbl_fabrica       USING (fabrica)
			WHERE tbl_posto_fabrica.posto_fabrica = $posto_fabrica";

		$res = @pg_query ($con,$sql);

		if (pg_num_rows ($res) == 1) {
			$a_row = pg_fetch_assoc($res, 0);   //		Pega o registro completo num array com índice=nome campo
			foreach ($a_row as $campo => $dado) {  //	Cria cada variável com o nome do campo
				$$campo = utf8_encode($dado);
			}

			if ($fabrica == 50) { //50 ?>
				<br />
				<div>
				<div class="alerts">
					<div class="alert success" style="display:block"><i class="fa fa-check-circle"></i>Parabéns, você fez o primeiro acesso!</div>
				</div>
				Agora você pode acessar o sistema de <font color='3366ff'>Pós-Venda</font>, um moderno software de gerenciamento de assistências técnicas ON-LINE.<br>
				<br />
				Foi enviado um email para:
				<strong><?=$posto_email?></strong> com seus dados cadastrais
				para que você possa se logar ao sistema.<br><br>
				Junto ao email segue um link de confirmação, você deve acessar
				o endereço correspondente para que seu acesso ao sistema seja liberado.<br><br>
				Caso não receba o email verifique no seu webmail a pasta de <b>spam,</b>
				 <b>lixo eletrônico</b> ou similar.<br />
				 Se não recebeu o e-mail em no máximo 30 minutos, entre em contato com o suporte
				(<i>suporte@telecontrol.com.br</i> ) para que seu acesso seja liberado.<br><br>
				</div>
				<div>
					<h3>Dados Cadastrais e de Acesso</h3>
				   <h4>Dados Cadastrais</h4>
					<ul>
						<li><span>Fábrica</span><?=$fabrica_nome?></li>
						<li><span>Posto</span><?=$posto_nome?></li>
						<li><span>CNPJ</span><?=$posto_cnpj?></li>
					</ul>
					<h4>Dados de Acesso:</h4>
					<ul>
						<li><span>Código Posto</span> <span>Confirmar Email</span></li>
						<li><span>Senha</span> <span class='red italic'>Confirmar Email</span></li>
						<li>
							<span>Endereço de Acesso</span>
							Página &nbsp;<i><a href='http://www.telecontrol.com.br'>www.telecontrol.com.br</a></i>
						</li>
					</ul>
				</div><br/>

<?				if ($atualizado == "1"){ ?>
					<div id='primeiro'>
						<h3 style='color:#3366cc;font-size:14px;font-weight:bold'>Confirmação de Dados</h3>
						<p style='padding:2px'><b>As alterações foram envidas para a Fábrica</b></p>
						<p style='padding:2px'>
							As informações alteradas ainda não foram efetivadas.<br>
							Serão efetivadas após validação.
						</p>
						<br>
						<p>Para acessar o sistema:&nbsp;
							<a href='<?=$url_acesso?>' style='text-decoration:underline!important'>Clique aqui</a> para ir a página inicial da Telecontrol.
						</p>
<?				} else {	?>
					<form name="frm_cadastro_complemento" method="post" action='<? echo $PHP_SELF ?>?key=<?=$key?>'>
					<input type="hidden" name="alteracao_dados" value="<?=$posto?>">
					<input type="hidden" name="fabrica" value="<?=$fabrica?>">
					<input type="hidden" name="posto"   value="<?=$posto?>">
					<input type="hidden" name="pais" value="<?=$pais?>">
					<fieldset class='colunas' style='border:0;'>
						<legend>Confirmação de Dados</legend>
						<p>
							Verifique os dados abaixo. Se alguma informação estiver incorreta, faça a alteração.
							As informações só serão efetivadas após a Fábrica validar.
	                        <br /><br />
	                        <label>E-Mail</label>
							<input  type='text' name='contato_email'
									size='35' maxlength='80' value='<?=$contato_email?>' />
							<br />
	                        <label>Endereço</label>
							<input  type='text' name='contato_endereco'
									size='40' maxlength='50' value='<?=$contato_endereco?>'>
							<br />
	                        <label>Número</label>
							<input  type='text' name='contato_numero'
									size='5' maxlength='5' value='<?=$contato_numero?>'>
							<label>Complemento</label>
							<input type='text' name='contato_complemento' size='40' maxlength='50' value='<?=$contato_complemento?>'>
							<label>Bairro</label>
							<input type='text' name='contato_bairro' size='40' maxlength='50' value='<?=$contato_bairro?>'>
							<br />
	                        <label>CEP</label>
							<input type='text' name='contato_cep' size='10' maxlength='8' value='<?=$contato_cep?>'>
							<br />
	                        <label>Cidade</label>
							<input type='text' name='contato_cidade' size='40' maxlength='50' value='<?=htmlentities($contato_cidade,ENT_QUOTES,'ISO-8859-1')?>'>
						</p>
					</fieldset>
					<label>&nbsp;</label>
					<label>&nbsp;</label>
					<label>&nbsp;</label>
					<button type="submit" name='btn_acao' value='<?=ttext($pavalida, "Confirmar_Dados")?>'><i class="fa fa-check"></i>Concluir</button>
					<!--<input name='btn_acao' value='<?=ttext($pavalida, "Confirmar_Dados")?>' type='submit'> -->
				</form>
<?				}   ?>
				<br>Página de acesso:&nbsp;<a href='<?=$url_acesso?>' style='text-decoration:underline!important'>
					Clique aqui</a> para ir a página inicial do Assist Telecontrol
				</div>
<?			} else {
				echo "<div>\n";
				if ($pais!='BR') { //BR    ?>
					<div class="alerts">
						<div class="alert success" style="display:block"><i class="fa fa-check-circle"></i>Se envió un correo electrónico a <strong><?= $email_destino ?></strong> Para registrar la contraseña de acceso</div>
					</div>
					<br>
<?				} else {
?>
					<div class="alerts">
						<div class="alert success" style="display:block"><i class="fa fa-check-circle"></i>Foi enviado um e-mail para <strong><?= $email_destino ?></strong> para cadastrar a Senha de Acesso</div>
					</div>
					</section>
<?				}
			}
		}
	}
} else {
	if (strlen($posto) == 0) {
	?>
		<div class="alerts">
			<div class="alert error" style='display:block'><i class="fa fa-exclamation-circle"></i><?=ttext($pavalida, "err_sem_acesso")?></div>
			<h3><?=ttext($pavalida, "err_sem_acesso_msg")?></h3>
		</div>
	</div>
<?php   include('site_estatico/footer.php');
        die;
	}
	
	$sqlBuscaPosto = "SELECT UPPER(tbl_posto_fabrica.credenciamento) AS credenciamento
						FROM tbl_fabrica
						JOIN tbl_posto_fabrica USING (fabrica)
						LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
						WHERE tbl_posto_fabrica.posto = {$posto}
						AND UPPER(credenciamento) = 'EM CREDENCIAMENTO'
						AND primeiro_acesso IS NULL
						AND ativo_fabrica IS TRUE
						AND fabrica not in(10,133)
						AND tbl_posto.cnpj = '{$cnpj}'
					";

	$resBuscaPosto = pg_query($con, $sqlBuscaPosto);

	$credenciamento_posto 	= pg_fetch_result($resBuscaPosto, 0, 'credenciamento');

	if($credenciamento_posto == "EM CREDENCIAMENTO"){
		$sql = "SELECT tbl_posto_fabrica.fabrica,tbl_fabrica.nome,contato_email
			  	FROM tbl_fabrica
			  	JOIN tbl_posto_fabrica USING (fabrica)
			 	WHERE posto = $posto
			   	AND UPPER(credenciamento) = 'EM CREDENCIAMENTO'
			   	AND primeiro_acesso IS NULL
			   	AND ativo_fabrica IS TRUE
			   	AND fabrica not in(10,133)
		  		ORDER BY nome";		
		
	} else {
		$sql = "SELECT tbl_posto_fabrica.fabrica,tbl_fabrica.nome,contato_email
				  FROM tbl_fabrica
				  JOIN tbl_posto_fabrica USING (fabrica)
				 WHERE posto = $posto
				   AND UPPER(credenciamento) = 'CREDENCIADO'
				   AND primeiro_acesso IS NULL
				   AND ativo_fabrica IS TRUE
				   AND fabrica not in(10,133)
			  ORDER BY nome";
	}
//  MLG 19/02/2010 - A fábrica 10 não deve sair no SELECT...
// "SELECT  tbl_posto_fabrica.*,
// 							tbl_fabrica.fabrica,
// 							tbl_fabrica.nome
// 					FROM    tbl_posto_fabrica
// 					JOIN    tbl_fabrica USING (fabrica)
// 					WHERE   tbl_posto_fabrica.posto = $posto
// 					ORDER BY tbl_fabrica.nome;";
		$res = @pg_query($con,$sql);
		$tot_fabricas = pg_num_rows($res);
		//echo $sql;
		if ($tot_fabricas > 0){ ?>
		<div class="title">
			<h1>
				Nome: <span><?=$nome;?></span>
			</h1>
		</div>
		<div class="sep"></div>
		<div class="desc">
			<h3 class="m-b"><?=ttext($pavalida, "instrucoes")?>:</h3>
			<ul>
				<li><?=ttext($pavalida, "instrucoes_1")?></li>
				<li><?=ttext($pavalida, "instrucoes_2")?></li>
				<li><?=ttext($pavalida, "instrucoes_5")?></li>
				<li><?=ttext($pavalida, "instrucoes_6")?></li>
			</ul>
		</div>

		<form name="frm_cadastro_senha" id='pa_senha' method="post" >
			<input type='hidden' name='cnpj' value='<?php echo $cnpj;?>'>
			<input type='hidden' name='btn_acao' value='Gravar'>
			<input type="hidden" name="pais" value="<?=$pais?>">
			<h2><?=ttext($pavalida, "cadastro_de_usuarios")?></h2>
			<input type='hidden' name='posto' id='posto' value='<?=$posto?>'>

			<select name="fabrica" id='fabrica'>
				<option value=''><?=ttext($pavalida, "instrucoes_1")?></option>
					<?
						for($i=0; $i<$tot_fabricas; $i++) {
							$i_fabrica = pg_fetch_result($res,$i,fabrica);
							$i_nome    = pg_fetch_result($res,$i,nome);
							$email[$i_fabrica] = pg_fetch_result($res,$i,contato_email);
							unset($i_sel);
							$i_sel = ($fabrica == $i_fabrica) ? " SELECTED" : "";
							echo "\t\t\t\t\t\t<option value='$i_fabrica'$i_sel>".pg_fetch_result($res,$i,nome)."</option>\n";
						}
					?>
			</select>
			<div class="input-box">
				<p><span>Confirmar E-mail</span></p><input id='email_confirma' type="email" name ='email_confirma' value="">
			</div>

			<select name="capita_interior">
				<option value="" selected>Selecione: Capital / Interior</option>
				<option value='CAPITAL' <? if ($capital_interior == 'CAPITAL')   echo ' selected' ?>><?=ucfirst(strtolower($cap))?>CAPITAL</option>
				<option value='INTERIOR' <? if ($capital_interior == 'INTERIOR') echo ' selected' ?>><?=ucfirst(strtolower($int))?>INTERIOR</option>
			</select>
			<!-- DESCOMENTAR
			<div id="reCaptcha" style="margin-top: 15px;">
                Carrengado reCaptcha
            </div> -->

			<button type="button" type="submit" name="btn_acao" value='Gravar' class="input_gravar"
							 onclick="verifica_primeiro_acesso_cadastro('');"><i class="fa fa-check"></i>&nbsp;&nbsp;<?=ttext($pavalida, "Gravar");?>&nbsp;&nbsp;</button>
	</form>
		</div>
</section>
<?	} else {    ?>
		<!--<h3><?=ttext($pavalida, "sem_pa_subtitle")?></h3>
		<p>&nbsp;</p>-->
			<div class="border_tc_8" id="ex">
				<p><?=ttext($pavalida, "prezado_usuario")?>,
				<br />
				<?=ttext($pavalida, "nao_tem_pa")?>.</p>
				<p>&nbsp;</p>
				<p><?=ttext($pavalida, "caso_de_erro")?><a href='contato.php'><?=ttext($a_trad_header, 'Contato')?></a>, <?=ttext($pavalida, "ou_email")?>&nbsp;
				<a href="mailto:suporte@telecontrol.com.br" style="text-decoration:underline">suporte@telecontrol.com.br</a>) <?=ttext($pavalida, "para_esclarecimentos")?>.</p>
				<!--<p>&nbsp;</p>
			    <a href='<?=$url_acesso?>' style='text-decoration:underline!important'><?=ttext($pavalida, "Clique_aqui")?></a> <?=ttext($pavalida, "para_acessar_o_sistema")?>.-->
		</div>
<?	}?>
</div>
<?}?>
<div class="blank_footer">&nbsp;</div>

<?php include('site_estatico/footer.php') ?>
