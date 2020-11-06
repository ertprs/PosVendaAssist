<?php
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';

if ($lista_email==1){
	if (strlen($fabrica)>0){
		$sql ="SELECT TRIM(email) AS email,TRIM(contato_email) AS contato_email
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE fabrica = $fabrica AND tbl_posto_fabrica.posto = $posto";
// echo "<pre>$sql</pre>\n";
		$resD = pg_query ($con,$sql) ;
		$email  = trim(pg_fetch_result($resD, 0, 'email'));
		$contato_email  = trim(pg_fetch_result($resD, 0, 'contato_email'));
		if (strlen($contato_email)==0){
			$contato_email = $email;
		}
		if(strlen($contato_email) > 0){
			echo $contato_email;
		} else {
			echo "KO";
		}
	exit;
	}
}

$body_top .= 'MIME-Version: 1.0' . "\n";
$body_top .= "Content-type: text/html; charset=UTF-8\n";
$body_top .= "From: Telecontrol<suporte@telecontrol.com.br>\n";

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
$body_txt	= email_acesso($oid_posto_fabrica,$codigo_posto, $senha, $fabrica, $fabrica_nome, $idioma);

function email_acesso($oid, $codigo_posto, $senha, $fabrica, $fabrica_nome, $idioma = 'pt-br', $copia_suporte = false) {

	global $email_posto;

	$email = $email_posto;
	$key   = md5($codigo_posto);

	$server = $_SERVER['SERVER_NAME'];
	$dir = explode ( '/', dirname($_SERVER['REQUEST_URI']) );

	unset($dir[count($dir)], $dir[count($dir) - 1] );

	$dir = implode('/',$dir);

	$url_libera_senha = 'http://' . $server . $dir . '/libera_senha.php?'.
						"id=$key&id2=$codigo_posto&id3=$fabrica&id4=$oid";
	switch ($idioma) {
	    case 'es':
			$body  = "\n<br>\n<p>".
					 "Dirección para acceso: <a href='http://www.telecontrol.com.br'>http://www.telecontrol.com.br</a><br>".
					 "Sus datos de acceso al Sistema Telecontrol: </p>\n\n";
			if ($copia_suporte) $body .= " Fábrica: $fabrica_nome<br> \n";
			$body .= "Usuario: $codigo_posto<br> \n".
					 "Clave: $senha<br>\n\n".
					 "<p>Para desbloquear el acceso al Sistema, \n".
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
					 "Endereço de acesso: <a href='http://www.telecontrol.com.br'>http://www.telecontrol.com.br</a><br>".
					 "Seguem os dados de acesso ao sistema: </p>\n\n";
			if ($copia_suporte) $body .= " Fábrica: $fabrica_nome<br> \n";
			$body .= "Login: $codigo_posto<br> \n".
					 "Senha: $senha<br>\n\n".
					 "<p>Para liberar seu acesso ao site, clique no link abaixo:<br>\n".
					 "<a href='$url_libera_senha' ".
					 "style='color:blue;font-weight:bold'>CLIQUE AQUI</a></p>\n".
					 "<p>Caso não consiga, copie e cole o endereço a seguir no seu navegador:<br>\n".
					 "<span style='color:red'>$url_libera_senha</span></p>\n\n".
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
	$email				= strtolower(getPost('contato_email'));

	if(strlen($email)==0){
		$email = strtolower(getPost('contato_email'));
	}

	if (strlen($confirmar_senha) > 0) {
		$xconfirmar_senha = strtolower($confirmar_senha);
	}else{
		$msg_erro = ttext($pavalida, "err_conf_pass");
	}


	//Wellington 31/08/2006 - MINIMO 6 CARACTERES SENDO UM MINIMO DE 2 LETRAS E MINIMO DE 2 NUMEROS
	if (strlen($senha) > 0) {
		if (strlen($senha) > 5 and strlen($senha) < 11) {
			$senha = strtolower($senha);

			//- verifica qtd de letras e numeros da senha digitada -//
			$count_letras	= preg_match_all('/[a-z]/i', $senha, $a_letras);
			$count_nums		= preg_match_all('/\d/', $senha, $a_nums);
			$count_invalido	= preg_match_all('/\W/', $senha, $a_invalidos);

			if ($count_invalido > 0) {
				$msg_erro = ttext($pavalida, "err_senha_invalidos");
			}
			if ($count_letras < 2) {
				$msg_erro = ttext($pavalida, "err_senha_2_letras");
			}
			if ($count_nums < 2){
				$msg_erro = ttext($pavalida, "err_senha_2_nums");
			}
		}else{
			$msg_erro = ttext($pavalida, "err_senha_6_min");
		}

		$xsenha = "'".$senha."'";
	}else{
		$msg_erro = ttext($pavalida, "err_senha_vazia");
	}

	if (strlen($fabrica) > 0) {
		$xfabrica = "'".$fabrica."'";
	}else{
		$msg_erro = ttext($pavalida, "sel_fabrica");
	}

	if (strlen($capital_interior) > 0){
		$xcapital_interior = "'".$capital_interior."'";
	}else{
		$xcapital_interior = 'null';
	}

	$sql = "SELECT posto
			FROM tbl_posto_fabrica
			WHERE  tbl_posto_fabrica.posto   = $posto
			AND    (tbl_posto_fabrica.senha   = '*' OR primeiro_acesso IS NULL)
			AND    tbl_posto_fabrica.fabrica = $xfabrica";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) == 0) {

		$msg_erro = ttext($pavalida, 'err_senha_cadastrada');

	}

	// Verifica se o posto já tem codigo e senha cadastrados para alguma outra fábrica
	$sql = "SELECT tbl_posto_fabrica.fabrica
			FROM   tbl_posto_fabrica
			WHERE  tbl_posto_fabrica.posto   = $posto
			AND    tbl_posto_fabrica.senha   = $xsenha
			AND    tbl_posto_fabrica.fabrica <> $xfabrica";
	$res = @pg_query($con,$sql);

	if (@pg_num_rows ($res) > 0) {
		$msg_erro = ttext($pavalida, "err_senha_dup");
	}
	// verifica se o posto já tem codigo e senha cadastrados para alguma outra fábrica

	if (strlen($msg_erro) == 0) {
		if($senha == $xconfirmar_senha){
			$sql = "SELECT tbl_posto.posto               ,
					TRIM(tbl_posto.email) AS email       ,
					tbl_posto_fabrica.*                  ,
					tbl_posto.pais                       ,
					tbl_posto_fabrica.primeiro_acesso    ,
					tbl_fabrica.nome      AS nome_fabrica,
					tbl_posto_fabrica.oid AS oid_posto_fabrica,
					TRIM(tbl_posto_fabrica.contato_email) AS contato_email
				FROM   tbl_posto
				JOIN   tbl_posto_fabrica USING (posto)
				JOIN   tbl_fabrica ON tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
				WHERE  tbl_posto_fabrica.posto   = $posto
				AND    tbl_posto_fabrica.fabrica = $xfabrica";
			$res = @pg_query($con,$sql);

			$codigo_posto      =      pg_fetch_result($res,0,'codigo_posto');
			$pais              = trim(pg_fetch_result($res,0,'pais'));
			$primeiro_acesso   = trim(pg_fetch_result($res,0,'primeiro_acesso'));
			$fabrica_nome      =      pg_fetch_result($res,0,'nome_fabrica');
			$posto_email       = trim(pg_fetch_result($res,0,'email'));
			$contato_email     = trim(pg_fetch_result($res,0,'contato_email'));

			if(strlen($contato_email) >0){
				$email_posto = $contato_email;
			}else{
				$email_posto = $posto_email;
			}
			//echo $primeiro_acesso.$email_posto;
			if(strlen($pais)==0){
				$pais = "BR";
			}
			$oid_posto_fabrica = pg_fetch_result($res,0,oid_posto_fabrica);

			if (strlen($email_posto) == 0)
				$msg_erro = ttext($pavalida, "err_no_email");

			if (trim(pg_result_error($res)) != '') $msg_erro = pg_result_error($res);

			$res2 = pg_query ($con,"BEGIN TRANSACTION");
			if(strlen(trim($xsenha)) > 0){
				if (strlen($msg_erro) == 0){
					if (pg_num_rows ($res) > 0) {
						$sql = "UPDATE tbl_posto_fabrica SET
									senha             = $xsenha                        ,
									data_expira_senha = current_date + interval '90day',
									login_provisorio  = TRUE
								WHERE tbl_posto_fabrica.posto   = $posto
								AND   tbl_posto_fabrica.fabrica = $xfabrica ";
						$res = @pg_query ($con,$sql);
						if (!is_resource($res)) $msg_erro = pg_result_error($res);

						if(strlen($primeiro_acesso)==0){
							$sql = "UPDATE tbl_posto SET
									capital_interior = upper($xcapital_interior)
								WHERE tbl_posto.posto = $posto";

							$res = @pg_query ($con,$sql);
							if (!is_resource($res)) $msg_erro = pg_result_error($res);

							$sql = "UPDATE tbl_posto_fabrica SET
									primeiro_acesso  = current_timestamp
								WHERE posto   = $posto
								  AND fabrica = $fabrica";

							$res = @pg_query ($con,$sql);
							if (!is_resource($res)) $msg_erro = pg_result_error($res);
						}
					}else{
						$msg_erro = ttext($pavalida, "err_update_senha");
					}
				}
			}else{
				$msg_erro = ttext($pavalida, "err_update_senha");
			}
		}else {
			$msg_erro = ttext($pavalida, "err_senha_nao_bate");
		}
	}

	if (strlen($msg_erro) == 0){
		$email		= $email_posto;
		//$email		= "suporte@telecontrol.com.br";
		$key		= md5($codigo_posto);
		$assunto	= ttext($pavalida, "email_subject");
		$email_from	= "TELECONTROL <suporte@telecontrol.com.br>";
		$body_txt	= email_acesso($oid_posto_fabrica,$codigo_posto, $senha, $fabrica, $fabrica_nome, $idioma);
		$debug = true;
		if(!mail($email, utf8_encode($assunto), utf8_encode($body_txt), "$body_top\nFrom: $email_from\n")) {
			$msg_erro = ttext($pavalida, "err_email_ko");
			if ($debug) echo "$email, $assunto,$email_from<br>".nl2br($body_top);
		}
#	Cópia para o suporte caso o PA não receba o e-mail. SEMPRE português!
		$body_txt = email_acesso($oid_posto_fabrica,$codigo_posto, $senha, $fabrica, $fabrica_nome, "pt-br", true);

		if(!mail($email_from, utf8_encode($assunto), utf8_encode($body_txt), "$body_top")){
			$msg_erro = ttext($pavalida, "err_email_ko");
			if ($debug) echo "$email, $assunto,$email_from<br>".nl2br($body_top).$msg_erro;
		}
	}
	if (strlen($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		$key = md5($posto);
		header ("Location: $PHP_SELF?posto=$posto&fabrica=$fabrica&key=$key");
		exit;
	} else {
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
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
				if(mail($email_to, utf8_encode($assunto), utf8_encode($body), $email_from."\n $body_top")){
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

// $body_top = "--Message-Boundary\n";
// $body_top .= "Content-type: text/html; charset=UTF-8\n";
// $body_top .= "Content-transfer-encoding: 7BIT\n";
// $body_top .= "Content-description: Mail message body\n\n";
header("Content-Type: text/html;charset=UTF-8");
//include ('../mlg/mlg_funciones_utf8.php');

$html_titulo = ttext($pavalida, "Primeiro_Acesso");
include('topo_wordpress.php');
?>
<!--<link rel="stylesheet" href="css/login_unico_envio_email.css" type="text/css" media="screen" />-->
<div class="titulo_tela">
	<br><h1><a href="javascript:void(0)" style="cursor:point;">Primeiro Acesso - Cadastro Senha</a></h1>
</div>
<div class="div_top_principal">
	<table width="948" style="text-align: right;">
		<tr>
			<td>
				*Campos obrigat&oacute;rios.
			</td>
		<tr>
	</table>
</div>

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
	}else{
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

if ($pais != 'BR') $cook_idioma='es';
?>

<script language="JavaScript">
/*  Começa o jQuery */
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
					}else{
						email.html(resposta);
						email.addClass('azul').css('font-size','12px');
					}
				}

		});
	});
}); /* FIM jQuery   */
</script>
<table width="948" class="barra_topo">
	<tr>
		<td>
			<div id="mensagem_envio" class='erro_campos_obrigatorios'>&nbsp;<?php echo $msg_erro;?></div>
		</td>
	</tr>
</table>
<table style="border:solid 1px #CCCCCC;width: 948px;height:300px;" class="caixa_conteudo">
	<tr>
		<td style="padding: 1ex 2em">
			<div id='conteiner'>
			  <div id='conteudo'>

<?php

$key = '';
$key     = $_GET["key"];
if(strlen($key)>0){
	$fabrica = $_GET["fabrica"];
	$posto   = $_GET["posto"]  ;
	$posto_key = md5($posto);
    $url_acesso = "http://www.telecontrol.com.br/";
	$url_acesso.= ($fabrica==20) ? "assist/bosch.php" : "";

	if ($key == $posto_key){
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
			WHERE tbl_posto.posto =$posto
			AND tbl_posto_fabrica.fabrica = $fabrica";

		$res = @pg_query ($con,$sql);

		if (pg_num_rows ($res) == 1) {
			$a_row = pg_fetch_assoc($res, 0);   //		Pega o registro completo num array com índice=nome campo
			foreach ($a_row as $campo => $dado) {  //	Cria cada variável com o nome do campo
				$$campo = utf8_encode($dado);
			}

			if ($fabrica == 50) {  ?>
				<br />
				<div>
				<h3>Parabéns, você fez o primeiro acesso!</h3>
				&nbsp;&nbsp;&nbsp;&nbsp;Agora você pode acessar o sistema de <font color='3366ff'>Pós-Venda</font>, um moderno software de gerenciamento de assistências técnicas ON-LINE.<br>
				&nbsp;&nbsp;&nbsp;&nbsp;Foi enviado um email para:
				<span class='vermelho italic'><?=$posto_email?></span> com seus dados cadastrais
				para que você possa se logar ao sistema.<br>
				&nbsp;&nbsp;&nbsp;&nbsp;Junto ao email segue um link de confirmação, você deve acessar
				o endereço correspondente para que seu acesso ao sistema seja liberado.<br>
				Caso não receba o email verifique no seu webmail a pasta de <b>spam</p>,
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
				</div>

<?				if ($atualizado == "1"){ ?>
					<div id='primeiro' style='padding:10px;border:#e5ecf9 3px solid'>
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
<?				}else{	?>
					<form name="frm_cadastro_complemento" method="post" action='<? echo $PHP_SELF ?>?key=<?=$key?>'>
					<input type="hidden" name="alteracao_dados" value="<?=$posto?>">
					<input type="hidden" name="fabrica" value="<?=$fabrica?>">
					<input type="hidden" name="posto"   value="<?=$posto?>">
					<input type="hidden" name="pais" value="<?=$pais?>">
					<fieldset class='colunas'>
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
					<input name='btn_acao' value='<?=ttext($pavalida, "Confirmar_Dados")?>' type='submit'>
				</form>
<?				}   ?>
				<br>Página de acesso:&nbsp;<a href='<?=$url_acesso?>' style='text-decoration:underline!important'>
					Clique aqui</a> para ir a página inicial do Assist Telecontrol
				</div>
<?			} else {
				echo "<div>\n";
				if($pais!='BR'){    ?>
					<h3>Felicidades, ha completado con éxito el Primer Acceso</h3>
					<p>Ya puede usted acceder al sistema de <b class='azul'>Pós-Venda</b>,
					un moderno software de administración de servicios técnicos ON-LINE.</p>
					<p>Se le ha enviado un mensaje a la dirección: <span class='vermelho italic'><?=$posto_email?></span>
					con sus datos para poder acceder al Sistema Telecontrol.</p>
					<p>En el mensaje encontrará un enlace para confirmar su acceso, usuario y clave.
						Deberá seguir ese enlace para que su acceso sea liberado.
						<br />
						Si no recibe este mensaje, compruebe que no haya sido filtrado como <b>spam</p>
						por su programa o su proveedor (a través de web-mail, normalmente).<br />
						Si por cualquier motivo no recibe el mensaje en un periodo máximo de 30 minutos,
						entre en contacto con nuestro <a href='mailto:suporte@telecontrol.com.br'
						title="Enviar e-mail para Suporte Telecontrol">Soporte Técnico</a> para que su acceso
						sea liberado.
					</p>
					<br>
					<div>
					<fieldset>
						<h3>Datos de Registro y de Acesso</h3>
					    <h4>Datos de su Registro:</h4>
						<ul style='list-style:none'>
							<li><label>Fabricante:</label><?=$fabrica_nome?></li>
							<li><label>Razón Social:</label><?=$posto_nome?></li>
							<li><label>ID Fiscal:</label><?$posto_cnpj?></li>
						</ul>
						<h4>Datos de acceso:</h4>
						<ul style='list-style:none'>
							<li><label>Usuario:	</label>
								<i class='azul'
								   title='Sus datos de acceso se enviaron por correo-e'>Confirmar correo-e</i></li>
							<li><label>Clave:	</label>
								<i class='azul'
								   title='Sus datos de acceso se enviaron por correo-e'>Confirmar correo-e</i></li>
							<li><label title='Este é o endereço de acesso ao Sistema de Pós-Venda da Telecontrol'>Endereço:</label><i class='azul'>
<?					if($fabrica == 20) echo "www.bosch.com.br/assist";
					else               echo "www.telecontrol.com.br";?></i></li>
							</ul>
							<h4>Página de acxeso:</h4>
							<p>
							    <a href='<?=$url_acesso?>' style='text-decoration:underline!important'>Clique aqui</a> para ir a la página inicial del Sistema.
							</p>
						</fieldset>
					</div>
<?				}else{
?>					<h3>Parabéns, você fez o primeiro acesso!</h3>
					<p>Agora você pode acessar o sistema de <b class='azul'>Pós-Venda</b>,
					um moderno software de gerenciamento de assistências técnicas ON-LINE.</p>
					<p>Foi enviado um email para: <span class='vermelho italic'><?=$posto_email?></span> com seus
					dados cadastrais para que você possa se logar ao sistema.
					Junto ao email segue um <i>link</i> de confirmação, você deve acessar
					o endereço correspondente para que seu acesso ao sistema seja liberado.<br>
					Caso não receba o email verifique no seu webmail a pasta de <b>spam,
					 <b>lixo eletrônico</b> ou similar.<br />
					 Se não recebeu o e-mail em no máximo 30 minutos, entre em contato com o suporte
					(<a href="mailto:suporte@telecontrol.com.br" title="Enviar e-mail para Suporte Telecontrol">
					<i>suporte@telecontrol.com.br</i></a>) para que seu acesso seja liberado.<br><br>
					</div>
					<div>
					<fieldset>
						<h3>Dados Cadastrais e de Acesso</h3>
					    <h4>Dados Cadastrais</h4>
						<ul style='list-style:none'>
							<li><label>Fábrica:</label><?=$fabrica_nome?></li>
							<li><label>Posto:</label><?=$posto_nome?></li>
							<li><label>CNPJ:</label><?=$posto_cnpj?></li>
						</ul>
						<h4>Dados de Acesso:</h4>
						<ul style='list-style:none'>
							<li><label>Código Posto:</label>
								<i class='azul' title='Os dados de acesso foram enviados por e-mail'>Confirmar Email</i></li>
							<li><label>Senha:</label>
								<i class='azul' title='Os dados de acesso foram enviados por e-mail'>Confirmar Email</i></li>
							<li><label title='Este é o endereço de acesso ao Sistema de Pós-Venda da Telecontrol'>Endereço:</label><i class='azul'>
<?						if($fabrica == 20) echo "www.bosch.com.br/assist";
						else               echo "www.telecontrol.com.br";?></i></li>
							</ul>
							<h4>Página de acesso:</h4></h4>
							<p>
							    <a href='<?=$url_acesso?>' style='text-decoration:underline!important'>Clique aqui</a> para ir à página inicial do Sistema
							</p>
						</fieldset>
					</div>
<?				}
			}
		}
	}
}else{
	if(strlen($posto) == 0){
	?>
		<h3><?=ttext($pavalida, "err_sem_acesso")?></h3>
		<p><?=ttext($pavalida, "err_sem_acesso_msg")?></p>
	</div>
<?      //include "inc_footer.php";
		exit;
	}
	$sql = "SELECT tbl_posto_fabrica.fabrica,tbl_fabrica.nome,contato_email
			  FROM tbl_fabrica
			  JOIN tbl_posto_fabrica USING (fabrica)
			 WHERE posto = $posto
			   AND credenciamento = 'CREDENCIADO'
			   AND primeiro_acesso IS NULL
			   AND ativo_fabrica IS TRUE
			   AND fabrica != 10
		  ORDER BY nome";
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
		<!--<h3><?=ttext($pavalida, "cadastra_senha")?></h3>-->
		<p>&nbsp;NOME:&nbsp;<b><?=$nome;?></b></p><br />
		<h4><b><label style="color:#535252;font-size:13px;">&nbsp;<?=ttext($pavalida, "instrucoes")?>:</label></b></h4>

			<div style="margin-left:20px;" class="texto_informacao">
				<p>
				<li><?=ttext($pavalida, "instrucoes_1")?></li>
				<li class='vermelho negrito'><?=ttext($pavalida, "instrucoes_2")?></li>
				<li><?=ttext($pavalida, "instrucoes_3")?></li>
				<li><?=ttext($pavalida, "instrucoes_4")?></li>
				<li><?=ttext($pavalida, "instrucoes_5")?></li>
				<li><?=ttext($pavalida, "instrucoes_6")?></li>
				</p>
			</div>

		<p>&nbsp;</p>
		<form name="frm_cadastro_senha" id='pa_senha' method="post" >
		<input type='hidden' name='cnpj' value='<?php echo $cnpj;?>'>
		<input type='hidden' name='btn_acao' value='Gravar'>
			<input type="hidden" name="pais" value="<?=$pais?>">
			<legend><?=ttext($pavalida, "cadastro_de_usuarios")?>:</legend>
			<table style="table-layout:fixed;width:850px;">
				<tr>
					<td width="150">
						<!--<h3><?=ttext($pavalida, "instrucoes_1")?></h3>-->
						<label class="fabrica"><?=ttext($pavalida, "fabrica")?>&nbsp;*&nbsp;</label>
						<input type='hidden' name='posto' id='posto' value='<?=$posto?>'>
					</td>
					<td width="700">
						<select name='fabrica' id='fabrica'>
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
					</td>
				</tr>

				<tr>
					<td>
						<p>
						<label><?=ttext($pavalida, "E-mail")?>&nbsp;&nbsp;</label>
						</p>
					</td>
					<td>
						<p><span id='email_fabrica'><?=(count($_POST)&&$fabrica)?$email[$fabrica]:ttext($pavalida,'instrucoes_1')?></span></p>
					</td>
				</tr>
				<tr>
					<td>
						&nbsp;
					</td>
					<td>
						<span style="color:#A9A8A8;font-size:11px;"><?=ttext($pavalida, "info_email_1")?><br />
						<?=ttext($pavalida, "info_email_2")?></span>
						<p>&nbsp;</p>
					</td>
				</tr>

				<tr>
					<td>
						<label class="senha"><?=ttext($pavalida, "digite_senha")?>&nbsp;*&nbsp;</label>
					</td>
					<td>
						<input name='senha' id='senha' size='10' maxlength='10' value='' type='password'>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<span style="color:#A9A8A8;font-size:11px;"><?=ttext($pavalida, "instrucoes_3")?></span>
					</td>
				</tr>

				<tr>
					<td>
						<label class="confirmar_senha"><?=ttext($pavalida, "confirme_senha")?>&nbsp;*&nbsp;</label>
					</td>
					<td>
						<input name='confirmar_senha' id="confirmar_senha" size='10' maxlength='10' value=''  type='password' />
					</td>
				</tr>

				<tr>
					<td>
						<p></p>
						<?php list($cap,$int) = explode(" / ",ttext($pavalida, "cap_int"));?>
						<label><?="$cap / $int"?>&nbsp;&nbsp;</label>
					</td>
					<td>
						<p></p>
						<select name='capital_interior' size='1' class='Caixa'>
							<option value='CAPITAL' <? if ($capital_interior == 'CAPITAL')   echo ' selected' ?>><?=ucfirst(strtolower($cap))?></option>
							<option value='INTERIOR' <? if ($capital_interior == 'INTERIOR') echo ' selected' ?>><?=ucfirst(strtolower($int))?></option>
						</select>
					</td>
				</tr>

			    <tr>
					<td><p></p><p></p>&nbsp;</td>
					<td>
						<p></p><p></p>
						<button type="button" name="btn_acao" value='Gravar' class="input_gravar"
							 onclick="verifica_primeiro_acesso_cadastro('');">&nbsp;&nbsp;<?=ttext($pavalida, "Gravar");?>&nbsp;&nbsp;</button>
					</td>
				</tr>

		</table>
	</form>
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
</div>
	</td>
</td>
</table>
<div class="blank_footer">&nbsp;</div>

