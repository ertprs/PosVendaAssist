<?php

// include('helpdesk/mlg_funciones.php');

// Variáveis para determinar o servidor de login
$http_server_name = $_SERVER['SERVER_NAME'];
$http_referer	  = $_SERVER['HTTP_REFERER'];
$origem_xhr       = $_SERVER['HTTP_ORIGIN'];

$http_login_wp    = "http://$http_server_name" . dirname($_SERVER['PHP_SELF']) . "/index.php";

$allowed_servers  = array(
	'http://www.telecontrol.com.br',
	'http://brasil.telecontrol.com.br',
	'http://urano.telecontrol.com.br',
	'http://telecontrol.no-ip.org',
	'http://telecontrol-urano.no-ip.org',
	'http://192.168.0.199',
	'http://ww2.telecontrol.com.br',
	'https://ww2.telecontrol.com.br'
);

/*
p_echo("Link de retorno: $http_referer");
p_echo("Link login no-CORS: $http_login_wp");
pre_echo($_SERVER,	"Array _SERVER");
pre_echo($_GET,		"Array _GET");
pre_echo($_POST,	"Array _POST");
pre_echo($_COOKIE,	"Cookies...");
p_echo("Parâmetros GET: " . count($_GET));
p_echo("Parâmetros POST: " . count($_POST));
*/

if(strpos($http_referer,"admin/posto_cadastro") > 0 or strpos($http_referer,"admin/login_posto") > 0){
//if (is_int($_COOKIE['cook_admin'])) { // Não altera o retorno uma vez que o admin está logado...
	$http_referer = "";
 } else{
	if(isset($_COOKIE['cook_retorno_url']))
		setcookie('cook_retorno_url', null, 0);
 }

// Adiciona o 'www' se o usuário 'esqueceu' de colocar...
if (!isset($_SERVER['HTTP_REFERER']) and $http_server_name == 'telecontrol.com.br')
	header('Location: http://posvenda.telecontrol.com.br/assist/index.php');

/* Se não houver informações para processar, mostrar formulário de login */
if (count(array_filter($_GET))==1 and count(array_filter($_POST))==2) {
	//p_echo("Sem parâmetros para processar...");
	//die();
	if ($_POST['btnErro']=='login') {
		header('Location: ' . $http_login_wp);
		exit;
	}
	if ($http_referer	  == '' or in_array($http_server_name, $allowed_servers)) {
		include 'frm_index.html'; //Arquivo que contémin o mesmo formulário do index.html, mas com os textos traduzidos
	} else {
		if(isset($_COOKIE['cook_retorno_url']))
			setcookie('cook_retorno_url', null, 0);
		header('Location: ' . $http_referer);
		exit;
	}
}

/*******************************************************************
* Esta variável faz com que apareça o conteúdo do arquivo          *
* assist/www/tc_comunicado.php sobre a tela do posto ou            *
* do admin.                                                        *
* Serve para mostrar comunicado para todos os usuários do sistema. *
* Também cria uma 'cookie' de sessão 'cook_comunicado_telecontrol' *
* com o valor 'naoleu'											   *
********************************************************************/
$mlg_hoje = strtotime('now');
if ($mlg_hoje > strtotime('2011-11-22 10:01:00') and $mlg_hoje < strtotime('2011-11-22 17:00:00'))
$mostra_comunicado_tc = true;

$ip_redir = $_GET['ip_redir'];
/*if(strlen($ip_redir) == 0){
header("Location: http://201.77.210.68/assist/index.php?ip_redir=sim");
}*/

/*echo "<CENTER><h1>ATENÇÃO</h1>";
echo "<h3>O sistema passará por manutenção técnica</h3";
echo "<h3>Dentro de algumas horas será restabelecido</h3";
echo "<h3> </h3";
echo "<p><h3>Agradecemos a compreensão!</h3>";
exit;
*/
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if (strlen($_POST["btnAcao"]) > 0) {$btnAcao = trim($_POST["btnAcao"]);}
if (strlen($_GET["btnAcao"]) > 0) {$btnAcao = trim($_GET["btnAcao"]);}

if (strlen($_POST["id"]) > 0)  {$id = trim($_POST["id"]);}
if (strlen($_GET["id"]) > 0)  {$id = trim($_GET["id"]);}

if (strlen($_POST["id2"]) > 0) {$id2 = trim($_POST["id2"]);}
if (strlen($_GET["id2"]) > 0) {$id2 = trim($_GET["id2"]);}

if (strlen($_POST["key1"]) > 0){$key1 = trim($_POST["key1"]);}
if (strlen($_GET["key1"]) > 0){$key1 = trim($_GET["key1"]);}

if (strlen($_POST["key2"]) > 0){$key2 = trim($_POST["key2"]);}
if (strlen($_GET["key2"]) > 0){$key2 = trim($_GET["key2"]);}


/*******************************************************************************
 * Estes headers são para habilitar o Cross Origin Resource Sharing (CORS), ou *
 * o acesso desde outros domínios via XHR (AJAX)                               *
 *******************************************************************************/
if ($_SERVER['REQUEST_METHOD']== 'POST' and $origem_xhr != '' and in_array($origem_xhr, $allowed_servers)) {
	header("Access-Control-Allow-Methods: GET, POST");
	header("Access-Control-Allow-Credentials: true");
	header("Access-Control-Allow-Headers: Content-Type, *");
	header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

if($key1 == md5($id) AND $key2 == md5($id2)){
	if(strlen($id)>0 AND strlen($id2)>0 AND strlen($key1)>0 AND strlen($key2)>0 ){
	/*
	$sql = "SELECT tbl_admin.admin,hd_chamado,login,senha
	FROM tbl_hd_chamado
	JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
	WHERE hd_chamado     = $id
	AND  tbl_admin.admin = $id2
	AND(  status          = 'Resolvido' OR exigir_resposta IS TRUE)
	AND  resolvido IS NULL";
	*/

		/*HD - 15025  Acertando a rotina de exigir resposta e chamado resolvido Raphael Giovanini*/
        $sql = "  SELECT tbl_admin.admin, hd_chamado, login, senha, tbl_admin.fabrica
					FROM tbl_hd_chamado
					JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
				   WHERE hd_chamado      = $id
					 AND tbl_admin.admin = $id2
					 AND (status         = 'Resolvido'
					  OR exigir_resposta IS TRUE)
					 AND resolvido       IS NULL";
		#if($ip=="201.71.54.144") echo $sql;
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 1) {
			$hd_chamado    = pg_fetch_result($res, 0, 'hd_chamado');
			$admin         = pg_fetch_result($res, 0, 'admin');
			$hd_login      = pg_fetch_result($res, 0, 'login');
			$hd_senha      = pg_fetch_result($res, 0, 'senha');
			$admin_fabrica = pg_fetch_result($res, 0, 'fabrica');
			$hd = "OK";
		}
	}
}


if (trim($_POST["btnAcao"]) == "OK") {

	$cnpj = trim($_POST["cnpj"]);

	if (strlen($_POST["cnpj"]) > 0) {
		$aux_cnpj = trim($_POST["cnpj"]);
		$aux_cnpj = str_replace(".","",$aux_cnpj);
		$aux_cnpj = str_replace("/","",$aux_cnpj);
		$aux_cnpj = str_replace("-","",$aux_cnpj);
		$aux_cnpj = str_replace(" ","",$aux_cnpj);
		header("Location: cadastra_senha.php?cnpj=$aux_cnpj");
		exit;
	}else{
		$msg_erro = "Digite seu CNPJ.";
	}
}

//print_r($_POST); exit;
$botao = strtolower(trim($_POST["btnAcao"]));
$ajax  = $_REQUEST['ajax'];
$acao  = $_REQUEST['acao'];
$redir = $_REQUEST['redir'];
//if ($acao == 'validar') {
if ($botao == 'entrar' or $botao == 'enviar') {
	$login = trim($_POST["login"]);
	$senha = trim($_POST["senha"]);
// var_dump($login);exit;

	//HD 415691 - Não permitir acesso sem senha (quando senha == '*')
	if ($senha == '*') {
		header('Content-Type: text/html; charset=utf-8');
		exit(utf8_encode("ko|<b>Senha inválida!</b><br />Se ainda não fez o primeiro acesso, acesse a tela <a href='http://posvenda.telecontrol.com.br/assist/externos/primeiro_acesso.php'>Primeiro Acesso</a> para escolher sua senha e liberar o acesso."));
	}
	$sql = "SELECT fabrica
		      FROM tbl_fabrica
		     WHERE lower(nome) = lower('$login') and ativo_fabrica;";
	$res = pg_query($con, $sql);

	$tempsenha = explode("|",$senha);
	if ((pg_numrows ($res) == 1) and (count($tempsenha)==2) and 1==2) {

		$senha = trim($_POST["senha"]);

		$tempsenha = explode("|",$senha);

		if (count($tempsenha)==2){

			$temp_login = $tempsenha[0];
			$temp_senha = $tempsenha[1];
			#------------------- Pesquisa acesso ADMIN ------------------
			$sql = "SELECT  tbl_admin.admin,
						tbl_admin.privilegios
					FROM tbl_admin
					WHERE  LOWER (tbl_admin.login) = LOWER ('$temp_login')
					AND    LOWER (tbl_admin.senha) = LOWER ('$temp_senha')
					AND    ativo IS TRUE
					AND fabrica=10";
			$res = pg_exec ($con,$sql);

			if (pg_numrows ($res) == 1) {
				$sql = "SELECT nome,fabrica
						FROM tbl_fabrica
						WHERE LOWER (nome) = LOWER ('$login');";
				$res = pg_exec ($con,$sql);
				if (pg_numrows ($res) > 0) {
					$xlogin= $temp_login;
					$senha = $temp_senha;
					$fabrica_master = pg_result ($res,0,fabrica);
					$login_master= pg_result ($res,0,nome);
				}
				//echo "passou aqui xlog:$xlogin - senh:$senha - fab_m: $fabrica_master - log_master: $login_master";
				//exit;
			}else{
				$msg="Login ou senha invalidos";
			}
		}

		setcookie ("cook_posto_fabrica");
		setcookie ("cook_posto");
		setcookie ("cook_fabrica");
		setcookie ("cook_login_posto");
		setcookie ("cook_login_nome");
		setcookie ("cook_login_cnpj");
		setcookie ("cook_login_fabrica");
		setcookie ("cook_login_fabrica_nome");
		setcookie ("cook_login_pede_peca_garantia");
		setcookie ("cook_login_tipo_posto");
		setcookie ("cook_login_e_distribuidor");
		setcookie ("cook_login_distribuidor");
		setcookie ("cook_pedido_via_distribuidor");

		setcookie ("cook_empresa","");
		setcookie ("cook_loja","");
		setcookie ("cook_admin","");
		setcookie ("cook_empregado","");
		setcookie ("cook_pessoa","");

		if (strlen($login) == 0) {
			$msg = "Informe seu CNPJ ou Login !!!";
		}else{
			if (strlen($senha) == 0) {
				$msg = "Informe sua senha !!!";
			}
		}
		
		if (strlen($msg) == 0) {
			#------------------- Pesquisa acesso ADMIN ------------------
			$sql = "SELECT  tbl_admin.admin   ,
						tbl_admin.login       ,
						tbl_admin.senha       ,
						tbl_admin.privilegios ,
						tbl_admin.pais
					FROM tbl_admin
					JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica AND tbl_fabrica.ativo_fabrica is true
					WHERE  lower (tbl_admin.login) = lower ('$temp_login')
					AND    lower (tbl_admin.senha) = lower ('$temp_senha')
					AND    ativo IS TRUE";

			$res = pg_exec ($con,$sql);

			if (pg_numrows ($res) == 1) {

				$pais		= pg_result($res,0,'pais') ;
				$admin		= pg_result($res,0,'admin');
				$ip    = $_SERVER['REMOTE_ADDR'] ;
				$sql2 = "UPDATE tbl_admin SET
							ultimo_ip = '$ip' ,
							ultimo_acesso = CURRENT_TIMESTAMP
						 WHERE admin = $admin";

				$res2 = pg_exec($con,$sql2);


				if ($pais<>'BR') setcookie ("cook_admin_es",pg_result($res, 0, admin));
				else             setcookie ("cook_admin",   pg_result($res, 0, admin));
				

				setcookie ("cook_posto_fabrica");
    			setcookie ("cook_posto");

				setcookie ("cook_master",$login_master);
				setcookie ("cook_fabrica",$fabrica_master);
				setcookie ("cook_admin",$admin);
				if (strlen($http_referer))
					setcookie('cook_retorno_url', $http_referer);

				$privilegios = pg_result ($res,0,privilegios);
				$acesso = explode(",",$privilegios);

	//--=== ADMINS AMRICA LATINA ========================RAPHAEL===============--\\
				if($pais<>'BR'){
					$pagina = "admin_es/menu_gerencia.php";
					if ($mostra_comunicado_tc) setcookie('cook_comunicado_telecontrol', 'naoleu');
				    if ($ajax=="sim") {
						exit("ok|$pagina");
				    }else{
						header ("Location: $pagina");
						exit;
				    } 
				}
	//--========================================================================--\\

				for($i=0; $i < count($acesso); $i++){
					if(strlen($acesso[$i]) > 0){
						if ($acesso[$i] == "gerencia"){
							$pagina = "admin/menu_gerencia.php";
						}elseif ($acesso[$i] == "call_center"){
							$pagina = "admin/menu_callcenter.php";
						}elseif ($acesso[$i] == "cadastros"){
							$pagina = "admin/menu_cadastro.php";
						}elseif ($acesso[$i] == "info_tecnica"){
							$pagina = "admin/menu_tecnica.php";
						}elseif ($acesso[$i] == "financeiro"){
							$pagina = "admin/menu_financeiro.php";
						}elseif ($acesso[$i] == "auditoria"){
							$pagina = "admin/menu_auditoria.php";
						}elseif ($acesso[$i] == "*"){
							$pagina = "admin/menu_cadastro.php";
						}
						if ($mostra_comunicado_tc) setcookie('cook_comunicado_telecontrol', 'naoleu');
						if ($ajax=="sim"){
							echo "ok|$pagina";
							exit;
						}else{
							header ("Location: $pagina");
							exit;
						}
					}
				}
			
			}else{
				$msg = "<!--OFFLINE//-I-->Login ou senha inv&aacute;lidos !!!<!--OFFLINE//-F-->";
			}
			if (strlen ($msg) == 0) {
				$msg = "<!--OFFLINE//-I-->Login ou senha inv&aacute;lidos !!!<!--OFFLINE//-F-->";
			}
			setcookie ("cook_posto_fabrica");
			setcookie ("cook_admin");
		}else{
			$msg = "<!--OFFLINE//-I-->Login ou senha inv&aacute;lidos!!!<!--OFFLINE//-F-->";
		}
	}else{

		$tempemail = explode("@",$login);

		//login_unico
		if(count($tempemail)==2){

			$login = trim($_POST["login"]);
			$senha = trim($_POST["senha"]);
			$sql = " SELECT login_unico,posto
				FROM tbl_login_unico
				WHERE email = '$login'
				AND   senha = 'md5' || md5('$senha')
				AND   ativo IS TRUE
				AND   email_autenticado IS NOT NULL";
#echo $sql;
#exit;

			$res = pg_exec ($con,$sql);

			if (pg_numrows ($res) == 1 ) {

				$imp_sql=$sql;
				$posto       = pg_result ($res,0,posto);
				$login_unico = pg_result ($res,0,login_unico);

				setcookie ("cook_posto_fabrica");
				setcookie ("cook_posto");
				setcookie ("cook_fabrica");
				setcookie ("cook_login_posto");
				setcookie ("cook_login_nome");
				setcookie ("cook_login_cnpj");
				setcookie ("cook_login_fabrica");
				setcookie ("cook_login_fabrica_nome");
				setcookie ("cook_login_pede_peca_garantia");
				setcookie ("cook_login_tipo_posto");
				setcookie ("cook_login_e_distribuidor");
				setcookie ("cook_login_distribuidor");
				setcookie ("cook_pedido_via_distribuidor");

				setcookie ("cook_login_unico",$login_unico);
				setcookie ("cook_posto",$posto);
				if (strlen($http_referer))
					setcookie('cook_retorno_url', $http_referer);

				$pagina = "login_unico.php";

				if ($mostra_comunicado_tc) setcookie('cook_comunicado_telecontrol', 'naoleu');
				if ($ajax =="sim") {
					echo "ok|$pagina";
					exit;
				}else{
				    header ("Location: $pagina");
				    exit;
				}

			}else{
				$sql = " SELECT login_unico,email
					FROM tbl_login_unico
					WHERE email = '$login'
					AND   senha = 'md5' || md5('$senha')
					AND   ativo IS TRUE
					AND   email_autenticado IS NULL";
		
				$res = pg_exec ($con,$sql);

				if (pg_numrows ($res) == 1 ) {
					$login_unico  = pg_result ($res,0,0);
					$email        = pg_result ($res,0,1);

					$chave1=md5($login_unico);
					$email_origem  = "helpdesk@telecontrol.com.br";
					$email_destino = $email;
					$assunto       = "Assist - Login Único";
					$corpo.="<P align=left><STRONG>Este e-mail é gerado automaticamente.<br>**** NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>

							<P align=justify>Você está recebendo novamente um email para validar sua conta. Para <FONT
							color=#006600><STRONG>validar</STRONG></FONT> seu email,utilize o link abaixo:
							<br><a href='http://www.telecontrol.com.br/login_unico.php?id=$login_unico&key1=$chave1'><u><b>Clique aqui para validar seu email</b></u></a>.</P>
							<br>Caso esteja com problemas copie e cole o link abaixo em seu navegador:<br>http://www.telecontrol.com.br/login_unico.php?id=$login_unico&key1=$chave1<br>
							<P align=justify>Suporte Telecontrol Networking.<BR>helpdesk@telecontrol.com.br
							</P>";

					$body_top = "--Message-Boundary\n";
					$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
					$body_top .= "Content-transfer-encoding: 7BIT\n";
					$body_top .= "Content-description: Mail message body\n\n";

					if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ) {
						$msg_erro = "Seu email não está autenticado, foi enviado um email para confirmação em: ".$email_destino;
					}
					
					if ($mostra_comunicado_tc) setcookie('cook_comunicado_telecontrol', 'naoleu');
					if ($ajax=="sim") {
						header('Content-Type: text/html; charset=utf-8');
					    exit(utf8_encode("1|$msg_erro"));
					}else{
						header('Content-Type: text/html; charset=utf-8');
						setcookie('errLogin', $msg_erro);
						if ($_POST['btnErro'] == 'login') {
							header('Location: ' . $http_login_wp . "?errLogin=$msg_erro");
						} else {
							header ("Location: ". $http_referer . "?errLogin=$msg_erro");
						}
					    exit;
					}

				}else{
				    $msg_erro = "Login ou senha invalidos";
				}
			}
		}


		if(count($tempemail)==2){

			$login = trim($_POST["login"]);
			$senha = trim($_POST["senha"]);

			$sql = "SELECT  fn_erp_autentica('$login','$senha');";
			$res = @pg_exec ($con,$sql);
			if(@pg_numrows($res)>0){
				$pagina = "time/index2.php?ajax=sim&acao=validar&redir=sim&login=$login&senha=$senha";
				if ($ajax=='sim') {
					exit("time|$pagina");
				} else {
					header('Location: /' . $pagina);
					exit;
				}
			}
			$sql = " SELECT pessoa,
					empregado,
					loja,
					tbl_empregado.empresa
				FROM tbl_pessoa
				JOIN tbl_empregado USING(pessoa)
				WHERE tbl_pessoa.email = '$login'
				AND tbl_empregado.senha = '$senha'
				AND tbl_empregado.ativo IS TRUE
				";
//echo "sql: $sql";
			$res = pg_exec ($con,$sql);
//exit;
			if (pg_numrows ($res) == 1) {
				$imp_sql=$sql;
				$pessoa     = pg_result ($res,0,pessoa);
				$empregado  = pg_result ($res,0,empregado);
				$empresa    = pg_result ($res,0,empresa);
				$loja       = pg_result ($res,0,loja);

				setcookie ("cook_empresa",$empresa);
				setcookie ("cook_loja",$loja);
				setcookie ("cook_admin",$empregado);
				setcookie ("cook_empregado",$empregado);
				setcookie ("cook_pessoa",$pessoa);
/*echo "passou aqui- empregado: $empregado";
print_r($_COOKIE);
PROVISORIO, VERIFICAR COMO DEVER SER FEITO, POIS, PERDE AS COOKIES QDO MUDA DE DIRETORIO. COMO AINDA ESTAO UTILIZANDO O SISTEMA ANTIGO, PERMANECE O ACESSO AO SISTEMA ANTIGO, DEPOIS RETIRAR E MANDAR TODOS PARA O PROGRAMA NOVO
*/				if(($empresa<>10 and $empresa <> 27 and $empresa<>49 ) or $login=="takashi@telecontrol.com.br"){
					$pagina = "time/index2.php?ajax=sim&acao=validar&redir=sim&login=$login&senha=$senha";
					if ($ajax=='sim') {
						exit("time|$pagina");
					} else {
						header('Location: /' . $pagina);
						exit;
					}
				}else{
					$pagina = "erp/index.php";
					if ($ajax=='sim') {
						exit("ok|$pagina");
					} else {
						header('Location: ' . $pagina);
						exit;
					}
				}
		/*	}else{
/*Para sistema de revendas // HD 345003 - Retirar o login do Sistema de Revendas 
				$msg_erro ="Login ou senha inválidos.";
				$login = trim($_POST["login"]);
				$senha = trim($_POST["senha"]);
				$sql = " SELECT revenda,fabrica
					FROM tbl_revenda
					JOIN tbl_revenda_fabrica USING(revenda)
					WHERE tbl_revenda_fabrica.email = '$login'
					AND   tbl_revenda_fabrica.senha = '$senha'";

				$res = pg_exec ($con,$sql);

				if (pg_numrows ($res) == 1) {
					$imp_sql=$sql;
					$fabrica     = pg_result ($res,0,fabrica);
					$revenda     = pg_result ($res,0,revenda);

					setcookie ("cook_fabrica",$fabrica);
					setcookie ("cook_revenda",$revenda);
					$pagina = "revend/index.php";
					if($redir=='sim'){
						header("Location: $pagina");
					}else{
						echo "ok|$pagina";
					}
					exit;
				}else{
					$msg_erro ="Login ou senha inválidos.";
				}*/
			}
		}else{ 
			$login = trim($_POST["login"]);
			$senha = trim($_POST["senha"]);
			if($hd=='OK'){
				$login = $hd_login   ;
				$senha = $hd_senha   ;
			}
			$tempsenha = explode("|",$senha);
			if (count($tempsenha)==2 and 1==2){
				$temp_login = $tempsenha[0];
				$temp_senha = $tempsenha[1];
				//IGOR HD 2064  quando no login colocarmos ex: leandro|tectoy direcionar para a Tectoy e no para a Dynacom.
				$templogin = explode("|",$login); //verificar quando o login for diferente para 2 fabricas
				if (count($templogin)==2 and 1==2){

					$temp_login_login   = $templogin[0];
					$temp_login_fabrica	= $templogin[1];

					$sql = " SELECT fabrica
							 FROM tbl_fabrica
							 WHERE lower(nome )= lower('$temp_login_fabrica') and ativo_fabrica;";

					$res = pg_exec ($con,$sql);

					if (pg_numrows ($res) == 1) {
						$fabrica = pg_result ($res,0,fabrica);

						#------------------- Pesquisa acesso ADMIN ------------------
						$sql = "SELECT  tbl_admin.admin
								FROM tbl_admin
								WHERE  lower (tbl_admin.login) = lower ('$temp_login')
								AND    lower (tbl_admin.senha) = lower ('$temp_senha')
								AND    ativo IS TRUE
								AND fabrica=10";
						$res = pg_exec ($con,$sql);
						if (pg_numrows ($res) == 1) {
							$sql = "SELECT  tbl_admin.login,
										tbl_admin.senha
									FROM tbl_admin
									WHERE  lower (tbl_admin.login) = lower ('$temp_login_login')
									AND fabrica = $fabrica ORDER BY privilegios";
							$res = pg_exec ($con,$sql);
							if (pg_numrows ($res) > 0) {
								$login = pg_result ($res,0,login);
								$senha = pg_result ($res,0,senha);
							}
						}
					}
				}else{
				

					#------------------- Pesquisa acesso ADMIN ------------------
					$sql = "SELECT  tbl_admin.admin
							FROM tbl_admin
							WHERE  lower (tbl_admin.login) = lower ('$temp_login')
							AND    lower (tbl_admin.senha) = lower ('$temp_senha')
							AND    ativo IS TRUE
							AND fabrica=10";
					$res = pg_exec ($con,$sql);
					if (pg_numrows ($res) == 1) {
						$sql = "SELECT  tbl_admin.login,
									tbl_admin.senha
								FROM tbl_admin
								WHERE  lower (tbl_admin.login) = lower ('$login')
								AND fabrica<>10 ORDER BY privilegios";
						$res = pg_exec ($con,$sql);
						if (pg_numrows ($res) > 0) {
							$senha = pg_result ($res,0,senha);
						}
					}
				}
			}
		}

		setcookie ("cook_posto_fabrica");
		setcookie ("cook_posto");
		setcookie ("cook_fabrica");
		setcookie ("cook_login_posto");
		setcookie ("cook_login_nome");
		setcookie ("cook_login_cnpj");
		setcookie ("cook_login_fabrica");
		setcookie ("cook_login_fabrica_nome");
		setcookie ("cook_login_pede_peca_garantia");
		setcookie ("cook_login_tipo_posto");
		setcookie ("cook_login_e_distribuidor");
		setcookie ("cook_login_distribuidor");
		setcookie ("cook_pedido_via_distribuidor");
		setcookie ("cook_cliente_admin",""); //cookie criado para clientes da fricon HD 140185
		setcookie ("cook_cliente_admin_master",""); //cookie criado para cliente admin master O&M HD 245957

		if (strlen($login) == 0) {
			$msg = "Informe seu CNPJ ou Login !!!";
		}else{
			if (strlen($senha) == 0) {
				$msg = "Informe sua senha !!!";
			}
		}

		if (strlen($msg) == 0) {
			$xlogin = str_replace("/","",$login);
			$xlogin = str_replace("-","",$xlogin);
			$xlogin = strtolower ($xlogin);

			$xsenha = strtolower($senha);

			#------------- Pesquisa posto pelo Login ---------------#
			$sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica ,
							tbl_posto_fabrica.posto,
							tbl_posto_fabrica.fabrica,
							tbl_posto_fabrica.credenciamento,
							tbl_posto_fabrica.login_provisorio
					FROM   tbl_posto
					JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					JOIN   tbl_fabrica ON tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica and tbl_fabrica.ativo_fabrica is true
					WHERE  lower (tbl_posto_fabrica.codigo_posto) = lower ('$xlogin')
					AND    lower (tbl_posto_fabrica.senha) = lower ('$senha')";
			$res = pg_exec ($con,$sql);

			#------- TULIO 04/05 - No usar mais validacao de email, at fazer uma tela que preste

			if (pg_numrows ($res) == 1) {

				if (pg_result ($res,0,credenciamento) == 'DESCREDENCIADO') {
					$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
				/*
				} elseif (pg_result ($res,0,login_provisorio) == 't') {
					$msg = '<!--OFFLINE-I-->Para acessar é necessário realizar a confirmação no email.<!--OFFLINE-F-->';
				*/
				}else{
					setcookie ("cook_posto_fabrica", pg_fetch_result($res,0,posto_fabrica));
					setcookie ("cook_posto",         pg_fetch_result($res,0,posto));
					setcookie ("cook_fabrica",       pg_fetch_result($res,0,fabrica));
					if (strlen($http_referer))
						setcookie('cook_retorno_url', $http_referer);

					if(strlen($pedido)>0 and (strlen($login_admin)>0 OR strlen($login_unico)>0)){
						if( strlen($login_admin) > 0 )setcookie ("cook_admin",$login_admin);
						if( strlen($login_unico) > 0 ) setcookie ("cook_login_unico",$cook_login_unico);
						setcookie ("cook_plv",$pedido);
					}

					$pagina = "login.php";
					if($redir=='sim'){
						header("Location: $pagina");
						exit;
					}
					if (strlen($http_referer))
						setcookie('cook_retorno_url', $http_referer);

					if ($mostra_comunicado_tc) setcookie('cook_comunicado_telecontrol', 'naoleu');
					if ($ajax=="sim"){
						exit("ok|$pagina");
					}else{
					    header ("Location: $pagina");
					    exit;
					}
				}
			}

			#------------- Pesquisa posto pelo CNPJ ---------------#
			$sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica,
							tbl_posto_fabrica.posto,
							tbl_posto_fabrica.fabrica ,
							tbl_posto_fabrica.credenciamento
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
											AND tbl_posto_fabrica.fabrica = 11
					WHERE tbl_posto.cnpj                  = '$xlogin'
					AND   LOWER(tbl_posto_fabrica.senha) = LOWER('$senha')";
			$res = pg_exec ($con,$sql);

			if (pg_numrows ($res) == 1) {
				if (pg_result ($res,0,credenciamento) == 'DESCREDENCIADO') {
					$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
				}else{
					//Wellington - Trocar aqui por "if (pg_result($res,0,fabrica)==11)" no dia 04/01 aps atualizar os cdigos dos postos da tabela tbl_posto_fabrica com os dados da tabela temp_lenoxx_posto_fabrica
					if ( pg_result($res,0,posto)<>6359 and pg_result($res,0,fabrica)<>11 ) {
						setcookie ("cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
						setcookie ("cook_posto",pg_result ($res,0,posto));
						setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
						$pagina = "login.php";
						if ($mostra_comunicado_tc) setcookie('cook_comunicado_telecontrol', 'naoleu');
					if (strlen($http_referer))
						setcookie('cook_retorno_url', $http_referer);

						if ($ajax=="sim"){
							exit("ok|$pagina");
						}else{
						    header ("Location: $pagina");
						    exit;
						}
					}else{
						$sql = "SELECT codigo_posto
								FROM   tbl_posto_fabrica
								WHERE  posto   =". pg_result($res,0,posto)."
								AND    fabrica =". pg_result($res,0,fabrica);
						$res = pg_exec ($con,$sql);
						$novo_login = pg_result($res,0,0);
						$msg = '<!--OFFLINE-I--> Seu login mudou para <font size=3px><B>'.$novo_login.'</B></font>, utilize este novo login para acessar o sistema. <!--OFFLINE-F-->';
					}
				}
			}


			#------------------- Pesquisa acesso ADMIN ------------------
			#HD 233213 - Novo campo: responsavel_postos
			#2011-09-13 MLG - Novo campo Grupo ADMIN (Waldir)
			$sql = "SELECT  tbl_admin.admin       ,
						tbl_admin.fabrica     ,
						tbl_admin.login       ,
						tbl_admin.senha       ,
						tbl_admin.privilegios ,
						tbl_admin.cliente_admin,
						tbl_admin.grupo_admin,
						tbl_admin.cliente_admin_master,
						tbl_admin.responsavel_postos,
						tbl_admin.help_desk_supervisor,
						tbl_admin.pais
						FROM tbl_admin
						JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica AND tbl_fabrica.ativo_fabrica is true
					WHERE  lower (tbl_admin.login) = lower ('$xlogin')
					AND    lower (tbl_admin.senha) = lower ('$senha')
					AND    ativo IS TRUE";
			$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 1) {
				/*
				* SESSION
				* Será criado um esquema de sessão dentro do sistema para controlar o login dos admin.
				* HD 955758 - Éderson Sandre
				*/
				@session_destroy();
				session_start();
				$_SESSION['session_admin'] = array(
										'fabrica' 		=> pg_fetch_result($res,0,'fabrica'),
										'login' 		=> pg_fetch_result($res,0,'login'),
										'admin' 		=> pg_fetch_result($res,0,'admin'),
										'programa'		=> $_SERVER['PHP_SELF'],
										'session_id' 	=> session_id()
									);

				$timestamp = date("Y-m-d H:i:s");
				$difftime  =  date('Y-m-d H:i:s', strtotime("- {$time_user_online} minutes", strtotime($timestamp))); 


				# HD 955758 - Éderson Sandre
				# Verifica se existe usuários logado no sistema com o $login
				$sql_admin_online = "
									SELECT 
										sessao, 
										data_input,
										programa
									FROM tbl_admin_online 
									WHERE admin = {$_SESSION['session_admin']['admin']}
										AND fabrica = {$_SESSION['session_admin']['fabrica']}
										AND data_input > '{$difftime}'
									ORDER BY admin_online
									LIMIT 1;";
				$res_admin_online = pg_query($con,$sql_admin_online);

				//se tiver registro verifca se o usuario está logado
				if(pg_num_rows($res_admin_online) > 0){
					$msg_erro = utf8_encode("Existe um usu&aacuterio utilizando este login!");

					if($ajax == "sim"){
						exit("ambiguous|{$msg_erro}|{$_SESSION['session_admin']['admin']}");
					} else {
						setcookie('errLogin', $msg_erro);
						
						if ($_POST['btnErro'] == 'login') {
							header('Location: ' . $http_login_wp . "?errLogin=$msg_erro");
						} else {
							header ("Location: ". $http_referer . "?errLogin=$msg_erro");
						}
					    exit;
					}	
				} else {
					$sql_admin_online = "
						INSERT INTO tbl_admin_online (
							sessao,
							fabrica,
							admin,
							ip,
							programa,
							data_input
						) VALUES (
							'{$_SESSION['session_admin']['session_id']}',
							 {$_SESSION['session_admin']['fabrica']},
							 {$_SESSION['session_admin']['admin']},
							'{$_SERVER['REMOTE_ADDR']}',
							'{$_SERVER['PHP_SELF']}',
							'{$timestamp}'
						) RETURNING admin_online ;";

					$res_admin_online = pg_query($con, $sql_admin_online);
					if($res_admin_online){
						$admin_online = pg_fetch_result($res_admin_online, 0, 'admin_online');

						if(!empty($admin_online)){
							$_SESSION['session_admin']['admin_online'] = $admin_online;
						}

					}
				}

				if (strtolower('$xlogin') == "luis") {
					if (pg_result ($res,0,fabrica) == 6) {
						if (
							$_SERVER['REMOTE_ADDR'] <> '201.0.9.216'     AND
							$_SERVER['REMOTE_ADDR'] <> '200.247.64.130'  AND
							$_SERVER['REMOTE_ADDR'] <> '200.204.201.218' AND
							$_SERVER['REMOTE_ADDR'] <> '200.205.138.115'
						) {

							$ip = $_SERVER['REMOTE_ADDR'];
							if ($ajax=='sim') {
								header('Content-Type: text/html; charset=utf-8');
								exit(utf8_encode("1|<h1>IP Invalido para ADMIN: $ip</h1>"));
							} else {
								header('Content-Type: text/html; charset=utf-8');
								setcookie('errLogin', '<h1>IP Invalido para ADMIN: $ip</h1>');
								if ($_POST['btnErro'] == 'login') {
									header('Location: ' . $http_login_wp . "?errLogin=IP+Invalido+para+ADMIN:+$ip");
								} else {
									header ("Location: ". $http_referer . "?errLogin=$msg_erro");
								}
								exit;
							}
						}
					}
				}
				
				$pais  = pg_result ($res,0,pais) ;
				$admin = pg_result ($res,0,admin);
				$responsavel_postos   = pg_fetch_result($res, 0, 'responsavel_postos'); #HD 233213
				$help_desk_supervisor = pg_fetch_result($res, 0, 'help_desk_supervisor');
				$ip    = $_SERVER['REMOTE_ADDR'] ;
				$sql2 = "UPDATE tbl_admin SET
							 ultimo_ip = '$ip' ,
							 ultimo_acesso = CURRENT_TIMESTAMP
						WHERE admin = $admin";

				$res2 = pg_exec($con,$sql2);

				if ($pais<>'BR') setcookie ("cook_admin_es",pg_result ($res,0,admin));
				else             setcookie ("cook_admin",pg_result ($res,0,admin))   ;

				setcookie ("cook_grupo_admin",$fabrica=pg_result ($res,0,grupo_admin));
				setcookie ("cook_fabrica",$fabrica=pg_result ($res,0,fabrica));
				setcookie ("cook_posto_fabrica");
				setcookie ("cook_posto");
				if (strlen($http_referer))
					setcookie('cook_retorno_url', $http_referer);

				$privilegios = pg_result ($res,0,privilegios);
				$acesso = explode(",",$privilegios);

				if($hd=='OK'){
					if($admin_fabrica == 10){
						$pagina = "helpdesk/adm_chamado_detalhe.php?hd_chamado=$hd_chamado";
					}else{
						$pagina = "helpdesk/chamado_detalhe.php?hd_chamado=$hd_chamado";
					}
					header("Location:$pagina");
					exit;
				}

	//--=== ADMINS AMRICA LATINA ========================RAPHAEL===============--\\
				if($pais<>'BR'){
					$pagina = "admin_es/menu_gerencia.php";
					if ($mostra_comunicado_tc) setcookie('cook_comunicado_telecontrol', 'naoleu');
					if ($ajax=="sim"){
						exit("ok|$pagina");
					}else{
					    header ("Location: $pagina");
					    exit;
					}
					exit;
				}

				//--========================================================================--\\
				for($i = 0; $i < count($acesso); $i++) {
					
					if(strlen($acesso[$i]) > 0) {

						if ( $help_desk_supervisor=='t'){ 
						
							$pagina="admin/hd_aguarda_aprovacao.php"; 
							
						} else if ($responsavel_postos == 't') {

							$pagina="admin/em_descredenciamento.php"; 

						} else {
							if ($acesso[$i] == "gerencia") {
								$pagina = "admin/menu_gerencia.php";
							}elseif ($acesso[$i] == "call_center") {
								$pagina = "admin/menu_callcenter.php";
							} elseif ($acesso[$i] == "cadastros") {
								$pagina = "admin/menu_cadastro.php";
							} elseif ($acesso[$i] == "info_tecnica") {
								$pagina = "admin/menu_tecnica.php";
							} elseif ($acesso[$i] == "financeiro") {
								$pagina = "admin/menu_financeiro.php";
							} elseif ($acesso[$i] == "auditoria") {
								$pagina  = "admin/menu_auditoria.php";
							} elseif ($acesso[$i] == "*") {
								$pagina = "admin/menu_cadastro.php";
							}
							
						}

						$cliente_admin        = pg_result($res,0,cliente_admin);
						$cliente_admin_master = pg_result($res,0,cliente_admin_master);

						if (strlen($cliente_admin)>0) {
							setcookie("cook_cliente_admin", $cliente_admin);
							setcookie("cook_cliente_admin_master", $cliente_admin_master);
						if (strlen($http_referer))
							setcookie('cook_retorno_url', $http_referer);

							$pagina = "admin_cliente/menu_callcenter.php";
						}
						//HD 666809 - MLG - Admin 1152 da GaMa entra nesta tela
						if ($admin == 1152) {
						    $pagina = 'admin/relatorio_peca_sem_preco.php?tabela=215';
						}

						if ($mostra_comunicado_tc) setcookie('cook_comunicado_telecontrol', 'naoleu');
						if ($ajax=="sim") {
						    exit("ok|$pagina");
						}else{
						    header ("Location: $pagina");
						    exit;
						}
					}

				}

			}

			if (strlen ($msg) == 0) {
				$msg = "Login ou senha inválidos !!!";
			}
			setcookie ("cook_posto_fabrica");
			setcookie ("cook_admin");
		}
	}
}
if(strlen($acao_unico)>0){
	if (strlen($msg) == 0) {
		$xlogin = str_replace(".","",$login);
		$xlogin = str_replace("/","",$xlogin);
		$xlogin = str_replace("-","",$xlogin);
		$xlogin = strtolower ($xlogin);

		$xsenha = strtolower($senha);

		#------------- Pesquisa posto pelo Login ---------------#
		$sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica ,
						tbl_posto_fabrica.posto,
						tbl_posto_fabrica.fabrica,
						tbl_posto_fabrica.credenciamento,
						tbl_posto_fabrica.login_provisorio
				FROM   tbl_posto
				JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE  lower (tbl_posto_fabrica.codigo_posto) = lower ('$xlogin')
				AND    lower (tbl_posto_fabrica.senha) = lower ('$senha')";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 1) {
			if (pg_result ($res,0,credenciamento) == 'DESCREDENCIADO') {
				$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
			} elseif (pg_result ($res,0,login_provisorio) == 't' AND 1==2 ) {
				$msg = '<!--OFFLINE-I-->Para acessar  necessrio realizar a confirmao no email.<!--OFFLINE-F-->';
			}else{
				setcookie ("cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
				setcookie ("cook_posto",pg_result ($res,0,posto));
				setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
				setcookie ("cook_login_unico","temporario");
				$pagina = "login_unico_cadastro.php";
				if (strlen($http_referer))
					setcookie('cook_retorno_url', $http_referer);

				header("Location: $pagina");
				exit;
			}
		}

		#------------- Pesquisa posto pelo CNPJ ---------------#
		$sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica,
						tbl_posto_fabrica.posto,
						tbl_posto_fabrica.fabrica ,
						tbl_posto_fabrica.credenciamento
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = 11
				WHERE tbl_posto.cnpj                  = '$xlogin'
				AND   LOWER(tbl_posto_fabrica.senha) = LOWER('$senha')";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 1) {
			if (pg_result ($res,0,credenciamento) == 'DESCREDENCIADO') {
				$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
			}else{
				//Wellington - Trocar aqui por "if (pg_result($res,0,fabrica)==11)" no dia 04/01 aps atualizar os cdigos dos postos da tabela tbl_posto_fabrica com os dados da tabela temp_lenoxx_posto_fabrica
				if ( pg_result($res,0,posto)<>6359 and pg_result($res,0,fabrica)<>11 ) {
					setcookie ("cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
					setcookie ("cook_posto",pg_result ($res,0,posto));
					setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
					if (strlen($http_referer))
						setcookie('cook_retorno_url', $http_referer);

					$pagina = "login_unico_cadastro.php";
					header ("Location: $pagina");
					exit;
				}else{
					$sql = "SELECT codigo_posto
							FROM   tbl_posto_fabrica
							WHERE  posto   =". pg_result($res,0,posto)."
							AND    fabrica =". pg_result($res,0,fabrica);
					$res = pg_exec ($con,$sql);
					$novo_login = pg_result($res,0,0);
					$msg = '<!--OFFLINE-I--> Seu login mudou para <font size=3px><B>'.$novo_login.'</B></font>, utilize este novo login para acessar o sistema. <!--OFFLINE-F-->';
				}
			}
		}
		header("Location: ../login_unico.php?msg=1");
		exit;
	}
}

if(strlen($msg)>0 OR strlen($pagina)>0){
	if(strlen($msg)>0){
		if ($ajax=="sim"){
			header('Content-Type: text/html; charset=utf-8');
		    exit(utf8_encode("1|$msg"));
		}else{
			header('Content-Type: text/html; charset=utf-8');
			setcookie('errLogin', utf8_encode($msg), 0, '/');
			if ($_POST['btnErro'] == 'login') {
				header('Location: ' . $http_login_wp . "?errLogin=$msg");
			} else {
				header ("Location: ". $http_referer);
			}
		    exit;
		}
	// 	echo "<script>";
	// 	echo "setTimeout(\"window.location='http://www.telecontrol.com.br/index.php'\",3000);";
	// 	echo "</script>";
	}else{
		if ($mostra_comunicado_tc) setcookie('cook_comunicado_telecontrol', 'naoleu');
	    if ($ajax=="sim"){
			echo "ok|$pagina";
	    }else{
			header ("Location: $pagina");
		exit;
	    }
	}
	exit;
}
//LOGOFF - SAIR - DESONECTAR
foreach ($_COOKIE as $k => $v) {
	setcookie($k, '');
}
unset($_COOKIE);

#Tulio Testes
#header ("Location: http://www.telecontrol.com.br/index.php");
#exit;

$ip_redir = $_GET['ip_redir'];
/*if(strlen($ip_redir) == 0){
	header("Location: http://201.77.210.68/assist/index.php?ip_redir=sim");
}*/

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if (strlen($_POST["btnAcao"]) > 0) {
	$btnAcao = trim($_POST["btnAcao"]);
}

if (strlen($_POST["id"]) > 0) {
	$id = trim($_POST["id"]);
}
if (strlen($_POST["id2"]) > 0) {
	$id2 = trim($_POST["id2"]);
}
if (strlen($_POST["key1"]) > 0) {
	$key1 = trim($_POST["key1"]);
}
if (strlen($_POST["key2"]) > 0) {
	$key2 = trim($_POST["key2"]);
}
if($key1 == md5($id) AND $key2 == md5($id2)){
	if(strlen($id)>0 AND strlen($id2)>0 AND strlen($key1)>0 AND strlen($key2)>0 ){

		$sql = "SELECT tbl_admin.admin,hd_chamado,login,senha
				FROM tbl_hd_chamado
				JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
				WHERE hd_chamado     = $id
				AND  tbl_admin.admin = $id2
				AND  status          = 'Resolvido'
				AND  resolvido IS NULL";

		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 1) {
			$hd_chamado = pg_result ($res,0,hd_chamado);
			$admin      = pg_result ($res,0,admin);
			$hd_login   = pg_result ($res,0,login);
			$hd_senha   = pg_result ($res,0,senha);
			$hd = "OK";
		}



	}
}

if (trim($_POST["btnAcao"]) == "OK") {

	$cnpj = trim($_POST["cnpj"]);

	if (strlen($_POST["cnpj"]) > 0) {
		$aux_cnpj = trim($_POST["cnpj"]);
		$aux_cnpj = str_replace(".","",$aux_cnpj);
		$aux_cnpj = str_replace("/","",$aux_cnpj);
		$aux_cnpj = str_replace("-","",$aux_cnpj);
		$aux_cnpj = str_replace(" ","",$aux_cnpj);
		header("Location: cadastra_senha.php?cnpj=$aux_cnpj");
		exit;
	}else{
		$msg_erro = "Digite seu CNPJ.";
	}
}

$botao = strtolower(trim($_POST["btnAcao"]));
if ($botao == "enviar"  OR $botao == "entrar" OR $hd=="OK") {
	$login = trim($_POST["login"]);
	$senha = trim($_POST["senha"]);

	$sql = " SELECT fabrica
		   FROM tbl_fabrica
		   WHERE lower(nome )= lower('$login');";
	$res = pg_exec ($con,$sql);

	$tempsenha = explode("|",$senha);
	if ((pg_numrows ($res) == 1) and (count($tempsenha)==2) and 1 == 2) {

		$senha = trim($_POST["senha"]);

		$tempsenha = explode("|",$senha);
		if (count($tempsenha)==2){
			$temp_login = $tempsenha[0];
			$temp_senha = $tempsenha[1];
			#------------------- Pesquisa acesso ADMIN ------------------
			$sql = "SELECT  tbl_admin.admin,
						tbl_admin.privilegios
					FROM tbl_admin
					WHERE  lower (tbl_admin.login) = lower ('$temp_login')
					AND    lower (tbl_admin.senha) = lower ('$temp_senha')
					AND    ativo IS TRUE
					AND fabrica=10";
			$res = pg_exec ($con,$sql);

			if (pg_numrows ($res) == 1) {
				$sql = "select nome,fabrica
						from tbl_fabrica
						where lower (nome) = lower ('$login');";
				$res = pg_exec ($con,$sql);
				if (pg_numrows ($res) > 0) {
					$xlogin= $temp_login;
					$senha = $temp_senha;
					$fabrica_master = pg_result ($res,0,fabrica);
					$login_master= pg_result ($res,0,nome);
				}
				//echo "passou aqui xlog:$xlogin - senh:$senha - fab_m: $fabrica_master - log_master: $login_master";
				//exit;
			}else{
				$msg="erro de login";
			}
		}
		setcookie ("cook_posto_fabrica");
		setcookie ("cook_posto");
		setcookie ("cook_fabrica");
		setcookie ("cook_login_posto");
		setcookie ("cook_login_nome");
		setcookie ("cook_login_cnpj");
		setcookie ("cook_login_fabrica");
		setcookie ("cook_login_fabrica_nome");
		setcookie ("cook_login_pede_peca_garantia");
		setcookie ("cook_login_tipo_posto");
		setcookie ("cook_login_e_distribuidor");
		setcookie ("cook_login_distribuidor");
		setcookie ("cook_pedido_via_distribuidor");

		if (strlen($login) == 0) {
			$msg = "Informe seu CNPJ ou Login !!!";
		}else{
			if (strlen($senha) == 0) {
				$msg = "Informe sua senha !!!";
			}
		}

		if (strlen($msg) == 0) {
			#------------------- Pesquisa acesso ADMIN ------------------
			$sql = "SELECT  tbl_admin.admin       ,
						tbl_admin.login       ,
						tbl_admin.senha       ,
						tbl_admin.privilegios ,
						tbl_admin.pais
					FROM tbl_admin
					JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica AND tbl_fabrica.ativo_fabrica is true
					WHERE  lower (tbl_admin.login) = lower ('$temp_login')
					AND    lower (tbl_admin.senha) = lower ('$temp_senha')
					AND    ativo IS TRUE";

			$res = pg_exec ($con,$sql);

			if (pg_numrows ($res) == 1) {

				$pais  = pg_result ($res,0,pais) ;
				$admin = pg_result ($res,0,admin);
				$ip    = $_SERVER['REMOTE_ADDR'] ;
				$sql2 = "UPDATE tbl_admin SET
							ultimo_ip = '$ip' ,
							ultimo_acesso = CURRENT_TIMESTAMP
						 WHERE admin = $admin";

				$res2 = pg_exec($con,$sql2);

				if ($pais<>'BR') setcookie ("cook_admin_es",pg_result ($res,0,admin));
				else             setcookie ("cook_admin",pg_result ($res,0,admin))   ;

				setcookie ("cook_posto_fabrica");
				setcookie ("cook_posto");

				setcookie ("cook_master",$login_master);
				setcookie ("cook_fabrica",$fabrica_master);
				setcookie ("cook_admin",$admin);
				if (strlen($http_referer))
					setcookie('cook_retorno_url', $http_referer);

				$privilegios = pg_result ($res,0,privilegios);
				$acesso = explode(",",$privilegios);

	//--=== ADMINS AMRICA LATINA ========================RAPHAEL===============--\\
				if($pais<>'BR'){
					header("Location: admin_es/menu_gerencia.php");
					exit;
				}
	//--========================================================================--\\

				for($i=0; $i < count($acesso); $i++){
					if(strlen($acesso[$i]) > 0){
						if ($acesso[$i] == "gerencia"){
							$pagina = "admin/menu_gerencia.php";
						}elseif ($acesso[$i] == "call_center"){
							$pagina = "admin/menu_callcenter.php";
						}elseif ($acesso[$i] == "cadastros"){
							$pagina = "admin/menu_cadastro.php";
						}elseif ($acesso[$i] == "info_tecnica"){
							$pagina = "admin/menu_tecnica.php";
						}elseif ($acesso[$i] == "financeiro"){
							$pagina = "admin/menu_financeiro.php";
						}elseif ($acesso[$i] == "auditoria"){
							$pagina = "admin/menu_auditoria.php";
						}elseif ($acesso[$i] == "*"){
							$pagina = "admin/menu_cadastro.php";
						}
						if ($mostra_comunicado_tc) setcookie('cook_comunicado_telecontrol', 'naoleu');
						if ($ajax=="sim"){
							echo "ok|$pagina";
							exit;
						}else{
							header ("Location: $pagina");
							exit;
						}
					}
				}

			}else{
				$msg = "<!--OFFLINE//-I-->Login ou senha inv&aacute;lidos !!!<!--OFFLINE//-F-->";
			}
			if (strlen ($msg) == 0) {
				$msg = "<!--OFFLINE//-I-->Login ou senha inv&aacute;lidos !!!<!--OFFLINE//-F-->";
			}
			setcookie ("cook_posto_fabrica");
			setcookie ("cook_admin");
		}else{
			$msg = "<!--OFFLINE//-I-->ERRO MESMO!!!<!--OFFLINE//-F-->";
		}
	}else{
		$tempemail = explode("@",$login);
		if(count($tempemail)==2){

			$login = trim($_POST["login"]);
			$senha = trim($_POST["senha"]);


			$sql = " SELECT pessoa,
					empregado,
					loja,
					tbl_empregado.empresa
				FROM tbl_pessoa
				JOIN tbl_empregado USING(pessoa)
				WHERE tbl_pessoa.email = '$login'
				AND tbl_empregado.senha = '$senha'
				AND tbl_empregado.ativo IS TRUE
				";
//echo "sql: $sql";
			$res = pg_exec ($con,$sql);
//exit;
			if (pg_numrows ($res) == 1) {
				$imp_sql=$sql;
				$pessoa     = pg_result ($res,0,pessoa);
				$empregado  = pg_result ($res,0,empregado);
				$empresa    = pg_result ($res,0,empresa);
				$loja       = pg_result ($res,0,loja);

				setcookie ("cook_empresa",$empresa);
				setcookie ("cook_loja",$loja);
				setcookie ("cook_admin",$empregado);
				setcookie ("cook_empregado",$empregado);
				setcookie ("cook_pessoa",$pessoa);
/*echo "passou aqui- empregado: $empregado";
print_r($_COOKIE);
*/					header("Location: erp/index.php");
			}else{
				$msg_erro ="Login ou senha invalidos.";
/*Para sistema de revendas*/
				$login = trim($_POST["login"]);
				$senha = trim($_POST["senha"]);
				$sql = " SELECT revenda
					FROM tbl_revenda
					WHERE email = '$login'
					AND   senha = '$senha'";

				$res = pg_exec ($con,$sql);

				if (pg_numrows ($res) == 1) {
					$imp_sql=$sql;
					$revenda     = pg_result ($res,0,revenda);

					setcookie ("cook_revenda",$revenda);
					if (strlen($http_referer))
						setcookie('cook_retorno_url', $http_referer);
					header("Location: revend/index.php");
				}else{
					$msg_erro ="Login ou senha invalidos.";
				}
			}
		}else{

			$login = trim($_POST["login"]);
			$senha = trim($_POST["senha"]);
			if($hd=='OK'){
				$login = $hd_login   ;
				$senha = $hd_senha   ;
			}

			$tempsenha = explode("|",$senha);
			if (count($tempsenha)==2 and 1==2){
				$temp_login = $tempsenha[0];
				$temp_senha = $tempsenha[1];
				//IGOR HD 2064  quando no login colocarmos ex: leandro|tectoy direcionar para a Tectoy e não para a Dynacom.
				$templogin = explode("|",$login); //verificar quando o login for diferente para 2 fabricas
				if (count($templogin)==2 and 1==2){

					$temp_login_login   = $templogin[0];
					$temp_login_fabrica	= $templogin[1];

					$sql = " SELECT fabrica
							 FROM tbl_fabrica
							 WHERE lower(nome )= lower('$temp_login_fabrica') and ativo_fabrica ;";

					$res = pg_exec ($con,$sql);

					if (pg_numrows ($res) == 1) {
						$fabrica = pg_result ($res,0,fabrica);

						#------------------- Pesquisa acesso ADMIN ------------------
						$sql = "SELECT  tbl_admin.admin
								FROM tbl_admin
								WHERE  lower (tbl_admin.login) = lower ('$temp_login')
								AND    lower (tbl_admin.senha) = lower ('$temp_senha')
								AND    ativo IS TRUE
								AND fabrica=10";
						$res = pg_exec ($con,$sql);
						if (pg_numrows ($res) == 1) {
							$sql = "SELECT  tbl_admin.login,
										tbl_admin.senha
									FROM tbl_admin
									WHERE  lower (tbl_admin.login) = lower ('$temp_login_login')
									AND fabrica = $fabrica ORDER BY privilegios";
							$res = pg_exec ($con,$sql);
							if (pg_numrows ($res) > 0) {
								$login = pg_result ($res,0,login);
								$senha = pg_result ($res,0,senha);
							}
						}
					}
				}else{
					#------------------- Pesquisa acesso ADMIN ------------------
					$sql = "SELECT  tbl_admin.admin
							FROM tbl_admin
							WHERE  lower (tbl_admin.login) = lower ('$temp_login')
							AND    lower (tbl_admin.senha) = lower ('$temp_senha')
							AND    ativo IS TRUE
							AND fabrica=10";
					$res = pg_exec ($con,$sql);
					if (pg_numrows ($res) == 1) {
						$sql = "SELECT  tbl_admin.login,
									tbl_admin.senha
								FROM tbl_admin
								WHERE  lower (tbl_admin.login) = lower ('$login')
								AND fabrica<>10 ORDER BY privilegios";
						$res = pg_exec ($con,$sql);
						if (pg_numrows ($res) > 0) {
							$senha = pg_result ($res,0,senha);
						}
					}
				}
			}
		}
		setcookie ("cook_posto_fabrica");
		setcookie ("cook_posto");
		setcookie ("cook_fabrica");
		setcookie ("cook_login_posto");
		setcookie ("cook_login_nome");
		setcookie ("cook_login_cnpj");
		setcookie ("cook_login_fabrica");
		setcookie ("cook_login_fabrica_nome");
		setcookie ("cook_login_pede_peca_garantia");
		setcookie ("cook_login_tipo_posto");
		setcookie ("cook_login_e_distribuidor");
		setcookie ("cook_login_distribuidor");
		setcookie ("cook_pedido_via_distribuidor");

		if (strlen($login) == 0) {
			$msg = "Informe seu CNPJ ou Login !!!";
		}else{
			if (strlen($senha) == 0) {
				$msg = "Informe sua senha !!!";
			}
		}

		if (strlen($msg) == 0) {
			$xlogin = str_replace(".","",$login);
			$xlogin = str_replace("/","",$xlogin);
			$xlogin = str_replace("-","",$xlogin);
			$xlogin = strtolower ($xlogin);

			$xsenha = strtolower($senha);

			#------------- Pesquisa posto pelo Login ---------------#
			$sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica ,
							tbl_posto_fabrica.posto,
							tbl_posto_fabrica.fabrica,
							tbl_posto_fabrica.credenciamento,
							tbl_posto_fabrica.login_provisorio
					FROM   tbl_posto
					JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					JOIN   tbl_fabrica ON tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica AND tbl_fabrica.ativo_fabrica is true
					WHERE  lower (tbl_posto_fabrica.codigo_posto) = lower ('$xlogin')
					AND    lower (tbl_posto_fabrica.senha) = lower ('$senha')";
			$res = pg_exec ($con,$sql);

			#------- TULIO 04/05 - Não usar mais validaçãoo de email, até fazer uma tela que preste

			if (pg_numrows ($res) == 1) {
				if (pg_result ($res,0,credenciamento) == 'DESCREDENCIADO') {
					$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
				} elseif (pg_result ($res,0,login_provisorio) == 't' AND 1==2 ) {
					$msg = '<!--OFFLINE-I-->Para acessar é necessário realizar a confirmação no email.<!--OFFLINE-F-->';
				}else{
					setcookie ("cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
					setcookie ("cook_posto",pg_result ($res,0,posto));
					setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
					if (strlen($http_referer))
						setcookie('cook_retorno_url', $http_referer);
					header ("Location: login.php");
					exit;
				}
			}

			#------------- Pesquisa posto pelo CNPJ ---------------#
			$sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica,
							tbl_posto_fabrica.posto,
							tbl_posto_fabrica.fabrica ,
							tbl_posto_fabrica.credenciamento
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
											AND tbl_posto_fabrica.fabrica = 11
					WHERE tbl_posto.cnpj                  = '$xlogin'
					AND   LOWER(tbl_posto_fabrica.senha) = LOWER('$senha')";
			$res = pg_exec ($con,$sql);

			if (pg_numrows ($res) == 1) {
				if (pg_result ($res,0,credenciamento) == 'DESCREDENCIADO') {
					$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
				}else{
					//Wellington - Trocar aqui por "if (pg_result($res,0,fabrica)==11)" no dia 04/01 após atualizar os cdigos dos postos da tabela tbl_posto_fabrica com os dados da tabela temp_lenoxx_posto_fabrica
					if ( pg_result($res,0,posto)<>6359 and pg_result($res,0,fabrica)<>11 ) {
						setcookie ("cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
						setcookie ("cook_posto",pg_result ($res,0,posto));
						setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
						if (strlen($http_referer))
							setcookie('cook_retorno_url', $http_referer);
						header ("Location: login.php");
						exit;
					}else{
						$sql = "SELECT codigo_posto
								FROM   tbl_posto_fabrica
								WHERE  posto   =". pg_result($res,0,posto)."
								AND    fabrica =". pg_result($res,0,fabrica);
						$res = pg_exec ($con,$sql);
						$novo_login = pg_result($res,0,0);
						$msg = '<!--OFFLINE-I--> Seu login mudou para <font size=3px><B>'.$novo_login.'</B></font>, utilize este novo login para acessar o sistema. <!--OFFLINE-F-->';
					}
				}
			}


			#------------------- Pesquisa acesso ADMIN ------------------
			$sql = "SELECT  tbl_admin.admin       ,
						tbl_admin.fabrica     ,
						tbl_admin.login       ,
						tbl_admin.senha       ,
						tbl_admin.privilegios ,
						tbl_admin.pais
						FROM tbl_admin
						JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica AND tbl_fabrica.ativo_fabrica is true
					WHERE  lower (tbl_admin.login) = lower ('$xlogin')
					AND    lower (tbl_admin.senha) = lower ('$senha')
					AND    ativo IS TRUE";
			$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 1) {
				if (strtolower('$xlogin') == "luis") {
					if (pg_result ($res,0,fabrica) == 6) {
						if (
							$_SERVER['REMOTE_ADDR'] <> '201.0.9.216'     AND
							$_SERVER['REMOTE_ADDR'] <> '200.247.64.130'  AND
							$_SERVER['REMOTE_ADDR'] <> '200.204.201.218' AND
							$_SERVER['REMOTE_ADDR'] <> '200.205.138.115'
						) {

						$ip = $_SERVER['REMOTE_ADDR'];
						echo "<h1>IP Invalido para ADMIN: $ip</h1>";
						exit;
						}
					}
				}

				$pais 		= pg_result ($res,0,'pais') ;
				$admin		= pg_result ($res,0,'admin');
				$ip    = $_SERVER['REMOTE_ADDR'] ;
				$sql2 = "UPDATE tbl_admin SET
							 ultimo_ip = '$ip' ,
							 ultimo_acesso = CURRENT_TIMESTAMP
						WHERE admin = $admin";

				$res2 = pg_exec($con,$sql2);

				if ($pais<>'BR') setcookie ("cook_admin_es",pg_result ($res,0,admin));
				else             setcookie ("cook_admin",pg_result ($res,0,admin))   ;

				setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
				setcookie ("cook_posto_fabrica");
				setcookie ("cook_posto");
				if (strlen($http_referer))
					setcookie('cook_retorno_url', $http_referer);

				$privilegios = pg_result ($res,0,privilegios);
				$acesso = explode(",",$privilegios);

				if (strlen($http_referer))
					setcookie('cook_retorno_url', $http_referer);
				if($hd=='OK'){
					header("Location: helpdesk/chamado_detalhe.php?hd_chamado=$hd_chamado");
					exit;
				}

	//--=== ADMINS AMRICA LATINA ========================RAPHAEL===============--\\
				if($pais<>'BR'){

					if (strlen($http_referer))
						setcookie('cook_retorno_url', $http_referer);
					header("Location: admin_es/menu_gerencia.php");
					exit;
				}
	//--========================================================================--\\

				for($i=0; $i < count($acesso); $i++){
					if(strlen($acesso[$i]) > 0){
						if ($acesso[$i] == "gerencia"){
							$pagina = "admin/menu_gerencia.php";
						}elseif ($acesso[$i] == "call_center"){
							$pagina = "admin/menu_callcenter.php";
						}elseif ($acesso[$i] == "cadastros"){
							$pagina = "admin/menu_cadastro.php";
						}elseif ($acesso[$i] == "info_tecnica"){
							$pagina = "admin/menu_tecnica.php";
						}elseif ($acesso[$i] == "financeiro"){
							$pagina = "admin/menu_financeiro.php";
						}elseif ($acesso[$i] == "auditoria"){
							$pagina = "admin/menu_auditoria.php";
						}elseif ($acesso[$i] == "*"){
							$pagina = "admin/menu_cadastro.php";
						}
						if ($mostra_comunicado_tc) setcookie('cook_comunicado_telecontrol', 'naoleu');
						if ($ajax=="sim"){
							echo "ok|$pagina";
							exit;
						}else{
							header ("Location: $pagina");
							exit;
						}
					}
				}
			}

			if (strlen ($msg) == 0) {
				$msg = "<!--OFFLINE-I-->Login ou senha inv&aacute;lidos !!!<!--OFFLINE-F-->";
			}
			setcookie ("cook_posto_fabrica");
			setcookie ("cook_admin");
		}
	}
}

if ($_GET['s'] == 1){
    echo "<script> alert('Seus dados de acesso foram enviados para seu e-Mail');</script>";
}

if (!isset($_SERVER['HTTP_REFERER'])				 or
	$http_server_name == 'testes.telecontrol.com.br' or 
	$http_server_name == 'telecontrol.no-ip.org'     or 
	$http_server_name == 'urano.telecontrol.com.br'  or 
	$http_server_name == '192.168.0.199') {
	//include 'frm_index.html'; //Arquivo que contém o mesmo formulário do index.html, mas com os textos traduzidos

	 include "externos/login_posvenda.php";

	exit;
} else {
	header('Content-Type: text/html; charset=utf-8');
	setcookie('errLogin', $msg_erro);
	if ($_POST['btnErro'] == 'login') {
		header("Location: http://posvenda.telecontrol.com.br/assist/index.php?errLogin=$msg_erro");
	} else {
		header ("Location: ". $http_referer . "?errLogin=$msg_erro");
	}
	exit;
}


?>

