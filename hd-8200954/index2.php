<?
$ip_redir = $_GET['ip_redir'];
/*if(strlen($ip_redir) == 0){
	header("Location: http://201.77.210.68/assist/index.php?ip_redir=sim");
}*/

include '/var/www/assist/www/dbconfig.php';
include '/var/www/assist/www/includes/dbconnect-inc.php';

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

$botao = trim($HTTP_POST_VARS["btnAcao"]);
$ajax  = $_GET['ajax'];
$acao  = $_GET['acao'];
$redir = $_GET['redir'];

if ( $ajax == 'sim' AND $acao == 'validar' ) {

	$login = trim($HTTP_POST_VARS["login"]);
	$senha = trim($HTTP_POST_VARS["senha"]);

	$sql = " SELECT fabrica 
		   FROM tbl_fabrica 
		   WHERE lower(nome )= lower('$login');";
	$res = pg_exec ($con,$sql);

	$tempsenha = explode("|",$senha);

	if ((pg_numrows ($res) == 1) and (count($tempsenha)==2)) {

		$senha = trim($HTTP_POST_VARS["senha"]);

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
				$msg="Login ou senha inválidos";
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

				$privilegios = pg_result ($res,0,privilegios);
				$acesso = explode(",",$privilegios);		

	//--=== ADMINS AMÉRICA LATINA ========================RAPHAEL===============--\\
				if($pais<>'BR'){
					$pagina = "admin_es/menu_gerencia.php";
					echo "ok|$pagina";
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
						echo "ok|$pagina";
						exit;
					}
				}

			}else{
				$msg = "<!--OFFLINE-I-->Login ou senha inválidos !!!<!--OFFLINE-F-->";
			}
			if (strlen ($msg) == 0) {
				$msg = "<!--OFFLINE-I-->Login ou senha inválidos !!!<!--OFFLINE-F-->";
			}
			setcookie ("cook_posto_fabrica");
			setcookie ("cook_admin");
		}else{
			$msg = "<!--OFFLINE-I-->Login ou senha inválidos!!!<!--OFFLINE-F-->";
		}  
	}else{
		$tempemail = explode("@",$login);

		//login_unico
		
		if(count($tempemail)==2){
			$login = trim($HTTP_POST_VARS["login"]);
			$senha = trim($HTTP_POST_VARS["senha"]);
			$sql = " SELECT login_unico,posto
				FROM tbl_login_unico	
				WHERE email = '$login'
				AND   senha = 'md5' || md5('$senha')
				AND   ativo IS TRUE
				AND   email_autenticado IS NOT NULL";

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

				$pagina = "login_unico.php";

				echo "ok|$pagina";
				exit;

			}else{
				//$msg_erro ="Login ou senha inválidos.";
				$msg_erro = "Login ou senha inválidos";
			}
		}
		


		if(count($tempemail)==2){
		
			$login = trim($HTTP_POST_VARS["login"]);
			$senha = trim($HTTP_POST_VARS["senha"]);


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
*/					$pagina = "../time/index.php";
					echo "ok|$pagina";
					exit;
			}else{
				$msg_erro ="Login ou senha inválidos.";
/*Para sistema de revendas*/
				$login = trim($HTTP_POST_VARS["login"]);
				$senha = trim($HTTP_POST_VARS["senha"]);
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
				}
			}
		}else{

			$login = trim($HTTP_POST_VARS["login"]);
			$senha = trim($HTTP_POST_VARS["senha"]);
			if($hd=='OK'){
				$login = $hd_login   ;
				$senha = $hd_senha   ;
			}

			$tempsenha = explode("|",$senha);
			if (count($tempsenha)==2){
				$temp_login = $tempsenha[0];
				$temp_senha = $tempsenha[1];
				//IGOR HD 2064  quando no login colocarmos ex: leandro|tectoy direcionar para a Tectoy e não para a Dynacom.
				$templogin = explode("|",$login); //verificar quando o login for diferente para 2 fabricas	
				if (count($templogin)==2){

					$temp_login_login   = $templogin[0];
					$temp_login_fabrica	= $templogin[1];	

					$sql = " SELECT fabrica 
							 FROM tbl_fabrica 
							 WHERE lower(nome )= lower('$temp_login_fabrica');";

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
					WHERE  lower (tbl_posto_fabrica.codigo_posto) = lower ('$xlogin')
					AND    lower (tbl_posto_fabrica.senha) = lower ('$senha')";
			$res = pg_exec ($con,$sql);
			
			#------- TULIO 04/05 - Não usar mais validação de email, até fazer uma tela que preste
			
			if (pg_numrows ($res) == 1) {
				if (pg_result ($res,0,credenciamento) == 'DESCREDENCIADO') {
					$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
				} elseif (pg_result ($res,0,login_provisorio) == 't' AND 1==2 ) {
					$msg = '<!--OFFLINE-I-->Para acessar é necessário realizar a confirmação no email.<!--OFFLINE-F-->';
				}else{
					setcookie ("cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
					setcookie ("cook_posto",pg_result ($res,0,posto));
					setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
					$pagina = "login.php";
					if($redir=='sim'){
						header("Location: $pagina");
					}	
					echo "ok|$pagina";
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
					//Wellington - Trocar aqui por "if (pg_result($res,0,fabrica)==11)" no dia 04/01 após atualizar os códigos dos postos da tabela tbl_posto_fabrica com os dados da tabela temp_lenoxx_posto_fabrica
					if ( pg_result($res,0,posto)<>6359 and pg_result($res,0,fabrica)<>11 ) {
						setcookie ("cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
						setcookie ("cook_posto",pg_result ($res,0,posto));
						setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
						$pagina = "login.php";
						echo "ok|$pagina";
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
				
				setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
				setcookie ("cook_posto_fabrica");
				setcookie ("cook_posto");
				
				$privilegios = pg_result ($res,0,privilegios);
				$acesso = explode(",",$privilegios);

				if($hd=='OK'){
					$pagina = "assist/helpdesk/chamado_detalhe.php?hd_chamado=$hd_chamado";
					header("Location:$pagina");
					exit;
				}

	//--=== ADMINS AMÉRICA LATINA ========================RAPHAEL===============--\\
				if($pais<>'BR'){
					$pagina = "admin_es/menu_gerencia.php";
					echo "ok|$pagina";
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
							$pagina  = "admin/menu_auditoria.php";
						}elseif ($acesso[$i] == "*"){
							$pagina = "admin/menu_cadastro.php";
						}
						echo "ok|$pagina";
						exit;
					}
				}
			}

			if (strlen ($msg) == 0) {
				$msg = "<!--OFFLINE-I-->Login ou senha inválidos !!!<!--OFFLINE-F-->";
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
				$msg = '<!--OFFLINE-I-->Para acessar é necessário realizar a confirmação no email.<!--OFFLINE-F-->';
			}else{
				setcookie ("cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
				setcookie ("cook_posto",pg_result ($res,0,posto));
				setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
				setcookie ("cook_login_unico","temporario");
				$pagina = "login_unico_cadastro.php";

				header("Location: assist/$pagina");
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
				//Wellington - Trocar aqui por "if (pg_result($res,0,fabrica)==11)" no dia 04/01 após atualizar os códigos dos postos da tabela tbl_posto_fabrica com os dados da tabela temp_lenoxx_posto_fabrica
				if ( pg_result($res,0,posto)<>6359 and pg_result($res,0,fabrica)<>11 ) {
					setcookie ("cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
					setcookie ("cook_posto",pg_result ($res,0,posto));
					setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
					$pagina = "login_unico_cadastro.php";
					header ("Location:assist/$pagina");
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
		header("Location: telecontrol/login_unico.php?msg=1");
	}
	
}
if(strlen($msg)>0 OR strlen($pagina)>0){
if(strlen($msg)>0)
	echo "1|$msg";
else
	echo "ok|$pagina";
exit;
}
header ("Location: http://www.telecontrol.com.br/index.php");
exit;























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

$botao = trim($HTTP_POST_VARS["btnAcao"]);
if ( $botao == "Enviar"  OR $botao == "entrar" OR $hd=="OK") {
	$login = trim($HTTP_POST_VARS["login"]);
	$senha = trim($HTTP_POST_VARS["senha"]);

	$sql = " SELECT fabrica 
		   FROM tbl_fabrica 
		   WHERE lower(nome )= lower('$login');";
	$res = pg_exec ($con,$sql);
  
	$tempsenha = explode("|",$senha);
	if ((pg_numrows ($res) == 1) and (count($tempsenha)==2)) {

		$senha = trim($HTTP_POST_VARS["senha"]);

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

				$privilegios = pg_result ($res,0,privilegios);
				$acesso = explode(",",$privilegios);		

	//--=== ADMINS AMÉRICA LATINA ========================RAPHAEL===============--\\
				if($pais<>'BR'){
					header("Location: admin_es/menu_gerencia.php");
					exit;
				}
	//--========================================================================--\\

				for($i=0; $i < count($acesso); $i++){
					if(strlen($acesso[$i]) > 0){
						if ($acesso[$i] == "gerencia"){
							header("Location: admin/menu_gerencia.php");
						}elseif ($acesso[$i] == "call_center"){
							header("Location: admin/menu_callcenter.php");
						}elseif ($acesso[$i] == "cadastros"){
							header("Location: admin/menu_cadastro.php");
						}elseif ($acesso[$i] == "info_tecnica"){
							header("Location: admin/menu_tecnica.php");
						}elseif ($acesso[$i] == "financeiro"){
							header("Location: admin/menu_financeiro.php");
						}elseif ($acesso[$i] == "auditoria"){
							header("Location: admin/menu_auditoria.php");
						}elseif ($acesso[$i] == "*"){
							//header("Location: admin/menu_cadastro.php");
							header("Location: admin/menu_cadastro.php");
						}
						exit;
					}
				}

			}else{
				$msg = "<!--OFFLINE-I-->Login ou senha inválidos !!!<!--OFFLINE-F-->";
			}
			if (strlen ($msg) == 0) {
				$msg = "<!--OFFLINE-I-->Login ou senha inválidos !!!<!--OFFLINE-F-->";
			}
			setcookie ("cook_posto_fabrica");
			setcookie ("cook_admin");
		}else{
			$msg = "<!--OFFLINE-I-->ERRO MESMO!!!<!--OFFLINE-F-->";
		}  
	}else{
		$tempemail = explode("@",$login);
		if(count($tempemail)==2){
		
			$login = trim($HTTP_POST_VARS["login"]);
			$senha = trim($HTTP_POST_VARS["senha"]);


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
*/					header("Location: ../time/index.php");
			}else{
				$msg_erro ="Login ou senha inválidos.";
/*Para sistema de revendas*/
				$login = trim($HTTP_POST_VARS["login"]);
				$senha = trim($HTTP_POST_VARS["senha"]);
				$sql = " SELECT revenda
					FROM tbl_revenda	
					WHERE email = '$login'
					AND   senha = '$senha'";
	
				$res = pg_exec ($con,$sql);
	
				if (pg_numrows ($res) == 1) {
					$imp_sql=$sql;
					$revenda     = pg_result ($res,0,revenda);
	
					setcookie ("cook_revenda",$revenda);
					header("Location: revend/index.php");
				}else{
					$msg_erro ="Login ou senha inválidos.";
				}
			}
		}else{

			$login = trim($HTTP_POST_VARS["login"]);
			$senha = trim($HTTP_POST_VARS["senha"]);
			if($hd=='OK'){
				$login = $hd_login   ;
				$senha = $hd_senha   ;
			}

			$tempsenha = explode("|",$senha);
			if (count($tempsenha)==2){
				$temp_login = $tempsenha[0];
				$temp_senha = $tempsenha[1];
				//IGOR HD 2064  quando no login colocarmos ex: leandro|tectoy direcionar para a Tectoy e não para a Dynacom.
				$templogin = explode("|",$login); //verificar quando o login for diferente para 2 fabricas	
				if (count($templogin)==2){

					$temp_login_login   = $templogin[0];
					$temp_login_fabrica	= $templogin[1];	

					$sql = " SELECT fabrica 
							 FROM tbl_fabrica 
							 WHERE lower(nome )= lower('$temp_login_fabrica');";

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
					WHERE  lower (tbl_posto_fabrica.codigo_posto) = lower ('$xlogin')
					AND    lower (tbl_posto_fabrica.senha) = lower ('$senha')";
			$res = pg_exec ($con,$sql);
			
			#------- TULIO 04/05 - Não usar mais validação de email, até fazer uma tela que preste
			
			if (pg_numrows ($res) == 1) {
				if (pg_result ($res,0,credenciamento) == 'DESCREDENCIADO') {
					$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
				} elseif (pg_result ($res,0,login_provisorio) == 't' AND 1==2 ) {
					$msg = '<!--OFFLINE-I-->Para acessar é necessário realizar a confirmação no email.<!--OFFLINE-F-->';
				}else{
					setcookie ("cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
					setcookie ("cook_posto",pg_result ($res,0,posto));
					setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
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
					//Wellington - Trocar aqui por "if (pg_result($res,0,fabrica)==11)" no dia 04/01 após atualizar os códigos dos postos da tabela tbl_posto_fabrica com os dados da tabela temp_lenoxx_posto_fabrica
					if ( pg_result($res,0,posto)<>6359 and pg_result($res,0,fabrica)<>11 ) {
						setcookie ("cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
						setcookie ("cook_posto",pg_result ($res,0,posto));
						setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
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
				
				setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
				setcookie ("cook_posto_fabrica");
				setcookie ("cook_posto");
				
				$privilegios = pg_result ($res,0,privilegios);
				$acesso = explode(",",$privilegios);

				if($hd=='OK'){
					header("Location: helpdesk/chamado_detalhe.php?hd_chamado=$hd_chamado");
					exit;
				}

	//--=== ADMINS AMÉRICA LATINA ========================RAPHAEL===============--\\
				if($pais<>'BR'){

					header("Location: admin_es/menu_gerencia.php");
					exit;
				}
	//--========================================================================--\\

				for($i=0; $i < count($acesso); $i++){
					if(strlen($acesso[$i]) > 0){
						if ($acesso[$i] == "gerencia"){
							header("Location: admin/menu_gerencia.php");
						}elseif ($acesso[$i] == "call_center"){
							header("Location: admin/menu_callcenter.php");
						}elseif ($acesso[$i] == "cadastros"){
							header("Location: admin/menu_cadastro.php");
						}elseif ($acesso[$i] == "info_tecnica"){
							header("Location: admin/menu_tecnica.php");
						}elseif ($acesso[$i] == "financeiro"){
							header("Location: admin/menu_financeiro.php");
						}elseif ($acesso[$i] == "auditoria"){
							header("Location: admin/menu_auditoria.php");
						}elseif ($acesso[$i] == "*"){
							header("Location: admin/menu_cadastro.php");
						}
						exit;
					}
				}
			}

			if (strlen ($msg) == 0) {
				$msg = "<!--OFFLINE-I-->Login ou senha inválidos !!!<!--OFFLINE-F-->";
			}
			setcookie ("cook_posto_fabrica");
			setcookie ("cook_admin");
		}
	}
}

$title = "Assistência Técnica - Login";

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<HTML>

<HEAD>

	<TITLE> Telecontrol NewLogin </TITLE>
		<META NAME="Generator" CONTENT="EditPlus">
		<META NAME="Author" CONTENT="Marcos Teruo Ouchi - Telecontrol <c>2004">
		<META NAME="Keywords" CONTENT="assistência técnica, website, sistemas, design, elétrica, eletricidade, eletrônica, manutenção">
		<META NAME="Description" CONTENT="Sistema para gerenciamento de Ordens de Serviço para fabricantes de equipamentos eletro-eletrônicos">
		<META NAME="copyright" CONTENT="Message Digital Design Ltd" />
		<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1" />
		<META NAME="copyright" CONTENT="Telecontrol Networking Ltd" />

	<!-- LINK PARA O CSS -->
	<link href="css/basico.css" rel="stylesheet" type="text/css" />
	<link type="text/css" rel="stylesheet" href="css/x_basico.css">

</HEAD>

<BODY onload='javascript: frm_login.login.focus();'>
<table cellpadding='0' cellspacing='0' align='center'>
<tr>
<td>
<!-- ========================== CABECALHO ================================ -->
<? include 'x_cabecalho.php' ?>

<!-- ====================== TITULO DA PÁGINA ============================= -->
<table width="100%" cellpadding='0' cellspacing='0' bgcolor='#FFFFFF'  align='center'>
<tr>
	<td width='100%' align='center'><img src="x_imagens/assist_cabecalho.gif" alt=""></td><!-- 283x44px -->
	<td><img src="x_imagens/idx_imagem_2.jpg" alt=""></td><!-- 375x55px -->
</tr>
</table>


	<DIV id="wrapper">
<!-- 		<a href="index2.php" accesskey="1"><img src="image/logo_telecontrol.gif" id="logo" alt="Vai para a página Principal" /></a><br> -->

		<div id="topNav" class="clear">
			<!-- insira aqui a barra de navegação -->
		</div>
<!-- 		<div>
			<div id="mainBranding">
				<div class="inline"><img src="image/imagem_principal_eletro.jpg" alt="Message team posing against blue sky" width="505" height="150" />
			</div>
		</div> -->



	</div>

	<div id="leftCol">

		<div class="contentBlockLeft">
			<!-- Insira aqui o texto de sua escolha -->
			<img src='imagens/conf_01.gif'><img src='imagens/conf_02.gif' onclick="window.open('configuracao.php','janela','toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=450,height=400,top=18,left=0')" style='cursor: pointer;'><img src='imagens/conf_03.gif' onclick="window.open('configuracao_ns.php','janela','toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=450,height=400,top=18,left=0')" style='cursor: pointer;'>
			<img src='imagens/conf_04.gif' onclick="window.open('configuracao_mozilla.php','janela','toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=450,height=400,top=18,left=0')" style='cursor: pointer;'>
			<img src='imagens/conf_05.gif' onclick="window.open('configuracao_opera.php','janela','toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=450,height=400,top=18,left=0')" style='cursor: pointer;'>


			<h3>Clique no logotipo de seu navegador para saber mais sobre as configurações necessárias.</h3>
		</div>
	</div>

	<div id="middleCol">
<!--	<div class="contentBlockMiddle">
			<CENTER><h1>A V I S O &nbsp;&nbsp; I M P O R T A N T E</h1></CENTER>
			<div align='justify'>
			<font face='verdana, arial' size='2' color='#ff0000'>
				O programa Telecontrol Assist <B>"offline"</B> está em <B>fase final de teste.</B><br>
				Os lançamentos não estão sendo considerados válidos.<br>
				Portanto, é necessário efetuar os lançamentos no sistema <B>"online"</B>.
			</font>
			</div>
		</div>-->

		<div class="contentBlockMiddle">
			<!-- Insira aqui o texto de sua escolha -->
			<IMG SRC="image/tit_md_assistencia_tecnica.gif" ALT="">
			<h3>Aqui os Postos Autorizados podem efetuar o lançamento de Ordens de Serviço em garantia, conferir seu extrato financeiro, visualizar e imprimir vistas explodidas, contatar a empresa através do Fale Conosco, ficar a par de lançamentos de produtos e promoções entre outros recursos de grande utilidade para agilizar todo o processo de controle de Ordens de Serviço.</h3>
		</div>
		<div class="contentBlockMiddle">

			<!-- Insira aqui o texto de sua escolha -->
			<a href="http://www.telecontrol.com.br"><img src="image/parceiro.jpg" alt=""></a>
			<h3>A Telecontrol desenvolve sistemas totalmente destinados à Internet, com isto você tem acesso às informações de sua empresa de qualquer lugar, podendo tomar decisões gerenciais com total segurança. </h3>


		</div>
	</div>

	<!-- NOVO ACESSO -->
	<div id="rightCol">

		<div class="contentBlockRight">
			<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_login" method="post" action="<? $PHP_SELF ?>">
				<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b>Login</b></font><br>
				<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b><input class="frm" type="text" name="login" maxlength="50" value="<? echo $login ?>"></b></font><br>
				<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b>Senha</b></font><br>
				<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b><input class="frm" type="password" name="senha" value="" maxlength="30"></b></font><br><br>
				<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b><input type="submit" name="btnAcao" value="Enviar"></b></font>
				<hr><font face="Verdana, Arial, Helvetica, sans-serif" size="1"><? if (strlen($msg) > 0) { ?><b><font color="#FF0000">
				<? echo $msg ?>
				</font></b></font><? } ?>
			</form>
		</div>

		<? if (strlen($_GET["pa"]) == 0) { ?>

		<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_cnpj" method="post" action='<? echo $PHP_SELF?>'>
			<div class="contentBlockRight">
				<!-- Insira aqui o texto de sua escolha -->
				<img src="imagens/cadastre.gif" alt="">

				<h3>Para obter seu login e criar uma senha de acesso, digite seu CNPJ. <br />Não se esqueça de desabilitar qualquer tipo de ANTI-POP-UP que você tiver. <br />&nbsp;<br /><center><b><input class="frm" type="text" name="cnpj" maxlength="20" value="<? echo $cnpj ?>"><input type="submit" name="btnAcao" value="OK">
				<? if (strlen($msg_erro) > 0) { ?><b><font color="#FF0000"><? echo $msg_erro ?></font></b></font><? } ?>
				</b></center><br />ex.: 01.297.216/0001-11</h3>
			</div>
		</form>

		<? } ?>


	</div>
</td>
</tr>
</table>

	
<!-- ========================== RODAPÉ ============================== -->
<? include 'x_rodape.php' ?>
	</div>
</form>

</BODY>

</HTML>

<!-- Start of StatCounter Code -->
<script type="text/javascript" language="javascript">
var sc_project=1223945; 
var sc_invisible=1; 
var sc_partition=10; 
var sc_security="853989b0"; 
</script>

<script type="text/javascript" language="javascript" src="http://www.statcounter.com/counter/counter.js"></script>
<noscript><a href="http://www.statcounter.com/" target="_blank"><img  src="http://c11.statcounter.com/counter.php?sc_project=1223945&amp;java=0&amp;security=853989b0&amp;invisible=1" alt="counter create hit" border="0"></a></noscript>
<!-- End of StatCounter Code -->

<?
if ($_GET['s'] == 1){
echo "<script> alert('Seus dados de acesso foram enviados para seu e-Mail');</script>";
}
?>
