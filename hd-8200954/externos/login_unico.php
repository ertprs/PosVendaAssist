<?php

header("Location: login_unico_new.php");
exit;

header("Content-Type:text/html; charset=utf-8");

$arr_host = explode('.', $_SERVER['HTTP_HOST']);
if ($arr_host[0] != "ww2" && $arr_host[0] != "devel" && $arr_host[0] != "elginautomacao") {
	/**
	 * @since HD 878899 - redireciona pro ww2 como solução [temporária] para os problemas de envio de email
	 */
	$uri = preg_replace("/~\w+\//", '','http://ww2.telecontrol.com.br' . $_SERVER['REQUEST_URI']);
	$uri = str_replace('/posvenda/', '/assist/', $uri);
	echo '<meta http-equiv="Refresh" content="0 ; url=' . $uri . '" />';
	//echo '<meta http-equiv="Refresh" content="0 ; url=http://ww2.telecontrol.com.br/assist/externos/login_unico_envio_email.php" />';
	exit;
}

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../helpdesk/mlg_funciones.php';

if (!function_exists('ttext')) {
	include 'trad_site/fn_ttext.php';
}

$login       = trim($_POST["login"]);
$senha       = trim($_POST["senha"]);
$acao_unico  = trim($_POST['acao_unico']);
$cook_idioma = (isset($_COOKIE['idioma']))?$_COOKIE['idioma']:"pt-br";

if(strlen($acao_unico)>0){

	if (strlen($msg) == 0) {
		$login = preg_replace('/(\.|\/|-)/', '', strtolower($login));

		#------------- Pesquisa posto pelo Login ---------------#
		$sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica ,
						tbl_posto_fabrica.posto,
						tbl_posto_fabrica.fabrica,
						tbl_posto_fabrica.credenciamento,
						tbl_posto_fabrica.login_provisorio
				FROM   tbl_posto
				JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE  lower (tbl_posto_fabrica.codigo_posto) = '$login'
				AND    tbl_posto_fabrica.senha		  = '$senha'";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 1) {
			extract(pg_fetch_assoc($res, 0));
			if ($credenciamento == 'DESCREDENCIADO') {
				$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
			} elseif ($login_provisorio == 't' AND 1==2 ) {
				$msg = '<!--OFFLINE-I-->Para acessar é necessário realizar a confirmação no email.<!--OFFLINE-F-->';
			}else{

                $token_cookie = gera_token($fabrica,$posto_fabrica,$posto);
                setcookie("sess",$token_cookie);    
                
                $cookie_login = get_cookie_login($token_cookie);

                add_cookie($cookie_login,'cook_posto_fabrica', $posto_fabrica);
				add_cookie($cookie_login,'cook_posto'        , $posto);
				add_cookie($cookie_login,'cook_fabrica'      , $fabrica);
				add_cookie($cookie_login,'cook_login_unico'  , 'temporario');

				set_cookie_login($token_cookie,$cookie_login);

				// unset($_COOKIE);
				// setcookie ('cook_posto_fabrica', $posto_fabrica, null, '/');
				// setcookie ('cook_posto'        , $posto,		 null, '/');
				// setcookie ('cook_fabrica'      , $fabrica,		 null, '/');
				// setcookie ('cook_login_unico'  , 'temporario',	 null, '/');
				$posto = md5($posto);
				$fabrica = md5($fabrica);
				header ("Location: ../login_unico_cadastro.php?lu=temp&fabrica=$fabrica&posto=$posto");
				exit;
			}
		}

		#------------- Pesquisa posto pelo CNPJ ---------------#
		$sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica,
						tbl_posto_fabrica.posto,
						tbl_posto_fabrica.fabrica ,
						tbl_posto_fabrica.credenciamento
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_posto.posto
				WHERE tbl_posto.cnpj                 = '$login'
				AND   tbl_posto_fabrica.senha = '$senha'";
		$res = pg_query ($con,$sql);

		if (pg_num_rows ($res) == 1) {
			extract(pg_fetch_assoc($res, 0));
			if ($credenciamento == 'DESCREDENCIADO') {
				$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
			}else{
				//Wellington - Trocar aqui por "if (pg_fetch_result($res,0,fabrica)==11)" no dia 04/01 após atualizar os códigos dos postos da tabela tbl_posto_fabrica com os dados da tabela temp_lenoxx_posto_fabrica
				//if ($posto <> 6359 and $fabrica)<>11) {

				setcookie ('cook_posto_fabrica', $posto_fabrica, null, '/');
				setcookie ('cook_posto' ,		 $posto,		 null, '/');
				setcookie ('cook_fabrica' ,		 $fabrica,		 null, '/');
				setcookie ('cook_login_unico' ,	 'temporario',	 null, '/');
				header ("Location: ../login_unico_cadastro.php");
				exit;
			/*	}else{
					$sql = "SELECT codigo_posto
							FROM   tbl_posto_fabrica
							WHERE  posto   = $posto
							AND    fabrica = $fabrica";
					$res = pg_query ($con,$sql);
					$novo_login = pg_fetch_result($res,0,0);
					$msg = '<!--OFFLINE-I--> Seu login mudou para <font size=3px><B>'.$novo_login.'</B></font>, utilize este novo login para acessar o sistema. <!--OFFLINE-F-->';
				}*/
			}
		}
		$msg= "<label class='erro_campos_obrigatorios'>".ttext($a_trad_LU,'login_invalido')."</label>";
	}
}

if(md5($_GET["id"])==$_GET["key1"]){
	$lu_id = $_GET["id"];
	$sql = "UPDATE tbl_login_unico SET email_autenticado = CURRENT_TIMESTAMP WHERE login_unico = $lu_id";
	$res = pg_query($con, $sql);

	if(pg_affected_rows($res) == 1) {
		$msg      = "<label class='email_sucesso'>".ttext($a_trad_LU, "parabens")."OK</label>\n";
		$validaOK = true;
	} else {
	    echo "<div class='erro' id='mensagem'>".ttext($a_trad_LU, "erro_gravar_auth").
			 "<a href='mailto:suporte@telecontrol.com.br'>".ttext($a_trad_header, "Suporte")."</a>.</div>\n";
   }
}

$html_titulo = ttext($a_trad_LU, 'login_unico');
include('topo_wordpress.php');

?>
<script type="text/javascript" language="JavaScript">
    function abreManualLU() {
			var xx=480;
			var y=10;
			var x=window.screen.availWidth;
			var yy=window.screen.availHeight;
				x=(parseInt(x)/2)-(xx/2);// Calcula a posição do centro horiz. da janela
				yy=(parseInt(yy) - 40);
				y=(parseInt(window.screen.availHeight) - yy)/2;
			var winopts="toolbar=0,status=1,menubar=0,resizable=1,";
			    winopts=winopts+"scrollbars=1,width="+xx+",height="+yy+",top="+y+",left="+x;
	window.open("lu_man.php","_blank",winopts);
	}
	jQuery().ready(function ($) {
		$('#mensagem').click(function () {$(this).hide('fast');});
	});
</script>
<div class="titulo_tela">
	<br />
	<h1><a href="javascript:void(0)" style="cursor:point;">Login Único</a></h1>
</div>

<div class="div_top_principal">
	<table width="950" style="text-align: right;">
		<tr>
			<td>
				*Campos obrigat&oacute;rios.
			</td>
		<tr>
	</table>
</div>

<table style="width:948px" class="barra_topo">
	<tr>
		<td>
			<div id="mensagem_envio">&nbsp;<?php echo $msg;?></div>
		</td>
	<tr>
</table>
<table style="border:solid 1px #CCCCCC;width: 948px;height:300px;" class="caixa_conteudo">
	<tr>
		<td>
			<div id="conteiner">
				<div id="conteudo" style='padding-left: 2em'>
					<br>
<?	if ($validaOK) {	// Mostra apenas a mensagem de validação OK
	echo "<h3>".ttext($a_trad_LU, "parabens")."</h3></div></div></td></tr></table>";
	//include "rodape_wordpress.php";
	echo "</div></body></html>\n";
	exit;
}?>
				<h3>Com o Login Único, você tem várias vantagens</h3>
					<ul style="list-style:disc inside;margin-left:5px;">
						<li>Usando seu próprio e-mail e uma única senha, terá acesso a todas as fábricas em que trabalha</li>
						<li>Poderá restringir o acesso de seus funcionários a áreas específicas do site</li>
						<li>Poderá consultar o andamento de seus pedidos de peças (compra ou garantia), independente do fabricante</li>
						<li>Poderá consultar suas OS em aberto, filtrando por status ou fabricante</li>
					</ul>
					<p>
						&nbsp;Comece agora: Use o usuário e senha de algum de seus fabricantes, e crie seu <b>Login Principal</b>.
					</p>

					<p>
					&nbsp;Depois, crie os logins de seus funcionários, e determine suas áreas de acesso.
					<form name="login_unico" id="lu" method="POST" action="login_unico.php">
						<br />
						<fieldset>
						<legend>Cadastre-se</legend>
						<table width="550px">
							<tr>
								<td width="200px">
									<input type="hidden" name="acao_unico" value="ok">
									<label class="login_fabricante" style="width:200px">Login de um dos Fabricantes&nbsp;*&nbsp;</label>
								</td>
								<td width="350px">
									<input name="login" id="campo_login" size="20" maxlength="50" value="" type="text" />
								</td>
							</tr>

							<tr>
								<td>
									<label class="login_senha" style="width:200px">Senha deste fabricante&nbsp;*&nbsp;</label>
								</td>
								<td>
									<input type="password" name="senha" id="campo_senha" size="20" />
								</td>
							</tr>
							<?php
								if($msg==1) {
?>
							<tr>
								<td>&nbsp;</td>
								<td>
									<span>
									<?php
										echo $msg;
									?>
									&nbsp;</span>
								</td>
							</tr>
							<?php
								}
							?>
							<tr>
								<td>&nbsp;</td>
								<td>
									<button type="button" name="acao" value="Acessar" class="input_gravar" onclick="verifica_login_unico('');">Acessar</button>
								</td>
							</tr>

						</table>
						</fieldset>
						<p>&nbsp;</p>
							&nbsp;Não recebeu o email de confirmação?&nbsp;
						<a href="login_unico_envio_email.php" style="font-weight:bold">Clique aqui</a>
						<p>&nbsp;</p>
						<a id="manual" href="javascript:abreManualLU();" style="text-decoration: blink;">
							&nbsp;Clique para abrir passo a passo<img src="img/ext.gif"></a><br>
					</form>
				</div>
			</div>
		</td>
	</tr>
</table>
</div>
<div class="blank_footer">&nbsp;</div>

