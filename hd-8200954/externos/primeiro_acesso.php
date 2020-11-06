<?php

header("Location: primeiro_acesso_new.php");
exit;

header("Content-Type: text/html;charset=UTF-8");

$html_titulo = 'Primeiro Acesso';
include('topo_wordpress.php');

if ($_GET['mensagem'] == 'sucesso') {
	$class_mensagem = 'email_sucesso';
	$msg = "<p>Prezado Posto Autorizado, seja bem-vindo!</p>
			<p>Seu acesso foi liberado com sucesso.</p>
			<p><a href='http://www.telecontrol.com.br/'>Clique aqui para acessar nosso <i>site</i>.</a></p>";
}
?>
<link rel="stylesheet" href="css/login_unico_envio_email.css" type="text/css" media="screen" />
<div class="titulo_tela">
	<br />
	<h1><a href="javascript:void(0)" style="cursor:point;">Confirmar Acesso</a></h1>
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

<table width="948" class="barra_topo">
	<tr>
		<td>
			<div id="mensagem_envio" class='<?php echo $class_mensagem;?>'>&nbsp;<?php echo $msg;?></div>
		</td>
	</tr>
</table>

<table style="border:solid 1px #CCCCCC;width: 948px;height:300px;" class="caixa_conteudo">
	<tr>
		<td style="padding: 3em 2em 1ex 2em;height:420px;vertical-align:top">
			<form name="frm" id="frm" method="POST" action="primeiro_acesso_valida.php">
				<ol style="list-style:none;margin-left:0px;text-align: left;">
					<li>Para obter seu login e criar uma senha de acesso, digite seu CNPJ.</li>
					<li>Não se esqueça de desabilitar qualquer tipo de ANTI-POP-UP que você tiver.</li>
				</ol>

				<br><br>
				<table width="850">
					<tr>
						<td width="100x" >
							<label class="cnpj">CNPJ:</label>
						</td>
						<td width="130x">
							<input name="cnpj" id="cnpj" size="16" maxlength="19" value="" type="text" onkeypress="soNumeros(this);"
								 onblur="soNumeros(this);" />
						</td>
						<td width="620x">
							<button name="btnG" value="Gravar" type="button" class="input_gravar" onclick="verifica_primeiro_acesso();">Acessar</button>
						</td>
					</tr>

					<tr>
						<td>&nbsp;</td>
						<td style="text-align: left;">
							<span style="color:#A9A8A8">Ex: 01297216000111</span>
						</td>
						<td>&nbsp;</td>
					</tr>
				</table>
				</p>
			</form>
		</td>
	</tr>
</table>
</div>
<div class="blank_footer">&nbsp;</div>

