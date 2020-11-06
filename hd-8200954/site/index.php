<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';

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
 $login = trim($HTTP_POST_VARS["login"]);
$senha = trim($HTTP_POST_VARS["senha"]);


if (trim($HTTP_POST_VARS["btnAcao"]) == "Enviar"  OR $hd=="OK") {
	$login = trim($HTTP_POST_VARS["login"]);
	$senha = trim($HTTP_POST_VARS["senha"]);
	if($hd=='OK'){
		$login = $hd_login   ;
		$senha = $hd_senha   ;
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
		
		if (pg_numrows ($res) == 1) {
			if (pg_result ($res,0,credenciamento) == 'DESCREDENCIADO') {
				$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
			} elseif (pg_result ($res,0,login_provisorio) == 't') {
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
				} else {
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

?>

<html>
<head>
	<title>telecontrol</title>
	<link rel="stylesheet" href="estilos.css" type="text/css">
	<script language="javascript" src="flash.js"></script>
</head>

<body>
<div align="center">

<!-- TOP ..::..TOP ..::..TOP ..::..TOP ..::..TOP ..::..TOP ..::..TOP ..::..TOP ..::..TOP ..::..TOP ..::..TOP ..::..TOP ..::.. -->
<table cellpadding="0" cellspacing="0" width="776">
	<tr><td height="1"></td></tr>
	<tr>
		<td class="top" valign="top" height="130">
		<table width="719" align="center" cellpadding="0" cellspacing="0">
			<tr>
				<td width="519">
				<script language="javascript">logo();</script>
				</td>
				<td>
			<!-- caixa login .::. caixa login .::. caixa login .::. caixa login .::. caixa login .::. caixa login .::. caixa login .::. caixa login .::. -->
				
				<table cellpadding="0" cellspacing="0" width="229">
					<tr><td><img src="imgs/caixa_l1.gif" alt="" width="229" height="21" border="0"><br></td></tr>
					<tr>
						<td bgcolor="#ffffff">
						<table align="center" width="208" cellpadding="0" cellspacing="0">
							<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_login" method="post" action="<? $PHP_SELF ?>">
							<tr>
								<td>
								Usuário:
								<input type="Text" name="login" class="in" style="width: 95px">
								</td>
								<td>
								Senha:
								<input type="password" name="senha" class="in" style="width: 57px">
								</td>
								<td valign="bottom">
								<input type = "hidden"  name="btnAcao" value="Enviar">
								<input type="Image" src="imgs/entrar.gif" onclick="javascript: document.frm_login.submit(); ">
								</td>
							</tr>
							<tr>
								<td class="erro" colspan="2">
								<?if(strlen($msg)>0) echo $msg;?>
								</td>
							</tr>
							</form>
						</table>
						</td>
					</tr>
					<tr><td><img src="imgs/caixa_l2.gif" alt="" width="229" height="7" border="0"><br></td></tr>
				</table>
			<!--^caixa login .::. caixa login .::. caixa login .::. caixa login .::. caixa login .::. caixa login .::. caixa login .::. caixa login .::. -->
				</td>
			</tr>
			<tr><td height="10"></td></tr>
			<tr>
				<td colspan="2">
				<table bgcolor="003C68" width="719" cellpadding="0" cellspacing="0">
					<tr>
						<td height="26">
						<script language="javascript">menu();</script>
						</td>
					</tr>
				</table>
				</td>
			</tr>
		</table>
		</td>
	</tr>
	<tr><td height="10"></td></tr>
</table>
<!--^TOP ..::..TOP ..::..TOP ..::..TOP ..::..TOP ..::..TOP ..::..TOP ..::..TOP ..::..TOP ..::..TOP ..::..TOP ..::..TOP ..::.. -->
<table cellpadding="0" cellspacing="0" width="719">
	<tr>
		<td width="208" bgcolor="#E3E6E9" valign="top">
	<!-- lado menu :: lado menu :: lado menu :: lado menu :: lado menu :: lado menu :: lado menu :: lado menu :: lado menu :: lado menu :: -->
		<table align="center" width="194" cellpadding="0" cellspacing="0">
			<tr><td height="5"></td></tr>
			
			<tr>
				<td>
				<img src="imgs/primeiro_acesso.gif" alt="" width="82" height="29" border="0">
				<br>
				</td>
			</tr>
			<tr>
				<td class="tdmen">
				Para obter seu login e criar uma senha de acesso, digite seu CNPJ.Não se esqueça de desabilitar qualquer tipo de ANTI-POP-UP que você tiver. 
				</td>
			</tr>
			<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_cnpj" method="post" action='<? echo $PHP_SELF?>'>
				<tr>
					<td align="center">
					<input type="Text" name="cnpj" class="in2" style="width: 162px">
					<input type="Image" src="imgs/btok.gif" align="absmiddle" name="btnAcao" value="OK" onclick="javascript:document.frm_cnpj.submit();"><br>
					ex.: 01.297.216/0001-11
					<br><br>
					</td>
				</tr>
			</form>
			
			<tr>
				<td class="tdmen" bgcolor="#F6F7F8" height="51">
				<a class="menu" href="">Sistema de Gerenciamento de Assistência Técnica</a>
				</td>
			</tr>
			<tr><td height="5"></td></tr>
			<tr>
				<td class="tdmen" bgcolor="#F6F7F8" height="51">
				<a class="menu" href="">Sistema de Força de Vendas</a>
				</td>
			</tr>
			<tr><td height="5"></td></tr>
			<tr>
				<td class="tdmen" bgcolor="#F6F7F8" height="51">
				<a class="menu" href="">Assessoria no credenciamento da rede autorizada</a>
				</td>
			</tr>
			<tr><td height="5"></td></tr>
			<tr>
				<td class="tdmen" bgcolor="#F6F7F8" height="51">
				<a class="menu" href="">Centro de Reparos Avançados</a>
				</td>
			</tr>
			<tr><td height="5"></td></tr>
			<tr>
				<td class="tdmen" bgcolor="#F6F7F8" height="51">
				<a class="menu" href="">Logística e Distribuição de Peças de Reposição</a>
				</td>
			</tr>
			<tr><td height="5"></td></tr>
			<tr>
				<td class="tdmen" bgcolor="#F6F7F8" height="51">
				<a class="menu" href="">Análise prévia das Ordens de Serviço</a>
				</td>
			</tr>
			<tr><td height="5"></td></tr>
			<tr>
				<td class="tdmen" bgcolor="#F6F7F8" height="51">
				<a class="menu" href="">Reparos em estoques de revendas</a>
				</td>
			</tr>
			
			<tr><td height="5"></td></tr>
			<tr>
				<td>
				<img src="imgs/h_news.gif" alt="" width="53" height="18" border="0">
				<br>
				<strong class="td3">29.01.2007</strong>
				<br><br>
				<a class="link1" href=""></a>
				</td>
			</tr>
		</table>
	<!--^lado menu :: lado menu :: lado menu :: lado menu :: lado menu :: lado menu :: lado menu :: lado menu :: lado menu :: lado menu :: -->
		</td>
		<td width="5"></td>
		<td width="506" valign="top">
		
<!-- CONTEUDO *************************************************************************************************************************************** -->
				<?
			switch($_GET["op"]){
			
					
				case "empresa":
					include_once("empresa.php");
					break;
					
				case "solucoes":
					include_once("solucoes.php");
					break;
					
				case "cases":
					include_once("cases.php");
					break;
				
				case "servicos":
					include_once("servicos.php");
					break;
					
				case "atendimento":
					include_once("atendimento.php");
					break;
					
				default:
					include_once("home.php");
			}
		?>
<!--^CONTEUDO *************************************************************************************************************************************** -->
				
		</td>
	</tr>
<!-- rodapé .::. rodapé .::.rodapé .::.rodapé .::.rodapé .::.rodapé .::.rodapé .::.rodapé .::.rodapé .::.rodapé .::.rodapé .::.rodapé .::.rodapé .::.-->
	<tr><td colspan="3" height="15"></td></tr>
	<tr>
		<td class="rodape" colspan="3" height="82">
			<table width="100%" align="center">
				<tr>
					<td style="padding-left: 30px">
					<a class="link1" href="">Suporte técnico</a>  |  <a class="link1" href="">Privacidade</a>  |  <a class="link1" href="">Atentimento Comercial</a>  |  <a class="link1" href="">Utilizando o telecontrol.com.br</a><br>
					Perguntas ou comentários sobre o site? Entre em contato com webmaster@telecontrol.com.br
					</td>
					<td class="td2" align="center">
					<img src="imgs/logobase.gif" alt="" width="152" height="34" border="0"><br>
					www.telecontrol.com.br
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr><td bgcolor="#326F9C" colspan="3" height="8"></td></tr>
<!--^rodapé .::. rodapé .::.rodapé .::.rodapé .::.rodapé .::.rodapé .::.rodapé .::.rodapé .::.rodapé .::.rodapé .::.rodapé .::.rodapé .::.rodapé .::.-->
</table>
</div>
</body>
</html>
